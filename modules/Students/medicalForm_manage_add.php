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

use Gibbon\Http\Url;
use Gibbon\Forms\Form;
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Forms\DatabaseFormFactory;

if (isActionAccessible($guid, $connection2, '/modules/Students/medicalForm_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('Manage Medical Forms'), 'medicalForm_manage.php')
        ->add(__('Add Medical Form'));

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $session->get('absoluteURL').'/index.php?q=/modules/Students/medicalForm_manage_edit.php&gibbonPersonMedicalID='.$_GET['editID'].'&search='.$_GET['search'];
    }
    $page->return->setEditLink($editLink);

    $gibbonPersonID = (isset($_GET['gibbonPersonID']))? $_GET['gibbonPersonID'] : '';
    $search = $_GET['search'] ?? '';
    if ($search != '') {
        $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Students', 'medicalForm_manage.php')->withQueryParam('search', $search));
    }

    $form = Form::create('action', $session->get('absoluteURL').'/modules/'.$session->get('module')."/medicalForm_manage_addProcess.php?search=$search");

    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));

    $form->addRow()->addHeading('General Information', __('General Information'));

    $row = $form->addRow();
        $row->addLabel('gibbonPersonID', __('Student'));
        $row->addSelectStudent('gibbonPersonID', $session->get('gibbonSchoolYearID'))->required()->placeholder()->selected($gibbonPersonID);

    $row = $form->addRow();
        $row->addLabel('longTermMedication', __('Long-Term Medication?'));
        $row->addYesNo('longTermMedication')->placeholder();

    $form->toggleVisibilityByClass('longTermMedicationDetails')->onSelect('longTermMedication')->when('Y');

    $row = $form->addRow()->addClass('longTermMedicationDetails');
        $row->addLabel('longTermMedicationDetails', __('Medication Details'));
        $row->addTextArea('longTermMedicationDetails')->setRows(5);

    // CUSTOM FIELDS
    $container->get(CustomFieldHandler::class)->addCustomFieldsToForm($form, 'Medical Form', []);

    $row = $form->addRow();
        $row->addLabel('comment', __('Comment'));
        $row->addTextArea('comment')->setRows(6);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
