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

    $settingGateway = $container->get(SettingGateway::class);

    $dateEnd = (isset($_POST['dateEnd']))? Format::dateConvert($_POST['dateEnd']) : date('Y-m-d');
    $dateStart = (isset($_POST['dateStart']))? Format::dateConvert($_POST['dateStart']) : date('Y-m-d', strtotime( $dateEnd.' -1 month') );
    $countClassAsSchool = $settingGateway->getSettingByScope('Attendance', 'countClassAsSchool');

    // Correct inverse date ranges rather than generating an error
    if ($dateStart > $dateEnd) {
        $swapDates = $dateStart;
        $dateStart = $dateEnd;
        $dateEnd = $swapDates;
    }

    // Limit date range to the current school year
    if ($dateStart < $session->get('gibbonSchoolYearFirstDay')) {
        $dateStart = $session->get('gibbonSchoolYearFirstDay');
    }

    if ($dateEnd > $session->get('gibbonSchoolYearLastDay')) {
        $dateEnd = $session->get('gibbonSchoolYearLastDay');
    }

    $sort = !empty($_POST['sort'])? $_POST['sort'] : 'surname, preferredName';

    // Get the form groups - revert to All if it's selected
    $formGroups = $_POST['gibbonFormGroupID'] ?? array('all');
    if (in_array('all', $formGroups)) $formGroups = array('all');

    require_once __DIR__ . '/src/AttendanceView.php';
    $attendance = new AttendanceView($gibbon, $pdo, $settingGateway);

    if (isset($_POST['types']) && isset($_POST['dateStart'])) {
        $types = $_POST['types'] ?? [];
    } else {
        if (!isset($_POST['dateStart'])) {
            $types = $attendance->getAttendanceTypes();
            unset($types[0]);
        } else {
            $types = array();
        }
    }

    $reasons = (isset($_POST['reasons']))? $_POST['reasons'] : array();

    // Options & Filters
    $form = Form::create('attendanceTrends', $session->get('absoluteURL').'/index.php?q=/modules/'.$session->get('module').'/report_graph_byType.php');
    $form->setTitle(__('Choose Date'));
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('address', $session->get('address'));

    $row = $form->addRow();
        $row->addLabel('dateStart', __('Start Date'));
        $row->addDate('dateStart')->setValue(Format::date($dateStart))->required();

    $row = $form->addRow();
        $row->addLabel('dateEnd', __('End Date'));
        $row->addDate('dateEnd')->setValue(Format::date($dateEnd))->required();

    $row = $form->addRow();
        $row->addLabel('types', __('Types'));
        $row->addSelect('types')->fromArray($attendance->getAttendanceTypes())->selectMultiple()->selected($types);

    $reasonOptions = $attendance->getAttendanceReasons();
    $reasonOptions = array_map('__', $reasonOptions);

    $row = $form->addRow();
        $row->addLabel('reasons', __('Reasons'));
        $row->addSelect('reasons')->fromArray($reasonOptions)->selectMultiple()->selected($reasons);

    $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
    $sql = "SELECT gibbonFormGroupID as value, name FROM gibbonFormGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY LENGTH(name), name";
    $row = $form->addRow();
        $row->addLabel('gibbonFormGroupID', __('Form Group'));
        $row->addSelect('gibbonFormGroupID')->fromArray(array('all' => __('All')))->fromQuery($pdo, $sql, $data)->selectMultiple()->selected($formGroups);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSearchSubmit($session);

    echo $form->getOutput();


    if ($dateStart != '') {
        echo '<h2>';
        echo __('Report Data');
        echo '</h2>';

        echo '<p><span class="small emphasis">'.__('Click a legend item to toggle visibility.').'</span></p>';

        //Produce array of attendance data
        $attendanceLogGateway = $container->get(AttendanceLogPersonGateway::class);
        $rows = $attendanceLogGateway->queryAttendanceCountsByType(
            $attendanceLogGateway->newQueryCriteria(),
            $session->get('gibbonSchoolYearID'),
            $formGroups,
            $dateStart,
            $dateEnd,
            $countClassAsSchool
        );

        if (empty($rows)) {
            echo $page->getBlankSlate();
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
                    'tooltip' => [
                        'mode' => 'single',
                    ],
                    'hover' => [
                        'mode' => 'dataset',
                    ],
                    'scales' => [
                        'x' => [
                            'min' => 0,
                            'ticks' => [
                                'autoSkip'    => true,
                                'maxRotation' => 0,
                                'padding'     => 30,
                            ]
                        ],
                        'y' => [
                            'min' => 0,
                        ],
                    ],
                ])
                ->setLabels(array_map(function ($date) {
                    return Format::dateReadable($date, Format::MEDIUM_NO_YEAR);
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
