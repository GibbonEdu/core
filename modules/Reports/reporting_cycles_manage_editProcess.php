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

use Gibbon\Module\Reports\Domain\ReportingCycleGateway;
use Gibbon\Services\Format;

require_once '../../gibbon.php';

$gibbonReportingCycleID = $_POST['gibbonReportingCycleID'] ?? '';
$gibbonSchoolYearID = $_POST['gibbonSchoolYearID'] ?? '';

$URL = $gibbon->session->get('absoluteURL').'/index.php?q=/modules/Reports/reporting_cycles_manage_edit.php&gibbonReportingCycleID='.$gibbonReportingCycleID;

if (isActionAccessible($guid, $connection2, '/modules/Reports/reporting_cycles_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $reportingCycleGateway = $container->get(ReportingCycleGateway::class);

    $data = [
        'gibbonSchoolYearID'    => $gibbonSchoolYearID,
        'gibbonYearGroupIDList' => isset($_POST['gibbonYearGroupIDList'])? implode(',', $_POST['gibbonYearGroupIDList']) : null,
        'name'                  => $_POST['name'] ?? '',
        'nameShort'             => $_POST['nameShort'] ?? '',
        'dateStart'             => $_POST['dateStart'] ?? '',
        'dateEnd'               => $_POST['dateEnd'] ?? '',
        'cycleNumber'           => $_POST['cycleNumber'] ?? '1',
        'cycleTotal'            => $_POST['cycleTotal'] ?? '1',
        'notes'                 => $_POST['notes'] ?? '',
        'milestones'            => $_POST['milestones'] ?? '',
    ];

    $data['dateStart'] = Format::dateConvert($data['dateStart']);
    $data['dateEnd'] = Format::dateConvert($data['dateEnd']);

    // Sort and save milestones as a JSON blob
    $data['milestones'] = array_map(function ($item) {
        $item['milestoneDate'] = Format::dateConvert($item['milestoneDate']);
        return $item;
    }, $data['milestones']);
    $data['milestones'] = array_combine(array_keys($_POST['order']), array_values($data['milestones']));
    ksort($data['milestones']);
    $data['milestones'] = json_encode($data['milestones']);
    
    // Validate the required values are present
    if (empty($gibbonReportingCycleID) || empty($gibbonSchoolYearID) || empty($data['name']) || empty($data['nameShort'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate the database relationships exist
    if (!$reportingCycleGateway->exists($gibbonReportingCycleID)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Validate that this record is unique
    if (!$reportingCycleGateway->unique($data, ['name', 'gibbonSchoolYearID'], $gibbonReportingCycleID)) {
        $URL .= '&return=error7';
        header("Location: {$URL}");
        exit;
    }

    // Update the record
    $updated = $reportingCycleGateway->update($gibbonReportingCycleID, $data);

    $URL .= !$updated
        ? "&return=error2"
        : "&return=success0";

    header("Location: {$URL}");
}
