<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright 2010, Gibbon Foundation
Gibbon, Gibbon Education Ltd. (Hong Kong)

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
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\IndividualNeeds\INInvestigationGateway;
use Gibbon\Domain\IndividualNeeds\INEligibilityAssessmentGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/eligibility_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Get action with highest precedence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if (empty($highestAction)) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        $page->breadcrumbs->add(__('Manage Eligibility Assessments'));

        $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
        $gibbonFormGroupID = $_GET['gibbonFormGroupID'] ?? '';
        $gibbonYearGroupID = $_GET['gibbonYearGroupID'] ?? '';
        $status = $_GET['status'] ?? '';

        $form = Form::create('filter', $session->get('absoluteURL').'/index.php', 'get');
        $form->setTitle(__('Filter'));
        $form->setClass('noIntBorder w-full');
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->addHiddenValue('q', '/modules/Individual Needs/eligibility_manage.php');

        $row = $form->addRow();
            $row->addLabel('gibbonPersonID', __('Student'));
            $row->addSelectStudent('gibbonPersonID', $session->get('gibbonSchoolYearID'))->placeholder()->selected($gibbonPersonID);

        $row = $form->addRow();
            $row->addLabel('gibbonFormGroupID', __('Form Group'));
            $row->addSelectFormGroup('gibbonFormGroupID', $session->get('gibbonSchoolYearID'))->placeholder()->selected($gibbonFormGroupID);

        $row = $form->addRow();
            $row->addLabel('gibbonYearGroupID', __('Year Group'));
            $row->addSelectYearGroup('gibbonYearGroupID')->placeholder()->selected($gibbonYearGroupID);
            
        $statuses = [
            'Eligibility Assessment' => __('In Progress'),
            'Eligibility Complete' => __('Complete')
        ];
        $row = $form->addRow();
            $row->addLabel('status', __('Status'));
            $row->addSelect('status')->fromArray(['' => __('All')] + $statuses)->selected($status);

        $row = $form->addRow();
            $row->addSearchSubmit($session, __('Clear Filters'));

        echo $form->getOutput();

        // CRITERIA
        $investigationGateway = $container->get(INInvestigationGateway::class);
        
        $criteria = $investigationGateway->newQueryCriteria()
            ->sortBy(['student.surname', 'student.preferredName'])
            ->filterBy('student', $gibbonPersonID)
            ->filterBy('formGroup', $gibbonFormGroupID)
            ->filterBy('yearGroup', $gibbonYearGroupID)
            ->filterBy('status', $status)
            ->fromPOST();

        // Get the current school year
        $gibbonSchoolYearID = $session->get('gibbonSchoolYearID');
        
        // QUERY
        $investigations = null; // Initialize as null to prevent undefined variable error
        
        if ($highestAction == 'Manage Eligibility Assessments_all') {
            $investigations = $investigationGateway->queryEligibilityAssessments($criteria, $gibbonSchoolYearID);
        } else if ($highestAction == 'Manage Eligibility Assessments_my') {
            $investigations = $investigationGateway->queryEligibilityAssessments($criteria, $gibbonSchoolYearID, $session->get('gibbonPersonID'));
        }

        // Only proceed if we have investigations data
        if (!is_null($investigations)) {
            // DATA TABLE
            $table = DataTable::createPaginated('eligibilityManage', $criteria);
            $table->setTitle(__('Eligibility Assessments'));

            $table->modifyRows(function ($investigation, $row) {
                if ($investigation['status'] == 'Eligibility Complete') $row->addClass('success');
                if ($investigation['status'] == 'Eligibility Assessment') $row->addClass('warning');
                return $row;
            });

            $table->addColumn('student', __('Student'))
                ->sortable(['student.surname', 'student.preferredName'])
                ->format(function ($investigation) {
                    return Format::name('', $investigation['preferredName'], $investigation['surname'], 'Student', true);
                });

            $table->addColumn('formGroup', __('Form Group'))->sortable();

            $table->addColumn('status', __('Status'))->sortable();
            
            $table->addColumn('eligibilityDecision', __('Decision'))
                ->format(function ($investigation) {
                    if ($investigation['eligibilityDecision'] == 'Eligible') {
                        return '<span class="tag success">'.__('Eligible').'</span>';
                    } else if ($investigation['eligibilityDecision'] == 'Not Eligible') {
                        return '<span class="tag error">'.__('Not Eligible').'</span>';
                    } else {
                        return '<span class="tag dull">'.__('Pending').'</span>';
                    }
                });
                
            $table->addColumn('creator', __('Created By'))
                ->sortable(['surnameCreator', 'preferredNameCreator'])
                ->format(function ($investigation) {
                    return Format::name($investigation['titleCreator'], $investigation['preferredNameCreator'], $investigation['surnameCreator'], 'Staff', false, true);
                });

            // ACTIONS
            $table->addActionColumn()
                ->addParam('gibbonINInvestigationID')
                ->addParam('gibbonPersonID', $gibbonPersonID)
                ->addParam('gibbonFormGroupID', $gibbonFormGroupID)
                ->addParam('gibbonYearGroupID', $gibbonYearGroupID)
                ->format(function ($investigation, $actions) use ($highestAction) {
                    $actions->addAction('edit', __('Edit'))
                        ->setURL('/modules/Individual Needs/eligibility_edit.php');
                });

            echo $table->render($investigations);
        }
    }
}
