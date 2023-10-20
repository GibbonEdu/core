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
use Gibbon\Domain\School\SchoolYearSpecialDayGateway;

include '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonSchoolYearID = $_POST['gibbonSchoolYearID'] ?? '';
$firstDay = $_POST['firstDay'] ?? '';
$lastDay = $_POST['lastDay'] ?? '';
$dateStamp = $_POST['dateStamp'] ?? '';

$URL = $session->get('absoluteURL')."/index.php?q=/modules/School Admin/schoolYearSpecialDay_manage.php&gibbonSchoolYearID=$gibbonSchoolYearID";

if (isActionAccessible($guid, $connection2, '/modules/School Admin/schoolYearSpecialDay_manage_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $data = [
        'date'                   => !empty($_POST['date']) ? Format::dateConvert($_POST['date']) : '',
        'type'                   => $_POST['type'] ?? '',
        'name'                   => $_POST['name'] ?? '',
        'description'            => $_POST['description'] ?? '',
        'gibbonSchoolYearTermID' => $_POST['gibbonSchoolYearTermID'] ?? '',
        'schoolOpen'             => null,
        'schoolStart'            => null,
        'schoolEnd'              => null,
        'schoolClose'            => null,
        'gibbonYearGroupIDList'  => $_POST['gibbonYearGroupIDList'] ?? '',
        'gibbonFormGroupIDList'  => $_POST['gibbonFormGroupIDList'] ?? '',
        'cancelActivities'       => $_POST['cancelActivities'] ?? 'N',
    ];

    if (!empty($_POST['schoolOpenH']) && is_numeric($_POST['schoolOpenH']) && is_numeric($_POST['schoolOpenM'])) {
        $data['schoolOpen'] = $_POST['schoolOpenH'].':'.$_POST['schoolOpenM'].':00';
    }

    if (!empty($_POST['schoolStartH']) && is_numeric($_POST['schoolStartH']) && is_numeric($_POST['schoolStartM'])) {
        $data['schoolStart'] = $_POST['schoolStartH'].':'.$_POST['schoolStartM'].':00';
    }

    if (!empty($_POST['schoolEndH']) && is_numeric($_POST['schoolEndH']) && is_numeric($_POST['schoolEndM'])) {
        $data['schoolEnd'] = $_POST['schoolEndH'].':'.$_POST['schoolEndM'].':00';
    }

    if (!empty($_POST['schoolCloseH']) && is_numeric($_POST['schoolCloseH']) && is_numeric($_POST['schoolCloseM'])) {
        $data['schoolClose'] = $_POST['schoolCloseH'].':'.$_POST['schoolCloseM'].':00';
    }

    if (!empty($data['gibbonYearGroupIDList']) && is_array($data['gibbonYearGroupIDList'])) {
        $data['gibbonYearGroupIDList'] = implode(',', $data['gibbonYearGroupIDList']);
    }

    if (!empty($data['gibbonFormGroupIDList']) && is_array($data['gibbonFormGroupIDList'])) {
        $data['gibbonFormGroupIDList'] = implode(',', $data['gibbonFormGroupIDList']);
    }

    // Validate Inputs
    if (empty($data['date']) || empty($data['type']) || empty($data['name']) || empty($gibbonSchoolYearID) || empty($dateStamp) || empty($data['gibbonSchoolYearTermID']) || empty($firstDay) || empty($lastDay)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    } 

    $specialDayGateway = $container->get(SchoolYearSpecialDayGateway::class);

    if (!$specialDayGateway->unique($data, ['date'])) {
        $URL .= '&return=error7';
        header("Location: {$URL}");
        exit;
    }

    if ($dateStamp < $firstDay or $dateStamp > $lastDay) {
        $URL .= '&return=warning1';
        header("Location: {$URL}");
        exit;
    } 

    // Write to database
    $gibbonSchoolYearSpecialDayID = $specialDayGateway->insert($data);

    $URL .= empty($gibbonSchoolYearSpecialDayID)
        ? '&return=error2'
        : '&return=success0';
    header("Location: {$URL}");
}
