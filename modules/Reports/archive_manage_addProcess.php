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

use Gibbon\Module\Reports\Domain\ReportArchiveGateway;
use Gibbon\Services\Format;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Reports/archive_manage_add.php';

if (isActionAccessible($guid, $connection2, '/modules/Reports/archive_manage_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $reportArchiveGateway = $container->get(ReportArchiveGateway::class);

    $data = [
        'name'             => $_POST['name'] ?? '',
        'path'             => $_POST['path'] ?? '',
        'readonly'         => $_POST['readonly'] ?? 'Y',
        'viewableStaff'    => $_POST['viewableStaff'] ?? 'N',
        'viewableStudents' => $_POST['viewableStudents'] ?? 'N',
        'viewableParents'  => $_POST['viewableParents'] ?? 'N',
        'viewableOther'    => $_POST['viewableOther'] ?? 'N',
    ];

    $data['path'] = '/'.trim($data['path'], '/');

    // Validate the required values are present
    if (empty($data['name']) || empty($data['path'])) {
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
    $gibbonReportArchiveID = $reportArchiveGateway->insert($data);

    $URL .= !$gibbonReportArchiveID
        ? "&return=error2"
        : "&return=success0";

    header("Location: {$URL}&editID=$gibbonReportArchiveID");
}
