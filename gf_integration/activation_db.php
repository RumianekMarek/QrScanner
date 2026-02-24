<?php 

class Activation_DB {

    private $entry;
    private $form;
         
    // Konstruktor przyjmujący dane
    public function __construct($entry_id) {
        $this->entry = GFAPI::get_entry($entry_id);
        $this->form = GFAPI::get_form($this->entry['form_id']) ;
    }

    private function token() {
        $secret_key = 'CvmJtiPdohSGs926';
        $today = date('d-m-Y');
        return hash_hmac('sha256', $today, $secret_key);
    }

    public function init() {
        $qrFeed = GFAPI::get_feed($this->form['id'], 'qr_code');
        $unusedFields = [];
        $fieldsMap = [];
        $entryData = [];
        if(is_wp_error($meta)) return;

        $skipedFields = [
            'location',
            'UTM',
            'source',
            'Zgoda na przetwarzanie danych osobowych',
            'CAPTCHA',
        ];

        foreach($this->form['fields'] as $field){

            if(in_array($field['label'], $skipedFields)) continue;

            switch (true){
                case $field['inputType'] === 'email' || preg_match('/(mail)/iu', $field['label']):
                    $fieldsMap[$field['id']] = 'email';
                    break;

                case $field['inputType'] === 'phone'|| preg_match('/(telefon)/iu', $field['label']):
                    $fieldsMap[$field['id']] = 'phone';
                    break;
                
                case (preg_match('/(imię|nazwisko|osoba)/iu', $field['label'])):
                    $fieldsMap[$field['id']] = 'full_name';
                    break;

                case (preg_match('/(ulica|street)/iu', $field['label'])):
                    $fieldsMap[$field['id']] = 'street_address';
                    break;

                case (preg_match('/(domu|adres|house)/iu', $field['label'])):
                    $fieldsMap[$field['id']] = 'house_number';
                    break;
                
                case (preg_match('/(lokalu)/iu', $field['label'])):
                    $fieldsMap[$field['id']] = 'apartment_number';
                    break;

                case (preg_match('/(pocztowy|post)/iu', $field['label'])):
                    $fieldsMap[$field['id']] = 'postal_code';
                    break;

                case (preg_match('/(miasto|city|town)/iu', $field['label'])):
                    $fieldsMap[$field['id']] = 'city';
                    break;

                case (preg_match('/(country|państwo|kraj|nation)/iu', $field['label'])):
                    $fieldsMap[$field['id']] = 'country';
                    break;

                default:
                    $unusedFields[] = $field['label'];
            }
        }

        foreach($this->entry as $id => $ent){

            if(empty($fieldsMap[$id]) || empty($ent)) continue;

            $entryData[$fieldsMap[$id]] = $ent;
        }
        
        if(empty($entryData)) return;
    
        $entryData['entry_id'] = $this->entry['id'];
        $entryData['domain'] = do_shortcode('[trade_fair_domainadress]');
        $entryData['badge'] = do_shortcode('[trade_fair_badge]');
        $entryData['fairYear'] = do_shortcode('[trade_fair_catalog_year]');
        $entryData['Qrcode'] =  $qrFeed['meta']['qrcodeFields'][0]['custom_key'] . $this->entry['id'] . $qrFeed['meta']['qrcodeFields'][1]['custom_key'] . $this->entry['id'];
        $entryData['fairDate'] = do_shortcode('[trade_fair_datetotimer]');
        $token = $this->token();

        wp_remote_post('https://activation.warsawexpo.eu/insert/inserter.php' , [
            'headers' => [
                'Authorization' => $token,
            ],
            'body' => $entryData,
            'timeout' => 0.01,
            'blocking' => false,
        ]);
    }
}