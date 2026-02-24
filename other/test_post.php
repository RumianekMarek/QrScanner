<?php    
$new_url = str_replace('private_html','public_html',$_SERVER["DOCUMENT_ROOT"]) .'/wp-load.php';
require_once($new_url);

$response = wp_remote_post(plugin_dir_url(__FILE__) . 'visitor_activation.php' , [
    'body' => [
        'entry_id' => ' 67286 ',
        'token' => hash_hmac('sha256', $_SERVER['HTTP_HOST'], PWE_API_KEY_4),
    ],
    // 'timeout' => 0.01,
    // 'blocking' => false,
]);

$body = wp_remote_retrieve_body($response);
$status = wp_remote_retrieve_response_code($response);
echo '<pre>';
var_dump($body, 'status - ' . $status);