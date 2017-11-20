/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

jQuery(function($){
    /**
     * Form Class: generic check All/None checkboxes
     */
    $('.checkall').click(function () {
        $(this).parents('fieldset:eq(0)').find(':checkbox').attr('checked', this.checked);
    });

    /**
     * Column Highlighting
     */
    var columnHighlight = $(".columnHighlight td");
    columnHighlight.on("mouseover", function() {
      columnHighlight.filter(":nth-child(" + ($(this).index() + 1) + ")").addClass("hover");
    })
    .on("mouseout", function() {
      columnHighlight.removeClass("hover");
    });

    /**
     * Password Generator. Requires data-source, data-confirm and data-alert attributes.
     */
    $(".generatePassword").click(function(){
        if ($(this).data("source") == "" || $(this).data("confirm") == "") return;

        var chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789![]{}()%&*$#^~@|";
        var text = '';
        for(var i=0; i < 12; i++) {
            if (i==0) { text += chars.charAt(Math.floor(Math.random() * 26)); }
            else if (i==1) { text += chars.charAt(Math.floor(Math.random() * 26)+26); }
            else if (i==2) { text += chars.charAt(Math.floor(Math.random() * 10)+52); }
            else if (i==3) { text += chars.charAt(Math.floor(Math.random() * 19)+62); }
            else { text += chars.charAt(Math.floor(Math.random() * chars.length)); }
        }
        $('input[name="' + $(this).data("source") + '"]').val(text).blur();
        $('input[name="' + $(this).data("confirm") + '"]').val(text).blur();
        prompt($(this).data("alert"), text);
    });

    /**
     * Generic Uniqueness Check.
     */
    $('input.checkUniqueness').each(function () {
        var ajaxURL = $(this).data("ajax-url");
        var ajaxData = $(this).data("ajax-data");
        if (ajaxURL == '' || ajaxData == '') return;

        var validation = window[$(this).attr('id') + "Validate"];
        if (validation == null || typeof validation != "object") {
            validation = new LiveValidation($(this).attr('id'));
        }

        $(this).removeAttr("data-ajax-url data-ajax-data");

        $(this).on('input', function (event) {
            // Give the LiveValidation priority - and don't proceed if it fails
            if (validation.doValidations() != true) {
                return;
            }
    
            // Pass the current value as POST data (optionally by a defined fieldName)
            ajaxData[ajaxData.fieldName || "value"] = $(this).val();
    
            // Send an AJAX request to check uniqueness
            // event.stopPropagation();
            // event.preventDefault();
            $.ajax({
                type: 'POST',
                data: ajaxData,
                url: ajaxURL,
                context: this,
                success: function (responseText) {
                    console.log(responseText);
                    if (responseText < 0) {
                        validation.invalidMessage = $(this).data("alert-error");
                        validation.onInvalid();
                    } else if (responseText == 0) {
                        validation.validMessage = $(this).data("alert-success");
                        validation.onValid();
                    } else if (responseText > 0){
                        validation.invalidMessage = $(this).data("alert-fail");
                        validation.onInvalid();
                        validation.add(Validate.Exclusion, { within: [$(this).val()], failureMessage: $(this).data("alert-fail") });
                    }
                },
                error: function() {
                    validation.invalidMessage = $(this).data("alert-error");
                    validation.onInvalid();
                }
            });
        });
    });
    
});