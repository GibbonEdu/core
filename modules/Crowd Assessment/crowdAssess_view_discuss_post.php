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

use Gibbon\Forms\Form;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Crowd Assessment/crowdAssess_view_discuss_post.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get class variable
    $gibbonPersonID = $_GET['gibbonPersonID'];
    $gibbonPlannerEntryID = $_GET['gibbonPlannerEntryID'];
    $gibbonPlannerEntryHomeworkID = $_GET['gibbonPlannerEntryHomeworkID'];

    $urlParams = ['gibbonPlannerEntryID' => $gibbonPlannerEntryID, 'gibbonPersonID' => $gibbonPersonID, 'gibbonPlannerEntryHomeworkID' => $gibbonPlannerEntryHomeworkID];
    $page->breadcrumbs
        ->add(__('View All Assessments'), 'crowdAssess.php')
        ->add(__('View Assessment'), 'crowdAssess_view.php', $urlParams)
        ->add(__('Discuss'),'crowdAssess_view_discuss.php', $urlParams)
        ->add(__('Add Post'));    
    
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    if ($gibbonPersonID == '' or $gibbonPlannerEntryID == '' or $gibbonPlannerEntryHomeworkID == '') {
        echo "<div class='warning'>";
        echo __('You have not specified one or more required parameters.');
        echo '</div>';
    }
    //Check existence of and access to this class.
    else {
        $and = " AND gibbonPlannerEntryID=$gibbonPlannerEntryID";
        $sql = getLessons($guid, $connection2, $and);
        
            $result = $connection2->prepare($sql[1]);
            $result->execute($sql[0]);

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __('The selected record does not exist, or you do not have access to it.');
            echo '</div>';
        } else {
            $row = $result->fetch();

            $role = getCARole($guid, $connection2, $row['gibbonCourseClassID']);
            $replyTo = null;
            if (isset($_GET['replyTo'])) {
                $replyTo = $_GET['replyTo'];
            }
            $sqlList = getStudents($guid, $connection2, $role, $row['gibbonCourseClassID'], $row['homeworkCrowdAssessOtherTeachersRead'], $row['homeworkCrowdAssessOtherParentsRead'], $row['homeworkCrowdAssessSubmitterParentsRead'], $row['homeworkCrowdAssessClassmatesParentsRead'], $row['homeworkCrowdAssessOtherStudentsRead'], $row['homeworkCrowdAssessClassmatesRead'], " AND gibbonPerson.gibbonPersonID=$gibbonPersonID");

            if ($sqlList[1] != '') {
                
                    $resultList = $connection2->prepare($sqlList[1]);
                    $resultList->execute($sqlList[0]);

                if ($resultList->rowCount() != 1) {
                    echo "<div class='error'>";
                    echo __('There is currently no work to assess.');
                    echo '</div>';
                } else {
                    $rowList = $resultList->fetch();

                    $form = Form::create('courseEdit', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/crowdAssess_view_discuss_postProcess.php?gibbonPlannerEntryID=$gibbonPlannerEntryID&gibbonPlannerEntryHomeworkID=$gibbonPlannerEntryHomeworkID&address=".$_GET['q']."&gibbonPersonID=$gibbonPersonID&replyTo=$replyTo");
                
                    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

                    $column = $form->addRow()->addColumn();
                        $column->addLabel('commentLabel', __('Write your comment below:'));
                        $column->addEditor('comment', $guid)->setRows(10)->required();

                    $form->addRow()->addSubmit();

                    echo $form->getOutput();
                }
            }
        }
    }
}
