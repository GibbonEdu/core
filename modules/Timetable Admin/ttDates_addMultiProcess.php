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

use Gibbon\Domain\School\SchoolYearGateway;
use Gibbon\Domain\Timetable\TimetableDayGateway;
use Gibbon\Domain\Timetable\TimetableDayDateGateway;
use Gibbon\Data\Validator;
use Gibbon\Http\Url;

require_once __DIR__ . '/../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
$dates = $_POST['dates'] ?? [];
$gibbonTTDayID = $_POST['gibbonTTDayID'] ?? '';
$overwrite = $_POST['overwrite'] ?? 'N';

$URL = Url::fromModuleRoute('Timetable Admin', 'ttDates')->withQueryParam('gibbonSchoolYearID', $gibbonSchoolYearID);

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/ttDates_edit_add.php') == false) {
    header('Location: ' . $URL->withReturn('error0'));
} else {
    // Proceed!
    $partialFail = false;

    // Validate Inputs
    if (empty($gibbonSchoolYearID) or empty($dates) or empty($gibbonTTDayID)) {
        header('Location: ' . $URL->withReturn('error1'));
        exit;
    }

    $timetableDayGateway = $container->get(TimetableDayGateway::class);
    $timetableDayDateGateway = $container->get(TimetableDayDateGateway::class);

    // Validate records exist
    $schoolYear = $container->get(SchoolYearGateway::class)->getByID($gibbonSchoolYearID);
    $gibbonTTDay = $timetableDayGateway->getTTDayByID($gibbonTTDayID);
    if (empty($schoolYear) || empty($gibbonTTDay)) {
        header('Location: ' . $URL->withReturn('error2'));
        exit();
    }

    foreach ($dates as $date) {
        if (!isSchoolOpen($guid, date('Y-m-d', $date), $connection2, true)) {
            $partialFail = true;
            continue;
        }

        // Remove existing TT Day Dates if overwriting
        if ($overwrite == 'Y') {
            $timetableDayDateGateway->deleteWhere(['date' => date('Y-m-d', $date)]);
        }

        // Check if a day from the TT is already set
        $days = $timetableDayGateway->selectDaysByDate(date('Y-m-d', $date), $gibbonTTDay['gibbonTTID']);

        if ($days->rowCount() > 0) {
            $partialFail = true;
        } else {
            $data = ['gibbonTTDayID' => $gibbonTTDayID, 'date' => date('Y-m-d', $date)];
            $inserted = $timetableDayDateGateway->insert($data);
            $partialFail &= !$inserted;
        }

    }

    if ($partialFail) {
        header('Location: ' . $URL->withReturn('warning1'));
    } else {
        header('Location: ' . $URL->withReturn('success0'));
    }
}
