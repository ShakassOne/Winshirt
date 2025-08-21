<?php
namespace WinShirt;

if (!defined('ABSPATH')) { exit; }

class Lottery_Template {
    private static $instance = null;

    public static function instance(){
        return self::$instance ?: self::$instance = new self();
    }

    public function init(){
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_shortcode('winshirt_lotteries', [$this, 'sc_lotteries']);
        add_shortcode('winshirt_lottery_card', [$this, 'sc_lottery_card']);
    }

    /**
     * Charge styles/JS (sans ombre, spacing configurable).
     */
    public function enqueue_assets(){
        // Swiper (CDN light, version stable).
        wp_register_style('swiper', 'https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css', [], '10.3.0');
        wp_register_script('swiper', 'https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js', [], '10.3.0', true);

        wp_enqueue_style('winshirt-lottery', WINSHIRT_PLUGIN_URL . 'assets/css/winshirt-lottery.css', ['swiper'], '1.0.9');
        wp_enqueue_script('winshirt-lottery', WINSHIRT_PLUGIN_URL . 'assets/js/winshirt-lottery.js', ['swiper'], '1.0.9', true);
    }

    /**
     * [winshirt_lotteries] – grille / slider / diagonal
     */
    public function sc_lotteries($atts){
        $a = shortcode_atts([
            'status'      => 'active',           // active|finished|upcoming|all
            'featured'    => '0',                // 1|0
            'limit'       => '12',
            'layout'      => 'grid',             // grid|slider|diagonal
            'columns'     => '3',                // 1|2|3|4 (grid/slider)
            'gap'         => '24',               // px (espacement cartes)
            'show_timer'  => '1',                // 1|0
            'show_count'  => '1',                // 1|0
            'autoplay'    => '0',                // ms (0 = off)
            'speed'       => '600',              // ms transition
            'loop'        => '0',                // 0 = ne repart PAS au début
        ], $atts, 'winshirt_lotteries');

        // Query CPT
        $args = [
            'post_type'      => 'winshirt_lottery',
            'posts_per_page' => intval($a['limit']),
            'post_status'    => 'publish',
        ];
        if ($a['featured'] === '1') {
            $args['meta_query'][] = [
                'key'   => '_wsl_featured',
                'value' => '1'
            ];
        }
        // Status filter (meta _wsl_start/_wsl_end)
        if ($a['status'] !== 'all') {
            $now = current_time('timestamp');
            $args['meta_query'][] = ['relation' => 'AND'];
            if ($a['status'] === 'active') {
                $args['meta_query'][] = [
                    'key'     => '_wsl_start',
                    'value'   => $now,
                    'compare' => '<=',
                    'type'    => 'NUMERIC'
                ];
                $args['meta_query'][] = [
                    'key'     => '_wsl_end',
                    'value'   => $now,
                    'compare' => '>=',
                    'type'    => 'NUMERIC'
                ];
            } elseif ($a['status'] === 'upcoming') {
                $args['meta_query'][] = [
                    'key'     => '_wsl_start',
                    'value'   => $now,
                    'compare' => '>',
                    'type'    => 'NUMERIC'
                ];
            } elseif ($a['status'] === 'finished') {
                $args['meta_query'][] = [
                    'key'     => '_wsl_end',
                    'value'   => $now,
                    'compare' => '<',
                    'type'    => 'NUMERIC'
                ];
            }
        }

        $q = new \WP_Query($args);
        if (!$q->have_posts()){
            return '<div class="wsl-empty">Aucune loterie.</div>';
        }

        // Wrapper data-* => JS
        $layout = esc_attr($a['layout']);
        $columns = max(1, min(4, intval($a['columns'])));
        $gap = max(0, intval($a['gap']));
        $autoplay = max(0, intval($a['autoplay']));
        $speed = max(100, intval($a['speed']));
        $loop  = $a['loop'] === '1' ? '1' : '0';

        ob_start();

        if ($layout === 'grid'){
            echo '<div class="wsl-grid" style="--wsl-gap:'.$gap.'px; --wsl-cols:'.$columns.'">';
            while ($q->have_posts()){ $q->the_post();
                echo $this->render_card(get_the_ID(), $a);
            }
            echo '</div>';
        } else {
            // slider + diagonal => même markup Swiper, styles différents
            $extra_class = $layout === 'diagonal' ? ' wsl-diagonal' : ' wsl-slider';
            echo '<div class="wsl-swiper'.$extra_class.'" 
                    data-layout="'.$layout.'" 
                    data-gap="'.$gap.'" 
                    data-cols="'.$columns.'" 
                    data-autoplay="'.$autoplay.'" 
                    data-speed="'.$speed.'" 
                    data-loop="'.$loop.'">
                  <div class="swiper">';
            echo '<div class="swiper-wrapper">';
            while ($q->have_posts()){ $q->the_post();
                echo '<div class="swiper-slide">'.$this->render_card(get_the_ID(), $a, true).'</div>';
            }
            echo '</div>';
            echo '<div class="wsl-nav wsl-prev"><span></span></div>';
            echo '<div class="wsl-nav wsl-next"><span></span></div>';
            echo '<div class="wsl-dots"></div>';
            echo '</div></div>';
        }
        wp_reset_postdata();

        return ob_get_clean();
    }

    /**
     * [winshirt_lottery_card id="3400" ...]
     */
    public function sc_lottery_card($atts){
        $a = shortcode_atts([
            'id'         => '0',
            'show_timer' => '1',
            'show_count' => '1',
        ], $atts, 'winshirt_lottery_card');

        $id = intval($a['id']);
        if (!$id) return '';

        return '<div class="wsl-card-single">'.$this->render_card($id, $a).'</div>';
    }

    /**
     * Rend une carte loterie (HTML pur). Titre en blanc, overlay bottom.
     */
    private function render_card($post_id, $opts, $inside_slider=false){
        $title   = esc_html(get_the_title($post_id));
        $thumb   = get_the_post_thumbnail_url($post_id, 'large');
        $thumb   = $thumb ?: WINSHIRT_PLUGIN_URL.'assets/img/placeholder.jpg';
        $perma   = get_permalink($post_id);

        $value   = get_post_meta($post_id, '_wsl_value', true);
        $goal    = intval(get_post_meta($post_id, '_wsl_goal', true));
        $tickets = intval(get_post_meta($post_id, '_wsl_tickets_sold', true));

        $start   = intval(get_post_meta($post_id, '_wsl_start', true));
        $end     = intval(get_post_meta($post_id, '_wsl_end', true));
        $show_timer = $opts['show_timer'] === '1';

        ob_start(); ?>
        <article class="wsl-card<?= $inside_slider ? ' wsl-card--slider' : '' ?>">
            <a class="wsl-media" href="<?= esc_url($perma); ?>">
                <img src="<?= esc_url($thumb); ?>" alt="<?= esc_attr($title); ?>">
            </a>
            <div class="wsl-overlay">
                <h3 class="wsl-title"><?= $title; ?></h3>
                <ul class="wsl-meta">
                    <?php if ($value) : ?>
                        <li>Valeur: <strong><?= esc_html($value); ?></strong></li>
                    <?php endif; ?>
                    <?php if ($show_timer): ?>
                        <li class="wsl-timer" data-end="<?= esc_attr($end); ?>">
                            <span class="wsl-tj">–</span> j
                            <span class="wsl-th">–</span> h
                            <span class="wsl-tm">–</span> m
                            <span class="wsl-ts">–</span> s
                        </li>
                    <?php endif; ?>
                    <?php if ($opts['show_count'] === '1'): ?>
                        <li><?= intval($tickets); ?> tickets — Objectif: <?= intval($goal); ?></li>
                    <?php endif; ?>
                    <li class="wsl-draw">
                        <?php if ($end) : ?>
                            Tirage le <?= date_i18n('d/m/Y', $end); ?>
                        <?php else: ?>
                            Date de tirage à venir
                        <?php endif; ?>
                    </li>
                </ul>
                <a class="wsl-btn" href="<?= esc_url($perma); ?>">Participer</a>
            </div>
            <?php if (get_post_meta($post_id, '_wsl_featured', true) == '1'): ?>
                <span class="wsl-badge">En vedette</span>
            <?php endif; ?>
        </article>
        <?php
        return ob_get_clean();
    }
}
