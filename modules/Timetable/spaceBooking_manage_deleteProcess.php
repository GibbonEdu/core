<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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

include '../../gibbon.php';

$gibbonTTSpaceBookingID = $_POST['gibbonTTSpaceBookingID'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address']).'/spaceBooking_manage_delete.php&gibbonTTSpaceBookingID='.$gibbonTTSpaceBookingID;
$URLDelete = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address']).'/spaceBooking_manage.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable/spaceBooking_manage_delete.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_POST['address'], $connection2);
    if ($highestAction == false) {
        $URL .= "&return=error0$params";
        header("Location: {$URL}");
    } else {
        //Proceed!
        //Check if gibbonTTSpaceBookingID specified
        if ($gibbonTTSpaceBookingID == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            try {
                if ($highestAction == 'Manage Facility Bookings_allBookings') {
                    $data = array('gibbonTTSpaceBookingID1' => $gibbonTTSpaceBookingID, 'gibbonTTSpaceBookingID2' => $gibbonTTSpaceBookingID);
                    $sql = "(SELECT gibbonTTSpaceBooking.*, gibbonSpace.name AS name, surname, preferredName FROM gibbonTTSpaceBooking JOIN gibbonSpace ON (gibbonTTSpaceBooking.foreignKeyID=gibbonSpace.gibbonSpaceID) JOIN gibbonPerson ON (gibbonTTSpaceBooking.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE foreignKey='gibbonSpaceID' AND gibbonTTSpaceBookingID=:gibbonTTSpaceBookingID1) UNION (SELECT gibbonTTSpaceBooking.*, gibbonLibraryItem.name AS name, surname, preferredName FROM gibbonTTSpaceBooking JOIN gibbonLibraryItem ON (gibbonTTSpaceBooking.foreignKeyID=gibbonLibraryItem.gibbonLibraryItemID) JOIN gibbonPerson ON (gibbonTTSpaceBooking.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE foreignKey='gibbonLibraryItemID' AND gibbonTTSpaceBookingID=:gibbonTTSpaceBookingID2) ORDER BY date, name";
                } else {
                    $data = array('gibbonPersonID1' => $session->get('gibbonPersonID'), 'gibbonPersonID2' => $session->get('gibbonPersonID'), 'gibbonTTSpaceBookingID1' => $gibbonTTSpaceBookingID, 'gibbonTTSpaceBookingID2' => $gibbonTTSpaceBookingID);
                    $sql = "(SELECT gibbonTTSpaceBooking.*, gibbonSpace.name AS name, surname, preferredName FROM gibbonTTSpaceBooking JOIN gibbonSpace ON (gibbonTTSpaceBooking.foreignKeyID=gibbonSpace.gibbonSpaceID) JOIN gibbonPerson ON (gibbonTTSpaceBooking.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE foreignKey='gibbonSpaceID' AND gibbonTTSpaceBooking.gibbonPersonID=:gibbonPersonID1 AND gibbonTTSpaceBookingID=:gibbonTTSpaceBookingID1) UNION (SELECT gibbonTTSpaceBooking.*, gibbonLibraryItem.name AS name, surname, preferredName FROM gibbonTTSpaceBooking JOIN gibbonLibraryItem ON (gibbonTTSpaceBooking.foreignKeyID=gibbonLibraryItem.gibbonLibraryItemID) JOIN gibbonPerson ON (gibbonTTSpaceBooking.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE foreignKey='gibbonLibraryItemID' AND gibbonTTSpaceBooking.gibbonPersonID=:gibbonPersonID2 AND gibbonTTSpaceBookingID=:gibbonTTSpaceBookingID2) ORDER BY date, name";
                }
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            if ($result->rowCount() != 1) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
            } else {
                //Write to database
                try {
                    $data = array('gibbonTTSpaceBookingID' => $gibbonTTSpaceBookingID);
                    $sql = 'DELETE FROM gibbonTTSpaceBooking WHERE gibbonTTSpaceBookingID=:gibbonTTSpaceBookingID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                $URLDelete = $URLDelete.'&return=success0';
                header("Location: {$URLDelete}");
            }
        }
    }
}
