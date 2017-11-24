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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/schoolYearTerm_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Terms').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    try {
        $data = array();
        $sql = 'SELECT gibbonSchoolYearTermID, gibbonSchoolYear.name AS yearName, gibbonSchoolYearTerm.name AS termName, gibbonSchoolYear.sequenceNumber AS yearSequenceNumber, gibbonSchoolYearTerm.sequenceNumber AS termSequenceNumber, nameShort, gibbonSchoolYearTerm.firstDay, gibbonSchoolYearTerm.lastDay FROM gibbonSchoolYear, gibbonSchoolYearTerm WHERE gibbonSchoolYear.gibbonSchoolYearID=gibbonSchoolYearTerm.gibbonSchoolYearID ORDER BY gibbonSchoolYear.sequenceNumber, gibbonSchoolYearTerm.sequenceNumber';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    echo "<div class='linkTop'>";
    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/schoolYearTerm_manage_add.php'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
    echo '</div>';

    if ($result->rowCount() < 1) {
        echo "<div class='error'>";
        echo __($guid, 'There are no records to display.');
        echo '</div>';
    } else {
        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo '<th>';
        echo __($guid, 'School Year');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Sequence');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Name');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Short Name');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Dates');
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
            echo $row['yearName'];
            echo '</td>';
            echo '<td>';
            echo $row['termSequenceNumber'];
            echo '</td>';
            echo '<td>';
            echo $row['termName'];
            echo '</td>';
            echo '<td>';
            echo $row['nameShort'];
            echo '</td>';
            echo '<td>';
            if ($row['firstDay'] != null and $row['lastDay'] != null) {
                echo dateConvertBack($guid, $row['firstDay']).' - '.dateConvertBack($guid, $row['lastDay']);
            }
            echo '</td>';
            echo '<td>';
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/schoolYearTerm_manage_edit.php&gibbonSchoolYearTermID='.$row['gibbonSchoolYearTermID']."'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
            echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/'.$_SESSION[$guid]['module'].'/schoolYearTerm_manage_delete.php&gibbonSchoolYearTermID='.$row['gibbonSchoolYearTermID']."&width=650&height=135'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a> ";
            echo '</td>';
            echo '</tr>';

            ++$count;
        }
        echo '</table>';
    }
}
