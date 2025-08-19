<?php
namespace WinShirt;

if ( ! defined('ABSPATH') ) exit;

/**
 * CPT Loteries + métas + participants + export + shortcodes + améliorations admin.
 */
class Lottery {

    private static $instance;
    public static function instance(): self { return self::$instance ?: (self::$instance = new self()); }

    /* ------------------------- Bootstrap ------------------------- */
    public function init(): void {
        add_action('init', [ $this, 'register_cpt' ]);
        add_action('add_meta_boxes', [ $this, 'add_meta_boxes' ]);
        add_action('save_post_winshirt_lottery', [ $this, 'save_meta' ], 10, 2);

        // Colonnes + row actions (affiche ID)
        add_filter('manage_winshirt_lottery_posts_columns', [ $this, 'admin_cols' ]);
        add_action('manage_winshirt_lottery_posts_custom_column', [ $this, 'admin_col_render' ], 10, 2);
        add_filter('post_row_actions', [ $this, 'add_row_action_id' ], 10, 2);

        // Formulaire front + export CSV
        add_action('template_redirect', [ $this, 'handle_entry_post' ]);
        add_action('wp_ajax_ws_lottery_export_csv', [ $this, 'ajax_export_csv' ]);

        // Shortcodes
        add_shortcode('winshirt_lotteries',    [ $this, 'sc_list' ]);
        add_shortcode('winshirt_lottery_card', [ $this, 'sc_card' ]);
        add_shortcode('winshirt_lottery_form', [ $this, 'sc_form' ]);
    }

    /* --------------------- CPT + rewrite propres --------------------- */
    public function register_cpt(): void {
        register_post_type('winshirt_lottery', [
            'labels' => [
                'name' => __('Loteries','winshirt'),
                'singular_name' => __('Loterie','winshirt'),
                'add_new_item' => __('Ajouter une loterie','winshirt'),
                'edit_item' => __('Modifier la loterie','winshirt'),
                'all_items' => __('Toutes les loteries','winshirt'),
            ],
            'public'             => true,
            'publicly_queryable' => true,
            'query_var'          => 'winshirt_lottery',
            'has_archive'        => true,
            'rewrite'            => [ 'slug' => 'loteries', 'with_front' => false ],
            'menu_icon'          => 'dashicons-tickets-alt',
            'supports'           => [ 'title','editor','thumbnail','excerpt','author' ],
            'show_in_rest'       => true,
            'map_meta_cap'       => true,
        ]);
    }

    /* --------------------------- Metaboxes --------------------------- */
    public function add_meta_boxes(): void {
        add_meta_box('ws_lottery_details', __('Détails de la loterie','winshirt'), [ $this,'mb_details' ], 'winshirt_lottery', 'normal', 'high');
        add_meta_box('ws_lottery_participants', __('Participants','winshirt'), [ $this,'mb_participants' ], 'winshirt_lottery', 'normal', 'default');
    }

    public function mb_details(\WP_Post $post): void {
        wp_nonce_field('ws_lottery_save','ws_lottery_nonce');

        $start   = get_post_meta($post->ID,'_ws_lottery_start',true);
        $end     = get_post_meta($post->ID,'_ws_lottery_end',true);
        $goal    = (int) get_post_meta($post->ID,'_ws_lottery_goal',true);
        $value   = (string) get_post_meta($post->ID,'_ws_lottery_value',true);
        $terms   = (string) get_post_meta($post->ID,'_ws_lottery_terms_url',true);
        $feat    = get_post_meta($post->ID,'_ws_lottery_featured',true)==='yes';
        $product = (int) get_post_meta($post->ID,'_ws_lottery_product_id',true);

        $products = function_exists('wc_get_products') ? wc_get_products([ 'status'=>'publish','limit'=>200,'orderby'=>'title','order'=>'ASC' ]) : [];
        ?>
        <table class="form-table">
            <tr><th><label for="ws_lottery_start"><?php esc_html_e('Début','winshirt'); ?></label></th>
                <td><input type="datetime-local" id="ws_lottery_start" name="ws_lottery_start" value="<?php echo esc_attr($this->fmt_dt_local($start)); ?>"></td></tr>
            <tr><th><label for="ws_lottery_end"><?php esc_html_e('Fin','winshirt'); ?></label></th>
                <td><input type="datetime-local" id="ws_lottery_end" name="ws_lottery_end" value="<?php echo esc_attr($this->fmt_dt_local($end)); ?>"></td></tr>
            <tr><th><label for="ws_lottery_goal"><?php esc_html_e('Objectif participants','winshirt'); ?></label></th>
                <td><input type="number" min="0" id="ws_lottery_goal" name="ws_lottery_goal" value="<?php echo (int)$goal; ?>"></td></tr>
            <tr><th><label for="ws_lottery_value"><?php esc_html_e('Valeur du lot','winshirt'); ?></label></th>
                <td><input type="text" id="ws_lottery_value" name="ws_lottery_value" value="<?php echo esc_attr($value); ?>" placeholder="ex: 4900 €"></td></tr>
            <tr><th><label for="ws_lottery_product_id"><?php esc_html_e('Produit Woo associé (optionnel)','winshirt'); ?></label></th>
                <td><select id="ws_lottery_product_id" name="ws_lottery_product_id" style="min-width:260px">
                        <option value="0"><?php esc_html_e('— Aucun —','winshirt'); ?></option>
                        <?php foreach($products as $p): ?>
                        <option value="<?php echo (int)$p->get_id(); ?>" <?php selected($product,(int)$p->get_id()); ?>>
                            <?php echo esc_html($p->get_name().' (#'.$p->get_id().')'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select></td></tr>
            <tr><th><label for="ws_lottery_terms_url"><?php esc_html_e('Règlement (URL)','winshirt'); ?></label></th>
                <td><input type="url" id="ws_lottery_terms_url" name="ws_lottery_terms_url" style="width:100%" value="<?php echo esc_url($terms); ?>" placeholder="https://.../reglement"></td></tr>
            <tr><th><?php esc_html_e('En vedette','winshirt'); ?></th>
                <td><label><input type="checkbox" name="ws_lottery_featured" value="yes" <?php checked($feat,true); ?>> <?php esc_html_e('Afficher le badge « En vedette »','winshirt'); ?></label></td></tr>
        </table>
        <?php
    }

    public function save_meta(int $post_id, \WP_Post $post): void {
        if ( ! isset($_POST['ws_lottery_nonce']) || ! wp_verify_nonce($_POST['ws_lottery_nonce'],'ws_lottery_save') ) return;
        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
        if ( $post->post_type !== 'winshirt_lottery' ) return;

        $start   = sanitize_text_field($_POST['ws_lottery_start'] ?? '');
        $end     = sanitize_text_field($_POST['ws_lottery_end'] ?? '');
        $goal    = (int)($_POST['ws_lottery_goal'] ?? 0);
        $value   = sanitize_text_field($_POST['ws_lottery_value'] ?? '');
        $terms   = esc_url_raw($_POST['ws_lottery_terms_url'] ?? '');
        $feat    = isset($_POST['ws_lottery_featured']) ? 'yes' : 'no';
        $product = (int)($_POST['ws_lottery_product_id'] ?? 0);

        update_post_meta($post_id,'_ws_lottery_start',$this->parse_dt_local($start));
        update_post_meta($post_id,'_ws_lottery_end',$this->parse_dt_local($end));
        update_post_meta($post_id,'_ws_lottery_goal',max(0,$goal));
        update_post_meta($post_id,'_ws_lottery_value',$value);
        update_post_meta($post_id,'_ws_lottery_terms_url',$terms);
        update_post_meta($post_id,'_ws_lottery_featured',$feat);
        update_post_meta($post_id,'_ws_lottery_product_id',$product);

        if ( get_post_meta($post_id,'_ws_lottery_count',true)==='' ) update_post_meta($post_id,'_ws_lottery_count',0);
        if ( get_post_meta($post_id,'_ws_lottery_participants',true)==='' ) update_post_meta($post_id,'_ws_lottery_participants',[]);
    }

    /* ----------------------- Participants & export ----------------------- */
    public function mb_participants(\WP_Post $post): void {
        $rows  = (array) get_post_meta($post->ID,'_ws_lottery_participants',true);
        $count = (int) get_post_meta($post->ID,'_ws_lottery_count',true); ?>
        <p><b><?php esc_html_e('Total participants :','winshirt'); ?></b> <?php echo (int)$count; ?></p>
        <p><a href="#" class="button" id="ws-export-csv" data-id="<?php echo (int)$post->ID; ?>"><?php esc_html_e('Exporter CSV','winshirt'); ?></a></p>
        <table class="widefat striped">
            <thead><tr><th>Date</th><th>Nom</th><th>Email</th><th>Commande</th><th>IP</th></tr></thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="5"><?php esc_html_e('Aucun participant pour le moment.','winshirt'); ?></td></tr>
            <?php else: foreach(array_reverse($rows) as $r): ?>
                <tr>
                    <td><?php echo esc_html($r['date'] ?? ''); ?></td>
                    <td><?php echo esc_html($r['name'] ?? ''); ?></td>
                    <td><?php echo esc_html($r['email'] ?? ''); ?></td>
                    <td><?php echo esc_html($r['order'] ?? ''); ?></td>
                    <td><?php echo esc_html($r['ip'] ?? ''); ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        <script>(function($){$('#ws-export-csv').on('click',function(e){e.preventDefault();var id=$(this).data('id');window.location=ajaxurl+'?action=ws_lottery_export_csv&post_id='+id+'&_wpnonce=<?php echo wp_create_nonce('ws_lottery_csv'); ?>';});})(jQuery);</script>
        <?php
    }

    public function handle_entry_post(): void {
        if ( ! isset($_POST['ws_lottery_enter']) ) return;
        if ( ! isset($_POST['ws_lottery_nonce']) || ! wp_verify_nonce($_POST['ws_lottery_nonce'],'ws_lottery_enter') ) return;

        $post_id = (int)($_POST['ws_lottery_id'] ?? 0);
        $name    = sanitize_text_field($_POST['ws_name'] ?? '');
        $email   = sanitize_email($_POST['ws_email'] ?? '');
        $order   = sanitize_text_field($_POST['ws_order'] ?? '');
        $accept  = isset($_POST['ws_accept']);

        if ( ! $post_id || get_post_type($post_id)!=='winshirt_lottery' ) return;
        if ( empty($name) || empty($email) || ! $accept ) return;

        $end = get_post_meta($post_id,'_ws_lottery_end',true);
        if ( $end && strtotime($end) < current_time('timestamp') ) {
            wp_safe_redirect(add_query_arg('ws_lottery','ended',get_permalink($post_id))); exit;
        }

        $rows = (array) get_post_meta($post_id,'_ws_lottery_participants',true);
        foreach($rows as $r){ if ( strtolower($r['email']??'') === strtolower($email) ) {
            wp_safe_redirect(add_query_arg('ws_lottery','exists',get_permalink($post_id))); exit; } }

        $rows[] = [ 'date'=>date_i18n('Y-m-d H:i:s'), 'name'=>$name, 'email'=>$email, 'order'=>$order, 'ip'=>$_SERVER['REMOTE_ADDR'] ?? '' ];
        update_post_meta($post_id,'_ws_lottery_participants',$rows);
        update_post_meta($post_id,'_ws_lottery_count',count($rows));

        $subject = sprintf(__('Confirmation de participation : %s','winshirt'), get_the_title($post_id));
        $body    = $this->email_tpl($post_id,$name);
        wp_mail($email,$subject,$body,['Content-Type: text/html; charset=UTF-8']);

        wp_safe_redirect(add_query_arg('ws_lottery','ok',get_permalink($post_id))); exit;
    }

    private function email_tpl(int $post_id, string $name): string {
        $title = get_the_title($post_id); $url = get_permalink($post_id); $site = wp_specialchars_decode(get_bloginfo('name'),ENT_QUOTES);
        ob_start(); ?>
        <div style="font-family:Arial,Helvetica,sans-serif;max-width:560px;margin:0 auto;padding:16px;border:1px solid #eee;border-radius:8px">
            <h2 style="margin:0 0 10px 0"><?php echo esc_html($site); ?></h2>
            <p><?php printf(esc_html__('Bonjour %s,','winshirt'),esc_html($name)); ?></p>
            <p><?php printf(esc_html__("Votre participation à la loterie « %s » est confirmée.",'winshirt'),esc_html($title)); ?></p>
            <p><a href="<?php echo esc_url($url); ?>" style="display:inline-block;padding:10px 16px;background:#111;color:#fff;text-decoration:none;border-radius:6px"><?php esc_html_e('Voir la loterie','winshirt'); ?></a></p>
        </div>
        <?php return ob_get_clean();
    }

    public function ajax_export_csv(): void {
        check_admin_referer('ws_lottery_csv');
        if ( ! current_user_can('edit_posts') ) wp_die('forbidden','',[ 'response'=>403 ]);
        $post_id = (int)($_GET['post_id'] ?? 0);
        if ( ! $post_id || get_post_type($post_id)!=='winshirt_lottery' ) wp_die('invalid','',[ 'response'=>400 ]);
        $rows = (array) get_post_meta($post_id,'_ws_lottery_participants',true);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="lottery-'.$post_id.'-participants.csv"');
        $out = fopen('php://output','w'); fputcsv($out,['date','name','email','order','ip']);
        foreach($rows as $r){ fputcsv($out,[$r['date']??'',$r['name']??'',$r['email']??'',$r['order']??'',$r['ip']??'']); }
        fclose($out); exit;
    }

    /* -------------------------- Shortcodes -------------------------- */
    // [winshirt_lotteries status="active|finished|all" featured="0|1" limit="12" layout="grid|list" columns="3" show_timer="1" show_count="1"]
    public function sc_list($atts=[]): string {
        $a = shortcode_atts([
            'status'     => 'active',
            'featured'   => '0',
            'limit'      => '12',
            'layout'     => 'grid',
            'columns'    => '3',
            'show_timer' => '1',
            'show_count' => '1',
        ], $atts, 'winshirt_lotteries');

        $now = current_time('timestamp');
        $q = new \WP_Query([
            'post_type'      => 'winshirt_lottery',
            'posts_per_page' => (int)$a['limit'],
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);

        ob_start();
        $class = $a['layout']==='list' ? 'ws-list' : 'ws-grid cols-'.(int)$a['columns'];
        echo '<div class="ws-lottery-list '.$class.'">';
        if ( $q->have_posts() ) {
            while ( $q->have_posts() ) { $q->the_post();
                $id  = get_the_ID();
                $end = get_post_meta($id,'_ws_lottery_end',true);
                $f   = get_post_meta($id,'_ws_lottery_featured',true)==='yes';
                $ok  = true;
                if ( $a['featured']==='1' && ! $f ) $ok = false;
                if ( $a['status']==='active' )   $ok = $end ? ( strtotime($end) >= $now ) : $ok;
                if ( $a['status']==='finished' ) $ok = $end && ( strtotime($end) <  $now );
                if ( $a['status']==='all' )      $ok = true;
                if ( ! $ok ) continue;
                echo $this->render_card($id, [
                    'show_timer' => $a['show_timer']==='1',
                    'show_count' => $a['show_count']==='1',
                ]);
            }
            wp_reset_postdata();
        } else {
            echo '<p>'.esc_html__('Aucune loterie disponible.','winshirt').'</p>';
        }
        echo '</div>';
        return ob_get_clean();
    }

    // [winshirt_lottery_card id="123" show_timer="1" show_count="0"]
    public function sc_card($atts=[]): string {
        $a = shortcode_atts([ 'id'=>'0', 'show_timer'=>'1', 'show_count'=>'1' ], $atts, 'winshirt_lottery_card');
        $id = (int) $a['id'];
        if ( ! $id || get_post_type($id)!=='winshirt_lottery' ) return '';
        return $this->render_card($id, [
            'show_timer' => $a['show_timer']==='1',
            'show_count' => $a['show_count']==='1',
        ]);
    }

    // [winshirt_lottery_form id="123"]
    public function sc_form($atts=[]): string {
        $a = shortcode_atts([ 'id'=>'0' ], $atts, 'winshirt_lottery_form' );
        $id = (int) $a['id']; if ( ! $id || get_post_type($id)!=='winshirt_lottery' ) return '';
        ob_start(); ?>
        <form class="ws-form" method="post">
            <input type="hidden" name="ws_lottery_id" value="<?php echo (int)$id; ?>">
            <?php wp_nonce_field('ws_lottery_enter','ws_lottery_nonce'); ?>
            <label class="ws-field"><span><?php esc_html_e('Nom','winshirt'); ?> *</span><input type="text" name="ws_name" required></label>
            <label class="ws-field"><span><?php esc_html_e('Email','winshirt'); ?> *</span><input type="email" name="ws_email" required></label>
            <label class="ws-field"><span><?php esc_html_e('N° de commande (facultatif)','winshirt'); ?></span><input type="text" name="ws_order"></label>
            <label class="ws-terms-accept"><input type="checkbox" name="ws_accept" required> <span><?php esc_html_e("J'accepte le règlement et la politique de confidentialité.",'winshirt'); ?></span></label>
            <button type="submit" name="ws_lottery_enter" value="1" class="ws-btn ws-btn-primary"><?php esc_html_e('Valider ma participation','winshirt'); ?></button>
        </form>
        <?php return ob_get_clean();
    }

    /* -------------------- Rendu carte réutilisable -------------------- */
    private function render_card(int $post_id, array $opts=[]): string {
        $title   = get_the_title($post_id);
        $url     = get_permalink($post_id);
        $thumb   = get_the_post_thumbnail($post_id,'large',['loading'=>'lazy']);
        $end     = get_post_meta($post_id,'_ws_lottery_end',true);
        $end_ts  = $end ? strtotime($end) : 0;
        $count   = (int) get_post_meta($post_id,'_ws_lottery_count',true);
        $goal    = (int) get_post_meta($post_id,'_ws_lottery_goal',true);
        $value   = (string) get_post_meta($post_id,'_ws_lottery_value',true);
        $feat    = get_post_meta($post_id,'_ws_lottery_featured',true)==='yes';

        $show_timer = ! empty($opts['show_timer']);
        $show_count = ! empty($opts['show_count']);

        ob_start(); ?>
        <article class="ws-card-mini">
            <a class="ws-card-mini-media" href="<?php echo esc_url($url); ?>">
                <?php echo $thumb ?: '<div class="ws-ph"></div>'; ?>
                <?php if ($feat): ?><span class="ws-mini-badge ws-badge-featured"><?php esc_html_e('En vedette','winshirt'); ?></span><?php endif; ?>
            </a>
            <div class="ws-card-mini-body">
                <h3 class="ws-card-mini-title"><a href="<?php echo esc_url($url); ?>"><?php echo esc_html($title); ?></a></h3>
                <?php if ($value): ?><div class="ws-mini-value"><?php echo esc_html(sprintf(__('Valeur: %s','winshirt'),$value)); ?></div><?php endif; ?>
                <?php if ($show_timer && $end_ts): ?>
                    <div class="ws-mini-timer" data-end="<?php echo (int)$end_ts; ?>"><span data-u="d">--</span>d <span data-u="h">--</span>h <span data-u="m">--</span>m <span data-u="s">--</span>s</div>
                <?php endif; ?>
                <?php if ($show_count): ?>
                    <div class="ws-mini-count"><?php echo esc_html(sprintf(_n('%d participant','%d participants',$count,'winshirt'),$count)); ?><?php if($goal): ?> — <?php echo esc_html(sprintf(__('Objectif: %d','winshirt'),$goal)); ?><?php endif; ?></div>
                <?php endif; ?>
                <a class="ws-btn ws-btn-sm" href="<?php echo esc_url($url); ?>"><?php esc_html_e('Participer','winshirt'); ?></a>
            </div>
        </article>
        <?php return ob_get_clean();
    }

    /* -------------------- Admin helpers & utils -------------------- */
    public function admin_cols(array $cols): array { $cols['ws_end']=__('Fin','winshirt'); $cols['ws_goal']=__('Objectif','winshirt'); $cols['ws_count']=__('Participants','winshirt'); return $cols; }
    public function admin_col_render(string $col, int $post_id): void {
        if ($col==='ws_end'){ $v=get_post_meta($post_id,'_ws_lottery_end',true); echo esc_html($v?date_i18n('d/m/Y H:i',strtotime($v)):'—'); }
        if ($col==='ws_goal'){ echo (int) get_post_meta($post_id,'_ws_lottery_goal',true); }
        if ($col==='ws_count'){ echo (int) get_post_meta($post_id,'_ws_lottery_count',true); }
    }
    /** Ajoute "ID: ####" dans les actions de ligne (comme produits) */
    public function add_row_action_id(array $actions, \WP_Post $post): array {
        if ( $post->post_type === 'winshirt_lottery' ) {
            $actions['ws_id'] = '<span class="row-id" style="opacity:.7">'.esc_html__('ID','winshirt').': '.$post->ID.'</span>';
        }
        return $actions;
    }

    private function fmt_dt_local($stored): string { if(empty($stored))return''; $ts=strtotime($stored); return $ts?date_i18n('Y-m-d\TH:i',$ts):''; }
    private function parse_dt_local(string $v): string { if(empty($v))return''; $ts=strtotime($v); return $ts?date_i18n('Y-m-d H:i:s',$ts):''; }
}
