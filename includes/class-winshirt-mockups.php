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
            // L'édition des mockups est liée à un sous-menu personnalisé
            // de WinShirt. On désactive donc l'ajout automatique dans le
            // menu pour éviter les doublons.
            'show_in_menu'     => false,
            'supports'         => [ 'title' ],
            'menu_position'    => 5,
            // Utilise les capacités standards des articles pour que les
            // administrateurs puissent accéder aux pages sans droits
            // supplémentaires.
            'capability_type'  => 'post',
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
        wp_enqueue_media();
        $front = get_post_meta( $post->ID, '_ws_mockup_front', true );
        $back  = get_post_meta( $post->ID, '_ws_mockup_back', true );
        echo '<p><label>' . esc_html__( 'Image avant', 'winshirt' ) . '</label><br />';
        echo '<input type="text" class="widefat" id="ws_mockup_front" name="ws_mockup_front" value="' . esc_attr( $front ) . '"/>';
        echo '<button class="button ws-upload-image" data-target="#ws_mockup_front">' . esc_html__( 'Téléverser', 'winshirt' ) . '</button></p>';
        echo '<p><label>' . esc_html__( 'Image arrière', 'winshirt' ) . '</label><br />';
        echo '<input type="text" class="widefat" id="ws_mockup_back" name="ws_mockup_back" value="' . esc_attr( $back ) . '"/>';
        echo '<button class="button ws-upload-image" data-target="#ws_mockup_back">' . esc_html__( 'Téléverser', 'winshirt' ) . '</button></p>';
        ?>
        <script>
        jQuery(function($){
            $('.ws-upload-image').on('click', function(e){
                e.preventDefault();
                var target = $($(this).data('target'));
                var frame = wp.media({
                    title: '<?php echo esc_js( __( 'Sélectionner une image', 'winshirt' ) ); ?>',
                    button: { text: '<?php echo esc_js( __( 'Utiliser cette image', 'winshirt' ) ); ?>' },
                    multiple: false
                });
                frame.on('select', function(){
                    var attachment = frame.state().get('selection').first().toJSON();
                    target.val(attachment.url);
                });
                frame.open();
            });
        });
        </script>
        <?php
    }

    /**
     * Render meta box Colors
     */
    public function colors_box( $post ) {
        wp_enqueue_media();
        $colors       = get_post_meta( $post->ID, '_ws_mockup_colors', true );
        $color_images = get_post_meta( $post->ID, '_ws_mockup_color_images', true );
        echo '<p><label>' . esc_html__( 'Couleurs (hex, séparées par des virgules)', 'winshirt' ) . '</label>';
        echo '<input type="text" class="widefat" name="ws_mockup_colors" value="' . esc_attr( $colors ) . '"/></p>';
        echo '<p><label>' . esc_html__( 'Visuels de couleurs', 'winshirt' ) . '</label>';
        echo '<input type="text" class="widefat" id="ws_mockup_color_images" name="ws_mockup_color_images" value="' . esc_attr( $color_images ) . '"/>';
        echo '<button class="button ws-upload-colors" data-target="#ws_mockup_color_images">' . esc_html__( 'Téléverser', 'winshirt' ) . '</button></p>';
        ?>
        <script>
        jQuery(function($){
            $('.ws-upload-colors').on('click', function(e){
                e.preventDefault();
                var target = $($(this).data('target'));
                var frame = wp.media({
                    title: '<?php echo esc_js( __( 'Sélectionner des images', 'winshirt' ) ); ?>',
                    button: { text: '<?php echo esc_js( __( 'Utiliser ces images', 'winshirt' ) ); ?>' },
                    multiple: true
                });
                frame.on('select', function(){
                    var urls = [];
                    frame.state().get('selection').each(function(att){ urls.push(att.toJSON().url); });
                    target.val(urls.join(','));
                });
                frame.open();
            });
        });
        </script>
        <?php
    }

    /**
     * Render meta box Zones d'impression
     */
    public function zones_box( $post ) {
        wp_enqueue_script( 'jquery-ui-draggable' );
        wp_enqueue_script( 'jquery-ui-resizable' );
        $front = get_post_meta( $post->ID, '_ws_mockup_front', true );
        $zones = get_post_meta( $post->ID, '_ws_mockup_zones', true );
        if ( ! is_array( $zones ) ) {
            $zones = [];
        }
        echo '<div id="ws-mockup-zone-wrapper">';
        if ( $front ) {
            echo '<img src="' . esc_url( $front ) . '" id="ws-mockup-base" />';
        } else {
            echo '<p>' . esc_html__( 'Veuillez définir une image avant pour le mockup.', 'winshirt' ) . '</p>';
        }
        foreach ( $zones as $index => $zone ) {
            $name   = esc_attr( $zone['name']   ?? '' );
            $width  = esc_attr( $zone['width']  ?? 100 );
            $height = esc_attr( $zone['height'] ?? 100 );
            $top    = esc_attr( $zone['top']    ?? 0 );
            $left   = esc_attr( $zone['left']   ?? 0 );
            $price  = esc_attr( $zone['price']  ?? 0 );
            echo '<div class="ws-zone" data-index="' . esc_attr( $index ) . '" style="width:' . $width . 'px;height:' . $height . 'px;top:' . $top . 'px;left:' . $left . 'px;">';
            echo '<span class="ws-zone-remove">&times;</span>';
            echo '<span class="ws-zone-label">' . esc_html( $name ) . ' (' . esc_html( $price ) . ')</span>';
            echo '<input type="hidden" name="ws_mockup_zones[' . $index . '][name]" value="' . $name . '" />';
            echo '<input type="hidden" class="zone-width" name="ws_mockup_zones[' . $index . '][width]" value="' . $width . '" />';
            echo '<input type="hidden" class="zone-height" name="ws_mockup_zones[' . $index . '][height]" value="' . $height . '" />';
            echo '<input type="hidden" class="zone-top" name="ws_mockup_zones[' . $index . '][top]" value="' . $top . '" />';
            echo '<input type="hidden" class="zone-left" name="ws_mockup_zones[' . $index . '][left]" value="' . $left . '" />';
            echo '<input type="hidden" class="zone-price" name="ws_mockup_zones[' . $index . '][price]" value="' . $price . '" />';
            echo '</div>';
        }
        echo '</div>';
        echo '<p><button class="button" id="ws-add-zone">' . esc_html__( 'Ajouter une zone', 'winshirt' ) . '</button></p>';
        ?>
        <style>
        #ws-mockup-zone-wrapper{position:relative;display:inline-block;}
        #ws-mockup-zone-wrapper img{max-width:100%;height:auto;display:block;}
        .ws-zone{position:absolute;border:2px dashed #007cba;background:rgba(0,124,186,0.15);}
        .ws-zone-label{position:absolute;bottom:-20px;left:0;background:rgba(0,0,0,0.6);color:#fff;padding:2px 4px;font-size:11px;}
        .ws-zone-remove{position:absolute;top:-8px;right:-8px;background:#d63638;color:#fff;border-radius:50%;width:16px;height:16px;text-align:center;line-height:16px;font-size:12px;cursor:pointer;}
        </style>
        <script>
        jQuery(function($){
            var index = $('#ws-mockup-zone-wrapper .ws-zone').length;
            function initZone(zone){
                zone.draggable({ containment:'#ws-mockup-zone-wrapper', stop:updateInputs });
                zone.resizable({ containment:'#ws-mockup-zone-wrapper', stop:updateInputs });
            }
            function updateInputs(){
                var zone = $(this);
                zone.find('.zone-width').val(zone.width());
                zone.find('.zone-height').val(zone.height());
                zone.find('.zone-top').val(zone.position().top);
                zone.find('.zone-left').val(zone.position().left);
            }
            $('#ws-add-zone').on('click', function(e){
                e.preventDefault();
                var name = prompt('<?php echo esc_js( __( 'Nom de la zone', 'winshirt' ) ); ?>');
                if(!name){return;}
                var price = prompt('<?php echo esc_js( __( 'Prix de la zone', 'winshirt' ) ); ?>','0');
                if(price === null){return;}
                var zone = $('<div class="ws-zone"><span class="ws-zone-remove">&times;</span><span class="ws-zone-label"></span></div>');
                zone.attr('data-index', index);
                zone.css({width:100,height:100,top:0,left:0});
                zone.find('.ws-zone-label').text(name+' ('+price+')');
                var inputs = '<input type="hidden" name="ws_mockup_zones['+index+'][name]" value="'+name+'" />'
                    +'<input type="hidden" class="zone-width" name="ws_mockup_zones['+index+'][width]" value="100" />'
                    +'<input type="hidden" class="zone-height" name="ws_mockup_zones['+index+'][height]" value="100" />'
                    +'<input type="hidden" class="zone-top" name="ws_mockup_zones['+index+'][top]" value="0" />'
                    +'<input type="hidden" class="zone-left" name="ws_mockup_zones['+index+'][left]" value="0" />'
                    +'<input type="hidden" class="zone-price" name="ws_mockup_zones['+index+'][price]" value="'+price+'" />';
                zone.append(inputs);
                $('#ws-mockup-zone-wrapper').append(zone);
                initZone(zone);
                index++;
            });
            $('#ws-mockup-zone-wrapper').on('click', '.ws-zone-remove', function(){
                $(this).closest('.ws-zone').remove();
            });
            $('#ws-mockup-zone-wrapper .ws-zone').each(function(){
                initZone($(this));
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
        update_post_meta( $post_id, '_ws_mockup_color_images', sanitize_text_field( $_POST['ws_mockup_color_images'] ?? '' ) );
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
new WinShirt_Mockups();
