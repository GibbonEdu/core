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

$gibbonMessengerID = $_GET['gibbonMessengerID'] ?? '';
$search = $_GET['search'] ?? '';

$address = $_POST['address'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($address)."/messenger_manage_delete.php&search=$search&gibbonMessengerID=".$gibbonMessengerID;
$URLDelete = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($address)."/messenger_manage.php&search=$search";

if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_manage_delete.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $highestAction = getHighestGroupedAction($guid, $address, $connection2);
    if ($highestAction == false) {
        $URL .= "&return=error0$params";
        header("Location: {$URL}");
    } else {
        //Proceed!
        //Check if gibbonMessengerID specified
        if ($gibbonMessengerID == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            try {
                if ($highestAction == 'Manage Messages_all') {
                    $data = array('gibbonMessengerID' => $gibbonMessengerID);
                    $sql = 'SELECT gibbonMessenger.*, title, surname, preferredName FROM gibbonMessenger JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonMessengerID=:gibbonMessengerID';
                } else {
                    $data = array('gibbonMessengerID' => $gibbonMessengerID, 'gibbonPersonID' => $session->get('gibbonPersonID'));
                    $sql = 'SELECT gibbonMessenger.*, title, surname, preferredName FROM gibbonMessenger JOIN gibbonPerson ON (gibbonMessenger.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonMessengerID=:gibbonMessengerID AND gibbonMessenger.gibbonPersonID=:gibbonPersonID';
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
                    $data = array('gibbonMessengerID' => $gibbonMessengerID);
                    $sql = 'DELETE FROM gibbonMessenger WHERE gibbonMessengerID=:gibbonMessengerID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                $session->set('messageWallRefreshed', 0);

                $URLDelete = $URLDelete.'&return=success0';
                header("Location: {$URLDelete}");
            }
        }
    }
}
