<?php
/**
 * Admin pour le CPT ws-mockup :
 * - Image avant / arrière (URL via Media Library)
 * - Couleurs (CSV) + visuels
 * - Zones d'impression (JSON front/back)
 *
 * Meta keys standardisées :
 *   _winshirt_mockup_front   (string URL)
 *   _winshirt_mockup_back    (string URL)
 *   _winshirt_mockup_colors  (string CSV ex: #000000,#FFFFFF,blue)
 *   _winshirt_zones          (JSON : {"front":[{xPct:..}], "back":[{..}]})
 */

if ( ! defined('ABSPATH') ) exit;

if ( ! class_exists('WinShirt_Mockups_Admin') ) {

class WinShirt_Mockups_Admin {

	public static function init() {
		add_action( 'add_meta_boxes', [ __CLASS__, 'add_boxes' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin' ] );
		add_action( 'save_post_ws-mockup', [ __CLASS__, 'save' ] );
	}

	public static function add_boxes() {
		add_meta_box(
			'ws_mockup_images',
			__('Images', 'winshirt'),
			[ __CLASS__, 'box_images' ],
			'ws-mockup', 'normal', 'high'
		);
		add_meta_box(
			'ws_mockup_colors',
			__('Couleurs', 'winshirt'),
			[ __CLASS__, 'box_colors' ],
			'ws-mockup', 'normal', 'default'
		);
		add_meta_box(
			'ws_mockup_zones',
			__('Zones d’impression', 'winshirt'),
			[ __CLASS__, 'box_zones' ],
			'ws-mockup', 'normal', 'default'
		);
	}

	public static function enqueue_admin( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen || $screen->post_type !== 'ws-mockup' ) return;

		// Media frame WP
		wp_enqueue_media();

		// Un peu de style
		wp_add_inline_style( 'wp-admin', '
			.ws-field { margin-bottom:14px; }
			.ws-field label { display:block; font-weight:600; margin-bottom:6px; }
			.ws-row { display:flex; gap:12px; align-items:center; }
			.ws-url { width:100%; }
			.ws-thumb { width:120px; height:120px; border:1px solid #e0e0e0; background:#fafafa; display:flex; align-items:center; justify-content:center; overflow:hidden }
			.ws-thumb img { max-width:100%; max-height:100%; display:block }
			.ws-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
			.ws-textarea { width:100%; min-height:120px; font-family:inherit; }
			.ws-help { font-size:12px; opacity:.75; }
		' );

		// JS pour boutons "Téléverser"
		wp_add_inline_script( 'jquery-core', "
		(function($){
			$(document).on('click','.ws-pick',function(e){
				e.preventDefault();
				var $wrap = $(this).closest('.ws-row');
				var $inp  = $wrap.find('input[type=text]');
				var $th   = $wrap.find('.ws-thumb');

				var frame = wp.media({
					title: 'Choisir une image',
					button: { text: 'Utiliser cette image' },
					multiple: false
				});
				frame.on('select', function(){
					var att = frame.state().get('selection').first().toJSON();
					$inp.val(att.url).trigger('change');
					$th.html('<img src=\"'+att.url+'\" alt=\"\">');
				});
				frame.open();
			});

			$(document).on('change','.ws-url',function(){
				var url = $(this).val();
				var $th = $(this).closest('.ws-row').find('.ws-thumb');
				if(url){ $th.html('<img src=\"'+url+'\" alt=\"\">'); }
				else   { $th.html('—'); }
			});
		})(jQuery);
		" );
	}

	// --- BOXES ---

	public static function box_images( $post ) {
		$front = get_post_meta( $post->ID, '_winshirt_mockup_front', true );
		$back  = get_post_meta( $post->ID, '_winshirt_mockup_back', true );

		wp_nonce_field( 'ws_mockup_save', 'ws_mockup_nonce' );

		echo '<div class="ws-field">';
		echo '<label>'.esc_html__('Image avant (recto)', 'winshirt').'</label>';
		echo '<div class="ws-row">';
		printf('<input class="ws-url" type="text" name="_winshirt_mockup_front" value="%s" placeholder="https://…" />', esc_attr($front) );
		echo '<button class="button ws-pick">'.esc_html__('Téléverser', 'winshirt').'</button>';
		echo '<div class="ws-thumb">'.( $front ? '<img src="'.esc_url($front).'" alt="">' : '—' ).'</div>';
		echo '</div>';
		echo '</div>';

		echo '<div class="ws-field">';
		echo '<label>'.esc_html__('Image arrière (verso)', 'winshirt').'</label>';
		echo '<div class="ws-row">';
		printf('<input class="ws-url" type="text" name="_winshirt_mockup_back" value="%s" placeholder="https://…" />', esc_attr($back) );
		echo '<button class="button ws-pick">'.esc_html__('Téléverser', 'winshirt').'</button>';
		echo '<div class="ws-thumb">'.( $back ? '<img src="'.esc_url($back).'" alt="">' : '—' ).'</div>';
		echo '</div>';
		echo '</div>';
	}

	public static function box_colors( $post ) {
		$colors = get_post_meta( $post->ID, '_winshirt_mockup_colors', true );

		echo '<div class="ws-field">';
		echo '<label>'.esc_html__('Couleurs disponibles (CSV)', 'winshirt').'</label>';
		printf('<input class="regular-text ws-mono" type="text" name="_winshirt_mockup_colors" value="%s" placeholder="#000000,#FFFFFF,red,blue" />', esc_attr($colors) );
		echo '<p class="ws-help">'.esc_html__('Liste séparée par des virgules (hex ou noms CSS). Optionnel.', 'winshirt').'</p>';
		echo '</div>';
	}

	public static function box_zones( $post ) {
		$zones = get_post_meta( $post->ID, '_winshirt_zones', true );
		$pretty = '';
		if ( is_array( $zones ) ) {
			$pretty = wp_json_encode( $zones, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		} elseif ( is_string( $zones ) ) {
			$pretty = $zones;
		}
		if ( empty( $pretty ) ) {
			$pretty = "{\n  \"front\": [ { \"xPct\": 20, \"yPct\": 20, \"wPct\": 60, \"hPct\": 45 } ],\n  \"back\":  [ { \"xPct\": 20, \"yPct\": 20, \"wPct\": 60, \"hPct\": 45 } ]\n}";
		}

		echo '<div class="ws-field">';
		echo '<label>'.esc_html__('Zones (JSON)', 'winshirt').'</label>';
		printf('<textarea class="ws-textarea ws-mono" name="_winshirt_zones" spellcheck="false">%s</textarea>', esc_textarea($pretty) );
		echo '<p class="ws-help">'.esc_html__('Structure attendue : {"front":[{xPct,yPct,wPct,hPct}], "back":[…]}. Les pourcentages sont relatifs au mockup.', 'winshirt').'</p>';
		echo '</div>';
	}

	// --- SAVE ---

	public static function save( $post_id ) {
		if ( ! isset($_POST['ws_mockup_nonce']) || ! wp_verify_nonce( $_POST['ws_mockup_nonce'], 'ws_mockup_save' ) ) return;
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
		if ( ! current_user_can( 'edit_post', $post_id ) ) return;

		// URLs images
		$front = isset($_POST['_winshirt_mockup_front']) ? esc_url_raw( trim($_POST['_winshirt_mockup_front']) ) : '';
		$back  = isset($_POST['_winshirt_mockup_back'])  ? esc_url_raw( trim($_POST['_winshirt_mockup_back']) )  : '';
		update_post_meta( $post_id, '_winshirt_mockup_front', $front );
		update_post_meta( $post_id, '_winshirt_mockup_back',  $back );

		// Couleurs CSV
		$colors = isset($_POST['_winshirt_mockup_colors']) ? sanitize_text_field( $_POST['_winshirt_mockup_colors'] ) : '';
		update_post_meta( $post_id, '_winshirt_mockup_colors', $colors );

		// Zones JSON
		$zones_raw = isset($_POST['_winshirt_zones']) ? wp_unslash( $_POST['_winshirt_zones'] ) : '';
		$zones_dec = json_decode( (string) $zones_raw, true );
		if ( is_array($zones_dec) && isset($zones_dec['front']) && isset($zones_dec['back']) ) {
			update_post_meta( $post_id, '_winshirt_zones', $zones_dec );
		} else {
			// On enregistre tel quel pour que l’admin puisse corriger — mais on ne casse pas
			update_post_meta( $post_id, '_winshirt_zones', $zones_raw );
		}
	}
}

WinShirt_Mockups_Admin::init();
}
