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

use Gibbon\Forms\Prefab\DeleteForm;
use Gibbon\Domain\Activities\ActivityChoiceGateway;

if (isActionAccessible($guid, $connection2, '/modules/Activities/choices_manage_delete.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $gibbonActivityCategoryID = $_GET['gibbonActivityCategoryID'] ?? '';
    $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';

    if (empty($gibbonActivityCategoryID) || empty($gibbonPersonID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $choices = $container->get(ActivityChoiceGateway::class)->selectChoicesByPerson($gibbonActivityCategoryID, $gibbonPersonID);
    if (empty($choices)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $form = DeleteForm::createForm($session->get('absoluteURL').'/modules/Activities/choices_manage_deleteProcess.php');
    echo $form->getOutput();
}
