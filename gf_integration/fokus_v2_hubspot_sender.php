<?php    
$new_url = str_replace('private_html','public_html',$_SERVER["DOCUMENT_ROOT"]) .'/wp-load.php';
require_once($new_url);

$log_file = __DIR__ . '/focuslog_v2.txt'; 
$raw_json = file_get_contents('php://input');

http_response_code(204);

$upload = wp_upload_dir();
$dir = trailingslashit($upload['basedir']) . 'fokus_log';
wp_mkdir_p($dir);

$file_path = $dir . '/fokus_json_v2_' . current_time('m-d') . '.jsonl';
file_put_contents($file_path, $raw_json . PHP_EOL, FILE_APPEND | LOCK_EX);

exit;
echo '<pre>';

$passes['secret'] = PWECommonFunctions::get_database_meta_data('hubspot_fokus');


$url = 'https://api.hubapi.com/crm/v3/objects/';

$array = json_decode($raw_json, true);
$array_value = $array['data']['values'];

// Sprawdzenie classifier - filtrowanie danych
$allowed_classifiers = [
    'Umówione Spotkanie Stacjonarne',
    'Umówione spotkanie', 
    'Umówione Rozmowa Telefoniczna',
    'Zainteresowany ofertą'
];

$classifier = trim($array['data']['classifier'] ?? '');

if (!in_array($classifier, $allowed_classifiers)) {
    // file_put_contents($log_file, "===== [REJECTED] " . date('Y-m-d H:i:s') . " =====\n" . 
    //     "Classifier: " . $classifier . " nie jest dozwolony. Dozwolone: " . 
    //     implode(', ', $allowed_classifiers) . "\n\n", FILE_APPEND);
    exit;
}

$hubspotStatus = null;
switch($classifier) {
    case'Umówione Spotkanie Stacjonarne':
        $hubspotStatus = 'Spotkanie stacjonarne';
        break;
    case'Umówione spotkanie':
        $hubspotStatus = 'Rezerwacja online';
        break;
    case'Umówione Rozmowa Telefoniczna':
        $hubspotStatus = 'Rezerwacja telefoniczna';
        break;
    case'Zainteresowany ofertą':
        $hubspotStatus = 'Zainteresowany ofertą';
        break;
}

$full_name = explode(' ', $array_value["Imię i nazwisko"]);

$all_data = [
    'contacts' => [
        'properties' => [
            'firstname' => $full_name[0] ?? '',
            'lastname' => $full_name[1] ?? '',
            'email' => trim($array_value['Adres email']) ?? '',
            'phone' => $array['data']['phoneNumber'] ?? '',
        ]
    ],
    'companies' => [
        'properties' => [
            'name' => $array_value['Firma'] ?? '',
            'kraj_firmy' => $array_value['Kraj firmy'] ?? '',
            'nip__sklonowano_' => $array_value['NIP'] ?? '',
        ]
    ],
    'deals' => [
        'properties' => [
            'dealname' => ($array_value['Firma'] ?? '') . ' - ' . ($array_value['Udział targów'] ?? ''),
            'udzial_targow' => $array_value['Udział targów'] ?? '',
            'wartosc_zrodla_k' => $array_value['Źródło kontaktu'] ?? '',
            'kraj_firmy' => $array_value['Kraj firmy'] ?? '',
            'notatka_z_focusa' => isset($array['data']['notes']) ? implode("\n", $array['data']['notes']) : '',

            'data_i_godzina_spotkania' => !empty($array_value['Data spotkania']) ? 
                date('Y-m-d\TH:i:s+02:00', strtotime($array_value['Data spotkania'])) : '',

            'id_z_focusa' => $array['data']['userId'] ?? '',
            'rodzaj_umowionego_spotkania___umawiacz__handlowy_' => $hubspotStatus,
            'pipeline' => '1801425088',
            'dealstage' => '2443726065',
        ]
    ]
];

$all_response = [];

foreach($all_data as $hub_name => $hub_data){
    $response_contact = wp_remote_post($url . $hub_name, [
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $passes['secret'],
        ],
        'body' => json_encode($hub_data),
        'method' => 'POST',
    ]);

    $response = json_decode(wp_remote_retrieve_body($response_contact), true);

    switch (true){
        case !empty($response['id']):
            $all_response[$hub_name] = $response['id'];
            break;
        case !empty($response["category"]) && strtolower($response["category"]) == "conflict":
            $all_response[$hub_name] = trim(explode(':', $response['message'])[1]);
            break;
        case $hub_name == 'companies' && strtolower($response["category"]) == "validation_error" && stripos($response['message'], 'PROPERTY_DOESNT_EXIST') === false:
            // Sprawdzenie czy błąd dotyczy istniejącej firmy z tym samym NIP
            if (stripos($response['message'], 'already has that value') !== false) {
                preg_match('/(\d{10,15})\s+already has that value/', $response['message'], $matches);
                if (!empty($matches[1])) {
                    $all_response[$hub_name] = $matches[1];
                } else {
                    // Jeśli nie udało się wyciągnąć ID, loguj pełny komunikat błędu
                    file_put_contents($log_file, "===== [REGEX FAILED] " . date('Y-m-d H:i:s') . " =====\n" . 
                        "Nie udało się wyciągnąć ID z komunikatu: " . $response['message'] . "\n\n", FILE_APPEND);
                    $all_response[$hub_name] = $response["category"] . ' ' . $response["message"];
                }
            } else {
                // Inne błędy walidacji - szukaj ID w komunikacie
                preg_match_all('/\b\d{12}\b/', $response['message'], $matches);
                $all_response[$hub_name] = !empty($matches[0]) ? min(array_map('intval', $matches[0])) : $response["category"] . ' ' . $response["message"];
            }
            break;
        default:
            $all_response[$hub_name] = $response["category"] . ' ' . $response["message"];
            break;
    }
}

$assoc = [
    "contacts" => [
        "inputs" => [
            [
                "from" => [ "id" => $all_response['companies'] ],
                "to" => [ "id" => $all_response['contacts'] ],
                "type" => "company_to_contact",
            ]
        ]
    ],
    "deals" => [
        "inputs" => [
            [
                "from" => [ "id" => $all_response['companies'] ],
                "to" => [ "id" => $all_response['deals'] ],
                "type" => "company_to_deal",
            ]
        ]
    ],
    "contacts_to_deals" => [
        "inputs" => [
            [
                "from" => [ "id" => $all_response['contacts'] ],
                "to" => [ "id" => $all_response['deals'] ],
                "type" => "contact_to_deal",
            ]
        ]
    ]
];

$response_association = [];

foreach($assoc as $assoc_name => $assoc_data){
    $assoc_url = '';

    switch ($assoc_name) {
        case 'contacts':
            $assoc_url = 'https://api.hubapi.com/crm/v3/associations/companies/contacts/batch/create';
            break;
        case 'deals':
            $assoc_url = 'https://api.hubapi.com/crm/v3/associations/companies/deals/batch/create';
            break;
        case 'contacts_to_deals':
            $assoc_url = 'https://api.hubapi.com/crm/v3/associations/contacts/deals/batch/create';
            break;
    }

    $response_association[$assoc_name] = wp_remote_post($assoc_url, [
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $passes['secret'],
        ],
        'body' => json_encode($assoc_data),
        'method' => 'POST',
    ]);
}