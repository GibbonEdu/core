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

include '../../gibbon.php';

$gibbonLibraryItemEventID = $_GET['gibbonLibraryItemEventID'];
$gibbonLibraryItemID = $_GET['gibbonLibraryItemID'];

if ($gibbonLibraryItemID == '') { echo 'Fatal error loading this page!';
} else {
    $URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/library_lending_item_edit.php&gibbonLibraryItemID=$gibbonLibraryItemID&gibbonLibraryItemEventID=$gibbonLibraryItemEventID&name=".$_GET['name'].'&gibbonLibraryTypeID='.$_GET['gibbonLibraryTypeID'].'&gibbonSpaceID='.$_GET['gibbonSpaceID'].'&status='.$_GET['status'];

    if (isActionAccessible($guid, $connection2, '/modules/Library/library_lending_item_edit.php') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        //Proceed!
        //Check if event specified
        if ($gibbonLibraryItemEventID == '' or $gibbonLibraryItemID == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            try {
                $data = array('gibbonLibraryItemEventID' => $gibbonLibraryItemEventID, 'gibbonLibraryItemID' => $gibbonLibraryItemID);
                $sql = 'SELECT * FROM gibbonLibraryItemEvent JOIN gibbonLibraryItem ON (gibbonLibraryItemEvent.gibbonLibraryItemID=gibbonLibraryItem.gibbonLibraryItemID) WHERE gibbonLibraryItemEventID=:gibbonLibraryItemEventID AND gibbonLibraryItem.gibbonLibraryItemID=:gibbonLibraryItemID';
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
                //Validate Inputs
                $status = $_POST['status'];
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
                $returnExpected = null;
                if ($_POST['returnExpected'] != '') {
                    $returnExpected = dateConvert($guid, $_POST['returnExpected']);
                }
                $returnAction = $_POST['returnAction'];
                $gibbonPersonIDReturnAction = null;
                if ($_POST['gibbonPersonIDReturnAction'] != '') {
                    $gibbonPersonIDReturnAction = $_POST['gibbonPersonIDReturnAction'];
                }

                //Write to database
                try {
                    $data = array('gibbonLibraryItemEventID' => $gibbonLibraryItemEventID, 'type' => $type, 'status' => $status, 'gibbonPersonIDOut' => $_SESSION[$guid]['gibbonPersonID'], 'timestampOut' => date('Y-m-d H:i:s', time()), 'returnExpected' => $returnExpected, 'returnAction' => $returnAction, 'gibbonPersonIDReturnAction' => $gibbonPersonIDReturnAction);
                    $sql = 'UPDATE gibbonLibraryItemEvent SET type=:type, status=:status, gibbonPersonIDOut=:gibbonPersonIDOut, timestampOut=:timestampOut, returnExpected=:returnExpected, returnAction=:returnAction, gibbonPersonIDReturnAction=:gibbonPersonIDReturnAction WHERE gibbonLibraryItemEventID=:gibbonLibraryItemEventID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2'.$e->getMessage();
                    header("Location: {$URL}");
                    exit();
                }

                try {
                    $data = array('gibbonLibraryItemID' => $gibbonLibraryItemID, 'status' => $status, 'gibbonPersonIDStatusRecorder' => $_SESSION[$guid]['gibbonPersonID'], 'timestampStatus' => date('Y-m-d H:i:s', time()), 'returnExpected' => $returnExpected, 'returnAction' => $returnAction, 'gibbonPersonIDReturnAction' => $gibbonPersonIDReturnAction);
                    $sql = 'UPDATE gibbonLibraryItem SET status=:status, gibbonPersonIDStatusRecorder=:gibbonPersonIDStatusRecorder, timestampStatus=:timestampStatus, returnExpected=:returnExpected, returnAction=:returnAction, gibbonPersonIDReturnAction=:gibbonPersonIDReturnAction WHERE gibbonLibraryItemID=:gibbonLibraryItemID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                $URL .= '&return=success0';
                header("Location: {$URL}");
            }
        }
    }
}
