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
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Students\MedicalGateway;

if (isActionAccessible($guid, $connection2, '/modules/Students/medicalForm_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('Manage Medical Forms'), 'medicalForm_manage.php')
        ->add(__('Edit Medical Form'));

    //Check if person medical specified
    $gibbonPersonMedicalID = isset($_GET['gibbonPersonMedicalID'])? $_GET['gibbonPersonMedicalID'] : '';
    $search = isset($_GET['search'])? $_GET['search'] : '';

    if ($gibbonPersonMedicalID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {

        $medicalGateway = $container->get(MedicalGateway::class);
        $values = $medicalGateway->getMedicalFormByID($gibbonPersonMedicalID);

        if (empty($values)) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            if ($search != '') {
                $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Students', 'medicalForm_manage.php')->withQueryParam('search', $search));
            }

            $form = Form::create('action', $session->get('absoluteURL').'/modules/'.$session->get('module').'/medicalForm_manage_editProcess.php?gibbonPersonMedicalID='.$gibbonPersonMedicalID."&search=$search");

            $form->setFactory(DatabaseFormFactory::create($pdo));

            $form->addHiddenValue('address', $session->get('address'));

            $form->addRow()->addHeading('General Information', __('General Information'));

            $row = $form->addRow();
                $row->addLabel('name', __('Student'));
                $row->addTextField('name')->setValue(Format::name('', $values['preferredName'], $values['surname'], 'Student'))->required()->readonly();

            $row = $form->addRow();
                $row->addLabel('longTermMedication', __('Long-Term Medication?'));
                $row->addYesNo('longTermMedication')->placeholder();

            $form->toggleVisibilityByClass('longTermMedicationDetails')->onSelect('longTermMedication')->when('Y');

            $row = $form->addRow()->addClass('longTermMedicationDetails');
                $row->addLabel('longTermMedicationDetails', __('Medication Details'));
                $row->addTextArea('longTermMedicationDetails')->setRows(5);

            // CUSTOM FIELDS
            $container->get(CustomFieldHandler::class)->addCustomFieldsToForm($form, 'Medical Form', [], $values['fields']);

            $row = $form->addRow();
                $row->addLabel('comment', __('Comment'));
                $row->addTextArea('comment')->setRows(6);

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            $form->loadAllValuesFrom($values);

            echo $form->getOutput();

            $conditions = $medicalGateway->selectMedicalConditionsByID($gibbonPersonMedicalID);

            $table = DataTable::create('medicalConditions');
            $table->setTitle(__('Medical Conditions'));
            $table->setDescription($container->get(SettingGateway::class)->getSettingByScope('Students', 'medicalConditionIntro'));

            $table->addHeaderAction('add', __('Add'))
                ->setURL('/modules/Students/medicalForm_manage_condition_add.php')
                ->addParam('gibbonPersonMedicalID', $gibbonPersonMedicalID)
                ->addParam('search', $search)
                ->displayLabel();

            $table->addColumn('name', __('Name'));
            $table->addColumn('risk', __('Risk'));
            $table->addColumn('details', __('Details'))->format(function($condition){
                $output = '';
                if (!empty($condition['triggers'])) $output .= '<b>'.__('Triggers').':</b> '.$condition['triggers'].'<br/>';
                if (!empty($condition['reaction'])) $output .= '<b>'.__('Reaction').':</b> '.$condition['reaction'].'<br/>';
                if (!empty($condition['response'])) $output .= '<b>'.__('Response').':</b> '.$condition['response'].'<br/>';
                if (!empty($condition['lastEpisode'])) $output .= '<b>'.__('Last Episode').':</b> '.Format::date($condition['lastEpisode']).'<br/>';
                if (!empty($condition['lastEpisodeTreatment'])) $output .= '<b>'.__('Last Episode Treatment').':</b> '.$condition['lastEpisodeTreatment'].'<br/>';
                return $output;
            });
            $table->addColumn('medication', __('Medication'));
            $table->addColumn('comment', __('Comment'));

            // ACTIONS
            $table->addActionColumn()
                ->addParam('gibbonPersonMedicalID', $gibbonPersonMedicalID)
                ->addParam('gibbonPersonMedicalConditionID')
                ->addParam('search', $search)
                ->format(function ($person, $actions) {
                    $actions->addAction('edit', __('Edit'))
                        ->setURL('/modules/Students/medicalForm_manage_condition_edit.php');

                    $actions->addAction('delete', __('Delete'))
                        ->setURL('/modules/Students/medicalForm_manage_condition_delete.php');
                });

            echo $table->render($conditions->toDataSet());
        }
    }
}
