<?php
if ( ! defined('ABSPATH') ) exit;

class WinShirt_Assets {
    public static function init(){
        add_action('wp_enqueue_scripts', [__CLASS__,'register_front']);
        add_action('admin_enqueue_scripts', [__CLASS__,'admin']);
    }
    public static function register_front(){
        wp_register_style('winshirt-frontend', WINSHIRT_URL.'assets/css/frontend.css', [], WINSHIRT_VERSION);
        wp_register_style('winshirt-diagonal', WINSHIRT_URL.'assets/css/diagonal.css', ['winshirt-frontend'], WINSHIRT_VERSION);
        wp_register_script('winshirt-diagonal', WINSHIRT_URL.'assets/js/diagonal.js', [], WINSHIRT_VERSION, true);
    }
    public static function admin($hook){
        // CSS admin léger (zones, etc.) s'il y a lieu
        $admin_css = WINSHIRT_DIR.'assets/css/admin-zones.css';
        if ( file_exists($admin_css) ) {
            wp_enqueue_style('winshirt-admin-zones', WINSHIRT_URL.'assets/css/admin-zones.css', [], WINSHIRT_VERSION);
        }
    }
    public static function need_front(){
        if ( ! wp_style_is('winshirt-frontend','enqueued') ) {
            wp_enqueue_style('winshirt-frontend');
        }
    }
    public static function enqueue_diagonal(){
        self::need_front();
        wp_enqueue_style('winshirt-diagonal');
        wp_enqueue_script('winshirt-diagonal');
    }
}
