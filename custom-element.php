<?php
/*
 * Plugin Name: Custom Element
 * Plugin URI: https://github.com/ptak-warsaw-expo-dev/custom-element
 * Description: Adding a new element to the website.
 * Version: 5.10.2
 * Author: Marek Rumianek
 * Co-authors: Anton Melnychuk, Piotr Krupniewski, Jakub Choła
 * Author URI: github.com/RumianekMarek
 */

// Czyszczenie pamięci wp_rocket
function clear_wp_rocket_cache_on_plugin_update( $plugin ) {
  // Sprawdź, czy zaktualizowana wtyczka to twoja wtyczka
  if ( 'custom-element/custom-element.php' === $plugin ) {
    // Sprawdź, czy WP Rocket jest aktywny21
    if ( function_exists( 'rocket_clean_domain' ) ) {
      // Wywołaj funkcję czyszczenia pamięci podręcznej WP Rocket
      rocket_clean_domain();
    }
  }
}

add_action( 'upgrader_process_complete', 'clear_wp_rocket_cache_on_plugin_update', 10, 2 );

function getGithubKey() {
  global $wpdb;

  $table_name = $wpdb->prefix . 'custom_klavio_setup';
  if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) != $table_name) {
    if (!is_admin()) {
      echo '<script>console.log("No KL-table")</script>';
    }
      return null;
  }

  $github_pre = $wpdb->prepare("SELECT klavio_list_id FROM $table_name WHERE klavio_list_name = %s", 'github_secret_2');
  $github_result = $wpdb->get_results($github_pre);
    
  if (!empty(trim($github_result[0]->klavio_list_id))) {
      return $github_result[0]->klavio_list_id;
  }

  if (!is_admin()) {
    echo '<script>console.log("empty Github Key")</script>';
  }

  return null;
}

// Adres autoupdate
include( plugin_dir_path( __FILE__ ) . 'plugin-update-checker/plugin-update-checker.php');
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://github.com/ptak-warsaw-expo-dev/custom-element',
	__FILE__,
	'custom-element'
);

if (getGithubKey()){
  $myUpdateChecker->setAuthentication(getGithubKey());
}

$myUpdateChecker->getVcsApi()->enableReleaseAssets();

define('CUSTOM_ELEMENT_PATH', plugin_dir_path(__FILE__));
define('CUSTOM_ELEMENT_URL', plugin_dir_url(__FILE__));

if (is_admin()) {
    // Edytor plików dostepFTP
    include_once plugin_dir_path(__FILE__) . 'FTP/main-dostepFTP.php';
        
    //opisy do Mediów
    include_once plugin_dir_path(__FILE__) . 'FTP/opisy-mediow.php';
    
    // Edytor plików dostepFTP
    include_once plugin_dir_path(__FILE__) . 'FTP/klavio.php';

    // Edytor plików dostepFTP
    include_once plugin_dir_path(__FILE__) . 'FTP/gf_importer.php';
}

// New Exhibitors Phone
include_once plugin_dir_path(__FILE__) . 'pwe-functions.php';

// Badge
include_once plugin_dir_path(__FILE__) . 'badge/badge.php';

// QR Check
include_once plugin_dir_path(__FILE__) . 'badge/qrcodecheck.php';

// QR Scanner
include_once plugin_dir_path(__FILE__) . 'qr-scanner/qr-scanner.php';

// GF Downloader
include_once plugin_dir_path(__FILE__) . 'gf_download/gf_download.php';

// GF Redirector
include_once plugin_dir_path(__FILE__) . 'gf_redirector/gf_redirector.php';

//Shortcodes
include_once plugin_dir_path(__FILE__) . 'gf_filter/gf_shortcodes.php';

// GF Form Creator
include_once plugin_dir_path(__FILE__) . 'elements/gf_form_creator/gf_form_creator.php';

// CC Registery
include_once plugin_dir_path(__FILE__) . 'elements/cc_registery/cc_registery.php';

// Registration Finder
include_once plugin_dir_path(__FILE__) . 'elements/registration_finder/registration_finder.php';

// New Exhibitors Phone
include_once plugin_dir_path(__FILE__) . 'elements/new_exhibitors_phone/new_exhibitors_phone.php';

// Voucher Generator
include_once plugin_dir_path(__FILE__) . 'elements/voucher_generator/voucher_generator.php';

// Re-sender
include_once plugin_dir_path(__FILE__) . 'elements/re_sender/re_sender.php';

// Admin Role
include_once plugin_dir_path(__FILE__) . 'admin_role/admin_role.php';



// ARCHIVE ELEMENTS !!!

// Info + Modal
include_once plugin_dir_path(__FILE__) . 'archive__display-info/display-info.php';

// Speakers
include_once plugin_dir_path(__FILE__) . 'archive__display-info/display-info-speakers.php';