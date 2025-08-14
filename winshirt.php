<?php
/**
 * Plugin Name: WinShirt
 * Description: Personnalisation produits textile + loteries (RECOVERY v1.0)
 * Author: WinShirt Team (Shakass Communication, Claude, ChatGPT)
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
		error_log( "WinShirt: Fichier manquant - " . $abs );
		return false;
	}
}

// ===== CORE CLASSES (ordre critique) =====
// 1. Core principal
winshirt_require_if_exists( 'includes/class-winshirt-core.php' );

// 2. Assets et frontend
winshirt_require_if_exists( 'includes/class-winshirt-assets.php' );

// 3. CPT et données ⭐ NOUVEAU
winshirt_require_if_exists( 'includes/class-winshirt-cpt.php' );

// 4. Admin interface
winshirt_require_if_exists( 'includes/class-winshirt-admin.php' );

// 5. Roadmap et tracking ⭐ NOUVEAU
winshirt_require_if_exists( 'includes/class-winshirt-roadmap.php' );

// 6. Settings et WooCommerce
winshirt_require_if_exists( 'includes/class-winshirt-settings.php' );

// 7. Modules avancés
winshirt_require_if_exists( 'includes/class-winshirt-order.php' );

// ===== ACTIVATION / DÉSACTIVATION =====
register_activation_hook( __FILE__, 'winshirt_activate' );
register_deactivation_hook( __FILE__, 'winshirt_deactivate' );

function winshirt_activate() {
	// Flush rewrite rules pour les CPT
	flush_rewrite_rules();
	
	// Créer option version
	add_option( 'winshirt_version', WINSHIRT_VERSION );
	
	// Initialiser progression roadmap si elle n'existe pas
	if ( ! get_option( 'winshirt_completed_tasks' ) ) {
		add_option( 'winshirt_completed_tasks', [] );
	}
	
	// Déclencher migration si nécessaire
	do_action( 'winshirt_activated' );
	
	// Log d'activation
	error_log( 'WinShirt: Plugin activé - version ' . WINSHIRT_VERSION );
}

function winshirt_deactivate() {
	flush_rewrite_rules();
	do_action( 'winshirt_deactivated' );
	error_log( 'WinShirt: Plugin désactivé' );
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
	
	// Log de debug avec statut des modules
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		$modules_status = [
			'Core' => class_exists( 'WinShirt_Core' ) ? 'OK' : 'MANQUANT',
			'Assets' => class_exists( 'WinShirt_Assets' ) ? 'OK' : 'MANQUANT',
			'CPT' => class_exists( 'WinShirt_CPT' ) ? 'OK' : 'MANQUANT',
			'Admin' => class_exists( 'WinShirt_Admin' ) ? 'OK' : 'MANQUANT',
			'Roadmap' => class_exists( 'WinShirt_Roadmap' ) ? 'OK' : 'MANQUANT',
			'Settings' => class_exists( 'WinShirt_Settings' ) ? 'OK' : 'MANQUANT',
		];
		error_log( 'WinShirt: Modules chargés - ' . json_encode( $modules_status ) );
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

// ===== DEBUG INFO (en mode développement) =====
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
	add_action( 'admin_footer', function() {
		if ( current_user_can( 'manage_options' ) ) {
			echo '<!-- WinShirt Debug: Version ' . WINSHIRT_VERSION . ' -->';
			echo '<!-- CPT Status: ws-mockup=' . ( post_type_exists( 'ws-mockup' ) ? 'OK' : 'NO' ) . ' -->';
		}
	});
}

// ===== HOOK DE RÉCUPÉRATION (temporaire) =====
// Afficher les erreurs de chargement en admin
add_action( 'admin_notices', function() {
	if ( current_user_can( 'manage_options' ) && isset( $_GET['page'] ) && strpos( $_GET['page'], 'winshirt' ) === 0 ) {
		$missing_files = [];
		
		$critical_files = [
			'includes/class-winshirt-core.php',
			'includes/class-winshirt-cpt.php',
			'includes/class-winshirt-admin.php',
			'includes/class-winshirt-roadmap.php'
		];
		
		foreach ( $critical_files as $file ) {
			if ( ! file_exists( WINSHIRT_PATH . $file ) ) {
				$missing_files[] = $file;
			}
		}
		
		if ( ! empty( $missing_files ) ) {
			?>
			<div class="notice notice-error">
				<p><strong>WinShirt:</strong> Fichiers manquants détectés :</p>
				<ul>
					<?php foreach ( $missing_files as $file ) : ?>
						<li><code><?php echo esc_html( $file ); ?></code></li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php
		}
	}
});
