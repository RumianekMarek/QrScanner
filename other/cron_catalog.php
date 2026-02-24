<?php

$key_cat = 'iR8gCdZlITxRvVBS';
$pass_cat = null;

// HTTP – parameter ?pass=...
if (isset($_GET['pass'])) {
    $pass_cat = trim($_GET['pass']);
}

// Verification
if (($pass_cat !== null && $key_cat !== null && $pass_cat === $key_cat) ||
    ($pass !== null && $secret !== null && $pass === $secret)) {

    $wp_load = str_replace('private_html', 'public_html', $_SERVER["DOCUMENT_ROOT"]) . '/wp-load.php';

    if (file_exists($wp_load)) {
        require_once($wp_load);
    }

    if ($pass_cat === $key_cat) {
        echo 'Katalog jest zaktualizowany!';
    }

} else {
    // Block access
    $remote_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    error_log('[' . date('Y-m-d H:i:s') . "] Próba nieautoryzowanego dostępu z IP: {$remote_ip}");

    header('HTTP/1.1 403 Forbidden');
    echo 'Access denied.';
    exit;
}

$hour = (int) current_time('H');

// Do not perform between 11pm and 6am
if ($hour >= 23 || $hour < 6) {
    return;
}

$server_dir = $args_array[0] == null ? $_SERVER["DOCUMENT_ROOT"] : $args_array[0];
$domain_adress = do_shortcode('[trade_fair_domainadress]');
$fair_name = do_shortcode('[trade_fair_name]');
$fair_date = do_shortcode('[trade_fair_date]');

// <---------------------------------------------------------------------------------------------------------------<
// OLD CATALOG (from expoplanner) MULTI-ID VERSION
// <---------------------------------------------------------------------------------------------------------------<
$catalog_ids_old = do_shortcode('[trade_fair_catalog]'); // ex. 1787 OR 13,45,93
$catalogs_archive_old = do_shortcode('[trade_fair_catalog_archive]'); // ex. 2025-1787,1788;2024-1233;...

$today = new DateTime();
$token = md5("#22targiexpo22@@@#" . $today->format('Y-m-d'));

$exh_catalog_address = PWECommonFunctions::get_database_meta_data('exh_catalog_address');

if (empty($exh_catalog_address)) {
    throw new Exception('Brak exh_catalog_address');
}

$baseDir = $server_dir . '/wp-content/uploads/exhibitor-catalogs/';
if (!is_dir($baseDir)) {
    mkdir($baseDir, 0775, true);
}

// CATCH
function handleCatalogError(
    Exception $e,
    string $url,
    string $contextLabel,
    string $domain_adress,
    string $fair_name,
    int $hour
): void {

    if ($hour !== 8 && !isset($_GET['pass'])) {
        return;
    }

    $to = [
        'anton.melnychuk@warsawexpo.eu',
        'marek.rumianek@warsawexpo.eu',
        'natalia.kulik@warsawexpo.eu',
        'piotr.krupniewski@warsawexpo.eu'
    ];

    $subject = '[CRON] (CATALOG) Błąd pobierania wystawców ' . $domain_adress;

    $message =
        'Kontekst: <strong>' . $contextLabel . '</strong><br>' .
        'Błąd:<br><strong>' . $e->getMessage() . '</strong>';

    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $fair_name . ' - [CRON][EXHIBITOR CATALOG][' . $domain_adress . '] <noreply@' . $domain_adress . '>'
    ];

    wp_mail($to, $subject, $message, $headers);
}

// FUNCTION: FETCH AND MERGE OLD CATALOGS
$fetchAndMergeOldCatalogs = function(array $catalog_ids_old, $exh_catalog_address, $token) {

    $context = stream_context_create([
        'http' => ['timeout' => 10]
    ]);

    $mergedExhibitors = [];
    $mergedTargi = [];
    $allStarts = [];
    $allEnds = [];
    $counter = 1;

    foreach ($catalog_ids_old as $single_id) {

        $url = $exh_catalog_address . $token . '&id_targow=' . $single_id .'&v='. time();

        $json = @file_get_contents($url, false, $context);
        if ($json === false) {
            continue;
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            continue;
        }

        $key = array_key_first($data);
        if (!$key || empty($data[$key])) {
            continue;
        }

        // ['Targi']
        if (!empty($data[$key]['Targi'])) {
            $mergedTargi[] = trim($data[$key]['Targi']);
        }

        // ['Edycja'] (date range)
        if (!empty($data[$key]['Edycja']) && str_contains($data[$key]['Edycja'], ' - ')) {
            [$start, $end] = explode(' - ', $data[$key]['Edycja']);

            $allStarts[] = trim($start);
            $allEnds[]   = trim($end);
        }

        // ['Wystawcy']
        $wystawcy = $data[$key]['Wystawcy'] ?? [];

        foreach ($wystawcy as $exhibitor) {
            $index = number_format($counter, 2, '.', '');
            $mergedExhibitors[$index] = $exhibitor;
            $counter++;
        }
    }

    if (empty($mergedTargi) || empty($mergedExhibitors)) {
        return [];
    }

    // FINAL ID KEY 
    $combinedKey = implode(',', $catalog_ids_old);

    // FINAL DATE RANGE 
    $finalDateRange = '';

    if (!empty($allStarts) && !empty($allEnds)) {
        $globalStart = min($allStarts);
        $globalEnd   = max($allEnds);
        $finalDateRange = $globalStart . ' - ' . $globalEnd;
    }

    return [
        $combinedKey => [
            'Targi' => implode(', ', array_unique($mergedTargi)),
            'Edycja' => $finalDateRange,
            'Wystawcy' => $mergedExhibitors
        ]
    ];
};

// CURRENT CATALOG → old-pwe-exhibitors.json
if (!empty($catalog_ids_old) && stripos($fair_date, 'nowa data') === false) {

    $catalog_ids_old = array_filter(array_map('trim', explode(',', (string)$catalog_ids_old)));
    $currentFile = $baseDir . 'old-pwe-exhibitors.json';

    try {

        $finalData = $fetchAndMergeOldCatalogs($catalog_ids_old, $exh_catalog_address, $token);

        if (empty($finalData)) {
            throw new Exception('Nie udało się pobrać żadnego katalogu.');
        }

        file_put_contents(
            $currentFile,
            json_encode($finalData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

    } catch (Exception $e) {

        handleCatalogError(
            $e,
            '',
            'AKTUALNY KATALOG',
            $domain_adress,
            $fair_name,
            $hour
        );
    }
}

// ARCHIVE CATALOG → old-pwe-exhibitors-{year}.json
if (!empty($catalogs_archive_old)) {

    $archives = array_filter(array_map('trim', explode(';', $catalogs_archive_old)));

    foreach ($archives as $archive) {

        if (!str_contains($archive, '-')) {
            continue;
        }

        [$year, $ids] = explode('-', $archive, 2);

        if (empty($year) || empty($ids)) {
            continue;
        }

        $archiveFile = $baseDir . 'old-pwe-exhibitors-' . $year . '.json';

        if (file_exists($archiveFile) && filesize($archiveFile) > 0) {
            continue;
        }

        try {

            $catalog_ids_old = array_filter(array_map('trim', explode(',', $ids)));

            $finalData = $fetchAndMergeOldCatalogs($catalog_ids_old, $exh_catalog_address, $token);

            if (empty($finalData)) {
                throw new Exception('Brak danych dla archiwum ' . $year);
            }

            file_put_contents(
                $archiveFile,
                json_encode($finalData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );

        } catch (Exception $e) {

            handleCatalogError(
                $e,
                '',
                'ARCHIWUM ' . $year,
                $domain_adress,
                $fair_name,
                $hour
            );
        }
    }
}


// <---------------------------------------------------------------------------------------------------------------<
// NEW CATALOG 
// <---------------------------------------------------------------------------------------------------------------<
$catalog_ids = do_shortcode('[trade_fair_catalog_id]'); // ex. 17,23,24...
$catalogs_id_archive = do_shortcode('[trade_fair_catalog_id_archive]'); // ex. 2025-47,48;2024-78,79;...
$exh_catalog_address_2 = PWECommonFunctions::get_database_meta_data('exh_catalog_address_2');

$baseDir = rtrim($server_dir, '/\\') . '/wp-content/uploads/exhibitor-catalogs/';
if (!is_dir($baseDir)) {
    mkdir($baseDir, 0775, true);
}

// CATCH
function handleCatalogError2(
    Throwable $e,
    string $contextLabel,
    string $domain_adress,
    string $fair_name,
    int $hour
) {
    if ($hour !== 8 && !isset($_GET['pass'])) {
        return;
    }

    $to = [
        'anton.melnychuk@warsawexpo.eu',
        'marek.rumianek@warsawexpo.eu',
        'natalia.kulik@warsawexpo.eu',
        'piotr.krupniewski@warsawexpo.eu'
    ];

    $subject = "[CRON] ($contextLabel) Błąd pobierania wystawców $domain_adress";
    $message = "<p>Kontekst: <strong>$contextLabel</strong></p>"
             . "<p>Błąd: <strong>" . esc_html($e->getMessage()) . "</strong></p>"
             . "<pre style='white-space:pre-wrap;word-break:break-word;'>" 
             . esc_html($e->getTraceAsString()) 
             . "</pre>";

    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . esc_html($fair_name) . " [CRON][EXHIBITOR CATALOG][$domain_adress] <noreply@$domain_adress>"
    ];

    try {
        wp_mail($to, $subject, $message, $headers);
    } catch (Throwable $mailErr) {
        error_log("[" . date('Y-m-d H:i:s') . "] (CRON) Błąd wysyłki maila: " . $mailErr->getMessage());
        
    }
}

// FUNCTION: FETCH AND MERGE OLD CATALOGS
$getAllExhibitors = function($catalogIds, $exh_catalog_address_2) {
    $all_exhibitors = ['exhibitors' => []];

    foreach ($catalogIds as $catalog_id) {
        $catalog_url = "{$exh_catalog_address_2}{$catalog_id}/exhibitors.json";

        $res = wp_remote_get($catalog_url, [
            'timeout' => 10,
            'headers' => ['Accept' => 'application/json'],
        ]);

        if (is_wp_error($res)) {
            throw new Exception("Błąd połączenia z API: " . $res->get_error_message());
        }

        $status = wp_remote_retrieve_response_code($res);
        if ($status !== 200) {
            throw new Exception("Nieprawidłowy kod HTTP: {$status} dla URL: {$catalog_url}");
        }

        $body = (string) wp_remote_retrieve_body($res);
        if (trim($body) === '') {
            throw new Exception("Pobrano pustą odpowiedź z API: {$catalog_url}");
        }

        $data = json_decode($body, true);
        if (!is_array($data) || empty($data['success']) || !is_array($data['exhibitors'])) {
            throw new Exception("Niepoprawna struktura danych w: {$catalog_url}");
        }

        $all_exhibitors['exhibitors'] = array_merge($all_exhibitors['exhibitors'], $data['exhibitors']);
    }

    return $all_exhibitors;
};

// CURRENT CATALOG → pwe-exhibitors.json
if (!empty($catalog_ids) && stripos($fair_date, 'nowa data') === false) {
    try {
        $catalog_array = array_map('intval', array_map('trim', explode(',', $catalog_ids)));

        $allExhibitors = $getAllExhibitors($catalog_array, $exh_catalog_address_2);

        // delete people
        foreach ($allExhibitors['exhibitors'] as &$exhibitor) {
            unset($exhibitor['people']);
        }
        unset($exhibitor);

        $target_file = $baseDir . '/pwe-exhibitors.json';
        file_put_contents($target_file, json_encode($allExhibitors['exhibitors'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    } catch (Throwable $e) {
        handleCatalogError2($e, 'AKTUALNY KATALOG', $domain_adress, $fair_name, $hour);
    }
}

// ARCHIVE CATALOG → pwe-exhibitors-{year}.json
if (!empty($catalogs_id_archive)) {
    $archives = array_filter(array_map('trim', explode(';', $catalogs_id_archive)));

    foreach ($archives as $archive) {
        // format: YEAR-ID1,ID2,...
        if (!str_contains($archive, '-')) continue;
        [$year, $ids] = explode('-', $archive, 2);
        if (empty($year) || empty($ids)) continue;

        $archive_file = $baseDir . "pwe-exhibitors-$year.json";
        if (file_exists($archive_file) && filesize($archive_file) > 0) continue;

        try {
            $catalog_array = array_map('intval', array_map('trim', explode(',', $ids)));
            $allExhibitors = $getAllExhibitors($catalog_array, $exh_catalog_address_2);

            // delete people
            foreach ($allExhibitors['exhibitors'] as &$exhibitor) {
                unset($exhibitor['people']);
            }
            unset($exhibitor);

            file_put_contents($archive_file, json_encode($allExhibitors['exhibitors'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        } catch (Throwable $e) {
            handleCatalogError2($e, "ARCHIWUM $year", $domain_adress, $fair_name, $hour);
        }
    }
}
