<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WinShirt_Mockups {

    public function __construct() {
        add_action( 'init',              [ $this, 'register_cpt' ] );
        add_action( 'add_meta_boxes',     [ $this, 'register_meta_boxes' ] );
        add_action( 'save_post_ws-mockup', [ $this, 'save_meta' ], 10, 2 );
        add_action( 'wp_ajax_winshirt_delete_zones', [ $this, 'ajax_delete_zones' ] );
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
        $front = get_post_meta( $post->ID, '_winshirt_mockup_front', true );
        $back  = get_post_meta( $post->ID, '_winshirt_mockup_back', true );
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
        $front = get_post_meta( $post->ID, '_winshirt_mockup_front', true );
        $back  = get_post_meta( $post->ID, '_winshirt_mockup_back', true );
        $zones = get_post_meta( $post->ID, '_winshirt_print_zones', true );
        if ( ! is_array( $zones ) ) {
            $zones = [ 'front' => [], 'back' => [] ];
        }
        $nonce = wp_create_nonce( 'winshirt_delete_zones' );

        echo '<div id="ws-zone-sides" data-front="' . esc_attr( $front ) . '" data-back="' . esc_attr( $back ) . '">';
        echo '<button type="button" class="button button-secondary active" data-side="front">' . esc_html__( 'Recto', 'winshirt' ) . '</button> ';
        echo '<button type="button" class="button button-secondary" data-side="back">' . esc_html__( 'Verso', 'winshirt' ) . '</button>';
        echo '</div>';

        echo '<div id="ws-mockup-zone-wrapper">';
        if ( $front ) {
            echo '<img src="' . esc_url( $front ) . '" id="ws-mockup-image" style="width:600px;height:auto;" />';
        } else {
            echo '<p>' . esc_html__( 'Veuillez définir une image avant pour le mockup.', 'winshirt' ) . '</p>';
        }
        foreach ( [ 'front', 'back' ] as $side ) {
            foreach ( $zones[ $side ] as $index => $zone ) {
                $id = esc_attr( $zone['id'] ?? '' );
                $x  = floatval( $zone['x'] ?? 0 );
                $y  = floatval( $zone['y'] ?? 0 );
                $w  = floatval( $zone['w'] ?? 0 );
                $h  = floatval( $zone['h'] ?? 0 );
                $display = ( $side === 'front' ) ? 'block' : 'none';
                echo '<div class="ws-zone" data-index="' . esc_attr( $index ) . '" data-side="' . esc_attr( $side ) . '" style="left:' . $x . '%;top:' . $y . '%;width:' . $w . '%;height:' . $h . '%;display:' . $display . ';">';
                echo '<span class="ws-zone-remove">&times;</span>';
                echo '<span class="ws-zone-label">' . esc_html( $id ) . '</span>';
                echo '<input type="hidden" class="zone-id" name="winshirt_print_zones[' . $side . '][' . $index . '][id]" value="' . $id . '" />';
                echo '<input type="hidden" class="zone-x" name="winshirt_print_zones[' . $side . '][' . $index . '][x]" value="' . $x . '" />';
                echo '<input type="hidden" class="zone-y" name="winshirt_print_zones[' . $side . '][' . $index . '][y]" value="' . $y . '" />';
                echo '<input type="hidden" class="zone-w" name="winshirt_print_zones[' . $side . '][' . $index . '][w]" value="' . $w . '" />';
                echo '<input type="hidden" class="zone-h" name="winshirt_print_zones[' . $side . '][' . $index . '][h]" value="' . $h . '" />';
                echo '</div>';
            }
        }
        echo '</div>';

        echo '<p><button class="button" id="ws-add-zone">' . esc_html__( 'Ajouter une zone', 'winshirt' ) . '</button> ';
        echo '<button type="button" class="button" id="ws-delete-zones" data-post="' . esc_attr( $post->ID ) . '" data-nonce="' . esc_attr( $nonce ) . '">' . esc_html__( 'Supprimer toutes les zones', 'winshirt' ) . '</button></p>';
        ?>
        <style>
        #ws-mockup-zone-wrapper{position:relative;display:inline-block;}
        #ws-mockup-zone-wrapper img{max-width:100%;height:auto;display:block;}
        .ws-zone{position:absolute;border:2px dashed #007cba;background:rgba(0,124,186,0.15);display:none;}
        .ws-zone-label{position:absolute;bottom:-20px;left:0;background:rgba(0,0,0,0.6);color:#fff;padding:2px 4px;font-size:11px;}
        .ws-zone-remove{position:absolute;top:-8px;right:-8px;background:#d63638;color:#fff;border-radius:50%;width:16px;height:16px;text-align:center;line-height:16px;font-size:12px;cursor:pointer;}
        #ws-zone-sides{margin-bottom:10px;}
        #ws-zone-sides .button.active{background:#2271b1;color:#fff;}
        </style>
        <script>
        jQuery(function($){
            var currentSide = 'front';
            var index = $('#ws-mockup-zone-wrapper .ws-zone').length;
            function initZone(zone){
                zone.draggable({ containment:'#ws-mockup-zone-wrapper', stop:updateInputs })
                    .resizable({ containment:'#ws-mockup-zone-wrapper', stop:updateInputs });
            }
            function updateInputs(){
                var zone = $(this);
                var wrap = $('#ws-mockup-zone-wrapper');
                var w = wrap.width();
                var h = wrap.height();
                zone.find('.zone-w').val( (zone.width()/w*100).toFixed(2) );
                zone.find('.zone-h').val( (zone.height()/h*100).toFixed(2) );
                zone.find('.zone-x').val( (zone.position().left/w*100).toFixed(2) );
                zone.find('.zone-y').val( (zone.position().top/h*100).toFixed(2) );
            }
            $('#ws-add-zone').on('click', function(e){
                e.preventDefault();
                var name = prompt('<?php echo esc_js( __( 'Nom de la zone', 'winshirt' ) ); ?>');
                if(!name){return;}
                var zone = $('<div class="ws-zone" data-side="'+currentSide+'"><span class="ws-zone-remove">&times;</span><span class="ws-zone-label"></span></div>');
                zone.attr('data-index', index);
                zone.css({left:'10%',top:'10%',width:'20%',height:'20%'});
                zone.find('.ws-zone-label').text(name);
                var inputs = '<input type="hidden" class="zone-id" name="winshirt_print_zones['+currentSide+']['+index+'][id]" value="'+name+'" />'
                    +'<input type="hidden" class="zone-x" name="winshirt_print_zones['+currentSide+']['+index+'][x]" value="10" />'
                    +'<input type="hidden" class="zone-y" name="winshirt_print_zones['+currentSide+']['+index+'][y]" value="10" />'
                    +'<input type="hidden" class="zone-w" name="winshirt_print_zones['+currentSide+']['+index+'][w]" value="20" />'
                    +'<input type="hidden" class="zone-h" name="winshirt_print_zones['+currentSide+']['+index+'][h]" value="20" />';
                zone.append(inputs);
                $('#ws-mockup-zone-wrapper').append(zone);
                initZone(zone);
                if(currentSide === 'front'){ zone.show(); }
                index++;
            });
            $('#ws-mockup-zone-wrapper').on('click', '.ws-zone-remove', function(){
                $(this).closest('.ws-zone').remove();
            });
            $('#ws-zone-sides button').on('click', function(){
                var side = $(this).data('side');
                if(side === currentSide){ return; }
                currentSide = side;
                $('#ws-zone-sides .button').removeClass('active');
                $(this).addClass('active');
                var img = $('#ws-zone-sides').data(side);
                $('#ws-mockup-image').attr('src', img);
                $('#ws-mockup-zone-wrapper .ws-zone').hide().filter(function(){ return $(this).data('side') === side; }).show();
            });
            $('#ws-mockup-zone-wrapper .ws-zone').each(function(){ initZone($(this)); });
            $('#ws-delete-zones').on('click', function(e){
                e.preventDefault();
                if(!confirm('<?php echo esc_js( __( 'Confirmer la suppression ?', 'winshirt' ) ); ?>')){ return; }
                var nonce = $(this).data('nonce');
                var post = $(this).data('post');
                $.post(ajaxurl, { action: 'winshirt_delete_zones', nonce: nonce, post_id: post }, function(){ location.reload(); });
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
        $front = sanitize_text_field( $_POST['ws_mockup_front'] ?? '' );
        $back  = sanitize_text_field( $_POST['ws_mockup_back']  ?? '' );
        update_post_meta( $post_id, '_winshirt_mockup_front', $front );
        update_post_meta( $post_id, '_winshirt_mockup_back',  $back );
        // Ancien format
        update_post_meta( $post_id, '_ws_mockup_front', $front );
        update_post_meta( $post_id, '_ws_mockup_back',  $back );

        // Couleurs
        update_post_meta( $post_id, '_ws_mockup_colors', sanitize_text_field( $_POST['ws_mockup_colors'] ?? '' ) );
        update_post_meta( $post_id, '_ws_mockup_color_images', sanitize_text_field( $_POST['ws_mockup_color_images'] ?? '' ) );

        // Zones d'impression
        $zones = $_POST['winshirt_print_zones'] ?? [];
        $clean = [ 'front' => [], 'back' => [] ];
        if ( is_array( $zones ) ) {
            foreach ( [ 'front', 'back' ] as $side ) {
                if ( isset( $zones[ $side ] ) && is_array( $zones[ $side ] ) ) {
                    foreach ( $zones[ $side ] as $z ) {
                        $clean[ $side ][] = [
                            'id' => sanitize_text_field( $z['id'] ?? '' ),
                            'x'  => floatval( $z['x']  ?? 0 ),
                            'y'  => floatval( $z['y']  ?? 0 ),
                            'w'  => floatval( $z['w']  ?? 0 ),
                            'h'  => floatval( $z['h']  ?? 0 ),
                        ];
                    }
                }
            }
        }
        update_post_meta( $post_id, '_winshirt_print_zones', $clean );
    }

    /**
     * AJAX handler to delete all print zones.
     */
    public function ajax_delete_zones() {
        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
        $nonce   = $_POST['nonce'] ?? '';
        if ( ! $post_id || ! wp_verify_nonce( $nonce, 'winshirt_delete_zones' ) ) {
            wp_send_json_error();
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            wp_send_json_error();
        }
        delete_post_meta( $post_id, '_winshirt_print_zones' );
        wp_send_json_success();
    }
}

// Instanciation
new WinShirt_Mockups();
