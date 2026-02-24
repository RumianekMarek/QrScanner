<?php 
    function ticket_count_checker(){
        global $wpdb;

        $feeds = GFAPI::get_feeds(null, null, 'qr-code');
        if (is_wp_error($feeds) || empty($feeds)) {
            exit();
        }

        $allowed_form_ids = array_unique(array_column($feeds, 'form_id'));

        $allowed_forms = array_filter($allowed_form_ids, function($a_form) {
            $former = GFAPI::get_form($a_form);
            return $former['is_trash'] == 0;
        });

        $placeholders = implode(',', array_fill(0, count($allowed_forms), '%d'));

        // bezpieczne przygotowanie zapytania
        $sql_needed = $wpdb->prepare(
            "
            SELECT e.id, e.form_id
            FROM {$wpdb->prefix}gf_entry e
            LEFT JOIN {$wpdb->prefix}gf_entry_meta em
                ON e.id = em.entry_id
            AND em.meta_key = %s
            WHERE (em.meta_value IS NULL OR em.meta_value = '')
            AND e.form_id IN ($placeholders)
            ",
            array_merge(['ticket_image_3'],$allowed_form_ids)
        );

        $ticketsNeeded = $wpdb->get_results($sql_needed);

        $sql_created = $wpdb->prepare(
            "
            SELECT e.id, e.form_id
            FROM {$wpdb->prefix}gf_entry e
            INNER JOIN {$wpdb->prefix}gf_entry_meta em
                ON e.id = em.entry_id
            AND em.meta_key = %s
            AND em.meta_value <> ''
            WHERE e.form_id IN ($placeholders)
            ",
            array_merge(['ticket_image_3'], $allowed_form_ids)
        );

        $ticketsCreated = $wpdb->get_results($sql_created);

        print_r('Ilość biletów do wygenerowania - ' . count($ticketsNeeded));
        echo '<br>';
        print_r('Ilość biletów wygenerowanych - ' . count($ticketsCreated));

        $hour = (int) current_time('H');
        if ( $hour ==  7) {
            if (count($ticketsNeeded) > 0) {
                $to = 'jakub.koscielak@warsawexpo.eu';
                $subject = do_shortcode('[trade_fair_domainadress]') . ' do wygenerowania - ' . count($ticketsNeeded);
                $message = sprintf(
                    'Ilość biletów do wygenerowania - ' . count($ticketsNeeded) . '<br> Ilość biletów wygenerowanych - ' . count($ticketsCreated)
                );
                $headers = array('Content-Type: text/html; charset=UTF-8');

                $sent = wp_mail($to, $subject, $message, $headers);
            }
        }          
    }

    function pwe_get_meta_withaut_key(string $key, array $forms, int $genCounter = 20) {
        global $wpdb;

        $allowed_forms = implode(',', array_fill(0, count($forms), '%d'));

        // bezpieczne przygotowanie zapytania
        $sql = $wpdb->prepare(
            "
            SELECT e.id, e.form_id
            FROM {$wpdb->prefix}gf_entry e
            LEFT JOIN {$wpdb->prefix}gf_entry_meta em
            ON e.id = em.entry_id AND em.meta_key = %s
            WHERE em.meta_value IS NULL
            AND e.form_id IN ($allowed_forms)
            LIMIT %d
            ",
            array_merge([$key], $forms, [$genCounter])
        );

        return $wpdb->get_results($sql);
    }

    function create_qrcode_tickets (int $genCounter = 20) {

        $feeds = GFAPI::get_feeds(null, null, 'qr-code');
        if (is_wp_error($feeds) || empty($feeds)) {
            return;
        }

        $feeds_by_form = array_reduce($feeds, function($carry, $feed) {             
            $carry[$feed['form_id']] = $feed; 
            return $carry; 
        }, []);

        $allowed_form_ids = array_unique(array_column($feeds, 'form_id'));

        $allowed_forms = array_filter($allowed_form_ids, function($a_form) {
            $former = GFAPI::get_form($a_form);
            return $former['is_trash'] == 0;
        });

        $entries = pwe_get_meta_withaut_key('ticket_image_3', $allowed_forms, $genCounter);

        array_walk($entries, function($entry) use ($feeds_by_form) {
            proces_single_entry($entry->id, $feeds_by_form[$entry->form_id]);            
        });
    }

    function proces_single_entry(int $entry_id, $feed = null){
        if(empty($feed)){
            $entry = GFAPI::get_entry($entry_id);

            $feeds = GFAPI::get_feeds(null, $entry['form_id'], 'qr-code');
                if (empty($feeds) || is_wp_error($feeds)) {
                    return;
                }
            $feed = $feeds[0];
        }
        $qrCode = $feed['meta']['qrcodeFields'][0]['custom_key'] . $entry_id . $feed['meta']['qrcodeFields'][1]['custom_key'] . $entry_id;
        $qrUrl = gform_get_meta($entry_id, 'qr-code_feed_' . ($feed['id']) . '_url' );
        if (!$qrUrl){
            gform_update_meta($entry_id, 'ticket_image_3', 'QrUrlError');
            echo 'qr-code URL error for ' . $entry_id . '<br>';
            return;
        }
        create_ticket($qrUrl, $qrCode, $entry_id);
    }

    function proces_entries_with_qrcode(int $formId, ?array $feed = null, int $genCounter = 20){
        $returner   = [];
        $total = 0;
        $page  = ['page_size' => 200, 'offset' => 0];
        do {
            $batch = GFAPI::get_entries($formId, null, null, $page, $total);
            $count = 0;
            foreach ($batch as &$entry) {
                $ticket = gform_get_meta(
                    $entry['id'],
                    'ticket_image_2'
                );

                if ($ticket) {
                    continue;
                }

                $qrUrl = gform_get_meta(
                    $entry['id'],
                    'qr-code_feed_' . ($feed['id']) . '_url'
                );
                
                $qrCode = $feed['meta']['qrcodeFields'][0]['custom_key'] . $entry['id'] . $feed['meta']['qrcodeFields'][1]['custom_key'] . $entry['id'];

                create_ticket($qrUrl, $qrCode, $entry['id']);
                $count++;
                if($count > 1){
                    exit;
                }
            }

            $returner   = array_merge($returner, $batch);
            $page['offset'] += $page['page_size'];
        } while (!empty($batch) && $page['offset'] < $total);
    }

    function create_ticket(string $qrUrl, string $qrCode, int $entry_id) {
        $qrExplode = explode('/wp-content/', $qrUrl);
        $qrPath = ABSPATH . '/wp-content/' . $qrExplode[1];
        if(!file_exists($qrPath)){
            gform_update_meta($entry_id, 'ticket_image_3', 'qr file not found', 0);
            return;
        }

        $edition = mb_strtoupper(do_shortcode('[trade_fair_edition]').'  Edycja', 'UTF-8');
        $dateTxt = do_shortcode('[trade_fair_date_custom_format]') . ' WARSZAWA';
        $descPL  = do_shortcode('[trade_fair_desc]');
        $descEN  = do_shortcode('[trade_fair_desc_eng]');
        $colorAccent = 'black';

        $logoUrl = site_url('/doc/logo.png');
        $logoPWE = site_url('/doc/pwe_logo.svg');
        $logoPWE = site_url('/wp-content/plugins/pwe-media/media/logo_pwe_black.webp');
        $logoPath = ABSPATH . 'doc/logo.png';
        
        if (!file_exists($logoPath)) {
            $to = 'marek.rumianek@warsawexpo.eu';
            $subject = 'Brak logo';
            $message = sprintf(
                'Brak logo.png na ' . $logoUrl
            );
            $headers = array('Content-Type: text/html; charset=UTF-8');

            $sent = wp_mail($to, $subject, $message, $headers);
            exit;
        }

        $uploads = wp_upload_dir();
        $dir  = trailingslashit($uploads['basedir']).'tickets';
        $url  = trailingslashit($uploads['baseurl']).'tickets';
        wp_mkdir_p($dir);
        
        $width = 500;
        $height = 680;
        
        $im = new Imagick();
        $im->setResolution(300, 300);
        $im->newImage($width, $height, new ImagickPixel('white'));
        $im->setImageFormat('jpg');

        // Logotyp 1
        $logo1 = new Imagick($logoUrl);
        $w = 150;
        $h = intval($logo1->getImageHeight() * ($w / $logo1->getImageWidth()));
        $logo1->scaleImage($w, $h);

        $headerHeight = 140;
        $header = new Imagick();
        $header->newImage($width, $headerHeight, new ImagickPixel($colorAccent));

        // Dodajemy tekst headera
        $drawHeader = new ImagickDraw();

        // Logotyp 2
        $logo2 = new Imagick($logoPWE);
        $w2 = 70;
        $h2= 70;
        $logo2->scaleImage($w2, $h2);

        // Pozycjonowanie: obliczamy start X, żeby były wyśrodkowane razem
        $totalWidth = $w + $w2;
        $startX = ($width - $w) / 2;

         if($h < 75){
            $headOfSet = (int) floor(($headerHeight - $h) / 2);
        } else {
            $headOfSet = 10;
        }

        $header->compositeImage($logo1, Imagick::COMPOSITE_OVER, $startX, $headOfSet);
        $header->compositeImage($logo2, Imagick::COMPOSITE_OVER, (420), 10);

        $drawHeader->setFontSize(19);
        $drawHeader->setFillColor('white');
        $drawHeader->setFontWeight(800);
        $drawHeader->setGravity(Imagick::GRAVITY_NORTH);
        $header->annotateImage($drawHeader, 0, $h + $headOfSet + 10, 0, $dateTxt);

        $im->compositeImage($header, Imagick::COMPOSITE_OVER, 0, 0);

        // Dodanie kilku linii tekstu poniżej loga
        $drawText = new ImagickDraw();
        $drawText->setFontSize(20);
        $drawText->setFillColor($colorAccent);
        $drawText->setGravity(Imagick::GRAVITY_NORTH);
        $drawText->setFontWeight(800);

        $line1 = [
            "TO JEST TWÓJ BILET BRANŻOWY",
            "ZESKANUJ GO NA WEJŚCIU",
        ];

        $lineli = [
            "NA TARGACH CZEKAJĄ NA CIEBIE:",
            "• SPOTKANIA Z WYSTAWCAMI",
            "• KONFERENCJE I PRELEKCJE",
            "• NAJNOWSZE TRENDY I ROZWIĄZANIA",
        ];

        $line2 = [
            "DO ZOBACZENIA",
            "NA TARGACH",
        ];

        $yOffset = $headerHeight + 10;
        foreach ($line1 as $line) {
            $im->annotateImage($drawText, 0, $yOffset, 0, $line);
            $yOffset += 25; // odstęp między liniami
        }

        // Dodanie QR kodu poniżej tekstu
        $qr = new Imagick($qrUrl); // plik QR kodu
        $qrH = $qrW = 300;
        $qr->scaleImage($qrW, $qrH, true);
        $qrStart = ($width - $qr->getImageWidth()) / 2;
        $im->compositeImage(
            $qr,
            Imagick::COMPOSITE_OVER,
            $qrStart,
            $yOffset
        );

        $drawTextLi = new ImagickDraw();
        $drawTextLi->setFontSize(16);
        $drawTextLi->setFillColor($colorAccent);
        $drawTextLi->setFontWeight(400);
        
        $yOffset = $yOffset + $qrH + 20;
        foreach ($lineli as $line) {
            $im->annotateImage($drawTextLi, $qrStart, $yOffset, 0, $line);
            $yOffset += 25; // odstęp między liniami
        }

        $drawText->setGravity(Imagick::GRAVITY_NORTH);
        $drawText->setFontWeight(800);

        $line2 = [
            "DO ZOBACZENIA",
            "NA TARGACH",
        ];

        foreach ($line2 as $line) {
            $im->annotateImage($drawText, 0, $yOffset, 0, $line);
            $yOffset += 25; // odstęp między liniami
        }
        
        $im->setImageCompressionQuality(80);
        $filepath = $dir . '/' .  $qrCode . '.jpg';
        $fileUrl = $url  . '/' .  $qrCode . '.jpg';
        $im->writeImage($filepath);
        $im->clear();
        $im->destroy();
        
        gform_update_meta($entry_id, 'ticket_image_3', $fileUrl, 0);

        echo '<img style="width:500px; border: 1px solid;" src="' . $fileUrl .'">';
    };