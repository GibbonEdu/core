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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\Activities\ActivityReportGateway;
use Gibbon\Services\Format;
use Gibbon\Domain\Activities\ActivityGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Activities/report_activityChoices_byStudent.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Activity Choices By Student'));

    $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';

    $form = Form::create('action', $session->get('absoluteURL')."/index.php", "get");
    $form->setTitle(__('Choose Student'));
    $form->setClass('noIntBorder fullWidth');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('q', "/modules/".$session->get('module')."/report_activityChoices_byStudent.php");

    $row = $form->addRow();
        $row->addLabel('gibbonPersonID', __('Student'));
        $row->addSelectStudent('gibbonPersonID', $session->get('gibbonSchoolYearID'), array("allStudents" => false, "byName" => true, "byForm" => true))->required()->placeholder()->selected($gibbonPersonID);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSearchSubmit($session);

    echo $form->getOutput();

    if (!empty($gibbonPersonID)) {
        $settingGateway = $container->get(SettingGateway::class);
        $activityTypes = $container->get(ActivityGateway::class)->selectActivityTypeOptions()->fetchKeyPair();
        $dateType = $settingGateway->getSettingByScope('Activities', 'dateType');
        if ($dateType == 'Term') {
            $maxPerTerm = $settingGateway->getSettingByScope('Activities', 'maxPerTerm');
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

            if (!empty($activityTypes)) {
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

            $table->addColumn('status', __('Status'))->translatable();
            $table->addActionColumn()
                ->addParam('gibbonActivityID')
                ->format(function ($item, $actions) {
                    $actions
                        ->addAction('view', __('View Details'))
                        ->setURL('/modules/Activities/activities_view_full.php')
                        ->isModal(1000, 500);
                });

            echo $table->render($activities);
        }
    }
}
