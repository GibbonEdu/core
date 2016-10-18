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

if (isActionAccessible($guid, $connection2, '/modules/Attendance/report_studentsNotOnsite_byDate.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Students Not Onsite').'</div>';
    echo '</div>';

    echo '<h2>';
    echo __($guid, 'Choose Date');
    echo '</h2>';

    if (isset($_GET['currentDate']) == false) {
        $currentDate = date('Y-m-d');
    } else {
        $currentDate = dateConvert($guid, $_GET['currentDate']);
    }

    $allStudents = !empty($_GET["allStudents"])? 1 : 0;

    $sort = !empty($_GET['sort'])? $_GET['sort'] : 'surname, preferredName';

    require_once './modules/Attendance/src/attendanceView.php';
    $attendance = new Module\Attendance\attendanceView(NULL, NULL, $pdo);

    ?>
	
	<form method="get" action="<?php echo $_SESSION[$guid]['absoluteURL']?>/index.php">
		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<tr>
				<td style='width: 275px'> 
					<b><?php echo __($guid, 'Date') ?> *</b><br/>
					<span class="emphasis small"><?php echo __($guid, 'Format:').' ';
					if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
						echo 'dd/mm/yyyy';
					} else {
						echo $_SESSION[$guid]['i18n']['dateFormat'];
					}
					?></span>
				</td>
				<td class="right">
					<input name="currentDate" id="currentDate" maxlength=10 value="<?php echo dateConvertBack($guid, $currentDate) ?>" type="text" class="standardWidth">
					<script type="text/javascript">
						var date=new LiveValidation('date');
						date.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
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
						date.add(Validate.Presence);
					</script>
					 <script type="text/javascript">
						$(function() {
							$( "#currentDate" ).datepicker();
						});
					</script>
				</td>
			</tr>
            <tr>
                <td>
                    <b><?php echo __($guid, 'Sort By') ?></b><br/>
                </td>
                <td class="right">
                    <select name="sort" class="standardWidth">
                        <option value="surname, preferredName" <?php if ($sort == 'surname, preferredName') { echo 'selected'; } ?>><?php echo __($guid, 'Surname'); ?></option>
                        <option value="preferredName" <?php if ($sort == 'preferredName') { echo 'selected'; } ?>><?php echo __($guid, 'Given Name'); ?></option>
                        <option value="rollGroup" <?php if ($sort == 'rollGroup') { echo 'selected'; } ?>><?php echo __($guid, 'Roll Group'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <td>
                    <b><?php print _('All Students') ?></b><br/>
                    <span style="font-size: 90%"><i><?php print _('Include all students, even those where attendance has not yet been recorded.') ?></i></span>
                </td>
                <td class="right">
                    <?php
                        print "<input ".( ($allStudents)? "checked" : ""  )." name=\"allStudents\" id=\"allStudents\" type=\"checkbox\">" ;
                    ?>
                </td>
            </tr>
			<tr>
				<td colspan=2 class="right">
					<input type="hidden" name="q" value="/modules/<?php echo $_SESSION[$guid]['module'] ?>/report_studentsNotOnsite_byDate.php">
					<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php

    if ($currentDate != '') {
        echo '<h2>';
        echo __($guid, 'Report Data');
        echo '</h2>';

        //Produce array of attendance data
        try {
            $data = array('date' => $currentDate);
            $sql = 'SELECT * FROM gibbonAttendanceLogPerson WHERE date=:date ORDER BY gibbonPersonID, gibbonAttendanceLogPersonID DESC';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() < 1) {
            echo "<div class='error'>";
            echo __($guid, 'There are no records to display.');
            echo '</div>';
        } else {
            $log = array();
            $currentStudent = '';
            $lastStudent = '';
            while ($row = $result->fetch()) {
                $currentStudent = $row['gibbonPersonID'];
                if ( $attendance->isTypeOnsite($row['type']) and $currentStudent != $lastStudent) {
                    $log[$row['gibbonPersonID']] = true;
                }
                $lastStudent = $currentStudent;
            }

            try {
                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                $sql = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, gibbonRollGroup.gibbonRollGroupID, gibbonRollGroup.name as rollGroupName, gibbonRollGroup.nameShort AS rollGroup FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) LEFT JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID";
                
                if ($sort != 'surname, preferredName' && $sort != 'preferredName' && $sort != 'rollGroup') {
                    $sort = 'surname, preferredName';
                }
                
                if ($sort == 'rollGroup') {
                    $sql .= ' ORDER BY LENGTH(rollGroup), rollGroup, surname, preferredName';
                } else {
                    $sql .= ' ORDER BY ' . $sort;
                }

                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() < 1) {
                echo "<div class='error'>";
                echo __($guid, 'There are no records to display.');
                echo '</div>';
            } else {
                echo "<div class='linkTop'>";
                echo "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/report.php?q=/modules/'.$_SESSION[$guid]['module'].'/report_studentsNotOnsite_byDate_print.php&currentDate='.dateConvertBack($guid, $currentDate)."&allStudents=" . $allStudents . "&sort=" . $sort . "'>".__($guid, 'Print')."<img style='margin-left: 5px' title='".__($guid, 'Print')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/print.png'/></a>";
                echo '</div>';

                $lastPerson = '';

                echo '<table cellspacing="0" class="fullWidth colorOddEven" >';
                echo '<tr class="head">';
                echo '<th style="width:80px">';
                echo __($guid, 'Roll Group');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Name');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Status');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Reason');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Comment');
                echo '</th>';
                echo '</tr>';

                while ($row = $result->fetch()) {
                    if (isset($log[$row['gibbonPersonID']]) == false) {

                        try {
                            $dataAttendance = array('date' => $currentDate, 'gibbonPersonID' => $row['gibbonPersonID']);
                            $sqlAttendance = 'SELECT * FROM gibbonAttendanceLogPerson WHERE date=:date AND gibbonPersonID=:gibbonPersonID ORDER BY gibbonAttendanceLogPersonID DESC';
                            $resultAttendance = $connection2->prepare($sqlAttendance);
                            $resultAttendance->execute($dataAttendance);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }

                        // Skip rows with no record if we're not displaying all students
                        if ($resultAttendance->rowCount()<1 && $allStudents == FALSE) {
                            continue;
                        }

                        // Row
                        echo "<tr>";
                        echo '<td>';
                            echo $row['rollGroupName'];
                        echo '</td>';
                        echo '<td>';
                            echo formatName('', $row['preferredName'], $row['surname'], 'Student', ($sort != 'preferredName') );
                        echo '</td>';
                        echo '<td>';
                        $rowRollAttendance = null;
                        
                        if ($resultAttendance->rowCount() < 1) {
                            echo '<i>Not registered</i>';
                        } else {
                            $rowRollAttendance = $resultAttendance->fetch();
                            echo $rowRollAttendance['type'];
                        }
                        echo '</td>';
                        echo '<td>';
                            echo $rowRollAttendance['reason'];
                        echo '</td>';
                        echo '<td>';
                            echo $rowRollAttendance['comment'];
                        echo '</td>';
                        echo '</tr>';

                        $lastPerson = $row['gibbonPersonID'];
                    }
                }
                if ($result->rowCount() == 0) {
                    echo "<tr>";
                    echo '<td colspan=5>';
                    echo __($guid, 'All students are present.');
                    echo '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            }
        }
    }
}
?>