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
                        $entries = GFAPI::get_entries($form_id, null, null, array( 'offset' => 0, 'page_size' => 0));
                        foreach ($entries as $entry_check) {
                            foreach ($entry_check as $entry_id => $field_check) {
                                if (is_numeric($entry_id)  && !empty($field_check) && filter_var($field_check, FILTER_VALIDATE_EMAIL)) {
                                    $all_emails[$entry_check['id']] = $field_check;
                                    continue 2;
                                }
                            }
                        }
                    }
                }

                $spad_form_entry = GFAPI::get_entries($form['id'], null, null, array( 'offset' => 0, 'page_size' => 0));
                $spad_form = GFAPI::get_form($form['id']);
                $i = 0;

                foreach($spad_form_entry as $entry_value){              
                    foreach($entry_value as $e_field => $field_value){
                        if (is_numeric($e_field)  && !empty($field_value) && filter_var($field_value, FILTER_VALIDATE_EMAIL)) {
                            $spad_emails[$i]['email'] = $field_value;
                            $spad_emails[$i]['date_created'] = $entry_value['date_created'];
                            $i++;
                            continue 2;
                        }
                    }
                }

                // Process each entry in the data
                foreach ($data[$domain] as $id => $value) {
                    if (is_array($spad_emails)) {
                        $email_list = array_column($spad_emails, 'email');
                        
                        $value_index_in_spady = array_search($value[0], $email_list);

                        if($value_index_in_spady !== false){
                            if(strpos($spad_emails[$value_index_in_spady]['date_created'], $data['date']) !== false){
                                $report[$domain_raport]['new_entry'][] = 'NEW ' . $value[0] . ' ' . $value[1];
                            } else {
                                $report[$domain_raport]['entry_id'][] = 'OLD_entry ' . $value[0] . ' ' . $value[1];
                            }
                        } else {
                            $report[$domain_raport]['entry_id'][] = 'OLD_entry ' . $value[0] . ' ' . $value[1];
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
