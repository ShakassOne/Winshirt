<?php
namespace WinShirt;
if ( ! defined('ABSPATH') ) exit;

/**
 * Onglet WooCommerce sur le produit + defaults par catégorie + bandeau front.
 */
class Lottery_Product_Link {
    private static $instance;
    public static function instance(): self { return self::$instance ?: (self::$instance = new self()); }

    public function init(): void {
        if ( ! class_exists('WooCommerce') ) return;

        add_filter('woocommerce_product_data_tabs', [ $this, 'add_tab' ]);
        add_action('woocommerce_product_data_panels', [ $this, 'panel' ]);
        add_action('woocommerce_process_product_meta', [ $this, 'save' ]);

        add_action('product_cat_add_form_fields', [ $this, 'cat_add_fields' ]);
        add_action('product_cat_edit_form_fields', [ $this, 'cat_edit_fields' ]);
        add_action('created_product_cat', [ $this, 'cat_save_fields' ]);
        add_action('edited_product_cat',  [ $this, 'cat_save_fields' ]);

        add_action('woocommerce_single_product_summary', [ $this, 'render_notice' ], 7);
    }

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
                woocommerce_wp_checkbox([ 'id'=>'_ws_enable_lottery','label'=>__('Activer pour ce produit','winshirt'), 'value'=>$enabled?'yes':'no' ]);
                woocommerce_wp_select([ 'id'=>'_ws_lottery_id','label'=>__('Loterie liée','winshirt'), 'options'=>$this->options_for($lotteries), 'value'=>$lottery ]);
                woocommerce_wp_text_input([ 'id'=>'_ws_ticket_count','label'=>__('Nombre de tickets','winshirt'),'type'=>'number','custom_attributes'=>['min'=>'0','step'=>'1'], 'value'=>$tickets>0?$tickets:'' ]);
                ?>
            </div>
        </div>
        <?php
    }

    public function save(int $post_id): void {
        update_post_meta($post_id,'_ws_enable_lottery', isset($_POST['_ws_enable_lottery']) && $_POST['_ws_enable_lottery']==='yes' ? 'yes':'no');
        update_post_meta($post_id,'_ws_lottery_id', isset($_POST['_ws_lottery_id']) ? (int)$_POST['_ws_lottery_id'] : 0 );
        update_post_meta($post_id,'_ws_ticket_count', isset($_POST['_ws_ticket_count']) ? max(0,(int)$_POST['_ws_ticket_count']) : 0 );
    }

    public function cat_add_fields(): void { ?>
        <div class="form-field">
            <label for="ws_lottery_cat_id"><?php esc_html_e('Loterie par défaut','winshirt'); ?></label>
            <select id="ws_lottery_cat_id" name="ws_lottery_cat_id"><?php echo $this->options_html(); ?></select>
            <p class="description"><?php esc_html_e('Utilisée si le produit n’a pas de réglage propre.','winshirt'); ?></p>
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
        update_term_meta($term_id,'_ws_lottery_cat_id', isset($_POST['ws_lottery_cat_id']) ? (int)$_POST['ws_lottery_cat_id'] : 0 );
        update_term_meta($term_id,'_ws_lottery_cat_tickets', isset($_POST['ws_lottery_cat_tickets']) ? max(0,(int)$_POST['ws_lottery_cat_tickets']) : 0 );
    }

    public function render_notice(): void {
        if ( ! function_exists('is_product') || ! is_product() ) return;
        global $product; if ( ! $product ) return;

        $enabled = get_post_meta($product->get_id(),'_ws_enable_lottery',true)==='yes';
        $lottery = (int) get_post_meta($product->get_id(),'_ws_lottery_id',true);
        $tickets = (int) get_post_meta($product->get_id(),'_ws_ticket_count',true);

        if ( ! $enabled || $lottery<=0 || $tickets<=0 ) {
            $terms = get_the_terms($product->get_id(),'product_cat') ?: [];
            foreach($terms as $t){
                $cat_l = (int) get_term_meta($t->term_id,'_ws_lottery_cat_id',true);
                $cat_t = (int) get_term_meta($t->term_id,'_ws_lottery_cat_tickets',true);
                if ($cat_l>0 && $cat_t>0){ $enabled=true; $lottery=$cat_l; $tickets=$cat_t; break; }
            }
        }
        if ( ! $enabled || $lottery<=0 || $tickets<=0 ) return;

        $lottery_title = get_the_title($lottery); if ( ! $lottery_title ) return;
        $cats = get_the_terms($product->get_id(),'product_cat');
        $names = $cats && ! is_wp_error($cats) ? wp_list_pluck($cats,'name') : [];
        $cats_str = $names ? implode(', ',$names) : __('ce produit','winshirt');

        echo '<div class="ws-lottery-product-notice">'.sprintf(
            __('Ce produit (%1$s) vous donne droit à %2$d ticket(s) pour la loterie « %3$s ».','winshirt'),
            esc_html($cats_str), (int)$tickets, esc_html($lottery_title)
        ).'</div>';
    }

    private function options_for(array $posts): array { $o=[0=>__('— Sélectionnez —','winshirt')]; foreach($posts as $p){ $o[$p->ID]=$p->post_title.' (#'.$p->ID.')'; } return $o; }
    private function options_html(int $selected=0): string {
        $posts = get_posts([ 'post_type'=>'winshirt_lottery','numberposts'=>-1,'post_status'=>'publish','orderby'=>'title','order'=>'ASC' ]);
        $h = '<option value="0">'.esc_html__('— Sélectionnez —','winshirt').'</option>';
        foreach($posts as $p){ $h .= '<option value="'.$p->ID.'" '.selected($selected,$p->ID,false).'>'.esc_html($p->post_title.' (#'.$p->ID.')').'</option>'; }
        return $h;
    }
}
