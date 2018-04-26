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

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Attendance/attendance.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __('View Daily Attendance') . "</div>" ;
	print "</div>" ;

	print "<h2>" ;
	print __("View Daily Attendance") ;
	print "</h2>" ;

	if (isset($_GET["currentDate"])==FALSE) {
	 	$currentDate=date("Y-m-d");
	}
	else {
		$currentDate=dateConvert($guid, $_GET["currentDate"]) ;
	}

	$today=date("Y-m-d");

	$lastNSchoolDays = getLastNSchoolDays($guid, $connection2, $currentDate, 10, true);

	$accessNotRegistered = isActionAccessible($guid, $connection2, "/modules/Attendance/report_rollGroupsNotRegistered_byDate.php") && isActionAccessible($guid, $connection2, "/modules/Attendance/report_courseClassesNotRegistered_byDate.php");

	$gibbonPersonID = ($accessNotRegistered && isset($_GET['gibbonPersonID']))? $_GET['gibbonPersonID'] : $_SESSION[$guid]["gibbonPersonID"];

	$form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php','get');

    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', "/modules/".$_SESSION[$guid]['module']."/attendance.php");

	$row = $form->addRow();
	    $row->addLabel('currentDate', __('Date'))->description($_SESSION[$guid]['i18n']['dateFormat'])->prepend(__('Format:'));
	    $row->addDate('currentDate')->setValue(dateConvertBack($guid, $currentDate))->isRequired();

    if (isActionAccessible($guid, $connection2, '/modules/Attendance/report_rollGroupsNotRegistered_byDate.php')) {
        $row = $form->addRow();
            $row->addLabel('gibbonPersonID', __('Staff'));
            $row->addSelectStaff('gibbonPersonID')->selected($gibbonPersonID)->placeholder()->isRequired();
    } else {
        $form->addHiddenValue('gibbonPersonID', $_SESSION[$guid]['gibbonPersonID']);
    }

    $row = $form->addRow();
        $row->addFooter();
        $row->addSearchSubmit($gibbon->session);

    echo $form->getOutput();

	if (isset($_SESSION[$guid]["username"])) {

		if ($currentDate>$today) {
			print "<div class='error'>" ;
				print __("The specified date is in the future: it must be today or earlier.");
			print "</div>" ;
		}
		else if (isSchoolOpen($guid, $currentDate, $connection2)==FALSE) {
			print "<div class='error'>" ;
				print __("School is closed on the specified date, and so attendance information cannot be recorded.") ;
			print "</div>" ;
		} else {


			if ( isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byRollGroup.php") ) {
				// Show My Form Groups
				try {
					$data=array("gibbonPersonIDTutor1"=>$gibbonPersonID, "gibbonPersonIDTutor2"=>$gibbonPersonID, "gibbonPersonIDTutor3"=>$gibbonPersonID, "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]);
					$sql="SELECT gibbonRollGroupID, gibbonRollGroup.nameShort as name, firstDay, lastDay FROM gibbonRollGroup JOIN gibbonSchoolYear ON (gibbonRollGroup.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE (gibbonPersonIDTutor=:gibbonPersonIDTutor1 OR gibbonPersonIDTutor2=:gibbonPersonIDTutor2 OR gibbonPersonIDTutor3=:gibbonPersonIDTutor3) AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonRollGroup.attendance = 'Y'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) {
					print "<div class='error'>" . $e->getMessage() . "</div>" ;
				}

			 	if ($result->rowCount()>0) {

			 		print "<h2 style='margin-bottom: 10px'  class='sidebar'>" ;
					print __($guid, "My Roll Group") ;
					print "</h2>" ;

					print "<table class='mini' cellspacing='0' style='width: 100%; table-layout: fixed;'>" ;
						print "<tr class='head'>" ;
								print "<th style='width: 80px; font-size: 85%; text-transform: uppercase'>" ;
								print __("Group") ;
							print "</th>" ;

							print "<th style='width: 342px; font-size: 60%; text-align: center; text-transform: uppercase'>" ;
								print __("Recent History") ;
							print "</th>" ;

							print "<th style='width: 40px; font-size: 60%; text-align: center; text-transform: uppercase'>" ;
								print __("Today") ;
							print "</th>" ;

							print "<th style='width: 40px; font-size: 60%; text-align: center; text-transform: uppercase'>" ;
								print __("In") ;
							print "</th>" ;

							print "<th style='width: 40px; font-size: 60%; text-align: center; text-transform: uppercase'>" ;
								print __("Out") ;
							print "</th>" ;

							if (isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byRollGroup.php")) {

								print "<th style='width: 50px; font-size: 60%; text-align: center; text-transform: uppercase'>" ;
									print __("Actions") ;
								print "</th>" ;
							}

						print "</tr>" ;

						while ($row=$result->fetch()) {

							//Produce array of attendance data
					        try {
					            $dataAttendance = array("gibbonRollGroupID" => $row["gibbonRollGroupID"], 'dateStart' => $lastNSchoolDays[count($lastNSchoolDays)-1], 'dateEnd' => $lastNSchoolDays[0] );
					            $sqlAttendance = 'SELECT date, gibbonRollGroupID, UNIX_TIMESTAMP(timestampTaken) FROM gibbonAttendanceLogRollGroup WHERE gibbonRollGroupID=:gibbonRollGroupID AND date>=:dateStart AND date<=:dateEnd ORDER BY date';
					            $resultAttendance = $connection2->prepare($sqlAttendance);
					            $resultAttendance->execute($dataAttendance);
					        } catch (PDOException $e) {
					            echo "<div class='error'>".$e->getMessage().'</div>';
					        }
					        $logHistory = array();
					        while ($rowAttendance = $resultAttendance->fetch()) {
					            $logHistory[$rowAttendance['date']] = true;
					        }

							//Grab attendance log for the group & current day
							try {
								$dataLog=array("gibbonRollGroupID"=>$row["gibbonRollGroupID"], "date"=>$currentDate . "%");

								$sqlLog="SELECT DISTINCT gibbonAttendanceLogRollGroupID, gibbonAttendanceLogRollGroup.timestampTaken as timestamp,
								COUNT(DISTINCT gibbonAttendanceLogPerson.gibbonPersonID) AS total,
								COUNT(DISTINCT CASE WHEN gibbonAttendanceLogPerson.direction = 'Out' THEN gibbonAttendanceLogPerson.gibbonPersonID END) AS absent
								FROM gibbonAttendanceLogPerson
								JOIN gibbonAttendanceLogRollGroup ON (gibbonAttendanceLogRollGroup.date = gibbonAttendanceLogPerson.date)
								JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonAttendanceLogPerson.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonAttendanceLogRollGroup.gibbonRollGroupID)
								WHERE gibbonAttendanceLogRollGroup.gibbonRollGroupID=:gibbonRollGroupID
								AND gibbonAttendanceLogPerson.date LIKE :date
								AND gibbonAttendanceLogPerson.context = 'Roll Group'
								GROUP BY gibbonAttendanceLogRollGroup.gibbonAttendanceLogRollGroupID
								ORDER BY gibbonAttendanceLogPerson.timestampTaken" ;

								$resultLog=$connection2->prepare($sqlLog);
								$resultLog->execute($dataLog);
							}
							catch(PDOException $e) {
								print "<div class='error'>" . $e->getMessage() . "</div>" ;
							}

							$log=$resultLog->fetch();

							print "<tr>" ;

								print "<td style='word-wrap: break-word'>" ;
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Roll Groups/rollGroups_details.php&gibbonRollGroupID=" . $row["gibbonRollGroupID"] . "'>" . $row["name"] . "</a>" ;
								print "</td>" ;


								print "<td style='text-align: center'>" ;


									echo "<table cellspacing='0' class='historyCalendarMini' style='width:160px;margin:0;' >";
			                        echo '<tr>';
			                        $historyCount = 0;
			                        for ($i = count($lastNSchoolDays)-1; $i >= 0; --$i) {

			                            $link = '';
			                            if ($i > ( count($lastNSchoolDays) - 1)) {
			                                echo "<td class='highlightNoData'>";
			                                echo '<i>'.__($guid, 'NA').'</i>';
			                                echo '</td>';
			                            } else {

			                                $currentDayTimestamp = dateConvertToTimestamp($lastNSchoolDays[$i]);

			                                $link = './index.php?q=/modules/Attendance/attendance_take_byRollGroup.php&gibbonRollGroupID='.$row['gibbonRollGroupID'].'&currentDate='.dateConvertBack($guid, $lastNSchoolDays[$i]);

			                                if (isset($logHistory[$lastNSchoolDays[$i]]) == false) {
			                                    //$class = 'highlightNoData';
			                                    $class = 'highlightAbsent';
			                                } else {

			                                    $class = 'highlightPresent';
			                                }

			                                echo "<td class='$class' style='padding: 12px !important;'>";
			                                    echo "<a href='$link'>";
			                                    echo date('d', $currentDayTimestamp).'<br/>';
			                                    echo "<span>".date('M', $currentDayTimestamp).'</span>';
			                                    echo '</a>';
			                                echo '</td>';
			                            }

			                            // Wrap to a new line every 10 dates
			                            if (  ($historyCount+1) % 10 == 0 ) {
			                                echo '</tr><tr>';
			                            }

			                            $historyCount++;
			                        }

			                        echo '</tr>';
			                        echo '</table>';


								print "</td>" ;

								print "<td style='text-align: center'>" ;
									// Attendance not taken
									if ($resultLog->rowCount()<1) {
										print '<img src="./themes/' . $_SESSION[$guid]["gibbonThemeName"] . '/img/iconCross.png"/>' ;
									} else {
										print '<img src="./themes/' . $_SESSION[$guid]["gibbonThemeName"] . '/img/iconTick.png"/>' ;
									}
								print "</td>" ;

								print "<td style='text-align: center'>" ;
									print ($resultLog->rowCount()<1)? "" : ($log["total"] - $log["absent"]);
								print "</td>" ;

								print "<td style='text-align: center'>" ;
									print $log["absent"];
								print "</td>" ;


								if (isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byRollGroup.php")) {

									print "<td style='text-align: center'>" ;
										print "<a href='index.php?q=/modules/Attendance/attendance_take_byRollGroup.php&gibbonRollGroupID=" . $row["gibbonRollGroupID"] . "&currentDate=" . dateConvertBack($guid, $currentDate) . "'><img title='" . __('Take Attendance') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/attendance.png'/></a>" ;
									print "</td>" ;

								}


							print "</tr>" ;


						}

					print "</table><br/>" ;
				}
			}

			if ( isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byCourseClass.php") ) {

				//Produce array of attendance data
		        try {
		            $data = array('dateStart' => $lastNSchoolDays[count($lastNSchoolDays)-1], 'dateEnd' => $lastNSchoolDays[0]);
		            $sql = "SELECT date, gibbonCourseClassID FROM gibbonAttendanceLogCourseClass WHERE date>=:dateStart AND date<=:dateEnd ORDER BY date";

		            $result = $connection2->prepare($sql);
		            $result->execute($data);
		        } catch (PDOException $e) {
		            echo "<div class='error'>".$e->getMessage().'</div>';
		        }
		        $logHistory = array();
		        while ($row = $result->fetch()) {
		            $logHistory[$row['gibbonCourseClassID']][$row['date']] = true;
		        }

		        // Produce an array of scheduled classes
		        try {
		            $data = array('dateStart' => $lastNSchoolDays[count($lastNSchoolDays)-1], 'dateEnd' => $lastNSchoolDays[0] );
		            $sql = "SELECT gibbonTTDayRowClass.gibbonCourseClassID, gibbonTTDayDate.date FROM gibbonTTDayRowClass JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDayRowClass.gibbonTTDayID) JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseClassID=gibbonTTDayRowClass.gibbonCourseClassID) WHERE gibbonCourseClass.attendance = 'Y' AND gibbonTTDayDate.date>=:dateStart AND gibbonTTDayDate.date<=:dateEnd ORDER BY gibbonTTDayDate.date";

		            $result = $connection2->prepare($sql);
		            $result->execute($data);
		        } catch (PDOException $e) {
		            echo "<div class='error'>".$e->getMessage().'</div>';
		        }
		        $ttHistory = array();
		        while ($row = $result->fetch()) {
		            $ttHistory[$row['gibbonCourseClassID']][$row['date']] = true;
		        }

				//Show My Classes
				try {
					$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=> $gibbonPersonID);

					$sql="SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID,
					(SELECT count(*) FROM gibbonCourseClassPerson WHERE role='Student' AND gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) as studentCount
					FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson
					WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID
					AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID
					AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role LIKE '% - Left%'
					AND gibbonCourseClass.attendance = 'Y'
					ORDER BY course, class" ;

					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }

				if ($result->rowCount()>0) {
					print "<h2 style='margin-bottom: 10px'  class='sidebar'>" ;
					print __("My Classes") ;
					print "</h2>" ;

					print "<table class='mini colorOddEven fullWidth' cellspacing='0' style='table-layout: fixed;'>" ;
						print "<tr class='head'>" ;
							print "<th style='width: 80px; font-size: 85%; text-transform: uppercase'>" ;
							print __("Group") ;
						print "</th>" ;

						print "<th style='width: 342px; font-size: 60%; text-align: center; text-transform: uppercase'>" ;
							print __("Recent History") ;
						print "</th>" ;

						print "<th style='width: 40px; font-size: 60%; text-align: center; text-transform: uppercase'>" ;
							print __("Today") ;
						print "</th>" ;

						print "<th style='width: 40px; font-size: 60%; text-align: center; text-transform: uppercase'>" ;
							print __("In") ;
						print "</th>" ;

						print "<th style='width: 40px; font-size: 60%; text-align: center; text-transform: uppercase'>" ;
							print __("Out") ;
						print "</th>" ;

						if (isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byCourseClass.php")) {

							print "<th style='width: 50px; font-size: 60%; text-align: center; text-transform: uppercase'>" ;
								print __("Actions") ;
							print "</th>" ;
						}

					print "</tr>" ;

						$count=0;

						while ($row=$result->fetch()) {

							// Skip unscheduled courses
							//if ( isset($ttHistory[$row['gibbonCourseClassID']]) == false || count($ttHistory[$row['gibbonCourseClassID']]) == 0) continue;

							// Skip classes with no students
                			if ($row['studentCount'] <= 0) continue;

							$count++ ;

							//Grab attendance log for the class & current day
							try {
								$dataLog=array("gibbonCourseClassID"=>$row["gibbonCourseClassID"], "date"=>$currentDate . "%");

								$sqlLog="SELECT gibbonAttendanceLogCourseClass.timestampTaken as timestamp,
								COUNT(gibbonAttendanceLogPerson.gibbonPersonID) AS total, SUM(gibbonAttendanceLogPerson.direction = 'Out') AS absent
								FROM gibbonAttendanceLogCourseClass
								JOIN gibbonAttendanceLogPerson ON gibbonAttendanceLogPerson.gibbonCourseClassID = gibbonAttendanceLogCourseClass.gibbonCourseClassID
								WHERE gibbonAttendanceLogCourseClass.gibbonCourseClassID=:gibbonCourseClassID
								AND gibbonAttendanceLogPerson.context='Class'
								AND gibbonAttendanceLogCourseClass.date LIKE :date AND gibbonAttendanceLogPerson.date LIKE :date
								GROUP BY gibbonAttendanceLogCourseClass.gibbonAttendanceLogCourseClassID
								ORDER BY gibbonAttendanceLogCourseClass.timestampTaken" ;

								$resultLog=$connection2->prepare($sqlLog);
								$resultLog->execute($dataLog);
							}
							catch(PDOException $e) {
								print "<div class='error'>" . $e->getMessage() . "</div>" ;
							}

							$log=$resultLog->fetch();

							print "<tr>" ;

								print "<td style='word-wrap: break-word'>" ;
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Departments/department_course_class.php&gibbonCourseClassID=" . $row["gibbonCourseClassID"] . "'>" . $row["course"] . "." . $row["class"] . "</a>" ;
								print "</td>" ;

								print "<td style='text-align: center'>" ;

								echo "<table cellspacing='0' class='historyCalendarMini' style='width:160px;margin:0;' >";
		                        echo '<tr>';

		                        $historyCount = 0;
		                        for ($i = count($lastNSchoolDays)-1; $i >= 0; --$i) {
		                            $link = '';
		                            if ($i > ( count($lastNSchoolDays) - 1)) {
		                                echo "<td class='highlightNoData'>";
		                                echo '<i>'.__($guid, 'NA').'</i>';
		                                echo '</td>';
		                            } else {

		                                $currentDayTimestamp = dateConvertToTimestamp($lastNSchoolDays[$i]);

		                                $link = './index.php?q=/modules/Attendance/attendance_take_byCourseClass.php&gibbonCourseClassID='.$row['gibbonCourseClassID'].'&currentDate='.dateConvertBack($guid, $lastNSchoolDays[$i]);

		                                if (isset($logHistory[$row['gibbonCourseClassID']][$lastNSchoolDays[$i]]) == true) {
		                                	$class = 'highlightPresent';
		                                } else {

		                                	if (isset($ttHistory[$row['gibbonCourseClassID']][$lastNSchoolDays[$i]]) == true) {
		                                    	$class = 'highlightAbsent';
		                                    } else {
		                                    	$class = 'highlightNoData';
		                                    	$link = '';
		                                    }
		                                }

		                                echo "<td class='$class' style='padding: 12px !important;'>";
		                                if ($link != '') {
		                                    echo "<a href='$link'>";
		                                    echo date('d', $currentDayTimestamp).'<br/>';
		                                    echo "<span>".date('M', $currentDayTimestamp).'</span>';
		                                    echo '</a>';
		                                } else {
		                                    echo date('d', $currentDayTimestamp).'<br/>';
		                                    echo "<span>".date('M', $currentDayTimestamp).'</span>';
		                                }
		                                echo '</td>';

		                                // Wrap to a new line every 10 dates
		                                if (  ($historyCount+1) % 10 == 0 ) {
		                                    echo '</tr><tr>';
		                                }

		                                $historyCount++;
		                            }
		                        }

		                        echo '</tr>';
		                        echo '</table>';

								print "</td>" ;

								print "<td style='text-align: center'>" ;
								// Attendance not taken, timetabled
								if (isset($ttHistory[$row['gibbonCourseClassID']][$currentDate]) == true) {
									if ($resultLog->rowCount()<1) {
										print '<img src="./themes/' . $_SESSION[$guid]["gibbonThemeName"] . '/img/iconCross.png"/>' ;
									} else {
										print '<img src="./themes/' . $_SESSION[$guid]["gibbonThemeName"] . '/img/iconTick.png"/>' ;
									}
								} else if (isset($logHistory[$row['gibbonCourseClassID']][$currentDate])) {
                                    echo '<span title="'.__('This class is not timetabled to run on the specified date. Attendance may still be taken for this group however it currently falls outside the regular schedule for this class.').'">';
                                        echo __('N/A');
                                    echo '</span>';
                                }
								print "</td>" ;

								print "<td style='text-align: center'>" ;
									print ($resultLog->rowCount()<1)? "" : ($log["total"] - $log["absent"]);
								print "</td>" ;

								print "<td style='text-align: center'>" ;
									print $log["absent"];
								print "</td>" ;


								if (isActionAccessible($guid, $connection2, "/modules/Attendance/attendance_take_byCourseClass.php")) {

									print "<td style='text-align: center'>" ;
										print "<a href='index.php?q=/modules/Attendance/attendance_take_byCourseClass.php&gibbonCourseClassID=" . $row["gibbonCourseClassID"] . "&currentDate=" . dateConvertBack($guid, $currentDate) . "'><img title='" . __('Take Attendance') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/attendance.png'/></a>" ;
									print "</td>" ;

								}


							print "</tr>" ;
						}
					print "</table>" ;
				}
			}
		}
	}


}
?>
