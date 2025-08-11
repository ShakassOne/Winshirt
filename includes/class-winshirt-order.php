<?php
/**
 * WinShirt - Intégration WooCommerce (panier / commande / emails)
 *
 * Objectif :
 * - Stocker dans l’item du panier les infos de personnalisation :
 *   previews recto/verso (URLs) + JSON des calques + id loterie éventuel
 * - Propager ces infos sur la commande (order items) et les afficher
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WinShirt_Order' ) ) {

class WinShirt_Order {

	public static function init() {
		// Lors de l'ajout au panier, on lit des champs POST (ou JS) et on les accroche à l'item
		add_filter( 'woocommerce_add_cart_item_data', [ __CLASS__, 'add_cart_item_data' ], 10, 3 );

		// Affichage côté panier/checkout
		add_filter( 'woocommerce_get_item_data', [ __CLASS__, 'display_item_data' ], 10, 2 );

		// Transfert vers la commande
		add_action( 'woocommerce_checkout_create_order_line_item', [ __CLASS__, 'add_order_item_meta' ], 10, 4 );

		// Affichage BO (liste des articles d'une commande)
		add_action( 'woocommerce_before_order_itemmeta', [ __CLASS__, 'admin_render_previews' ], 10, 3 );
	}

	/**
	 * Lecture des données envoyées par le front.
	 * Convention : le front poste un champ caché "winshirt_payload" (JSON)
	 * Exemple :
	 * {
	 *   "frontPreviewUrl": ".../front.png",
	 *   "backPreviewUrl":  ".../back.png",
	 *   "layers": { "front":[...], "back":[...] },
	 *   "lotteryId": 123
	 * }
	 */
	public static function add_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
		if ( empty( $_POST['winshirt_payload'] ) ) {
			return $cart_item_data;
		}

		$raw = wp_unslash( $_POST['winshirt_payload'] );
		$payload = json_decode( $raw, true );

		if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $payload ) ) {
			return $cart_item_data;
		}

		$data = [
			'frontPreviewUrl' => isset($payload['frontPreviewUrl']) ? esc_url_raw( $payload['frontPreviewUrl'] ) : '',
			'backPreviewUrl'  => isset($payload['backPreviewUrl'])  ? esc_url_raw( $payload['backPreviewUrl'] )  : '',
			'layers'          => isset($payload['layers']) && is_array($payload['layers']) ? $payload['layers'] : [ 'front'=>[], 'back'=>[] ],
			'lotteryId'       => isset($payload['lotteryId']) ? (int) $payload['lotteryId'] : 0,
		];

		// marqueur unique pour que WC différencie les items
		$data['__winshirt_uid'] = uniqid( 'ws_', true );

		$cart_item_data['winshirt'] = $data;
		return $cart_item_data;
	}

	/**
	 * Affichage succinct dans le panier / checkout (visuel + mention)
	 */
	public static function display_item_data( $item_data, $cart_item ) {
		if ( empty( $cart_item['winshirt'] ) ) return $item_data;
		$ws = $cart_item['winshirt'];

		if ( ! empty( $ws['frontPreviewUrl'] ) ) {
			$item_data[] = [
				'key'   => __( 'Personnalisation (Recto)', 'winshirt' ),
				'value' => '<img src="'.esc_url( $ws['frontPreviewUrl'] ).'" style="max-width:120px;height:auto;border:1px solid #ddd;border-radius:4px;" />',
				'display' => '',
			];
		}
		if ( ! empty( $ws['backPreviewUrl'] ) ) {
			$item_data[] = [
				'key'   => __( 'Personnalisation (Verso)', 'winshirt' ),
				'value' => '<img src="'.esc_url( $ws['backPreviewUrl'] ).'" style="max-width:120px;height:auto;border:1px solid #ddd;border-radius:4px;" />',
				'display' => '',
			];
		}

		if ( ! empty( $ws['lotteryId'] ) ) {
			$item_data[] = [
				'key'   => __( 'Loterie', 'winshirt' ),
				'value' => '#'.(int)$ws['lotteryId'],
			];
		}

		return $item_data;
	}

	/**
	 * Copie les metas dans l'order item
	 */
	public static function add_order_item_meta( $item, $cart_item_key, $values, $order ) {
		if ( empty( $values['winshirt'] ) ) return;
		$ws = $values['winshirt'];

		if ( ! empty( $ws['frontPreviewUrl'] ) ) {
			$item->add_meta_data( '_winshirt_front_preview', esc_url_raw( $ws['frontPreviewUrl'] ), true );
		}
		if ( ! empty( $ws['backPreviewUrl'] ) ) {
			$item->add_meta_data( '_winshirt_back_preview', esc_url_raw( $ws['backPreviewUrl'] ), true );
		}

		// JSON calques
		if ( ! empty( $ws['layers'] ) && is_array( $ws['layers'] ) ) {
			$item->add_meta_data( '_winshirt_layers', wp_json_encode( $ws['layers'] ), true );
		}

		// Loterie
		if ( ! empty( $ws['lotteryId'] ) ) {
			$item->add_meta_data( '_winshirt_lottery_id', (int) $ws['lotteryId'], true );
		}
	}

	/**
	 * Rendu visuel dans l'admin (page commande)
	 */
	public static function admin_render_previews( $item_id, $item, $product ) {
		if ( ! is_admin() ) return;

		$front = wc_get_order_item_meta( $item_id, '_winshirt_front_preview', true );
		$back  = wc_get_order_item_meta( $item_id, '_winshirt_back_preview', true );

		if ( ! $front && ! $back ) return;

		echo '<div class="winshirt-order-previews" style="margin:6px 0;">';
		if ( $front ) {
			echo '<div style="display:inline-block;margin-right:8px;text-align:center;">';
			echo '<div style="font-size:11px;color:#666;margin-bottom:2px;">Recto</div>';
			echo '<img src="'.esc_url( $front ).'" style="max-width:120px;height:auto;border:1px solid #ddd;border-radius:4px;background:#fff;" />';
			echo '</div>';
		}
		if ( $back ) {
			echo '<div style="display:inline-block;text-align:center;">';
			echo '<div style="font-size:11px;color:#666;margin-bottom:2px;">Verso</div>';
			echo '<img src="'.esc_url( $back ).'" style="max-width:120px;height:auto;border:1px solid #ddd;border-radius:4px;background:#fff;" />';
			echo '</div>';
		}
		echo '</div>';
	}
}

WinShirt_Order::init();
}
