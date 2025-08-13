<?php
/**
 * WinShirt – BO Mockups (metaboxes + éditeur zones + couleurs)
 *
 * CPT ciblé : ws-mockup
 * Metas :
 *   _winshirt_mockup_front   (URL)
 *   _winshirt_mockup_back    (URL)
 *   _winshirt_mockup_colors  (JSON array of {label,hex,front,back})
 *   _winshirt_zones          (JSON {"front":[{left,top,width,height,name,price}], "back":[...]})
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
			__( 'Couleurs & variations (recto/verso)', 'winshirt' ),
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

	/** Metabox couleurs + visuels par couleur (recto/verso) */
	public static function box_colors( $post ) {
		$stored = get_post_meta( $post->ID, '_winshirt_mockup_colors', true );

		// Back-compat : si ancienne valeur CSV, on l’interprète en lignes simples
		if ( empty( $stored ) ) {
			$csv = get_post_meta( $post->ID, 'ws_mockup_colors', true );
			if ( is_string( $csv ) && trim( $csv ) !== '' ) {
				$parts  = array_filter( array_map( 'trim', explode( ',', $csv ) ) );
				$stored = [];
				foreach ( $parts as $p ) {
					$stored[] = [ 'label' => $p, 'hex' => $p, 'front' => '', 'back' => '' ];
				}
			}
		}

		$rows = ( is_array( $stored ) ? $stored : [] );
		?>
		<p><em><?php esc_html_e( 'Ajoutez des couleurs disponibles et, si besoin, des visuels spécifiques par couleur (recto/verso).', 'winshirt' ); ?></em></p>

		<table class="widefat striped ws-colors-table">
			<thead>
				<tr>
					<th style="width:16%"><?php esc_html_e( 'Label', 'winshirt' ); ?></th>
					<th style="width:12%"><?php esc_html_e( 'Hex/nom', 'winshirt' ); ?></th>
					<th><?php esc_html_e( 'URL Recto', 'winshirt' ); ?></th>
					<th><?php esc_html_e( 'URL Verso', 'winshirt' ); ?></th>
					<th style="width:60px"></th>
				</tr>
			</thead>
			<tbody id="ws-colors-rows">
				<?php if ( empty( $rows ) ) : ?>
					<tr class="ws-color-row">
						<td><input type="text" class="widefat" name="ws_color_label[]" placeholder="Blanc" value=""></td>
						<td><input type="text" class="widefat" name="ws_color_hex[]"   placeholder="#FFFFFF" value=""></td>
						<td><input type="text" class="widefat" name="ws_color_front[]" placeholder="https://.../recto.png" value=""></td>
						<td><input type="text" class="widefat" name="ws_color_back[]"  placeholder="https://.../verso.png" value=""></td>
						<td><button type="button" class="button ws-colors-del">–</button></td>
					</tr>
				<?php else : foreach ( $rows as $r ) : ?>
					<tr class="ws-color-row">
						<td><input type="text" class="widefat" name="ws_color_label[]" value="<?php echo esc_attr( $r['label'] ?? '' ); ?>"></td>
						<td><input type="text" class="widefat" name="ws_color_hex[]"   value="<?php echo esc_attr( $r['hex']   ?? '' ); ?>"></td>
						<td><input type="text" class="widefat" name="ws_color_front[]" value="<?php echo esc_attr( $r['front'] ?? '' ); ?>"></td>
						<td><input type="text" class="widefat" name="ws_color_back[]"  value="<?php echo esc_attr( $r['back']  ?? '' ); ?>"></td>
						<td><button type="button" class="button ws-colors-del">–</button></td>
					</tr>
				<?php endforeach; endif; ?>
			</tbody>
		</table>
		<p style="margin-top:8px">
			<button type="button" class="button button-secondary ws-colors-add"><?php esc_html_e( 'Ajouter une couleur', 'winshirt' ); ?></button>
		</p>
		<?php
	}

	/** Metabox zones – éditeur visuel + panneau noms/prix */
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
		<div class="ws-zone-editor ws-has-sidebar" data-front="<?php echo esc_attr( $front ); ?>" data-back="<?php echo esc_attr( $back ); ?>">
			<div class="ws-ze-topbar">
				<div class="ws-ze-left">
					<button type="button" class="button ws-ze-side" data-side="front"><?php esc_html_e( 'Recto', 'winshirt' ); ?></button>
					<button type="button" class="button ws-ze-side" data-side="back"><?php esc_html_e( 'Verso', 'winshirt' ); ?></button>
					<button type="button" class="button button-primary ws-ze-add"><?php esc_html_e( 'Ajouter une zone', 'winshirt' ); ?></button>
					<button type="button" class="button ws-ze-clear"><?php esc_html_e( 'Tout supprimer (côté courant)', 'winshirt' ); ?></button>
				</div>
				<div class="ws-ze-right"><em><?php esc_html_e( 'Glissez/redimensionnez les zones. Éditez nom et prix ci-contre.', 'winshirt' ); ?></em></div>
			</div>

			<div class="ws-ze-layout">
				<div class="ws-ze-canvas-wrap">
					<div id="ws-ze-canvas">
						<img id="ws-ze-img" src="<?php echo esc_url( $front ); ?>" alt="Mockup">
						<!-- rectangles injectés en JS -->
					</div>
				</div>

				<aside class="ws-ze-sidebar">
					<h4 style="margin:.3rem 0 0.8rem"><?php esc_html_e( 'Zones (côté en cours)', 'winshirt' ); ?></h4>
					<div id="ws-ze-list"><!-- lignes JS --></div>
				</aside>
			</div>

			<input type="hidden" id="ws-ze-data" name="ws_mockup_zones" value="<?php echo esc_attr( $zones_json ); ?>" />
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

		update_post_meta( $post_id, '_winshirt_mockup_front',  $front );
		update_post_meta( $post_id, '_winshirt_mockup_back',   $back );

		// Couleurs (repeater)
		$labels = isset( $_POST['ws_color_label'] ) ? (array) $_POST['ws_color_label'] : [];
		$hexes  = isset( $_POST['ws_color_hex'] )   ? (array) $_POST['ws_color_hex']   : [];
		$fronts = isset( $_POST['ws_color_front'] ) ? (array) $_POST['ws_color_front'] : [];
		$backs  = isset( $_POST['ws_color_back'] )  ? (array) $_POST['ws_color_back']  : [];

		$rows = [];
		$max  = max( count( $labels ), count( $hexes ), count( $fronts ), count( $backs ) );
		for ( $i = 0; $i < $max; $i++ ) {
			$label = isset( $labels[$i] ) ? sanitize_text_field( wp_unslash( $labels[$i] ) ) : '';
			$hex   = isset( $hexes[$i] )  ? sanitize_text_field( wp_unslash( $hexes[$i] ) )   : '';
			$furl  = isset( $fronts[$i] ) ? esc_url_raw( trim( wp_unslash( $fronts[$i] ) ) )   : '';
			$burl  = isset( $backs[$i] )  ? esc_url_raw( trim( wp_unslash( $backs[$i] ) ) )    : '';
			if ( $label === '' && $hex === '' && $furl === '' && $burl === '' ) continue;

			$rows[] = [
				'label' => $label,
				'hex'   => $hex,
				'front' => $furl,
				'back'  => $burl,
			];
		}
		if ( ! empty( $rows ) ) {
			update_post_meta( $post_id, '_winshirt_mockup_colors', wp_json_encode( $rows ) );
		} else {
			delete_post_meta( $post_id, '_winshirt_mockup_colors' );
		}

		// Zones JSON (avec name/price)
		if ( isset( $_POST['ws_mockup_zones'] ) ) {
			$raw = wp_unslash( $_POST['ws_mockup_zones'] );
			$try = json_decode( $raw, true );
			if ( is_array( $try ) ) {
				// sanitation minimale des champs name/price
				foreach ( ['front','back'] as $sd ) {
					if ( ! empty( $try[$sd] ) && is_array( $try[$sd] ) ) {
						foreach ( $try[$sd] as &$z ) {
							$z['name']  = isset( $z['name'] )  ? sanitize_text_field( $z['name'] ) : '';
							$z['price'] = isset( $z['price'] ) ? floatval( $z['price'] ) : 0.0;
						}
						unset($z);
					}
				}
				update_post_meta( $post_id, '_winshirt_zones', wp_json_encode( $try ) );
			}
		}
	}

	/** Scripts/CSS admin */
	public static function enqueue( $hook ) {
		global $post;
		if ( ! isset( $post->post_type ) || 'ws-mockup' !== $post->post_type ) return;

		$plugin_main = dirname( __DIR__ ) . '/winshirt.php';
		$base = plugins_url( '', $plugin_main ) . '/';

		wp_enqueue_style( 'winshirt-admin-zones', $base . 'assets/css/admin-zones.css', [], '1.1.0' );
		wp_enqueue_script( 'winshirt-admin-zones', $base . 'assets/js/admin-zones.js', [], '1.1.0', true );
	}
}

WinShirt_Mockups_Admin::init();

}
