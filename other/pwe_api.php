<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

$new_url = str_replace('private_html','public_html',$_SERVER["DOCUMENT_ROOT"]) .'/wp-load.php';

if (file_exists($new_url)) {
    require_once($new_url);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'API Error (Connecting with warsawexpo)']);
    exit;
}

// Token verification key
$secret_key = defined('PWE_API_KEY_1') ? PWE_API_KEY_1 : '';

// Getting token from header
$headers = apache_request_headers();

$token = isset($headers['Authorization']) ? $headers['Authorization'] : '';

if ($token !== hash_hmac('sha256', $_SERVER['HTTP_HOST'], $secret_key)) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Catalogs
$document_root = $_SERVER['DOCUMENT_ROOT'];
$public_html_path = str_replace('/private_html', '/public_html/', $document_root);
$domain = $_SERVER['HTTP_HOST'];
$base_url = 'https://'. $domain .'/';

$directories = [
    'doc'          => 'doc',
    'partnerzy' => 'doc/Logotypy/Rotator 2',
    'wydarzenia' => 'doc/Logotypy/Europa',
    'konferencje' => 'doc/Logotypy/Konferencje'
];

// Checking directories and downloading files
$data = [];
foreach ($directories as $key => $dir) {
    $fullPath = $public_html_path . $dir;
    
    if (!is_dir($fullPath)) {
        // If directory doesn't exist, set empty array
        $data[$key] = [];
        continue;
    }
    
    $data[$key] = getFilesFromDirectory($fullPath, $public_html_path, $base_url);
}

// Get JSON
echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

// Get a list of files from a directory and generates full URLs
function getFilesFromDirectory($dir, $public_html_path, $base_url) {
    $result = [];
    $items = array_diff(scandir($dir), ['.', '..']);
    foreach ($items as $item) {
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            $result = array_merge($result, getFilesFromDirectory($path, $public_html_path, $base_url));
        } elseif (is_file($path)) {
            $relativePath = str_replace($public_html_path, '', $path);
            $result[] = [
                'path' => $base_url . $relativePath,
                'title' => basename(dirname($path))
            ];
        }
    }
    return $result;
}