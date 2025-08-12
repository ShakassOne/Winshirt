<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WinShirt_Mockups {

    const PT      = 'ws-mockup';
    const META_F  = '_ws_front';
    const META_B  = '_ws_back';
    const META_Z  = '_ws_zones';
    const META_COL= '_ws_colors_csv';

    public static function init() {
        add_action( 'init', [ __CLASS__, 'register_cpt' ] );
        add_action( 'add_meta_boxes', [ __CLASS__, 'add_metaboxes' ] );
        add_action( 'save_post_' . self::PT, [ __CLASS__, 'save' ] );

        // Admin assets uniquement sur l’édition de mockups
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'admin_assets' ] );
    }

    public static function register_cpt() {
        register_post_type( self::PT, [
            'label'  => __( 'Mockups', 'winshirt' ),
            'labels' => [
                'name' => __( 'Mockups', 'winshirt' ),
                'singular_name' => __( 'Mockup', 'winshirt' ),
                'add_new' => __( 'Ajouter', 'winshirt' ),
                'add_new_item' => __( 'Ajouter un mockup', 'winshirt' ),
                'edit_item' => __( 'Modifier le mockup', 'winshirt' ),
                'new_item' => __( 'Nouveau mockup', 'winshirt' ),
                'view_item' => __( 'Voir', 'winshirt' ),
                'search_items' => __( 'Rechercher', 'winshirt' ),
                'not_found' => __( 'Aucun mockup', 'winshirt' ),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,          // on gère via menu WinShirt
            'supports' => [ 'title' ],
            'capability_type' => 'post',
        ] );
    }

    public static function add_metaboxes() {
        add_meta_box(
            'ws_mockup_images',
            __( 'Images du mockup', 'winshirt' ),
            [ __CLASS__, 'box_images' ],
            self::PT, 'normal', 'high'
        );

        add_meta_box(
            'ws_mockup_colors',
            __( 'Couleurs disponibles (optionnel)', 'winshirt' ),
            [ __CLASS__, 'box_colors' ],
            self::PT, 'normal', 'default'
        );

        add_meta_box(
            'ws_mockup_zones',
            __( 'Zones d’impression', 'winshirt' ),
            [ __CLASS__, 'box_zones' ],
            self::PT, 'normal', 'high'
        );
    }

    public static function box_images( $post ) {
        $front = get_post_meta( $post->ID, self::META_F, true );
        $back  = get_post_meta( $post->ID, self::META_B, true );
        ?>
        <p><label for="ws_front"><?php _e('Image avant (recto) URL', 'winshirt'); ?></label>
            <input type="text" id="ws_front" name="ws_front" class="widefat" value="<?php echo esc_attr( $front ); ?>">
        </p>
        <p><label for="ws_back"><?php _e('Image arrière (verso) URL', 'winshirt'); ?></label>
            <input type="text" id="ws_back" name="ws_back" class="widefat" value="<?php echo esc_attr( $back ); ?>">
        </p>
        <p class="description"><?php _e('Tu peux coller directement les URLs de la médiathèque.', 'winshirt'); ?></p>
        <?php
    }

    public static function box_colors( $post ) {
        $csv = get_post_meta( $post->ID, self::META_COL, true );
        ?>
        <p><label for="ws_colors_csv"><?php _e('Couleurs (HEX ou noms CSS), séparées par des virgules', 'winshirt'); ?></label>
            <input type="text" id="ws_colors_csv" name="ws_colors_csv" class="widefat" value="<?php echo esc_attr( $csv ); ?>" placeholder="#000000,#FFFFFF,red,blue">
        </p>
        <?php
    }

    public static function box_zones( $post ) {
        $front = get_post_meta( $post->ID, self::META_F, true );
        $back  = get_post_meta( $post->ID, self::META_B, true );
        $zones = get_post_meta( $post->ID, self::META_Z, true );
        if ( empty( $zones ) ) $zones = '{}';
        ?>
        <div class="ws-zone-editor" data-front="<?php echo esc_url( $front ); ?>" data-back="<?php echo esc_url( $back ); ?>">
            <div class="ws-ze-toolbar" style="margin:8px 0;display:flex;gap:8px;align-items:center;">
                <button type="button" class="button button-secondary ws-ze-side" data-side="front"><?php _e('Recto','winshirt'); ?></button>
                <button type="button" class="button button-secondary ws-ze-side" data-side="back"><?php _e('Verso','winshirt'); ?></button>
                <span style="flex:1"></span>
                <button type="button" class="button ws-ze-add"><?php _e('Ajouter une zone','winshirt'); ?></button>
                <button type="button" class="button ws-ze-clear"><?php _e('Tout effacer','winshirt'); ?></button>
            </div>

            <div id="ws-ze-canvas" style="position:relative;width:100%;max-width:900px;aspect-ratio:3/4;background:#f8f8f8;border:1px solid #e5e7eb;overflow:hidden;">
                <img id="ws-ze-img" alt="" style="position:absolute;inset:0;margin:auto;max-width:100%;max-height:100%;object-fit:contain;pointer-events:none;">
            </div>

            <input type="hidden" id="ws-ze-data" name="ws_zones" value="<?php echo esc_attr( $zones ); ?>">
            <p class="description"><?php _e('Les positions sont enregistrées en % du canvas.', 'winshirt'); ?></p>
        </div>
        <?php
    }

    public static function save( $post_id ) {
        if ( isset($_POST['ws_front']) ) {
            update_post_meta( $post_id, self::META_F, esc_url_raw( $_POST['ws_front'] ) );
        }
        if ( isset($_POST['ws_back']) ) {
            update_post_meta( $post_id, self::META_B, esc_url_raw( $_POST['ws_back'] ) );
        }
        if ( isset($_POST['ws_colors_csv']) ) {
            update_post_meta( $post_id, self::META_COL, sanitize_text_field( $_POST['ws_colors_csv'] ) );
        }
        if ( isset($_POST['ws_zones']) ) {
            // on stocke tel quel (JSON déjà en %)
            update_post_meta( $post_id, self::META_Z, wp_kses_post( wp_unslash( $_POST['ws_zones'] ) ) );
        }
    }

    public static function admin_assets( $hook ) {
        global $post_type;
        if ( $post_type !== self::PT ) return;

        // CSS du rectangle + handle
        wp_enqueue_style( 'winshirt-admin-zones', plugins_url( 'assets/css/admin-zones.css', dirname(__FILE__) ), [], WINSHIRT_VERSION ?? '1.0.0' );

        // JS éditeur zones (fichier ENTIER que tu as collé)
        wp_enqueue_script( 'winshirt-admin-zones', plugins_url( 'assets/js/admin-zones.js', dirname(__FILE__) ), [], WINSHIRT_VERSION ?? '1.0.0', true );
    }
}
WinShirt_Mockups::init();
