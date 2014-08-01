/**
 * Manage the interactive actions for the tags filter.
 */

/*jslint regexp: true */
/*global document, $ */

(function () {
    "use strict";

    $(document).ready(function () {

        var $hideBtn   = $('#hide-small-tags'),
            $showBtn   = $('#show-small-tags'),
            $smallTags = $('.tags-box > .small-tag');

        // By default, hide the "hide" button and the "small tags"
        $hideBtn.add($smallTags).hide();

        $showBtn.click(function () {
            $showBtn.hide();
            $hideBtn.show();
            $smallTags.show();
        });

        $hideBtn.click(function () {
            $showBtn.show();
            $hideBtn.hide();
            $smallTags.hide();
        });

    });

}());
