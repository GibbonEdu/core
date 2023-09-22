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
use Gibbon\Http\Url;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonReportingCycleID = $_POST['gibbonReportingCycleID'] ?? '';
$gibbonSchoolYearID = $_POST['gibbonSchoolYearID'] ?? '';

$URL = Url::fromModuleRoute('Reports', 'reporting_cycles_manage_edit')
    ->withQueryParam('gibbonReportingCycleID', $gibbonReportingCycleID);

if (isActionAccessible($guid, $connection2, '/modules/Reports/reporting_cycles_manage_edit.php') == false) {
    header("Location: {$URL->withReturn('error0')}");
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
        $data['milestones'] = array_combine(array_keys($_POST['order'] ?? []), array_values($data['milestones']));
        ksort($data['milestones']);
        $data['milestones'] = json_encode($data['milestones']);
    }

    // Validate the required values are present
    if (empty($gibbonReportingCycleID) || empty($gibbonSchoolYearID) || empty($data['name']) || empty($data['nameShort'])) {
        $URL = $URL->withReturn('error1');
        header("Location: {$URL}");
        exit;
    }

    // Validate the database relationships exist
    if (!$reportingCycleGateway->exists($gibbonReportingCycleID)) {
        $URL = $URL->withReturn('error2');
        header("Location: {$URL}");
        exit;
    }

    // Validate that this record is unique
    if (!$reportingCycleGateway->unique($data, ['name', 'gibbonSchoolYearID'], $gibbonReportingCycleID)) {
        $URL = $URL->withReturn('error7');
        header("Location: {$URL}");
        exit;
    }

    // Update the record
    $updated = $reportingCycleGateway->update($gibbonReportingCycleID, $data);

    $URL = $URL->withReturn(!$updated ? 'error2' : 'success0');
    header("Location: {$URL}");
}
