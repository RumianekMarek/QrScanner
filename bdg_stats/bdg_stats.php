<?php 

$new_url = str_replace('private_html','public_html',$_SERVER["DOCUMENT_ROOT"]) .'/wp-load.php';

function generateToken($domain) {
    $secret_key = 'gmlbu5oNGsbPCCS';
    return hash_hmac('sha256', $domain, $secret_key);
}
echo '<pre>';
if (file_exists($new_url)) {
    require_once($new_url);

    // if (!empty($_SERVER['Authorization']) && $_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['Authorization'] == generateToken($_SERVER['HTTP_HOST'])) {
        $all_entries = array();
        $all_forms_data = array();
        $report['year'] = substr(get_option('trade_fair_datetotimer'), 0 ,4);

        $last_id = !empty($_POST['last_id']) ? $_POST['last_id'] : 0;
        $last_form = intval($_POST['last_form']);

        $search_criteria = [
            'field_filters' => [
                [
                    'key'      => 'id',
                    'value'    => $last_id,
                    'operator' => '>'
                ]
            ]
        ];

        if (isset($_POST['add_data']) && $_POST['add_data']){
            $report['name'] = do_shortcode('[trade_fair_name]');
            $report['meta'] = do_shortcode('[trade_fair_badge]');
            $report['start'] = do_shortcode('[trade_fair_datetotimer]');
            $report['end'] = do_shortcode('[trade_fair_enddata]');
        }
        if (class_exists('GFAPI')) {
            $all_forms = GFAPI::get_forms();
            foreach($all_forms as $single_form){
                if(stripos($single_form['title'], 'napisz') !== false || stripos($single_form['title'], 'write') !== false || stripos($single_form['title'], 'katalog') !== false){
                    continue;
                }
                $form_fields = array(
                    'email' => '',
                    'telefon' => '',
                    'dane' => 'brak',
                    'utm' => '',
                );

                if ($single_form['id'] > $last_form){
                    $qr_code_meta = array();
                    $qr_feeds = GFAPI::get_feeds(NULL, $single_form['id']);
                    if(!is_wp_error($qr_feeds)){
                        foreach($qr_feeds as $single_feed){
                            if($single_feed['addon_slug'] == 'qr-code'){
                                foreach($single_feed['meta']['qrcodeFields'] as $qr_id => $qr_val){
                                    $qr_code_meta[$qr_id] = $qr_val['custom_key'];
                                }
                            }
                        }
                    }
                    $all_forms_data[$single_form['id']] = [
                        'form_title' => $single_form['title'],
                        'form_meta_id' => $qr_code_meta[0],
                        'form_meta_rnd' => $qr_code_meta[1],
                    ];
                }
    
                    foreach($single_form['fields'] as $single_field){
                        if(stripos($single_field['label'], 'mail') !== false){
                            $form_fields['email'] = $single_field['id'];
                            continue;
                        }
    
                        if(stripos($single_field['label'], 'tel') !== false || stripos($single_field['label'], 'phone') !== false){
                            $form_fields['telefon'] = $single_field['id'];
                            continue;
                        }
    
                        if(stripos($single_field['label'], 'imię') !== false || stripos($single_field['label'], 'imie') !== false || stripos($single_field['label'], 'name') !== false || stripos($single_field['label'], 'osoba') !== false){
                            $form_fields['dane'] = $single_field['id'];
                            continue;
                        }
    
                        if(stripos($single_field['label'], 'firm') !== false || stripos($single_field['label'], 'compa') !== false){
                            $form_fields['firma'] = $single_field['id'];
                            continue;
                        }

                        if(stripos($single_field['label'], 'utm') !== false){
                            $form_fields['utm'] = $single_field['id'];
                            continue;
                        }
                    }
    


                $form_entries = GFAPI::get_entries($single_form['id'], $search_criteria, null, array( 'offset' => 0, 'page_size' => 0));
                $indexes = array(
                    'data_created',
                    'id',
                    'user_agent',
                    'source_url',
                );
                foreach($form_entries as $single_entry){
                    // foreach($single_entry as $index => $val){
                    //     if($in)
                    // }
                    $all_entries[$single_entry['id']]['entry_id'] = $single_entry['id'];
                    $all_entries[$single_entry['id']]['entry_id'] = $single_entry['id'];
                    $all_entries[$single_entry['id']]['date_created'] = $single_entry['date_created'];
                    $all_entries[$single_entry['id']]['form_id'] = $single_entry['form_id'];
                    $all_entries[$single_entry['id']]['source_url'] = $single_entry['source_url'];
                    $all_entries[$single_entry['id']]['user_ip'] = $single_entry['ip'];
                    $all_entries[$single_entry['id']]['qr_code'] = (!empty($qr_code_meta)) ? $qr_code_meta[0] . $single_entry['id'] . $qr_code_meta[1] . $single_entry['id'] : 'brak kodu QR';
                    foreach($form_fields as $f_id => $f_val){
                        if ($single_entry[$f_val] === null){
                            continue;
                        }
                        $all_entries[$single_entry['id']][$f_id] = $single_entry[$f_val];
                    }
                    break 2;
                }
            }

            $report['data'] = $all_entries;
            var_dump($report['data']);
            // $report['forms'] = $all_forms_data;
            // echo json_encode($report);
        }
    // } else {
    //     http_response_code(401);
    //     echo 'Unauthorized entry';
    //     exit;
    // }
}