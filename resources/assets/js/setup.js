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

document.addEventListener("DOMContentLoaded", () => {

    htmx.onLoad(function (content) {
        
        // Initialize all legacy Thickbox links as HTMX AJAX calls
        Array.from(document.getElementsByClassName('thickbox')).forEach((element) => {
            if (element.nodeName != 'A') return;
            
            element.setAttribute('hx-boost', 'true');
            element.setAttribute('hx-target', '#modalContent');
            element.setAttribute('hx-push-url', 'false');
            element.setAttribute('hx-swap', 'innerHTML show:no-scroll swap:0s');
            element.setAttribute('x-on:htmx:after-on-load', 'modalOpen = true');
            element.classList.remove('thickbox');

            element.setAttribute('x-on:click', element.getAttribute('href').includes('_delete') ? "modalType = 'delete'" : "modalType = 'view'");

            htmx.process(element);
        });

        // Convert all title attributes into x-tooltip attributes
        Array.from(document.querySelectorAll('[title]')).forEach((element) => {
            if (element.title != undefined && element.title != '') {
                element.setAttribute('x-tooltip', element.title.replaceAll('"', '\''));
                element.title = '';
            }
        });

        $(document).trigger('gibbon-setup');

        // Initialize latex
        $(".latex").latex();
    });

});

const gibbonTinyMCEDefaults = {
    license_key: 'gpl',
    width: '100%',
    resize: true,
    branding: false,
    onboarding: false,
    promotion: false,
    browser_spellcheck: true,
    convert_urls: false,
    relative_urls: false,
    
    valid_elements: Gibbon.config.tinymce.valid_elements,
    extended_valid_elements : Gibbon.config.tinymce.extended_valid_elements,
    invalid_elements: '',

    link_default_target: "_blank",
    link_context_toolbar: true,
    link_quicklink: true,

    image_advtab: true,
    images_upload_url: './modules/User/form_editor_uploadAjaxProcess.php',
    images_upload_credentials: true,
    
    init_instance_callback: (editor) => {
        // Enable validation checking
        editor.on('blur', (e) => {
            tinymce.triggerSave();
            e.target.targetElm.dispatchEvent(new Event('blur'));
        });
    }
};

const gibbonTinyMCEMinimal = {
    menubar : false,
    toolbar: false,
    statusbar: false,
    contextmenu: 'cut copy paste pastetext | searchreplace | link | table styles fontfamily fontsize lineheight | forecolor backcolor | removeformat | code preview',

    plugins: 'autoresize table lists link image media quickbars code preview searchreplace',
    quickbars_selection_toolbar: 'bold italic underline | quicklink | h1 h2 h3 | alignleft aligncenter alignright | bullist numlist |  code',
    quickbars_insert_toolbar: 'quickimage media quicktable blockquote hr',
    quickbars_image_toolbar: 'alignleft aligncenter alignright',

    autoresize_bottom_margin: 0,
};

const gibbonTinyMCEInline = {
    inline: true,
    plugins: 'table lists link image media quickbars',
};

const gibbonTinyMCEFull = {
    statusbar: true,
    menubar : 'file edit view insert format table html',
    contextmenu: 'cut copy paste pastetext | searchreplace | link | table | removeformat | code preview ',
    plugins: 'autosave table lists link image media quickbars wordcount charmap fullscreen code preview searchreplace',
    
    menu: {
        view: { title: 'View', items: 'code wordcount | preview fullscreen' },
        html: { title: 'HTML', items: 'code preview' },
    },

    toolbar_mode: 'floating',
    toolbar_groups: {
        formatting: {
          icon: 'typography',
          items: 'forecolor backcolor | h1 h2 h3 strikethrough blockquote | superscript subscript | removeformat'
        },
        styling: {
            icon: 'paragraph',
            items: 'blocks fontfamily fontsizeinput'
        },
        alignment: {
            icon: 'align-left',
            tooltip: 'Align and indent',
            items: 'alignleft aligncenter alignright alignjustify | indent outdent'
        },
        upload: {
            icon: 'add-file',
            items: 'image media'
        },
    },
    toolbar: 'togglemenubar | bold italic underline formatting | styling link | alignment bullist numlist | upload table | charmap hr | code preview fullscreen',

    quickbars_selection_toolbar: false,
    quickbars_insert_toolbar: false,
    quickbars_image_toolbar: 'alignleft aligncenter alignright',

    apply_source_formatting : true,
    autosave_restore_when_empty: true,
    
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

    setup: function (editor) {
        editor.ui.registry.addButton("togglemenubar", {
          tooltip: "Advanced",
          icon: "settings",
          onAction: function () {
            const menubar = editor.getContainer().querySelector('.tox-menubar');
            if (menubar) {
                menubar.style.display = menubar.style.display == 'flex' ? 'none' : 'flex';
            }
          },
        });
      },

    init_instance_callback: (editor) => {
        // Enable quick save from within tinymce
        editor.addShortcut("meta+s", "Custom Ctrl+S", function (e) {
            editor.formElement.dispatchEvent(new Event('quicksave'));
        });

        // Enable validation checking
        editor.on('blur', (e) => {
            tinymce.triggerSave();
            e.target.targetElm.dispatchEvent(new Event('blur'));
        });

        // Autosave trigger
        if (editor.targetElm.hasAttribute('data-autosave')) {
            setTimeout(function () {
                editor.on('keydown', function () {
                    tinymce.triggerSave();
                    gibbonFormSubmitQuiet(document.getElementById(editor.formElement.id), editor.targetElm.getAttribute('data-autosave'))
                })
            }, 100);
        }
    }
};
