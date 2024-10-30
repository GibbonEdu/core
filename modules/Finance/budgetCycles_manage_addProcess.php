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

include '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$address = $_POST['address'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($address).'/budgetCycles_manage_add.php';

if (isActionAccessible($guid, $connection2, '/modules/Finance/budgetCycles_manage_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Validate Inputs
    $name = $_POST['name'] ?? '';
    $status = $_POST['status'] ?? '';
    $sequenceNumber = $_POST['sequenceNumber'] ?? '';
    $dateStart = !empty($_POST['dateStart']) ? Format::dateConvert($_POST['dateStart']) : null;
    $dateEnd = !empty($_POST['dateEnd']) ? Format::dateConvert($_POST['dateEnd']) : null;

    if ($name == '' or $status == '' or $sequenceNumber == '' or is_numeric($sequenceNumber) == false or $dateStart == '' or $dateEnd == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        //Check unique inputs for uniquness
        try {
            $data = array('name' => $name, 'sequenceNumber' => $sequenceNumber);
            $sql = 'SELECT * FROM gibbonFinanceBudgetCycle WHERE name=:name OR sequenceNumber=:sequenceNumber';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        if ($result->rowCount() > 0) {
            $URL .= '&return=error7';
            header("Location: {$URL}");
        } else {
            //Write to database
            try {
                $data = array('name' => $name, 'status' => $status, 'sequenceNumber' => $sequenceNumber, 'dateStart' => $dateStart, 'dateEnd' => $dateEnd, 'gibbonPersonIDCreator' => $session->get('gibbonPersonID'));
                $sql = "INSERT INTO gibbonFinanceBudgetCycle SET name=:name, status=:status, sequenceNumber=:sequenceNumber, dateStart=:dateStart, dateEnd=:dateEnd, gibbonPersonIDCreator=:gibbonPersonIDCreator, timestampCreator='".date('Y-m-d H:i:s')."'";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                exit();
                $URL .= '&return=error2';
                header("Location: {$URL}");
            }

            $gibbonFinanceBudgetCycleID = str_pad($connection2->lastInsertID(), 14, '0', STR_PAD_LEFT);

            //UPDATE CYCLE ALLOCATION VALUES
            $partialFail = false;
            if (isset($_POST['values'])) {
                $values = $_POST['values'] ?? [];
                $gibbonFinanceBudgetIDs = $_POST['gibbonFinanceBudgetIDs'] ?? [];
                $count = 0;
                foreach ($values as $value) {
                    try {
                        $data = array('value' => $value, 'gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID, 'gibbonFinanceBudgetID' => $gibbonFinanceBudgetIDs[$count]);
                        $sql = 'INSERT INTO gibbonFinanceBudgetCycleAllocation SET value=:value, gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID, gibbonFinanceBudgetID=:gibbonFinanceBudgetID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $partialFail = true;
                    }
                    ++$count;
                }
            }

            if ($partialFail == true) {
                $URL .= '&return=warning1';
                header("Location: {$URL}");
            } else {
                $URL .= "&return=success0&editID=$gibbonFinanceBudgetCycleID";
                header("Location: {$URL}");
            }
        }
    }
}
