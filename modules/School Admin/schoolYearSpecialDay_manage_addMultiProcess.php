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
use Gibbon\Domain\School\SchoolYearGateway;
use Gibbon\Domain\School\SchoolYearSpecialDayGateway;
use Gibbon\Domain\School\SchoolYearTermGateway;
use Gibbon\Services\Format;

require_once __DIR__ . '/../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonSchoolYearID = is_scalar($_POST['gibbonSchoolYearID'] ?? null)
    ? trim((string) $_POST['gibbonSchoolYearID'])
    : '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/School Admin/schoolYearSpecialDay_manage.php&gibbonSchoolYearID='.rawurlencode($gibbonSchoolYearID);

if (isActionAccessible($guid, $connection2, '/modules/School Admin/schoolYearSpecialDay_manage_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
}

$dateStart = !empty($_POST['dateStart']) && is_scalar($_POST['dateStart']) ? Format::dateConvert((string) $_POST['dateStart']) : '';
$dateEnd = !empty($_POST['dateEnd']) && is_scalar($_POST['dateEnd']) ? Format::dateConvert((string) $_POST['dateEnd']) : '';

$normalizeIDList = function ($value): ?string {
    $values = is_array($value) ? $value : [$value];
    $normalized = [];
    foreach ($values as $item) {
        if (empty($item)) {
            continue;
        }
        if (!is_scalar($item) || !ctype_digit((string) $item)) {
            return null;
        }
        $normalized[] = (string) $item;
    }

    return implode(',', array_unique($normalized));
};

// The bulk form mirrors the single-add fields for the chosen type.
$data = array(
    'type'                   => is_scalar($_POST['type'] ?? null) ? (string) $_POST['type'] : '',
    'name'                   => is_scalar($_POST['name'] ?? null) ? trim((string) $_POST['name']) : '',
    'description'            => is_scalar($_POST['description'] ?? null) ? trim((string) $_POST['description']) : '',
    'schoolOpen'             => null,
    'schoolStart'            => null,
    'schoolEnd'              => null,
    'schoolClose'            => null,
    'gibbonYearGroupIDList'  => $normalizeIDList($_POST['gibbonYearGroupIDList'] ?? ''),
    'gibbonFormGroupIDList'  => $normalizeIDList($_POST['gibbonFormGroupIDList'] ?? ''),
    'cancelActivities'       => ($_POST['cancelActivities'] ?? 'N') === 'Y' ? 'Y' : 'N',
    'cancelBookings'         => ($_POST['cancelBookings'] ?? 'N') === 'Y' ? 'Y' : 'N',
    'cancelClasses'          => ($_POST['cancelClasses'] ?? 'N') === 'Y' ? 'Y' : 'N',
    'cancelDuty'             => ($_POST['cancelDuty'] ?? 'N') === 'Y' ? 'Y' : 'N',
);

foreach (array('schoolOpen', 'schoolStart', 'schoolEnd', 'schoolClose') as $time) {
    $hour = $_POST[$time.'H'] ?? '';
    $minute = $_POST[$time.'M'] ?? '';
    if ($hour === '' && $minute === '') {
        continue;
    }
    if (!is_scalar($hour) || !is_scalar($minute) || !ctype_digit((string) $hour) || !ctype_digit((string) $minute)) {
        $data[$time] = null;
        continue;
    }
    $hour = (int) $hour;
    $minute = (int) $minute;
    if ($hour >= 0 && $hour <= 23 && $minute >= 0 && $minute <= 59) {
        $data[$time] = sprintf('%02d:%02d:00', $hour, $minute);
    }
}

$allowedTypes = array('School Closure', 'Timing Change', 'Off Timetable');

$startDate = DateTimeImmutable::createFromFormat('!Y-m-d', $dateStart);
$endDate = DateTimeImmutable::createFromFormat('!Y-m-d', $dateEnd);

if (
    empty($gibbonSchoolYearID)
    || !$startDate
    || !$endDate
    || $startDate->format('Y-m-d') !== $dateStart
    || $endDate->format('Y-m-d') !== $dateEnd
    || empty($data['name'])
    || mb_strlen($data['name']) > 20
    || mb_strlen($data['description']) > 255
    || !in_array($data['type'], $allowedTypes, true)
    || $data['gibbonYearGroupIDList'] === null
    || $data['gibbonFormGroupIDList'] === null
) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
}

// Normalise an inverted range.
if ($startDate > $endDate) {
    list($startDate, $endDate) = [$endDate, $startDate];
}

// Guard against runaway ranges (roughly one year) to protect system stability.
$daysInRange = (int) $startDate->diff($endDate)->days + 1;
if ($daysInRange > 366) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
}

$schoolYear = $container->get(SchoolYearGateway::class)->getByID($gibbonSchoolYearID);
if (empty($schoolYear)) {
    $URL .= '&return=error2';
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

$validateReferenceList = function (string $list, string $sql, array $params) use ($connection2): bool {
    if ($list === '') {
        return true;
    }

    $ids = array_values(array_filter(explode(',', $list)));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $statement = $connection2->prepare(str_replace(':ids', $placeholders, $sql));
    $statement->execute(array_merge($params, $ids));

    return count($statement->fetchAll(PDO::FETCH_COLUMN)) === count($ids);
};

if (!$validateReferenceList(
    $data['gibbonYearGroupIDList'],
    'SELECT gibbonYearGroupID FROM gibbonYearGroup WHERE gibbonYearGroupID IN (:ids)',
    []
) || !$validateReferenceList(
    $data['gibbonFormGroupIDList'],
    'SELECT gibbonFormGroupID FROM gibbonFormGroup WHERE gibbonSchoolYearID = ? AND gibbonFormGroupID IN (:ids)',
    [$gibbonSchoolYearID]
)) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
}

// Dates that already have a special day: a date may only have one, so these are skipped.
$existing = array();
$existingDays = $specialDayGateway->selectSpecialDaysByDateRange($startDate->format('Y-m-d'), $endDate->format('Y-m-d'));
while ($row = $existingDays->fetch()) {
    $existing[$row['date']] = true;
}

$addedCount = 0;
$skippedCount = 0;

$connection2->beginTransaction();

try {
    $dateEndExclusive = $endDate->modify('+1 day');
    foreach (new DatePeriod($startDate, new DateInterval('P1D'), $dateEndExclusive) as $day) {
        $date = $day->format('Y-m-d');

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
            && in_array($day->format('D'), $schoolWeekdays, true)
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
    if ($connection2->inTransaction()) {
        $connection2->rollBack();
    }
    $URL .= '&return=error2';
    header("Location: {$URL}");
    exit;
}

$URL .= '&return=success0&added=' . $addedCount . '&skipped=' . $skippedCount;
header("Location: {$URL}");
exit;
