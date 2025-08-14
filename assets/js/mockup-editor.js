/**
 * WinShirt Mockup Editor - JavaScript (Recovery v1.0)
 */

(function($) {
	'use strict';

	let WinShirtMockupEditor = {
		
		// Variables globales
		canvas: null,
		zones: [],
		activeZone: null,
		activeSide: 'recto',
		isDragging: false,
		isResizing: false,
		zoneCounter: 0,
		
		// Configuration
		config: {
			minZoneSize: 30,
			snapThreshold: 10,
			gridSize: 5
		},

		/**
		 * Initialisation
		 */
		init: function() {
			this.canvas = $('#ws-zones-canvas');
			this.loadExistingZones();
			this.bindEvents();
			this.updateUI();
			
			console.log('WinShirt Mockup Editor initialized');
		},

		/**
		 * Charger les zones existantes
		 */
		loadExistingZones: function() {
			const zonesData = $('#ws-zones-data').val();
			if (zonesData) {
				try {
					this.zones = JSON.parse(zonesData);
					this.zoneCounter = this.zones.length;
				} catch (e) {
					console.error('Error parsing zones data:', e);
					this.zones = [];
				}
			}
		},

		/**
		 * Bind événements
		 */
		bindEvents: function() {
			const self = this;

			// Ajouter couleur
			$('#ws-add-color').on('click', this.addColor.bind(this));
			
			// Supprimer couleur
			$(document).on('click', '.ws-remove-color', this.removeColor.bind(this));
			
			// Upload d'images
			$(document).on('click', '.ws-upload-area', this.uploadImage.bind(this));
			$(document).on('click', '.ws-remove-image', this.removeImage.bind(this));
			
			// Switch recto/verso
			$('input[name="active_side"]').on('change', this.switchSide.bind(this));
			
			// Ajouter zone
			$('#ws-add-zone').on('click', this.addZone.bind(this));
			
			// Sauvegarder zones
			$('#ws-save-zones').on('click', this.saveZones.bind(this));
			
			// Interaction avec zones
			$(document).on('click', '.ws-zone-rectangle', this.selectZone.bind(this));
			$(document).on('click', '.ws-remove-zone-btn', this.removeZone.bind(this));
			
			// Édition nom/prix zones
			$(document).on('input', '.ws-zone-name', this.updateZoneName.bind(this));
			$(document).on('input', '.ws-zone-price', this.updateZonePrice.bind(this));
			
			// Drag & Drop zones
			this.initZoneDragDrop();
			
			// Prévenir submit accidentel
			$('form').on('submit', function(e) {
				self.saveZonesData();
			});
		},

		/**
		 * Ajouter une couleur
		 */
		addColor: function(e) {
			e.preventDefault();
			
			const colorIndex = $('.ws-color-item').length;
			const isFirst = colorIndex === 0;
			
			const colorHtml = `
				<div class="ws-color-item" data-index="${colorIndex}">
					<div class="ws-color-header">
						<div class="ws-color-preview" style="background-color: #ffffff"></div>
						<input type="text" 
							   name="colors[${colorIndex}][name]" 
							   value="Couleur ${colorIndex + 1}"
							   placeholder="${wsMockupEditor.i18n.add_color}"
							   class="ws-color-name" />
						<input type="color" 
							   name="colors[${colorIndex}][hex]" 
							   value="#ffffff"
							   class="ws-color-picker" />
						<label class="ws-default-color">
							<input type="radio" 
								   name="default_color" 
								   value="${colorIndex}"
								   ${isFirst ? 'checked' : ''} />
							Par défaut
						</label>
						<button type="button" class="ws-remove-color button-link-delete">
							<span class="dashicons dashicons-trash"></span>
						</button>
					</div>
					<div class="ws-color-images">
						<div class="ws-image-upload">
							<label>Image Recto:</label>
							<div class="ws-upload-area" data-side="recto">
								<div class="ws-upload-placeholder">
									<span class="dashicons dashicons-plus"></span>
									<span>Cliquez pour uploader</span>
								</div>
							</div>
						</div>
						<div class="ws-image-upload">
							<label>Image Verso:</label>
							<div class="ws-upload-area" data-side="verso">
								<div class="ws-upload-placeholder">
									<span class="dashicons dashicons-plus"></span>
									<span>Cliquez pour uploader</span>
								</div>
							</div>
						</div>
					</div>
				</div>
			`;
			
			$('.ws-no-colors').hide();
			$('#ws-colors-list').append(colorHtml);
			
			// Bind color picker
			$(`.ws-color-item[data-index="${colorIndex}"] .ws-color-picker`).on('change', function() {
				$(this).siblings('.ws-color-preview').css('background-color', $(this).val());
			});
		},

		/**
		 * Supprimer couleur
		 */
		removeColor: function(e) {
			e.preventDefault();
			e.stopPropagation();
			
			if (confirm(wsMockupEditor.i18n.delete_confirm)) {
				$(e.target).closest('.ws-color-item').remove();
				
				if ($('.ws-color-item').length === 0) {
					$('.ws-no-colors').show();
				}
			}
		},

		/**
		 * Upload d'image
		 */
		uploadImage: function(e) {
			e.preventDefault();
			e.stopPropagation();
			
			const $uploadArea = $(e.currentTarget);
			
			// Si c'est le bouton de suppression, ne pas déclencher l'upload
			if ($(e.target).hasClass('ws-remove-image')) {
				return;
			}
			
			const frame = wp.media({
				title: 'Sélectionner une image',
				button: { text: 'Utiliser cette image' },
				multiple: false,
				library: { type: 'image' }
			});
			
			frame.on('select', function() {
				const attachment = frame.state().get('selection').first().toJSON();
				
				$uploadArea.html(`
					<img src="${attachment.url}" class="ws-preview-img" />
					<input type="hidden" name="${$uploadArea.data('input-name') || 'image'}" value="${attachment.url}" />
					<button type="button" class="ws-remove-image">×</button>
				`).addClass('has-image');
				
				// Mettre à jour l'image de fond si c'est la couleur par défaut
				if ($uploadArea.closest('.ws-color-item').find('input[name="default_color"]:checked').length > 0) {
					this.updateCanvasBackground(attachment.url, $uploadArea.data('side'));
				}
			}.bind(this));
			
			frame.open();
		},

		/**
		 * Supprimer image
		 */
		removeImage: function(e) {
			e.preventDefault();
			e.stopPropagation();
			
			const $uploadArea = $(e.target).closest('.ws-upload-area');
			const side = $uploadArea.data('side');
			
			$uploadArea.html(`
				<div class="ws-upload-placeholder">
					<span class="dashicons dashicons-plus"></span>
					<span>Cliquez pour uploader</span>
				</div>
			`).removeClass('has-image');
		},

		/**
		 * Mettre à jour l'arrière-plan du canvas
		 */
		updateCanvasBackground: function(imageUrl, side) {
			const $bg = $('#ws-mockup-bg');
			if (side === this.activeSide) {
				$bg.attr('src', imageUrl);
			}
			$bg.data(side, imageUrl);
		},

		/**
		 * Switch recto/verso
		 */
		switchSide: function(e) {
			this.activeSide = $(e.target).val();
			
			// Mettre à jour l'image de fond
			const $bg = $('#ws-mockup-bg');
			const imageUrl = $bg.data(this.activeSide);
			if (imageUrl) {
				$bg.attr('src', imageUrl);
			}
			
			// Mettre à jour la visibilité des zones
			this.updateZonesVisibility();
			
			console.log('Switched to:', this.activeSide);
		},

		/**
		 * Mettre à jour la visibilité des zones
		 */
		updateZonesVisibility: function() {
			$('.ws-zone-rectangle').each(function() {
				const zoneSide = $(this).data('side') || 'recto';
				if (zoneSide === this.activeSide) {
					$(this).show();
				} else {
					$(this).hide();
				}
			}.bind(this));
		},

		/**
		 * Ajouter une zone
		 */
		addZone: function(e) {
			e.preventDefault();
			
			const zoneId = this.zoneCounter++;
			const zone = {
				id: zoneId,
				name: `Zone ${zoneId + 1}`,
				side: this.activeSide,
				x: 20, // %
				y: 20, // %
				w: 25, // %
				h: 25, // %
				price: 0
			};
			
			this.zones.push(zone);
			this.createZoneElement(zone);
			this.updateZonesList();
			this.selectZone(null, zoneId);
			
			console.log('Added zone:', zone);
		},

		/**
		 * Créer élément zone dans le canvas
		 */
		createZoneElement: function(zone) {
			const zoneHtml = `
				<div class="ws-zone-rectangle" 
					 data-zone-id="${zone.id}"
					 data-side="${zone.side}"
					 style="left: ${zone.x}%; top: ${zone.y}%; width: ${zone.w}%; height: ${zone.h}%;">
					<div class="ws-zone-label">${zone.name}</div>
					<div class="ws-zone-handles">
						<div class="ws-handle ws-handle-nw"></div>
						<div class="ws-handle ws-handle-ne"></div>
						<div class="ws-handle ws-handle-sw"></div>
						<div class="ws-handle ws-handle-se"></div>
					</div>
				</div>
			`;
			
			this.canvas.append(zoneHtml);
			this.updateZonesVisibility();
		},

		/**
		 * Sélectionner une zone
		 */
		selectZone: function(e, zoneId) {
			if (e) {
				e.stopPropagation();
				zoneId = $(e.currentTarget).data('zone-id');
			}
			
			// Désélectionner toutes les zones
			$('.ws-zone-rectangle, .ws-zone-item').removeClass('active');
			
			// Sélectionner la zone
			$(`.ws-zone-rectangle[data-zone-id="${zoneId}"]`).addClass('active');
			$(`.ws-zone-item[data-zone-id="${zoneId}"]`).addClass('active');
			
			this.activeZone = zoneId;
		},

		/**
		 * Supprimer une zone
		 */
		removeZone: function(e) {
			e.preventDefault();
			e.stopPropagation();
			
			const zoneId = $(e.target).closest('.ws-zone-item').data('zone-id');
			
			if (confirm(wsMockupEditor.i18n.delete_confirm)) {
				// Supprimer du DOM
				$(`.ws-zone-rectangle[data-zone-id="${zoneId}"]`).remove();
				$(`.ws-zone-item[data-zone-id="${zoneId}"]`).remove();
				
				// Supprimer du tableau
				this.zones = this.zones.filter(zone => zone.id !== zoneId);
				
				// Réinitialiser la sélection
				this.activeZone = null;
				
				// Mettre à jour l'affichage
				if (this.zones.length === 0) {
					$('.ws-no-zones').show();
				}
				
				console.log('Removed zone:', zoneId);
			}
		},

		/**
		 * Mettre à jour le nom d'une zone
		 */
		updateZoneName: function(e) {
			const zoneId = $(e.target).closest('.ws-zone-item').data('zone-id');
			const newName = $(e.target).val();
			
			// Mettre à jour dans le tableau
			const zone = this.zones.find(z => z.id === zoneId);
			if (zone) {
				zone.name = newName;
			}
			
			// Mettre à jour le label dans le canvas
			$(`.ws-zone-rectangle[data-zone-id="${zoneId}"] .ws-zone-label`).text(newName);
		},

		/**
		 * Mettre à jour le prix d'une zone
		 */
		updateZonePrice: function(e) {
			const zoneId = $(e.target).closest('.ws-zone-item').data('zone-id');
			const newPrice = parseFloat($(e.target).val()) || 0;
			
			// Mettre à jour dans le tableau
			const zone = this.zones.find(z => z.id === zoneId);
			if (zone) {
				zone.price = newPrice;
			}
		},

		/**
		 * Mettre à jour la liste des zones
		 */
		updateZonesList: function() {
			const $list = $('#ws-zones-list');
			
			if (this.zones.length === 0) {
				$list.html('<p class="ws-no-zones">Aucune zone définie.</p>');
				return;
			}
			
			let html = '';
			this.zones.forEach(zone => {
				html += `
					<div class="ws-zone-item" data-zone-id="${zone.id}">
						<input type="text" 
							   value="${zone.name}"
							   placeholder="Nom de la zone"
							   class="ws-zone-name" />
						<input type="number" 
							   value="${zone.price}"
							   step="0.01" 
							   min="0"
							   placeholder="0.00"
							   class="ws-zone-price" />
						<span class="ws-zone-coords">
							${Math.round(zone.w)}×${Math.round(zone.h)}
						</span>
						<button type="button" class="ws-remove-zone-btn">×</button>
					</div>
				`;
			});
			
			$list.html(html);
		},

		/**
		 * Initialiser le drag & drop des zones
		 */
		initZoneDragDrop: function() {
			const self = this;
			
			// Rendre les zones draggables et resizable
			$(document).on('mousedown', '.ws-zone-rectangle', function(e) {
				if ($(e.target).hasClass('ws-handle')) {
					// C'est un resize
					self.initResize(e);
				} else {
					// C'est un drag
					self.initDrag(e);
				}
			});
		},

		/**
		 * Initialiser le drag
		 */
		initDrag: function(e) {
			e.preventDefault();
			const $zone = $(e.currentTarget);
			const zoneId = $zone.data('zone-id');
			
			this.selectZone(null, zoneId);
			this.isDragging = true;
			
			const startX = e.pageX;
			const startY = e.pageY;
			const startLeft = parseFloat($zone.css('left'));
			const startTop = parseFloat($zone.css('top'));
			
			const canvasOffset = this.canvas.offset();
			const canvasWidth = this.canvas.width();
			const canvasHeight = this.canvas.height();
			
			$(document).on('mousemove.zonedrag', function(e) {
				const deltaX = e.pageX - startX;
				const deltaY = e.pageY - startY;
				
				let newLeft = startLeft + (deltaX / canvasWidth * 100);
				let newTop = startTop + (deltaY / canvasHeight * 100);
				
				// Contraintes
				newLeft = Math.max(0, Math.min(newLeft, 100 - parseFloat($zone.css('width')) / canvasWidth * 100));
				newTop = Math.max(0, Math.min(newTop, 100 - parseFloat($zone.css('height')) / canvasHeight * 100));
				
				$zone.css({
					left: newLeft + '%',
					top: newTop + '%'
				});
			});
			
			$(document).on('mouseup.zonedrag', function() {
				$(document).off('.zonedrag');
				self.isDragging = false;
				self.updateZoneData(zoneId, $zone);
			});
		},

		/**
		 * Initialiser le resize
		 */
		initResize: function(e) {
			e.preventDefault();
			e.stopPropagation();
			
			const $handle = $(e.target);
			const $zone = $handle.closest('.ws-zone-rectangle');
			const zoneId = $zone.data('zone-id');
			const handleClass = $handle.attr('class');
			
			this.isResizing = true;
			
			const startX = e.pageX;
			const startY = e.pageY;
			const startWidth = $zone.width();
			const startHeight = $zone.height();
			const startLeft = parseFloat($zone.css('left'));
			const startTop = parseFloat($zone.css('top'));
			
			const canvasWidth = this.canvas.width();
			const canvasHeight = this.canvas.height();
			
			$(document).on('mousemove.zoneresize', function(e) {
				const deltaX = e.pageX - startX;
				const deltaY = e.pageY - startY;
				
				let newWidth = startWidth;
				let newHeight = startHeight;
				let newLeft = startLeft;
				let newTop = startTop;
				
				// Calculer selon la poignée
				if (handleClass.includes('ws-handle-se')) {
					// Coin sud-est
					newWidth = Math.max(self.config.minZoneSize, startWidth + deltaX);
					newHeight = Math.max(self.config.minZoneSize, startHeight + deltaY);
				} else if (handleClass.includes('ws-handle-sw')) {
					// Coin sud-ouest
					newWidth = Math.max(self.config.minZoneSize, startWidth - deltaX);
					newHeight = Math.max(self.config.minZoneSize, startHeight + deltaY);
					newLeft = startLeft + (deltaX / canvasWidth * 100);
				} else if (handleClass.includes('ws-handle-ne')) {
					// Coin nord-est
					newWidth = Math.max(self.config.minZoneSize, startWidth + deltaX);
					newHeight = Math.max(self.config.minZoneSize, startHeight - deltaY);
					newTop = startTop + (deltaY / canvasHeight * 100);
				} else if (handleClass.includes('ws-handle-nw')) {
					// Coin nord-ouest
					newWidth = Math.max(self.config.minZoneSize, startWidth - deltaX);
					newHeight = Math.max(self.config.minZoneSize, startHeight - deltaY);
					newLeft = startLeft + (deltaX / canvasWidth * 100);
					newTop = startTop + (deltaY / canvasHeight * 100);
				}
				
				// Contraintes canvas
				const rightLimit = 100 - (newLeft);
				const bottomLimit = 100 - (newTop);
				
				if (newWidth / canvasWidth * 100 > rightLimit) {
					newWidth = rightLimit / 100 * canvasWidth;
				}
				if (newHeight / canvasHeight * 100 > bottomLimit) {
					newHeight = bottomLimit / 100 * canvasHeight;
				}
				
				$zone.css({
					width: newWidth + 'px',
					height: newHeight + 'px',
					left: newLeft + '%',
					top: newTop + '%'
				});
			});
			
			$(document).on('mouseup.zoneresize', function() {
				$(document).off('.zoneresize');
				self.isResizing = false;
				self.updateZoneData(zoneId, $zone);
			});
		},

		/**
		 * Mettre à jour les données d'une zone
		 */
		updateZoneData: function(zoneId, $zone) {
			const canvasWidth = this.canvas.width();
			const canvasHeight = this.canvas.height();
			
			const zone = this.zones.find(z => z.id == zoneId);
			if (!zone) return;
			
			// Calculer les nouvelles positions/dimensions en pourcentage
			zone.x = Math.round((parseFloat($zone.css('left')) / canvasWidth * 100) * 10) / 10;
			zone.y = Math.round((parseFloat($zone.css('top')) / canvasHeight * 100) * 10) / 10;
			zone.w = Math.round(($zone.width() / canvasWidth * 100) * 10) / 10;
			zone.h = Math.round(($zone.height() / canvasHeight * 100) * 10) / 10;
			
			// Mettre à jour l'affichage des coordonnées
			$(`.ws-zone-item[data-zone-id="${zoneId}"] .ws-zone-coords`).text(
				`${Math.round(zone.w)}×${Math.round(zone.h)}`
			);
			
			console.log('Updated zone data:', zone);
		},

		/**
		 * Sauvegarder les zones
		 */
		saveZones: function(e) {
			if (e) e.preventDefault();
			
			this.saveZonesData();
			
			// Envoyer via AJAX
			$.post(wsMockupEditor.ajaxurl, {
				action: 'ws_save_zones',
				post_id: wsMockupEditor.post_id,
				zones: JSON.stringify(this.zones),
				nonce: wsMockupEditor.nonce
			}, function(response) {
				if (response.success) {
					this.showMessage(wsMockupEditor.i18n.save_success, 'success');
				} else {
					this.showMessage('Erreur lors de la sauvegarde', 'error');
				}
			}.bind(this));
		},

		/**
		 * Sauvegarder les données dans le textarea caché
		 */
		saveZonesData: function() {
			$('#ws-zones-data').val(JSON.stringify(this.zones));
		},

		/**
		 * Afficher un message
		 */
		showMessage: function(text, type) {
			const $message = $(`<div class="ws-message ${type}">${text}</div>`);
			$('.ws-zones-controls').after($message);
			
			setTimeout(function() {
				$message.fadeOut(function() {
					$(this).remove();
				});
			}, 3000);
		},

		/**
		 * Mettre à jour l'interface
		 */
		updateUI: function() {
			// Masquer zones vides si nécessaire
			if ($('.ws-color-item').length === 0) {
				$('.ws-no-colors').show();
			}
			
			if (this.zones.length === 0) {
				$('.ws-no-zones').show();
			}
			
			// Mettre à jour la liste des zones
			this.updateZonesList();
		}
	};

	/**
	 * Initialisation au ready
	 */
	$(document).ready(function() {
		// Vérifier qu'on est sur la bonne page
		if ($('#ws-zones-canvas').length > 0) {
			WinShirtMockupEditor.init();
		}
	});

	/**
	 * Gestionnaire de redimensionnement de fenêtre
	 */
	$(window).on('resize', function() {
		// Recalculer les positions des zones si nécessaire
		if (WinShirtMockupEditor.canvas) {
			$('.ws-zone-rectangle').each(function() {
				const zoneId = $(this).data('zone-id');
				const zone = WinShirtMockupEditor.zones.find(z => z.id == zoneId);
				if (zone) {
					// Repositionner selon les pourcentages sauvegardés
					$(this).css({
						left: zone.x + '%',
						top: zone.y + '%',
						width: zone.w + '%',
						height: zone.h + '%'
					});
				}
			});
		}
	});

	/**
	 * Prévenir la perte de données
	 */
	$(window).on('beforeunload', function() {
		if (WinShirtMockupEditor.zones && WinShirtMockupEditor.zones.length > 0) {
			WinShirtMockupEditor.saveZonesData();
		}
	});

})(jQuery);
