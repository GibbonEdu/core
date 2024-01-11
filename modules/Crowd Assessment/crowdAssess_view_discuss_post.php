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

use Gibbon\Forms\Form;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Crowd Assessment/crowdAssess_view_discuss_post.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get class variable
    $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
    $gibbonPlannerEntryID = $_GET['gibbonPlannerEntryID'] ?? '';
    $gibbonPlannerEntryHomeworkID = $_GET['gibbonPlannerEntryHomeworkID'] ?? '';

    $urlParams = ['gibbonPlannerEntryID' => $gibbonPlannerEntryID, 'gibbonPersonID' => $gibbonPersonID, 'gibbonPlannerEntryHomeworkID' => $gibbonPlannerEntryHomeworkID];
    $page->breadcrumbs
        ->add(__('View All Assessments'), 'crowdAssess.php')
        ->add(__('View Assessment'), 'crowdAssess_view.php', $urlParams)
        ->add(__('Discuss'),'crowdAssess_view_discuss.php', $urlParams)
        ->add(__('Add Post'));

    if ($gibbonPersonID == '' or $gibbonPlannerEntryID == '' or $gibbonPlannerEntryHomeworkID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $and = " AND gibbonPlannerEntryID=$gibbonPlannerEntryID";
    $sql = getLessons($guid, $connection2, $and);
    $lesson = $pdo->select($sql[1], $sql[0])->fetch();

    if (empty($lesson)) {
        $page->addError(__('The selected record does not exist, or you do not have access to it.'));
        return;
    }

    $role = getCARole($guid, $connection2, $lesson['gibbonCourseClassID']);
    $replyTo = $_GET['replyTo'] ?? null;

    // Get Student work
    $sqlList = getStudents($guid, $connection2, $role, $lesson['gibbonCourseClassID'], $lesson['homeworkCrowdAssessOtherTeachersRead'], $lesson['homeworkCrowdAssessOtherParentsRead'], $lesson['homeworkCrowdAssessSubmitterParentsRead'], $lesson['homeworkCrowdAssessClassmatesParentsRead'], $lesson['homeworkCrowdAssessOtherStudentsRead'], $lesson['homeworkCrowdAssessClassmatesRead'], " AND gibbonPerson.gibbonPersonID=$gibbonPersonID");

    if (empty($sqlList)) {
        $page->addError(__('The selected record does not exist, or you do not have access to it.'));
        return;
    }

    $student = $pdo->select($sqlList[1], $sqlList[0])->fetch();
    if (empty($student)) {
        $page->addError(__('There is currently no work to assess.'));
        return;
    }

    // FORM
    $form = Form::create('crowdAssessment', $session->get('absoluteURL').'/modules/'.$session->get('module')."/crowdAssess_view_discuss_postProcess.php?gibbonPlannerEntryID=$gibbonPlannerEntryID&gibbonPlannerEntryHomeworkID=$gibbonPlannerEntryHomeworkID&address=".$_GET['q']."&gibbonPersonID=$gibbonPersonID&replyTo=$replyTo");

    $form->addHiddenValue('address', $session->get('address'));

    $column = $form->addRow()->addColumn();
        $column->addLabel('commentLabel', __('Write your comment below:'));
        $column->addEditor('comment', $guid)->setRows(10)->required();

    $form->addRow()->addSubmit();

    echo $form->getOutput();
}
