/**
 * Some global/bulk javascript
 */
(function ($, document) {
    "use strict";

    $(document).ready(function () {

        // auto-tooltip <a/>, <i/> and <button/>
        $('a[title]').add('i[title]').add('button[title]').tooltip();

    });

})(jQuery, document);
