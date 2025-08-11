/**
 * WinShirt - State central
 * Gère l'état global du configurateur : côté (recto/verso), calques, sélection,
 * zones d'impression, produit courant, etc.
 *
 * Tous les modules (ui-panels, layers, tools, router-hooks) doivent passer par ici.
 */
(function($){
    'use strict';

    // Objet state central (modifiable via WinShirtState.*)
    const WinShirtState = {
        // Côté actuel du produit : 'front' ou 'back'
        currentSide: 'front',

        // Index de la zone d'impression active (si plusieurs zones par côté)
        currentZoneIndex: 0,

        // Liste des calques par côté (recto/verso)
        layers: {
            front: [],
            back: []
        },

        // ID du calque actuellement sélectionné
        selectedLayerId: null,

        // Métadonnées produit
        productId: (window.WinShirtData && WinShirtData.product) ? WinShirtData.product.id : 0,

        // Données mockups/zones fournies par PHP (WinShirtData)
        mockups: (window.WinShirtData && WinShirtData.mockups) ? WinShirtData.mockups : {},
        zones: (window.WinShirtData && WinShirtData.zones) ? WinShirtData.zones : {},

        // Config globale
        config: (window.WinShirtData && WinShirtData.config) ? WinShirtData.config : {},

        /**
         * Change le côté actif (recto/verso) et notifie les listeners.
         */
        setSide(side) {
            if(side !== 'front' && side !== 'back') return;
            this.currentSide = side;
            $(document).trigger('winshirt:sideChanged', [side]);
        },

        /**
         * Change la zone active
         */
        setZone(index) {
            this.currentZoneIndex = index;
            $(document).trigger('winshirt:zoneChanged', [index]);
        },

        /**
         * Ajoute un calque sur le côté actif
         */
        addLayer(layer) {
            if(!layer || !layer.id) {
                console.error('WinShirtState.addLayer: layer invalide', layer);
                return;
            }
            this.layers[this.currentSide].push(layer);
            this.selectedLayerId = layer.id;
            $(document).trigger('winshirt:layerAdded', [layer, this.currentSide]);
        },

        /**
         * Supprime un calque par ID
         */
        removeLayer(layerId) {
            this.layers[this.currentSide] = this.layers[this.currentSide].filter(l => l.id !== layerId);
            if(this.selectedLayerId === layerId) {
                this.selectedLayerId = null;
            }
            $(document).trigger('winshirt:layerRemoved', [layerId, this.currentSide]);
        },

        /**
         * Sélectionne un calque
         */
        selectLayer(layerId) {
            this.selectedLayerId = layerId;
            $(document).trigger('winshirt:layerSelected', [layerId, this.currentSide]);
        },

        /**
         * Met à jour un calque
         */
        updateLayer(layerId, updates) {
            const idx = this.layers[this.currentSide].findIndex(l => l.id === layerId);
            if(idx === -1) return;
            Object.assign(this.layers[this.currentSide][idx], updates);
            $(document).trigger('winshirt:layerUpdated', [this.layers[this.currentSide][idx], this.currentSide]);
        },

        /**
         * Récupère le calque sélectionné
         */
        getSelectedLayer() {
            return this.layers[this.currentSide].find(l => l.id === this.selectedLayerId) || null;
        },

        /**
         * Reset complet de l'état
         */
        reset() {
            this.currentSide = 'front';
            this.currentZoneIndex = 0;
            this.layers = { front: [], back: [] };
            this.selectedLayerId = null;
            $(document).trigger('winshirt:reset');
        }
    };

    // Expose globalement
    window.WinShirtState = WinShirtState;

    // Initialisation auto (si besoin)
    $(function(){
        $(document).trigger('winshirt:stateReady', [WinShirtState]);
    });

})(jQuery);
