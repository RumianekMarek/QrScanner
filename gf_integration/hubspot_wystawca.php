<?php
$new_url = str_replace('private_html','public_html',$_SERVER['DOCUMENT_ROOT']) .'/wp-load.php';
require_once($new_url);

function hubspot_wystwaca_sender($entry_data, $rej_send_log_id){
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_klavio_setup';

    // Logowanie - utworzenie pliku log
    $log_file = __DIR__ . '/hubspot_wystawca.txt';

    $passes['secret'] = PWECommonFunctions::get_database_meta_data('hubspot_fokus');
    
    // Funkcja do formatowania numeru telefonu
    function format_phone_number($phone) {
        if (empty($phone)) return '';
        
        // Usuń wszystkie spacje i nie-cyfrowe znaki oprócz +
        $cleaned = preg_replace('/[^\d+]/', '', $phone);
        
        // Jeśli numer zaczyna się od +48, zwróć go bez zmian
        if (strpos($cleaned, '+48') === 0) {
            return $cleaned;
        }
        
        return $cleaned;
    }

    if (empty($entry_data['email']) || stripos($entry_data['email'], '@warsawexpo.eu') !== false) {
        exit;
    }

    // Sprawdź czy kontakt już istnieje w HubSpot (po emailu)
    $search_url = 'https://api.hubapi.com/crm/v3/objects/2-143551467/search';
    $formatted_phone = format_phone_number($entry_data['telefon'] ?? '');
    
    // Pierwsze wyszukiwanie po emailu
    $search_data = [
        'filterGroups' => [
            [
                'filters' => [
                    [
                        'propertyName' => 'email_',
                        'operator' => 'EQ',
                        'value' => $entry_data['email']
                    ],
                    [
                        'propertyName' => 'nazwa_targow',
                        'operator' => 'EQ',
                        'value' => $entry_data['name']
                    ],
                    [
                        'propertyName' => 'hs_createdate',
                        'operator' => 'GTE',
                        'value' => strtotime('today UTC') * 1000
                    ]
                ]
            ]
        ],
        'limit' => 1
    ];

    $search_response = wp_remote_post($search_url, [
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $passes['secret'],
        ],
        'body' => json_encode($search_data),
        'method' => 'POST',
    ]);

    $existing_contact = null;
    $existing_id = null;
    
    $search_body = json_decode(wp_remote_retrieve_body($search_response), true);
    
    // Przygotuj pełne imię i nazwisko
    $first_name = '';
    $last_name = '';

    if (!empty($entry_data['dane'])){
        $full_name = explode(' ', $entry_data['dane'], 2);
        $first_name = $full_name[0] ?? '';
        $last_name = $full_name[1] ?? '';
    }

    // Przygotuj dane do wysłania/aktualizacji
    $data = [
        'properties' => [
            'nazwa' => $entry_data['email'],
            'nazwa_targow' => $entry_data['name'] ?? '',
            'email_' => $entry_data['email'] ?? '',
            'numer_telefonu' => $formatted_phone ?? '',
            'utm' => $entry_data['utm'] ?? '',
            'imie' => $first_name,
            'nazwisko' => $last_name,
            'nip_' => $entry_data['nip'] ?? '',
            'dodatkowe_inf_o_firmie' => $entry_data['firma'] ?? '',
            'powierzchnia' => $entry_data['0'] ?? '',
            'hs_pipeline' => 1818076346,
            'hs_pipeline_stage' => 2472325352,
        ]
    ];

    // Nie wysyłaj email_ przy aktualizacji (może powodować błędy duplikatów)
    if ($search_body['total'] > 0) {
        unset($data['properties']['email_']);
        $existing_id = $search_body['results'][0]['id'];
        $url = 'https://api.hubapi.com/crm/v3/objects/2-143551467/' . $existing_id;
        $method = 'PATCH';
    } else {
        $url = 'https://api.hubapi.com/crm/v3/objects/2-143551467';
        $method = 'POST';
    }

    $response_contact = wp_remote_post($url, [
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $passes['secret'],
        ],
        'body' => json_encode($data),
        'method' => $method,
    ]);

    $response_body = json_decode(wp_remote_retrieve_body($response_contact), true);
    $http_code = wp_remote_retrieve_response_code($response_contact);

    // Logowanie końcowe
    $success = !empty($response_body['id']);
    $operation_type = $existing_contact ? "UPDATE" : "CREATE";

    // Logi do bazy danych
    if ($http_code > 299) {
        
        file_put_contents($log_file, "===== [ERROR] " . date('Y-m-d H:i:s') . " =====\n" . 
        json_encode($response_body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n", FILE_APPEND);

        if (isset($response_body['message'])){
            $response_data['message'] = json_encode($response_body['message']);
        }
        $response_data['status'] = $http_code;

        error_log('Hubspot error response: ' . json_encode($response_body));
    }

    $response_data['hubspot_wystawca'] = $http_code;

    // $wpdb->update('rej_send_log', $response_data, [ 'id' => $rej_send_log_id ]);
}