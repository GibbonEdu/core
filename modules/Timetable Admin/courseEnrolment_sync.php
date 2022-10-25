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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\Form;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\Timetable\CourseSyncGateway;
use Gibbon\Http\Url;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_sync.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $page->breadcrumbs->add(__('Sync Course Enrolment'));

    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');

    $page->navigator->addSchoolYearNavigation($gibbonSchoolYearID);

    $form = Form::create(
        'settings',
        Url::fromModuleRoute('Timetable Admin', 'courseEnrolment_sync_settingsProcess')
    );
    $form->setTitle(__('Settings'));
    $form->addHiddenValue('address', $session->get('address'));

    $setting = $container->get(SettingGateway::class)->getSettingByScope('Timetable Admin', 'autoEnrolCourses', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $row = $form->addRow();
        $row->addSubmit();

    echo $form->getOutput();

    $syncGateway = $container->get(CourseSyncGateway::class);

    // QUERY
    $criteria = $syncGateway->newQueryCriteria(true)
        ->sortBy(['gibbonYearGroup.sequenceNumber'])
        ->fromArray($_POST);

    $classMaps = $syncGateway->queryCourseClassMaps($criteria, $gibbonSchoolYearID);
    $classMapsAllYearGroups = implode(',', $classMaps->getColumn('gibbonYearGroupID'));

    $table = DataTable::createPaginated('sync', $criteria);

    $table->setTitle(__('Map Classes'));
    $table->setDescription(__('Syncing enrolment lets you enrol students into courses by mapping them to a Form Group and Year Group within the school. If auto-enrol is turned on, new students accepted through the application form and student enrolment process will be enrolled in courses automatically.'));

    $table->addHeaderAction('add', __('Add'))
        ->setURL(Url::fromModuleRoute('Timetable Admin', 'courseEnrolment_sync_add'))
        ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
        ->displayLabel()
        ->append('&nbsp;|&nbsp;');

    $table->addHeaderAction('sync', __('Sync All'))
        ->setURL(Url::fromModuleRoute('Timetable Admin', 'courseEnrolment_sync_run'))
        ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
        ->addParam('gibbonYearGroupIDList', $classMapsAllYearGroups)
        ->setIcon('refresh')
        ->displayLabel();

    $table->addColumn('yearGroupName', __('Year Group'))->sortable(['gibbonYearGroup.sequenceNumber']);
    $table->addColumn('formGroupList', __('Form Groups'));
    $table->addColumn('classCount', __('Classes'));

    // ACTIONS
    $table->addActionColumn()
        ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
        ->addParam('gibbonYearGroupID')
        ->format(function ($row, $actions) {
            $actions->addAction('edit', __('Edit'))
                ->setURL(Url::fromModuleRoute('Timetable Admin', 'courseEnrolment_sync_edit'));

            $actions->addAction('delete', __('Delete'))
                ->setURL(Url::fromModuleRoute('Timetable Admin', 'courseEnrolment_sync_delete'));

            $actions->addAction('sync', __('Sync Now'))
                ->setIcon('refresh')
                ->addParam('gibbonYearGroupIDList', $row['gibbonYearGroupID'])
                ->setURL(Url::fromModuleRoute('Timetable Admin', 'courseEnrolment_sync_run'));
        });

    echo $table->render($classMaps);
}
