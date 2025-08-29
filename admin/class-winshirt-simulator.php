<?php
/**
 * WinShirt - Simulateur (SAFE-MIN, boot immédiat)
 * Objectif: garantir l'affichage de la page admin sans erreur critique.
 * - Menu top-level "Simulateur" (slug: winshirt-simulator)
 * - AUCUN enqueue, AUCUN CPT, AUCUNE dépendance externe.
 * - Formulaire minimal + calculs basiques en PHP pur.
 * Compat PHP >= 7.0
 */
if ( ! defined('ABSPATH') ) exit;

if ( ! class_exists('WinShirt_Simulator_SafeMin') ) {

class WinShirt_Simulator_SafeMin {
    const MENU_SLUG = 'winshirt-simulator';

    public function __construct() {
        // Enregistre le menu tout de suite
        add_action('admin_menu', array($this,'add_menu'));
    }

    public function add_menu() {
        // Crée systématiquement un menu top-level fiable
        add_menu_page(
            'Simulateur WinShirt (SAFE-MIN)', // Titre de page
            'Simulateur',                     // Titre du menu
            'manage_options',                 // Capacité requise
            self::MENU_SLUG,                  // Slug unique
            array($this,'render_admin_page'), // Callback
            'dashicons-chart-area',           // Icône
            56                                // Position
        );
    }

    // ----------------- OUTILS -----------------
    private function num($v,$def=0.0){
        if (is_string($v)) $v = str_replace(',','.',$v);
        return is_numeric($v) ? (float)$v : (float)$def;
    }
    private function price_ht($ttc, $tva){ return (float)$ttc / (1.0 + (float)$tva); }

    // ----------------- PAGE -------------------
    public function render_admin_page() {
        if ( ! current_user_can('manage_options') ) return;

        // Défauts
        $d = array(
            'tva' => 0.20,
            'price_ttc' => 20.00,
            'cost_unit_ht' => 4.00,   // coût produit HT hors port
            'ship_unit_ht' => 5.00,   // port unitaire HT
            'fixed_ht' => 5360.00,    // structure+com+huissier+divers+sacs
            'volume' => 3000,
            'prize_value_ht' => 7000.00,
            'prep_ht' => 5000.00,     // incluse d'office (prépa)
            'refund_enabled' => 1,
            'refund_amount_ht' => 5.00,
            'draw_threshold' => 5000,
            'mode' => 'draw',         // draw | refund
        );

        // Lecture POST en safe
        $p = isset($_POST) ? wp_unslash($_POST) : array();
        $cfg = array();
        $cfg['tva']              = isset($p['tva']) ? $this->num($p['tva'], $d['tva']) : $d['tva'];
        $cfg['price_ttc']        = isset($p['price_ttc']) ? $this->num($p['price_ttc'], $d['price_ttc']) : $d['price_ttc'];
        $cfg['cost_unit_ht']     = isset($p['cost_unit_ht']) ? $this->num($p['cost_unit_ht'], $d['cost_unit_ht']) : $d['cost_unit_ht'];
        $cfg['ship_unit_ht']     = isset($p['ship_unit_ht']) ? $this->num($p['ship_unit_ht'], $d['ship_unit_ht']) : $d['ship_unit_ht'];
        $cfg['fixed_ht']         = isset($p['fixed_ht']) ? $this->num($p['fixed_ht'], $d['fixed_ht']) : $d['fixed_ht'];
        $cfg['volume']           = isset($p['volume']) ? (int)$p['volume'] : $d['volume'];
        $cfg['prize_value_ht']   = isset($p['prize_value_ht']) ? $this->num($p['prize_value_ht'], $d['prize_value_ht']) : $d['prize_value_ht'];
        $cfg['prep_ht']          = isset($p['prep_ht']) ? $this->num($p['prep_ht'], $d['prep_ht']) : $d['prep_ht'];
        $cfg['refund_enabled']   = !empty($p['refund_enabled']) ? 1 : (isset($p['ws_submitted']) ? 0 : $d['refund_enabled']);
        $cfg['refund_amount_ht'] = isset($p['refund_amount_ht']) ? $this->num($p['refund_amount_ht'], $d['refund_amount_ht']) : $d['refund_amount_ht'];
        $cfg['draw_threshold']   = isset($p['draw_threshold']) ? (int)$p['draw_threshold'] : $d['draw_threshold'];
        $cfg['mode']             = (isset($p['mode']) && in_array($p['mode'], array('draw','refund'), true)) ? $p['mode'] : $d['mode'];

        // Calculs minimalistes (1 produit, 1 lot)
        $price_ht = $this->price_ht($cfg['price_ttc'], $cfg['tva']);
        $unit_var = $cfg['cost_unit_ht'] + $cfg['ship_unit_ht'];
        $ca_ht    = $cfg['volume'] * $price_ht;
        $var_tot  = $cfg['volume'] * $unit_var;

        // Prépa: TOUJOURS incluse (dépensée dès le départ)
        $fixed = $cfg['fixed_ht'] + $cfg['prep_ht'];

        $refund_total = ( $cfg['refund_enabled'] && $cfg['volume'] < $cfg['draw_threshold'] )
            ? $cfg['refund_amount_ht'] * $cfg['volume']
            : 0.0;

        if ($cfg['mode']==='draw') {
            $profit = $ca_ht - $var_tot - $fixed - $cfg['prize_value_ht'];
        } else {
            $profit = $ca_ht - $var_tot - $fixed - $refund_total;
        }

        // ----- Rendu HTML sans dépendances -----
        echo '<div class="wrap"><h1>WinShirt – Simulateur (SAFE-MIN)</h1>';
        echo '<p style="color:#6b7280;margin:8px 0;">Version minimale pour garantir l’accès. Si cette page s’affiche, on pourra réactiver les fonctionnalités avancées étape par étape.</p>';

        echo '<form method="post" style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:16px;max-width:980px;">';
        echo '<input type="hidden" name="ws_submitted" value="1" />';

        // Ligne 1
        echo '<div style="display:flex;gap:12px;flex-wrap:wrap;">';
        echo $this->field('Prix TTC (€)','price_ttc',$cfg['price_ttc']);
        echo $this->field('TVA (ex: 0.20)','tva',$cfg['tva'], '0.01');
        echo $this->field('Coût unitaire HT (hors port)','cost_unit_ht',$cfg['cost_unit_ht'], '0.01');
        echo $this->field('Port unitaire HT','ship_unit_ht',$cfg['ship_unit_ht'], '0.01');
        echo '</div>';

        // Ligne 2
        echo '<div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:10px;">';
        echo $this->field('Frais fixes HT (hors prépa)','fixed_ht',$cfg['fixed_ht'], '0.01');
        echo $this->field('Prépa lot HT (toujours incluse)','prep_ht',$cfg['prep_ht'], '0.01');
        echo $this->field('Valeur lot HT (si tirage)','prize_value_ht',$cfg['prize_value_ht'], '0.01');
        echo $this->field('Volume (ventes)','volume',$cfg['volume'], '1', 'number');
        echo '</div>';

        // Ligne 3
        echo '<div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:10px;">';
        echo $this->field('Seuil tirage','draw_threshold',$cfg['draw_threshold'], '1', 'number');
        echo $this->field('Remboursement HT (si < seuil)','refund_amount_ht',$cfg['refund_amount_ht'], '0.01');
        echo '<div style="display:flex;align-items:flex-end;gap:8px;padding:6px 0;">';
        echo '<label><input type="checkbox" name="refund_enabled" value="1" '.checked(1,$cfg['refund_enabled'],false).' /> Remboursement activé</label>';
        echo '</div>';
        echo '<div style="display:flex;align-items:flex-end;gap:8px;padding:6px 0;">';
        echo '<label><input type="radio" name="mode" value="draw" '.checked('draw',$cfg['mode'],false).' /> Tirage (lot attribué)</label>';
        echo '<label style="margin-left:12px;"><input type="radio" name="mode" value="refund" '.checked('refund',$cfg['mode'],false).' /> Remboursement (pas de lot)</label>';
        echo '</div>';
        echo '</div>';

        echo '<p><button class="button button-primary" type="submit">Calculer</button></p>';
        echo '</form>';

        // Résultats
        if ( isset($_POST['ws_submitted']) ) {
            $ok = ($profit >= 0);
            echo '<div style="margin-top:16px;background:'.($ok?'#ecfdf5':'#fef2f2').';border:1px solid '.($ok?'#10b981':'#ef4444').';border-radius:10px;padding:12px;max-width:980px;">';
            echo '<h2 style="margin:0 0 8px 0;">Résultat</h2>';
            echo '<p style="margin:0;">CA HT: <strong>'.number_format($ca_ht,2,',',' ').' €</strong> — Variables: <strong>'.number_format($var_tot,2,',',' ').' €</strong> — Fixes+Prépa: <strong>'.number_format($fixed,2,',',' ').' €</strong>';
            if ($cfg['mode']==='draw') {
                echo ' — Lot: <strong>'.number_format($cfg['prize_value_ht'],2,',',' ').' €</strong>';
            } else {
                echo ' — Remboursements: <strong>'.number_format($refund_total,2,',',' ').' €</strong>';
            }
            echo '<br>Profit HT: <strong>'.number_format($profit,2,',',' ').' €</strong> '.($ok?'✅ rentable':'❌ déficitaire').'</p>';
            echo '</div>';
        }

        echo '</div>';
    }

    private function field($label, $name, $val, $step='0.01', $type='number'){
        $v = esc_attr($val);
        $s = '<div style="display:flex;flex-direction:column;min-width:200px;flex:1 1 200px;">';
        $s.= '<label style="font-weight:600;margin-bottom:4px;">'.esc_html($label).'</label>';
        $s.= '<input style="padding:8px;border:1px solid #d1d5db;border-radius:8px;width:100%;" type="'.$type.'" step="'.$step.'" name="'.$name.'" value="'.$v.'">';
        $s.= '</div>';
        return $s;
    }
}
} // class guard

// ---------- BOOT IMMÉDIAT & ADMIN-ONLY ----------
// (pas de hook tardif; la classe est instanciée dès l'inclusion du fichier)
if ( is_admin() ) {
    new WinShirt_Simulator_SafeMin();
}
