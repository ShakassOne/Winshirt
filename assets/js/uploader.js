/**
 * WinShirt - Uploader
 * - Upload d'images (préviews recto/verso, fichiers ajoutés) vers WordPress Media Library via REST API
 * - Retourne attachment_id + URL
 *
 * Dépendances : jQuery, WinShirtData (nonce, restUrl)
 */

(function($){
    'use strict';

    const Uploader = {

        /**
         * Upload depuis un Blob ou File
         * @param {Blob|File} file
         * @param {String} filename
         * @param {Function} cbSuccess (attachment)
         * @param {Function} cbError (error)
         */
        uploadFile(file, filename, cbSuccess, cbError){
            if(!file){
                cbError && cbError('Aucun fichier fourni');
                return;
            }

            const formData = new FormData();
            formData.append('file', file, filename || file.name || 'upload.png');

            $.ajax({
                url: `${WinShirtData.restUrl}/media`,
                method: 'POST',
                processData: false,
                contentType: false,
                data: formData,
                headers: {
                    'X-WP-Nonce': WinShirtData.nonce
                },
                success: function(response){
                    cbSuccess && cbSuccess(response);
                },
                error: function(xhr){
                    console.error('Upload error', xhr);
                    cbError && cbError(xhr);
                }
            });
        },

        /**
         * Upload depuis une dataURL (ex: html2canvas output)
         * @param {String} dataUrl
         * @param {String} filename
         * @param {Function} cbSuccess
         * @param {Function} cbError
         */
        uploadDataURL(dataUrl, filename, cbSuccess, cbError){
            try{
                const arr = dataUrl.split(',');
                const mimeMatch = arr[0].match(/:(.*?);/);
                const mime = mimeMatch ? mimeMatch[1] : 'image/png';
                const bstr = atob(arr[1]);
                let n = bstr.length;
                const u8arr = new Uint8Array(n);
                while(n--){
                    u8arr[n] = bstr.charCodeAt(n);
                }
                const file = new Blob([u8arr], { type: mime });
                this.uploadFile(file, filename, cbSuccess, cbError);
            }catch(e){
                console.error('uploadDataURL error', e);
                cbError && cbError(e);
            }
        }
    };

    window.WinShirtUploader = Uploader;

    // Hooks pour boutons data-ws-upload-dataurl
    $(function(){
        $(document).on('click', '[data-ws-upload-dataurl]', function(e){
            e.preventDefault();
            const sel = $(this).attr('data-ws-upload-dataurl');
            const $el = $(sel);
            if(!$el.length){
                alert('Élément source introuvable pour capture');
                return;
            }
            html2canvas($el[0], { useCORS: true }).then(canvas=>{
                const dataUrl = canvas.toDataURL('image/png');
                Uploader.uploadDataURL(dataUrl, 'preview.png', function(res){
                    console.log('Upload OK', res);
                }, function(err){
                    console.error('Upload fail', err);
                });
            });
        });
    });

})(jQuery);
