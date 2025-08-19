<?php
namespace WinShirt;
if ( ! defined('ABSPATH') ) exit;

/**
 * Lien WooCommerce : Produits ↔ Loteries
 * - Onglet produit : activer, choisir la loterie, nombre de tickets
 * - Defaults par catégorie
 * - Bandeau front (single produit ET shortcode [product_page])
 * - Récap des tickets dans le Panier et au Checkout
 */
class Lottery_Product_Link {
    private static $instance;
    public static function instance(): self { return self::$instance ?: (self::$instance = new self()); }

    /** Bootstrap des hooks WooCommerce */
    public function init(): void {
        if ( ! class_exists('WooCommerce') ) return;

        /* === ADMIN PRODUIT === */
        add_filter('woocommerce_product_data_tabs',    [ $this, 'add_tab' ]);
        add_action('woocommerce_product_data_panels',  [ $this, 'panel' ]);
        add_action('woocommerce_process_product_meta', [ $this, 'save' ]);

        /* === ADMIN CATÉGORIE (defaults) === */
        add_action('product_cat_add_form_fields', [ $this, 'cat_add_fields' ]);
        add_action('product_cat_edit_form_fields', [ $this, 'cat_edit_fields' ]);
        add_action('created_product_cat', [ $this, 'cat_save_fields' ]);
        add_action('edited_product_cat',  [ $this, 'cat_save_fields' ]);

        /* === FRONT : BANDEAU SUR PRODUIT ===
         * On se branche sur le hook standard du template single, MAIS
         * on supprime la condition is_product() pour que le bandeau
         * apparaisse aussi quand un produit est affiché via [product_page].
         */
        add_action('woocommerce_single_product_summary', [ $this, 'render_notice' ], 7);

        /* === FRONT : RÉCAP TICKETS PANIER / CHECKOUT === */
        // Panier (au-dessus du total)
        add_action('woocommerce_cart_totals_before_order_total', [ $this, 'render_cart_notice' ]);
        // Checkout (au-dessus du bloc de paiement)
        add_action('woocommerce_checkout_before_order_review',   [ $this, 'render_cart_notice' ]);
    }

    /* ---------------------------------------------------------------------
     * Onglet personnalisé sur l'écran produit
     * -------------------------------------------------------------------*/
    public function add_tab($tabs){
        $tabs['ws_lottery'] = [
            'label'    => __('Loterie','winshirt'),
            'target'   => 'ws_lottery_product_data',
            'class'    => ['show_if_simple','show_if_variable','show_if_external','show_if_grouped'],
            'priority' => 70,
        ];
        return $tabs;
    }

    /** Rendu du panneau d’options dans l’édition produit */
    public function panel(): void {
        global $post;
        $enabled = get_post_meta($post->ID,'_ws_enable_lottery',true)==='yes';
        $lottery = (int) get_post_meta($post->ID,'_ws_lottery_id',true);
        $tickets = (int) get_post_meta($post->ID,'_ws_ticket_count',true);
        $lotteries = get_posts([
            'post_type'   => 'winshirt_lottery',
            'numberposts' => -1,
            'post_status' => 'publish',
            'orderby'     => 'title',
            'order'       => 'ASC',
        ]);
        ?>
        <div id="ws_lottery_product_data" class="panel woocommerce_options_panel">
            <div class="options_group">
                <?php
                woocommerce_wp_checkbox([
                    'id'          => '_ws_enable_lottery',
                    'label'       => __('Activer pour ce produit','winshirt'),
                    'value'       => $enabled ? 'yes' : 'no',
                    'desc_tip'    => true,
                    'description' => __('Si activé, ce produit crédite une loterie d’un certain nombre de tickets.','winshirt')
                ]);
                woocommerce_wp_select([
                    'id'          => '_ws_lottery_id',
                    'label'       => __('Loterie liée','winshirt'),
                    'value'       => $lottery,
                    'options'     => $this->options_for($lotteries),
                    'desc_tip'    => true,
                    'description' => __('Sélectionnez la loterie créditée à l’achat.','winshirt')
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

    /** Sauvegarde des métadonnées produit */
    public function save(int $post_id): void {
        $enabled = isset($_POST['_ws_enable_lottery']) && $_POST['_ws_enable_lottery']==='yes' ? 'yes':'no';
        $lottery = isset($_POST['_ws_lottery_id']) ? (int) $_POST['_ws_lottery_id'] : 0;
        $tickets = isset($_POST['_ws_ticket_count']) ? max(0,(int)$_POST['_ws_ticket_count']) : 0;

        update_post_meta($post_id,'_ws_enable_lottery',$enabled);
        update_post_meta($post_id,'_ws_lottery_id',$lottery);
        update_post_meta($post_id,'_ws_ticket_count',$tickets);
    }

    /* ---------------------------------------------------------------------
     * Catégories produit : valeurs par défaut (fallback)
     * -------------------------------------------------------------------*/
    public function cat_add_fields(): void { ?>
        <div class="form-field">
            <label for="ws_lottery_cat_id"><?php esc_html_e('Loterie par défaut','winshirt'); ?></label>
            <select id="ws_lottery_cat_id" name="ws_lottery_cat_id"><?php echo $this->options_html(); ?></select>
            <p class="description"><?php esc_html_e('Appliquée si le produit n’a pas de loterie configurée.','winshirt'); ?></p>
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
        $tickets = isset($_POST['ws_lottery_cat_tickets']) ? max(0,(int) $_POST['ws_lottery_cat_tickets']) : 0;
        update_term_meta($term_id,'_ws_lottery_cat_id',$lottery);
        update_term_meta($term_id,'_ws_lottery_cat_tickets',$tickets);
    }

    /* ---------------------------------------------------------------------
     * FRONT : Bandeau sur fiche produit ET quand rendu via [product_page]
     * -------------------------------------------------------------------*/
    public function render_notice(): void {
        global $product;

        // Sécurité : s’assurer qu’on a bien un objet produit dans ce contexte.
        if ( ! $product || ! is_a($product, '\WC_Product') ) return;

        // Cherche loterie/tickets sur le produit/variation, sinon fallback catégorie.
        $info = $this->resolve_lottery_for_product( $product->get_id() );
        if ( ! $info['enabled'] || $info['lottery_id'] <= 0 || $info['tickets'] <= 0 ) return;

        $lottery_title = get_the_title( $info['lottery_id'] );
        if ( ! $lottery_title ) return;

        // Liste des catégories (utile pour le wording)
        $terms = get_the_terms( $product->get_id(), 'product_cat' );
        $names = $terms && ! is_wp_error($terms) ? wp_list_pluck($terms,'name') : [];
        $cats_str = $names ? implode(', ',$names) : __('ce produit','winshirt');

        $msg = sprintf(
            __('Ce produit (%1$s) vous donne droit à %2$d ticket(s) pour la loterie « %3$s ».','winshirt'),
            esc_html($cats_str),
            (int) $info['tickets'],
            esc_html($lottery_title)
        );

        echo '<div class="ws-lottery-product-notice">'.$msg.'</div>';
    }

    /* ---------------------------------------------------------------------
     * FRONT : Récap des tickets dans le PANIER et le CHECKOUT
     * -------------------------------------------------------------------*/
    public function render_cart_notice(): void {
        if ( ! function_exists('WC') || ! WC()->cart ) return;

        $cart = WC()->cart->get_cart();
        if ( empty($cart) ) return;

        // Regroupe par loterie : [lottery_id => total_tickets]
        $by_lottery = [];
        foreach ( $cart as $item ) {
            $pid = $item['product_id'] ?? 0;
            $qty = (int) ($item['quantity'] ?? 1);

            // Si variation, on préfère l’ID parent si la variation n’a pas de réglage propre
            if ( empty($pid) && ! empty($item['variation_id']) ) {
                $pid = (int) $item['variation_id'];
            }

            $info = $this->resolve_lottery_for_product( $pid );
            if ( ! $info['enabled'] || $info['lottery_id'] <= 0 || $info['tickets'] <= 0 ) {
                continue;
            }
            $tickets = $info['tickets'] * max(1,$qty);
            if ( ! isset($by_lottery[$info['lottery_id']]) ) $by_lottery[$info['lottery_id']] = 0;
            $by_lottery[$info['lottery_id']] += $tickets;
        }

        if ( empty($by_lottery) ) return;

        // Rendu
        echo '<div class="ws-lottery-product-notice" style="margin-bottom:12px">';
        echo '<strong>'.esc_html__('Tickets de loterie associés à votre panier :','winshirt').'</strong><br>';
        $total = 0;
        foreach ( $by_lottery as $lid => $tix ) {
            $title = get_the_title($lid) ?: ('#'.$lid);
            $total += $tix;
            echo '<div>• '.esc_html($title).' : <b>'.(int)$tix.'</b> ' . esc_html(_n('ticket','tickets',$tix,'winshirt')) . '</div>';
        }
        echo '<div style="margin-top:6px;border-top:1px dashed #ddd;padding-top:6px">'.sprintf(
            esc_html__('Total tickets : %d','winshirt'), (int)$total
        ).'</div>';
        echo '</div>';
    }

    /* ---------------------------------------------------------------------
     * Helpers
     * -------------------------------------------------------------------*/

    /**
     * Calcule la loterie et les tickets pour un produit donné :
     * - Si variation => vérifie la variation puis le parent
     * - Sinon => produit simple
     * - Fallback : caté produit (si ni produit ni variation n’a de réglage)
     * @return array{enabled:bool, lottery_id:int, tickets:int}
     */
    private function resolve_lottery_for_product( int $product_id ): array {
        $enabled = get_post_meta($product_id,'_ws_enable_lottery',true)==='yes';
        $lottery = (int) get_post_meta($product_id,'_ws_lottery_id',true);
        $tickets = (int) get_post_meta($product_id,'_ws_ticket_count',true);

        // Si pas de réglage sur la variation, on tente le parent
        $parent_id = wp_get_post_parent_id($product_id);
        if ( ( ! $enabled || $lottery<=0 || $tickets<=0 ) && $parent_id ) {
            $enabled = get_post_meta($parent_id,'_ws_enable_lottery',true)==='yes';
            $lottery = (int) get_post_meta($parent_id,'_ws_lottery_id',true);
            $tickets = (int) get_post_meta($parent_id,'_ws_ticket_count',true);
        }

        // Fallback catégorie
        if ( ! $enabled || $lottery<=0 || $tickets<=0 ) {
            $terms = get_the_terms($product_id,'product_cat') ?: [];
            foreach($terms as $t){
                $cat_lot = (int) get_term_meta($t->term_id,'_ws_lottery_cat_id',true);
                $cat_tix = (int) get_term_meta($t->term_id,'_ws_lottery_cat_tickets',true);
                if ($cat_lot>0 && $cat_tix>0){ $enabled=true; $lottery=$cat_lot; $tickets=$cat_tix; break; }
            }
        }

        return [
            'enabled'    => (bool) $enabled,
            'lottery_id' => (int)  $lottery,
            'tickets'    => (int)  $tickets,
        ];
    }

    /** Liste d’options pour le select (admin produit) */
    private function options_for(array $posts): array {
        $o = [ 0 => __('— Sélectionnez —','winshirt') ];
        foreach($posts as $p){ $o[$p->ID] = $p->post_title.' (#'.$p->ID.')'; }
        return $o;
    }

    /** HTML des options (admin catégories) */
    private function options_html(int $selected=0): string {
        $posts = get_posts([
            'post_type'   => 'winshirt_lottery',
            'numberposts' => -1,
            'post_status' => 'publish',
            'orderby'     => 'title',
            'order'       => 'ASC',
        ]);
        $html = '<option value="0">'.esc_html__('— Sélectionnez —','winshirt').'</option>';
        foreach($posts as $p){
            $sel = selected($selected,$p->ID,false);
            $html .= '<option value="'.$p->ID.'" '.$sel.'>'.esc_html($p->post_title.' (#'.$p->ID.')').'</option>';
        }
        return $html;
    }
}
