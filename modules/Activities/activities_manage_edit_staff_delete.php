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

use Gibbon\Domain\Activities\ActivityGateway;
use Gibbon\Domain\Activities\ActivityStaffGateway;
use Gibbon\Forms\Prefab\DeleteForm;

//Note: This is a modal page
if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_manage_edit.php') == false) {
    //Acess denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $gibbonActivityID = $_GET['gibbonActivityID'] ?? '';
    $gibbonActivityStaffID = $_GET['gibbonActivityStaffID'] ?? '';
    $search = $_GET['search'] ?? '';
    $gibbonSchoolYearTermID = $_GET['gibbonSchoolYearTermID'] ?? '';

    $activityGateway = $container->get(ActivityGateway::class);
    $activityStaffGateway = $container->get(ActivityStaffGateway::class);
    
    if (!$activityGateway->exists($gibbonActivityID) || !$activityStaffGateway->exists($gibbonActivityStaffID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        $form = DeleteForm::createForm($session->get('absoluteURL') . '/modules/' . $session->get('module') . "/activities_manage_edit_staff_deleteProcess.php");
        $form->addHiddenValue('address', $session->get('address'));
        $form->addHiddenValue('gibbonActivityID', $gibbonActivityID);
        $form->addHiddenValue('gibbonActivityStaffID', $gibbonActivityStaffID);
        $form->addHiddenValue('search', $search);
        $form->addHiddenValue('gibbonSchoolYearTermID', $gibbonSchoolYearTermID);

        echo $form->getOutput();
    }
}
?>
