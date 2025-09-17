<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

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
use Gibbon\Tables\DataTable;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\StudentAlerts\AlertGateway;
use Gibbon\View\Component;
use Gibbon\UI\Components\Alert;
use Gibbon\Domain\School\YearGroupGateway;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (!isActionAccessible($guid, $connection2, '/modules/Student Alerts/studentAlerts_manage.php')) {
	// Access denied
	$page->addError(__('You do not have access to this action.'));
} else {
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        $page->breadcrumbs->add(__('Manage Alerts'));

        $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
        $gibbonFormGroupID = $_GET['gibbonFormGroupID'] ?? '';
        $gibbonYearGroupID = $_GET['gibbonYearGroupID'] ?? '';

        $alertGateway = $container->get(AlertGateway::class);

        $yearGroup = $container->get(YearGroupGateway::class)->getYearGroupByPerson($session->get('gibbonPersonID'));
        if ($highestAction == 'Manage Student Alerts_headOfYear' && !empty($yearGroup) && empty($gibbonYearGroupID)) {
            $gibbonYearGroupIDHOY = $yearGroup['gibbonYearGroupID'];
            $gibbonYearGroupID = $yearGroup['gibbonYearGroupID'];
        }

        // SEARCH
        $form = Form::createSearch();
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $row = $form->addRow();
            $row->addLabel('gibbonPersonID',__('Student'));
            $row->addSelectStudent('gibbonPersonID', $session->get('gibbonSchoolYearID'))->selected($gibbonPersonID)->placeholder();

        $row = $form->addRow();
            $row->addLabel('gibbonFormGroupID',__('Form Group'));
            $row->addSelectFormGroup('gibbonFormGroupID', $session->get('gibbonSchoolYearID'))->selected($gibbonFormGroupID)->placeholder();

        $row = $form->addRow();
            $row->addLabel('gibbonYearGroupID',__('Year Group'));
            $row->addSelectYearGroup('gibbonYearGroupID')->placeholder()->selected($gibbonYearGroupID);

        $row = $form->addRow();
            $row->addFooter();
            $row->addSearchSubmit($session, __('Clear Filters'));

        echo $form->getOutput();
        

         // CRITERIA
        $criteria = $alertGateway->newQueryCriteria(true)
            ->sortBy('timestampCreated', 'DESC')
            ->filterBy('student', $gibbonPersonID)
            ->filterBy('formGroup', $gibbonFormGroupID)
            ->filterBy('yearGroup', $gibbonYearGroupID)
            ->fromPOST();

        if ($highestAction == 'Manage Student Alerts_all' || $highestAction == 'Manage Student Alerts_headOfYear') {
            $alerts = $alertGateway->queryAlertsBySchoolYear($criteria, $session->get('gibbonSchoolYearID'));
        } else if ($highestAction == 'Manage Student Alerts_my') {
            $alerts = $alertGateway->queryAlertsBySchoolYear($criteria, $session->get('gibbonSchoolYearID'), $session->get('gibbonPersonID'));
        } else {
            return;
        }

        // DATA TABLE
        $table = DataTable::createPaginated('manageAlerts', $criteria);
        $table->setTitle(__('Alerts'));

        $table->addHeaderAction('add', __('Add Alert'))
            ->setURL('/modules/Student Alerts/studentAlerts_add.php')
            ->addParam('gibbonPersonID', $gibbonPersonID)
            ->addParam('gibbonFormGroupID', $gibbonFormGroupID)
            ->addParam('gibbonYearGroupID', $gibbonYearGroupID)
            ->displayLabel();

        $table->modifyRows(function($alert, $row) {
            // if ($alert['status'] == 'Approved') $row->addClass('success');
            if ($alert['status'] == 'Pending') $row->addClass('warning');
            elseif ($alert['status'] == 'Declined') $row->addClass('dull');
            return $row;
        });

        // $table->addExpandableColumn('comment')
        //     ->format(function($alert) {
        //         $output = '';
        //         if (!empty($alert['comment'])) {
        //             $output .= Format::bold(__('Incident')).'<br/>';
        //             $output .= nl2br($alert['comment']).'<br/>';
        //         }
        //         return $output;
        //     });

        $table->addColumn('tag', __('Tag'))
            ->width('8%')
            ->format(function($values) {
                return Component::render(Alert::class, [
                    'color'   => $values['levelColor'] ?? $values['color'],
                    'colorBG' => $values['levelColorBG'] ?? $values['colorBG'],
                    'title' => $values['type'] ?? '',
                    'large' => true,
                ] + $values);
            });

        $table->addColumn('student', __('Student'))
            ->description(__('Form Group'))
            ->sortable(['student.surname', 'student.preferredName'])
            ->context('primary')
            ->format(function($student) {
                return Format::nameLinked($student['gibbonPersonID'], '', $student['preferredName'], $student['surname'], 'Student', true, true, ['subpage' => 'Personal']) . '<br/><small><i>'.$student['formGroup'].'</i></small>';
            });

        $table->addColumn('type', __('Type'));
        $table->addColumn('level', __('Level'));
        $table->addColumn('status', __('Status'));

        // $table->addColumn('dateStart', __('Start Date'))
        //     ->format(function($alert) {
        //         if (!empty($alert['dateStart'])) {
        //             return Format::dateReadable($alert['dateStart']);
                    
        //         }
        //         return Format::tag(__('N/A'), 'dull');
        //     });

        // $table->addColumn('dateEnd', __('End Date'))
        //     ->format(function($alert) {
        //         if (!empty($alert['dateEnd'])) {
        //             return Format::dateReadable($alert['dateEnd']);
        //         }
        //         return Format::tag(__('N/A'), 'dull');
        //     });

        $table->addColumn('teacher', __('Created By'))
            ->context('secondary')
            ->sortable(['preferredNameCreator', 'surnameCreator'])
            ->format(function($staff) {
                return Format::name($staff['titleCreator'], $staff['preferredNameCreator'], $staff['surnameCreator'], 'Staff');
            });

        $table->addColumn('timestampCreated', __('Date Recorded'))
            ->context('primary')
            ->format(function($alert) {
                return Format::date($alert['timestampCreated']);
            });

        $table->addActionColumn()
            ->addParam('gibbonPersonID', $gibbonPersonID)
            ->addParam('gibbonFormGroupID', $gibbonFormGroupID)
            ->addParam('gibbonYearGroupID', $gibbonYearGroupID)
            ->addParam('gibbonAlertID')
            ->format(function ($alert, $actions) use ($highestAction) {
                $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Student Alerts/studentAlerts_edit.php');

                $actions->addAction('view', __('View'))
                    ->setURL('/modules/Student Alerts/studentAlerts_manage_view.php');
                
                if ($highestAction == 'Manage Student Alerts_all') {
                    if ($alert['status'] == 'Pending') {
                        $actions->addAction('approve', __('Approve'))
                            ->setIcon('iconTick')
                            ->addParam('status', 'Approved')
                            ->setURL('/modules/Student Alerts/studentAlerts_manage_approval.php');

                        $actions->addAction('decline', __('Decline'))
                            ->setIcon('iconCross')
                            ->addParam('status', 'Declined')
                            ->setURL('/modules/Student Alerts/studentAlerts_manage_approval.php');
                    }
                }
            });

        echo $table->render($alerts);
    }
}
