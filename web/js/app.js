/**
 * Some global/bulk javascript
 */

/*global document, window, $ */

(function () {
    "use strict";

    /**
     * Enable the possibility to change the captcha of current user.
     */
    $.enableChangeCaptcha = function () {
        $('#captcha-change').click(function () {

            var $img = $('#captcha-img');

            $img.attr('src', '/images/loading.gif');

            $.ajax({
                method  : 'GET',
                url     : '/captcha-change',
                success : function (data) { $img.attr('src', data); }
            });

        });
    };

    /**
     * Manage #back2top link.
     */
    $(window).scroll(function () {

        if (this.pageYOffset > 200) {
            $('#back2top').fadeIn(600);
        } else {
            $('#back2top').fadeOut(400);
        }

    });

    $(document).ready(function () {
        $('body').tooltip({
            selector: '[title]'
        });
    });

}());
