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
use Gibbon\Tables\Action;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\Forms\FormGateway;
use Gibbon\Forms\Builder\FormBuilder;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\Forms\FormPageGateway;
use Gibbon\Domain\Forms\FormFieldGateway;
use Gibbon\Forms\MultiPartForm;
use Gibbon\Http\Url;

if (isActionAccessible($guid, $connection2, '/modules/System Admin/formBuilder_page_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $gibbonFormID = $_REQUEST['gibbonFormID'] ?? '';

    $page->breadcrumbs
        ->add(__('Form Builder'), 'formBuilder.php')
        ->add(__('Edit Form'), 'formBuilder_edit.php', ['gibbonFormID' => $gibbonFormID])
        ->add(__('Design'));

    if (empty($gibbonFormID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    if (!empty($_GET['duplicate'])) {
        $page->return->addReturns([
            'warning1' => __('Your request was successful, but some fields were not unique and could not be added to the form: {duplicate}', ['duplicate' => '<b>'.$_GET['duplicate'].'</b>']),
        ]);
    }

    $formGateway = $container->get(FormGateway::class);
    $formPageGateway = $container->get(FormPageGateway::class);
    $formFieldGateway = $container->get(FormFieldGateway::class);

    $fieldGroup = $_REQUEST['fieldGroup'] ?? '';
    $gibbonFormPageID = $_REQUEST['gibbonFormPageID'] ?? '';
    if (empty($gibbonFormPageID)) {
        $gibbonFormPageID = $formPageGateway->getPageIDByNumber($gibbonFormID, 1);
    }

    // Check for existing submissions and warn about making changes
    $submissions = $formGateway->getSubmissionCountByForm($gibbonFormID);
    if ($submissions > 0) {
        $page->addAlert(Format::bold(__('Warning')).': '.__('This form is already in use. Changes to this form could affect the data for {count} existing submissions. Proceed with caution! If you are looking to make significant changes this form, it is safer to set it to inactive and create a new form, which will prevent changes that could affect your existing submissions.', ['count' => Format::bold($submissions)]), 'warning');
    }

    $urlParams = compact('gibbonFormID', 'gibbonFormPageID', 'fieldGroup');

    $formValues = $formGateway->getByID($gibbonFormID);
    $values = $formPageGateway->getByID($gibbonFormPageID);

    if (empty($formValues) || empty($values)) {
        echo $page->getBlankSlate();
        return;
    }

    // QUERY
    $criteria = $formGateway->newQueryCriteria()
        ->sortBy('sequenceNumber', 'ASC')
        ->fromPOST();

    $fields = $formFieldGateway->queryFieldsByPage($criteria, $gibbonFormPageID);
    $formBuilder = $container->get(FormBuilder::class);
    
    // FORM FIELDS
    $formFields = MultiPartForm::create('formFields', '');
    $formFields->setTitle(__($values['name']));
    $formFields->setFactory(DatabaseFormFactory::create($pdo));

    $formFields->setMaxPage($formPageGateway->getFinalPageNumber($gibbonFormID));
    $formFields->addData('drag-url', $session->get('absoluteURL').'/modules/System%20Admin/formBuilder_page_editOrderAjax.php');
    $formFields->addData('drag-data', ['gibbonFormPageID' => $gibbonFormPageID]);

    $formPages = $formPageGateway->selectPagesByForm($gibbonFormID)->fetchGroupedUnique();

    if (count($formPages) > 1) {
        $formFields->setCurrentPage($values['sequenceNumber']);

        foreach ($formPages as $formPage) {
            $pageUrl = Url::fromModuleRoute('System Admin', 'formBuilder_page_design.php')->withQueryParams(['gibbonFormPageID' => $formPage['gibbonFormPageID'], 'sidebar' => 'false'] + $urlParams);
            $formFields->addPage($formPage['sequenceNumber'], $formPage['name'], $pageUrl);
        }
    }
    
    foreach ($fields as $field) {
        $fieldGroupClass = $formBuilder->getFieldGroup($field['fieldGroup']);

        if (empty($fieldGroupClass)) {
            $formFields->addRow()->addContent(Format::alert(__('The specified record cannot be found.')));
            continue;
        }

        $row = $fieldGroupClass->addFieldToForm($formBuilder, $formFields, $field);

        $row->addClass('draggableRow')
            ->addData('drag-id', $field['gibbonFormFieldID']);

        if ($field['hidden'] == 'Y') {
            $row->addClass('bg-purple-200');
        }

        if ($element = $row->getElement($field['fieldName'])) {
            $element->addClass('flex-1');
        }

        $row->addContent((new Action('edit', __('Edit')))
            ->setURL('/modules/System Admin/formBuilder_page_edit_field_edit.php')
            ->addParam('gibbonFormFieldID', $field['gibbonFormFieldID'])
            ->addParams($urlParams)
            ->modalWindow(900, 520)
            ->getOutput().
            (new Action('delete', __('Delete')))
            ->setURL('/modules/System Admin/formBuilder_page_edit_field_delete.php')
            ->addParam('gibbonFormFieldID', $field['gibbonFormFieldID'])
            ->addParams($urlParams)
            ->getOutput()
        );
    }

    // $formFields->clearTriggers();

    // TEMPLATE
    echo $page->fetchFromTemplate('components/formBuilder.twig.html', [
        'gibbonFormID' => $gibbonFormID,
        'gibbonFormPageID' => $gibbonFormPageID,
        'fieldCount'   => count($fields),
        'fields'       => $formFields,
    ]);
}
