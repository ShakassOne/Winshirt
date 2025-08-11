/**
 * WinShirt - Outils Images
 * - Ajout d'un calque image (depuis URL ou <input type="file">)
 * - Fit dans la zone d'impression (contain/cover/none)
 * - Sélection automatique du calque créé
 *
 * Dépendances : jQuery, WinShirtState, WinShirtLayers
 */

(function($){
    'use strict';

    const ACCEPTED_MIME = ['image/png','image/jpeg','image/jpg','image/webp','image/gif','image/svg+xml'];

    const ImageTools = {

        /**
         * ID unique
         */
        _uid(){
            return 'img_' + Math.random().toString(36).slice(2, 9);
        },

        /**
         * Détermine le rectangle de la zone d'impression actuelle.
         * Cherche un élément .ws-print-zone[data-side] dans le canvas. Fallback: le canvas lui-même.
         * Retourne {left, top, width, height} en px relatifs au canvas.
         */
        _getPrintZoneRect(){
            const $cv = WinShirtLayers && WinShirtLayers.$canvas ? WinShirtLayers.$canvas : null;
            if(!$cv || !$cv.length){
                return { left: 0, top: 0, width: 300, height: 300 }; // fallback
            }
            const side = WinShirtState.currentSide;
            const $zone = $cv.find(`.ws-print-zone[data-side="${side}"]`);
            const w = $cv.innerWidth();
            const h = $cv.innerHeight();

            if($zone.length){
                return {
                    left: parseFloat($zone.css('left')) || 0,
                    top: parseFloat($zone.css('top')) || 0,
                    width: $zone.outerWidth() || w,
                    height: $zone.outerHeight() || h
                };
            }
            // Si pas de zone dédiée, on prend tout le canvas
            return { left: 0, top: 0, width: w, height: h };
        },

        /**
         * Calcule une boîte "fit" dans un conteneur (contain/cover/none)
         * Retourne {left, top, width, height}
         */
        _fitBox(imgRatio, box, mode='contain'){
            const { width: BW, height: BH } = box;

            if(mode === 'none'){
                // Pas de fit, on centre juste un cadre carré raisonnable
                const side = Math.min(BW, BH) * 0.5;
                return {
                    width: side,
                    height: side,
                    left: box.left + (BW - side)/2,
                    top: box.top + (BH - side)/2
                };
            }

            // contain / cover
            const boxRatio = BW / BH;
            let w, h;

            if(mode === 'contain'){
                if(imgRatio > boxRatio){
                    w = BW; h = BW / imgRatio;
                }else{
                    h = BH; w = BH * imgRatio;
                }
            } else { // cover
                if(imgRatio > boxRatio){
                    h = BH; w = BH * imgRatio;
                }else{
                    w = BW; h = BW / imgRatio;
                }
            }

            return {
                width: w,
                height: h,
                left: box.left + (BW - w)/2,
                top: box.top + (BH - h)/2
            };
        },

        /**
         * Ajoute un calque image depuis une URL
         * @param {Object} opts - { src, name, fit: 'contain'|'cover'|'none', left, top, width, height, rotation }
         */
        addImageFromURL(opts = {}){
            if(!opts.src){
                console.error('ImageTools.addImageFromURL: src manquant');
                return null;
            }

            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = () => {
                const id = this._uid();
                const ratio = img.width / img.height;
                const zone = this._getPrintZoneRect();

                let geom;
                if(opts.width && opts.height){
                    geom = {
                        width: parseFloat(opts.width),
                        height: parseFloat(opts.height),
                        left: parseFloat(opts.left ?? (zone.left + (zone.width - opts.width)/2)),
                        top: parseFloat(opts.top ?? (zone.top + (zone.height - opts.height)/2))
                    };
                } else {
                    geom = this._fitBox(ratio, zone, opts.fit || 'contain');
                }

                const layer = {
                    id,
                    type: 'image',
                    name: opts.name || 'Image',
                    src: opts.src,
                    width: geom.width,
                    height: geom.height,
                    left: geom.left,
                    top: geom.top,
                    rotation: parseFloat(opts.rotation || 0)
                };

                WinShirtState.addLayer(layer);

                if(window.WinShirtLayers && WinShirtLayers.$canvas){
                    WinShirtLayers.addLayerElement(layer);
                    WinShirtLayers.highlightSelected();
                }

                $(document).trigger('winshirt:imageAdded', [layer, WinShirtState.currentSide]);
            };

            img.onerror = () => {
                console.error('ImageTools: impossible de charger', opts.src);
            };

            img.src = opts.src;
            return true;
        },

        /**
         * Ajoute un calque image depuis un File (input)
         * @param {File} file
         * @param {Object} opts - options (fit, name…)
         */
        addImageFromFile(file, opts = {}){
            if(!file || !file.type || ACCEPTED_MIME.indexOf(file.type) === -1){
                console.warn('ImageTools.addImageFromFile: type non supporté', file && file.type);
                return;
            }
            const reader = new FileReader();
            reader.onload = (e) => {
                this.addImageFromURL(Object.assign({}, opts, { src: e.target.result, name: file.name }));
            };
            reader.readAsDataURL(file);
        },

        /**
         * Branche un <input type="file"> externe pour l’upload local
         * @param {String|jQuery} selector
         * @param {Object} opts
         */
        bindFileInput(selector, opts = {}){
            const $input = $(selector);
            if(!$input.length) return;

            $input.attr('accept', ACCEPTED_MIME.join(','));
            $input.off('change.winshirt').on('change.winshirt', (e)=>{
                const files = e.target.files || [];
                if(!files.length) return;
                // on ne prend que le premier pour l’instant
                this.addImageFromFile(files[0], opts);
                $input.val(''); // reset
            });
        },

        /**
         * Replace/fit le calque sélectionné dans la zone d’impression
         * @param {'contain'|'cover'|'none'} mode
         */
        fitSelected(mode='contain'){
            const layer = WinShirtState.getSelectedLayer();
            if(!layer || layer.type !== 'image') return;

            const zone = this._getPrintZoneRect();

            // Image réelle : calcul du ratio via un objet Image
            const img = new Image();
            img.onload = () => {
                const ratio = img.width / img.height;
                const geom = this._fitBox(ratio, zone, mode);
                WinShirtState.updateLayer(layer.id, geom);

                if(WinShirtLayers && WinShirtLayers.$canvas){
                    const $el = WinShirtLayers.$canvas.find(`.ws-layer[data-id="${layer.id}"]`);
                    $el.css({
                        left: geom.left,
                        top: geom.top,
                        width: geom.width,
                        height: geom.height
                    });
                }

                $(document).trigger('winshirt:imageFitted', [layer.id, mode]);
            };
            img.src = layer.src;
        }
    };

    // Expose
    window.WinShirtImageTools = ImageTools;

    // Hooks simples
    $(function(){
        // Bouton générique : data-ws-add-image-url="https://..."
        $(document).on('click', '[data-ws-add-image-url]', function(e){
            e.preventDefault();
            const url = $(this).attr('data-ws-add-image-url');
            if(url) ImageTools.addImageFromURL({ src: url, fit: 'contain' });
        });

        // Input fichier générique : <input data-ws-image-input>
        ImageTools.bindFileInput('input[data-ws-image-input]', { fit: 'contain' });

        // Boutons de fit
        $(document).on('click', '[data-ws-image-fit]', function(e){
            e.preventDefault();
            const mode = $(this).attr('data-ws-image-fit') || 'contain';
            ImageTools.fitSelected(mode);
        });
    });

})(jQuery);
