/*jslint nomen: true, regexp: true */
/*global $, tmpl, console */

(function () {
    "use strict";

    // New syntax for the tmpl engine to avoid conflict with Twig syntax
    // Use [ and ] instead of { and }
    tmpl.regexp = /([\s'\\])(?![^%]*%\])|(?:\[%(=|#)([\s\S]+?)%\])|(\[%)|(%\])/g;

    $.widget('blueimp.fileupload', $.blueimp.fileupload, {

        // Overloading
        _create: function () {
            this._super();
            this._refresh();
        },

        // Overloading
        _initEventHandlers: function () {
            this._super();

            this._on({
                fileuploadadded         : this._refresh,
                fileuploadfinished      : this._refresh,
                fileuploaddestroyed     : this._refresh,
                fileuploaddestroyfailed : this._destroyfailed
            });
        },

        // Overloading
        _destroyEventHandlers: function () {
            this._off(this.element,
                'fileuploadadded ' +
                'fileuploadfinished ' +
                'fileuploaddestroyed ' +
                'fileuploaddestroyfailed');
            this._super();
        },

        _isReadyToSend: function () {
            var uploadedValid = $('#media > .template-download:not(.danger)').length;

            return (uploadedValid === $.media.count()) && (uploadedValid > 0);
        },

        _refresh: function () {

            $.media.shiftCheckboxHandler();
            $.media.refresh();

            if ($.media.count() > 0) {
                $('#media > .no-element').hide();
            } else {
                $('#media > .no-element').show();
            }

            if (this._isReadyToSend()) {
                $('#submit-media').show();
            } else {
                $('#submit-media').hide();
            }
        },

        _destroyfailed: function () {
            console.log('Impossible to delete this uploaded media element.');
        }

    });

}());
