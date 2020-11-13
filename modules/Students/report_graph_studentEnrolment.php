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

//Module includes
require_once __DIR__ . '/moduleFunctions.php';
include './modules/Attendance/moduleFunctions.php';

function getDateRange($firstDate, $lastDate, $step = '+1 day', $output_format = 'Y-m-d' ) {

    // Check if there's no range to calculate
    if ($firstDate === $lastDate) {
        return array($firstDate);
    }

    // Handle an invalid step by returning the first and last dates only
    if (stripos($step, '+') === false) {
        return array($firstDate, $lastDate);
    }

    $dates = array();
    $current = strtotime($firstDate);
    $last = strtotime($lastDate);

    while( $current <= $last ) {
        $dates[] = date($output_format, $current);
        $current = strtotime($step, $current);
    }

    if (!in_array($lastDate, $dates)) {
        $dates[] = $lastDate;
    }

    return $dates;
}

if (isActionAccessible($guid, $connection2, '/modules/Students/report_graph_studentEnrolment.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Student Enrolment Trends'));

    echo '<h2>';
    echo __('Choose Date');
    echo '</h2>';

    $dateStart = (isset($_POST['dateStart']))? dateConvert($guid, $_POST['dateStart']) : $_SESSION[$guid]['gibbonSchoolYearFirstDay'];
    $dateEnd = (isset($_POST['dateEnd']))? dateConvert($guid, $_POST['dateEnd']) : $_SESSION[$guid]['gibbonSchoolYearLastDay'];
    $interval =  (isset($_POST['interval']))? $_POST['interval'] : '+1 week';
    $excludeLeft = $_POST['excludeLeft'] ?? '';

    // Correct inverse date ranges rather than generating an error
    if ($dateStart > $dateEnd) {
        $swapDates = $dateStart;
        $dateStart = $dateEnd;
        $dateEnd = $swapDates;
    }

    // Get the roll groups - revert to All if it's selected
    $yearGroups = !empty($_POST['gibbonYearGroupID'])? $_POST['gibbonYearGroupID'] : array('all');
    if (in_array('all', $yearGroups)) $yearGroups = array('all');

    $intervals = array(
        '+1 day' => __('1 Day'),
        '+1 week' => __('1 Week'),
        '+1 month' => __('1 Month'),
        '+3 month' => __('3 Months'),
        '+6 month' => __('6 Months'),
        '+1 year' => __('Year')
    );

    $form = Form::create('attendanceTrends', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/report_graph_studentEnrolment.php');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $row = $form->addRow();
        $row->addLabel('dateStart', __('Start Date'))->description($_SESSION[$guid]['i18n']['dateFormat'])->prepend(__('Format:'));
        $row->addDate('dateStart')->setValue(dateConvertBack($guid, $dateStart))->required();

    $row = $form->addRow();
        $row->addLabel('dateEnd', __('End Date'))->description($_SESSION[$guid]['i18n']['dateFormat'])->prepend(__('Format:'));
        $row->addDate('dateEnd')->setValue(dateConvertBack($guid, $dateEnd))->required();

    $row = $form->addRow();
        $row->addLabel('interval', __('Interval'));
        $row->addSelect('interval')->fromArray($intervals)->selected($interval);

    $sql = "SELECT gibbonYearGroup.name as value, name FROM gibbonYearGroup ORDER BY sequenceNumber";

    $row = $form->addRow();
        $row->addLabel('gibbonYearGroupID', __('Year Group'));
        $row->addSelect('gibbonYearGroupID')->fromArray(array('all' => __('All')))->fromQuery($pdo, $sql)->selectMultiple()->selected($yearGroups);

    $row = $form->addRow();
        $row->addLabel('excludeLeft', __('Exclude Left Students'));
        $row->addCheckbox('excludeLeft')->setValue('Y')->checked($excludeLeft);
                
    $form->addRow()->addSubmit();
    echo $form->getOutput();

    if (!empty($dateStart) && !empty($dateEnd)) {

        $dateRange = getDateRange($dateStart, $dateEnd, $interval);

        if (count($dateRange) > 100) {
            echo "<div class='error'>";
                echo __('Too many data points. Choose a longer interval of time.');
            echo '</div>';
            return;
        }

        echo '<h2>';
        echo __('Report Data');
        echo '</h2>';

        $enrolment = array();
        $groupBy = ($yearGroups != array('all'))? 'gibbonYearGroup.name' : "'all'";

        foreach ($dateRange as $date) {

            $data = array('date' => $date);
            $sql = "SELECT {$groupBy} as groupBy, COUNT(DISTINCT gibbonPerson.gibbonPersonID) as count, :date as date
                    FROM gibbonSchoolYear
                    LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
                    LEFT JOIN gibbonYearGroup ON (gibbonYearGroup.gibbonYearGroupID=gibbonStudentEnrolment.gibbonYearGroupID)
                    LEFT JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                    WHERE (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd >= :date)
                    AND (:date BETWEEN gibbonSchoolYear.firstDay AND gibbonSchoolYear.lastDay)
                ";

            if ($excludeLeft == 'Y') {
                $sql .= " AND (gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart <= :date)
                          AND(gibbonPerson.status='Full' OR gibbonPerson.status='Expected') ";
            }

            if ($yearGroups != array('all')) {
                $data['yearGroups'] = implode(',', $yearGroups);
                $sql .= " AND FIND_IN_SET(gibbonYearGroup.name, :yearGroups) GROUP BY date, gibbonYearGroup.gibbonYearGroupID";
            } else {
                $sql .= " GROUP BY date";
            }
            $result = $pdo->executeQuery($data, $sql);

            // Skip results that fall in between school years
            if ($result->rowCount() == 0) continue;

            $counts = $result->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_UNIQUE);

            foreach ($yearGroups as $group) {
                $enrolment[$group][$date] = (isset($counts[$group]['count']))? $counts[$group]['count'] : 0;
            }
        }

        if (empty($enrolment)) {
            echo "<div class='error'>";
            echo __('There are no records to display.');
            echo '</div>';
        } else {
            //PLOT DATA
            $page->scripts->add('chart');

            $labels = array_map(function ($date) {
                return date('M j, Y', strtotime($date));
            }, array_keys(current($enrolment)));

            $options = [
                'fill'         => false,
                'showTooltips' => true,
                'tooltips'     => ['mode' => 'single'],
                'hover'        => ['mode' => 'dataset'],
                'scales'       => [
                    'xAxes' => [[
                        'ticks' => [
                            'autoSkip'    => true,
                            'maxRotation' => 0,
                            'padding'     => 30,
                        ]
                    ]],
                    'yAxes' => [[
                        'ticks' => [
                            'beginAtZero'  => false,
                        ]
                    ]],
                ],
            ];

            $chart = Chart::create('studentEnrolment', 'line')
                ->setLabels($labels)
                ->setOptions($options)
                ->setLegend(true);

            foreach ($enrolment as $groupBy => $dates) {
                $chart->addDataset($groupBy)
                    ->setLabel($groupBy)
                    ->setProperties([
                        'fill'        => false,
                        'borderWidth' => 1,
                    ])
                    ->setData($dates);
            }

            echo $chart->render();
        }
    }
}
