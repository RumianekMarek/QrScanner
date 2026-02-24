<?php 
if ($_SERVER['HTTPS'] !== 'on') {
    header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    echo 'https';
    exit();
}

// Implement secure password handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $token = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
    $domain_raport = $_SERVER ["HTTP_HOST"];
    $domain = 'https://' . $domain_raport . '/';
    $new_url = str_replace('private_html','public_html',$_SERVER["DOCUMENT_ROOT"]) .'/wp-load.php';
    $forms = array();
    $form_id = '';
    $report = array();
    $start_date = $data['start'];
    $end_date = $data['end'];

    if (validateToken($token, $domain)) {
        if (file_exists($new_url)) {
            require_once($new_url);
            if (class_exists('GFAPI')) {
                $all_forms = GFAPI::get_forms();

                $search_criteria = array(
                    'status' => 'active',
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                );

                foreach ($all_forms as $key){
                    if (strpos(strtolower($key['title']), 'rejestracja gości wystawców') !== false ){
                        if (strpos(strtolower($key['title']), 'en') !== false || strpos(strtolower($key['title']), '(eng)') !== false ){
                            $entries = GFAPI::get_entries($key["id"], $search_criteria);
                            $forms[$key["id"]] = count($entries);
                            $forms['En'] += count($entries);
                        } else {
                            $entries = GFAPI::get_entries($key["id"], $search_criteria);
                            $forms[$key["id"]] = count($entries);
                            $forms['Pl'] += count($entries);
                        }
                    } else if (strpos(strtolower($key['title']), 'generator zaproszen - potwierdzenie aktywacji') !== false ){
                        if (strpos(strtolower($key['title']), ' en ') !== false || strpos(strtolower($key['title']), '(eng)') !== false ){
                            $entries = GFAPI::get_entries($key["id"], $search_criteria);
                            $forms[$key["id"]] = count($entries);
                            $forms['En'] += count($entries);
                        } else {
                            $entries = GFAPI::get_entries($key["id"], $search_criteria);
                            $forms[$key["id"]] = count($entries);
                            $forms['Pl'] += count($entries);
                        }
                    }
                }
            }

            $forms['targi'] = $domain_raport;
    
            $json_response = json_encode($forms);
            echo $json_response;
        } else {
            echo 'Wordpress problem';
        }
    } else {
        echo 'Token problem';
    }
} else {
    echo 'not a POST';
}

function generateToken($domain) {
    $secret_key = '^GY0ZlZ!xzn1eM5';
    return hash_hmac('sha256', $domain, $secret_key);
}

// Function to validate a token
function validateToken($token, $domain) {
    $expected_token = generateToken($domain);
    return hash_equals($expected_token, $token);
}