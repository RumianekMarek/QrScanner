<?php
// Script handling data of individuals interested in the trade fair, transmitted from Facebook via the make.com platform, for registration and sending QR codes granting access to the event.

// Preper log.txt.
$sciezka_do_pliku = 'log.txt';
$dane_do_zapisu = "\n" . date('Y-m-d H:i:s') . "\n";

// Check if password and method is correct.
if ($_SERVER['HTTP_HEAD'] == '(rR1*sS3(tT5&uU7)vV2+wW4@yY' && $_SERVER["REQUEST_METHOD"] == "GET" && !empty($_GET)) {

    // Get sended data
    $przeslane_dane = $_GET;
    $dane_do_zapisu .= json_encode($przeslane_dane, JSON_PRETTY_PRINT) . "\n\n";

    // Create wp-load.php url.
    $new_url = str_replace('private_html','public_html',$_SERVER["DOCUMENT_ROOT"]) .'/wp-load.php';

    $entry = [];

    // Set language.
    $lang = 'pl';
    if($przeslane_dane['lang'] == 'en'){
        $lang = 'en';
    }

    // Check if all needed components exists.
    if (file_exists($new_url)) {
        require_once($new_url);
        if (class_exists('GFAPI')) {

            // Find Facebook Form -> (FB).
            $all_forms = GFAPI::get_forms();
            foreach($all_forms as $form){
                if(strpos($form['title'], '(FB)') !== false && strpos(strtolower($form['title']), $lang) !== false){
                    $entry['form_id'] = $form['id'];
                    $face_form = $form;
                    $all_fields = $form['fields'];
                    break;
                }
            }

            // Create Gravity Form entry object, 
            // Add all info from data to specific form fields.
            foreach($all_fields as $field){
                if(strpos(strtolower($field['label']), 'nazwisko') !== false){
                    $entry[$field['id']] = $przeslane_dane['name'];
                } elseif(strpos(strtolower($field['label']), 'name') !== false){
                    $entry[$field['id']] = $przeslane_dane['name'];
                } elseif(strpos(strtolower($field['label']), 'mail') !== false){
                    $entry[$field['id']] = $przeslane_dane['email'];
                } elseif(strpos(strtolower($field['label']), 'telefon') !== false){
                    $entry[$field['id']] = $przeslane_dane['phone'];
                } elseif(strpos(strtolower($field['label']), 'phon') !== false){
                    $entry[$field['id']] = $przeslane_dane['phone'];
                } elseif(strpos(strtolower($field['label']), 'nip') !== false){
                    $entry[$field['id']] = $przeslane_dane['fair'];
                }
            }
            $entry_id = GFAPI::add_entry($entry);

            // Find Qr code ID in meta data.
            $qr_feeds = GFAPI::get_feeds( NULL, $form[ 'id' ]);
            foreach($qr_feeds as $feed){
                if (gform_get_meta($entry_id, 'qr-code_feed_' . $feed['id'] . '_url')){
                    $qr_code_id = $feed['id'];
                }   
            }

            // Create Qr code image url
            $meta_key_url = gform_get_meta($entry_id, 'qr-code_feed_' . $qr_code_id . '_url');
            $meta_key_image = '<img data-imagetype="External" src="' . $meta_key_url . '" width="200">';
            
            // Change shortcode in notification to corect QR code display.
            foreach($face_form["notifications"] as $id => $key){
                if($key["isActive"]){
                    if(strpos($key["message"], '{qrcode-url-' . $qr_code_id . '}') != false){
                        $face_form["notifications"][$id]["message"] = str_replace('{qrcode-url-' . $qr_code_id . '}', $meta_key_url . '" width="200', $key["message"]);
                    } else if (strpos($key["message"], '{qrcode-image-' . $qr_code_id . '}') != false){
                        $face_form["notifications"][$id]["message"] = str_replace('{qrcode-image-' . $qr_code_id . '}', $meta_key_image, $key["message"]);
                    }
                }
            }

            // Check if the entry has been added correctly.
            if ($entry_id && !is_wp_error($entry_id)) {      

                wp_remote_post(home_url('wp-content/plugins/custom-element/action_handler.php'), [
                    'body' => [
                        'element' => 'gform_after_submission',
                        'entry_id' => $entry_id,
                        'url' => null
                    ],
                    'timeout' => 0.01,
                    'blocking' => false,
                ]);
                
                // Try to send active notifications 
                try {
                    $entry_gf = GFAPI::get_entry($entry_id);
                    GFAPI::send_notifications($face_form, $entry_gf);
                    $dane_do_zapisu .= 'Powiadomienie dla ' . $przeslane_dane['email'] . ' wysłane.';
                } catch (Exception $e) {
                    $dane_do_zapisu .= 'Błąd send_notifications: ' . $e->getMessage();
                }

                // Send the entry to Klaviyo.
                // $klavio_sender_url = ABSPATH . 'wp-content/plugins/custom-element/other/klavio_sender.php';
                // if (file_exists($klavio_sender_url)){
                //     $entry_klavio = GFAPI::get_entry($entry_id);
                //     include_once $klavio_sender_url;
                //     klavio_sender($entry_klavio, $form);
                // }
            } else {
                $dane_do_zapisu .= 'Błąd dodawania wpisu do Gravity Forms.';
            }
            
        }
    }
// Error logs if password or method is incorrect
} else {
    $dane_do_zapisu .= 'error-log ||';
    foreach($_SERVER as $id => $key){
        $dane_do_zapisu .= $id . ' => ' . $key ;
    }
    $dane_do_zapisu .= '||';
    $dane_do_zapisu .= ' empty GET -> '.empty($_GET) .' ||';
    if (!empty($_POST)){
        $dane_do_zapisu .= 'POST -> ';
        foreach($_POST as $data){
            $dane_do_zapisu .= $data. ' ';
        }
    }
}

// Error log for unexpected problems
if ($dane_do_zapisu == '') {
    $blad = error_get_last();
    $dane_do_zapisu .= $blad ? json_encode($blad, JSON_PRETTY_PRINT) : 'Brak informacji o błędzie.';
}

// Add a log entry to the file for safekeeping.
$plik = fopen($sciezka_do_pliku, 'a');
if ($plik) {
    fwrite($plik, $dane_do_zapisu);
    fclose($plik);
}