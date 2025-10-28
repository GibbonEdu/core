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

use Gibbon\Comms\Mailer;
use Gibbon\Data\Validator;
use Gibbon\Services\Format;
use Gibbon\Comms\EmailTemplate;
use Gibbon\Comms\NotificationEvent;
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

    // Get event details
    $values = $calendarEventGateway->getByID($gibbonCalendarEventID);
    if (!empty($gibbonCalendarEventID) && empty($values)) {
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

    $notes = $_POST['notes'] ?? '';
    $notifyGroups = $_POST['notifyGroups'] ?? [];
    $allStaff = $_POST['allStaff'] ?? 'N';
    $notificationList = isset($_POST['notificationList']) ? explode(',', $_POST['notificationList']) : [];
    $staff = [];

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

    echo '<pre>';
    print_r($staffDetails);
    echo '</pre>';
    die();

    // Create the email Template
    $template = $container->get(EmailTemplate::class)->setTemplate('Calendar Event Notification');

    $mail = $container->get(Mailer::class);
    $mail->SMTPKeepAlive = true;

    $emailIndex = 1;
    $emails = [];


    foreach ($staffDetails as $staffDetail) {

        // Setup the email recipients
        $mail->ClearAddresses();
        $mail->AddAddress($staffDetail['email']);

        $mail->SetFrom($sender['email'], $sender['preferredName'].' '.$sender['surname']);
        $mail->AddReplyTo($sender['email'],);
        $mail->setDefaultSender($template->renderSubject($templateData));

        $mail->renderBody('mail/message.twig.html', [
            'title'  => $template->renderSubject($templateData),
            'body'   => $template->renderBody($templateData),
        ]);

        // Send email and record the result
        $sent = $mail->Send();

        $emails[$emailIndex] = Format::name($templateData['title'], $templateData['preferredName'], $templateData['surname'], 'Staff').': '.$templateData['email'].' ($'.$templateData['amount'].') - '. ($sent ? __('Sent') : __('Failed') );
        $emailIndex++;
    }

        
    // echo '<pre>';
    // print_r($emailStaff);
    // echo '</pre>';
    // die();
        

        // $studentName = Format::name('', $student['preferredName'], $student['surname'], 'Student', false, true);
            
        // $today = date("Y-m-d"); 
        // if ($today > $data['dateEnd']) {
        //     $notificationString = __('{student} {formGroup} has withdrawn from {school} on {date}.', [
        //         'student'   => $studentName,
        //         'formGroup'   => $student['formGroup'],
        //         'school'    => $session->get('organisationNameShort'),
        //         'date'      => Format::date($data['dateEnd']),
        //     ]);
        // } else {
        //     $notificationString = __('{student} {formGroup} will withdraw from {school}, effective from {date}.', [
        //         'student'   => $studentName,
        //         'formGroup'   => $student['formGroup'],
        //         'school'    => $session->get('organisationNameShort'),
        //         'date'      => Format::date($data['dateEnd']),
        //     ]);
        // }
        
        // if (!empty($withdrawNote)) {
        //     $notificationString .= '<br/><br/>'.__('Withdraw Note').': '.$withdrawNote;
        // }

        // Raise a new notification event
        // $event = new NotificationEvent('Admissions', 'Student Withdrawn');
        // $event->addScope('gibbonPersonIDStudent', $gibbonPersonID);
        // $event->addScope('gibbonYearGroupID', $student['gibbonYearGroupID']);
        // $event->setNotificationText($notificationString);
        // $event->setActionLink('/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$gibbonPersonID.'&search=&sort=&allStudents=on');

        // Notify Additional People

        // Head of Year
        // if (in_array('HOY', $notify)) {
        //     $yearGroup = $container->get(YearGroupGateway::class)->getByID($student['gibbonYearGroupID']);
        //     $event->addRecipient($yearGroup['gibbonPersonIDHOY']);
        // }

        // Form Tutors
        // if (in_array('tutors', $notify)) {
        //     $formGroup = $container->get(FormGroupGateway::class)->getByID($student['gibbonFormGroupID']);
        //     $event->addRecipient($formGroup['gibbonPersonIDTutor']);
        //     $event->addRecipient($formGroup['gibbonPersonIDTutor2']);
        //     $event->addRecipient($formGroup['gibbonPersonIDTutor3']);
        // }

        // // Class Teachers
        // if (in_array('teachers', $notify)) {
        //     $teachers = $container->get(CourseEnrolmentGateway::class)->selectClassTeachersByStudent($session->get('gibbonSchoolYearID'), $gibbonPersonID);
        //     foreach ($teachers as $teacher) {
        //         $event->addRecipient($teacher['gibbonPersonID']);
        //     }
        // }

        // Add event listeners to the notification sender
        // $event->sendNotifications($pdo, $session);

    

    $URL .= $partialFail
        ? "&return=warning1"
        : "&return=success0";

    header("Location: {$URL}");
}
