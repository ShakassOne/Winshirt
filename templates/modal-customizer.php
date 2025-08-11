<?php
/**
 * Template – WinShirt Customizer Modal
 * - Structure neutre + hooks data-*
 * - Mockup + zones d’impression + panneaux L1/L2/L3
 * - Boutons Recto/Verso (passage de côté via WinShirtState)
 * - Champ hidden pour payload WooCommerce (cart item)
 */

if ( ! defined('ABSPATH') ) exit;

// Mockups (fallback si WinShirtData non peuplé côté PHP)
$front_img = isset( $args['front_img'] ) ? esc_url( $args['front_img'] ) : '';
$back_img  = isset( $args['back_img'] )  ? esc_url( $args['back_img'] )  : '';

?>
<div id="winshirt-customizer-modal" class="winshirt-modal" data-ws-root>
    <!-- Zone haute : mockup + canvas calques -->
    <div class="winshirt-mockup-area">
        <div class="winshirt-mockup-canvas" id="winshirt-canvas" aria-live="polite">
            <!-- Mockups recto/verso (affichage alterné) -->
            <img class="winshirt-mockup-img" data-side="front" alt="Mockup Recto" style="position:absolute; inset:0; margin:auto; max-width:100%; max-height:100%; object-fit:contain; display:block;">
            <img class="winshirt-mockup-img" data-side="back"  alt="Mockup Verso" style="position:absolute; inset:0; margin:auto; max-width:100%; max-height:100%; object-fit:contain; display:none;">

            <!-- Zones d'impression (en px recalculés depuis %) -->
            <div class="ws-print-zone" data-side="front" style="position:absolute; border:1px dashed rgba(0,0,255,0.4); pointer-events:none;"></div>
            <div class="ws-print-zone" data-side="back"  style="position:absolute; border:1px dashed rgba(0,0,255,0.4); pointer-events:none; display:none;"></div>
        </div>
    </div>

    <!-- Boutons Recto / Verso -->
    <div class="winshirt-side-buttons" role="tablist" aria-label="Choix du côté">
        <button type="button" class="ws-side-btn active" data-ws-side="front" role="tab" aria-selected="true">Recto</button>
        <button type="button" class="ws-side-btn"        data-ws-side="back"  role="tab" aria-selected="false">Verso</button>
    </div>

    <!-- Panneaux L1/L2/L3 (router UI) -->
    <div id="winshirt-panel-root" class="ws-panels" data-active-level="0" aria-live="polite">
        <div class="ws-panel ws-panel-l1" data-level="1" aria-hidden="true"></div>
        <div class="ws-panel ws-panel-l2" data-level="2" aria-hidden="true"></div>
        <div class="ws-panel ws-panel-l3" data-level="3" aria-hidden="true"></div>
    </div>

    <!-- Actions basiques (tu pourras les replacer / styler) -->
    <div class="winshirt-actions" style="display:flex; gap:8px; padding:10px;">
        <button type="button" class="button ws-save-design" data-ws-save>Enregistrer le design</button>
        <button type="button" class="button ws-add-to-cart" data-ws-add-to-cart>Ajouter au panier</button>
    </div>

    <!-- Champ hidden pour Woo (cart item) -->
    <input type="hidden" name="winshirt_payload" id="winshirt-payload" value="">
</div>

<script>
(function($){
    'use strict';

    // --- 1) Appliquer les mockups recto/verso s'ils existent dans WinShirtData ---
    function applyMockups(){
        const front = (window.WinShirtData && WinShirtData.mockups && WinShirtData.mockups.front) ? WinShirtData.mockups.front : '<?php echo $front_img; ?>';
        const back  = (window.WinShirtData && WinShirtData.mockups && WinShirtData.mockups.back ) ? WinShirtData.mockups.back  : '<?php echo $back_img; ?>';
        if(front){
            $('#winshirt-canvas .winshirt-mockup-img[data-side="front"]').attr('src', front);
        }
        if(back){
            $('#winshirt-canvas .winshirt-mockup-img[data-side="back"]').attr('src', back);
        }
    }

    // --- 2) Recalcule les zones d'impression (px) depuis WinShirtData.zones (en %) ---
    function lerp(a,b,t){ return a + (b-a)*t; }
    function applyZones(){
        const $cv = $('#winshirt-canvas');
        const W = $cv.innerWidth(), H = $cv.innerHeight();
        const zones = (window.WinShirtData && WinShirtData.zones) ? WinShirtData.zones : null;

        ['front','back'].forEach(side=>{
            const $z = $cv.find('.ws-print-zone[data-side="'+side+'"]');
            if(!$z.length) return;

            let cfg = null;
            if(zones && zones[side] && zones[side].length){
                // on prend la zone active (index 0 par défaut)
                const idx = (window.WinShirtState ? WinShirtState.currentZoneIndex : 0) || 0;
                cfg = zones[side][idx] || zones[side][0];
            }
            if(!cfg){
                // fallback : 60% largeur, ratio 0.75
                const w = W * 0.6, h = H * 0.45;
                $z.css({ left:(W-w)/2, top:(H-h)/2, width:w, height:h });
                return;
            }
            // cfg attendu : { xPct, yPct, wPct, hPct } en %
            const x = (cfg.xPct||20) / 100 * W;
            const y = (cfg.yPct||20) / 100 * H;
            const w = (cfg.wPct||60) / 100 * W;
            const h = (cfg.hPct||45) / 100 * H;
            $z.css({ left:x, top:y, width:w, height:h });
        });
    }

    // --- 3) Gestion Recto/Verso ---
    function setSide(side){
        if(!window.WinShirtState){ return; }
        WinShirtState.setSide(side);

        // Images mockup & zones
        $('#winshirt-canvas .winshirt-mockup-img').hide();
        $('#winshirt-canvas .winshirt-mockup-img[data-side="'+side+'"]').show();

        $('#winshirt-canvas .ws-print-zone').hide();
        $('#winshirt-canvas .ws-print-zone[data-side="'+side+'"]').show();

        // Boutons actifs
        $('.ws-side-btn').removeClass('active').attr('aria-selected','false');
        $('.ws-side-btn[data-ws-side="'+side+'"]').addClass('active').attr('aria-selected','true');
    }

    // --- 4) Save design → previews + JSON calques → REST /save-design ---
    async function saveDesign(){
        try{
            // capture recto/verso (si html2canvas présent)
            const $cv = $('#winshirt-canvas');
            if(typeof html2canvas !== 'function'){
                alert('Capture indisponible (html2canvas manquant).'); return;
            }

            // Recto
            setSide('front'); await new Promise(r=>setTimeout(r,50));
            const canvasF = await html2canvas($cv[0], {useCORS:true});
            const frontDataUrl = canvasF.toDataURL('image/png');

            // Verso
            setSide('back'); await new Promise(r=>setTimeout(r,50));
            const canvasB = await html2canvas($cv[0], {useCORS:true});
            const backDataUrl = canvasB.toDataURL('image/png');

            // Payload
            const layers = window.WinShirtState ? WinShirtState.layers : {front:[], back:[]};
            const body = {
                product_id: (window.WinShirtData && WinShirtData.product) ? WinShirtData.product.id : 0,
                front_dataurl: frontDataUrl,
                back_dataurl:  backDataUrl,
                layers_json: JSON.stringify(layers),
                lottery_id: 0
            };

            const resp = await fetch((WinShirtData.restUrl||'')+'/save-design', {
                method:'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': WinShirtData.nonce || ''
                },
                body: JSON.stringify(body)
            });
            if(!resp.ok){
                const t = await resp.text();
                console.error('save-design error', t);
                alert('Erreur sauvegarde design');
                return;
            }
            const data = await resp.json();

            // Remplit le hidden Woo
            const payload = {
                frontPreviewUrl: data.front ? data.front.url : '',
                backPreviewUrl:  data.back  ? data.back.url  : '',
                layers: layers,
                lotteryId: data.lottery_id || 0
            };
            $('#winshirt-payload').val(JSON.stringify(payload));

            // Revenir côté initial
            setSide(WinShirtState.currentSide === 'back' ? 'back' : 'front');

            alert('Design sauvegardé.');
        }catch(e){
            console.error(e);
            alert('Erreur inattendue (save).');
        }
    }

    // --- 5) Add to cart : exige que #winshirt-payload soit rempli (après save) ---
    function addToCart(){
        if(!$('#winshirt-payload').val()){
            alert('Enregistre d’abord le design (prévisualisations).');
            return;
        }
        // Si le bouton "Ajouter au panier" Woo est présent : on déclenche submit du formulaire produit.
        const $form = $('form.cart');
        if($form.length){
            $form.trigger('submit');
        }else{
            alert('Formulaire WooCommerce introuvable. Ajoute au panier manuellement.');
        }
    }

    // --- Boot ---
    $(function(){
        applyMockups();
        applyZones();
        setSide('front');

        // Recalcul zones au resize
        let t=null;
        $(window).on('resize', function(){ clearTimeout(t); t=setTimeout(applyZones, 120); });

        // Boutons recto/verso
        $(document).on('click', '.ws-side-btn', function(){
            setSide($(this).data('ws-side'));
        });

        // Actions
        $(document).on('click', '[data-ws-save]', saveDesign);
        $(document).on('click', '[data-ws-add-to-cart]', addToCart);

        // Signaler qu’on est prêt
        $(document).trigger('winshirt:templateReady');
    });
})(jQuery);
</script>
