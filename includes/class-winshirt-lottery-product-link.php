<?php
namespace WinShirt;
if ( ! defined('ABSPATH') ) exit;

class Lottery_Product_Link {
    private static $instance;
    public static function instance(){ return self::$instance ?: (self::$instance = new self); }

    public function init(){
        add_filter('woocommerce_product_data_tabs', [$this,'add_tab']);
        add_action('woocommerce_product_data_panels', [$this,'render_fields']);
        add_action('woocommerce_process_product_meta', [$this,'save_fields']);
        add_action('woocommerce_single_product_summary', [$this,'display_message'], 25);
    }

    public function add_tab($tabs){
        $tabs['winshirt_lottery'] = [
            'label' => __('Loterie','winshirt'),
            'target' => 'winshirt_lottery_product_data',
            'class' => []
        ];
        return $tabs;
    }

    public function render_fields(){
        echo '<div id="winshirt_lottery_product_data" class="panel woocommerce_options_panel">';
        woocommerce_wp_checkbox([
            'id' => '_ws_enable_lottery',
            'label' => __('Activer la loterie','winshirt')
        ]);
        woocommerce_wp_text_input([
            'id' => '_ws_lottery_id',
            'label' => __('ID de la loterie','winshirt')
        ]);
        woocommerce_wp_text_input([
            'id' => '_ws_ticket_count',
            'label' => __('Nombre de tickets','winshirt'),
            'type' => 'number'
        ]);
        echo '</div>';
    }

    public function save_fields($post_id){
        update_post_meta($post_id,'_ws_enable_lottery', isset($_POST['_ws_enable_lottery'])?'yes':'no');
        if(isset($_POST['_ws_lottery_id'])) update_post_meta($post_id,'_ws_lottery_id', sanitize_text_field($_POST['_ws_lottery_id']));
        if(isset($_POST['_ws_ticket_count'])) update_post_meta($post_id,'_ws_ticket_count', intval($_POST['_ws_ticket_count']));
    }

    public function display_message(){
        global $product;
        if(get_post_meta($product->get_id(),'_ws_enable_lottery',true)!=='yes') return;
        $lottery_id = get_post_meta($product->get_id(),'_ws_lottery_id',true);
        $tickets = intval(get_post_meta($product->get_id(),'_ws_ticket_count',true));
        if(!$lottery_id) return;
        $lottery = get_post($lottery_id);
        if(!$lottery) return;
        $cats = wc_get_product_category_list($product->get_id());
        echo '<div class="ws-lottery-banner">Ce produit ('.$cats.') vous donne droit à '.$tickets.' ticket(s) pour la loterie <strong>'.$lottery->post_title.'</strong>.</div>';
    }
}
