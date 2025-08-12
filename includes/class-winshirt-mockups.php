<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WinShirt_Mockups' ) ) {

class WinShirt_Mockups {

    public static function init() {
        add_action( 'init', [ __CLASS__, 'register_cpt' ] );
        add_action( 'add_meta_boxes', [ __CLASS__, 'add_metaboxes' ] );
        add_action( 'save_post_ws-mockup', [ __CLASS__, 'save_meta' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'admin_assets' ] );
    }

    public static function register_cpt() {
        if ( post_type_exists( 'ws-mockup' ) ) return;
        register_post_type( 'ws-mockup', [
            'label' => __( 'Mockups', 'winshirt' ),
            'labels'=> [ 'singular_name'=>__( 'Mockup', 'winshirt' ) ],
            'public'=> false, 'show_ui'=> true, 'menu_position'=> 25, 'menu_icon'=>'dashicons-format-image',
            'supports'=> ['title'],
        ]);
    }

    public static function add_metaboxes() {
        add_meta_box( 'ws_mockup_images', __( 'Images du mockup', 'winshirt' ), [ __CLASS__, 'render_images_box' ], 'ws-mockup', 'normal', 'high' );
        add_meta_box( 'ws_mockup_zones',  __( 'Zones d\'impression', 'winshirt' ), [ __CLASS__, 'render_zones_box'  ], 'ws-mockup', 'normal', 'high' );
    }

    public static function render_images_box( $post ) {
        $front = get_post_meta( $post->ID, '_ws_mockup_front', true );
        $back  = get_post_meta( $post->ID, '_ws_mockup_back',  true );
        ?>
        <p><label><?php _e('Image avant (recto)', 'winshirt'); ?></label>
            <input type="text" class="widefat" name="ws_mockup_front" value="<?php echo esc_attr($front); ?>">
        </p>
        <p><label><?php _e('Image arrière (verso)', 'winshirt'); ?></label>
            <input type="text" class="widefat" name="ws_mockup_back" value="<?php echo esc_attr($back); ?>">
        </p>
        <p class="description"><?php _e('Collez l’URL de l’image (ou utilisez la médiathèque et copiez l’URL).', 'winshirt'); ?></p>
        <?php
    }

    public static function render_zones_box( $post ) {
        $front = get_post_meta( $post->ID, '_ws_mockup_front', true );
        $back  = get_post_meta( $post->ID, '_ws_mockup_back',  true );
        $zones = get_post_meta( $post->ID, '_ws_mockup_zones', true );
        if ( ! is_array( $zones ) ) $zones = [ 'front'=>[], 'back'=>[] ];
        ?>
        <style>
            .ws-zone-editor{ border:1px solid #e5e7eb; padding:10px; background:#fff }
            .ws-ze-toolbar{ display:flex; gap:8px; margin-bottom:8px }
            .ws-ze-canvas{ position:relative; width:620px; max-width:100%; margin:0 auto; background:#f9fafb; border:1px solid #e5e7eb }
            .ws-ze-canvas img{ width:100%; height:auto; display:block; }
            .ws-ze-rect{ position:absolute; border:1px dashed #2b6cb0; background:rgba(59,130,246,0.15); }
            .ws-ze-rect .ws-h{ position:absolute; width:12px; height:12px; background:#2b6cb0; border-radius:6px; right:-6px; bottom:-6px; cursor:nwse-resize; }
        </style>

        <div class="ws-zone-editor" data-front="<?php echo esc_attr( $front ); ?>" data-back="<?php echo esc_attr( $back ); ?>">
            <div class="ws-ze-toolbar">
                <button type="button" class="button ws-ze-side" data-side="front"><?php _e('Recto','winshirt'); ?></button>
                <button type="button" class="button ws-ze-side" data-side="back"><?php _e('Verso','winshirt'); ?></button>
                <button type="button" class="button ws-ze-add"><?php _e('Ajouter une zone','winshirt'); ?></button>
                <button type="button" class="button ws-ze-clear"><?php _e('Effacer les zones','winshirt'); ?></button>
            </div>
            <div class="ws-ze-canvas" id="ws-ze-canvas" style="aspect-ratio: 620/700;">
                <img id="ws-ze-img" src="<?php echo esc_url( $front ); ?>" alt="">
                <!-- zones injectées par JS -->
            </div>
            <input type="hidden" id="ws-ze-data" name="ws_mockup_zones" value="<?php echo esc_attr( wp_json_encode( $zones ) ); ?>">
        </div>
        <?php
    }

    public static function save_meta( $post_id ) {
        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
        if ( isset($_POST['ws_mockup_front']) ) {
            update_post_meta( $post_id, '_ws_mockup_front', esc_url_raw( $_POST['ws_mockup_front'] ) );
        }
        if ( isset($_POST['ws_mockup_back']) ) {
            update_post_meta( $post_id, '_ws_mockup_back', esc_url_raw( $_POST['ws_mockup_back'] ) );
        }
        if ( isset($_POST['ws_mockup_zones']) ) {
            $json = wp_unslash( $_POST['ws_mockup_zones'] );
            $arr = json_decode( $json, true );
            if ( is_array( $arr ) ) {
                update_post_meta( $post_id, '_ws_mockup_zones', $arr );
            }
        }
    }

    public static function admin_assets( $hook ) {
        $screen = get_current_screen();
        if ( $screen && $screen->post_type === 'ws-mockup' ) {
            // admin-zones.js
            $base = plugins_url( '', dirname(__FILE__) . '/../winshirt.php' ) . '/';
            wp_enqueue_script( 'winshirt-admin-zones', $base.'assets/js/admin-zones.js', [], '1.0.0', true );
        }
    }
}

WinShirt_Mockups::init();
}
