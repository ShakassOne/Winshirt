/**
 * WinShirt - Admin Mockup Editor JavaScript
 */

jQuery(document).ready(function($) {
    'use strict';

    // Variables globales
    let mockupData = {
        colors: {},
        zones: {},
        defaultColor: '',
        currentSide: 'front'
    };
    
    let canvas, ctx, currentImage = null;
    let nextColorId = 1;
    let nextZoneId = 1;
    let selectedZone = null;
    let isDragging = false;
    let isResizing = false;
    let dragOffset = { x: 0, y: 0 };

    // ==========================================================================
    // Initialisation
    // ==========================================================================

    function init() {
        setupCanvas();
        setupEventListeners();
        loadExistingData();
        updateCanvas();
    }

    function setupCanvas() {
        canvas = document.getElementById('zones-canvas');
        if (!canvas) return;
        
        ctx = canvas.getContext('2d');
        canvas.style.cursor = 'crosshair';
    }

    function setupEventListeners() {
        // Couleurs
        $(document).on('click', '#add-color', addColor);
        $(document).on('click', '.remove-color', removeColor);
        $(document).on('change', '.color-hex', updateColorPreview);
        $(document).on('change', '.color-name', updateColorData);
        $(document).on('change', 'input[name="default_color"]', updateDefaultColor);
        $(document).on('click', '.upload-image', uploadImage);

        // Zones
        $(document).on('click', '#add-zone', addZone);
        $(document).on('click', '.remove-zone', removeZone);
        $(document).on('change', '.zone-name, .zone-price, .zone-side', updateZoneData);
        $(document).on('click', '.side-btn', switchSide);

        // Canvas
        $(canvas).on('mousedown', onCanvasMouseDown);
        $(canvas).on('mousemove', onCanvasMouseMove);
        $(canvas).on('mouseup', onCanvasMouseUp);
        $(canvas).on('dblclick', onCanvasDoubleClick);

        // Sauvegarde
        $('#winshirt-save-mockup').on('click', saveMockup);
        
        // Suppression mockup
        $('.winshirt-delete-mockup').on('click', deleteMockup);
    }

    function loadExistingData() {
        // Charger les données existantes du DOM
        $('#colors-container .color-item').each(function() {
            const colorId = $(this).data('color-id');
            const colorData = {
                name: $(this).find('.color-name').val(),
                hex: $(this).find('.color-hex').val(),
                front: $(this).find('.image-url[data-side="front"]').val(),
                back: $(this).find('.image-url[data-side="back"]').val()
            };
            mockupData.colors[colorId] = colorData;
            
            if (colorId > nextColorId) nextColorId = colorId + 1;
        });

        $('#zones-container .zone-item').each(function() {
            const zoneId = $(this).data('zone-id');
            const zoneData = {
                name: $(this).find('.zone-name').val(),
                price: parseFloat($(this).find('.zone-price').val()) || 0,
                side: $(this).find('.zone-side').val(),
                x: 10, y: 10, width: 100, height: 100 // Valeurs par défaut
            };
            mockupData.zones[zoneId] = zoneData;
            
            if (zoneId > nextZoneId) nextZoneId = zoneId + 1;
        });

        mockupData.defaultColor = $('input[name="default_color"]:checked').val() || '';
        updateCanvas();
    }

    // ==========================================================================
    // Gestion des Couleurs
    // ==========================================================================

    function addColor() {
        const colorId = nextColorId++;
        const template = $('#color-template').html()
            .replace(/{{COLOR_ID}}/g, colorId);

        $('#colors-container').append(template);
        
        mockupData.colors[colorId] = {
            name: '',
            hex: '#000000',
            front: '',
            back: ''
        };

        // Si c'est la première couleur, la définir par défaut
        if (Object.keys(mockupData.colors).length === 1) {
            $(`input[name="default_color"][value="${colorId}"]`).prop('checked', true);
            mockupData.defaultColor = colorId;
            updateCanvas();
        }
    }

    function removeColor() {
        const $colorItem = $(this).closest('.color-item');
        const colorId = $colorItem.data('color-id');
        
        if (confirm('Supprimer cette couleur ?')) {
            delete mockupData.colors[colorId];
            $colorItem.remove();
            
            // Si c'était la couleur par défaut, choisir une autre
            if (mockupData.defaultColor == colorId) {
                const firstColor = Object.keys(mockupData.colors)[0];
                if (firstColor) {
                    $(`input[name="default_color"][value="${firstColor}"]`).prop('checked', true);
                    mockupData.defaultColor = firstColor;
                } else {
                    mockupData.defaultColor = '';
                }
                updateCanvas();
            }
        }
    }

    function updateColorPreview() {
        const $item = $(this).closest('.color-item');
        const hex = $(this).val();
        
        $item.find('.color-preview').css('background-color', hex);
        updateColorData.call(this);
    }

    function updateColorData() {
        const $item = $(this).closest('.color-item');
        const colorId = $item.data('color-id');
        
        if (!mockupData.colors[colorId]) {
            mockupData.colors[colorId] = {};
        }
        
        mockupData.colors[colorId].name = $item.find('.color-name').val();
        mockupData.colors[colorId].hex = $item.find('.color-hex').val();
        mockupData.colors[colorId].front = $item.find('.image-url[data-side="front"]').val();
        mockupData.colors[colorId].back = $item.find('.image-url[data-side="back"]').val();
    }

    function updateDefaultColor() {
        mockupData.defaultColor = $(this).val();
        updateCanvas();
    }

    function uploadImage() {
        const $button = $(this);
        const $item = $button.closest('.color-item');
        const side = $button.data('side');
        
        const mediaUploader = wp.media({
            title: 'Choisir une image',
            button: { text: 'Utiliser cette image' },
            multiple: false,
            library: { type: 'image' }
        });

        mediaUploader.on('select', function() {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            const imageUrl = attachment.url;
            
            // Mettre à jour l'aperçu
            $item.find(`.image-preview`).html(`<img src="${imageUrl}" alt="${side}">`);
            $item.find(`.image-url[data-side="${side}"]`).val(imageUrl);
            
            // Mettre à jour les données
            updateColorData.call($item.find('.color-name')[0]);
            
            // Redessiner le canvas si c'est la couleur active
            updateCanvas();
        });

        mediaUploader.open();
    }

    // ==========================================================================
    // Gestion des Zones
    // ==========================================================================

    function addZone() {
        const zoneId = nextZoneId++;
        const template = $('#zone-template').html()
            .replace(/{{ZONE_ID}}/g, zoneId);

        $('#zones-container').append(template);
        
        // Ajouter la zone aux données
        mockupData.zones[zoneId] = {
            name: `Zone ${zoneId}`,
            price: 0,
            side: mockupData.currentSide,
            x: 20, // Pourcentage
            y: 20, // Pourcentage
            width: 25, // Pourcentage
            height: 25 // Pourcentage
        };

        // Mettre à jour les champs
        const $zoneItem = $(`.zone-item[data-zone-id="${zoneId}"]`);
        $zoneItem.find('.zone-name').val(`Zone ${zoneId}`);
        $zoneItem.find('.zone-price').val(0);
        $zoneItem.find('.zone-side').val(mockupData.currentSide);

        updateCanvas();
    }

    function removeZone() {
        const $zoneItem = $(this).closest('.zone-item');
        const zoneId = $zoneItem.data('zone-id');
        
        if (confirm('Supprimer cette zone ?')) {
            delete mockupData.zones[zoneId];
            $zoneItem.remove();
            updateCanvas();
        }
    }

    function updateZoneData() {
        const $item = $(this).closest('.zone-item');
        const zoneId = $item.data('zone-id');
        
        if (!mockupData.zones[zoneId]) return;
        
        mockupData.zones[zoneId].name = $item.find('.zone-name').val();
        mockupData.zones[zoneId].price = parseFloat($item.find('.zone-price').val()) || 0;
        mockupData.zones[zoneId].side = $item.find('.zone-side').val();
        
        updateCanvas();
    }

    function switchSide() {
        $('.side-btn').removeClass('active');
        $(this).addClass('active');
        
        mockupData.currentSide = $(this).data('side');
        updateCanvas();
    }

    // ==========================================================================
    // Gestion du Canvas
    // ==========================================================================

    function updateCanvas() {
        if (!canvas || !ctx) return;
        
        // Effacer le canvas
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        // Charger l'image de fond
        const defaultColor = mockupData.defaultColor;
        if (defaultColor && mockupData.colors[defaultColor]) {
            const imageUrl = mockupData.colors[defaultColor][mockupData.currentSide];
            if (imageUrl) {
                loadBackgroundImage(imageUrl);
            }
        }
        
        // Dessiner les zones pour le côté actuel
        drawZones();
    }

    function loadBackgroundImage(imageUrl) {
        const img = new Image();
        img.onload = function() {
            // Calculer les dimensions pour conserver le ratio
            const canvasRatio = canvas.width / canvas.height;
            const imageRatio = img.width / img.height;
            
            let drawWidth, drawHeight, drawX, drawY;
            
            if (imageRatio > canvasRatio) {
                // Image plus large que le canvas
                drawWidth = canvas.width;
                drawHeight = canvas.width / imageRatio;
                drawX = 0;
                drawY = (canvas.height - drawHeight) / 2;
            } else {
                // Image plus haute que le canvas
                drawHeight = canvas.height;
                drawWidth = canvas.height * imageRatio;
                drawX = (canvas.width - drawWidth) / 2;
                drawY = 0;
            }
            
            ctx.drawImage(img, drawX, drawY, drawWidth, drawHeight);
            drawZones(); // Redessiner les zones par-dessus
            currentImage = { img, drawX, drawY, drawWidth, drawHeight };
        };
        img.src = imageUrl;
    }

    function drawZones() {
        Object.entries(mockupData.zones).forEach(([zoneId, zone]) => {
            if (zone.side !== mockupData.currentSide) return;
            
            // Convertir les pourcentages en pixels
            const x = (zone.x / 100) * canvas.width;
            const y = (zone.y / 100) * canvas.height;
            const width = (zone.width / 100) * canvas.width;
            const height = (zone.height / 100) * canvas.height;
            
            // Dessiner la zone
            ctx.strokeStyle = selectedZone == zoneId ? '#dc3545' : '#0073aa';
            ctx.fillStyle = selectedZone == zoneId ? 'rgba(220, 53, 69, 0.1)' : 'rgba(0, 115, 170, 0.1)';
            ctx.lineWidth = 2;
            
            ctx.fillRect(x, y, width, height);
            ctx.strokeRect(x, y, width, height);
            
            // Dessiner le nom de la zone
            ctx.fillStyle = '#0073aa';
            ctx.font = '12px Arial';
            ctx.fillText(zone.name || `Zone ${zoneId}`, x, y - 5);
            
            // Dessiner les poignées de redimensionnement si sélectionnée
            if (selectedZone == zoneId) {
                drawResizeHandles(x, y, width, height);
            }
        });
    }

    function drawResizeHandles(x, y, width, height) {
        const handleSize = 6;
        ctx.fillStyle = '#0073aa';
        
        // Coins
        ctx.fillRect(x - handleSize/2, y - handleSize/2, handleSize, handleSize);
        ctx.fillRect(x + width - handleSize/2, y - handleSize/2, handleSize, handleSize);
        ctx.fillRect(x - handleSize/2, y + height - handleSize/2, handleSize, handleSize);
        ctx.fillRect(x + width - handleSize/2, y + height - handleSize/2, handleSize, handleSize);
    }

    // ==========================================================================
    // Événements Canvas
    // ==========================================================================

    function onCanvasMouseDown(e) {
        const rect = canvas.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        
        // Vérifier si on clique sur une zone existante
        const clickedZone = getZoneAtPosition(x, y);
        
        if (clickedZone) {
            selectedZone = clickedZone;
            isDragging = true;
            
            const zone = mockupData.zones[clickedZone];
            const zoneX = (zone.x / 100) * canvas.width;
            const zoneY = (zone.y / 100) * canvas.height;
            
            dragOffset = {
                x: x - zoneX,
                y: y - zoneY
            };
        } else {
            selectedZone = null;
        }
        
        updateCanvas();
    }

    function onCanvasMouseMove(e) {
        if (!isDragging || !selectedZone) return;
        
        const rect = canvas.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        
        // Calculer la nouvelle position en pourcentages
        const newX = Math.max(0, Math.min(100, ((x - dragOffset.x) / canvas.width) * 100));
        const newY = Math.max(0, Math.min(100, ((y - dragOffset.y) / canvas.height) * 100));
        
        // Mettre à jour la zone
        mockupData.zones[selectedZone].x = newX;
        mockupData.zones[selectedZone].y = newY;
        
        updateCanvas();
    }

    function onCanvasMouseUp(e) {
        isDragging = false;
        isResizing = false;
    }

    function onCanvasDoubleClick(e) {
        const rect = canvas.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        
        // Créer une nouvelle zone à la position du double-clic
        if (!getZoneAtPosition(x, y)) {
            const zoneId = nextZoneId++;
            
            mockupData.zones[zoneId] = {
                name: `Zone ${zoneId}`,
                price: 0,
                side: mockupData.currentSide,
                x: (x / canvas.width) * 100,
                y: (y / canvas.height) * 100,
                width: 20,
                height: 20
            };
            
            // Ajouter à l'interface
            const template = $('#zone-template').html()
                .replace(/{{ZONE_ID}}/g, zoneId);
            $('#zones-container').append(template);
            
            const $zoneItem = $(`.zone-item[data-zone-id="${zoneId}"]`);
            $zoneItem.find('.zone-name').val(`Zone ${zoneId}`);
            $zoneItem.find('.zone-price').val(0);
            $zoneItem.find('.zone-side').val(mockupData.currentSide);
            
            selectedZone = zoneId;
            updateCanvas();
        }
    }

    function getZoneAtPosition(x, y) {
        for (const [zoneId, zone] of Object.entries(mockupData.zones)) {
            if (zone.side !== mockupData.currentSide) continue;
            
            const zoneX = (zone.x / 100) * canvas.width;
            const zoneY = (zone.y / 100) * canvas.height;
            const zoneWidth = (zone.width / 100) * canvas.width;
            const zoneHeight = (zone.height / 100) * canvas.height;
            
            if (x >= zoneX && x <= zoneX + zoneWidth &&
                y >= zoneY && y <= zoneY + zoneHeight) {
                return zoneId;
            }
        }
        return null;
    }

    // ==========================================================================
    // Sauvegarde
    // ==========================================================================

    function saveMockup() {
        const $button = $('#winshirt-save-mockup');
        const mockupId = $('#winshirt-mockup-form').data('mockup-id');
        
        $button.prop('disabled', true).text('Sauvegarde...');
        
        const data = {
            action: 'winshirt_save_mockup',
            nonce: winshirtAdmin.nonce,
            mockup_id: mockupId,
            title: $('#mockup-title').val(),
            colors: mockupData.colors,
            zones: mockupData.zones,
            default_color: mockupData.defaultColor
        };
        
        $.post(winshirtAdmin.ajax_url, data)
            .done(function(response) {
                if (response.success) {
                    showNotice('success', winshirtAdmin.strings.save_success);
                    
                    // Mettre à jour l'URL si nouveau mockup
                    if (!mockupId && response.data.mockup_id) {
                        const newUrl = window.location.href + '&id=' + response.data.mockup_id;
                        window.history.replaceState({}, '', newUrl);
                        $('#winshirt-mockup-form').data('mockup-id', response.data.mockup_id);
                    }
                } else {
                    showNotice('error', response.data || winshirtAdmin.strings.save_error);
                }
            })
            .fail(function() {
                showNotice('error', winshirtAdmin.strings.save_error);
            })
            .always(function() {
                $button.prop('disabled', false).text('Sauvegarder');
            });
    }

    function deleteMockup() {
        const mockupId = $(this).data('id');
        
        if (!confirm(winshirtAdmin.strings.delete_confirm)) return;
        
        const data = {
            action: 'winshirt_delete_mockup',
            nonce: winshirtAdmin.nonce,
            mockup_id: mockupId
        };
        
        $.post(winshirtAdmin.ajax_url, data)
            .done(function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    showNotice('error', response.data || 'Erreur lors de la suppression');
                }
            });
    }

    // ==========================================================================
    // Utilitaires
    // ==========================================================================

    function showNotice(type, message) {
        const $notice = $(`<div class="winshirt-notice ${type}">${message}</div>`);
        $('#winshirt-save-status').append($notice);
        
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }

    // Initialiser l'application
    init();
});
