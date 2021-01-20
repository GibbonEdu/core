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
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\Activities\ActivityReportGateway;
use Gibbon\Services\Format;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Activities/report_activityChoices_byStudent.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Activity Choices By Student'));

    $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';

    $form = Form::create('action', $_SESSION[$guid]['absoluteURL']."/index.php", "get");
    $form->setTitle(__('Choose Student'));
    $form->setClass('noIntBorder fullWidth');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('q', "/modules/".$_SESSION[$guid]['module']."/report_activityChoices_byStudent.php");

    $row = $form->addRow();
        $row->addLabel('gibbonPersonID', __('Student'));
        $row->addSelectStudent('gibbonPersonID', $_SESSION[$guid]['gibbonSchoolYearID'], array("allStudents" => false, "byName" => true, "byRoll" => true))->required()->placeholder()->selected($gibbonPersonID);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSearchSubmit($gibbon->session);

    echo $form->getOutput();

    if (!empty($gibbonPersonID)) {
        $options = getSettingByScope($connection2, 'Activities', 'activityTypes');
        $dateType = getSettingByScope($connection2, 'Activities', 'dateType');
        if ($dateType == 'Term') {
            $maxPerTerm = getSettingByScope($connection2, 'Activities', 'maxPerTerm');
        }

        $gateway = $container->get(ActivityReportGateway::class);
        $criteriaYears = $gateway
            ->newQueryCriteria()
            ->filterBy('gibbonPersonID', $gibbonPersonID)
            ->fromPOST();

        $enroledYears = $gateway->queryStudentYears($criteriaYears);
        
        foreach ($enroledYears as $enroledYear) {
            $criteriaActivities = $gateway
                ->newQueryCriteria()
                ->sortBy('activityName')
                ->filterBy('gibbonPersonID', $gibbonPersonID)
                ->filterBy('gibbonSchoolYearID', $enroledYear['gibbonSchoolYearID'])
                ->fromPOST();

            $activities = $gateway->queryStudentActivities($criteriaActivities);
            $table = DataTable::create('activities');
            $table->setTitle($enroledYear['name']);
            $table->addColumn('activityName', __('Activity'));

            if ($options != '') {
                $table->addColumn('activityType', __('Type'));
            }

            if ($dateType != 'Date') {
                //If system is configured for term based activities, show the term assigned to the assigned activity
                $table->addColumn('terms', __('Terms'))
                    ->format(function ($item) {
                        //Check terms have been assigned to this activity
                        if ($item['terms'] == '') {
                            if ($item['programStart'] != '' || $item['programEnd'] != '') {
                                return Format::small(__('Assigned to date'));
                            }
                            return Format::small(__('Not assigned to term'));
                        }

                        return $item['terms'];
                    });
            } else {
                //If system is configured for date based activities, summarise the date range
                //e.g. 01-01-2020 -> 31-07-2020 into July 2020
                $table->addColumn('programStart', __('Dates'))
                    ->format(function ($item) {
                        if ($item['programStart'] == '' || $item['programEnd'] == '') {
                            if ($item['terms'] != '') {
                                return Format::small(__('Assigned to term'));
                            }
                            return Format::small(__('Not assigned to date range'));
                        } else {
                            return Format::dateRangeReadable($item['programStart'], $item['programEnd']);
                        }
                    });
            }

            $table->addColumn('status', __('Status'));
            $table->addActionColumn()
                ->addParam('gibbonActivityID')
                ->format(function ($item, $actions) {
                    $actions
                        ->addAction('view', __('View Details'))
                        ->setURL('/modules/Activities/activities_view_full.php')
                        ->isModal(900, 500);
                });

            echo $table->render($activities);
        }
    }
}
