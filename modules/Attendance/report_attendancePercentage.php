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

use Gibbon\Forms\Form;
use Gibbon\Http\Url;
use Gibbon\Domain\DataSet;
use Gibbon\Services\Format;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Tables\Prefab\ReportTable;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Attendance\AttendanceLogPersonGateway;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Attendance/report_attendancePercentage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $settingGateway = $container->get(SettingGateway::class);
    $sessions = getFormGroupAttendanceSessions($settingGateway);

    $viewMode = $_REQUEST['format'] ?? '';
    $today = date('Y-m-d');
    $dateStart = !empty($_GET['dateStart']) ? Format::dateConvert($_GET['dateStart']) : $session->get('gibbonSchoolYearFirstDay');
    $dateEnd = !empty($_GET['dateEnd']) ? Format::dateConvert($_GET['dateEnd']) : min($today, $session->get('gibbonSchoolYearLastDay'));
    $below = isset($_GET['below']) && is_numeric($_GET['below']) ? floatval($_GET['below']) : '';
    $gibbonYearGroupIDList = $_GET['gibbonYearGroupIDList'] ?? [];

    if (empty($viewMode)) {
        $page->breadcrumbs->add(__('Attendance Percentage by Student'));

        $form = Form::create('action', $session->get('absoluteURL').'/index.php', 'get');
        $form->setFactory(DatabaseFormFactory::create($pdo));
        $form->setTitle(__('Choose Dates'));
        $form->setDescription(!empty($sessions)
            ? __('Attendance is counted by registration session ({sessions}). Late counts as attended.', ['sessions' => implode(', ', $sessions)])
            : __('Attendance is counted by day. Late counts as attended.'));
        $form->setClass('noIntBorder w-full');

        $form->addHiddenValue('q', '/modules/'.$session->get('module').'/report_attendancePercentage.php');

        $row = $form->addRow();
            $row->addLabel('dateStart', __('Start Date'));
            $row->addDate('dateStart')->setValue(Format::date($dateStart))->required();

        $row = $form->addRow();
            $row->addLabel('dateEnd', __('End Date'));
            $row->addDate('dateEnd')->setValue(Format::date($dateEnd))->required();

        $row = $form->addRow();
            $row->addLabel('below', __('Below'))->description(__('Only show students whose attendance is below this percentage.'));
            $row->addNumber('below')->minimum(0)->maximum(100)->decimalPlaces(1)->setValue($below);

        $row = $form->addRow();
            $row->addLabel('gibbonYearGroupIDList', __('Year Groups'))->description(__('Relevant student year groups'));
            if (!empty($gibbonYearGroupIDList)) {
                $values = ['gibbonYearGroupIDList' => $gibbonYearGroupIDList];
                $row->addCheckboxYearGroup('gibbonYearGroupIDList')->addCheckAllNone()->loadFrom($values);
            } else {
                $row->addCheckboxYearGroup('gibbonYearGroupIDList')->addCheckAllNone()->checkAll();
            }

        $row = $form->addRow();
            $row->addFooter();
            $row->addSearchSubmit($session);

        echo $form->getOutput();
    }

    if (empty($_GET['dateStart'])) {
        return;
    }

    if ($dateStart > $dateEnd) {
        echo Format::alert(__('The start date must be before the end date.'), 'error');
        return;
    }

    // Students enrolled in the selected year groups
    $data = ['gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonYearGroupIDList' => implode(',', (array) $gibbonYearGroupIDList)];
    $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonPerson.preferredName, gibbonPerson.surname, gibbonFormGroup.nameShort AS formGroup
            FROM gibbonStudentEnrolment
            JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
            JOIN gibbonFormGroup ON (gibbonFormGroup.gibbonFormGroupID=gibbonStudentEnrolment.gibbonFormGroupID)
            WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
            AND gibbonPerson.status='Full'
            AND FIND_IN_SET(gibbonStudentEnrolment.gibbonYearGroupID, :gibbonYearGroupIDList)";
    $students = $pdo->select($sql, $data)->fetchAll();

    $counts = !empty($students)
        ? getAttendanceCounts($pdo, $settingGateway, array_column($students, 'gibbonPersonID'), $dateStart, $dateEnd)
        : [];

    $rows = [];
    foreach ($students as $student) {
        $student += $counts[$student['gibbonPersonID']] ?? [];
        if ($below !== '' && ($student['percentage'] === null || $student['percentage'] >= $below)) continue;
        $rows[] = $student;
    }

    // Lowest attendance first, then students with no data, each by form group and name
    usort($rows, function ($a, $b) {
        return [$a['percentage'] ?? 101, $a['formGroup'], $a['surname'], $a['preferredName']]
            <=> [$b['percentage'] ?? 101, $b['formGroup'], $b['surname'], $b['preferredName']];
    });

    $criteria = $container->get(AttendanceLogPersonGateway::class)->newQueryCriteria()->pageSize(0);

    $table = ReportTable::createPaginated('attendancePercentage', $criteria)->setViewMode($viewMode, $session);
    $table->setTitle(__('Report Data'));
    $table->setDescription(Format::dateRangeReadable($dateStart, $dateEnd));
    $table->addMetaData('blankSlate', __('There are no records to display.'));

    $table->addRowCountColumn();
    $table->addColumn('formGroup', __('Form Group'))->context('primary')->width('10%');
    $table->addColumn('name', __('Name'))
        ->context('primary')
        ->format(function ($student) use ($viewMode) {
            $name = Format::name('', $student['preferredName'], $student['surname'], 'Student', true, true);
            if (!empty($viewMode)) return $name;
            $url = Url::fromModuleRoute('Students', 'student_view_details.php')->withQueryParams(['gibbonPersonID' => $student['gibbonPersonID'], 'subpage' => 'Attendance']);
            return Format::link($url, $name);
        });
    $table->addColumn('total', !empty($sessions) ? __('Sessions') : __('Days'))
        ->description(__('Recorded'));
    $table->addColumn('attended', __('Attended'));
    $table->addColumn('absent', __('Absent'));
    $table->addColumn('late', __('Late'));
    $table->addColumn('minutesLate', __('Minutes Late'));
    $table->addColumn('percentage', __('Attendance'))
        ->context('primary')
        ->format(function ($student) {
            return $student['percentage'] === null ? Format::small(__('No data')) : Format::bold($student['percentage'].'%');
        });

    echo $table->render(new DataSet($rows));
}
