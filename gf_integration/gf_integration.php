<?php 

class GF_Integration {

    private $entry;
    private $form;
    private $hubspot_file;
    private $url;
         
    // Konstruktor przyjmujący dane
    public function __construct($entry_id, $url) {
        $this->entry = GFAPI::get_entry($entry_id);
        $this->form = GFAPI::get_form($this->entry['form_id']) ;
        $this->url = $url;
        $this->hubspot_file = __DIR__ . '/hubspot_sender.php';
    }


    public static function create_rej_send_log_table_if_not_exists() {
        global $wpdb;
    
        $table_name = 'rej_send_log';
        $charset_collate = $wpdb->get_charset_collate();
    
        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" );
    
        if ( $table_exists !== $table_name ) {
            $sql = "CREATE TABLE {$table_name} (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                entry_id INT,
                email VARCHAR(255),
                phone VARCHAR(50),
                form_name VARCHAR(100),
                channel VARCHAR(100),
                salesmanago VARCHAR(100),
                hubspot_wystawca VARCHAR(100),
                hubspot_marketing VARCHAR(100),
                message TEXT,
                archive TINYINT(1) NOT NULL DEFAULT 0,
                send_at DATETIME NOT NULL,
                INDEX (email),
                INDEX (send_at)
            ) $charset_collate;";
    
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta( $sql );
        } else {
            $defs = [
                'salesmanago'        => 'VARCHAR(100)',
                'hubspot_wystawca'   => 'VARCHAR(100)',
                'hubspot_marketing'  => 'VARCHAR(100)',
                'entry_id'           => 'INT',
            ];

            $existing_cols = $wpdb->get_col( $wpdb->prepare(
                "SELECT COLUMN_NAME
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s",
                $table_name
            ) ) ?: [];

            $missing = array_values(array_diff(array_keys($defs), $existing_cols));

            if (!empty($missing)) {
                $alter_sql = "ALTER TABLE `{$table_name}` " . implode(', ', array_map(
                    function($c) use ($defs) { return "ADD `{$c}` {$defs[$c]}"; },
                    $missing
                )) . ';';
                $wpdb->query($alter_sql);
            }
        }
    }

    public function entry_data() {
        $single_form = $this->form;
        $entry_id = $this->entry['id'];

        $osPatterns = array(
            'Windows' => '/Windows NT/i',
            'MacBook' => '/Mac OS X|Macintosh/i',
            'Android' => '/Android/i',
            'iPhone'  => '/iPhone|iPad/i',
            'Linux'   => '/Linux/i',
            'Api' => '/Api/i',
        );

        $skip_fields = array(
            'captcha',
            'zgod',
            'consent',
            'konferencje',
            'kongres',
            'qrcode',
            'kod qr',
            'wystawca1',
            'wystawca2',
            'wystawca3',
            'wystawca4',
            'media1',
            'media2',
            'media3',
            'media4',
            'id',
            'bez tytu',
            'logotyp',
        );

        $save_fields = array(
            'form_id',
            'date_created',
            'id',
            'source_url',
        );

        // getting qr_code url
        $qr_feeds = GFAPI::get_feeds(NULL, $single_form['id']);
        foreach ($qr_feeds as $feed) {
            $entry_data['qr_code_url'] = gform_get_meta($entry_id, 'qr-code_feed_' . $feed['id'] . '_url');
            if (!empty($entry_data['qr_code_url'])) {
                // $entry_data['qr_code'] = $feed['id'];
                break;
            }
        }

        $entry_data['name'] = do_shortcode('[trade_fair_name]');
        $entry_data['meta'] = do_shortcode('[trade_fair_badge]');
        $entry_data['rok'] = do_shortcode('[trade_fair_catalog_year]');
        $entry_data['start'] = do_shortcode('[trade_fair_datetotimer]');
        $entry_data['end'] = do_shortcode('[trade_fair_enddata]');

        $qr_feeds = GFAPI::get_feeds(NULL, $single_form['id']);

        if(!is_wp_error($qr_feeds)){
            foreach($qr_feeds as $single_feed){
                if($single_feed['addon_slug'] == 'qr-code'){
                    foreach($single_feed['meta']['qrcodeFields'] as $qr_id => $qr_val){
                        $qr_code_meta[$qr_id] = $qr_val['custom_key'];
                    }
                }
            }
        }

        $entry_data['qr_code'] = (!empty($qr_code_meta)) ? $qr_code_meta[0] . $entry_id . $qr_code_meta[1] . $entry_id : '';
        $entry_data['form_title'] = $single_form['title'] ?? '';
        $entry_data['form_meta_id'] = (!empty($qr_code_meta)) ? $qr_code_meta[0] : '';
        $entry_data['form_meta_rnd'] = (!empty($qr_code_meta)) ? $qr_code_meta[1] : '';

        foreach($single_form['fields'] as $field_index => $single_field){
            
            $label = strtolower($single_field['label']);
            $admin_label = strtolower($single_field['adminLabel']);

            if (empty(trim($label))){
                continue;
            }

            foreach($skip_fields as $single_skip){                            
                if (stripos($label, $single_skip) !== false && stripos('tax', $label) === false){
                    continue 2;
                }
            }

            switch (true) {
                case stripos($label, 'id') !== false:
                    $form_fields['entry_id'] = $single_field['id'];
                    continue 2;

                case $admin_label == 'email' || stripos($label, 'mail') !== false:
                    $form_fields['email'] = strtolower($single_field['id']);
                    continue 2;

                case $admin_label == 'phone' || stripos($label, 'tel') !== false || stripos($label, 'phone') !== false:
                    if(empty($form_fields['telefon'])){
                        $form_fields['telefon'] = $single_field['id'];
                    }
                    continue 2;

                case $admin_label == 'full_name' || stripos($label, 'nazwisk') !== false || stripos($label, 'imie') !== false || stripos($label, 'name') !== false || stripos($label, 'osoba') !== false || stripos($label, 'imię') !== false || stripos($label, 'imiĘ') !== false:
                    $form_fields['dane'] = $single_field['id'];
                    continue 2;

                case $admin_label == 'company_name' || stripos($label, 'firm') !== false || stripos($label, 'compa') !== false:
                    $form_fields['firma'] = $single_field['id'];
                    continue 2;

                case $admin_label == 'fair_hall' || stripos($label, 'hala') !== false:
                    $form_fields['fair_hall'] = $single_field['id'];
                    continue 2;

                case $admin_label == 'fair_stamd' || stripos($label, 'stanowisko') !== false:
                    $form_fields['stand'] = $single_field['id'];
                    continue 2;

                case $admin_label == 'utm' || stripos($label, 'utm') !== false:
                    $form_fields['utm'] = $single_field['id'];
                    continue 2;

                case $admin_label == 'company_nip' || stripos($label, 'nip') !== false || stripos($label, 'tax') !== false:
                    $form_fields['nip'] = $single_field['id'];
                    continue 2;
                
                case $admin_label == 'stand_size' || stripos($label, 'powierzchnię') !== false || stripos($label, 'exhibition') !== false || stripos($label, 'area') !== false:
                    $form_fields['wielkosc_stoiska'] = $single_field['id'];
                    continue 2;

                case $admin_label == 'activation' || stripos($label, 'active') !== false || stripos($label, 'aktywac') !== false:
                    $form_fields['aktywacja'] = $single_field['id'];
                    continue 2;

                case $admin_label == 'language' || stripos($label, 'język') !== false || stripos($label, 'lang') !== false:
                    $form_fields['jezyk'] = $single_field['id'];
                    continue 2;

                case $admin_label == 'country' || stripos($label, 'country') !== false:
                    $form_fields['panstwo'] = $single_field['id'];
                    continue 2;

                case $admin_label == 'city' || stripos($label, 'city') !== false || stripos($label, 'miasto') !== false:
                    $form_fields['miasto'] = $single_field['id'];
                    continue 2;
                    
                case $admin_label == 'post' || stripos($label, 'kod') !== false || stripos($label, 'post') !== false || stripos($label, 'code') !== false:
                    $form_fields['kod_pocztowy'] = $single_field['id'];
                    continue 2;

                case $admin_label == 'street' || stripos($label, 'ulica') !== false || stripos($label, 'street') !== false:
                    $form_fields['ulica'] = $single_field['id'];
                    continue 2;

                case $admin_label == 'house' || stripos($label, 'numer u') !== false || stripos($label, 'numer domu') !== false || stripos($label, 'building') !== false:
                    $form_fields['nr_ulicy'] = $single_field['id'];
                    continue 2;

                case $admin_label == 'apartment' || stripos($label, 'Mieszkan') !== false || stripos($label, 'apartment') !== false || stripos($label, 'lokal') !== false || stripos($label, 'house') !== false:
                    $form_fields['nr_mieszkania'] = $single_field['id'];
                    continue 2;

                case $admin_label == 'addres' || stripos($label, 'adres') !== false :
                    $form_fields['adres'] = $single_field['id'];
                    continue 2;
                
                case $admin_label == 'pwe_sender' || stripos($label, 'location') !== false || stripos($label, 'kana') !== false || stripos($label, 'dane wysy') !== false:
                    $form_fields['pwe_wysylajacy'] = $single_field['id'];
                    continue 2;

                case $admin_label == 'more_info' || stripos($label, 'more info') !== false:
                    $form_fields['dodatkowe_informacje'] = $single_field['id'];
                    continue 2;

                case $admin_label == 'fair_sector' || stripos($label, 'sektor') !== false || stripos($label, 'bran') !== false:
                    $form_fields['sektory_targowe'] = $single_field['id'];
                    continue 2;

                default:
                    $form_fields[$label] = $single_field['id'];
            }
        }

        foreach($this->entry as $index => $val){
            if (empty($val)) continue;

            try {
                switch (true) {
                    case is_numeric($index) && floor($index) == $index:
                        $entry_field_name = array_search($index, $form_fields);
                        $entry_data[$entry_field_name] = $val;
                        continue 2;
                    
                    case $index == 'user_agent':
                        foreach ($osPatterns as $os_index => $os_value){
                            if (preg_match($os_value, $val)) {
                                $entry_data[$index] = $os_index;
                                continue 3;
                            } 
                        }
                        $entry_data[$index] = $val . ' custome';
                        continue 2;
                    default :
                        if (in_array($index, $save_fields)){
                            $entry_data[$index] = $val;
                        }
                }
            } catch (\Throwable $e) {
                $entry_data[$index] = '';
                error_log($e->getMessage());
                continue;
            }
        }
        
        return $entry_data;
    }

    public function init() {

        global $wpdb;
        // Check if form is one of the registration forms
        $patterns = [
            '/^\(\s*20\d{2}\s*\)\s?Rejestracja (PL|EN|Zaproszeń - call\scen.*|gości wystawców.*)(\s*\(header(?:\s*new)?\))?(\s*\(Branzowe\))?(\s*\(FB\))?$/',
            '/^\(\s*20\d{2}\s*PW\s*\)\s?Potencjalny Wystawca.*$/i',
        ];
        $pattern_mach = false;
        foreach($patterns as $pattern){
            if (preg_match($pattern, $this->form['title'])) {
                $pattern_mach = true;
                break;
            }
        }

        self::create_rej_send_log_table_if_not_exists();

        $send_info = array();

        foreach($this->form['fields'] as $f_id => $f_val){
            if($f_val['type'] == 'email'){
                $send_info['email'] = $this->entry[$f_val['id']];
            }
            if(stripos($f_val['label'], 'tel') !== false || stripos($f_val['label'], 'phone') !== false){
                $send_info['phone'] = $this->entry[$f_val['id']];
            }
            if(stripos($f_val['label'], 'kana') !== false){
                $send_info['channel'] = $this->entry[$f_val['id']];
            }
        }

        $log_entry = [
            'entry_id' => $this->entry['id'],
            'email' => $send_info['email'] ?? '',
            'phone' => $send_info['phone'] ?? '',
            'form_name' => $this->form['title'],
            'send_at' => date('Y-m-d H:i:s'),
            'channel' => $send_info['channel'] ?? '',
            'status' => 200,
            'hubspot_wystawca' => 'N/A',
            'salesmanago' => 'N/A',
            'archive' => 0,
        ];
        
        $wpdb->insert('rej_send_log', $log_entry);
        $rej_send_log_id = $wpdb->insert_id;

        $entry_data = $this->entry_data();

        if($pattern_mach){
            $send_info['lang'] = explode('_', get_locale())[0];
            include_once 'salesmanago_sender.php';

            api_sender(
                $send_info['phone'] ?? '',
                $send_info['email'] ?? '',
                $send_info['lang'] ?? '',
                $this->url, $send_info['channel'] ?? '',
                $this->form['title'] ?? '',
                $rej_send_log_id ?? ''
            );
        }

        if(mb_stripos($this->form['title'], 'zostań wystawcą') !== false){
            include_once 'hubspot_wystawca.php';
            hubspot_wystwaca_sender($entry_data, $rej_send_log_id);
        }

        include_once 'hubspot_sender.php';
        hubspot_sender($entry_data, $rej_send_log_id);
        
        /**
         *  ZAWIESZENE
         */
        // include_once 'qrcode_ticket.php';
        // proces_single_entry($this->entry['id']);
    }

    public function get_secrets($site) {
        if($site == 'hubspot'){
            global $wpdb;
            $db_returner = array();
            $table_name = $wpdb->prefix . 'custom_klavio_setup';
        
            $klavio_pre = $wpdb->prepare(
                "SELECT klavio_list_id FROM $table_name WHERE klavio_list_name = 'hubspot_secret'"
            );
        
            $db_data = $wpdb->get_results($klavio_pre);
            $db_returner = $db_data[0]->klavio_list_id;
            
            return $db_returner;
        }
    }
}   