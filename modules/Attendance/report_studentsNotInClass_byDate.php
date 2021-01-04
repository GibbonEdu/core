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
use Gibbon\Services\Format;
use Gibbon\Tables\Prefab\ReportTable;
use Gibbon\Domain\Attendance\AttendanceLogPersonGateway;
use Gibbon\Domain\Attendance\AttendanceCodeGateway;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Attendance/report_studentsNotInClass_byDate.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $viewMode = $_REQUEST['format'] ?? '';
    $allStudents = $_GET['allStudents'] ?? '';
    $currentDate = isset($_GET['currentDate'])
            ? Format::dateConvert($_GET['currentDate'])
            : date('Y-m-d');

    $types = $_GET['types'] ?? [];
    $gibbonYearGroupIDList = (!empty($_GET['gibbonYearGroupIDList']) && is_array($_GET['gibbonYearGroupIDList'])) ? $_GET['gibbonYearGroupIDList'] : null;

    if (empty($viewMode)) {
        $page->breadcrumbs->add(__('Students Not In Class'));

        $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
        $form->setTitle(__('Choose Date'));

        $form->setFactory(DatabaseFormFactory::create($pdo));
        $form->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('q', "/modules/".$_SESSION[$guid]['module']."/report_studentsNotInClass_byDate.php");

        $row = $form->addRow();
            $row->addLabel('currentDate', __('Date'))->description($_SESSION[$guid]['i18n']['dateFormat'])->prepend(__('Format:'));
            $row->addDate('currentDate')->setValue(Format::date($currentDate))->required();

        $typeOptions = $container->get(AttendanceCodeGateway::class)->selectAttendanceCodes()->fetchKeyPair();
        $typeOptions = array_slice($typeOptions, 1);
        $typeOptions = array_map('__', $typeOptions);
        if (empty($types)) $types = array_keys($typeOptions);

        $row = $form->addRow();
            $row->addLabel('types', __('Types'));
            $row->addSelect('types')->fromArray($typeOptions)->selectMultiple()->selected($types);

        $row = $form->addRow();
            $row->addLabel('allStudents', __('All Students'))->description(__('Include all students, even those who have already been marked absent from school. By default, this report shows only class absences for students who are present in school.'));
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

    if ($currentDate != '') {
        $attendanceLogGateway = $container->get(AttendanceLogPersonGateway::class);

        // CRITERIA
        $criteria = $attendanceLogGateway->newQueryCriteria(true)
            ->sortBy(['sequenceNumber', 'rollGroup', 'gibbonPerson.surname', 'gibbonPerson.preferredName'])
            ->filterBy('yearGroup', implode(',', $gibbonYearGroupIDList ?? []))
            ->filterBy('types', implode(',', $types ?? []))
            ->pageSize(!empty($viewMode) ? 0 : 50)
            ->fromPOST();

        $logs = $attendanceLogGateway->queryStudentsNotInClass($criteria, $gibbon->session->get('gibbonSchoolYearID'), $currentDate, $allStudents);

        // DATA TABLE
        $table = ReportTable::createPaginated('studentsNotInClass', $criteria)->setViewMode($viewMode, $gibbon->session);
        $table->setTitle(__('Students Not In Class'));

        $table->addRowCountColumn($logs->getPageFrom());

        $table->addColumn('rollGroup', __('Roll Group'));

        $table->addColumn('student', __('Student'))
            ->context('primary')
            ->sortable(['gibbonPerson.surname', 'gibbonPerson.preferredName'])
            ->format(function ($student) use ($currentDate) {
                $name = Format::name('', $student['preferredName'], $student['surname'], 'Student', true, true);
                $url = './index.php?q=/modules/Attendance/attendance_take_byPerson.php&gibbonPersonID='.$student['gibbonPersonID'].'&currentDate='.$currentDate;
                return Format::link($url, $name);
            });

        $table->addColumn('class', __('Class'))
            ->sortable(['courseName', 'className'])
            ->format(function ($log) {
                return Format::courseClassName($log['courseName'], $log['className']);
            });

        $table->addColumn('type', __('Status'));
        $table->addColumn('reason', __('Reason'))
            ->format(function ($log) {
                return Format::tooltip($log['reason'], $log['comment']);
            });

        echo $table->render($logs);
    }
}
