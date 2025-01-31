<?php 
// Ensure connection is HTTPS
if ($_SERVER['HTTPS'] !== 'on') {
    header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit();
}

// Checking send methode
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read JSON input
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Get authorization token, and domain to authorization hex
    $token = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
    $domain_raport = $_SERVER["HTTP_HOST"];
    $domain = 'https://' . $domain_raport . '/';

    // Get wordpress load file
    $new_url = str_replace('private_html','public_html',$_SERVER["DOCUMENT_ROOT"]) .'/wp-load.php';

    $forms = array();
    $form_id = '';
    $report = array();
    $entries = array();
    
    // Validate token
    if (validateToken($token, $domain)) {
        // Check if WordPress environment is available
        if (file_exists($new_url)) {
            require_once($new_url);
            // Check if GFAPI class exists
            if (class_exists('GFAPI')) {
                $all_forms = GFAPI::get_forms();
                $all_emails = array();

                // Get form IDs based on titles
                foreach ($all_forms as $key => $value) {
                    if (preg_match('/^\(.{4}\)\s*Rejestracja PL(\s?\(Branzowe\))?$/i', $value['title'])) {
                        $form['def-pl'] = $value['id'];
                    } elseif (preg_match('/^\(.{4}\)\s*Rejestracja EN(\s?\(Branzowe\))?$/i', $value['title'])) {
                        $form['def-en'] = $value['id'];
                    }
                }
                
                // Fallback titles
                foreach ($all_forms as $key => $value) {
                    if ('rejestracja pl 2024' == strtolower($value['title'])) {
                        $form['def-pl'] = $value['id'];
                    } elseif ('rejestracja en 2024' == strtolower($value['title'])) {
                        $form['def-en'] = $value['id'];
                    }
                }

                // Get form based on option
                $form = strtolower($data['options']) == 'pl' ? GFAPI::get_form($form['def-pl']) : GFAPI::get_form($form['def-en']);
                

                foreach ($all_forms as $form_check) {
                    if (strpos(strtolower($form_check['title']), 'rejestracja') !== false) {
                        $offset = 0;
                        $page_size = 1000;

                        do {
                            $entries = GFAPI::get_entries(
                                $form_check['id'],
                                array(
                                    'status' => 'active',
                                ), 
                                null, 
                                array(
                                    'offset' => $offset,
                                    'page_size' => $page_size,
                                )
                            );
                            if(is_array($entries) && count($entries) > 0){
                                foreach ($entries as $entry_check) {
                                    foreach ($entry_check as $entry_id => $field_check) {
                                        if (is_numeric($entry_id)  && !empty($field_check) && filter_var($field_check, FILTER_VALIDATE_EMAIL)) {
                                            $all_emails[$entry_check['id']] = $field_check;
                                            continue 2;
                                        }
                                    }
                                }
                            }

                            $offset += $page_size;
                            
                        } while (is_array($entries) && count($entries) > 0);
                    }
                }



                // Process each entry in the data
                foreach ($data[$domain] as $id => $value) {

                    //Check index if new email is already registered
                    $value_index_in_array = array_search($value[0], $all_emails);
                    if ($value_index_in_array !== false){
                        $report[$domain_raport]['entry_id'][] = 'OLD_entry_' . $value_index_in_array . ' ' . $value[0] . ' ' . $value[1];
                    } else {
                        // Create a new entry
                        $entry = ['form_id' => $form['id']];

                        foreach ($form['fields'] as $field) {
                            if (strpos(strtolower($field['label']), 'mail') !== false) {
                                $entry[$field['id']] = $value[0];
                            } elseif (strpos(strtolower($field['label']), 'telefon') !== false || strpos(strtolower($field['label']), 'phone') !== false) {
                                $entry[$field['id']] = $value[1];
                            } elseif (strpos(strtolower($field['label']), 'utm') !== false) {
                                $entry[$field['id']] = 'utm_source=spady_lead&drop_kanal=' . $value[2];
                            } elseif (strpos(strtolower($field['label']), 'location') !== false) {
                                $entry[$field['id']] = 'rejestracja';
                            }
                        }

                        $report[$domain_raport]['new_entry'][] = 'NEW ' . $value[0] . ' ' . $value[1];
                        
                        $entry_id = GFAPI::add_entry($entry);

                        $all_emails[$entry_id] = $value[0];

                        // Handle QR code feeds
                        $qr_feeds = GFAPI::get_feeds(NULL, $form['id']);
                        foreach ($qr_feeds as $feed) {
                            $qr_code_url = gform_get_meta($entry_id, 'qr-code_feed_' . $feed['id'] . '_url');
                            if ($qr_code_url) {
                                $qr_code_id = $feed['id'];
                                break;
                            }
                        }

                        if ($qr_code_url && strpos($qr_code_url, 'http://') !== false) {
                            $qr_code_url = str_replace('http:', 'https:', $qr_code_url);
                        }

                        $qr_code_image = '<img data-imagetype="External" src="' . $qr_code_url . '" width="200">';

                        // Update notifications with QR code
                        foreach ($form["notifications"] as $id => $notification) {
                            if ($notification["isActive"]) {
                                $message = $notification["message"];
                                if (strpos($message, '{qrcode-url-' . $qr_code_id . '}') !== false) {
                                    $form["notifications"][$id]["message"] = str_replace('{qrcode-url-' . $qr_code_id . '}', $qr_code_url, $message);
                                } else {
                                    $form["notifications"][$id]["message"] = str_replace('{qrcode-image-' . $qr_code_id . '}', $qr_code_image, $message);
                                }
                            }
                        }

                        if (!is_wp_error($entry_id)) {
                            try {
                                GFAPI::send_notifications($form, $entry);
                            } catch (Exception $e) {
                                $report['error'] = 'Błąd send_notifications: ' . $e->getMessage();
                            }

                            $klavio_sender_url = ABSPATH . 'wp-content/plugins/custom-element/other/klavio_sender.php';

                            if (file_exists($klavio_sender_url)){
                                $entry_klavio = GFAPI::get_entry($entry_id);
                                include_once $klavio_sender_url;
                                klavio_sender($entry_klavio, $form);

                            }
                        } else {
                            $report['error'] = 'Błąd dodawania wpisu do Gravity Forms.';
                        }
                    }
                }

                // Send JSON response
                echo json_encode($report);
            } else {
                http_response_code(404);
                echo 'WordPress problems contact web developers code - "WORDPRESS GF ERROR ' . $domain . ' ".';
            }
        } else {
            http_response_code(404);
            echo 'Invalid token contact web developers code - "WordPress Function PHP error ' . $domain . ' ".';
        }
    } else {
        http_response_code(401);
        echo 'Invalid token contact web developers code - "INVALID TOKEN ' . $domain . ' ".';
    }
}

// Function to generate a token
function generateToken($domain) {
    $secret_key = '^GY0ZlZ!xzn1eM5';
    return hash_hmac('sha256', $domain, $secret_key);
}

// Function to validate a token
function validateToken($token, $domain) {
    $expected_token = generateToken($domain);
    return hash_equals($expected_token, $token);
}
