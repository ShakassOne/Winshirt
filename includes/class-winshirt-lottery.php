<?php
namespace WinShirt;
if (!defined('ABSPATH')) { exit; }

/**
 * WinShirt – CPT Loteries + Shortcodes + Layouts (grid/masonry/slider/diagonal)
 * VERSION: 1.1.0 (diagonal revamp)
 */
class Lottery {
    private static $instance;
    public static function instance(){ return self::$instance ?: (self::$instance = new self()); }
    public static function register_post_type(){ self::instance()->register_cpt(); }

    public function init(){
        add_action('init',               [$this,'register_cpt']);
        add_action('add_meta_boxes',     [$this,'add_meta_boxes']);
        add_action('save_post_winshirt_lottery',[$this,'save_meta'],10,2);

        add_filter('manage_winshirt_lottery_posts_columns', [$this,'admin_cols']);
        add_action('manage_winshirt_lottery_posts_custom_column',[$this,'admin_col_render'],10,2);
        add_filter('post_row_actions',   [$this,'add_row_action_id'],10,2);

        add_action('template_redirect',  [$this,'handle_entry_post']);
        add_action('wp_ajax_ws_lottery_export_csv',[$this,'ajax_export_csv']);

        add_shortcode('winshirt_lotteries',    [$this,'sc_list']);
        add_shortcode('winshirt_lottery_card', [$this,'sc_card']);
        add_shortcode('winshirt_lottery_form', [$this,'sc_form']);

        add_action('wp_head',  [$this,'inline_css']);
        add_action('wp_footer',[$this,'inline_js']);
    }

    /* ================= CPT / Metas ================= */

    public function register_cpt(){
        register_post_type('winshirt_lottery',[
            'labels'=>[
                'name'=>__('Loteries','winshirt'),
                'singular_name'=>__('Loterie','winshirt'),
                'add_new_item'=>__('Ajouter une loterie','winshirt'),
                'edit_item'=>__('Modifier la loterie','winshirt'),
                'all_items'=>__('Toutes les loteries','winshirt'),
            ],
            'public'=>true,
            'publicly_queryable'=>true,
            'query_var'=>'winshirt_lottery',
            'has_archive'=>true,
            'rewrite'=>['slug'=>'loteries','with_front'=>false],
            'menu_icon'=>'dashicons-tickets-alt',
            'supports'=>['title','editor','thumbnail','excerpt','author'],
            'show_in_rest'=>true,
        ]);
    }

    public function add_meta_boxes(){
        add_meta_box('ws_lottery_details',__('Détails de la loterie','winshirt'),[$this,'mb_details'],'winshirt_lottery','normal','high');
        add_meta_box('ws_lottery_participants',__('Tickets & entrées','winshirt'),[$this,'mb_participants'],'winshirt_lottery','normal','default');
    }

    public function mb_details(\WP_Post $post){
        wp_nonce_field('ws_lottery_save','ws_lottery_nonce');
        $start=get_post_meta($post->ID,'_ws_lottery_start',true);
        $end  =get_post_meta($post->ID,'_ws_lottery_end',true);
        $goal =(int)get_post_meta($post->ID,'_ws_lottery_goal',true);
        $value=(string)get_post_meta($post->ID,'_ws_lottery_value',true);
        $terms=(string)get_post_meta($post->ID,'_ws_lottery_terms_url',true);
        $feat =get_post_meta($post->ID,'_ws_lottery_featured',true)==='yes';
        $product=(int)get_post_meta($post->ID,'_ws_lottery_product_id',true);
        $products=function_exists('wc_get_products')? wc_get_products(['status'=>'publish','limit'=>200,'orderby'=>'title','order'=>'ASC']) : [];
        ?>
        <table class="form-table">
            <tr><th><label for="ws_lottery_start"><?php esc_html_e('Début','winshirt'); ?></label></th>
                <td><input type="datetime-local" id="ws_lottery_start" name="ws_lottery_start" value="<?php echo esc_attr($this->fmt_dt_local($start)); ?>"></td></tr>
            <tr><th><label for="ws_lottery_end"><?php esc_html_e('Fin','winshirt'); ?></label></th>
                <td><input type="datetime-local" id="ws_lottery_end" name="ws_lottery_end" value="<?php echo esc_attr($this->fmt_dt_local($end)); ?>"></td></tr>
            <tr><th><label for="ws_lottery_goal"><?php esc_html_e('Objectif (tickets)','winshirt'); ?></label></th>
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

    public function save_meta($post_id,\WP_Post $post){
        if(!isset($_POST['ws_lottery_nonce']) || !wp_verify_nonce($_POST['ws_lottery_nonce'],'ws_lottery_save')) return;
        if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if($post->post_type!=='winshirt_lottery') return;

        $start=sanitize_text_field($_POST['ws_lottery_start'] ?? '');
        $end  =sanitize_text_field($_POST['ws_lottery_end'] ?? '');
        $goal =(int)($_POST['ws_lottery_goal'] ?? 0);
        $value=sanitize_text_field($_POST['ws_lottery_value'] ?? '');
        $terms=esc_url_raw($_POST['ws_lottery_terms_url'] ?? '');
        $feat =isset($_POST['ws_lottery_featured'])? 'yes':'no';
        $product=(int)($_POST['ws_lottery_product_id'] ?? 0);

        update_post_meta($post_id,'_ws_lottery_start',$this->parse_dt_local($start));
        update_post_meta($post_id,'_ws_lottery_end',$this->parse_dt_local($end));
        update_post_meta($post_id,'_ws_lottery_goal',max(0,$goal));
        update_post_meta($post_id,'_ws_lottery_value',$value);
        update_post_meta($post_id,'_ws_lottery_terms_url',$terms);
        update_post_meta($post_id,'_ws_lottery_featured',$feat);
        update_post_meta($post_id,'_ws_lottery_product_id',$product);

        if(get_post_meta($post_id,'_ws_lottery_participants',true)==='') update_post_meta($post_id,'_ws_lottery_participants',[]);
        if(get_post_meta($post_id,'_ws_lottery_count',true)==='')        update_post_meta($post_id,'_ws_lottery_count',0);
    }

    public function mb_participants(\WP_Post $post){
        $rows=(array)get_post_meta($post->ID,'_ws_lottery_participants',true);
        $count=(int)get_post_meta($post->ID,'_ws_lottery_count',true); ?>
        <p><b><?php esc_html_e('Total tickets :','winshirt'); ?></b> <?php echo (int)$count; ?></p>
        <p><a href="#" class="button" id="ws-export-csv" data-id="<?php echo (int)$post->ID; ?>"><?php esc_html_e('Exporter CSV','winshirt'); ?></a></p>
        <table class="widefat striped">
            <thead><tr><th>Date</th><th>Nom</th><th>Email</th><th>Commande</th><th>Tickets</th><th>IP</th><th>Source</th></tr></thead>
            <tbody>
            <?php if(empty($rows)): ?>
                <tr><td colspan="7"><?php esc_html_e('Aucune entrée pour le moment.','winshirt'); ?></td></tr>
            <?php else: foreach(array_reverse($rows) as $r): ?>
                <tr>
                    <td><?php echo esc_html($r['date'] ?? ''); ?></td>
                    <td><?php echo esc_html($r['name'] ?? ''); ?></td>
                    <td><?php echo esc_html($r['email'] ?? ''); ?></td>
                    <td><?php echo esc_html($r['order'] ?? ''); ?></td>
                    <td><?php echo isset($r['tickets'])?(int)$r['tickets']:0; ?></td>
                    <td><?php echo esc_html($r['ip'] ?? ''); ?></td>
                    <td><?php echo esc_html($r['source'] ?? ''); ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        <script>
        (function(){
            var b=document.getElementById('ws-export-csv'); if(!b) return;
            b.addEventListener('click',function(e){
                e.preventDefault();
                var id=this.getAttribute('data-id')||'0';
                var url=(window.ajaxurl||'<?php echo esc_js(admin_url('admin-ajax.php')); ?>')+'?action=ws_lottery_export_csv&post_id='+encodeURIComponent(id)+'&_wpnonce=<?php echo wp_create_nonce('ws_lottery_csv'); ?>';
                window.location.href=url;
            });
        })();
        </script>
        <?php
    }

    /* ============ POST mini-form (1 ticket) ============ */

    public function handle_entry_post(){
        if(!isset($_POST['ws_lottery_enter'])) return;
        if(!isset($_POST['ws_lottery_nonce']) || !wp_verify_nonce($_POST['ws_lottery_nonce'],'ws_lottery_enter')) return;

        $post_id=(int)($_POST['ws_lottery_id'] ?? 0);
        $name=sanitize_text_field($_POST['ws_name'] ?? '');
        $email=sanitize_email($_POST['ws_email'] ?? '');
        $order=sanitize_text_field($_POST['ws_order'] ?? '');
        $accept=isset($_POST['ws_accept']);

        if(!$post_id || get_post_type($post_id)!=='winshirt_lottery') return;
        if(empty($name)||empty($email)||!$accept) return;

        $end=get_post_meta($post_id,'_ws_lottery_end',true);
        if($end && strtotime($end) < current_time('timestamp')){
            wp_safe_redirect(add_query_arg('ws_lottery','ended',get_permalink($post_id))); exit;
        }

        $rows=(array)get_post_meta($post_id,'_ws_lottery_participants',true);
        $tickets_added=1;
        $previous_total=(int)get_post_meta($post_id,'_ws_lottery_count',true);

        $rows[]=[
            'date'=>date_i18n('Y-m-d H:i:s'),
            'name'=>$name,'email'=>$email,'order'=>$order,
            'tickets'=>$tickets_added,'ip'=>($_SERVER['REMOTE_ADDR'] ?? ''),'source'=>'form',
        ];
        $new_total=$previous_total+$tickets_added;

        update_post_meta($post_id,'_ws_lottery_participants',$rows);
        update_post_meta($post_id,'_ws_lottery_count',$new_total);

        $from=$previous_total+1; $to=$new_total;
        $subject=sprintf(__('Confirmation de participation : %s','winshirt'), get_the_title($post_id));
        $body=$this->email_tpl($post_id,$name,$tickets_added,$from,$to);
        wp_mail($email,$subject,$body,['Content-Type: text/html; charset=UTF-8']);

        wp_safe_redirect(add_query_arg('ws_lottery','ok',get_permalink($post_id))); exit;
    }

    private function email_tpl($post_id,$name,$tickets,$from,$to){
        $site=wp_specialchars_decode(get_bloginfo('name'),ENT_QUOTES);
        $title=get_the_title($post_id);
        $url=get_permalink($post_id);
        $range=($from===$to)? '#'.$from : '#'.$from.' '.__('à','winshirt').' #'.$to;
        ob_start(); ?>
        <div style="font-family:Arial,Helvetica,sans-serif;max-width:560px;margin:0 auto;padding:16px;border:1px solid #eee;border-radius:8px">
            <h2 style="margin:0 0 10px 0"><?php echo esc_html($site); ?></h2>
            <p><?php printf(esc_html__('Bonjour %s,','winshirt'), esc_html($name)); ?></p>
            <p><?php printf(esc_html__("Votre participation à la loterie « %s » est confirmée.",'winshirt'), esc_html($title)); ?></p>
            <p><?php printf(esc_html__('Tickets obtenus : %1$d (%2$s).','winshirt'), (int)$tickets, esc_html($range)); ?></p>
            <p><a href="<?php echo esc_url($url); ?>" style="display:inline-block;padding:10px 16px;background:#111;color:#fff;text-decoration:none;border-radius:6px"><?php esc_html_e('Voir la loterie','winshirt'); ?></a></p>
        </div>
        <?php return ob_get_clean();
    }

    /* ================= Shortcodes ================= */

    public function sc_list($atts=[]){
        $a=shortcode_atts([
            'status'=>'active','featured'=>'0','limit'=>'12',
            'layout'=>'grid','columns'=>'3',
            'show_timer'=>'1','show_count'=>'1',
            'autoplay'=>'1','interval'=>'4000',
        ],$atts,'winshirt_lotteries');

        $now=current_time('timestamp');
        $q=new \WP_Query([
            'post_type'=>'winshirt_lottery','posts_per_page'=>(int)$a['limit'],
            'orderby'=>'date','order'=>'DESC',
        ]);

        $layout=in_array($a['layout'],['grid','masonry','slider','diagonal'],true)? $a['layout']:'grid';
        $cols=in_array((int)$a['columns'],[2,3,4],true)? (int)$a['columns']:3;

        $classes=['ws-lottery-list','layout-'.$layout,'cols-'.$cols];
        if($layout==='grid')     $classes[]='ws-grid';
        if($layout==='masonry')  $classes[]='ws-masonry';
        if($layout==='slider')   $classes[]='ws-slider';
        if($layout==='diagonal') $classes[]='ws-diagonal';

        $attrs='';
        if(in_array($layout,['slider','diagonal'],true)){
            $attrs.=' data-columns="'.(int)$cols.'" data-autoplay="'.(int)$a['autoplay'].'" data-interval="'.(int)$a['interval'].'"';
        }

        ob_start();
        if(in_array($layout,['slider','diagonal'],true)) echo '<div class="ws-slider-wrap">';
        echo '<div class="'.esc_attr(implode(' ',$classes)).'"'.$attrs.'>';

        if($q->have_posts()){
            $printed=0; $i=0;
            while($q->have_posts()){ $q->the_post();
                $id=get_the_ID();
                $end=get_post_meta($id,'_ws_lottery_end',true);
                $f=get_post_meta($id,'_ws_lottery_featured',true)==='yes';

                $ok=true;
                if($a['featured']==='1' && !$f) $ok=false;
                if($a['status']==='active')   $ok=$end? (strtotime($end)>= $now):$ok;
                if($a['status']==='finished') $ok=$end && (strtotime($end)<  $now);
                if($a['status']==='all')      $ok=true;
                if(!$ok) continue;

                $card=$this->render_card($id,[
                    'show_timer'=>$a['show_timer']==='1',
                    'show_count'=>$a['show_count']==='1',
                ]);
                if($layout==='diagonal'){ $card=preg_replace('/^<article /','<article data-index="'.(int)$i.'" ',$card); }
                echo $card;
                $i++; $printed++;
            }
            wp_reset_postdata();
            if($printed===0) echo '<p>'.esc_html__('Aucune loterie disponible.','winshirt').'</p>';
        }else{
            echo '<p>'.esc_html__('Aucune loterie disponible.','winshirt').'</p>';
        }
        echo '</div>';
        if(in_array($layout,['slider','diagonal'],true)){
            echo '<button class="ws-nav ws-prev" aria-label="Précédent">‹</button>';
            echo '<button class="ws-nav ws-next" aria-label="Suivant">›</button>';
            echo '<div class="ws-dots" aria-hidden="true"></div>';
            echo '</div>';
        }
        return ob_get_clean();
    }

    public function sc_card($atts=[]){
        $a=shortcode_atts(['id'=>'0','show_timer'=>'1','show_count'=>'1'],$atts,'winshirt_lottery_card');
        $id=(int)$a['id']; if(!$id || get_post_type($id)!=='winshirt_lottery') return '';
        return $this->render_card($id,['show_timer'=>$a['show_timer']==='1','show_count'=>$a['show_count']==='1']);
    }

    public function sc_form($atts=[]){
        $a=shortcode_atts(['id'=>'0'],$atts,'winshirt_lottery_form');
        $id=(int)$a['id']; if(!$id || get_post_type($id)!=='winshirt_lottery') return '';
        ob_start(); ?>
        <form class="wsl-form" method="post">
            <input type="hidden" name="ws_lottery_id" value="<?php echo (int)$id; ?>">
            <?php wp_nonce_field('ws_lottery_enter','ws_lottery_nonce'); ?>
            <label class="wsl-field"><span><?php esc_html_e('Nom','winshirt'); ?> *</span><input type="text" name="ws_name" required></label>
            <label class="wsl-field"><span><?php esc_html_e('Email','winshirt'); ?> *</span><input type="email" name="ws_email" required></label>
            <label class="wsl-field"><span><?php esc_html_e('N° de commande (facultatif)','winshirt'); ?></span><input type="text" name="ws_order"></label>
            <label class="wsl-terms"><input type="checkbox" name="ws_accept" required> <span><?php esc_html_e("J'accepte le règlement et la politique de confidentialité.",'winshirt'); ?></span></label>
            <button type="submit" name="ws_lottery_enter" value="1" class="wsl-btn wsl-btn-primary"><?php esc_html_e('Valider (1 ticket)','winshirt'); ?></button>
        </form>
        <?php return ob_get_clean();
    }

    /* ============== Rendu carte ============== */

    private function render_card($post_id,$opts=[]){
        $title=get_the_title($post_id);
        $url=get_permalink($post_id);
        $thumb=get_the_post_thumbnail($post_id,'large',[
            'loading'=>'lazy','class'=>'wsl-img','decoding'=>'async','alt'=>esc_attr($title),
        ]);

        $end=get_post_meta($post_id,'_ws_lottery_end',true);
        $end_ts=$end? strtotime($end):0;
        $tickets=(int)get_post_meta($post_id,'_ws_lottery_count',true);
        $goal=(int)get_post_meta($post_id,'_ws_lottery_goal',true);
        $value=(string)get_post_meta($post_id,'_ws_lottery_value',true);
        $feat=get_post_meta($post_id,'_ws_lottery_featured',true)==='yes';

        $show_timer=!empty($opts['show_timer']);
        $show_count=!empty($opts['show_count']);

        ob_start(); ?>
        <article class="wsl-card" tabindex="0">
            <a class="wsl-link" href="<?php echo esc_url($url); ?>" aria-label="<?php echo esc_attr($title); ?>">
                <figure class="wsl-figure">
                    <?php echo $thumb ?: '<div class="wsl-img wsl-ph"></div>'; ?>
                    <?php if($feat): ?><span class="wsl-badge"><?php esc_html_e('En vedette','winshirt'); ?></span><?php endif; ?>

                    <figcaption class="wsl-overlay">
                        <div class="wsl-ov-inner">
                            <h3 class="wsl-title"><?php echo esc_html($title); ?></h3>
                            <?php if($value): ?><p class="wsl-line"><?php echo esc_html(sprintf(__('Valeur: %s','winshirt'),$value)); ?></p><?php endif; ?>
                            <?php if($show_timer && $end_ts): ?>
                                <p class="wsl-line wsl-timer" data-end="<?php echo (int)$end_ts; ?>">
                                    <span data-u="d">--</span> <?php esc_html_e('j','winshirt'); ?>
                                    <span data-u="h">--</span> <?php esc_html_e('h','winshirt'); ?>
                                    <span data-u="m">--</span> <?php esc_html_e('m','winshirt'); ?>
                                    <span data-u="s">--</span> <?php esc_html_e('s','winshirt'); ?>
                                </p>
                            <?php endif; ?>
                            <?php if($show_count): ?>
                                <p class="wsl-line"><?php echo esc_html(sprintf(_n('%d ticket — Objectif: %d','%d tickets — Objectif: %d',$tickets,'winshirt'),$tickets,$goal)); ?></p>
                            <?php endif; ?>
                            <p class="wsl-line wsl-date"><?php echo $end_ts? esc_html(sprintf(__('Tirage le %s','winshirt'), date_i18n('d/m/Y',$end_ts))) : esc_html__('Date de tirage à venir','winshirt'); ?></p>
                            <span class="wsl-btn-ghost"><?php esc_html_e('Participer','winshirt'); ?></span>
                        </div>
                    </figcaption>
                </figure>
            </a>
        </article>
        <?php
        return ob_get_clean();
    }

    /* ============== Admin list helpers ============== */

    public function admin_cols($c){ $c['ws_end']=__('Fin','winshirt'); $c['ws_goal']=__('Objectif','winshirt'); $c['ws_count']=__('Tickets','winshirt'); return $c; }
    public function admin_col_render($col,$id){
        if($col==='ws_end'){ $v=get_post_meta($id,'_ws_lottery_end',true); echo esc_html($v? date_i18n('d/m/Y H:i',strtotime($v)):'—'); }
        if($col==='ws_goal'){ echo (int)get_post_meta($id,'_ws_lottery_goal',true); }
        if($col==='ws_count'){ echo (int)get_post_meta($id,'_ws_lottery_count',true); }
    }
    public function add_row_action_id($actions,\WP_Post $post){
        if($post->post_type==='winshirt_lottery'){ $actions['ws_id']='<span class="row-id" style="opacity:.7">'.esc_html__('ID','winshirt').': '.$post->ID.'</span>'; }
        return $actions;
    }

    /* ============== Export CSV ============== */

    public function ajax_export_csv(){
        check_admin_referer('ws_lottery_csv');
        if(!current_user_can('edit_posts')) wp_die('forbidden','',[ 'response'=>403 ]);
        $post_id=(int)($_GET['post_id']??0);
        if(!$post_id || get_post_type($post_id)!=='winshirt_lottery') wp_die('invalid','',[ 'response'=>400 ]);
        $rows=(array)get_post_meta($post_id,'_ws_lottery_participants',true);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="lottery-'.$post_id.'-tickets.csv"');
        $out=fopen('php://output','w'); fputcsv($out,['date','name','email','order','tickets','ip','source']);
        foreach($rows as $r){ fputcsv($out,[$r['date']??'',$r['name']??'',$r['email']??'',$r['order']??'',$r['tickets']??0,$r['ip']??'',$r['source']??'']); }
        fclose($out); exit;
    }

    /* ============== Helpers ============== */

    private function fmt_dt_local($stored){ if(empty($stored)) return ''; $ts=strtotime($stored); return $ts? date_i18n('Y-m-d\TH:i',$ts):''; }
    private function parse_dt_local($v){ if(empty($v)) return ''; $ts=strtotime($v); return $ts? date_i18n('Y-m-d H:i:s',$ts):''; }

    /* ============== CSS inline (inclut diagonal refondu) ============== */

    public function inline_css(){ ?>
        <style id="winshirt-lottery-cards">
            /* GRID */
            .ws-lottery-list{margin:10px auto;gap:18px}
            .ws-lottery-list.ws-grid{display:grid}
            .ws-lottery-list.ws-grid.cols-2{grid-template-columns:repeat(2,minmax(0,1fr))}
            .ws-lottery-list.ws-grid.cols-3{grid-template-columns:repeat(3,minmax(0,1fr))}
            .ws-lottery-list.ws-grid.cols-4{grid-template-columns:repeat(4,minmax(0,1fr))}
            /* MASONRY */
            .ws-lottery-list.ws-masonry{column-gap:18px}
            .ws-lottery-list.ws-masonry.cols-2{column-count:2}
            .ws-lottery-list.ws-masonry.cols-3{column-count:3}
            .ws-lottery-list.ws-masonry.cols-4{column-count:4}
            .ws-lottery-list.ws-masonry>.wsl-card{break-inside:avoid;display:inline-block;width:100%;margin:0 0 18px}

            /* SLIDER simples (scroll-snap) */
            .ws-slider-wrap{position:relative}
            .ws-lottery-list.ws-slider{display:flex;overflow:auto;gap:18px;scroll-snap-type:x mandatory;padding:4px 4px 8px 4px;scrollbar-width:none;-ms-overflow-style:none}
            .ws-lottery-list.ws-slider::-webkit-scrollbar{display:none}
            .ws-lottery-list.ws-slider>.wsl-card{min-width:calc(100%/3 - 12px);scroll-snap-align:start}
            .ws-lottery-list.ws-slider.cols-2>.wsl-card{min-width:calc(100%/2 - 9px)}
            .ws-lottery-list.ws-slider.cols-3>.wsl-card{min-width:calc(100%/3 - 12px)}
            .ws-lottery-list.ws-slider.cols-4>.wsl-card{min-width:calc(100%/4 - 13.5px)}
            .ws-nav{position:absolute;top:50%;transform:translateY(-50%);z-index:9;border:0;border-radius:999px;width:44px;height:44px;line-height:44px;text-align:center;font-size:22px;font-weight:700;background:#2b2b2b;color:#fff;opacity:.92;cursor:pointer;box-shadow:0 8px 24px rgba(0,0,0,.25)}
            .ws-prev{left:24px}.ws-next{right:24px}
            .ws-dots{display:flex;gap:8px;justify-content:center;margin-top:10px}
            .ws-dots button{width:8px;height:8px;border-radius:999px;border:0;background:#d2d2d2}
            .ws-dots button.is-active{background:#111}

            /* ===== DIAGONAL CAROUSEL revamp ===== */
            .ws-lottery-list.ws-diagonal{
                position:relative;
                height:clamp(320px, 58vh, 560px);
                overflow:hidden;
                perspective: 1400px;
                transform-style: preserve-3d;
                user-select:none;
            }
            .ws-diagonal > .wsl-card{
                position:absolute;
                left:50%; top:50%;
                width: clamp(300px, 46vw, 820px);
                transform-origin:center center;
                transition: transform .5s cubic-bezier(.2,.7,.2,1), opacity .4s ease, filter .4s ease;
                will-change: transform, opacity, filter;
                filter: drop-shadow(0 14px 40px rgba(0,0,0,.35));
                border-radius:18px; overflow:hidden;
                pointer-events:none;  /* réactivé pour l’active */
            }
            .ws-diagonal .wsl-card.is-active{
                pointer-events:auto;
                filter: drop-shadow(0 24px 60px rgba(0,0,0,.45));
            }

            /* Carte / image / overlay */
            .wsl-card{background:#000}
            .wsl-link{display:block;color:inherit;text-decoration:none}
            .wsl-figure{position:relative;margin:0;aspect-ratio:16/9;background:#0a0a0a;border-radius:18px;overflow:hidden}
            .wsl-img{width:100%;height:100%;object-fit:cover;display:block}
            .wsl-ph{background:linear-gradient(135deg,#111,#1f1f1f)}
            .wsl-badge{position:absolute;top:12px;left:12px;padding:6px 10px;border-radius:999px;background:#6d28d9;color:#fff;font-size:12px;font-weight:700;z-index:3}

            /* Overlay uniquement au survol/focus */
            .wsl-overlay{position:absolute;inset:0;display:flex;align-items:flex-end;justify-content:center;padding:22px;background:linear-gradient(180deg,rgba(0,0,0,0) 8%,rgba(0,0,0,.94) 82%);opacity:0;transform:translateY(8%);transition:opacity .25s ease, transform .25s ease}
            .wsl-card:hover .wsl-overlay,
            .wsl-card:focus .wsl-overlay,
            .wsl-card:focus-within .wsl-overlay{opacity:1;transform:translateY(0)}
            .wsl-ov-inner{color:#fff;text-align:center;max-width:90%}
            .wsl-title{margin:0 0 6px 0;font-size:clamp(18px,2.2vw,22px);line-height:1.25;font-weight:800;text-shadow:0 2px 10px rgba(0,0,0,.35)}
            .wsl-line{margin:0;font-size:clamp(13px,1.8vw,14px);color:#E6E6E6}
            .wsl-timer{font-family:ui-monospace,monospace}
            .wsl-btn-ghost{display:inline-block;margin-top:8px;border:1px solid rgba(255,255,255,.95);color:#fff;padding:8px 12px;border-radius:10px;font-weight:700;font-size:14px;background:transparent}

            /* NAV pour diagonal */
            .ws-slider-wrap .ws-nav{position:absolute;top:50%;transform:translateY(-50%);z-index:12}
            .ws-slider-wrap .ws-dots{position:absolute;left:0;right:0;bottom:10px;display:flex;gap:8px;justify-content:center;z-index:11}

            @media (max-width:1024px){
                .ws-lottery-list.ws-grid.cols-3{grid-template-columns:repeat(2,minmax(0,1fr))}
                .ws-lottery-list.ws-grid.cols-4{grid-template-columns:repeat(2,minmax(0,1fr))}
            }
            @media (max-width:640px){
                .ws-lottery-list.ws-grid{grid-template-columns:repeat(1,minmax(0,1fr)) !important}
                .ws-lottery-list.ws-masonry{column-count:1 !important}
            }
        </style>
    <?php }

    /* ============== JS inline (compteurs + sliders + diagonal) ============== */

    public function inline_js(){ ?>
        <script>
        (function(){
            /* ======= Compteurs ======= */
            function tick(){
                document.querySelectorAll('.wsl-timer').forEach(function(el){
                    var end=parseInt(el.getAttribute('data-end')||'0',10)*1000; if(!end) return;
                    var d=Math.max(0,end-Date.now()), s=Math.floor(d/1000), m=Math.floor(s/60), h=Math.floor(m/60), j=Math.floor(h/24);
                    s%=60; m%=60; h%=24;
                    el.querySelector('[data-u="d"]').textContent=j;
                    el.querySelector('[data-u="h"]').textContent=h;
                    el.querySelector('[data-u="m"]').textContent=m;
                    el.querySelector('[data-u="s"]').textContent=s;
                });
            }
            tick(); setInterval(tick,1000);

            /* ======= Slider simple (scroll-snap) ======= */
            document.querySelectorAll('.ws-lottery-list.ws-slider').forEach(function(track){
                var wrap=track.parentElement, prev=wrap.querySelector('.ws-prev'), next=wrap.querySelector('.ws-next'), dots=wrap.querySelector('.ws-dots');
                var cols=parseInt(track.getAttribute('data-columns')||'3',10);
                var autoplay=parseInt(track.getAttribute('data-autoplay')||'1',10)===1;
                var interval=parseInt(track.getAttribute('data-interval')||'4000',10);

                function pages(){ return Math.max(1, Math.ceil(track.scrollWidth/track.clientWidth)); }
                function buildDots(){ if(!dots) return; dots.innerHTML=''; for(var i=0;i<pages();i++){ var b=document.createElement('button'); if(i===0) b.className='is-active'; dots.appendChild(b);} }
                function setDot(){ if(!dots) return; var i=Math.round(track.scrollLeft/track.clientWidth); dots.querySelectorAll('button').forEach(function(b,idx){ b.classList.toggle('is-active', idx===i); }); }
                function scrollByPage(dir){ track.scrollTo({left: track.scrollLeft + dir*track.clientWidth, behavior:'smooth'}); setTimeout(setDot,350); }

                prev && prev.addEventListener('click',function(){ scrollByPage(-1); });
                next && next.addEventListener('click',function(){ scrollByPage(1);  });
                track.addEventListener('scroll', function(){ window.requestAnimationFrame(setDot); });
                buildDots(); setDot();

                var needAuto = track.querySelectorAll('.wsl-card').length > cols;
                var t=null; function start(){ if(!autoplay||!needAuto) return; stop(); t=setInterval(function(){ scrollByPage(1); }, interval); }
                function stop(){ if(t){ clearInterval(t); t=null; } }
                start(); track.addEventListener('mouseenter',stop); track.addEventListener('mouseleave',start);
                window.addEventListener('resize',function(){ buildDots(); setDot(); start(); });
            });

            /* ======= DIAGONAL CAROUSEL – refonte =======
             * - Carte active: droite (rotate/skew 0), zIndex haut, pointer-events ON
             * - Autres: rotation +/- 13°, décalage X/Y, scale, blur léger
             * - Navigation: molette, drag, flèches, dots, clavier, autoplay
             */
            document.querySelectorAll('.ws-lottery-list.ws-diagonal').forEach(function(scene){
                var cards=[].slice.call(scene.querySelectorAll('.wsl-card'));
                var wrap=scene.parentElement, prev=wrap.querySelector('.ws-prev'), next=wrap.querySelector('.ws-next'), dots=wrap.querySelector('.ws-dots');
                var autoplay=parseInt(scene.getAttribute('data-autoplay')||'1',10)===1;
                var interval=parseInt(scene.getAttribute('data-interval')||'4000',10);
                if(cards.length===0) return;

                var current=0;
                var ROT=13;              // rotation des cartes adjacentes
                var GAP_X=90;            // décalage horizontal
                var GAP_Y=42;            // décalage vertical
                var SCALE_STEP=0.10;     // réduction par niveau
                var FAR_HIDE=6;          // au delà -> fade

                function tf(el,rel){
                    // rel = index relatif: 0 (active), 1 (juste derrière), -1 (devant)
                    var sign = (rel<0)? -1 : 1;
                    var abs = Math.abs(rel);

                    var rot   = (rel===0)? 0 : sign*ROT;
                    var skew  = 0;
                    var scale = Math.max(0.5, 1 - abs*SCALE_STEP);
                    var tx    = rel*GAP_X;
                    var ty    = abs*GAP_Y;

                    el.style.transform = 'translate3d(calc(-50% + '+tx+'px), calc(-50% + '+ty+'px), 0) rotate('+rot+'deg) skewY('+skew+'deg) scale('+scale+')';
                    el.style.zIndex    = String(100 - abs);
                    el.style.opacity   = (abs>FAR_HIDE)? 0 : 1;
                    el.style.filter    = (abs===0)? 'drop-shadow(0 24px 60px rgba(0,0,0,.45))' : 'drop-shadow(0 14px 40px rgba(0,0,0,.35)) blur('+(abs>3?1.2:0)+'px)';
                    el.classList.toggle('is-active', rel===0);
                    el.style.pointerEvents = (rel===0)? 'auto':'none';
                    el.style.willChange = 'transform';
                }

                function layout(){
                    cards.forEach(function(el,idx){ tf(el, idx-current); });
                    setDots();
                }

                function buildDots(){
                    if(!dots) return;
                    dots.innerHTML='';
                    for(var i=0;i<cards.length;i++){
                        var b=document.createElement('button'); if(i===current) b.className='is-active';
                        (function(i2){ b.addEventListener('click',function(){ current=i2; layout(); }); })(i);
                        dots.appendChild(b);
                    }
                }
                function setDots(){
                    if(!dots) return; var bs=dots.querySelectorAll('button');
                    bs.forEach(function(b,i){ b.classList.toggle('is-active', i===current); });
                }

                function go(d){ current=(current+d+cards.length)%cards.length; layout(); }
                prev && prev.addEventListener('click',function(){ go(-1); });
                next && next.addEventListener('click',function(){ go(+1); });

                // Clavier
                wrap.setAttribute('tabindex','0');
                wrap.addEventListener('keydown',function(e){
                    if(e.key==='ArrowLeft'){ e.preventDefault(); go(-1); }
                    if(e.key==='ArrowRight'){ e.preventDefault(); go(+1); }
                });

                // Molette (throttle)
                var wheelLock=false;
                scene.addEventListener('wheel',function(e){
                    e.preventDefault();
                    if(wheelLock) return; wheelLock=true;
                    go( e.deltaY>0 ? +1 : -1 );
                    setTimeout(function(){ wheelLock=false; }, 380);
                }, {passive:false});

                // Drag souris / tactile
                var startX=0, dragging=false;
                function onDown(e){ dragging=true; startX=(e.touches? e.touches[0].clientX : e.clientX); }
                function onMove(e){
                    if(!dragging) return;
                    var x=(e.touches? e.touches[0].clientX : e.clientX);
                    var dx=x-startX;
                    if(Math.abs(dx)>40){ go(dx<0 ? +1 : -1); dragging=false; }
                }
                function onUp(){ dragging=false; }
                scene.addEventListener('mousedown',onDown);
                scene.addEventListener('mousemove',onMove);
                document.addEventListener('mouseup',onUp);
                scene.addEventListener('touchstart',onDown,{passive:true});
                scene.addEventListener('touchmove',onMove,{passive:true});
                scene.addEventListener('touchend',onUp);

                // Autoplay
                var needAuto = cards.length > 1;
                var t=null; function start(){ if(!autoplay||!needAuto) return; stop(); t=setInterval(function(){ go(+1); }, interval); }
                function stop(){ if(t){ clearInterval(t); t=null; } }
                start(); wrap.addEventListener('mouseenter',stop); wrap.addEventListener('mouseleave',start);

                buildDots(); layout();
                window.addEventListener('resize', layout);
            });
        })();
        </script>
    <?php }
}
