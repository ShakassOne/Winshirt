/**
 * WinShirt - Outils Texte
 * - Ajout d'un calque texte
 * - Mise à jour de styles: fontFamily, fontSize, fontWeight, textAlign, color, strokeColor, strokeWidth, letterSpacing, lineHeight
 * - Sélection automatique du calque créé
 *
 * Dépendances : jQuery, WinShirtState, WinShirtLayers
 */

(function($){
    'use strict';

    const DEFAULTS = {
        text: 'Votre texte',
        fontFamily: 'Arial, sans-serif',
        fontSize: 32,         // px
        fontWeight: 600,
        textAlign: 'center',  // left|center|right
        color: '#111111',
        strokeColor: '#ffffff',
        strokeWidth: 0,       // px
        letterSpacing: 0,     // px
        lineHeight: 1.2,      // unitless
        width: 260,           // px (zone initiale)
        height: 100,
        left: 80,
        top: 80,
        rotation: 0
    };

    const TextTools = {

        /**
         * Crée un ID unique
         */
        _uid() {
            return 'txt_' + Math.random().toString(36).slice(2, 9);
        },

        /**
         * Ajoute un calque texte et le rend sélectionné
         * @param {Object} opts - options texte (merge avec DEFAULTS)
         */
        addText(opts = {}) {
            const o = Object.assign({}, DEFAULTS, opts);
            const id = this._uid();

            /** Modèle de calque texte dans le State */
            const layer = {
                id,
                type: 'text',
                name: (o.name || 'Texte'),
                text: String(o.text || DEFAULTS.text),
                fontFamily: o.fontFamily,
                fontSize: parseInt(o.fontSize, 10),
                fontWeight: o.fontWeight,
                textAlign: o.textAlign,
                color: o.color,
                strokeColor: o.strokeColor,
                strokeWidth: parseInt(o.strokeWidth, 10),
                letterSpacing: parseFloat(o.letterSpacing),
                lineHeight: parseFloat(o.lineHeight),
                width: parseFloat(o.width),
                height: parseFloat(o.height),
                left: parseFloat(o.left),
                top: parseFloat(o.top),
                rotation: parseFloat(o.rotation)
            };

            // State
            WinShirtState.addLayer(layer);

            // Canvas immédiat (si déjà initialisé)
            if(window.WinShirtLayers && WinShirtLayers.$canvas){
                WinShirtLayers.addLayerElement(layer);
                WinShirtLayers.highlightSelected();
            }

            $(document).trigger('winshirt:textAdded', [layer, WinShirtState.currentSide]);
            return layer;
        },

        /**
         * Applique un ensemble de styles au calque sélectionné (ou à un calque par id)
         * @param {Object} styles - ex: { fontSize: 40, color: '#ff0000' }
         * @param {String|null} layerId
         */
        applyStyles(styles = {}, layerId = null) {
            const id = layerId || WinShirtState.selectedLayerId;
            if(!id){
                console.warn('TextTools.applyStyles: aucun calque sélectionné');
                return;
            }

            // Normalisation
            const updates = {};
            if(styles.text      !== undefined) updates.text        = String(styles.text);
            if(styles.fontFamily!== undefined) updates.fontFamily  = String(styles.fontFamily);
            if(styles.fontSize  !== undefined) updates.fontSize    = parseInt(styles.fontSize, 10);
            if(styles.fontWeight!== undefined) updates.fontWeight  = styles.fontWeight; // 400/600/bold…
            if(styles.textAlign !== undefined) updates.textAlign   = String(styles.textAlign); // left|center|right
            if(styles.color     !== undefined) updates.color       = String(styles.color);
            if(styles.strokeColor!==undefined) updates.strokeColor = String(styles.strokeColor);
            if(styles.strokeWidth!==undefined) updates.strokeWidth = parseInt(styles.strokeWidth, 10);
            if(styles.letterSpacing!==undefined) updates.letterSpacing = parseFloat(styles.letterSpacing);
            if(styles.lineHeight !== undefined) updates.lineHeight = parseFloat(styles.lineHeight);
            if(styles.width     !== undefined) updates.width       = parseFloat(styles.width);
            if(styles.height    !== undefined) updates.height      = parseFloat(styles.height);
            if(styles.left      !== undefined) updates.left        = parseFloat(styles.left);
            if(styles.top       !== undefined) updates.top         = parseFloat(styles.top);
            if(styles.rotation  !== undefined) updates.rotation    = parseFloat(styles.rotation);

            // State
            WinShirtState.updateLayer(id, updates);

            // DOM refresh ciblé
            if(window.WinShirtLayers && WinShirtLayers.$canvas){
                const $el = WinShirtLayers.$canvas.find(`.ws-layer[data-id="${id}"]`);
                if($el.length){
                    // maj box + transform
                    const css = {};
                    if(updates.left  !== undefined) css.left = updates.left;
                    if(updates.top   !== undefined) css.top = updates.top;
                    if(updates.width !== undefined) css.width = updates.width;
                    if(updates.height!== undefined) css.height = updates.height;
                    if(updates.rotation !== undefined){
                        const rot = updates.rotation;
                        const prev = $el.css('transform'); // peu fiable, on remplace
                        css.transform = `rotate(${rot}deg)`;
                    }
                    $el.css(css);

                    // maj contenu texte
                    const $txt = $el.find('.ws-text-content');
                    if(!$txt.length){
                        $el.empty().append('<div class="ws-text-content"></div>');
                    }
                    const $content = $el.find('.ws-text-content');
                    if(updates.text !== undefined) $content.text(updates.text);

                    const styleStr = [
                        updates.fontFamily  !== undefined ? `font-family:${updates.fontFamily}` : null,
                        updates.fontSize    !== undefined ? `font-size:${updates.fontSize}px` : null,
                        updates.fontWeight  !== undefined ? `font-weight:${updates.fontWeight}` : null,
                        updates.textAlign   !== undefined ? `text-align:${updates.textAlign}` : null,
                        updates.color       !== undefined ? `color:${updates.color}` : null,
                        updates.letterSpacing!==undefined ? `letter-spacing:${updates.letterSpacing}px` : null,
                        updates.lineHeight  !== undefined ? `line-height:${updates.lineHeight}` : null,
                        // stroke via text-shadow fallback simple (contour pauvre mais léger)
                        (updates.strokeWidth !== undefined || updates.strokeColor !== undefined)
                            ? TextTools._textStrokeCSS(
                                updates.strokeWidth ?? WinShirtState.getSelectedLayer()?.strokeWidth ?? 0,
                                updates.strokeColor ?? WinShirtState.getSelectedLayer()?.strokeColor ?? '#ffffff'
                              )
                            : null
                    ].filter(Boolean).join(';');

                    $content.attr('style', styleStr);
                }
            }

            $(document).trigger('winshirt:textUpdated', [id, updates]);
        },

        /**
         * Génére une approximation de contour texte (text-stroke CSS n’étant pas bien supporté partout)
         * Via text-shadow multiples concentriques (simple/peu coûteux visuellement)
         */
        _textStrokeCSS(width, color){
            width = parseInt(width, 10) || 0;
            if(width <= 0) return '';
            const shadows = [];
            for(let r=1; r<=width; r++){
                // 8 directions par rayon
                shadows.push(`${r}px 0 0 ${color}`);
                shadows.push(`-${r}px 0 0 ${color}`);
                shadows.push(`0 ${r}px 0 ${color}`);
                shadows.push(`0 -${r}px 0 ${color}`);
                shadows.push(`${r}px ${r}px 0 ${color}`);
                shadows.push(`-${r}px ${r}px 0 ${color}`);
                shadows.push(`${r}px -${r}px 0 ${color}`);
                shadows.push(`-${r}px -${r}px 0 ${color}`);
            }
            return `text-shadow:${shadows.join(',')}`;
        }
    };

    // Expose global
    window.WinShirtTextTools = TextTools;

    // Hooks simples pour démos/liaisons rapides (facultatifs)
    $(function(){
        // Si un bouton global existe (ex: [data-ws-add-text])
        $(document).on('click', '[data-ws-add-text]', function(e){
            e.preventDefault();
            TextTools.addText({ text: $(this).data('defaultText') || DEFAULTS.text });
        });

        // Exemple d’inputs liés (data-ws-text-*), utiles quand on branchera le L3 "Styles"
        $(document).on('input change', '[data-ws-text-style]', function(){
            const key = $(this).attr('name'); // ex: fontSize, color...
            const val = $(this).val();
            if(!key) return;

            const styles = {};
            styles[key] = val;
            TextTools.applyStyles(styles);
        });
    });

})(jQuery);
