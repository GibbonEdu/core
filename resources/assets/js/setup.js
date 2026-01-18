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
    });

});

/**
 * Clear templated nodes from the document before saving it in history. This
 * prevents Alpine from creating duplicate objects when the history is loaded.
 */
document.addEventListener('htmx:beforeHistorySave', (event) => {
    document.querySelectorAll('[x-from-template]').forEach((e) => e.remove());
    document.querySelectorAll('.tox-tinymce').forEach((e) => e.remove());
    document.querySelectorAll('ul.token-input-list-facebook').forEach((e) => e.remove());
});


/**
 * TinyMCE
 *
 * Create pre-configured objects for different Editor modes, to be loaded 
 * as needed in the Editor.template.php file. Hook into settings using 
 * Gibbon.config.tinymce from index.php.
 */
const gibbonTinyMCEFileUpload = {
    title: Gibbon.config.tinymce.insert_file,
    body: {
        type: "panel",
        items: [
            {
                type: "urlinput",
                name: "file_upload",
                filetype: "file",
                label: Gibbon.config.tinymce.select_file_label,
                picker_text: Gibbon.config.tinymce.select_file,
            },
            {
                type: "htmlpanel",
                html: `<span style="font-size: 0.75rem; color: #888; display: inline-block; margin-top: 1rem;">${Gibbon.config.tinymce.file_types_label}: ${Gibbon.config.tinymce.file_types}</span>`,
            },
        ],
    },
    buttons: [
        {
            type: "cancel",
            name: "closeButton",
            text: Gibbon.config.tinymce.cancel,
        },
        {
            type: "submit",
            name: "submitButton",
            text: Gibbon.config.tinymce.save,
            buttonType: "primary",
        },
    ],
    onSubmit: (api) => {
        const data = api.getData();

        if (data.file_upload.value == '') {
            tinymce.activeEditor.notificationManager.open({ text: Gibbon.config.tinymce.invalid_file, type: 'warning' });
            api.close();
            return;
        }

        const notification = tinymce.activeEditor.notificationManager.open({ text: Gibbon.config.tinymce.uploading, progressBar: true });
        tinymce.activeEditor.setProgressState(true);

        const success = function (location, title) {
            tinymce.activeEditor.setProgressState(false);
            tinymce.activeEditor.notificationManager.close();
            tinymce.activeEditor.execCommand("mceInsertContent", false, `<p><a href="${location}" data-fileupload="${title ?? location}">${title ?? location}</a></p>`);

            api.close();
        };

        const failure = function (message) {
            tinymce.activeEditor.setProgressState(false);
            tinymce.activeEditor.notificationManager.close();
            tinymce.activeEditor.notificationManager.open({ text: message, type: 'error' });
            api.close();
        };

        var xhr, formData;
        xhr = new XMLHttpRequest();
        xhr.withCredentials = true;

        // If a value is provided, check that it exists
        if (!data.file_upload.meta.title) {
            success(data.file_upload.value);
            return;
        }
        
        xhr.open("POST", "./modules/User/form_editor_uploadAjaxProcess.php");

        xhr.upload.onprogress = function (e) {
            notification.progressBar.value((e.loaded / e.total) * 100);
        };

        xhr.onload = function () {
            var json;

            if (xhr.status === 403) {
                failure(Gibbon.config.tinymce.error + " " + xhr.status + ": " + xhr.statusText);
                return;
            }

            if (xhr.status < 200 || xhr.status >= 300) {
                failure(Gibbon.config.tinymce.error + " " + xhr.status + ": " + xhr.statusText);
                return;
            }

            json = JSON.parse(xhr.responseText);

            if (!json || typeof json.location != "string") {
                failure(Gibbon.config.tinymce.error + " " + xhr.responseText);
                return;
            }

            success(json.location, data.file_upload.meta.title);
        };

        xhr.onerror = function () {
            failure(Gibbon.config.tinymce.error + " " + xhr.status + ": " + xhr.statusText);
        };

        try {
            const blobCache =  tinymce.activeEditor.editorUpload.blobCache;
            const blobInfo = blobCache.get(data.file_upload.meta.id);

            formData = new FormData();
            formData.append("file", blobInfo.blob(), data.file_upload.meta.title );

            xhr.send(formData);
        } catch (e) {
            failure(Gibbon.config.tinymce.invalid_file);
        }
    },
};

const gibbonTinyMCEDefaults = {
    language: Gibbon.config.tinymce.locale,
    directionality: Gibbon.config.tinymce.locale_rtl,
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
    // image_class_list: [
    //     { title: 'None', value: '' },
    //     { title: 'No border', value: 'img_no_border' },
    //     { title: 'Green border', value: 'img_green_border' },
    //     { title: 'Blue border', value: 'img_blue_border' },
    //     { title: 'Red border', value: 'img_red_border' }
    //   ],

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
    contextmenu: false,  //'cut copy paste pastetext | searchreplace | link | table | removeformat | code preview ',
    plugins: 'autosave table lists link image media quickbars wordcount charmap fullscreen code preview searchreplace',
    
    menu: {
        view: { title: Gibbon.config.tinymce.view, items: 'code wordcount | preview fullscreen' },
        html: { title: Gibbon.config.tinymce.html, items: 'code preview' },
    },

    toolbar_mode: 'floating',
    toolbar_groups: {
        formatting: {
          icon: 'typography',
          items: 'forecolor backcolor | h1 h2 h3 strikethrough blockquote | superscript subscript | removeformat'
        },
        styling: {
            icon: 'paragraph',
            items: 'blocks fontfamily fontsizeinput lineheight'
        },
        alignment: {
            icon: 'align-left',
            items: 'alignleft aligncenter alignright alignjustify | indent outdent'
        },
        upload: {
            icon: 'add-file',
            items: 'image media fileupload'
        },
    },
    toolbar: 'togglemenubar | bold italic underline formatting | styling link | alignment bullist numlist | upload table | charmap hr | code preview fullscreen',

    quickbars_selection_toolbar: false,
    quickbars_insert_toolbar: false,
    quickbars_image_toolbar: 'image | alignleft aligncenter alignright | imagedownload imagedelete',

    apply_source_formatting : true,
    autosave_restore_when_empty: true,

    file_picker_types: 'file image',

    /* and here's our custom image picker*/
    file_picker_callback: (cb, value, meta) => {
        const input = document.createElement('input');
        input.setAttribute('type', 'file');

        if (meta.filetype == 'image') {
            input.setAttribute('accept', 'image/*');
        }

        input.addEventListener('change', (e) => {
            const file = e.target.files[0];

            const reader = new FileReader();
            reader.addEventListener('load', () => {
                const id = 'blobid' + (new Date()).getTime();
                const blobCache =  tinymce.activeEditor.editorUpload.blobCache;
                const base64 = reader.result.split(',')[1];
                const blobInfo = blobCache.create(id, file, base64);
                blobCache.add(blobInfo);

                /* call the callback and populate the Title field with the file name */
                cb(blobInfo.blobUri(), { title: file.name, id: id });
            });
            reader.readAsDataURL(file);
        });

        input.click();
    },
    
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
          tooltip: Gibbon.config.tinymce.advanced_options,
          icon: "settings",
          onAction: function () {
            const menubar = editor.getContainer().querySelector('.tox-menubar');
            if (menubar) {
                menubar.style.display = menubar.style.display == 'flex' ? 'none' : 'flex';
            }
          },
        });

        editor.ui.registry.addButton('fileupload', {
            icon: 'new-document',
            tooltip: Gibbon.config.tinymce.insert_file,
            onAction: () => editor.windowManager.open(gibbonTinyMCEFileUpload)
        });

        editor.ui.registry.addButton('imagedownload', {
            icon: 'save',
            tooltip: Gibbon.config.tinymce.download,
            onAction: function() {
                const node = editor.selection.getNode();
                downloadLink = document.createElement('a');
                downloadLink.href = node.src;
                downloadLink.setAttribute('download', '');
                downloadLink.target = 'downloadIframe';
                downloadLink.click();
            }
        });

        editor.ui.registry.addButton('imagedelete', {
            icon: 'remove',
            tooltip: Gibbon.config.tinymce.delete,
            onAction: function() {
                const node = editor.selection.getNode();
                editor.dom.remove(node);
            }
        });

        const isFileLinkElement = (node) => {
            return node.nodeName.toLowerCase() === 'a' && node.href && node.dataset.fileupload;
          }
      
          const getFileLinkElement = () => {
            const node = editor.selection.getNode();
            return isFileLinkElement(node) ? node : null;
          };
      
          editor.ui.registry.addContextForm("fileedit", {
              launch: {
                  type: "contextformbutton",
                  icon: "new-document",
              },
              label: Gibbon.config.tinymce.file,
              predicate: isFileLinkElement,
              initValue: () => {
                  const elm = getFileLinkElement();
                  return !!elm ? elm.dataset.fileupload : "";
              },
              commands: [
                  {
                      type: "contextformbutton",
                      icon: "new-tab",
                      tooltip: Gibbon.config.tinymce.open,
                      onAction: (formApi) => {
                          const elm = getFileLinkElement();
                          window.open(elm.href, "_blank");
                          formApi.hide();
                      },
                  },
                  {
                      type: "contextformbutton",
                      icon: "save",
                      tooltip: Gibbon.config.tinymce.download,
                      onAction: (formApi) => {
                            const elm = getFileLinkElement();

                            downloadLink = document.createElement('a');
                            downloadLink.href = elm.href;
                            downloadLink.setAttribute('download', '');
                            downloadLink.target = 'downloadIframe';
                            downloadLink.click();

                            formApi.hide();
                      },
                  },
                  {
                      type: "contextformbutton",
                      icon: "remove",
                      tooltip: Gibbon.config.tinymce.delete,
                      onAction: (formApi) => {
                        editor.windowManager.confirm(Gibbon.config.tinymce.delete_confirm, (state) => {
                            if (!state) return;
                            const elm = getFileLinkElement();
                            editor.dom.remove(elm);
                          });
                          
                          formApi.hide();
                      },
                  },
              ],
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
