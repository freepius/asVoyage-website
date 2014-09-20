/**
 * Some javascript in bulk for Media module.
 */

/*jslint nomen: true, regexp: true */
/*global document, window, $ */

(function () {
    "use strict";

    /***************************************************************************
     *
     * Manage the selection checkboxes, counters, controls... for media elements.
     *
     **************************************************************************/
    $.media = {

        init: function () {
            this.shiftCheckboxHandler();
            this.refresh();
        },

        shiftCheckboxHandler: function () {
            $('#media .actions')
                .off('.shiftcheckbox')
                .shiftcheckbox({
                    checkboxSelector: '.toggle',
                    selectAll: $('.toggle-all'),
                    onChange: $.media.refresh
                });

            $('#media .toggle')
                .unbind('change')
                .change($.media.refresh);
        },

        /**
         * Getters, counters, etc.
         */

        // Number of media elements
        count: function () {
            return $('#media').children(':not(.no-element)').length;
        },

        // Number of selected media elements
        countSelected: function () {
            return $('.toggle:checked').length;
        },

        /**
         * Actions on controls, texts, etc.
         */

        refresh: function () {
            $.media.refreshControls();
            $.media.refreshCounters();
        },

        refreshControls: function () {
            $('.control-elements').prop('disabled', $.media.countSelected() === 0);
        },

        refreshCounters: function () {
            $('.counter-elements').html($.media.count());
            $('.counter-selected-elements').html($.media.countSelected());
        }
    };


    /***************************************************************************
     *
     * Manage the "set meta" buttons.
     *
     * /!\ For admin purpose.
     *
     **************************************************************************/
    $.mediaMeta = {

        // Ask a value and fill metadata
        handler: function () {
            var $this = this;

            $('.set-meta').click(function (e) {
                e.preventDefault();

                var meta    = $(e.currentTarget).data('meta'),
                    message = $this.messages[meta],
                    value   = window.prompt(message),
                    checked;

                if (null !== value) {
                    checked = $('.toggle:checked').closest('tr')
                        .find('input[name*="' + meta + '"]');

                    $.mediaMeta[meta](value, checked);
                }
            });
        },

        creationDate: function (value, checked) {
            return checked.val(value);
        },

        geoCoords: function (value, checked) {
            return checked.val(value);
        },

        /**
         * Change tags of selected elements :
         *  -> no prefix    : replace tags by "value"
         *  -> '+' prefixed : add "value" to tags
         *  -> '-' prefixed : delete "value" to tags
         *
         * "value" param. must be tags separated by comma.
         */
        tags: function (value, checked) {
            value = $.trim(value);

            var normalizeTags = function (tags) {
                    return $.map(
                        tags.split(','),
                        function (tag) { return $.trim(tag); }
                    );
                },
                op = value[0],
                tags;

            switch (op) {
            // Delete tags
            case '-':
                tags = normalizeTags(value.substr(1));

                checked.each(function () {
                    var current = $.map(
                        window.array_diff(normalizeTags(this.value), tags),
                        function (v) { return v; }
                    );
                    $(this).val(current.join(', '));
                });
                break;

            // Add tags
            case '+':
                checked.each(function () {
                    this.value += ', ' + value.substr(1);
                });
                break;

            // Replace tags
            default:
                checked.val(value);
            }
        }
    };


    /***************************************************************************
     *
     * Manage the 3 different ways (short, medium and full)
     * of displaying media elements.
     *
     * /!\ Not for admin purpose.
     *
     **************************************************************************/
    $.mediaViews = {

        init: function () {
            $.mediaViews.handler();
            $('#view-short').click(); // default : short view
        },

        handler: function () {
            // Handle the short view (has tooltip)
            $('#view-short').click(function () {
                $('#media')
                    .removeClass('view-full view-medium')
                    .addClass('view-short');

                $('.thumbnail')
                    .popover('destroy')
                    .tooltip('destroy')  // NOTE: mandatory to destroy 'tooltip' ! Apparently, it isn't a bug.
                    .tooltip({
                        html      : true,
                        container : 'body',
                        placement : 'right',
                        title     : function () { return $(this).find('.caption > em').html(); }
                    });
            });

            // Handle the medium view (has popover)
            $('#view-medium').click(function () {
                $('#media')
                    .removeClass('view-full view-short')
                    .addClass('view-medium');

                $.mediaViews.equalizeThumbnails();

                $('.thumbnail')
                    .tooltip('destroy')
                    .popover({
                        html      : true,
                        container : 'body',
                        trigger   : 'hover',
                        title     : function () { return $(this).find('.technical').html(); },
                        content   : function () { return $(this).find('.meta').html(); }
                    });
            });

            // Handle the full view (no tooltip nor popover)
            $('#view-full').click(function () {
                $('#media')
                    .removeClass('view-medium view-short')
                    .addClass('view-full');

                $.mediaViews.equalizeThumbnails();

                $('.thumbnail').tooltip('destroy').popover('destroy');
            });
        },

        /**
         * Equalize the heights of left and right '.thumbnail' elements.
         */
        equalizeThumbnails: function () {
            var allMedia = $('#media .thumbnail').height('auto'),
                allLeft  = allMedia.filter(':even'),
                allRight = allMedia.filter(':odd'),
                left,
                right,
                i;

            for (i = 0; i < allRight.length; i += 1) {
                left  = $(allLeft[i]);
                right = $(allRight[i]);

                left.add(right).height(
                    Math.max(left.height(), right.height()) + 5
                );
            }
        }
    };

}());
