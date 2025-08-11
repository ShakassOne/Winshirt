<?php
/**
 * WinShirt REST API
 * Routes :
 *  - POST /winshirt/v1/save-design       (sauve previews recto/verso + JSON calques)
 *  - POST /winshirt/v1/upload-dataurl    (upload d'une image à partir d'une dataURL)
 *  - POST /winshirt/v1/price             (calcul prix côté serveur, optionnel)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WinShirt_REST' ) ) {

	class WinShirt_REST {

		public static function init() {
			add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
		}

		public static function register_routes() {
			register_rest_route( 'winshirt/v1', '/save-design', [
				'methods'             => 'POST',
				'permission_callback' => [ __CLASS__, 'check_permissions' ],
				'args'                => [
					'product_id'   => [ 'type' => 'integer', 'required' => false ],
					'front_dataurl'=> [ 'type' => 'string',  'required' => false ],
					'back_dataurl' => [ 'type' => 'string',  'required' => false ],
					'layers_json'  => [ 'type' => 'string',  'required' => true ], // JSON stringifié des calques (front/back)
					'lottery_id'   => [ 'type' => 'integer', 'required' => false ],
				],
				'callback'            => [ __CLASS__, 'save_design' ],
			] );

			register_rest_route( 'winshirt/v1', '/upload-dataurl', [
				'methods'             => 'POST',
				'permission_callback' => [ __CLASS__, 'check_permissions' ],
				'args'                => [
					'data_url' => [ 'type' => 'string', 'required' => true ],
					'filename' => [ 'type' => 'string', 'required' => false ],
				],
				'callback'            => [ __CLASS__, 'upload_dataurl' ],
			] );

			register_rest_route( 'winshirt/v1', '/price', [
				'methods'             => 'POST',
				'permission_callback' => '__return_true', // lecture/estimation publique
				'args'                => [
					// Simplifié : on attend zone {width,height} et layers {front:[], back:[]}
					'zone'   => [ 'type' => 'object', 'required' => true ],
					'layers' => [ 'type' => 'object', 'required' => true ],
					'config' => [ 'type' => 'object', 'required' => false ],
				],
				'callback'            => [ __CLASS__, 'compute_price' ],
			] );
		}

		/**
		 * Vérifie le nonce REST + connexion
		 */
		public static function check_permissions( $request ) {
			// Par défaut : requiert connexion + nonce (X-WP-Nonce)
			if ( ! is_user_logged_in() ) {
				return new WP_Error( 'winshirt_rest_forbidden', __( 'Authentification requise.', 'winshirt' ), [ 'status' => 401 ] );
			}
			$nonce = $request->get_header( 'x_wp_nonce' );
			if ( ! $nonce && isset( $_SERVER['HTTP_X_WP_NONCE'] ) ) {
				$nonce = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_WP_NONCE'] ) );
			}
			if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
				return new WP_Error( 'winshirt_rest_bad_nonce', __( 'Nonce invalide.', 'winshirt' ), [ 'status' => 403 ] );
			}
			return true;
		}

		/**
		 * POST /save-design
		 * Sauvegarde les previews (front/back) et le JSON des calques en meta d'un "design".
		 * Pour rester neutre tant que le CPT n'existe pas, on retourne juste les attachments.
		 */
		public static function save_design( WP_REST_Request $req ) {
			$product_id    = (int) $req->get_param( 'product_id' );
			$front_dataurl = $req->get_param( 'front_dataurl' );
			$back_dataurl  = $req->get_param( 'back_dataurl' );
			$layers_json   = $req->get_param( 'layers_json' );
			$lottery_id    = (int) $req->get_param( 'lottery_id' );

			$current_user = get_current_user_id();
			if ( ! $current_user ) {
				return new WP_Error( 'winshirt_rest_no_user', __( 'Utilisateur requis.', 'winshirt' ), [ 'status' => 401 ] );
			}

			$result = [
				'product_id' => $product_id,
				'user_id'    => $current_user,
				'lottery_id' => $lottery_id ?: null,
				'front'      => null,
				'back'       => null,
				'layers'     => null,
			];

			// Sauvegarde des dataURL (si fournies)
			if ( $front_dataurl ) {
				$front = self::save_dataurl_as_attachment( $front_dataurl, 'winshirt-front-preview.png', $current_user );
				if ( is_wp_error( $front ) ) return $front;
				$result['front'] = $front;
			}
			if ( $back_dataurl ) {
				$back  = self::save_dataurl_as_attachment( $back_dataurl,  'winshirt-back-preview.png',  $current_user );
				if ( is_wp_error( $back ) ) return $back;
				$result['back'] = $back;
			}

			// JSON calques (front/back)
			$json = json_decode( $layers_json, true );
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				return new WP_Error( 'winshirt_rest_bad_json', __( 'layers_json invalide.', 'winshirt' ), [ 'status' => 400 ] );
			}
			$result['layers'] = $json;

			// Ici, plus tard : création d’un CPT "winshirt_design" si souhaité
			// (pour le moment, on renvoie juste les attachments + json pour add-to-cart)

			return rest_ensure_response( $result );
		}

		/**
		 * POST /upload-dataurl
		 * Upload une image dataURL en attachment WP.
		 */
		public static function upload_dataurl( WP_REST_Request $req ) {
			$data_url = $req->get_param( 'data_url' );
			$filename = $req->get_param( 'filename' );
			if ( ! $data_url ) {
				return new WP_Error( 'winshirt_rest_missing', __( 'data_url manquant.', 'winshirt' ), [ 'status' => 400 ] );
			}

			$user_id = get_current_user_id();
			if ( ! $user_id ) {
				return new WP_Error( 'winshirt_rest_no_user', __( 'Utilisateur requis.', 'winshirt' ), [ 'status' => 401 ] );
			}

			$att = self::save_dataurl_as_attachment( $data_url, $filename ?: 'winshirt-upload.png', $user_id );
			if ( is_wp_error( $att ) ) return $att;

			return rest_ensure_response( $att );
		}

		/**
		 * POST /price
		 * Calcule un prix simple côté serveur (miroir du client) :
		 * - zone: {width,height}
		 * - layers: {front:[{width,height}], back:[...]}
		 * - config: { base, perSideBase, tiers:[{maxPct,label,price}] }
		 */
		public static function compute_price( WP_REST_Request $req ) {
			$zone   = (array) $req->get_param( 'zone' );
			$layers = (array) $req->get_param( 'layers' );
			$config = (array) $req->get_param( 'config' );

			$zone_w = max( 1, (int) ($zone['width']  ?? 0) );
			$zone_h = max( 1, (int) ($zone['height'] ?? 0) );
			$zone_area = $zone_w * $zone_h;

			$tiers = $config['tiers'] ?? [
				[ 'maxPct' => 5,   'label' => 'A7', 'price' => 3.5 ],
				[ 'maxPct' => 12,  'label' => 'A6', 'price' => 6.0 ],
				[ 'maxPct' => 25,  'label' => 'A5', 'price' => 9.0 ],
				[ 'maxPct' => 45,  'label' => 'A4', 'price' => 12.0 ],
				[ 'maxPct' => 75,  'label' => 'A3', 'price' => 16.0 ],
				[ 'maxPct' => 100, 'label' => 'MAX','price' => 20.0 ],
			];

			$base        = (float) ( $config['base']        ?? 0 );
			$perSideBase = (float) ( $config['perSideBase'] ?? 0 );

			$front_pct = self::compute_percent( $layers['front'] ?? [], $zone_area );
			$back_pct  = self::compute_percent( $layers['back']  ?? [], $zone_area );

			$front_info = self::tier_for_percent( $front_pct, $tiers );
			$back_info  = self::tier_for_percent( $back_pct,  $tiers );

			$total = $base
				+ ( $front_pct > 0 ? ( $perSideBase + (float)$front_info['price'] ) : 0 )
				+ ( $back_pct  > 0 ? ( $perSideBase + (float)$back_info['price'] )  : 0 );

			$detail = [
				'base'  => round( $base, 2 ),
				'total' => round( $total, 2 ),
				'sides' => [
					'front' => [
						'pct'    => round( $front_pct, 2 ),
						'format' => $front_info['label'] ?? null,
						'price'  => isset($front_info['price']) ? round((float)$front_info['price'] + $perSideBase, 2) : 0,
					],
					'back' => [
						'pct'    => round( $back_pct, 2 ),
						'format' => $back_info['label'] ?? null,
						'price'  => isset($back_info['price']) ? round((float)$back_info['price'] + $perSideBase, 2) : 0,
					],
				],
			];

			return rest_ensure_response( $detail );
		}

		// ==== Helpers =======================================================

		private static function compute_percent( $layers, $zone_area ) {
			$sum = 0;
			if ( is_array( $layers ) ) {
				foreach ( $layers as $l ) {
					$w = isset($l['width'])  ? (float)$l['width']  : 0;
					$h = isset($l['height']) ? (float)$l['height'] : 0;
					if ( $w > 0 && $h > 0 ) {
						$sum += $w * $h;
					}
				}
			}
			if ( $zone_area <= 0 ) return 0.0;
			$pct = ( $sum / $zone_area ) * 100;
			if ( $pct < 0 ) $pct = 0;
			if ( $pct > 100 ) $pct = 100;
			return $pct;
		}

		private static function tier_for_percent( $pct, $tiers ) {
			$pct = (float) $pct;
			foreach ( $tiers as $t ) {
				$max = (float) ( $t['maxPct'] ?? 100 );
				if ( $pct <= $max ) return $t;
			}
			return end( $tiers );
		}

		/**
		 * Sauvegarde une dataURL en attachment WordPress et retourne
		 * [ 'id'=>attachment_id, 'url'=>guid, 'file'=>path ]
		 */
		private static function save_dataurl_as_attachment( $data_url, $filename, $user_id ) {
			$data_url = trim( (string) $data_url );
			if ( strpos( $data_url, 'data:' ) !== 0 ) {
				return new WP_Error( 'winshirt_bad_dataurl', __( 'DataURL invalide.', 'winshirt' ), [ 'status' => 400 ] );
			}

			// Parse header
			$parts = explode( ',', $data_url, 2 );
			if ( count( $parts ) !== 2 ) {
				return new WP_Error( 'winshirt_dataurl_parse', __( 'DataURL mal formée.', 'winshirt' ), [ 'status' => 400 ] );
			}
			$meta = $parts[0]; // data:image/png;base64
			$base64 = $parts[1];

			if ( preg_match( '#^data:(.*?);base64$#', $meta, $m ) ) {
				$mime = $m[1];
			} else {
				$mime = 'image/png';
			}

			$raw = base64_decode( str_replace( ' ', '+', $base64 ) );
			if ( ! $raw ) {
				return new WP_Error( 'winshirt_dataurl_decode', __( 'Impossible de décoder la DataURL.', 'winshirt' ), [ 'status' => 400 ] );
			}

			$upload_dir = wp_upload_dir();
			if ( ! empty( $upload_dir['error'] ) ) {
				return new WP_Error( 'winshirt_upload_dir', $upload_dir['error'], [ 'status' => 500 ] );
			}

			$ext = self::mime_to_ext( $mime ) ?: 'png';
			$sanitized = sanitize_file_name( $filename ?: 'winshirt-image.' . $ext );
			if ( ! str_ends_with( strtolower( $sanitized ), '.' . strtolower( $ext ) ) ) {
				$sanitized .= '.' . $ext;
			}

			$target = trailingslashit( $upload_dir['path'] ) . wp_unique_filename( $upload_dir['path'], $sanitized );

			// Écrit le fichier
			$written = file_put_contents( $target, $raw );
			if ( ! $written ) {
				return new WP_Error( 'winshirt_write_fail', __( 'Écriture du fichier échouée.', 'winshirt' ), [ 'status' => 500 ] );
			}

			$filetype = wp_check_filetype( $target, null );
			$attachment = [
				'post_mime_type' => $filetype['type'] ?: $mime,
				'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $target ) ),
				'post_content'   => '',
				'post_status'    => 'inherit',
			];

			$attach_id = wp_insert_attachment( $attachment, $target );
			if ( is_wp_error( $attach_id ) || ! $attach_id ) {
				@unlink( $target );
				return new WP_Error( 'winshirt_attach_fail', __( 'Création de la pièce jointe échouée.', 'winshirt' ), [ 'status' => 500 ] );
			}

			require_once ABSPATH . 'wp-admin/includes/image.php';
			$attach_data = wp_generate_attachment_metadata( $attach_id, $target );
			wp_update_attachment_metadata( $attach_id, $attach_data );

			// Attache l'image à l'utilisateur (auteur)
			wp_update_post( [
				'ID'          => $attach_id,
				'post_author' => (int) $user_id,
			] );

			return [
				'id'   => (int) $attach_id,
				'url'  => wp_get_attachment_url( $attach_id ),
				'file' => $target,
			];
		}

		private static function mime_to_ext( $mime ) {
			$map = [
				'image/png'  => 'png',
				'image/jpeg' => 'jpg',
				'image/jpg'  => 'jpg',
				'image/webp' => 'webp',
				'image/gif'  => 'gif',
				'image/svg+xml' => 'svg',
			];
			return $map[ strtolower( $mime ) ] ?? null;
		}
	}

	WinShirt_REST::init();
}
