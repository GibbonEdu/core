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

use Gibbon\Forms\Prefab\DeleteForm;
use Gibbon\Module\FreeLearning\Domain\UnitGateway;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage_delete.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        $freeLearningUnitID = $_GET['freeLearningUnitID'] ?? '';
        $gibbonDepartmentID = $_REQUEST['gibbonDepartmentID'] ?? '';
        $difficulty = $_GET['difficulty'] ?? '';
        $name = $_GET['name'] ?? '';
        $gibbonYearGroupIDMinimum = $_GET['gibbonYearGroupIDMinimum'] ?? '';
        $view = $_GET['view'] ?? '';

        $urlParams = compact('view', 'name', 'gibbonYearGroupIDMinimum', 'difficulty', 'gibbonDepartmentID', 'freeLearningUnitID');

        if (empty($freeLearningUnitID)) {
            $page->addError(__('You have not specified one or more required parameters.'));
            return;
        }

        $values = $container->get(UnitGateway::class)->getByID($freeLearningUnitID);

        if (empty($values)) {
            $page->addError(__('The specified record cannot be found.'));
            return;
        }

        $form = DeleteForm::createForm($session->get('absoluteURL')."/modules/Free Learning/units_manage_deleteProcess.php?".http_build_query($urlParams), true);
        $form->addHiddenValue('freeLearningUnitID', $freeLearningUnitID);
        echo $form->getOutput();
    }
}
