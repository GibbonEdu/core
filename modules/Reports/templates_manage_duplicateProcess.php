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
use Gibbon\Module\Reports\Domain\ReportTemplateGateway;
use Gibbon\Module\Reports\Domain\ReportTemplateSectionGateway;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonReportTemplateID = $_POST['gibbonReportTemplateID'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Reports/templates_manage_duplicate.php&gibbonReportTemplateID='.$gibbonReportTemplateID;

if (isActionAccessible($guid, $connection2, '/modules/Reports/templates_manage_duplicate.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $reportTemplateGateway = $container->get(ReportTemplateGateway::class);
    $reportTemplateSectionGateway = $container->get(ReportTemplateSectionGateway::class);

    $data = [
        'name'    => $_POST['name'] ?? '',
        'context' => $_POST['context'] ?? '',
    ];

    // Validate the required values are present
    if (empty($gibbonReportTemplateID) || empty($data['name']) || empty($data['context'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }
    
    // Validate the database relationships exist
    $values = $reportTemplateGateway->getByID($gibbonReportTemplateID);
    if (empty($values)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Validate that this record is unique
    if (!$reportTemplateGateway->unique($data, ['name'], $gibbonReportTemplateID)) {
        $URL .= '&return=error7';
        header("Location: {$URL}");
        exit;
    }

    // Update duplicated values
    $data['orientation'] = $values['orientation'];
    $data['pageSize'] = $values['pageSize'];
    $data['marginX'] = $values['marginX'];
    $data['marginY'] = $values['marginY'];
    $data['stylesheet'] = $values['stylesheet'];
    $data['flags'] = $values['flags'];
    $data['config'] = $values['config'];

    // Create the record
    $gibbonReportTemplateIDNew = $reportTemplateGateway->insert($data);
    $failedSections = 0;

    if (empty($gibbonReportTemplateIDNew)) {
        $URL .= "&return=error2";
        header("Location: {$URL}");
    }

    // Duplicate the template sections
    $sections = $reportTemplateSectionGateway->selectBy(['gibbonReportTemplateID' => $gibbonReportTemplateID])->fetchAll();
    foreach ($sections as $sectionData) {
        $sectionData['gibbonReportTemplateID'] = $gibbonReportTemplateIDNew;
        $gibbonReportTemplateSectionIDNew = $reportTemplateSectionGateway->insert($sectionData);
        $failedSections += empty($gibbonReportTemplateSectionIDNew);
    }

    $URL .= !empty($failedSections)
        ? "&return=warning1&failedSections=$failedSections"
        : "&return=success0";

    header("Location: {$URL}&editID=$gibbonReportTemplateIDNew");
}
