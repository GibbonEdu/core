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

use Gibbon\Module\Reports\Domain\ReportTemplateGateway;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonReportTemplateID = $_POST['gibbonReportTemplateID'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Reports/templates_manage_edit.php&gibbonReportTemplateID='.$gibbonReportTemplateID.'&sidebar=false';

if (isActionAccessible($guid, $connection2, '/modules/Reports/templates_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $templateGateway = $container->get(ReportTemplateGateway::class);

    $data = [
        'name'        => $_POST['name'] ?? '',
        'orientation' => $_POST['orientation'] ?? '',
        'pageSize'    => $_POST['pageSize'] ?? '',
        'marginX'     => $_POST['marginX'] ?? '',
        'marginY'     => $_POST['marginY'] ?? '',
        'stylesheet'  => $_POST['stylesheet'] ?? '',
        'flags'       => $_POST['flags'] ?? '',
    ];

    $config = [
        'fonts' => $_POST['fonts'] ?? [],
    ];

    $data['config'] = json_encode($config);

    // Validate the required values are present
    if (empty($data['name'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate the database relationships exist
    if (!$templateGateway->exists($gibbonReportTemplateID)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Validate that this record is unique
    if (!$templateGateway->unique($data, ['name'], $gibbonReportTemplateID)) {
        $URL .= '&return=error7';
        header("Location: {$URL}");
        exit;
    }

    // Update the record
    $updated = $templateGateway->update($gibbonReportTemplateID, $data);

    $URL .= !$updated
        ? "&return=error2"
        : "&return=success0";

    header("Location: {$URL}");
}
