<?php 

$new_url = str_replace('private_html','public_html',$_SERVER["DOCUMENT_ROOT"]) .'/wp-load.php';
require_once($new_url);

$headers = apache_request_headers();
$token = isset($headers['Authorization']) ? $headers['Authorization'] : $_POST['token'];

if ($token !== hash_hmac('sha256', $_SERVER['HTTP_HOST'], PWE_API_KEY_4)) {
    http_response_code(403);
    error_log(json_encode(['error' => 'Unauthorized']));
    exit;
}

$entry = GFAPI::get_entry(trim($_POST['entry_id']));

$form = GFAPI::get_form($entry['form_id']);
$email_field = null;
foreach($form['fields'] as $field){
    if($field['type'] == 'email'){
        $email_field = $field['id'];
        break;
    }
}
$qr_feeds = GFAPI::get_feeds(NULL, $entry['form_id']);
foreach ($qr_feeds as $feed) {
    $qr_code_url = gform_get_meta($entry['id'], 'qr-code_feed_' . $feed['id'] . '_url');
    if ($qr_code_url) {
        $qr_code_id = $feed['id'];
        break;
    }
}

$to = $entry[$email_field];
$subject = 'Potwierdzeni aktywacji biletu na ' . do_shortcode('[trade_fair_name]');

$message = file_get_contents(plugin_dir_url(__DIR__) . 'emails/potwierdzenie-aktywacja.php');

$message = str_replace('{qrcode-url-custom}', $qr_code_url, $message);

$headers = [
    'Content-Type: text/html; charset=UTF-8',
    'From:' . do_shortcode('[trade_fair_name]') . ' ' . do_shortcode('[trade_fair_rejestracja]'),
];

$mail_send = wp_mail($to, $subject, do_shortcode($message), $headers);

$table_name = do_shortcode('[trade_fair_catalog_year]') . '_' . do_shortcode('[trade_fair_badge]') . '_visitors_activity';

klavia_data_base_create($table_name);
entry_activation($_POST['entry_id'], $table_name);

if ($mail_send){
    http_response_code(200);
    echo 'User activated successfully.';
} else {
    http_response_code(500);
    echo 'Activation email was not send.';
    error_log(json_encode('Activation email was not send.'));
}



function klavia_data_base_create($table_name) {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();
    

    //Sprawdź, czy tabela istnieje
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name) {
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name", ARRAY_A);
        $existing_columns = array_column($columns, 'Field');
        $required_columns = [
            'update_time' => 'DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL',
            'entry_id' => 'INT NOT NULL UNIQUE',
            'updated_entry' => 'BOOLEAN DEFAULT FALSE NOT NULL',
        ];

        foreach ($required_columns as $column => $definition) {
            if (!in_array($column, $existing_columns)) {
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN $column $definition");
            }
        }
    } else {
        // Tabela nie istnieje, stwórz nową tabelę
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            update_time DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
            entry_id INT NOT NULL UNIQUE,
            updated_entry BOOLEAN DEFAULT FALSE NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta($sql);
    }

    // Sprawdź, czy tabela została poprawnie utworzona
    if ($wpdb->last_error) {
        error_log(json_encode('Database Error: ' . esc_html($wpdb->last_error)));
    } 
}

function entry_activation($entry_id, $table_name){
    global $wpdb;

    $wpdb->query($wpdb->prepare(
        "INSERT IGNORE INTO $table_name (entry_id) VALUES (%d)",
        $entry_id
    ));
    
    if ($wpdb->last_error) {
        error_log(json_encode('Insert failed: ' . esc_html($wpdb->last_error)));
    };
}