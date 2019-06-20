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

    // Initialize datepicker
    $.datepicker.setDefaults($.datepicker.regional[Gibbon.config.datepicker.locale]);


    // Initialize tooltip
    if ($(window).width() > 768) {
        $(document).tooltip({
            show: 800,
            hide: false,
            content: function () {
                return $(this).prop('title');
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
        toolbar: 'bold, italic, underline,forecolor,backcolor,|,alignleft, aligncenter, alignright, alignjustify, |, formatselect, |, fontselect, fontsizeselect, |, table, |, bullist, numlist,outdent, indent, |, link, unlink, image, media, hr, charmap, subscript, superscript, |, cut, copy, paste, undo, redo, fullscreen',
        plugins: 'table, template, paste, visualchars, link, template, textcolor, hr, charmap, fullscreen',
        statusbar: false,
        valid_elements: Gibbon.config.tinymce.valid_elements,
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
        $.sessionTimeout({
            message: Gibbon.config.sessionTimeout.message,
            keepAliveUrl: 'keepAlive.php' ,
            redirUrl: 'logout.php?timeout=true',
            logoutUrl: 'logout.php' ,
            warnAfter: sessionDuration * 1000,
            redirAfter: (sessionDuration * 1000) + 600000
        });
    }
});
