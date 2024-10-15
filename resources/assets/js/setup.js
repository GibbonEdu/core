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

// Initialize an Alpine.js Tooltip (on non-mobile devices)
document.addEventListener('alpine:init', () => {
    if (window.innerWidth < 768) return;

    var currentTooltip;

    Alpine.directive('tooltip', (el, { modifiers, expression }, { cleanup }) => {
        var tooltipActive = false;
        let tooltipText = expression;
        let tooltipArrow = modifiers.includes('noarrow') ? false : true;
        let tooltipPosition = 'top';
        let tooltipId = 'tooltip-' + Date.now().toString(36) + Math.random().toString(36).substring(2, 7);
        let positions = ['top', 'bottom', 'left', 'right'];
        let elementPosition = getComputedStyle(el).position;

        for (let position of positions) {
            if (modifiers.includes(position)) {
                tooltipPosition = position;
                break;
            }
        }

        if(!['relative', 'absolute', 'fixed'].includes(elementPosition)){
            el.style.position='relative';
        }
        
        let tooltipHTML = `
            <div id="${tooltipId}" x-cloak x-data="{ tooltipVisible: false, tooltipText: '${tooltipText}', tooltipArrow: ${tooltipArrow}, tooltipPosition: '${tooltipPosition}' }" x-ref="tooltip" x-init="setTimeout(function(){ tooltipVisible = true; }, 1);" x-show="tooltipVisible" :class="{ 'top-0 left-1/2 -translate-x-1/2 -mt-1.5 -translate-y-full' : tooltipPosition == 'top', 'top-1/2 -translate-y-1/2 -ml-1.5 left-0 -translate-x-full' : tooltipPosition == 'left', 'bottom-0 left-1/2 -translate-x-1/2 -mb-0.5 translate-y-full' : tooltipPosition == 'bottom', 'top-1/2 -translate-y-1/2 -mr-1.5 right-0 translate-x-full' : tooltipPosition == 'right' }" class="absolute pointer-events-none w-auto text-sm font-normal" style="z-index: 100;" >
                <div x-show="tooltipVisible" class="relative px-2 py-1 text-white bg-black bg-opacity-80 backdrop-blur-lg backdrop-contrast-125 backdrop-saturate-150 rounded-md"
                    x-transition:enter="transition delay-75 ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-50"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-50" >
                    <p class="flex-shrink-0 block m-0 p-1 text-xs whitespace-nowrap" >${tooltipText}</p>
                    <div x-ref="tooltipArrow" x-show="tooltipArrow" :class="{ 'bottom-0 -translate-x-1/2 left-1/2 w-2.5 translate-y-full' : tooltipPosition == 'top', 'right-0 -translate-y-1/2 top-1/2 h-2.5 -mt-px translate-x-full' : tooltipPosition == 'left', 'top-0 -translate-x-1/2 left-1/2 w-2.5 -translate-y-full' : tooltipPosition == 'bottom', 'left-0 -translate-y-1/2 top-1/2 h-2.5 -mt-px -translate-x-full' : tooltipPosition == 'right' }" class="absolute inline-flex items-center justify-center overflow-hidden">
                        <div :class="{ 'origin-top-left -rotate-45' : tooltipPosition == 'top', 'origin-top-left rotate-45' : tooltipPosition == 'left', 'origin-bottom-left rotate-45' : tooltipPosition == 'bottom', 'origin-top-right -rotate-45' : tooltipPosition == 'right' }" class="w-1.5 h-1.5 transform bg-black bg-opacity-80"></div>
                    </div>
                </div>
            </div>
        `;
        
        el.dataset.tooltip = tooltipId;

        let mouseEnter = function(event){ 
            if (currentTooltip != null) {
                currentTooltip.dispatchEvent(new Event('mouseleave'));
            }
            if (!tooltipActive) {
                el.insertAdjacentHTML('beforeend', tooltipHTML);
                tooltipActive = true;
                currentTooltip = el;
            }
        };

        let mouseLeave = function(event){
            var tooltip = document.getElementById(event.target.dataset.tooltip);
            if (tooltip) tooltip.remove();

            tooltipActive = false;
            currentTooltip = null;
        };
        
        el.addEventListener('mouseenter', mouseEnter);
        el.addEventListener('mouseleave', mouseLeave);
        document.addEventListener('htmx:beforeRequest', mouseLeave);

        cleanup(() => {
            el.removeEventListener('mouseenter', mouseEnter);
            el.removeEventListener('mouseleave', mouseLeave);
        })
    });
    
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

    // Convert all title attributes into x-tooltip attributes
    Array.from(document.querySelectorAll('[title]')).forEach((element) => {
        if (element.title != undefined && element.title != '') {
            element.setAttribute('x-tooltip', element.title);
            element.title = '';
        }
    });

    $(document).trigger('gibbon-setup');

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
