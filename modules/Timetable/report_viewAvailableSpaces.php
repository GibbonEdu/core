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
use Gibbon\Domain\Timetable\FacilityBookingGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable/report_viewAvailableSpaces.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $page->breadcrumbs->add(__('View Available Facilities'));

    echo '<h2>';
    echo __('Choose Options');
    echo '</h2>';

    $gibbonTTID = null;
    if (isset($_GET['gibbonTTID'])) {
        $gibbonTTID = $_GET['gibbonTTID'];
    }
    $spaceType = null;
    if (isset($_GET['spaceType'])) {
        $spaceType = $_GET['spaceType'];
    }

    $ttDate = null;
    if (isset($_GET['ttDate'])) {
        $ttDate = $_GET['ttDate'];
    }
    if ($ttDate == '') {
        $ttDate = date($_SESSION[$guid]['i18n']['dateFormatPHP']);
    }

    $form = Form::create('viewAvailableFacilities', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');

    $form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/report_viewAvailableSpaces.php');

    $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
    $sql = 'SELECT gibbonTTID as value, name FROM gibbonTT WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name';

    $row = $form->addRow();
        $row->addLabel('gibbonTTID', __('Timetable'));
        $row->addSelect('gibbonTTID')->fromQuery($pdo, $sql, $data)->required()->placeholder(__('Please select...'))->selected($gibbonTTID);

    $facilityTypes = getSettingByScope($connection2, 'School Admin', 'facilityTypes');
    $facilityTypes = (!empty($facilityTypes))? explode(',', $facilityTypes) : array();

    $row = $form->addRow();
        $row->addLabel('spaceType', __('Facility Type'));
        $row->addSelect('spaceType')->fromArray(array('' => __('All')))->fromArray($facilityTypes)->selected($spaceType);

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

        
            $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonTTID' => $gibbonTTID);
            $sql = 'SELECT * FROM gibbonTT WHERE gibbonTTID=:gibbonTTID AND gibbonSchoolYearID=:gibbonSchoolYearID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __('The selected record does not exist, or you do not have access to it.');
            echo '</div>';
        } else {
            $row = $result->fetch();
            $startDayStamp = strtotime(dateConvert($guid, $ttDate));

            //Check which days are school days
            $daysInWeek = 0;
            $days = array();
            $timeStart = '';
            $timeEnd = '';
            
                $dataDays = array();
                $sqlDays = "SELECT * FROM gibbonDaysOfWeek WHERE schoolDay='Y' ORDER BY sequenceNumber";
                $resultDays = $connection2->prepare($sqlDays);
                $resultDays->execute($dataDays);
            $days = $resultDays->fetchAll();
            $daysInWeek = $resultDays->rowCount();
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
            $endDayStamp = $startDayStamp + (86400 * ($daysInWeek ));

            //Convert dates
            $startDate = Format::dateFromTimestamp($startDayStamp, 'Y-m-d');
            $endDate = Format::dateFromTimestamp($endDayStamp, 'Y-m-d');

            //Get and store room bookings for use later
            $facilityBookingGateway = $container->get(FacilityBookingGateway::class);
            $facilityBookings = $facilityBookingGateway->queryFacilityBookingsByDate($startDate, $endDate)->fetchAll();

            $bookings = array();
            foreach ($facilityBookings as $facilityBooking) {
                $bookings[$facilityBooking['date']][$facilityBooking['gibbonSpaceID']][]=array('timeStart' => $facilityBooking['timeStart'], 'timeEnd' => $facilityBooking['timeEnd']);
            }

            $schoolCalendarAlpha = 0.85;
            $ttAlpha = 1.0;

            //Max diff time for week based on timetables
            
                $dataDiff = array('date1' => date('Y-m-d', ($startDayStamp + (86400 * 0))), 'date2' => date('Y-m-d', ($endDayStamp + (86400 * 1))), 'gibbonTTID' => $row['gibbonTTID']);
                $sqlDiff = 'SELECT DISTINCT gibbonTTColumn.gibbonTTColumnID FROM gibbonTTDay JOIN gibbonTTDayDate ON (gibbonTTDay.gibbonTTDayID=gibbonTTDayDate.gibbonTTDayID) JOIN gibbonTTColumn ON (gibbonTTDay.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) WHERE (date>=:date1 AND date<=:date2) AND gibbonTTID=:gibbonTTID';
                $resultDiff = $connection2->prepare($sqlDiff);
                $resultDiff->execute($dataDiff);
            while ($rowDiff = $resultDiff->fetch()) {
                
                    $dataDiffDay = array('gibbonTTColumnID' => $rowDiff['gibbonTTColumnID']);
                    $sqlDiffDay = 'SELECT * FROM gibbonTTColumnRow WHERE gibbonTTColumnID=:gibbonTTColumnID ORDER BY timeStart';
                    $resultDiffDay = $connection2->prepare($sqlDiffDay);
                    $resultDiffDay->execute($dataDiffDay);
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
                echo "<span style='font-size: 80%; font-style: italic'>".date($_SESSION[$guid]['i18n']['dateFormatPHP'], ($startDayStamp + (86400 * $dateCorrection))).'</span><br/>';
                echo '</th>';
                $count ++;
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
            
                $dataTerm = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                $sqlTerm = 'SELECT gibbonSchoolYearTerm.firstDay, gibbonSchoolYearTerm.lastDay FROM gibbonSchoolYearTerm, gibbonSchoolYear WHERE gibbonSchoolYearTerm.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID AND gibbonSchoolYear.gibbonSchoolYearID=:gibbonSchoolYearID';
                $resultTerm = $connection2->prepare($sqlTerm);
                $resultTerm->execute($dataTerm);
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
				if ($day['schoolDay'] == 'Y') {
					$dateCorrection = ($day['sequenceNumber'] - 1)-($firstSequence-1);
                    $date = date('Y-m-d', ($startDayStamp + (86400 * $dateCorrection)));

					//Check to see if day is term time
					$isDayInTerm = false;
					
						$dataTerm = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
						$sqlTerm = 'SELECT gibbonSchoolYearTerm.firstDay, gibbonSchoolYearTerm.lastDay FROM gibbonSchoolYearTerm, gibbonSchoolYear WHERE gibbonSchoolYearTerm.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID AND gibbonSchoolYear.gibbonSchoolYearID=:gibbonSchoolYearID';
						$resultTerm = $connection2->prepare($sqlTerm);
						$resultTerm->execute($dataTerm);
					while ($rowTerm = $resultTerm->fetch()) {
						if ($date >= $rowTerm['firstDay'] and $date <= $rowTerm['lastDay']) {
							$isDayInTerm = true;
						}
					}

					if ($isDayInTerm == true) {
						//Check for school closure day
						
							$dataClosure = array('date' => $date);
							$sqlClosure = "SELECT * FROM gibbonSchoolYearSpecialDay WHERE date=:date and type='School Closure'";
							$resultClosure = $connection2->prepare($sqlClosure);
							$resultClosure->execute($dataClosure);
						if ($resultClosure->rowCount() == 1) {
							$rowClosure = $resultClosure->fetch();
							$dayOut .= "<td style='text-align: center; vertical-align: top; font-size: 11px'>";
							$dayOut .= "<div style='position: relative'>";
							$dayOut .= "<div style='z-index: 1; position: absolute; top: 0; width: $width ; border: 1px solid rgba(136,136,136,$ttAlpha); height: ".ceil($diffTime / 60)."px; margin: 0px; padding: 0px; background-color: rgba(255,196,202,$ttAlpha)'>";
							$dayOut .= "<div style='position: relative; top: 50%'>";
							$dayOut .= "<span style='color: rgba(255,0,0,$ttAlpha);'>".$rowClosure['name'].'</span>';
							$dayOut .= '</div>';
							$dayOut .= '</div>';
							$dayOut .= '</div>';
							$dayOut .= '</td>';
						} else {
							$schoolCalendarAlpha = 0.85;
							$ttAlpha = 1.0;

							$output = '';
							$blank = true;

							//Make array of space changes
							$spaceChanges = array();
							
								$dataSpaceChange = array('date' => $date);
								$sqlSpaceChange = 'SELECT gibbonTTSpaceChange.*, gibbonSpace.name AS space, phoneInternal FROM gibbonTTSpaceChange LEFT JOIN gibbonSpace ON (gibbonTTSpaceChange.gibbonSpaceID=gibbonSpace.gibbonSpaceID) WHERE date=:date';
								$resultSpaceChange = $connection2->prepare($sqlSpaceChange);
								$resultSpaceChange->execute($dataSpaceChange);
							while ($rowSpaceChange = $resultSpaceChange->fetch()) {
								$spaceChanges[$rowSpaceChange['gibbonTTDayRowClassID']][0] = $rowSpaceChange['space'];
								$spaceChanges[$rowSpaceChange['gibbonTTDayRowClassID']][1] = $rowSpaceChange['phoneInternal'];
							}

							//Get day start and end!
							$dayTimeStart = '';
							$dayTimeEnd = '';
							
								$dataDiff = array('date' => $date, 'gibbonTTID' => $gibbonTTID);
								$sqlDiff = 'SELECT timeStart, timeEnd FROM gibbonTTDay JOIN gibbonTTDayDate ON (gibbonTTDay.gibbonTTDayID=gibbonTTDayDate.gibbonTTDayID) JOIN gibbonTTColumn ON (gibbonTTDay.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) JOIN gibbonTTColumnRow ON (gibbonTTColumn.gibbonTTColumnID=gibbonTTColumnRow.gibbonTTColumnID) WHERE date=:date AND gibbonTTID=:gibbonTTID';
								$resultDiff = $connection2->prepare($sqlDiff);
								$resultDiff->execute($dataDiff);
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
							try {
								$dataDay = array('gibbonTTID' => $gibbonTTID, 'date' => $date);
								$sqlDay = 'SELECT gibbonTTDay.gibbonTTDayID FROM gibbonTTDayDate JOIN gibbonTTDay ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) WHERE gibbonTTID=:gibbonTTID AND date=:date';
								$resultDay = $connection2->prepare($sqlDay);
								$resultDay->execute($dataDay);
							} catch (PDOException $e) {
								$dayOut .= "<div class='error'>".$e->getMessage().'</div>';
							}

							if ($resultDay->rowCount() == 1) {
								$rowDay = $resultDay->fetch();
								$zCount = 0;
								$dayOut .= "<div style='position: relative;'>";

								//Draw outline of the day
								try {
									$dataPeriods = array('gibbonTTDayID' => $rowDay['gibbonTTDayID'], 'date' => $date);
									$sqlPeriods = 'SELECT gibbonTTColumnRow.gibbonTTColumnRowID, gibbonTTColumnRow.name, timeStart, timeEnd, type, date FROM gibbonTTDay JOIN gibbonTTDayDate ON (gibbonTTDay.gibbonTTDayID=gibbonTTDayDate.gibbonTTDayID) JOIN gibbonTTColumn ON (gibbonTTDay.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) JOIN gibbonTTColumnRow ON (gibbonTTColumnRow.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) WHERE gibbonTTDayDate.gibbonTTDayID=:gibbonTTDayID AND date=:date ORDER BY timeStart, timeEnd';
									$resultPeriods = $connection2->prepare($sqlPeriods);
									$resultPeriods->execute($dataPeriods);
								} catch (PDOException $e) {
									$dayOut .= "<div class='error'>".$e->getMessage().'</div>';
								}
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
										$bg = "rgba(238,238,238,$ttAlpha)";
										if ((date('H:i:s') > $effectiveStart) and (date('H:i:s') < $effectiveEnd) and $rowPeriods['date'] == date('Y-m-d')) {
											$bg = "rgba(179,239,194,$ttAlpha)";
										}
										$style = '';
										if ($rowPeriods['type'] == 'Lesson') {
											$style = '';
										}
										$dayOut .= "<div style='color: rgba(0,0,0,$ttAlpha); z-index: $zCount; position: absolute; top: $top; width: $width ; border: 1px solid rgba(136,136,136, $ttAlpha); height: $height; margin: 0px; padding: 0px; background-color: $bg; color: rgba(136,136,136, $ttAlpha) $style'>";
										if ($height > 15) {
											$dayOut .= $rowPeriods['name'].'<br/>';
										}
										if ($rowPeriods['type'] == 'Lesson') {
											$vacancies = '';
											try {
												if ($spaceType == '') {
													$dataSelect = array();
													$sqlSelect = 'SELECT * FROM gibbonSpace ORDER BY name';
												} else {
													$dataSelect = array('type' => $spaceType);
													$sqlSelect = 'SELECT * FROM gibbonSpace WHERE type=:type ORDER BY name';
												}
												$resultSelect = $connection2->prepare($sqlSelect);
												$resultSelect->execute($dataSelect);
											} catch (PDOException $e) {}
											$removers = array();
											$adders = array();
											while ($rowSelect = $resultSelect->fetch()) {
												
													$dataUnique = array('gibbonTTDayID' => $rowDay['gibbonTTDayID'], 'gibbonTTColumnRowID' => $rowPeriods['gibbonTTColumnRowID'], 'gibbonSpaceID' => $rowSelect['gibbonSpaceID']);
													$sqlUnique = 'SELECT gibbonTTDayRowClass.*, gibbonSpace.name AS roomName FROM gibbonTTDayRowClass JOIN gibbonSpace ON (gibbonTTDayRowClass.gibbonSpaceID=gibbonSpace.gibbonSpaceID) WHERE gibbonTTDayID=:gibbonTTDayID AND gibbonTTColumnRowID=:gibbonTTColumnRowID AND gibbonTTDayRowClass.gibbonSpaceID=:gibbonSpaceID';
													$resultUnique = $connection2->prepare($sqlUnique);
													$resultUnique->execute($dataUnique);
												if ($resultUnique->rowCount() != 1) {
													$vacancies .= $rowSelect['name'].', ';
												} else {
													//Check if space freed up here
													$rowUnique = $resultUnique->fetch();
													if (isset($spaceChanges[$rowUnique['gibbonTTDayRowClassID']])) {
														//Save newly used space
														$removers[$spaceChanges[$rowUnique['gibbonTTDayRowClassID']][0]] = $spaceChanges[$rowUnique['gibbonTTDayRowClassID']][0];

														//Save newly freed space
														$adders[$rowUnique['roomName']] = $rowUnique['roomName'];
													}
												}

                                                //Add any bookings to removers
                                                if (!empty($bookings[$date][$rowSelect['gibbonSpaceID']]) && is_array($bookings[$date][$rowSelect['gibbonSpaceID']])) {
                                                    
                                                    foreach ($bookings[$date][$rowSelect['gibbonSpaceID']] AS $bookingInner) {
                                                        if (($bookingInner['timeStart'] <= $effectiveEnd) && ($bookingInner['timeEnd'] >= $effectiveStart)) {
                                                            $removers[$rowSelect['name']] = $rowSelect['name'];
                                                        }
                                                    }
                                                }
											}

											//Remove any cancelling moves
											foreach ($removers as $remove) {
												if (isset($adders[$remove])) {
													$adders[$remove] = null;
													$removers[$remove] = null;
												}
											}
											foreach ($adders as $adds) {
												if ($adds != '') {
													$vacancies .= $adds.', ';
												}
											}
											foreach ($removers as $remove) {
												if ($remove != '') {
													$vacancies = str_replace($remove.', ', '', $vacancies);
												}
											}

											//Explode vacancies into array and sort, get ready to output
											$vacancies = explode(',', substr($vacancies, 0, -2));
											natcasesort($vacancies);
											$vacanciesOutput = '';
											foreach ($vacancies as $vacancy) {
												$vacanciesOutput .= $vacancy.', ';
											}
											$vacanciesOutput = substr($vacanciesOutput, 0, -2);

											$dayOut .= "<div title='".htmlPrep($vacanciesOutput)."' style='color: black; font-weight: normal; line-height: 0.9'>";
											if (strlen($vacanciesOutput) <= 50) {
												$dayOut .= $vacanciesOutput;
											} else {
												$dayOut .= substr($vacanciesOutput, 0, 50).'...';
											}

											$dayOut .= '</div>';
										}
										$dayOut .= '</div>';
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

					if ($dayOut == '') {
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
