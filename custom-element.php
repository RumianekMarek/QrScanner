<?php
/*
Plugin Name: Custom Element
Plugin URI:
Description: Adding a new element to the website.
Version: 4.8.3
Author: Marek Rumianek
Author URI: github.com/RumianekMarek
*/

// Czyszczenie pamięci wp_rocket
function clear_wp_rocket_cache_on_plugin_update( $plugin ) {
  // Sprawdź, czy zaktualizowana wtyczka to twoja wtyczka
  if ( 'custom-element/custom-element.php' === $plugin ) {
    // Sprawdź, czy WP Rocket jest aktywny
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

      $github_pre = $wpdb->prepare("SELECT klavio_list_id FROM $table_name WHERE klavio_list_name = %s", 'github_secret');
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
	'https://github.com/RumianekMarek/Custom-Element',
	__FILE__,
	'custom-element'
);

if (getGithubKey()){
  $myUpdateChecker->setAuthentication(getGithubKey());
}

$myUpdateChecker->getVcsApi()->enableReleaseAssets();

if (is_admin()) {
    // Edytor plików dostepFTP
    include_once plugin_dir_path(__FILE__) . '/FTP/main-dostepFTP.php';
        
    //opisy do Mediów
    include_once plugin_dir_path(__FILE__) . '/FTP/opisy-mediow.php';
    
    // Edytor plików dostepFTP
    include_once plugin_dir_path(__FILE__) . '/FTP/klavio.php';

    // Edytor plików dostepFTP
    include_once plugin_dir_path(__FILE__) . '/FTP/gf_importer.php';
}

// My Custom Element
include_once plugin_dir_path(__FILE__) . '/my-custom-element/main-custom-element.php';

// Katalog wystawców
include_once plugin_dir_path(__FILE__) . '/katalog-wystawcow/main-katalog-wystawcow.php';

// Info + Modal
include_once plugin_dir_path(__FILE__) . '/display-info/display-info.php';

// Speakers
include_once plugin_dir_path(__FILE__) . '/display-info/display-info-speakers.php';

// Badge
include_once plugin_dir_path(__FILE__) . '/badge/badge.php';

// QR Check
include_once plugin_dir_path(__FILE__) . '/badge/qrcodecheck.php';

// QR Scanner
include_once plugin_dir_path(__FILE__) . '/qr-scanner/qr-scanner.php';

// GF Downloader
include_once plugin_dir_path(__FILE__) . '/gf_download/gf_download.php';

// GF Redirector
include_once plugin_dir_path(__FILE__) . '/gf_redirector/gf_redirector.php';

// Media Gallery
include_once plugin_dir_path(__FILE__) . '/media_gallery/media_gallery.php';

// Opinion Slider
include_once plugin_dir_path(__FILE__) . '/opinions_slider/opinions_slider.php';

// GF Form Creator
include_once plugin_dir_path(__FILE__) . '/elements/gf_form_creator/gf_form_creator.php';

// CC Registery
include_once plugin_dir_path(__FILE__) . '/elements/cc_registery/cc_registery.php';

// Registration Finder
include_once plugin_dir_path(__FILE__) . '/elements/registration_finder/registration_finder.php';

// New Exhibitors Phone
include_once plugin_dir_path(__FILE__) . '/elements/new_exhibitors_phone/new_exhibitors_phone.php';

// New Exhibitors Phone
include_once plugin_dir_path(__FILE__) . '/elements/voucher_generator/voucher_generator.php';