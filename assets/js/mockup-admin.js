/**
 * WinShirt Mockup Admin JavaScript
 * Gestion de l'interface d'édition des mockups avec zones redimensionnables
 */

(function($) {
    'use strict';

    let currentSide = 'front';
    let zones = {};
    let draggedZone = null;
    let isResizing = false;
    let resizeHandle = null;
    let canvas = null;
    let canvasRect = null;
    let zoneCounter = 0;

    $(document).ready(function() {
        initMockupEditor();
    });

    function initMockupEditor() {
        canvas = $('#zone-canvas')[0];
        if (!canvas) return;

        loadExistingZones();
        bindEvents();
        updateCanvas();
    }

    function bindEvents() {
        // Switch recto/verso
        $('.side-switch .btn').on('click', function() {
            $('.side-switch .btn').removeClass('active');
            $(this).addClass('active');
            currentSide = $(this).data('side');
            updateCanvas();
        });

        // Double-clic pour créer une zone
        $('#zone-canvas').on('dblclick', function(e) {
            if (isResizing) return;
            
            const rect = this.getBoundingClientRect();
            const x = ((e.clientX - rect.left) / rect.width) * 100;
            const y = ((e.clientY - rect.top) / rect.height) * 100;
            
            createZone(x, y);
        });

        // Gestion couleurs
        $('#add-color').on('click', function() {
            addNewColor();
        });

        $(document).on('click', '.remove-color', function() {
            removeColor($(this).data('color-id'));
        });

        $(document).on('change', '.color-picker', function() {
            updateColorHex($(this).data('color-id'), $(this).val());
        });

        // Sauvegarde automatique
        setInterval(autoSave, 5000);
    }

    function createZone(x, y, existingData = null) {
        zoneCounter++;
        const zoneId = existingData ? existingData.id : 'zone_' + zoneCounter + '_' + Date.now();
        
        const zoneData = existingData || {
            id: zoneId,
            name: 'Zone ' + zoneCounter,
            side: currentSide,
            x: Math.max(0, Math.min(80, x)),
            y: Math.max(0, Math.min(80, y)),
            width: 15,
            height: 10,
            price: 0
        };

        zones[zoneId] = zoneData;
        
        createZoneElement(zoneData);
        addZoneToList(zoneData);
        saveZones();
    }

    function createZoneElement(zoneData) {
        if (zoneData.side !== currentSide) return;

        const zoneEl = $(`
            <div class="zone-element" data-zone-id="${zoneData.id}" 
                 style="left: ${zoneData.x}%; top: ${zoneData.y}%; width: ${zoneData.width}%; height: ${zoneData.height}%;">
                <div class="zone-label">${zoneData.name}</div>
                <div class="zone-handle resize-handle-se"></div>
                <div class="zone-handle resize-handle-ne"></div>
                <div class="zone-handle resize-handle-sw"></div>
                <div class="zone-handle resize-handle-nw"></div>
                <div class="zone-handle resize-handle-n"></div>
                <div class="zone-handle resize-handle-s"></div>
                <div class="zone-handle resize-handle-e"></div>
                <div class="zone-handle resize-handle-w"></div>
            </div>
        `);

        $('#zone-canvas').append(zoneEl);
        makeZoneDraggable(zoneEl);
        makeZoneResizable(zoneEl);
    }

    function makeZoneDraggable(zoneEl) {
        zoneEl.on('mousedown', function(e) {
            if ($(e.target).hasClass('zone-handle') || isResizing) return;
            
            e.preventDefault();
            draggedZone = $(this);
            canvasRect = canvas.getBoundingClientRect();
            
            const startX = e.clientX;
            const startY = e.clientY;
            const startLeft = parseFloat(draggedZone.css('left'));
            const startTop = parseFloat(draggedZone.css('top'));

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
        zoneEl.find('.zone-handle').on('mousedown', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            isResizing = true;
            resizeHandle = $(this);
            const zoneId = zoneEl.data('zone-id');
            canvasRect = canvas.getBoundingClientRect();
            
            const startX = e.clientX;
            const startY = e.clientY;
            const startLeft = parseFloat(zoneEl.css('left'));
            const startTop = parseFloat(zoneEl.css('top'));
            const startWidth = parseFloat(zoneEl.css('width'));
            const startHeight = parseFloat(zoneEl.css('height'));
            
            const handleClass = resizeHandle.attr('class');

            $(document).on('mousemove.resize', function(e) {
                const deltaX = ((e.clientX - startX) / canvasRect.width) * 100;
                const deltaY = ((e.clientY - startY) / canvasRect.height) * 100;
                
                let newLeft = startLeft;
                let newTop = startTop;
                let newWidth = startWidth;
                let newHeight = startHeight;

                // Gestion des différentes poignées de redimensionnement
                if (handleClass.includes('resize-handle-se')) {
                    // Sud-Est : augmente largeur et hauteur
                    newWidth = Math.max(5, Math.min(100 - startLeft, startWidth + deltaX));
                    newHeight = Math.max(5, Math.min(100 - startTop, startHeight + deltaY));
                } else if (handleClass.includes('resize-handle-sw')) {
                    // Sud-Ouest : diminue largeur à gauche, augmente hauteur
                    newWidth = Math.max(5, startWidth - deltaX);
                    newLeft = Math.max(0, startLeft + deltaX);
                    newHeight = Math.max(5, Math.min(100 - startTop, startHeight + deltaY));
                } else if (handleClass.includes('resize-handle-ne')) {
                    // Nord-Est : augmente largeur, diminue hauteur en haut
                    newWidth = Math.max(5, Math.min(100 - startLeft, startWidth + deltaX));
                    newHeight = Math.max(5, startHeight - deltaY);
                    newTop = Math.max(0, startTop + deltaY);
                } else if (handleClass.includes('resize-handle-nw')) {
                    // Nord-Ouest : diminue largeur et hauteur en haut-gauche
                    newWidth = Math.max(5, startWidth - deltaX);
                    newLeft = Math.max(0, startLeft + deltaX);
                    newHeight = Math.max(5, startHeight - deltaY);
                    newTop = Math.max(0, startTop + deltaY);
                } else if (handleClass.includes('resize-handle-n')) {
                    // Nord : diminue hauteur en haut
                    newHeight = Math.max(5, startHeight - deltaY);
                    newTop = Math.max(0, startTop + deltaY);
                } else if (handleClass.includes('resize-handle-s')) {
                    // Sud : augmente hauteur
                    newHeight = Math.max(5, Math.min(100 - startTop, startHeight + deltaY));
                } else if (handleClass.includes('resize-handle-e')) {
                    // Est : augmente largeur
                    newWidth = Math.max(5, Math.min(100 - startLeft, startWidth + deltaX));
                } else if (handleClass.includes('resize-handle-w')) {
                    // Ouest : diminue largeur à gauche
                    newWidth = Math.max(5, startWidth - deltaX);
                    newLeft = Math.max(0, startLeft + deltaX);
                }

                // Contraintes pour éviter de sortir du canvas
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
                resizeHandle = null;
                saveZones();
            });
        });
    }

    function updateZoneData(zoneId, newData) {
        if (zones[zoneId]) {
            Object.assign(zones[zoneId], newData);
            updateZoneInList(zoneId);
        }
    }

    function addZoneToList(zoneData) {
        const listItem = $(`
            <div class="zone-item" data-zone-id="${zoneData.id}">
                <div class="zone-info">
                    <input type="text" class="zone-name" value="${zoneData.name}" />
                    <span class="zone-side">${zoneData.side === 'front' ? 'Recto' : 'Verso'}</span>
                </div>
                <div class="zone-controls">
                    <input type="number" class="zone-price" value="${zoneData.price}" step="0.01" min="0" placeholder="Prix" />
                    <button class="remove-zone btn btn-sm btn-danger">×</button>
                </div>
            </div>
        `);

        $('#zones-list').append(listItem);
        
        // Events pour la liste
        listItem.find('.zone-name').on('change', function() {
            updateZoneData(zoneData.id, { name: $(this).val() });
            updateZoneLabel(zoneData.id, $(this).val());
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

    function updateZoneInList(zoneId) {
        const zoneData = zones[zoneId];
        const listItem = $(`.zone-item[data-zone-id="${zoneId}"]`);
        if (listItem.length && zoneData) {
            listItem.find('.zone-side').text(zoneData.side === 'front' ? 'Recto' : 'Verso');
        }
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
            ? colorRow.find('.front-image-url').val()
            : colorRow.find('.back-image-url').val();

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
        const zonesData = $('#zones-data').val();
        if (zonesData) {
            try {
                const parsedZones = JSON.parse(zonesData);
                Object.values(parsedZones).forEach(function(zoneData) {
                    zones[zoneData.id] = zoneData;
                    addZoneToList(zoneData);
                    zoneCounter = Math.max(zoneCounter, parseInt(zoneData.id.split('_')[1]) || 0);
                });
            } catch (e) {
                console.error('Erreur lors du chargement des zones:', e);
            }
        }
    }

    function saveZones() {
        $('#zones-data').val(JSON.stringify(zones));
        
        // Sauvegarder via AJAX
        const data = {
            action: 'save_mockup_zones',
            post_id: $('#post_ID').val(),
            zones: zones,
            nonce: winshirtAjax.nonce
        };

        $.post(winshirtAjax.ajaxurl, data, function(response) {
            if (response.success) {
                showNotification('Zones sauvegardées', 'success');
            }
        });
    }

    function autoSave() {
        if (Object.keys(zones).length > 0) {
            saveZones();
        }
    }

    function addNewColor() {
        const colorCount = $('.color-row').length + 1;
        const colorId = 'color_' + Date.now();
        
        const colorHtml = `
            <div class="color-row" data-color-id="${colorId}">
                <div class="color-basic">
                    <input type="color" class="color-picker" data-color-id="${colorId}" value="#FFFFFF" />
                    <input type="text" class="color-hex" value="#FFFFFF" readonly />
                    <input type="radio" name="_default_color" value="${colorId}" />
                    <span>Par défaut</span>
                    <button type="button" class="remove-color btn btn-sm btn-danger" data-color-id="${colorId}">Supprimer</button>
                </div>
                <div class="color-images">
                    <div class="image-upload">
                        <label>Image Recto:</label>
                        <button type="button" class="upload-image btn btn-secondary" data-target="front-${colorId}">Choisir Image</button>
                        <input type="hidden" class="front-image-url" name="colors[${colorId}][front]" value="" />
                    </div>
                    <div class="image-upload">
                        <label>Image Verso:</label>
                        <button type="button" class="upload-image btn btn-secondary" data-target="back-${colorId}">Choisir Image</button>
                        <input type="hidden" class="back-image-url" name="colors[${colorId}][back]" value="" />
                    </div>
                </div>
            </div>
        `;
        
        $('#colors-container').append(colorHtml);
    }

    function removeColor(colorId) {
        $(`.color-row[data-color-id="${colorId}"]`).remove();
    }

    function updateColorHex(colorId, hexValue) {
        $(`.color-row[data-color-id="${colorId}"] .color-hex`).val(hexValue);
    }

    function showNotification(message, type = 'info') {
        const notification = $(`<div class="notice notice-${type} is-dismissible"><p>${message}</p></div>`);
        $('.wrap h1').after(notification);
        setTimeout(() => notification.fadeOut(), 3000);
    }

    // Export pour utilisation globale
    window.WinShirtMockupEditor = {
        updateCanvas: updateCanvas,
        saveZones: saveZones
    };

})(jQuery);
