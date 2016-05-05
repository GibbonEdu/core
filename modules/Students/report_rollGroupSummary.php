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

if (isActionAccessible($guid, $connection2, '/modules/Students/report_rollGroupSummary.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Roll Group Summary').'</div>';
    echo '</div>';

    $today = time();

    //Get roll groups in current school year
    try {
        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
        $sql = 'SELECT * FROM gibbonRollGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
    }

    //Get all students
    try {
        $dataList = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
        $sqlList = "SELECT gibbonRollGroup.name AS rollGroup, dob, gender FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID AND status='FULL' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY rollGroup";
        $resultList = $connection2->prepare($sqlList);
        $resultList->execute($dataList);
    } catch (PDOException $e) {
    }

    $everything = array();
    $count = 0;
    while ($rowList = $resultList->fetch()) {
        $everything[$count][0] = $rowList['dob'];
        $everything[$count][1] = $rowList['gender'];
        $everything[$count][2] = $rowList['rollGroup'];
        ++$count;
    }

    if ($result->rowCount() == 0) {
        echo "<div class='error'>";
        echo __($guid, 'There are no records to display.');
        echo '</div>';
    } else {
        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo '<th>';
        echo __($guid, 'Roll Group');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Mean Age');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Male');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Female');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Total');
        echo '</th>';
        echo '</tr>';

        $count = 0;
        $rowNum = 'odd';
        while ($row = $result->fetch()) {
            if ($count % 2 == 0) {
                $rowNum = 'even';
            } else {
                $rowNum = 'odd';
            }
            ++$count;

            //COLOR ROW BY STATUS!
            echo "<tr class=$rowNum>";
            echo '<td>';
            echo $row['name'];
            echo '</td>';
            echo '<td>';
            $cellCount = 0;
            $total = 0;
            foreach ($everything as $thing) {
                if ($thing[2] == $row['name']) {
                    ++$cellCount;
                    $total += (($today - strtotime($thing[0])) / 31556926);
                }
            }
            if ($cellCount != 0) {
                echo round(($total / $cellCount), 1);
            }
            echo '</td>';
            echo '<td>';
            $cellCount = 0;
            foreach ($everything as $thing) {
                if ($thing[1] == 'M' and $thing[2] == $row['name']) {
                    ++$cellCount;
                }
            }
            if ($cellCount != 0) {
                echo $cellCount;
            }
            echo '</td>';
            echo '<td>';
            $cellCount = 0;
            foreach ($everything as $thing) {
                if ($thing[1] == 'F' and $thing[2] == $row['name']) {
                    ++$cellCount;
                }
            }
            if ($cellCount != 0) {
                echo $cellCount;
            }
            echo '</td>';
            echo '<td>';
            $cellCount = 0;
            foreach ($everything as $thing) {
                if ($thing[2] == $row['name']) {
                    ++$cellCount;
                }
            }
            if ($cellCount != 0) {
                echo '<b>'.$cellCount.'</b>';
            }
            echo '</td>';
            echo '</tr>';
        }
        echo "<tr style='background-color: #FFD2A9'>";
        echo '<td>';
        echo '<b>'.__($guid, 'All Roll Groups').'</b>';
        echo '</td>';
        echo '<td>';
        $cellCount = 0;
        $total = 0;
        foreach ($everything as $thing) {
            ++$cellCount;
            $total += (($today - strtotime($thing[0])) / 31556926);
        }
        if ($cellCount != 0) {
            echo '<b>'.round(($total / $cellCount), 1).'</b>';
        }
        echo '</td>';
        echo '<td>';
        $cellCount = 0;
        foreach ($everything as $thing) {
            if ($thing[1] == 'M') {
                ++$cellCount;
            }
        }
        if ($cellCount != 0) {
            echo '<b>'.$cellCount.'</b>';
        }
        echo '</td>';
        echo '<td>';
        $cellCount = 0;
        foreach ($everything as $thing) {
            if ($thing[1] == 'F') {
                ++$cellCount;
            }
        }
        if ($cellCount != 0) {
            echo '<b>'.$cellCount.'</b>';
        }
        echo '</td>';
        echo '<td>';
        if (count($everything) != 0) {
            echo '<b>'.count($everything).'</b>';
        }
        echo '</td>';
        echo '</tr>';
        echo '</table>';
    }
}
