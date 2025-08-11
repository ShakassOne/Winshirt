<?php
/**
 * WinShirt - Réglages produit Woo + bouton & modal sur la page produit
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WinShirt_Settings' ) ) {

class WinShirt_Settings {

	public static function init() {
		// Métas produit (admin)
		add_action( 'woocommerce_product_options_general_product_data', [ __CLASS__, 'product_fields' ] );
		add_action( 'woocommerce_admin_process_product_object',         [ __CLASS__, 'save_product_fields' ] );

		// Front (page produit)
		add_action( 'wp', [ __CLASS__, 'maybe_hook_product_page' ] );
	}

	/** Page options (placeholder pour le menu admin) */
	public static function render_settings_page() {
		echo '<div class="wrap"><h1>Paramètres WinShirt</h1><p>'.esc_html__('Rien à configurer ici pour l’instant.','winshirt').'</p></div>';
	}

	// ---------- Admin produit ----------

	public static function product_fields() {
		echo '<div class="options_group">';

		woocommerce_wp_checkbox( [
			'id'          => '_winshirt_enable',
			'label'       => __( 'Activer la personnalisation WinShirt', 'winshirt' ),
			'description' => __( 'Affiche le bouton “Personnaliser” sur la page produit.', 'winshirt' ),
		] );

		// Sélecteur du CPT mockup (slug : ws-mockup)
		$choices = [];
		$posts = get_posts([
			'post_type'      => 'ws-mockup',
			'post_status'    => 'publish',
			'posts_per_page' => 200,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'fields'         => 'ids',
		]);
		foreach ($posts as $pid) $choices[$pid] = get_the_title($pid) . ' (#'.$pid.')';

		woocommerce_wp_select( [
			'id'          => '_winshirt_mockup_id',
			'label'       => __( 'Mockup à utiliser', 'winshirt' ),
			'options'     => [ '' => __( '— Sélectionner —', 'winshirt' ) ] + $choices,
			'description' => __( 'Choisissez le mockup (recto/verso + zones) défini dans le CPT “Mockup”.', 'winshirt' ),
			'desc_tip'    => true,
		] );

		echo '</div>';
	}

	public static function save_product_fields( $product ) {
		$enable = isset( $_POST['_winshirt_enable'] ) ? 'yes' : 'no';
		$product->update_meta_data( '_winshirt_enable', $enable );

		$mockup_id = isset( $_POST['_winshirt_mockup_id'] ) ? absint( $_POST['_winshirt_mockup_id'] ) : 0;
		$product->update_meta_data( '_winshirt_mockup_id', $mockup_id );
	}

	// ---------- Front ----------

	public static function maybe_hook_product_page() {
		if ( ! function_exists('is_product') || ! is_product() ) return;

		$product = wc_get_product( get_the_ID() );
		if ( ! $product ) return;

		$enabled   = $product->get_meta( '_winshirt_enable' ) === 'yes';
		$mockup_id = (int) $product->get_meta( '_winshirt_mockup_id' );

		if ( ! $enabled || ! $mockup_id ) return;

		// Bouton Personnaliser
		add_action( 'woocommerce_single_product_summary', [ __CLASS__, 'render_customize_button' ], 35 );

		// Modal en footer
		add_action( 'wp_footer', [ __CLASS__, 'render_modal_template' ] );

		// Force les assets
		add_filter( 'winshirt_force_enqueue', '__return_true' );

		// Pousse product_id dans WinShirtData
		add_filter( 'winshirt_front_data', function( $data ) use ( $product ) {
			if ( ! is_array( $data ) ) $data = [];
			$data['product'] = [
				'id'    => (int) $product->get_id(),
				'title' => $product->get_name(),
			];
			return $data;
		} );
	}

	public static function render_customize_button() {
		echo '<p><button type="button" class="single_add_to_cart_button button alt" data-ws-open-customizer>'
		   . esc_html__( 'Personnaliser', 'winshirt' )
		   . '</button></p>';
	}

	public static function render_modal_template() {
		$path = WINSHIRT_PATH . 'templates/modal-customizer.php';
		if ( file_exists( $path ) ) include $path;
	}
}

WinShirt_Settings::init();
}
