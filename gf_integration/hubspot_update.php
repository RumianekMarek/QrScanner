<?php
function get_forms_fields ($form_id){
    $single_form = GFAPI::get_form($form_id);
    $form_fields = null;
    $form_fields['is_active'] = $single_form["is_active"];

    if($form_fields['is_active'] != true){
        return $form_fields;
    }

    $form_fields['title'] = $single_form['title'];

    foreach($single_form['fields'] as $field_index => $single_field){  
        $label = strtolower($single_field['label']);
        $admin_label = strtolower($single_field['adminLabel']);

        if (empty(trim($label))){
            continue;
        }

        foreach($skip_fields as $single_skip){                            
            if (stripos($label, $single_skip) !== false ){
                continue 2;
            }
        }

        switch (true) {
            case $admin_label == 'email' || stripos($label, 'mail') !== false:
                $form_fields[strtolower($single_field['id'])] = 'email';
                continue 2;

            case $admin_label == 'phone' || stripos($label, 'tel') !== false || stripos($label, 'phone') !== false:
                $form_fields[$single_field['id']] = 'telefon';
                continue 2;

            case $admin_label == 'full_name' || stripos($label, 'nazwisk') !== false || stripos($label, 'imie') !== false || stripos($label, 'name') !== false || stripos($label, 'osoba') !== false || stripos($label, 'imię') !== false || stripos($label, 'imiĘ') !== false:
                $form_fields[$single_field['id']] = 'dane';
                continue 2;

            case $admin_label == 'company_name' || stripos($label, 'firm') !== false || stripos($label, 'compa') !== false:
                $form_fields[$single_field['id']] = 'firma';
                continue 2;

            case $admin_label == 'fair_hall' || stripos($label, 'hala') !== false:
                $form_fields[$single_field['id']] = 'hala';
                continue 2;

            case $admin_label == 'fair_stand' || stripos($label, 'stanowisko') !== false:
                $form_fields[$single_field['id']] = 'stand';
                continue 2;

            case $admin_label == 'utm' || stripos($label, 'utm') !== false:
                $form_fields[$single_field['id']] = 'utm';
                continue 2;

            case $admin_label == 'company_nip' || stripos($label, 'nip') !== false || stripos($label, 'tax') !== false:
                $form_fields[$single_field['id']] = 'nip';
                continue 2;
            
            case $admin_label == 'stand_size' || stripos($label, 'powierzchnię') !== false || stripos($label, 'exhibition') !== false || stripos($label, 'area') !== false:
                $form_fields[$single_field['id']] = 'wielkosc_stoiska';
                continue 2;

            case $admin_label == 'activation' || stripos($label, 'active') !== false || stripos($label, 'aktywac') !== false:
                $form_fields[$single_field['id']] = 'aktywacja';
                continue 2;

            case $admin_label == 'language' || stripos($label, 'język') !== false || stripos($label, 'lang') !== false:
                $form_fields[$single_field['id']] = 'jezyk';
                continue 2;

            case $admin_label == 'country' || stripos($label, 'country') !== false:
                $form_fields[$single_field['id']] = 'panstwo';
                continue 2;

            case $admin_label == 'city' || stripos($label, 'city') !== false || stripos($label, 'miasto') !== false:
                $form_fields[$single_field['id']] = 'miasto';
                continue 2;
                
            case $admin_label == 'post' || stripos($label, 'kod') !== false || stripos($label, 'post') !== false || stripos($label, 'code') !== false:
                $form_fields[$single_field['id']] = 'kod_pocztowy';
                continue 2;

            case $admin_label == 'street' || stripos($label, 'ulica') !== false || stripos($label, 'street') !== false || $admin_label == 'addres' || stripos($label, 'adres') !== false :
                $form_fields[$single_field['id']] = 'ulica';
                continue 2;

            case $admin_label == 'house' || stripos($label, 'numer u') !== false || stripos($label, 'numer domu') !== false || stripos($label, 'building') !== false:
                $form_fields[$single_field['id']] = 'numer_domu';
                continue 2;

            case $admin_label == 'apartment' || stripos($label, 'Mieszkan') !== false || stripos($label, 'apartment') !== false || stripos($label, 'lokal') !== false || stripos($label, 'house') !== false:
                $form_fields[$single_field['id']] = 'nr_mieszkania';
                continue 2;
            
            case $admin_label == 'pwe_sender' || stripos($label, 'location') !== false || stripos($label, 'kana') !== false || stripos($label, 'dane wysy') !== false:
                $form_fields[$single_field['id']] = 'pwe_wysylajacy';
                continue 2;

            case $admin_label == 'more_info' || stripos($label, 'more info') !== false:
                $form_fields[$single_field['id']] = 'dodatkowe_informacje';
                continue 2;

            case $admin_label == 'fair_sector' || stripos($label, 'sektor') !== false || stripos($label, 'bran') !== false:
                $form_fields[$single_field['id']] = 'sektory_targowe';
                continue 2;
        }
    }
    return $form_fields;
}
$accessToken = PWECommonFunctions::get_database_meta_data('hubspot_marketing_production');

$badge = do_shortcode('[trade_fair_badge]');
$domain = do_shortcode('[trade_fair_domainadress]');
$allContacts = [];
$hasMore = true;
$after = null;

$todayStart = new DateTime('today');
$todayTimestamp = (int)$todayStart->format('U') * 1000;

$todayNow = new DateTime('now');
$nowTimestamp = $todayNow->getTimestamp() * 1000;

do  {
    $payload = [
        "filterGroups" => [
            [
                "filters" => [
                    [
                        "propertyName" => "badge",
                        "operator" => "EQ",
                        "value" => $badge
                    ],
                    [
                        'propertyName' => 'hs_lastmodifieddate',
                        'operator' => 'LT',
                        'value' => $todayTimestamp
                    ]
                ]
            ]
        ],
        "properties" => ["id", "email", "badge"],
        "limit" => 100
    ];

    if ($after) {
        $payload["after"] = $after;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.hubapi.com/crm/v3/objects/2-145985975/search");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$accessToken}",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    if (!isset($data['results'])) exit;

    foreach ($data['results'] as $contact) {
        $allContacts[] = [
            "id" => $contact['id'],
            "entry_id" => $contact['properties']['id'] ?? '',
            "email" => $contact['properties']['email'] ?? '',
        ];
    }

    $after = $data['paging']['next']['after'] ?? null;
    if ($after && (int)$after >= 9000) {
        $messageMore = 'jest wiecej elementów do updatu powtórz proces.';
        break;
    }
    $hasMore = $after !== null;

} while ($hasMore);

echo ' pobrano ' . count($allContacts) . ' wpisów z Hubspota <br> <strong>' . $messageMore . ' </strong><br>';

if (!empty($allContacts) && count($allContacts) > 0) {
    $all_forms_fields = array();
    $all_forms_qr_feed = array();
    $rok_edycji = do_shortcode('[trade_fair_catalog_year]');

    foreach ($allContacts as $con) {
        $entry = GFAPI::get_entry($con['entry_id']);
        $entry_data = array();

        if(is_wp_error($all_forms_fields) || is_wp_error($entry)){
            $entry_data['update_time '] = $nowTimestamp;
            $entry_data['gravity_status'] = 'deleted';

            $updatedEntries[] = [
                "id" => $con['id'],
                "properties" => $entry_data,
            ];
            continue;
        }

        if ($entry['form_id'] && empty($all_forms_fields[$entry['form_id']])) {
            $all_forms_fields[$entry['form_id']] = get_forms_fields($entry['form_id']);
        }
        
        // if ($entry['form_id'] && empty($all_forms_fields[$entry['form_id']]) && $all_forms_fields[$entry['form_id']['is_active']] != true) {
        //     gform_update_meta($entry["properties"]['id'], 'hubspot_sender', 'true');
        //     continue;
        // }

        if ($entry['form_id'] && empty($all_forms_qr_feed[$entry['form_id']])) {
            $all_forms_qr_feed[$entry['form_id']]['$qr_feed'] = GFAPI::get_feeds(NULL, $entry['form_id'], 'qr-code');
        }
        
        if(!is_wp_error($all_forms_qr_feed[$entry['form_id']]['$qr_feed'])){
            $feed = $all_forms_qr_feed[$entry['form_id']]['$qr_feed'][0];
            $entry_data['qrcode_url'] = gform_get_meta($entry['id'], 'qr-code_feed_' . $feed['id'] . '_url');
            $entry_data['qrcode'] = $feed['meta']['qrcodeFields'][0]['custom_key'] . $entry['id'] . $feed['meta']['qrcodeFields'][1]['custom_key'] . $entry['id'];
            $entry_data['update_time '] = $nowTimestamp;
            $entry_data['ticket_url '] = 'https://' . $domain . '/wp-content/uploads/tickets/' . $entry_data['qrcode'] . '.jpg';
        }

        $entry_data['id'] = $entry['id'];
        $entry_data['form_id'] = $entry['form_id'];
        $entry_data['date_created'] = isset($entry['date_created']) ? strtotime($entry['date_created']) * 1000 : '';
        $entry_data['rok'] = $rok_edycji;
        $entry_data['source_url'] = $entry['source_url'];
        $entry_data['gravity_status'] = $entry['status'];

        foreach ($all_forms_fields[$entry['form_id']] as $index => $val){
            switch ($index) {
                case 'title':
                    $entry_data['form_title'] = $val;
                    break;
                    
                case 'is_active':
                    break;
                
                default : 
                    $entry_data[$val] = $entry[$index];
                    break;
            }
        }

        $updatedEntries[] = [
            "id" => $con['id'],
            "properties" => $entry_data,
        ];
    }
} else {
    exit;
}

if(empty($updatedEntries) || count($updatedEntries) < 0){
    echo 'wszystkie wpisy były już dzisiaj updatowane<br>';
    exit;
}

echo 'znaleziono ' . count($updatedEntries) . ' pobranych wpisów na stronie<br>';

$chunks = array_chunk($updatedEntries, 100);
$updateCount = 0; 

foreach ($chunks as $chunk) {
    $payload = json_encode(["inputs" => $chunk]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.hubapi.com/crm/v3/objects/2-145985975/batch/update");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$accessToken}",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    if($data['status'] == 'error'){
        print_r($data);
    }

    $result = array_reduce($data['errors'] ?? [], function($carry, $error) {
        $carry[] = "Błąd rekordu {$error['id']}: {$error['message']}";
        return $carry;
    }, []);

    if (empty($result)) {
        $updateCount += count($chunk);
    } else {
        var_dump($resoult);
    }
}

echo "update " . $updateCount . " elementów zakończył się sukcesem\n";