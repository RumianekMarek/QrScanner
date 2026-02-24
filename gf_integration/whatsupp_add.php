<?php

//Finding wp-load.php path
if($_SERVER["DOCUMENT_ROOT"] != ""){
    $new_url = str_replace('private_html','public_html',$_SERVER["DOCUMENT_ROOT"]) .'/wp-load.php';
} else {
    $args_array = explode('wp-content', $argv[0]);
    $new_url = $args_array[0] . '/wp-load.php';
}

// Check if wp-load.php is in find path.
if (file_exists($new_url)) {
    require_once($new_url);
}

$report = array();
$headers = getallheaders();
if ($_SERVER['REQUEST_METHOD'] != 'POST' || $headers['Authorization'] != PWE_API_KEY_3) {
    http_response_code(403);
    exit;
}

// Funkcja przetwarzająca pola formularza na zunifikowane pola do hubspota
function get_forms_fields ($form_id){
    $single_form = GFAPI::get_form($form_id);
    $form_fields = null;

    // Sprawdzenie czy formularz nie powinien być wykluczany za zakazane nazwy
    if(stripos($single_form['title'], '_part_') !== false || stripos($single_form['title'], 'write to us') !== false || stripos($single_form['title'], 'napisz do nas') !== false ){
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

            // case $admin_label == 'fair_hall' || stripos($label, 'hala') !== false:
            //     $form_fields[$single_field['id']] = 'hala';
            //     continue 2;

            // case $admin_label == 'fair_stamd' || stripos($label, 'stanowisko') !== false:
            //     $form_fields[$single_field['id']] = 'stand';
            //     continue 2;

            case $admin_label == 'utm' || stripos($label, 'utm') !== false:
                $form_fields[$single_field['id']] = 'utm';
                continue 2;

            case $admin_label == 'company_nip' || stripos($label, 'nip') !== false || stripos($label, 'tax') !== false:
                $form_fields[$single_field['id']] = 'nip';
                continue 2;
            
            case $admin_label == 'stand_size' || stripos($label, 'powierzchnię') !== false || stripos($label, 'exhibition') !== false || stripos($label, 'area') !== false:
                $form_fields[$single_field['id']] = 'wielkosc_stoiska';
                continue 2;

            // case $admin_label == 'activation' || stripos($label, 'active') !== false || stripos($label, 'aktywac') !== false:
            //     $form_fields[$single_field['id']] = 'aktywacja';
            //     continue 2;

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

function pwe_get_entries($allFormsIDs) {
    global $wpdb;

    if (empty($allFormsIDs) || !is_array($allFormsIDs)) {
        return [];
    }

    $placeholders = array_fill(0, count($allFormsIDs), '%d');
    $placeholder_list = implode(', ', $placeholders);

    $sql = $wpdb->prepare(
        "
        SELECT id, form_id
        FROM {$wpdb->prefix}gf_entry
        WHERE form_id IN ({$placeholder_list})
        ",
        ...$allFormsIDs
    );
    return $wpdb->get_results($sql);
}

$total = 0;
$updateCount = $skipedTrash = 0;

$allContacts = null;
$hubspot_search_message = null;

$searched_ids = array();
$all_forms_fields = array();
$all_forms_qr_feed = array();
$all_entries_to_add = array();
$allFormsIDs =array();

$domain = do_shortcode('[trade_fair_domainadress]');
$rok_edycji = do_shortcode('[trade_fair_catalog_year]');
$badge = do_shortcode('[trade_fair_badge]');

$all_forms = GFAPI::get_forms();

foreach($all_forms as $form){
    $formQR = $formName = false;

    $fields = $all_forms_fields[$form['id']] = get_forms_fields($form['id']);
    $qrs = $all_forms_qr_feed[$form_id]['$qr_feed'] = GFAPI::get_feeds(NULL, $form_id, 'qr-code');

    if (empty($fields) || 
        (isset($fields['is_trash']) && $fields['is_trash'] == 'true') || 
        (isset($fields['part']) && $fields['part'] == 'true')) {
    } else {
        $formName = true;
    }

    if(!is_wp_error($qrs)){
        $formQR = true;
    }

    if($formQR && $formName){
        $allFormsIDs[] = $form['id'];
    }
}

$all_entries = pwe_get_entries($allFormsIDs);
$todayNow = new DateTime('now');
$nowTimestamp = $todayNow->getTimestamp() * 1000;

$savedEntries = json_decode($_POST['entry_ids']) ;

if($savedEntries !== null){
    $entries_ids = array_values(
        array_filter($all_entries, fn($entry) => !in_array($entry->id, $savedEntries))
    );
} else {
    $entries_ids = $all_entries;
}

if(empty($entries_ids)){
    exit;
};

foreach ($entries_ids as $single_ids) {

    $form_id = $single_ids->form_id;
    $entry_id = $single_ids->id;
    $entry_data = array();

    if (count($all_entries_to_add) > 9999) break;

    $updatedEntries = array();
    
    if (empty($form_id)){
        continue;
    }

    if (empty($all_forms_fields[$form_id])) {
        $all_forms_fields[$form_id] = get_forms_fields($form_id);
    }

    if (empty($all_forms_fields[$form_id]) || 
        (isset($all_forms_fields[$form_id]['is_trash']) && $all_forms_fields[$form_id]['is_trash'] == 'true') || 
        (isset($all_forms_fields[$form_id]['part']) && $all_forms_fields[$form_id]['part'] == 'true')) {
            continue;
    }

    if ($form_id && empty($all_forms_qr_feed[$form_id])) {
        $all_forms_qr_feed[$form_id]['$qr_feed'] = GFAPI::get_feeds(NULL, $form_id, 'qr-code');
    }

    if(!is_wp_error($all_forms_qr_feed[$form_id]['$qr_feed'])){
        $feed = $all_forms_qr_feed[$form_id]['$qr_feed'][0];
        $entry_data['qrcode_url'] = gform_get_meta($entry_id, 'qr-code_feed_' . $feed['id'] . '_url');
        $entry_data['qrcode'] = $feed['meta']['qrcodeFields'][0]['custom_key'] . $entry_id . $feed['meta']['qrcodeFields'][1]['custom_key'] . $entry_id;
        $entry_data['ticket_url'] = 'https://' . $domain . '/wp-content/uploads/tickets/' . $entry_data['qrcode'] . '.jpg';
    }

    $entry = GFAPI::get_entry($entry_id);

    $entry_data['id'] = $entry_id;
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

    $updatedEntries[] = $entry_data;

    $all_entries_to_add = array_merge($all_entries_to_add, $updatedEntries);
};

$report['entries'] = $all_entries_to_add;
echo json_encode($report);