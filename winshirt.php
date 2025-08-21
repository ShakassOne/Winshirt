<?php
/**
 * Plugin Name: WinShirt
 * Description: Bootstrap tolérant avec SAFE PARTIEL (tous modules sauf Template côté front).
 * Version: 2.0.3
 * Author: WinShirt by Shakass Communication
 * Text Domain: winshirt
 */

if (!defined('ABSPATH')) exit;

define('WINSHIRT_VERSION', '2.0.3');
define('WINSHIRT_DIR', plugin_dir_path(__FILE__));
define('WINSHIRT_URL', plugin_dir_url(__FILE__));

/** SAFE PARTIEL
 * - true  : côté front, charge tout SAUF le Template (pas de carrousel).
 * - false : charge aussi le Template côté front (à réactiver après fix du slider).
 */
if (!defined('WINSHIRT_FRONT_SAFE_PARTIAL')) define('WINSHIRT_FRONT_SAFE_PARTIAL', true);

/** Logger */
if (!function_exists('winshirt_log')) {
    function winshirt_log(string $msg): void {
        $line = '[WinShirt ' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
        if (defined('WP_CONTENT_DIR')) @file_put_contents(WP_CONTENT_DIR . '/winshirt_fatal.log', $line, FILE_APPEND);
        else @error_log($line);
    }
}

/** require tolérant */
if (!function_exists('winshirt_require')) {
    function winshirt_require(string $rel): bool {
        $p = WINSHIRT_DIR . ltrim($rel, '/');
        if (file_exists($p)) { require_once $p; winshirt_log("require OK: {$rel}"); return true; }
        winshirt_log("require MISS: {$rel}"); return false;
    }
}

/** 1) Chargement modules */
add_action('plugins_loaded', function () {
    $is_admin_like = is_admin() || (function_exists('wp_doing_ajax') && wp_doing_ajax()) || (defined('REST_REQUEST') && REST_REQUEST);
    $front = !$is_admin_like;

    // — Modules non-template : chargés partout (admin + front), même en SAFE PARTIEL
    winshirt_require('includes/class-winshirt-lottery.php');
    winshirt_require('includes/class-winshirt-tickets.php');
    winshirt_require('includes/class-winshirt-lottery-order.php');
    winshirt_require('includes/class-winshirt-lottery-display.php');
    winshirt_require('includes/class-winshirt-lottery-product-link.php');

    // — Module Template (carrousel/renderer) :
    //    - Admin toujours ON
    //    - Front ON seulement si SAFE PARTIEL = false
    if ($is_admin_like || ($front && !WINSHIRT_FRONT_SAFE_PARTIAL)) {
        winshirt_require('includes/class-winshirt-lottery-template.php');
    } else {
        winshirt_log('Template SKIP on front (SAFE PARTIAL active).');
    }

    $boot = function (string $class, string $label) {
        if (!class_exists($class)) { winshirt_log("init SKIP {$label}"); return; }
        try {
            if (method_exists($class, 'instance') && method_exists($class, 'init')) { $class::instance()->init(); winshirt_log("init OK {$label}: instance()->init()"); return; }
            if (method_exists($class, 'init')) { $class::init(); winshirt_log("init OK {$label}: static init()"); return; }
            winshirt_log("init NOINIT {$label}");
        } catch (\Throwable $t) {
            winshirt_log("init THROW {$label}: " . $t->getMessage() . ' @ ' . $t->getFile() . ':' . $t->getLine());
        }
    };

    // Boot non-template partout
    $boot('\\WinShirt\\Lottery',              'Lottery');
    $boot('\\WinShirt\\Tickets',              'Tickets');
    $boot('\\WinShirt\\Lottery_Order',        'Lottery_Order');
    $boot('\\WinShirt\\Lottery_Display',      'Lottery_Display');
    $boot('\\WinShirt\\Lottery_Product_Link', 'Lottery_Product_Link');

    // Boot Template selon le mode
    if ($is_admin_like || (!$is_admin_like && !WINSHIRT_FRONT_SAFE_PARTIAL)) {
        $boot('\\WinShirt\\Lottery_Template', 'Lottery_Template');
    }
}, 5);

/** 2) Shortcodes: toujours actifs. Si Template absent côté front, fallback propre (pas de carrousel). */
add_action('init', function () {

    $render_list = function ($atts) {
        $atts = shortcode_atts([
            'status'      => 'all',
            'featured'    => '0',
            'limit'       => '12',
            'layout'      => 'grid',   // grid|slider|diagonal
            'columns'     => '3',
            'gap'         => '24',
            'show_timer'  => '1',
            'show_count'  => '1',
            'autoplay'    => '0',
            'speed'       => '600',
            'loop'        => '0',
        ], $atts, 'winshirt_lotteries');

        $template_ready = class_exists('\\WinShirt\\Lottery_Template') && method_exists('\\WinShirt\\Lottery_Template', 'render_list');

        if ($template_ready) {
            try { return \WinShirt\Lottery_Template::render_list($atts); }
            catch (\Throwable $t) { winshirt_log('shortcode list THROW: ' . $t->getMessage()); }
        }

        // Fallback (Template non chargé côté front → pas de slider)
        ob_start(); ?>
        <div class="winshirt-lotteries winshirt-fallback" data-layout="<?php echo esc_attr($atts['layout']); ?>">
            <div class="winshirt-alert" style="padding:12px;border:1px dashed #ccc;border-radius:8px;">
                WinShirt : affichage temporaire (Template inactif côté front). Liste — status=<?php echo esc_html($atts['status']); ?>, limit=<?php echo esc_html($atts['limit']); ?>.
            </div>
        </div>
        <?php return ob_get_clean();
    };

    $render_card = function ($atts) {
        $atts = shortcode_atts(['id' => '0', 'show_timer' => '1', 'show_count' => '1'], $atts, 'winshirt_lottery_card');
        $template_ready = class_exists('\\WinShirt\\Lottery_Template') && method_exists('\\WinShirt\\Lottery_Template', 'render_card');

        if ($template_ready) {
            try { return \WinShirt\Lottery_Template::render_card($atts); }
            catch (\Throwable $t) { winshirt_log('shortcode card THROW: ' . $t->getMessage()); }
        }

        ob_start(); ?>
        <div class="winshirt-lottery-card winshirt-fallback" data-id="<?php echo esc_attr($atts['id']); ?>">
            <div class="winshirt-alert" style="padding:12px;border:1px dashed #ccc;border-radius:8px;">
                WinShirt : affichage temporaire (Template inactif côté front). Carte — id=<?php echo esc_html($atts['id']); ?>.
            </div>
        </div>
        <?php return ob_get_clean();
    };

    add_shortcode('winshirt_lotteries', $render_list);
    add_shortcode('winshirt_lottery_card', $render_card);
}, 9);

/** 3) Assets : on ne pousse le CSS/JS qu’en présence du Template (évite JS du slider en front safe) */
add_action('wp_enqueue_scripts', function () {
    if (!class_exists('\\WinShirt\\Lottery_Template')) return;
    $css = WINSHIRT_DIR . 'assets/css/winshirt-lottery.css';
    if (file_exists($css)) wp_enqueue_style('winshirt-lottery', WINSHIRT_URL . 'assets/css/winshirt-lottery.css', [], WINSHIRT_VERSION);
    $js = WINSHIRT_DIR . 'assets/js/winshirt-lottery.js';
    if (file_exists($js)) wp_enqueue_script('winshirt-lottery', WINSHIRT_URL . 'assets/js/winshirt-lottery.js', ['jquery'], WINSHIRT_VERSION, true);
}, 20);

/** 4) Menu admin */
add_action('admin_menu', function () {
    add_menu_page(
        'WinShirt','WinShirt','manage_options','winshirt',
        function () {
            $log_url = esc_url(add_query_arg('winshirt_fatal_log', '1', home_url('/')));
            echo '<div class="wrap"><h1>WinShirt</h1>';
            echo '<p>SAFE PARTIEL front: <strong>' . (WINSHIRT_FRONT_SAFE_PARTIAL ? 'ON (Template OFF en front)' : 'OFF (Template ON en front)') . '</strong></p>';
            echo '<ul style="list-style:disc;margin-left:20px">';
            echo '<li><a href="' . $log_url . '" target="_blank">Ouvrir le log runtime</a></li>';
            echo '<li>Shortcodes: <code>[winshirt_lotteries]</code> / <code>[winshirt_lottery_card id="123"]</code></li>';
            echo '</ul>';
            echo '</div>';
        },
        'dashicons-tickets-alt',56
    );
}, 9);

/** 5) Activation douce + flush */
register_activation_hook(__FILE__, function () {
    try {
        if (class_exists('\\WinShirt\\Lottery')) {
            if (method_exists('\\WinShirt\\Lottery','instance') && method_exists('\\WinShirt\\Lottery','register_cpt')) \WinShirt\Lottery::instance()->register_cpt();
            elseif (method_exists('\\WinShirt\\Lottery','register_post_type')) \WinShirt\Lottery::register_post_type();
        }
        if (class_exists('\\WinShirt\\Tickets') && method_exists('\\WinShirt\\Tickets','install')) {
            try { method_exists('\\WinShirt\\Tickets','instance') ? \WinShirt\Tickets::instance()->install() : \WinShirt\Tickets::install(); }
            catch (\Throwable $t) { winshirt_log('activation tickets THROW: '.$t->getMessage()); }
        }
    } catch (\Throwable $t) {
        winshirt_log('activation THROW: '.$t->getMessage());
    }
    flush_rewrite_rules(false);
});
