<?php
namespace WinShirt;
if ( ! defined('ABSPATH') ) exit;

/**
 * Lien Produits ↔ Loteries (WooCommerce)
 * - Onglet "Loterie" sur les produits : activer, choisir la loterie, nombre de tickets
 * - Defaults par catégorie produit (facultatif)
 * - Bandeau front sur la fiche produit : "Ce produit (catégories) donne droit à X tickets pour la loterie « Titre »."
 */
class Lottery_Product_Link {
    private static $instance;
    public static function instance(): self { return self::$instance ?: (self::$instance = new self()); }

    public function init(): void {
        if ( ! class_exists('WooCommerce') ) return;

        // Produit
        add_filter('woocommerce_product_data_tabs', [ $this, 'add_tab' ]);
        add_action('woocommerce_product_data_panels', [ $this, 'panel' ]);
        add_action('woocommerce_process_product_meta', [ $this, 'save' ]);

        // Catégorie produit (defaults)
        add_action('product_cat_add_form_fields', [ $this, 'cat_add_fields' ]);
        add_action('product_cat_edit_form_fields', [ $this, 'cat_edit_fields' ]);
        add_action('created_product_cat', [ $this, 'cat_save_fields' ]);
        add_action('edited_product_cat',  [ $this, 'cat_save_fields' ]);

        // Bandeau front
        add_action('woocommerce_single_product_summary', [ $this, 'render_notice' ], 7);
    }

    /* ---------- Produit : onglet ---------- */
    public function add_tab($tabs){
        $tabs['ws_lottery'] = [
            'label'    => __('Loterie','winshirt'),
            'target'   => 'ws_lottery_product_data',
            'class'    => ['show_if_simple','show_if_variable','show_if_external','show_if_grouped'],
            'priority' => 70,
        ];
        return $tabs;
    }

    public function panel(): void {
        global $post;
        $enabled = get_post_meta($post->ID,'_ws_enable_lottery',true)==='yes';
        $lottery = (int) get_post_meta($post->ID,'_ws_lottery_id',true);
        $tickets = (int) get_post_meta($post->ID,'_ws_ticket_count',true);

        $lotteries = get_posts([ 'post_type'=>'winshirt_lottery', 'numberposts'=>-1, 'post_status'=>'publish', 'orderby'=>'title','order'=>'ASC' ]);
        ?>
        <div id="ws_lottery_product_data" class="panel woocommerce_options_panel">
            <div class="options_group">
                <?php
                woocommerce_wp_checkbox([
                    'id'          => '_ws_enable_lottery',
                    'label'       => __('Activer pour ce produit','winshirt'),
                    'value'       => $enabled ? 'yes' : 'no',
                    'desc_tip'    => true,
                    'description' => __('Si activé, ce produit génère des tickets pour une loterie.','winshirt')
                ]);
                woocommerce_wp_select([
                    'id'       => '_ws_lottery_id',
                    'label'    => __('Loterie liée','winshirt'),
                    'value'    => $lottery,
                    'options'  => $this->options_for($lotteries),
                    'desc_tip' => true,
                    'description' => __('Sélectionnez la loterie à créditer.','winshirt')
                ]);
                woocommerce_wp_text_input([
                    'id'                => '_ws_ticket_count',
                    'label'             => __('Nombre de tickets','winshirt'),
                    'type'              => 'number',
                    'custom_attributes' => ['min'=>'0','step'=>'1'],
                    'value'             => $tickets>0 ? $tickets : ''
                ]);
                ?>
            </div>
        </div>
        <?php
    }

    public function save(int $post_id): void {
        $enabled = isset($_POST['_ws_enable_lottery']) && $_POST['_ws_enable_lottery']==='yes' ? 'yes':'no';
        $lottery = isset($_POST['_ws_lottery_id']) ? (int) $_POST['_ws_lottery_id'] : 0;
        $tickets = isset($_POST['_ws_ticket_count']) ? max(0,(int)$_POST['_ws_ticket_count']) : 0;

        update_post_meta($post_id,'_ws_enable_lottery',$enabled);
        update_post_meta($post_id,'_ws_lottery_id',$lottery);
        update_post_meta($post_id,'_ws_ticket_count',$tickets);
    }

    /* ---------- Catégorie produit (defaults facultatifs) ---------- */
    public function cat_add_fields(): void { ?>
        <div class="form-field">
            <label for="ws_lottery_cat_id"><?php esc_html_e('Loterie par défaut','winshirt'); ?></label>
            <select id="ws_lottery_cat_id" name="ws_lottery_cat_id"><?php echo $this->options_html(); ?></select>
            <p class="description"><?php esc_html_e('Utilisée si le produit n’a pas de loterie définie.','winshirt'); ?></p>
        </div>
        <div class="form-field">
            <label for="ws_lottery_cat_tickets"><?php esc_html_e('Tickets par défaut','winshirt'); ?></label>
            <input type="number" min="0" step="1" id="ws_lottery_cat_tickets" name="ws_lottery_cat_tickets">
        </div>
    <?php }

    public function cat_edit_fields($term): void {
        $lottery = (int) get_term_meta($term->term_id,'_ws_lottery_cat_id',true);
        $tickets = (int) get_term_meta($term->term_id,'_ws_lottery_cat_tickets',true); ?>
        <tr class="form-field">
            <th scope="row"><label for="ws_lottery_cat_id"><?php esc_html_e('Loterie par défaut','winshirt'); ?></label></th>
            <td><select id="ws_lottery_cat_id" name="ws_lottery_cat_id"><?php echo $this->options_html($lottery); ?></select></td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label for="ws_lottery_cat_tickets"><?php esc_html_e('Tickets par défaut','winshirt'); ?></label></th>
            <td><input type="number" min="0" step="1" id="ws_lottery_cat_tickets" name="ws_lottery_cat_tickets" value="<?php echo (int)$tickets; ?>"></td>
        </tr>
    <?php }

    public function cat_save_fields($term_id): void {
        $lottery = isset($_POST['ws_lottery_cat_id']) ? (int) $_POST['ws_lottery_cat_id'] : 0;
        $tickets = isset($_POST['ws_lottery_cat_tickets']) ? max(0,(int)$_POST['ws_lottery_cat_tickets']) : 0;
        update_term_meta($term_id,'_ws_lottery_cat_id',$lottery);
        update_term_meta($term_id,'_ws_lottery_cat_tickets',$tickets);
    }

    /* ---------- Bandeau front produit ---------- */
    public function render_notice(): void {
        if ( ! function_exists('is_product') || ! is_product() ) return;
        global $product;
        if ( ! $product ) return;

        $enabled = get_post_meta($product->get_id(),'_ws_enable_lottery',true)==='yes';
        $lottery = (int) get_post_meta($product->get_id(),'_ws_lottery_id',true);
        $tickets = (int) get_post_meta($product->get_id(),'_ws_ticket_count',true);

        // Fallback catégorie si pas de réglage produit
        if ( ! $enabled || $lottery<=0 || $tickets<=0 ) {
            $terms = get_the_terms($product->get_id(),'product_cat') ?: [];
            foreach($terms as $t){
                $cat_lot = (int) get_term_meta($t->term_id,'_ws_lottery_cat_id',true);
                $cat_tix = (int) get_term_meta($t->term_id,'_ws_lottery_cat_tickets',true);
                if ($cat_lot>0 && $cat_tix>0){ $enabled = true; $lottery=$cat_lot; $tickets=$cat_tix; break; }
            }
        }

        if ( ! $enabled || $lottery<=0 || $tickets<=0 ) return;

        $lottery_title = get_the_title($lottery);
        if ( ! $lottery_title ) return;

        $cats = get_the_terms($product->get_id(),'product_cat');
        $names = $cats && ! is_wp_error($cats) ? wp_list_pluck($cats,'name') : [];
        $cats_str = $names ? implode(', ',$names) : __('ce produit','winshirt');

        $msg = sprintf(
            __('Ce produit (%1$s) vous donne droit à %2$d ticket(s) pour la loterie « %3$s ».','winshirt'),
            esc_html($cats_str),
            (int)$tickets,
            esc_html($lottery_title)
        );

        echo '<div class="ws-lottery-product-notice">'.$msg.'</div>';
    }

    /* ---------- Utils ---------- */
    private function options_for(array $posts): array {
        $o = [ 0 => __('— Sélectionnez —','winshirt') ];
        foreach($posts as $p){ $o[$p->ID] = $p->post_title.' (#'.$p->ID.')'; }
        return $o;
    }
    private function options_html(int $selected=0): string {
        $posts = get_posts([ 'post_type'=>'winshirt_lottery','numberposts'=>-1,'post_status'=>'publish','orderby'=>'title','order'=>'ASC' ]);
        $html = '<option value="0">'.esc_html__('— Sélectionnez —','winshirt').'</option>';
        foreach($posts as $p){
            $sel = selected($selected,$p->ID,false);
            $html .= '<option value="'.$p->ID.'" '.$sel.'>'.esc_html($p->post_title.' (#'.$p->ID.')').'</option>';
        }
        return $html;
    }
}
