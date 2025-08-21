<?php
/**
 * Plugin Name: WinShirt
 * Description: Bootstrap tolérant avec SAFE MODE (front en fallback), admin complet pour diagnostic.
 * Version: 2.0.2
 * Author: WinShirt by Shakass Communication
 * Text Domain: winshirt
 */

if (!defined('ABSPATH')) exit;

define('WINSHIRT_VERSION', '2.0.2');
define('WINSHIRT_DIR', plugin_dir_path(__FILE__));
define('WINSHIRT_URL', plugin_dir_url(__FILE__));

/** === SAFE MODE FRONT ===
 * true  = le front N'INCLUT AUCUN module (fallbacks uniquement)
 * false = modules chargés aussi côté front (quand on sera prêt)
 */
if (!defined('WINSHIRT_FRONT_SAFE_MODE')) {
    define('WINSHIRT_FRONT_SAFE_MODE', true);
}

/** Logger léger vers wp-content/winshirt_fatal.log */
if (!function_exists('winshirt_log')) {
    function winshirt_log(string $msg): void {
        $line = '[WinShirt ' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
        if (defined('WP_CONTENT_DIR')) {
            @file_put_contents(WP_CONTENT_DIR . '/winshirt_fatal.log', $line, FILE_APPEND);
        } else {
            @error_log($line);
        }
    }
}

/** require tolérant */
if (!function_exists('winshirt_require')) {
    function winshirt_require(string $rel): bool {
        $p = WINSHIRT_DIR . ltrim($rel, '/');
        if (file_exists($p)) {
            require_once $p;
            winshirt_log("require OK: {$rel}");
            return true;
        }
        winshirt_log("require MISS: {$rel}");
        return false;
    }
}

/** 1) Chargement des modules — ADMIN UNIQUEMENT tant que SAFE MODE = true */
add_action('plugins_loaded', function () {
    $is_admin_context = is_admin() || (function_exists('wp_doing_ajax') && wp_doing_ajax()) || (defined('REST_REQUEST') && REST_REQUEST);
    $load_in_front = !$is_admin_context && !WINSHIRT_FRONT_SAFE_MODE ? true : false;

    // On charge les modules si on est en admin/REST/AJAX, ou sur le front uniquement si SAFE MODE = false
    if ($is_admin_context || $load_in_front) {
        winshirt_require('includes/class-winshirt-lottery.php');
        winshirt_require('includes/class-winshirt-lottery-template.php');
        winshirt_require('includes/class-winshirt-tickets.php');
        winshirt_require('includes/class-winshirt-lottery-order.php');
        winshirt_require('includes/class-winshirt-lottery-display.php');
        winshirt_require('includes/class-winshirt-lottery-product-link.php');

        $boot = function (string $class, string $label) {
            if (!class_exists($class)) { winshirt_log("init SKIP {$label}"); return; }
            try {
                if (method_exists($class, 'instance') && method_exists($class, 'init')) {
                    $class::instance()->init();
                    winshirt_log("init OK {$label}: instance()->init()");
                } elseif (method_exists($class, 'init')) {
                    $class::init();
                    winshirt_log("init OK {$label}: static init()");
                } else {
                    winshirt_log("init NOINIT {$label}");
                }
            } catch (\Throwable $t) {
                winshirt_log("init THROW {$label}: " . $t->getMessage() . ' @ ' . $t->getFile() . ':' . $t->getLine());
            }
        };

        $boot('\\WinShirt\\Lottery',              'Lottery');
        $boot('\\WinShirt\\Lottery_Template',     'Lottery_Template');
        $boot('\\WinShirt\\Tickets',              'Tickets');
        $boot('\\WinShirt\\Lottery_Order',        'Lottery_Order');
        $boot('\\WinShirt\\Lottery_Display',      'Lottery_Display');
        $boot('\\WinShirt\\Lottery_Product_Link', 'Lottery_Product_Link');
    } else {
        winshirt_log('SAFE MODE FRONT: aucun module chargé côté front.');
    }
}, 5);

/** 2) Shortcodes TOUJOURS enregistrés — fallback côté front si SAFE MODE */
add_action('init', function () {

    $render_list = function ($atts) {
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

        // Si front en SAFE MODE → fallback propre
        if (!is_admin() && WINSHIRT_FRONT_SAFE_MODE) {
            ob_start(); ?>
            <div class="winshirt-lotteries winshirt-fallback" data-layout="<?php echo esc_attr($atts['layout']); ?>">
                <div class="winshirt-alert" style="padding:12px;border:1px dashed #ccc;border-radius:8px;">
                    WinShirt : affichage temporaire (SAFE MODE). Liste — status=<?php echo esc_html($atts['status']); ?>, limit=<?php echo esc_html($atts['limit']); ?>.
                </div>
            </div>
            <?php return ob_get_clean();
        }

        // Sinon, déléguer si dispo
        if (class_exists('\\WinShirt\\Lottery_Template') && method_exists('\\WinShirt\\Lottery_Template', 'render_list')) {
            try {
                return \WinShirt\Lottery_Template::render_list($atts);
            } catch (\Throwable $t) {
                winshirt_log('shortcode list THROW: ' . $t->getMessage());
            }
        }

        // Fallback si classe indisponible
        ob_start(); ?>
        <div class="winshirt-lotteries winshirt-fallback">
            <div class="winshirt-alert" style="padding:12px;border:1px dashed #ccc;border-radius:8px;">
                WinShirt : module d’affichage non chargé. Liste (fallback).
            </div>
        </div>
        <?php return ob_get_clean();
    };

    $render_card = function ($atts) {
        $atts = shortcode_atts(['id' => '0', 'show_timer' => '1', 'show_count' => '1'], $atts, 'winshirt_lottery_card');

        if (!is_admin() && WINSHIRT_FRONT_SAFE_MODE) {
            ob_start(); ?>
            <div class="winshirt-lottery-card winshirt-fallback" data-id="<?php echo esc_attr($atts['id']); ?>">
                <div class="winshirt-alert" style="padding:12px;border:1px dashed #ccc;border-radius:8px;">
                    WinShirt : affichage temporaire (SAFE MODE). Carte — id=<?php echo esc_html($atts['id']); ?>.
                </div>
            </div>
            <?php return ob_get_clean();
        }

        if (class_exists('\\WinShirt\\Lottery_Template') && method_exists('\\WinShirt\\Lottery_Template', 'render_card')) {
            try {
                return \WinShirt\Lottery_Template::render_card($atts);
            } catch (\Throwable $t) {
                winshirt_log('shortcode card THROW: ' . $t->getMessage());
            }
        }

        ob_start(); ?>
        <div class="winshirt-lottery-card winshirt-fallback" data-id="<?php echo esc_attr($atts['id']); ?>">
            <div class="winshirt-alert" style="padding:12px;border:1px dashed #ccc;border-radius:8px;">
                WinShirt : module d’affichage non chargé. Carte (fallback).
            </div>
        </div>
        <?php return ob_get_clean();
    };

    add_shortcode('winshirt_lotteries', $render_list);
    add_shortcode('winshirt_lottery_card', $render_card);
}, 9);

/** 3) Assets — on évite d’injecter quoi que ce soit tant que SAFE MODE front est actif */
add_action('wp_enqueue_scripts', function () {
    if (WINSHIRT_FRONT_SAFE_MODE) return; // pas d'assets en front pendant le SAFE MODE
    $css = WINSHIRT_DIR . 'assets/css/winshirt-lottery.css';
    if (file_exists($css)) {
        wp_enqueue_style('winshirt-lottery', WINSHIRT_URL . 'assets/css/winshirt-lottery.css', [], WINSHIRT_VERSION);
    }
    $js = WINSHIRT_DIR . 'assets/js/winshirt-lottery.js';
    if (file_exists($js)) {
        wp_enqueue_script('winshirt-lottery', WINSHIRT_URL . 'assets/js/winshirt-lottery.js', ['jquery'], WINSHIRT_VERSION, true);
    }
}, 20);

/** 4) Menu admin minimal */
add_action('admin_menu', function () {
    add_menu_page(
        'WinShirt',
        'WinShirt',
        'manage_options',
        'winshirt',
        function () {
            $log_url = esc_url(add_query_arg('winshirt_fatal_log', '1', home_url('/')));
            echo '<div class="wrap"><h1>WinShirt</h1>';
            echo '<p>SAFE MODE front: <strong>' . (WINSHIRT_FRONT_SAFE_MODE ? 'ON' : 'OFF') . '</strong></p>';
            echo '<ul style="list-style:disc;margin-left:20px">';
            echo '<li><a href="' . $log_url . '" target="_blank">Ouvrir le log runtime</a></li>';
            echo '<li>Shortcodes: <code>[winshirt_lotteries]</code> / <code>[winshirt_lottery_card id="123"]</code></li>';
            echo '</ul>';
            echo '<p style="opacity:.7">Admin complet chargé pour diagnostic. Front en fallback tant que SAFE MODE = ON.</p>';
            echo '</div>';
        },
        'dashicons-tickets-alt',
        56
    );
}, 9);

/** 5) Bandeau d’état */
add_action('admin_notices', function () {
    if (!current_user_can('activate_plugins')) return;
    echo '<div class="notice notice-info"><p><strong>WinShirt</strong> : Admin complet chargé. '
       . 'Front en <em>SAFE MODE</em> (fallbacks), aucun module inclus côté front tant que WINSHIRT_FRONT_SAFE_MODE = true.</p></div>';
});

/** 6) Activation douce + flush */
register_activation_hook(__FILE__, function () {
    try {
        if (class_exists('\\WinShirt\\Lottery')) {
            if (method_exists('\\WinShirt\\Lottery', 'instance') && method_exists('\\WinShirt\\Lottery', 'register_cpt')) {
                \WinShirt\Lottery::instance()->register_cpt();
            } elseif (method_exists('\\WinShirt\\Lottery', 'register_post_type')) {
                \WinShirt\Lottery::register_post_type();
            }
        }
        if (class_exists('\\WinShirt\\Tickets') && method_exists('\\WinShirt\\Tickets', 'install')) {
            try {
                if (method_exists('\\WinShirt\\Tickets', 'instance')) {
                    \WinShirt\Tickets::instance()->install();
                } else {
                    \WinShirt\Tickets::install();
                }
            } catch (\Throwable $t) {
                winshirt_log('activation tickets THROW: ' . $t->getMessage());
            }
        }
    } catch (\Throwable $t) {
        winshirt_log('activation THROW: ' . $t->getMessage());
    }
    flush_rewrite_rules(false);
});
