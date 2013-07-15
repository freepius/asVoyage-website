/**
 * Some global/bulk javascript
 */

/*global document, $ */

(function () {
    "use strict";

    /**
     * Enable the possibility to change the captcha of current user.
     */
    $.enableChangeCaptcha = function () {
        $('#captcha-change').click(function () {

            var $img = $('#captcha-img');

            $img.attr('src', '/images/ajax-loader.gif');

            $.ajax({
                method  : 'GET',
                url     : '/captcha-change',
                success : function (data) { $img.attr('src', data); }
            });

        });
    };

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
