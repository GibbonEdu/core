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
    $('input.checkUniqueness').after('<span></span><div class="LV_validation_message LV_invalid availability_result"></div>');

    $('input.checkUniqueness').on('input', function () {
        // First check the LV validation before proceeding
        var self = $(this);
        var validation = window[self.attr('id') + "Validate"];
        if (validation != null) {
            if (self.val() == '' || validation.doValidations() == false) {
                self.parent().find('.availability_result').html('');
                return;
            }
        }

        if (self.data("ajax-url") == '') return;

        $.ajax({
            type: 'POST',
            data: { username: self.val(), gibbonPersonID: 0 },
            url: self.data("ajax-url"),
            success: function (responseText) {
                if (responseText == 0) {
                    $(this).next('.LV_validation_message').hide();
                    self.parent().find('.availability_result').html(self.data("alert-success"));
                    self.parent().find('.availability_result').switchClass('LV_invalid', 'LV_valid');
                } else {
                    self.parent().find('.availability_result').html(self.data("alert-fail"));
                    self.parent().find('.availability_result').switchClass('LV_valid', 'LV_invalid');
                    
                    // Prevent submitting form with a non-unique email
                    validation.add(Validate.Exclusion, { within: [self.val()], failureMessage: self.data("alert-fail")});
                }
            }
        });
    });

});