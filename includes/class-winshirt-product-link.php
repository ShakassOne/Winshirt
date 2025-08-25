<?php
if ( ! defined('ABSPATH') ) exit;

class WS_Product_Link {

    public static function init(){
        if ( ! class_exists('WooCommerce') ) return;
        add_action('add_meta_boxes_product', [__CLASS__, 'box']);
        add_action('save_post_product', [__CLASS__, 'save'], 10, 2);
    }

    public static function box($post){
        add_meta_box('ws_product_lottery', 'Loterie liée (WinShirt)', [__CLASS__,'render'], 'product', 'side', 'low');
    }

    public static function render($post){
        $lottery_post = (int) get_post_meta($post->ID, '_ws_linked_lottery_post', true);
        wp_nonce_field('ws_product_lottery_save','ws_product_lottery_nonce');
        echo '<p><label>ID Post/Portfolio de la loterie<br><input type="number" name="_ws_linked_lottery_post" value="'.esc_attr($lottery_post).'" min="0" style="width:100%"></label></p>';
    }

    public static function save($post_id, $post){
        if ( !isset($_POST['ws_product_lottery_nonce']) || !wp_verify_nonce($_POST['ws_product_lottery_nonce'],'ws_product_lottery_save') ) return;
        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
        if ( ! current_user_can('edit_post',$post_id) ) return;
        $v = (int)($_POST['_ws_linked_lottery_post'] ?? 0);
        update_post_meta($post_id, '_ws_linked_lottery_post', $v);
    }
}
