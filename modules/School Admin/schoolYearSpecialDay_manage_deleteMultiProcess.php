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
use Gibbon\Domain\School\SchoolYearSpecialDayGateway;
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
$type = $_POST['type'] ?? '';

$allowedTypes = array('School Closure', 'Timing Change', 'Off Timetable');

if (empty($gibbonSchoolYearID) || empty($dateStart) || empty($dateEnd) || ($type !== '' && !in_array($type, $allowedTypes, true))) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
}

// Normalise an inverted range.
if ($dateStart > $dateEnd) {
    list($dateStart, $dateEnd) = array($dateEnd, $dateStart);
}

// Guard against runaway ranges (roughly one year).
$daysInRange = (int) floor((strtotime($dateEnd) - strtotime($dateStart)) / 86400) + 1;
if ($daysInRange > 366) {
    $URL .= '&return=error1';
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
$params = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'dateStart' => $dateStart, 'dateEnd' => $dateEnd);

if ($type !== '') {
    $sql .= " AND gibbonSchoolYearSpecialDay.type = :type";
    $params['type'] = $type;
}

$result = $connection2->prepare($sql);
$result->execute($params);
$ids = $result->fetchAll(PDO::FETCH_COLUMN);

$specialDayGateway = $container->get(SchoolYearSpecialDayGateway::class);

$deletedCount = 0;
foreach ($ids as $id) {
    if ($specialDayGateway->delete($id)) {
        $deletedCount++;
    }
}

$URL .= '&return=success0&deleted=' . $deletedCount;
header("Location: {$URL}");
exit;
