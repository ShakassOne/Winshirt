<?php
if ( ! defined('ABSPATH') ) exit;

class WS_Lottery_Meta {

    public static function init(){
        add_action('init', [__CLASS__, 'register_meta']);
        add_action('add_meta_boxes', [__CLASS__, 'add_metabox']);
        add_action('save_post', [__CLASS__, 'save_meta'], 10, 2);
        add_action('admin_init', [__CLASS__, 'columns']);
    }

    public static function supported_types(){
        $types = ['post'];
        if ( post_type_exists('portfolio') ) $types[] = 'portfolio';
        return apply_filters('ws_supported_post_types', $types);
    }

    public static function register_meta(){
        $meta = [
            '_ws_lottery_enabled'       => ['type'=>'boolean','single'=>true,'default'=>false],
            '_ws_lottery_value'         => ['type'=>'string','single'=>true,'default'=>''],
            '_ws_lottery_participants'  => ['type'=>'integer','single'=>true,'default'=>0],
            '_ws_lottery_goal'          => ['type'=>'integer','single'=>true,'default'=>0],
            '_ws_lottery_end'           => ['type'=>'string','single'=>true,'default'=>''], // YYYY-MM-DD
            '_ws_lottery_product'       => ['type'=>'integer','single'=>true,'default'=>0], // product ID
            '_ws_lottery_status'        => ['type'=>'string','single'=>true,'default'=>'active'],
            '_ws_lottery_featured'      => ['type'=>'string','single'=>true,'default'=>'no'], // yes|no
        ];
        foreach ( self::supported_types() as $pt ){
            foreach ( $meta as $k=>$a ){
                register_post_meta($pt, $k, array_merge($a, [
                    'show_in_rest'=>true,
                    'auth_callback'=>function(){ return current_user_can('edit_posts'); }
                ]));
            }
        }
    }

    public static function add_metabox(){
        foreach ( self::supported_types() as $pt ){
            add_meta_box('ws_lottery_meta', 'Loterie WinShirt', [__CLASS__,'box'], $pt, 'side', 'high');
        }
    }

    public static function box($post){
        $get = function($k,$d=''){ $v=get_post_meta($post->ID,$k,true); return ($v==='' ? $d : $v); };
        $en   = (bool)$get('_ws_lottery_enabled', false);
        $val  = $get('_ws_lottery_value','');
        $pax  = (int)$get('_ws_lottery_participants',0);
        $goal = (int)$get('_ws_lottery_goal',0);
        $end  = esc_attr($get('_ws_lottery_end',''));
        $prod = (int)$get('_ws_lottery_product',0);
        $st   = $get('_ws_lottery_status','active');
        $feat = $get('_ws_lottery_featured','no');

        wp_nonce_field('ws_lottery_save','ws_lottery_nonce');

        echo '<p><label><input type="checkbox" name="_ws_lottery_enabled" value="1" '.checked($en,true,false).'> Activer la loterie</label></p>';
        echo '<p><label>Valeur (€)<br><input type="text" name="_ws_lottery_value" value="'.esc_attr($val).'" style="width:100%"></label></p>';
        echo '<p><label>Participants<br><input type="number" name="_ws_lottery_participants" value="'.esc_attr($pax).'" min="0" style="width:100%"></label></p>';
        echo '<p><label>Objectif Tickets<br><input type="number" name="_ws_lottery_goal" value="'.esc_attr($goal).'" min="0" style="width:100%"></label></p>';
        echo '<p><label>Date de fin<br><input type="date" name="_ws_lottery_end" value="'.$end.'" style="width:100%"></label></p>';
        echo '<p><label>ID produit lié (WooCommerce)<br><input type="number" name="_ws_lottery_product" value="'.esc_attr($prod).'" min="0" style="width:100%"></label></p>';
        echo '<p><label>Statut<br><select name="_ws_lottery_status" style="width:100%">';
        foreach (['active'=>'Actif','closed'=>'Clôturé','draft'=>'Brouillon'] as $k=>$lab){
            echo '<option value="'.esc_attr($k).'" '.selected($st,$k,false).'>'.esc_html($lab).'</option>';
        }
        echo '</select></label></p>';
        echo '<p><label>En vedette<br><select name="_ws_lottery_featured" style="width:100%">';
        foreach (['no'=>'Non','yes'=>'Oui'] as $k=>$lab){
            echo '<option value="'.esc_attr($k).'" '.selected($feat,$k,false).'>'.esc_html($lab).'</option>';
        }
        echo '</select></label></p>';
    }

    public static function save_meta($post_id, $post){
        if ( !isset($_POST['ws_lottery_nonce']) || !wp_verify_nonce($_POST['ws_lottery_nonce'],'ws_lottery_save') ) return;
        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
        if ( ! current_user_can('edit_post',$post_id) ) return;

        $map = [
            '_ws_lottery_enabled'      => isset($_POST['_ws_lottery_enabled']) ? 1 : 0,
            '_ws_lottery_value'        => sanitize_text_field($_POST['_ws_lottery_value'] ?? ''),
            '_ws_lottery_participants' => (int)($_POST['_ws_lottery_participants'] ?? 0),
            '_ws_lottery_goal'         => (int)($_POST['_ws_lottery_goal'] ?? 0),
            '_ws_lottery_end'          => sanitize_text_field($_POST['_ws_lottery_end'] ?? ''),
            '_ws_lottery_product'      => (int)($_POST['_ws_lottery_product'] ?? 0),
            '_ws_lottery_status'       => sanitize_text_field($_POST['_ws_lottery_status'] ?? 'active'),
            '_ws_lottery_featured'     => sanitize_text_field($_POST['_ws_lottery_featured'] ?? 'no'),
        ];
        foreach ($map as $k=>$v) update_post_meta($post_id,$k,$v);
    }

    public static function columns(){
        foreach ( self::supported_types() as $pt ){
            add_filter("manage_{$pt}_columns", function($cols){
                $cols['_ws_lottery_enabled'] = 'Loterie';
                $cols['_ws_lottery_end'] = 'Fin';
                $cols['_ws_lottery_participants'] = 'Participants';
                return $cols;
            });
            add_action("manage_{$pt}_custom_column", function($col,$id){
                if ($col==='_ws_lottery_enabled') echo get_post_meta($id,'_ws_lottery_enabled',true)?'✅':'—';
                if ($col==='_ws_lottery_end') echo esc_html(get_post_meta($id,'_ws_lottery_end',true) ?: '—');
                if ($col==='_ws_lottery_participants') echo (int)get_post_meta($id,'_ws_lottery_participants',true);
            },10,2);
        }
    }
}
