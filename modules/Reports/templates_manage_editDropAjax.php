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
use Gibbon\Module\Reports\Domain\ReportTemplateSectionGateway;
use Gibbon\Module\Reports\Domain\ReportPrototypeSectionGateway;

$_POST['address'] = '/modules/Reports/templates_manage_edit.php';

require_once '../../gibbon.php';

if (isActionAccessible($guid, $connection2, '/modules/Reports/templates_manage_edit.php') == false) {
    exit;
} else {
    // Proceed!
    $templateGateway = $container->get(ReportTemplateGateway::class);
    $templateSectionGateway = $container->get(ReportTemplateSectionGateway::class);
    $prototypeSectionGateway = $container->get(ReportPrototypeSectionGateway::class);

    $data = [
        'gibbonReportTemplateID' => $_POST['gibbonReportTemplateID'] ?? '',
        'gibbonReportPrototypeSectionID' => $_POST['gibbonReportPrototypeSectionID'] ?? '',
        'type' => $_POST['type'] ?? 'Header',
    ];

    // Validate the required values are present
    if (empty($data['gibbonReportTemplateID']) || empty($data['gibbonReportPrototypeSectionID']) || empty($data['type'])) {
        exit;
    }

    $prototypeSection = $prototypeSectionGateway->getByID($data['gibbonReportPrototypeSectionID']);

    if ($config = json_decode($prototypeSection['config'] ?? '', true)) {
        $config = array_reduce(array_keys($config), function ($group, $key) use (&$config) {
            $group[$key] = $config[$key]['default'] ?? '';
            return $group;
        }, []);
        $data['config'] = json_encode($config);
    }

    // Validate the database relationships exist
    if (empty($prototypeSection) || !$templateGateway->exists($data['gibbonReportTemplateID'])) {
        exit;
    }

    $dataMax = ['gibbonReportTemplateID' => $data['gibbonReportTemplateID']];
    $sqlMax = "SELECT MAX(sequenceNumber) FROM gibbonReportTemplateSection WHERE gibbonReportTemplateID=:gibbonReportTemplateID";

    $data['name'] = $prototypeSection['name'];
    $data['templateParams'] = $prototypeSection['templateParams'];
    $data['sequenceNumber'] = intval($pdo->selectOne($sqlMax, $dataMax)) + 1;

    // Create the record
    $gibbonReportTemplateSectionID = $templateSectionGateway->insert($data);
}
