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
use Gibbon\Tables\Prefab\ReportTable;
use Gibbon\Domain\Activities\ActivityReportGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Activities/report_attendance_byDate.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $today = date('Y-m-d');
    $date = (isset($_GET['date']))? dateConvert($guid, $_GET['date']) : date('Y-m-d');
    $sort = (isset($_GET['sort']))? $_GET['sort'] : 'surname';
    $viewMode = isset($_REQUEST['format']) ? $_REQUEST['format'] : '';

    if (empty($viewMode)) {
        $page->breadcrumbs->add(__('Activity Attendance by Date'));

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, null);
        }

        // Options & Filters
        $form = Form::create('filter', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');

        $form->setTitle(__('Choose Date'));
        $form->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/report_attendance_byDate.php');
        $form->addHiddenValue('address', $_SESSION[$guid]['address']);

        $row = $form->addRow();
            $row->addLabel('date', __('Date'))->description($_SESSION[$guid]['i18n']['dateFormat'])->prepend(__('Format:'));
            $row->addDate('date')->setValue(dateConvertBack($guid, $date))->required();

        $sortOptions = array('absent' => __('Absent'), 'surname' => __('Surname'), 'preferredName' => __('Given Name'), 'rollGroup' => __('Roll Group'));
        $row = $form->addRow();
            $row->addLabel('sort', __('Sort By'));
            $row->addSelect('sort')->fromArray($sortOptions)->selected($sort);

        $row = $form->addRow();
            $row->addFooter();
            $row->addSearchSubmit($gibbon->session);

        echo $form->getOutput();
    }

    // Cancel out early if we have no date
    if (empty($date)) return;

    if ($date > $today) {
        echo "<div class='error'>" ;
        echo __('The specified date is in the future: it must be today or earlier.');
        echo "</div>" ;
        return;
    } else if (isSchoolOpen($guid, $date, $connection2)==FALSE) {
        echo "<div class='error'>" ;
        echo __('School is closed on the specified date, and so attendance information cannot be recorded.') ;
        echo "</div>" ;
        return;
    }

    //Turn $date into UNIX timestamp and extract day of week
    $dayOfWeek = date('l', dateConvertToTimestamp($date));
    $dateType = getSettingByScope($connection2, 'Activities', 'dateType');

    $activityGateway = $container->get(ActivityReportGateway::class);

    switch ($sort) {
        case 'surname':         $defaultSort = ['gibbonPerson.surname', 'gibbonPerson.preferredName']; break;
        case 'preferredName':   $defaultSort = ['gibbonPerson.preferredName', 'gibbonPerson.surname']; break;
        case 'rollGroup':       $defaultSort = ['rollGroup', 'gibbonPerson.surname', 'gibbonPerson.preferredName']; break;
        case 'absent':
        default:                $defaultSort = ['attendance', 'gibbonPerson.surname', 'gibbonPerson.preferredName']; break;
    }

    // CRITERIA
    $criteria = $activityGateway->newQueryCriteria(true)
        ->searchBy($activityGateway->getSearchableColumns(), isset($_GET['search'])? $_GET['search'] : '')
        ->sortBy($defaultSort)
        ->pageSize(!empty($viewMode) ? 0 : 50)
        ->fromPOST();

    $activityAttendance = $activityGateway->queryActivityAttendanceByDate($criteria, $_SESSION[$guid]['gibbonSchoolYearID'], $dateType, $date);

    // DATA TABLE
    $table = ReportTable::createPaginated('attendance_byDate', $criteria)->setViewMode($viewMode, $gibbon->session);

    $table->setTitle(__('Activity Attendance by Date'));

    $table->modifyRows(function($student, $row) {
        if ($student['attendance'] == 'Absent') $row->addClass('error');
        return $row;
    });

    $table->addMetaData('post', ['date' => $date]);

    $table->addColumn('rollGroup', __('Roll Group'))->width('10%');
    $table->addColumn('student', __('Student'))
        ->sortable(['gibbonPerson.surname', 'gibbonPerson.preferredName'])
        ->format(Format::using('name', ['', 'preferredName', 'surname', 'Student', true]));
    $table->addColumn('attendance', __('Attendance'));
    $table->addColumn('activity', __('Activity'));
    $table->addColumn('provider', __('Provider'))
        ->format(function($activity) use ($guid){
            return ($activity['provider'] == 'School')? $_SESSION[$guid]['organisationNameShort'] : __('External');
        });

    echo $table->render($activityAttendance);
}
