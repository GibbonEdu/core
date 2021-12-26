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
use Gibbon\Domain\Forms\FormPageGateway;

if (isActionAccessible($guid, $connection2, '/modules/System Admin/formBuilder_page_delete.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $gibbonFormID = $_GET['gibbonFormID'] ?? '';
    $gibbonFormPageID = $_GET['gibbonFormPageID'] ?? '';
    
    if (empty($gibbonFormPageID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $values = $container->get(FormPageGateway::class)->getByID($gibbonFormPageID);

    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $form = DeleteForm::createForm($gibbon->session->get('absoluteURL').'/modules/System Admin/formBuilder_page_deleteProcess.php?gibbonFormID='.$gibbonFormID.'&gibbonFormPageID='.$gibbonFormPageID, true);
    echo $form->getOutput();
}
