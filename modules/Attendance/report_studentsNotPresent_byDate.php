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
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Tables\Prefab\ReportTable;
use Gibbon\Module\Attendance\AttendanceView;
use Gibbon\Domain\Attendance\AttendanceLogPersonGateway;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Attendance/report_studentsNotPresent_byDate.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $viewMode = $_REQUEST['format'] ?? '';
    $allStudents = $_GET['allStudents'] ?? 'N';
    $sort = !empty($_GET['sort']) ? $_GET['sort'] : 'surname';
    $gibbonYearGroupIDList = $_GET['gibbonYearGroupIDList'] ?? [];
    $currentDate = isset($_GET['currentDate']) ? Format::dateConvert($_GET['currentDate']) : date('Y-m-d');
    $countClassAsSchool = getSettingByScope($connection2, 'Attendance', 'countClassAsSchool');

    require_once __DIR__ . '/src/AttendanceView.php';
    $attendance = new AttendanceView($gibbon, $pdo);

    if (empty($viewMode)) {
        $page->breadcrumbs->add(__('Students Not Present'));

        $form = Form::create('action', $_SESSION[$guid]['absoluteURL'] . '/index.php', 'get');

        $form->setFactory(DatabaseFormFactory::create($pdo));
        $form->setTitle(__('Choose Date'));
        $form->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('q', "/modules/" . $_SESSION[$guid]['module'] . "/report_studentsNotPresent_byDate.php");

        $row = $form->addRow();
            $row->addLabel('currentDate', __('Date'));
            $row->addDate('currentDate')->setValue(Format::date($currentDate))->required();

        $sortOptions = ['surname' => __('Surname'), 'preferredName' => __('Preferred Name'), 'rollGroup' => __('Roll Group')];
        $row = $form->addRow();
            $row->addLabel('sort', __('Sort By'));
            $row->addSelect('sort')->fromArray($sortOptions)->selected($sort)->required();

        $row = $form->addRow();
            $row->addLabel('allStudents', __('All Students'))->description(__('Include all students, even those where attendance has not yet been recorded.'));
            $row->addCheckbox('allStudents')->checked($allStudents)->setValue('Y');

        $row = $form->addRow();
        $row->addLabel('gibbonYearGroupIDList', __('Year Groups'))->description(__('Relevant student year groups'));
        if (!empty($gibbonYearGroupIDList)) {
            $values['gibbonYearGroupIDList'] = $gibbonYearGroupIDList;
            $row->addCheckboxYearGroup('gibbonYearGroupIDList')->addCheckAllNone()->loadFrom($values);
        } else {
            $row->addCheckboxYearGroup('gibbonYearGroupIDList')->addCheckAllNone()->checkAll();
        }

        $row = $form->addRow();
            $row->addFooter();
            $row->addSearchSubmit($gibbon->session);

        echo $form->getOutput();
    }
    
    if (empty($currentDate)) {
        return;
    }

    $attendanceGateway = $container->get(AttendanceLogPersonGateway::class);
    $criteria = $attendanceGateway->newQueryCriteria()
        ->filterBy('yearGroup', implode(',', $gibbonYearGroupIDList));

    switch ($sort) {
        case 'preferredName':
            $criteria->sortBy(['gibbonPerson.preferredName', 'gibbonPerson.surname', 'gibbonRollGroup.nameShort']); break;
        case 'rollGroup':
            $criteria->sortBy(['gibbonRollGroup.nameShort', 'gibbonPerson.surname', 'gibbonPerson.preferredName']); break;
        default:
        case 'surname':
            $criteria->sortBy(['gibbonPerson.surname', 'gibbonPerson.preferredName', 'gibbonRollGroup.nameShort']); break;
    }
    $criteria->fromPOST();

    $attendance = $attendanceGateway->queryStudentsNotPresent($criteria, $gibbon->session->get('gibbonSchoolYearID'), $currentDate, $allStudents, $countClassAsSchool);

    $table = ReportTable::createPaginated('attendanceReport', $criteria)->setViewMode($viewMode, $gibbon->session);
    $table->setTitle(__('Report Data'));

    $table->addMetaData('blankSlate', __('All students are present.'));
    $table->addRowCountColumn($attendance->getPageFrom());

    $table->addColumn('rollGroup', __('Roll Group'))->width('10%');
    $table->addColumn('name', __('Name'))
        ->sortable(['gibbonPerson.surname', 'gibbonPerson.preferredName'])
        ->format(function ($student) {
            return Format::nameLinked($student['gibbonPersonID'], '', $student['preferredName'], $student['surname'], 'Student', true, true, ['subpage' => 'Attendance']);
        });
    $table->addColumn('status', __('Status'))
        ->format(function ($student) {
            return !empty($student['type']) ? __($student['type']) : Format::small(__('Not registered'));
        });
    $table->addColumn('reason', __('Reason'));
    $table->addColumn('comment', __('Comment'))
        ->format(Format::using('truncate', 'comment'));

    echo $table->render($attendance);
}
