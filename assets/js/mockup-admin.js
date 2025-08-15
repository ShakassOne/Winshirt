/**
 * WinShirt Mockup Admin JavaScript
 * Interface d'édition des mockups - Version corrigée
 */

jQuery(document).ready(function($) {
    let currentSide = 'front';
    let zones = {};
    let isCreatingZone = false;
    let draggedZone = null;
    let isResizing = false;
    let startX, startY, startLeft, startTop, startWidth, startHeight;

    // Initialisation
    initializeMockupEditor();

    function initializeMockupEditor() {
        console.log('Initialisation de l\'éditeur de mockup');
        
        // Charger les zones existantes
        loadExistingZones();
        
        // Mettre à jour le canvas
        updateCanvas();
        
        // Bind des événements
        bindEvents();
    }

    function bindEvents() {
        // Switch recto/verso
        $('.side-switch .btn').off('click').on('click', function(e) {
            e.preventDefault();
            $('.side-switch .btn').removeClass('active');
            $(this).addClass('active');
            currentSide = $(this).data('side');
            updateCanvas();
        });

        // Bouton ajouter une zone
        $('#add-zone-btn').off('click').on('click', function(e) {
            e.preventDefault();
            addZoneAtCenter();
        });

        // Double-clic pour créer une zone
        $('#zone-canvas').off('dblclick').on('dblclick', function(e) {
            if (isResizing) return;
            
            const rect = this.getBoundingClientRect();
            const x = ((e.clientX - rect.left) / rect.width) * 100;
            const y = ((e.clientY - rect.top) / rect.height) * 100;
            
            createZone(x, y);
        });

        // Gestion des couleurs
        $('#add-color').off('click').on('click', function(e) {
            e.preventDefault();
            addNewColor();
        });

        $(document).off('click', '.remove-color').on('click', '.remove-color', function(e) {
            e.preventDefault();
            removeColor($(this).data('color-id'));
        });

        // Upload d'images
        $(document).off('click', '.upload-image').on('click', '.upload-image', function(e) {
            e.preventDefault();
            openMediaUploader($(this));
        });

        // Changement de couleur par défaut
        $(document).off('change', 'input[name="_default_color"]').on('change', 'input[name="_default_color"]', function() {
            updateCanvas();
        });
    }

    function addZoneAtCenter() {
        createZone(40, 40); // Position par défaut au centre-ish
    }

    function createZone(x, y, existingData = null) {
        const zoneId = existingData ? existingData.id : 'zone_' + Date.now();
        
        const zoneData = existingData || {
            id: zoneId,
            name: 'Zone ' + (Object.keys(zones).length + 1),
            side: currentSide,
            x: Math.max(0, Math.min(80, x)),
            y: Math.max(0, Math.min(80, y)),
            width: 15,
            height: 10,
            price: 0
        };

        zones[zoneId] = zoneData;
        
        if (zoneData.side === currentSide) {
            createZoneElement(zoneData);
        }
        
        addZoneToList(zoneData);
        saveZones();
    }

    function createZoneElement(zoneData) {
        const zoneEl = $(`
            <div class="zone-element" data-zone-id="${zoneData.id}" 
                 style="left: ${zoneData.x}%; top: ${zoneData.y}%; width: ${zoneData.width}%; height: ${zoneData.height}%;">
                <div class="zone-label">${zoneData.name}</div>
                
                <!-- Poignées de redimensionnement -->
                <div class="resize-handle nw-resize" data-direction="nw"></div>
                <div class="resize-handle n-resize" data-direction="n"></div>
                <div class="resize-handle ne-resize" data-direction="ne"></div>
                <div class="resize-handle w-resize" data-direction="w"></div>
                <div class="resize-handle e-resize" data-direction="e"></div>
                <div class="resize-handle sw-resize" data-direction="sw"></div>
                <div class="resize-handle s-resize" data-direction="s"></div>
                <div class="resize-handle se-resize" data-direction="se"></div>
            </div>
        `);

        $('#zone-canvas').append(zoneEl);
        makeZoneDraggable(zoneEl);
        makeZoneResizable(zoneEl);
    }

    function makeZoneDraggable(zoneEl) {
        zoneEl.off('mousedown').on('mousedown', function(e) {
            if ($(e.target).hasClass('resize-handle') || isResizing) return;
            
            e.preventDefault();
            draggedZone = $(this);
            
            const canvasRect = $('#zone-canvas')[0].getBoundingClientRect();
            startX = e.clientX;
            startY = e.clientY;
            startLeft = parseFloat(draggedZone.css('left'));
            startTop = parseFloat(draggedZone.css('top'));

            $(document).on('mousemove.drag', function(e) {
                const deltaX = ((e.clientX - startX) / canvasRect.width) * 100;
                const deltaY = ((e.clientY - startY) / canvasRect.height) * 100;
                
                const newLeft = Math.max(0, Math.min(100 - parseFloat(draggedZone.css('width')), startLeft + deltaX));
                const newTop = Math.max(0, Math.min(100 - parseFloat(draggedZone.css('height')), startTop + deltaY));
                
                draggedZone.css({
                    left: newLeft + '%',
                    top: newTop + '%'
                });
                
                updateZoneData(draggedZone.data('zone-id'), {
                    x: newLeft,
                    y: newTop
                });
            });

            $(document).on('mouseup.drag', function() {
                $(document).off('.drag');
                draggedZone = null;
                saveZones();
            });
        });
    }

    function makeZoneResizable(zoneEl) {
        zoneEl.find('.resize-handle').off('mousedown').on('mousedown', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            isResizing = true;
            const direction = $(this).data('direction');
            const zoneId = zoneEl.data('zone-id');
            const canvasRect = $('#zone-canvas')[0].getBoundingClientRect();
            
            startX = e.clientX;
            startY = e.clientY;
            startLeft = parseFloat(zoneEl.css('left'));
            startTop = parseFloat(zoneEl.css('top'));
            startWidth = parseFloat(zoneEl.css('width'));
            startHeight = parseFloat(zoneEl.css('height'));

            $(document).on('mousemove.resize', function(e) {
                const deltaX = ((e.clientX - startX) / canvasRect.width) * 100;
                const deltaY = ((e.clientY - startY) / canvasRect.height) * 100;
                
                let newLeft = startLeft;
                let newTop = startTop;
                let newWidth = startWidth;
                let newHeight = startHeight;

                switch(direction) {
                    case 'se':
                        newWidth = Math.max(5, Math.min(100 - startLeft, startWidth + deltaX));
                        newHeight = Math.max(5, Math.min(100 - startTop, startHeight + deltaY));
                        break;
                    case 'sw':
                        newWidth = Math.max(5, startWidth - deltaX);
                        newLeft = Math.max(0, startLeft + deltaX);
                        newHeight = Math.max(5, Math.min(100 - startTop, startHeight + deltaY));
                        break;
                    case 'ne':
                        newWidth = Math.max(5, Math.min(100 - startLeft, startWidth + deltaX));
                        newHeight = Math.max(5, startHeight - deltaY);
                        newTop = Math.max(0, startTop + deltaY);
                        break;
                    case 'nw':
                        newWidth = Math.max(5, startWidth - deltaX);
                        newLeft = Math.max(0, startLeft + deltaX);
                        newHeight = Math.max(5, startHeight - deltaY);
                        newTop = Math.max(0, startTop + deltaY);
                        break;
                    case 'n':
                        newHeight = Math.max(5, startHeight - deltaY);
                        newTop = Math.max(0, startTop + deltaY);
                        break;
                    case 's':
                        newHeight = Math.max(5, Math.min(100 - startTop, startHeight + deltaY));
                        break;
                    case 'e':
                        newWidth = Math.max(5, Math.min(100 - startLeft, startWidth + deltaX));
                        break;
                    case 'w':
                        newWidth = Math.max(5, startWidth - deltaX);
                        newLeft = Math.max(0, startLeft + deltaX);
                        break;
                }

                // Contraintes
                if (newLeft + newWidth > 100) {
                    newWidth = 100 - newLeft;
                }
                if (newTop + newHeight > 100) {
                    newHeight = 100 - newTop;
                }

                zoneEl.css({
                    left: newLeft + '%',
                    top: newTop + '%',
                    width: newWidth + '%',
                    height: newHeight + '%'
                });
                
                updateZoneData(zoneId, {
                    x: newLeft,
                    y: newTop,
                    width: newWidth,
                    height: newHeight
                });
            });

            $(document).on('mouseup.resize', function() {
                $(document).off('.resize');
                isResizing = false;
                saveZones();
            });
        });
    }

    function updateZoneData(zoneId, newData) {
        if (zones[zoneId]) {
            Object.assign(zones[zoneId], newData);
        }
    }

    function addZoneToList(zoneData) {
        const listItem = $(`
            <div class="zone-item" data-zone-id="${zoneData.id}">
                <input type="text" class="zone-name" value="${zoneData.name}" placeholder="Nom de la zone" />
                <select class="zone-side">
                    <option value="front" ${zoneData.side === 'front' ? 'selected' : ''}>Recto</option>
                    <option value="back" ${zoneData.side === 'back' ? 'selected' : ''}>Verso</option>
                </select>
                <input type="number" class="zone-price" value="${zoneData.price}" step="0.01" min="0" placeholder="Prix €" />
                <button type="button" class="remove-zone">Supprimer</button>
            </div>
        `);

        $('#zones-list').append(listItem);
        
        // Events pour la liste
        listItem.find('.zone-name').on('change', function() {
            updateZoneData(zoneData.id, { name: $(this).val() });
            updateZoneLabel(zoneData.id, $(this).val());
            saveZones();
        });
        
        listItem.find('.zone-side').on('change', function() {
            updateZoneData(zoneData.id, { side: $(this).val() });
            updateCanvas();
            saveZones();
        });
        
        listItem.find('.zone-price').on('change', function() {
            updateZoneData(zoneData.id, { price: parseFloat($(this).val()) || 0 });
            saveZones();
        });
        
        listItem.find('.remove-zone').on('click', function() {
            removeZone(zoneData.id);
        });
    }

    function updateZoneLabel(zoneId, newName) {
        $(`.zone-element[data-zone-id="${zoneId}"] .zone-label`).text(newName);
    }

    function removeZone(zoneId) {
        delete zones[zoneId];
        $(`.zone-element[data-zone-id="${zoneId}"]`).remove();
        $(`.zone-item[data-zone-id="${zoneId}"]`).remove();
        saveZones();
    }

    function updateCanvas() {
        $('.zone-element').remove();
        
        // Charger la bonne image de fond
        loadBackgroundImage();
        
        // Afficher les zones du côté actuel
        Object.values(zones).forEach(function(zoneData) {
            if (zoneData.side === currentSide) {
                createZoneElement(zoneData);
            }
        });
    }

    function loadBackgroundImage() {
        const defaultColor = $('input[name="_default_color"]:checked').val();
        if (!defaultColor) return;

        const colorRow = $(`.color-row[data-color-id="${defaultColor}"]`);
        const imageUrl = currentSide === 'front' 
            ? colorRow.find('input[name*="[front]"]').val()
            : colorRow.find('input[name*="[back]"]').val();

        if (imageUrl) {
            $('#zone-canvas').css({
                'background-image': `url(${imageUrl})`,
                'background-size': 'contain',
                'background-repeat': 'no-repeat',
                'background-position': 'center'
            });
        }
    }

    function loadExistingZones() {
        const zonesInput = $('input[name="_zones"]');
        if (zonesInput.length && zonesInput.val()) {
            try {
                const existingZones = JSON.parse(zonesInput.val());
                zones = existingZones;
                
                Object.values(zones).forEach(function(zoneData) {
                    addZoneToList(zoneData);
                });
            } catch (e) {
                console.error('Erreur lors du chargement des zones:', e);
            }
        }
    }

    function saveZones() {
        $('input[name="_zones"]').val(JSON.stringify(zones));
        
        // Sauvegarder via AJAX si disponible
        if (typeof winshirtAjax !== 'undefined') {
            const data = {
                action: 'save_mockup_zones',
                post_id: $('#post_ID').val(),
                zones: zones,
                nonce: winshirtAjax.nonce
            };

            $.post(winshirtAjax.ajaxurl, data, function(response) {
                if (response.success) {
                    console.log('Zones sauvegardées');
                }
            });
        }
    }

    function addNewColor() {
        const colorCount = $('.color-row').length;
        const colorId = 'color_' + Date.now();
        
        const colorHtml = `
            <div class="color-row" data-color-id="${colorId}">
                <div class="color-controls">
                    <label>Couleur:</label>
                    <input type="color" class="color-picker" value="#FFFFFF" />
                    <input type="text" class="color-name" placeholder="Nom couleur" value="Couleur ${colorCount + 1}" />
                    <label>
                        <input type="radio" name="_default_color" value="${colorId}" ${colorCount === 0 ? 'checked' : ''} />
                        Par défaut
                    </label>
                    <button type="button" class="remove-color" data-color-id="${colorId}">Supprimer</button>
                </div>
                <div class="color-images">
                    <div class="image-section">
                        <label>Image Recto:</label>
                        <button type="button" class="upload-image" data-side="front" data-color="${colorId}">Choisir Image Recto</button>
                        <input type="hidden" name="colors[${colorId}][front]" />
                        <div class="image-preview"></div>
                    </div>
                    <div class="image-section">
                        <label>Image Verso:</label>
                        <button type="button" class="upload-image" data-side="back" data-color="${colorId}">Choisir Image Verso</button>
                        <input type="hidden" name="colors[${colorId}][back]" />
                        <div class="image-preview"></div>
                    </div>
                </div>
            </div>
        `;
        
        $('#colors-container').append(colorHtml);
        
        // Si c'est la première couleur, la marquer comme par défaut
        if (colorCount === 0) {
            updateCanvas();
        }
    }

    function removeColor(colorId) {
        $(`.color-row[data-color-id="${colorId}"]`).remove();
        
        // Si on supprime la couleur par défaut, marquer la première restante
        if (!$('input[name="_default_color"]:checked').length) {
            $('input[name="_default_color"]').first().prop('checked', true);
            updateCanvas();
        }
    }

    function openMediaUploader(button) {
        const colorId = button.data('color');
        const side = button.data('side');
        
        // WordPress Media Uploader
        const mediaUploader = wp.media({
            title: 'Choisir une image',
            button: {
                text: 'Utiliser cette image'
            },
            multiple: false
        });

        mediaUploader.on('select', function() {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            const input = button.siblings(`input[name="colors[${colorId}][${side}]"]`);
            const preview = button.siblings('.image-preview');
            
            input.val(attachment.url);
            preview.html(`<img src="${attachment.url}" style="max-width: 100px; height: auto;" />`);
            
            // Mettre à jour le canvas si c'est la couleur par défaut
            const isDefault = $(`.color-row[data-color-id="${colorId}"] input[name="_default_color"]`).is(':checked');
            if (isDefault) {
                updateCanvas();
            }
        });

        mediaUploader.open();
    }

    // Rendre les fonctions disponibles globalement pour debug
    window.WinShirtDebug = {
        zones: zones,
        updateCanvas: updateCanvas,
        saveZones: saveZones
    };
});
