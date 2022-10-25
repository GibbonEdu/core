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

use Gibbon\Domain\Timetable\TimetableColumnGateway;
use Gibbon\Http\Url;

require_once __DIR__ . '/../../gibbon.php';

$action = $_POST['action'] ?? '';

$URL = Url::fromModuleRoute('Timetable Admin', 'ttColumn');

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/ttColumn.php') == false) {
    header('Location: ' . $URL->withReturn('error0'));
} else if ($action == '') {
    header('Location: ' . $URL->withReturn('error1'));
} else {

    $columns = isset($_POST['gibbonTTColumnIDList']) ? $_POST['gibbonTTColumnIDList'] : array();

    //Proceed!
    if (count($columns) < 1) {
        header('Location: ' . $URL->withReturn('error3'));
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
            header('Location: ' . $URL->withReturn('warning1'));
        } else {
            header('Location: ' . $URL->withReturn('success0'));
        }
    }

}
