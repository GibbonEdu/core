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

if (isActionAccessible($guid, $connection2, '/modules/Crowd Assessment/crowdAssess.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $page->breadcrumbs->add(__('View All Assessments'));
    
    $sql = getLessons($guid, $connection2);

    
        $result = $connection2->prepare($sql[1]);
        $result->execute($sql[0]);

    echo '<p>';
    echo __('The list below shows all lessons in which there is work that you can crowd assess.');
    echo '</p>';

    if ($result->rowCount() < 1) {
        echo "<div class='error'>";
        echo __('There are currently no lessons to for you to crowd assess.');
        echo '</div>';
    } else {
        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo '<th>';
        echo __('Class');
        echo '</th>';
        echo '<th>';
        echo __('Lesson').'</br>';
        echo "<span style='font-size: 85%; font-style: italic'>".__('Unit').'</span>';
        echo '</th>';
        echo '<th>';
        echo __('Date');
        echo '</th>';
        echo '<th>';
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
            ++$count;

            //COLOR ROW BY STATUS!
            echo "<tr class=$rowNum>";
            echo '<td>';
            echo $row['course'].'.'.$row['class'];
            echo '</td>';
            echo '<td>';
            echo '<b>'.$row['name'].'</b><br/>';
            echo "<span style='font-size: 85%; font-style: italic'>";
            if ($row['gibbonUnitID'] != '') {
                
                    $dataUnit = array('gibbonUnitID' => $row['gibbonUnitID']);
                    $sqlUnit = 'SELECT * FROM gibbonUnit WHERE gibbonUnitID=:gibbonUnitID';
                    $resultUnit = $connection2->prepare($sqlUnit);
                    $resultUnit->execute($dataUnit);
                if ($resultUnit->rowCount() == 1) {
                    $rowUnit = $resultUnit->fetch();
                    echo $rowUnit['name'];
                }
            }
            echo '</span>';
            echo '</td>';
            echo '<td>';
            echo dateConvertBack($guid, $row['date']);
            echo '</td>';
            echo '<td>';
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/crowdAssess_view.php&gibbonPlannerEntryID='.$row['gibbonPlannerEntryID']."'><img title='".__('View Details')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a> ";
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
}
