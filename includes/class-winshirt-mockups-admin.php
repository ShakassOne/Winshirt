<?php
/**
 * WinShirt – BO Mockups (metaboxes + éditeur zones)
 *
 * CPT ciblé : ws-mockup
 * Metas :
 *   _winshirt_mockup_front   (URL)
 *   _winshirt_mockup_back    (URL)
 *   _winshirt_mockup_colors  (CSV)
 *   _winshirt_zones          (JSON {"front":[{left,top,width,height}], "back":[...]})
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WinShirt_Mockups_Admin' ) ) {

class WinShirt_Mockups_Admin {

	public static function init() {
		add_action( 'add_meta_boxes',        [ __CLASS__, 'add_boxes' ] );
		add_action( 'save_post_ws-mockup',   [ __CLASS__, 'save' ], 10, 2 );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue' ] );
	}

	/** Ajoute les metaboxes */
	public static function add_boxes() {
		add_meta_box(
			'winshirt_mockup_images',
			__( 'Images mockup', 'winshirt' ),
			[ __CLASS__, 'box_images' ],
			'ws-mockup', 'normal', 'high'
		);

		add_meta_box(
			'winshirt_mockup_colors',
			__( 'Couleurs disponibles (CSV)', 'winshirt' ),
			[ __CLASS__, 'box_colors' ],
			'ws-mockup', 'normal', 'default'
		);

		add_meta_box(
			'winshirt_mockup_zones',
			__( 'Zones d’impression', 'winshirt' ),
			[ __CLASS__, 'box_zones' ],
			'ws-mockup', 'normal', 'high'
		);
	}

	/** Metabox images */
	public static function box_images( $post ) {
		wp_nonce_field( 'winshirt_mockup_save', '_winshirt_mockup_nonce' );

		$front = get_post_meta( $post->ID, '_winshirt_mockup_front', true );
		if ( ! $front ) $front = get_post_meta( $post->ID, 'ws_mockup_front', true );

		$back  = get_post_meta( $post->ID, '_winshirt_mockup_back', true );
		if ( ! $back ) $back = get_post_meta( $post->ID, 'ws_mockup_back', true );
		?>
		<p><label for="ws_mockup_front"><strong><?php esc_html_e( 'Image avant (recto)', 'winshirt' ); ?></strong></label></p>
		<input type="text" class="widefat" id="ws_mockup_front" name="ws_mockup_front" value="<?php echo esc_attr( $front ); ?>" placeholder="https://.../recto.png" />
		<p><em><?php esc_html_e( 'Collez l’URL complète du visuel recto (PNG/JPG transparent recommandé).', 'winshirt' ); ?></em></p>

		<p style="margin-top:1em"><label for="ws_mockup_back"><strong><?php esc_html_e( 'Image arrière (verso)', 'winshirt' ); ?></strong></label></p>
		<input type="text" class="widefat" id="ws_mockup_back" name="ws_mockup_back" value="<?php echo esc_attr( $back ); ?>" placeholder="https://.../verso.png" />
		<p><em><?php esc_html_e( 'Optionnel si vous n’utilisez que le recto.', 'winshirt' ); ?></em></p>
		<?php
	}

	/** Metabox couleurs */
	public static function box_colors( $post ) {
		$colors = get_post_meta( $post->ID, '_winshirt_mockup_colors', true );
		if ( ! $colors ) $colors = get_post_meta( $post->ID, 'ws_mockup_colors', true );
		?>
		<input type="text" class="widefat" id="ws_mockup_colors" name="ws_mockup_colors"
		       value="<?php echo esc_attr( $colors ); ?>"
		       placeholder="#000000,#FFFFFF,red,blue" />
		<p><em><?php esc_html_e( 'Liste séparée par des virgules (hex ou noms CSS). Optionnel.', 'winshirt' ); ?></em></p>
		<?php
	}

	/** Metabox zones – éditeur visuel */
	public static function box_zones( $post ) {
		$front = get_post_meta( $post->ID, '_winshirt_mockup_front', true );
		if ( ! $front ) $front = get_post_meta( $post->ID, 'ws_mockup_front', true );

		$back  = get_post_meta( $post->ID, '_winshirt_mockup_back', true );
		if ( ! $back ) $back = get_post_meta( $post->ID, 'ws_mockup_back', true );

		$zones_json = get_post_meta( $post->ID, '_winshirt_zones', true );
		if ( ! $zones_json ) $zones_json = get_post_meta( $post->ID, 'ws_mockup_zones', true );
		if ( ! is_string( $zones_json ) || $zones_json === '' ) {
			$zones_json = '{"front":[],"back":[]}';
		}
		?>
		<div class="ws-zone-editor" data-front="<?php echo esc_attr( $front ); ?>" data-back="<?php echo esc_attr( $back ); ?>">
			<div style="display:flex;gap:12px;align-items:center;margin-bottom:8px">
				<button type="button" class="button ws-ze-side" data-side="front"><?php esc_html_e( 'Recto', 'winshirt' ); ?></button>
				<button type="button" class="button ws-ze-side" data-side="back"><?php esc_html_e( 'Verso', 'winshirt' ); ?></button>
				<button type="button" class="button button-primary ws-ze-add"><?php esc_html_e( 'Ajouter une zone', 'winshirt' ); ?></button>
				<button type="button" class="button ws-ze-clear"><?php esc_html_e( 'Tout supprimer (côté courant)', 'winshirt' ); ?></button>
			</div>

			<div id="ws-ze-canvas" style="position:relative;max-width:680px;border:1px solid rgba(0,0,0,.1);background:#fff">
				<img id="ws-ze-img" src="<?php echo esc_url( $front ); ?>" alt="Mockup" style="width:100%;height:auto;display:block;object-fit:contain">
				<!-- Les rectangles .ws-ze-rect seront insérés en JS -->
			</div>

			<input type="hidden" id="ws-ze-data" name="ws_mockup_zones" value="<?php echo esc_attr( $zones_json ); ?>" />
			<p style="margin-top:6px"><em><?php esc_html_e( 'Astuce : les zones sont stockées en pourcentages du mockup.', 'winshirt' ); ?></em></p>
		</div>
		<?php
	}

	/** Sauvegarde */
	public static function save( $post_id, $post ) {
		if ( ! isset( $_POST['_winshirt_mockup_nonce'] ) || ! wp_verify_nonce( $_POST['_winshirt_mockup_nonce'], 'winshirt_mockup_save' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		if ( ! current_user_can( 'edit_post', $post_id ) ) return;

		$front  = isset( $_POST['ws_mockup_front'] )  ? esc_url_raw( trim( wp_unslash( $_POST['ws_mockup_front'] ) ) )  : '';
		$back   = isset( $_POST['ws_mockup_back'] )   ? esc_url_raw( trim( wp_unslash( $_POST['ws_mockup_back'] ) ) )   : '';
		$colors = isset( $_POST['ws_mockup_colors'] ) ? sanitize_text_field( wp_unslash( $_POST['ws_mockup_colors'] ) ) : '';

		update_post_meta( $post_id, '_winshirt_mockup_front',  $front );
		update_post_meta( $post_id, '_winshirt_mockup_back',   $back );
		update_post_meta( $post_id, '_winshirt_mockup_colors', $colors );

		// Zones JSON (valide ?)
		if ( isset( $_POST['ws_mockup_zones'] ) ) {
			$raw = wp_unslash( $_POST['ws_mockup_zones'] );
			$try = json_decode( $raw, true );
			if ( is_array( $try ) ) {
				update_post_meta( $post_id, '_winshirt_zones', wp_json_encode( $try ) );
			}
		}
	}

	/** Scripts/CSS de l’éditeur, seulement sur ws-mockup */
	public static function enqueue( $hook ) {
		global $post;
		if ( ! isset( $post->post_type ) || 'ws-mockup' !== $post->post_type ) return;

		$base = plugins_url( '', dirname( __FILE__ ) . '/../winshirt.php' ) . '/'; // racine plugin
		wp_enqueue_style( 'winshirt-admin-zones', $base . 'assets/css/admin-zones.css', [], '1.0.0' );
		wp_enqueue_script( 'winshirt-admin-zones', $base . 'assets/js/admin-zones.js', [], '1.0.0', true );
	}
}

WinShirt_Mockups_Admin::init();

}
