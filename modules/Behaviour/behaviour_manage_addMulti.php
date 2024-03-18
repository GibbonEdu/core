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

use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\Form;
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Services\Format;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

$settingGateway = $container->get(SettingGateway::class);
$enableDescriptors = $settingGateway->getSettingByScope('Behaviour', 'enableDescriptors');
$enableLevels = $settingGateway->getSettingByScope('Behaviour', 'enableLevels');

if (isActionAccessible($guid, $connection2, '/modules/Behaviour/behaviour_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $page->breadcrumbs
        ->add(__('Manage Behaviour Records'), 'behaviour_manage.php')
        ->add(__('Add Multiple'));

    $gibbonBehaviourID = $_GET['gibbonBehaviourID'] ?? null;
    $gibbonMultiIncidentID = $_GET['gibbonMultiIncidentID'] ?? null;
    $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
    $gibbonFormGroupID = $_GET['gibbonFormGroupID'] ?? '';
    $gibbonYearGroupID = $_GET['gibbonYearGroupID'] ?? '';
    $type = $_GET['type'] ?? '';

    $settingGateway = $container->get(SettingGateway::class);

    $form = Form::create('addform', $session->get('absoluteURL').'/modules/Behaviour/behaviour_manage_addMultiProcess.php?gibbonPersonID='.$_GET['gibbonPersonID'].'&gibbonFormGroupID='.$_GET['gibbonFormGroupID'].'&gibbonYearGroupID='.$_GET['gibbonYearGroupID'].'&type='.$_GET['type']);
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->addHiddenValue('address', '/modules/Behaviour/behaviour_manage_addMulti.php');
    $form->addRow()->addHeading('Step 1', __('Step 1'));

    $policyLink = $settingGateway->getSettingByScope('Behaviour', 'policyLink');
    if (!empty($policyLink)) {
        $form->addHeaderAction('viewPolicy', __('View Behaviour Policy'))
            ->setExternalURL($policyLink);
    }
    if (!empty($gibbonPersonID) or !empty($gibbonFormGroupID) or !empty($gibbonYearGroupID) or !empty($type)) {
        $form->addHeaderAction('back', __('Back to Search Results'))
            ->setURL('/modules/Behaviour/behaviour_manage.php')
            ->setIcon('search')
            ->displayLabel()
            ->addParam('gibbonPersonID', $_GET['gibbonPersonID'])
            ->addParam('gibbonFormGroupID', $_GET['gibbonFormGroupID'])
            ->addParam('gibbonYearGroupID', $_GET['gibbonYearGroupID'])
            ->addParam('type', $_GET['type'])
            ->prepend((!empty($policyLink)) ? ' | ' : '');
    }

    //Student
    $row = $form->addRow();
        $col = $row->addColumn();
            $col->addLabel('gibbonPersonIDMulti', __('Students'));

            $studentGateway = $container->get(StudentGateway::class);
            $studentCriteria = $studentGateway->newQueryCriteria()
                ->sortBy(['surname', 'preferredName']);

            $students = array_reduce($studentGateway->queryStudentsBySchoolYear($studentCriteria, $session->get('gibbonSchoolYearID'))->toArray(), function ($array, $student) {
                $array['students'][$student['gibbonPersonID']] = Format::name($student['title'], $student['preferredName'], $student['surname'], 'Student', true) . ' - ' . $student['formGroup'];
                $array['form'][$student['gibbonPersonID']] = $student['formGroup'];
                return $array;
            });

            $multiSelect = $col->addMultiSelect('gibbonPersonIDMulti')
                ->addSortableAttribute('Form', $students['form'])
                ->required();

            $multiSelect->source()->fromArray($students['students']);

    //Date
    $row = $form->addRow();
        $row->addLabel('date', __('Date'));
        $row->addDate('date')->setValue(date($session->get('i18n')['dateFormatPHP']))->required();

    //Type
    $row = $form->addRow();
        $row->addLabel('type', __('Type'));
        $row->addSelect('type')->fromArray(['Positive' => __('Positive'), 'Negative' => __('Negative')])->required();

    //Descriptor
    if ($enableDescriptors == 'Y') {
        $negativeDescriptors = $settingGateway->getSettingByScope('Behaviour', 'negativeDescriptors');
        $negativeDescriptors = (!empty($negativeDescriptors)) ? explode(',', $negativeDescriptors) : [];
        $positiveDescriptors = $settingGateway->getSettingByScope('Behaviour', 'positiveDescriptors');
        $positiveDescriptors = (!empty($positiveDescriptors)) ? explode(',', $positiveDescriptors) : [];

        $chainedToNegative = array_combine($negativeDescriptors, array_fill(0, count($negativeDescriptors), 'Negative'));
        $chainedToPositive = array_combine($positiveDescriptors, array_fill(0, count($positiveDescriptors), 'Positive'));
        $chainedTo = array_merge($chainedToNegative, $chainedToPositive);

        $row = $form->addRow();
            $row->addLabel('descriptor', __('Descriptor'));
            $row->addSelect('descriptor')
                ->fromArray($positiveDescriptors)
                ->fromArray($negativeDescriptors)
                ->chainedTo('type', $chainedTo)
                ->required()
                ->placeholder();
    }

    //Level
    if ($enableLevels == 'Y') {
        $optionsLevels = $settingGateway->getSettingByScope('Behaviour', 'levels');
        if ($optionsLevels != '') {
            $optionsLevels = explode(',', $optionsLevels);
        }
        $row = $form->addRow();
            $row->addLabel('level', __('Level'));
            $row->addSelect('level')
                ->fromArray($optionsLevels)
                ->placeholder();
    }

    $form->addRow()->addHeading('Details', __('Details'));

    //Incident
    $row = $form->addRow();
        $col = $row->addColumn();
        $col->addLabel('comment', __('Incident'));
        $col->addTextArea('comment')
            ->setRows(5)
            ->setClass('fullWidth');

    //Follow Up
    $row = $form->addRow();
        $col = $row->addColumn();
        $col->addLabel('followup', __('Follow Up'));
        $col->addTextArea('followUp')
            ->setRows(5)
            ->setClass('fullWidth');

    // CUSTOM FIELDS
    $container->get(CustomFieldHandler::class)->addCustomFieldsToForm($form, 'Behaviour', []);

    //Copy to Notes
    $row = $form->addRow();
        $row->addLabel('copyToNotes', __('Copy To Notes'));
        $row->addCheckbox('copyToNotes');

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
?>
