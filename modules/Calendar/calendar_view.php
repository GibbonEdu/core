<?php
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

use Gibbon\Support\Facades\Access;

if (isActionAccessible($guid, $connection2, '/modules/Calendar/calendar_view.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('View Calendar'));

    echo '<div id="calendar"></div>';
    
    $canViewEvents = Access::allows('Calendar', 'calendar_event_view');
    $canAddEvents = Access::allows('Calendar', 'calendar_event_add');

    // Set the first day of the week based on system settings
    $firstDayOfTheWeek = $session->get('firstDayOfTheWeek', 'Sunday');
    $firstDay = $firstDayOfTheWeek == 'Monday' ? 1 : ($firstDayOfTheWeek == 'Saturday' ? 6 : 0);

    // Set the ltr or rtl text direction
    $i18n = $session->get('i18n');
    $direction = $i18n['rtl'] == 'Y' ? 'rtl' : 'ltr';

    // See if a locale file exists for the users locale and load it if possible
    $localeFull = strtolower(str_replace('_', '-', $i18n['code'] ?? 'en'));
    $localeShort = substr($localeFull, 0, 2);
    
    $localeFileFull = "/lib/fullcalendar/packages/locales/{$localeFull}.global.min.js";
    $localeFileShort = "/lib/fullcalendar/packages/locales/{$localeShort}.global.min.js";

    // First see if a full locale exists, otherwise fallback to two-char locale
    if (file_exists($session->get('absolutePath').$localeFileFull)) {
        $localeFile = $session->get('absoluteURL').$localeFileFull;
        $locale = $localeFull;
    } elseif (file_exists($session->get('absolutePath').$localeFileShort)) {
        $localeFile = $session->get('absoluteURL').$localeFileShort;
        $locale = $localeShort;
    } else {
        $localeFile = '';
        $locale = 'en';
    }
}
?>

<script src="<?= $session->get('absoluteURL'); ?>/lib/fullcalendar/dist/index.global.min.js" type="text/javascript"></script>
<?= !empty($localeFile) ? '<script src="'.$localeFile.'" type="text/javascript"></script>' : '' ?>

<script type="text/javascript">

    function setupCalendar() {
        if (!(typeof FullCalendar === 'object')) window.location.reload();
        
        var calendarEl = document.getElementById('calendar');
        var calendar = new FullCalendar.Calendar(calendarEl, {
            timeZone: 'UTC',
            editable: false,
            selectable: true,
            businessHours: true,
            dayMaxEvents: true, 

            firstDay: <?= $firstDay ?>,
            direction: '<?= $direction ?>',
            locale: '<?= $locale ?>',

            headerToolbar: window.innerWidth < 765 ? {
                start: 'title',
                center: window.innerWidth > 500 ? 'dayGridMonth,listMonth' : '',
                end: 'prev,next today',
            } : {
                start: 'prev,next today',
                center: 'title',
                end: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
            },
            eventSources: [
                {
                    url: '<?= $session->get('absoluteURL') ?>/modules/Calendar/calendar_viewInternal.php',
                },
            ],

            initialView: window.innerWidth < 765 ? 'listMonth' : 'dayGridMonth',
            navLinks: true, 
            selectable: <?= $canAddEvents ? 'true' : 'false' ?>,
            selectMirror: true,

            <?php if ($canAddEvents) { ?>
            select: function(info) {
                htmx.ajax('GET', '<?= $session->get('absoluteURL'); ?>/fullscreen.php?q=/modules/Calendar/calendar_event_add.php', {
                    target: '#modalContent',
                    values: {
                        source: 'ajax',
                        start: info.start.toISOString(),
                        end: info.end.toISOString(),
                        allDay: info.allDay
                    }
                }).then(() => {
                    document.body.dispatchEvent(new CustomEvent('modalwindow', { bubbles: true, detail: 'delete'}));
                });

                calendar.unselect()
            },
            <?php } ?>

            <?php if ($canViewEvents) { ?>
            eventClick: function(info) {
                htmx.ajax('GET', '<?= $session->get('absoluteURL'); ?>/fullscreen.php?q=/modules/Calendar/calendar_event_view.php', {
                    target: '#modalContent',
                    values: {
                        source: 'ajax',
                        gibbonCalendarEventID: info.event.id,
                    }
                }).then(() => {
                    document.body.dispatchEvent(new CustomEvent('modalwindow', { bubbles: true, detail: 'full'}));
                });
            },
            <?php } ?>

            eventDidMount: function(info) {
                var tooltipContent = `
                <div class='w-80 flex flex-col py-2 gap-1 overflow-hidden'>
                    <div class='px-2 pb-1'>
                        <div class='flex justify-between leading-normal'>
                            <span class='font-semibold text-sm'>${info.event.title}</span>
                            <span class='tag ml-2 text-xxs h-5 border-0 outline outline-1 ' style='${info.event.extendedProps.palette.style} ${info.event.extendedProps.palette.textStyle}'>${info.event.extendedProps.type}</span>
                        </div>
                        <div class='font-normal mt-1'>${info.event.extendedProps.description}</div>
                    </div>
                    
                    <div class='px-2 pt-2 border-t flex justify-between leading-relaxed'>
                        <div><?= icon('outline', 'clock', 'size-4 text-gray-600 inline align-middle mr-1', ['stroke-width' => 2.4]) ?> 
                        ${info.event.extendedProps.timeRange}
                        </div>
                    </div>

                    <div class='px-2 flex justify-between leading-relaxed'>
                        <div><?= icon('solid', 'calendar', 'size-4 text-gray-600 inline align-middle mr-1', ['stroke-width' => 2.4]) ?> 
                        ${info.event.extendedProps.calendar}
                        </div>
                    </div>
                `;

                if (info.event.extendedProps.locationType == 'Internal' && info.event.extendedProps.location) {
                tooltipContent += `
                    <div class='px-2 flex justify-between leading-relaxed'>
                        <div>
                            <?= icon('solid', 'map-pin', 'size-4 text-gray-600 inline align-middle mr-1') ?> ${info.event.extendedProps.location} 
                        </div>
                        <div><?= icon('solid', 'phone', 'size-4 text-gray-600 inline align-middle mr-1') ?> ${info.event.extendedProps.phone}</div>
                    </div>
                `;
                } else if (info.event.extendedProps.location) {
                    tooltipContent += `
                    <div class='px-2 flex justify-between leading-relaxed'>
                        <div>
                            <?= icon('solid', 'map-pin', 'size-4 text-gray-600 inline align-middle mr-1') ?> ${info.event.extendedProps.location} 
                        </div>
                    </div>
                `;
                }

                tooltipContent += `
                </div>
                `;

                info.el.setAttribute('x-tooltip.white', tooltipContent);
            },
                    
        });

        calendar.render();
    }

    setupCalendar();

    // Force a reload after htmx swaps to the same page
    document.addEventListener('htmx:beforeOnLoad', function (event) {
        if (event.detail.pathInfo.finalRequestPath.includes('calendar_view')) {
            window.location.reload();
            event.preventDefault();
        }
    }, { once: true });

</script>
