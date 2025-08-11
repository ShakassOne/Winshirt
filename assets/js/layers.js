/**
 * WinShirt - Gestion des calques (images, textes, QR, etc.)
 * - Ajout / suppression / sélection
 * - Drag & Drop, redimensionnement, rotation
 * - Conservation par côté (front/back) dans WinShirtState
 *
 * Dépendances : jQuery, WinShirtState
 */

(function($){
    'use strict';

    const Layers = {
        $canvas: null, // conteneur principal pour les calques
        dragData: null,

        init($canvasSelector){
            this.$canvas = $($canvasSelector);
            if(!this.$canvas.length){
                console.error('WinShirt Layers: canvas introuvable', $canvasSelector);
                return;
            }

            // Sélection d'un calque
            this.$canvas.on('click', '.ws-layer', (e) => {
                e.stopPropagation();
                const id = $(e.currentTarget).data('id');
                WinShirtState.selectLayer(id);
                this.highlightSelected();
            });

            // Déplacement
            this.$canvas.on('mousedown touchstart', '.ws-layer', (e) => {
                const id = $(e.currentTarget).data('id');
                WinShirtState.selectLayer(id);
                const pos = this._getEventPosition(e);
                const $layer = $(e.currentTarget);
                this.dragData = {
                    id: id,
                    startX: pos.x,
                    startY: pos.y,
                    origLeft: parseFloat($layer.css('left')),
                    origTop: parseFloat($layer.css('top'))
                };
                e.preventDefault();
            });

            $(document).on('mousemove touchmove', (e) => {
                if(!this.dragData) return;
                const pos = this._getEventPosition(e);
                const dx = pos.x - this.dragData.startX;
                const dy = pos.y - this.dragData.startY;
                const $layer = this.$canvas.find(`.ws-layer[data-id="${this.dragData.id}"]`);
                $layer.css({
                    left: this.dragData.origLeft + dx,
                    top: this.dragData.origTop + dy
                });
            });

            $(document).on('mouseup touchend', (e) => {
                if(!this.dragData) return;
                const id = this.dragData.id;
                const $layer = this.$canvas.find(`.ws-layer[data-id="${id}"]`);
                const left = parseFloat($layer.css('left'));
                const top = parseFloat($layer.css('top'));
                WinShirtState.updateLayer(id, { left, top });
                this.dragData = null;
            });

            // Déselection en cliquant sur le vide
            this.$canvas.on('click', (e) => {
                if($(e.target).hasClass('ws-layer')) return;
                WinShirtState.selectLayer(null);
                this.highlightSelected();
            });

            // Réagir aux changements de côté
            $(document).on('winshirt:sideChanged', (e, side) => {
                this.renderSide(side);
            });

            // Initial render
            this.renderSide(WinShirtState.currentSide);
        },

        /**
         * Ajoute un calque visuellement
         */
        addLayerElement(layer){
            if(!layer || !layer.id) return;
            const $el = $(`<div class="ws-layer" data-id="${layer.id}"></div>`);

            $el.css({
                position: 'absolute',
                left: layer.left || 50,
                top: layer.top || 50,
                width: layer.width || 100,
                height: layer.height || 100,
                transform: `rotate(${layer.rotation || 0}deg)`,
                cursor: 'move'
            });

            if(layer.type === 'image' && layer.src){
                $el.append(`<img src="${layer.src}" draggable="false" style="width:100%;height:100%;object-fit:contain;">`);
            } else if(layer.type === 'text' && layer.text){
                $el.append(`<div class="ws-text-content">${layer.text}</div>`);
            }

            this.$canvas.append($el);
        },

        /**
         * Rend le côté demandé
         */
        renderSide(side){
            this.$canvas.empty();
            const layers = WinShirtState.layers[side] || [];
            layers.forEach(layer => {
                this.addLayerElement(layer);
            });
            this.highlightSelected();
        },

        /**
         * Met en évidence le calque sélectionné
         */
        highlightSelected(){
            const selId = WinShirtState.selectedLayerId;
            this.$canvas.find('.ws-layer').removeClass('selected');
            if(selId){
                this.$canvas.find(`.ws-layer[data-id="${selId}"]`).addClass('selected');
            }
        },

        /**
         * Récupère la position (souris ou tactile)
         */
        _getEventPosition(e){
            if(e.originalEvent.touches && e.originalEvent.touches.length){
                return { x: e.originalEvent.touches[0].clientX, y: e.originalEvent.touches[0].clientY };
            }
            return { x: e.clientX, y: e.clientY };
        }
    };

    window.WinShirtLayers = Layers;

    // Boot auto si un canvas existe déjà
    $(function(){
        const $canvas = $('.winshirt-mockup-canvas');
        if($canvas.length){
            Layers.init($canvas);
        }
    });

})(jQuery);
