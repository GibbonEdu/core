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
use Gibbon\Module\Interventions\Domain\INInterventionGateway;
use Gibbon\Module\Interventions\Domain\INInterventionStrategyGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Interventions/interventions_manage_outcome_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Get action with highest precedence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if (empty($highestAction)) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        // Proceed!
        $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
        $gibbonFormGroupID = $_GET['gibbonFormGroupID'] ?? '';
        $gibbonYearGroupID = $_GET['gibbonYearGroupID'] ?? '';
        $status = $_GET['status'] ?? '';
        $gibbonINInterventionID = $_GET['gibbonINInterventionID'] ?? '';
        $gibbonINInterventionStrategyID = $_GET['gibbonINInterventionStrategyID'] ?? '';

        if (empty($gibbonINInterventionID) || empty($gibbonINInterventionStrategyID)) {
            $page->addError(__('You have not specified one or more required parameters.'));
            return;
        }

        $interventionGateway = $container->get(INInterventionGateway::class);
        $strategyGateway = $container->get(INInterventionStrategyGateway::class);
        
        $intervention = $interventionGateway->getInterventionByID($gibbonINInterventionID);
        $strategy = $strategyGateway->getByID($gibbonINInterventionStrategyID);

        if (empty($intervention) || empty($strategy)) {
            $page->addError(__('The specified record cannot be found.'));
            return;
        }

        // Check access based on the highest action level
        if ($highestAction == 'Manage Interventions_my' && $intervention['gibbonPersonIDCreator'] != $session->get('gibbonPersonID')) {
            $page->addError(__('You do not have access to this action.'));
            return;
        }

        $page->breadcrumbs
            ->add(__('Manage Interventions'), 'interventions_manage.php', [
                'gibbonPersonID' => $gibbonPersonID,
                'gibbonFormGroupID' => $gibbonFormGroupID,
                'gibbonYearGroupID' => $gibbonYearGroupID,
                'status' => $status,
            ])
            ->add(__('Edit Intervention'), 'interventions_manage_edit.php', [
                'gibbonINInterventionID' => $gibbonINInterventionID,
                'gibbonPersonID' => $gibbonPersonID,
                'gibbonFormGroupID' => $gibbonFormGroupID,
                'gibbonYearGroupID' => $gibbonYearGroupID,
                'status' => $status,
            ])
            ->add(__('Add Outcome'));

        $form = Form::create('outcome', $session->get('absoluteURL').'/modules/Interventions/interventions_manage_outcome_addProcess.php');
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->addHiddenValue('address', $session->get('address'));
        $form->addHiddenValue('gibbonINInterventionID', $gibbonINInterventionID);
        $form->addHiddenValue('gibbonINInterventionStrategyID', $gibbonINInterventionStrategyID);
        $form->addHiddenValue('gibbonPersonID', $gibbonPersonID);
        $form->addHiddenValue('gibbonFormGroupID', $gibbonFormGroupID);
        $form->addHiddenValue('gibbonYearGroupID', $gibbonYearGroupID);
        $form->addHiddenValue('status', $status);

        // Get student details
        $studentName = Format::name('', $intervention['preferredName'], $intervention['surname'], 'Student', true);
        $form->addRow()->addHeading(__('Student Details'));
        $row = $form->addRow();
            $row->addLabel('studentName', __('Student'));
            $row->addTextField('studentName')->setValue($studentName)->readonly();

        $row = $form->addRow();
            $row->addLabel('formGroup', __('Form Group'));
            $row->addTextField('formGroup')->setValue($intervention['formGroup'])->readonly();

        // Strategy Details
        $form->addRow()->addHeading(__('Strategy'));
        $row = $form->addRow();
            $row->addLabel('strategyName', __('Strategy'));
            $row->addTextField('strategyName')->setValue($strategy['name'])->readonly();

        // Outcome Details
        $form->addRow()->addHeading(__('Outcome Details'));
        $row = $form->addRow();
            $row->addLabel('outcome', __('Outcome'))->description(__('What was the result of this strategy?'));
            $row->addTextArea('outcome')->setRows(5)->required();

        $row = $form->addRow();
            $row->addLabel('evidence', __('Evidence'))->description(__('What evidence supports this outcome?'));
            $row->addTextArea('evidence')->setRows(5);

        $successOptions = [
            'Yes' => __('Yes'),
            'No' => __('No'),
            'Partial' => __('Partial')
        ];
        $row = $form->addRow();
            $row->addLabel('successful', __('Successful?'))->description(__('Was this strategy successful?'));
            $row->addSelect('successful')->fromArray($successOptions)->required();

        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

        echo $form->getOutput();
    }
}
