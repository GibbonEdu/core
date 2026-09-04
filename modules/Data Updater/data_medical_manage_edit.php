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
use Gibbon\Services\Format;
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Domain\System\AlertLevelGateway;
use Gibbon\Domain\School\MedicalConditionGateway;
use Gibbon\Domain\DataUpdater\MedicalUpdateGateway;
use Gibbon\Domain\Students\PersonMedicalConditionGateway;
use Gibbon\Domain\DataUpdater\MedicalConditionUpdateGateway;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_medical_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');

    $urlParams = ['gibbonSchoolYearID' => $gibbonSchoolYearID];

    $page->breadcrumbs
        ->add(__('Medical Data Updates'), 'data_medical_manage.php', $urlParams)
        ->add(__('Edit Request'));

    // Check if gibbonPersonMedicalUpdateID specified
    $gibbonPersonMedicalUpdateID = $_GET['gibbonPersonMedicalUpdateID'] ?? '';
    if ($gibbonPersonMedicalUpdateID == 'Y') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        $result = $container->get(MedicalUpdateGateway::class)->getMedicalDetailsByUpdateID($gibbonPersonMedicalUpdateID);

        if (empty($result)) {
            $page->addError(__('The specified record does not exist.'));
        } else {
            $newResult = $container->get(MedicalUpdateGateway::class)->getByID($gibbonPersonMedicalUpdateID);

            // Let's go!
            $oldValues = $result;
            $newValues = $newResult;

            // Provide a link back to edit the associated record
            if (isActionAccessible($guid, $connection2, '/modules/Students/medicalForm_manage_edit.php') == true && !empty($oldValues['gibbonPersonMedicalID'])) {
                $params = [ 
                    'gibbonPersonMedicalID' => $oldValues['gibbonPersonMedicalID']
                ];
                $page->navigator->addHeaderAction('edit', __('Edit Medical Form'))
                    ->setURL('/modules/Students/medicalForm_manage_edit.php')
                    ->addParams($params)
                    ->setIcon('config')
                    ->displayLabel();
            }

            $compare = array(
                'longTermMedication'        => __('Long-Term Medication?'),
                'longTermMedicationDetails' => __('Medication Details'),
                'comment'      => __('Comment'),
            );

            $compareCondition = array(
                'name'                 => __('Condition Name'),
                'gibbonAlertLevelID'   => __('Risk'),
                'triggers'             => __('Triggers'),
                'reaction'             => __('Reaction'),
                'response'             => __('Response'),
                'medication'           => __('Medication'),
                'lastEpisode'          => __('Last Episode Date'),
                'lastEpisodeTreatment' => __('Last Episode Treatment'),
                'comment'              => __('Comment'),
                'attachment'           => __('Attachment'),
            );

            $conditions = $container->get(MedicalConditionGateway::class)->selectAllMedicalConditions()->fetchKeyPair();
            $alerts = $container->get(AlertLevelGateway::class)->selectAlertLevels()->fetchKeyPair();

            $form = Form::createTable('updateMedical', $session->get('absoluteURL').'/modules/'.$session->get('module').'/data_medical_manage_editProcess.php?gibbonPersonMedicalUpdateID='.$gibbonPersonMedicalUpdateID);

            $form->setClass('w-full colorOddEven');
            $form->addHiddenValue('address', $session->get('address'));
            $form->addHiddenValue('gibbonPersonID', $newValues['gibbonPersonID']);
            $form->addHiddenValue('formExists', !empty($oldValues['gibbonPersonMedicalID']));

            $row = $form->addRow()->setClass('head bg-gray-200');
                $row->addContent(__('Field'));
                $row->addContent(__('Current Value'));
                $row->addContent(__('New Value'));
                $row->addContent(__('Accept'));

            $changeCount = 0;

            // Create a reusable function for adding comparisons to the form
            $comparisonFields = function ($form, $oldValues, $newValues, $fieldName, $label, $count = '') use ($conditions, $alerts, &$changeCount) {
                $oldValue = isset($oldValues[$fieldName])? $oldValues[$fieldName] : '';
                $newValue = isset($newValues[$fieldName])? $newValues[$fieldName] : '';
                $isNotMatching = ($oldValue != $newValue);

                if ($fieldName == 'name') {
                    $oldValue = isset($conditions[$oldValue])? $conditions[$oldValue] : $oldValue;
                    $newValue = isset($conditions[$newValue])? $conditions[$newValue] : $newValue;
                }

                if ($fieldName == 'gibbonAlertLevelID') {
                    $oldValue = isset($alerts[$oldValue])? $alerts[$oldValue] : $oldValue;
                    $newValue = isset($alerts[$newValue])? $alerts[$newValue] : $newValue;
                }

                if ($fieldName == 'lastEpisode') {
                    $oldValue = Format::date($oldValue);
                    $newValue = Format::date($newValue);
                }

                if ($fieldName == 'attachment') {
                    $oldValue = !empty($oldValue) ? Format::link('./'.$oldValue, $oldValue, ['target' => '_blank']) : '';
                    $newValue = !empty($newValue) ? Format::link('./'.$newValue, $newValue, ['target' => '_blank']) : '';
                }

                $row = $form->addRow();
                $row->addLabel($fieldName.'On'.$count, $label);
                $row->addContent($oldValue);
                $row->addContent($newValue)->addClass($isNotMatching ? 'matchHighlightText' : '');

                if ($isNotMatching) {
                    $row->addCheckbox($fieldName.'On'.$count)->checked(true)->setClass('textCenter');
                    $form->addHiddenValue($fieldName.$count, $newValues[$fieldName]);
                    $changeCount++;
                } else {
                    $row->addContent();
                }
            };

            // Basic Medical Form
            $form->addRow()->addHeading('Basic Information', __('Basic Information'));

            foreach ($compare as $fieldName => $label) {
                $comparisonFields($form, $oldValues, $newValues, $fieldName, $label);
            }

            // CUSTOM FIELDS
            $container->get(CustomFieldHandler::class)->addCustomFieldsToDataUpdate($form, 'Medical Form', ['dataUpdater' => 1], $oldValues, $newValues);

            // Existing Conditions
            $existing = true;
            $result = $container->get(MedicalConditionUpdateGateway::class)->selectMedicalConditionUpdatesByID($gibbonPersonMedicalUpdateID, $existing);

            $count = 0;
            if ($result->rowCount() > 0) {
                while ($newValues = $result->fetch()) {
                    $oldValues = $container->get(PersonMedicalConditionGateway::class)->getByID($newValues['gibbonPersonMedicalConditionID']);

                    $form->addRow()->addHeading(__('Existing Condition').' '.($count+1));
                    $form->addHiddenValue('gibbonPersonMedicalConditionID'.$count, $newValues['gibbonPersonMedicalConditionID']);

                    foreach ($compareCondition as $fieldName => $label) {
                        $comparisonFields($form, $oldValues, $newValues, $fieldName, $label, $count);
                    }

                    $count++;
                }
            }

            // New Conditions
            $existing = false;
            $result = $container->get(MedicalConditionUpdateGateway::class)->selectMedicalConditionUpdatesByID($gibbonPersonMedicalUpdateID, $existing);

            $count2 = 0;
            if ($result->rowCount() > 0) {
                while ($newValues = $result->fetch()) {
                    $count2++;

                    $form->addRow()->addHeading(__('New Condition').' '.$count2);
                    $form->addHiddenValue('gibbonPersonMedicalConditionUpdateID'.($count+$count2), $newValues['gibbonPersonMedicalConditionUpdateID']);

                    foreach ($compareCondition as $fieldName => $label) {
                        $comparisonFields($form, array(), $newValues, $fieldName, $label, $count+$count2);
                    }
                }
            }

            $form->addHiddenValue('count', $count);
            $form->addHiddenValue('count2', $count2);

            $row = $form->addRow();
                $row->addSubmit();

            echo $form->getOutput();
        }
    }
}
