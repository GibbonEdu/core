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

@session_start();

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Crowd Assessment/crowdAssess_view.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/crowdAssess.php'>".__($guid, 'View All Assessments')."</a> > </div><div class='trailEnd'>".__($guid, 'View Assessment').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Get class variable
    $gibbonPlannerEntryID = $_GET['gibbonPlannerEntryID'];
    if ($gibbonPlannerEntryID == '') {
        echo "<div class='warning'>";
        echo __($guid, 'You have not specified one or more required parameters.');
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

            echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
            echo '<tr>';
            echo "<td style='width: 34%; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Class').'</span><br/>';
            echo $row['course'].'.'.$row['class'];
            echo '</td>';
            echo "<td style='width: 33%; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Name').'</span><br/>';
            echo $row['name'];
            echo '</td>';
            echo "<td style='width: 34%; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Date').'</span><br/>';
            echo dateConvertBack($guid, $row['date']);
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo "<td style='padding-top: 15px; width: 34%; vertical-align: top' colspan=3>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Homework Details').'</span><br/>';
            echo $row['homeworkDetails'];
            echo '</td>';
            echo '</tr>';
            echo '</table>';

            $role = getCARole($guid, $connection2, $row['gibbonCourseClassID']);

            $sqlList = getStudents($guid, $connection2, $role, $row['gibbonCourseClassID'], $row['homeworkCrowdAssessOtherTeachersRead'], $row['homeworkCrowdAssessOtherParentsRead'], $row['homeworkCrowdAssessSubmitterParentsRead'], $row['homeworkCrowdAssessClassmatesParentsRead'], $row['homeworkCrowdAssessOtherStudentsRead'], $row['homeworkCrowdAssessClassmatesRead']);

            //Return $sqlList as table
            if ($sqlList[1] != '') {
                try {
                    $resultList = $connection2->prepare($sqlList[1]);
                    $resultList->execute($sqlList[0]);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($resultList->rowCount() < 1) {
                    echo "<div class='error'>";
                    echo 'There is currently no work to assess.';
                    echo '</div>';
                } else {
                    echo "<table cellspacing='0' style='width: 100%'>";
                    echo "<tr class='head'>";
                    echo '<th>';
                    echo __($guid, 'Student');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Read');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Star');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Comments');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Discuss');
                    echo '</th>';
                    echo '</tr>';

                    $count = 0;
                    $rowNum = 'odd';
                    while ($rowList = $resultList->fetch()) {
                        if ($count % 2 == 0) {
                            $rowNum = 'even';
                        } else {
                            $rowNum = 'odd';
                        }
                        ++$count;

                        //COLOR ROW BY STATUS!
                        echo "<tr class=$rowNum>";
                        echo '<td>';
                        echo "<a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=".$rowList['gibbonPersonID']."'>".formatName('', $rowList['preferredName'], $rowList['surname'], 'Student', true).'</a>';
                        echo '</td>';
                        echo '<td>';
                        $rowWork = null;
                        try {
                            $dataWork = array('gibbonPlannerEntryID' => $gibbonPlannerEntryID, 'gibbonPersonID' => $rowList['gibbonPersonID']);
                            $sqlWork = 'SELECT * FROM gibbonPlannerEntryHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID ORDER BY count DESC';
                            $resultWork = $connection2->prepare($sqlWork);
                            $resultWork->execute($dataWork);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        if ($resultWork->rowCount() > 0) {
                            $rowWork = $resultWork->fetch();

                            if ($rowWork['status'] == 'Exemption') {
                                $linkText = 'Exemption';
                            } elseif ($rowWork['version'] == 'Final') {
                                $linkText = 'Final';
                            } else {
                                $linkText = 'Draft'.$rowWork['count'];
                            }

                            if ($rowWork['type'] == 'File') {
                                echo "<span title='".$rowWork['version'].'. Submitted at '.substr($rowWork['timestamp'], 11, 5).' on '.dateConvertBack($guid, substr($rowWork['timestamp'], 0, 10))."'><a href='".$_SESSION[$guid]['absoluteURL'].'/'.$rowWork['location']."'>$linkText</a></span>";
                            } elseif ($rowWork['type'] == 'Link') {
                                echo "<span title='".$rowWork['version'].'. Submitted at '.substr($rowWork['timestamp'], 11, 5).' on '.dateConvertBack($guid, substr($rowWork['timestamp'], 0, 10))."'><a target='_blank' href='".$rowWork['location']."'>$linkText</a></span>";
                            } else {
                                echo "<span title='Recorded at ".substr($rowWork['timestamp'], 11, 5).' on '.dateConvertBack($guid, substr($rowWork['timestamp'], 0, 10))."'>$linkText</span>";
                            }
                        }
                        echo '</td>';
                        echo '<td>';
                        if ($rowWork['gibbonPlannerEntryHomeworkID'] != '' and $rowList['gibbonPersonID'] != $_SESSION[$guid]['gibbonPersonID'] and $rowWork['status'] != 'Exemption') {
                            $likesGiven = countLikesByContextAndGiver($connection2, 'Crowd Assessment', 'gibbonPlannerEntryHomeworkID', $rowWork['gibbonPlannerEntryHomeworkID'], $_SESSION[$guid]['gibbonPersonID']);
                            if ($likesGiven != 1) {
                                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/modules/Crowd Assessment/crowdAssess_viewProcess.php?gibbonPlannerEntryID=$gibbonPlannerEntryID&gibbonPlannerEntryHomeworkID=".$rowWork['gibbonPlannerEntryHomeworkID'].'&address='.$_GET['q'].'&gibbonPersonID='.$rowList['gibbonPersonID']."'><img src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/like_off.png'></a>";
                            } else {
                                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/modules/Crowd Assessment/crowdAssess_viewProcess.php?gibbonPlannerEntryID=$gibbonPlannerEntryID&gibbonPlannerEntryHomeworkID=".$rowWork['gibbonPlannerEntryHomeworkID'].'&address='.$_GET['q'].'&gibbonPersonID='.$rowList['gibbonPersonID']."'><img src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/like_on.png'></a>";
                            }

                            $likesTotal = countLikesByContext($connection2, 'Crowd Assessment', 'gibbonPlannerEntryHomeworkID', $rowWork['gibbonPlannerEntryHomeworkID']);
                            echo ' x '.$likesTotal;
                        }
                        echo '</td>';
                        echo '<td>';
                        $dataDiscuss = array('gibbonPlannerEntryHomeworkID' => $rowWork['gibbonPlannerEntryHomeworkID']);
                        $sqlDiscuss = 'SELECT gibbonCrowdAssessDiscuss.*, title, surname, preferredName, category FROM gibbonCrowdAssessDiscuss JOIN gibbonPerson ON (gibbonCrowdAssessDiscuss.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE gibbonPlannerEntryHomeworkID=:gibbonPlannerEntryHomeworkID';
                        $resultDiscuss = $connection2->prepare($sqlDiscuss);
                        $resultDiscuss->execute($dataDiscuss);
                        echo $resultDiscuss->rowCount();
                        echo '</td>';
                        echo '<td>';
                        if ($rowWork['gibbonPlannerEntryHomeworkID'] != '' and $rowWork['status'] != 'Exemption') {
                            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/crowdAssess_view_discuss.php&gibbonPlannerEntryID=$gibbonPlannerEntryID&gibbonPlannerEntryHomeworkID=".$rowWork['gibbonPlannerEntryHomeworkID'].'&gibbonPersonID='.$rowList['gibbonPersonID']."'><img title='".__($guid, 'View')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a> ";
                        }
                        echo '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';
                }
            }
        }
    }
}
