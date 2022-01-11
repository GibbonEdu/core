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

use Gibbon\Module\Reports\Domain\ReportingCriteriaTypeGateway;
use Gibbon\Services\Format;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$URL = $gibbon->session->get('absoluteURL').'/index.php?q=/modules/Reports/criteriaTypes_manage_add.php';

if (isActionAccessible($guid, $connection2, '/modules/Reports/criteriaTypes_manage_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $reportArchiveGateway = $container->get(ReportingCriteriaTypeGateway::class);

    $data = [
        'name'           => $_POST['name'] ?? '',
        'active'         => $_POST['active'] ?? '',
        'valueType'      => $_POST['valueType'] ?? '',
        'defaultValue'   => $_POS['defaultValue'] ?? null,
        'characterLimit' => $_POST['characterLimit'] ?? null,
        'gibbonScaleID'  => $_POST['gibbonScaleID'] ?? null,
    ];

    // Validate the required values are present
    if (empty($data['name']) || empty($data['valueType'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate that this record is unique
    if (!$reportArchiveGateway->unique($data, ['name'])) {
        $URL .= '&return=error7';
        header("Location: {$URL}");
        exit;
    }

    // Create the record
    $gibbonReportingCriteriaTypeID = $reportArchiveGateway->insert($data);

    $URL .= !$gibbonReportingCriteriaTypeID
        ? "&return=error2"
        : "&return=success0";

    header("Location: {$URL}&editID=$gibbonReportingCriteriaTypeID");
}
