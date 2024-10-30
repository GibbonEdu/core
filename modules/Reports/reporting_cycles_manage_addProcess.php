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

use Gibbon\Module\Reports\Domain\ReportingCycleGateway;
use Gibbon\Services\Format;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Reports/reporting_cycles_manage_add.php';

if (isActionAccessible($guid, $connection2, '/modules/Reports/reporting_cycles_manage_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $reportingCycleGateway = $container->get(ReportingCycleGateway::class);

    $data = [
        'gibbonSchoolYearID'    => $session->get('gibbonSchoolYearID'),
        'gibbonYearGroupIDList' => isset($_POST['gibbonYearGroupIDList'])? implode(',', $_POST['gibbonYearGroupIDList']) : null,
        'name'                  => $_POST['name'] ?? '',
        'nameShort'             => $_POST['nameShort'] ?? '',
        'dateStart'             => $_POST['dateStart'] ?? '',
        'dateEnd'               => $_POST['dateEnd'] ?? '',
        'cycleNumber'           => $_POST['cycleNumber'] ?? '1',
        'cycleTotal'            => $_POST['cycleTotal'] ?? '1',
        'notes'                 => $_POST['notes'] ?? '',
        'milestones'            => $_POST['milestones'] ?? [],
    ];

    $data['dateStart'] = Format::dateConvert($data['dateStart']);
    $data['dateEnd'] = Format::dateConvert($data['dateEnd']);

    // Sort and save milestones as a JSON blob
    if (!empty($data['milestones'])) {
        $data['milestones'] = array_map(function ($item) {
            $item['milestoneDate'] = Format::dateConvert($item['milestoneDate']);
            return $item;
        }, $data['milestones']);
        $data['milestones'] = array_combine(array_keys($_POST['order']), array_values($data['milestones']));
        ksort($data['milestones']);
        $data['milestones'] = json_encode($data['milestones']);
    }

    // Validate the required values are present
    if (empty($data['name']) || empty($data['nameShort']) || empty($data['gibbonSchoolYearID'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate that this record is unique
    if (!$reportingCycleGateway->unique($data, ['name', 'gibbonSchoolYearID'])) {
        $URL .= '&return=error7';
        header("Location: {$URL}");
        exit;
    }

    // Create the record
    $gibbonReportingCycleID = $reportingCycleGateway->insert($data);

    $URL .= !$gibbonReportingCycleID
        ? "&return=error2"
        : "&return=success0";

    header("Location: {$URL}&editID=$gibbonReportingCycleID");
}
