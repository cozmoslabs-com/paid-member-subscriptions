jQuery(document).ready(function ($) {

    /**
     * Import screen JS
     */
    var PMS_Import = {

        init: function () {

            this.submit();
            this.dismiss_message();
        },

        submit: function () {

            var self = this;

            $(document.body).on('submit', '.pms-import-form', function (e) {
                e.preventDefault();

                var submitButton = $(this).find('input[type="submit"]');

                if (!submitButton.hasClass('button-disabled')) {

                    var data = $(this).serialize();

                    var files = $(this.querySelector('#subscriptionscsv')).prop('files'); // FileList object
                    var file = files[0];

                    if ( !file ){
                        alert('Please select a file');
                        return;
                    }

                    if ( file.type !== 'text/csv' ){
                        alert('Please select a .csv file');
                        return;
                    }

                    // submitButton.addClass('button-disabled');
                    $(this).find('.notice-wrap').remove();
                    $(this).prepend('<div class="notice-wrap pms-processing"><span class="spinner is-active"></span></div></div></div>');

                    // read the file contents
                    var reader = new FileReader();
                    reader.readAsText(file);

                    reader.onload = function(event){
                        var csv = event.target.result;

                        csv = JSON.stringify(csv);
                        var file_data = JSON.stringify( { 'name': file.name, 'size' : file.size, 'type' : file.type, 'lastModified' : file.lastModified } );

                        self.process(data, csv, file_data, self);
                    };

                    reader.onerror = function(){
                        alert('Unable to read ' + file.fileName);

                        //submitButton.removeClass('button-disabled');
                    };

                }

            });
        },

        process: function ( data, csv, file_data, self) {

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    form: data,
                    csv: csv,
                    file_data: file_data,
                    action: 'pms_do_ajax_import',
                },
                dataType: "json",
                success: function (response) {
                    var import_form = $('.pms-import-form').find('.pms-processing').parent().parent();
                    var notice_wrap = import_form.find('.notice-wrap');

                    import_form.find('.button-disabled').removeClass('button-disabled');

                    if ( response.error || response.success) {

                        if (response.error) {

                            var error_message = response.message;
                            notice_wrap.html('<div class="updated error pms-notice"><p>' + error_message + '</p></div>');

                        } else if (response.success) {

                            var success_message = response.message;
                            notice_wrap.html('<div id="pms-import-success" class="updated notice is-dismissible pms-notice"><p>' + success_message + '<span class="notice-dismiss"></span></p></div>');

                        } else {

                            notice_wrap.remove();
                            window.location = response.url;

                        }

                    } else {
                        notice_wrap.html('<div class="updated error pms-notice"><p>Something went wrong after the upload!</p></div>');
                    }

                }
            }).fail(function (response) {
                if (window.console && window.console.log) {
                    console.log(response);
                }
            });

        },

        dismiss_message: function () {
            $(document.body).on('click', '#pms-import-success .notice-dismiss', function () {
                $('#pms-import-success').parent().slideUp('fast');
            });
        }

    };
    PMS_Import.init();

});
