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
use Gibbon\Services\Format;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable/report_viewAvailableTeachers.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $page->breadcrumbs->add(__('View Available Teachers'));

    $viewBy = $_GET['viewBy'] ?? '';
    $gibbonTTID = $_GET['gibbonTTID'] ?? '';
    $ttDate = $_GET['ttDate'] ?? '';

    if (empty($ttDate)) {
        $ttDate = Format::date(date('Y-m-d'));
    }

    $form = Form::create('viewAvailableTeachers', $session->get('absoluteURL').'/index.php', 'get');
    $form->setTitle(__('Choose Options'));

    $form->addHiddenValue('q', '/modules/'.$session->get('module').'/report_viewAvailableTeachers.php');

    $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
    $sql = 'SELECT gibbonTTID as value, name FROM gibbonTT WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name';

    $row = $form->addRow();
        $row->addLabel('gibbonTTID', __('Timetable'));
        $select = $row->addSelect('gibbonTTID')->fromQuery($pdo, $sql, $data)->required()->placeholder()->selected($gibbonTTID);

    if ($select->getOptionCount() == 1) {
        $option = $select->getOptions();
        $select->selected(key($option));
        $gibbonTTID = key($option);
    }

    $row = $form->addRow();
        $row->addLabel('viewBy', __('View'));
        $row->addSelect('viewBy')->fromArray(array('username' => __('Username'), 'name' => __('Name') ))->selected($viewBy);

    $row = $form->addRow();
        $row->addLabel('ttDate', __('Date'));
        $row->addDate('ttDate')->setValue($ttDate);

    $row = $form->addRow();
        $row->addSubmit();

    echo $form->getOutput();


    if ($gibbonTTID != '') {
        echo '<h2>';
        echo __('Report Data');
        echo '</h2>';

        echo '<p>'.__('Click the timetable to view availability details.').'</p>';

        $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonTTID' => $gibbonTTID);
        $sql = 'SELECT * FROM gibbonTT WHERE gibbonTTID=:gibbonTTID AND gibbonSchoolYearID=:gibbonSchoolYearID';
        $result = $pdo->select($sql, $data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The selected record does not exist, or you do not have access to it.'));
        } else {
            $row = $result->fetch();
            $startDayStamp = strtotime(Format::dateConvert($ttDate));

            //Check which days are school days
            $daysInWeek = 0;
            $days = [];
            $timeStart = '';
            $timeEnd = '';
            
            $sqlDays = "SELECT * FROM gibbonDaysOfWeek WHERE schoolDay='Y' ORDER BY sequenceNumber";
            $days = $pdo->select($sqlDays)->fetchAll();
            $daysInWeek = count($days);

            foreach ($days as $day) {
                if ($timeStart == '' or $timeEnd == '') {
                    $timeStart = $day['schoolStart'];
                    $timeEnd = $day['schoolEnd'];
                } else {
                    if ($day['schoolStart'] < $timeStart) {
                        $timeStart = $day['schoolStart'];
                    }
                    if ($day['schoolEnd'] > $timeEnd) {
                        $timeEnd = $day['schoolEnd'];
                    }
                }
            }

            //Count back to first dayOfWeek before specified calendar date
            while (date('D', $startDayStamp) != $days[0]['nameShort']) {
                $startDayStamp = $startDayStamp - 86400;
            }

            //Count forward to the end of the week
            $endDayStamp = $startDayStamp + (86400 * ($daysInWeek - 1));

            $schoolCalendarAlpha = 0.85;
            $ttAlpha = 1.0;

            //Max diff time for week based on timetables
            
            $dataDiff = array('date1' => date('Y-m-d', ($startDayStamp + (86400 * 0))), 'date2' => date('Y-m-d', ($endDayStamp + (86400 * 1))), 'gibbonTTID' => $row['gibbonTTID']);
            $sqlDiff = 'SELECT DISTINCT gibbonTTColumn.gibbonTTColumnID FROM gibbonTTDay JOIN gibbonTTDayDate ON (gibbonTTDay.gibbonTTDayID=gibbonTTDayDate.gibbonTTDayID) JOIN gibbonTTColumn ON (gibbonTTDay.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) WHERE (date>=:date1 AND date<=:date2) AND gibbonTTID=:gibbonTTID';
            $resultDiff = $pdo->select($sqlDiff, $dataDiff);
            while ($rowDiff = $resultDiff->fetch()) {
                
                $dataDiffDay = array('gibbonTTColumnID' => $rowDiff['gibbonTTColumnID']);
                $sqlDiffDay = 'SELECT * FROM gibbonTTColumnRow WHERE gibbonTTColumnID=:gibbonTTColumnID ORDER BY timeStart';
                $resultDiffDay = $pdo->select($sqlDiffDay, $dataDiffDay);
                while ($rowDiffDay = $resultDiffDay->fetch()) {
                    if ($rowDiffDay['timeStart'] < $timeStart) {
                        $timeStart = $rowDiffDay['timeStart'];
                    }
                    if ($rowDiffDay['timeEnd'] > $timeEnd) {
                        $timeEnd = $rowDiffDay['timeEnd'];
                    }
                }
            }

            //Final calc
            $diffTime = strtotime($timeEnd) - strtotime($timeStart);
            $width = (ceil(690 / $daysInWeek) - 20).'px';

            $count = 0;

            echo "<table class='mini' cellspacing='0' style='width: 760px; margin: 0px 0px 30px 0px;'>";
            echo "<tr class='head'>";
            echo "<th style='vertical-align: top; width: 70px; text-align: center'>";
            //Calculate week number
            $week = getWeekNumber($startDayStamp, $connection2, $guid);
            if ($week != false) {
                echo __('Week').' '.$week.'<br/>';
            }
            echo "<span style='font-weight: normal; font-style: italic;'>".__('Time').'<span>';
            echo '</th>';
            $count = 0;
            foreach ($days as $day) {
                if ($count == 0) {
                    $firstSequence = $day['sequenceNumber'];
                }
                $dateCorrection = ($day['sequenceNumber'] - 1)-($firstSequence-1);

                echo "<th style='vertical-align: top; text-align: center; width: ".(550 / $daysInWeek)."px'>";
                echo __($day['nameShort']).'<br/>';
                echo "<span style='font-size: 80%; font-style: italic'>".date($session->get('i18n')['dateFormatPHP'], ($startDayStamp + (86400 * $dateCorrection))).'</span><br/>';
                echo '</th>';
                $count++ ;
            }
            echo '</tr>';

            echo "<tr style='height:".(ceil($diffTime / 60) + 14)."px'>";
            echo "<td style='height: 300px; width: 75px; text-align: center; vertical-align: top'>";
            echo "<div style='position: relative; width: 71px'>";
            $countTime = 0;
            $time = $timeStart;
            echo "<div style='position: absolute; top: -3px; width: 71px ; border: none; height: 60px; margin: 0px; padding: 0px; font-size: 92%'>";
            echo substr($time, 0, 5).'<br/>';
            echo '</div>';
            $time = date('H:i:s', strtotime($time) + 3600);
            $spinControl = 0;
            while ($time <= $timeEnd and $spinControl < (23 - substr($timeStart, 0, 2))) {
                ++$countTime;
                echo "<div style='position: absolute; top:".(($countTime * 60) - 5)."px ; width: 71px ; border: none; height: 60px; margin: 0px; padding: 0px; font-size: 92%'>";
                echo substr($time, 0, 5).'<br/>';
                echo '</div>';
                $time = date('H:i:s', strtotime($time) + 3600);
                ++$spinControl;
            }

            echo '</div>';
            echo '</td>';

            //Check to see if week is at all in term time...if it is, then display the grid
            $isWeekInTerm = false;
            
            $dataTerm = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
            $sqlTerm = 'SELECT gibbonSchoolYearTerm.firstDay, gibbonSchoolYearTerm.lastDay FROM gibbonSchoolYearTerm, gibbonSchoolYear WHERE gibbonSchoolYearTerm.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID AND gibbonSchoolYear.gibbonSchoolYearID=:gibbonSchoolYearID';
            $resultTerm = $pdo->select($sqlTerm, $dataTerm);

            $weekStart = date('Y-m-d', ($startDayStamp + (86400 * 0)));
            $weekEnd = date('Y-m-d', ($startDayStamp + (86400 * 6)));
            while ($rowTerm = $resultTerm->fetch()) {
                if ($weekStart <= $rowTerm['firstDay'] and $weekEnd >= $rowTerm['firstDay']) {
                    $isWeekInTerm = true;
                } elseif ($weekStart >= $rowTerm['firstDay'] and $weekEnd <= $rowTerm['lastDay']) {
                    $isWeekInTerm = true;
                } elseif ($weekStart <= $rowTerm['lastDay'] and $weekEnd >= $rowTerm['lastDay']) {
                    $isWeekInTerm = true;
                }
            }
            if ($isWeekInTerm == true) {
                $blank = false;
            }

            //Run through days of the week
            foreach ($days as $day) {
                $dayOut = '';
                $zCount = 0;

                if ($day['schoolDay'] == 'Y') {
                    $dateCorrection = ($day['sequenceNumber'] - 1)-($firstSequence-1);

                    //Check to see if day is term time
                    $isDayInTerm = false;
                    
                    $dataTerm = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
                    $sqlTerm = 'SELECT gibbonSchoolYearTerm.firstDay, gibbonSchoolYearTerm.lastDay FROM gibbonSchoolYearTerm, gibbonSchoolYear WHERE gibbonSchoolYearTerm.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID AND gibbonSchoolYear.gibbonSchoolYearID=:gibbonSchoolYearID';
                    $resultTerm = $pdo->select($sqlTerm, $dataTerm);

                    while ($rowTerm = $resultTerm->fetch()) {
                        if (date('Y-m-d', ($startDayStamp + (86400 * $dateCorrection))) >= $rowTerm['firstDay'] and date('Y-m-d', ($startDayStamp + (86400 * $dateCorrection))) <= $rowTerm['lastDay']) {
                            $isDayInTerm = true;
                        }
                    }

                    if ($isDayInTerm == true) {
                        //Check for school closure day
                        $dataClosure = array('date' => date('Y-m-d', ($startDayStamp + (86400 * $dateCorrection))));
                        $sqlClosure = "SELECT * FROM gibbonSchoolYearSpecialDay WHERE date=:date and type='School Closure'";
                        $resultClosure = $pdo->select($sqlClosure, $dataClosure);

                        if ($resultClosure->rowCount() == 1) {
                            $rowClosure = $resultClosure->fetch();
                            $dayOut .= "<td style='text-align: center; vertical-align: top; font-size: 11px'>";
                            $dayOut .= "<div style='position: relative'>";
                            $dayOut .= "<div style='z-index: $zCount; position: absolute; top: 0; width: $width ; border: 1px solid rgba(136,136,136,$ttAlpha); height: ".ceil($diffTime / 60)."px; margin: 0px; padding: 0px; background-color: rgba(255,196,202,$ttAlpha)'>";
                            $dayOut .= "<div style='position: relative; top: 50%'>";
                            $dayOut .= "<span style='color: rgba(255,0,0,$ttAlpha);'>".$rowClosure['name'].'</span>';
                            $dayOut .= '</div>';
                            $dayOut .= '</div>';
                            $dayOut .= '</div>';
                            $dayOut .= '</td>';
                        } else {
                            $schoolCalendarAlpha = 0.85;
                            $ttAlpha = 1.0;

                            $date = date('Y/m/d', ($startDayStamp + (86400 * $dateCorrection)));

                            $output = '';
                            $blank = true;
                            //Get day start and end!
                            $dayTimeStart = '';
                            $dayTimeEnd = '';
                            
                            $dataDiff = array('date' => date('Y-m-d', ($startDayStamp + (86400 * $dateCorrection))), 'gibbonTTID' => $gibbonTTID);
                            $sqlDiff = 'SELECT timeStart, timeEnd FROM gibbonTTDay JOIN gibbonTTDayDate ON (gibbonTTDay.gibbonTTDayID=gibbonTTDayDate.gibbonTTDayID) JOIN gibbonTTColumn ON (gibbonTTDay.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) JOIN gibbonTTColumnRow ON (gibbonTTColumn.gibbonTTColumnID=gibbonTTColumnRow.gibbonTTColumnID) WHERE date=:date AND gibbonTTID=:gibbonTTID';
                            $resultDiff = $pdo->select($sqlDiff, $dataDiff);

                            while ($rowDiff = $resultDiff->fetch()) {
                                if ($dayTimeStart == '') {
                                    $dayTimeStart = $rowDiff['timeStart'];
                                }
                                if ($rowDiff['timeStart'] < $dayTimeStart) {
                                    $dayTimeStart = $rowDiff['timeStart'];
                                }
                                if ($dayTimeEnd == '') {
                                    $dayTimeEnd = $rowDiff['timeEnd'];
                                }
                                if ($rowDiff['timeEnd'] > $dayTimeEnd) {
                                    $dayTimeEnd = $rowDiff['timeEnd'];
                                }
                            }

                            $dayDiffTime = strtotime($dayTimeEnd) - strtotime($dayTimeStart);

                            $startPad = strtotime($dayTimeStart) - strtotime($timeStart);

                            $dayOut .= "<td style='text-align: center; vertical-align: top; font-size: 11px'>";

                            $dataDay = array('gibbonTTID' => $gibbonTTID, 'date' => date('Y-m-d', ($startDayStamp + (86400 * $dateCorrection))));
                            $sqlDay = 'SELECT gibbonTTDay.gibbonTTDayID FROM gibbonTTDayDate JOIN gibbonTTDay ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) WHERE gibbonTTID=:gibbonTTID AND date=:date';
                            $resultDay = $pdo->select($sqlDay, $dataDay);


                            if ($resultDay->rowCount() == 1) {
                                $rowDay = $resultDay->fetch();
                                $zCount = 0;
                                $dayOut .= "<div style='position: relative;'>";

                                //Draw outline of the day

                                $dataPeriods = array('gibbonTTDayID' => $rowDay['gibbonTTDayID'], 'date' => date('Y-m-d', ($startDayStamp + (86400 * $dateCorrection))));
                                $sqlPeriods = 'SELECT gibbonTTColumnRow.gibbonTTColumnRowID, gibbonTTColumnRow.name, timeStart, timeEnd, type, date FROM gibbonTTDay JOIN gibbonTTDayDate ON (gibbonTTDay.gibbonTTDayID=gibbonTTDayDate.gibbonTTDayID) JOIN gibbonTTColumn ON (gibbonTTDay.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) JOIN gibbonTTColumnRow ON (gibbonTTColumnRow.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) WHERE gibbonTTDayDate.gibbonTTDayID=:gibbonTTDayID AND date=:date ORDER BY timeStart, timeEnd';
                                $resultPeriods = $pdo->select($sqlPeriods, $dataPeriods);

                                while ($rowPeriods = $resultPeriods->fetch()) {
                                    $isSlotInTime = false;
                                    if ($rowPeriods['timeStart'] <= $dayTimeStart and $rowPeriods['timeEnd'] > $dayTimeStart) {
                                        $isSlotInTime = true;
                                    } elseif ($rowPeriods['timeStart'] >= $dayTimeStart and $rowPeriods['timeEnd'] <= $dayTimeEnd) {
                                        $isSlotInTime = true;
                                    } elseif ($rowPeriods['timeStart'] < $dayTimeEnd and $rowPeriods['timeEnd'] >= $dayTimeEnd) {
                                        $isSlotInTime = true;
                                    }

                                    if ($isSlotInTime == true) {
                                        $effectiveStart = $rowPeriods['timeStart'];
                                        $effectiveEnd = $rowPeriods['timeEnd'];
                                        if ($dayTimeStart > $rowPeriods['timeStart']) {
                                            $effectiveStart = $dayTimeStart;
                                        }
                                        if ($dayTimeEnd < $rowPeriods['timeEnd']) {
                                            $effectiveEnd = $dayTimeEnd;
                                        }

                                        $width = (ceil(690 / $daysInWeek) - 20).'px';
                                        $height = ceil((strtotime($effectiveEnd) - strtotime($effectiveStart)) / 60).'px';
                                        $top = ceil(((strtotime($effectiveStart) - strtotime($dayTimeStart)) + $startPad) / 60).'px';
                                        $bg = "bg-gray-200";
                                        if ((date('H:i:s') > $effectiveStart) and (date('H:i:s') < $effectiveEnd) and $rowPeriods['date'] == date('Y-m-d')) {
                                            $bg = "bg-green-200";
                                        }
                                       
                                        $availability = [];
                                        $vacancies = [];
                                        if ($rowPeriods['type'] == 'Lesson') {
                                            
                                            $sqlSelect = "SELECT gibbonPerson.gibbonPersonID, initials, username, surname, preferredName FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' and type='Teaching' ORDER BY preferredName, surname, initials";
                                            $resultSelect = $pdo->select($sqlSelect);

                                            while ($rowSelect = $resultSelect->fetch()) {
                                                
                                                $dataUnique = array('gibbonTTDayID' => $rowDay['gibbonTTDayID'], 'gibbonTTColumnRowID' => $rowPeriods['gibbonTTColumnRowID'], 'gibbonPersonID' => $rowSelect['gibbonPersonID']);
                                                $sqlUnique = "SELECT * FROM gibbonTTDayRowClass JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonTTDayRowClass.gibbonCourseClassID) LEFT JOIN gibbonTTDayRowClassException ON (gibbonTTDayRowClassException.gibbonTTDayRowClassID=gibbonTTDayRowClass.gibbonTTDayRowClassID AND gibbonTTDayRowClassException.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) WHERE gibbonTTDayID=:gibbonTTDayID AND gibbonTTColumnRowID=:gibbonTTColumnRowID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Teacher' AND gibbonTTDayRowClassExceptionID IS NULL";

                                                $rowUnique = $pdo->selectOne($sqlUnique, $dataUnique);

                                                if (empty($rowUnique)) {

                                                    if ($viewBy == 'name') {
                                                        $vacancies[] = Format::name('', $rowSelect['preferredName'], $rowSelect['surname'], 'Staff');
                                                    }
                                                    else if ($viewBy == 'username') {
                                                        $vacancies[] = $rowSelect['username'];
                                                    }
                                                    else if (isset($rowSelect['initials'])) {
                                                        $vacancies[] = $rowSelect['initials'];
                                                    } else {
                                                        $vacancies[] = $rowSelect['username'];
                                                    }

                                                    $availability[] = $rowSelect['gibbonPersonID'];
                                                }
                                            }

                                            //Explode vacancies into array and sort, get ready to output
                                            $availability = array_map('trim', $availability);
                                            natcasesort($availability);
                                            natcasesort($vacancies);
                                        }

                                        $dayOut .= "<a class='thickbox hover:bg-blue-200 $bg' href='".$session->get('absoluteURL')."/fullscreen.php?q=/modules/Timetable/report_viewAvailableTeachers_view.php&width=800&height=550&".http_build_query(['ids' => $availability, 'date' => $rowPeriods['date'], 'period' => $rowPeriods['name']])."' style='color: rgba(0,0,0,$ttAlpha); z-index: $zCount; position: absolute; left: 0; top: $top; width: $width ; border: 1px solid rgba(136,136,136, $ttAlpha); height: $height; margin: 0px; padding: 0px;; color: rgba(136,136,136, $ttAlpha)'>";

                                        if ($height > 15) {
                                            $dayOut .= $rowPeriods['name'].'<br/>';
                                        }

                                        if ($height > 30) {

                                            $vacanciesOutput = implode(', ', $vacancies);
                                            $dayOut .= "<div title='".htmlPrep($vacanciesOutput)."' style='color: black; font-weight: normal;line-height: 0.9'>";
                                            if (strlen($vacanciesOutput) <= 50) {
                                                $dayOut .= $vacanciesOutput;
                                            } else {
                                                $dayOut .= substr($vacanciesOutput, 0, 50).'...';
                                            }

                                            $dayOut .= '</div>';
                                        }

                                        $dayOut .= '</a>';
                                        ++$zCount;
                                    }
                                }
                            }
                            $dayOut .= '</td>';
                        }
                    } else {
                        $dayOut .= "<td style='text-align: center; vertical-align: top; font-size: 11px'>";
                        $dayOut .= "<div style='position: relative'>";
                        $dayOut .= "<div style='position: absolute; top: 0; width: $width ; border: 1px solid rgba(136,136,136,$ttAlpha); height: ".ceil($diffTime / 60)."px; margin: 0px; padding: 0px; background-color: rgba(255,196,202,$ttAlpha)'>";
                        $dayOut .= "<div style='position: relative; top: 50%'>";
                        $dayOut .= "<span style='color: rgba(255,0,0,$ttAlpha);'>".__('School Closed').'</span>';
                        $dayOut .= '</div>';
                        $dayOut .= '</div>';
                        $dayOut .= '</div>';
                        $dayOut .= '</td>';
                    }

                    if ($day == '') {
                        $dayOut .= "<td style='text-align: center; vertical-align: top; font-size: 11px'></td>";
                    }

                    echo $dayOut;

                    ++$count;
                }
            }

            echo '</tr>';
            echo "<tr style='height: 1px'>";
            echo "<td style='vertical-align: top; width: 70px; text-align: center; border-top: 1px solid #888'>";
            echo '</td>';
            echo "<td colspan=$daysInWeek style='vertical-align: top; width: 70px; text-align: center; border-top: 1px solid #888'>";
            echo '</td>';
            echo '</tr>';
            echo '</table>';
        }
    }
}
