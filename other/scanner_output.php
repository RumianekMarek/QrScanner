<?php 
$report['status'] = "false";
$new_url = str_replace('private_html','public_html',$_SERVER["DOCUMENT_ROOT"]) .'/wp-load.php';

function generateToken($domain) {
    $secret_key = 'CvmJtiPdohSGs926';
    
    return hash_hmac('sha256', $domain, $secret_key);
}
// echo '<pre>';
if (file_exists($new_url)) {
    require_once($new_url);

    if (!empty($_SERVER['Authorization']) && $_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['Authorization'] == generateToken($_SERVER['HTTP_HOST'])) {
        // $report['entry'] = 
        $entry = GFAPI::get_entry($_POST['entry_id'], null, null, array( 'offset' => 0, 'page_size' => 0));
        $form = GFAPI::get_form($entry['form_id']);
        $entry_sanitized = array();

        foreach ($form['fields'] as $field) {
            if (strpos(strtolower($field['label']), 'mail') !== false) {
                $entry_sanitized['email'] = $entry[$field['id']];
            } elseif (strpos(strtolower($field['label']), 'telefon') !== false || strpos(strtolower($field['label']), 'phone') !== false) {
                if(!empty($entry[$field['id']]) && empty($entry_sanitized['phone'])){
                    $entry_sanitized['phone'] = $entry[$field['id']];
                }
            } elseif (strpos(strtolower($field['label']), 'imie') !== false || strpos(strtolower($field['label']), 'name' ) !== false || strpos(strtolower($field['label']), 'nazwisko' ) !== false || strpos(strtolower($field['label']), 'osoba') !== false) {
                $entry_sanitized['name'] = $entry[$field['id']];
            } elseif (trim(strtolower($field['label'])) == 'firma' || trim(strtolower($field['label'])) == 'company') {
                $entry_sanitized['company'] = $entry[$field['id']];
            }
        }

        $report['data'] = $entry_sanitized;
        $report['status'] = "true";
        
    } else {    
        http_response_code(401);
        $report['error'] = 'Unauthorized entry';
    }
}
echo json_encode($report);