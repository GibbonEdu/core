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
use Gibbon\Domain\Interventions\INInterventionGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Interventions/interventions_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Get action with highest precedence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if (empty($highestAction)) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        $page->breadcrumbs->add(__('Manage Interventions'));

        $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
        $gibbonFormGroupID = $_GET['gibbonFormGroupID'] ?? '';
        $gibbonYearGroupID = $_GET['gibbonYearGroupID'] ?? '';
        $status = $_GET['status'] ?? '';

        $form = Form::create('filter', $session->get('absoluteURL').'/index.php', 'get');
        $form->setTitle(__('Filter'));
        $form->setClass('noIntBorder w-full');
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->addHiddenValue('q', '/modules/Interventions/interventions_manage.php');

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
            'Pending' => __('Pending'),
            'In Progress' => __('In Progress'),
            'Completed' => __('Completed'),
            'Discontinued' => __('Discontinued')
        ];
        $row = $form->addRow();
            $row->addLabel('status', __('Status'));
            $row->addSelect('status')->fromArray(['' => __('All')] + $statuses)->selected($status);

        $row = $form->addRow();
            $row->addSearchSubmit($session, __('Clear Filters'));

        echo $form->getOutput();

        // CRITERIA
        $interventionGateway = $container->get(INInterventionGateway::class);
        
        $criteria = $interventionGateway->newQueryCriteria()
            ->sortBy(['student.surname', 'student.preferredName'])
            ->filterBy('student', $gibbonPersonID)
            ->filterBy('formGroup', $gibbonFormGroupID)
            ->filterBy('yearGroup', $gibbonYearGroupID)
            ->filterBy('status', $status)
            ->fromPOST();

        // Get the current school year
        $gibbonSchoolYearID = $session->get('gibbonSchoolYearID');
        
        // QUERY
        if ($highestAction == 'Manage Interventions_all') {
            $interventions = $interventionGateway->queryInterventions($criteria, $gibbonSchoolYearID);
        } else if ($highestAction == 'Manage Interventions_my') {
            $interventions = $interventionGateway->queryInterventions($criteria, $gibbonSchoolYearID, $session->get('gibbonPersonID'));
        }

        // DATA TABLE
        $table = DataTable::createPaginated('interventionsManage', $criteria);
        $table->setTitle(__('Interventions'));

        $table->modifyRows(function ($intervention, $row) {
            if ($intervention['status'] == 'Resolved' || $intervention['status'] == 'Completed') $row->addClass('success');
            if ($intervention['status'] == 'Referral' || $intervention['status'] == 'Form Tutor Review') $row->addClass('warning');
            if ($intervention['status'] == 'Intervention') $row->addClass('current');
            return $row;
        });

        $table->addExpandableColumn('details')
            ->format(function ($intervention) {
                $output = '';
                $output .= '<strong>'.__('Description').'</strong><br/>';
                $output .= nl2br($intervention['description']).'<br/>';
                
                if (!empty($intervention['formTutorNotes'])) {
                    $output .= '<br/><strong>'.__('Form Tutor Notes').'</strong><br/>';
                    $output .= nl2br($intervention['formTutorNotes']).'<br/>';
                }
                
                if (!empty($intervention['outcomeNotes'])) {
                    $output .= '<br/><strong>'.__('Outcome Notes').'</strong><br/>';
                    $output .= nl2br($intervention['outcomeNotes']).'<br/>';
                }
                
                return $output;
            });

        $table->addHeaderAction('add', __('New Intervention'))
            ->setURL('/modules/Interventions/interventions_manage_add.php')
            ->addParam('gibbonPersonID', $gibbonPersonID)
            ->addParam('gibbonFormGroupID', $gibbonFormGroupID)
            ->addParam('gibbonYearGroupID', $gibbonYearGroupID)
            ->displayLabel();

        $table->addColumn('student', __('Student'))
            ->description(__('Form Group'))
            ->sortable(['student.surname', 'student.preferredName'])
            ->width('25%')
            ->format(function ($intervention) use ($pdo) {
                $url = './index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$intervention['gibbonPersonID'].'&subpage=Individual Needs&search=&allStudents=&sort=surname,preferredName';
                
                $output = '<b>'.Format::link($url, Format::name('', $intervention['preferredName'], $intervention['surname'], 'Student', true)).'</b>';
                
                // If form group is missing, try to get it directly
                if (!isset($intervention['formGroup']) || empty($intervention['formGroup'])) {
                    if (isset($intervention['gibbonPersonID'])) {
                        $data = ['gibbonPersonID' => $intervention['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION['gibbonSchoolYearID'] ?? null];
                        if (!empty($data['gibbonSchoolYearID'])) {
                            $sql = "SELECT formGroup.name AS formGroup 
                                    FROM gibbonStudentEnrolment 
                                    JOIN gibbonFormGroup AS formGroup ON (formGroup.gibbonFormGroupID=gibbonStudentEnrolment.gibbonFormGroupID) 
                                    WHERE gibbonStudentEnrolment.gibbonPersonID=:gibbonPersonID 
                                    AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID";
                            $formGroupResult = $pdo->select($sql, $data);
                            if ($formGroupResult->rowCount() > 0) {
                                $intervention['formGroup'] = $formGroupResult->fetch()['formGroup'];
                            }
                        }
                    }
                }
                
                if (isset($intervention['formGroup']) && !empty($intervention['formGroup'])) {
                    $output .= '<br/><small><i>'.$intervention['formGroup'].'</i></small>';
                }
                
                return $output;
            });

        $table->addColumn('name', __('Intervention'))->sortable();
        
        $table->addColumn('status', __('Status'))->sortable();
        
        $table->addColumn('parentConsent', __('Parent Consent'))
            ->format(function ($intervention) {
                return isset($intervention['parentConsent']) ? Format::yesNo($intervention['parentConsent']) : Format::yesNo('N');
            });
        
        $table->addColumn('targetDate', __('Target Date'))
            ->format(function ($intervention) {
                return !empty($intervention['targetDate']) ? Format::date($intervention['targetDate']) : '';
            });
            
        $table->addColumn('creator', __('Created By'))
            ->sortable(['surnameCreator', 'preferredNameCreator'])
            ->format(function ($intervention) use ($pdo) {
                // If creator info is missing, try to get it directly
                if (!isset($intervention['titleCreator']) || !isset($intervention['preferredNameCreator']) || !isset($intervention['surnameCreator'])) {
                    if (isset($intervention['gibbonPersonIDCreator'])) {
                        $data = ['gibbonPersonID' => $intervention['gibbonPersonIDCreator']];
                        $sql = "SELECT title, preferredName, surname FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID";
                        $result = $pdo->select($sql, $data);
                        
                        if ($result && $result->rowCount() > 0) {
                            $person = $result->fetch();
                            $output = Format::name($person['title'], $person['preferredName'], $person['surname'], 'Staff', false, true);
                        } else {
                            $output = __('Unknown');
                        }
                    } else {
                        $output = __('Unknown');
                    }
                } else {
                    $output = Format::name($intervention['titleCreator'], $intervention['preferredNameCreator'], $intervention['surnameCreator'], 'Staff', false, true);
                }
                
                if (isset($intervention['timestampCreated'])) {
                    $output .= '<br/><span class="text-xs">'.Format::date($intervention['timestampCreated']).'</span>';
                }
                return $output;
            });

        // ACTIONS
        $table->addActionColumn()
            ->addParam('gibbonINInterventionID')
            ->addParam('gibbonPersonID', $gibbonPersonID)
            ->addParam('gibbonFormGroupID', $gibbonFormGroupID)
            ->addParam('gibbonYearGroupID', $gibbonYearGroupID)
            ->format(function ($intervention, $actions) use ($highestAction) {
                $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Interventions/interventions_manage_edit.php');
                
                // Add delete button
                $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/Interventions/interventions_manage_delete.php')
                    ->setIcon('trash')
                    ->setClass('text-red')
                    ->modalWindow(650, 400);
            });

        echo $table->render($interventions);
    }
}
