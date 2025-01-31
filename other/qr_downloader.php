<?php 
session_start();
if($_SESSION['haslo'] != 'PizzaHawajska'){

echo '<form id="haslo" action="" method="post">
        <input type="text" id="haslo" name="haslo" placeholder="wpisz hasło" required>
        <button class="modal-button" id="submit_haslo_form" name="submit_haslo">Send</button>
    </form>';

if(isset($_POST['submit_haslo'])){
    $_SESSION['haslo'] = $_POST['haslo'];
}

} else {
?>
    <script>
        function DownloadFile(content, fileName) {
            var a = document.createElement('a');
            
            var blob = new Blob([content], { type: 'application/json' });
            var url = URL.createObjectURL(blob);
            var fileN = fileName;

            a.href = url;
            a.download = fileN;
            
            a.click();
            
            URL.revokeObjectURL(url);
        } 
    </script>
    <?php

    echo '
        <form id="qr-code" action="" method="post">
            <input type="text" id="form_id" name="form_id" placeholder="ID Formularza" required>
            <input type="text" id="donwload_number" name="donwload_number" placeholder="Ilość do pobrania" pattern="[0-9]*">
            <button class="modal-button" id="submit-form" name="submit">Send</button>
        </form>
        <p>Pobierane są losowe recordy, jeżęli ilość będzie pusta zostaną pobrane wszystkie</p>';
    
    function Create_JSON ($form_ids, $download_count){

        if (class_exists('GFAPI')) {
            $qr_code_resoult = array();
            
            foreach($form_ids as $form_id){
                $feeds = GFAPI::get_feeds( NULL, $form_id);
                if(is_wp_error($feeds)){
                    echo '<br>Formularz '.$form_id.' nie istnieje, podany w '.$_POST["form_id"].'<br>';
                } else {
                    $entries = GFAPI::get_entries($form_id,null,null,array( 'offset' => 0, 'page_size' => 1000 ));

                    if(isset($feeds[0]['meta']['hash']) == false){
                        $custom[0] = $feeds[0]['meta']['qrcodeFields'][0]['custom_key'];
                        $custom[1] = $feeds[0]['meta']['qrcodeFields'][1]['custom_key'];
                    } else {
                        $custom[0] = $feeds[1]['meta']['qrcodeFields'][0]['custom_key'];
                        $custom[1] = $feeds[1]['meta']['qrcodeFields'][1]['custom_key'];
                    }
                    
                    shuffle($entries);
                    for($i=0; $i<$download_count; $i++){
                        if ($entries[$i]['id']){
                            $qr_code_resoult[] = '"' . $custom[0] . $entries[$i]['id'] . $custom[1] . $entries[$i]['id'] . '"';
                        }
                    }
                }
            }
        }

        $jsonData = json_encode($qr_code_resoult);
        $trade_name = do_shortcode("[trade_fair_name]");

        $file_name = 'form '. $trade_name.' '.date('y.m.d').'.json';
    ?>
        <script>
            DownloadFile(<?php echo $jsonData ?>, "<?php echo $file_name ?>")
        </script>
    <?php
    }

    if(isset($_POST["submit"])){
        require_once($_SERVER['DOCUMENT_ROOT'].'/wp-load.php');
        $forms_array = explode(",",$_POST["form_id"]);
        Create_JSON ($forms_array, $_POST['donwload_number']); 
    }
}
?>

