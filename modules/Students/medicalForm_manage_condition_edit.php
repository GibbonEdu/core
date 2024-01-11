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
use Gibbon\Domain\Students\MedicalGateway;
use Gibbon\Services\Format;

if (isActionAccessible($guid, $connection2, '/modules/Students/medicalForm_manage_condition_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $gibbonPersonMedicalID = $_GET['gibbonPersonMedicalID'] ?? '';
    $gibbonPersonMedicalConditionID = $_GET['gibbonPersonMedicalConditionID'] ?? '';
    $search = $_GET['search'] ?? '';

    $page->breadcrumbs
        ->add(__('Manage Medical Forms'), 'medicalForm_manage.php')
        ->add(__('Manage Medical Forms'), 'medicalForm_manage.php')
        ->add(__('Edit Medical Form'), 'medicalForm_manage_edit.php', ['gibbonPersonMedicalID' => $gibbonPersonMedicalID])
        ->add(__('Edit Condition'));

    if ($gibbonPersonMedicalID == '' or $gibbonPersonMedicalConditionID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        $medicalGateway = $container->get(MedicalGateway::class);
        $values = $medicalGateway->getMedicalConditionByID($gibbonPersonMedicalConditionID);

        if (empty($values)) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
           $form = Form::create('action', $session->get('absoluteURL').'/modules/'.$session->get('module')."/medicalForm_manage_condition_editProcess.php?gibbonPersonMedicalID=$gibbonPersonMedicalID&search=$search&gibbonPersonMedicalConditionID=$gibbonPersonMedicalConditionID");

            $form->setFactory(DatabaseFormFactory::create($pdo));

            $form->addHiddenValue('address', $session->get('address'));
            $form->addHiddenValue('gibbonPersonMedicalID', $gibbonPersonMedicalID);
            
            if ($search != '') {
                $params = [
                    "search" => $search,
                    "gibbonPersonMedicalID" => $gibbonPersonMedicalID
                ];
                $form->addHeaderAction('back', __('Back'))
                    ->setURL('/modules/Students/medicalForm_manage_edit.php')
                    ->addParams($params);
            }

            $form->addRow()->addHeading('General Information', __('General Information'));

            $row = $form->addRow();
                $row->addLabel('personName', __('Student'));
                $row->addTextField('personName')->setValue(Format::name('', $values['preferredName'], $values['surname']), 'Student')->required()->readonly();

            $sql = "SELECT name AS value, name FROM gibbonMedicalCondition ORDER BY name";
            $row = $form->addRow();
                $row->addLabel('name', __('Condition Name'));
                $row->addSelect('name')->fromQuery($pdo, $sql)->required()->placeholder();

            $row = $form->addRow();
                $row->addLabel('gibbonAlertLevelID', __('Risk'));
                $row->addSelectAlert('gibbonAlertLevelID')->required();

            $row = $form->addRow();
                $row->addLabel('triggers', __('Triggers'));
                $row->addTextField('triggers')->maxLength(255);

            $row = $form->addRow();
                $row->addLabel('reaction', __('Reaction'));
                $row->addTextField('reaction')->maxLength(255);

            $row = $form->addRow();
                $row->addLabel('response', __('Response'));
                $row->addTextField('response')->maxLength(255);

            $row = $form->addRow();
                $row->addLabel('medication', __('Medication'));
                $row->addTextField('medication')->maxLength(255);

            $row = $form->addRow();
                $row->addLabel('lastEpisode', __('Last Episode Date'));
                $row->addDate('lastEpisode');

            $row = $form->addRow();
                $row->addLabel('lastEpisodeTreatment', __('Last Episode Treatment'));
                $row->addTextField('lastEpisodeTreatment')->maxLength(255);

            $row = $form->addRow();
                $row->addLabel('comment', __('Comment'));
                $row->addTextArea('comment');

            $row = $form->addRow();
                $row->addLabel('attachment', __('Attachment'))
                    ->description(__('Additional details about this medical condition. Attachments are only visible to users who manage medical data.'));
                $row->addFileUpload('attachment')
                    ->setAttachment('attachment', $session->get('absoluteURL'), $values['attachment'] ?? '');

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            $form->loadAllValuesFrom($values);

            echo $form->getOutput();
        }
    }
}
