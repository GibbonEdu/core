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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/
(function () {
    function init() {
        var dataElement = document.getElementById('ttDatesPreviewData');
        var form = document.getElementById('ttDates');
        var previewDays = document.getElementById('ttSmartPreviewDays');
        if (!dataElement || !form || !previewDays) return;

        var data;
        try {
            data = JSON.parse(dataElement.textContent);
        } catch (e) {
            return;
        }

        var groups = data.groups || {};
        var modeInputs = form.querySelectorAll('input[name="ttDatesMode"]');
        var rows = {
            custom: document.getElementById('customDayRow'),
            smart: document.getElementById('smartDayRow'),
            preview: document.getElementById('smartPreviewRow'),
        };
        var customSelect = form.querySelector('select[name="gibbonTTDayID[]"]');
        var smartSelect = form.querySelector('select[name="smartDayGroup"]');

        var getMode = function () {
            var checked = form.querySelector('input[name="ttDatesMode"]:checked');
            return checked ? checked.value : 'custom';
        };
        var clearSelections = function () {
            if (customSelect) {
                Array.prototype.forEach.call(customSelect.options, function (option) {
                    option.selected = false;
                });
            }
            if (smartSelect) smartSelect.value = '';
        };
        var renderPreview = function () {
            previewDays.innerHTML = '';

            var days = getMode() === 'smart' && smartSelect ? groups[smartSelect.value] || [] : [];
            if (days.length === 0) {
                rows.preview.classList.add('hidden');
                return;
            }

            days.forEach(function (label) {
                var chip = document.createElement('span');
                chip.className = 'inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-800';
                chip.textContent = label;
                previewDays.appendChild(chip);
            });
            rows.preview.classList.remove('hidden');
        };
        var update = function () {
            var mode = getMode();
            Object.keys(rows).forEach(function (key) {
                if (!rows[key] || key === 'preview') return;
                rows[key].classList.toggle('hidden', key !== mode);
            });

            if (customSelect) customSelect.disabled = mode !== 'custom';
            if (smartSelect) smartSelect.disabled = mode !== 'smart';

            renderPreview();
        };

        Array.prototype.forEach.call(modeInputs, function (input) {
            input.addEventListener('change', function () {
                clearSelections();
                update();
            });
        });
        form.addEventListener('change', update);
        update();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
