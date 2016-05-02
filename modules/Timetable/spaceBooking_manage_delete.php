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

if (isActionAccessible($guid, $connection2, '/modules/Timetable/spaceBooking_manage_delete.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo _('You do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo _('The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        //Proceed!
        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>"._('Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>"._(getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/spaceBooking_manage.php'>"._('Manage Facility Bookings')."</a> > </div><div class='trailEnd'>"._('Delete Facility Booking').'</div>';
        echo '</div>';

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }

        //Check if school year specified
        $gibbonTTSpaceBookingID = $_GET['gibbonTTSpaceBookingID'];
        if ($gibbonTTSpaceBookingID == '') {
            echo "<div class='error'>";
            echo _('You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            try {
                if ($highestAction == 'Manage Space Bookings_allBookings') {
                    $data = array('gibbonTTSpaceBookingID1' => $gibbonTTSpaceBookingID, 'gibbonTTSpaceBookingID2' => $gibbonTTSpaceBookingID);
                    $sql = "(SELECT gibbonTTSpaceBooking.*, gibbonSpace.name AS name, surname, preferredName FROM gibbonTTSpaceBooking JOIN gibbonSpace ON (gibbonTTSpaceBooking.foreignKeyID=gibbonSpace.gibbonSpaceID) JOIN gibbonPerson ON (gibbonTTSpaceBooking.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE foreignKey='gibbonSpaceID' AND gibbonTTSpaceBookingID=:gibbonTTSpaceBookingID1) UNION (SELECT gibbonTTSpaceBooking.*, gibbonLibraryItem.name AS name, surname, preferredName FROM gibbonTTSpaceBooking JOIN gibbonLibraryItem ON (gibbonTTSpaceBooking.foreignKeyID=gibbonLibraryItem.gibbonLibraryItemID) JOIN gibbonPerson ON (gibbonTTSpaceBooking.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE foreignKey='gibbonLibraryItemID' AND gibbonTTSpaceBookingID=:gibbonTTSpaceBookingID2) ORDER BY date, name";
                } else {
                    $data = array('gibbonPersonID1' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonPersonID2' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonTTSpaceBookingID1' => $gibbonTTSpaceBookingID, 'gibbonTTSpaceBookingID2' => $gibbonTTSpaceBookingID);
                    $sql = "(SELECT gibbonTTSpaceBooking.*, gibbonSpace.name AS name, surname, preferredName FROM gibbonTTSpaceBooking JOIN gibbonSpace ON (gibbonTTSpaceBooking.foreignKeyID=gibbonSpace.gibbonSpaceID) JOIN gibbonPerson ON (gibbonTTSpaceBooking.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE foreignKey='gibbonSpaceID' AND gibbonTTSpaceBooking.gibbonPersonID=:gibbonPersonID1 AND gibbonTTSpaceBookingID=:gibbonTTSpaceBookingID1) UNION (SELECT gibbonTTSpaceBooking.*, gibbonLibraryItem.name AS name, surname, preferredName FROM gibbonTTSpaceBooking JOIN gibbonLibraryItem ON (gibbonTTSpaceBooking.foreignKeyID=gibbonLibraryItem.gibbonLibraryItemID) JOIN gibbonPerson ON (gibbonTTSpaceBooking.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE foreignKey='gibbonLibraryItemID' AND gibbonTTSpaceBooking.gibbonPersonID=:gibbonPersonID2 AND gibbonTTSpaceBookingID=:gibbonTTSpaceBookingID2) ORDER BY date, name";
                }
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() != 1) {
                echo "<div class='error'>";
                echo _('The specified record cannot be found.');
                echo '</div>';
            } else {
                //Let's go!
                $row = $result->fetch();
                ?>
				<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/spaceBooking_manage_deleteProcess.php?gibbonTTSpaceBookingID=$gibbonTTSpaceBookingID" ?>">
					<table class='smallIntBorder fullWidth' cellspacing='0'>	
						<tr>
							<td> 
								<b><?php echo _('Are you sure you want to delete this record?');
                ?></b><br/>
								<span style="font-size: 90%; color: #cc0000"><i><?php echo _('This operation cannot be undone, and may lead to loss of vital data in your system. PROCEED WITH CAUTION!');
                ?></span>
							</td>
							<td class="right">
							
							</td>
						</tr>
						<tr>
							<td> 
								<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
								<input type="submit" value="<?php echo _('Yes');
                ?>">
							</td>
							<td class="right">
							
							</td>
						</tr>
					</table>
				</form>
				<?php

            }
        }
    }
}
?>