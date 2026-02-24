<?php    
$new_url = str_replace('private_html','public_html',$_SERVER["DOCUMENT_ROOT"]) .'/wp-load.php';
require_once($new_url);

function handleContactResponse($status_code, $response, $file_path){
    $returner = '';
    if($status_code == 200 || $status_code == 201){
       $returner = $response['id'];
    } elseif ($status_code == 409 && preg_match('/Existing ID:\s*(\d+)/', $response['message'], $matches)) {
        $returner = $matches[1];
    } else {
        $log = 'coś poszło nie tak, status kod -  ' . $status_code . ' odpowiedź - ' . json_encode($response, JSON_UNESCAPED_UNICODE);
        file_put_contents($file_path, 'contact ' . $log . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
    return $returner;
};

function handleCompanyResponse($status_code, $response, $file_path){
    $returner = '';
    if($status_code == 200 || $status_code == 201){
        $returner = $response['id'];
    } elseif ($status_code == 400 && preg_match('/(\d+)\s+already has that value/', $response['message'], $matches)) {
        $returner = $matches[1];
    } else {
        $log = 'coś poszło nie tak, status kod -  ' . $status_code . ' odpowiedź - ' . json_encode($response, JSON_UNESCAPED_UNICODE);
        file_put_contents($file_path, 'company ' . $log . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
    return $returner;
};

function handleDealResponse($status_code, $response, $file_path){
    $returner = '';
    if($status_code == 200 || $status_code == 201){
        $returner = $response['id'];
    } else {
        $log = 'coś poszło nie tak, status kod -  ' . $status_code . ' odpowiedź - ' . json_encode($response, JSON_UNESCAPED_UNICODE);
        file_put_contents($file_path, 'deals ' . $log . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
    return $returner;
};

$raw_json = file_get_contents('php://input');

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

$upload = wp_upload_dir();
$dir = trailingslashit($upload['basedir']) . 'fokus_log';
wp_mkdir_p($dir);

$log_file = $dir . '/focuslog_' . current_time('m-d') . '.txt';
$file_path = $dir . '/fokus_json_' . current_time('m-d') . '.jsonl';
file_put_contents($file_path, $raw_json . PHP_EOL, FILE_APPEND | LOCK_EX);

echo '<pre>';

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

$full_name = explode(' ', ($array_value["Imię i nazwisko"] ?? $array_value["Imię i Nazwisko"]));
$contactSource = $array_value['Źródło kontaktu'] ?? $array_value['Źródło Kontaktu'] ?? '';

$all_data = [
    'contacts' => [
        'properties' => [
            'firstname' => $array_value['Imię'] ?? $full_name[0] ?? '',
            'lastname' => $array_value['Nazwisko'] ?? $full_name[1] ?? '',
            'email' => trim($array_value['Adres email'] ?? $array_value['Email'] ?? ''),
            'phone' => $array['data']['phoneNumber'] ?? trim($array_value['Nowy numer telefonu'] ?? ''),
        ]
    ],
    'companies' => [
        'properties' => [
            'name' => $array_value['Firma'] ?? $array_value['Nazwa Firmy'] ?? 'Brak Nazwy',
            'kraj_firmy' => $array_value['Kraj firmy'] ?? $array_value['Kraj'] ?? 'Polska',
            'nip__sklonowano_' => $array_value['NIP'] ?? $array_value['Numer NIP'] ?? '',
        ]
    ],
    'deals' => [
        'properties' => [
            'dealname' => ($array_value['Firma'] ?? $array_value['Nazwa Firmy'] ?? $array_value['Nazwisko'] ?? $full_name[1] ?? '') . ' - ' . ($array_value['Udział targów'] ?? $array_value['Udział Targów'] ?? ''),
            'udzial_targow' => $array_value['Udział targów'] ?? $array_value['Udział Targów'] ?? '',
            'wartosc_zrodla_k' => $contactSource,
            'kraj_firmy' => $array_value['Kraj firmy'] ?? $array_value['Kraj'] ?? 'Polska',
            'notatka_z_focusa' => isset($array['data']['notes']) ? implode("\n", $array['data']['notes']) : $array_value['Notatka'] ?? '',

            'data_i_godzina_spotkania' => !empty($array_value['Data spotkania']) ? 
                date('Y-m-d\TH:i:s+02:00', strtotime($array_value['Data spotkania'])) : '',

            'id_z_focusa' => $array['data']['userId'] ?? '',
            'rodzaj_umowionego_spotkania___umawiacz__handlowy_' => $hubspotStatus,
            'pipeline' => '1801425088',
            'dealstage' => '2443726065',
            'sent_by_focus' => 'Przesłane z Focusa',
        ]
    ]
];

if(!in_array($contactSource, ['KW Złote', 'Specjalsi', 'Kanał platynowy', 'Stary Lead PL'])){
    $file_test = wp_upload_dir()['basedir'] . '/fokus_log/focus_test_' . date('Y-m-d') . '.json';
    file_put_contents($file_test, "..." . $raw_json . "\n", FILE_APPEND);
    file_put_contents($file_test, ' ' . json_encode($all_data) . "\n\n", FILE_APPEND);
}

if (!in_array($classifier, $allowed_classifiers)) {
    exit;
}

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
    $status_code = wp_remote_retrieve_response_code($response_contact);

    switch ($hub_name) {
        case 'contacts':
            $all_response['contacts'] = handleContactResponse($status_code, $response, $file_path);
            break;

        case 'companies':
            $all_response['companies'] = handleCompanyResponse($status_code, $response, $file_path);
            break;

        case 'deals':
            $all_response['deals'] = handleDealResponse($status_code, $response, $file_path);
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

if (!in_array($classifier, $allowed_classifiers)) {
    exit;
}

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

file_put_contents($file_path, str_repeat('-', 100) . PHP_EOL, FILE_APPEND | LOCK_EX);