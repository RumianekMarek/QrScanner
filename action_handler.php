<?php
define('WP_USE_THEMES', false);
$new_url = str_replace('private_html','public_html',$_SERVER["DOCUMENT_ROOT"]) .'/wp-load.php';
require_once($new_url);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$element = $_POST['element'] ?? null;

switch ($element) {
    case 'gform_after_submission':
        include_once ABSPATH . 'wp-content/plugins/custom-element/gf_integration/gf_integration.php';
        include_once ABSPATH . 'wp-content/plugins/custom-element/gf_integration/activation_db.php';

        $entry_id = $_POST['entry_id'] ?? '';
        $url = $_POST['url'] ?? '';

        if (class_exists('GF_Integration')) {
            $integration = new GF_Integration($entry_id, $url);
            $integration->init();
        } else {
            error_log('Brak klasy GF_Integration');
        }

        if (class_exists('Activation_DB')) {
            $integration = new Activation_DB($entry_id, $url);
            $integration->init();
        } else {
            error_log('Brak klasy Activation_DB');
        }

        break;

    default:
        error_log("Nieobsługiwany element: $element");
}
