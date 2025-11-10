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
use Gibbon\Domain\IndividualNeeds\INAssistantGateway;
use Gibbon\Domain\Calendar\CalendarEventPersonGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['notes' => 'HTML']);

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
    $userGateway = $container->get(UserGateway::class);

    $subject = $_POST['subject'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $notifyGroups = $_POST['notifyGroups'] ?? [];
    $allStaff = $_POST['allStaff'] ?? 'N';
    $notificationList = isset($_POST['notificationList']) ? explode(',', $_POST['notificationList']) : [];
    $staff = [];
    $staffStudentContext = [];

    // Get event details
    $event = $calendarEventGateway->getByID($gibbonCalendarEventID);
    if (!empty($gibbonCalendarEventID) && empty($event)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Get all student participants
    $criteria = $calendarEventPersonGateway->newQueryCriteria()
        ->sortBy(['surname', 'preferredName', 'category'])
        ->fromPOST();
    $students = $calendarEventPersonGateway->queryEnrolledAttendees($criteria, $gibbonCalendarEventID)->toArray();

    if (empty($students)) {
        $URL .= '&return=error3';
        header("Location: {$URL}");
        exit;
    }

    if ($allStaff == 'Y') {
         // All Staff
        $staffGateway = $container->get(StaffGateway::class);
        $criteria = $staffGateway->newQueryCriteria();

        $results = $staffGateway->queryAllStaff($criteria);
        foreach ($results as $result) {
            $staff[] = $result['gibbonPersonID'];
        }
    } else {
        if (!empty($notifyGroups)) {
            foreach ($students as $student) {
                $gibbonPersonIDStudent = $student['gibbonPersonID'];

                // Head of Year
                if (in_array('HOY', $notifyGroups)) {
                    $yearGroup = $container->get(YearGroupGateway::class)->getByID($student['gibbonYearGroupID']);
                    $gibbonPersonIDHOY = $yearGroup['gibbonPersonIDHOY'] ?? null;
                    if (!empty($gibbonPersonIDHOY)) {
                        $staff[] = $gibbonPersonIDHOY;

                        // Record Relation
                        if (!isset($staffStudentContext[$gibbonPersonIDHOY][$gibbonPersonIDStudent]['context']) || !in_array('HOY', $staffStudentContext[$gibbonPersonIDHOY][$gibbonPersonIDStudent]['context'])) {
                            $staffStudentContext[$gibbonPersonIDHOY][$gibbonPersonIDStudent]['context'][] = 'HOY';
                        }
                    }
                }

                // Form Tutors
                if (in_array('tutors', $notifyGroups)) {
                    $formGroup = $container->get(FormGroupGateway::class)->getByID($student['gibbonFormGroupID']);
                    $tutorIDs = [
                        $formGroup['gibbonPersonIDTutor'] ?? null,
                        $formGroup['gibbonPersonIDTutor2'] ?? null,
                        $formGroup['gibbonPersonIDTutor3'] ?? null,
                    ];

                    foreach ($tutorIDs as $gibbonPersonIDTutor) {
                        if (empty($gibbonPersonIDTutor)) continue;
                        $staff[] = $gibbonPersonIDTutor;

                        // Record Relation
                        if (!isset($staffStudentContext[$gibbonPersonIDTutor][$gibbonPersonIDStudent]['context']) || !in_array('Form Tutor', $staffStudentContext[$gibbonPersonIDTutor][$gibbonPersonIDStudent]['context'])) {
                            $staffStudentContext[$gibbonPersonIDTutor][$gibbonPersonIDStudent]['context'][] = 'Form Tutor';
                        }
                    }
                }

                // Class Teachers
                if (in_array('teachers', $notifyGroups)) {
                    $teachers = $container->get(CourseEnrolmentGateway::class)->selectClassTeachersByStudent($session->get('gibbonSchoolYearID'), $gibbonPersonIDStudent);
                    foreach ($teachers as $teacher) {
                        $gibbonPersonIDTeacher = $teacher['gibbonPersonID'] ?? null;

                        if (empty($gibbonPersonIDTeacher)) continue;
                        $staff[] = $gibbonPersonIDTeacher;

                        // Record relation
                        if (!isset($staffStudentContext[$gibbonPersonIDTeacher][$gibbonPersonIDStudent]['context']) || !in_array('Class Teacher', $staffStudentContext[$gibbonPersonIDTeacher][$gibbonPersonIDStudent]['context'])) {
                            $staffStudentContext[$gibbonPersonIDTeacher][$gibbonPersonIDStudent]['context'][] = 'Class Teacher';
                        }
                    }
                }

                if (in_array('INAssistant', $notifyGroups)) {
                    $assistants = $container->get(INAssistantGateway::class)->selectINAssistantsByStudent($gibbonPersonIDStudent);
                    foreach ($assistants as $assistant) {
                        $gibbonPersonIDAssistant = $assistant['gibbonPersonID'] ?? null;

                        if (empty($gibbonPersonIDAssistant)) continue; 
                        $staff[] = $gibbonPersonIDAssistant;

                        // Record Relation
                        if (!isset($staffStudentContext[$gibbonPersonIDAssistant][$gibbonPersonIDStudent]['context']) || !in_array('LSA', $staffStudentContext[$gibbonPersonIDAssistant][$gibbonPersonIDStudent]['context'])) {
                            $staffStudentContext[$gibbonPersonIDAssistant][$gibbonPersonIDStudent]['context'][] = 'LSA';
                        }
                    }
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
    $staffDetails = $userGateway->selectNotificationDetailsByPerson($staffPersonIDs)->fetchAll();

    $view = $container->get(View::class);
    $mail = $container->get(Mailer::class);
    $mail->SMTPKeepAlive = true;

    $sender = $userGateway->getByID($session->get('gibbonPersonID'));
    $replyTo = $sender['email'];
    $replyToName = Format::name($sender['title'], $sender['preferredName'], $sender['surname'], 'Staff');

    foreach ($staffDetails as $staffDetail) {
        $gibbonPersonIDTeacher = $staffDetail['gibbonPersonID'];

        // Get the relevant students of this staff
        $relevantStudents = [];
        foreach ($students as $student) {
            $gibbonPersonIDStudent = $student['gibbonPersonID'];
            if (isset($staffStudentContext[$gibbonPersonIDTeacher][$gibbonPersonIDStudent]['context'])) {
                
                // Get all the roles for this student-teacher pair
                $contextLabels = implode(', ', $staffStudentContext[$gibbonPersonIDTeacher][$gibbonPersonIDStudent]['context']);
                $relevantStudents[] = array_merge($student, [
                    'context' => $contextLabels
                ]);
            } else {
                $relevantStudents[] = array_merge($student, [
                    'context' => $allStaff == 'Y' ? 'All Staff' : 'Other',
                ]);
            }
        }

        $buttonURL = "index.php?q=/modules/Calendar/calendar_event_view.php&gibbonCalendarEventID=".$gibbonCalendarEventID;
        $subject = !empty($subject) ? $subject : __('Event').': '. $values['name'] . ($values['allDay'] != 'Y' ? ', ' .Format::dateRangeReadable($values['dateStart'], $values['dateEnd']) : '');
        
        // Generate content from template
        $content = $view->fetchFromTemplate('calendarEvents.twig.html', [
            'students' => $relevantStudents,
            'event' => $event ?? [],
            'notes' => $notes ?? '',
        ]);

        // $body = sprintf(__('Dear %1$s'), $staffDetail['preferredName'].' '.$staffDetail['surname']).',<br/><br/>';
        // $body .= $content;

        $mail->AddReplyTo($replyTo ?? $session->get('organisationEmail'), $replyToName ?? '');
        $mail->AddAddress($staffDetail['email'], $staffDetail['surname'].', '.$staffDetail['preferredName']);

        $mail->setDefaultSender($subject);
        $mail->renderBody('mail/message.twig.html', [
            'title'  => $event['name'],
            'body'   => $body,
            'button' => [
                'url'  => $buttonURL,
                'text' => __('View Details'),
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
