/**
 * WinShirt Mockup Admin - Version Simple et Fonctionnelle
 */

jQuery(document).ready(function($) {
    console.log('WinShirt Mockup Admin chargé');
    
    let currentSide = 'front';
    let zones = {};
    
    // Initialisation
    init();
    
    function init() {
        loadExistingZones();
        bindEvents();
        updateCanvas();
        console.log('Initialisation terminée');
    }
    
    function bindEvents() {
        // Switch recto/verso
        $(document).on('click', '.side-switch .btn', function(e) {
            e.preventDefault();
            $('.side-switch .btn').removeClass('active');
            $(this).addClass('active');
            currentSide = $(this).data('side');
            updateCanvas();
            console.log('Switch vers:', currentSide);
        });
        
        // Ajouter une zone
        $(document).on('click', '#add-zone-btn', function(e) {
            e.preventDefault();
            addZone();
        });
        
        // Double-clic sur canvas pour ajouter zone
        $(document).on('dblclick', '#zone-canvas', function(e) {
            const rect = this.getBoundingClientRect();
            const x = ((e.clientX - rect.left) / rect.width) * 100;
            const y = ((e.clientY - rect.top) / rect.height) * 100;
            addZone(x, y);
        });
        
        // Ajouter couleur
        $(document).on('click', '#add-color', function(e) {
            e.preventDefault();
            addColor();
        });
        
        // Supprimer couleur
        $(document).on('click', '.remove-color', function(e) {
            e.preventDefault();
            $(this).closest('.color-row').remove();
        });
        
        // Upload image
        $(document).on('click', '.upload-image', function(e) {
            e.preventDefault();
            openMediaUploader($(this));
        });
        
        // Changement couleur par défaut
        $(document).on('change', 'input[name="_default_color"]', function() {
            updateCanvas();
        });
        
        // Supprimer zone
        $(document).on('click', '.remove-zone', function(e) {
            e.preventDefault();
            const zoneId = $(this).closest('.zone-item').data('zone-id');
            removeZone(zoneId);
        });
        
        // Sauvegarder
        $(document).on('click', '#publish, #save-post', function() {
            saveZones();
        });
        
        // Auto-save
        setInterval(saveZones, 10000);
    }
    
    function addZone(x = 30, y = 30) {
        const zoneId = 'zone_' + Date.now();
        const zoneName = 'Zone ' + (Object.keys(zones).length + 1);
        
        zones[zoneId] = {
            id: zoneId,
            name: zoneName,
            side: currentSide,
            x: x,
            y: y,
            width: 25,
            height: 20,
            price: 0
        };
        
        addZoneToList(zones[zoneId]);
        updateCanvas();
        saveZones();
        
        console.log('Zone ajoutée:', zoneId);
    }
    
    function addZoneToList(zone) {
        const html = `
            <div class="zone-item" data-zone-id="${zone.id}">
                <div style="margin-bottom: 5px;">
                    <input type="text" value="${zone.name}" class="zone-name" style="width: 100%;" />
                </div>
                <div style="margin-bottom: 5px;">
                    <select class="zone-side" style="width: 100%;">
                        <option value="front" ${zone.side === 'front' ? 'selected' : ''}>Recto</option>
                        <option value="back" ${zone.side === 'back' ? 'selected' : ''}>Verso</option>
                    </select>
                </div>
                <div style="margin-bottom: 5px;">
                    <input type="number" value="${zone.price}" class="zone-price" placeholder="Prix €" step="0.01" style="width: 100%;" />
                </div>
                <button type="button" class="remove-zone" style="width: 100%; background: #dc3545; color: white; border: none; padding: 5px; border-radius: 3px;">
                    Supprimer
                </button>
            </div>
        `;
        
        $('#zones-list').append(html);
        
        // Événements pour cette zone
        const item = $(`.zone-item[data-zone-id="${zone.id}"]`);
        
        item.find('.zone-name').on('change', function() {
            zones[zone.id].name = $(this).val();
            updateCanvas();
            saveZones();
        });
        
        item.find('.zone-side').on('change', function() {
            zones[zone.id].side = $(this).val();
            updateCanvas();
            saveZones();
        });
        
        item.find('.zone-price').on('change', function() {
            zones[zone.id].price = parseFloat($(this).val()) || 0;
            saveZones();
        });
    }
    
    function removeZone(zoneId) {
        delete zones[zoneId];
        $(`.zone-item[data-zone-id="${zoneId}"]`).remove();
        $(`.zone-element[data-zone-id="${zoneId}"]`).remove();
        saveZones();
        console.log('Zone supprimée:', zoneId);
    }
    
    function updateCanvas() {
        const canvas = $('#zone-canvas');
        
        // Vider le canvas
        canvas.find('.zone-element').remove();
        
        // Charger l'image de fond
        loadBackgroundImage();
        
        // Afficher les zones du côté actuel
        Object.values(zones).forEach(zone => {
            if (zone.side === currentSide) {
                createZoneElement(zone);
            }
        });
        
        console.log('Canvas mis à jour pour:', currentSide);
    }
    
    function createZoneElement(zone) {
        const html = `
            <div class="zone-element" data-zone-id="${zone.id}" 
                 style="position: absolute; left: ${zone.x}%; top: ${zone.y}%; width: ${zone.width}%; height: ${zone.height}%; 
                        border: 2px solid #0073aa; background: rgba(0,115,170,0.1); cursor: move;">
                <div style="position: absolute; top: -20px; left: 0; background: #0073aa; color: white; padding: 2px 5px; 
                           font-size: 10px; border-radius: 2px; white-space: nowrap;">
                    ${zone.name}
                </div>
            </div>
        `;
        
        $('#zone-canvas').append(html);
        
        // Rendre la zone déplaçable
        makeZoneDraggable($(`.zone-element[data-zone-id="${zone.id}"]`));
    }
    
    function makeZoneDraggable(element) {
        let isDragging = false;
        let startX, startY, startLeft, startTop;
        
        element.on('mousedown', function(e) {
            isDragging = true;
            const canvas = $('#zone-canvas');
            const canvasRect = canvas[0].getBoundingClientRect();
            
            startX = e.clientX;
            startY = e.clientY;
            startLeft = parseFloat(element.css('left'));
            startTop = parseFloat(element.css('top'));
            
            $(document).on('mousemove.drag', function(e) {
                if (!isDragging) return;
                
                const deltaX = ((e.clientX - startX) / canvasRect.width) * 100;
                const deltaY = ((e.clientY - startY) / canvasRect.height) * 100;
                
                const newLeft = Math.max(0, Math.min(75, startLeft + deltaX));
                const newTop = Math.max(0, Math.min(75, startTop + deltaY));
                
                element.css({
                    left: newLeft + '%',
                    top: newTop + '%'
                });
                
                // Mettre à jour les données
                const zoneId = element.data('zone-id');
                if (zones[zoneId]) {
                    zones[zoneId].x = newLeft;
                    zones[zoneId].y = newTop;
                }
            });
            
            $(document).on('mouseup.drag', function() {
                isDragging = false;
                $(document).off('.drag');
                saveZones();
            });
        });
    }
    
    function loadBackgroundImage() {
        const defaultColor = $('input[name="_default_color"]:checked').val();
        if (!defaultColor) return;
        
        const colorRow = $(`.color-row[data-color-id="${defaultColor}"]`);
        let imageUrl = '';
        
        if (currentSide === 'front') {
            imageUrl = colorRow.find('input[name*="[front]"]').val();
        } else {
            imageUrl = colorRow.find('input[name*="[back]"]').val();
        }
        
        if (imageUrl) {
            $('#zone-canvas').css({
                'background-image': `url(${imageUrl})`,
                'background-size': 'contain',
                'background-repeat': 'no-repeat',
                'background-position': 'center',
                'background-color': '#f9f9f9'
            });
        }
    }
    
    function addColor() {
        const colorId = 'color_' + Date.now();
        const colorCount = $('.color-row').length + 1;
        
        const html = `
            <div class="color-row" data-color-id="${colorId}" style="border-bottom: 1px solid #eee; padding: 10px; background: #fafafa;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                    <input type="color" value="#FFFFFF" style="width: 40px; height: 30px;" />
                    <input type="text" placeholder="Nom couleur" value="Couleur ${colorCount}" style="flex: 1; padding: 5px;" />
                    <label style="display: flex; align-items: center; gap: 5px;">
                        <input type="radio" name="_default_color" value="${colorId}" ${colorCount === 1 ? 'checked' : ''} />
                        Par défaut
                    </label>
                    <button type="button" class="remove-color" data-color-id="${colorId}" 
                            style="background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 3px;">
                        Supprimer
                    </button>
                </div>
                <div style="display: flex; gap: 15px;">
                    <div style="text-align: center;">
                        <label style="display: block; margin-bottom: 5px; font-size: 12px;">Recto:</label>
                        <button type="button" class="upload-image" data-side="front" data-color="${colorId}"
                                style="background: #0073aa; color: white; border: none; padding: 6px 12px; border-radius: 4px; font-size: 11px;">
                            Choisir Image
                        </button>
                        <input type="hidden" name="colors[${colorId}][front]" />
                        <div class="image-preview" style="margin-top: 5px;"></div>
                    </div>
                    <div style="text-align: center;">
                        <label style="display: block; margin-bottom: 5px; font-size: 12px;">Verso:</label>
                        <button type="button" class="upload-image" data-side="back" data-color="${colorId}"
                                style="background: #0073aa; color: white; border: none; padding: 6px 12px; border-radius: 4px; font-size: 11px;">
                            Choisir Image
                        </button>
                        <input type="hidden" name="colors[${colorId}][back]" />
                        <div class="image-preview" style="margin-top: 5px;"></div>
                    </div>
                </div>
            </div>
        `;
        
        $('#colors-container').append(html);
        console.log('Couleur ajoutée:', colorId);
    }
    
    function openMediaUploader(button) {
        if (typeof wp === 'undefined' || !wp.media) {
            alert('WordPress Media Library non disponible');
            return;
        }
        
        const frame = wp.media({
            title: 'Choisir une image',
            button: { text: 'Utiliser cette image' },
            multiple: false
        });
        
        frame.on('select', function() {
            const attachment = frame.state().get('selection').first().toJSON();
            const colorId = button.data('color');
            const side = button.data('side');
            
            // Mettre à jour l'input hidden
            const input = button.siblings(`input[name="colors[${colorId}][${side}]"]`);
            input.val(attachment.url);
            
            // Afficher l'aperçu
            const preview = button.siblings('.image-preview');
            preview.html(`<img src="${attachment.url}" style="max-width: 60px; height: auto; border: 1px solid #ddd; border-radius: 3px;" />`);
            
            // Mettre à jour le canvas si c'est la couleur par défaut
            updateCanvas();
            
            console.log('Image uploadée:', attachment.url);
        });
        
        frame.open();
    }
    
    function loadExistingZones() {
        const input = $('input[name="_zones"]');
        if (input.length && input.val()) {
            try {
                zones = JSON.parse(input.val());
                Object.values(zones).forEach(zone => {
                    addZoneToList(zone);
                });
                console.log('Zones existantes chargées:', Object.keys(zones).length);
            } catch (e) {
                console.error('Erreur chargement zones:', e);
                zones = {};
            }
        }
    }
    
    function saveZones() {
        const input = $('input[name="_zones"]');
        if (input.length) {
            input.val(JSON.stringify(zones));
            console.log('Zones sauvegardées:', Object.keys(zones).length);
        }
        
        // Sauvegarder via AJAX si disponible
        if (typeof ajaxurl !== 'undefined' && $('#post_ID').length) {
            $.post(ajaxurl, {
                action: 'save_mockup_zones',
                post_id: $('#post_ID').val(),
                zones: zones,
                nonce: $('input[name="winshirt_nonce"]').val()
            });
        }
    }
    
    // Debug global
    window.WinShirtDebug = {
        zones: zones,
        currentSide: currentSide,
        updateCanvas: updateCanvas,
        saveZones: saveZones
    };
});
