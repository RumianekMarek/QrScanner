<?php

// Check arfv[1] pass code.
if($argv[1] == 'fM8mYEIEr4HC'){

    // Find path to wp-load.php for wordpress functions.
    if($_SERVER["DOCUMENT_ROOT"] != ""){
        $new_url = str_replace('private_html','public_html',$_SERVER["DOCUMENT_ROOT"]) .'/wp-load.php';
    } else {
        $args_array = explode('wp-content', $argv[0]);
        $new_url = $args_array[0] . '/wp-load.php';
    }
    
    // Check if wp-load.php is in find path.
    if (file_exists($new_url)) {
        require_once($new_url);

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
                    $form_entry = GFAPI::get_entry($results[0]->gf_entry_id);
                    $form = GFAPI::get_form($form_entry['form_id']);

                    foreach($results as $result){

                        $entry = GFAPI::get_entry($result->gf_entry_id);
                        $send = GFAPI::send_notifications($form, $entry);

                        //Changing status in sended invitations,
                        // new -> send.
                        $wpdb->update(
                            $table_name,
                            array('status' => 'send'),
                            array('id' => $result->id),
                            array('%s'),
                            array('%d')
                        );
                    }
                }
            }
        }
    }
}