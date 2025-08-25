<?php
if (!defined('ABSPATH')) exit;

class WinShirt_Archive_Overlay {

    public static function init(){
        add_filter('post_thumbnail_html', [__CLASS__,'inject_overlay'], 10, 5);
        add_filter('post_class', [__CLASS__,'mark_lottery_posts'], 10, 3);
        add_action('wp_enqueue_scripts', [__CLASS__,'assets']);
    }

    public static function assets(){
        wp_register_style('winshirt-archive-overlay', WINSHIRT_URL . 'assets/css/archive-overlay.css', [], WINSHIRT_VERSION);
        wp_register_script('winshirt-archive-overlay', WINSHIRT_URL . 'assets/js/archive-overlay.js', [], WINSHIRT_VERSION, true);
        if (!is_admin()) {
            wp_enqueue_style('winshirt-archive-overlay');
            wp_enqueue_script('winshirt-archive-overlay');
        }
    }

    public static function is_lottery_post($post_id){
        $cat = get_option('winshirt_lottery_category','loterie');
        return has_category($cat, $post_id);
    }

    public static function inject_overlay($html, $post_id, $thumb_id, $size, $attr){
        if (!self::is_lottery_post($post_id)) return $html;

        // Récup métas
        $value    = get_post_meta($post_id, '_ws_lottery_value', true);
        $goal     = (int) get_post_meta($post_id, '_ws_lottery_goal', true);
        $count    = (int) get_post_meta($post_id, '_ws_lottery_count', true);
        $end_date = get_post_meta($post_id, '_ws_lottery_end', true);
        $featured = (get_post_meta($post_id, '_ws_lottery_featured', true) === 'yes');

        // Encapsule l'image pour permettre l’overlay absolu
        $attr_str = is_array($attr) ? join(' ', array_map(function($k)use($attr){ return esc_attr($k).'="'.esc_attr($attr[$k]).'"'; }, array_keys($attr))) : '';
        $img = $html;

        $badge_featured = $featured ? '<span class="wsov-badge wsov-badge-featured">En vedette</span>' : '';
        $badge_status   = '<span class="wsov-badge wsov-badge-active">Active</span>';

        $value_html = $value ? '<div class="wsov-value">Valeur : <strong>'.esc_html($value).'</strong></div>' : '';
        $goal_html  = ($goal>0) ? '<div class="wsov-goal"><span class="wsov-people">👥</span> '.intval($count).' participants&nbsp;&nbsp;Objectif : '.intval($goal).'</div>' : '';
        $date_html  = $end_date ? '<div class="wsov-draw"><span class="wsov-cal">📅</span> Tirage le '.esc_html(date_i18n(get_option('date_format','d/m/Y'), strtotime($end_date))).'</div>' : '';

        $countdown_attr = $end_date ? ' data-wsov-end="'.esc_attr($end_date).'"' : '';

        $overlay = '
        <div class="wsov-overlay"'.$countdown_attr.'>
            <div class="wsov-top">'.$badge_status.$badge_featured.'</div>
            <div class="wsov-mid">
                '.$value_html.'
                <div class="wsov-timer" aria-label="Compte à rebours">
                    <span class="wsov-dd">—</span><small>Jours</small>
                    <span class="wsov-hh">—</span><small>Heures</small>
                    <span class="wsov-mm">—</span><small>Minutes</small>
                </div>
                '.$goal_html.'
                <div class="wsov-bar"><span style="width:'. ( $goal>0 ? min(100, round( ($count / max(1,$goal))*100 )) : 0 ) .'%"></span></div>
            </div>
            <div class="wsov-bottom">'.$date_html.'<span class="wsov-cta">Participer</span></div>
        </div>';

        return '<span class="wsov-wrap">'.$img.$overlay.'</span>';
    }

    public static function mark_lottery_posts($classes, $class, $post_id){
        if ( self::is_lottery_post($post_id) ) $classes[] = 'wsov-item';
        return $classes;
    }
}
