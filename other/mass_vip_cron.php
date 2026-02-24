<?php

$secret = 'fM8mYEIEr4HC';
$pass = null;

// CLI – argument passed from CRON fM8mYEIEr4HC
if (php_sapi_name() === 'cli' && isset($argv[1])) {
    $pass = trim($argv[1]);
}

// HTTP – parameter ?pass=...
elseif (isset($_GET['pass'])) {
    $pass = trim($_GET['pass']);
}

// Verification
if ($pass !== $secret) {

    // Unauthorized access attempt log
    $remote_ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
    error_log('['.date('Y-m-d H:i:s')."] Próba nieautoryzowanego dostępu do mass_vip_cron.php z IP: {$remote_ip}, pass: ".var_export($pass,true));
    
    // Access error
    if (php_sapi_name() === 'cli') {
        echo "Access denied: invalid pass.\n";
        exit(1);
    } else {
        header('HTTP/1.1 403 Forbidden');
        echo 'Access denied.';
        exit;
    }
}

// Załaduj WordPress
if ($_SERVER["DOCUMENT_ROOT"] != "") {
    $wp_load = str_replace('private_html','public_html',$_SERVER["DOCUMENT_ROOT"]) . '/wp-load.php';
} else {
    $args_array = explode('wp-content', __FILE__);
    $wp_load = $args_array[0] . '/wp-load.php';
}
    
// Check if wp-load.php is in find path.
if (file_exists($wp_load)) {
    require_once($wp_load);

    $domain_adress = do_shortcode('[trade_fair_domainadress]');

    add_filter('wp_mail_from', function() use ($domain_adress){
        return 'noreply@' . $domain_adress;
    });

    add_action('phpmailer_init', function($phpmailer) use ($domain_adress) {
        $phpmailer->Sender = 'noreply@' . $domain_adress;
    });
    
    // Check if gravity form class GFAPI is available.
    if (class_exists('GFAPI')) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mass_exhibitors_invite_query';
        $limit_maili = "100";

        // Chech if table exists.
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") == $table_name) {

            // Get data from table in quantity as $limit_mail
            $query = $wpdb->prepare("
                SELECT id, gf_entry_id 
                FROM $table_name 
                WHERE status = %s 
                ORDER BY id ASC 
                LIMIT $limit_maili", 'new');

            $results = $wpdb->get_results($query);

            // Get all positions i queue
            $count_new = $wpdb->get_var( 
                $wpdb->prepare( 
                    "SELECT COUNT(*) FROM $table_name WHERE status = %s", 
                    'new'
                ) 
            );

            // Check if tere are any more invitations to send,
            // Will specify if there is to many invitations added already and allert need to by send.
            if($count_new > 0){
                // Checking today and fair starts date.
                $today_date = new DateTime();
                $fair_start_date = new DateTime(do_shortcode("[trade_fair_datetotimer]"));
                
                $date_diffarance = $today_date->diff($fair_start_date);
                
                // Creating count of invitations that can by send befor the fair starts,
                // Check if fair starting date already past.
                if($date_diffarance->invert == 0){
                    // Create queue count available to send before starting date,
                    // Diffance in days * 24h * 100 emails send every houre,
                    // Substracting one 24h for security.
                    $hours_remaining = ($date_diffarance->days * 24) - 24;
                    $total_email_capacity = $hours_remaining * 100;
                } else {
                    // If fair already starts queue count is 0.
                    $total_email_capacity = 0;
                }

                // Check if queue capasity is already depleated by wiating invitations,
                // Send email with Warning.
                if($total_email_capacity < $count_new){
                    $to = 'marek.rumianek@warsawexpo.eu';
                    $subject = 'Ostrzeżenie: Przekroczono limit wysyłki dla generatora wystawców';
                    $message = sprintf(
                        'Ostrzeżenie: Liczba maili do wysłania przekroczyła maksymalną dozwoloną liczbę na targach ' . do_shortcode('[trade_fair_name]') . 'cap=' . $total_email_capacity . ' new=' . $count_new
                    );
                    $headers = array('Content-Type: text/html; charset=UTF-8');

                    $sent = wp_mail($to, $subject, $message, $headers);
                    error_log('Przekroczenie max liczby maili w generatorze wystawcow, cap=' . $total_email_capacity . ' new=' . $count_new);
                }
            }

            // Check if thera are any invitations imported from database,
            // If Yes sending them.
            if(count($results) > 0){
                foreach($results as $result){
                    $form_entry = GFAPI::get_entry($result->gf_entry_id);

                    if (is_wp_error($form_entry)) {
                        $wpdb->update(
                            $table_name,
                            array('status' => 'error'),
                            array('gf_entry_id' => $result->gf_entry_id),
                            array('%s'),
                            array('%d')
                        );
                        continue;
                    }

                    $form = GFAPI::get_form($form_entry['form_id']);

                    $send = GFAPI::send_notifications($form, $form_entry);

                    //Changing status in sended invitations,
                    // new -> send.
                    $wpdb->update(
                        $table_name,
                        array('status' => 'send'),
                        array('id' => $result->id),
                        array('%s'),
                        array('%d')
                    );

                    if (!empty($result->gf_entry_id)){         
                        wp_remote_post(home_url('wp-content/plugins/custom-element/action_handler.php'), [
                            'body' => [
                                'element' => 'gform_after_submission',
                                'entry_id' => $result->gf_entry_id,
                                'url' => null
                            ],
                            'timeout' => 0.01,
                            'blocking' => false,
                        ]);
                    }
                }
            }
        }


        /** ZAWIESZENE
         * Get all entries form all forms
         * Check for qr-code and create Ticket with it if available and not yet created
         */
        // ob_start();
        //     include_once plugin_dir_path(__DIR__) . 'gf_integration/qrcode_ticket.php';
        //     $hour = (int) current_time('H');
        //     if ( $hour >= 23 || $hour < 6 ) {
        //         $genCounter = 500;
        //     } else {
        //         $genCounter = 50;
        //     }
        //     create_qrcode_tickets ($genCounter);  
        //     if ( $hour == 7 ) {
        //         ticket_count_checker();
        //     }
        // ob_get_clean();
    }

    
    require_once plugin_dir_path(__FILE__) . 'cron_catalog.php';
    
}