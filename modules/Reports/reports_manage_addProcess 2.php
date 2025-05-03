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

use Gibbon\Module\Reports\Domain\ReportGateway;
use Gibbon\Services\Format;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Reports/reports_manage_add.php&gibbonSchoolYearID='.$gibbonSchoolYearID;

if (isActionAccessible($guid, $connection2, '/modules/Reports/reports_manage_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $reportGateway = $container->get(ReportGateway::class);

    $data = [
        'gibbonSchoolYearID'     => $_POST['gibbonSchoolYearID'] ?? '',
        'gibbonReportArchiveID'  => $_POST['gibbonReportArchiveID'] ?? '',
        'gibbonReportingCycleID' => $_POST['gibbonReportingCycleID'] ?? null,
        'gibbonReportTemplateID' => $_POST['gibbonReportTemplateID'] ?? '',
        'name'                   => $_POST['name'] ?? '',
        'active'                 => $_POST['active'] ?? 'Y',
        'gibbonYearGroupIDList'  => isset($_POST['gibbonYearGroupIDList'])? implode(',', $_POST['gibbonYearGroupIDList']) : null,
    ];

    if (!empty($_POST['accessDate'])) {
        $data['accessDate'] = Format::dateConvert($_POST['accessDate']).' '.($_POST['accessTime'] ?? '00:00');
    }

    // Validate the required values are present
    if (empty($data['gibbonSchoolYearID']) || empty($data['gibbonReportTemplateID']) || empty($data['name'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate that this record is unique
    if (!$reportGateway->unique($data, ['gibbonSchoolYearID', 'name'])) {
        $URL .= '&return=error7';
        header("Location: {$URL}");
        exit;
    }

    // Create the record
    $gibbonReportID = $reportGateway->insert($data);

    $URL .= !$gibbonReportID
        ? "&return=error2"
        : "&return=success0";

    header("Location: {$URL}&editID=$gibbonReportID");
}
