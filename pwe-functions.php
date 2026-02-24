<?php 
    add_action('gform_after_submission', 'gf_integration_anchore', 10, 2);

    function gf_integration_anchore($entry, $form) {
        $current_url = home_url( add_query_arg( null, null ) );
        wp_remote_post(plugin_dir_url(__FILE__) . 'action_handler.php' , [
            'body' => [
                'element' => 'gform_after_submission',
                'entry_id' => $entry['id'],
                'url' => $current_url
            ],
            'timeout' => 0.01,
            'blocking' => false,
        ]);
    }

    add_action('init', function() {
        if (wp_next_scheduled('cerber_scheduled_hash')) {
            wp_clear_scheduled_hook('cerber_scheduled_hash');
        }
    });
    
    add_action('template_redirect', function () {
        if (is_front_page()) {
        add_filter( 'wpseo_json_ld_output', '__return_false' );
        }
    });