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

use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\RollGroups\RollGroupGateway;
use Gibbon\Domain\School\YearGroupGateway;

if (isActionAccessible($guid, $connection2, '/modules/Roll Groups/rollGroups.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        //Proceed!
        $page->breadcrumbs->add(__('View Roll Groups'));

        $gateway = $container->get(RollGroupGateway::class);
        if ($highestAction == "View Roll Groups_all") {
            $rollGroups = $gateway->selectRollGroupsBySchoolYear($gibbon->session->get('gibbonSchoolYearID'));
        }
        else {
            $rollGroups = $gateway->selectRollGroupsBySchoolYearMyChildren($gibbon->session->get('gibbonSchoolYearID'), $gibbon->session->get('gibbonPersonID'));
        }

        $formatTutorsList = function($row) use ($gateway) {
            $tutors = $gateway->selectTutorsByRollGroup($row['gibbonRollGroupID'])->fetchAll();
            if (count($tutors) > 1) $tutors[0]['surname'] .= ' ('.__('Main Tutor').')';

            return Format::nameList($tutors, 'Staff', false, true);
        };

        $table = DataTable::create('rollGroups');
        $table->setTitle(__('Roll Groups'));

        $table->addColumn('name', __('Name'));
        $table->addColumn('tutors', __('Form Tutors'))->format($formatTutorsList);
        $table->addColumn('space', __('Room'));
        if (getRoleCategory($_SESSION[$guid]['gibbonRoleIDCurrent'], $connection2) == "Staff") {
            $table->addColumn('students', __('Students'));
        }
        $table->addColumn('website', __('Website'))->format(Format::using('link', 'website'));

        $actions = $table->addActionColumn()->addParam('gibbonRollGroupID');
        $actions->addAction('view', __('View'))
                ->setURL('/modules/Roll Groups/rollGroups_details.php');

        echo $table->render($rollGroups->toDataSet());

        //Display year group table for staff
        $roleCategory = getRoleCategory($_SESSION[$guid]['gibbonRoleIDCurrent'], $connection2);
        if ($roleCategory == 'Staff') {
            $yearGroupGateway = $container->get(YearGroupGateway::class);

            $criteria = $yearGroupGateway->newQueryCriteria(true)
                ->sortBy(['gibbonYearGroup.sequenceNumber'])
                ->fromPOST('clinics');

            $yearGroups = $yearGroupGateway->queryYearGroups($criteria);

            $table = DataTable::create('yearGroups');
            $table->setTitle(__('Year Group Summary'));

            $table->addColumn('name', __('Name'));
            $table->addColumn('gibbonPersonIDHOY', __('Head of Year'))
                ->format(function ($values) {
                    if (!empty($values['preferredName']) && !empty($values['surname'])) {
                        return Format::name('', $values['preferredName'], $values['surname'], 'Staff', false, true);
                    }
                });
            $table->addColumn('students', __('Students'))
                ->format(function ($values) use ($yearGroupGateway) {
                    return $yearGroupGateway->studentCountByYearGroup($values['gibbonYearGroupID']);
                });

            echo $table->render($yearGroups);
        }
    }
}
