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

use Gibbon\Services\Format;
use Gibbon\Forms\Prefab\DeleteForm;
use Gibbon\Domain\Forms\FormGateway;

if (isActionAccessible($guid, $connection2, '/modules/System Admin/formBuilder_delete.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $gibbonFormID = $_GET['gibbonFormID'] ?? '';
    
    if (empty($gibbonFormID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $formGateway = $container->get(FormGateway::class);
    $values = $formGateway->getByID($gibbonFormID);

    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    // Check for existing submissions and warn about making changes
    $submissions = $formGateway->getSubmissionCountByForm($gibbonFormID);
    if ($submissions > 0) {
        $page->addAlert(Format::bold(__('Warning')).': '.__('This form is already in use. Deleting this form will affect the data for {count} existing submissions. Proceed with extreme caution! It is safer to set this form to inactive and create a new form.', ['count' => Format::bold($submissions)]), 'error');
    }

    $form = DeleteForm::createForm($session->get('absoluteURL').'/modules/System Admin/formBuilder_deleteProcess.php?gibbonFormID='.$gibbonFormID, true);
    echo $form->getOutput();
}
