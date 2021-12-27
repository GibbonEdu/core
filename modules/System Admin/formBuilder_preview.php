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

use Gibbon\Forms\Builder\FormBuilder;
use Gibbon\Forms\Builder\Processor\PreviewFormProcessor;

if (isActionAccessible($guid, $connection2, '/modules/System Admin/formBuilder_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Form Builder'), 'formBuilder.php')
        ->add(__('Preview'));

    $gibbonFormID = $_REQUEST['gibbonFormID'] ?? '';
    $page = $_REQUEST['page'] ?? 1;

    if (empty($gibbonFormID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $formBuilder = $container->get(FormBuilder::class);

    // Build the form
    $form = $formBuilder->build($gibbonFormID, $page, $session->get('absoluteURL').'/modules/System Admin/formBuilder_previewProcess.php');
    $form->addHiddenValue('gibbonFormID', $gibbonFormID);
    $form->addHiddenValue('page', $page);

    // Load values from the form data storage
    $formProcessor = $container->get(PreviewFormProcessor::class);
    $values = $formProcessor->loadData('preview');
    $form->loadAllValuesFrom($values);

    echo $form->getOutput();
}
