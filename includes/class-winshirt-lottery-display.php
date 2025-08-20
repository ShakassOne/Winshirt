<?php
namespace WinShirt;
if ( ! defined('ABSPATH') ) exit;

/**
 * Options d'affichage (layout/overlay/hover) pour les loteries.
 * - Métas par loterie:
 *   _ws_display_layout   = grid|masonry|slider
 *   _ws_display_overlay  = dark|light|none
 *   _ws_display_fields[] = value,count,button,date,featured
 * - Réglages globaux (page Réglages > Lecture > WinShirt Loteries) si besoin.
 * - Hooks de rendu: filtre les classes CSS et le HTML optionnel du survol.
 */
class Lottery_Display {

    private static $instance;
    public static function instance(): self { return self::$instance ?: (self::$instance = new self()); }

    public function init(): void {
        // Metabox sur CPT
        add_action('add_meta_boxes', [ $this, 'add_meta_box' ]);
        add_action('save_post_winshirt_lottery', [ $this, 'save_meta' ], 10, 2);

        // Filtrage des shortcodes rendus par Lottery::sc_list / sc_card via filters
        add_filter('winshirt_lottery_render_card_args', [ $this, 'filter_card_args' ], 10, 3);

        // Admin: Ajout export tickets bouton
        add_filter('post_row_actions', [ $this, 'row_action_export' ], 10, 2);

        // Un peu de CSS pour les overlays/hover
        add_action('wp_head', [ $this, 'inline_css' ]);
    }

    /* --------- Metabox options d'affichage --------- */
    public function add_meta_box(): void {
        add_meta_box('ws_lottery_display', __('Affichage (carte & liste)','winshirt'), [ $this, 'mb_display' ], 'winshirt_lottery', 'side', 'default');
    }

    public function mb_display(\WP_Post $post): void {
        $layout  = get_post_meta($post->ID,'_ws_display_layout',true) ?: 'grid';
        $overlay = get_post_meta($post->ID,'_ws_display_overlay',true) ?: 'dark';
        $fields  = (array) get_post_meta($post->ID,'_ws_display_fields',true) ?: [ 'value','count','button','date','featured' ];
        wp_nonce_field('ws_lottery_display_save','ws_lottery_display_nonce'); ?>
        <p><label for="ws_display_layout"><strong><?php esc_html_e('Layout','winshirt'); ?></strong></label><br>
            <select name="ws_display_layout" id="ws_display_layout" style="width:100%">
                <option value="grid"    <?php selected($layout,'grid');    ?>><?php esc_html_e('Grille','winshirt'); ?></option>
                <option value="masonry" <?php selected($layout,'masonry'); ?>><?php esc_html_e('Masonry','winshirt'); ?></option>
                <option value="slider"  <?php selected($layout,'slider');  ?>><?php esc_html_e('Slider','winshirt'); ?></option>
            </select></p>
        <p><label for="ws_display_overlay"><strong><?php esc_html_e('Overlay','winshirt'); ?></strong></label><br>
            <select name="ws_display_overlay" id="ws_display_overlay" style="width:100%">
                <option value="dark" <?php selected($overlay,'dark'); ?>><?php esc_html_e('Foncé','winshirt'); ?></option>
                <option value="light"<?php selected($overlay,'light');?>><?php esc_html_e('Clair','winshirt'); ?></option>
                <option value="none" <?php selected($overlay,'none'); ?>><?php esc_html_e('Aucun','winshirt'); ?></option>
            </select></p>
        <p><strong><?php esc_html_e('Champs au survol','winshirt'); ?></strong><br>
            <?php
            $opts = [
                'value'    => __('Valeur du lot','winshirt'),
                'count'    => __('Compteur de tickets','winshirt'),
                'button'   => __('Bouton participer','winshirt'),
                'date'     => __('Date de tirage','winshirt'),
                'featured' => __('Badge “En vedette”','winshirt'),
            ];
            foreach($opts as $k=>$label){
                printf('<label style="display:block"><input type="checkbox" name="ws_display_fields[]" value="%s" %s> %s</label>',
                    esc_attr($k), checked(in_array($k,$fields,true), true, false), esc_html($label)
                );
            }
            ?>
        </p>
        <?php
    }

    public function save_meta(int $post_id, \WP_Post $post): void {
        if ( ! isset($_POST['ws_lottery_display_nonce']) || ! wp_verify_nonce($_POST['ws_lottery_display_nonce'],'ws_lottery_display_save') ) return;
        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
        if ( $post->post_type !== 'winshirt_lottery' ) return;

        $layout  = sanitize_text_field($_POST['ws_display_layout'] ?? 'grid');
        $overlay = sanitize_text_field($_POST['ws_display_overlay'] ?? 'dark');
        $fields  = array_map('sanitize_text_field', (array)($_POST['ws_display_fields'] ?? []));

        update_post_meta($post_id,'_ws_display_layout',  $layout);
        update_post_meta($post_id,'_ws_display_overlay', $overlay);
        update_post_meta($post_id,'_ws_display_fields',  array_values(array_unique($fields)));
    }

    /* --------- Applique les préférences au rendu des cartes --------- */
    /**
     * Filtre facultatif appelé par Lottery::render_card (si présent).
     * @param array $args  arguments de rendu
     * @param int   $post_id
     * @param array $shortcode_atts  attrs shortcode (prioritaires)
     */
    public function filter_card_args(array $args, int $post_id, array $shortcode_atts): array {
        // Valeurs par loterie
        $layout  = get_post_meta($post_id,'_ws_display_layout',true) ?: 'grid';
        $overlay = get_post_meta($post_id,'_ws_display_overlay',true) ?: 'dark';
        $fields  = (array) get_post_meta($post_id,'_ws_display_fields',true) ?: ['value','count','button','date','featured'];

        // Attributs shortcode (force)
        if (!empty($shortcode_atts['layout']))  $layout  = sanitize_text_field($shortcode_atts['layout']);
        if (!empty($shortcode_atts['overlay'])) $overlay = sanitize_text_field($shortcode_atts['overlay']);
        if (!empty($shortcode_atts['fields']))  $fields  = array_map('trim', explode(',', sanitize_text_field($shortcode_atts['fields'])));

        $args['layout']  = $layout;
        $args['overlay'] = $overlay;
        $args['fields']  = $fields;
        return $args;
    }

    /* --------- Lien Export tickets dans la liste des loteries --------- */
    public function row_action_export(array $actions, \WP_Post $post): array {
        if ($post->post_type !== 'winshirt_lottery') return $actions;
        $nonce = wp_create_nonce('ws_export_tickets_'.$post->ID);
        $url = admin_url('admin-post.php?action=ws_export_tickets&lottery_id='.$post->ID.'&_wpnonce='.$nonce);
        $actions['ws_export'] = '<a href="'.esc_url($url).'">'.esc_html__('Exporter tickets CSV','winshirt').'</a>';
        return $actions;
    }

    /* --------- Un peu de CSS utilitaire (overlay/hover) --------- */
    public function inline_css(): void {
        ?>
        <style id="winshirt-lottery-display">
        .ws-card-mini {position:relative; overflow:hidden; border-radius:16px; box-shadow:0 6px 20px rgba(0,0,0,.08); background:#0a0a0a; color:#fff;}
        .ws-card-mini-media img{width:100%;height:auto;display:block;}
        .ws-card-mini .ws-mini-badge{position:absolute;top:12px;left:12px;padding:4px 8px;border-radius:12px;background:#6d28d9;font-size:12px}
        .ws-card-mini .ws-card-mini-body{padding:14px}
        .ws-card-mini .ws-card-mini-title{font-size:18px;line-height:1.2;margin:0 0 6px}
        .ws-card-mini .ws-mini-value{opacity:.85;margin-bottom:6px}
        .ws-card-mini .ws-mini-timer{font-family:ui-monospace,monospace;opacity:.9;margin-bottom:6px}
        .ws-card-mini .ws-mini-count{opacity:.9;margin-bottom:10px}
        .ws-btn{display:inline-block;padding:8px 14px;border-radius:999px;background:#fff;color:#111;text-decoration:none;font-weight:600}
        .ws-btn.ws-btn-sm{padding:6px 12px;font-size:13px}
        /* overlays */
        .ws-card-mini[data-overlay="dark"] .ws-card-mini-media::after{content:"";position:absolute;inset:0;background:linear-gradient(180deg,rgba(0,0,0,0) 40%, rgba(0,0,0,.6) 100%);}
        .ws-card-mini[data-overlay="light"] .ws-card-mini-media::after{content:"";position:absolute;inset:0;background:linear-gradient(180deg,rgba(255,255,255,0) 40%, rgba(255,255,255,.45) 100%);}
        </style>
        <?php
    }
}
