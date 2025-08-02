<?php
/**
 * Plugin Name: WinShirt
 * Description: Plugin WordPress pour personnalisation textile et gestion de loteries.
 * Version: 3.0
 * Author: Shakass
*/

if (!defined('ABSPATH')) {
    exit;
}

define('WINSHIRT_VERSION', '3.0');
define('WINSHIRT_PATH', plugin_dir_path(__FILE__));

autoload();

function autoload() {
    require_once WINSHIRT_PATH . 'includes/class-winshirt-product-customization.php';
    require_once WINSHIRT_PATH . 'includes/class-winshirt-modal.php';
}

function winshirt_init() {
    new WinShirt_Product_Customization();
}
add_action('plugins_loaded', 'winshirt_init');

add_action('admin_menu', 'winshirt_register_settings_page');
add_action('admin_init', 'winshirt_register_settings');

function winshirt_register_settings_page() {
    add_menu_page(
        'WinShirt Settings',
        'WinShirt',
        'manage_options',
        'winshirt-settings',
        'winshirt_settings_page_html',
        'dashicons-admin-generic',
        60
    );
}

function winshirt_register_settings() {
    register_setting('winshirt_settings_group', 'winshirt_settings', 'winshirt_sanitize_settings');

    add_settings_section(
        'winshirt_settings_section_main',
        __('Paramètres généraux', 'winshirt'),
        'winshirt_settings_section_main_cb',
        'winshirt-settings'
    );

    add_settings_field(
        'winshirt_ftp_host',
        __('FTP Host', 'winshirt'),
        'winshirt_field_ftp_host_cb',
        'winshirt-settings',
        'winshirt_settings_section_main'
    );

    add_settings_field(
        'winshirt_ftp_user',
        __('FTP Username', 'winshirt'),
        'winshirt_field_ftp_user_cb',
        'winshirt-settings',
        'winshirt_settings_section_main'
    );

    add_settings_field(
        'winshirt_ftp_pass',
        __('FTP Password', 'winshirt'),
        'winshirt_field_ftp_pass_cb',
        'winshirt-settings',
        'winshirt_settings_section_main'
    );
}

function winshirt_sanitize_settings($input) {
    $output = [];
    $output['ftp_host'] = sanitize_text_field($input['ftp_host'] ?? '');
    $output['ftp_user'] = sanitize_text_field($input['ftp_user'] ?? '');
    $output['ftp_pass'] = sanitize_text_field($input['ftp_pass'] ?? '');
    return $output;
}

function winshirt_settings_section_main_cb() {
    echo '<p>' . esc_html__('Configurez les accès FTP et options IA.', 'winshirt') . '</p>';
}

function winshirt_field_ftp_host_cb() {
    $opts = get_option('winshirt_settings');
    printf(
        '<input type="text" name="winshirt_settings[ftp_host]" value="%s" class="regular-text" />',
        esc_attr($opts['ftp_host'] ?? '')
    );
}

function winshirt_field_ftp_user_cb() {
    $opts = get_option('winshirt_settings');
    printf(
        '<input type="text" name="winshirt_settings[ftp_user]" value="%s" class="regular-text" />',
        esc_attr($opts['ftp_user'] ?? '')
    );
}

function winshirt_field_ftp_pass_cb() {
    $opts = get_option('winshirt_settings');
    printf(
        '<input type="password" name="winshirt_settings[ftp_pass]" value="%s" class="regular-text" />',
        esc_attr($opts['ftp_pass'] ?? '')
    );
}

function winshirt_settings_page_html() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_GET['settings-updated'])) {
        add_settings_error('winshirt_messages', 'winshirt_message', __('Paramètres mis à jour.', 'winshirt'), 'updated');
    }
    settings_errors('winshirt_messages');

    echo '<div class="wrap"><h1>' . esc_html__('Configuration WinShirt', 'winshirt') . '</h1>';
    echo '<form action="options.php" method="post">';
    settings_fields('winshirt_settings_group');
    do_settings_sections('winshirt-settings');
    submit_button(__('Enregistrer', 'winshirt'));
    echo '</form></div>';
}

function winshirt_plugin_row_meta($links, $file) {
    if ($file === plugin_basename(__FILE__)) {
        $links[] = '<a href="https://shakass.com/" target="_blank">' . esc_html__('Site Web', 'winshirt') . '</a>';
        $links[] = '<a href="' . esc_url(plugins_url('readme.txt', __FILE__)) . '" target="_blank">' . esc_html__('Readme', 'winshirt') . '</a>';
    }
    return $links;
}
add_filter('plugin_row_meta', 'winshirt_plugin_row_meta', 10, 2);
