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

use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\CrowdAssessment\CrowdAssessDiscussGateway;
use Gibbon\Domain\Planner\PlannerEntryHomeworkGateway;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Crowd Assessment/crowdAssess_view.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed
    $page->breadcrumbs
        ->add(__('View All Assessments'), 'crowdAssess.php')
        ->add(__('View Assessment'));

    // Get lesson variable
    $gibbonPlannerEntryID = $_GET['gibbonPlannerEntryID'] ?? '';
    if (empty($gibbonPlannerEntryID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    // Check existence of and access to this lesson.
    $and = " AND gibbonPlannerEntryID=$gibbonPlannerEntryID";
    $sql = getLessons($guid, $connection2, $and);
    $lesson = $pdo->select($sql[1], $sql[0])->fetch();

    if (empty($lesson)) {
        $page->addError(__('The selected record does not exist, or you do not have access to it.'));
        return;
    }

    $plannerHomeworkGateway = $container->get(PlannerEntryHomeworkGateway::class);
    $crowdDiscussionGateway = $container->get(CrowdAssessDiscussGateway::class);

    // DETAILS
    $table = DataTable::createDetails('crowdAssessment');

    $table->addColumn('class', __('Class'))->format(Format::using('courseClassName', ['course', 'class']));
    $table->addColumn('name', __('Name'));
    $table->addColumn('date', __('Date'))->format(Format::using('date', 'date'));
    $table->addColumn('homeworkDetails', __('Details'))->addClass('col-span-3');

    echo $table->render([$lesson]);
    echo '<br/>';

    // Get Student work
    $role = getCARole($guid, $connection2, $lesson['gibbonCourseClassID']);

    $sqlList = getStudents($guid, $connection2, $role, $lesson['gibbonCourseClassID'], $lesson['homeworkCrowdAssessOtherTeachersRead'], $lesson['homeworkCrowdAssessOtherParentsRead'], $lesson['homeworkCrowdAssessSubmitterParentsRead'], $lesson['homeworkCrowdAssessClassmatesParentsRead'], $lesson['homeworkCrowdAssessOtherStudentsRead'], $lesson['homeworkCrowdAssessClassmatesRead']);

    // DATA TABLE
    $students = $pdo->select($sqlList[1], $sqlList[0])->fetchAll();
    foreach ($students as $index => $student) {
        $homework = $plannerHomeworkGateway->selectBy(['gibbonPlannerEntryID' => $gibbonPlannerEntryID, 'gibbonPersonID' => $student['gibbonPersonID']])->fetch();
        $discuss = $crowdDiscussionGateway->selectDiscussionByHomeworkID($homework['gibbonPlannerEntryHomeworkID'] ?? '');

        $students[$index]['homework'] = $homework;
        $students[$index]['comments'] = $discuss->rowCount() ?? 0;
    }

    $table = DataTable::create('crowdAssessmentStudents');
    $table->addMetaData('blankSlate', __('There is currently no work to assess.'));

    $highestStudentAction = getHighestGroupedAction($guid, '/modules/Students/student_view_details.php', $connection2);
    $canViewProfile = !empty($highestStudentAction) && ($highestStudentAction == 'View Student Profile_brief' || stripos($highestStudentAction, 'full') !== false);

    $table->addColumn('name', __('Student'))
        ->format(function ($values)  use ($canViewProfile) {
            return $canViewProfile
                ? Format::nameLinked($values['gibbonPersonID'], '', $values['preferredName'], $values['surname'], 'Student', true, true)
                : Format::name('', $values['preferredName'], $values['surname'], 'Student', true, true);
        });
        
    $table->addColumn('read', __('Read'))
        ->format(function($student) {
            $homework = $student['homework'];
            if (empty($homework)) return '';

            if ($homework['status'] == 'Exemption') {
                $linkText = 'Exemption';
            } elseif ($homework['version'] == 'Final') {
                $linkText = 'Final';
            } else {
                $linkText = 'Draft'.$homework['count'];
            }

            if ($homework['type'] == 'File' || $homework['type'] == 'Link') {
                $url = $homework['type'] == 'File'  ? './'.$homework['location'] : $homework['location'];
                $title = $homework['version'].' '.sprintf(__('Submitted at %1$s on %2$s'), substr($homework['timestamp'], 11, 5), Format::date($homework['timestamp']));
                return Format::link($url, $linkText, ['title' => $title]);
            } else {
                $title = sprintf(__('Recorded at %1$s on %2$s'), substr($homework['timestamp'], 11, 5), Format::date($homework['timestamp']));
                return Format::tooltip($linkText, $title);
            }
        });
    $table->addColumn('comments', __('Comments'));
    
    $table->addActionColumn()
        ->addParam('gibbonPersonID')
        ->addParam('gibbonPlannerEntryID', $gibbonPlannerEntryID)
        ->format(function ($student, $actions) {
            if (!empty($student['homework']) && $student['homework']['status'] != 'Exemption') {
                $actions->addAction('view', __('Discuss'))
                    ->addParam('gibbonPlannerEntryHomeworkID', $student['homework']['gibbonPlannerEntryHomeworkID'])
                    ->setURL('/modules/Crowd Assessment/crowdAssess_view_discuss.php');
            }
        });

    echo $table->render($students);
}
