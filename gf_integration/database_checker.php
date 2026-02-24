<?php

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

//Finding wp-load.php path
if($_SERVER["DOCUMENT_ROOT"] != ""){
    $new_url = str_replace('private_html','public_html',$_SERVER["DOCUMENT_ROOT"]) .'/wp-load.php';
} else {
    $args_array = explode('wp-content', $argv[0]);
    $new_url = $args_array[0] . '/wp-load.php';
}

// Check if wp-load.php is in find path.
if (file_exists($new_url)) {
    require_once($new_url);
}

/**
 * Ensure the rej_send_log table exists and is up-to-date.
 * - Creates the table if missing (via dbDelta)
 * - Adds critical columns if they don't exist (simple migration)
 */
function create_rej_send_log_table_if_not_exists() {
    global $wpdb;

    // Use WP table prefix for multi-site / conventions
    $table_name = $wpdb->prefix . 'rej_send_log';
    $charset_collate = $wpdb->get_charset_collate();

    // Check presence using a prepared statement (defensive)
    $table_exists = $wpdb->get_var(
        $wpdb->prepare("SHOW TABLES LIKE %s", $table_name)
    );

    if ( $table_exists !== $table_name ) {
        // If the table doesn't exist, create it with the target schema
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
            INDEX (send_at),
            INDEX (entry_id) -- speeds up lookups by entry
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql ); // dbDelta handles create/adjust per definition
    } else {
        // Table exists: ensure critical columns are present (lightweight migration)
        $defs = [
            'salesmanago'        => 'VARCHAR(100)',
            'hubspot_wystawca'   => 'VARCHAR(100)',
            'hubspot_marketing'  => 'VARCHAR(100)',
            'entry_id'           => 'INT',
        ];

        // Read existing column names from information_schema (current DB)
        $existing_cols = $wpdb->get_col( $wpdb->prepare(
            "SELECT COLUMN_NAME
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s",
            $table_name
        ) ) ?: [];

        // Determine which columns are missing
        $missing = array_values(array_diff(array_keys($defs), $existing_cols));

        if (!empty($missing)) {
            // Add all missing columns in a single ALTER for efficiency if nedded
            $alter_sql = "ALTER TABLE `{$table_name}` " . implode(', ', array_map(
                function($c) use ($defs) { return "ADD `{$c}` {$defs[$c]}"; },
                $missing
            )) . ';';
            $wpdb->query($alter_sql);
        }
    }
}

if ($_SESSION['password'] != PWE_DB_PASSWORD_4 && isset($_POST['password_submit']) && $_POST['password'] === PWE_DB_PASSWORD_4){
    $_SESSION['password'] = $_POST['password'];
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

if ($_SESSION['password'] === PWE_DB_PASSWORD_4){
    create_rej_send_log_table_if_not_exists();

    /** ZAWIESZONE
     * Buttons options
     * hubspot_update -> Update hubspot entries with correct data
     * hubspot_add -> Add missing data to hubspot
     * ticket_generator -> Create tickets jpg for every visitor
     */

    // <form id='integration_action' action='' method='post'>
    //     <input type='text' name='day_data'>
    //     <input type='submit' name='day_check' id='day_check' value='Fokus poprawki'>
    // </form>

    echo "
        <form id='integration_action' action='' method='post'>
            <input type='submit' name='hubspot_update' id='hubspot_update' value='Popraw rekordy'>
        </form>
        <form id='integration_action' action='' method='post'>
            <input type='submit' name='hubspot_add' id='hubspot_add' value='Dodaj brakujące rekordy'>
        </form>
        <form id='integration_action' action='' method='post'>
            <input type='submit' name='ticket_generator' id='ticket_generator' value='Wygeneruj Bilety'>
        </form>
        <form id='integration_action' action='' method='post'>
            <input type='submit' name='ticket_check' id='ticket_check' value='Sprawdź Bilety'>
        </form>
        ";



    // Update hubspot entries with correct data
    if (isset($_POST['hubspot_update'])){
        echo '<pre>';
        require_once 'hubspot_update.php';
    
    // Add missing data to hubspot
    } else if (isset($_POST['hubspot_add'])){
        echo '<pre>';
        require_once 'hubspot_add.php';
    
    // Create tickets jpg for every visitor
    } else if (isset($_POST['ticket_generator'])){
        echo '<pre>';
        
        include_once 'qrcode_ticket.php';
        $genCounter = 0;
        $hour = (int) current_time('H');
        if ( $hour >= 20 && $hour < 8 ) {
            $genCounter = 500;
        } else {
            $genCounter = 50;
        }

        create_qrcode_tickets($genCounter);
    } else if (isset($_POST['ticket_check'])){
        include_once 'qrcode_ticket.php';
        ticket_count_checker();

    // Update hubspot entries with missed data
    } else if (isset($_POST['day_check'])){
        $fokusHubspotUrl = plugins_url('fokus_hubspot_sender.php', __FILE__);
        $filePath = wp_upload_dir()['basedir'] . '/fokus_log/fokus_json_' . $_POST['day_data'] . '.jsonl';
        $fh = fopen($filePath, 'r');

        while (($line = fgets($fh)) !== false) {
            $line = rtrim($line, "\r\n");

            if ($line === '') continue;

            $res = wp_remote_post($fokusHubspotUrl , [
            'headers' => ['Content-Type' => 'application/x-ndjson'],
            'body'    => $line,
            'timeout' => 2,
            ]);
            printf($line);
            echo '<br><br>';
        }
        fclose($fh);
    }
} else {
    echo'
        <div>
            <form id="csvForm" enctype="multipart/form-data" action="" method="post">
                <label>Wpisz hasło</label><br>
                <input type="password" id="password" name="password"required/>
                <button id="password-submit" name="password_submit">Log In</button>
            </form>
        </div>
    ';
}
