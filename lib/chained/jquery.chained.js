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
            var backup = $(child).clone();

            $(parent_selector).on("change", function() {
                updateChildren();
            });

            updateChildren();

            function updateChildren() {

                var selectedParent = $("option:selected", parent_selector).val();
                var selectedChild = $("option:selected", child).val();

                /* Duplicate the original full list, then remove the unnecessary elements */
                $(child).html(backup.html());
                $(child).val('');

                $(child).find('option').each(function() {
                    if ( !$(this).hasClass(selectedParent) && $(this).val() != '') {
                        $(this).detach().remove();
                    }
                });

                /* Re-select options after re-building the select element */
                $('option[value="'+selectedChild+'"]', child).prop('selected', true);

                /* Filter selectable options so we're never showing the list when there's nothing to select */
                var selectable = $(child).find('option').filter(function() {
                    return $(this).css('display') != 'none' && $(this).val() != '';
                });

                /* If we have only the default value disable select. */
                $(child).prop("disabled", selectable.length == 0 );
            }
        });
    };

    /* Alias for those who like to use more English like syntax. */
    $.fn.chainedTo = $.fn.chained;

    /* Default settings for plugin. */
    $.fn.chained.defaults = {};

})(window.jQuery, window, document);
