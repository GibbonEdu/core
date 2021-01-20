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

use Gibbon\View\View;
use Gibbon\Services\Format;
use Gibbon\Contracts\Comms\Mailer;
use Gibbon\Comms\NotificationEvent;
use Gibbon\Domain\Planner\PlannerEntryGateway;
use Gibbon\Domain\User\FamilyGateway;
use Gibbon\Domain\RollGroups\RollGroupGateway;
use Gibbon\Domain\Planner\PlannerParentWeeklyEmailSummaryGateway;

$_POST['address'] = '/modules/School Admin/emailSummarySettings.php';

require __DIR__.'/../gibbon.php';

// Setup some of the globals
getSystemSettings($guid, $connection2);
setCurrentSchoolYear($guid, $connection2);
Format::setupFromSession($container->get('session'));

if (!isCommandLineInterface()) {
    echo __('This script cannot be run from a browser, only via CLI.');
    return;
}

// Check that one of the days in question is a school day
$isSchoolOpen = false;
for ($i = 0; $i < 7; ++$i) {
    if (isSchoolOpen($guid, date('Y-m-d', strtotime("-$i day")), $connection2, true) == true) {
        $isSchoolOpen = true;
    }
}

if ($isSchoolOpen == false) {
    echo __('School is not open, so no emails will be sent.');
    return;
}

if ($_SESSION[$guid]['organisationEmail'] == '') {
    echo __('This script cannot be run, as no school email address has been set.');
    return;
}

$gibbonSchoolYearID = $gibbon->session->get('gibbonSchoolYearID');
$parentWeeklyEmailSummaryIncludeBehaviour = getSettingByScope($connection2, 'School Admin', 'parentWeeklyEmailSummaryIncludeBehaviour');
$parentWeeklyEmailSummaryIncludeMarkbook = getSettingByScope($connection2, 'School Admin', 'parentWeeklyEmailSummaryIncludeMarkbook');
$sendReport = ['emailSent' => 0, 'emailFailed' => 0, 'emailErrors' => ''];

// Prep for email sending later
$mail = $container->get(Mailer::class);
$mail->SMTPKeepAlive = true;

$familyGateway = $container->get(FamilyGateway::class);
$rollGroupGateway = $container->get(RollGroupGateway::class);
$plannerEntryGateway = $container->get(PlannerEntryGateway::class);
$emailSummaryGateway = $container->get(PlannerParentWeeklyEmailSummaryGateway::class);
$view = $container->get(View::class);

// Get all student data grouped by family
$families = $familyGateway->selectFamiliesWithActiveStudents($gibbonSchoolYearID)->fetchGrouped();

foreach ($families as $gibbonFamilyID => $students) {
    // Get the adults in this family and filter by email settings
    $familyAdults = $familyGateway->selectAdultsByFamily($gibbonFamilyID, true)->fetchAll();
    $familyAdults = array_filter($familyAdults, function ($parent) {
        return $parent['contactEmail'] == 'Y';
    });

    if (empty($familyAdults)) continue;

    foreach ($students as $student) {

        // HOMEWORK
        $criteria = $plannerEntryGateway->newQueryCriteria(true)
            ->sortBy('homeworkDueDateTime', 'ASC')
            ->filterBy('weekly:Y')
            ->filterBy('viewableParents:Y')
            ->fromPOST();

        $allHomework = $plannerEntryGateway->queryHomeworkByPerson($criteria, $gibbonSchoolYearID, $student['gibbonPersonID']);

        $tracker = $plannerEntryGateway->selectHomeworkTrackerByStudent($gibbonSchoolYearID, $student['gibbonPersonID'])->fetchGroupedUnique();
        $allHomework->joinColumn('gibbonPlannerEntryID', 'tracker', $tracker);

        $submissions = $plannerEntryGateway->selectHomeworkSubmissionsByStudent($gibbonSchoolYearID, $student['gibbonPersonID'])->fetchGrouped();
        $allHomework->joinColumn('gibbonPlannerEntryID', 'submissions', $submissions);

        // BEHAVIOUR
        if ($parentWeeklyEmailSummaryIncludeBehaviour == 'Y') {
            $dataBehaviour = ['gibbonPersonID' => $student['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'today' => date('Y-m-d'), 'lastWeek' => date('Y-m-d', strtotime('-1 week'))];
            $sqlBehaviour = "
                SELECT COUNT(DISTINCT CASE WHEN type='Positive' THEN gibbonBehaviourID END) as positive, 
                COUNT(DISTINCT CASE WHEN type='Negative' THEN gibbonBehaviourID END) as negative 
                FROM gibbonBehaviour 
                WHERE gibbonPersonID=:gibbonPersonID 
                AND gibbonSchoolYearID=:gibbonSchoolYearID 
                AND date>:lastWeek AND date<=:today";

            $behaviour = $pdo->selectOne($sqlBehaviour, $dataBehaviour);
        }

        // MARKBOOK
        if ($parentWeeklyEmailSummaryIncludeMarkbook == 'Y') {
            $dataMarkbook = ['gibbonPersonID' => $student['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'today' => date('Y-m-d'), 'lastWeek' => date('Y-m-d', strtotime('-1 week'))];
            $sqlMarkbook = "
                SELECT
                    CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) AS class,
                    gibbonMarkbookColumn.name
                FROM gibbonMarkbookEntry
                    JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID)
                    JOIN gibbonCourseClass ON (gibbonMarkbookColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                    JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                    JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND gibbonMarkbookEntry.gibbonPersonIDStudent=gibbonCourseClassPerson.gibbonPersonID)
                WHERE
                    gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID
                    AND gibbonMarkbookEntry.gibbonPersonIDStudent=:gibbonPersonID
                    AND complete='Y'
                    AND completeDate >:lastWeek
                    AND completeDate <=:today";

            $markbook = $pdo->select($sqlMarkbook, $dataMarkbook)->fetchAll();
        }

        // Format the student summary for emailing
        $content = $view->fetchFromTemplate('cli/parentWeeklyEmailSummary.twig.html', [
            'homeworkNameSingular' => getSettingByScope($connection2, 'Planner', 'homeworkNameSingular'),
            'homeworkNamePlural' => getSettingByScope($connection2, 'Planner', 'homeworkNamePlural'),
            'includeBehaviour' => $parentWeeklyEmailSummaryIncludeBehaviour,
            'includeMarkbook' => $parentWeeklyEmailSummaryIncludeMarkbook,
            'includeHomework' => 'Y',
            'student' => $student,
            'homework' => $allHomework->toArray(),
            'behaviour' => $behaviour ?? [],
            'markbook' => $markbook ?? [],
        ]);

        // Get main form tutor email for reply-to
        $formTutor = $rollGroupGateway->selectTutorsByRollGroup($student['gibbonRollGroupID'])->fetch();
        if (!empty($formTutor)) {
            $replyTo = $formTutor['email'];
            $replyToName = Format::name($formTutor['title'], $formTutor['preferredName'], $formTutor['surname'], 'Staff');
        }
    
        // Check for send this week, and only proceed if no prior send
        $parentContact1 = current($familyAdults);
        $checkExistingSummary = $emailSummaryGateway->getWeeklySummaryDetailsByParent($gibbonSchoolYearID, $parentContact1['gibbonPersonID'], $student['gibbonPersonID']);

        if (!empty($checkExistingSummary)) {
            $sendReport['emailFailed']++;
            $sendReport['emailErrors'] .= sprintf(__('An error (%1$s) occurred sending an email to %2$s.'), 'duplicate key exists', $parentContact1['preferredName'].' '.$parentContact1['surname']).'<br/>';
            continue;
        }

        // Make and store unique code for confirmation.
        for ($count = 0; $count < 100; $count++) {
            $key = randomPassword(40);
            $checkUnique = $emailSummaryGateway->getAnySummaryDetailsByKey($key);

            if (empty($checkUnique)) break;
        }

        // Check key exists
        if (empty($key) || !empty($checkUnique)) {
            $sendReport['emailFailed']++;
            $sendReport['emailErrors'] .= sprintf(__('An error (%1$s) occurred sending an email to %2$s.'), 'key create failed', $parentContact1['preferredName'].' '.$parentContact1['surname']).'<br/>';
            continue;
        } 

        // Write key to database
        $data = [
            'gibbonSchoolYearID' => $gibbonSchoolYearID,
            'gibbonPersonIDStudent' => $student['gibbonPersonID'],
            'gibbonPersonIDParent' => $parentContact1['gibbonPersonID'],
            'weekOfYear' => date('W'),
            'confirmed' => 'N',
            'key' => $key,
        ];
        $gibbonPlannerParentWeeklyEmailSummaryID = $emailSummaryGateway->insert($data);

        // Check key was inserted
        if (empty($gibbonPlannerParentWeeklyEmailSummaryID)) {
            $sendReport['emailFailed']++;
            $sendReport['emailErrors'] .= sprintf(__('An error (%1$s) occurred sending an email to %2$s.'), 'key write failed', $parentContact1['preferredName'].' '.$parentContact1['surname']).'<br/>';
            continue;
        }

        // Send an email to each parent using the same key
        foreach ($familyAdults as $parent) {
            
            // Prep email
            $buttonURL = "/index.php?q=/modules/Planner/planner_parentWeeklyEmailSummaryConfirm.php&key=$key&gibbonPersonIDStudent=".$student['gibbonPersonID'].'&gibbonPersonIDParent='.$parent['gibbonPersonID'].'&gibbonSchoolYearID='.$_SESSION[$guid]['gibbonSchoolYearID'];

            $subject = sprintf(__('Weekly Planner Summary for %1$s via %2$s at %3$s'), $student['surname'].', '.$student['preferredName'].' ('.$student['rollGroup'].')', $gibbon->session->get('systemName'), $gibbon->session->get('organisationNameShort'));

            $body = sprintf(__('Dear %1$s'), $parent['preferredName'].' '.$parent['surname']).',<br/><br/>';
            $body .= $content;

            $mail->AddReplyTo($replyTo ?? $gibbon->session->get('organisationEmail'), $replyToName ?? '');
            $mail->AddAddress($parent['email'], $parent['surname'].', '.$parent['preferredName']);

            $mail->setDefaultSender($subject);
            $mail->renderBody('mail/message.twig.html', [
                'title'  => __('Weekly Planner Summary'),
                'body'   => $body,
                'button' => [
                    'url'  => $buttonURL,
                    'text' => __('Click Here to Confirm'),
                ],
            ]);

            // Send
            if ($mail->Send()) {
                $sendReport['emailSent']++;
            } else {
                $sendReport['emailFailed']++;
                $sendReport['emailErrors'] .= sprintf(__('An error (%1$s) occurred sending an email to %2$s.'), 'email send failed', $parent['preferredName'].' '.$parent['surname']).'<br/>';
            }

            // Clear addresses
            $mail->ClearAllRecipients(); 
            $mail->clearReplyTos(); 
        }
    }
}


// Close SMTP connection
$mail->smtpClose();

// Raise a new notification event
$event = new NotificationEvent('School Admin', 'Parent Weekly Email Summary');

$body = __('Week').': '.date('W').'<br/>';
$body .= __('Total Count').': '.($sendReport['emailSent'] + $sendReport['emailFailed']).'<br/>';
$body .= __('Send Succeed Count').': '.$sendReport['emailSent'].'<br/>';
$body .= __('Send Fail Count').': '.$sendReport['emailFailed'].'<br/><br/>';
$body .= $sendReport['emailErrors'];

$event->setNotificationText(__('A School Admin CLI script has run.').'<br/>'.$body);
$event->setActionLink('/index.php?q=/modules/Planner/report_parentWeeklyEmailSummaryConfirmation.php');

// Notify admin
$event->addRecipient($gibbon->session->get('organisationAdministrator'));

// Send all notifications
$event->sendNotifications($pdo, $gibbon->session);

// Output the result to terminal
echo sprintf('Sent %1$s emails: %2$s emails sent, %3$s emails failed.', $sendReport['emailSent'] + $sendReport['emailFailed'], $sendReport['emailSent'], $sendReport['emailFailed'])."\n";
