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

use Gibbon\Module\FreeLearning\UnitExporter;
use Gibbon\Module\FreeLearning\UnitDuplicator;
use Gibbon\Module\FreeLearning\Domain\UnitGateway;

$_POST['address'] = '/modules/Free Learning/units_manage.php';

require_once '../../gibbon.php';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Free Learning/units_manage.php';

// Override the ini to keep this process alive
ini_set('memory_limit', '2048M');
ini_set('max_execution_time', 1800);
set_time_limit(1800);

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $highestAction = getHighestGroupedAction($guid, $session->get('address'), $connection2);
    if ($highestAction == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        // Proceed!
        $action = $_REQUEST['action'] ?? '';
        $name = $_REQUEST['name'] ?? [];
        $freeLearningUnitID = $_REQUEST['freeLearningUnitID'] ?? [];
        $freeLearningUnitIDList = is_array($freeLearningUnitID) ? $freeLearningUnitID : [$freeLearningUnitID];
        $partialFail = false;

        if (empty($action)) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
            exit;
        }

        if ($action == 'Export') {

            // Export zip contents of units
            $exporter = $container->get(UnitExporter::class);
            $exporter->setFilename(!empty($name)? $name : 'FreeLearningUnits');

            foreach ($freeLearningUnitIDList as $freeLearningUnitID) {
                $exporter->addUnitToExport($freeLearningUnitID);
            }

            $exporter->output();
            exit;

        } else if ($action == 'Duplicate') {

            $duplicator = $container->get(UnitDuplicator::class);

            foreach ($freeLearningUnitIDList as $freeLearningUnitID) {
                $partialFail = $duplicator->duplicateUnit($freeLearningUnitID);
            }

        } else if (($action == 'Lock' or $action == 'Unlock') and $highestAction == 'Manage Units_all') {

            $unitGateway = $container->get(UnitGateway::class);

            foreach ($freeLearningUnitIDList as $freeLearningUnitID) {
                $partialFail = !$unitGateway->update($freeLearningUnitID, ['editLock' => ($action == 'Lock' ? 'Y' : 'N')]);
            }

        } else {
            $URL .= '&return=error1';
            header("Location: {$URL}");
            exit;
        }

        $URL .= $partialFail
            ? '&return=warning1'
            : '&return=success0';
        header("Location: {$URL}");
    }
}
