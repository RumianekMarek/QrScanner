<?php 
// Ensure connection is HTTPS
if ($_SERVER['HTTPS'] !== 'on') {
    header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit();
}

// Checking send methode
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_start();

    // Read JSON input
    $data = json_decode(file_get_contents('php://input'), true);
    $raport['data'] = $data;

    // Get authorization token, and domain to authorization hex
    $token = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
    $domain_raport = $_SERVER["HTTP_HOST"];
    $domain = 'https://' . $domain_raport . '/';

    // Get wordpress load file
    $new_url = str_replace('private_html','public_html',$_SERVER["DOCUMENT_ROOT"]) .'/wp-load.php';

    $forms = array();
    $form_id = '';
    $report[$domain_raport] = [];
    $entries = array();
    $value_index_in_array = '';
    $email_field = '';
    $preg_match_array = null;

    // Validate token
    if (validateToken($token, $domain)) {
        // Check if WordPress environment is available
        if (file_exists($new_url)) {
            require_once($new_url);
            // Check if GFAPI class exists
            if (class_exists('GFAPI')) {
                $all_forms = GFAPI::get_forms();
                $all_emails = array();

                foreach ($data[$domain] as $id => $value) {
                    foreach ($all_forms as $form_check) {
                        if (strpos(strtolower($form_check['title']), 'rejestr') !== false) {
                            foreach($form_check['fields'] as $field ){
                                if ($field['type'] == 'email'){
                                    $email_field = $field['id'];
                                }
                            }
                            $entries = GFAPI::get_entries(
                                $form_check['id'],
                                array(
                                    'status' => 'active',
                                    'field_filters' => [
                                        ['key' => $email_field, 'value' => $value[0], 'operator' => 'is']
                                    ],
                                ),
                                null, 
                                array(
                                    'offset' => 0,
                                    'page_size' => 1,
                                )
                            );

                            if (!empty($entries)){
                                $report[$domain_raport]['entry_id'][] = 'OLD_entry_' . $entries[0]['id'] . ' ' . $value[0] . ' ' . $value[1];
                                continue 2;
                            }
                        }
                    }
                    
                    $form_rej = '';

                    // Get form IDs based on titles
                    if (stripos('FB (FORMULARZ)', $value[2]) !== false) {
                        $preg_match_array = strtolower($data['options']) == 'pl'
                            ? ['/^\(.{4}\)\s*Rejestracja PL(\s?\(Branzowe\))?\s*\(FB\)$/i', '']
                            : ['/^\(.{4}\)\s*Rejestracja EN(\s?\(Branzowe\))?\s*\(FB\)$/i', ''];
                    }
                    if ($preg_match_array === null) {
                        $preg_match_array = strtolower($data['options']) == 'pl'
                            ? ['/^\(.{4}\)\s*Rejestracja PL(\s?\(Branzowe\))?$/i', 'rejestracja pl 2024']
                            : ['/^\(.{4}\)\s*Rejestracja EN(\s?\(Branzowe\))?$/i', 'rejestracja en 2024'];
                    }

                    foreach ($all_forms as $form_value) {
                        $title = strtolower($form_value['title']);

                        if (preg_match($preg_match_array[0], $title) || $title === $preg_match_array[1]) {
                            $form_rej = $form_value['id'];
                            break;
                        }
                    }
                    
                    // Create a new entry
                    $entry = ['form_id' => $form_rej];
                    $form = GFAPI::get_form($form_rej);

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

                    if (!empty($entry_id)){         
                        wp_remote_post(home_url('wp-content/plugins/custom-element/action_handler.php'), [
                            'body' => [
                                'element' => 'gform_after_submission',
                                'entry_id' => $entry_id,
                                'url' => null
                            ],
                            'timeout' => 0.01,
                            'blocking' => false,
                        ]);
                    }
                    
                    $all_emails[$entry_id] = $value[0];

                    // Handle QR code feeds
                    $qr_feeds = GFAPI::get_feeds(NULL, $form_rej);
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
                    } else {
                        $report['error'] = 'Błąd dodawania wpisu do Gravity Forms.';
                    }
                }

                ob_end_clean(); 
                ob_clean(); 
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
