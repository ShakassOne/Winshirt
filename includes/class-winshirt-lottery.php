<?php
namespace WinShirt;

if ( ! defined('ABSPATH') ) exit;

class Lottery {
    private static $instance;
    public static function instance(){ return self::$instance ?: (self::$instance = new self); }

    public static function register_post_type(){
        register_post_type('winshirt_lottery', [
            'label' => 'Loteries',
            'public' => true,
            'supports' => ['title','editor','thumbnail'],
            'show_in_rest' => true,
            'has_archive' => true,
            'rewrite' => ['slug'=>'loteries'],
        ]);
    }

    public function init(){
        add_action('init', [__CLASS__, 'register_post_type']);
    }
}

