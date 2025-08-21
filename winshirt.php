<?php
/**
 * Plugin Name: WinShirt
 * Description: Bootstrap tolérant avec harnais de debug (log précis par module). Active les modules un par un via les constantes ci-dessous.
 * Version: 2.0.0-debugh
 * Author: WinShirt by Shakass Communication
 * Text Domain: winshirt
 */

if (!defined('ABSPATH')) exit;

define('WINSHIRT_VERSION', '2.0.0-debugh');
define('WINSHIRT_DIR', plugin_dir_path(__FILE__));
define('WINSHIRT_URL', plugin_dir_url(__FILE__));

/** === Sélecteurs de modules (active = true, par défaut tout OFF) === */
if (!defined('WINSHIRT_ENABLE_LOTTERY'))            define('WINSHIRT_ENABLE_LOTTERY', false);
if (!defined('WINSHIRT_ENABLE_TEMPLATE'))           define('WINSHIRT_ENABLE_TEMPLATE', false);
if (!defined('WINSHIRT_ENABLE_TICKETS'))            define('WINSHIRT_ENABLE_TICKETS', false);
if (!defined('WINSHIRT_ENABLE_ORDER'))              define('WINSHIRT_ENABLE_ORDER', false);
if (!defined('WINSHIRT_ENABLE_DISPLAY'))            define('WINSHIRT_ENABLE_DISPLAY', false);
if (!defined('WINSHIRT_ENABLE_PRODUCT_LINK'))       define('WINSHIRT_ENABLE_PRODUCT_LINK', false);

/** === Logger minimal vers wp-content/winshirt_fatal.log (même fichier que le mu-plugin) === */
if (!function_exists('winshirt_log')) {
    function winshirt_log(string $msg): void {
        // Security: pas d'I/O si WP_CONTENT_DIR pas défini, fallback error_log
        $line = '[WinShirt ' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
        if (defined('WP_CONTENT_DIR')) {
            @file_put_contents(WP_CONTENT_DIR . '/winshirt_fatal.log', $line, FILE_APPEND);
        } else {
            @error_log($line);
        }
    }
}

/** === Require tolérant (ne jamais fatal) + log clair === */
if (!function_exists('winshirt_require')) {
    function winshirt_require(string $rel): bool {
        $path = WINSHIRT_DIR . ltrim($rel, '/');
        if (file_exists($path)) {
            require_once $path;
            winshirt_log("require OK: {$rel}");
            return true;
        }
        winshirt_log("require MISS: {$rel}");
        return false;
    }
}

/** === Drapeau utilitaire : environnement === */
winshirt_log('=== BOOT START === WP ' . get_bloginfo('version') . ' PHP ' . PHP_VERSION . (defined('WC_VERSION') ? (' WC ' . WC_VERSION) : ' WC:NA'));

/** === Inclusions (ne font rien si fichier manquant) === */
if (WINSHIRT_ENABLE_LOTTERY)      winshirt_require('includes/class-winshirt-lottery.php');
if (WINSHIRT_ENABLE_TEMPLATE)     winshirt_require('includes/class-winshirt-lottery-template.php');
if (WINSHIRT_ENABLE_TICKETS)      winshirt_require('includes/class-winshirt-tickets.php');
if (WINSHIRT_ENABLE_ORDER)        winshirt_require('includes/class-winshirt-lottery-order.php');
if (WINSHIRT_ENABLE_DISPLAY)      winshirt_require('includes/class-winshirt-lottery-display.php');
if (WINSHIRT_ENABLE_PRODUCT_LINK) winshirt_require('includes/class-winshirt-lottery-product-link.php');

/** === Activation : registration soft + install tickets (jamais fatal si absent) === */
register_activation_hook(__FILE__, function () {
    try {
        if (class_exists('\\WinShirt\\Lottery')) {
            // Compat: certains projets ont register_cpt() (instance) vs register_post_type() (static)
            if (method_exists('\\WinShirt\\Lottery', 'instance') && method_exists('\\WinShirt\\Lottery', 'register_cpt')) {
                \WinShirt\Lottery::instance()->register_cpt();
                winshirt_log('activation: Lottery::instance()->register_cpt()');
            } elseif (method_exists('\\WinShirt\\Lottery', 'register_post_type')) {
                \WinShirt\Lottery::register_post_type();
                winshirt_log('activation: Lottery::register_post_type()');
            } else {
                winshirt_log('activation: Lottery present, no register_* method found');
            }
        }
        if (class_exists('\\WinShirt\\Tickets')) {
            if (method_exists('\\WinShirt\\Tickets', 'instance') && method_exists('\\WinShirt\\Tickets', 'install')) {
                \WinShirt\Tickets::instance()->install();
                winshirt_log('activation: Tickets::instance()->install()');
            } elseif (method_exists('\\WinShirt\\Tickets', 'install')) {
                \WinShirt\Tickets::install();
                winshirt_log('activation: Tickets::install() static');
            }
        }
        flush_rewrite_rules(false);
    } catch (\Throwable $t) {
        winshirt_log('activation THROW: ' . get_class($t) . ' ' . $t->getMessage() . ' @ ' . $t->getFile() . ':' . $t->getLine());
    }
});

/** === Boot runtime : init conditionnel avec try/catch pour loguer le vrai fautif === */
add_action('plugins_loaded', function () {
    $boot = function (string $class, string $label) {
        if (!class_exists($class)) {
            winshirt_log("init SKIP {$label}: class not found");
            return;
        }
        try {
            // Instance pattern commun
            if (method_exists($class, 'instance') && method_exists($class, 'init')) {
                $class::instance()->init();
                winshirt_log("init OK {$label}: instance()->init()");
                return;
            }
            // Pattern init() static
            if (method_exists($class, 'init')) {
                $class::init();
                winshirt_log("init OK {$label}: static init()");
                return;
            }
            winshirt_log("init SKIP {$label}: no init method");
        } catch (\Throwable $t) {
            winshirt_log("init THROW {$label}: " . get_class($t) . ' ' . $t->getMessage() . ' @ ' . $t->getFile() . ':' . $t->getLine());
        }
    };

    if (WINSHIRT_ENABLE_LOTTERY)      $boot('\\WinShirt\\Lottery',             'Lottery');
    if (WINSHIRT_ENABLE_TEMPLATE)     $boot('\\WinShirt\\Lottery_Template',    'Lottery_Template');
    if (WINSHIRT_ENABLE_TICKETS)      $boot('\\WinShirt\\Tickets',             'Tickets');
    if (WINSHIRT_ENABLE_ORDER)        $boot('\\WinShirt\\Lottery_Order',       'Lottery_Order');
    if (WINSHIRT_ENABLE_DISPLAY)      $boot('\\WinShirt\\Lottery_Display',     'Lottery_Display');
    if (WINSHIRT_ENABLE_PRODUCT_LINK) $boot('\\WinShirt\\Lottery_Product_Link','Lottery_Product_Link');
});

/** === Assets (laisse OFF tant que le cœur n’est pas validé) === */
// add_action('wp_enqueue_scripts', function () {
//     wp_enqueue_style('winshirt-lottery', WINSHIRT_URL . 'assets/css/winshirt-lottery.css', [], WINSHIRT_VERSION);
//     wp_enqueue_script('winshirt-lottery', WINSHIRT_URL . 'assets/js/winshirt-lottery.js', ['jquery'], WINSHIRT_VERSION, true);
// });

/** === Bandeau admin: état des modules (utile) === */
add_action('admin_notices', function () {
    if (!current_user_can('activate_plugins')) return;
    $on = [];
    if (WINSHIRT_ENABLE_LOTTERY)      $on[] = 'Lottery';
    if (WINSHIRT_ENABLE_TEMPLATE)     $on[] = 'Template';
    if (WINSHIRT_ENABLE_TICKETS)      $on[] = 'Tickets';
    if (WINSHIRT_ENABLE_ORDER)        $on[] = 'Order';
    if (WINSHIRT_ENABLE_DISPLAY)      $on[] = 'Display';
    if (WINSHIRT_ENABLE_PRODUCT_LINK) $on[] = 'Product_Link';
    $msg = empty($on) ? 'Aucun module actif (mode debug).' : ('Modules actifs: ' . implode(', ', $on));
    echo '<div class="notice notice-warning"><p><strong>WinShirt Debug:</strong> ' . esc_html($msg) . '</p></div>';
});
