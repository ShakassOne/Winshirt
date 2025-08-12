<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WinShirt_Router' ) ) {

class WinShirt_Router {

	public static function init() {
		add_action( 'wp_ajax_winshirt_modal',        [ __CLASS__, 'ajax_modal' ] );
		add_action( 'wp_ajax_nopriv_winshirt_modal', [ __CLASS__, 'ajax_modal' ] );
	}

	/**
	 * Retourne le HTML du customizer (template) pour affichage dans le modal.
	 * Accepte product_id en GET/POST, sinon essaie d’inférer.
	 */
	public static function ajax_modal() {
		// Sécurité basique : nonce REST si dispo
		if ( isset( $_REQUEST['_wpnonce'] ) && ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'wp_rest' ) ) {
			wp_send_json_error( [ 'message' => 'Invalid nonce' ], 403 );
		}

		$product_id = isset( $_REQUEST['product_id'] ) ? absint( $_REQUEST['product_id'] ) : 0;
		if ( ! $product_id && function_exists( 'is_product' ) && is_product() ) {
			global $product;
			if ( $product && method_exists( $product, 'get_id' ) ) {
				$product_id = (int) $product->get_id();
			}
		}

		// Préparer le contexte si besoin (pas obligatoire pour le template actuel)
		if ( $product_id ) {
			// Forcer global $post si nécessaire
			$GLOBALS['post'] = get_post( $product_id );
			setup_postdata( $GLOBALS['post'] );
		}

		$template = WINSHIRT_PATH . 'templates/modal-customizer.php';
		if ( ! file_exists( $template ) ) {
			wp_send_json_error( [ 'message' => 'Template not found' ], 500 );
		}

		ob_start();
		include $template;
		$html = ob_get_clean();

		if ( $product_id ) {
			wp_reset_postdata();
		}

		wp_send_json_success( [ 'html' => $html ] );
	}
}

WinShirt_Router::init();
}
