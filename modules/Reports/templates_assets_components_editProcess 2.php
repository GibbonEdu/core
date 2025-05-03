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

use Gibbon\Module\Reports\Domain\ReportPrototypeSectionGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Data\Validator;

ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['templateContent' => 'RAW']);
require_once __DIR__.'/moduleFunctions.php';

$gibbonReportPrototypeSectionID = $_POST['gibbonReportPrototypeSectionID'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Reports/templates_assets_components_edit.php&gibbonReportPrototypeSectionID='.$gibbonReportPrototypeSectionID.'&sidebar=false';

if (isActionAccessible($guid, $connection2, '/modules/Reports/templates_assets_components_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $partialFail = false;
    $prototypeGateway = $container->get(ReportPrototypeSectionGateway::class);
    
    if (empty($gibbonReportPrototypeSectionID)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    $values = $prototypeGateway->getByID($gibbonReportPrototypeSectionID);
    if (empty($values)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }
    
    // Update file contents
    $absolutePath = $session->get('absolutePath');
    $customAssetPath = $container->get(SettingGateway::class)->getSettingByScope('Reports', 'customAssetPath');
    $templatePath = $absolutePath.$customAssetPath.'/templates';

    $templateContent = $_POST['templateContent'] ?? '';

    $fileUpdated = file_put_contents($templatePath.'/'.$values['templateFile'], $templateContent);
    $partialFail &= !$fileUpdated;

    // Parse and update template data based on yaml front-matter
    if ($data = parseComponent($templatePath, $templatePath.'/'.$values['templateFile'], 'Additional')) {
        $updated = $prototypeGateway->update($gibbonReportPrototypeSectionID, $data);
        $partialFail &= !$updated;
    }

    // Clear reports cache if this is not a development system (for ease of templating)
    if ($session->get('installType') != 'Development') {
        include $session->get('absolutePath').'/modules/System Admin/moduleFunctions.php';
        removeDirectoryContents($session->get('absolutePath').'/uploads/cache/reports');
    }

    $URL .= !$updated
        ? "&return=error2"
        : "&return=success0";

    header("Location: {$URL}");
}
