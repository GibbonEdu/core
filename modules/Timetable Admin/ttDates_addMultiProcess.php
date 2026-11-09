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

use Gibbon\Domain\School\SchoolYearGateway;
use Gibbon\Domain\Timetable\TimetableDayGateway;
use Gibbon\Domain\Timetable\TimetableDayDateGateway;
use Gibbon\Data\Validator;

require_once __DIR__ . '/../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
$dates = $_POST['dates'] ?? [];
$overwrite = $_POST['overwrite'] ?? 'N';

// Accept gibbonTTDayID as an array and resolve the selected mode server-side.
$gibbonTTDayIDs = $_POST['gibbonTTDayID'] ?? [];
if (!is_array($gibbonTTDayIDs)) {
    $gibbonTTDayIDs = [$gibbonTTDayIDs];
}
$gibbonTTDayIDs = array_filter(array_map('trim', $gibbonTTDayIDs));
$smartDayGroup = trim($_POST['smartDayGroup'] ?? '');
$ttDatesMode = $_POST['ttDatesMode'] ?? 'custom';
$deleteDates = !empty($_POST['deleteDates']);

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['q'])."/ttDates.php&gibbonSchoolYearID=$gibbonSchoolYearID";

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/ttDates_edit_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    // Proceed!
    $partialFail = false;

    $timetableDayGateway = $container->get(TimetableDayGateway::class);
    $timetableDayDateGateway = $container->get(TimetableDayDateGateway::class);

    if (empty($gibbonSchoolYearID) or empty($dates)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Restrict all operations to terms in the selected school year.
    $termStatement = $connection2->prepare('SELECT firstDay, lastDay FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearID=:gibbonSchoolYearID');
    $termStatement->execute(['gibbonSchoolYearID' => $gibbonSchoolYearID]);
    $termRanges = $termStatement->fetchAll(\PDO::FETCH_ASSOC);
    $isInSchoolYear = function ($date) use ($termRanges) {
        foreach ($termRanges as $term) {
            if ($date >= $term['firstDay'] && $date <= $term['lastDay']) return true;
        }
        return false;
    };

    if ($deleteDates) {
        $deletedCount = 0;
        foreach ($dates as $timestamp) {
            if (!is_numeric($timestamp)) continue;
            $date = date('Y-m-d', (int) $timestamp);
            if (!$isInSchoolYear($date)) continue;

            if ($timetableDayDateGateway->deleteWhere(['date' => $date])) {
                $deletedCount++;
            }
        }

        $URL .= '&return=success0&deleted='.$deletedCount;
        header("Location: {$URL}");
        exit;
    }

    // Resolve the selected mode on the server. Exactly one mode is accepted.
    $groups = $timetableDayGateway->selectTTDayGroupsBySchoolYear($gibbonSchoolYearID);
    $validTTDayIDs = [];
    foreach ($groups as $group) {
        $validTTDayIDs = array_merge($validTTDayIDs, $group['ids']);
    }

    if (!in_array($ttDatesMode, ['custom', 'smart'], true)) {
        $ttDatesMode = $smartDayGroup !== '' ? 'smart' : 'custom';
    }

    if ($ttDatesMode === 'custom') {
        $gibbonTTDayIDs = array_values(array_unique(array_filter($gibbonTTDayIDs)));
        if (empty($gibbonTTDayIDs)) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
            exit;
        }
    } else {
        if (!isset($groups[$smartDayGroup])) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
            exit;
        }
        $gibbonTTDayIDs = $groups[$smartDayGroup]['ids'];
    }

    if (!empty($validTTDayIDs)) {
        foreach ($gibbonTTDayIDs as $ttDayID) {
            if (!in_array((string) $ttDayID, $validTTDayIDs, true)) {
                $URL .= '&return=error1';
                header("Location: {$URL}");
                exit;
            }
        }
    }

    // Validate records exist
    $schoolYear = $container->get(SchoolYearGateway::class)->getByID($gibbonSchoolYearID);
    if (empty($schoolYear)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit();
    }

    // Validate each TT Day record and cache them for the insert loop
    $ttDayRecords = [];
    foreach ($gibbonTTDayIDs as $ttDayID) {
        $gibbonTTDay = $timetableDayGateway->getTTDayByID($ttDayID);
        if (empty($gibbonTTDay)) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }
        $ttDayRecords[$ttDayID] = $gibbonTTDay;
    }

    foreach ($dates as $timestamp) {
        if (!is_numeric($timestamp)) {
            $partialFail = true;
            continue;
        }

        $date = date('Y-m-d', (int) $timestamp);
        if (!$isInSchoolYear($date) || !isSchoolOpen($guid, $date, $connection2, true)) {
            $partialFail = true;
            continue;
        }

        // Overwrite resets the selected dates; otherwise append new timetable days.
        if ($overwrite == 'Y') {
            if (!$timetableDayDateGateway->deleteWhere(['date' => $date])) {
                $partialFail = true;
                continue;
            }
        }

        foreach ($ttDayRecords as $ttDayID => $gibbonTTDay) {
            if ($overwrite != 'Y' && $timetableDayDateGateway->existsForDateAndDay($date, $ttDayID)) {
                continue;
            }

            $data = ['gibbonTTDayID' => $ttDayID, 'date' => $date];
            $inserted = $timetableDayDateGateway->insert($data);
            if (!$inserted) {
                $partialFail = true;
            }
        }
    }

    $URL .= $partialFail
        ? '&return=warning1'
        : '&return=success0';
    header("Location: {$URL}");

}
