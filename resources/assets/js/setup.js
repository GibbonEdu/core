/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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
        contextmenu: false,
        branding: false,
        valid_elements: Gibbon.config.tinymce.valid_elements,
        extended_valid_elements : Gibbon.config.tinymce.extended_valid_elements,
        invalid_elements: '',
        apply_source_formatting : true,
        browser_spellcheck: true,
        convert_urls: false,
        relative_urls: false,
        default_link_target: "_blank",
        color_map: [
            "#BFEDD2", "Light Green", 
            "#FBEEB8", "Light Yellow", 
            "#F8CAC6", "Light Red", 
            "#ECCAFA", "Light Purple", 
            "#C2E0F4", "Light Blue", 
            "#2DC26B", "Green", 
            "#F1C40F", "Yellow", 
            "#FF0000", "Red", 
            "#B96AD9", "Purple", 
            "#3598DB", "Blue", 
            "#169179", "Dark Turquoise", 
            "#E67E23", "Orange", 
            "#BA372A", "Dark Red", 
            "#843FA1", "Dark Purple", 
            "#236FA1", "Dark Blue", 
            "#ECF0F1", "Light Gray", 
            "#CED4D9", "Medium Gray", 
            "#95A5A6", "Gray", 
            "#7E8C8D", "Dark Gray", 
            "#34495E", "Navy Blue", 
            "#000000", "Black", 
            "#ffffff", "White", 
        ],
    });

    // Sticky Observer
    const el = document.querySelector(".submitRow.sticky");
    const observer = new IntersectionObserver( 
        function([e]) { 
            e.target.classList.toggle("shadow-top", e.intersectionRatio < 1);
            e.target.classList.toggle("bg-gray-300", e.intersectionRatio < 1);
        },
        { threshold: [1] }
    );

    if (el != undefined) observer.observe(el);
});
