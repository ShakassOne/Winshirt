<?php
/**
 * WinShirt - Simulateur de Scénarios (BETA)
 * Fichier: admin/class-winshirt-simulator.php
 *
 * Ajoute une page "Simulateur" dans l’admin WordPress (menu WinShirt),
 * permet de saisir des paramètres (produits, lots, coûts), calcule la rentabilité
 * en modes "Tirage" (lot attribué) et "Remboursement" (pas de tirage),
 * et enregistre/relit des scénarios via un CPT `ws_scenario`.
 *
 * - Prépa du lot : TOUJOURS incluse dès le départ (comme demandé).
 * - Coûts: TVA, port, sacs (fixe), structure, com, huissier, divers.
 * - Produits: prix TTC, coût vêtement HT, cm² d’impression, coût interne/externe/cm², port unitaire HT, mix.
 * - Lots: nom, valeur (HT), prépa (HT), image.
 * - Volumes: 3000/4000/5000 (éditables).
 * - Graphique: Chart.js (résultats par volume et par lot, tirage vs remboursement).
 *
 * Sécurité : nonces, caps, sanitization (BETA).
 */

if ( ! defined('ABSPATH') ) exit;

if ( ! class_exists('WinShirt_Simulator') ) {

class WinShirt_Simulator {

    const MENU_SLUG = 'winshirt-simulator';
    const CPT       = 'ws_scenario';

    public function __construct() {
        add_action('init', [$this, 'register_cpt']);
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
    }

    public function register_cpt() {
        $labels = [
            'name'               => 'Scénarios',
            'singular_name'      => 'Scénario',
            'menu_name'          => 'Scénarios',
            'name_admin_bar'     => 'Scénario',
            'add_new'            => 'Ajouter',
            'add_new_item'       => 'Ajouter un scénario',
            'new_item'           => 'Nouveau scénario',
            'edit_item'          => 'Éditer le scénario',
            'view_item'          => 'Voir le scénario',
            'all_items'          => 'Tous les scénarios',
            'search_items'       => 'Chercher des scénarios',
            'not_found'          => 'Aucun scénario',
            'not_found_in_trash' => 'Aucun scénario dans la corbeille',
        ];
        $args = [
            'labels'             => $labels,
            'public'             => false,
            'show_ui'            => false, // géré par notre page custom
            'show_in_menu'       => false,
            'supports'           => ['title'],
            'capability_type'    => 'post',
        ];
        register_post_type(self::CPT, $args);
    }

    public function add_menu() {
        // Parent "WinShirt" doit déjà exister dans ton plugin principal
        add_submenu_page(
            'winshirt',                  // slug parent (menu WinShirt)
            'Simulateur',                // page title
            'Simulateur',                // menu title
            'manage_options',            // capability
            self::MENU_SLUG,            // menu slug
            [$this, 'render_admin_page'] // callback
        );
    }

    public function enqueue($hook) {
        if ( ! isset($_GET['page']) || $_GET['page'] !== self::MENU_SLUG ) return;

        // Media uploader (pour images produits/lots)
        wp_enqueue_media();

        // Chart.js
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js',
            [],
            '4.4.1',
            true
        );

        // Petites styles pour un rendu clean
        $css = '
        .ws-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px;margin:16px 0;box-shadow:0 1px 2px rgba(0,0,0,0.04)}
        .ws-grid{display:grid;grid-template-columns:repeat(12,1fr);gap:12px}
        .ws-col-4{grid-column:span 4}
        .ws-col-6{grid-column:span 6}
        .ws-col-8{grid-column:span 8}
        .ws-col-12{grid-column:span 12}
        .ws-label{font-weight:600;margin-bottom:4px;display:block}
        .ws-input{width:100%;padding:8px;border:1px solid #d1d5db;border-radius:8px}
        .ws-checkbox{transform:scale(1.2);margin-right:6px}
        .ws-btn{background:#111827;color:#fff;border:none;border-radius:10px;padding:10px 16px;cursor:pointer}
        .ws-btn.secondary{background:#6b7280}
        .ws-tag{display:inline-block;background:#f3f4f6;border:1px solid #e5e7eb;border-radius:999px;padding:4px 10px;margin-right:6px}
        .ws-flex{display:flex;gap:8px;align-items:center}
        .ws-ok{background:#ecfdf5;border:1px solid #10b981;color:#065f46;padding:10px;border-radius:10px}
        .ws-bad{background:#fef2f2;border:1px solid #ef4444;color:#7f1d1d;padding:10px;border-radius:10px}
        table.ws{width:100%;border-collapse:collapse}
        table.ws th, table.ws td{border:1px solid #e5e7eb;padding:8px;text-align:right}
        table.ws th:first-child, table.ws td:first-child{text-align:left}
        .ws-muted{color:#6b7280}
        .ws-img{width:56px;height:56px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb}
        ';
        wp_add_inline_style('wp-components', $css);

        // Small JS to add dynamic rows + media uploader
        $js = '
        (function($){
          function attachMedia($btn){
            $btn.on("click", function(e){
              e.preventDefault();
              var target = $(this).data("target");
              var frame = wp.media({ title:"Choisir une image", multiple:false });
              frame.on("select", function(){
                var img = frame.state().get("selection").first().toJSON();
                $("#"+target).val(img.url);
              });
              frame.open();
            });
          }
          $(document).on("click",".ws-add-product", function(e){
             e.preventDefault();
             var $tpl = $("#ws-product-template").html();
             $("#ws-products").append($tpl);
          });
          $(document).on("click",".ws-add-prize", function(e){
             e.preventDefault();
             var $tpl = $("#ws-prize-template").html();
             $("#ws-prizes").append($tpl);
             attachMedia($(".ws-pick-media").last());
          });
          $(document).on("click",".ws-remove-row", function(e){
             e.preventDefault();
             $(this).closest(".ws-row").remove();
          });
          $(function(){
            $(".ws-pick-media").each(function(){ attachMedia($(this)); });
          });
        })(jQuery);
        ';
        wp_add_inline_script('chartjs', $js);
    }

    private function defaults() : array {
        return [
            'tva'               => 0.20,
            'shipping_ht'       => 5.00,
            'refund_amount_ht'  => 5.00,
            'refund_enabled'    => 1,
            'draw_threshold'    => 5000,
            'sacs_total_ht'     => 360.00,
            'fix_structure'     => 1000.00,
            'fix_com'           => 3000.00,
            'fix_huissier'      => 500.00,
            'fix_divers'        => 500.00,
            // Coût externe dérivé par défaut: 1.50€ pour 310.8 cm² (A5)
            'external_ref_area' => 310.8,
            'external_ref_cost' => 1.50,
            'products' => [
                [
                    'name' => 'T-shirt',
                    'price_ttc' => 20.00,
                    'cost_garment_ht' => 3.20,
                    'area_cm2' => 310.8,
                    'cost_internal_cm2' => 0.003,
                    'cost_external_cm2' => null, // auto
                    'use_internal' => 1,
                    'shipping_unit_ht' => 5.00,
                    'mix' => 1.00,
                    'image' => ''
                ],
            ],
            'prizes' => [
                [ 'name'=>'Monster (moto)', 'value_ht'=>7000.0, 'prep_ht'=>5000.0, 'image'=>'' ],
                [ 'name'=>'Cartier Santos 100', 'value_ht'=>5000.0, 'prep_ht'=>0.0, 'image'=>'' ],
                [ 'name'=>'iPhone 15 Pro', 'value_ht'=>1500.0, 'prep_ht'=>0.0, 'image'=>'' ],
            ],
            'volumes' => [3000, 4000, 5000],
            'scenario_title' => '',
            'save_scenario'  => 0,
        ];
    }

    private function sanitize_float($v, $default=0.0) {
        if (is_string($v)) { $v = str_replace(',', '.', $v); }
        return is_numeric($v) ? (float)$v : (float)$default;
    }

    private function fetch_config_from_post() : array {
        $d = $this->defaults();
        $p = wp_unslash($_POST);

        $cfg = [];
        $cfg['scenario_title']   = sanitize_text_field($p['scenario_title'] ?? '');
        $cfg['save_scenario']    = !empty($p['save_scenario']) ? 1 : 0;

        $cfg['tva']              = $this->sanitize_float($p['tva'] ?? $d['tva']);
        $cfg['shipping_ht']      = $this->sanitize_float($p['shipping_ht'] ?? $d['shipping_ht']);
        $cfg['refund_amount_ht'] = $this->sanitize_float($p['refund_amount_ht'] ?? $d['refund_amount_ht']);
        $cfg['refund_enabled']   = !empty($p['refund_enabled']) ? 1 : 0;
        $cfg['draw_threshold']   = (int)($p['draw_threshold'] ?? $d['draw_threshold']);
        $cfg['sacs_total_ht']    = $this->sanitize_float($p['sacs_total_ht'] ?? $d['sacs_total_ht']);

        $cfg['fix_structure'] = $this->sanitize_float($p['fix_structure'] ?? $d['fix_structure']);
        $cfg['fix_com']       = $this->sanitize_float($p['fix_com'] ?? $d['fix_com']);
        $cfg['fix_huissier']  = $this->sanitize_float($p['fix_huissier'] ?? $d['fix_huissier']);
        $cfg['fix_divers']    = $this->sanitize_float($p['fix_divers'] ?? $d['fix_divers']);

        $cfg['external_ref_area'] = $this->sanitize_float($p['external_ref_area'] ?? $d['external_ref_area']);
        $cfg['external_ref_cost'] = $this->sanitize_float($p['external_ref_cost'] ?? $d['external_ref_cost']);

        // Products
        $cfg['products'] = [];
        if ( ! empty($p['prod_name']) && is_array($p['prod_name']) ) {
            $count = count($p['prod_name']);
            for ($i=0; $i<$count; $i++){
                $cfg['products'][] = [
                    'name'               => sanitize_text_field($p['prod_name'][$i]),
                    'price_ttc'          => $this->sanitize_float($p['prod_price_ttc'][$i] ?? 0),
                    'cost_garment_ht'    => $this->sanitize_float($p['prod_cost_vet_ht'][$i] ?? 0),
                    'area_cm2'           => $this->sanitize_float($p['prod_area_cm2'][$i] ?? 0),
                    'cost_internal_cm2'  => $this->sanitize_float($p['prod_cost_int_cm2'][$i] ?? 0),
                    'cost_external_cm2'  => $this->sanitize_float($p['prod_cost_ext_cm2'][$i] ?? 0),
                    'use_internal'       => !empty($p['prod_use_internal'][$i]) ? 1 : 0,
                    'shipping_unit_ht'   => $this->sanitize_float($p['prod_ship_ht'][$i] ?? $cfg['shipping_ht']),
                    'mix'                => $this->sanitize_float($p['prod_mix'][$i] ?? 0),
                    'image'              => esc_url_raw($p['prod_image'][$i] ?? ''),
                ];
            }
        }

        // Prizes
        $cfg['prizes'] = [];
        if ( ! empty($p['prize_name']) && is_array($p['prize_name']) ) {
            $count = count($p['prize_name']);
            for ($i=0; $i<$count; $i++){
                $cfg['prizes'][] = [
                    'name'     => sanitize_text_field($p['prize_name'][$i]),
                    'value_ht' => $this->sanitize_float($p['prize_value_ht'][$i] ?? 0),
                    'prep_ht'  => $this->sanitize_float($p['prize_prep_ht'][$i] ?? 0),
                    'image'    => esc_url_raw($p['prize_image'][$i] ?? ''),
                ];
            }
        }

        // Volumes
        $cfg['volumes'] = [];
        if ( ! empty($p['volumes']) && is_array($p['volumes']) ) {
            foreach ($p['volumes'] as $vv) {
                $v = (int)$vv;
                if ($v > 0) $cfg['volumes'][] = $v;
            }
        }

        return $cfg;
    }

    private function load_config_from_post_id($post_id) : array {
        $raw = get_post_meta($post_id, '_ws_scenario_config', true);
        $cfg = is_array($raw) ? $raw : [];
        return wp_parse_args($cfg, $this->defaults());
    }

    private function save_scenario(array $cfg, array $results) : int {
        $title = $cfg['scenario_title'];
        if (empty($title)) {
            $title = 'Scénario ' . current_time('Y-m-d H:i');
        }
        $post_id = wp_insert_post([
            'post_type'   => self::CPT,
            'post_status' => 'publish',
            'post_title'  => $title,
        ]);
        if ($post_id && ! is_wp_error($post_id)) {
            update_post_meta($post_id, '_ws_scenario_config', $cfg);
            update_post_meta($post_id, '_ws_scenario_results', $results);
        }
        return (int)$post_id;
    }

    private function compute_external_cm2_cost($cfg) : float {
        $area = max(0.0001, (float)$cfg['external_ref_area']);
        $cost = (float)$cfg['external_ref_cost'];
        return $cost / $area;
    }

    private function calc_price_ht(float $price_ttc, float $tva) : float {
        return $price_ttc / (1.0 + $tva);
    }

    private function calc_results(array $cfg) : array {
        $res = [];

        $tva = (float)$cfg['tva'];
        $refund_amt = (float)$cfg['refund_amount_ht'];
        $refund_enabled = !empty($cfg['refund_enabled']);
        $threshold = (int)$cfg['draw_threshold'];

        $fixed_non_prize = (float)$cfg['sacs_total_ht']
                         + (float)$cfg['fix_structure']
                         + (float)$cfg['fix_com']
                         + (float)$cfg['fix_huissier']
                         + (float)$cfg['fix_divers'];

        $ext_cm2_default = $this->compute_external_cm2_cost($cfg);

        // Normalise mix (au cas où la somme != 1)
        $mix_sum = 0.0;
        foreach ($cfg['products'] as $p) { $mix_sum += (float)$p['mix']; }
        $mix_sum = max(0.0001, $mix_sum);

        foreach ($cfg['volumes'] as $volume) {
            $volume = (int)$volume;
            if ($volume <= 0) continue;

            // CA et variables
            $ca_ht = 0.0;
            $var_total_internal = 0.0;
            $var_total_external = 0.0;

            foreach ($cfg['products'] as $p) {
                $share = (float)$p['mix'] / $mix_sum;
                $units = $volume * $share;

                $price_ht = $this->calc_price_ht((float)$p['price_ttc'], $tva);

                $garment = (float)$p['cost_garment_ht'];
                $area    = (float)$p['area_cm2'];
                $int_cm2 = (float)$p['cost_internal_cm2'];
                $ext_cm2 = (isset($p['cost_external_cm2']) && $p['cost_external_cm2']>0)
                            ? (float)$p['cost_external_cm2']
                            : $ext_cm2_default;
                $ship    = (float)$p['shipping_unit_ht'];

                $print_int = $area * $int_cm2;
                $print_ext = $area * $ext_cm2;

                $unit_var_int = $garment + $print_int + $ship;
                $unit_var_ext = $garment + $print_ext + $ship;

                $ca_ht += $units * $price_ht;
                $var_total_internal += $units * $unit_var_int;
                $var_total_external += $units * $unit_var_ext;
            }

            foreach ($cfg['prizes'] as $prize) {
                $name    = $prize['name'];
                $value   = (float)$prize['value_ht'];
                $prep    = (float)$prize['prep_ht']; // TOUJOURS incluse
                $prep_cost_always = $prep;

                // Modes: tirage (lot attribué) vs remboursement (pas de lot)
                // Remboursement actif uniquement si option ON et volume < threshold
                $refund_total = ($refund_enabled && $volume < $threshold) ? $refund_amt * $volume : 0.0;

                // Résultat en interne (tirage)
                $profit_internal_draw = $ca_ht
                    - $var_total_internal
                    - $fixed_non_prize
                    - $prep_cost_always
                    - $value // lot attribué
                    - 0.0;   // pas de remboursement

                // Résultat en interne (remboursement)
                $profit_internal_refund = $ca_ht
                    - $var_total_internal
                    - $fixed_non_prize
                    - $prep_cost_always
                    - 0.0          // pas de lot
                    - $refund_total;

                // Résultat en externe (tirage)
                $profit_external_draw = $ca_ht
                    - $var_total_external
                    - $fixed_non_prize
                    - $prep_cost_always
                    - $value
                    - 0.0;

                // Résultat en externe (remboursement)
                $profit_external_refund = $ca_ht
                    - $var_total_external
                    - $fixed_non_prize
                    - $prep_cost_always
                    - 0.0
                    - $refund_total;

                $res[] = [
                    'volume' => $volume,
                    'prize'  => $name,
                    'mode'   => 'Tirage (lot attribué) – Interne',
                    'ca_ht'  => $ca_ht,
                    'var'    => $var_total_internal,
                    'fixed'  => $fixed_non_prize,
                    'lot'    => $value,
                    'prep'   => $prep_cost_always,
                    'refund' => 0.0,
                    'profit' => $profit_internal_draw,
                ];
                $res[] = [
                    'volume' => $volume,
                    'prize'  => $name,
                    'mode'   => 'Remboursement – Interne',
                    'ca_ht'  => $ca_ht,
                    'var'    => $var_total_internal,
                    'fixed'  => $fixed_non_prize,
                    'lot'    => 0.0,
                    'prep'   => $prep_cost_always,
                    'refund' => $refund_total,
                    'profit' => $profit_internal_refund,
                ];
                $res[] = [
                    'volume' => $volume,
                    'prize'  => $name,
                    'mode'   => 'Tirage (lot attribué) – Externe',
                    'ca_ht'  => $ca_ht,
                    'var'    => $var_total_external,
                    'fixed'  => $fixed_non_prize,
                    'lot'    => $value,
                    'prep'   => $prep_cost_always,
                    'refund' => 0.0,
                    'profit' => $profit_external_draw,
                ];
                $res[] = [
                    'volume' => $volume,
                    'prize'  => $name,
                    'mode'   => 'Remboursement – Externe',
                    'ca_ht'  => $ca_ht,
                    'var'    => $var_total_external,
                    'fixed'  => $fixed_non_prize,
                    'lot'    => 0.0,
                    'prep'   => $prep_cost_always,
                    'refund' => $refund_total,
                    'profit' => $profit_external_refund,
                ];
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
                $saved_id = $this->save_scenario($cfg, $results);
                if ($saved_id) {
                    $notice = '<div class="ws-ok">Scénario enregistré (ID: '.$saved_id.').</div>';
                }
            }
        } elseif ( $loaded_id ) {
            $cfg = $this->load_config_from_post_id($loaded_id);
            $results = get_post_meta($loaded_id, '_ws_scenario_results', true);
            if ( ! is_array($results) ) $results = [];
            $notice = '<div class="ws-ok">Scénario chargé (ID: '.$loaded_id.').</div>';
        } else {
            $cfg = $this->defaults();
            $results = [];
        }

        // Pour la liste des scénarios existants (chargement rapide)
        $existing = get_posts([
            'post_type'      => self::CPT,
            'posts_per_page' => 20,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);

        // Agrégation simple pour graphique (profit par volume pour le 1er lot)
        $chart = $this->build_chart_dataset($results);

        echo '<div class="wrap"><h1>WinShirt – Simulateur</h1>';
        echo $notice;

        echo '<div class="ws-card">';
        echo '<div class="ws-flex"><span class="ws-tag">BETA</span><span class="ws-muted">Prépa incluse d’office dans tous les cas (tirage ou remboursement), conforme à ta règle.</span></div>';
        echo '</div>';

        echo '<form method="post">';
        wp_nonce_field('ws_simulator_nonce');

        // --------- SCENARIO META ----------
        echo '<div class="ws-card">';
        echo '<div class="ws-grid">';
        echo '<div class="ws-col-6"><label class="ws-label">Titre du scénario</label><input class="ws-input" type="text" name="scenario_title" value="'.esc_attr($cfg['scenario_title']).'" placeholder="Ex: Monster + Cartier + iPhone (été 2025)"></div>';
        echo '<div class="ws-col-6 ws-flex" style="align-items:flex-end;gap:12px;"><label class="ws-label" style="visibility:hidden">Actions</label>';
        echo '<label class="ws-flex"><input type="checkbox" name="save_scenario" value="1" '.checked(1, $cfg['save_scenario'], false).' class="ws-checkbox">Sauvegarder ce scénario</label>';
        echo '</div></div>';
        echo '</div>';

        // --------- PARAMÈTRES GÉNÉRAUX ----------
        echo '<div class="ws-card"><h2>Paramètres généraux</h2><div class="ws-grid">';
        $fields = [
            ['TVA (%)','tva', $cfg['tva']*100, 'number','step="0.01"'],
            ['Frais de port par commande (HT)','shipping_ht',$cfg['shipping_ht'],'number','step="0.01"'],
            ['Remboursement si &lt; seuil (HT)','refund_amount_ht',$cfg['refund_amount_ht'],'number','step="0.01"'],
            ['Seuil de tirage (ventes)','draw_threshold',$cfg['draw_threshold'],'number',''],
            ['Sacs brandés – coût total (HT)','sacs_total_ht',$cfg['sacs_total_ht'],'number','step="0.01"'],
            ['Structure (HT)','fix_structure',$cfg['fix_structure'],'number','step="0.01"'],
            ['Communication (HT)','fix_com',$cfg['fix_com'],'number','step="0.01"'],
            ['Huissier (HT)','fix_huissier',$cfg['fix_huissier'],'number','step="0.01"'],
            ['Divers (HT)','fix_divers',$cfg['fix_divers'],'number','step="0.01"'],
        ];
        foreach ($fields as $f) {
            echo '<div class="ws-col-4"><label class="ws-label">'.$f[0].'</label><input class="ws-input" type="'.$f[3].'" name="'.$f[1].'" value="'.esc_attr($f[1]==='tva' ? number_format((float)$f[2],2,'.','') : $f[2]).'" '.$f[4].'></div>';
        }
        echo '<div class="ws-col-12 ws-flex"><label class="ws-flex"><input type="checkbox" name="refund_enabled" value="1" '.checked(1,$cfg['refund_enabled'],false).' class="ws-checkbox">Activer le remboursement si ventes &lt; seuil</label></div>';
        echo '</div></div>';

        // Référence externe
        echo '<div class="ws-card"><h2>Référence impression externe</h2><div class="ws-grid">';
        echo '<div class="ws-col-6"><label class="ws-label">Surface de réf. (cm²) – ex. A5 = 310.8</label><input class="ws-input" type="number" step="0.01" name="external_ref_area" value="'.esc_attr($cfg['external_ref_area']).'"></div>';
        echo '<div class="ws-col-6"><label class="ws-label">Coût de réf. (HT) – ex. 1.50€ pour A5</label><input class="ws-input" type="number" step="0.01" name="external_ref_cost" value="'.esc_attr($cfg['external_ref_cost']).'"></div>';
        echo '<div class="ws-col-12 ws-muted">Le coût externe au cm² sera dérivé automatiquement : coût_réf / surface_réf.</div>';
        echo '</div></div>';

        // --------- PRODUITS ----------
        echo '<div class="ws-card"><h2>Produits</h2>';
        echo '<div id="ws-products">';
        foreach ($cfg['products'] as $p) {
            $this->render_product_row($p, $cfg);
        }
        echo '</div>';
        echo '<button class="ws-btn ws-add-product" type="button">+ Ajouter un produit</button>';
        echo '<template id="ws-product-template">';
        $this->render_product_row(null, $cfg, true);
        echo '</template>';
        echo '</div>';

        // --------- LOTS ----------
        echo '<div class="ws-card"><h2>Lots</h2>';
        echo '<div id="ws-prizes">';
        foreach ($cfg['prizes'] as $pr) {
            $this->render_prize_row($pr);
        }
        echo '</div>';
        echo '<button class="ws-btn secondary ws-add-prize" type="button">+ Ajouter un lot</button>';
        echo '<template id="ws-prize-template">';
        $this->render_prize_row(null, true);
        echo '</template>';
        echo '</div>';

        // --------- VOLUMES ----------
        echo '<div class="ws-card"><h2>Volumes</h2>';
        echo '<div class="ws-grid">';
        $i=0;
        foreach ($cfg['volumes'] as $vol) {
            echo '<div class="ws-col-4"><label class="ws-label">Volume '.(++$i).'</label><input class="ws-input" type="number" name="volumes[]" value="'.esc_attr($vol).'"></div>';
        }
        echo '<div class="ws-col-4"><label class="ws-label">Volume personnalisé</label><input class="ws-input" type="number" name="volumes[]" value=""></div>';
        echo '</div>';
        echo '</div>';

        echo '<p><button class="ws-btn" type="submit" name="ws_simulator_submit" value="1">Simuler</button></p>';

        // --------- RÉSULTATS ----------
        if ( ! empty($results) ) {
            $this->render_results($results);
            $this->render_chart($chart);
        }

        echo '</form>';

        // Liste rapide des scénarios
        echo '<div class="ws-card"><h2>Scénarios enregistrés</h2>';
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

        echo '</div>'; // .wrap
    }

    private function render_product_row($p=null, $cfg=[], $is_template=false) {
        $d = [
            'name' => 'T-shirt',
            'price_ttc' => 20.00,
            'cost_garment_ht' => 3.20,
            'area_cm2' => 310.8,
            'cost_internal_cm2' => 0.003,
            'cost_external_cm2' => 0.0,
            'use_internal' => 1,
            'shipping_unit_ht' => isset($cfg['shipping_ht']) ? $cfg['shipping_ht'] : 5.00,
            'mix' => 1.00,
            'image' => '',
        ];
        if (is_array($p)) $d = array_merge($d, $p);

        $wrap = $is_template ? '<div class="ws-row ws-card" style="margin-top:12px;">' : '<div class="ws-row ws-card" style="margin-top:12px;">';
        echo $wrap;
        echo '<div class="ws-grid">';
        echo '<div class="ws-col-4"><label class="ws-label">Produit</label><input class="ws-input" name="prod_name[]" value="'.esc_attr($d['name']).'"></div>';
        echo '<div class="ws-col-2"><label class="ws-label">Prix TTC</label><input class="ws-input" type="number" step="0.01" name="prod_price_ttc[]" value="'.esc_attr($d['price_ttc']).'"></div>';
        echo '<div class="ws-col-2"><label class="ws-label">Coût vêtement HT</label><input class="ws-input" type="number" step="0.01" name="prod_cost_vet_ht[]" value="'.esc_attr($d['cost_garment_ht']).'"></div>';
        echo '<div class="ws-col-2"><label class="ws-label">Surface (cm²)</label><input class="ws-input" type="number" step="0.01" name="prod_area_cm2[]" value="'.esc_attr($d['area_cm2']).'"></div>';
        echo '<div class="ws-col-2"><label class="ws-label">Port unitaire HT</label><input class="ws-input" type="number" step="0.01" name="prod_ship_ht[]" value="'.esc_attr($d['shipping_unit_ht']).'"></div>';

        echo '<div class="ws-col-2"><label class="ws-label">Coût interne / cm²</label><input class="ws-input" type="number" step="0.0001" name="prod_cost_int_cm2[]" value="'.esc_attr($d['cost_internal_cm2']).'"></div>';
        echo '<div class="ws-col-2"><label class="ws-label">Coût externe / cm²</label><input class="ws-input" type="number" step="0.0001" name="prod_cost_ext_cm2[]" placeholder="auto" value="'.esc_attr($d['cost_external_cm2']).'"></div>';
        echo '<div class="ws-col-2" style="display:flex;align-items:flex-end;"><label class="ws-flex"><input class="ws-checkbox" type="checkbox" name="prod_use_internal[]" value="1" '.checked(1,$d['use_internal'],false).'>Prod interne</label></div>';
        echo '<div class="ws-col-2"><label class="ws-label">Mix (0–1)</label><input class="ws-input" type="number" step="0.01" name="prod_mix[]" value="'.esc_attr($d['mix']).'"></div>';

        echo '<div class="ws-col-3"><label class="ws-label">Image</label><div class="ws-flex"><input class="ws-input" type="text" name="prod_image[]" id="" value="'.esc_attr($d['image']).'"><button class="ws-btn ws-pick-media" data-target="">Choisir</button></div></div>';
        echo '<div class="ws-col-1" style="display:flex;align-items:flex-end;justify-content:flex-end;"><button class="ws-btn secondary ws-remove-row">Suppr.</button></div>';

        echo '</div></div>';
    }

    private function render_prize_row($pr=null, $is_template=false) {
        $d = [
            'name' => 'Nouveau lot',
            'value_ht' => 0.0,
            'prep_ht'  => 0.0,
            'image'    => '',
        ];
        if (is_array($pr)) $d = array_merge($d, $pr);

        echo '<div class="ws-row ws-card" style="margin-top:12px;">';
        echo '<div class="ws-grid">';
        echo '<div class="ws-col-4"><label class="ws-label">Nom du lot</label><input class="ws-input" name="prize_name[]" value="'.esc_attr($d['name']).'"></div>';
        echo '<div class="ws-col-3"><label class="ws-label">Valeur (HT)</label><input class="ws-input" type="number" step="0.01" name="prize_value_ht[]" value="'.esc_attr($d['value_ht']).'"></div>';
        echo '<div class="ws-col-3"><label class="ws-label">Prépa (HT)</label><input class="ws-input" type="number" step="0.01" name="prize_prep_ht[]" value="'.esc_attr($d['prep_ht']).'"></div>';
        $target = 'prize_img_'.wp_generate_uuid4();
        echo '<div class="ws-col-2"><label class="ws-label">Image</label><div class="ws-flex"><input class="ws-input" id="'.$target.'" type="text" name="prize_image[]" value="'.esc_attr($d['image']).'"><button class="ws-btn ws-pick-media" data-target="'.$target.'">Choisir</button></div></div>';
        echo '<div class="ws-col-12" style="display:flex;justify-content:flex-end;"><button class="ws-btn secondary ws-remove-row">Suppr.</button></div>';
        echo '</div></div>';
    }

    private function render_results(array $rows) {
        // Tableau
        echo '<div class="ws-card"><h2>Résultats</h2>';
        echo '<table class="ws"><thead><tr>';
        $heads = ['Volume','Lot','Mode','CA HT','Coût variables','Fixes (hors lot)','Prépa','Lot','Remboursements','Résultat HT'];
        foreach ($heads as $h) echo '<th>'.esc_html($h).'</th>';
        echo '</tr></thead><tbody>';

        foreach ($rows as $r) {
            $class = $r['profit'] >= 0 ? 'style="background:#f7fef9;"' : 'style="background:#fff7f7;"';
            echo "<tr $class>";
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

    private function build_chart_dataset(array $rows) : array {
        // On prend le premier lot comme référence pour le graphe,
        // et regroupe par volume (tirage vs remboursement, interne uniquement pour lisibilité).
        $dataset = [];
        foreach ($rows as $r) {
            if (strpos($r['mode'], 'Interne') === false) continue;
            $key = $r['volume'];
            if ( ! isset($dataset[$key]) ) $dataset[$key] = ['draw'=>null,'refund'=>null];
            if (strpos($r['mode'], 'Tirage') !== false) {
                $dataset[$key]['draw'] = $r['profit'];
            } else {
                $dataset[$key]['refund'] = $r['profit'];
            }
        }
        ksort($dataset);
        return $dataset;
    }

    private function render_chart(array $chart) {
        if (empty($chart)) return;

        $labels = array_keys($chart);
        $draw   = [];
        $refund = [];
        foreach ($chart as $vol => $vals) {
            $draw[]   = isset($vals['draw'])   ? round($vals['draw'],2)   : 0;
            $refund[] = isset($vals['refund']) ? round($vals['refund'],2) : 0;
        }

        echo '<div class="ws-card"><h2>Graphique (Interne)</h2>';
        echo '<canvas id="wsChart" height="120"></canvas>';
        echo '<script>
        (function(){
            const ctx = document.getElementById("wsChart").getContext("2d");
            new Chart(ctx, {
              type: "bar",
              data: {
                labels: '.wp_json_encode($labels).',
                datasets: [
                  { label: "Tirage (lot attribué)", data: '.wp_json_encode($draw).', borderWidth:1 },
                  { label: "Remboursement", data: '.wp_json_encode($refund).', borderWidth:1 }
                ]
              },
              options: {
                responsive:true,
                scales: { y: { beginAtZero: true } }
              }
            });
        })();
        </script>';
        echo '</div>';
    }
}

// Boot
add_action('plugins_loaded', function(){
    // Instancier le simulateur si le menu WinShirt existe (sinon, on l’ajoute sous "Outils")
    $sim = new WinShirt_Simulator();
    // Fallback: si le menu parent "winshirt" n’existe pas, ajoute une entrée indépendante
    if ( ! has_action('admin_menu', [$sim, 'add_menu']) ) {
        add_menu_page('WinShirt','WinShirt','manage_options','winshirt',function(){
            echo '<div class="wrap"><h1>WinShirt</h1><p>Tableau de bord WinShirt</p></div>';
        }, 'dashicons-tickets', 56);
        add_submenu_page('winshirt','Simulateur','Simulateur','manage_options',WinShirt_Simulator::MENU_SLUG,[$sim,'render_admin_page']);
    }
});
