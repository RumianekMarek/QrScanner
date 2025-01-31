<?php 

header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['HTTPS'] !== 'on') {
    header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit();
}

// Implement secure password handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    var_dump($data);
    $token = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '';
    $domain = 'https://' . $_SERVER ["HTTP_HOST"] . '/';
    $new_url = str_replace('private_html','public_html',$_SERVER["DOCUMENT_ROOT"]) .'/wp-load.php';

    if (validateToken($token, $domain)) {
        if (file_exists($new_url)) {
            require_once($new_url);
            if (class_exists('GFAPI')) {
                $all_forms = GFAPI::get_forms();
                
                foreach ($all_forms as $key => $value) {
                    if (strpos(strtolower($value['title']), strtolower($data['options'][0])) !== false){
                        $form = $value;
                        break;
                    }
                }
                foreach ($data[$domain] as $id => $value){
                    
                    $entry = [];
                    $entry['form_id'] = $form['id'];
                    foreach ($form['fields'] as $key){
                        if(strpos(strtolower($key['label']), 'nazwisko') !== false ){
                            $entry[$key['id']] = $value[1];
                        } elseif (strpos(strtolower($key['label']), 'firma') !== false ){
                            $entry[$key['id']] = $value[0];
                        }  elseif (strpos(strtolower($key['label']), 'wybierz') !== false ){
                            $entry[$key['id']] = $data['options'][1];
                        }  
                    }

                    $entry_id = GFAPI::add_entry($entry);

                    for ($i=0; $i<=300;$i++){
                        if(gform_get_meta($entry_id , 'qr-code_feed_' . $i . '_url') != ''){
                            $qr_code_url = gform_get_meta($entry_id, 'qr-code_feed_'.$i.'_url');
                            break;
                        }
                    }
                    $badge_url = 'https://warsawexpo.eu/assets/badge/local/loading.html?category='. $data['options'][1].'&getname='.$value[1].'&firma='.$value[0].'&qrcode='.$qr_code_url;

                    echo '<script>window.open("'.$badge_url.'");</script>';
                    if($id != 0 && $id % 10 === 0){
                        sleep(2);
                    }
                }
            } else {
                echo 'WordPress problems contact web developers code - "WORDPRESS ERROR".';
                echo'<br><br>';
            }
        }
    } else {
        echo 'ivalide token contact web developers code - "INVALID TOKEN '.$domain.' ".';
        echo'<br><br>';
        http_response_code(401);
        exit;
    }
}

function generateToken($domain) {
    $secret_key = 'T#8c$wrYz@jw2W3s6L7';
    return hash_hmac('sha256', $domain, $secret_key);
}

// Function to validate a token
function validateToken($token, $domain) {
    $expected_token = generateToken($domain);
    return hash_equals($expected_token, $token);
}