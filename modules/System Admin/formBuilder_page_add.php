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

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\Forms\FormGateway;

if (isActionAccessible($guid, $connection2, '/modules/System Admin/formBuilder_page_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $gibbonFormID = $_REQUEST['gibbonFormID'] ?? '';
    $redirect = $_REQUEST['redirect'] ?? '';

    $page->breadcrumbs
        ->add(__('Form Builder'), 'formBuilder.php')
        ->add(__('Edit Form'), 'formBuilder_edit.php', ['gibbonFormID' => $gibbonFormID])
        ->add(__('Add Page'));

    if (isset($_GET['editID'])) {
        $page->return->setEditLink($session->get('absoluteURL').'/index.php?q=/modules/System Admin/formBuilder_page_edit.php&gibbonFormID='.$gibbonFormID.'&gibbonFormPageID='.$_GET['editID']);
    }

    if (empty($gibbonFormID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $formValues = $container->get(FormGateway::class)->getByID($gibbonFormID);

    $form = Form::create('formsManage', $session->get('absoluteURL').'/modules/System Admin/formBuilder_page_addProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    
    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonFormID', $gibbonFormID);
    $form->addHiddenValue('redirect', $redirect);

    $row = $form->addRow();
        $row->addLabel('formName', __('Form Name'));
        $row->addTextField('formName')->readonly()->required()->setValue($formValues['name']);
        
    $row = $form->addRow();
        $row->addLabel('name', __('Page Name'))->description(__('Must be unique'));
        $row->addTextField('name')->maxLength(90)->required();

    $col = $form->addRow()->addColumn();
        $col->addLabel('introduction', __('Introduction'))->description(__('Information to display before the form'));
        $col->addEditor('introduction', $guid)->setRows(8);

    $col = $form->addRow()->addColumn();
        $col->addLabel('postscript', __('Postscript'))->description(__('Information to display at the end of the form'));
        $col->addEditor('postscript', $guid)->setRows(8);


    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
