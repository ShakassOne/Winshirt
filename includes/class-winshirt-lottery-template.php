<?php
namespace WinShirt;
if ( ! defined('ABSPATH') ) exit;

class Lottery_Template {
    private static $instance;
    public static function instance(){ return self::$instance ?: (self::$instance = new self); }

    public function init(){
        add_filter('single_template', [$this,'load_single_template']);
        add_action('wp_enqueue_scripts', [$this,'enqueue_assets']);
    }

    public function load_single_template($template){
        if (is_singular('winshirt_lottery')){
            return WINSHIRT_PLUGIN_DIR . 'templates/single-winshirt_lottery.php';
        }
        return $template;
    }

    public function enqueue_assets(){
        if (is_singular('winshirt_lottery')){
            wp_enqueue_style('winshirt-lottery', WINSHIRT_PLUGIN_URL.'assets/css/lottery-single-card.css');
            wp_enqueue_script('winshirt-countdown', WINSHIRT_PLUGIN_URL.'assets/js/lottery-countdown.js', [], false, true);
        }
    }
}
