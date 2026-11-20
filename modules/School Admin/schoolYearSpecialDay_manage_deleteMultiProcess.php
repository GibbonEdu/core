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
use Gibbon\Domain\School\SchoolYearGateway;
use Gibbon\Domain\School\SchoolYearSpecialDayGateway;
use Gibbon\Services\Format;

require_once __DIR__ . '/../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonSchoolYearID = is_scalar($_POST['gibbonSchoolYearID'] ?? null)
    ? trim((string) $_POST['gibbonSchoolYearID'])
    : '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/School Admin/schoolYearSpecialDay_manage.php&gibbonSchoolYearID='.rawurlencode($gibbonSchoolYearID);

if (isActionAccessible($guid, $connection2, '/modules/School Admin/schoolYearSpecialDay_manage_delete.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
}

$dateStart = !empty($_POST['dateStart']) && is_scalar($_POST['dateStart']) ? Format::dateConvert((string) $_POST['dateStart']) : '';
$dateEnd = !empty($_POST['dateEnd']) && is_scalar($_POST['dateEnd']) ? Format::dateConvert((string) $_POST['dateEnd']) : '';
$type = is_scalar($_POST['type'] ?? null) ? (string) $_POST['type'] : '';

$allowedTypes = array('School Closure', 'Timing Change', 'Off Timetable');

$startDate = DateTimeImmutable::createFromFormat('!Y-m-d', $dateStart);
$endDate = DateTimeImmutable::createFromFormat('!Y-m-d', $dateEnd);

if (
    empty($gibbonSchoolYearID)
    || !$startDate
    || !$endDate
    || $startDate->format('Y-m-d') !== $dateStart
    || $endDate->format('Y-m-d') !== $dateEnd
    || ($type !== '' && !in_array($type, $allowedTypes, true))
) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
}

// Normalise an inverted range.
if ($startDate > $endDate) {
    list($startDate, $endDate) = [$endDate, $startDate];
}

// Guard against runaway ranges (roughly one year).
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

// Only ever delete special days that belong to a term of the selected school year.
$sql = "SELECT gibbonSchoolYearSpecialDay.gibbonSchoolYearSpecialDayID
        FROM gibbonSchoolYearSpecialDay
        JOIN gibbonSchoolYearTerm ON gibbonSchoolYearTerm.gibbonSchoolYearTermID = gibbonSchoolYearSpecialDay.gibbonSchoolYearTermID
        WHERE gibbonSchoolYearTerm.gibbonSchoolYearID = :gibbonSchoolYearID
          AND gibbonSchoolYearSpecialDay.date >= :dateStart
          AND gibbonSchoolYearSpecialDay.date <= :dateEnd";
$params = array(
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
    'dateStart' => $startDate->format('Y-m-d'),
    'dateEnd' => $endDate->format('Y-m-d'),
);

if ($type !== '') {
    $sql .= " AND gibbonSchoolYearSpecialDay.type = :type";
    $params['type'] = $type;
}

$result = $connection2->prepare($sql);
$result->execute($params);
$ids = $result->fetchAll(PDO::FETCH_COLUMN);

$specialDayGateway = $container->get(SchoolYearSpecialDayGateway::class);

$deletedCount = 0;
$connection2->beginTransaction();
try {
    foreach ($ids as $id) {
        if (!$specialDayGateway->delete($id)) {
            throw new RuntimeException('Unable to delete special day');
        }
        $deletedCount++;
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

$URL .= '&return=success0&deleted=' . $deletedCount;
header("Location: {$URL}");
exit;
