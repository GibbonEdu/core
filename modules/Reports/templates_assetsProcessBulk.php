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

//use Gibbon\Data\Validator;
use Gibbon\Module\Reports\Domain\ReportPrototypeSectionGateway;

include '../../gibbon.php';

$action = $_POST['action'] ?? '';
$search = $_POST['search'] ?? '';
$gibbonReportPrototypeSectionIDs = $_POST['gibbonReportPrototypeSectionID'] ?? [];

$URL = $session->get('absoluteURL')."/index.php?q=/modules/Reports/templates_assets.php&search=$search";

if (isActionAccessible($guid, $connection2, '/modules/Reports/templates_assets.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    if (empty($action) || empty($gibbonReportPrototypeSectionIDs)) { 
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        $reportPrototypeSectionGateway = $container->get(ReportPrototypeSectionGateway::class);
        $gibbonReportPrototypeSectionIDList = is_array($gibbonReportPrototypeSectionIDs)? implode(',', $gibbonReportPrototypeSectionIDs) : $gibbonReportPrototypeSectionIDs;

        if($action == 'ActiveStatus') {
            $updated = $reportPrototypeSectionGateway->updateActiveStatus($gibbonReportPrototypeSectionIDList, 'Y');
        } elseif ($action == 'InactiveStatus') {
            $updated = $reportPrototypeSectionGateway->updateActiveStatus($gibbonReportPrototypeSectionIDList, 'N');
        }

        $URL .= !$updated ? "&return=error2" : "&return=success0";
        header("Location: {$URL}");
        exit();
    }
}