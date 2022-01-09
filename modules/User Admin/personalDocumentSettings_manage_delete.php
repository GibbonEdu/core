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
use Gibbon\Domain\User\PersonalDocumentTypeGateway;

if (isActionAccessible($guid, $connection2, '/modules/User Admin/personalDocumentSettings_manage_delete.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $gibbonPersonalDocumentTypeID = $_GET['gibbonPersonalDocumentTypeID'] ?? '';
    $personalDocumentTypeGateway = $container->get(PersonalDocumentTypeGateway::class);

    if (!$personalDocumentTypeGateway->exists($gibbonPersonalDocumentTypeID)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $form = DeleteForm::createForm($session->get('absoluteURL').'/modules/User Admin/personalDocumentSettings_manage_deleteProcess.php?gibbonPersonalDocumentTypeID='.$gibbonPersonalDocumentTypeID);
    echo $form->getOutput();
}
