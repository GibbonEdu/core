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

});

// Form API Functions

/**
 * TextField Uniqueness Check
 */
$.prototype.gibbonUniquenessCheck = function (settings) {
    var uniqueField = this;
    var validation;

    $(uniqueField).ready(function(){
        // Get the existing LiveValidation object, otherwise create one
        validation = window[$(uniqueField).attr('id') + "Validate"];
        if (validation == null || typeof validation != "object") {
            validation = new LiveValidation($(uniqueField).attr('id'));
        }

        validation.onValid = function() {
            // Pass the current value as POST['value'] (optionally by a defined fieldName)
            settings.ajaxData[settings.ajaxData.fieldName || "value"] = $(uniqueField).val();

            // Send an AJAX request to check uniqueness, and use LiveValidation messages to display response
            $.ajax({
                type: 'POST',
                data: settings.ajaxData,
                url: settings.ajaxURL,
                success: function (responseText) {
                    // The response should be the count of matching values, so 0 is unique and -1 is an error
                    if (responseText < 0) {
                        validation.message = validation.invalidMessage = settings.alertError;
                        validation.validationFailed = true;
                    } else if (responseText == 0) {
                        validation.message = validation.validMessage = settings.alertSuccess;
                    } else if (responseText > 0) {
                        validation.message = validation.invalidMessage = settings.alertFailure;
                        validation.validationFailed = true;
                        validation.add(Validate.Exclusion, { within: [$(uniqueField).val()], failureMessage: settings.alertFailure });
                    }
                },
                error: function() {
                    validation.message = validation.invalidMessage = settings.alertError;
                    validation.validationFailed = true;
                },
                complete: function() {
                    validation.insertMessage(validation.createMessageSpan());
                    validation.addFieldClass();
                }
            });
        };
    });
};