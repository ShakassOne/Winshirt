/**
 * WinShirt Mockup Admin JavaScript - VERSION STABLE QUI MARCHE
 */

jQuery(document).ready(function($) {
    'use strict';

    let currentSide = 'front';
    let zones = {};
    let colorCounter = 0;

    // Initialisation
    init();

    function init() {
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

        // Ajouter couleur
        $('#add-color').on('click', addNewColor);

        // Supprimer couleur
        $(document).on('click', '.remove-color', function() {
            $(this).closest('.color-row').remove();
        });

        // Upload d'images
        $(document).on('click', '.upload-image', function() {
            openMediaUploader($(this));
        });

        // Changement couleur par défaut
        $(document).on('change', 'input[name="_default_color"]', function() {
            updateCanvas();
        });

        // Ajouter zone
        $('#add-zone-btn').on('click', function() {
            addZone(40, 40);
        });

        // Double-clic pour créer zone
        $('#zone-canvas').on('dblclick', function(e) {
            const rect = this.getBoundingClientRect();
            const x = ((e.clientX - rect.left) / rect.width) * 100;
            const y = ((e.clientY - rect.top) / rect.height) * 100;
            addZone(x, y);
        });

        // Supprimer zone
        $(document).on('click', '.remove-zone', function() {
            const zoneId = $(this).data('zone-id');
            removeZone(zoneId);
        });
    }

    function addNewColor() {
        colorCounter++;
        const colorId = 'color_' + colorCounter + '_' + Date.now();
        
        const colorHtml = `
            <div class="color-row" data-color-id="${colorId}">
                <div class="color-basic">
                    <input type="color" class="color-picker" data-color-id="${colorId}" value="#FFFFFF" />
                    <input type="text" class="color-hex" value="#FFFFFF" readonly />
                    <input type="radio" name="_default_color" value="${colorId}" />
                    <span>Par défaut</span>
                    <button type="button" class="remove-color btn btn-danger" data-color-id="${colorId}">Supprimer</button>
                </div>
                <div class="color-images">
                    <div class="image-upload">
                        <label>Image Recto:</label>
                        <button type="button" class="upload-image btn btn-secondary" data-target="front-${colorId}">Choisir Image Recto</button>
                        <input type="hidden" class="front-image-url" name="colors[${colorId}][front]" value="" />
                    </div>
                    <div class="image-upload">
                        <label>Image Verso:</label>
                        <button type="button" class="upload-image btn btn-secondary" data-target="back-${colorId}">Choisir Image Verso</button>
                        <input type="hidden" class="back-image-url" name="colors[${colorId}][back]" value="" />
                    </div>
                </div>
            </div>
        `;
        
        $('#colors-container').append(colorHtml);
    }

    function openMediaUploader(button) {
        const mediaUploader = wp.media({
            title: 'Choisir une image',
            button: { text: 'Utiliser cette image' },
            multiple: false
        });

        mediaUploader.on('select', function() {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            const target = button.data('target');
            const parts = target.split('-');
            const side = parts[0];
            const colorId = parts.slice(1).join('-');
            
            // Mettre à jour l'input
            button.siblings(`.${side}-image-url`).val(attachment.url);
            
            // Ajouter aperçu
            button.parent().find('.image-preview').remove();
            button.parent().append(`<div class="image-preview"><img src="${attachment.url}" style="max-width: 100px;" /></div>`);
            
            updateCanvas();
        });

        mediaUploader.open();
    }

    function addZone(x, y) {
        const zoneId = 'zone_' + Date.now();
        const zone = {
            id: zoneId,
            name: 'Zone ' + (Object.keys(zones).length + 1),
            side: currentSide,
            x: x,
            y: y,
            width: 20,
            height: 15,
            price: 0
        };

        zones[zoneId] = zone;
        addZoneToList(zone);
        updateCanvas();
        saveZones();
    }

    function addZoneToList(zone) {
        const zoneHtml = `
            <div class="zone-item" data-zone-id="${zone.id}">
                <input type="text" class="zone-name" value="${zone.name}" />
                <select class="zone-side">
                    <option value="front" ${zone.side === 'front' ? 'selected' : ''}>Recto</option>
                    <option value="back" ${zone.side === 'back' ? 'selected' : ''}>Verso</option>
                </select>
                <input type="number" class="zone-price" value="${zone.price}" step="0.01" min="0" placeholder="Prix" />
                <button class="remove-zone" data-zone-id="${zone.id}">Supprimer</button>
            </div>
        `;
        
        $('#zones-list').append(zoneHtml);
        
        // Events
        const $item = $(`.zone-item[data-zone-id="${zone.id}"]`);
        $item.find('.zone-name').on('change', function() {
            zones[zone.id].name = $(this).val();
            updateCanvas();
            saveZones();
        });
        
        $item.find('.zone-side').on('change', function() {
            zones[zone.id].side = $(this).val();
            updateCanvas();
            saveZones();
        });
        
        $item.find('.zone-price').on('change', function() {
            zones[zone.id].price = parseFloat($(this).val()) || 0;
            saveZones();
        });
    }

    function removeZone(zoneId) {
        delete zones[zoneId];
        $(`.zone-item[data-zone-id="${zoneId}"]`).remove();
        updateCanvas();
        saveZones();
    }

    function updateCanvas() {
        const canvas = $('#zone-canvas');
        canvas.empty();
        
        loadBackgroundImage();
        
        Object.values(zones).forEach(function(zone) {
            if (zone.side === currentSide) {
                createZoneElement(zone);
            }
        });
    }

    function createZoneElement(zone) {
        const zoneEl = $(`
            <div class="zone-element" data-zone-id="${zone.id}" 
                 style="left: ${zone.x}%; top: ${zone.y}%; width: ${zone.width}%; height: ${zone.height}%;">
                <div class="zone-label">${zone.name}</div>
            </div>
        `);

        $('#zone-canvas').append(zoneEl);
        makeZoneDraggable(zoneEl);
    }

    function makeZoneDraggable(zoneEl) {
        zoneEl.draggable({
            containment: '#zone-canvas',
            stop: function() {
                const zoneId = zoneEl.data('zone-id');
                const canvas = $('#zone-canvas');
                const x = (parseFloat(zoneEl.css('left')) / canvas.width()) * 100;
                const y = (parseFloat(zoneEl.css('top')) / canvas.height()) * 100;
                
                zones[zoneId].x = x;
                zones[zoneId].y = y;
                saveZones();
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
                zones = JSON.parse(zonesData);
                Object.values(zones).forEach(function(zone) {
                    addZoneToList(zone);
                });
            } catch (e) {
                console.error('Erreur chargement zones:', e);
            }
        }
    }

    function saveZones() {
        $('#zones-data').val(JSON.stringify(zones));
        
        // Sauvegarder via AJAX
        if (typeof winshirtAjax !== 'undefined') {
            $.post(winshirtAjax.ajaxurl, {
                action: 'save_mockup_zones',
                post_id: $('#post_ID').val(),
                zones: zones,
                nonce: winshirtAjax.nonce
            });
        }
    }
});
