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

namespace Gibbon\Module\Calendar;

use Gibbon\View\View;
use Gibbon\Services\Format;
use Gibbon\Contracts\Comms\Mailer;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\Staff\StaffGateway;
use Gibbon\Contracts\Services\Session;
use Gibbon\Services\BackgroundProcess;
use Gibbon\Domain\School\YearGroupGateway;
use Gibbon\Domain\FormGroups\FormGroupGateway;
use Gibbon\Domain\Calendar\CalendarEventGateway;
use Gibbon\Domain\Timetable\CourseEnrolmentGateway;
use Gibbon\Domain\IndividualNeeds\INAssistantGateway;
use Gibbon\Domain\Calendar\CalendarEventPersonGateway;

/**
 * CalendarEventNotificationProcess
 *
 * @version v30
 * @since   v30
 */
class CalendarEventNotificationProcess extends BackgroundProcess
{
    protected $session;
    protected $view;
    protected $mail;
    protected $userGateway;
    protected $staffGateway;
    protected $yearGroupGateway;
    protected $formGroupGateway;
    protected $courseEnrolmentGateway;
    protected $iNAssistantGateway;
    protected $calendarEventGateway;
    protected $calendarEventPersonGateway;

    public function __construct(
        UserGateway $userGateway,
        StaffGateway $staffGateway,
        YearGroupGateway $yearGroupGateway,
        FormGroupGateway $formGroupGateway,
        CourseEnrolmentGateway $courseEnrolmentGateway,
        INAssistantGateway $iNAssistantGateway,
        CalendarEventGateway $calendarEventGateway,
        CalendarEventPersonGateway $calendarEventPersonGateway,
        Session $session,
        View $view,
        Mailer $mail
    ) {
        $this->session = $session;
        $this->userGateway = $userGateway;
        $this->staffGateway = $staffGateway;
        $this->yearGroupGateway = $yearGroupGateway;
        $this->formGroupGateway = $formGroupGateway;
        $this->courseEnrolmentGateway = $courseEnrolmentGateway;
        $this->iNAssistantGateway = $iNAssistantGateway;
        $this->calendarEventGateway = $calendarEventGateway;
        $this->calendarEventPersonGateway = $calendarEventPersonGateway;
        $this->view = $view;
        $this->mail = $mail;
    }

    public function runNotifyStaff($gibbonCalendarEventID, $subject, $notes, $notifyGroups, $allStaff, $notificationList)
    {
        $staff = [];
        $staffStudentContext = [];

        $event = $this->calendarEventGateway->getByID($gibbonCalendarEventID);


        // Get all Attendees 
        $criteria = $this->calendarEventPersonGateway->newQueryCriteria()
            ->sortBy(['surname', 'preferredName', 'category'])
            ->fromPOST();

        $students = $this->calendarEventPersonGateway->queryEventAttendees($criteria, $gibbonCalendarEventID)->toArray();

        // All Staff
        if ($allStaff == 'Y') {
            $criteria = $this->staffGateway->newQueryCriteria();
            $results = $this->staffGateway->queryAllStaff($criteria);

            foreach ($results as $result) {
                $staff[] = $result['gibbonPersonID'];
            }
        } else {
            if (!empty($notifyGroups)) {
                foreach ($students as $student) {
                    $gibbonPersonIDStudent = $student['gibbonPersonID'];

                    // Head of Year
                    if (in_array('HOY', $notifyGroups)) {
                        $yearGroup = $this->yearGroupGateway->getByID($student['gibbonYearGroupID']);
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
                        $formGroup = $this->formGroupGateway->getByID($student['gibbonFormGroupID']);
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
                        $teachers = $this->courseEnrolmentGateway->selectClassTeachersByStudent($this->session->get('gibbonSchoolYearID'), $gibbonPersonIDStudent);
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
                        $assistants = $this->iNAssistantGateway->selectINAssistantsByStudent($gibbonPersonIDStudent);
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
        $staffDetails = $this->userGateway->selectNotificationDetailsByPerson($staffPersonIDs)->fetchAll();

        $this->mail->SMTPKeepAlive = true;

        $sender = $this->userGateway->getByID($this->session->get('gibbonPersonID'));
        $replyTo = $sender['email'];
        $replyToName = Format::name($sender['title'], $sender['preferredName'], $sender['surname'], 'Staff');
        $sendReport = ['emailSent' => 0, 'emailFailed' => 0, 'emailErrors' => ''];

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
            $subject = !empty($subject) ? $subject : __('Event').': '. $event['name'] . ($event['allDay'] != 'Y' ? ', ' .Format::dateRangeReadable($event['dateStart'], $event['dateEnd']) : '');
        
            // Generate content from template
            $content = $this->view->fetchFromTemplate('calendarEvents.twig.html', [
                'students' => $relevantStudents,
                'event' => $event ?? [],
                'notes' => $notes ?? '',
            ]);


            $body = sprintf(__('Dear %1$s'), $staffDetail['preferredName'].' '.$staffDetail['surname']).',<br/><br/>';
            $body .= $content;

            $this->mail->AddReplyTo($replyTo ?? $this->session->get('organisationEmail'), $replyToName ?? '');
            $this->mail->AddAddress($staffDetail['email'], $staffDetail['surname'].', '.$staffDetail['preferredName']);

            $this->mail->setDefaultSender($subject);
            $this->mail->renderBody('mail/message.twig.html', [
                'title'  => $event['name'],
                'body'   => $body,
                'button' => [
                    'url'  => $buttonURL,
                    'text' => __('View Details'),
                ],
            ]);

            // Send
            if ($this->mail->Send()) {
                $sendReport['emailSent']++;
            } else {
                $sendReport['emailFailed']++;
                $sendReport['emailErrors'] .= sprintf(__('An error (%1$s) occurred sending an email to %2$s.'), 'email send failed', $staffDetail['preferredName'].' '.$staffDetail['surname']).'<br/>';
            }

            $this->mail->ClearAllRecipients();
            $this->mail->clearReplyTos();
        }

        $reportSubject = __('EmailReport For: ').$event['name'];
        $reportBody = sprintf(__('Dear %1$s'), $sender['preferredName'].' '.$sender['surname']).',<br/><br/>';
        $reportBody .= '<strong>'.__('Summary').':</strong><br/>';
        $reportBody .= sprintf(__('Emails Sent: %1$s'), $sendReport['emailSent']) . '<br/>';
        $reportBody .= sprintf(__('Emails Failed: %1$s'), $sendReport['emailFailed']) . '<br/>';
        
        if (!empty($sendReport['emailErrors'])) {
            $reportBody .= '<br/><strong>'.__('Errors').':</strong><br/>';
            $reportBody .= $sendReport['emailErrors'];
        }

        $this->mail->AddAddress($sender['email'], $sender['surname'].', '.$sender['preferredName']);
        $this->mail->setDefaultSender($reportSubject);
        $this->mail->renderBody('mail/message.twig.html', [
            'title'  => __('Email Report'),
            'body'   => $reportBody,
        ]);

        $this->mail->Send();

        // Close SMTP connection
        $this->mail->smtpClose();

        return $sendReport['emailFailed'] == 0;
    }
}