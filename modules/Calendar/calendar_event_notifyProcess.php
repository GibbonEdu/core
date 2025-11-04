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

use Gibbon\View\View;
use Gibbon\Data\Validator;
use Gibbon\Services\Format;
use Gibbon\Contracts\Comms\Mailer;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\Staff\StaffGateway;
use Gibbon\Domain\School\YearGroupGateway;
use Gibbon\Domain\FormGroups\FormGroupGateway;
use Gibbon\Domain\Calendar\CalendarEventGateway;
use Gibbon\Domain\Timetable\CourseEnrolmentGateway;
use Gibbon\Domain\Calendar\CalendarEventPersonGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Calendar/calendar_event_notify.php';
$gibbonCalendarEventID = $_POST['gibbonCalendarEventID'] ?? '';

if (isActionAccessible($guid, $connection2, '/modules/Calendar/calendar_event_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $calendarEventGateway = $container->get(CalendarEventGateway::class);
    $calendarEventPersonGateway = $container->get(CalendarEventPersonGateway::class);

    $notes = $_POST['notes'] ?? '';
    $notifyGroups = $_POST['notifyGroups'] ?? [];
    $allStaff = $_POST['allStaff'] ?? 'N';
    $notificationList = isset($_POST['notificationList']) ? explode(',', $_POST['notificationList']) : [];
    $staff = [];

    // Get event details
    $event = $calendarEventGateway->getByID($gibbonCalendarEventID);
    if (!empty($gibbonCalendarEventID) && empty($event)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    // Get all student participants
    $criteria = $calendarEventPersonGateway->newQueryCriteria()
        ->sortBy(['surname', 'preferredName', 'category'])
        ->fromPOST();
    $students = $calendarEventPersonGateway->queryEnrolledAttendees($criteria, $gibbonCalendarEventID);

    if (empty($students)) {
        $page->addError(__('The specified record does not have any enrolled attendees.'));
        return;
    }

    if ($allStaff == 'Y') {
         // All Staff
        $staffGateway = $container->get(StaffGateway::class);
        $criteria = $staffGateway->newQueryCriteria();

        $results = $staffGateway->queryAllStaff($criteria);
        foreach ($results as $result) {
            $staff[] = $result['gibbonPersonID'];
        }
    } elseif (!empty($notifyGroups)) {
        foreach ($students as $student) {
            // Head of Year
            if (in_array('HOY', $notifyGroups)) {
                $yearGroup = $container->get(YearGroupGateway::class)->getByID($student['gibbonYearGroupID']);
                if (!empty($yearGroup['gibbonPersonIDHOY'])) {
                    $staff[] = $yearGroup['gibbonPersonIDHOY'];
                }
            }

            // Form Tutors
            if (in_array('tutors', $notifyGroups)) {
                $formGroup = $container->get(FormGroupGateway::class)->getByID($student['gibbonFormGroupID']);
                $staff[] = $formGroup['gibbonPersonIDTutor'];
                $staff[] = $formGroup['gibbonPersonIDTutor2'];
                $staff[] = $formGroup['gibbonPersonIDTutor3'];
            }

            // Class Teachers
            if (in_array('teachers', $notifyGroups)) {
                $teachers = $container->get(CourseEnrolmentGateway::class)->selectClassTeachersByStudent($session->get('gibbonSchoolYearID'), $student['gibbonPersonID']);
                foreach ($teachers as $teacher) {
                    $staff[] = $teacher['gibbonPersonID'];
                }
            }
        }

        if (!empty($notificationList)) {
            foreach ($notificationList as $gibbonPersonIDNotify) {
                // Add the staff
                $staff[] = $gibbonPersonIDNotify;
            }
        }
    }

    $staffPersonIDs = isset($staff) ? array_values(array_filter(array_unique($staff))) : [];

    $staffDetails = $container->get(UserGateway::class)->selectNotificationDetailsByPerson($staffPersonIDs)->fetchAll();
    $sender = $container->get(UserGateway::class)->getByID($session->get('gibbonPersonID'));

    $view = $container->get(View::class);
    $mail = $container->get(Mailer::class);
    $mail->SMTPKeepAlive = true;

    $content = $view->fetchFromTemplate('calendarEvents.twig.html', [
        'students' => $students->toArray(),
        'event' => $event ?? [],
        'notes' => $notes ?? '',
    ]);
    
    $sender = $container->get(UserGateway::class)->getByID($session->get('gibbonPersonID'));
    $replyTo = $sender['email'];
    $replyToName = Format::name($sender['title'], $sender['preferredName'], $sender['surname'], 'Staff');

    foreach ($staffDetails as $staffDetail) {
        $buttonURL = "index.php?q=/modules/Calendar/calendar_event_view.php&gibbonCalendarEventID=".$gibbonCalendarEventID;
        $subject = sprintf(__('Event Summary - %1$s (%2$s - %3$s'), $event['name'], Format::date($event['dateStart']), Format::date($event['dateEnd']). ')', $session->get('systemName'), $session->get('organisationNameShort'));

        $body = sprintf(__('Dear %1$s'), $staffDetail['preferredName'].' '.$staffDetail['surname']).',<br/><br/>';
        $body .= $content;

        $mail->AddReplyTo($replyTo ?? $session->get('organisationEmail'), $replyToName ?? '');
        $mail->AddAddress($staffDetail['email'], $staffDetail['surname'].', '.$staffDetail['preferredName']);

        $mail->setDefaultSender($subject);
        $mail->renderBody('mail/message.twig.html', [
            'title'  => __('Event Summary'),
            'body'   => $body,
            'button' => [
                'url'  => $buttonURL,
                'text' => __('Click Here to View Event'),
            ],
        ]);

        // Send
        if ($mail->Send()) {
            $sendReport['emailSent']++;
        } else {
            $sendReport['emailFailed']++;
            $sendReport['emailErrors'] .= sprintf(__('An error (%1$s) occurred sending an email to %2$s.'), 'email send failed', $staffDetail['preferredName'].' '.$staffDetail['surname']).'<br/>';
        }

        $mail->ClearAllRecipients();
        $mail->clearReplyTos();
    }

    // Close SMTP connection
    $mail->smtpClose();
        

    
    $URL .= $partialFail
        ? "&return=warning1"
        : "&return=success0";

    header("Location: {$URL}");
}
