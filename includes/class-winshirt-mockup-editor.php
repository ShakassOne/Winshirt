<?php
/**
 * WinShirt Mockup Editor - Interface visuelle complète (Recovery v1.0)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WinShirt_Mockup_Editor' ) ) {

class WinShirt_Mockup_Editor {

	public static function init() {
		add_action( 'add_meta_boxes', [ __CLASS__, 'add_meta_boxes' ] );
		add_action( 'save_post', [ __CLASS__, 'save_mockup_data' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_assets' ] );
		
		// AJAX endpoints
		add_action( 'wp_ajax_ws_upload_mockup_image', [ __CLASS__, 'ajax_upload_image' ] );
		add_action( 'wp_ajax_ws_save_zones', [ __CLASS__, 'ajax_save_zones' ] );
	}

	/**
	 * Enqueue assets pour l'admin
	 */
	public static function enqueue_admin_assets( $hook ) {
		if ( $hook === 'post.php' || $hook === 'post-new.php' ) {
			global $post;
			if ( $post && $post->post_type === 'ws-mockup' ) {
				
				// CSS
				wp_enqueue_style( 'ws-mockup-editor', WINSHIRT_URL . 'assets/css/mockup-editor.css', [], WINSHIRT_VERSION );
				
				// JS
				wp_enqueue_script( 'ws-mockup-editor', WINSHIRT_URL . 'assets/js/mockup-editor.js', [ 'jquery', 'jquery-ui-draggable', 'jquery-ui-resizable' ], WINSHIRT_VERSION, true );
				
				// Localisation
				wp_localize_script( 'ws-mockup-editor', 'wsMockupEditor', [
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'nonce' => wp_create_nonce( 'ws_mockup_editor' ),
					'post_id' => $post->ID,
					'i18n' => [
						'add_color' => __( 'Ajouter une couleur', 'winshirt' ),
						'add_zone' => __( 'Ajouter une zone', 'winshirt' ),
						'delete_confirm' => __( 'Êtes-vous sûr de vouloir supprimer ?', 'winshirt' ),
						'upload_error' => __( 'Erreur lors de l\'upload', 'winshirt' ),
						'save_success' => __( 'Zones sauvegardées !', 'winshirt' ),
					]
				]);
				
				// Media uploader
				wp_enqueue_media();
			}
		}
	}

	/**
	 * Ajouter meta boxes
	 */
	public static function add_meta_boxes() {
		
		// Meta box principale - Couleurs et images
		add_meta_box(
			'ws-mockup-colors',
			__( 'Couleurs et Images', 'winshirt' ),
			[ __CLASS__, 'render_colors_meta_box' ],
			'ws-mockup',
			'normal',
			'high'
		);

		// Meta box - Éditeur de zones
		add_meta_box(
			'ws-mockup-zones-editor',
			__( 'Éditeur de Zones d\'Impression', 'winshirt' ),
			[ __CLASS__, 'render_zones_editor_meta_box' ],
			'ws-mockup',
			'normal',
			'high'
		);

		// Meta box - Paramètres
		add_meta_box(
			'ws-mockup-settings',
			__( 'Paramètres du Mockup', 'winshirt' ),
			[ __CLASS__, 'render_settings_meta_box' ],
			'ws-mockup',
			'side',
			'default'
		);
	}

	/**
	 * Meta box - Couleurs et images
	 */
	public static function render_colors_meta_box( $post ) {
		wp_nonce_field( 'ws_mockup_data', 'ws_mockup_nonce' );
		
		$colors = get_post_meta( $post->ID, '_ws_mockup_colors', true ) ?: [];
		
		?>
		<div class="ws-colors-manager">
			<div class="ws-colors-header">
				<button type="button" id="ws-add-color" class="button button-primary">
					<span class="dashicons dashicons-plus"></span>
					<?php esc_html_e( 'Ajouter une couleur', 'winshirt' ); ?>
				</button>
				<p class="description">
					<?php esc_html_e( 'Gérez les différentes couleurs de votre mockup avec leurs images recto/verso.', 'winshirt' ); ?>
				</p>
			</div>

			<div id="ws-colors-list" class="ws-colors-list">
				<?php if ( empty( $colors ) ) : ?>
					<div class="ws-no-colors">
						<p><?php esc_html_e( 'Aucune couleur définie. Cliquez sur "Ajouter une couleur" pour commencer.', 'winshirt' ); ?></p>
					</div>
				<?php else : ?>
					<?php foreach ( $colors as $index => $color ) : ?>
						<?php self::render_color_item( $index, $color ); ?>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render un item couleur
	 */
	private static function render_color_item( $index, $color ) {
		$color_name = $color['name'] ?? '';
		$color_hex = $color['hex'] ?? '#ffffff';
		$recto_img = $color['recto'] ?? '';
		$verso_img = $color['verso'] ?? '';
		$is_default = isset( $color['default'] ) && $color['default'];
		
		?>
		<div class="ws-color-item" data-index="<?php echo esc_attr( $index ); ?>">
			<div class="ws-color-header">
				<div class="ws-color-preview" style="background-color: <?php echo esc_attr( $color_hex ); ?>"></div>
				<input type="text" 
					   name="colors[<?php echo $index; ?>][name]" 
					   value="<?php echo esc_attr( $color_name ); ?>"
					   placeholder="<?php esc_attr_e( 'Nom de la couleur', 'winshirt' ); ?>"
					   class="ws-color-name" />
				<input type="color" 
					   name="colors[<?php echo $index; ?>][hex]" 
					   value="<?php echo esc_attr( $color_hex ); ?>"
					   class="ws-color-picker" />
				<label class="ws-default-color">
					<input type="radio" 
						   name="default_color" 
						   value="<?php echo $index; ?>"
						   <?php checked( $is_default ); ?> />
					<?php esc_html_e( 'Par défaut', 'winshirt' ); ?>
				</label>
				<button type="button" class="ws-remove-color button-link-delete">
					<span class="dashicons dashicons-trash"></span>
				</button>
			</div>

			<div class="ws-color-images">
				<div class="ws-image-upload">
					<label><?php esc_html_e( 'Image Recto:', 'winshirt' ); ?></label>
					<div class="ws-upload-area" data-side="recto">
						<?php if ( $recto_img ) : ?>
							<img src="<?php echo esc_url( $recto_img ); ?>" class="ws-preview-img" />
							<input type="hidden" name="colors[<?php echo $index; ?>][recto]" value="<?php echo esc_url( $recto_img ); ?>" />
							<button type="button" class="ws-remove-image">×</button>
						<?php else : ?>
							<div class="ws-upload-placeholder">
								<span class="dashicons dashicons-plus"></span>
								<span><?php esc_html_e( 'Cliquez pour uploader', 'winshirt' ); ?></span>
							</div>
						<?php endif; ?>
					</div>
				</div>

				<div class="ws-image-upload">
					<label><?php esc_html_e( 'Image Verso:', 'winshirt' ); ?></label>
					<div class="ws-upload-area" data-side="verso">
						<?php if ( $verso_img ) : ?>
							<img src="<?php echo esc_url( $verso_img ); ?>" class="ws-preview-img" />
							<input type="hidden" name="colors[<?php echo $index; ?>][verso]" value="<?php echo esc_url( $verso_img ); ?>" />
							<button type="button" class="ws-remove-image">×</button>
						<?php else : ?>
							<div class="ws-upload-placeholder">
								<span class="dashicons dashicons-plus"></span>
								<span><?php esc_html_e( 'Cliquez pour uploader', 'winshirt' ); ?></span>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Meta box - Éditeur de zones
	 */
	public static function render_zones_editor_meta_box( $post ) {
		$zones = get_post_meta( $post->ID, '_ws_mockup_zones', true ) ?: [];
		$colors = get_post_meta( $post->ID, '_ws_mockup_colors', true ) ?: [];
		
		// Trouver l'image par défaut
		$default_image = '';
		foreach ( $colors as $color ) {
			if ( isset( $color['default'] ) && $color['default'] && ! empty( $color['recto'] ) ) {
				$default_image = $color['recto'];
				break;
			}
		}
		
		if ( ! $default_image && ! empty( $colors ) && ! empty( $colors[0]['recto'] ) ) {
			$default_image = $colors[0]['recto'];
		}
		
		?>
		<div class="ws-zones-editor">
			
			<!-- Contrôles -->
			<div class="ws-zones-controls">
				<button type="button" id="ws-add-zone" class="button button-primary">
					<span class="dashicons dashicons-plus"></span>
					<?php esc_html_e( 'Ajouter une zone', 'winshirt' ); ?>
				</button>
				
				<div class="ws-side-selector">
					<label>
						<input type="radio" name="active_side" value="recto" checked />
						<?php esc_html_e( 'Recto', 'winshirt' ); ?>
					</label>
					<label>
						<input type="radio" name="active_side" value="verso" />
						<?php esc_html_e( 'Verso', 'winshirt' ); ?>
					</label>
				</div>

				<button type="button" id="ws-save-zones" class="button button-secondary">
					<?php esc_html_e( 'Sauvegarder les zones', 'winshirt' ); ?>
				</button>
			</div>

			<!-- Canvas principal -->
			<div class="ws-zones-workspace">
				
				<!-- Canvas des zones -->
				<div class="ws-zones-canvas" id="ws-zones-canvas">
					<?php if ( $default_image ) : ?>
						<img src="<?php echo esc_url( $default_image ); ?>" 
							 class="ws-mockup-background" 
							 id="ws-mockup-bg" 
							 data-recto="<?php echo esc_url( $default_image ); ?>"
							 data-verso="<?php echo esc_url( $default_image ); ?>" />
					<?php else : ?>
						<div class="ws-no-mockup">
							<p><?php esc_html_e( 'Ajoutez d\'abord une couleur avec images pour voir l\'éditeur.', 'winshirt' ); ?></p>
						</div>
					<?php endif; ?>
					
					<!-- Zones existantes -->
					<?php foreach ( $zones as $index => $zone ) : ?>
						<div class="ws-zone-rectangle" 
							 data-zone-id="<?php echo esc_attr( $index ); ?>"
							 data-side="<?php echo esc_attr( $zone['side'] ?? 'recto' ); ?>"
							 style="left: <?php echo esc_attr( $zone['x'] ?? 0 ); ?>%; 
									top: <?php echo esc_attr( $zone['y'] ?? 0 ); ?>%; 
									width: <?php echo esc_attr( $zone['w'] ?? 20 ); ?>%; 
									height: <?php echo esc_attr( $zone['h'] ?? 20 ); ?>%;">
							<div class="ws-zone-label"><?php echo esc_html( $zone['name'] ?? "Zone {$index}" ); ?></div>
							<div class="ws-zone-handles">
								<div class="ws-handle ws-handle-nw"></div>
								<div class="ws-handle ws-handle-ne"></div>
								<div class="ws-handle ws-handle-sw"></div>
								<div class="ws-handle ws-handle-se"></div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>

				<!-- Panneau des zones -->
				<div class="ws-zones-panel">
					<h4><?php esc_html_e( 'Zones d\'impression', 'winshirt' ); ?></h4>
					<div id="ws-zones-list" class="ws-zones-list">
						<?php if ( empty( $zones ) ) : ?>
							<p class="ws-no-zones"><?php esc_html_e( 'Aucune zone définie.', 'winshirt' ); ?></p>
						<?php else : ?>
							<?php foreach ( $zones as $index => $zone ) : ?>
								<div class="ws-zone-item" data-zone-id="<?php echo esc_attr( $index ); ?>">
									<input type="text" 
										   value="<?php echo esc_attr( $zone['name'] ?? "Zone {$index}" ); ?>"
										   placeholder="<?php esc_attr_e( 'Nom de la zone', 'winshirt' ); ?>"
										   class="ws-zone-name" />
									<input type="number" 
										   value="<?php echo esc_attr( $zone['price'] ?? 0 ); ?>"
										   step="0.01" 
										   min="0"
										   placeholder="0.00"
										   class="ws-zone-price" />
									<span class="ws-zone-coords">
										<?php printf( 
											'%dx%d', 
											round( $zone['w'] ?? 0 ), 
											round( $zone['h'] ?? 0 ) 
										); ?>
									</span>
									<button type="button" class="ws-remove-zone-btn">×</button>
								</div>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<!-- Data hidden pour JS -->
			<textarea id="ws-zones-data" name="mockup_zones" style="display: none;"><?php echo esc_textarea( json_encode( $zones ) ); ?></textarea>
		</div>
		<?php
	}

	/**
	 * Meta box - Settings
	 */
	public static function render_settings_meta_box( $post ) {
		$family = get_post_meta( $post->ID, '_ws_product_family', true );
		$max_zones = get_post_meta( $post->ID, '_ws_max_zones', true ) ?: 3;
		$zone_pricing = get_post_meta( $post->ID, '_ws_zone_pricing_mode', true ) ?: 'fixed';
		
		?>
		<div class="ws-mockup-settings">
			<p>
				<label>
					<strong><?php esc_html_e( 'Famille de produit:', 'winshirt' ); ?></strong><br>
					<select name="product_family" style="width: 100%;">
						<option value=""><?php esc_html_e( 'Sélectionner...', 'winshirt' ); ?></option>
						<option value="tshirt" <?php selected( $family, 'tshirt' ); ?>>T-shirt</option>
						<option value="sweat" <?php selected( $family, 'sweat' ); ?>>Sweat</option>
						<option value="casquette" <?php selected( $family, 'casquette' ); ?>>Casquette</option>
						<option value="polo" <?php selected( $family, 'polo' ); ?>>Polo</option>
					</select>
				</label>
			</p>

			<p>
				<label>
					<strong><?php esc_html_e( 'Zones maximum:', 'winshirt' ); ?></strong><br>
					<input type="number" name="max_zones" value="<?php echo esc_attr( $max_zones ); ?>" min="1" max="10" style="width: 100%;" />
				</label>
			</p>

			<p>
				<label>
					<strong><?php esc_html_e( 'Tarification zones:', 'winshirt' ); ?></strong><br>
					<select name="zone_pricing_mode" style="width: 100%;">
						<option value="fixed" <?php selected( $zone_pricing, 'fixed' ); ?>><?php esc_html_e( 'Prix fixe par zone', 'winshirt' ); ?></option>
						<option value="cumulative" <?php selected( $zone_pricing, 'cumulative' ); ?>><?php esc_html_e( 'Prix cumulatif', 'winshirt' ); ?></option>
						<option value="surface" <?php selected( $zone_pricing, 'surface' ); ?>><?php esc_html_e( 'Prix par surface', 'winshirt' ); ?></option>
					</select>
				</label>
			</p>
		</div>
		<?php
	}

	/**
	 * Sauvegarder les données du mockup
	 */
	public static function save_mockup_data( $post_id ) {
		
		// Vérifications
		if ( ! isset( $_POST['ws_mockup_nonce'] ) || 
			 ! wp_verify_nonce( $_POST['ws_mockup_nonce'], 'ws_mockup_data' ) ||
			 ! current_user_can( 'edit_post', $post_id ) ||
			 get_post_type( $post_id ) !== 'ws-mockup' ) {
			return;
		}

		// Sauvegarder couleurs
		if ( isset( $_POST['colors'] ) && is_array( $_POST['colors'] ) ) {
			$colors = [];
			$default_color = isset( $_POST['default_color'] ) ? intval( $_POST['default_color'] ) : 0;
			
			foreach ( $_POST['colors'] as $index => $color_data ) {
				$colors[] = [
					'name' => sanitize_text_field( $color_data['name'] ?? '' ),
					'hex' => sanitize_hex_color( $color_data['hex'] ?? '#ffffff' ),
					'recto' => esc_url_raw( $color_data['recto'] ?? '' ),
					'verso' => esc_url_raw( $color_data['verso'] ?? '' ),
					'default' => ( $index == $default_color )
				];
			}
			update_post_meta( $post_id, '_ws_mockup_colors', $colors );
		}

		// Sauvegarder zones (via AJAX séparément)
		if ( isset( $_POST['mockup_zones'] ) ) {
			$zones_json = stripslashes( $_POST['mockup_zones'] );
			$zones = json_decode( $zones_json, true );
			if ( is_array( $zones ) ) {
				update_post_meta( $post_id, '_ws_mockup_zones', $zones );
			}
		}

		// Sauvegarder settings
		if ( isset( $_POST['product_family'] ) ) {
			update_post_meta( $post_id, '_ws_product_family', sanitize_text_field( $_POST['product_family'] ) );
		}
		if ( isset( $_POST['max_zones'] ) ) {
			update_post_meta( $post_id, '_ws_max_zones', intval( $_POST['max_zones'] ) );
		}
		if ( isset( $_POST['zone_pricing_mode'] ) ) {
			update_post_meta( $post_id, '_ws_zone_pricing_mode', sanitize_text_field( $_POST['zone_pricing_mode'] ) );
		}
	}

	/**
	 * AJAX: Upload d'image
	 */
	public static function ajax_upload_image() {
		check_ajax_referer( 'ws_mockup_editor', 'nonce' );
		
		if ( ! current_user_can( 'upload_files' ) ) {
			wp_die( 'Unauthorized' );
		}

		if ( ! isset( $_FILES['image'] ) || $_FILES['image']['error'] !== UPLOAD_ERR_OK ) {
			wp_send_json_error( 'Upload failed' );
		}

		$upload = wp_handle_upload( $_FILES['image'], [ 'test_form' => false ] );
		
		if ( isset( $upload['error'] ) ) {
			wp_send_json_error( $upload['error'] );
		}

		wp_send_json_success( [
			'url' => $upload['url'],
			'file' => $upload['file']
		]);
	}

	/**
	 * AJAX: Sauvegarder zones
	 */
	public static function ajax_save_zones() {
		check_ajax_referer( 'ws_mockup_editor', 'nonce' );
		
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( 'Unauthorized' );
		}

		$post_id = intval( $_POST['post_id'] ?? 0 );
		$zones_json = stripslashes( $_POST['zones'] ?? '[]' );
		$zones = json_decode( $zones_json, true );

		if ( ! $post_id || ! is_array( $zones ) ) {
			wp_send_json_error( 'Invalid data' );
		}

		update_post_meta( $post_id, '_ws_mockup_zones', $zones );
		wp_send_json_success();
	}
}

WinShirt_Mockup_Editor::init();
}
