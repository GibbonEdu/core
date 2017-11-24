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

if (isActionAccessible($guid, $connection2, '/modules/Timetable/spaceBooking_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        //Proceed!
        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Facility Bookings').'</div>';
        echo '</div>';

        if ($highestAction == 'Manage Facility Bookings_allBookings') {
            echo '<p>'.__($guid, 'This page allows you to create facility and library bookings, whilst managing bookings created by all users. Only current and future bookings are shown: past bookings are hidden.').'</p>';
        } else {
            echo '<p>'.__($guid, 'This page allows you to create and manage facility and library bookings. Only current and future changes are shown: past bookings are hidden.').'</p>';
        }

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
            if ($highestAction == 'Manage Facility Bookings_allBookings') {
                $data = array('date' => date('Y-m-d'));
                $sql = "(SELECT gibbonTTSpaceBooking.*, gibbonSpace.name AS name, surname, preferredName FROM gibbonTTSpaceBooking JOIN gibbonSpace ON (gibbonTTSpaceBooking.foreignKeyID=gibbonSpace.gibbonSpaceID) JOIN gibbonPerson ON (gibbonTTSpaceBooking.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE foreignKey='gibbonSpaceID' AND date>=:date) UNION (SELECT gibbonTTSpaceBooking.*, gibbonLibraryItem.name AS name, surname, preferredName FROM gibbonTTSpaceBooking JOIN gibbonLibraryItem ON (gibbonTTSpaceBooking.foreignKeyID=gibbonLibraryItem.gibbonLibraryItemID) JOIN gibbonPerson ON (gibbonTTSpaceBooking.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE foreignKey='gibbonLibraryItemID' AND date>=:date) ORDER BY date, name";
            } else {
                $data = array('date' => date('Y-m-d'), 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                $sql = "(SELECT gibbonTTSpaceBooking.*, gibbonSpace.name AS name, surname, preferredName FROM gibbonTTSpaceBooking JOIN gibbonSpace ON (gibbonTTSpaceBooking.foreignKeyID=gibbonSpace.gibbonSpaceID) JOIN gibbonPerson ON (gibbonTTSpaceBooking.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE foreignKey='gibbonSpaceID' AND date>=:date AND gibbonTTSpaceBooking.gibbonPersonID=:gibbonPersonID) UNION (SELECT gibbonTTSpaceBooking.*, gibbonLibraryItem.name AS name, surname, preferredName FROM gibbonTTSpaceBooking JOIN gibbonLibraryItem ON (gibbonTTSpaceBooking.foreignKeyID=gibbonLibraryItem.gibbonLibraryItemID) JOIN gibbonPerson ON (gibbonTTSpaceBooking.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE foreignKey='gibbonLibraryItemID' AND date>=:date AND gibbonTTSpaceBooking.gibbonPersonID=:gibbonPersonID) ORDER BY date, name";
            }
            $sqlPage = $sql.' LIMIT '.$_SESSION[$guid]['pagination'].' OFFSET '.(($page - 1) * $_SESSION[$guid]['pagination']);
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        echo "<div class='linkTop'>";
        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/spaceBooking_manage_add.php'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
        echo '</div>';

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
            echo __($guid, 'Date');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Facility');
            echo '</th>';
            if ($highestAction == 'Manage Facility Bookings_allBookings') {
                echo '<th>';
                echo __($guid, 'Person');
                echo '</th>';
            }
            echo '<th>';
            echo __($guid, 'Time');
            echo '</th>';
            echo '<th>';
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
                ++$count;

                //COLOR ROW BY STATUS!
                echo "<tr class=$rowNum>";
                echo '<td>';
                echo dateConvertBack($guid, $row['date']);
                echo '</td>';
                echo '<td>';
                echo $row['name'];
                echo '</td>';
                if ($highestAction == 'Manage Facility Bookings_allBookings') {
                    echo '<td>';
                    echo formatName('', $row['preferredName'], $row['surname'], 'Student', false);
                    echo '</td>';
                }
                echo '<td>';
                echo substr($row['timeStart'], 0, 5).' - '.substr($row['timeEnd'], 0, 5);
                echo '</td>';
                echo '<td>';
                echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/'.$_SESSION[$guid]['module'].'/spaceBooking_manage_delete.php&gibbonTTSpaceBookingID='.$row['gibbonTTSpaceBookingID']."&width=650&height=135'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';

            if ($result->rowCount() > $_SESSION[$guid]['pagination']) {
                printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]['pagination'], 'bottom');
            }
        }
    }
}
