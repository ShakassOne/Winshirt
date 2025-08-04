<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WinShirt_Mockups {

    public function __construct() {
        add_action( 'init',              [ $this, 'register_cpt' ] );
        add_action( 'add_meta_boxes',     [ $this, 'register_meta_boxes' ] );
        add_action( 'save_post_ws-mockup', [ $this, 'save_meta' ], 10, 2 );
    }

    /**
     * Enregistre le Custom Post Type ws-mockup
     */
    public function register_cpt() {
        $labels = [
            'name'               => __( 'Mockups', 'winshirt' ),
            'singular_name'      => __( 'Mockup', 'winshirt' ),
            'add_new'            => __( 'Ajouter', 'winshirt' ),
            'add_new_item'       => __( 'Ajouter un mockup', 'winshirt' ),
            'edit_item'          => __( 'Modifier le mockup', 'winshirt' ),
            'new_item'           => __( 'Nouveau mockup', 'winshirt' ),
            'view_item'          => __( 'Voir le mockup', 'winshirt' ),
            'search_items'       => __( 'Rechercher des mockups', 'winshirt' ),
            'not_found'          => __( 'Aucun mockup trouvé', 'winshirt' ),
            'not_found_in_trash' => __( 'Aucun mockup dans la corbeille', 'winshirt' ),
            'menu_name'          => __( 'Mockups', 'winshirt' ),
        ];

        $args = [
            'labels'           => $labels,
            'public'           => false,
            'show_ui'          => true,
            'show_in_menu'     => 'winshirt',
            'supports'         => [ 'title' ],
            'menu_position'    => 5,
            'capability_type'  => 'ws_mockup',
            'map_meta_cap'     => true,
            'capabilities'     => [
                'create_posts' => 'edit_posts',
            ],
        ];

        register_post_type( 'ws-mockup', $args );
    }

    /**
     * Enregistre les meta boxes pour les mockups
     */
    public function register_meta_boxes() {
        add_meta_box(
            'ws_mockup_images',
            __( 'Images', 'winshirt' ),
            [ $this, 'images_box' ],
            'ws-mockup',
            'normal',
            'default'
        );
        add_meta_box(
            'ws_mockup_colors',
            __( 'Couleurs', 'winshirt' ),
            [ $this, 'colors_box' ],
            'ws-mockup',
            'normal',
            'default'
        );
        add_meta_box(
            'ws_mockup_zones',
            __( "Zones d'impression", 'winshirt' ),
            [ $this, 'zones_box' ],
            'ws-mockup',
            'normal',
            'default'
        );
    }

    /**
     * Render meta box Images
     */
    public function images_box( $post ) {
        wp_nonce_field( 'ws_mockup_save', 'ws_mockup_nonce' );
        $front = get_post_meta( $post->ID, '_ws_mockup_front', true );
        $back  = get_post_meta( $post->ID, '_ws_mockup_back', true );
        echo '<p><label>' . esc_html__( 'Image avant URL', 'winshirt' ) . '</label>';
        echo '<input type="text" class="widefat" name="ws_mockup_front" value="' . esc_attr( $front ) . '"/></p>';
        echo '<p><label>' . esc_html__( 'Image arrière URL', 'winshirt' ) . '</label>';
        echo '<input type="text" class="widefat" name="ws_mockup_back" value="' . esc_attr( $back ) . '"/></p>';
    }

    /**
     * Render meta box Colors
     */
    public function colors_box( $post ) {
        $colors = get_post_meta( $post->ID, '_ws_mockup_colors', true );
        echo '<p><label>' . esc_html__( 'Couleurs (hex, séparées par des virgules)', 'winshirt' ) . '</label>';
        echo '<input type="text" class="widefat" name="ws_mockup_colors" value="' . esc_attr( $colors ) . '"/></p>';
    }

    /**
     * Render meta box Zones d'impression
     */
    public function zones_box( $post ) {
        $zones = get_post_meta( $post->ID, '_ws_mockup_zones', true );
        if ( ! is_array( $zones ) ) {
            $zones = [];
        }
        echo '<table class="widefat" id="ws-mockup-zones-table">';
        echo '<thead><tr>' .
             '<th>' . esc_html__( 'Nom', 'winshirt' ) . '</th>' .
             '<th>' . esc_html__( 'Largeur', 'winshirt' ) . '</th>' .
             '<th>' . esc_html__( 'Hauteur', 'winshirt' ) . '</th>' .
             '<th>' . esc_html__( 'Top', 'winshirt' ) . '</th>' .
             '<th>' . esc_html__( 'Left', 'winshirt' ) . '</th>' .
             '<th>' . esc_html__( 'Prix', 'winshirt' ) . '</th>' .
             '<th></th>' .
             '</tr></thead><tbody>';
        foreach ( $zones as $index => $zone ) {
            $name   = esc_attr( $zone['name']   ?? '' );
            $width  = esc_attr( $zone['width']  ?? '' );
            $height = esc_attr( $zone['height'] ?? '' );
            $top    = esc_attr( $zone['top']    ?? '' );
            $left   = esc_attr( $zone['left']   ?? '' );
            $price  = esc_attr( $zone['price']  ?? '' );
            echo '<tr>' .
                 '<td><input type="text" name="ws_mockup_zones[' . $index . '][name]" value="' . $name . '" /></td>' .
                 '<td><input type="number" step="0.01" name="ws_mockup_zones[' . $index . '][width]" value="' . $width . '" /></td>' .
                 '<td><input type="number" step="0.01" name="ws_mockup_zones[' . $index . '][height]" value="' . $height . '" /></td>' .
                 '<td><input type="number" step="0.01" name="ws_mockup_zones[' . $index . '][top]" value="' . $top . '" /></td>' .
                 '<td><input type="number" step="0.01" name="ws_mockup_zones[' . $index . '][left]" value="' . $left . '" /></td>' .
                 '<td><input type="number" step="0.01" name="ws_mockup_zones[' . $index . '][price]" value="' . $price . '" /></td>' .
                 '<td><button class="button ws-remove-zone">' . esc_html__( 'Supprimer', 'winshirt' ) . '</button></td>' .
                 '</tr>';
        }
        echo '</tbody></table>';
        echo '<p><button class="button" id="ws-add-zone">' . esc_html__( 'Ajouter une zone', 'winshirt' ) . '</button></p>';
        ?>
        <script>
        jQuery(function($){
            $('#ws-add-zone').on('click', function(e){
                e.preventDefault();
                var rowCount = $('#ws-mockup-zones-table tbody tr').length;
                var row = '<tr>' +
                    '<td><input type="text" name="ws_mockup_zones['+rowCount+'][name]" /></td>' +
                    '<td><input type="number" step="0.01" name="ws_mockup_zones['+rowCount+'][width]" /></td>' +
                    '<td><input type="number" step="0.01" name="ws_mockup_zones['+rowCount+'][height]" /></td>' +
                    '<td><input type="number" step="0.01" name="ws_mockup_zones['+rowCount+'][top]" /></td>' +
                    '<td><input type="number" step="0.01" name="ws_mockup_zones['+rowCount+'][left]" /></td>' +
                    '<td><input type="number" step="0.01" name="ws_mockup_zones['+rowCount+'][price]" /></td>' +
                    '<td><button class="button ws-remove-zone"><?php echo esc_js( __( 'Supprimer', 'winshirt' ) ); ?></button></td>' +
                    '</tr>';
                $('#ws-mockup-zones-table tbody').append(row);
            });
            $('#ws-mockup-zones-table').on('click', '.ws-remove-zone', function(e){
                e.preventDefault();
                $(this).closest('tr').remove();
            });
        });
        </script>
        <?php
    }

    /**
     * Sauvegarde des métadonnées
     */
    public function save_meta( $post_id, $post ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! isset( $_POST['ws_mockup_nonce'] ) || ! wp_verify_nonce( $_POST['ws_mockup_nonce'], 'ws_mockup_save' ) ) {
            return;
        }
        if ( $post->post_type !== 'ws-mockup' ) {
            return;
        }
        // Images
        update_post_meta( $post_id, '_ws_mockup_front', sanitize_text_field( $_POST['ws_mockup_front'] ?? '' ) );
        update_post_meta( $post_id, '_ws_mockup_back',  sanitize_text_field( $_POST['ws_mockup_back']  ?? '' ) );
        // Couleurs
        update_post_meta( $post_id, '_ws_mockup_colors', sanitize_text_field( $_POST['ws_mockup_colors'] ?? '' ) );
        // Zones
        $zones = $_POST['ws_mockup_zones'] ?? [];
        $clean = [];
        if ( is_array( $zones ) ) {
            foreach ( $zones as $z ) {
                $clean[] = [
                    'name'   => sanitize_text_field( $z['name']   ?? '' ),
                    'width'  => floatval(        $z['width']  ?? 0 ),
                    'height' => floatval(        $z['height'] ?? 0 ),
                    'top'    => floatval(        $z['top']    ?? 0 ),
                    'left'   => floatval(        $z['left']   ?? 0 ),
                    'price'  => floatval(        $z['price']  ?? 0 ),
                ];
            }
        }
        update_post_meta( $post_id, '_ws_mockup_zones', $clean );
    }
}

// Instanciation
new WinShirt_Mockups
