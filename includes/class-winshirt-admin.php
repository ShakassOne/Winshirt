<?php
/**
 * WinShirt Admin - Menu et pages d'administration (Recovery v1.0)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WinShirt_Admin' ) ) {

class WinShirt_Admin {

	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'add_menu' ] );
		add_action( 'admin_menu', [ __CLASS__, 'cleanup_menu' ], 99 );
		add_action( 'admin_notices', [ __CLASS__, 'admin_notices' ] );
	}

	/**
	 * Ajouter menu principal WinShirt
	 */
	public static function add_menu() {
		add_menu_page(
			__( 'WinShirt', 'winshirt' ),
			__( 'WinShirt', 'winshirt' ),
			'manage_options',
			'winshirt',
			[ __CLASS__, 'dashboard_page' ],
			'dashicons-tshirt',
			56
		);

		// Sous-menu Mockups
		add_submenu_page(
			'winshirt',
			__( 'Mockups', 'winshirt' ),
			__( 'Mockups', 'winshirt' ),
			'edit_posts',
			'edit.php?post_type=ws-mockup'
		);

		add_submenu_page(
			'winshirt',
			__( 'Ajouter un mockup', 'winshirt' ),
			__( 'Ajouter un mockup', 'winshirt' ),
			'edit_posts',
			'post-new.php?post_type=ws-mockup'
		);

		// Sous-menu Designs/Visuels (si le CPT existe)
		if ( post_type_exists( 'ws-design' ) ) {
			add_submenu_page(
				'winshirt',
				__( 'Visuels', 'winshirt' ),
				__( 'Visuels', 'winshirt' ),
				'edit_posts',
				'edit.php?post_type=ws-design'
			);
		}

		// Page de statut
		add_submenu_page(
			'winshirt',
			__( 'Statut', 'winshirt' ),
			__( 'Statut', 'winshirt' ),
			'manage_options',
			'winshirt-status',
			[ __CLASS__, 'status_page' ]
		);
	}

	/**
	 * Nettoyer menu (supprimer doublons)
	 */
	public static function cleanup_menu() {
		// Supprimer sous-menus générés automatiquement par les CPT
		remove_submenu_page( 'winshirt', 'edit-tags.php?taxonomy=ws-design-category&post_type=ws-design' );
	}

	/**
	 * Page dashboard principale
	 */
	public static function dashboard_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'WinShirt - Personnalisation Textile', 'winshirt' ); ?></h1>
			
			<div class="card">
				<h2><?php esc_html_e( 'Démarrage rapide', 'winshirt' ); ?></h2>
				<ol>
					<li>
						<strong><?php esc_html_e( 'Créer un mockup', 'winshirt' ); ?></strong> - 
						<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=ws-mockup' ) ); ?>">
							<?php esc_html_e( 'Ajouter un mockup', 'winshirt' ); ?>
						</a>
					</li>
					<li>
						<strong><?php esc_html_e( 'Configurer un produit', 'winshirt' ); ?></strong> - 
						Aller dans Produits → Modifier → Cocher "Activer la personnalisation"
					</li>
					<li>
						<strong><?php esc_html_e( 'Tester', 'winshirt' ); ?></strong> - 
						Aller sur la page produit → Cliquer "Personnaliser"
					</li>
				</ol>
			</div>

			<div class="card">
				<h2><?php esc_html_e( 'Statistiques', 'winshirt' ); ?></h2>
				<?php self::render_stats(); ?>
			</div>

			<div class="card">
				<h2><?php esc_html_e( 'Phase de récupération', 'winshirt' ); ?></h2>
				<p>✅ <strong>Phase 0 terminée</strong> - Plugin stabilisé</p>
				<p>🚀 Prêt pour Phase 1 - Nettoyage architecture</p>
			</div>

		</div>
		<?php
	}

	/**
	 * Page de statut
	 */
	public static function status_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Statut WinShirt', 'winshirt' ); ?></h1>

			<table class="widefat">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Composant', 'winshirt' ); ?></th>
						<th><?php esc_html_e( 'Statut', 'winshirt' ); ?></th>
						<th><?php esc_html_e( 'Détails', 'winshirt' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php self::render_status_rows(); ?>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Fichiers critiques', 'winshirt' ); ?></h2>
			<?php self::check_critical_files(); ?>

		</div>
		<?php
	}

	/**
	 * Render statistiques
	 */
	private static function render_stats() {
		$mockups_count = wp_count_posts( 'ws-mockup' )->publish ?? 0;
		$products_count = self::count_customizable_products();
		
		echo '<p>';
		printf( 
			esc_html__( 'Mockups créés : %d | Produits personnalisables : %d', 'winshirt' ),
			$mockups_count,
			$products_count
		);
		echo '</p>';
	}

	/**
	 * Render lignes de statut
	 */
	private static function render_status_rows() {
		$checks = [
			'WooCommerce' => class_exists( 'WooCommerce' ),
			'Core WinShirt' => class_exists( 'WinShirt_Core' ),
			'Assets' => class_exists( 'WinShirt_Assets' ),
			'Settings' => class_exists( 'WinShirt_Settings' ),
			'CPT Mockups' => post_type_exists( 'ws-mockup' ),
		];

		foreach ( $checks as $component => $status ) {
			$status_text = $status ? '✅ OK' : '❌ Manquant';
			$status_class = $status ? 'notice-success' : 'notice-error';
			
			echo '<tr>';
			echo '<td>' . esc_html( $component ) . '</td>';
			echo '<td><span class="' . esc_attr( $status_class ) . '">' . $status_text . '</span></td>';
			echo '<td>' . ( $status ? 'Chargé' : 'Non trouvé' ) . '</td>';
			echo '</tr>';
		}
	}

	/**
	 * Vérifier fichiers critiques
	 */
	private static function check_critical_files() {
		$critical_files = [
			'assets/css/frontend.css',
			'assets/js/customizer.js',
			'templates/modal-customizer.php',
			'includes/class-winshirt-core.php',
			'includes/class-winshirt-assets.php'
		];

		echo '<ul>';
		foreach ( $critical_files as $file ) {
			$exists = file_exists( WINSHIRT_PATH . $file );
			$status = $exists ? '✅' : '❌';
			echo '<li>' . $status . ' <code>' . esc_html( $file ) . '</code></li>';
		}
		echo '</ul>';
	}

	/**
	 * Compter produits personnalisables
	 */
	private static function count_customizable_products() {
		global $wpdb;
		
		$count = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->postmeta} pm 
			 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id 
			 WHERE pm.meta_key = %s 
			 AND pm.meta_value = %s 
			 AND p.post_type = %s 
			 AND p.post_status = %s",
			'_winshirt_enable',
			'yes',
			'product',
			'publish'
		) );

		return (int) $count;
	}

	/**
	 * Notices admin
	 */
	public static function admin_notices() {
		// Notice si aucun mockup
		if ( ! self::has_mockups() ) {
			?>
			<div class="notice notice-warning">
				<p>
					<strong><?php esc_html_e( 'WinShirt', 'winshirt' ); ?></strong> - 
					<?php esc_html_e( 'Aucun mockup créé.', 'winshirt' ); ?>
					<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=ws-mockup' ) ); ?>">
						<?php esc_html_e( 'Créer le premier mockup', 'winshirt' ); ?>
					</a>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Vérifier si des mockups existent
	 */
	private static function has_mockups() {
		$mockups = get_posts( [
			'post_type' => 'ws-mockup',
			'numberposts' => 1,
			'post_status' => 'publish'
		] );
		
		return ! empty( $mockups );
	}
}

WinShirt_Admin::init();
}
