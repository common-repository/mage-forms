jQuery(document).ready(function($) {

    var Mage_Form = {
        init: function () {
            $('.mage-form').on('submit', this.checkSubmit);
            $('.mage-form').on('click', 'a.mage-delete-image', this.removeFeatImg);
            this.featImgUploader();
        },
        checkSubmit: function (e) {
            var form = $(this);
			var hasError = false;
			var req_class = 'mage-required';
			var err_class = 'mage-form-error';
            form.find('.'+req_class).each(function() {
				var er = $(this);
                if(er.hasClass(err_class) ) {
                    er.removeClass(err_class);
					hasError = false;
                } else if(er.hasClass('select2-offscreen')) {
					er.prev().removeClass(err_class); 
					er.prev().removeClass(req_class);
					hasError = false;
                }
            });
            form.find('.'+req_class).each(function() {
                var el = $(this);
                //var labelText = el.prev('label').text();
				//var eVal = $.trim(el.val());
				if(el.is('input:text') || el.is('textarea') || el.is('button')) {
					if(el.val() == '') {
						el.addClass(err_class);
						hasError = true;
					}
                } else if(el.hasClass('email')) {
                    var emailReg = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;
                    if(!emailReg.test($.trim(el.val()))) {
                        el.addClass(err_class);
                        hasError = true;
                    }
                } else if(el.hasClass('select2-offscreen')) {
                    if( el.val() == '-1' || el.val() == '') {
                        //el.addClass('error');
						//el.parent().find('div.select2-container').addClass('error');
						var selectContainer =  el.prev();
						selectContainer.addClass(err_class);
						selectContainer.addClass(req_class);
                        hasError = true;
                    }
                }
            });
			
            if( ! hasError ) {
				$(this).find('.mage-form-submit').removeClass(err_class); 
				$(this).find('.mage-form-submit').addClass('mage-form-success');
                $(this).find('.mage-form-submit').html(scribe.mage_status_submit);
                return true;
            } else {
				$(this).find('.mage-form-submit').addClass(err_class);
				$(this).find('.mage-help-block').addClass(err_class);
			}
            //return false;
			e.preventDefault();
        },
        featImgUploader: function() {           
            var uploader = new plupload.Uploader(scribe.plupload);
			uploader.bind('Init', function(up, params) {
                });
            $('.mage-form-upload-btn').click(function(e) {
                uploader.start();
                e.preventDefault();
            });
            uploader.init();
            uploader.bind('FilesAdded', function(up, files){
                $.each(files, function(i, file) {
                    $('.mage-form-upload-thumbnail').append(
                        '<div id="' + file.id + '">' +
                        file.name + ' (' + plupload.formatSize(file.size) + ') <b></b>' +
                        '</div>');
                });
                up.refresh(); // Reposition Flash/Silverlight
                uploader.start();
            });
            uploader.bind('UploadProgress', function(up, file) {
                $('#' + file.id + " b").html(file.percent + "%");
            });

            uploader.bind('Error', function(up, err) {
                $('.mage-form-upload-thumbnail').append("<div>Error: " + err.code +
                    ", Message: " + err.message +
                    (err.file ? ", File: " + err.file.name : "") +
                    "</div>"
                    );

                up.refresh(); // Reposition Flash/Silverlight
            });

            uploader.bind('FileUploaded', function(up, file, response) {
                var resp = $.parseJSON(response.response);
                //$('#' + file.id + " b").html("100%");
                
                if( resp.success ) {
                    $('.mage-form-upload-thumbnail').append(resp.html);
                    $('#mage-form-upload-button').hide();
					$('#' + file.id).remove();
                }
            });
        },
        removeFeatImg: function(e) {
            e.preventDefault();
            if(confirm(scribe.mage_status_confirm)) {
                var el = $(this),
                    data = {
                        'attach_id' : el.data('id'),
                        'nonce' : scribe.nonce,
                        'action' : 'mage_img_delete'
                    }
                $.post(scribe.ajaxurl, data, function(){
                    $('#mage-form-upload-button').show();
                    el.parent().remove();
                });
            }
        },
    };
    Mage_Form.init();
});
