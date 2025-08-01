<?php
if (!defined('ABSPATH')) {
    exit;
}

class WinShirt_Admin {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu'));
    }

    public function add_menu() {
        add_menu_page(
            __('WinShirt', 'winshirt'),
            __('WinShirt', 'winshirt'),
            'manage_options',
            'winshirt',
            array($this, 'display_page'),
            'dashicons-admin-generic'
        );
    }

    public function display_page() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('WinShirt Settings', 'winshirt') . '</h1>';
        echo '<p>' . esc_html__('Admin interface coming soon...', 'winshirt') . '</p>';
        echo '</div>';
    }
}

?>
