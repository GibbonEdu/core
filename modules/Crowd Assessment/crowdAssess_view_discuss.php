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

use Gibbon\Services\Format;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Crowd Assessment/crowdAssess_view_discuss.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get class variable
    $gibbonPersonID = $_GET['gibbonPersonID'];
    $gibbonPlannerEntryID = $_GET['gibbonPlannerEntryID'];
    $gibbonPlannerEntryHomeworkID = $_GET['gibbonPlannerEntryHomeworkID'];

    $urlParams = ['gibbonPlannerEntryID' => $gibbonPlannerEntryID];
    $page->breadcrumbs
        ->add(__('View All Assessments'), 'crowdAssess.php')
        ->add(__('View Assessment'), 'crowdAssess_view.php', $urlParams)
        ->add(__('Discuss'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    if ($gibbonPersonID == '' or $gibbonPlannerEntryID == '' or $gibbonPlannerEntryHomeworkID == '') {
        echo "<div class='warning'>";
        echo 'Student, lesson or homework has not been specified .';
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

                    //Get details of homework
                    
                        $dataWork = array('gibbonPlannerEntryID' => $gibbonPlannerEntryID, 'gibbonPersonID' => $gibbonPersonID, 'gibbonPlannerEntryHomeworkID' => $gibbonPlannerEntryHomeworkID);
                        $sqlWork = 'SELECT * FROM gibbonPlannerEntryHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID AND gibbonPlannerEntryHomeworkID=:gibbonPlannerEntryHomeworkID ORDER BY count DESC';
                        $resultWork = $connection2->prepare($sqlWork);
                        $resultWork->execute($dataWork);

                    if ($resultWork->rowCount() != 1) {
                        echo "<div class='error'>";
                        echo __('There is currently no work to assess.');
                        echo '</div>';
                    } else {
                        $rowWork = $resultWork->fetch();

                        echo "<table class='smallIntBorder mb-4' cellspacing='0' style='width: 100%'>";
                        echo '<tr>';
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>Student</span><br/>";
                        echo "<a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=".$rowList['gibbonPersonID']."'>".Format::name('', $rowList['preferredName'], $rowList['surname'], 'Student').'</a>';
                        echo '</td>';
                        echo "<td style='width: 34%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>Version</span><br/>";
                        if ($rowWork['version'] == 'Final') {
                            $linkText = __('Final');
                        } else {
                            $linkText = __('Draft').$rowWork['count'];
                        }

                        if ($rowWork['type'] == 'File') {
                            echo "<span title='".$rowWork['version'].'. Submitted at '.substr($rowWork['timestamp'], 11, 5).' on '.dateConvertBack($guid, substr($rowWork['timestamp'], 0, 10))."'><a href='".$_SESSION[$guid]['absoluteURL'].'/'.$rowWork['location']."'>$linkText</a></span>";
                        } else {
                            echo "<span title='".$rowWork['version'].'. Submitted at '.substr($rowWork['timestamp'], 11, 5).' on '.dateConvertBack($guid, substr($rowWork['timestamp'], 0, 10))."'><a target='_blank' href='".$rowWork['location']."'>$linkText</a></span>";
                        }
                        echo '</td>';
                        echo '</tr>';
                        echo '</table>';

                        echo "<div style='margin: 0px' class='linkTop'>";
                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/crowdAssess_view_discuss_post.php&gibbonPersonID=$gibbonPersonID&gibbonPlannerEntryID=$gibbonPlannerEntryID&gibbonPlannerEntryHomeworkID=$gibbonPlannerEntryHomeworkID'>".__('Add')."<img style='margin-left: 5px' title='".__('Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
                        echo '</div>';

                        //Get discussion
                        echo getThread($guid, $connection2, $rowWork['gibbonPlannerEntryHomeworkID'], null, 0, null, $gibbonPersonID, $gibbonPlannerEntryID);

                        echo '<br/><br/>';
                    }
                }
            }
        }
    }
}
