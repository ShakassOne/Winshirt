<?php
/**
 * WinShirt Core - Bootstrap principal (Recovery v1.0)
 * Centralise l'initialisation et coordonne tous les modules
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WinShirt_Core' ) ) {

class WinShirt_Core {
	
	/**
	 * Instance unique (singleton)
	 */
	private static $instance = null;
	
	/**
	 * Modules chargés
	 */
	private $modules = array();
	
	/**
	 * Status d'initialisation
	 */
	private $initialized = false;
	
	/**
	 * Initialisation publique
	 */
	public static function init() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	/**
	 * Constructeur privé (singleton)
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
		$this->init_modules();
		$this->initialized = true;
		
		do_action( 'winshirt_core_loaded', $this );
	}
	
	/**
	 * Charger les dépendances requises
	 */
	private function load_dependencies() {
		// Assets management (critique)
		if ( class_exists( 'WinShirt_Assets' ) ) {
			$this->modules['assets'] = 'WinShirt_Assets';
		}
		
		// Mockups admin
		if ( class_exists( 'WinShirt_Mockups_Admin' ) ) {
			$this->modules['mockups_admin'] = 'WinShirt_Mockups_Admin';
		}
		
		// Settings produits
		if ( class_exists( 'WinShirt_Settings' ) ) {
			$this->modules['settings'] = 'WinShirt_Settings';
		}
		
		// Order integration
		if ( class_exists( 'WinShirt_Order' ) ) {
			$this->modules['order'] = 'WinShirt_Order';
		}
	}
	
	/**
	 * Initialiser les hooks WordPress
	 */
	private function init_hooks() {
		// AJAX handlers
		add_action( 'wp_ajax_winshirt_save_design', array( $this, 'ajax_save_design' ) );
		add_action( 'wp_ajax_nopriv_winshirt_save_design', array( $this, 'ajax_save_design' ) );
		
		// Admin notices
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
	}
	
	/**
	 * Initialiser les modules
	 */
	private function init_modules() {
		foreach ( $this->modules as $module_key => $module_class ) {
			if ( class_exists( $module_class ) && method_exists( $module_class, 'init' ) ) {
				call_user_func( array( $module_class, 'init' ) );
				do_action( "winshirt_module_loaded_{$module_key}", $module_class );
			}
		}
	}
	
	/**
	 * AJAX: Sauvegarder design personnalisé
	 */
	public function ajax_save_design() {
		// Vérifier nonce
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'winshirt_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Sécurité : Nonce invalide', 'winshirt' ) ) );
		}
		
		// Récupérer données
		$design_data_raw = wp_unslash( $_POST['design_data'] ?? '{}' );
		$product_id = absint( $_POST['product_id'] ?? 0 );
		$preview_dataurl = $_POST['preview_dataurl'] ?? '';
		
		// Valider JSON
		$design_data = json_decode( $design_data_raw, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			wp_send_json_error( array( 'message' => __( 'Données JSON invalides', 'winshirt' ) ) );
		}
		
		// Valider produit
		if ( ! $product_id || 'product' !== get_post_type( $product_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Produit invalide', 'winshirt' ) ) );
		}
		
		// Générer preview si fournie
		$preview_url = '';
		if ( $preview_dataurl && $this->is_valid_dataurl( $preview_dataurl ) ) {
			$preview_url = $this->save_preview_image( $preview_dataurl, $product_id );
		}
		
		// Réponse succès
		wp_send_json_success( array(
			'message' => __( 'Design sauvegardé avec succès', 'winshirt' ),
			'preview_url' => $preview_url,
			'design_data' => $design_data
		) );
	}
	
	/**
	 * Valider DataURL
	 */
	private function is_valid_dataurl( $dataurl ) {
		return ( strpos( $dataurl, 'data:image/' ) === 0 && strpos( $dataurl, 'base64,' ) !== false );
	}
	
	/**
	 * Sauvegarder image preview depuis DataURL
	 */
	private function save_preview_image( $dataurl, $product_id ) {
		// Extraire données base64
		$data_parts = explode( ',', $dataurl );
		if ( count( $data_parts ) !== 2 ) {
			return '';
		}
		
		$base64_data = $data_parts[1];
		$image_data = base64_decode( $base64_data );
		
		if ( ! $image_data ) {
			return '';
		}
		
		// Répertoire uploads
		$upload_dir = wp_upload_dir();
		if ( ! empty( $upload_dir['error'] ) ) {
			return '';
		}
		
		// Nom fichier unique
		$filename = sprintf( 
			'winshirt-preview-%d-%d-%s.png',
			$product_id,
			get_current_user_id(),
			wp_generate_password( 8, false )
		);
		
		$filepath = trailingslashit( $upload_dir['path'] ) . $filename;
		
		// Écrire fichier
		if ( file_put_contents( $filepath, $image_data ) ) {
			return trailingslashit( $upload_dir['url'] ) . $filename;
		}
		
		return '';
	}
	
	/**
	 * Notices admin
	 */
	public function admin_notices() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		
		// Vérifier que les assets critiques existent
		$critical_files = array(
			'assets/css/frontend.css',
			'assets/js/customizer.js',
			'templates/modal-customizer.php'
		);
		
		$missing_files = array();
		foreach ( $critical_files as $file ) {
			if ( ! file_exists( WINSHIRT_PATH . $file ) ) {
				$missing_files[] = $file;
			}
		}
		
		if ( ! empty( $missing_files ) ) {
			?>
			<div class="notice notice-warning">
				<p>
					<strong><?php esc_html_e( 'WinShirt', 'winshirt' ); ?></strong>
					<?php esc_html_e( 'Fichiers critiques manquants:', 'winshirt' ); ?>
				</p>
				<ul style="margin-left: 20px;">
					<?php foreach ( $missing_files as $file ) : ?>
						<li><code><?php echo esc_html( $file ); ?></code></li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php
		}
	}
	
	/**
	 * Getter modules chargés
	 */
	public function get_loaded_modules() {
		return $this->modules;
	}
	
	/**
	 * Status d'initialisation
	 */
	public function is_initialized() {
		return $this->initialized;
	}
	
	/**
	 * Version du plugin
	 */
	public function get_version() {
		return WINSHIRT_VERSION;
	}
}

} // end class_exists check
