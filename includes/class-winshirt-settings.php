<?php
/**
 * WinShirt - Settings produit WooCommerce (Recovery v1.0)
 * Gère l'activation et configuration des produits personnalisables
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WinShirt_Settings' ) ) {

class WinShirt_Settings {

	public static function init() {
		// Admin product settings
		add_action( 'woocommerce_product_options_general_product_data', [ __CLASS__, 'product_fields' ] );
		add_action( 'woocommerce_admin_process_product_object', [ __CLASS__, 'save_product_fields' ] );
		
		// Frontend hooks
		add_action( 'wp', [ __CLASS__, 'maybe_hook_product_page' ] );
	}

	/**
	 * Champs produit dans l'admin WooCommerce
	 */
	public static function product_fields() {
		echo '<div class="options_group">';

		woocommerce_wp_checkbox( [
			'id'          => '_winshirt_enable',
			'label'       => __( 'Activer la personnalisation WinShirt', 'winshirt' ),
			'description' => __( 'Permet aux clients de personnaliser ce produit', 'winshirt' ),
		] );

		// Liste des mockups disponibles
		$mockups = get_posts( [
			'post_type'      => 'ws-mockup',
			'numberposts'    => -1,
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC'
		] );

		$mockup_options = [ '' => __( '— Sélectionner un mockup —', 'winshirt' ) ];
		foreach ( $mockups as $mockup ) {
			$mockup_options[ $mockup->ID ] = $mockup->post_title . ' (#' . $mockup->ID . ')';
		}

		woocommerce_wp_select( [
			'id'          => '_winshirt_mockup_id',
			'label'       => __( 'Mockup à utiliser', 'winshirt' ),
			'options'     => $mockup_options,
			'description' => __( 'Choisissez le mockup (recto/verso + zones) pour ce produit', 'winshirt' ),
			'desc_tip'    => true,
		] );

		echo '</div>';
	}

	/**
	 * Sauvegarde des champs produit
	 */
	public static function save_product_fields( $product ) {
		$enable = isset( $_POST['_winshirt_enable'] ) ? 'yes' : 'no';
		$mockup_id = isset( $_POST['_winshirt_mockup_id'] ) ? absint( $_POST['_winshirt_mockup_id'] ) : 0;

		$product->update_meta_data( '_winshirt_enable', $enable );
		$product->update_meta_data( '_winshirt_mockup_id', $mockup_id );
	}

	/**
	 * Hook frontend si produit personnalisable
	 */
	public static function maybe_hook_product_page() {
		if ( ! function_exists('is_product') || ! is_product() ) return;

		$product = wc_get_product( get_the_ID() );
		if ( ! $product ) return;

		$enabled   = $product->get_meta( '_winshirt_enable' ) === 'yes';
		$mockup_id = (int) $product->get_meta( '_winshirt_mockup_id' );

		if ( ! $enabled || ! $mockup_id ) return;

		// Bouton Personnaliser
		add_action( 'woocommerce_single_product_summary', [ __CLASS__, 'render_customize_button' ], 25 );

		// Modal en footer
		add_action( 'wp_footer', [ __CLASS__, 'render_modal_template' ] );

		// Force les assets
		add_filter( 'winshirt_force_enqueue', '__return_true' );

		// Push product data
		add_filter( 'winshirt_front_data', function( $data ) use ( $product ) {
			if ( ! is_array( $data ) ) $data = [];
			$data['product'] = [
				'id'    => (int) $product->get_id(),
				'title' => $product->get_name(),
			];
			return $data;
		} );
	}

	/**
	 * Render bouton personnaliser
	 */
	public static function render_customize_button() {
		?>
		<div class="winshirt-customize-section">
			<button type="button" class="winshirt-customize-btn" data-winshirt-open>
				<?php esc_html_e( 'Personnaliser ce produit', 'winshirt' ); ?>
			</button>
		</div>
		<?php
	}

	/**
	 * Render modal template
	 */
	public static function render_modal_template() {
		$template_path = WINSHIRT_PATH . 'templates/modal-customizer.php';
		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
	}
}

WinShirt_Settings::init();
}
