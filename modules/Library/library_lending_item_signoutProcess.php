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

use Gibbon\Services\Format;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonLibraryItemID = $_POST['gibbonLibraryItemID'] ?? '';

$address = $_POST['address'] ?? '';
$name = $_GET['name'] ?? '';
$gibbonLibraryTypeID = $_GET['gibbonLibraryTypeID'] ?? '';
$gibbonSpaceID = $_GET['gibbonSpaceID'] ?? '';
$status = $_GET['status'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($address)."/library_lending_item_signOut.php&gibbonLibraryItemID=$gibbonLibraryItemID&name=$name&gibbonLibraryTypeID=$gibbonLibraryTypeID&gibbonSpaceID=$gibbonSpaceID&status=$status";
$URLSuccess = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($address)."/library_lending_item.php&gibbonLibraryItemID=$gibbonLibraryItemID&name=$name&gibbonLibraryTypeID=$gibbonLibraryTypeID&gibbonSpaceID=$gibbonSpaceID&status=$status";

if (isActionAccessible($guid, $connection2, '/modules/Library/library_lending_item_signOut.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $statusCurrent = $_POST['statusCurrent'] ?? '';
    $status = $_POST['status'] ?? '';
    $type = 'Other';
    if ($status == 'Decommissioned') {
        $type = 'Decommission';
    } elseif ($status == 'Lost') {
        $type = 'Loss';
    } elseif ($status == 'On Loan') {
        $type = 'Loan';
    } elseif ($status == 'Repair') {
        $type = 'Repair';
    } elseif ($status == 'Reserved') {
        $type = 'Reserve';
    }
    $gibbonPersonIDStatusResponsible = $_POST['gibbonPersonIDStatusResponsible'] ?? null;
    $returnExpected = !empty($_POST['returnExpected']) ? Format::dateConvert($_POST['returnExpected']) : null;
    $returnAction = $_POST['returnAction'] ?? '';
    $gibbonPersonIDReturnAction = $_POST['gibbonPersonIDReturnAction'] ?? null;

    //Validate Inputs
    if ($gibbonLibraryItemID == '' or $status == '' or empty($gibbonPersonIDStatusResponsible) or $statusCurrent != 'Available') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        try {
            $data = array('gibbonLibraryItemID' => $gibbonLibraryItemID);
            $sql = 'SELECT * FROM gibbonLibraryItem WHERE gibbonLibraryItemID=:gibbonLibraryItemID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        if ($result->rowCount() != 1) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            //Write to database
            try {
                $data = array('gibbonLibraryItemID' => $gibbonLibraryItemID, 'type' => $type, 'status' => $status, 'gibbonPersonIDStatusResponsible' => $gibbonPersonIDStatusResponsible, 'gibbonPersonIDOut' => $session->get('gibbonPersonID'), 'timestampOut' => date('Y-m-d H:i:s', time()), 'returnExpected' => $returnExpected, 'returnAction' => $returnAction, 'gibbonPersonIDReturnAction' => $gibbonPersonIDReturnAction);
                $sql = 'INSERT INTO gibbonLibraryItemEvent SET gibbonLibraryItemID=:gibbonLibraryItemID, type=:type, status=:status, gibbonPersonIDStatusResponsible=:gibbonPersonIDStatusResponsible, gibbonPersonIDOut=:gibbonPersonIDOut, timestampOut=:timestampOut, returnExpected=:returnExpected, returnAction=:returnAction, gibbonPersonIDReturnAction=:gibbonPersonIDReturnAction';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            try {
                $data = array('gibbonLibraryItemID' => $gibbonLibraryItemID, 'status' => $status, 'gibbonPersonIDStatusResponsible' => $gibbonPersonIDStatusResponsible, 'gibbonPersonIDStatusRecorder' => $session->get('gibbonPersonID'), 'timestampStatus' => date('Y-m-d H:i:s', time()), 'returnExpected' => $returnExpected, 'returnAction' => $returnAction, 'gibbonPersonIDReturnAction' => $gibbonPersonIDReturnAction);
                $sql = 'UPDATE gibbonLibraryItem SET status=:status, gibbonPersonIDStatusResponsible=:gibbonPersonIDStatusResponsible, gibbonPersonIDStatusRecorder=:gibbonPersonIDStatusRecorder, timestampStatus=:timestampStatus, returnExpected=:returnExpected, returnAction=:returnAction, gibbonPersonIDReturnAction=:gibbonPersonIDReturnAction WHERE gibbonLibraryItemID=:gibbonLibraryItemID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            $URL = $URLSuccess.'&return=success0';
            header("Location: {$URL}");
        }
    }
}
