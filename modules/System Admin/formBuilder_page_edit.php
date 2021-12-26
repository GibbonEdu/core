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

use Gibbon\Forms\Form;
use Gibbon\Tables\Action;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\Forms\FormGateway;
use Gibbon\Forms\Builder\FormBuilder;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\Forms\FormPageGateway;
use Gibbon\Domain\Forms\FormFieldGateway;

if (isActionAccessible($guid, $connection2, '/modules/System Admin/formBuilder_page_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $gibbonFormID = $_REQUEST['gibbonFormID'] ?? '';
    $gibbonFormPageID = $_REQUEST['gibbonFormPageID'] ?? '';
    $fieldGroup = $_REQUEST['fieldGroup'] ?? '';

    $page->breadcrumbs
        ->add(__('Form Builder'), 'formBuilder.php')
        ->add(__('Edit Form'), 'formBuilder_edit.php', ['gibbonFormID' => $gibbonFormID])
        ->add(__('Edit Page'));

    $formGateway = $container->get(FormGateway::class);
    $formPageGateway = $container->get(FormPageGateway::class);
    $formFieldGateway = $container->get(FormFieldGateway::class);

    if (empty($gibbonFormID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $formValues = $formGateway->getByID($gibbonFormID);
    $values = $formPageGateway->getByID($gibbonFormPageID);

    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $form = Form::create('formsManage', $gibbon->session->get('absoluteURL').'/modules/System Admin/formBuilder_page_editProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    
    $form->addHiddenValue('address', $gibbon->session->get('address'));
    $form->addHiddenValue('gibbonFormID', $gibbonFormID);
    $form->addHiddenValue('gibbonFormPageID', $gibbonFormPageID);

    $row = $form->addRow();
        $row->addLabel('formName', __('Form Name'));
        $row->addTextField('formName')->readonly()->required()->setValue($formValues['name']);

    $row = $form->addRow();
        $row->addLabel('name', __('Name'))->description(__('Must be unique'));
        $row->addTextField('name')->maxLength(90)->required();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    $form->loadAllValuesFrom($values);

    echo $form->getOutput();

    // QUERY
    $criteria = $formGateway->newQueryCriteria()
        ->sortBy('sequenceNumber', 'ASC')
        ->fromPOST();

    $fields = $formFieldGateway->queryFieldsByPage($criteria, $gibbonFormPageID);
    $formBuilder = $container->get(FormBuilder::class);
    
    // FORM FIELDS
    $formFields = Form::create('formFields', '');
    $formFields->setTitle($values['name']);
    $formFields->setFactory(DatabaseFormFactory::create($pdo));

    $formFields->addData('drag-url', $gibbon->session->get('absoluteURL').'/modules/System%20Admin/formBuilder_page_editOrderAjax.php');
    $formFields->addData('drag-data', ['gibbonFormPageID' => $gibbonFormPageID]);

    $params = [
        'gibbonFormID' => $gibbonFormID,
        'gibbonFormPageID' => $gibbonFormPageID,
        'fieldGroup' => $fieldGroup,
    ];
    
    foreach ($fields as $field) {
        $fieldGroupClass = $formBuilder->getFieldGroupClass($field['fieldGroup']);

        if (empty($fieldGroupClass)) {
            $formFields->addRow()->addContent(Format::alert(__('The specified record cannot be found.')));
            continue;
        }

        $row = $fieldGroupClass->addFieldToForm($formFields, $field);

        $row->addClass('draggableRow')
            ->addData('drag-id', $field['gibbonFormFieldID']);

        $element = $row->getElement($field['fieldName']);
        if (!empty($element)) {
            $element->addClass('flex-1')->setTitle($field['fieldName']);
        }

        $row->addContent((new Action('edit', __('Edit')))
            ->setURL('/modules/System Admin/formBuilder_page_edit_field_edit.php')
            ->addParam('gibbonFormFieldID', $field['gibbonFormFieldID'])
            ->addParams($params)
            ->modalWindow(900, 500)
            ->getOutput().
            (new Action('delete', __('Delete')))
            ->setURL('/modules/System Admin/formBuilder_page_edit_field_delete.php')
            ->addParam('gibbonFormFieldID', $field['gibbonFormFieldID'])
            ->addParams($params)
            ->getOutput()
        );
    }


    // FIELD GROUPS
    $formFieldGroups = Form::create('formFieldGroups', '');
    $formFieldGroups->addData('reload-url', $gibbon->session->get('absoluteURL').'/modules/System%20Admin/formBuilder_page_edit_field_add.php');
    $formFieldGroups->addData('reload-data', $params);

    $fieldGroups = [
        __('General') => [
            'LayoutFields' => __('Headings'),
            'GenericFields' => __('Generic Fields'),
            'CustomFields' => __('Custom Fields'),
        ], 
        __('Application Form') => [
            'AdmissionsFields' => __('Admissions'),
            'StudentFields' => __('Student'),
            'ParentFields' => __('Parent'),
            'FamilyFields' => __('Family'),
            'MedicalFields' => __('Medical'),
            'DocumentsFields' => __('Documents'),
            'FinanceFields' => __('Finance'),
            'LanguageFields' => __('Language'),
            'PrivacyFields' => __('Privacy'),
            'AgreementFields' => __('Agreement'),
        ], 
    ];
    $row = $formFieldGroups->addRow();
        $row->addSelect('fieldGroup')->fromArray($fieldGroups)
            ->addClass('auto-submit')
            ->selected($fieldGroup)
            ->placeholder(); 


    // TEMPLATE
    echo $page->fetchFromTemplate('ui/formBuilder.twig.html', [
        'gibbonFormID' => $gibbonFormID,
        'form'         => $values,
        'fields'       => $formFields,
        'fieldGroups'  => $formFieldGroups,
    ]);
}
