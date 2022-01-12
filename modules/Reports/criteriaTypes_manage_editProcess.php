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

$gibbonReportingCriteriaTypeID = $_POST['gibbonReportingCriteriaTypeID'] ?? '';

$URL = $gibbon->session->get('absoluteURL').'/index.php?q=/modules/Reports/criteriaTypes_manage_edit.php&gibbonReportingCriteriaTypeID='.$gibbonReportingCriteriaTypeID;

if (isActionAccessible($guid, $connection2, '/modules/Reports/criteriaTypes_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $criteriaTypeGateway = $container->get(ReportingCriteriaTypeGateway::class);

    $data = [
        'name'           => $_POST['name'] ?? '',
        'active'         => $_POST['active'] ?? '',
        'defaultValue'   => $_POST['defaultValue'] ?? null,
        'characterLimit' => $_POST['characterLimit'] ?? '',
    ];

    // Validate the required values are present
    if (empty($gibbonReportingCriteriaTypeID) || empty($data['name'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate the database relationships exist
    if (!$criteriaTypeGateway->exists($gibbonReportingCriteriaTypeID)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Validate that this record is unique
    if (!$criteriaTypeGateway->unique($data, ['name'], $gibbonReportingCriteriaTypeID)) {
        $URL .= '&return=error7';
        header("Location: {$URL}");
        exit;
    }

    // Update the record
    $updated = $criteriaTypeGateway->update($gibbonReportingCriteriaTypeID, $data);

    $URL .= !$updated
        ? "&return=error2"
        : "&return=success0";

    header("Location: {$URL}");
}
