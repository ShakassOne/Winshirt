<?php
/**
 * WinShirt CPT - Custom Post Types (Recovery v1.0)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WinShirt_CPT' ) ) {

class WinShirt_CPT {

	public static function init() {
		add_action( 'init', [ __CLASS__, 'register_post_types' ] );
		add_action( 'init', [ __CLASS__, 'register_taxonomies' ] );
		add_action( 'add_meta_boxes', [ __CLASS__, 'add_meta_boxes' ] );
		add_action( 'save_post', [ __CLASS__, 'save_meta_boxes' ] );
	}

	/**
	 * Enregistrer les CPT
	 */
	public static function register_post_types() {
		
		// ===== MOCKUPS =====
		register_post_type( 'ws-mockup', [
			'labels' => [
				'name' => __( 'Mockups', 'winshirt' ),
				'singular_name' => __( 'Mockup', 'winshirt' ),
				'add_new' => __( 'Ajouter un mockup', 'winshirt' ),
				'add_new_item' => __( 'Nouveau mockup', 'winshirt' ),
				'edit_item' => __( 'Modifier le mockup', 'winshirt' ),
				'new_item' => __( 'Nouveau mockup', 'winshirt' ),
				'view_item' => __( 'Voir le mockup', 'winshirt' ),
				'search_items' => __( 'Rechercher mockups', 'winshirt' ),
				'not_found' => __( 'Aucun mockup trouvé', 'winshirt' ),
				'not_found_in_trash' => __( 'Aucun mockup dans la corbeille', 'winshirt' ),
			],
			'public' => false,
			'show_ui' => true,
			'show_in_menu' => false, // Géré par notre menu admin
			'capability_type' => 'post',
			'supports' => [ 'title', 'editor', 'thumbnail' ],
			'has_archive' => false,
			'rewrite' => false,
			'show_in_rest' => true, // Pour l'API REST
		]);

		// ===== DESIGNS/VISUELS =====
		register_post_type( 'ws-design', [
			'labels' => [
				'name' => __( 'Designs', 'winshirt' ),
				'singular_name' => __( 'Design', 'winshirt' ),
				'add_new' => __( 'Ajouter un design', 'winshirt' ),
				'edit_item' => __( 'Modifier le design', 'winshirt' ),
			],
			'public' => false,
			'show_ui' => true,
			'show_in_menu' => false,
			'capability_type' => 'post',
			'supports' => [ 'title', 'editor' ],
			'has_archive' => false,
			'rewrite' => false,
			'show_in_rest' => true,
		]);

		// ===== LOTERIES =====
		register_post_type( 'ws-lottery', [
			'labels' => [
				'name' => __( 'Loteries', 'winshirt' ),
				'singular_name' => __( 'Loterie', 'winshirt' ),
				'add_new' => __( 'Ajouter une loterie', 'winshirt' ),
				'edit_item' => __( 'Modifier la loterie', 'winshirt' ),
			],
			'public' => false,
			'show_ui' => true,
			'show_in_menu' => false,
			'capability_type' => 'post',
			'supports' => [ 'title', 'editor', 'thumbnail' ],
			'has_archive' => false,
			'rewrite' => false,
			'show_in_rest' => true,
		]);
	}

	/**
	 * Enregistrer les taxonomies
	 */
	public static function register_taxonomies() {
		
		// Famille de produits (t-shirt, sweat, casquette...)
		register_taxonomy( 'ws-product-family', 'ws-mockup', [
			'labels' => [
				'name' => __( 'Familles produit', 'winshirt' ),
				'singular_name' => __( 'Famille produit', 'winshirt' ),
				'add_new_item' => __( 'Ajouter une famille', 'winshirt' ),
			],
			'public' => false,
			'show_ui' => true,
			'show_admin_column' => true,
			'hierarchical' => true,
			'rewrite' => false,
			'show_in_rest' => true,
		]);

		// Couleurs mockup
		register_taxonomy( 'ws-mockup-color', 'ws-mockup', [
			'labels' => [
				'name' => __( 'Couleurs', 'winshirt' ),
				'singular_name' => __( 'Couleur', 'winshirt' ),
				'add_new_item' => __( 'Ajouter une couleur', 'winshirt' ),
			],
			'public' => false,
			'show_ui' => true,
			'show_admin_column' => true,
			'hierarchical' => false,
			'rewrite' => false,
			'show_in_rest' => true,
		]);
	}

	/**
	 * Ajouter meta boxes
	 */
	public static function add_meta_boxes() {
		
		// Meta box Zones d'impression
		add_meta_box(
			'ws-mockup-zones',
			__( 'Zones d\'impression', 'winshirt' ),
			[ __CLASS__, 'render_zones_meta_box' ],
			'ws-mockup',
			'normal',
			'high'
		);

		// Meta box Images mockup
		add_meta_box(
			'ws-mockup-images',
			__( 'Images mockup', 'winshirt' ),
			[ __CLASS__, 'render_images_meta_box' ],
			'ws-mockup',
			'side',
			'default'
		);
	}

	/**
	 * Render meta box zones
	 */
	public static function render_zones_meta_box( $post ) {
		wp_nonce_field( 'ws_mockup_zones', 'ws_mockup_zones_nonce' );
		
		$zones = get_post_meta( $post->ID, '_ws_zones', true ) ?: [];
		
		?>
		<div id="ws-zones-editor">
			<p>
				<button type="button" id="ws-add-zone" class="button">
					<?php esc_html_e( 'Ajouter une zone', 'winshirt' ); ?>
				</button>
			</p>
			
			<div id="ws-zones-list">
				<?php if ( empty( $zones ) ) : ?>
					<p class="ws-no-zones"><?php esc_html_e( 'Aucune zone définie. Cliquez sur "Ajouter une zone" pour commencer.', 'winshirt' ); ?></p>
				<?php else : ?>
					<?php foreach ( $zones as $i => $zone ) : ?>
						<div class="ws-zone-item" data-index="<?php echo esc_attr( $i ); ?>">
							<h4><?php echo esc_html( $zone['name'] ?? "Zone {$i}" ); ?></h4>
							<label>
								<?php esc_html_e( 'Nom:', 'winshirt' ); ?>
								<input type="text" name="zones[<?php echo $i; ?>][name]" value="<?php echo esc_attr( $zone['name'] ?? '' ); ?>" />
							</label>
							<label>
								<?php esc_html_e( 'Prix (€):', 'winshirt' ); ?>
								<input type="number" step="0.01" name="zones[<?php echo $i; ?>][price]" value="<?php echo esc_attr( $zone['price'] ?? 0 ); ?>" />
							</label>
							<button type="button" class="ws-remove-zone button-link-delete"><?php esc_html_e( 'Supprimer', 'winshirt' ); ?></button>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>

		<script>
		jQuery(document).ready(function($) {
			let zoneIndex = <?php echo count( $zones ); ?>;
			
			$('#ws-add-zone').on('click', function() {
				const html = `
					<div class="ws-zone-item" data-index="${zoneIndex}">
						<h4>Zone ${zoneIndex + 1}</h4>
						<label>
							<?php esc_html_e( 'Nom:', 'winshirt' ); ?>
							<input type="text" name="zones[${zoneIndex}][name]" value="Zone ${zoneIndex + 1}" />
						</label>
						<label>
							<?php esc_html_e( 'Prix (€):', 'winshirt' ); ?>
							<input type="number" step="0.01" name="zones[${zoneIndex}][price]" value="0" />
						</label>
						<button type="button" class="ws-remove-zone button-link-delete"><?php esc_html_e( 'Supprimer', 'winshirt' ); ?></button>
					</div>
				`;
				$('#ws-zones-list').append(html);
				$('.ws-no-zones').hide();
				zoneIndex++;
			});
			
			$(document).on('click', '.ws-remove-zone', function() {
				$(this).closest('.ws-zone-item').remove();
				if ($('.ws-zone-item').length === 0) {
					$('.ws-no-zones').show();
				}
			});
		});
		</script>

		<style>
		.ws-zone-item {
			border: 1px solid #ddd;
			padding: 15px;
			margin: 10px 0;
			background: #f9f9f9;
		}
		.ws-zone-item label {
			display: block;
			margin: 5px 0;
		}
		.ws-zone-item input {
			width: 200px;
			margin-left: 10px;
		}
		</style>
		<?php
	}

	/**
	 * Render meta box images
	 */
	public static function render_images_meta_box( $post ) {
		$recto_img = get_post_meta( $post->ID, '_ws_recto_image', true );
		$verso_img = get_post_meta( $post->ID, '_ws_verso_image', true );
		
		?>
		<div class="ws-mockup-images">
			<p>
				<label>
					<strong><?php esc_html_e( 'Image Recto:', 'winshirt' ); ?></strong><br>
					<input type="url" name="ws_recto_image" value="<?php echo esc_url( $recto_img ); ?>" style="width: 100%;" />
				</label>
			</p>
			<p>
				<label>
					<strong><?php esc_html_e( 'Image Verso:', 'winshirt' ); ?></strong><br>
					<input type="url" name="ws_verso_image" value="<?php echo esc_url( $verso_img ); ?>" style="width: 100%;" />
				</label>
			</p>
			<p><small><?php esc_html_e( 'URLs des images mockup (recto et verso)', 'winshirt' ); ?></small></p>
		</div>
		<?php
	}

	/**
	 * Sauvegarder meta boxes
	 */
	public static function save_meta_boxes( $post_id ) {
		
		// Vérifier nonce et permissions
		if ( ! isset( $_POST['ws_mockup_zones_nonce'] ) || 
			 ! wp_verify_nonce( $_POST['ws_mockup_zones_nonce'], 'ws_mockup_zones' ) ||
			 ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Sauvegarder zones
		if ( isset( $_POST['zones'] ) && is_array( $_POST['zones'] ) ) {
			$zones = [];
			foreach ( $_POST['zones'] as $zone_data ) {
				$zones[] = [
					'name' => sanitize_text_field( $zone_data['name'] ?? '' ),
					'price' => floatval( $zone_data['price'] ?? 0 ),
					'x' => 0, // À implémenter avec l'éditeur visuel
					'y' => 0,
					'width' => 100,
					'height' => 100,
				];
			}
			update_post_meta( $post_id, '_ws_zones', $zones );
		}

		// Sauvegarder images
		if ( isset( $_POST['ws_recto_image'] ) ) {
			update_post_meta( $post_id, '_ws_recto_image', esc_url_raw( $_POST['ws_recto_image'] ) );
		}
		if ( isset( $_POST['ws_verso_image'] ) ) {
			update_post_meta( $post_id, '_ws_verso_image', esc_url_raw( $_POST['ws_verso_image'] ) );
		}
	}
}

WinShirt_CPT::init();
}
