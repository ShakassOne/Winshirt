<?php
/**
 * WinShirt - Réglages produit Woo + bouton & modal sur la page produit
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WinShirt_Settings' ) ) {

class WinShirt_Settings {

	// Slug du CPT mockup (adaptable via filtre)
	public static function mockup_cpt() {
		$slug = 'mockup';
		return apply_filters( 'winshirt_mockup_cpt', $slug );
	}

	public static function init() {
		// Métas produit
		add_action( 'woocommerce_product_options_general_product_data', [ __CLASS__, 'product_fields' ] );
		add_action( 'woocommerce_admin_process_product_object',         [ __CLASS__, 'save_product_fields' ] );

		// Bouton + modal + assets sur page produit (si activé)
		add_action( 'wp', [ __CLASS__, 'maybe_hook_product_page' ] );
	}

	// ----------- Admin produit -----------

	public static function product_fields() {
		echo '<div class="options_group">';

		woocommerce_wp_checkbox( [
			'id'          => '_winshirt_enable',
			'label'       => __( 'Activer la personnalisation WinShirt', 'winshirt' ),
			'description' => __( 'Affiche le bouton “Personnaliser” sur la page produit.', 'winshirt' ),
		] );

		// Sélecteur Mockup
		$mockups = self::get_mockup_choices();
		woocommerce_wp_select( [
			'id'          => '_winshirt_mockup_id',
			'label'       => __( 'Mockup à utiliser', 'winshirt' ),
			'options'     => [ '' => __( '— Sélectionner —', 'winshirt' ) ] + $mockups,
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

	private static function get_mockup_choices() {
		$choices = [];
		$posts = get_posts( [
			'post_type'      => self::mockup_cpt(),
			'post_status'    => 'publish',
			'posts_per_page' => 200,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'fields'         => 'ids',
		] );
		foreach ( $posts as $pid ) {
			$choices[ $pid ] = get_the_title( $pid ) . ' (#' . $pid . ')';
		}
		return $choices;
	}

	// ----------- Front (page produit) -----------

	public static function maybe_hook_product_page() {
		if ( ! function_exists('is_product') || ! is_product() ) return;

		$product = wc_get_product( get_the_ID() );
		if ( ! $product ) return;

		$enabled   = $product->get_meta( '_winshirt_enable' ) === 'yes';
		$mockup_id = (int) $product->get_meta( '_winshirt_mockup_id' );

		if ( ! $enabled || ! $mockup_id ) return;

		// 1) Bouton “Personnaliser” sous le bouton Ajouter au panier
		add_action( 'woocommerce_single_product_summary', [ __CLASS__, 'render_customize_button' ], 35 );

		// 2) Injecte le template modal en footer
		add_action( 'wp_footer', [ __CLASS__, 'render_modal_template' ] );

		// 3) Force l’enqueue des assets front
		add_filter( 'winshirt_force_enqueue', '__return_true' );

		// 4) Pousse product_id pour WinShirtData
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
		// Charge le template fourni par le plugin
		if ( function_exists( 'winshirt_require_if_exists' ) ) {
			$path = WINSHIRT_PATH . 'templates/modal-customizer.php';
			if ( file_exists( $path ) ) {
				// Passe des args vides : les images/zones seront injectées par WinShirt_Product_Customization
				include $path;
			}
		}
	}
}

WinShirt_Settings::init();
}
