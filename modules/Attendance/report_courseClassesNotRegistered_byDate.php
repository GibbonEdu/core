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

@session_start();

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Attendance/report_courseClassesNotRegistered_byDate.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Classes Not Registered').'</div>';
    echo '</div>';
    echo '<h2>';
    echo __($guid, 'Choose Date');
    echo '</h2>';

    $today = date('Y-m-d');

    $dateEnd = (isset($_GET['dateEnd']))? dateConvert($guid, $_GET['dateEnd']) : date('Y-m-d');
    $dateStart = (isset($_GET['dateStart']))? dateConvert($guid, $_GET['dateStart']) : date('Y-m-d', strtotime( $dateEnd.' -4 days') );

    // Correct inverse date ranges rather than generating an error
    if ($dateStart > $dateEnd) {
        $swapDates = $dateStart;
        $dateStart = $dateEnd;
        $dateEnd = $swapDates;
    }

    $datediff = strtotime($dateEnd) - strtotime($dateStart);
    $daysBetweenDates = floor($datediff / (60 * 60 * 24)) + 1;

    $lastSetOfSchoolDays = getLastNSchoolDays($guid, $connection2, $dateEnd, $daysBetweenDates, true);

    $lastNSchoolDays = array();
    for($i = 0; $i < count($lastSetOfSchoolDays); $i++) {
        if ( $lastSetOfSchoolDays[$i] >= $dateStart  ) $lastNSchoolDays[] = $lastSetOfSchoolDays[$i];
    }

    ?>
	
	<form method="get" action="<?php echo $_SESSION[$guid]['absoluteURL']?>/index.php">
		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<tr>
                <td style='width: 275px'> 
                    <b><?php echo __($guid, 'Start Date') ?> *</b><br/>
                    <span class="emphasis small"><?php echo __($guid, 'Format:').' '.$_SESSION[$guid]['i18n']['dateFormat']  ?></span>
                </td>
                <td class="right">
                    <input name="dateStart" id="dateStart" maxlength=10 value="<?php echo dateConvertBack($guid, $dateStart) ?>" type="text" class="standardWidth">
                    <script type="text/javascript">
                        var dateStart=new LiveValidation('dateStart');
                        dateStart.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
                            echo "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
                        } else {
                            echo $_SESSION[$guid]['i18n']['dateFormatRegEx'];
                        }
                            ?>, failureMessage: "Use <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
                            echo 'dd/mm/yyyy';
                        } else {
                            echo $_SESSION[$guid]['i18n']['dateFormat'];
                        }
                        ?>." } ); 
                        dateStart.add(Validate.Presence);
                    </script>
                     <script type="text/javascript">
                        $(function() {
                            $( "#dateStart" ).datepicker();
                        });
                    </script>
                </td>
            </tr>
            <tr>
                <td style='width: 275px'> 
                    <b><?php echo __($guid, 'End Date') ?> *</b><br/>
                    <span class="emphasis small"><?php echo __($guid, 'Format:').' '.$_SESSION[$guid]['i18n']['dateFormat']  ?></span>
                </td>
                <td class="right">
                    <input name="dateEnd" id="dateEnd" maxlength=10 value="<?php echo dateConvertBack($guid, $dateEnd) ?>" type="text" class="standardWidth">
                    <script type="text/javascript">
                        var dateEnd=new LiveValidation('dateEnd');
                        dateEnd.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
                            echo "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
                        } else {
                            echo $_SESSION[$guid]['i18n']['dateFormatRegEx'];
                        }
                            ?>, failureMessage: "Use <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
                            echo 'dd/mm/yyyy';
                        } else {
                            echo $_SESSION[$guid]['i18n']['dateFormat'];
                        }
                        ?>." } ); 
                        dateEnd.add(Validate.Presence);
                    </script>
                     <script type="text/javascript">
                        $(function() {
                            $( "#dateEnd" ).datepicker();
                        });
                    </script>
                </td>
            </tr>
			<tr>
				<td colspan=2 class="right">
					<input type="hidden" name="q" value="/modules/<?php echo $_SESSION[$guid]['module'] ?>/report_courseClassesNotRegistered_byDate.php">
					<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php

    if ( count($lastNSchoolDays) == 0 ) {
        echo "<div class='error'>";
        echo __($guid, 'School is closed on the specified date, and so attendance information cannot be recorded.');
        echo '</div>';
    }
    else if ($dateStart != '') {
        echo '<h2>';
        echo __($guid, 'Report Data');
        echo '</h2>';

        //Produce array of attendance data
        try {
            $data = array('dateStart' => $lastNSchoolDays[count($lastNSchoolDays)-1], 'dateEnd' => $lastNSchoolDays[0]);
            $sql = "SELECT date, gibbonCourseClassID FROM gibbonAttendanceLogCourseClass WHERE date>=:dateStart AND date<=:dateEnd ORDER BY date";

            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }
        $log = array();
        while ($row = $result->fetch()) {
            $log[$row['gibbonCourseClassID']][$row['date']] = true;
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
        $tt = array();
        while ($row = $result->fetch()) {
            $tt[$row['gibbonCourseClassID']][$row['date']] = true;
        }


        try {
            $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'] );
            $sql = "SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourseClass.name as class, gibbonCourse.name as course, gibbonCourse.nameShort as courseShort, (SELECT count(*) FROM gibbonCourseClassPerson WHERE role='Student' AND gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) as studentCount FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClass.attendance = 'Y' ORDER BY gibbonCourse.nameShort, gibbonCourseClass.nameShort";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() < 1) {
            echo "<div class='error'>";
            echo __($guid, 'There are no records to display.');
            echo '</div>';
        }
        else if ($dateStart > $today || $dateEnd > $today) {
            echo "<div class='error'>";
            echo __($guid, 'The specified date is in the future: it must be today or earlier.');
            echo '</div>';
        } else {
            //Produce array of roll groups
            $classes = $result->fetchAll();

            echo "<div class='linkTop'>";
            echo "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/report.php?q=/modules/'.$_SESSION[$guid]['module'].'/report_courseClassesNotRegistered_byDate_print.php&dateStart='.dateConvertBack($guid, $dateStart).'&dateEnd='.dateConvertBack($guid, $dateEnd)."'>".__($guid, 'Print')."<img style='margin-left: 5px' title='".__($guid, 'Print')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/print.png'/></a>";
            echo '</div>';

            echo "<table cellspacing='0' class='fullWidth colorOddEven'>";
            echo "<tr class='head'>";
            echo '<th width="140px">';
            echo __($guid, 'Class');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Date');
            echo '</th>';
            echo '<th width="164px">';
            echo __($guid, 'History');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Tutor');
            echo '</th>';
            echo '</tr>';

            $count = 0;

            $timestampStart = dateConvertToTimestamp($dateStart);
            $timestampEnd = dateConvertToTimestamp($dateEnd);

            //Loop through each roll group
            foreach ($classes as $row) {

                // Skip classes with no students
                if ($row['studentCount'] <= 0) continue;

                //Output row only if not registered on specified date, and timetabled for that day
                if (isset($tt[$row['gibbonCourseClassID']]) == true && (isset($log[$row['gibbonCourseClassID']]) == false || 
                    count($log[$row['gibbonCourseClassID']]) < min(count($lastNSchoolDays), count($tt[$row['gibbonCourseClassID']])) ) ) {
                    ++$count;

                    //COLOR ROW BY STATUS!
                    echo "<tr>";
                    echo '<td>';
                    echo $row['courseShort'].'.'.$row['class'];
                    echo '</td>';
                    echo '<td>';
                    echo date('M j', $timestampStart).' - '. date('M j, Y', $timestampEnd);
                    echo '</td>';
                    echo '<td style="padding: 0;">';
                    
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
                                
                                $link = './index.php?q=/modules/Attendance/attendance_take_byCourseClass.php&gibbonCourseClassID='.$row['gibbonCourseClassID'].'&currentDate='.$lastNSchoolDays[$i];

                                if ( isset($log[$row['gibbonCourseClassID']][$lastNSchoolDays[$i]]) == true ) {
                                    $class = 'highlightPresent';
                                } else {
                                    if (isset($tt[$row['gibbonCourseClassID']][$lastNSchoolDays[$i]]) == true) {
                                        $class = 'highlightAbsent';

                                    } else {
                                        $class = 'highlightNoData';
                                        $link = '';
                                    }
                                }

                                echo "<td class='$class' style='padding: 12px !important;'>";
                                if ($link != '') {
                                    echo "<a href='$link' >";
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

                    echo '</td>';
                    echo '<td>';

                    try {
                        $dataTutor = array('gibbonCourseClassID' => $row['gibbonCourseClassID'] );
                        $sqlTutor = 'SELECT gibbonPerson.gibbonPersonID, surname, preferredName FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonCourseClassPerson.role = "Teacher"';
                        $resultTutor = $connection2->prepare($sqlTutor);
                        $resultTutor->execute($dataTutor);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }

                    if ($resultTutor->rowCount() > 0) {
                        while ($rowTutor = $resultTutor->fetch()) {
                            echo formatName('', $rowTutor['preferredName'], $rowTutor['surname'], 'Staff', true, true).'<br/>';
                        }
                    }
                    
                    echo '</td>';
                    echo '</tr>';
                }
            }
            
            

            if ($count == 0) {
                echo "<tr";
                echo '<td colspan=3>';
                echo __($guid, 'All classes have been registered.');
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';

            if ($count > 0) {
                echo "<div class='success'>";
                    echo '<b>'.__($guid, 'Total:')." $count</b><br/>";
                echo "</div>";
            }
        }
    }
}
?>