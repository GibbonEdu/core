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
require_once __DIR__ . '/moduleFunctions.php';

// common variables
$makeUnitsPublic = getSettingByScope($connection2, 'Planner', 'makeUnitsPublic');
$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
$gibbonUnitID = $_GET['gibbonUnitID'] ?? '';

$page->breadcrumbs->add(__('Learn With Us'));

if ($makeUnitsPublic != 'Y') {
    //Acess denied
    echo "<div class='error'>";
    echo __('Your request failed because you do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    if ($gibbonSchoolYearID == '') {
        
            $data = array();
            $sql = "SELECT * FROM gibbonSchoolYear WHERE status='Current'";
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __('The specified record does not exist.');
            echo '</div>';
        } else {
            $row = $result->fetch();
            $gibbonSchoolYearID = $row['gibbonSchoolYearID'];
            $gibbonSchoolYearName = $row['name'];
        }
    } else {
        
            $data = array('gibbonSchoolYearID' => $_GET['gibbonSchoolYearID']);
            $sql = 'SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __('The specified record does not exist.');
            echo '</div>';
        } else {
            $row = $result->fetch();
            $gibbonSchoolYearID = $row['gibbonSchoolYearID'];
            $gibbonSchoolYearName = $row['name'];
        }
    }

    echo '<h2>';
    echo $gibbonSchoolYearName;
    echo '</h2>';

    echo "<div class='linkTop'>";
        //Print year picker
        if (getPreviousSchoolYearID($gibbonSchoolYearID, $connection2) != false) {
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/units_public.php&gibbonSchoolYearID='.getPreviousSchoolYearID($gibbonSchoolYearID, $connection2)."'>".__('Previous Year').'</a> ';
        } else {
            echo __('Previous Year').' ';
        }
		echo ' | ';
		if (getNextSchoolYearID($gibbonSchoolYearID, $connection2) != false) {
			echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/units_public.php&gibbonSchoolYearID='.getNextSchoolYearID($gibbonSchoolYearID, $connection2)."'>".__('Next Year').'</a> ';
		} else {
			echo __('Next Year').' ';
		}
    echo '</div>';

    //Fetch units
    
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT gibbonUnitID, gibbonUnit.gibbonCourseID, nameShort, gibbonUnit.name, gibbonUnit.description, gibbonCourse.name AS course FROM gibbonUnit JOIN gibbonCourse ON gibbonUnit.gibbonCourseID=gibbonCourse.gibbonCourseID WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND sharedPublic='Y' ORDER BY course, name";
        $result = $connection2->prepare($sql);
        $result->execute($data);

    echo "<div class='linkTop'></div>";

    if ($result->rowCount() < 1) {
        echo "<div class='error'>";
        echo __('There are no records to display.');
        echo '</div>';
    } else {
        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo "<th style='width: 150px'>";
        echo __('Course');
        echo '</th>';
        echo "<th style='width: 150px'>";
        echo __('Name');
        echo '</th>';
        echo "<th style='width: 450px'>";
        echo __('Description');
        echo '</th>';
        echo "<th style='width: 50px'>";
        echo __('Actions');
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

            //COLOR ROW BY STATUS!
            echo "<tr class=$rowNum>";
            echo '<td>';
            echo $row['course'];
            echo '</td>';
            echo '<td>';
            echo $row['name'];
            echo '</td>';
            echo "<td style='max-width: 270px'>";
            echo $row['description'];
            echo '</td>';
            echo '<td>';
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/units_public_view.php&gibbonUnitID='.$row['gibbonUnitID']."&gibbonSchoolYearID=$gibbonSchoolYearID&sidebar=false'><img title='".__('View Details')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a>";
            echo '</td>';
            echo '</tr>';

            ++$count;
        }
        echo '</table>';
    }
}
