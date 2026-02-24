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

    // Get authorization token, and domain to authorization hex
    $token = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
    $domain_raport = $_SERVER["HTTP_HOST"];
    $domain = 'https://' . $domain_raport . '/';

    // Get wordpress load file
    $new_url = str_replace('private_html','public_html',$_SERVER["DOCUMENT_ROOT"]) .'/wp-load.php';

    $forms = array();
    $report = array();
    $entries = array();
    $email_field = '';
 
    // Validate token
    if (validateToken($token, $domain)) {
        // Check if WordPress environment is available
        if (file_exists($new_url)) {
            require_once($new_url);
            // Check if GFAPI class exists
            if (class_exists('GFAPI')) {
                $all_forms = GFAPI::get_forms();
                foreach ($data[$domain] as $id => $value) {
                    foreach ($all_forms as $form_check) {
                        if (strpos(strtolower($form_check['title']), 'rejestracja') !== false) {
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
                                $check_date = date('Y-m-d', strtotime($data['date']));
                                $entry_date = date('Y-m-d', strtotime($entries[0]['date_created']));
                                if($check_date == $entry_date){
                                    $report[$domain_raport]['new_entry'][] = 'NEW ' . $value[0] . ' ' . $value[1];
                                    continue 2;
                                }  

                                $report[$domain_raport]['entry_id'][] = 'OLD_entry_' . $entries[0]['id'] . ' ' . $value[0] . ' ' . $value[1];
                                continue 2;
                            }
                        }
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