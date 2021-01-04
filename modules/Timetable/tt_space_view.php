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

if (isActionAccessible($guid, $connection2, '/modules/Timetable/tt_space_view.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __('The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        $gibbonSpaceID = isset($_REQUEST['gibbonSpaceID']) ? $_REQUEST['gibbonSpaceID'] : '';
        $search = isset($_REQUEST['search']) ? $_REQUEST['search'] : null;
        $gibbonTTID = isset($_REQUEST['gibbonTTID']) ? $_REQUEST['gibbonTTID'] : null;

        
            $data = array('gibbonSpaceID' => $gibbonSpaceID);
            $sql = 'SELECT * FROM gibbonSpace WHERE gibbonSpaceID=:gibbonSpaceID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __('The specified room does not seem to exist.');
            echo '</div>';
        } else {
            $row = $result->fetch();

            $page->breadcrumbs
                ->add(__('View Timetable by Facility'), 'tt_space.php')
                ->add($row['name']);

            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
            }

            if ($search != '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Timetable/tt_space.php&search=$search'>".__('Back to Search Results').'</a>';
                echo '</div>';
            }

            $ttDate = null;
            if (isset($_REQUEST['ttDate'])) {
                $date = dateConvert($guid, $_REQUEST['ttDate']);
                $ttDate = strtotime('last Sunday +1 day', strtotime($date));
            }

            if (isset($_POST['fromTT'])) {
                if ($_POST['fromTT'] == 'Y') {
                    if (isset($_POST['spaceBookingCalendar'])) {
                        if ($_POST['spaceBookingCalendar'] == 'on' or $_POST['spaceBookingCalendar'] == 'Y') {
                            $_SESSION[$guid]['viewCalendarSpaceBooking'] = 'Y';
                        } else {
                            $_SESSION[$guid]['viewCalendarSpaceBooking'] = 'N';
                        }
                    } else {
                        $_SESSION[$guid]['viewCalendarSpaceBooking'] = 'N';
                    }
                }
            }

            $tt = renderTTSpace($guid, $connection2, $gibbonSpaceID, $gibbonTTID, false, $ttDate, '/modules/Timetable/tt_space_view.php', "&gibbonSpaceID=$gibbonSpaceID&search=$search");

            if ($tt != false) {
                echo $tt;
            } else {
                echo "<div class='error'>";
                echo __('There are no records to display.');
                echo '</div>';
            }
        }
    }
}
