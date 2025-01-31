<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['HTTPS'] !== 'on') {
    header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit();
}

if (isset($_SERVER['HTTP_AUTHORIZATION']) && $_SERVER['HTTP_AUTHORIZATION'] == '3DkZU8RdEbdFH7m') {

    $new_url = str_replace('private_html', 'public_html', $_SERVER["DOCUMENT_ROOT"]) . '/wp-load.php';

    if (file_exists($new_url)) {
        require_once($new_url);
        if (class_exists('GFAPI')) {
            $all_entries = array();
            $all_forms = GFAPI::get_forms();

            foreach ($all_forms as $form) {
                $qr_feeds = GFAPI::get_feeds(NULL, $form['id']);
                if (is_wp_error($qr_feeds)) {
                    continue;
                }
                $entries = GFAPI::get_entries($form['id']);
                $all_fields = [];
                foreach ($form['fields'] as $field) {
                    $all_fields[$field['id']] = $field['label'];
                }
                foreach ($entries as $en_id => $entry) {
                    foreach ($entry as $e_id => $single) {
                        if (is_int($e_id)) {
                            $all_entries[$form['title']][$en_id][$all_fields[$e_id]] = $single;
                        } elseif (strpos($e_id, '.1') != false) {
                            $e_id = 'Consent_' . $e_id;
                            $all_entries[$form['title']][$en_id][$e_id] = $single == '1' ? 'yes' : 'no';
                        } elseif (in_array($e_id, ['id', 'date_created', 'ip', 'source_url'])) {
                            $all_entries[$form['title']][$en_id][$e_id] = $single;
                        }
                    }
                }
            }
            header('Content-Type: application/json');
            echo json_encode($all_entries);
        }
    }
    exit();
} else {
    header('Content-Type: application/json');
    echo json_encode(['status' => '403']);
    exit();
}
