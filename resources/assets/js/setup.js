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

// Initialize all legacy Thickbox links as HTMX AJAX calls
Array.from(document.getElementsByClassName('thickbox')).forEach((element) => {
    if (element.nodeName != 'A') return;
    
    element.setAttribute('hx-boost', 'true');
    element.setAttribute('hx-target', '#modalContent');
    element.setAttribute('hx-push-url', 'false');
    element.setAttribute('x-on:htmx:after-on-load', 'modalOpen = true');
    element.classList.remove('thickbox');

    if (element.getAttribute('href').includes('_delete')) {
        element.setAttribute('x-on:click', "modalType = 'delete'");
    }
});

// Enable preventing page navigation from hx-boosted links
if (!document.body.hasAttribute('hx-loaded')) {
    document.body.setAttribute('hx-loaded', true);
    document.body.addEventListener('htmx:confirm', function(evt) {
        if (!evt.detail.elt.hasAttribute('hx-boost')) return;

        evt.preventDefault();
    
        if (window.onbeforeunload != null) {
            if (window.confirm(Gibbon.config.htmx.unload_confirm)) {
                window.onbeforeunload = null;
                evt.detail.issueRequest(true);
            }
        } else {
            evt.detail.issueRequest(true);
        }
    }, false);
}

htmx.onLoad(function (content) {

    $(document).trigger('gibbon-setup');

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

    document.dispatchEvent(new Event('tinymceSetup'));
    
    // Unload tinymce if it exists, via ajax
    if (tinymce) tinymce.remove();

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

    
});
