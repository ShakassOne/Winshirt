/**
 * WinShirt - Pricing par surface et paliers de formats (A3–A7)
 *
 * Principe (indépendant du DPI) :
 * - On mesure l'aire totale occupée par les calques visibles d'un côté (union approximée = somme des bounding boxes).
 * - On compare cette aire à la surface de la zone d'impression active → on obtient un pourcentage.
 * - On applique un palier de prix (A7 < A6 < ... < A3) selon la surface.
 *
 * Données configurables (via WinShirtData.config.pricing) :
 *  {
 *    base: 0,                   // base du produit ou supplément minimum
 *    perSideBase: 0,            // supplément fixe par côté utilisé
 *    tiers: [                   // paliers (%) → format + tarif
 *      { maxPct: 5,   label:'A7', price: 3.5 },
 *      { maxPct: 12,  label:'A6', price: 6.0 },
 *      { maxPct: 25,  label:'A5', price: 9.0 },
 *      { maxPct: 45,  label:'A4', price: 12.0 },
 *      { maxPct: 75,  label:'A3', price: 16.0 },
 *      { maxPct: 100, label:'MAX',price: 20.0 }
 *    ]
 *  }
 *
 * Événements :
 *  - winshirt:priceUpdated [detail] (total, sides: {front:{pct,format,price}, back:{...}})
 *
 * Dépendances : jQuery, WinShirtState, WinShirtLayers
 */

(function($){
  'use strict';

  const Price = {
    config: {
      base: 0,
      perSideBase: 0,
      tiers: [
        { maxPct: 5,   label:'A7', price: 3.5 },
        { maxPct: 12,  label:'A6', price: 6.0 },
        { maxPct: 25,  label:'A5', price: 9.0 },
        { maxPct: 45,  label:'A4', price: 12.0 },
        { maxPct: 75,  label:'A3', price: 16.0 },
        { maxPct: 100, label:'MAX',price: 20.0 }
      ]
    },

    /**
     * Initialise la config à partir de WinShirtData.config.pricing s’il existe
     */
    init(){
      if(window.WinShirtData && WinShirtData.config && WinShirtData.config.pricing){
        const p = WinShirtData.config.pricing;
        if(typeof p.base === 'number') this.config.base = p.base;
        if(typeof p.perSideBase === 'number') this.config.perSideBase = p.perSideBase;
        if(Array.isArray(p.tiers) && p.tiers.length){
          this.config.tiers = p.tiers.map(t=>({
            maxPct: Number(t.maxPct),
            label: String(t.label || ''),
            price: Number(t.price)
          })).sort((a,b)=>a.maxPct-b.maxPct);
        }
      }

      // Recalculs sur événements clés
      $(document)
        .on('winshirt:layerAdded winshirt:layerRemoved winshirt:layerUpdated winshirt:sideChanged winshirt:zoneChanged', ()=>{
          this.updatePrice();
        });

      // Premier calcul
      this.updatePrice();
    },

    /**
     * Récupère le rect de zone d’impression (px) depuis le canvas (cf. image-tools)
     */
    _getPrintZoneRect(){
      const $cv = (window.WinShirtLayers && WinShirtLayers.$canvas) ? WinShirtLayers.$canvas : null;
      if(!$cv || !$cv.length){
        return { left:0, top:0, width: 300, height: 300 };
      }
      const side = WinShirtState.currentSide;
      const $zone = $cv.find(`.ws-print-zone[data-side="${side}"]`);
      const w = $cv.innerWidth();
      const h = $cv.innerHeight();
      if($zone.length){
        return {
          left: parseFloat($zone.css('left')) || 0,
          top: parseFloat($zone.css('top')) || 0,
          width: $zone.outerWidth() || w,
          height: $zone.outerHeight() || h
        };
      }
      return { left:0, top:0, width: w, height: h };
    },

    /**
     * Aire (%) occupée par les calques visibles d’un côté par rapport à la zone d’impression.
     * NB: approximation en sommant les bounding boxes (rapide et stable).
     */
    _computeSidePercent(side){
      const zone = this._getPrintZoneRect();
      const zoneArea = Math.max(1, zone.width * zone.height);

      const layers = WinShirtState.layers[side] || [];
      let sum = 0;

      layers.forEach(l=>{
        // On peut ignorer des types (ex: guides) si nécessaire
        const w = Number(l.width || 0);
        const h = Number(l.height || 0);
        sum += Math.max(0, w*h);
      });

      const pct = Math.min(100, Math.max(0, (sum / zoneArea) * 100));
      return { pct, zoneArea, sumArea: sum };
    },

    /**
     * Trouve le palier (format) en fonction du pourcentage
     */
    _tierForPercent(pct){
      for(const t of this.config.tiers){
        if(pct <= t.maxPct) return t;
      }
      // fallback dernier
      return this.config.tiers[this.config.tiers.length - 1];
    },

    /**
     * Calcule le prix d’un côté (pct → palier → prix)
     */
    _priceForSide(pct){
      if(pct <= 0) return { format: null, price: 0 };
      const tier = this._tierForPercent(pct);
      const sidePrice = (this.config.perSideBase || 0) + (tier.price || 0);
      return { format: tier.label, price: Number(sidePrice.toFixed(2)) };
    },

    /**
     * Recalcule le prix total (base + côtés utilisés)
     */
    updatePrice(){
      // Front
      const front = this._computeSidePercent('front');
      const frontInfo = this._priceForSide(front.pct);

      // Back
      const back  = this._computeSidePercent('back');
      const backInfo = this._priceForSide(back.pct);

      const total = Number((this.config.base + frontInfo.price + backInfo.price).toFixed(2));

      const detail = {
        base: this.config.base,
        total,
        sides: {
          front: { pct: Number(front.pct.toFixed(2)), format: frontInfo.format, price: frontInfo.price },
          back:  { pct: Number(back.pct.toFixed(2)),  format: backInfo.format,  price: backInfo.price }
        }
      };

      $(document).trigger('winshirt:priceUpdated', [detail]);
      return detail;
    }
  };

  // Expose global
  window.WinShirtPrice = Price;

  // Boot
  $(function(){ Price.init(); });

})(jQuery);
