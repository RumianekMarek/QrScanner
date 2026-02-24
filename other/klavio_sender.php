<?php

// Get klavio passes from database
function get_klavio_data() {
    global $wpdb;
    $klavio_returner = array();
    $table_name = $wpdb->prefix . 'custom_klavio_setup';

    $klavio_pre = $wpdb->prepare(
        "SELECT * FROM $table_name"
    );

    $klavio_data = $wpdb->get_results($klavio_pre);

    foreach($klavio_data as $key){
        $klavio_returner[$key->klavio_list_name] = $key->klavio_list_id;
    } 
    
    return $klavio_returner;
}

// Sending data from Gravity form to klavio
function klavio_sender($entry, $form){  
    exit;
    // Check if form is one of the registration forms
    $pattern = '/^\(\s*20\d{2}\s*\)\s?Rejestracja (PL|EN)(\s*\(header(?:\s*new)?\))?(\s*\(Branzowe\))?(\s*\(FB\))?$/';
    if (!preg_match($pattern, $form['title'])){
        return;
    }

    // Connecting wordpress functions
    $new_url = str_replace('private_html','public_html',$_SERVER["DOCUMENT_ROOT"]) .'/wp-load.php';
    if (file_exists($new_url)) {
        require_once($new_url);
        $klavio_db = get_klavio_data();
    }

    
    $dom_name = explode('.', do_shortcode('[trade_fair_domainadress]'));
    $dom_id_nolanguage = $dom_name[0];

    if (strpos(strtolower($form['title']), 'pl') !== false ) {
        $klavio_list_id = $klavio_db['klavio_list_pl'];
        $dom_id = $dom_name[0] . '_pl';
    } else {
        $klavio_list_id = $klavio_db['klavio_list_en'];
        $dom_id = $dom_name[0] . '_en';
    }

    foreach($form['fields'] as $field){
        if(strpos(strtolower($field['label']), 'email') !== false || strpos(strtolower($field['label']), 'e-mail') !== false){
            $email_id = $field['id'];
        } 
        if(strpos(strtolower($field['label']), 'tele') !== false || strpos(strtolower($field['label']), 'phone') !== false){
            $phone_id = $field['id'];
        }
        if(strpos(strtolower($field['label']), 'utm') !== false){
            $utm_id = $field['id'];
        }
    }

    // getting qr_code url
    $qr_feeds = GFAPI::get_feeds(NULL, $form['id']);
    foreach ($qr_feeds as $feed) {
        $qr_code_url = gform_get_meta($entry['id'], 'qr-code_feed_' . $feed['id'] . '_url');
        if ($qr_code_url) {
            $qr_code_id = $feed['id'];
            break;
        }
    }

    if(isset($email_id)){
        $email = rgar($entry, $email_id);
    } else {
        $email = '';
    }
    if(isset($phone_id)){
        $phone = rgar($entry, $phone_id);
    } else {
        $phone = '';
    }
    if(isset($utm_id)){
        $utm = rgar($entry, $utm_id);
    } else {
        if (strpos($form['title'], '(FB)') !== false){
            $utm = 'utm_source=facebook';
        } else {
            $utm = '';
        }
    }

    $email_array = explode('@', $email);
    $name = $email_array[0];

    $data = [
        "data" => [
            "type" => "profile-bulk-import-job",
            "attributes" => [
                "profiles" => [
                    "data" => [
                        [
                            "type" => "profile",
                            "attributes" => [
                                "email" => $email,
                                "first_name" => $name,
								"properties" => [
                                    "utm_" . $dom_id => $utm,
                                    "phone_" . $dom_id => $phone,
									"qr_code_" . $dom_id => $qr_code_url,
                                    "domena_" . $dom_id_nolanguage => do_shortcode('[trade_fair_domainadress]'),
                                    "entry_id_" . $dom_id => $entry['id'],
                                    "consent_" . $dom_id => "true",
                                    //"consent_data" . $dom_id => date("d/m/Y/T"),
								]
                            ]
                        ]
                    ]
                ]
            ],
           "relationships" => [
                "lists" => [
                    "data" => [
                        [
                            "type" => "list",
                            "id" => $klavio_list_id,
                        ]
                    ]
				]
			]	
        ]
    ];

    $args = [
        'body'        => wp_json_encode($data),
        'headers'     => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Klaviyo-API-Key '. $klavio_db['klavio_pkey'],
            'Accept' => 'application/json',
            'Revision' => '2024-07-15'
        ],
        'method'      => 'POST',
        'data_format' => 'body',
    ];

    $response = wp_remote_post('https://a.klaviyo.com/api/profile-bulk-import-jobs/', $args);
    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        error_log("Something went wrong: $error_message");
    } else {
        $message_to_log = json_decode($response['body'], true);
        if( $message_to_log['errors'][0]['status'] != null){
            error_log('Klaviyo error status:' . $message_to_log['errors'][0]['status']);
        } else {
            error_log('Profile successfully added to Klaviyo');
        }
    }
}