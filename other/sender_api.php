<?php

$new_url = str_replace('private_html','public_html',$_SERVER["DOCUMENT_ROOT"]) .'/wp-load.php';
if (file_exists($new_url)) {
    require_once($new_url);
} else {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Plik wp-load.php nie został znaleziony.'
    ]);
    exit;
}

function generateToken($domain) {
    $secret_key = PWE_API_KEY_4;
    
    return hash_hmac('sha256', $domain, $secret_key);
}

if (!empty($_SERVER['Authorization']) && $_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['Authorization'] == generateToken($_SERVER['HTTP_HOST'])) {
    global $wpdb;
    if (isset($_POST['status']) && $_POST['status'] == 'error'){
        $status = 200;
        $stats_count[] = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id,email,phone,form_name,channel,response,status,message
                FROM rej_send_log
                WHERE status != %s OR status IS NULL",
                $status
            ),
            ARRAY_A
        );
    } else if (isset($_POST['status']) && $_POST['status'] == 'success'){
        $status = 200;
        $stats_count[] = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id,email,phone,form_name,channel,response,status,message
                FROM rej_send_log
                WHERE status = %s ",
                $status
            ),
            ARRAY_A
        );
    } else {
        $results = $wpdb->get_results("SELECT * FROM rej_send_log", ARRAY_A);
        
        $stats_count = [
            'platyna' => 0,
            'success' => 0,
            'error' => 0,
        ];

        foreach ($results as $value) {

            $status = $value['status'];

            if ( $value['status'] == 200) {
                $stats_count['success']++;
            } else {
                $stats_count['error']++;
            }
            if(stripos($value['channel'], 'platyna') !== false){
                $stats_count['platyna']++;
            }
        }
    }
    
    echo json_encode($stats_count);
    exit;
} else {
    http_response_code(401);
    echo 'Unauthorized';
    exit;
}