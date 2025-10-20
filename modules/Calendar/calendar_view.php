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

use Gibbon\Domain\Calendar\CalendarEventGateway;

if (isActionAccessible($guid, $connection2, '/modules/Calendar/calendar_view.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('View Calendar'));

    echo '<div id="calendarDetails"></div>';
    echo '<div id="calendar"></div>';

    $eventGateway = $container->get(CalendarEventGateway::class);
    $events = $eventGateway->selectCalendarEvents()->fetchAll();
}

?>
<script src="./lib/fullcalendar/dist/index.global.min.js" type="text/javascript"></script>
<script type="text/javascript">
    var calendar;

    function setupCalendar() {
        //     htmx.onLoad(function (content) {

        var calendarEl = document.getElementById('calendar');

        calendar = new FullCalendar.Calendar(calendarEl, {
            timeZone: 'UTC',
            editable: true,
            selectable: true,
            businessHours: true,
            dayMaxEvents: true, // allow "more" link when too many events
            headerToolbar: window.innerWidth < 765 ? {
                start: 'title',
                center: window.innerWidth > 500 ? 'dayGridMonth,listMonth' : '',
                end: 'prev,next today',
            } : {
                start: 'prev,next today',
                center: 'title',
                end: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
            },
            // plugins: [dayGridPlugin, timeGridPlugin, listPlugin, googleCalendar],
            // height:  window.innerWidth < 765 ? 'auto' : ,
            initialView: window.innerWidth < 765 ? 'listMonth' : 'dayGridMonth',

            navLinks: true, // can click day/week names to navigate views
            selectable: true,
            selectMirror: true,
            select: function(arg) {
                var title = prompt('Event Title:');
                if (title) {

                    htmx.ajax('POST', '<?= $session->get('absoluteURL'); ?>/modules/Calendar/calendar_event_addEditProcess.php', {
                        target: '#calendarDetails',
                        values: {
                            title: title,
                            start: arg.start.toISOString(),
                            end: arg.end.toISOString(),
                            allDay: arg.allDay
                        }
                    }).then(() => {
                        console.log('Added');

                        calendar.addEvent({
                            title: title,
                            start: arg.start,
                            end: arg.end,
                            allDay: arg.allDay
                        })
                    });
                }
                calendar.unselect()
            },
            eventChange: function(changeInfo) {
                console.log('changed');
                console.log(changeInfo);

                htmx.ajax('POST', '<?= $session->get('absoluteURL'); ?>/modules/Calendar/calendar_event_addEditProcess.php', {
                    target: '#calendarDetails',
                    values: {
                        gibbonCalendarEventID: changeInfo.event.id,
                        start: changeInfo.event.start.toISOString(),
                        end: changeInfo.event.end.toISOString(),
                        allDay: changeInfo.event.allDay
                    }
                }).then(() => {
                    console.log('Changed!');

                    // calendar.addEvent({
                    //     start: arg.start,
                    //     end: arg.end,
                    //     allDay: arg.allDay
                    // })
                });
            },
            eventClick: function(arg) {
                if (confirm('Are you sure you want to delete this event?')) {
                    arg.event.remove()
                }
            },

            // eventRender: function(event, element) {
            //     console.log(event.name);
            // },
                        

            events: <?= json_encode($events); ?>
        });

        calendar.render();

        // });
    }

    setupCalendar();

    // Ensure the scripts load in the correct order
    // (async () => {
    //     await import("./lib/fullcalendar/dist/index.global.min.js")
    //     await setupCalendar();
    // })();

    // Remove the existing editor before htmx swaps to a new page
    // document.addEventListener('htmx:beforeRequest', function (event) {
    //     calendar.destroy();
    // }, { once: true });
</script>
