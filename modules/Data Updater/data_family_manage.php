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

if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_family_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Family Data Updates').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Set pagination variable
    $page = 1;
    if (isset($_GET['page'])) {
        $page = $_GET['page'];
    }
    if ((!is_numeric($page)) or $page < 1) {
        $page = 1;
    }

    try {
        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
        $sql = 'SELECT gibbonFamilyUpdateID, gibbonFamily.name, timestamp, gibbonPersonIDUpdater, gibbonFamilyUpdate.status FROM gibbonFamilyUpdate JOIN gibbonFamily ON (gibbonFamily.gibbonFamilyID=gibbonFamilyUpdate.gibbonFamilyID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY status, timestamp DESC';
        $sqlPage = $sql.' LIMIT '.$_SESSION[$guid]['pagination'].' OFFSET '.(($page - 1) * $_SESSION[$guid]['pagination']);
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    if ($result->rowCount() < 1) {
        echo "<div class='error'>";
        echo __($guid, 'There are no records to display.');
        echo '</div>';
    } else {
        if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
            printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'top');
        }

        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo '<th>';
        echo __($guid, 'Target Family');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Requesting User');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Date & Time');
        echo '</th>';
        echo '<th>';
        echo __($guid, 'Status');
        echo '</th>';
        echo "<th style='width: 80px'>";
        echo __($guid, 'Actions');
        echo '</th>';
        echo '</tr>';

        $count = 0;
        $rowNum = 'odd';
        try {
            $resultPage = $connection2->prepare($sqlPage);
            $resultPage->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }
        while ($row = $resultPage->fetch()) {
            if ($count % 2 == 0) {
                $rowNum = 'even';
            } else {
                $rowNum = 'odd';
            }

            if ($row['status'] == 'Complete') {
                $rowNum = 'current';
            }

            ++$count;

            //COLOR ROW BY STATUS!
            echo "<tr class=$rowNum>";
            echo '<td>';
            echo $row['name'];
            echo '</td>';
            echo '<td>';
            try {
                $dataUpdater = array('gibbonPersonIDUpdater' => $row['gibbonPersonIDUpdater']);
                $sqlUpdater = 'SELECT gibbonPerson.title, gibbonPerson.surname, gibbonPerson.preferredName FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonIDUpdater';
                $resultUpdater = $connection2->prepare($sqlUpdater);
                $resultUpdater->execute($dataUpdater);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($resultUpdater->rowCount() == 1) {
                $rowUpdater = $resultUpdater->fetch();
                echo formatName($rowUpdater['title'], $rowUpdater['preferredName'], $rowUpdater['surname'], 'Parent', false);
            }
            echo '</td>';
            echo '<td>';
            echo dateConvertBack($guid, substr($row['timestamp'], 0, 10)).' at '.substr($row['timestamp'], 11, 5);
            echo '</td>';
            echo '<td>';
            echo $row['status'];
            echo '</td>';
            echo '<td>';
            if ($row['status'] == 'Pending') {
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/data_family_manage_edit.php&gibbonFamilyUpdateID='.$row['gibbonFamilyUpdateID']."' title='".__($guid, 'Edit')."'><img src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/'.$_SESSION[$guid]['module'].'/data_family_manage_delete.php&gibbonFamilyUpdateID='.$row['gibbonFamilyUpdateID']."&width=650&height=135'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
            }
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';

        if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
            printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'bottom');
        }
    }
}
