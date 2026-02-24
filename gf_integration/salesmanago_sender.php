<?php
$new_url = str_replace('private_html','public_html',$_SERVER["DOCUMENT_ROOT"]) .'/wp-load.php';
require_once($new_url);

function api_sender($phone, $email, $lang, $url, $channel, $form_name, $rej_send_log_id){
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_klavio_setup';
    
    $passes['secret'] = $wpdb->get_var( 
        $wpdb->prepare(
            "SELECT klavio_list_id FROM $table_name WHERE klavio_list_name = %s",
            'salesmanago_secret'
        )
    );
    $passes['endpoint'] = $wpdb->get_var( 
        $wpdb->prepare(
            "SELECT klavio_list_id FROM $table_name WHERE klavio_list_name = %s",
            'salesmanago_endpoint'
        )
    );
    $passes['client_id'] = $wpdb->get_var( 
        $wpdb->prepare(
            "SELECT klavio_list_id FROM $table_name WHERE klavio_list_name = %s",
            'salesmanago_client_id'
        )
    );
    $passes['sha'] = $wpdb->get_var( 
        $wpdb->prepare(
            "SELECT klavio_list_id FROM $table_name WHERE klavio_list_name = %s",
            'salesmanago_sha'
        )
    );
    
    $endpoint = 'https://' . $passes['endpoint'] . '/api/contact/batchupsertv2';
    $domain_name_array = explode('.', do_shortcode('[trade_fair_domainadress]'));
    array_pop($domain_name_array);
    $domain_name = implode('_', $domain_name_array);
    
    $sels_slug = '';
    if(strpos($url, 'utm_source=platyna') !== false || strpos(strtolower($channel), 'platyna') !== false ){
        $sels_slug = 'PLATYNA_';
    }

    $fair_tag = strtoupper($domain_name . '_BIEZACE_' . $sels_slug . $lang . '_' . do_shortcode('[trade_fair_catalog_year]'));

    $data = [
        "clientId"    => $passes['client_id'],
        "apiKey"      => $passes['secret'],
        "sha"         => $passes['sha'],
        "owner"       => 'aleksandra.kolarczyk@warsawexpo.eu',
    ];
        
    $data['upsertDetails'][] = [
        "contact" => ['email' => $email, 'phone' => $phone],
        "tags" => [$fair_tag],
    ];

    $response = wp_remote_post($endpoint, [
        'body'    => json_encode($data),
        'headers' => ["Content-Type" => "application/json"],
        'method'  => "POST",
    ]);

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (isset($status['success']) && $status['success'] === false) {
        $success_send = false;
        error_log('SALESmanago error response: ' . $body);
    }
    
    if (wp_remote_retrieve_response_code($response) != 200) {
        if (isset($body->message)){
            $response_data['message'] = json_encode($body->message);
        }
        $response_data['status'] = wp_remote_retrieve_response_code($response);
    }

    $response_data['salesmanago'] = wp_remote_retrieve_response_code($response);

    $wpdb->update('rej_send_log', $response_data, [ 'id' => $rej_send_log_id ]);
}