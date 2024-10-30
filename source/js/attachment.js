jQuery(document).ready(function($) {
	
    var Mage_Attach = {
        init: function () {
            window.MageFileCount = typeof window.MageFileCount == 'undefined' ? 0 : window.MageFileCount;
			this.maxFiles = parseInt($('#mage_upload_limit').val());
            $('.mage-form-attachments-wrap').on('click', 'a.mage-form-del', this.removeTrack);
            $('.mage-form-attachments-wrap ul.mage-attachment-list').sortable({
                cursor: 'crosshair',
                handle: '.handle'
            });

            this.attachUploader();
            this.hideUploadBtn();
        },
        hideUploadBtn: function () {
            if(Mage_Attach.maxFiles !== 0 && window.MageFileCount >= Mage_Attach.maxFiles) {
				$("#mage-form-attach-button").prop('disabled', true);
				$('.mage-form-info').show();
                //$('#mage-form-attach-button').hide();
            }
        },
        attachUploader: function() {
            if(typeof plupload === 'undefined') {
                return;
            }
            var attachUploader = new plupload.Uploader(mage_attachment.plupload);

            $('#mage-form-attach-button').click(function(e) {
                attachUploader.start();
                e.preventDefault();
            });

            attachUploader.init();

            attachUploader.bind('FilesAdded', function(up, files) {
                $.each(files, function(i, file) {
                    $('.mage-form-attachments-wrap').append(
                        '<div id="' + file.id + '">' +
                        file.name + ' (' + plupload.formatSize(file.size) + ') <b></b>' +
                        '</div>');
                });

                up.refresh(); // Reposition Flash/Silverlight
                attachUploader.start();
            });

            attachUploader.bind('UploadProgress', function(up, file) {
                $('#' + file.id + " b").html(file.percent + "%");
            });

            attachUploader.bind('Error', function(up, err) {
                $('.mage-form-attachments-wrap').append("<div>Error: " + err.code +
                    ", Message: " + err.message +
                    (err.file ? ", File: " + err.file.name : "") +
                    "</div>"
                    );

                up.refresh(); // Reposition Flash/Silverlight
            });

            attachUploader.bind('FileUploaded', function(up, file, response) {
                var resp = $.parseJSON(response.response);
                $('#' + file.id).remove();
                //console.log(resp);
                if( resp.success ) {
                    window.MageFileCount += 1;
                    $('.mage-form-attachments-wrap ul').append(resp.html);

                    Mage_Attach.hideUploadBtn();
                }
            });
        },
        removeTrack: function(e) {
            e.preventDefault();

            if(confirm(scribe.mage_status_confirm)) {
                var el = $(this),
                data = {
                    'attach_id' : el.data('attach_id'),
                    'nonce' : mage_attachment.nonce,
                    'action' : 'mage_delete_file'
                };

                $.post(scribe.ajaxurl, data, function(){
                    el.parent().parent().remove();

                    window.MageFileCount -= 1;
                    if(Mage_Attach.maxFiles !== 0 && window.MageFileCount < Mage_Attach.maxFiles ) {
                        //$('#mage-form-attach-button').show();
						$("#mage-form-attach-button").prop('disabled', false);
						$('.mage-form-info').hide();
						
                    }
                });
            }
        }
    };

    //run the bootstrap
    Mage_Attach.init();

});