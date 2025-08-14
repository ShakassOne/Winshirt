<?php
/**
 * Plugin Name: WinShirt
 * Description: Personnalisation produits textile + loteries (RECOVERY v1.0)
 * Author: WinShirt Team ( Shakass Communication, Claude, Chatgpt )
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: winshirt
 * Domain Path: /languages
 */

if ( ! defined('ABSPATH') ) exit;

// ===== CONSTANTES PRINCIPALES =====
define( 'WINSHIRT_VERSION', '1.0.0' );
define( 'WINSHIRT_FILE', __FILE__ );
define( 'WINSHIRT_PATH', plugin_dir_path( __FILE__ ) );
define( 'WINSHIRT_URL',  plugins_url( '', __FILE__ ) . '/' );
define( 'WINSHIRT_BASENAME', plugin_basename( __FILE__ ) );

// ===== SAFE REQUIRE HELPER =====
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

// ===== CORE CLASSES (ordre d'importance) =====
winshirt_require_if_exists( 'includes/class-winshirt-core.php' );
winshirt_require_if_exists( 'includes/class-winshirt-assets.php' );

// === CPT et données ===
winshirt_require_if_exists( 'includes/class-winshirt-mockups-admin.php' );

// === Frontend et WooCommerce ===
winshirt_require_if_exists( 'includes/class-winshirt-settings.php' );

// === Modules optionnels ===
winshirt_require_if_exists( 'includes/class-winshirt-order.php' );

// ===== ACTIVATION / DÉSACTIVATION =====
register_activation_hook( __FILE__, 'winshirt_activate' );
register_deactivation_hook( __FILE__, 'winshirt_deactivate' );

function winshirt_activate() {
	// Flush rewrite rules pour les CPT
	flush_rewrite_rules();
	
	// Créer option version
	add_option( 'winshirt_version', WINSHIRT_VERSION );
	
	// Déclencher migration si nécessaire
	do_action( 'winshirt_activated' );
}

function winshirt_deactivate() {
	flush_rewrite_rules();
	do_action( 'winshirt_deactivated' );
}

// ===== BOOTSTRAP PRINCIPAL =====
add_action( 'plugins_loaded', 'winshirt_init' );

function winshirt_init() {
	// Vérifier dépendances
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'winshirt_woocommerce_required_notice' );
		return;
	}
	
	// Initialiser core
	if ( class_exists( 'WinShirt_Core' ) ) {
		WinShirt_Core::init();
	}
	
	// Action pour extensions
	do_action( 'winshirt_loaded' );
}

function winshirt_woocommerce_required_notice() {
	?>
	<div class="notice notice-error">
		<p>
			<strong><?php esc_html_e( 'WinShirt', 'winshirt' ); ?></strong> 
			<?php esc_html_e( 'nécessite WooCommerce pour fonctionner.', 'winshirt' ); ?>
		</p>
	</div>
	<?php
}

// ===== TEXTDOMAIN =====
add_action( 'init', 'winshirt_load_textdomain' );

function winshirt_load_textdomain() {
	load_plugin_textdomain( 
		'winshirt', 
		false, 
		dirname( WINSHIRT_BASENAME ) . '/languages' 
	);
}
