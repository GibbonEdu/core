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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Services\Format;
use Gibbon\Contracts\Comms\Mailer;
use Gibbon\Comms\EmailTemplate;
use Gibbon\Comms\NotificationEvent;
use Gibbon\Comms\NotificationSender;
use Gibbon\Domain\Behaviour\BehaviourLetterGateway;
use Gibbon\Domain\System\NotificationGateway;
use Gibbon\Domain\System\EmailTemplateGateway;
use Gibbon\Domain\User\UserGateway;

require getcwd().'/../gibbon.php';

//Check for CLI, so this cannot be run through browser
$settingGateway = $container->get(SettingGateway::class);
$remoteCLIKey = $settingGateway->getSettingByScope('System Admin', 'remoteCLIKey');
$remoteCLIKeyInput = $_GET['remoteCLIKey'] ?? null;
if (!(isCommandLineInterface() OR ($remoteCLIKey != '' AND $remoteCLIKey == $remoteCLIKeyInput))) {
    echo __('This script cannot be run from a browser, only via CLI.');
} else {
    $emailSendCount = 0;
    $emailFailCount = 0;
    $emailFailList = [];

    // Prep for email sending later
    $mail = $container->get(Mailer::class);
    $mail->SMTPKeepAlive = true;

    // Initialize the notification sender & gateway objects
    $notificationGateway = $container->get(NotificationGateway::class);
    $notificationSender = $container->get(NotificationSender::class);

    //Get settings
    $enableDescriptors = $settingGateway->getSettingByScope('Behaviour', 'enableDescriptors');
    $enableLevels = $settingGateway->getSettingByScope('Behaviour', 'enableLevels');
    $enableNegativeBehaviourLetters = $settingGateway->getSettingByScope('Behaviour', 'enableNegativeBehaviourLetters');
    if ($enableNegativeBehaviourLetters == 'Y') {
        $behaviourLettersNegativeLetter1Count = $settingGateway->getSettingByScope('Behaviour', 'behaviourLettersNegativeLetter1Count');
        $behaviourLettersNegativeLetter2Count = $settingGateway->getSettingByScope('Behaviour', 'behaviourLettersNegativeLetter2Count');
        $behaviourLettersNegativeLetter3Count = $settingGateway->getSettingByScope('Behaviour', 'behaviourLettersNegativeLetter3Count');

        $behaviourLetterGateway = $container->get(BehaviourLetterGateway::class);
        $emailTemplateGateway = $container->get(EmailTemplateGateway::class);
        $userGateway = $container->get(UserGateway::class);
        $template = $container->get(EmailTemplate::class);

        if ($behaviourLettersNegativeLetter1Count != '' and $behaviourLettersNegativeLetter2Count != '' and $behaviourLettersNegativeLetter3Count != '' and is_numeric($behaviourLettersNegativeLetter1Count) and is_numeric($behaviourLettersNegativeLetter2Count) and is_numeric($behaviourLettersNegativeLetter3Count)) {
            //SCAN THROUGH ALL STUDENTS

            $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
            $sql = "SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonFormGroup.gibbonFormGroupID, gibbonFormGroup.name AS formGroup, 'Student' AS role, gibbonPersonIDTutor, gibbonPersonIDTutor2, gibbonPersonIDTutor3 FROM gibbonPerson, gibbonStudentEnrolment, gibbonFormGroup WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonFormGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName";
            $result = $pdo->select($sql, $data);

            if ($result->rowCount() > 0) {
                while ($student = $result->fetch()) { //For every student
                    $studentName = Format::name('', $student['preferredName'], $student['surname'], 'Student', false);
                    $formGroup = $student['formGroup'];

                    //Check count of negative behaviour records in the current year=
                    $dataBehaviour = array('gibbonPersonID' => $student['gibbonPersonID'], 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
                    $sqlBehaviour = "SELECT * FROM gibbonBehaviour WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND type='Negative'";
                    $resultBehaviour = $pdo->select($sqlBehaviour, $dataBehaviour);

                    $behaviourCount = $resultBehaviour->rowCount();
                    if ($behaviourCount > 0) { //Only worry about students with more than zero negative records in the current year
                        //Get most recent letter entry
                        $dataLetters = array('gibbonPersonID' => $student['gibbonPersonID'], 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
                        $sqlLetters = "SELECT * FROM gibbonBehaviourLetter WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND type='Negative' ORDER BY timestamp DESC LIMIT 0, 1";
                        $resultLetters = $pdo->select($sqlLetters, $dataLetters);

                        $newLetterRequired = false;
                        $newLetterRequiredLevel = null;
                        $newLetterRequiredStatus = null;
                        $issueExistingLetter = false;
                        $issueExistingLetterLevel = null;
                        $issueExistingLetterID = null;

                        //DECIDE WHAT LETTERS TO SEND
                        if ($resultLetters->rowCount() != 1) { //NO LETTER EXISTS
                            $lastLetterLevel = null;
                            $lastLetterStatus = null;

                            if ($behaviourCount >= $behaviourLettersNegativeLetter3Count) { //Student is over or equal to level 3
                                $newLetterRequired = true;
                                $newLetterRequiredLevel = 3;
                                $newLetterRequiredStatus = 'Issued';
                            } elseif ($behaviourCount >= $behaviourLettersNegativeLetter2Count and $behaviourCount < $behaviourLettersNegativeLetter3Count) { //Student is equal to or greater than level 2 but less than level 3
                                $newLetterRequired = true;
                                $newLetterRequiredLevel = 2;
                                $newLetterRequiredStatus = 'Issued';
                            } elseif ($behaviourCount >= $behaviourLettersNegativeLetter1Count and $behaviourCount < $behaviourLettersNegativeLetter2Count) { //Student is equal to or greater than level 1 but less than level 2
                                $newLetterRequired = true;
                                $newLetterRequiredLevel = 1;
                                $newLetterRequiredStatus = 'Issued';
                            } elseif ($behaviourCount == ($behaviourLettersNegativeLetter1Count - 1)) { //Student is one less than level 1
                                $newLetterRequired = true;
                                $newLetterRequiredLevel = 1;
                                $newLetterRequiredStatus = 'Warning';
                            }
                        } else { //YES LETTER EXISTS
                            $rowLetters = $resultLetters->fetch();
                            $lastLetterLevel = $rowLetters['letterLevel'];
                            $lastLetterStatus = $rowLetters['status'];

                            if ($behaviourCount > $rowLetters['recordCountAtCreation']) { //Only consider action if count has increased since creation (stops second day issue of warning when count has not changed)
                                if ($lastLetterStatus == 'Warning') { //Last letter is warning
                                    if ($behaviourCount >= ${'behaviourLettersNegativeLetter'.$lastLetterLevel.'Count'} and $behaviourCount < ${'behaviourLettersNegativeLetter'.($lastLetterLevel + 1).'Count'}) { //Count escalted to above warning, and less than next full level
                                        $issueExistingLetter = true;
                                        $issueExistingLetterID = $rowLetters['gibbonBehaviourLetterID'];
                                        $issueExistingLetterLevel = $rowLetters['letterLevel'];
                                    } elseif ($behaviourCount >= ${'behaviourLettersNegativeLetter'.($lastLetterLevel + 1).'Count'}) { //Count escalated to equal to or above next level
                                        $newLetterRequired = true;
                                        if ($behaviourCount >= $behaviourLettersNegativeLetter3Count) {
                                            $newLetterRequiredLevel = 3;
                                        } elseif ($behaviourCount >= $behaviourLettersNegativeLetter2Count and $behaviourCount < $behaviourLettersNegativeLetter3Count) {
                                            $newLetterRequiredLevel = 2;
                                        }
                                        $newLetterRequiredStatus = 'Issued';
                                    }
                                } else { //Last letter is issued
                                    if ($behaviourCount == (${'behaviourLettersNegativeLetter'.($lastLetterLevel + 1).'Count'} - 1)) { //Count escalated to next warning
                                        $newLetterRequired = true;
                                        if ($behaviourCount == ($behaviourLettersNegativeLetter3Count - 1)) {
                                            $newLetterRequiredLevel = 3;
                                        } elseif ($behaviourCount == ($behaviourLettersNegativeLetter2Count - 1)) {
                                            $newLetterRequiredLevel = 2;
                                        }
                                        $newLetterRequiredStatus = 'Warning';
                                    } elseif ($behaviourCount > (${'behaviourLettersNegativeLetter'.($lastLetterLevel + 1).'Count'} - 1)) { //Count escalated above next warning
                                        $newLetterRequired = true;
                                        if ($behaviourCount >= $behaviourLettersNegativeLetter3Count) {
                                            $newLetterRequiredLevel = 3;
                                        } elseif ($behaviourCount >= $behaviourLettersNegativeLetter2Count) {
                                            $newLetterRequiredLevel = 2;
                                        }
                                        $newLetterRequiredStatus = 'Issued';
                                    }
                                }
                            }
                        }

                        //SEND LETTERS ACCORDING TO DECISIONS ABOVE
                        $email = false;
                        $letterUpdateFail = false;
                        $gibbonBehaviourLetterID = null;

                        //PREPARE BEHAVIOUR RECORD
                        if ($issueExistingLetter or ($newLetterRequired and $newLetterRequiredStatus == 'Issued')) {
                            //Prepare behaviour record for replacement
                            $behaviourRecord = '<ul>';

                            $dataBehaviourRecord = array('gibbonPersonID' => $student['gibbonPersonID'], 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
                            $sqlBehaviourRecord = "SELECT * FROM gibbonBehaviour WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND type='Negative' ORDER BY timestamp DESC";
                            $resultBehaviourRecord = $pdo->select($sqlBehaviourRecord, $dataBehaviourRecord);

                            while ($rowBehaviourRecord = $resultBehaviourRecord->fetch()) {
                                $behaviourRecord .= '<li>';
                                $behaviourRecord .= Format::date(substr($rowBehaviourRecord['timestamp'], 0, 10));
                                if ($enableDescriptors == 'Y' and $rowBehaviourRecord['descriptor'] != '') {
                                    $behaviourRecord .= ' - '.$rowBehaviourRecord['descriptor'];
                                }
                                if ($enableLevels == 'Y' and $rowBehaviourRecord['level'] != '') {
                                    $behaviourRecord .= ' - '.$rowBehaviourRecord['level'];
                                }
                                $behaviourRecord .= '</li>';
                            }
                            $behaviourRecord .= '</ul>';
                        }

                        if ($issueExistingLetter) { //Issue existing letter
                            //Update record
                            $gibbonBehaviourLetterID = $issueExistingLetterID;

                            $updated = $behaviourLetterGateway->update($gibbonBehaviourLetterID, ['status' => 'Issued']);

                            if ($updated) {
                                //Flag parents to receive email
                                $email = true;

                                //Notify tutor(s)
                                $notificationText = sprintf(__('A student (%1$s) in your form group has received a behaviour letter.'), $studentName);
                                if ($student['gibbonPersonIDTutor'] != '') {
                                    $notificationSender->addNotification($student['gibbonPersonIDTutor'], $notificationText, 'Behaviour', '/index.php?q=/modules/Behaviour/behaviour_letters.php&gibbonPersonID='.$student['gibbonPersonID']);
                                }
                                if ($student['gibbonPersonIDTutor2'] != '') {
                                    $notificationSender->addNotification($student['gibbonPersonIDTutor2'], $notificationText, 'Behaviour', '/index.php?q=/modules/Behaviour/behaviour_letters.php&gibbonPersonID='.$student['gibbonPersonID']);
                                }
                                if ($student['gibbonPersonIDTutor3'] != '') {
                                    $notificationSender->addNotification($student['gibbonPersonIDTutor3'], $notificationText, 'Behaviour', '/index.php?q=/modules/Behaviour/behaviour_letters.php&gibbonPersonID='.$student['gibbonPersonID']);
                                }
                            }
                        } elseif ($newLetterRequired) { //Issue new letter
                            if ($newLetterRequiredStatus == 'Warning') { //It's a warning
                                //Create new record
                                $gibbonBehaviourLetterID = $behaviourLetterGateway->insert([
                                    'type'                  => 'Negative',
                                    'status'                => 'Warning',
                                    'gibbonSchoolYearID'    => $session->get('gibbonSchoolYearID'),
                                    'gibbonPersonID'        => $student['gibbonPersonID'],
                                    'letterLevel'           => $newLetterRequiredLevel,
                                    'recordCountAtCreation' => $behaviourCount,
                                ]);

                                if (!empty($gibbonBehaviourLetterID)) {
                                    //Notify tutor(s)
                                    $notificationText = sprintf(__('A warning has been issued for a student (%1$s) in your form group,
                                     pending a behaviour letter.'), $studentName);
                                    if ($student['gibbonPersonIDTutor'] != '') {
                                        $notificationSender->addNotification($student['gibbonPersonIDTutor'], $notificationText, 'Behaviour', '/index.php?q=/modules/Behaviour/behaviour_letters.php&gibbonPersonID='.$student['gibbonPersonID']);
                                    }
                                    if ($student['gibbonPersonIDTutor2'] != '') {
                                        $notificationSender->addNotification($student['gibbonPersonIDTutor2'], $notificationText, 'Behaviour', '/index.php?q=/modules/Behaviour/behaviour_letters.php&gibbonPersonID='.$student['gibbonPersonID']);
                                    }
                                    if ($student['gibbonPersonIDTutor3'] != '') {
                                        $notificationSender->addNotification($student['gibbonPersonIDTutor3'], $notificationText, 'Behaviour', '/index.php?q=/modules/Behaviour/behaviour_letters.php&gibbonPersonID='.$student['gibbonPersonID']);
                                    }

                                    //Notify teachers
                                    $notificationText = sprintf(__('A warning has been issued for a student (%1$s) in one of your classes, pending a behaviour letter.'), $studentName);

                                    $dataTeachers = array('gibbonPersonID' => $student['gibbonPersonID']);
                                    $sqlTeachers = "SELECT DISTINCT teacher.gibbonPersonID FROM gibbonPerson AS teacher JOIN gibbonCourseClassPerson AS teacherClass ON (teacherClass.gibbonPersonID=teacher.gibbonPersonID)  JOIN gibbonCourseClassPerson AS studentClass ON (studentClass.gibbonCourseClassID=teacherClass.gibbonCourseClassID) JOIN gibbonPerson AS student ON (studentClass.gibbonPersonID=student.gibbonPersonID) JOIN gibbonCourseClass ON (studentClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE teacher.status='Full' AND teacherClass.role='Teacher' AND studentClass.role='Student' AND student.gibbonPersonID=:gibbonPersonID AND gibbonCourse.gibbonSchoolYearID=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE status='Current') ORDER BY teacher.preferredName, teacher.surname, teacher.email ;";
                                    $resultTeachers = $pdo->select($sqlTeachers, $dataTeachers);

                                    while ($rowTeachers = $resultTeachers->fetch()) {
                                        $notificationSender->addNotification($rowTeachers['gibbonPersonID'], $notificationText, 'Behaviour', '/index.php?q=/modules/Behaviour/behaviour_letters.php&gibbonPersonID='.$student['gibbonPersonID']);
                                    }
                                }
                            } else { //It's being issued
                                //Create new record
                                $gibbonBehaviourLetterID = $behaviourLetterGateway->insert([
                                    'type'                  => 'Negative',
                                    'status'                => 'Issued',
                                    'gibbonSchoolYearID'    => $session->get('gibbonSchoolYearID'),
                                    'gibbonPersonID'        => $student['gibbonPersonID'],
                                    'letterLevel'           => $newLetterRequiredLevel,
                                    'recordCountAtCreation' => $behaviourCount,
                                ]);

                                if (!empty($gibbonBehaviourLetterID)) {
                                    //Flag parents to receive email
                                    $email = true;

                                    //Notify tutor(s)
                                    $notificationText = sprintf(__('A student (%1$s) in your form group has received a behaviour letter.'), $studentName);
                                    if ($student['gibbonPersonIDTutor'] != '') {
                                        $notificationSender->addNotification($student['gibbonPersonIDTutor'], $notificationText, 'Behaviour', '/index.php?q=/modules/Behaviour/behaviour_letters.php&gibbonPersonID='.$student['gibbonPersonID']);
                                    }
                                    if ($student['gibbonPersonIDTutor2'] != '') {
                                        $notificationSender->addNotification($student['gibbonPersonIDTutor2'], $notificationText, 'Behaviour', '/index.php?q=/modules/Behaviour/behaviour_letters.php&gibbonPersonID='.$student['gibbonPersonID']);
                                    }
                                    if ($student['gibbonPersonIDTutor3'] != '') {
                                        $notificationSender->addNotification($student['gibbonPersonIDTutor3'], $notificationText, 'Behaviour', '/index.php?q=/modules/Behaviour/behaviour_letters.php&gibbonPersonID='.$student['gibbonPersonID']);
                                    }
                                }
                            }
                        }

                        //DEAL WIITH EMAILS
                        if ($email) {
                            $recipientList = '';

                            // Setup template to send
                            $templateCount = $issueExistingLetter ? $issueExistingLetterLevel : $newLetterRequiredLevel;
                            $templateType  = "Negative Behaviour Letter $templateCount";
                            $templateDetails = $emailTemplateGateway->selectBy(['templateType' => $templateType], ['templateName'])->fetch();
                            $template->setTemplate($templateDetails['templateName'] ?? 'Negative Behaviour Letter 1');

                            // Get form tutor details
                            $formTutor = $userGateway->getByID($student['gibbonPersonIDTutor'], ['title', 'surname', 'preferredName', 'email']);

                            //Send emails
                            $dataMember = array('gibbonPersonID' => $student['gibbonPersonID']);
                            $sqlMember = "SELECT DISTINCT email, preferredName, surname, title FROM gibbonFamilyChild JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyChild.gibbonPersonID=:gibbonPersonID AND gibbonPerson.status='Full' AND contactEmail='Y' ORDER BY contactPriority, surname, preferredName";
                            $resultMember = $pdo->select($sqlMember, $dataMember);

                            while ($parent = $resultMember->fetch()) {
                                ++$emailSendCount;
                                if ($parent['email'] == '') {
                                    ++$emailFailCount;
                                    $emailFailList[] = $parent['surname'].', '.$parent['preferredName'].' (no email)';
                                } else {
                                    $recipientList .= $parent['email'].', ';

                                    // Setup template data
                                    $templateData = [
                                        'behaviourCount'         => $behaviourCount,
                                        'behaviourRecord'        => $behaviourRecord,
                                        'studentPreferredName'   => $student['preferredName'],
                                        'studentSurname'         => $student['surname'],
                                        'studentFormGroup'       => $student['formGroup'],
                                        'parentPreferredName'    => $parent['preferredName'],
                                        'parentSurname'          => $parent['surname'],
                                        'parentTitle'            => $parent['title'],
                                        'formTutorPreferredName' => $formTutor['preferredName'],
                                        'formTutorSurname'       => $formTutor['surname'],
                                        'formTutorTitle'         => $formTutor['title'],
                                        'formTutorEmail'         => $formTutor['email'],
                                        'date'                   => Format::date(date('Y-m-d')),
                                    ];

                                    // Render the templates for this email
                                    $subject = $template->renderSubject($templateData);
                                    $body = $template->renderBody($templateData);

                                    // Send message
                                    $mail->AddAddress($parent['email'], $parent['surname'].', '.$parent['preferredName']);
                                    if ($session->has('organisationEmail')) {
                                        $mail->SetFrom($session->get('organisationEmail'), $session->get('organisationName'));
                                    } else {
                                        $mail->SetFrom($session->get('organisationAdministratorEmail'), $session->get('organisationAdministratorName'));
                                    }

                                    $mail->Subject = $subject;
                                    $mail->renderBody('mail/message.twig.html', [
                                        'title'  => $subject,
                                        'body'   => $body,
                                    ]);

                                    if ($mail->Send()) {
                                        $behaviourLetterGateway->update($gibbonBehaviourLetterID, ['body' => $body]);
                                    } else {
                                        ++$emailFailCount;
                                        $emailFailList[] = $parent['surname'].', '.$parent['preferredName'].' ('.$parent['email'].')';
                                    }

                                    // Clear addresses
                                    $mail->ClearAllRecipients();
                                }
                            }

                            if ($recipientList != '') {
                                $recipientList = substr($recipientList, 0, -2);

                                //Record email recipients in letter record
                                $dataUpdate = array('recipientList' => $recipientList, 'gibbonBehaviourLetterID' => $gibbonBehaviourLetterID);
                                $sqlUpdate = 'UPDATE gibbonBehaviourLetter set recipientList=:recipientList WHERE gibbonBehaviourLetterID=:gibbonBehaviourLetterID';
                                $resultUpdate = $pdo->select($sqlUpdate, $dataUpdate);
                            }
                        }
                    }
                }
            }
        }
    }

    // Close SMTP connection
    $mail->smtpClose();

    // Raise a new notification event
    $event = new NotificationEvent('Behaviour', 'Behaviour Letters');

    //Notify admin
    if (empty($email)) {
        $event->setNotificationText(__('The Behaviour Letter CLI script has run: no emails were sent.'));
    } else {
        $event->setNotificationText(sprintf(__('The Behaviour Letter CLI script has run: %1$s emails were sent, of which %2$s failed.'), $emailSendCount, $emailFailCount).'<br/><br/>'.Format::list($emailFailList));
    }

    $event->setActionLink('/index.php?q=/modules/Behaviour/behaviour_letters.php');

    // Add admin, then push the event to the notification sender
    $event->addRecipient($session->get('organisationAdministrator'));
    $event->pushNotifications($notificationGateway, $notificationSender);

    // Send all notifications
    $sendReport = $notificationSender->sendNotifications();

    // Output the result to terminal
    echo sprintf('Sent %1$s notifications: %2$s inserts, %3$s updates, %4$s emails sent, %5$s emails failed.', $sendReport['count'], $sendReport['inserts'], $sendReport['updates'], $emailSendCount, $emailFailCount)."\n";
}
