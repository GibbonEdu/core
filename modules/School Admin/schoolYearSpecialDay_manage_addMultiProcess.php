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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

use Gibbon\Data\Validator;
use Gibbon\Domain\School\DaysOfWeekGateway;
use Gibbon\Domain\School\SchoolYearSpecialDayGateway;
use Gibbon\Domain\School\SchoolYearTermGateway;
use Gibbon\Services\Format;

require_once __DIR__ . '/../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonSchoolYearID = $_POST['gibbonSchoolYearID'] ?? '';

$URL = $session->get('absoluteURL') . "/index.php?q=/modules/School Admin/schoolYearSpecialDay_manage.php&gibbonSchoolYearID=$gibbonSchoolYearID";

if (isActionAccessible($guid, $connection2, '/modules/School Admin/schoolYearSpecialDay_manage.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
}

$dateStart = !empty($_POST['dateStart']) ? Format::dateConvert($_POST['dateStart']) : '';
$dateEnd = !empty($_POST['dateEnd']) ? Format::dateConvert($_POST['dateEnd']) : '';

// The bulk form mirrors the single-add fields for the chosen type.
$data = array(
    'type'                   => $_POST['type'] ?? '',
    'name'                   => trim($_POST['name'] ?? ''),
    'description'            => trim($_POST['description'] ?? ''),
    'schoolOpen'             => null,
    'schoolStart'            => null,
    'schoolEnd'              => null,
    'schoolClose'            => null,
    'gibbonYearGroupIDList'  => $_POST['gibbonYearGroupIDList'] ?? '',
    'gibbonFormGroupIDList'  => $_POST['gibbonFormGroupIDList'] ?? '',
    'cancelActivities'       => $_POST['cancelActivities'] ?? 'N',
    'cancelBookings'         => $_POST['cancelBookings'] ?? 'N',
    'cancelClasses'          => $_POST['cancelClasses'] ?? 'N',
    'cancelDuty'             => $_POST['cancelDuty'] ?? 'N',
);

foreach (array('schoolOpen', 'schoolStart', 'schoolEnd', 'schoolClose') as $time) {
    if (!empty($_POST[$time . 'H']) && is_numeric($_POST[$time . 'H']) && is_numeric($_POST[$time . 'M'] ?? '')) {
        $data[$time] = $_POST[$time . 'H'] . ':' . $_POST[$time . 'M'] . ':00';
    }
}

if (!empty($data['gibbonYearGroupIDList']) && is_array($data['gibbonYearGroupIDList'])) {
    $data['gibbonYearGroupIDList'] = implode(',', $data['gibbonYearGroupIDList']);
}

if (!empty($data['gibbonFormGroupIDList']) && is_array($data['gibbonFormGroupIDList'])) {
    $data['gibbonFormGroupIDList'] = implode(',', $data['gibbonFormGroupIDList']);
}

$allowedTypes = array('School Closure', 'Timing Change', 'Off Timetable');

if (empty($gibbonSchoolYearID) || empty($dateStart) || empty($dateEnd) || empty($data['name']) || !in_array($data['type'], $allowedTypes, true)) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
}

// Normalise an inverted range.
if ($dateStart > $dateEnd) {
    list($dateStart, $dateEnd) = array($dateEnd, $dateStart);
}

// Guard against runaway ranges (roughly one year) to protect system stability.
$daysInRange = (int) floor((strtotime($dateEnd) - strtotime($dateStart)) / 86400) + 1;
if ($daysInRange > 366) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
}

// Terms of the selected school year: every special day must belong to a term.
$terms = $container->get(SchoolYearTermGateway::class)
    ->selectTermDetailsBySchoolYear($gibbonSchoolYearID)
    ->fetchAll(PDO::FETCH_ASSOC);

// Weekdays on which school is open.
$schoolWeekdays = array_column(
    $container->get(DaysOfWeekGateway::class)->selectSchoolWeekdays()->fetchAll(PDO::FETCH_ASSOC),
    'nameShort'
);

$specialDayGateway = $container->get(SchoolYearSpecialDayGateway::class);

// Dates that already have a special day: a date may only have one, so these are skipped.
$existing = array();
$existingDays = $specialDayGateway->selectSpecialDaysByDateRange($dateStart, $dateEnd);
while ($row = $existingDays->fetch()) {
    $existing[$row['date']] = true;
}

$addedCount = 0;
$skippedCount = 0;

$connection2->beginTransaction();

try {
for ($timestamp = strtotime($dateStart); $timestamp <= strtotime($dateEnd); $timestamp = strtotime('+1 day', $timestamp)) {
    $date = date('Y-m-d', $timestamp);

    // Find the term this date falls in, if any.
    $gibbonSchoolYearTermID = null;
    foreach ($terms as $term) {
        if ($date >= $term['firstDay'] && $date <= $term['lastDay']) {
            $gibbonSchoolYearTermID = $term['gibbonSchoolYearTermID'];
            break;
        }
    }

    // Only add for actual school days; skip everything else.
    $isSchoolDay = !empty($gibbonSchoolYearTermID)
        && in_array(date('D', $timestamp), $schoolWeekdays, true)
        && !isset($existing[$date]);

    if (!$isSchoolDay) {
        $skippedCount++;
        continue;
    }

    $inserted = $specialDayGateway->insert(array_merge($data, array(
        'gibbonSchoolYearTermID' => $gibbonSchoolYearTermID,
        'date'                   => $date,
    )));

    if (empty($inserted)) {
        throw new RuntimeException('Unable to insert special day');
    }

    $existing[$date] = true;
    $addedCount++;
}
    $connection2->commit();
} catch (Throwable $e) {
    if ($connection2->inTransaction()) $connection2->rollBack();
    $URL .= '&return=error2';
    header("Location: {$URL}");
    exit;
}

$URL .= '&return=success0&added=' . $addedCount . '&skipped=' . $skippedCount;
header("Location: {$URL}");
exit;
