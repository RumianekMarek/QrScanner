<?php
// Funkcja przetwarzająca pola formularza na zunifikowane pola do hubspota
function get_forms_fields ($form_id){
    $single_form = GFAPI::get_form($form_id);
    $form_fields = null;

    // Sprawdzenie czy formularz nie powinien być wykluczany za zakazane nazwy
    if(stripos($single_form['title'], '_part_') !== false){
        $form_fields['part'] = 'true';
        return $form_fields;
    }
    
    // Sprawdzenie czy formularz nie powinien być wykluczany z powodu przebuywania w koszu
    if (filter_var($single_form["is_trash"], FILTER_VALIDATE_BOOLEAN)) {
        $form_fields['is_trash'] = 'true';
        return $form_fields;
    }

    $form_fields['title'] = $single_form['title'];

    //sprawdzam wszystkie potrzebne pola z formularza i dodaje do tablicy zunifikowanych pul do hubspota
    foreach($single_form['fields'] as $field_index => $single_field){
        $label = strtolower($single_field['label']);
        $admin_label = strtolower($single_field['adminLabel']);

        if (empty(trim($label))){
            continue;
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

            case $admin_label == 'fair_stamd' || stripos($label, 'stanowisko') !== false:
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

// Wyszukanie wpisów w formularzach które nia posiadaja meta danych dodanie do hubspota
function pwe_get_meta_withaut_key(string $key, int $genCounter = 20) {
    global $wpdb;

    // bezpieczne przygotowanie zapytania
    $sql = $wpdb->prepare(
        "
        SELECT e.id, e.form_id
        FROM {$wpdb->prefix}gf_entry e
        LEFT JOIN {$wpdb->prefix}gf_entry_meta em
        ON e.id = em.entry_id AND em.meta_key = %s
        WHERE em.meta_value IS NULL
        ",
        $key
    );

    return $wpdb->get_results($sql);
}

$accessToken = PWECommonFunctions::get_database_meta_data('hubspot_marketing_production');

$total = 0;
$updateCount = $skipedTrash = 0;

$allContacts = null;
$hubspot_search_message = null;

$searched_ids = array();
$all_forms_fields = array();
$all_forms_qr_feed = array();
$all_entries_to_add = array();

$domain = do_shortcode('[trade_fair_domainadress]');
$rok_edycji = do_shortcode('[trade_fair_catalog_year]');
$badge = do_shortcode('[trade_fair_badge]');

$all_entries = pwe_get_meta_withaut_key('hubspot_sender');

$todayNow = new DateTime('now');
$nowTimestamp = $todayNow->getTimestamp() * 1000;


$entries_ids = array_map(function($entry) {
    return $entry->id;
}, $all_entries);

if(empty($entries_ids)){
    echo 'nie znaleziono wpisów nie istniejących jeszcze w hubspot<br>';
    exit;
} else {
    echo 'znaleziono ' . count($entries_ids) . ' wpisów potencjalnie nie istniejących jeszcze w hubspot<br>';
}

$chunks = array_chunk($entries_ids, 100);

foreach ($chunks as $chunk) {
    if($total > 9900){
        $hubspot_search_message = 'przekroczono limit szukania elementów w hubspot, <strong>wykonaj operacje ponownie</strong><br>';
        break;
    }

    $total += count($chunk);

    $payload = [
        "filterGroups" => [
            [
                "filters" => [
                    [
                        "propertyName" => "id",
                        "operator" => "IN",
                        "values" => $chunk
                    ],
                    [
                        "propertyName" => "badge",
                        "operator" => "EQ",
                        "value" => $badge
                    ],
                ]
            ]
        ],
        "properties" => ["id"],
        "limit" => 100
    ];

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

    foreach ($data['results'] ?? [] as $contact) {
        $key = array_search($contact['properties']['id'], $chunk);
        if ($key !== false) {
            unset($chunk[$key]);
        }
        $allContacts[] = $contact['properties']['id'];
    }

    $searched_ids = array_merge($searched_ids, $chunk);
}

echo (!empty($allContacts) ? count($allContacts) :  0 ) . ' wpisów już istnieje w hubspot<br>' . $hubspot_search_message;

foreach($allContacts as $contact){
    gform_update_meta($contact, 'hubspot_sender', 'true');
}

if(empty($searched_ids)){
    exit;
};

foreach ($searched_ids as $single_ids) {
    
    $updatedEntries = array();
    $entry = GFAPI::get_entry($single_ids);

    if (empty($entry['form_id'])){
        continue;
    }

    if (empty($all_forms_fields[$entry['form_id']])) {
        $all_forms_fields[$entry['form_id']] = get_forms_fields($entry['form_id']);
    }

    if (empty($all_forms_fields[$entry['form_id']]) || 
        (isset($all_forms_fields[$entry['form_id']]['is_trash']) && $all_forms_fields[$entry['form_id']]['is_trash'] == 'true') || 
        (isset($all_forms_fields[$entry['form_id']]['part']) && $all_forms_fields[$entry['form_id']]['part'] == 'true')) {
            $skipedTrash++;
            gform_update_meta($entry['id'], 'hubspot_sender', 'form_excluded');
            continue;
    }

    if ($entry['form_id'] && empty($all_forms_qr_feed[$entry['form_id']])) {
        $all_forms_qr_feed[$entry['form_id']]['$qr_feed'] = GFAPI::get_feeds(NULL, $entry['form_id'], 'qr-code');
    }
    
    $entry_data = array();

    if(!is_wp_error($all_forms_qr_feed[$entry['form_id']]['$qr_feed'])){
        $feed = $all_forms_qr_feed[$entry['form_id']]['$qr_feed'][0];
        $entry_data['qrcode_url'] = gform_get_meta($entry['id'], 'qr-code_feed_' . $feed['id'] . '_url');
        $entry_data['qrcode'] = $feed['meta']['qrcodeFields'][0]['custom_key'] . $entry['id'] . $feed['meta']['qrcodeFields'][1]['custom_key'] . $entry['id'];
        $entry_data['update_time '] = $nowTimestamp;
        $entry_data['ticket_url '] = 'https://' . $domain . '/wp-content/uploads/tickets/' . $entry_data['qrcode'] . '.jpg';
    }

    $entry_data['id'] = $entry['id'];
    $entry_data['badge'] = $badge;
    $entry_data['form_id'] = $entry['form_id'];
    $entry_data['date_created'] = isset($entry['date_created']) ? strtotime($entry['date_created']) * 1000 : '';
    $entry_data['rok'] = $rok_edycji;
    $entry_data['source_url'] = $entry['source_url'];

    foreach ($all_forms_fields[$entry['form_id']] as $index => $val){
        switch ($index) {
            case 'title':
                $entry_data['form_title'] = $val;
                break;
                
            case 'is_trash':
                break;
            
            default : 
                $entry_data[$val] = $entry[$index];
                break;
        }
    }

    $updatedEntries[]['properties'] = $entry_data;

    $all_entries_to_add = array_merge($all_entries_to_add, $updatedEntries);
};

$chunks = array_chunk($all_entries_to_add, 100);

foreach ($chunks as $chunk) {
    $payload = json_encode(["inputs" => $chunk]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.hubapi.com/crm/v3/objects/2-145985975/batch/create");
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
        array_map(function($entry){
            gform_update_meta($entry["properties"]['id'], 'hubspot_sender', true);
        }, $chunk);
    } else {
        var_dump($resoult);
    }
}

echo $updateCount . ' elementów zostało dodane do hubspot <br>';
echo $skipedTrash . ' elementów pominiętych z nieobsługiwanych formularzy <br>';