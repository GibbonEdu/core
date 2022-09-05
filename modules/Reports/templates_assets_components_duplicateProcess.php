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

use Gibbon\Module\Reports\Domain\ReportPrototypeSectionGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$URL = $gibbon->session->get('absoluteURL').'/index.php?q=/modules/Reports/templates_assets.php';

if (isActionAccessible($guid, $connection2, '/modules/Reports/templates_assets_components_duplicate.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $gibbonReportPrototypeSectionID = $_POST['gibbonReportPrototypeSectionID'] ?? '';
    $templateFileDestination = $_POST['templateFileDestination'] ?? '';
    $templateFileDestination = trim($templateFileDestination, '/ ');

    $prototypeGateway = $container->get(ReportPrototypeSectionGateway::class);

    $data = $prototypeGateway->getByID($gibbonReportPrototypeSectionID);
    if (empty($gibbonReportPrototypeSectionID) || empty($templateFileDestination) || empty($data)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }
    
    $absolutePath = $gibbon->session->get('absolutePath');
    $customAssetPath = $container->get(SettingGateway::class)->getSettingByScope('Reports', 'customAssetPath');

    $sourcePath = $data['type'] == 'Core'
        ? $absolutePath.'/modules/Reports/templates/'.$data['templateFile']
        : $absolutePath.$customAssetPath.'/templates/'.$data['templateFile'];

    $destinationPath = $absolutePath.$customAssetPath.'/templates/'.$templateFileDestination;

    if (!is_dir(dirname($destinationPath))) {
        mkdir(dirname($destinationPath), 0755, true);
    }

    if (copy($sourcePath, $destinationPath)) {
        chmod($destinationPath, 0755);
        $data['type'] = 'Additional';
        $data['templateFile'] = $templateFileDestination;
        $duplicated = $prototypeGateway->insert($data);
    } else {
        $duplicated = false;
    }

    $URL .= !$duplicated
        ? "&return=error2"
        : "&return=success0";

    header("Location: {$URL}");
}
