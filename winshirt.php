<?php
/**
 * Plugin Name: WinShirt
 * Description: Loteries WinShirt (CPT + template premium) + liaison WooCommerce Produits → Loteries (tickets).
 * Version: 1.2.0
 * Author: Shakass Com
 * Text Domain: winshirt
 */

if ( ! defined('ABSPATH') ) {
    exit;
}

/* ---------------------------------------------------------------------------
 * Constantes de base
 * -------------------------------------------------------------------------*/
define( 'WINSHIRT_VERSION',      '1.2.0' );
define( 'WINSHIRT_MIN_PHP',      '7.4' );
define( 'WINSHIRT_PLUGIN_FILE',  __FILE__ );
define( 'WINSHIRT_PLUGIN_DIR',   plugin_dir_path( __FILE__ ) );
define( 'WINSHIRT_PLUGIN_URL',   plugin_dir_url( __FILE__ ) );

/* ---------------------------------------------------------------------------
 * Vérifications minimales (version PHP)
 * -------------------------------------------------------------------------*/
if ( version_compare( PHP_VERSION, WINSHIRT_MIN_PHP, '<' ) ) {
    if ( is_admin() ) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p>'.
                 esc_html__( 'WinShirt nécessite PHP ', 'winshirt' ) .
                 esc_html( WINSHIRT_MIN_PHP ) .
                 esc_html__( ' ou supérieur. Le plugin a été désactivé.', 'winshirt' ) .
                 '</p></div>';
        });
    }
    // On stoppe le chargement (la page Extensions restera accessible).
    return;
}

/* ---------------------------------------------------------------------------
 * Internationalisation
 * -------------------------------------------------------------------------*/
add_action( 'init', function () {
    load_plugin_textdomain( 'winshirt', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
});

/* ---------------------------------------------------------------------------
 * Helper "require" avec contrôle d'existence + notice admin
 * -------------------------------------------------------------------------*/
if ( ! function_exists( 'winshirt_require' ) ) {
    /**
     * Charge un fichier du plugin en vérifiant son existence.
     * @param string $relative_path Chemin relatif à WINSHIRT_PLUGIN_DIR
     * @param bool   $fatal         Si true et fichier manquant → on stoppe (return).
     * @return bool  true si chargé, false sinon
     */
    function winshirt_require( string $relative_path, bool $fatal = true ): bool {
        $file = WINSHIRT_PLUGIN_DIR . ltrim( $relative_path, '/' );
        if ( file_exists( $file ) ) {
            require_once $file;
            return true;
        }
        if ( is_admin() ) {
            add_action( 'admin_notices', function() use ( $relative_path ) {
                echo '<div class="notice notice-error"><p><strong>WinShirt</strong> — ' .
                     sprintf( esc_html__( 'Fichier manquant : %s', 'winshirt' ), esc_html( $relative_path ) ) .
                     '</p></div>';
            } );
        }
        return ! $fatal ? false : false;
    }
}

/* ---------------------------------------------------------------------------
 * Chargement des modules "coeur loterie"
 * -------------------------------------------------------------------------*/
// Ces trois fichiers DOIVENT exister. Si l’un manque, on arrête ici proprement.
$ok = true;
$ok = winshirt_require( 'includes/class-winshirt-lottery.php', true ) && $ok;
$ok = winshirt_require( 'includes/class-winshirt-lottery-template.php', true ) && $ok;
$ok = winshirt_require( 'includes/class-winshirt-lottery-product-link.php', true ) && $ok;

if ( ! $ok ) {
    // On arrête le chargement si un fichier critique est absent.
    return;
}

/* ---------------------------------------------------------------------------
 * Hooks d’activation / désactivation
 * -------------------------------------------------------------------------*/
/**
 * À l’activation : on enregistre le CPT et on flush les permaliens.
 * Important si le site active le plugin sur un thème déjà en prod.
 */
function winshirt_activate() {
    // Enregistre le CPT pour que flush_rewrite_rules() connaisse les règles.
    if ( class_exists( '\WinShirt\Lottery' ) ) {
        // Appel "direct" au registre CPT. La classe Lottery implémente register_cpt().
        \WinShirt\Lottery::instance()->register_cpt();
    }
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'winshirt_activate' );

/**
 * À la désactivation : on flush simplement les règles.
 */
function winshirt_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'winshirt_deactivate' );

/* ---------------------------------------------------------------------------
 * Bootstrap : initialisation des modules
 * -------------------------------------------------------------------------*/
add_action( 'plugins_loaded', function () {
    // Initialisation du coeur Loterie (CPT + admin + front + emails + shortcodes)
    if ( class_exists( '\WinShirt\Lottery' ) ) {
        \WinShirt\Lottery::instance()->init();
    }

    // Template front (single loterie + assets CSS/JS)
    if ( class_exists( '\WinShirt\Lottery_Template' ) ) {
        \WinShirt\Lottery_Template::instance()->init();
    }

    // Liaison WooCommerce Produits → Loteries (onglet + tickets + bandeau front)
    if ( class_exists( '\WinShirt\Lottery_Product_Link' ) ) {
        // Si WooCommerce n’est pas actif, la classe interne s’auto-désactive.
        \WinShirt\Lottery_Product_Link::instance()->init();
    }
} );

/* ---------------------------------------------------------------------------
 * (Optionnel) Aide au debug : expose une notice si WooCommerce est manquant
 * -------------------------------------------------------------------------*/
add_action( 'admin_init', function () {
    if ( ! class_exists( 'WooCommerce' ) ) {
        // On n'empêche PAS le plugin de fonctionner (CPT + template ok),
        // on avertit juste que la partie "tickets produits" sera inactive.
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-warning"><p><strong>WinShirt :</strong> ' .
                 esc_html__( 'WooCommerce n’est pas actif. La liaison Produits → Loteries (tickets) restera désactivée, mais le CPT Loteries fonctionne.', 'winshirt' ) .
                 '</p></div>';
        } );
    }
} );
