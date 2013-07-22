/**
 * Some javascript for Blog creation/updating pages
 */

/*jslint regexp: true */
/*global document, $ */

(function () {
    "use strict";

    /**
     * Modifies a string to remove all non ASCII characters and spaces,
     * and to put ASCII characters in lowercase.
     */
    function slugify(str) {
        var from = "ãàáäâẽèéëêìíïîõòóöôùúüûñç·/_,:;",
            to   = "aaaaaeeeeeiiiiooooouuuunc------",
            i,
            l;

        str = str.toLowerCase();

        // remove accents, swap ñ for n, etc
        for (i = 0, l = from.length; i < l; i += 1) {
            str = str.replace(new RegExp(from.charAt(i), 'g'), to.charAt(i));
        }

        str = str.replace(/[^a-z0-9 \-]/g, '') // remove invalid chars
            .replace(/\s+/g, '-')              // collapse whitespace and replace by -
            .replace(/-+/g, '-')               // collapse dashes
            .replace(/^-+|-+$/g, '');          // trim -

        return str;
    }

    function previewMarkdown(field) {
        var idField   = '#' + field,
            idPreview = '#' + field + '-preview',
            idLink    = '#' + field + '-preview-link';

        $(idLink).click(function () {
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
                url     : '/render-richtext',
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

        // Generate slug from title
        $('#title').keyup(function (event) {
            $('#slug').val(slugify(event.target.value));
        });

    });

}());
