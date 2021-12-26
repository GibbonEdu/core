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

use Gibbon\Domain\Forms\FormGateway;
use Gibbon\Domain\Forms\FormPageGateway;
use Gibbon\Domain\Forms\ReportPrototypeSectionGateway;

$_POST['address'] = '/modules/System Admin/formBuilder_edit.php';

require_once '../../gibbon.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/formBuilder_edit.php') == false) {
    exit;
} else {
    // Proceed!
    $formGateway = $container->get(FormGateway::class);
    $formPageGateway = $container->get(FormPageGateway::class);

    $data = [
        'gibbonFormID' => $_POST['gibbonFormID'] ?? '',
        'type' => $_POST['type'] ?? 'Header',
    ];

    // Validate the required values are present
    if (empty($data['gibbonFormID']) || empty($data['type'])) {
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
    if (empty($prototypeSection) || !$formGateway->exists($data['gibbonFormID'])) {
        exit;
    }

    $dataMax = ['gibbonFormID' => $data['gibbonFormID']];
    $sqlMax = "SELECT MAX(sequenceNumber) FROM gibbonFormPage WHERE gibbonFormID=:gibbonFormID";

    $data['name'] = $prototypeSection['name'];
    $data['templateParams'] = $prototypeSection['templateParams'];
    $data['sequenceNumber'] = intval($pdo->selectOne($sqlMax, $dataMax)) + 1;

    // Create the record
    $gibbonFormPageID = $formPageGateway->insert($data);
}
