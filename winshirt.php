<?php
/**
 * Plugin Name: WinShirt
 * Description: Personnalisation produits + loteries (module WinShirt).
 * Author: WinShirt
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined('ABSPATH') ) exit;

// === Constantes ===
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

// === Assets front ===
winshirt_require_if_exists( 'includes/class-winshirt-assets.php' );

// === Pré-câblage modules ===
winshirt_require_if_exists( 'includes/class-winshirt-router.php' );
winshirt_require_if_exists( 'includes/class-winshirt-rest.php' );
winshirt_require_if_exists( 'includes/class-winshirt-order.php' );
winshirt_require_if_exists( 'includes/class-winshirt-price.php' );
winshirt_require_if_exists( 'includes/class-winshirt-product-customization.php' );

// === Admin / Réglages / Menu ===
winshirt_require_if_exists( 'includes/class-winshirt-admin.php' );
winshirt_require_if_exists( 'includes/class-winshirt-settings.php' );

// === Activation / Désactivation ===
register_activation_hook( __FILE__, function(){
	if ( function_exists('flush_rewrite_rules') ) flush_rewrite_rules();
});
register_deactivation_hook( __FILE__, function(){
	if ( function_exists('flush_rewrite_rules') ) flush_rewrite_rules();
});

// === Admin bootstrap (instancie les classes si besoin) ===
add_action( 'plugins_loaded', function () {
	// Certaines classes s’auto-initialisent ; au cas où, on instancie l’admin.
	if ( class_exists( 'WinShirt_Admin' ) && ! did_action('winshirt_admin_booted') ) {
		do_action('winshirt_admin_booted', new WinShirt_Admin() );
	}
	if ( class_exists( 'WinShirt_Settings' ) && method_exists( 'WinShirt_Settings', 'init' ) ) {
		// déjà appelé dans la classe, mais idempotent
		WinShirt_Settings::init();
	}
});

// === Notice info (non bloquante) tant que tout n’est pas en place ===
add_action('admin_notices', function(){
	if ( ! current_user_can('manage_options') ) return;
	$missing = [];
	foreach ([
		'assets/js/state.js',
		'assets/js/ui-router.js',
		'assets/js/ui-panels.js',
		'assets/js/mockup-canvas.js',
		'assets/js/layers.js',
		'assets/css/winshirt-helpers.css',
		'templates/modal-customizer.php',
	] as $rel) {
		if ( ! file_exists( WINSHIRT_PATH . $rel ) ) $missing[] = $rel;
	}
	if ( $missing ) {
		echo '<div class="notice notice-info"><p><strong>WinShirt :</strong> certains fichiers sont manquants pendant l’intégration :</p><ul style="margin-left:20px">';
		foreach ($missing as $m) echo '<li><code>'.esc_html($m).'</code></li>';
		echo '</ul></div>';
	}
});
