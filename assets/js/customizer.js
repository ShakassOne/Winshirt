(function($) {
    'use strict';
    
    const WinShirtCustomizer = {
        
        // État central
        state: {
            currentSide: 'front',
            activeZone: null,
            layers: {
                front: [],
                back: []
            },
            initialized: false
        },
        
        // Éléments DOM
        $modal: null,
        $canvas: null,
        $mockupImg: null,
        
        // Initialisation
        init() {
            if (this.state.initialized) return;
            
            this.bindEvents();
            this.initCanvas();
            this.state.initialized = true;
            
            console.log('WinShirt Customizer initialized');
        },
        
        // Events principaux
        bindEvents() {
            // Pas de binding automatique ici - fait dans assets.php
            $(document).on('click', '.ws-close', (e) => {
                e.preventDefault();
                this.closeModal();
            });
            
            $(document).on('click', '.ws-side-btn', (e) => {
                e.preventDefault();
                const side = $(e.target).data('side');
                this.switchSide(side);
            });
            
            $(document).on('click', '.ws-save', (e) => {
                e.preventDefault();
                this.saveDesign();
            });
            
            $(document).on('click', '.ws-add-cart', (e) => {
                e.preventDefault();
                this.addToCart();
            });
            
            // Fermeture par overlay
            $(document).on('click', '.winshirt-modal', (e) => {
                if ($(e.target).hasClass('winshirt-modal')) {
                    this.closeModal();
                }
            });
            
            // Échap pour fermer
            $(document).on('keydown', (e) => {
                if (e.key === 'Escape' && this.$modal && this.$modal.hasClass('is-open')) {
                    this.closeModal();
                }
            });
        },
        
        // Canvas et zones
        initCanvas() {
            this.$modal = $('#winshirt-customizer-modal');
            this.$canvas = $('#winshirt-canvas');
            this.$mockupImg = this.$canvas.find('.winshirt-mockup-img');
            
            if (!this.$canvas.length) {
                console.warn('WinShirt: Canvas non trouvé');
                return;
            }
        },
        
        // Modal
        openModal() {
            if (!this.$modal || !this.$modal.length) {
                console.error('WinShirt: Modal non trouvée');
                return;
            }
            
            this.$modal
                .addClass('is-open')
                .attr('aria-hidden', 'false');
            $('body').addClass('ws-modal-open');
            
            // Charger les données mockup
            this.loadMockupData();
            
            // Initialiser l'état
            this.refreshCanvas();
            
            console.log('Modal opened');
        },
        
        closeModal() {
            if (!this.$modal) return;
            
            this.$modal
                .removeClass('is-open')
                .attr('aria-hidden', 'true');
            $('body').removeClass('ws-modal-open');
            
            console.log('Modal closed');
        },
        
        loadMockupData() {
            if (!window.WinShirtData || !window.WinShirtData.mockupData) {
                console.warn('WinShirt: Données mockup manquantes');
                this.showError('Aucun mockup configuré pour ce produit');
                return;
            }
            
            const mockup = window.WinShirtData.mockupData;
            
            // Charger images
            if (mockup.images) {
                if (mockup.images.front) {
                    this.$canvas.find('[data-side="front"]').attr('src', mockup.images.front);
                }
                if (mockup.images.back) {
                    this.$canvas.find('[data-side="back"]').attr('src', mockup.images.back);
                }
            }
            
            // Charger zones
            if (mockup.zones) {
                this.renderZones(mockup.zones);
            } else {
                this.showZoneHint('Aucune zone d\'impression définie');
            }
        },
        
        renderZones(zones) {
            // Supprimer zones existantes
            this.$canvas.find('.ws-print-zone').remove();
            
            const currentZones = zones[this.state.currentSide] || [];
            
            if (currentZones.length === 0) {
                this.showZoneHint('Aucune zone définie pour ce côté');
                return;
            }
            
            currentZones.forEach((zone, index) => {
                const $zone = $('<div class="ws-print-zone"></div>');
                $zone.css({
                    position: 'absolute',
                    left: zone.left + '%',
                    top: zone.top + '%',
                    width: zone.width + '%',
                    height: zone.height + '%'
                });
                $zone.attr('data-zone-index', index);
                $zone.attr('data-zone-name', zone.name || `Zone ${index + 1}`);
                
                this.$canvas.append($zone);
            });
            
            // Activer première zone
            if (currentZones.length > 0) {
                this.state.activeZone = currentZones[0];
                this.$canvas.find('.ws-print-zone').first().addClass('active');
            }
            
            // Masquer hint
            this.hideZoneHint();
        },
        
        showZoneHint(message) {
            this.$canvas.find('.ws-zone-hint').remove();
            const $hint = $('<div class="ws-zone-hint"></div>').text(message);
            this.$canvas.append($hint);
        },
        
        hideZoneHint() {
            this.$canvas.find('.ws-zone-hint').remove();
        },
        
        switchSide(side) {
            if (side === this.state.currentSide) return;
            
            console.log('Switching to side:', side);
            
            // Sauvegarder état actuel
            this.saveCurrentState();
            
            // Changer côté
            this.state.currentSide = side;
            
            // Mettre à jour UI
            $('.ws-side-btn').removeClass('active');
            $(`.ws-side-btn[data-side="${side}"]`).addClass('active');
            
            // Afficher/masquer images mockup
            this.$canvas.find('.winshirt-mockup-img').hide();
            this.$canvas.find(`[data-side="${side}"]`).show();
            
            // Re-render zones et layers
            this.refreshCanvas();
        },
        
        refreshCanvas() {
            const mockup = window.WinShirtData ? window.WinShirtData.mockupData : null;
            if (mockup && mockup.zones) {
                this.renderZones(mockup.zones);
            }
            this.renderLayers();
        },
        
        renderLayers() {
            // Supprimer layers existants
            this.$canvas.find('.ws-layer').remove();
            
            // Rendre layers pour côté actuel
            const layers = this.state.layers[this.state.currentSide] || [];
            layers.forEach(layer => this.renderLayer(layer));
        },
        
        renderLayer(layer) {
            const $layer = $('<div class="ws-layer"></div>');
            $layer.css({
                position: 'absolute',
                left: layer.x + 'px',
                top: layer.y + 'px',
                width: layer.width + 'px',
                height: layer.height + 'px',
                zIndex: layer.zIndex || 1
            });
            
            if (layer.type === 'image') {
                $layer.append(`<img src="${layer.src}" style="width:100%;height:100%;object-fit:contain;" alt="" />`);
            } else if (layer.type === 'text') {
                $layer.append(`<div class="ws-text">${layer.content}</div>`);
            }
            
            this.$canvas.append($layer);
        },
        
        saveCurrentState() {
            // Sauvegarder l'état des layers pour le côté actuel
            // Implementation future pour persistence
        },
        
        // Actions
        saveDesign() {
            if (!window.WinShirtData) {
                this.showError('Données manquantes pour la sauvegarde');
                return;
            }
            
            const designData = {
                layers: this.state.layers,
                activeZone: this.state.activeZone,
                currentSide: this.state.currentSide,
                timestamp: Date.now()
            };
            
            $.ajax({
                url: window.WinShirtData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'winshirt_save_design',
                    nonce: window.WinShirtData.nonce,
                    product_id: window.WinShirtData.productId,
                    design_data: JSON.stringify(designData)
                },
                success: (response) => {
                    if (response.success) {
                        this.showSuccess('Design sauvegardé avec succès');
                    } else {
                        this.showError(response.data?.message || 'Erreur de sauvegarde');
                    }
                },
                error: () => {
                    this.showError('Erreur réseau lors de la sauvegarde');
                }
            });
        },
        
        addToCart() {
            // Pour l'instant, juste sauvegarder puis rediriger
            this.saveDesign();
            
            // Redirection simple vers ajout panier
            setTimeout(() => {
                const form = $('<form>', {
                    method: 'POST',
                    action: window.location.href
                });
                
                form.append($('<input>', {
                    type: 'hidden',
                    name: 'add-to-cart',
                    value: window.WinShirtData.productId
                }));
                
                form.append($('<input>', {
                    type: 'hidden',
                    name: 'winshirt_design_data',
                    value: JSON.stringify({
                        layers: this.state.layers,
                        currentSide: this.state.currentSide,
                        timestamp: Date.now()
                    })
                }));
                
                $('body').append(form);
                form.submit();
            }, 500);
        },
        
        // Utilitaires
        showError(message) {
            console.error('WinShirt Error:', message);
            // TODO: Implémenter notification UI
            alert('Erreur: ' + message);
        },
        
        showSuccess(message) {
            console.log('WinShirt Success:', message);
            // TODO: Implémenter notification UI
        },
        
        // Getters
        getCurrentSide() {
            return this.state.currentSide;
        },
        
        getActiveZone() {
            return this.state.activeZone;
        }
    };
    
    // Auto-init quand DOM prêt
    $(document).ready(() => {
        WinShirtCustomizer.init();
    });
    
    // Expose globalement
    window.WinShirtCustomizer = WinShirtCustomizer;
    
})(jQuery);
