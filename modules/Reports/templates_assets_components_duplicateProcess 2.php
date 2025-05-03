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

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Reports/templates_assets.php';

if (isActionAccessible($guid, $connection2, '/modules/Reports/templates_assets_components_duplicate.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $gibbonReportPrototypeSectionID = $_POST['gibbonReportPrototypeSectionID'] ?? '';

    // Get filename and remove all potential path traversal information
    $templateFileDestination = trim(basename($_POST['templateFileDestination'] ?? ''), '/ ');
    $templateFileDestination = preg_replace('/[^a-zA-Z0-9\-\_.]/', '', $templateFileDestination);

    // Check for required file extension
    if (strtolower(mb_substr($templateFileDestination, -10, 10)) != '.twig.html' || stripos($templateFileDestination, './') !== false) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    $prototypeGateway = $container->get(ReportPrototypeSectionGateway::class);

    $data = $prototypeGateway->getByID($gibbonReportPrototypeSectionID);
    if (empty($gibbonReportPrototypeSectionID) || empty($templateFileDestination) || empty($data)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }
    
    $absolutePath = $session->get('absolutePath');
    $customAssetPath = $container->get(SettingGateway::class)->getSettingByScope('Reports', 'customAssetPath');

    $sourcePath = $data['type'] == 'Core'
        ? $absolutePath.'/modules/Reports/templates/'.$data['templateFile']
        : $absolutePath.$customAssetPath.'/templates/'.$data['templateFile'];

    $sourceDir = str_replace('reports/', '', dirname($data['templateFile']));

    $destinationPath = $absolutePath.$customAssetPath.'/templates/'.$sourceDir.'/'.$templateFileDestination;

    if (!is_dir(dirname($destinationPath))) {
        mkdir(dirname($destinationPath), 0755, true);
    }

    if (copy($sourcePath, $destinationPath)) {
        chmod($destinationPath, 0755);
        $data['type'] = 'Additional';
        $data['templateFile'] = $sourceDir.'/'.$templateFileDestination;
        $duplicated = $prototypeGateway->insert($data);
    } else {
        $duplicated = false;
    }

    $URL .= !$duplicated
        ? "&return=error2"
        : "&return=success0";

    header("Location: {$URL}");
}
