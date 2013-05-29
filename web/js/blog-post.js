/**
 * Some javascript for Blog creation/updating pages
 */
(function ($, document) {
    "use strict";

    function previewMarkdown(field) {
        var idField   = '#' + field,
            idPreview = '#' + field + '-preview',
            idLink    = '#' + field + '-preview-link';

        $(idLink).click(function (event) {
            var preview = $(idPreview),
                text = $.trim($(idField).val());

            if (preview.hasClass('active') || text === '') {
                return false;
            }

            preview.html(
                '<div style="text-align: center;">' +
                    '<img src="/images/ajax-loader.gif" />' +
                '</div>'
            );

            $.ajax({
                method  : 'POST',
                url     : '/render-markdown',
                data    : { text: text },
                success : function (data) {
                    $(preview).html(data);
                }
            });
        });
    }

    $(document).ready(function () {

        $('#text, #summary').elastic();
        previewMarkdown('text');
        previewMarkdown('summary');

    });

})(jQuery, document);
