<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WinShirt_Product_Customization {

    public static function init() {
        add_filter( 'winshirt_front_data', [ __CLASS__, 'inject_mockup_and_zones' ] );
    }

    /**
     * Cherche l'ID du mockup associé au produit courant.
     * On supporte plusieurs clés possibles pour être compatible avec tes anciens réglages.
     */
    private static function get_product_mockup_id( $product_id ) {
        $keys = [ '_ws_mockup_id', 'ws_mockup_id', '_winshirt_mockup_id' ];
        foreach ( $keys as $k ) {
            $v = get_post_meta( $product_id, $k, true );
            if ( $v ) return absint( $v );
        }
        return 0;
    }

    public static function inject_mockup_and_zones( $data ) {

        // Détection produit (mêmes règles que ta classe assets)
        $pid = 0;
        if ( isset($_GET['product_id']) ) {
            $pid = absint($_GET['product_id']);
        } elseif ( function_exists('is_product') && is_product() ) {
            $pid = get_the_ID();
        }
        if ( ! $pid ) return $data;

        // Récupération mockup lié
        $mockup_id = self::get_product_mockup_id( $pid );
        if ( ! $mockup_id ) {
            // fallback : dernier mockup publié si rien n'est configuré
            $last = get_posts([
                'post_type' => 'ws-mockup',
                'numberposts' => 1,
                'post_status' => 'publish',
                'orderby' => 'ID',
                'order' => 'DESC',
                'fields' => 'ids',
            ]);
            $mockup_id = $last ? (int) $last[0] : 0;
        }
        if ( ! $mockup_id ) return $data;

        $front = get_post_meta( $mockup_id, '_ws_front', true );
        $back  = get_post_meta( $mockup_id, '_ws_back',  true );
        $zones_json = get_post_meta( $mockup_id, '_ws_zones', true );
        $zones_arr  = [];
        if ( $zones_json ) {
            $decoded = json_decode( $zones_json, true );
            if ( is_array($decoded) ) {
                // Schéma attendu par le front : tableau d’objets {side,left,top,width,height}
                foreach ([ 'front','back' ] as $side ) {
                    if ( empty($decoded[$side]) || !is_array($decoded[$side]) ) continue;
                    foreach ( $decoded[$side] as $z ) {
                        $zones_arr[] = [
                            'side'   => $side,
                            'left'   => isset($z['left'])   ? (float)$z['left']   : (isset($z['xPct']) ? (float)$z['xPct'] : 0 ),
                            'top'    => isset($z['top'])    ? (float)$z['top']    : (isset($z['yPct']) ? (float)$z['yPct'] : 0 ),
                            'width'  => isset($z['width'])  ? (float)$z['width']  : (isset($z['wPct']) ? (float)$z['wPct'] : 0 ),
                            'height' => isset($z['height']) ? (float)$z['height'] : (isset($z['hPct']) ? (float)$z['hPct'] : 0 ),
                        ];
                    }
                }
            }
        }

        // Injecte dans WinShirtData
        $data['mockups'] = [
            [
                'id'    => $mockup_id,
                'front' => esc_url_raw( $front ),
                'back'  => esc_url_raw( $back  ),
            ]
        ];
        $data['zones'] = $zones_arr;

        return $data;
    }
}
WinShirt_Product_Customization::init();
