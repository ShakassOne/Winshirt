
<?php
/**
 * Plugin Name: WinShirt
 * Plugin URI: https://winshirt.fr
 * Description: Bootstrap tolérant. Template chargé en front. Diagonal actif; slider neutralisé (optionnel) sans casser le site.
 * Version: 2.0.5
 * Author: WinShirt by Shakass Communication
 * Author URI: https://shakass.com/
 * Text Domain: winshirt
 */

// === Winshirt constants (plugin root) ===
if (!defined('WINSHIRT_PLUGIN_FILE')) define('WINSHIRT_PLUGIN_FILE', __FILE__);
if (!defined('WINSHIRT_PLUGIN_URL'))  define('WINSHIRT_PLUGIN_URL', plugin_dir_url(__FILE__));
if (!defined('WINSHIRT_PLUGIN_PATH')) define('WINSHIRT_PLUGIN_PATH', plugin_dir_path(__FILE__));
if (!defined('ABSPATH')) exit;

define('WINSHIRT_VERSION', '2.0.5');
define('WINSHIRT_DIR', plugin_dir_path(__FILE__));
define('WINSHIRT_URL', plugin_dir_url(__FILE__));

/** Désactiver le slider uniquement (diagonal reste actif) */
if (!defined('WINSHIRT_DISABLE_SLIDER')) define('WINSHIRT_DISABLE_SLIDER', true);

/** Logger léger */
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

/** 1) Inclusions (admin + front) */
add_action('plugins_loaded', function () {
    winshirt_require('includes/class-winshirt-lottery.php');
    winshirt_require('includes/class-winshirt-tickets.php');
    winshirt_require('includes/class-winshirt-lottery-order.php');
    winshirt_require('includes/class-winshirt-lottery-display.php');
    winshirt_require('includes/class-winshirt-lottery-product-link.php');
    winshirt_require('includes/class-winshirt-lottery-template.php');

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

    $boot('\\WinShirt\\Lottery',              'Lottery');
    $boot('\\WinShirt\\Tickets',              'Tickets');
    $boot('\\WinShirt\\Lottery_Order',        'Lottery_Order');
    $boot('\\WinShirt\\Lottery_Display',      'Lottery_Display');
    $boot('\\WinShirt\\Lottery_Product_Link', 'Lottery_Product_Link');
    $boot('\\WinShirt\\Lottery_Template',     'Lottery_Template');
}, 5);

/** 2) Shortcodes — ne forcer QUE le slider → grid; laisser diagonal intact */
add_action('init', function () {

    $coerce_layout = function (string $layout): string {
        $l = strtolower(trim($layout));
        if (WINSHIRT_DISABLE_SLIDER && $l === 'slider') return 'grid'; // neutralise uniquement le slider
        return in_array($l, ['grid','masonry','diagonal','slider'], true) ? $l : 'grid';
    };

    add_shortcode('winshirt_lotteries', function ($atts = []) use ($coerce_layout) {
        $atts = shortcode_atts([
            'status'      => 'all',
            'featured'    => '0',
            'limit'       => '12',
            'layout'      => 'grid',
            'columns'     => '3',
            'gap'         => '24',
            'show_timer'  => '1',
            'show_count'  => '1',
            'autoplay'    => '0',
            'speed'       => '600',
            'loop'        => '0',
        ], $atts, 'winshirt_lotteries');

        $atts['layout'] = $coerce_layout($atts['layout']);

        if (class_exists('\\WinShirt\\Lottery_Template') && method_exists('\\WinShirt\\Lottery_Template', 'render_list')) {
            try { return \WinShirt\Lottery_Template::render_list($atts); }
            catch (\Throwable $t) { winshirt_log('shortcode list THROW: ' . $t->getMessage() . ' @ ' . $t->getFile() . ':' . $t->getLine()); }
        }

        ob_start(); ?>
        <div class="winshirt-lotteries winshirt-fallback" data-layout="<?php echo esc_attr($atts['layout']); ?>">
            <div class="winshirt-alert" style="padding:12px;border:1px dashed #ccc;border-radius:8px;">
                WinShirt : template indisponible. Liste (fallback).
            </div>
        </div>
        <?php return ob_get_clean();
    });

    add_shortcode('winshirt_lottery_card', function ($atts = []) {
        $atts = shortcode_atts(['id' => '0', 'show_timer' => '1', 'show_count' => '1'], $atts, 'winshirt_lottery_card');

        if (class_exists('\\WinShirt\\Lottery_Template') && method_exists('\\WinShirt\\Lottery_Template', 'render_card')) {
            try { return \WinShirt\Lottery_Template::render_card($atts); }
            catch (\Throwable $t) { winshirt_log('shortcode card THROW: ' . $t->getMessage() . ' @ ' . $t->getFile() . ':' . $t->getLine()); }
        }

        ob_start(); ?>
        <div class="winshirt-lottery-card winshirt-fallback" data-id="<?php echo esc_attr($atts['id']); ?>">
            <div class="winshirt-alert" style="padding:12px;border:1px dashed #ccc;border-radius:8px;">
                WinShirt : template indisponible. Carte (fallback).
            </div>
        </div>
        <?php return ob_get_clean();
    });
}, 9);

/** 3) Assets — on injecte uniquement si nécessaire (aucune lib externe ici) */
add_action('wp_enqueue_scripts', function () {
    // rien de spécial; la classe imprime son CSS inline
}, 20);

/** 4) Menu admin */
add_action('admin_menu', function () {
    add_menu_page(
        'WinShirt','WinShirt','manage_options','winshirt',
        function () {
            $viewer = esc_url(home_url('/wp-content/winshirt_log.php'));
            echo '<div class="wrap"><h1>WinShirt</h1>';
            echo '<p>Slider : <strong>' . (WINSHIRT_DISABLE_SLIDER ? 'OFF (neutralisé)' : 'ON') . '</strong> — Diagonal : <strong>ON</strong></p>';
            echo '<ul style="list-style:disc;margin-left:20px">';
            echo '<li><a href="'.$viewer.'" target="_blank">Voir le log (viewer autonome)</a></li>';
            echo '<li>Shortcodes : <code>[winshirt_lotteries]</code> / <code>[winshirt_lottery_card id="123"]</code></li>';
            echo '</ul></div>';
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
