<?php
/**
 * WinShirt - Simulateur de Scénarios (PAGE COMPLÈTE)
 * - Ajoute un menu top-level "Simulateur" (slug: winshirt-simulator) + alias sous "WinShirt" si présent.
 * - Formulaire (produits, lots, coûts), calcul rentabilité (tirage vs remboursement).
 * - Prépa du lot TOUJOURS incluse (dépensée dès le départ, comme demandé).
 * - Graphique Chart.js.
 * - Sauvegarde/chargement des scénarios (CPT ws_scenario).
 * Compat PHP ≥ 7.0 (pas de type hints retour, pas de fonctions exotiques).
 */
if ( ! defined('ABSPATH') ) exit;

if ( ! class_exists('WinShirt_Simulator') ) {

class WinShirt_Simulator {
    const MENU_SLUG = 'winshirt-simulator';
    const CPT       = 'ws_scenario';

    public function __construct() {
        add_action('init', array($this,'register_cpt'));
        add_action('admin_menu', array($this,'add_menu'));
        add_action('admin_enqueue_scripts', array($this,'enqueue'));
    }

    public function register_cpt() {
        register_post_type(self::CPT, array(
            'labels' => array(
                'name'          => 'Scénarios',
                'singular_name' => 'Scénario',
                'menu_name'     => 'Scénarios',
            ),
            'public'       => false,
            'show_ui'      => false,
            'show_in_menu' => false,
            'supports'     => array('title'),
        ));
    }

    public function add_menu() {
        // 1) Toujours un top-level garanti (évite toute 404 d’admin)
        add_menu_page(
            'Simulateur WinShirt',            // Page title
            'Simulateur',                     // Menu title
            'manage_options',                 // Capability
            self::MENU_SLUG,                  // Slug
            array($this,'render_admin_page'), // Callback
            'dashicons-chart-area',           // Icon
            56                                // Position
        );

        // 2) Alias sous le parent "WinShirt" si présent (pratique si tu as déjà un menu parent)
        add_submenu_page(
            'winshirt',                       // parent slug (si inexistant, WP ignore simplement)
            'Simulateur',
            'Simulateur',
            'manage_options',
            self::MENU_SLUG,                  // même slug, donc les deux liens pointent vers la même page
            array($this,'render_admin_page')
        );
    }

    public function enqueue($hook) {
        if ( ! isset($_GET['page']) || $_GET['page'] !== self::MENU_SLUG ) return;

        wp_enqueue_media();
        wp_enqueue_script('chartjs','https://cdn.jsdelivr.net/npm/chart.js',array(), '4.4.1', true);

        // CSS inline minimal & propre
        $css = '
        .ws-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px;margin:16px 0;box-shadow:0 1px 2px rgba(0,0,0,.04)}
        .ws-grid{display:grid;grid-template-columns:repeat(12,1fr);gap:12px}
        .ws-col-1{grid-column:span 1}.ws-col-2{grid-column:span 2}.ws-col-3{grid-column:span 3}
        .ws-col-4{grid-column:span 4}.ws-col-6{grid-column:span 6}.ws-col-8{grid-column:span 8}.ws-col-12{grid-column:span 12}
        .ws-label{font-weight:600;margin-bottom:4px;display:block}
        .ws-input{width:100%;padding:8px;border:1px solid #d1d5db;border-radius:8px}
        .ws-checkbox{transform:scale(1.2);margin-right:6px}
        .ws-btn{background:#111827;color:#fff;border:none;border-radius:10px;padding:10px 16px;cursor:pointer}
        .ws-btn.secondary{background:#6b7280}
        .ws-tag{display:inline-block;background:#f3f4f6;border:1px solid #e5e7eb;border-radius:999px;padding:4px 10px;margin-right:6px}
        .ws-flex{display:flex;gap:8px;align-items:center}
        .ws-muted{color:#6b7280}
        table.ws{width:100%;border-collapse:collapse;margin-top:6px}
        table.ws th,table.ws td{border:1px solid #e5e7eb;padding:8px;text-align:right}
        table.ws th:first-child,table.ws td:first-child{text-align:left}
        ';
        add_action('admin_print_footer_scripts', function() use ($css){
            echo '<style id="winshirt-simulator-css">'.$css.'</style>';
        });

        // JS duplication + media picker
        $js = '(function($){
          function mediaPick($btn){
            $btn.off("click").on("click", function(e){
              e.preventDefault();
              var target = $(this).data("target");
              var frame = wp.media({title:"Choisir une image",multiple:false});
              frame.on("select", function(){
                var img = frame.state().get("selection").first().toJSON();
                $("#"+target).val(img.url);
              });
              frame.open();
            });
          }
          $(document).on("click",".ws-add-product", function(e){
            e.preventDefault(); $("#ws-products").append($("#ws-product-template").html());
          });
          $(document).on("click",".ws-add-prize", function(e){
            e.preventDefault(); $("#ws-prizes").append($("#ws-prize-template").html());
            mediaPick($(".ws-pick-media").last());
          });
          $(document).on("click",".ws-remove-row", function(e){
            e.preventDefault(); $(this).closest(".ws-row").remove();
          });
          $(function(){ $(".ws-pick-media").each(function(){ mediaPick($(this)); }); });
        })(jQuery);';
        wp_add_inline_script('chartjs', $js);
    }

    private function defaults() {
        return array(
            'tva' => 0.20,
            'shipping_ht' => 5.00,
            'refund_amount_ht' => 5.00,
            'refund_enabled' => 1,
            'draw_threshold' => 5000,
            'sacs_total_ht' => 360.00,
            'fix_structure' => 1000.00,
            'fix_com' => 3000.00,
            'fix_huissier' => 500.00,
            'fix_divers' => 500.00,
            'external_ref_area' => 310.8, // A5
            'external_ref_cost' => 1.50,  // A5
            'products' => array(array(
                'name'=>'T-shirt','price_ttc'=>20.00,'cost_garment_ht'=>3.20,
                'area_cm2'=>310.8,'cost_internal_cm2'=>0.003,'cost_external_cm2'=>0.0,
                'use_internal'=>1,'shipping_unit_ht'=>5.00,'mix'=>1.00,'image'=>''
            )),
            'prizes' => array(
                array('name'=>'Monster (moto)','value_ht'=>7000.0,'prep_ht'=>5000.0,'image'=>''),
                array('name'=>'Cartier Santos 100','value_ht'=>5000.0,'prep_ht'=>0.0,'image'=>''),
                array('name'=>'iPhone 15 Pro','value_ht'=>1500.0,'prep_ht'=>0.0,'image'=>''),
            ),
            'volumes' => array(3000,4000,5000),
            'scenario_title' => '',
            'save_scenario' => 0,
        );
    }

    private function num($v,$def=0.0){
        if (is_string($v)) $v = str_replace(',','.',$v);
        return is_numeric($v) ? (float)$v : (float)$def;
    }

    private function fetch_config_from_post() {
        $d = $this->defaults();
        $p = isset($_POST) ? wp_unslash($_POST) : array();
        $c = array();

        $c['scenario_title'] = isset($p['scenario_title']) ? sanitize_text_field($p['scenario_title']) : '';
        $c['save_scenario']  = !empty($p['save_scenario']) ? 1 : 0;

        $tva_in = isset($p['tva']) ? $this->num($p['tva'], $d['tva']*100) : $d['tva']*100;
        $c['tva']            = ($tva_in>1) ? ($tva_in/100.0) : $tva_in;
        $c['shipping_ht']    = $this->num(isset($p['shipping_ht'])?$p['shipping_ht']:$d['shipping_ht'], $d['shipping_ht']);
        $c['refund_amount_ht']= $this->num(isset($p['refund_amount_ht'])?$p['refund_amount_ht']:$d['refund_amount_ht'],$d['refund_amount_ht']);
        $c['refund_enabled'] = !empty($p['refund_enabled']) ? 1 : 0;
        $c['draw_threshold'] = isset($p['draw_threshold']) ? (int)$p['draw_threshold'] : $d['draw_threshold'];
        $c['sacs_total_ht']  = $this->num(isset($p['sacs_total_ht'])?$p['sacs_total_ht']:$d['sacs_total_ht'],$d['sacs_total_ht']);

        $c['fix_structure']  = $this->num(isset($p['fix_structure'])?$p['fix_structure']:$d['fix_structure'],$d['fix_structure']);
        $c['fix_com']        = $this->num(isset($p['fix_com'])?$p['fix_com']:$d['fix_com'],$d['fix_com']);
        $c['fix_huissier']   = $this->num(isset($p['fix_huissier'])?$p['fix_huissier']:$d['fix_huissier'],$d['fix_huissier']);
        $c['fix_divers']     = $this->num(isset($p['fix_divers'])?$p['fix_divers']:$d['fix_divers'],$d['fix_divers']);

        $c['external_ref_area'] = $this->num(isset($p['external_ref_area'])?$p['external_ref_area']:$d['external_ref_area'],$d['external_ref_area']);
        $c['external_ref_cost'] = $this->num(isset($p['external_ref_cost'])?$p['external_ref_cost']:$d['external_ref_cost'],$d['external_ref_cost']);

        // Produits
        $c['products'] = array();
        if ( ! empty($p['prod_name']) && is_array($p['prod_name']) ) {
            $n = count($p['prod_name']);
            for ($i=0; $i<$n; $i++){
                $c['products'][] = array(
                    'name'              => sanitize_text_field($p['prod_name'][$i]),
                    'price_ttc'         => $this->num(isset($p['prod_price_ttc'][$i])?$p['prod_price_ttc'][$i]:0,0),
                    'cost_garment_ht'   => $this->num(isset($p['prod_cost_vet_ht'][$i])?$p['prod_cost_vet_ht'][$i]:0,0),
                    'area_cm2'          => $this->num(isset($p['prod_area_cm2'][$i])?$p['prod_area_cm2'][$i]:0,0),
                    'cost_internal_cm2' => $this->num(isset($p['prod_cost_int_cm2'][$i])?$p['prod_cost_int_cm2'][$i]:0,0),
                    'cost_external_cm2' => $this->num(isset($p['prod_cost_ext_cm2'][$i])?$p['prod_cost_ext_cm2'][$i]:0,0),
                    'use_internal'      => !empty($p['prod_use_internal'][$i]) ? 1 : 0,
                    'shipping_unit_ht'  => $this->num(isset($p['prod_ship_ht'][$i])?$p['prod_ship_ht'][$i]:$c['shipping_ht'],$c['shipping_ht']),
                    'mix'               => $this->num(isset($p['prod_mix'][$i])?$p['prod_mix'][$i]:0,0),
                    'image'             => esc_url_raw(isset($p['prod_image'][$i])?$p['prod_image'][$i]:''),
                );
            }
        }

        // Lots
        $c['prizes'] = array();
        if ( ! empty($p['prize_name']) && is_array($p['prize_name']) ) {
            $n = count($p['prize_name']);
            for ($i=0; $i<$n; $i++){
                $c['prizes'][] = array(
                    'name'     => sanitize_text_field($p['prize_name'][$i]),
                    'value_ht' => $this->num(isset($p['prize_value_ht'][$i])?$p['prize_value_ht'][$i]:0,0),
                    'prep_ht'  => $this->num(isset($p['prize_prep_ht'][$i])?$p['prize_prep_ht'][$i]:0,0),
                    'image'    => esc_url_raw(isset($p['prize_image'][$i])?$p['prize_image'][$i]:''),
                );
            }
        }

        // Volumes
        $c['volumes'] = array();
        if ( ! empty($p['volumes']) && is_array($p['volumes']) ) {
            foreach ($p['volumes'] as $vv) {
                $v = (int)$vv; if ($v>0) $c['volumes'][] = $v;
            }
        }
        if (empty($c['volumes'])) $c['volumes'] = $d['volumes'];

        return $c;
    }

    private function load_config_from_post_id($post_id) {
        $raw = get_post_meta($post_id, '_ws_scenario_config', true);
        $cfg = is_array($raw) ? $raw : array();
        return array_merge($this->defaults(), $cfg);
    }

    private function save_scenario($cfg, $results) {
        $title = !empty($cfg['scenario_title']) ? $cfg['scenario_title'] : ('Scénario '. current_time('Y-m-d H:i'));
        $pid = wp_insert_post(array(
            'post_type'=>self::CPT,'post_status'=>'publish','post_title'=>$title
        ));
        if ($pid && ! is_wp_error($pid)) {
            update_post_meta($pid, '_ws_scenario_config', $cfg);
            update_post_meta($pid, '_ws_scenario_results', $results);
            return (int)$pid;
        }
        return 0;
    }

    private function ext_cm2_default($cfg) {
        $area = max(0.0001, (float)$cfg['external_ref_area']);
        $cost = (float)$cfg['external_ref_cost'];
        return $cost / $area;
    }

    private function price_ht($ttc, $tva){ return (float)$ttc / (1.0 + (float)$tva); }

    private function calc_results($cfg) {
        $res = array();

        $tva            = (float)$cfg['tva'];
        $refund_amt     = (float)$cfg['refund_amount_ht'];
        $refund_enabled = !empty($cfg['refund_enabled']);
        $threshold      = (int)$cfg['draw_threshold'];

        $fixed_non_prize = (float)$cfg['sacs_total_ht']
                         + (float)$cfg['fix_structure']
                         + (float)$cfg['fix_com']
                         + (float)$cfg['fix_huissier']
                         + (float)$cfg['fix_divers'];

        $ext_cm2_default = $this->ext_cm2_default($cfg);

        // Normaliser mix
        $mix_sum = 0.0;
        foreach ($cfg['products'] as $p) { $mix_sum += (float)$p['mix']; }
        $mix_sum = max(0.0001, $mix_sum);

        foreach ($cfg['volumes'] as $volume) {
            $volume = (int)$volume; if ($volume<=0) continue;

            $ca_ht = 0.0;
            $var_total_internal = 0.0;
            $var_total_external = 0.0;

            foreach ($cfg['products'] as $p) {
                $share = (float)$p['mix'] / $mix_sum;
                $units = $volume * $share;

                $price_ht = $this->price_ht($p['price_ttc'], $tva);
                $garment  = (float)$p['cost_garment_ht'];
                $area     = (float)$p['area_cm2'];
                $int_cm2  = (float)$p['cost_internal_cm2'];
                $ext_cm2  = (isset($p['cost_external_cm2']) && $p['cost_external_cm2']>0) ? (float)$p['cost_external_cm2'] : $ext_cm2_default;
                $ship     = (float)$p['shipping_unit_ht'];

                $print_int = $area * $int_cm2;
                $print_ext = $area * $ext_cm2;

                $unit_var_int = $garment + $print_int + $ship;
                $unit_var_ext = $garment + $print_ext + $ship;

                $ca_ht              += $units * $price_ht;
                $var_total_internal += $units * $unit_var_int;
                $var_total_external += $units * $unit_var_ext;
            }

            foreach ($cfg['prizes'] as $prize) {
                $name  = $prize['name'];
                $value = (float)$prize['value_ht'];
                $prep  = (float)$prize['prep_ht']; // TOUJOURS incluse

                $refund_total = ($refund_enabled && $volume < $threshold) ? $refund_amt * $volume : 0.0;

                // Interne – Tirage
                $profit_internal_draw   = $ca_ht - $var_total_internal - $fixed_non_prize - $prep - $value;
                // Interne – Remboursement
                $profit_internal_refund = $ca_ht - $var_total_internal - $fixed_non_prize - $prep - $refund_total;
                // Externe – Tirage
                $profit_external_draw   = $ca_ht - $var_total_external - $fixed_non_prize - $prep - $value;
                // Externe – Remboursement
                $profit_external_refund = $ca_ht - $var_total_external - $fixed_non_prize - $prep - $refund_total;

                $res[] = array('volume'=>$volume,'prize'=>$name,'mode'=>'Tirage – Interne','ca_ht'=>$ca_ht,'var'=>$var_total_internal,'fixed'=>$fixed_non_prize,'prep'=>$prep,'lot'=>$value,'refund'=>0.0,'profit'=>$profit_internal_draw);
                $res[] = array('volume'=>$volume,'prize'=>$name,'mode'=>'Remboursement – Interne','ca_ht'=>$ca_ht,'var'=>$var_total_internal,'fixed'=>$fixed_non_prize,'prep'=>$prep,'lot'=>0.0,'refund'=>$refund_total,'profit'=>$profit_internal_refund);
                $res[] = array('volume'=>$volume,'prize'=>$name,'mode'=>'Tirage – Externe','ca_ht'=>$ca_ht,'var'=>$var_total_external,'fixed'=>$fixed_non_prize,'prep'=>$prep,'lot'=>$value,'refund'=>0.0,'profit'=>$profit_external_draw);
                $res[] = array('volume'=>$volume,'prize'=>$name,'mode'=>'Remboursement – Externe','ca_ht'=>$ca_ht,'var'=>$var_total_external,'fixed'=>$fixed_non_prize,'prep'=>$prep,'lot'=>0.0,'refund'=>$refund_total,'profit'=>$profit_external_refund);
            }
        }

        return $res;
    }

    public function render_admin_page() {
        if ( ! current_user_can('manage_options') ) return;

        $notice = '';
        $loaded_id = isset($_GET['load']) ? (int)$_GET['load'] : 0;

        if ( isset($_POST['ws_simulator_submit']) ) {
            check_admin_referer('ws_simulator_nonce');
            $cfg = $this->fetch_config_from_post();
            $results = $this->calc_results($cfg);
            if ( ! empty($cfg['save_scenario']) ) {
                $saved = $this->save_scenario($cfg, $results);
                if ($saved) $notice = '<div class="ws-card" style="border-color:#10b981"><strong>Scénario enregistré</strong> (ID: '.(int)$saved.')</div>';
            }
        } elseif ( $loaded_id ) {
            $cfg = $this->load_config_from_post_id($loaded_id);
            $results = get_post_meta($loaded_id, '_ws_scenario_results', true);
            if ( ! is_array($results) ) $results = array();
            $notice = '<div class="ws-card" style="border-color:#10b981"><strong>Scénario chargé</strong> (ID: '.(int)$loaded_id.')</div>';
        } else {
            $cfg = $this->defaults();
            $results = array();
        }

        $existing = get_posts(array('post_type'=>self::CPT,'posts_per_page'=>20,'orderby'=>'date','order'=>'DESC'));
        $chart = $this->build_chart_dataset($results);

        echo '<div class="wrap"><h1>WinShirt – Simulateur</h1>';
        if ($notice) echo $notice;

        echo '<form method="post">';
        wp_nonce_field('ws_simulator_nonce');

        // META
        echo '<div class="ws-card"><div class="ws-grid">';
        echo '<div class="ws-col-6"><label class="ws-label">Titre du scénario</label><input class="ws-input" type="text" name="scenario_title" value="'.esc_attr($cfg['scenario_title']).'" placeholder="Ex: Monster + Cartier + iPhone (été 2025)"></div>';
        echo '<div class="ws-col-6" style="display:flex;align-items:flex-end;gap:12px;">
                <label class="ws-flex"><input type="checkbox" name="save_scenario" value="1" '.checked(1,!empty($cfg['save_scenario']),false).' class="ws-checkbox">Sauvegarder</label>
              </div>';
        echo '</div></div>';

        // PARAMS
        echo '<div class="ws-card"><h2>Paramètres généraux</h2><div class="ws-grid">';
        $fields = array(
            array('TVA (%)','tva', $cfg['tva']*100, 'number','step="0.01"'),
            array('Frais de port par commande (HT)','shipping_ht',$cfg['shipping_ht'],'number','step="0.01"'),
            array('Remboursement si &lt; seuil (HT)','refund_amount_ht',$cfg['refund_amount_ht'],'number','step="0.01"'),
            array('Seuil de tirage (ventes)','draw_threshold',$cfg['draw_threshold'],'number',''),
            array('Sacs brandés – coût total (HT)','sacs_total_ht',$cfg['sacs_total_ht'],'number','step="0.01"'),
            array('Structure (HT)','fix_structure',$cfg['fix_structure'],'number','step="0.01"'),
            array('Communication (HT)','fix_com',$cfg['fix_com'],'number','step="0.01"'),
            array('Huissier (HT)','fix_huissier',$cfg['fix_huissier'],'number','step="0.01"'),
            array('Divers (HT)','fix_divers',$cfg['fix_divers'],'number','step="0.01"'),
        );
        foreach ($fields as $f) {
            echo '<div class="ws-col-4"><label class="ws-label">'.$f[0].'</label><input class="ws-input" type="'.$f[3].'" name="'.$f[1].'" value="'.esc_attr($f[1]==='tva' ? number_format((float)$f[2],2,'.','') : $f[2]).'" '.$f[4].'></div>';
        }
        echo '<div class="ws-col-12" style="margin-top:6px;"><label class="ws-flex"><input type="checkbox" name="refund_enabled" value="1" '.checked(1,!empty($cfg['refund_enabled']),false).' class="ws-checkbox">Activer le remboursement si ventes &lt; seuil</label></div>';
        echo '</div></div>';

        // Référence externe
        echo '<div class="ws-card"><h2>Référence impression externe</h2><div class="ws-grid">';
        echo '<div class="ws-col-6"><label class="ws-label">Surface de réf. (cm²) – A5=310.8</label><input class="ws-input" type="number" step="0.01" name="external_ref_area" value="'.esc_attr($cfg['external_ref_area']).'"></div>';
        echo '<div class="ws-col-6"><label class="ws-label">Coût de réf. (HT) – ex. 1.50€ pour A5</label><input class="ws-input" type="number" step="0.01" name="external_ref_cost" value="'.esc_attr($cfg['external_ref_cost']).'"></div>';
        echo '<div class="ws-col-12 ws-muted">Le coût externe au cm² est dérivé automatiquement : coût_réf / surface_réf.</div>';
        echo '</div></div>';

        // PRODUITS
        echo '<div class="ws-card"><h2>Produits</h2><div id="ws-products">';
        foreach ($cfg['products'] as $p) $this->render_product_row($p, $cfg);
        echo '</div><button class="ws-btn ws-add-product" type="button">+ Ajouter un produit</button>';
        echo '<template id="ws-product-template">'; $this->render_product_row(null, $cfg, true); echo '</template>';
        echo '</div>';

        // LOTS
        echo '<div class="ws-card"><h2>Lots</h2><div id="ws-prizes">';
        foreach ($cfg['prizes'] as $pr) $this->render_prize_row($pr);
        echo '</div><button class="ws-btn secondary ws-add-prize" type="button">+ Ajouter un lot</button>';
        echo '<template id="ws-prize-template">'; $this->render_prize_row(null, true); echo '</template>';
        echo '</div>';

        // VOLUMES
        echo '<div class="ws-card"><h2>Volumes</h2><div class="ws-grid">';
        $i=0; foreach ($cfg['volumes'] as $vol) {
            echo '<div class="ws-col-4"><label class="ws-label">Volume '.(++$i).'</label><input class="ws-input" type="number" name="volumes[]" value="'.esc_attr($vol).'"></div>';
        }
        echo '<div class="ws-col-4"><label class="ws-label">Volume personnalisé</label><input class="ws-input" type="number" name="volumes[]" value=""></div>';
        echo '</div></div>';

        echo '<p><button class="ws-btn" type="submit" name="ws_simulator_submit" value="1">Simuler</button></p>';

        // Résultats + Graph
        if ( ! empty($results) ) {
            $this->render_results($results);
            $this->render_chart($this->build_chart_dataset($results));
        }

        echo '</form>';

        // Liste scénarios
        echo '<div class="ws-card'><h2>Scénarios enregistrés</h2>';
        if ($existing) {
            echo '<table class="ws"><thead><tr><th>Titre</th><th>Date</th><th>Charger</th></tr></thead><tbody>';
            foreach ($existing as $post) {
                $url = admin_url('admin.php?page='.self::MENU_SLUG.'&load='.$post->ID);
                echo '<tr><td>'.esc_html($post->post_title).'</td><td>'.esc_html($post->post_date).'</td><td><a class="ws-btn secondary" href="'.esc_url($url).'">Charger</a></td></tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<div class="ws-muted">Aucun scénario enregistré pour le moment.</div>';
        }
        echo '</div>';

        echo '</div>';
    }

    private function render_product_row($p=null, $cfg=array(), $is_template=false) {
        $d = array(
            'name'=>'T-shirt','price_ttc'=>20.00,'cost_garment_ht'=>3.20,'area_cm2'=>310.8,
            'cost_internal_cm2'=>0.003,'cost_external_cm2'=>0.0,'use_internal'=>1,
            'shipping_unit_ht'=> isset($cfg['shipping_ht'])?$cfg['shipping_ht']:5.00,'mix'=>1.00,'image'=>''
        );
        if (is_array($p)) $d = array_merge($d,$p);

        $target = 'prod_img_'.uniqid();
        echo '<div class="ws-row ws-card" style="margin-top:12px;"><div class="ws-grid">';
        echo '<div class="ws-col-3"><label class="ws-label">Produit</label><input class="ws-input" name="prod_name[]" value="'.esc_attr($d['name']).'"></div>';
        echo '<div class="ws-col-2"><label class="ws-label">Prix TTC</label><input class="ws-input" type="number" step="0.01" name="prod_price_ttc[]" value="'.esc_attr($d['price_ttc']).'"></div>';
        echo '<div class="ws-col-2"><label class="ws-label">Coût vêtement HT</label><input class="ws-input" type="number" step="0.01" name="prod_cost_vet_ht[]" value="'.esc_attr($d['cost_garment_ht']).'"></div>';
        echo '<div class="ws-col-2"><label class="ws-label">Surface (cm²)</label><input class="ws-input" type="number" step="0.01" name="prod_area_cm2[]" value="'.esc_attr($d['area_cm2']).'"></div>';
        echo '<div class="ws-col-2"><label class="ws-label">Port unitaire HT</label><input class="ws-input" type="number" step="0.01" name="prod_ship_ht[]" value="'.esc_attr($d['shipping_unit_ht']).'"></div>';
        echo '<div class="ws-col-2"><label class="ws-label">Coût interne / cm²</label><input class="ws-input" type="number" step="0.0001" name="prod_cost_int_cm2[]" value="'.esc_attr($d['cost_internal_cm2']).'"></div>';
        echo '<div class="ws-col-2"><label class="ws-label">Coût externe / cm²</label><input class="ws-input" type="number" step="0.0001" name="prod_cost_ext_cm2[]" placeholder="auto" value="'.esc_attr($d['cost_external_cm2']).'"></div>';
        echo '<div class="ws-col-2" style="display:flex;align-items:flex-end;"><label class="ws-flex"><input class="ws-checkbox" type="checkbox" name="prod_use_internal[]" value="1" '.checked(1,!empty($d['use_internal']),false).'>Prod interne</label></div>';
        echo '<div class="ws-col-2"><label class="ws-label">Mix (0–1)</label><input class="ws-input" type="number" step="0.01" name="prod_mix[]" value="'.esc_attr($d['mix']).'"></div>';
        echo '<div class="ws-col-3"><label class="ws-label">Image</label><div class="ws-flex"><input class="ws-input" type="text" name="prod_image[]" id="'.$target.'" value="'.esc_attr($d['image']).'"><button class="ws-btn ws-pick-media" data-target="'.$target.'">Choisir</button></div></div>';
        echo '<div class="ws-col-1" style="display:flex;align-items:flex-end;justify-content:flex-end;"><button class="ws-btn secondary ws-remove-row">Suppr.</button></div>';
        echo '</div></div>';
    }

    private function render_prize_row($pr=null, $is_template=false) {
        $d = array('name'=>'Nouveau lot','value_ht'=>0.0,'prep_ht'=>0.0,'image'=>'');
        if (is_array($pr)) $d = array_merge($d,$pr);

        $target = 'prize_img_'.uniqid();
        echo '<div class="ws-row ws-card" style="margin-top:12px;"><div class="ws-grid">';
        echo '<div class="ws-col-4"><label class="ws-label">Nom du lot</label><input class="ws-input" name="prize_name[]" value="'.esc_attr($d['name']).'"></div>';
        echo '<div class="ws-col-3"><label class="ws-label">Valeur (HT)</label><input class="ws-input" type="number" step="0.01" name="prize_value_ht[]" value="'.esc_attr($d['value_ht']).'"></div>';
        echo '<div class="ws-col-3"><label class="ws-label">Prépa (HT)</label><input class="ws-input" type="number" step="0.01" name="prize_prep_ht[]" value="'.esc_attr($d['prep_ht']).'"></div>';
        echo '<div class="ws-col-2"><label class="ws-label">Image</label><div class="ws-flex"><input class="ws-input" id="'.$target.'" type="text" name="prize_image[]" value="'.esc_attr($d['image']).'"><button class="ws-btn ws-pick-media" data-target="'.$target.'">Choisir</button></div></div>';
        echo '<div class="ws-col-12" style="display:flex;justify-content:flex-end;"><button class="ws-btn secondary ws-remove_row ws-remove-row">Suppr.</button></div>';
        echo '</div></div>';
    }

    private function render_results($rows) {
        echo '<div class="ws-card"><h2>Résultats</h2>';
        echo '<table class="ws"><thead><tr>';
        $heads = array('Volume','Lot','Mode','CA HT','Coût variables','Fixes (hors lot)','Prépa','Lot','Remboursements','Résultat HT');
        foreach ($heads as $h) echo '<th>'.esc_html($h).'</th>';
        echo '</tr></thead><tbody>';
        foreach ($rows as $r) {
            $bg = $r['profit']>=0 ? ' style="background:#f7fef9;"' : ' style="background:#fff7f7;"';
            echo '<tr'.$bg.'>';
            echo '<td>'.number_format($r['volume'],0,',',' ').'</td>';
            echo '<td>'.esc_html($r['prize']).'</td>';
            echo '<td>'.esc_html($r['mode']).'</td>';
            echo '<td>'.number_format($r['ca_ht'],2,',',' ').' €</td>';
            echo '<td>'.number_format($r['var'],2,',',' ').' €</td>';
            echo '<td>'.number_format($r['fixed'],2,',',' ').' €</td>';
            echo '<td>'.number_format($r['prep'],2,',',' ').' €</td>';
            echo '<td>'.number_format($r['lot'],2,',',' ').' €</td>';
            echo '<td>'.number_format($r['refund'],2,',',' ').' €</td>';
            echo '<td><strong>'.number_format($r['profit'],2,',',' ').' €</strong></td>';
            echo '</tr>';
        }
        echo '</tbody></table></div>';
    }

    private function build_chart_dataset($rows) {
        // Regroupe par volume (Interne: Tirage vs Remboursement)
        $dataset = array();
        foreach ($rows as $r) {
            if (strpos($r['mode'], 'Interne') === false) continue;
            $k = $r['volume'];
            if ( ! isset($dataset[$k]) ) $dataset[$k] = array('draw'=>null,'refund'=>null);
            if (strpos($r['mode'], 'Tirage') !== false) $dataset[$k]['draw'] = $r['profit'];
            else $dataset[$k]['refund'] = $r['profit'];
        }
        ksort($dataset);
        return $dataset;
    }

    private function render_chart($chart) {
        if (empty($chart)) return;
        $labels = array(); $draw = array(); $refund = array();
        foreach ($chart as $vol=>$vals){ $labels[]=$vol; $draw[]=round(isset($vals['draw'])?$vals['draw']:0,2); $refund[]=round(isset($vals['refund'])?$vals['refund']:0,2); }

        echo '<div class="ws-card"><h2>Graphique (Interne)</h2><canvas id="wsChart" height="120"></canvas>';
        echo '<script>(function(){const ctx=document.getElementById("wsChart").getContext("2d");new Chart(ctx,{type:"bar",data:{labels:'.wp_json_encode($labels).',datasets:[{label:"Tirage (lot attribué)",data:'.wp_json_encode($draw).',borderWidth:1},{label:"Remboursement",data:'.wp_json_encode($refund).',borderWidth:1}]},options:{responsive:true,scales:{y:{beginAtZero:true}}}});})();</script>';
        echo '</div>';
    }
}

} // class guard

// Boot unique, admin-only
add_action('plugins_loaded', function(){ if ( is_admin() ) { new WinShirt_Simulator(); } });
