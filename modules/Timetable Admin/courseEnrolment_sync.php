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
use Gibbon\Tables\DataTable;
use Gibbon\Domain\Timetable\CourseSyncGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_sync.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $page->breadcrumbs->add(__('Sync Course Enrolment'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $gibbonSchoolYearID = isset($_GET['gibbonSchoolYearID'])? $_GET['gibbonSchoolYearID'] : $_SESSION[$guid]['gibbonSchoolYearID'];

    if ($gibbonSchoolYearID == $_SESSION[$guid]['gibbonSchoolYearID']) {
        $gibbonSchoolYearName = $_SESSION[$guid]['gibbonSchoolYearName'];
    } else {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT name FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID";
        $gibbonSchoolYearName = $pdo->selectOne($sql, $data);
    }

    echo '<h2>';
    echo $gibbonSchoolYearName;
    echo '</h2>';

    echo "<div class='linkTop'>";
        //Print year picker
        $previousYear = getPreviousSchoolYearID($gibbonSchoolYearID, $connection2);
        $nextYear = getNextSchoolYearID($gibbonSchoolYearID, $connection2);
        if ($previousYear != false) {
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/courseEnrolment_sync.php&gibbonSchoolYearID='.getPreviousSchoolYearID($gibbonSchoolYearID, $connection2)."'>".__('Previous Year').'</a> ';
        } else {
            echo __('Previous Year').' ';
        }
        echo ' | ';
        if ($nextYear != false) {
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/courseEnrolment_sync.php&gibbonSchoolYearID='.getNextSchoolYearID($gibbonSchoolYearID, $connection2)."'>".__('Next Year').'</a> ';
        } else {
            echo __('Next Year').' ';
        }
    echo '</div>';

    $form = Form::create('settings', $_SESSION[$guid]['absoluteURL'].'/modules/Timetable Admin/courseEnrolment_sync_settingsProcess.php');
    $form->setTitle(__('Settings'));
    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $setting = getSettingByScope($connection2, 'Timetable Admin', 'autoEnrolCourses', true);
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
    $table->setDescription(__('Syncing enrolment lets you enrol students into courses by mapping them to a Roll Group and Year Group within the school. If auto-enrol is turned on, new students accepted through the application form and student enrolment process will be enrolled in courses automatically.'));

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/Timetable Admin/courseEnrolment_sync_add.php')
        ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
        ->displayLabel()
        ->append('&nbsp;|&nbsp;');

    $table->addHeaderAction('sync', __('Sync All'))
        ->setURL('/modules/Timetable Admin/courseEnrolment_sync_run.php')
        ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
        ->addParam('gibbonYearGroupIDList', $classMapsAllYearGroups)
        ->setIcon('refresh')
        ->displayLabel();

    $table->addColumn('yearGroupName', __('Year Group'))->sortable(['gibbonYearGroup.sequenceNumber']);
    $table->addColumn('rollGroupList', __('Roll Groups'));
    $table->addColumn('classCount', __('Classes'));

    // ACTIONS
    $table->addActionColumn()
        ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
        ->addParam('gibbonYearGroupID')
        ->format(function ($row, $actions) {
            $actions->addAction('edit', __('Edit'))
                ->setURL('/modules/Timetable Admin/courseEnrolment_sync_edit.php');

            $actions->addAction('delete', __('Delete'))
                ->setURL('/modules/Timetable Admin/courseEnrolment_sync_delete.php');

            $actions->addAction('sync', __('Sync Now'))
                ->setIcon('refresh')
                ->addParam('gibbonYearGroupIDList', $row['gibbonYearGroupID'])
                ->setURL('/modules/Timetable Admin/courseEnrolment_sync_run.php');
        });

    echo $table->render($classMaps);
}
