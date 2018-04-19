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

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Crowd Assessment/crowdAssess_view_discuss.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/crowdAssess.php'>".__($guid, 'View All Assessments')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/crowdAssess_view.php&gibbonPlannerEntryID='.$_GET['gibbonPlannerEntryID']."'>".__($guid, 'View Assessment')."</a> > </div><div class='trailEnd'>".__($guid, 'Discuss').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Get class variable
    $gibbonPersonID = $_GET['gibbonPersonID'];
    $gibbonPlannerEntryID = $_GET['gibbonPlannerEntryID'];
    $gibbonPlannerEntryHomeworkID = $_GET['gibbonPlannerEntryHomeworkID'];
    if ($gibbonPersonID == '' or $gibbonPlannerEntryID == '' or $gibbonPlannerEntryHomeworkID == '') {
        echo "<div class='warning'>";
        echo 'Student, lesson or homework has not been specified .';
        echo '</div>';
    }
    //Check existence of and access to this class.
    else {
        $and = " AND gibbonPlannerEntryID=$gibbonPlannerEntryID";
        $sql = getLessons($guid, $connection2, $and);
        try {
            $result = $connection2->prepare($sql[1]);
            $result->execute($sql[0]);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The selected record does not exist, or you do not have access to it.');
            echo '</div>';
        } else {
            $row = $result->fetch();

            $role = getCARole($guid, $connection2, $row['gibbonCourseClassID']);

            $sqlList = getStudents($guid, $connection2, $role, $row['gibbonCourseClassID'], $row['homeworkCrowdAssessOtherTeachersRead'], $row['homeworkCrowdAssessOtherParentsRead'], $row['homeworkCrowdAssessSubmitterParentsRead'], $row['homeworkCrowdAssessClassmatesParentsRead'], $row['homeworkCrowdAssessOtherStudentsRead'], $row['homeworkCrowdAssessClassmatesRead'], " AND gibbonPerson.gibbonPersonID=$gibbonPersonID");

            if ($sqlList[1] != '') {
                try {
                    $resultList = $connection2->prepare($sqlList[1]);
                    $resultList->execute($sqlList[0]);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($resultList->rowCount() != 1) {
                    echo "<div class='error'>";
                    echo __($guid, 'There is currently no work to assess.');
                    echo '</div>';
                } else {
                    $rowList = $resultList->fetch();

                    //Get details of homework
                    try {
                        $dataWork = array('gibbonPlannerEntryID' => $gibbonPlannerEntryID, 'gibbonPersonID' => $gibbonPersonID, 'gibbonPlannerEntryHomeworkID' => $gibbonPlannerEntryHomeworkID);
                        $sqlWork = 'SELECT * FROM gibbonPlannerEntryHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID AND gibbonPlannerEntryHomeworkID=:gibbonPlannerEntryHomeworkID ORDER BY count DESC';
                        $resultWork = $connection2->prepare($sqlWork);
                        $resultWork->execute($dataWork);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }

                    if ($resultWork->rowCount() != 1) {
                        echo "<div class='error'>";
                        echo __($guid, 'There is currently no work to assess.');
                        echo '</div>';
                    } else {
                        $rowWork = $resultWork->fetch();

                        echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                        echo '<tr>';
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>Student</span><br/>";
                        echo "<a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=".$rowList['gibbonPersonID']."'>".formatName('', $rowList['preferredName'], $rowList['surname'], 'Student').'</a>';
                        echo '</td>';
                        echo "<td style='width: 34%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>Version</span><br/>";
                        if ($rowWork['version'] == 'Final') {
                            $linkText = __($guid, 'Final');
                        } else {
                            $linkText = __($guid, 'Draft').$rowWork['count'];
                        }

                        if ($rowWork['type'] == 'File') {
                            echo "<span title='".$rowWork['version'].'. Submitted at '.substr($rowWork['timestamp'], 11, 5).' on '.dateConvertBack($guid, substr($rowWork['timestamp'], 0, 10))."'><a href='".$_SESSION[$guid]['absoluteURL'].'/'.$rowWork['location']."'>$linkText</a></span>";
                        } else {
                            echo "<span title='".$rowWork['version'].'. Submitted at '.substr($rowWork['timestamp'], 11, 5).' on '.dateConvertBack($guid, substr($rowWork['timestamp'], 0, 10))."'><a target='_blank' href='".$rowWork['location']."'>$linkText</a></span>";
                        }
                        echo '</td>';
                        echo "<td style='width: 34%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>Like Count</span><br/>";
                        $likesTotal = countLikesByContext($connection2, 'Crowd Assessment', 'gibbonPlannerEntryHomeworkID', $rowWork['gibbonPlannerEntryHomeworkID']);
                        echo ' x '.$likesTotal;
                        echo '</td>';
                        echo '</tr>';
                        echo '</table>';

                        echo "<div style='margin: 0px' class='linkTop'>";
                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/crowdAssess_view_discuss_post.php&gibbonPersonID=$gibbonPersonID&gibbonPlannerEntryID=$gibbonPlannerEntryID&gibbonPlannerEntryHomeworkID=$gibbonPlannerEntryHomeworkID'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
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
