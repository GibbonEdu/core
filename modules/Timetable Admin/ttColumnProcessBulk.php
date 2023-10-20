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

use Gibbon\Domain\Timetable\TimetableColumnGateway;

include '../../gibbon.php';

$action = $_POST['action'] ?? '';

$URL = $session->get('absoluteURL')."/index.php?q=/modules/Timetable Admin/ttColumn.php";

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/ttColumn.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else if ($action == '') {
    $URL .= '&return=error1';
    header("Location: {$URL}");
} else {

    $columns = isset($_POST['gibbonTTColumnIDList']) ? $_POST['gibbonTTColumnIDList'] : array();

    //Proceed!
    if (count($columns) < 1) {
        $URL .= '&return=error3';
        header("Location: {$URL}");
    } else {
        $timetableColumnGateway = $container->get(TimetableColumnGateway::class);
        $partialFail = false;

        foreach ($columns as $gibbonTTColumnID) {
            $data = $timetableColumnGateway->getByID($gibbonTTColumnID);
            $data['name'] .= " Copy";

            // Copy columns
            $inserted = $timetableColumnGateway->insert($data);
            $partialFail &= !$inserted;

            //Copy rows
            $rows = $timetableColumnGateway->selectTTColumnRowsByID($gibbonTTColumnID)->fetchAll();
            if (empty($rows)) continue;

            foreach ($rows as $row) {
                $insertedRow = $timetableColumnGateway->insertColumnRow([
                    'gibbonTTColumnID' => $inserted,
                    'name' => $row['name'],
                    'nameShort' => $row['nameShort'],
                    'timeStart' => $row['timeStart'],
                    'timeEnd' => $row['timeEnd'],
                    'type' => $row['type'],
                ]);
                $partialFail &= !$insertedRow;
            }
        }

        if ($partialFail == true) {
            $URL .= '&return=warning1';
            header("Location: {$URL}");
        } else {
            $URL .= '&return=success0';
            header("Location: {$URL}");
        }
    }

}
