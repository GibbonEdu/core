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

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/ttDates_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/ttDates.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID']."'>".__($guid, 'Tie Days to Dates')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Days in Date').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
    $dateStamp = $_GET['dateStamp'];
    if ($gibbonSchoolYearID == '' or $dateStamp == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('date' => date('Y-m-d', $dateStamp));
            $sql = 'SELECT gibbonTTDay.gibbonTTDayID, gibbonTTDay.name AS dayName, gibbonTT.name AS ttName FROM gibbonTTDayDate JOIN gibbonTTDay ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) JOIN gibbonTT ON (gibbonTTDay.gibbonTTID=gibbonTT.gibbonTTID) WHERE date=:date';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        echo "<div class='linkTop'>";
        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/ttDates_edit_add.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID']."&dateStamp=$dateStamp'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
        echo '</div>';

        if ($result->rowCount() < 1) {
            echo "<div class='error'>";
            echo __($guid, 'There are no records to display.');
            echo '</div>';
        } else {
            echo "<table cellspacing='0' style='width: 100%'>";
            echo "<tr class='head'>";
            echo '<th>';
            echo __($guid, 'Timetable');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Day');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Actions');
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
                echo $row['ttName'];
                echo '</td>';
                echo '<td>';
                echo $row['dayName'];
                echo '</td>';
                echo '<td>';
                echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/'.$_SESSION[$guid]['module'].'/ttDates_edit_delete.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID']."&dateStamp=$dateStamp&gibbonTTDayID=".$row['gibbonTTDayID']."&width=650&height=135'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a> ";
                echo '</td>';
                echo '</tr>';

                ++$count;
            }
            echo '</table>';
        }
    }
}
