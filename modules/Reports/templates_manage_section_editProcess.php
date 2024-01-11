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

use Gibbon\FileUploader;
use Gibbon\Data\Validator;
use Gibbon\Module\Reports\Domain\ReportTemplateGateway;
use Gibbon\Module\Reports\Domain\ReportTemplateSectionGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['templateContent' => 'RAW', 'text' => 'HTML']);

$gibbonReportTemplateID = $_POST['gibbonReportTemplateID'] ?? '';
$gibbonReportTemplateSectionID = $_POST['gibbonReportTemplateSectionID'] ?? '';
$search = $_GET['search'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Reports/templates_manage_section_edit.php&gibbonReportTemplateID='.$gibbonReportTemplateID.'&gibbonReportTemplateSectionID='.$gibbonReportTemplateSectionID.'&search='.$search;

if (isActionAccessible($guid, $connection2, '/modules/Reports/templates_manage_section_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $templateGateway = $container->get(ReportTemplateGateway::class);
    $templateSectionGateway = $container->get(ReportTemplateSectionGateway::class);

    $config = $_POST['config'] ?? [];
    $data = [
        'name'            => $_POST['name'] ?? '',
        'page'            => $_POST['pageCustom'] ?? $_POST['page'] ?? '',
        'templateParams'  => json_encode($_POST['templateParams'] ?? []),
        'templateContent' => $_POST['templateContent'] ?? '',
    ];

    // Handle bitwise flags
    if (!empty($_POST['flags']) && is_array($_POST['flags'])) {
        $data['flags'] = array_reduce($_POST['flags'], function ($group, $item) {
            return $group |= $item;
        }, 0);
    } else {
        $data['flags'] = 0;
    }

    // Handle file uploads for custom config fields
    foreach ($config ?? [] as $configName => $configValue) {
        if (!empty($_FILES['config']['tmp_name'][$configName])) {
            $file = array_reduce(array_keys($_FILES['config']), function ($group, $itemName) use ($configName) {
                $group[$itemName] = $_FILES['config'][$itemName][$configName] ?? null;
                return $group;
            }, []);

            // Upload the file, return the /uploads relative path
            $fileUploader = empty($fileUploader) ? new FileUploader($pdo, $session) : $fileUploader;
            $config[$configName] = $fileUploader->uploadFromPost($file, $configName.'_file');
        }
    }

    $data['config'] = json_encode($config);

    // Validate the required values are present
    if (empty($gibbonReportTemplateID) || empty($gibbonReportTemplateSectionID)  || empty($data['name'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }
   
    $result = $templateSectionGateway->selectBy([
        'gibbonReportTemplateID'        => $gibbonReportTemplateID,
        'gibbonReportTemplateSectionID' => $gibbonReportTemplateSectionID,
    ]);

    // Validate the database relationships exist
    if ($result->rowCount() == 0) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Update the record
    $updated = $templateSectionGateway->update($gibbonReportTemplateSectionID, $data);

    $URL .= !$updated
        ? "&return=error2"
        : "&return=success0";

    header("Location: {$URL}&editID=$updated");
}
