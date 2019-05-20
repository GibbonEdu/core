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
use Gibbon\UI\Chart\Chart;
use Gibbon\Services\Format;
use Gibbon\Module\Attendance\AttendanceView;
use Gibbon\Domain\Attendance\AttendanceLogPersonGateway;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Attendance/report_graph_byType.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('Attendance Trends'));
    $page->scripts->add('chart');

    $dateEnd = (isset($_POST['dateEnd']))? dateConvert($guid, $_POST['dateEnd']) : date('Y-m-d');
    $dateStart = (isset($_POST['dateStart']))? dateConvert($guid, $_POST['dateStart']) : date('Y-m-d', strtotime( $dateEnd.' -1 month') );

    // Correct inverse date ranges rather than generating an error
    if ($dateStart > $dateEnd) {
        $swapDates = $dateStart;
        $dateStart = $dateEnd;
        $dateEnd = $swapDates;
    }

    // Limit date range to the current school year
    if ($dateStart < $_SESSION[$guid]['gibbonSchoolYearFirstDay']) {
        $dateStart = $_SESSION[$guid]['gibbonSchoolYearFirstDay'];
    }

    if ($dateEnd > $_SESSION[$guid]['gibbonSchoolYearLastDay']) {
        $dateEnd = $_SESSION[$guid]['gibbonSchoolYearLastDay'];
    }

    $sort = !empty($_POST['sort'])? $_POST['sort'] : 'surname, preferredName';

    // Get the roll groups - revert to All if it's selected
    $rollGroups = $_POST['gibbonRollGroupID'] ?? array('all');
    if (in_array('all', $rollGroups)) $rollGroups = array('all');

    require_once __DIR__ . '/src/AttendanceView.php';
    $attendance = new AttendanceView($gibbon, $pdo);

    if (isset($_POST['types']) && isset($_POST['dateStart'])) {
        $types = $_POST['types'];
    } else {
        if (!isset($_POST['dateStart'])) {
            $types = array_keys($attendance->getAttendanceTypes());
            unset($types[0]);
        } else {
            $types = array();
        }
    }

    $reasons = (isset($_POST['reasons']))? $_POST['reasons'] : array();

    // Options & Filters
    $form = Form::create('attendanceTrends', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/report_graph_byType.php');
    $form->setTitle(__('Choose Date'));
    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $row = $form->addRow();
        $row->addLabel('dateStart', __('Start Date'))->description($_SESSION[$guid]['i18n']['dateFormat'])->prepend(__('Format:'));
        $row->addDate('dateStart')->setValue(dateConvertBack($guid, $dateStart))->required();

    $row = $form->addRow();
        $row->addLabel('dateEnd', __('End Date'))->description($_SESSION[$guid]['i18n']['dateFormat'])->prepend(__('Format:'));
        $row->addDate('dateEnd')->setValue(dateConvertBack($guid, $dateEnd))->required();

    $typeOptions = array_column($attendance->getAttendanceTypes(), 'name');
    $typeOptions = array_map('__', $typeOptions);

    $row = $form->addRow();
        $row->addLabel('types', __('Types'));
        $row->addSelect('types')->fromArray($typeOptions)->selectMultiple()->selected($types);

    $reasonOptions = $attendance->getAttendanceReasons();
    $reasonOptions = array_map('__', $reasonOptions);

    $row = $form->addRow();
        $row->addLabel('reasons', __('Reasons'));
        $row->addSelect('reasons')->fromArray($reasonOptions)->selectMultiple()->selected($reasons);

    $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
    $sql = "SELECT gibbonRollGroupID as value, name FROM gibbonRollGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY LENGTH(name), name";
    $row = $form->addRow();
        $row->addLabel('gibbonRollGroupID', __('Roll Group'));
        $row->addSelect('gibbonRollGroupID')->fromArray(array('all' => __('All')))->fromQuery($pdo, $sql, $data)->selectMultiple()->selected($rollGroups);

    $form->addRow()->addSubmit();

    echo $form->getOutput();


    if ($dateStart != '') {
        echo '<h2>';
        echo __('Report Data');
        echo '</h2>';

        echo '<p><span class="small emphasis">'.__('Click a legend item to toggle visibility.').'</span></p>';

        //Produce array of attendance data
        $attendanceLogGateway = $container->get(AttendanceLogPersonGateway::class);
        $rows = $attendanceLogGateway->queryAttendanceCountsByType(
            $attendanceLogGateway->newQueryCriteria()->pageSize(0),
            $_SESSION[$guid]['gibbonSchoolYearID'],
            $rollGroups,
            $dateStart,
            $dateEnd
        );

        if (empty($rows)) {
            echo "<div class='error'>";
            echo __('There are no records to display.');
            echo '</div>';
        } else {
            $data = [];
            $days = [];

            // Get the date range and filter school days
            $dateRange = new DatePeriod(
                new DateTime($dateStart),
                new DateInterval('P1D'),
                (new DateTime($dateEnd))->modify('+1 day')
            );
            foreach ($dateRange as $dateObject) {
                $date = $dateObject->format('Y-m-d');
                if (isSchoolOpen($guid, $date, $connection2)) {
                    $days[] = $date;
                }
            }

            // Fill each date with zeroes for each type & reason
            foreach ($days as $date) {
                foreach ($types as $type) {
                    $data[$type][$date] = 0;
                }
                foreach ($reasons as $reason) {
                    if ($reason == '') continue;
                    $data[$reason][$date] = 0;
                }
            }

            // Sum the counts for each type and reason
            foreach ($rows as $row) {
                if (isset($data[$row['name']][$row['date']])) {
                    $data[$row['name']][$row['date']] += $row['count'];
                }
                if (isset($data[$row['reason']][$row['date']])) {
                    $data[$row['reason']][$row['date']] += $row['count'];
                }
            }

            $chart = Chart::create('attendance', 'line')
                ->setOptions([
                    'fill' => false,
                    'showTooltips' => true,
                    'tooltips' => [
                        'mode' => 'single',
                    ],
                    'hover' => [
                        'mode' => 'dataset',
                    ],
                    'scales' => [
                        'xAxes' => [[
                            'ticks' => [
                                'autoSkip'    => true,
                                'maxRotation' => 0,
                                'padding'     => 30,
                            ]
                        ]],
                    ],
                ])
                ->setLabels(array_map(function ($date) {
                    return Format::dateReadable($date, '%b %d');
                }, $days));

            foreach ($data as $typeName => $dates) {
                $chart->addDataset($typeName)
                    ->setLabel(__($typeName))
                    ->setProperties(['fill' => false, 'borderWidth' => 1])
                    ->setData($dates);
            }

            echo $chart->render();
        }
    }
}
