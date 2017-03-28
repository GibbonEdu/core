<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

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

use Gibbon\Comms\NotificationEvent;

require getcwd().'/../config.php';
require getcwd().'/../functions.php';

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start();

getSystemSettings($guid, $connection2);

setCurrentSchoolYear($guid, $connection2);

//Set up for i18n via gettext
if (isset($_SESSION[$guid]['i18n']['code'])) {
    if ($_SESSION[$guid]['i18n']['code'] != null) {
        putenv('LC_ALL='.$_SESSION[$guid]['i18n']['code']);
        setlocale(LC_ALL, $_SESSION[$guid]['i18n']['code']);
        bindtextdomain('gibbon', getcwd().'/../i18n');
        textdomain('gibbon');
    }
}

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]['timezone']);

//Check for CLI, so this cannot be run through browser
if (php_sapi_name() != 'cli') { echo __($guid, 'This script cannot be run from a browser, only via CLI.');
} else {
    $currentDate = date('Y-m-d');

    if (isSchoolOpen($guid, $currentDate, $connection2, true)) {
        $report = '';
        $reportInner = '';

        $partialFail = false;

        try {
            $data = array('date' => $currentDate, 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);

            $sql = "SELECT gibbonRollGroup.nameShort AS rollGroup, gibbonStudentEnrolment.gibbonYearGroupID, gibbonBehaviour.gibbonBehaviourID, gibbonPerson.gibbonPersonID, gibbonPerson.surname, gibbonPerson.preferredName, staff.surname as staffSurname, staff.preferredName as staffPreferredName, gibbonBehaviour.descriptor, gibbonBehaviour.level, gibbonBehaviour.comment, gibbonBehaviour.followup, gibbonBehaviour.timestamp
                    FROM gibbonBehaviour
                    JOIN gibbonPerson ON (gibbonBehaviour.gibbonPersonID=gibbonPerson.gibbonPersonID)
                    JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                    JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
                    JOIN gibbonPerson as staff ON (gibbonBehaviour.gibbonPersonIDCreator=staff.gibbonPersonID)
                    WHERE gibbonBehaviour.gibbonSchoolYearID=:gibbonSchoolYearID
                    AND gibbonBehaviour.type='Negative'
                    AND gibbonBehaviour.date=:date
                    AND gibbonPerson.status='Full'
                    AND gibbonRollGroup.gibbonSchoolYearID=gibbonBehaviour.gibbonSchoolYearID
                    GROUP BY gibbonBehaviour.gibbonBehaviourID
                    ORDER BY gibbonBehaviour.timestamp";

            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $partialFail = true;
        }

        if ($result && $result->rowCount() > 0) {
            $report .= '<h2>'.__('Daily Behaviour Summary').'</h2>';
            while ($row = $result->fetch()) {

                $studentName = formatName('', $row['preferredName'], $row['surname'], 'Student', false);
                $staffName = formatName('', $row['staffPreferredName'], $row['staffSurname'], 'Staff', false, true);
                $comment = (mb_strlen($row['comment']) > 240)? mb_substr($row['comment'], 0, 240).'...' : $row['comment'];

                $report .= date('g:i a', strtotime($row['timestamp'])).' - '.__('Negative').' '.__('Behaviour').' - '.$row['level'];
                $report .= '<br/>';

                $report .= sprintf(__('%1$s (%2$s) received a report for %3$s from %4$s'), '<b>'.$studentName.'</b>', $row['rollGroup'], $row['descriptor'], $staffName);
                $report .= ' &raquo; <a href="'.$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Behaviour/behaviour_manage_edit.php&gibbonBehaviourID='.$row['gibbonBehaviourID'].'&gibbonPersonID='.$row['gibbonPersonID'].'&gibbonRollGroupID=&gibbonYearGroupID=&type=">'.__('View').'</a>';
                $report .= '<br/>';

                $report .= '<p style="margin-left: 32px;">';
                $report .= '<u>'.__('Incident').'</u>: '.$comment.'<br/>';

                if (!empty($row['followup'])) {
                    $followup = (mb_strlen($row['followup']) > 240)? mb_substr($row['followup'], 0, 240).'...' : $row['followup'];
                    $report .= '<u>'.__('Follow Up').'</u>: '.$followup.'<br/>';
                }
                $report .= '</p><br/>';
            }

            // Raise a new notification event
            $event = new NotificationEvent('Behaviour', 'Daily Behaviour Summary');

            $event->setNotificationText(__($guid, 'A Behaviour CLI script has run.').'<br/><br/>'.$report);
            $event->setActionLink('/index.php?q=/modules/Behaviour/behaviour_pattern.php&minimumCount=1&fromDate='.dateConvertBack($guid, $currentDate));

            $event->addRecipient($_SESSION[$guid]['organisationAdministrator']);

            // Send all notifications
            $sendReport = $event->sendNotifications($pdo, $gibbon->session);

            // Output the result to terminal
            echo sprintf('Sent %1$s notifications: %2$s inserts, %3$s updates, %4$s emails sent, %5$s emails failed.', $sendReport['count'], $sendReport['inserts'], $sendReport['updates'], $sendReport['emailSent'], $sendReport['emailFailed'])."\n";

        } else {
            // Output the result to terminal
            echo __('There are no records to display.')."\n";
        }
    }
}
