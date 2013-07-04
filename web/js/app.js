/**
 * Some global/bulk javascript
 */

/*global document, $ */

(function () {
    "use strict";

    /**
     * Auto-tooltip <a/>, <i/> and <button/>
     */
    $.autoTooltip = function () {
        $('a[title], i[title], button[title]').tooltip();
    };

    $(document).ready(function () {

        $.autoTooltip();

    });

}());
