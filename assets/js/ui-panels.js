/**
 * WinShirt - Déclaration des panneaux (L1/L2/L3)
 * Inspiré MisterTee : navigation progressive
 *
 * Chaque panneau est défini comme une "vue" avec :
 * - id : identifiant unique
 * - level : 1, 2 ou 3
 * - title : affiché en header
 * - render($mount, payload) : construit le contenu
 *
 * On commence par le L1 (menu principal), qui ouvre les L2,
 * qui eux peuvent ouvrir des L3 si nécessaire.
 */

(function($){
    'use strict';

    /**
     * Panneaux L1 : menu principal
     */
    function openMainMenu(){
        WinShirtUIRouter.push({
            id: 'main-menu',
            level: 1,
            title: 'Personnalisation',
            render: function($mount){
                const menu = $(`
                    <ul class="ws-menu-l1">
                        <li data-action="images"><span>📷 Images</span></li>
                        <li data-action="text"><span>🔤 Texte</span></li>
                        <li data-action="layers"><span>📚 Calques</span></li>
                        <li data-action="qr"><span>🔲 QR Code</span></li>
                        <li data-action="ia"><span>🤖 IA</span></li>
                    </ul>
                `);
                $mount.append(menu);

                menu.on('click', 'li', function(){
                    const action = $(this).data('action');
                    if(action === 'images') return openImagesMenu();
                    if(action === 'text') return openTextMenu();
                    if(action === 'layers') return openLayersMenu();
                    if(action === 'qr') return openQRMenu();
                    if(action === 'ia') return openIAMenu();
                });
            }
        });
    }

    /**
     * Panneaux L2 : par catégorie
     */
    function openImagesMenu(){
        WinShirtUIRouter.push({
            id: 'images-menu',
            level: 2,
            title: 'Images',
            render: function($mount){
                const html = $(`
                    <div class="ws-images-menu">
                        <button data-load="gallery">Galerie</button>
                        <button data-load="upload">Upload</button>
                    </div>
                `);
                $mount.append(html);

                html.on('click', '[data-load="gallery"]', function(){
                    openImagesGallery();
                });
                html.on('click', '[data-load="upload"]', function(){
                    openImagesUpload();
                });
            }
        });
    }

    function openTextMenu(){
        WinShirtUIRouter.push({
            id: 'text-menu',
            level: 2,
            title: 'Texte',
            render: function($mount){
                const html = $(`
                    <div class="ws-text-menu">
                        <button data-load="addText">Ajouter texte</button>
                        <button data-load="styles">Styles</button>
                    </div>
                `);
                $mount.append(html);

                html.on('click', '[data-load="addText"]', function(){
                    openAddText();
                });
                html.on('click', '[data-load="styles"]', function(){
                    openTextStyles();
                });
            }
        });
    }

    function openLayersMenu(){
        WinShirtUIRouter.push({
            id: 'layers-menu',
            level: 2,
            title: 'Calques',
            render: function($mount){
                const list = $('<ul class="ws-layers-list"></ul>');
                const layers = WinShirtState.layers[WinShirtState.currentSide];
                if(!layers.length){
                    list.append('<li>(Aucun calque)</li>');
                } else {
                    layers.forEach(layer=>{
                        list.append(`<li data-id="${layer.id}">${layer.type} - ${layer.name || layer.id}</li>`);
                    });
                }
                $mount.append(list);

                list.on('click', 'li[data-id]', function(){
                    const id = $(this).data('id');
                    WinShirtState.selectLayer(id);
                    WinShirtUIRouter.backTo(1); // Retour au menu principal après sélection
                });
            }
        });
    }

    function openQRMenu(){
        WinShirtUIRouter.push({
            id: 'qr-menu',
            level: 2,
            title: 'QR Code',
            render: function($mount){
                const html = $(`
                    <div class="ws-qr-menu">
                        <button data-load="generateQR">Générer QR</button>
                    </div>
                `);
                $mount.append(html);

                html.on('click', '[data-load="generateQR"]', function(){
                    openQRGenerator();
                });
            }
        });
    }

    function openIAMenu(){
        WinShirtUIRouter.push({
            id: 'ia-menu',
            level: 2,
            title: 'IA',
            render: function($mount){
                const html = $(`
                    <div class="ws-ia-menu">
                        <button data-load="generateIA">Générer visuel IA</button>
                    </div>
                `);
                $mount.append(html);

                html.on('click', '[data-load="generateIA"]', function(){
                    openIAGenerator();
                });
            }
        });
    }

    /**
     * Panneaux L3 : sous-options
     */
    function openImagesGallery(){
        WinShirtUIRouter.push({
            id: 'images-gallery',
            level: 3,
            title: 'Galerie',
            render: function($mount){
                $mount.append('<p>(Galerie d’images à implémenter)</p>');
            }
        });
    }

    function openImagesUpload(){
        WinShirtUIRouter.push({
            id: 'images-upload',
            level: 3,
            title: 'Upload image',
            render: function($mount){
                $mount.append('<p>(Formulaire upload à implémenter)</p>');
            }
        });
    }

    function openAddText(){
        WinShirtUIRouter.push({
            id: 'add-text',
            level: 3,
            title: 'Ajouter texte',
            render: function($mount){
                $mount.append('<p>(Formulaire ajout texte à implémenter)</p>');
            }
        });
    }

    function openTextStyles(){
        WinShirtUIRouter.push({
            id: 'text-styles',
            level: 3,
            title: 'Styles texte',
            render: function($mount){
                $mount.append('<p>(Options styles texte à implémenter)</p>');
            }
        });
    }

    function openQRGenerator(){
        WinShirtUIRouter.push({
            id: 'qr-generator',
            level: 3,
            title: 'Générateur QR',
            render: function($mount){
                $mount.append('<p>(Formulaire génération QR à implémenter)</p>');
            }
        });
    }

    function openIAGenerator(){
        WinShirtUIRouter.push({
            id: 'ia-generator',
            level: 3,
            title: 'Générateur IA',
            render: function($mount){
                $mount.append('<p>(Formulaire IA à implémenter)</p>');
            }
        });
    }

    // Boot auto : ouvre le menu principal dès que le router est prêt
    $(document).on('winshirt:routerReady', function(){
        openMainMenu();
    });

})(jQuery);
