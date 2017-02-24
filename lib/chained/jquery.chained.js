/*
 * Chained - jQuery / Zepto chained selects plugin
 *
 * Copyright (c) 2010-2014 Mika Tuupola
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Project home:
 *   http://www.appelsiini.net/projects/chained
 *
 * Version: 1.0.1
 *
 * Modified Feb 24, 2017 by SKuipers for Gibbon v14: https://github.com/GibbonEdu/core
 *   - Fixed jQuery recursion error by simplifying jQuery, removed ability to have multiple parent selects
 *
 */

;(function($, window, document, undefined) {
    "use strict";

    $.fn.chained = function(parent_selector) {

        return this.each(function() {
            /* Save this to child because this changes when scope changes. */
            var child = this;

            $(parent_selector).on("change", function() {
                updateChildren();
            });

            updateChildren();

            function updateChildren() {

                $(child).children('option').each(function() {
                    var selected = $("option:selected", parent_selector).val();
                    if ( !$(this).hasClass(selected) ) {
                        $(this).css("display", "none");
                        $(this).prop("selected", false);
                    } else {
                        $(this).css("display", "inherit");
                    }
                });

                var selectable = $('option', child).filter(function() {
                 return $(this).css('display') != 'none' && $(this).val() != '';
                });

                /* If we have only the default value disable select. */
                if (selectable.length == 0 ) {
                    $(child).prop("disabled", true);

                } else {
                    $(child).prop("disabled", false);
                }
            }
        });
    };

    /* Alias for those who like to use more English like syntax. */
    $.fn.chainedTo = $.fn.chained;

    /* Default settings for plugin. */
    $.fn.chained.defaults = {};

})(window.jQuery, window, document);
