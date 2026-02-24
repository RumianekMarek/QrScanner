<?php 
$report['status'] = 'false';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_SERVER['Authorization'] == 'qg58yn58q3yn5v') {

    $new_url = str_replace('private_html','public_html',$_SERVER["DOCUMENT_ROOT"]) .'/wp-load.php';
    if (file_exists($new_url)) {
        require_once($new_url);

        $sended_content = file_get_contents('php://input');
        $content = json_decode($sended_content);

        $all_forms = GFAPI::get_forms();

        foreach ($content as $key) {
            if(preg_match('/^https:\/\//',$key)){
                continue;
            }
            foreach ($all_forms as $form) {
                if(strpos(strtolower($form['title']), trim(strtolower($key))) !== false){
                    $entries = GFAPI::get_entries($form['id'], array('status' => 'active'), null, array( 'offset' => 0, 'page_size' => 0));
                    $form = array();
                    foreach ($entries as $entry){
                        $data = substr($entry['date_created'], 0, 10);
                        if(isset($form[$data])){
                            $form[$data] += 1;
                        } else {
                            $form[$data] = 1;
                        }
                    }
                    $report['forms'][$key] = $form;
                    continue 2;
                }
            }
        }
        $report['status'] = 'true';
    }
}

echo json_encode($report);