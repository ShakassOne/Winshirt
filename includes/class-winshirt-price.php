<?php
/**
 * WinShirt - Prix (serveur)
 *
 * - Injecte la config de pricing dans WinShirtData (front)
 * - Helper de calcul miroir du client (surface → palier → prix)
 * - Filtres pour surcharger la grille
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WinShirt_Price' ) ) {

class WinShirt_Price {

	/**
	 * Config par défaut (modifiable via filtre 'winshirt_pricing_config')
	 */
	public static function default_config() {
		$cfg = [
			'base'        => 0.0,
			'perSideBase' => 0.0,
			'tiers'       => [
				[ 'maxPct' => 5,   'label' => 'A7', 'price' => 3.5 ],
				[ 'maxPct' => 12,  'label' => 'A6', 'price' => 6.0 ],
				[ 'maxPct' => 25,  'label' => 'A5', 'price' => 9.0 ],
				[ 'maxPct' => 45,  'label' => 'A4', 'price' => 12.0 ],
				[ 'maxPct' => 75,  'label' => 'A3', 'price' => 16.0 ],
				[ 'maxPct' => 100, 'label' => 'MAX','price' => 20.0 ],
			],
		];
		/**
		 * Permet de modifier la config globale de pricing.
		 * @param array $cfg
		 */
		return apply_filters( 'winshirt_pricing_config', $cfg );
	}

	public static function init() {
		// Injecte la config pricing pour le front
		add_filter( 'winshirt_front_data', [ __CLASS__, 'inject_front_config' ] );

		// (Optionnel) : hooks prêts si un jour on veut ajouter des fees dynamiques Woo
		// add_action( 'woocommerce_cart_calculate_fees', [ __CLASS__, 'maybe_add_cart_fee' ] );
	}

	/**
	 * Ajoute WinShirtData.config.pricing si absent
	 */
	public static function inject_front_config( $data ) {
		if ( ! is_array( $data ) ) $data = [];
		if ( empty( $data['config'] ) || ! is_array( $data['config'] ) ) {
			$data['config'] = [];
		}
		if ( empty( $data['config']['pricing'] ) ) {
			$data['config']['pricing'] = self::default_config();
		}
		return $data;
	}

	/**
	 * Helper principal (miroir du JS)
	 * @param array $zone   ex: ['width'=>1200,'height'=>1600]
	 * @param array $layers ex: ['front'=>[['width'=>..,'height'=>..],...], 'back'=>[...]]
	 * @param array $config ex: self::default_config() (facultatif)
	 * @return array detail pricing
	 */
	public static function compute( $zone, $layers, $config = null ) {
		$cfg = $config ?: self::default_config();

		$zone_w = max( 1, (int) ( $zone['width']  ?? 0 ) );
		$zone_h = max( 1, (int) ( $zone['height'] ?? 0 ) );
		$zone_area = $zone_w * $zone_h;

		$front_pct = self::percent_for_side( $layers['front'] ?? [], $zone_area );
		$back_pct  = self::percent_for_side(  $layers['back'] ?? [], $zone_area );

		$front_info = self::tier_for_percent( $front_pct, $cfg['tiers'] );
		$back_info  = self::tier_for_percent(  $back_pct,  $cfg['tiers'] );

		$base        = (float) ( $cfg['base']        ?? 0 );
		$perSideBase = (float) ( $cfg['perSideBase'] ?? 0 );

		$total = $base
			+ ( $front_pct > 0 ? ( $perSideBase + (float) $front_info['price'] ) : 0 )
			+ ( $back_pct  > 0 ? ( $perSideBase + (float) $back_info['price'] )  : 0 );

		return [
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
			'configUsed' => $cfg,
		];
	}

	// ================= Helpers internes =================

	private static function percent_for_side( $layers, $zone_area ) {
		$sum = 0.0;
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
		$pct = ( $sum / $zone_area ) * 100.0;
		if ( $pct < 0 ) $pct = 0;
		if ( $pct > 100 ) $pct = 100;
		return $pct;
	}

	private static function tier_for_percent( $pct, $tiers ) {
		$pct = (float) $pct;
		if ( ! is_array( $tiers ) || ! count( $tiers ) ) {
			$tiers = self::default_config()['tiers'];
		}
		foreach ( $tiers as $t ) {
			$max = (float) ( $t['maxPct'] ?? 100 );
			if ( $pct <= $max ) return $t;
		}
		return end( $tiers );
	}

	// ================= (Optionnel) Fees Woo =================
	// Exemples si un jour on veut ajouter un supplément direct dans le panier.
	// public static function maybe_add_cart_fee( WC_Cart $cart ) {
	// 	if ( is_admin() && ! defined('DOING_AJAX') ) return;
	// 	// Lire un marqueur global si présent dans la session, etc.
	// 	// $cart->add_fee( __('Personnalisation','winshirt'), 5.00, true, '' );
	// }
}

WinShirt_Price::init();
}
