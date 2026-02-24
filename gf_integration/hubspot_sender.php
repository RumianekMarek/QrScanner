<?php
$new_url = str_replace('private_html','public_html',$_SERVER['DOCUMENT_ROOT']) .'/wp-load.php';
require_once($new_url);

function hubspot_sender($entry_data, $rej_send_log_id){
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_klavio_setup';
    $domain = do_shortcode('[trade_fair_domainadress]');
    
    $passes['secret'] = PWECommonFunctions::get_database_meta_data('hubspot_marketing_production');
    $url = 'https://api.hubapi.com/crm/v3/objects/2-145985975';

    $data = [
        'properties' => [
            'id' => $entry_data['id'] ?? '',
            'form_id' => $entry_data['form_id'] ?? '',
            'rok' => $entry_data['rok'] ?? '',
            'email' => $entry_data['email'] ?? '',
            'date_created' => isset($entry_data['date_created']) 
                ? strtotime($entry_data['date_created']) * 1000
                : '',

            'form_title' => $entry_data['form_title'] ?? '',
            'source_url' =>  $entry_data['source_url'] ?? '',
            'telefon' =>  $entry_data['telefon'] ?? '',
            'qrcode' =>  $entry_data['qr_code'] ?? '',
            'utm' =>  $entry_data['utm'] ?? '',
            'badge' =>  $entry_data['meta'] ?? '',
            'miasto' => $entry_data['miasto'] ?? '',
            'kod_pocztowy' => $entry_data['kod_pocztowy'] ?? '',
            'ulica' => $entry_data['ulica'] ?? '',
            'numer_domu' => $entry_data['nr_ulicy'] ?? '',
            'nr_mieszkania' => $entry_data['nr_mieszkania'] ?? '',
            'panstwo' => $entry_data['panstwo'] ?? '',
            'dane' => $entry_data['dane'] ?? '',
            'user_agent' => $entry_data['user_agent'] ?? '',
            'firma' => $entry_data['firma'] ?? '',
            'pwe_wysylajacy' => $entry_data['pwe_wysylajacy'] ?? '',
            'qrcode_url' => $entry_data['qr_code_url'] ?? '',
            'dodatkowe_informacje' => $entry_data['dodatkowe_informacje'] ?? '',
            'sektory_targowe' => $entry_data['sektory_targowe'] ?? '',
            'nip' => $entry_data['nip'] ?? '',
            'wielkosc_stoiska' => $entry_data['wielkosc_stoiska'] ?? '',
            'jezyk' => $entry_data['jezyk'] ?? '',
            'ticket_url' => (!empty($entry_data['qr_code'])) ? 'https://' . $domain . '/wp-content/uploads/tickets/' . $entry_data['qr_code'] . '.jpg' : false,

        ]
    ];

    // $response_contact = wp_remote_post($url, [
    //     'headers' => [
    //         'Content-Type' => 'application/json',
    //         'Authorization' => 'Bearer ' . $passes['secret'],
    //     ],
    //     'body' => json_encode($data),
    //     'method' => 'POST',
    // ]);

    // $body = json_decode(wp_remote_retrieve_body($response_contact), true);
    
    // $code = wp_remote_retrieve_response_code($response_contact);
    // if ($code > 299) {
    //     if (isset($body['message'])){
    //         $response_data['message'] = json_encode($body['message']);
    //     }
    //     $response_data['status'] = $code;

    //     error_log('Hubspot error response: ' . $body);
    // } else {
    //     gform_update_meta($entry_data['id'], 'hubspot_sender', 'true');
    // }
    // $response_data['hubspot_marketing'] = $code;
    // $wpdb->update('rej_send_log', $response_data, [ 'id' => $rej_send_log_id ]);



    $response_contact = wp_remote_post('https://hs.warsawexpo.eu/db_setup/db_updater.php', [
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => PWE_API_KEY_5,
        ],
        'body' => json_encode($data['properties']),
        'method' => 'POST',
    ]);
}