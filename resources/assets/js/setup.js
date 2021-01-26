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

$(document).ready(function(){

    $(document).trigger('gibbon-setup');

    // Initialize datepicker
    var dateDefaults = $.datepicker.regional[Gibbon.config.datepicker.locale];
    dateDefaults.dateFormat = Gibbon.config.datepicker.dateFormat;
    dateDefaults.firstDay = Gibbon.config.datepicker.firstDay;
    
    $.datepicker.setDefaults(dateDefaults);
    

    // Initialize tooltip
    if ($(window).width() > 768) {
        $(document).tooltip({
            show: 800,
            hide: false,
            items: "*[title]:not(.tox-edit-area__iframe):not(.tox-collection__item):not(.tox-button):not(.tox-tbtn--select)",
            content: function () {
                return $(this).prop('title');
            },
            open: function(event, ui) {
                ui.tooltip.delay(3000).fadeTo(1000, 0);
            },
            position: {
                my: "center bottom-20",
                at: "center top",
                using: function (position, feedback) {
                    $(this).css(position);
                    $("<div>").
                        addClass("arrow").
                        addClass(feedback.vertical).
                        addClass(feedback.horizontal).
                        appendTo(this);
                }
            }
        });
    }

    // Initialize latex
    $(".latex").latex();

    // Initialize tinymce
    tinymce.init({
        selector: "div#editorcontainer textarea",
        width: '100%',
        menubar : false,
        resize: true,
        toolbar_mode: 'sliding',
        toolbar: 'bold italic underline  forecolor backcolor |  alignleft aligncenter alignright alignjustify | bullist numlist indent outdent | link unlink hr charmap | fullscreen | styleselect fontselect fontsizeselect | table | subscript superscript | cut copy paste undo redo ',
        plugins: 'table lists paste link hr charmap fullscreen',
        statusbar: true,
        branding: false,
        valid_elements: Gibbon.config.tinymce.valid_elements,
        extended_valid_elements : Gibbon.config.tinymce.extended_valid_elements,
        invalid_elements: '',
        apply_source_formatting : true,
        browser_spellcheck: true,
        convert_urls: false,
        relative_urls: false,
        default_link_target: "_blank"
    });

    // Initialize sessionTimeout
    var sessionDuration = Gibbon.config.sessionTimeout.sessionDuration;
    if (sessionDuration > 0) {
        sessionTimeout({
            message: Gibbon.config.sessionTimeout.message,
            keepAliveUrl: 'keepAlive.php' ,
            timeOutUrl: 'logout.php?timeout=true',
            logOutUrl: 'logout.php',
            logOutBtnText: Gibbon.config.sessionTimeout.logOutBtnText,
            stayConnectedBtnText: Gibbon.config.sessionTimeout.stayConnectedBtnText,
            warnAfter: sessionDuration,
            timeOutAfter: (sessionDuration) + 600000,
            titleText: Gibbon.config.sessionTimeout.titleText
        });
    }
});
