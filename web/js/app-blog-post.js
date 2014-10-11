/**
 * Some javascript for Blog creation/updating pages
 */

/*jslint regexp: true */
/*global document, $, screenfull */

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
        var idWidget           = '#' + field,
            idInput            = '#' + field + '-input',
            idPreview          = '#' + field + '-preview',
            idRequestPreview   = '#' + field + '-request-preview',
            idToggleFullscreen = '#' + field + '-toggle-fullscreen';

        $(idToggleFullscreen).click(function (e) {
            e.preventDefault();

            if (screenfull.enabled) {
                screenfull.toggle($(idWidget)[0]);
            }
        });

        $(document).on(screenfull.raw.fullscreenchange, function () {
            $(idToggleFullscreen).toggleClass('fa-expand fa-compress');
        });

        $(idRequestPreview).click(function () {
            var preview = $(idPreview),
                text = $.trim($(idInput).val());

            if (preview.hasClass('active') || text === '') {
                return false;
            }

            preview.html(
                '<div style="text-align: center;">' +
                    '<img src="/images/loading.gif" />' +
                '</div>'
            );

            $.ajax({
                method  : 'POST',
                url     : '/render-richtext',
                data    : { text: text },
                success : function (data) {
                    preview.html(data);
                }
            });
        });
    }

    $(document).ready(function () {

        $('#text-input, #summary-input').elastic();

        previewMarkdown('text');
        previewMarkdown('summary');

        // Generate slug from title
        $('#title').keyup(function (event) {
            $('#slug').val(slugify(event.target.value));
        });

    });

}());
