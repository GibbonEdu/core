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
