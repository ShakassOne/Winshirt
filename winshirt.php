<?php
/**
 * Plugin Name: WinShirt
 * Description: Personnalisation produits + loteries (module WinShirt).
 * Author: WinShirt
 * Version: 2.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined('ABSPATH') ) exit;

// === Constantes de base ===
define( 'WINSHIRT_VERSION', '1.0.0' );
define( 'WINSHIRT_FILE', __FILE__ );
define( 'WINSHIRT_PATH', plugin_dir_path( __FILE__ ) );
define( 'WINSHIRT_URL',  plugins_url( '', __FILE__ ) . '/' );

// === Safe require ===
if ( ! function_exists('winshirt_require_if_exists') ) {
	function winshirt_require_if_exists( $relpath ) {
		$abs = WINSHIRT_PATH . ltrim( $relpath, '/\\' );
		if ( file_exists( $abs ) ) {
			require_once $abs;
			return true;
		}
		return false;
	}
}

// === Boot minimal front (assets + données) ===
// Charge la classe d’assets (créée) pour voir bouger le module de suite
winshirt_require_if_exists( 'includes/class-winshirt-assets.php' );

// === Pré-câblage des prochains modules (ne casse rien s’ils n’existent pas encore) ===
winshirt_require_if_exists( 'includes/class-winshirt-router.php' );
winshirt_require_if_exists( 'includes/class-winshirt-rest.php' );
winshirt_require_if_exists( 'includes/class-winshirt-order.php' );
winshirt_require_if_exists( 'includes/class-winshirt-price.php' );
winshirt_require_if_exists( 'includes/class-winshirt-product-customization.php' );
winshirt_require_if_exists( 'includes/class-winshirt-designs.php' );
winshirt_require_if_exists( 'includes/class-winshirt-mockups.php' );
winshirt_require_if_exists( 'includes/class-winshirt-lottery.php' );

// === Activation / Désactivation (au besoin plus tard) ===
register_activation_hook( __FILE__, function(){
	// Placeholder: flush des règles si la classe router ajoute des rewrites
	if ( function_exists('flush_rewrite_rules') ) {
		flush_rewrite_rules();
	}
});

register_deactivation_hook( __FILE__, function(){
	if ( function_exists('flush_rewrite_rules') ) {
		flush_rewrite_rules();
	}
});

// === Petit indicateur admin si des fichiers clés manquent (non bloquant) ===
add_action('admin_notices', function(){
	if ( ! current_user_can('manage_options') ) return;

	$missing = [];
	foreach ([
		'includes/class-winshirt-assets.php',
		'assets/js/state.js',
		'assets/js/ui-router.js',
		'assets/js/ui-panels.js',
		'assets/css/winshirt-panels.css',
		'assets/css/winshirt-mobile.css',
		'assets/js/layers.js',
		'assets/js/text-tools.js',
		'assets/js/image-tools.js',
		'assets/js/price.js',
		'assets/js/uploader.js',
	] as $rel) {
		if ( ! file_exists( WINSHIRT_PATH . $rel ) ) {
			$missing[] = $rel;
		}
	}

	if ( empty($missing) ) return;

	$list = '';
	foreach ($missing as $m) {
		$list .= '<li><code>'.esc_html($m).'</code></li>';
	}
	echo '<div class="notice notice-info"><p><strong>WinShirt :</strong> certains fichiers ne sont pas encore présents (c’est normal pendant l’intégration) :</p><ul style="margin-left:20px">'.$list.'</ul></div>';
});

// === Hook léger pour forcer le chargement front si besoin de test ===
// add_filter('winshirt_force_enqueue', '__return_true'); // Décommente pour forcer l’enqueue partout (debug)

