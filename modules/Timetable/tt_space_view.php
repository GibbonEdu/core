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

if (isActionAccessible($guid, $connection2, '/modules/Timetable/tt_space_view.php') == false) {
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
        $gibbonSpaceID = $_GET['gibbonSpaceID'];

        $search = null;
        if (isset($_GET['search'])) {
            $search = $_GET['search'];
        }

        $gibbonTTID = null;
        if (isset($_GET['gibbonTTID'])) {
            $gibbonTTID = $_GET['gibbonTTID'];
        }

        try {
            $data = array('gibbonSpaceID' => $gibbonSpaceID);
            $sql = 'SELECT * FROM gibbonSpace WHERE gibbonSpaceID=:gibbonSpaceID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo 'The specified room does not seem to exist.';
            echo '</div>';
        } else {
            $row = $result->fetch();

            echo "<div class='trail'>";
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/tt_space.php'>View Timetable by Facility</a> > </div><div class='trailEnd'>".$row['name'].'</div>';
            echo '</div>';

            if ($search != '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Timetable/tt_space.php&search=$seearch'>".__($guid, 'Back to Search Results').'</a>';
                echo '</div>';
            }

            $ttDate = null;
            if (isset($_POST['ttDate'])) {
                $ttDate = dateConvertToTimestamp(dateConvert($guid, $_POST['ttDate']));
            }

            if (isset($_POST['fromTT'])) {
                if ($_POST['fromTT'] == 'Y') {
                    if (isset($_POST['spaceBookingCalendar'])) {
                        if ($_POST['spaceBookingCalendar'] == 'on' or $_POST['spaceBookingCalendar'] == 'Y') {
                            $_SESSION[$guid]['viewCalendar']['SpaceBooking'] = 'Y';
                        } else {
                            $_SESSION[$guid]['viewCalendar']['SpaceBooking'] = 'N';
                        }
                    } else {
                        $_SESSION[$guid]['viewCalendar']['SpaceBooking'] = 'N';
                    }
                }
            }

            $tt = renderTTSpace($guid, $connection2, $gibbonSpaceID, $gibbonTTID, false, $ttDate, '/modules/Timetable/tt_space_view.php', "&gibbonSpaceID=$gibbonSpaceID&search=$search");

            if ($tt != false) {
                echo $tt;
            } else {
                echo "<div class='error'>";
                echo __($guid, 'There are no records to display.');
                echo '</div>';
            }
        }
    }
}
