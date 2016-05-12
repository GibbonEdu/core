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

if (isActionAccessible($guid, $connection2, '/modules/User Admin/rollover.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Rollover').'</div>';
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
				<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/rollover.php&step=2' ?>">
					<table class='smallIntBorder fullWidth' cellspacing='0'>	
						<tr>
							<td colspan=2 style='text-align: justify'> 
								<?php
                                echo sprintf(__($guid, 'By clicking the "Proceed" button below you will initiate the rollover from %1$s to %2$s. In a big school this operation may take some time to complete. This will change data in numerous tables across the system! %3$sYou are really, very strongly advised to backup all data before you proceed%4$s.'), '<b>'.$_SESSION[$guid]['gibbonSchoolYearName'].'</b>', '<b>'.$nameNext.'</b>', '<span style="color: #cc0000"><i>', '</span>'); ?>
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

                echo "<form method='post' action='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/rollover.php&step=3'>";

                    //Set enrolment select values
                    $yearGroupOptions = '';
                try {
                    $dataSelect = array();
                    $sqlSelect = 'SELECT gibbonYearGroupID, name FROM gibbonYearGroup ORDER BY sequenceNumber';
                    $resultSelect = $connection2->prepare($sqlSelect);
                    $resultSelect->execute($dataSelect);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                while ($rowSelect = $resultSelect->fetch()) {
                    $yearGroupOptions = $yearGroupOptions."<option value='".$rowSelect['gibbonYearGroupID']."'>".htmlPrep($rowSelect['name']).'</option>';
                }

                $rollGroupOptions = '';
                try {
                    $dataSelect = array('gibbonSchoolYearID' => $nextYear);
                    $sqlSelect = 'SELECT gibbonRollGroupID, name FROM gibbonRollGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name';
                    $resultSelect = $connection2->prepare($sqlSelect);
                    $resultSelect->execute($dataSelect);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                while ($rowSelect = $resultSelect->fetch()) {
                    $rollGroupOptions = $rollGroupOptions."<option value='".$rowSelect['gibbonRollGroupID']."'>".htmlPrep($rowSelect['name']).'</option>';
                }

				//ADD YEAR FOLLOWING NEXT
				if (getNextSchoolYearID($nextYear, $connection2) == false) {
					echo '<h4>';
					echo sprintf(__($guid, 'Add Year Following %1$s'), $nameNext);
					echo '</h4>';
					?>
					<table class='smallIntBorder fullWidth' cellspacing='0'>	
						<tr>
							<td style='width: 275px'> 
								<b><?php echo __($guid, 'School Year Name') ?> *</b><br/>
								<span class="emphasis small"><?php echo __($guid, 'Must be unique.') ?></span>
							</td>
							<td class="right">
								<input name="nextname" id="nextname" maxlength=9 value="" type="text" class="standardWidth">
								<script type="text/javascript">
									var nextname=new LiveValidation('nextname');
									nextname2.add(Validate.Presence);
								</script>
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php echo __($guid, 'Status') ?> *</b>
							</td>
							<td class="right">
								<input readonly name="next-status" id="next-status" value="Upcoming" type="text" class="standardWidth">
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php echo __($guid, 'Sequence Number') ?> *</b><br/>
								<span class="emphasis small"><?php echo __($guid, 'Must be unique. Controls chronological ordering.') ?></span>
							</td>
							<td class="right">
								<input readonly name="next-sequenceNumber" id="next-sequenceNumber" maxlength=3 value="<?php echo $sequenceNext + 1 ?>" type="text" class="standardWidth">
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php echo __($guid, 'First Day') ?> *</b><br/>
								<span class="emphasis small"><?php echo $_SESSION[$guid]['i18n']['dateFormat']  ?></span>
							</td>
							<td class="right">
								<input name="nextfirstDay" id="nextfirstDay" maxlength=10 value="" type="text" class="standardWidth">
								<script type="text/javascript">
									var nextfirstDay=new LiveValidation('nextfirstDay');
									nextfirstDay.add(Validate.Presence);
									nextfirstDay.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
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
								</script>
								 <script type="text/javascript">
									$(function() {
										$( "#nextfirstDay" ).datepicker();
									});
								</script>
							</td>
						</tr>
						<tr>
							<td> 
								<b><?php echo __($guid, 'Last Day') ?> *</b><br/>
								<span class="emphasis small"><?php echo $_SESSION[$guid]['i18n']['dateFormat']  ?></span>
							</td>
							<td class="right">
								<input name="nextlastDay" id="nextlastDay" maxlength=10 value="" type="text" class="standardWidth">
								<script type="text/javascript">
									var nextlastDay=new LiveValidation('nextlastDay');
									nextlastDay.add(Validate.Presence);
									nextlastDay.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
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
								</script>
								 <script type="text/javascript">
									$(function() {
										$( "#nextlastDay" ).datepicker();
									});
								</script>
							</td>
						</tr>
					</table>
					<?php

				}

				//SET EXPECTED USERS TO FULL
				echo '<h4>';
                echo __($guid, 'Set Expected Users To Full');
                echo '</h4>';
                echo '<p>';
                echo __($guid, 'This step primes newcomers who have status set to "Expected" to be enroled as students or added as staff (below).');
                echo '</p>';

                try {
                    $dataExpect = array();
                    $sqlExpect = "SELECT gibbonPersonID, surname, preferredName, name FROM gibbonPerson JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE status='Expected' ORDER BY name, surname, preferredName";
                    $resultExpect = $connection2->prepare($sqlExpect);
                    $resultExpect->execute($dataExpect);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($resultExpect->rowCount() < 1) {
                    echo "<div class='error'>";
                    echo __($guid, 'There are no records to display.');
                    echo '</div>';
                } else {
                    echo "<table cellspacing='0' style='width: 100%'>";
                    echo "<tr class='head'>";
                    echo '<th>';
                    echo __($guid, 'Name');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Primary Role');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Current Status');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'New Status');
                    echo '</th>';
                    echo '</tr>';

                    $count = 0;
                    $rowNum = 'odd';
                    while ($rowExpect = $resultExpect->fetch()) {
                        if ($count % 2 == 0) {
                            $rowNum = 'even';
                        } else {
                            $rowNum = 'odd';
                        }
                        ++$count;

                                //COLOR ROW BY STATUS!
                                echo "<tr class=$rowNum>";
                        echo '<td>';
                        echo "<input type='hidden' name='$count-expect-gibbonPersonID' value='".$rowExpect['gibbonPersonID']."'>";
                        echo formatName('', $rowExpect['preferredName'], $rowExpect['surname'], 'Student', true);
                        echo '</td>';
                        echo '<td>';
                        echo __($guid, $rowExpect['name']);
                        echo '</td>';
                        echo '<td>';
                        echo 'Expected';
                        echo '</td>';
                        echo '<td>';
                        echo "<select name='$count-expect-status' id='$count-expect-status' style='float: left; width:110px'>";
                        echo "<option value='Expected'>".__($guid, 'Expected').'</option>';
                        echo "<option selected value='Full'>".__($guid, 'Full').'</option>';
                        echo "<option value='Left'>".__($guid, 'Left').'</option>';
                        echo '</select>';
                        echo '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';

                    echo "<input type='hidden' name='expect-count' value='$count'>";
                }

				//ENROL NEW STUDENTS
				echo '<h4>';
                echo __($guid, 'Enrol New Students (Status Expected)');
                echo '</h4>';
                echo '<p>';
                echo __($guid, 'Take students who are marked expected and enrol them. All parents of new students who are enroled below will have their status set to "Full". If a student is not enroled, they will be set to "Left".');
                echo '</p>';

                if ($yearGroupOptions == '' or $rollGroupOptions == '') {
                    echo "<div class='error'>".__($guid, 'Year groups or roll groups are not properly set up, so you cannot proceed with this section.').'</div>';
                } else {
                    try {
                        $dataEnrol = array();
                        $sqlEnrol = "SELECT gibbonPersonID, surname, preferredName, name, category FROM gibbonPerson JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE status='Expected' AND category='Student' ORDER BY surname, preferredName";
                        $resultEnrol = $connection2->prepare($sqlEnrol);
                        $resultEnrol->execute($dataEnrol);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }

                    if ($resultEnrol->rowCount() < 1) {
                        echo "<div class='error'>";
                        echo __($guid, 'There are no records to display.');
                        echo '</div>';
                    } else {
                        echo "<table cellspacing='0' style='width: 100%'>";
                        echo "<tr class='head'>";
                        echo '<th>';
                        echo __($guid, 'Name');
                        echo '</th>';
                        echo '<th>';
                        echo __($guid, 'Primary Role');
                        echo '</th>';
                        echo '<th>';
                        echo __($guid, 'Enrol');
                        echo '</th>';
                        echo '<th>';
                        echo __($guid, 'Year Group');
                        echo '</th>';
                        echo '<th>';
                        echo __($guid, 'Roll Group');
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
                            echo "<input type='hidden' name='$count-enrol-gibbonPersonID' value='".$rowEnrol['gibbonPersonID']."'>";
                            echo formatName('', $rowEnrol['preferredName'], $rowEnrol['surname'], 'Student', true);
                            echo '</td>';
                            echo '<td>';
                            echo __($guid, $rowEnrol['name']);
                            echo '</td>';
                            echo '<td>';
                            echo "<input checked type='checkbox' name='$count-enrol-enrol' value='Y'>";
                            echo '</td>';
                            echo '<td>';
                            echo "<select name='$count-enrol-gibbonYearGroupID' id='$count-enrol-gibbonYearGroupID' style='float: left; width:110px'>";
                            echo $yearGroupOptions;
                            echo '</select>';
                            echo '</td>';
                            echo '<td>';
                            echo "<select name='$count-enrol-gibbonRollGroupID' id='$count-enrol-gibbonRollGroupID' style='float: left; width:110px'>";
                            echo $rollGroupOptions;
                            echo '</select>';
                            echo '</td>';
                            echo '</tr>';
                        }
                        echo '</table>';

                        echo "<input type='hidden' name='enrol-count' value='$count'>";
                    }
                }

                echo '<h4>';
                echo __($guid, 'Enrol New Students (Status Full)');
                echo '</h4>';
                echo '<p>';
                echo __($guid, 'Take new students who are already set as full, but who were not enroled last year, and enrol them. These students probably came through the Online Application form, and may already be enroled in next year: if this is the case, their enrolment will be updated as per the information below. All parents of new students who are enroled below will have their status set to "Full". If a student is not enroled, they will be set to "Left"');
                echo '</p>';

                if ($yearGroupOptions == '' or $rollGroupOptions == '') {
                    echo "<div class='error'>".__($guid, 'Year groups or roll groups are not properly set up, so you cannot proceed with this section.').'</div>';
                } else {
                    $students = array();
                    $count = 0;
                    try {
                        $dataEnrol = array();
                        $sqlEnrol = "SELECT gibbonPersonID, surname, preferredName, name, category FROM gibbonPerson JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE status='Full' AND category='Student' ORDER BY surname, preferredName";
                        $resultEnrol = $connection2->prepare($sqlEnrol);
                        $resultEnrol->execute($dataEnrol);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }

                    if ($resultEnrol->rowCount() < 1) {
                        echo "<div class='error'>";
                        echo __($guid, 'There are no records to display.');
                        echo '</div>';
                    } else {
                        while ($rowEnrol = $resultEnrol->fetch()) {
                            try {
                                $dataEnrolled = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $rowEnrol['gibbonPersonID']);
                                $sqlEnrolled = "SELECT gibbonStudentEnrolment.* FROM gibbonPerson JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' AND category='Student' AND gibbonStudentEnrolment.gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName";
                                $resultEnrolled = $connection2->prepare($sqlEnrolled);
                                $resultEnrolled->execute($dataEnrolled);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
                            if ($resultEnrolled->rowCount() < 1) {
                                $students[$count][0] = $rowEnrol['gibbonPersonID'];
                                $students[$count][1] = $rowEnrol['surname'];
                                $students[$count][2] = $rowEnrol['preferredName'];
                                $students[$count][3] = $rowEnrol['name'];
                                ++$count;
                            }
                        }
                    }

                    if ($count < 1) {
                        echo "<div class='error'>";
                        echo __($guid, 'There are no records to display.');
                        echo '</div>';
                    } else {
                        echo "<table cellspacing='0' style='width: 100%'>";
                        echo "<tr class='head'>";
                        echo '<th>';
                        echo __($guid, 'Name');
                        echo '</th>';
                        echo '<th>';
                        echo __($guid, 'Primary Role');
                        echo '</th>';
                        echo '<th>';
                        echo __($guid, 'Enrol');
                        echo '</th>';
                        echo '<th>';
                        echo __($guid, 'Year Group');
                        echo '</th>';
                        echo '<th>';
                        echo __($guid, 'Roll Group');
                        echo '</th>';
                        echo '</tr>';

                        $count = 0;
                        $rowNum = 'odd';
                        foreach ($students as $student) {
                            if ($count % 2 == 0) {
                                $rowNum = 'even';
                            } else {
                                $rowNum = 'odd';
                            }
                            ++$count;

                                    //COLOR ROW BY STATUS!
                                    echo "<tr class=$rowNum>";
                            echo '<td>';
                            echo "<input type='hidden' name='$count-enrolFull-gibbonPersonID' value='".$student[0]."'>";
                            echo formatName('', $student[2], $student[1], 'Student', true);
                            echo '</td>';
                            echo '<td>';
                            echo __($guid, $student[3]);
                            echo '</td>';
                            echo '<td>';
                            echo "<input checked type='checkbox' name='$count-enrolFull-enrol' value='Y'>";
                            echo '</td>';
                                        //Check for enrolment in next year (caused by automated enrolment on application form accept)
                                        $yearGroupSelect = '';
                            $rollGroupSelect = '';
                            try {
                                $dataEnrolled = array('gibbonSchoolYearID' => $nextYear, 'gibbonPersonID' => $student[0]);
                                $sqlEnrolled = 'SELECT * FROM gibbonStudentEnrolment WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID';
                                $resultEnrolled = $connection2->prepare($sqlEnrolled);
                                $resultEnrolled->execute($dataEnrolled);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
                            if ($resultEnrolled->rowCount() == 1) {
                                $rowEnrolled = $resultEnrolled->fetch();
                                $yearGroupSelect = $rowEnrolled['gibbonYearGroupID'];
                                $rollGroupSelect = $rowEnrolled['gibbonRollGroupID'];
                            }
                            echo '<td>';
                            echo "<select name='$count-enrolFull-gibbonYearGroupID' id='$count-enrolFull-gibbonYearGroupID' style='float: left; width:110px'>";
                            try {
                                $dataSelect = array();
                                $sqlSelect = 'SELECT gibbonYearGroupID, name FROM gibbonYearGroup ORDER BY sequenceNumber';
                                $resultSelect = $connection2->prepare($sqlSelect);
                                $resultSelect->execute($dataSelect);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
                            while ($rowSelect = $resultSelect->fetch()) {
                                $selected = '';
                                if ($yearGroupSelect == $rowSelect['gibbonYearGroupID']) {
                                    $selected = 'selected';
                                }
                                echo "<option $selected value='".$rowSelect['gibbonYearGroupID']."'>".htmlPrep($rowSelect['name']).'</option>';
                            }
                            echo '</select>';
                            echo '</td>';
                            echo '<td>';
                            echo "<select name='$count-enrolFull-gibbonRollGroupID' id='$count-enrolFull-gibbonRollGroupID' style='float: left; width:110px'>";
                            try {
                                $dataSelect = array('gibbonSchoolYearID' => $nextYear);
                                $sqlSelect = 'SELECT gibbonRollGroupID, name FROM gibbonRollGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name';
                                $resultSelect = $connection2->prepare($sqlSelect);
                                $resultSelect->execute($dataSelect);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
                            while ($rowSelect = $resultSelect->fetch()) {
                                $selected = '';
                                if ($rollGroupSelect == $rowSelect['gibbonRollGroupID']) {
                                    $selected = 'selected';
                                }
                                echo "<option $selected value='".$rowSelect['gibbonRollGroupID']."'>".htmlPrep($rowSelect['name']).'</option>';
                            }
                            echo '</select>';
                            echo '</td>';
                            echo '</tr>';
                        }
                        echo '</table>';

                        echo "<input type='hidden' name='enrolFull-count' value='$count'>";
                    }
                }

				//RE-ENROL OTHER STUDENTS
				echo '<h4>';
                echo __($guid, 'Re-Enrol Other Students');
                echo '</h4>';
                echo '<p>';
                echo __($guid, 'Any students who are not re-enroled will have their status set to "Left".').' '.__($guid, 'Students who are already enroled will have their enrolment updated.');
                echo '</p>';

                $lastYearGroup = getLastYearGroupID($connection2);

                if ($yearGroupOptions == '' or $rollGroupOptions == '') {
                    echo "<div class='error'>".__($guid, 'Year groups or roll groups are not properly set up, so you cannot proceed with this section.').'</div>';
                } else {
                    try {
                        $dataReenrol = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonYearGroupID' => $lastYearGroup);
                        $sqlReenrol = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, gibbonRole.name, category, gibbonStudentEnrolment.gibbonYearGroupID, gibbonRollGroupIDNext FROM gibbonPerson JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' AND category='Student' AND NOT gibbonYearGroupID=:gibbonYearGroupID ORDER BY surname, preferredName";
                        $resultReenrol = $connection2->prepare($sqlReenrol);
                        $resultReenrol->execute($dataReenrol);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }
                    if ($resultReenrol->rowCount() < 1) {
                        echo "<div class='error'>";
                        echo __($guid, 'There are no records to display.');
                        echo '</div>';
                    } else {
                        echo "<table cellspacing='0' style='width: 100%'>";
                        echo "<tr class='head'>";
                        echo '<th>';
                        echo __($guid, 'Name');
                        echo '</th>';
                        echo '<th>';
                        echo __($guid, 'Primary Role');
                        echo '</th>';
                        echo '<th>';
                        echo __($guid, 'Reenrol');
                        echo '</th>';
                        echo '<th>';
                        echo __($guid, 'Year Group');
                        echo '</th>';
                        echo '<th>';
                        echo __($guid, 'Roll Group');
                        echo '</th>';
                        echo '</tr>';

                        $count = 0;
                        $rowNum = 'odd';
                        while ($rowReenrol = $resultReenrol->fetch()) {
                            if ($count % 2 == 0) {
                                $rowNum = 'even';
                            } else {
                                $rowNum = 'odd';
                            }
                            ++$count;

                                    //COLOR ROW BY STATUS!
                                    echo "<tr class=$rowNum>";
                            echo '<td>';
                            echo "<input type='hidden' name='$count-reenrol-gibbonPersonID' value='".$rowReenrol['gibbonPersonID']."'>";
                            echo formatName('', $rowReenrol['preferredName'], $rowReenrol['surname'], 'Student', true);
                            echo '</td>';
                            echo '<td>';
                            echo __($guid, $rowReenrol['name']);
                            echo '</td>';
                            echo '<td>';
                            echo "<input checked type='checkbox' name='$count-reenrol-enrol' value='Y'>";
                            echo '</td>';
                                        //Check for enrolment
                                        try {
                                            $dataEnrolmentCheck = array('gibbonPersonID' => $rowReenrol['gibbonPersonID'], 'gibbonSchoolYearID' => $nextYear);
                                            $sqlEnrolmentCheck = 'SELECT * FROM gibbonStudentEnrolment WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID';
                                            $resultEnrolmentCheck = $connection2->prepare($sqlEnrolmentCheck);
                                            $resultEnrolmentCheck->execute($dataEnrolmentCheck);
                                        } catch (PDOException $e) {
                                            echo "<div class='error'>".$e->getMessage().'</div>';
                                        }
                            $enrolmentCheckYearGroup = null;
                            $enrolmentCheckRollGroup = null;
                            if ($resultEnrolmentCheck->rowCount() == 1) {
                                $rowEnrolmentCheck = $resultEnrolmentCheck->fetch();
                                $enrolmentCheckYearGroup = $rowEnrolmentCheck['gibbonYearGroupID'];
                                $enrolmentCheckRollGroup = $rowEnrolmentCheck['gibbonRollGroupID'];
                            }
                            echo '<td>';
                            echo "<select name='$count-reenrol-gibbonYearGroupID' id='$count-reenrol-gibbonYearGroupID' style='float: left; width:110px'>";
                            try {
                                $dataSelect = array();
                                $sqlSelect = 'SELECT gibbonYearGroupID, name FROM gibbonYearGroup ORDER BY sequenceNumber';
                                $resultSelect = $connection2->prepare($sqlSelect);
                                $resultSelect->execute($dataSelect);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
                            while ($rowSelect = $resultSelect->fetch()) {
                                $selected = '';
                                if (is_null($enrolmentCheckYearGroup)) {
                                    if ($rowSelect['gibbonYearGroupID'] == getNextYearGroupID($rowReenrol['gibbonYearGroupID'], $connection2)) {
                                        $selected = 'selected';
                                    }
                                } else {
                                    if ($rowSelect['gibbonYearGroupID'] == $enrolmentCheckYearGroup) {
                                        $selected = 'selected';
                                    }
                                }
                                echo "<option $selected value='".$rowSelect['gibbonYearGroupID']."'>".htmlPrep($rowSelect['name']).'</option>';
                            }
                            echo '</select>';
                            echo '</td>';
                            echo '<td>';
                            echo "<select name='$count-reenrol-gibbonRollGroupID' id='$count-reenrol-gibbonRollGroupID' style='float: left; width:110px'>";
                            try {
                                $dataSelect = array('gibbonSchoolYearID' => $nextYear);
                                $sqlSelect = 'SELECT gibbonRollGroupID, name FROM gibbonRollGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name';
                                $resultSelect = $connection2->prepare($sqlSelect);
                                $resultSelect->execute($dataSelect);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
                            while ($rowSelect = $resultSelect->fetch()) {
                                $selected = '';
                                if (is_null($enrolmentCheckRollGroup)) {
                                    if ($rowSelect['gibbonRollGroupID'] == $rowReenrol['gibbonRollGroupIDNext']) {
                                        $selected = 'selected';
                                    }
                                } else {
                                    if ($rowSelect['gibbonRollGroupID'] == $enrolmentCheckRollGroup) {
                                        $selected = 'selected';
                                    }
                                }
                                echo "<option $selected value='".$rowSelect['gibbonRollGroupID']."'>".htmlPrep($rowSelect['name']).'</option>';
                            }
                            echo '</select>';
                            echo '</td>';
                            echo '</tr>';
                        }
                        echo '</table>';

                        echo "<input type='hidden' name='reenrol-count' value='$count'>";
                    }
                }

				//SET FINAL YEAR STUDENTS TO LEFT
				echo '<h4>';
                echo __($guid, 'Set Final Year Students To Left');
                echo '</h4>';

                try {
                    $dataFinal = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonYearGroupID' => $lastYearGroup);
                    $sqlFinal = "SELECT gibbonPerson.gibbonPersonID, surname, preferredName, name, category FROM gibbonPerson JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' AND category='Student' AND gibbonYearGroupID=:gibbonYearGroupID ORDER BY surname, preferredName";
                    $resultFinal = $connection2->prepare($sqlFinal);
                    $resultFinal->execute($dataFinal);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($resultFinal->rowCount() < 1) {
                    echo "<div class='error'>";
                    echo __($guid, 'There are no records to display.');
                    echo '</div>';
                } else {
                    echo "<table cellspacing='0' style='width: 100%'>";
                    echo "<tr class='head'>";
                    echo '<th>';
                    echo __($guid, 'Name');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Primary Role');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Current Status');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'New Status');
                    echo '</th>';
                    echo '</tr>';

                    $count = 0;
                    $rowNum = 'odd';
                    while ($rowFinal = $resultFinal->fetch()) {
                        if ($count % 2 == 0) {
                            $rowNum = 'even';
                        } else {
                            $rowNum = 'odd';
                        }
                        ++$count;

                                //COLOR ROW BY STATUS!
                                echo "<tr class=$rowNum>";
                        echo '<td>';
                        echo "<input type='hidden' name='$count-final-gibbonPersonID' value='".$rowFinal['gibbonPersonID']."'>";
                        echo formatName('', $rowFinal['preferredName'], $rowFinal['surname'], 'Student', true);
                        echo '</td>';
                        echo '<td>';
                        echo __($guid, $rowFinal['name']);
                        echo '</td>';
                        echo '<td>';
                        echo 'Full';
                        echo '</td>';
                        echo '<td>';
                        echo "<select name='$count-final-status' id='$count-final-status' style='float: left; width:110px'>";
                        echo "<option value='Full'>".__($guid, 'Full').'</option>';
                        echo "<option selected value='Left'>".__($guid, 'Left').'</option>';
                        echo '</select>';
                        echo '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';

                    echo "<input type='hidden' name='final-count' value='$count'>";
                }

				//REGISTER NEW STAFF
				echo '<h4>';
                echo __($guid, 'Register New Staff');
                echo '</h4>';
                echo '<p>';
                echo __($guid, 'Any staff who are not registered will have their status set to "Left".');
                echo '</p>';

                try {
                    $dataRegister = array();
                    $sqlRegister = "SELECT gibbonPersonID, surname, preferredName, name, category FROM gibbonPerson JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE status='Expected' AND category='Staff' ORDER BY surname, preferredName";
                    $resultRegister = $connection2->prepare($sqlRegister);
                    $resultRegister->execute($dataRegister);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($resultRegister->rowCount() < 1) {
                    echo "<div class='error'>";
                    echo __($guid, 'There are no records to display.');
                    echo '</div>';
                } else {
                    echo "<table cellspacing='0' style='width: 100%'>";
                    echo "<tr class='head'>";
                    echo '<th>';
                    echo __($guid, 'Name');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Primary Role');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Register');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Type');
                    echo '</th>';
                    echo '<th>';
                    echo __($guid, 'Job Title');
                    echo '</th>';
                    echo '</tr>';

                    $count = 0;
                    $rowNum = 'odd';
                    while ($rowRegister = $resultRegister->fetch()) {
                        if ($count % 2 == 0) {
                            $rowNum = 'even';
                        } else {
                            $rowNum = 'odd';
                        }
                        ++$count;

                                //COLOR ROW BY STATUS!
                                echo "<tr class=$rowNum>";
                        echo '<td>';
                        echo "<input type='hidden' name='$count-register-gibbonPersonID' value='".$rowRegister['gibbonPersonID']."'>";
                        echo formatName('', $rowRegister['preferredName'], $rowRegister['surname'], 'Student', true);
                        echo '</td>';
                        echo '<td>';
                        echo __($guid, $rowRegister['name']);
                        echo '</td>';
                        echo '<td>';
                        echo "<input checked type='checkbox' name='$count-register-enrol' value='Y'>";
                        echo '</td>';
                        echo '<td>';
                        echo "<select name='$count-register-type' id='$count-register-type' style='float: left; width:110px'>";
                        echo "<option value='Teaching'>".__($guid, 'Teaching').'</option>';
                        echo "<option value='Support'>".__($guid, 'Support').'</option>';
                        echo '</select>';
                        echo '</td>';
                        echo '<td>';
                        echo "<input name='$count-register-jobTitle' id='$count-register-jobTitle' maxlength=100 value='' type='text' style='float: left; width:110px'>";
                        echo '</td>';
                        echo '</tr>';
                    }
                    echo '</table>';

                    echo "<input type='hidden' name='register-count' value='$count'>";
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

                //ADD YEAR FOLLOWING NEXT
                if (getNextSchoolYearID($nextYear, $connection2) == false) {
                    //ADD YEAR FOLLOWING NEXT
                    echo '<h4>';
                    echo sprintf(__($guid, 'Add Year Following %1$s'), $nameNext);
                    echo '</h4>';

                    $name = $_POST['nextname'];
                    $status = $_POST['next-status'];
                    $sequenceNumber = $_POST['next-sequenceNumber'];
                    $firstDay = dateConvert($guid, $_POST['nextfirstDay']);
                    $lastDay = dateConvert($guid, $_POST['nextlastDay']);

                    if ($name == '' or $status == '' or $sequenceNumber == '' or is_numeric($sequenceNumber) == false or $firstDay == '' or $lastDay == '') {
                        echo "<div class='error'>";
                        echo __($guid, 'Your request failed because your inputs were invalid.');
                        echo '</div>';
                    } else {
                        //Check unique inputs for uniqueness
                        try {
                            $data = array('name' => $name, 'sequenceNumber' => $sequenceNumber);
                            $sql = 'SELECT * FROM gibbonSchoolYear WHERE name=:name OR sequenceNumber=:sequenceNumber';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }

                        if ($result->rowCount() > 0) {
                            echo "<div class='error'>";
                            echo __($guid, 'Your request failed because your inputs were invalid.');
                            echo '</div>';
                        } else {
                            //Write to database
                            $fail = false;
                            try {
                                $data = array('name' => $name, 'status' => $status, 'sequenceNumber' => $sequenceNumber, 'firstDay' => $firstDay, 'lastDay' => $lastDay);
                                $sql = 'INSERT INTO gibbonSchoolYear SET name=:name, status=:status, sequenceNumber=:sequenceNumber, firstDay=:firstDay, lastDay=:lastDay';
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                                $fail = true;
                            }
                            if ($fail == false) {
                                echo "<div class='success'>";
                                echo __($guid, 'Your request was completed successfully.');
                                echo '</div>';
                            }
                        }
                    }
                }

                //Remember year end date of current year before advance
                $dateEnd = $_SESSION[$guid]['gibbonSchoolYearLastDay'];

                //ADVANCE SCHOOL YEAR
                echo '<h4>';
                echo __($guid, 'Advance School Year');
                echo '</h4>';

                //Write to database
                $advance = true;
                try {
                    $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                    $sql = "UPDATE gibbonSchoolYear SET status='Past' WHERE gibbonSchoolYearID=:gibbonSchoolYearID";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>";
                    echo __($guid, 'Your request failed due to a database error.');
                    echo '</div>';
                    $advance = false;
                }
                if ($advance) {
                    $advance2 = true;
                    try {
                        $data = array('gibbonSchoolYearID' => $nextYear);
                        $sql = "UPDATE gibbonSchoolYear SET status='Current' WHERE gibbonSchoolYearID=:gibbonSchoolYearID";
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        echo "<div class='error'>";
                        echo __($guid, 'Your request failed due to a database error.');
                        echo '</div>';
                        $advance2 = false;
                    }
                    if ($advance2) {
                        setCurrentSchoolYear($guid, $connection2);
                        $_SESSION[$guid]['gibbonSchoolYearIDCurrent'] = $_SESSION[$guid]['gibbonSchoolYearID'];
                        $_SESSION[$guid]['gibbonSchoolYearNameCurrent'] = $_SESSION[$guid]['gibbonSchoolYearName'];
                        $_SESSION[$guid]['gibbonSchoolYearSequenceNumberCurrent'] = $_SESSION[$guid]['gibbonSchoolYearSequenceNumber'];

                        echo "<div class='success'>";
                        echo __($guid, 'Advance was successful, you are now in a new academic year!');
                        echo '</div>';

                        //SET EXPECTED USERS TO FULL
                        echo '<h4>';
                        echo __($guid, 'Set Expected Users To Full');
                        echo '</h4>';

                        $count = null;
                        if (isset($_POST['expect-count'])) {
                            $count = $_POST['expect-count'];
                        }
                        if ($count == '') {
                            echo "<div class='error'>";
                            echo __($guid, 'Your request failed because your inputs were invalid.');
                            echo '</div>';
                        } else {
                            $success = 0;
                            for ($i = 1; $i <= $count; ++$i) {
                                $gibbonPersonID = $_POST["$i-expect-gibbonPersonID"];
                                $status = $_POST["$i-expect-status"];

                                //Write to database
                                $expected = true;
                                try {
                                    if ($status == 'Full') {
                                        $data = array('status' => $status, 'gibbonPersonID' => $gibbonPersonID, 'dateStart' => $_SESSION[$guid]['gibbonSchoolYearFirstDay']);
                                        $sql = 'UPDATE gibbonPerson SET status=:status, dateStart=:dateStart WHERE gibbonPersonID=:gibbonPersonID';
                                    } elseif ($status == 'Left' or $status == 'Expected') {
                                        $data = array('status' => $status, 'gibbonPersonID' => $gibbonPersonID);
                                        $sql = 'UPDATE gibbonPerson SET status=:status WHERE gibbonPersonID=:gibbonPersonID';
                                    }
                                    $result = $connection2->prepare($sql);
                                    $result->execute($data);
                                } catch (PDOException $e) {
                                    $expected = false;
                                }
                                if ($expected) {
                                    ++$success;
                                }
                            }

                            //Feedback result!
                            if ($success == 0) {
                                echo "<div class='error'>";
                                echo __($guid, 'Your request failed.');
                                echo '</div>';
                            } elseif ($success < $count) {
                                echo "<div class='warning'>";
                                echo sprintf(__($guid, '%1$s updates failed.'), ($count - $success));
                                echo '</div>';
                            } else {
                                echo "<div class='success'>";
                                echo __($guid, 'Your request was completed successfully.');
                                echo '</div>';
                            }
                        }

                        //ENROL NEW STUDENTS
                        echo '<h4>';
                        echo __($guid, 'Enrol New Students (Status Expected)');
                        echo '</h4>';

                        $count = null;
                        if (isset($_POST['enrol-count'])) {
                            $count = $_POST['enrol-count'];
                        }
                        if ($count == '') {
                            echo "<div class='error'>";
                            echo __($guid, 'Your request failed because your inputs were invalid.');
                            echo '</div>';
                        } else {
                            $success = 0;
                            for ($i = 1; $i <= $count; ++$i) {
                                $gibbonPersonID = $_POST["$i-enrol-gibbonPersonID"];
                                $enrol = $_POST["$i-enrol-enrol"];
                                $gibbonYearGroupID = $_POST["$i-enrol-gibbonYearGroupID"];
                                $gibbonRollGroupID = $_POST["$i-enrol-gibbonRollGroupID"];

                                //Write to database
                                if ($enrol == 'Y') {
                                    $enroled = true;
                                    try {
                                        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID, 'gibbonYearGroupID' => $gibbonYearGroupID, 'gibbonRollGroupID' => $gibbonRollGroupID);
                                        $sql = 'INSERT INTO gibbonStudentEnrolment SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonPersonID=:gibbonPersonID, gibbonYearGroupID=:gibbonYearGroupID, gibbonRollGroupID=:gibbonRollGroupID';
                                        $result = $connection2->prepare($sql);
                                        $result->execute($data);
                                    } catch (PDOException $e) {
                                        $enroled = false;
                                    }
                                    if ($enroled) {
                                        ++$success;

                                        try {
                                            $dataFamily = array('gibbonPersonID' => $gibbonPersonID);
                                            $sqlFamily = 'SELECT gibbonFamilyID FROM gibbonFamilyChild WHERE gibbonPersonID=:gibbonPersonID';
                                            $resultFamily = $connection2->prepare($sqlFamily);
                                            $resultFamily->execute($dataFamily);
                                        } catch (PDOException $e) {
                                            echo "<div class='error'>".$e->getMessage().'</div>';
                                        }
                                        while ($rowFamily = $resultFamily->fetch()) {
                                            try {
                                                $dataFamily2 = array('gibbonFamilyID' => $rowFamily['gibbonFamilyID']);
                                                $sqlFamily2 = 'SELECT gibbonPersonID FROM gibbonFamilyAdult WHERE gibbonFamilyID=:gibbonFamilyID';
                                                $resultFamily2 = $connection2->prepare($sqlFamily2);
                                                $resultFamily2->execute($dataFamily2);
                                            } catch (PDOException $e) {
                                                echo "<div class='error'>".$e->getMessage().'</div>';
                                            }
                                            while ($rowFamily2 = $resultFamily2->fetch()) {
                                                try {
                                                    $dataFamily3 = array('gibbonPersonID' => $rowFamily2['gibbonPersonID']);
                                                    $sqlFamily3 = "UPDATE gibbonPerson SET status='Full' WHERE gibbonPersonID=:gibbonPersonID";
                                                    $resultFamily3 = $connection2->prepare($sqlFamily3);
                                                    $resultFamily3->execute($dataFamily3);
                                                } catch (PDOException $e) {
                                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                                }
                                            }
                                        }
                                    }
                                } else {
                                    $ok = true;
                                    try {
                                        $data = array('gibbonPersonID' => $gibbonPersonID, 'dateEnd' => $dateEnd);
                                        $sql = "UPDATE gibbonPerson SET status='Left', dateEnd=:dateEnd WHERE gibbonPersonID=:gibbonPersonID";
                                        $result = $connection2->prepare($sql);
                                        $result->execute($data);
                                    } catch (PDOException $e) {
                                        $ok == false;
                                    }
                                    if ($ok = true) {
                                        ++$success;
                                    }
                                }
                            }

                            //Feedback result!
                            if ($success == 0) {
                                echo "<div class='error'>";
                                echo __($guid, 'Your request failed.');
                                echo '</div>';
                            } elseif ($success < $count) {
                                echo "<div class='warning'>";
                                echo sprintf(__($guid, '%1$s adds failed.'), ($count - $success));
                                echo '</div>';
                            } else {
                                echo "<div class='success'>";
                                echo __($guid, 'Your request was completed successfully.');
                                echo '</div>';
                            }
                        }

                        //ENROL NEW STUDENTS
                        echo '<h4>';
                        echo __($guid, 'Enrol New Students (Status Full)');
                        echo '</h4>';

                        $count = null;
                        if (isset($_POST['enrolFull-count'])) {
                            $count = $_POST['enrolFull-count'];
                        }
                        if ($count == '') {
                            echo "<div class='error'>";
                            echo __($guid, 'Your request failed because your inputs were invalid.');
                            echo '</div>';
                        } else {
                            $success = 0;
                            for ($i = 1; $i <= $count; ++$i) {
                                $gibbonPersonID = $_POST["$i-enrolFull-gibbonPersonID"].'<br/>';
                                $enrol = $_POST["$i-enrolFull-enrol"];
                                $gibbonYearGroupID = $_POST["$i-enrolFull-gibbonYearGroupID"];
                                $gibbonRollGroupID = $_POST["$i-enrolFull-gibbonRollGroupID"];

                                //Write to database
                                if ($enrol == 'Y') {
                                    $enroled = true;

                                    try {
                                        //Check for enrolment
                                        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID);
                                        $sql = 'SELECT * FROM gibbonStudentEnrolment WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID';
                                        $result = $connection2->prepare($sql);
                                        $result->execute($data);
                                    } catch (PDOException $e) {
                                        $enroled = false;
                                    }
                                    if ($enroled) {
                                        if ($result->rowCount() == 0) {
                                            try {
                                                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID, 'gibbonYearGroupID' => $gibbonYearGroupID, 'gibbonRollGroupID' => $gibbonRollGroupID);
                                                $sql = 'INSERT INTO gibbonStudentEnrolment SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonPersonID=:gibbonPersonID, gibbonYearGroupID=:gibbonYearGroupID, gibbonRollGroupID=:gibbonRollGroupID';
                                                $result = $connection2->prepare($sql);
                                                $result->execute($data);
                                            } catch (PDOException $e) {
                                                $enroled = false;
                                            }
                                        } elseif ($result->rowCount() == 1) {
                                            try {
                                                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID, 'gibbonYearGroupID' => $gibbonYearGroupID, 'gibbonRollGroupID' => $gibbonRollGroupID);
                                                $sql = 'UPDATE gibbonStudentEnrolment SET gibbonYearGroupID=:gibbonYearGroupID, gibbonRollGroupID=:gibbonRollGroupID WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID';
                                                $result = $connection2->prepare($sql);
                                                $result->execute($data);
                                            } catch (PDOException $e) {
                                                $enroled = false;
                                            }
                                        } else {
                                            $enroled = false;
                                        }
                                    }

                                    if ($enroled) {
                                        ++$success;
                                        try {
                                            $dataFamily = array('gibbonPersonID' => $gibbonPersonID);
                                            $sqlFamily = 'SELECT gibbonFamilyID FROM gibbonFamilyChild WHERE gibbonPersonID=:gibbonPersonID';
                                            $resultFamily = $connection2->prepare($sqlFamily);
                                            $resultFamily->execute($dataFamily);
                                        } catch (PDOException $e) {
                                            echo "<div class='error'>".$e->getMessage().'</div>';
                                        }
                                        while ($rowFamily = $resultFamily->fetch()) {
                                            try {
                                                $dataFamily2 = array('gibbonFamilyID' => $rowFamily['gibbonFamilyID']);
                                                $sqlFamily2 = 'SELECT gibbonPersonID FROM gibbonFamilyAdult WHERE gibbonFamilyID=:gibbonFamilyID';
                                                $resultFamily2 = $connection2->prepare($sqlFamily2);
                                                $resultFamily2->execute($dataFamily2);
                                            } catch (PDOException $e) {
                                                echo "<div class='error'>".$e->getMessage().'</div>';
                                            }
                                            while ($rowFamily2 = $resultFamily2->fetch()) {
                                                try {
                                                    $dataFamily3 = array('gibbonPersonID' => $rowFamily2['gibbonPersonID']);
                                                    $sqlFamily3 = "UPDATE gibbonPerson SET status='Full' WHERE gibbonPersonID=:gibbonPersonID";
                                                    $resultFamily3 = $connection2->prepare($sqlFamily3);
                                                    $resultFamily3->execute($dataFamily3);
                                                } catch (PDOException $e) {
                                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                                }
                                            }
                                        }
                                    }
                                } else {
                                    $ok = true;
                                    try {
                                        $data = array('gibbonPersonID' => $gibbonPersonID, 'dateEnd' => $dateEnd);
                                        $sql = "UPDATE gibbonPerson SET status='Left', dateEnd=:dateEnd WHERE gibbonPersonID=:gibbonPersonID";
                                        $result = $connection2->prepare($sql);
                                        $result->execute($data);
                                    } catch (PDOException $e) {
                                        $ok == false;
                                    }
                                    if ($ok = true) {
                                        ++$success;
                                    }
                                }
                            }

                            //Feedback result!
                            if ($success == 0) {
                                echo "<div class='error'>";
                                echo __($guid, 'Your request failed.');
                                echo '</div>';
                            } elseif ($success < $count) {
                                echo "<div class='warning'>";
                                echo  sprintf(__($guid, '%1$s adds failed.'), ($count - $success));
                                echo '</div>';
                            } else {
                                echo "<div class='success'>";
                                echo __($guid, 'Your request was completed successfully.');
                                echo '</div>';
                            }
                        }

                        //RE-ENROL OTHER STUDENTS
                        echo '<h4>';
                        echo __($guid, 'Re-Enrol Other Students');
                        echo '</h4>';

                        $count = null;
                        if (isset($_POST['reenrol-count'])) {
                            $count = $_POST['reenrol-count'];
                        }
                        if ($count == '') {
                            echo "<div class='error'>";
                            echo __($guid, 'Your request failed because your inputs were invalid.');
                            echo '</div>';
                        } else {
                            $success = 0;
                            for ($i = 1; $i <= $count; ++$i) {
                                $gibbonPersonID = $_POST["$i-reenrol-gibbonPersonID"];
                                $enrol = $_POST["$i-reenrol-enrol"];
                                $gibbonYearGroupID = $_POST["$i-reenrol-gibbonYearGroupID"];
                                $gibbonRollGroupID = $_POST["$i-reenrol-gibbonRollGroupID"];

                                //Write to database
                                if ($enrol == 'Y') {
                                    $reenroled = true;
                                    //Check for existing record...if exists, update
                                    try {
                                        $data = array('gibbonSchoolYearID' => $nextYear, 'gibbonPersonID' => $gibbonPersonID);
                                        $sql = 'SELECT * FROM gibbonStudentEnrolment WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID';
                                        $result = $connection2->prepare($sql);
                                        $result->execute($data);
                                    } catch (PDOException $e) {
                                        $reenroled = false;
                                        echo "<div class='error'>".$e->getMessage().'</div>';
                                    }

                                    if ($result->rowCount() != 1 and $result->rowCount() != 0) {
                                        $reenroled = false;
                                        echo "<div class='error'>".$e->getMessage().'</div>';
                                    } elseif ($result->rowCount() == 1) {
                                        try {
                                            $data2 = array('gibbonSchoolYearID' => $nextYear, 'gibbonPersonID' => $gibbonPersonID, 'gibbonYearGroupID' => $gibbonYearGroupID, 'gibbonRollGroupID' => $gibbonRollGroupID);
                                            $sql2 = 'UPDATE gibbonStudentEnrolment SET gibbonYearGroupID=:gibbonYearGroupID, gibbonRollGroupID=:gibbonRollGroupID WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID';
                                            $result2 = $connection2->prepare($sql2);
                                            $result2->execute($data2);
                                        } catch (PDOException $e) {
                                            $reenroled = false;
                                            echo "<div class='error'>".$e->getMessage().'</div>';
                                        }
                                        if ($reenroled) {
                                            ++$success;
                                        }
                                    } elseif ($result->rowCount() == 0) {
                                        //Else, write
                                        try {
                                            $data2 = array('gibbonSchoolYearID' => $nextYear, 'gibbonPersonID' => $gibbonPersonID, 'gibbonYearGroupID' => $gibbonYearGroupID, 'gibbonRollGroupID' => $gibbonRollGroupID);
                                            $sql2 = 'INSERT INTO gibbonStudentEnrolment SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonPersonID=:gibbonPersonID, gibbonYearGroupID=:gibbonYearGroupID, gibbonRollGroupID=:gibbonRollGroupID';
                                            $result2 = $connection2->prepare($sql2);
                                            $result2->execute($data2);
                                        } catch (PDOException $e) {
                                            $reenroled = false;
                                            echo "<div class='error'>".$e->getMessage().'</div>';
                                        }
                                        if ($reenroled) {
                                            ++$success;
                                        }
                                    }
                                } else {
                                    $reenroled = true;
                                    try {
                                        $data = array('gibbonPersonID' => $gibbonPersonID, 'dateEnd' => $dateEnd);
                                        $sql = "UPDATE gibbonPerson SET status='Left', dateEnd=:dateEnd WHERE gibbonPersonID=:gibbonPersonID";
                                        $result = $connection2->prepare($sql);
                                        $result->execute($data);
                                    } catch (PDOException $e) {
                                        $reenroled = false;
                                        echo "<div class='error'>".$e->getMessage().'</div>';
                                    }
                                    if ($reenroled) {
                                        ++$success;
                                    }
                                }
                            }

                            //Feedback result!
                            if ($success == 0) {
                                echo "<div class='error'>";
                                echo __($guid, 'Your request failed.');
                                echo '</div>';
                            } elseif ($success < $count) {
                                echo "<div class='warning'>";
                                echo sprintf(__($guid, '%1$s adds failed.'), ($count - $success));
                                echo '</div>';
                            } else {
                                echo "<div class='success'>";
                                echo __($guid, 'Your request was completed successfully.');
                                echo '</div>';
                            }
                        }

                        //SET FINAL YEAR STUDENTS TO LEFT
                        echo '<h4>';
                        echo __($guid, 'Set Final Year Students To Left');
                        echo '</h4>';

                        $count = null;
                        if (isset($_POST['final-count'])) {
                            $count = $_POST['final-count'];
                        }
                        if ($count == '') {
                            echo "<div class='error'>";
                            echo __($guid, 'Your request failed because your inputs were invalid.');
                            echo '</div>';
                        } else {
                            $success = 0;
                            for ($i = 1; $i <= $count; ++$i) {
                                $gibbonPersonID = $_POST["$i-final-gibbonPersonID"];
                                $status = $_POST["$i-final-status"];

                                //Write to database
                                $left = true;
                                try {
                                    $data = array('gibbonPersonID' => $gibbonPersonID, 'dateEnd' => $dateEnd, 'status' => $status);
                                    $sql = 'UPDATE gibbonPerson SET status=:status, dateEnd=:dateEnd WHERE gibbonPersonID=:gibbonPersonID';
                                    $result = $connection2->prepare($sql);
                                    $result->execute($data);
                                } catch (PDOException $e) {
                                    $left = false;
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }
                                if ($left) {
                                    ++$success;
                                }
                            }

                            //Feedback result!
                            if ($success == 0) {
                                echo "<div class='error'>";
                                echo __($guid, 'Your request failed.');
                                echo '</div>';
                            } elseif ($success < $count) {
                                echo "<div class='warning'>";
                                echo sprintf(__($guid, '%1$s updates failed.'), ($count - $success));
                                echo '</div>';
                            } else {
                                echo "<div class='success'>";
                                echo __($guid, 'Your request was completed successfully.');
                                echo '</div>';
                            }
                        }

                        //REGISTER NEW STAFF
                        echo '<h4>';
                        echo __($guid, 'Register New Staff');
                        echo '</h4>';

                        $count = null;
                        if (isset($_POST['register-count'])) {
                            $count = $_POST['register-count'];
                        }
                        if ($count == '') {
                            echo "<div class='error'>";
                            echo __($guid, 'Your request failed because your inputs were invalid.');
                            echo '</div>';
                        } else {
                            $success = 0;
                            for ($i = 1; $i <= $count; ++$i) {
                                $gibbonPersonID = $_POST["$i-register-gibbonPersonID"];
                                $enrol = $_POST["$i-register-enrol"];
                                $type = $_POST["$i-register-type"];
                                $jobTitle = $_POST["$i-register-jobTitle"];

                                //Write to database
                                if ($enrol == 'Y') {
                                    $enroled = true;
                                    //Check for existing record
                                    try {
                                        $dataCheck = array('gibbonPersonID' => $gibbonPersonID);
                                        $sqlCheck = 'SELECT * FROM gibbonStaff WHERE gibbonPersonID=:gibbonPersonID';
                                        $resultCheck = $connection2->prepare($sqlCheck);
                                        $resultCheck->execute($dataCheck);
                                    } catch (PDOException $e) {
                                        $enroled = false;
                                        echo "<div class='error'>".$e->getMessage().'</div>';
                                    }
                                    if ($resultCheck->rowCount() == 0) {
                                        try {
                                            $data = array('gibbonPersonID' => $gibbonPersonID, 'type' => $type, 'jobTitle' => $jobTitle);
                                            $sql = 'INSERT INTO gibbonStaff SET gibbonPersonID=:gibbonPersonID, type=:type, jobTitle=:jobTitle';
                                            $result = $connection2->prepare($sql);
                                            $result->execute($data);
                                        } catch (PDOException $e) {
                                            $enroled = false;
                                            echo "<div class='error'>".$e->getMessage().'</div>';
                                        }
                                        if ($enroled) {
                                            ++$success;
                                        }
                                    } elseif ($resultCheck->rowCount() == 1) {
                                        try {
                                            $data = array('gibbonPersonID' => $gibbonPersonID, 'type' => $type, 'jobTitle' => $jobTitle);
                                            $sql = 'UPDATE gibbonStaff SET type=:type, jobTitle=:jobTitle WHERE gibbonPersonID=:gibbonPersonID';
                                            $result = $connection2->prepare($sql);
                                            $result->execute($data);
                                        } catch (PDOException $e) {
                                            $enroled = false;
                                            echo "<div class='error'>".$e->getMessage().'</div>';
                                        }
                                        if ($enroled) {
                                            ++$success;
                                        }
                                    }
                                } else {
                                    $left = true;
                                    try {
                                        $data = array('gibbonPersonID' => $gibbonPersonID, 'type' => $type, 'jobTitle' => $jobTitle, 'dateEnd' => $dateEnd);
                                        $sql = "UPDATE gibbonPerson SET status='Left', dateEnd=:dateEnd WHERE gibbonPersonID=$gibbonPersonID";
                                        $result = $connection2->prepare($sql);
                                        $result->execute($data);
                                    } catch (PDOException $e) {
                                        $left = false;
                                        echo "<div class='error'>".$e->getMessage().'</div>';
                                    }
                                    if ($left) {
                                        ++$success;
                                    }
                                }
                            }

                            //Feedback result!
                            if ($success == 0) {
                                echo "<div class='error'>";
                                echo __($guid, 'Your request failed.');
                                echo '</div>';
                            } elseif ($success < $count) {
                                echo "<div class='warning'>";
                                echo sprintf(__($guid, '%1$s adds failed.'), ($count - $success));
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
    }
}
?>