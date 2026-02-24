<?php 
echo '<pre>';
$new_url = str_replace('private_html','public_html',$_SERVER["DOCUMENT_ROOT"]) .'/wp-load.php';
require_once($new_url);

global $wpdb;
$table_name = $wpdb->prefix . 'custom_klavio_setup';
$success_send = true;

if(empty($_POST)){
    $patterns = [
        '/^\(\s*20\d{2}\s*\)\s?Rejestracja (PL|EN|Zaproszeń - call\scenter.*|gości wystawców.*)(\s*\(header(?:\s*new)?\))?(\s*\(Branzowe\))?(\s*\(FB\))?$/',
        '/^\(\s*20\d{2}\s*PW\s*\)\s?Potencjalny Wystawca.*$/i',
    ];

    $all_forms = GFAPI::get_forms();
    $all_emails = array();

    foreach ($all_forms as $form_check) {
        $pattern_mach = false;
        foreach($patterns as $pattern){
            if (preg_match($pattern, $form_check['title'])) {
                $pattern_mach = true;
                break;
            }
        }
        if(!$pattern_mach){
            continue;
        }
        
        foreach($form_check['fields'] as $field ){
            if ($field['type'] == 'email'){
                $email_field = $field['id'];
            }
            if(strtolower($field['label']) === 'kanał wysyłki' && (strpos(strtolower($form_check['title']), 'call centre') !== false || strpos(strtolower($form_check['title']), 'call center') !== false)){
                $kanal_wysylki = $field['id'];
            }
        }

        if(empty($email_field)){
            continue;
        }

        $lang = (strpos(strtolower($form_check['title']), ' en') !== false) ? 'en' : 'pl';
        
        $entries = GFAPI::get_entries(
            $form_check['id'],
            array(
                'status' => 'active',
            ),
            null, 
            array(
                'offset' => 0,
                'page_size' => 0,
            )
        );

        var_dump($form_check['title'], $lang, count($entries));


        foreach($entries as $entrie){
            if(!empty($kanal_wysylki)){
                $lang1 = strpos(strtolower($entrie[$kanal_wysylki]), 'eng') !== false ? 'en' : 'pl'; 
            } 

            if(empty($all_emails[$lang]) || !in_array($entrie[$email_field], $all_emails[$lang])){
                $all_emails[$lang][] = $entrie[$email_field];
            }
        }
        var_dump($lang);
        echo '<br>';
    }
    var_dump('pl - ' . count($all_emails['pl']));
    var_dump('en - ' . count($all_emails['en']));

} else {
    if (!hash_equals($_POST['secret'], hash_hmac('sha256', $_SERVER["HTTP_HOST"], PWE_API_KEY_5))) {
        error_log('salessmanago_send_mass błąd klucza');
        exit;
    }
    $index = (int) ($_POST['index'] ?? 1);
    sleep($index * 5);

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
    
    if(empty($passes['secret']) || empty($passes['endpoint']) || empty($passes['client_id']) || empty($passes['sha'])){
        error_log('salesmanago, id scret key problem secret:' . $passes['secret'] . ' secret:' . $passes['secret'] . ' secret:' . $passes['secret'] . ' secret:' . $passes['secret']);
    }

    $lang = (strpos($_POST['lang'] ?? '', 'en') !== false) ? 'EN' : 'PL';
    $endpoint = 'https://' . $passes['endpoint'] . '/api/contact/batchupsertv2';

    $domain_name_array = explode('.', do_shortcode('[trade_fair_domainadress]'));
    array_pop($domain_name_array);
    $domain_name = implode('_', $domain_name_array);

    $fair_tag = strtoupper($domain_name . '_BIEZACE_' . $lang . '_' . do_shortcode('[trade_fair_catalog_year]'));

    $data = [
        "clientId"    => $passes['client_id'],
        "apiKey"      => $passes['secret'],
        "sha"         => $passes['sha'],
        "owner"       => 'agnieszka.wojtasik@warsawexpo.eu',
    ];

    foreach ($_POST['entrys'] as $id => $value) {
        if(empty($value)){
            continue;
        }
        $data['upsertDetails'][$id] = [
            "tags" => [$fair_tag],
            "contact" => array(),
        ];

        foreach($value as $val_id => $val_data){
            $data['upsertDetails'][$id]['contact'][$val_id] = $val_data;
        }
    }
    $response = wp_remote_post($endpoint, [
        'body'    => json_encode($data),
        'headers' => ["Content-Type" => "application/json"],
        'method'  => "POST",
    ]);

    $body = wp_remote_retrieve_body($response);
    $status = json_decode($body, true);

    if(!empty($status["requestId"])){
        sleep(20);
        $endpoint = 'https://' . $passes['endpoint'] . '/api/job/status';

        $data_check = [
            "clientId"    => $passes['client_id'],
            "apiKey"      => $passes['secret'],
            "sha"         => $passes['sha'],
            "owner"       => 'agnieszka.wojtasik@warsawexpo.eu',
            "requestId"   => $status["requestId"]
        ];

        // Wysyłka zapytania
        $response = wp_remote_post($endpoint, [
            'body'    => json_encode($data_check),
            'headers' => [
                "Content-Type" => "application/json"
            ],
        ]);

        $body  = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        $responser = file_get_contents($result["fileUrl"]);

        error_log(json_encode($responser));

        $log_entry = [
            'email' => 'mass_send',
            'phone' => null,
            'form_name' => 'Rejestracja gości wystawców ' . $lang,
            'send_at' => date('Y-m-d H:i:s'),
            'archive' => 0,
            'channel' =>  strtolower(trim($sels_slug, '_')),
            'response' => ($body['success'] ?? true) ? 'salesamanago-line-true' : 'salesamanago-line-false' ?? 'salesamanago-line-false',
            'message' => json_encode($responser) ?? 'no message',
            'status' => wp_remote_retrieve_response_code($response),
            'response_at' => date('Y-m-d H:i:s'),
        ];

        $wpdb->insert('rej_send_log', $log_entry);
    }

    if (isset($status['success']) && $status['success'] === false) {
        $success_send = false;
        error_log('SALESmanago error response: ' . $body);
        return;
    }

    

}