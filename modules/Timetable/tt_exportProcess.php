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

use Gibbon\Domain\System\NotificationGateway;

include '../../gibbon.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable/tt.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    // Calendar Export Process
    date_default_timezone_set($session->get('timezone'));
    $vCalendar = new \Eluceo\iCal\Component\Calendar('Calendar Export');

    $data0 = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
    $sql0 = 'SELECT * FROM gibbonTTDayDate JOIN gibbonTTDay on gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID WHERE date >= (SELECT firstDay FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID) AND date <= (SELECT lastDay FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID)';
    $result0 = $connection2->prepare($sql0);
    $result0->execute($data0);
    if ($result0->rowCount() < 1) {
    } else {
        while ($values0 = $result0->fetch()) {
            $data1 = array( 'gibbonTTDayID' => $values0['gibbonTTDayID'], 'gibbonPersonID' => $_POST['superSecretHiddenValue']);
            $sql1 = 'SELECT timeStart, timeEnd, gibbonCourse.name FROM gibbonTTDayRowClass
                      JOIN gibbonTTColumnRow on gibbonTTDayRowClass.gibbonTTColumnRowID = gibbonTTColumnRow.gibbonTTColumnRowID
                      JOIN gibbonCourseClassPerson on gibbonTTDayRowClass.gibbonCourseClassID = gibbonCourseClassPerson.gibbonCourseClassID
                      JOIN gibbonCourseClass on gibbonCourseClass.gibbonCourseClassID = gibbonCourseClassPerson.gibbonCourseClassID
                      JOIN gibbonCourse on gibbonCourseClass.gibbonCourseID = gibbonCourse.gibbonCourseID
                      WHERE gibbonTTDayID=:gibbonTTDayID
                      AND gibbonPersonID=:gibbonPersonID';
            $result1 = $connection2->prepare($sql1);
            $result1->execute($data1);
            if ($result1->rowCount() < 1) {
            } else {
                while ($values1 = $result1->fetch()) {
                  $vEvent = new \Eluceo\iCal\Component\Event();
                  if ((int)$_POST['options']!=(int)('options')) {
                    // Alarm Creation
                    $vAlarm = new \Eluceo\iCal\Component\Alarm();
                    $vAlarm->setAction('DISPLAY');
                    $vAlarm->setTrigger('-PT'.(int)$_POST['options'].'M'); // Set alert time
                    $vAlarm->setDescription('Event description');
                    $vEvent->addComponent($vAlarm);
                  } else {}
                    // Event Creation
                  $vEvent->setDtStart(new \DateTime($values0['date'].' '.$values1['timeStart']));
                  $vEvent->setDtEnd(new \DateTime($values0['date'].' '.$values1['timeEnd']));
                      if ( $_POST['prefix'] != '') {
                        $vEvent->setSummary($_POST['prefix'] . ' | ' . $values1['name']);
                      } else {
                        $vEvent->setSummary($values1['name']);
                      }
                  $vEvent->setUseTimezone(true);
                  $vCalendar->addComponent($vEvent);
                }
            }
        }
        // Notification settings
        $gateway = new NotificationGateway($pdo);
        $result = $gateway->selectNotificationEventByName('Timetable', 'Updated Timetable Subscriber')->fetch();
            $gibbonNotificationEventID = $result['gibbonNotificationEventID'];
            $gibbonPersonID = $session->get('gibbonPersonID');
            $listeners = $gateway->selectAllNotificationListeners($result['gibbonNotificationEventID'])->fetchAll();
            $scopeType = 'All';
            $scopeID = 0;
            $listener = array(
                'gibbonNotificationEventID' => $result['gibbonNotificationEventID'],
                'gibbonPersonID'            => $gibbonPersonID,
                'scopeType'                 => $scopeType,
                'scopeID'                   => $scopeID
            );
        if (!in_array($gibbonPersonID, array_column($listeners, 'gibbonPersonID'))){
          $result = $gateway->insertNotificationListener($listener);
        }

        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="cal.ics"');
        echo $vCalendar->render();
    }
  }
