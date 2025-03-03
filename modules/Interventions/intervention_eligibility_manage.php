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

use Gibbon\Http\Url;
use Gibbon\Forms\Form;
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\Interventions\INInterventionGateway;
use Gibbon\Domain\Interventions\INInterventionEligibilityAssessmentGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Interventions/intervention_eligibility_manage.php') == false) {
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

        $form->addHiddenValue('q', '/modules/Interventions/intervention_eligibility_manage.php');

        $row = $form->addRow();
            $row->addLabel('gibbonPersonID', __('Student'));
            $row->addSelectStudent('gibbonPersonID', $session->get('gibbonSchoolYearID'))->selected($gibbonPersonID)->placeholder();

        $row = $form->addRow();
            $row->addLabel('gibbonFormGroupID', __('Form Group'));
            $row->addSelectFormGroup('gibbonFormGroupID', $session->get('gibbonSchoolYearID'))->selected($gibbonFormGroupID)->placeholder();

        $row = $form->addRow();
            $row->addLabel('gibbonYearGroupID', __('Year Group'));
            $row->addSelectYearGroup('gibbonYearGroupID')->selected($gibbonYearGroupID)->placeholder();

        $statuses = [
            'In Progress' => __('In Progress'),
            'Complete' => __('Complete'),
        ];
        $row = $form->addRow();
            $row->addLabel('status', __('Status'));
            $row->addSelect('status')->fromArray(['' => __('All')])->fromArray($statuses)->selected($status);

        $row = $form->addRow();
            $row->addSearchSubmit($session, __('Clear Filters'));

        echo $form->getOutput();

        // Query the intervention-based eligibility assessments
        $assessmentGateway = $container->get(INInterventionEligibilityAssessmentGateway::class);
        $criteria = $assessmentGateway->newQueryCriteria(true)
            ->sortBy(['p.surname', 'p.preferredName'])
            ->filterBy('gibbonPersonID', $gibbonPersonID)
            ->filterBy('gibbonFormGroupID', $gibbonFormGroupID)
            ->filterBy('gibbonYearGroupID', $gibbonYearGroupID)
            ->filterBy('status', $status)
            ->fromPOST();
            
        $assessments = $assessmentGateway->queryEligibilityAssessments($criteria, $session->get('gibbonSchoolYearID'));
        
        // DATA TABLE
        $table = DataTable::createPaginated('interventionEligibilityManage', $criteria);
        $table->setTitle(__('Eligibility Assessments'));

        if ($highestAction == 'Manage Eligibility Assessments') {
            $table->addHeaderAction('add', __('Add'))
                ->setURL('/modules/Interventions/intervention_eligibility_edit.php')
                ->addParam('gibbonPersonIDStudent', $gibbonPersonID)
                ->addParam('gibbonFormGroupID', $gibbonFormGroupID)
                ->addParam('gibbonYearGroupID', $gibbonYearGroupID)
                ->addParam('status', $status)
                ->displayLabel();
        }

        $table->addColumn('student', __('Student'))
            ->format(function ($row) {
                return Format::name('', $row['preferredName'], $row['surname'], 'Student', true, true);
            });

        $table->addColumn('formGroup', __('Form Group'));
        $table->addColumn('yearGroup', __('Year Group'));
        $table->addColumn('interventionName', __('Intervention'));
        $table->addColumn('status', __('Status'));
        
        $table->addColumn('eligibilityDecision', __('Decision'))
            ->format(function ($row) {
                if ($row['status'] == 'Complete') {
                    return $row['eligibilityDecision'];
                } else {
                    return __('Pending');
                }
            });

        $table->addColumn('timestampCreated', __('Date'))
            ->format(Format::using('dateTime', ['timestampCreated']));

        $table->addActionColumn()
            ->addParam('gibbonINInterventionEligibilityAssessmentID')
            ->addParam('gibbonINInterventionID')
            ->addParam('gibbonPersonIDStudent', $gibbonPersonID)
            ->addParam('gibbonFormGroupID', $gibbonFormGroupID)
            ->addParam('gibbonYearGroupID', $gibbonYearGroupID)
            ->addParam('status', $status)
            ->format(function ($row, $actions) use ($highestAction) {
                $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Interventions/intervention_eligibility_edit.php');
                
                if ($highestAction == 'Manage Eligibility Assessments') {
                    $actions->addAction('delete', __('Delete'))
                        ->setURL('/modules/Interventions/intervention_eligibility_delete.php')
                        ->modalWindow(650, 400);
                }
            });

        if (count($assessments) > 0) {
            echo $table->render($assessments);
        } else {
            echo "<div class='error'>";
            echo __('There are no records to display.');
            echo "</div>";
        }
    }
}
