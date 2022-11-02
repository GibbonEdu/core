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

use Gibbon\Domain\Timetable\TimetableDayGateway;

require_once '../../gibbon.php';

$gibbonCourseClassID = $_REQUEST['gibbonCourseClassID'] ?? '';
$gibbonTTID = $_REQUEST['gibbonTTID'] ?? '';

$URL = $session->get('absoluteURL') . "/index.php?q=/modules/Timetable Admin/tt_edit_byClass.php&gibbonTTID={$gibbonTTID}&gibbonCourseClassID={$gibbonCourseClassID}";

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/tt_edit_byClass.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    //Proceed!
    if (empty($gibbonCourseClassID) || empty($gibbonTTID)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    $timetableDayGateway = $container->get(TimetableDayGateway::class);

    $timetableDayGateway->deleteTTDayRowClasses($gibbonTTID, $gibbonCourseClassID);

    $entryOrders = $_POST['order'] ?? [];
    $inserted = 0;

    foreach ($entryOrders as $order) {
        $entry = $_POST['ttBlocks'][$order];

        if (empty($entry['gibbonTTColumnRowID']) || empty($entry['gibbonTTDayID'])) {
            continue;
        }

        $data = [
            'gibbonTTColumnRowID' => strstr($entry['gibbonTTColumnRowID'], '-', true),
            'gibbonTTDayID' => $entry['gibbonTTDayID'],
            'gibbonCourseClassID' => $gibbonCourseClassID,
            'gibbonSpaceID' => $entry['gibbonTTSpaceID'] ?? ''
        ]; 

        if ($timetableDayGateway->insertDayRowClass($data) > 0) {
            $inserted++;
        }
    }

    $URL .= $inserted != count($entryOrders)
        ? '&return=warning1'
        : '&return=success0';
    header("Location: {$URL}");
    exit;
}
