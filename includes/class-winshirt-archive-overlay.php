<?php
if ( ! defined('ABSPATH') ) exit;

class WS_Archive_Overlay {

    public static function init(){
        add_action('wp_enqueue_scripts', [__CLASS__,'assets']);
        add_filter('post_thumbnail_html', [__CLASS__,'inject'], 10, 5);
        add_filter('post_class', [__CLASS__,'mark'], 10, 3);
    }

    public static function assets(){
        wp_enqueue_style('ws-archive-overlay', WINSHIRT_URL.'assets/css/archive-overlay.css', [], WINSHIRT_VERSION);
        wp_enqueue_script('ws-archive-overlay', WINSHIRT_URL.'assets/js/archive-overlay.js', [], WINSHIRT_VERSION, true);
    }

    protected static function is_lottery_post($post_id){
        $cat = get_option('winshirt_lottery_category','loterie');
        return has_category($cat, $post_id) || get_post_meta($post_id, '_ws_lottery_enabled', true);
    }

    public static function inject($html, $post_id, $thumb_id, $size, $attr){
        if ( is_admin() || ! self::is_lottery_post($post_id) ) return $html;

        $val   = get_post_meta($post_id, '_ws_lottery_value', true);
        $goal  = (int) get_post_meta($post_id, '_ws_lottery_goal', true);
        $count = (int) get_post_meta($post_id, '_ws_lottery_participants', true);
        $end   = get_post_meta($post_id, '_ws_lottery_end', true);
        $feat  = (get_post_meta($post_id, '_ws_lottery_featured', true) === 'yes');

        $badge_featured = $feat ? '<span class="wsov-badge wsov-badge-featured">En vedette</span>' : '';
        $badge_status   = '<span class="wsov-badge wsov-badge-active">Active</span>';

        $value_html = $val ? '<div class="wsov-value">Valeur : <strong>'.esc_html($val).'</strong></div>' : '';
        $goal_html  = ($goal>0) ? '<div class="wsov-goal">👥 '.intval($count).' participants&nbsp;&nbsp;Objectif : '.intval($goal).'</div>' : '';
        $date_html  = $end ? '<div class="wsov-draw">📅 Tirage le '.esc_html(date_i18n(get_option('date_format','d/m/Y'), strtotime($end))).'</div>' : '';

        $progress = ($goal>0) ? min(100, round(($count / max(1,$goal))*100)) : 0;
        $countdown_attr = $end ? ' data-wsov-end="'.esc_attr($end).'"' : '';

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
                <div class="wsov-bar"><span style="width:'.$progress.'%"></span></div>
            </div>
            <div class="wsov-bottom">'.$date_html.'<span class="wsov-cta">Participer</span></div>
        </div>';

        return '<span class="wsov-wrap">'.$html.$overlay.'</span>';
    }

    public static function mark($classes, $class, $post_id){
        if ( self::is_lottery_post($post_id) ) $classes[] = 'wsov-item';
        return $classes;
    }
}
