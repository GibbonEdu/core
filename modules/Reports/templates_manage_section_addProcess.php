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

use Gibbon\Module\Reports\Domain\ReportTemplateGateway;
use Gibbon\Module\Reports\Domain\ReportTemplateSectionGateway;

require_once '../../gibbon.php';

$gibbonReportTemplateID = $_POST['gibbonReportTemplateID'] ?? '';
$search = $_GET['search'] ?? '';

$URL = $gibbon->session->get('absoluteURL').'/index.php?q=/modules/Reports/templates_manage_edit.php&gibbonReportTemplateID='.$gibbonReportTemplateID.'&sidebar=false&search='.$search;

if (isActionAccessible($guid, $connection2, '/modules/Reports/templates_manage_section_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $templateGateway = $container->get(ReportTemplateGateway::class);
    $templateSectionGateway = $container->get(ReportTemplateSectionGateway::class);

    $data = [
        'gibbonReportTemplateID' => $gibbonReportTemplateID ?? '',
        'gibbonReportPrototypeSectionID' => $_POST['gibbonReportPrototypeSectionID'] ?? '',
        'name' => $_POST['name'] ?? '',
        'type' => $_POST['type'] ?? '',
    ];

    // Validate the required values are present
    if (empty($data['gibbonReportTemplateID']) || empty($data['name']) || empty($data['type'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    $prototypeSection = $templateSectionGateway->getPrototypeSectionByID($data['gibbonReportPrototypeSectionID']);

    // Validate the database relationships exist
    if (empty($prototypeSection) || !$templateGateway->exists($gibbonReportTemplateID)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Load the content of the template file into the database
    // try {
    //     $templateFile = $container->get('twig')->load($prototypeSection['templateFile']);
    //     $data['templateContent'] = $templateFile->getSourceContext()->getCode();
    // } catch (\Exception $e) {
    //     $URL .= '&return=error2';
    //     header("Location: {$URL}");
    //     exit;
    // }

    // Create the record
    $gibbonReportTemplateSectionID = $templateSectionGateway->insert($data);

    $URL .= !$gibbonReportTemplateSectionID
        ? "&return=error2"
        : "&return=success0";

    header("Location: {$URL}&editID=$gibbonReportTemplateSectionID");
}
