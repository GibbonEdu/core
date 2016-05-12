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

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/course_rollover.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Course Enrolment Rollover').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $step = null;
    if (isset($_GET['step'])) {
        $step = $_GET['step'];
    }
    if ($step != 1 and $step != 2 and $step != 3) {
        $step = 1;
    }

    //Step 1
    if ($step == 1) {
        echo '<h3>';
        echo __($guid, 'Step 1');
        echo '</h3>';

        $nextYear = getNextSchoolYearID($_SESSION[$guid]['gibbonSchoolYearID'], $connection2);
        if ($nextYear == false) {
            echo "<div class='error'>";
            echo __($guid, 'The next school year cannot be determined, so this action cannot be performed.');
            echo '</div>';
        } else {
            try {
                $dataNext = array('gibbonSchoolYearID' => $nextYear);
                $sqlNext = 'SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
                $resultNext = $connection2->prepare($sqlNext);
                $resultNext->execute($dataNext);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            if ($resultNext->rowCount() == 1) {
                $rowNext = $resultNext->fetch();
            }
            $nameNext = $rowNext['name'];
            if ($nameNext == '') {
                echo "<div class='error'>";
                echo __($guid, 'The next school year cannot be determined, so this action cannot be performed.');
                echo '</div>';
            } else {
                ?>
				<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/course_rollover.php&step=2' ?>">
					<table class='smallIntBorder fullWidth' cellspacing='0'>	
						<tr>
							<td colspan=2 style='text-align: justify'> 
								<?php
                                echo sprintf(__($guid, 'By clicking the "Proceed" button below you will initiate the course enrolment rollover from %1$s to %2$s. In a big school this operation may take some time to complete. %3$sYou are really, very strongly advised to backup all data before you proceed%4$s.'), '<b>'.$_SESSION[$guid]['gibbonSchoolYearName'].'</b>', '<b>'.$nameNext.'</b>', '<span style="color: #cc0000"><i>', '</span>'); ?>
							</td>
						</tr>
						<tr>
							<td class="right" colspan=2>
								<input type="hidden" name="nextYear" value="<?php echo $nextYear ?>">
								<input type="submit" value="Proceed">
							</td>
						</tr>
					</table>
				<?php

            }
        }
    } elseif ($step == 2) {
        echo '<h3>';
        echo __($guid, 'Step 2');
        echo '</h3>';

        $nextYear = $_POST['nextYear'];
        if ($nextYear == '' or $nextYear != getNextSchoolYearID($_SESSION[$guid]['gibbonSchoolYearID'], $connection2)) {
            echo "<div class='error'>";
            echo __($guid, 'The next school year cannot be determined, so this action cannot be performed.');
            echo '</div>';
        } else {
            try {
                $dataNext = array('gibbonSchoolYearID' => $nextYear);
                $sqlNext = 'SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
                $resultNext = $connection2->prepare($sqlNext);
                $resultNext->execute($dataNext);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            if ($resultNext->rowCount() == 1) {
                $rowNext = $resultNext->fetch();
            }
            $nameNext = $rowNext['name'];
            $sequenceNext = $rowNext['sequenceNumber'];
            if ($nameNext == '' or $sequenceNext == '') {
                echo "<div class='error'>";
                echo __($guid, 'The next school year cannot be determined, so this action cannot be performed.');
                echo '</div>';
            } else {
                echo '<p>';
                echo sprintf(__($guid, 'In rolling over to %1$s, the following actions will take place. You may need to adjust some fields below to get the result you desire.'), $nameNext);
                echo '</p>';

                echo "<form method='post' action='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/course_rollover.php&step=3'>";
                echo '<h4>';
                echo sprintf(__($guid, 'Options'), $nameNext);
                echo '</h4>'; ?>
					<table class='smallIntBorder fullWidth' cellspacing='0'>	
						<tr>
							<td style='width: 275px'> 
								<b><?php echo __($guid, 'Include Students') ?> *</b><br/>
							</td>
							<td class="right">
								<input checked type='checkbox' name='rollStudents'>
							</td>
						</tr>
						<tr>
							<td style='width: 275px'> 
								<b><?php echo __($guid, 'Include Teachers') ?> *</b><br/>
							</td>
							<td class="right">
								<input type='checkbox' name='rollTeachers'>
							</td>
						</tr>
					</table>
					<?php

                    echo '<h4>';
                echo __($guid, 'Map Classess');
                echo '</h4>';
                echo '<p>';
                echo __($guid, 'Determine which classes from this year roll to which classes in next year, and which not to rollover at all.');
                echo '</p>';

                $students = array();
                $count = 0;
                    //Get current courses/classes
                    try {
                        $dataEnrol = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                        $sqlEnrol = 'SELECT gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY course, class';
                        $resultEnrol = $connection2->prepare($sqlEnrol);
                        $resultEnrol->execute($dataEnrol);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }

                    //Store next years courses/classes in an array
                    $coursesNext = array();
                $coursesNextCount = 0;
                try {
                    $dataNext = array('gibbonSchoolYearID' => $nextYear);
                    $sqlNext = 'SELECT gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY course, class';
                    $resultNext = $connection2->prepare($sqlNext);
                    $resultNext->execute($dataNext);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                while ($rowNext = $resultNext->fetch()) {
                    $coursesNext[$coursesNextCount][0] = $rowNext['gibbonCourseClassID'];
                    $coursesNext[$coursesNextCount][1] = $rowNext['course'];
                    $coursesNext[$coursesNextCount][2] = $rowNext['class'];
                    $coursesNext[$coursesNextCount][3] = null;
                        //Prep for matching
                        $matches = array();
                    preg_match_all('!\d+!', $rowNext['course'], $matches);
                    if (count($matches) == 1) {
                        if (isset($matches[0][0])) {
                            $coursesNext[$coursesNextCount][3] = str_replace($matches[0][0], str_pad(($matches[0][0] - 1), strlen($matches[0][0]), '0', STR_PAD_LEFT), $rowNext['course']);
                        }
                    }

                    ++$coursesNextCount;
                }

                if ($resultEnrol->rowCount() < 1) {
                    echo "<div class='error'>";
                    echo __($guid, 'There are no records to display.');
                    echo '</div>';
                } else {
                    echo "<table cellspacing='0' style='width: 100%'>";
                    echo "<tr class='head'>";
                    echo '<th>';
                    echo __($guid, 'Class');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'New Class');
                    echo '</th>';
                    echo '</tr>';

                    $count = 0;
                    $rowNum = 'odd';
                    while ($rowEnrol = $resultEnrol->fetch()) {
                        if ($count % 2 == 0) {
                            $rowNum = 'even';
                        } else {
                            $rowNum = 'odd';
                        }
                        ++$count;

                                //COLOR ROW BY STATUS!
                                echo "<tr class=$rowNum>";
                        echo '<td>';
                        echo "<input type='hidden' name='$count-gibbonCourseClassID' value='".$rowEnrol['gibbonCourseClassID']."'>";
                        echo $rowEnrol['course'].'.'.$rowEnrol['class'];
                        echo '</td>';
                        echo '<td>';
                        echo "<select name='$count-gibbonCourseClassIDNext' id='$count-gibbonCourseClassIDNext' style='float: left; width:110px'>";
                        echo "<option value=''></option>";
                        foreach ($coursesNext as $courseNext) {
                            $selected = '';
                                                //Attempt to select...may not be 100%
                                                if ($courseNext[3] != null) {
                                                    if ($courseNext[3] == $rowEnrol['course']) {
                                                        if ($courseNext[2] == $rowEnrol['class']) {
                                                            $selected = 'selected';
                                                        }
                                                    }
                                                }
                            echo "<option $selected value='".$courseNext[0]."'>".htmlPrep($courseNext[1]).'.'.htmlPrep($courseNext[2]).'</option>';
                        }
                        echo '</select>';
                        echo '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';

                    echo "<input type='hidden' name='count' value='$count'>";
                }

                echo "<table cellspacing='0' style='width: 100%'>";
                echo '<tr>';
                echo '<td>';
                echo "<span style='font-size: 90%'><i>* ".__($guid, 'denotes a required field').'</span>';
                echo '</td>';
                echo "<td class='right'>";
                echo "<input type='hidden' name='nextYear' value='$nextYear'>";
                echo "<input type='submit' value='Proceed'>";
                echo '</td>';
                echo '</tr>';
                echo '</table>';
                echo '</form>';
            }
        }
    } elseif ($step == 3) {
        $nextYear = $_POST['nextYear'];
        if ($nextYear == '' or $nextYear != getNextSchoolYearID($_SESSION[$guid]['gibbonSchoolYearID'], $connection2)) {
            echo "<div class='error'>";
            echo __($guid, 'The next school year cannot be determined, so this action cannot be performed.');
            echo '</div>';
        } else {
            try {
                $dataNext = array('gibbonSchoolYearID' => $nextYear);
                $sqlNext = 'SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
                $resultNext = $connection2->prepare($sqlNext);
                $resultNext->execute($dataNext);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            if ($resultNext->rowCount() == 1) {
                $rowNext = $resultNext->fetch();
            }
            $nameNext = $rowNext['name'];
            $sequenceNext = $rowNext['sequenceNumber'];
            if ($nameNext == '' or $sequenceNext == '') {
                echo "<div class='error'>";
                echo __($guid, 'The next school year cannot be determined, so this action cannot be performed.');
                echo '</div>';
            } else {
                echo '<h3>';
                echo __($guid, 'Step 3');
                echo '</h3>';

                $partialFail = false;

                $count = $_POST['count'];
                $rollStudents = '';
                if (isset($_POST['rollStudents'])) {
                    $rollStudents = $_POST['rollStudents'];
                }
                $rollTeachers = '';
                if (isset($_POST['rollTeachers'])) {
                    $rollTeachers = $_POST['rollTeachers'];
                }

                if ($rollStudents != 'on' and $rollTeachers != 'on') {
                    echo "<div class='error'>";
                    echo __($guid, 'Your request failed because your inputs were invalid.');
                    echo '</div>';
                } else {
                    for ($i = 1; $i <= $count; ++$i) {
                        if (isset($_POST[$i.'-gibbonCourseClassID'])) {
                            $gibbonCourseClassID = $_POST[$i.'-gibbonCourseClassID'];
                            if (isset($_POST[$i.'-gibbonCourseClassIDNext'])) {
                                $gibbonCourseClassIDNext = $_POST[$i.'-gibbonCourseClassIDNext'];

                                //Get staff and students and copy them over
                                if ($rollStudents == 'on' and $rollTeachers == 'on') {
                                    $sqlWhere = " AND (role='Student' OR role='Teacher')";
                                } elseif ($rollStudents == 'on' and $rollTeachers == '') {
                                    $sqlWhere = " AND role='Student'";
                                } else {
                                    $sqlWhere = " AND role='Teacher'";
                                }
                                //Get current enrolment
                                try {
                                    $dataCurrent = array('gibbonCourseClassID' => $gibbonCourseClassID);
                                    $sqlCurrent = "SELECT gibbonPersonID, role FROM gibbonCourseClassPerson WHERE gibbonCourseClassID=:gibbonCourseClassID $sqlWhere";
                                    $resultCurrent = $connection2->prepare($sqlCurrent);
                                    $resultCurrent->execute($dataCurrent);
                                } catch (PDOException $e) {
                                    $partialFail = true;
                                }
                                if ($resultCurrent->rowCount() > 0) {
                                    while ($rowCurrent = $resultCurrent->fetch()) {
                                        try {
                                            $dataInsert = array('gibbonCourseClassID' => $gibbonCourseClassIDNext, 'gibbonPersonID' => $rowCurrent['gibbonPersonID'], 'role' => $rowCurrent['role']);
                                            $sqlInsert = 'INSERT INTO gibbonCourseClassPerson SET gibbonCourseClassID=:gibbonCourseClassID, gibbonPersonID=:gibbonPersonID, role=:role';
                                            $resultInsert = $connection2->prepare($sqlInsert);
                                            $resultInsert->execute($dataInsert);
                                        } catch (PDOException $e) {
                                            $partialFail = true;
                                        }
                                    }
                                }
                            }
                        }
                    }

                    //Feedback result!
                    if ($partialFail == true) {
                        echo "<div class='error'>";
                        echo __($guid, 'Your request was successful, but some data was not properly saved.');
                        echo '</div>';
                    } else {
                        echo "<div class='success'>";
                        echo __($guid, 'Your request was completed successfully.');
                        echo '</div>';
                    }
                }
            }
        }
    }
}
?>