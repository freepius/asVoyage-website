/**
 * Some javascript for Media home page in ADMIN mode.
 */

/*jslint regexp: true */
/*global window, document, $ */

(function () {
    "use strict";

    $(document).ready(function () {

        $.media.init();

        // Update checked media elements
        $('#update').click(function () {
            $('form#media').prop('action', '/media/init-update').submit();
        });

        // Delete checked media elements
        $('#delete').click(function () {

            var confirmDelete = window.confirm(
                $.media.deleteMessage.replace('0', $.media.countSelected())
            );

            if (confirmDelete) {
                $('form#media').prop('action', '/media/delete').submit();
            }
        });

    });

}());
