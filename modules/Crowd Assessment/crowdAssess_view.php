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

if (isActionAccessible($guid, $connection2, '/modules/Crowd Assessment/crowdAssess_view.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed
    $page->breadcrumbs
        ->add(__('View All Assessments'), 'crowdAssess.php')
        ->add(__('View Assessment'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Get class variable
    $gibbonPlannerEntryID = $_GET['gibbonPlannerEntryID'];
    if ($gibbonPlannerEntryID == '') {
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

            echo "<table class='smallIntBorder mb-4' cellspacing='0' style='width: 100%'>";
            echo '<tr>';
            echo "<td style='width: 34%; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__('Class').'</span><br/>';
            echo $row['course'].'.'.$row['class'];
            echo '</td>';
            echo "<td style='width: 33%; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__('Name').'</span><br/>';
            echo $row['name'];
            echo '</td>';
            echo "<td style='width: 34%; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__('Date').'</span><br/>';
            echo dateConvertBack($guid, $row['date']);
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo "<td style='padding-top: 15px; width: 34%; vertical-align: top' colspan=3>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__('Details').'</span><br/>';
            echo $row['homeworkDetails'];
            echo '</td>';
            echo '</tr>';
            echo '</table>';

            $role = getCARole($guid, $connection2, $row['gibbonCourseClassID']);

            $sqlList = getStudents($guid, $connection2, $role, $row['gibbonCourseClassID'], $row['homeworkCrowdAssessOtherTeachersRead'], $row['homeworkCrowdAssessOtherParentsRead'], $row['homeworkCrowdAssessSubmitterParentsRead'], $row['homeworkCrowdAssessClassmatesParentsRead'], $row['homeworkCrowdAssessOtherStudentsRead'], $row['homeworkCrowdAssessClassmatesRead']);

            //Return $sqlList as table
            if ($sqlList[1] != '') {
                
                    $resultList = $connection2->prepare($sqlList[1]);
                    $resultList->execute($sqlList[0]);

                if ($resultList->rowCount() < 1) {
                    echo "<div class='error'>";
                    echo 'There is currently no work to assess.';
                    echo '</div>';
                } else {
                    echo "<table cellspacing='0' style='width: 100%'>";
                    echo "<tr class='head'>";
                    echo '<th>';
                    echo __('Student');
                    echo '</th>';
                    echo '<th>';
                    echo __('Read');
                    echo '</th>';
                    echo '<th>';
                    echo __('Comments');
                    echo '</th>';
                    echo '<th>';
                    echo __('Discuss');
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
                        echo "<a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=".$rowList['gibbonPersonID']."'>".Format::name('', $rowList['preferredName'], $rowList['surname'], 'Student', true).'</a>';
                        echo '</td>';
                        echo '<td>';
                        $rowWork = null;
                        
                            $dataWork = array('gibbonPlannerEntryID' => $gibbonPlannerEntryID, 'gibbonPersonID' => $rowList['gibbonPersonID']);
                            $sqlWork = 'SELECT * FROM gibbonPlannerEntryHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID ORDER BY count DESC';
                            $resultWork = $connection2->prepare($sqlWork);
                            $resultWork->execute($dataWork);
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
                        $dataDiscuss = array('gibbonPlannerEntryHomeworkID' => $rowWork['gibbonPlannerEntryHomeworkID'] ?? '');
                        $sqlDiscuss = 'SELECT gibbonCrowdAssessDiscuss.*, title, surname, preferredName, category FROM gibbonCrowdAssessDiscuss JOIN gibbonPerson ON (gibbonCrowdAssessDiscuss.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE gibbonPlannerEntryHomeworkID=:gibbonPlannerEntryHomeworkID';
                        $resultDiscuss = $connection2->prepare($sqlDiscuss);
                        $resultDiscuss->execute($dataDiscuss);
                        echo $resultDiscuss->rowCount();
                        echo '</td>';
                        echo '<td>';
                        if (!empty($rowWork['gibbonPlannerEntryHomeworkID']) and $rowWork['status'] != 'Exemption') {
                            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/crowdAssess_view_discuss.php&gibbonPlannerEntryID=$gibbonPlannerEntryID&gibbonPlannerEntryHomeworkID=".$rowWork['gibbonPlannerEntryHomeworkID'].'&gibbonPersonID='.$rowList['gibbonPersonID']."'><img title='".__('View')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a> ";
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
