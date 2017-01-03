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

if (isActionAccessible($guid, $connection2, '/modules/Formal Assessment/externalAssessment_manage_details_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/externalAssessment.php'>".__($guid, 'View All Assessments')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/externalAssessment_details.php&gibbonPersonID='.$_GET['gibbonPersonID']."'>".__($guid, 'Student Details')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Assessment').'</div>';
    echo '</div>';

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Formal Assessment/externalAssessment_manage_details_edit.php&gibbonExternalAssessmentStudentID='.$_GET['editID'].'&search='.$_GET['search'].'&allStudents='.$_GET['allStudents'].'&gibbonPersonID='.$_GET['gibbonPersonID'].'&gibbonExternalAssessmentID='.$_GET['gibbonExternalAssessmentID'];
    }
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    $gibbonPersonID = $_GET['gibbonPersonID'];
    $search = $_GET['search'];
    $allStudents = '';
    if (isset($_GET['allStudents'])) {
        $allStudents = $_GET['allStudents'];
    }

    if ($gibbonPersonID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            if ($allStudents != 'on') {
                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID);
                $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonStudentEnrolment.gibbonYearGroupID, gibbonStudentEnrolmentID, surname, preferredName, title, image_240, gibbonYearGroup.name AS yearGroup, gibbonRollGroup.nameShort AS rollGroup FROM gibbonPerson, gibbonStudentEnrolment, gibbonYearGroup, gibbonRollGroup WHERE (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) AND (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) AND (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' AND gibbonPerson.gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName";
            } else {
                $data = array('gibbonPersonID' => $gibbonPersonID);
                $sql = 'SELECT DISTINCT gibbonPerson.gibbonPersonID, surname, preferredName, title, image_240, NULL AS yearGroup, NULL AS rollGroup FROM gibbonPerson, gibbonStudentEnrolment WHERE (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) AND gibbonPerson.gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName';
            }
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'There are no records to display.');
            echo '</div>';
        } else {
            if ($search != '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Formal Assessment/externalAssessment_details.php&gibbonPersonID=$gibbonPersonID&search=$search&allStudents=$allStudents'>".__($guid, 'Back').'</a>';
                echo '</div>';
            }
            $row = $result->fetch();

            echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
            echo '<tr>';
            echo "<td style='width: 34%; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Name').'</span><br/>';
            echo formatName('', $row['preferredName'], $row['surname'], 'Student');
            echo '</td>';
            echo "<td style='width: 33%; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Year Group').'</span><br/>';
            if ($row['yearGroup'] != '') {
                echo __($guid, $row['yearGroup']);
            }
            echo '</td>';
            echo "<td style='width: 34%; vertical-align: top'>";
            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Roll Group').'</span><br/>';
            echo $row['rollGroup'];
            echo '</td>';
            echo '</tr>';
            echo '</table>';

            $step = null;
            if (isset($_GET['step'])) {
                $step = $_GET['step'];
            }
            if ($step != 1 and $step != 2) {
                $step = 1;
            }

            //Step 1
            if ($step == 1) {
                ?>
				<form method="get" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/externalAssessment_manage_details_add.php' ?>">
					<table class='smallIntBorder fullWidth' cellspacing='0'>
						<tr class='break'>
							<td colspan=2>
								<h3><?php echo __($guid, 'Assessment Type') ?></h3>
							</td>
						</tr>

						<tr>
							<td style='width: 275px'>
								<b><?php echo __($guid, 'Choose Assessment') ?> *</b><br/>
							</td>
							<td class="right">
								<select class="standardWidth" name="gibbonExternalAssessmentID" id="gibbonExternalAssessmentID">
									<?php
                                    try {
                                        $dataSelect = array();
                                        $sqlSelect = "SELECT * FROM gibbonExternalAssessment WHERE active='Y' ORDER BY name";
                                        $resultSelect = $connection2->prepare($sqlSelect);
                                        $resultSelect->execute($dataSelect);
                                    } catch (PDOException $e) {
                                    }
									echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
									while ($rowSelect = $resultSelect->fetch()) {
										echo "<option id='gibbonExternalAssessmentID' value='".$rowSelect['gibbonExternalAssessmentID']."'>".htmlPrep(__($guid, $rowSelect['name'])).'</option>';
									}
									?>
								</select>
								<script type="text/javascript">
									var gibbonExternalAssessmentID=new LiveValidation('gibbonExternalAssessmentID');
									gibbonExternalAssessmentID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
								</script>
							</td>
						</tr>

						<!=- Copy CATS GCSE Targets to GCSE=->
						<script type="text/javascript">
							$(document).ready(function(){
								$("#copyToGCSE").css("display","none");


								$("#gibbonExternalAssessmentID").change(function(){
									if ($('#gibbonExternalAssessmentID').val()=="0002" ) {
										$("#copyToGCSE").slideDown("fast", $("#copyToGCSE").css("display","table-row"));
									}
									else {
										$("#copyToGCSE").css("display","none");
									}
								 });
							});
						</script>
						<tr id="copyToGCSE">
							<td>
								<b><?php echo __($guid, 'Copy Target Grades?') ?> *</b><br/>
								<span class="emphasis small"><?php echo __($guid, 'These will come from the student\'s last CAT test.') ?></span>
							</td>
							<td class="right">
								<input type="checkbox" name="copyToGCSECheck" id="copyToGCSECheck"><br/><br/>
							</td>
						</tr>

						<!=- Use GCSE Grades to create IB=->
						<script type="text/javascript">
							$(document).ready(function(){
								$("#copyToIB").css("display","none");

								$("#gibbonExternalAssessmentID").change(function(){
									if ($('#gibbonExternalAssessmentID').val()=="0003" ) {
										$("#copyToIB").slideDown("fast", $("#copyToIB").css("display","table-row"));
									}
									else {
										$("#copyToIB").css("display","none");
									}
								 });
							});
						</script>
						<tr id="copyToIB">
							<td>
								<b><?php echo __($guid, 'Create Target Grades?') ?> *</b><br/>
								<span class="emphasis small"><?php echo __($guid, 'These will be calculated from the student\'s GCSE grades.') ?></span>
							</td>
							<td class="right">
								<select class="standardWidth" name="copyToIBCheck" id="copyToIBCheck">
									<option value=''></option>
									<option value='Target'><?php echo __($guid, 'From GCSE Target Grades') ?></option>
									<option value='Final'>From <?php echo __($guid, 'GCSE Final Grades') ?></option>
								</select>
							</td>
						</tr>

						<tr>
							<td>
								<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
							</td>
							<td class="right">
								<input type="hidden" name="step" value="2">
								<input type="hidden" name="search" value="<?php echo $search ?>">
								<input type="hidden" name="allStudents" value="<?php echo $allStudents ?>">
								<input type="hidden" name="gibbonPersonID" value="<?php echo $gibbonPersonID ?>">
								<input type="hidden" name="q" value="<?php echo $_GET['q'] ?>">
								<input type="submit" value="Go">
							</td>
						</tr>
					</table>
				<?php

            } else {
                $gibbonExternalAssessmentID = $_GET['gibbonExternalAssessmentID'];
                $copyToGCSECheck = null;
                if (isset($_GET['copyToGCSECheck'])) {
                    $copyToGCSECheck = $_GET['copyToGCSECheck'];
                }
                $copyToIBCheck = null;
                if (isset($_GET['copyToIBCheck'])) {
                    $copyToIBCheck = $_GET['copyToIBCheck'];
                }

                try {
                    $dataSelect = array('gibbonExternalAssessmentID' => $gibbonExternalAssessmentID);
                    $sqlSelect = "SELECT * FROM gibbonExternalAssessment WHERE active='Y' AND gibbonExternalAssessmentID=:gibbonExternalAssessmentID ORDER BY name";
                    $resultSelect = $connection2->prepare($sqlSelect);
                    $resultSelect->execute($dataSelect);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($resultSelect->rowCount() != 1) {
                    echo "<div class='error'>";
                    echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                    echo '</div>';
                } else {
                    $rowSelect = $resultSelect->fetch();

                    //Attempt to get CATs grades to copy to GCSE target
                    if ($copyToGCSECheck == 'on') {
                        $grades = array();
                        try {
                            $dataCopy = array('gibbonPersonID' => $gibbonPersonID);
                            $sqlCopy = "SELECT * FROM gibbonExternalAssessment JOIN gibbonExternalAssessmentStudent ON (gibbonExternalAssessmentStudent.gibbonExternalAssessmentID=gibbonExternalAssessment.gibbonExternalAssessmentID) WHERE name='Cognitive Abilities Test' AND gibbonPersonID=:gibbonPersonID ORDER BY date DESC";
                            $resultCopy = $connection2->prepare($sqlCopy);
                            $resultCopy->execute($dataCopy);
                        } catch (PDOException $e) {
                        }
                        if ($resultCopy->rowCount() > 0) {
                            $rowCopy = $resultCopy->fetch();
                            try {
                                $dataCopy2 = array('category' => '%GCSE Target Grades', 'gibbonExternalAssessmentStudentID' => $rowCopy['gibbonExternalAssessmentStudentID']);
                                $sqlCopy2 = 'SELECT * FROM gibbonExternalAssessmentStudentEntry JOIN gibbonExternalAssessmentField ON (gibbonExternalAssessmentStudentEntry.gibbonExternalAssessmentFieldID=gibbonExternalAssessmentField.gibbonExternalAssessmentFieldID) WHERE category LIKE :category AND gibbonExternalAssessmentStudentID=:gibbonExternalAssessmentStudentID AND NOT (gibbonScaleGradeID IS NULL) ORDER BY name';
                                $resultCopy2 = $connection2->prepare($sqlCopy2);
                                $resultCopy2->execute($dataCopy2);
                            } catch (PDOException $e) {
                            }
                            while ($rowCopy2 = $resultCopy2->fetch()) {
                                $grades[$rowCopy2['name']][0] = $rowCopy2['gibbonScaleGradeID'];
                            }
                        }
                    }

                    //Attempt to get GCSE grades to copy to IB target
                    if ($copyToIBCheck == 'Target' or $copyToIBCheck == 'Final') {
                        $grades = array();
                        $count = 0;
                        $countWeighted = 0;
                        $total = 0;
                        try {
                            $dataCopy = array('gibbonPersonID' => $gibbonPersonID);
                            $sqlCopy = "SELECT * FROM gibbonExternalAssessment JOIN gibbonExternalAssessmentStudent ON (gibbonExternalAssessmentStudent.gibbonExternalAssessmentID=gibbonExternalAssessment.gibbonExternalAssessmentID) WHERE name='GCSE/iGCSE' AND gibbonPersonID=:gibbonPersonID ORDER BY date DESC";
                            $resultCopy = $connection2->prepare($sqlCopy);
                            $resultCopy->execute($dataCopy);
                        } catch (PDOException $e) {
                        }

                        if ($resultCopy->rowCount() > 0) {
                            $rowCopy = $resultCopy->fetch();
                            try {
                                $dataCopy2 = array('gibbonExternalAssessmentStudentID' => $rowCopy['gibbonExternalAssessmentStudentID']);
                                if ($copyToIBCheck == 'Target') {
                                    $sqlCopy2 = "SELECT * FROM gibbonExternalAssessmentStudentEntry JOIN gibbonExternalAssessmentField ON (gibbonExternalAssessmentStudentEntry.gibbonExternalAssessmentFieldID=gibbonExternalAssessmentField.gibbonExternalAssessmentFieldID) JOIN gibbonScaleGrade ON (gibbonExternalAssessmentStudentEntry.gibbonScaleGradeID=gibbonScaleGrade.gibbonScaleGradeID) WHERE category LIKE '%Target Grade' AND gibbonExternalAssessmentStudentID=:gibbonExternalAssessmentStudentID AND NOT (gibbonExternalAssessmentStudentEntry.gibbonScaleGradeID IS NULL) ORDER BY name";
                                } elseif ($copyToIBCheck == 'Final') {
                                    $sqlCopy2 = "SELECT * FROM gibbonExternalAssessmentStudentEntry JOIN gibbonExternalAssessmentField ON (gibbonExternalAssessmentStudentEntry.gibbonExternalAssessmentFieldID=gibbonExternalAssessmentField.gibbonExternalAssessmentFieldID) JOIN gibbonScaleGrade ON (gibbonExternalAssessmentStudentEntry.gibbonScaleGradeID=gibbonScaleGrade.gibbonScaleGradeID) WHERE category LIKE '%Final Grade' AND gibbonExternalAssessmentStudentID=:gibbonExternalAssessmentStudentID AND NOT (gibbonExternalAssessmentStudentEntry.gibbonScaleGradeID IS NULL) ORDER BY name";
                                }
                                $resultCopy2 = $connection2->prepare($sqlCopy2);
                                $resultCopy2->execute($dataCopy2);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
                            while ($rowCopy2 = $resultCopy2->fetch()) {
                                //Conert grade to numeric value
                                $grades[$count][0] = $rowCopy2['name'];
                                if ($rowCopy2['value'] == 'A*') {
                                    $grades[$count][1] = 7;
                                } elseif ($rowCopy2['value'] == 'A') {
                                    $grades[$count][1] = 6;
                                } elseif ($rowCopy2['value'] == 'A/B' or $rowCopy2['value'] == 'B') {
                                    $grades[$count][1] = 5;
                                } elseif ($rowCopy2['value'] == 'B/C' or $rowCopy2['value'] == 'C') {
                                    $grades[$count][1] = 4;
                                } elseif ($rowCopy2['value'] == 'C/D' or $rowCopy2['value'] == 'D') {
                                    $grades[$count][1] = 3;
                                } elseif ($rowCopy2['value'] == 'D/E' or $rowCopy2['value'] == 'E') {
                                    $grades[$count][1] = 2;
                                } elseif ($rowCopy2['value'] == 'F') {
                                    $grades[$count][1] = 1;
                                }

                                ++$countWeighted;
                                $total += $grades[$count][1];
                                if (isset($grades[$count][2])) {
                                    if ($grades[$count][2] == 'Science - Double Award') {
                                        ++$countWeighted;
                                        $total += $grades[$count][1];
                                    }
                                }
                                ++$count;
                            }

                            //Calculate GCSE numeric mean
                            if ($countWeighted != 0)
                                $mean = $total / $countWeighted;
                            else
                                $mean = 0;

                            //Apply regression
                            $regression = array();
                            $regression[1][1] = 'Biology';
                            $regression[1][2] = 1.165650007;
                            $regression[1][3] = -2.25440921;
                            $regression[1][4] = round(($mean * $regression[1][2]) + $regression[1][3]);
                            $regression[2][1] = 'Business Studies';
                            $regression[2][2] = 1.130455413;
                            $regression[2][3] = -1.519358653;
                            $regression[2][4] = round(($mean * $regression[2][2]) + $regression[2][3]);
                            $regression[3][1] = 'Chemistry';
                            $regression[3][2] = 1.304881104;
                            $regression[3][3] = -3.490021815;
                            $regression[3][4] = round(($mean * $regression[3][2]) + $regression[3][3]);
                            $regression[4][1] = 'Design Technology';
                            $regression[4][2] = 1.137380235;
                            $regression[4][3] = -2.122401828;
                            $regression[4][4] = round(($mean * $regression[4][2]) + $regression[4][3]);
                            $regression[5][1] = 'Economics';
                            $regression[5][2] = 1.143439044;
                            $regression[5][3] = -1.812296114;
                            $regression[5][4] = round(($mean * $regression[5][2]) + $regression[5][3]);
                            $regression[6][1] = 'Environmental Systems and Society';
                            $regression[6][2] = 1.248948252;
                            $regression[6][3] = -2.747483754;
                            $regression[6][4] = round(($mean * $regression[6][2]) + $regression[6][3]);
                            $regression[7][1] = 'English';
                            $regression[7][2] = 0.927976158;
                            $regression[7][3] = -0.94284584;
                            $regression[7][4] = round(($mean * $regression[7][2]) + $regression[7][3]);
                            $regression[8][1] = 'Film Studies';
                            $regression[8][2] = 1.182838166;
                            $regression[8][3] = -2.360542888;
                            $regression[8][4] = round(($mean * $regression[8][2]) + $regression[8][3]);
                            $regression[9][1] = 'Food Technology';
                            $regression[9][2] = 1.152883638;
                            $regression[9][3] = -2.260685644;
                            $regression[9][4] = round(($mean * $regression[9][2]) + $regression[9][3]);
                            $regression[10][1] = 'French';
                            $regression[10][2] = 1.157342439;
                            $regression[10][3] = -2.203111522;
                            $regression[10][4] = round(($mean * $regression[10][2]) + $regression[10][3]);
                            $regression[11][1] = 'Geography';
                            $regression[11][2] = 1.202926215;
                            $regression[11][3] = -2.385292067;
                            $regression[11][4] = round(($mean * $regression[11][2]) + $regression[11][3]);
                            $regression[12][1] = 'German';
                            $regression[12][2] = 1.137380235;
                            $regression[12][3] = -2.122401828;
                            $regression[12][4] = round(($mean * $regression[12][2]) + $regression[12][3]);
                            $regression[13][1] = 'History';
                            $regression[13][2] = 1.204129207;
                            $regression[13][3] = -2.364351524;
                            $regression[13][4] = round(($mean * $regression[13][2]) + $regression[13][3]);
                            $regression[14][1] = 'Italian';
                            $regression[14][2] = 1.128043332;
                            $regression[14][3] = -1.851982229;
                            $regression[14][4] = round(($mean * $regression[14][2]) + $regression[14][3]);
                            $regression[15][1] = 'Maths Studies';
                            $regression[15][2] = 1.048269401;
                            $regression[15][3] = -0.990598742;
                            $regression[15][4] = round(($mean * $regression[15][2]) + $regression[15][3]);
                            $regression[16][1] = 'Mathematics HL';
                            $regression[16][2] = 1.395775638;
                            $regression[16][3] = -4.717945299;
                            $regression[16][4] = round(($mean * $regression[16][2]) + $regression[16][3]);
                            $regression[17][1] = 'Music';
                            $regression[17][2] = 1.124046791;
                            $regression[17][3] = -1.820212137;
                            $regression[17][4] = round(($mean * $regression[17][2]) + $regression[17][3]);
                            $regression[18][1] = 'Philosophy';
                            $regression[18][2] = 1.201966539;
                            $regression[18][3] = -2.372274051;
                            $regression[18][4] = round(($mean * $regression[18][2]) + $regression[18][3]);
                            $regression[19][1] = 'Physics';
                            $regression[19][2] = 1.343381065;
                            $regression[19][3] = -3.749028496;
                            $regression[19][4] = round(($mean * $regression[19][2]) + $regression[19][3]);
                            $regression[20][1] = 'Psychology';
                            $regression[20][2] = 1.111003966;
                            $regression[20][3] = -1.810597105;
                            $regression[20][4] = round(($mean * $regression[20][2]) + $regression[20][3]);
                            $regression[21][1] = 'Spanish';
                            $regression[21][2] = 1.164894191;
                            $regression[21][3] = -2.334848569;
                            $regression[21][4] = round(($mean * $regression[21][2]) + $regression[21][3]);
                            $regression[22][1] = 'Theatre Arts';
                            $regression[22][2] = 1.102638258;
                            $regression[22][3] = -1.81567801;
                            $regression[22][4] = round(($mean * $regression[22][2]) + $regression[22][3]);
                            $regression[23][1] = 'Visual Arts';
                            $regression[23][2] = 0.981346183;
                            $regression[23][3] = -0.747573107;
                            $regression[23][4] = round(($mean * $regression[23][2]) + $regression[23][3]);
                            $regression[24][1] = 'Mathematics SL';
                            $regression[24][2] = 1.248787179;
                            $regression[24][3] = -3.349326039;
                            $regression[24][4] = round(($mean * $regression[24][2]) + $regression[24][3]);
                            $regression[25][1] = 'World Politics';
                            $regression[25][2] = 1.076900902;
                            $regression[25][3] = -1.663846831;
                            $regression[25][4] = round(($mean * $regression[25][2]) + $regression[25][3]);
                        }
                    }

                    ?>
					<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/externalAssessment_manage_details_addProcess.php?search=$search&allStudents=$allStudents" ?>" enctype="multipart/form-data">
						<table class='smallIntBorder fullWidth' cellspacing='0'>
							<tr>
								<td style='width: 275px'>
									<b><?php echo __($guid, 'Assessment Type') ?> *</b><br/>
									<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
								</td>
								<td class="right" colspan=2>
									<input readonly name="name" id="name" maxlength=20 value="<?php echo $rowSelect['name'] ?>" type="text" style="width: 300px; text-align: right">
								</td>
							</tr>
							<tr>
								<td>
									<b><?php echo __($guid, 'Date') ?> *</b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Format:').' ';
                    if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
                        echo 'dd/mm/yyyy';
                    } else {
                        echo $_SESSION[$guid]['i18n']['dateFormat'];
                    }
                    ?><br/></span>
								</td>
								<td class="right" colspan=2>
									<input name="date" id="date" maxlength=10 value="" type="text" class="standardWidth">
									<script type="text/javascript">
										var date=new LiveValidation('date');
										date.add(Validate.Presence);
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
									</script>
									 <script type="text/javascript">
										$(function() {
											$( "#date" ).datepicker();
										});
									</script>
								</td>
							</tr>
							<?php
                            if ($rowSelect['allowFileUpload'] == 'Y') {
                                ?>
								<tr>
									<td style='width: 275px'>
										<b><?php echo __($guid, 'Upload File') ?></b><br/>
										<span class="emphasis small"><?php echo __($guid, 'Use this to attach raw data, graphical summary, etc.') ?></span>
									</td>
									<td class="right" colspan=2>
										<input type="file" name="file" id="file"><br/><br/>
										<?php
                                        //Get list of acceptable file extensions
                                        try {
                                            $dataExt = array();
                                            $sqlExt = 'SELECT * FROM gibbonFileExtension';
                                            $resultExt = $connection2->prepare($sqlExt);
                                            $resultExt->execute($dataExt);
                                        } catch (PDOException $e) {
                                        }
                                $ext = '';
                                while ($rowExt = $resultExt->fetch()) {
                                    $ext = $ext."'.".$rowExt['extension']."',";
                                }
                                ?>

										<script type="text/javascript">
											var file=new LiveValidation('file');
											file.add( Validate.Inclusion, { within: [<?php echo $ext;
                                ?>], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
										</script>
									</td>
								</tr>
								<?php

                            }

                    try {
                        $dataField = array('gibbonExternalAssessmentID' => $gibbonExternalAssessmentID);
                        $sqlField = 'SELECT gibbonExternalAssessmentField.*, gibbonScale.usage FROM gibbonExternalAssessmentField JOIN gibbonScale ON (gibbonExternalAssessmentField.gibbonScaleID=gibbonScale.gibbonScaleID) WHERE gibbonExternalAssessmentID=:gibbonExternalAssessmentID ORDER BY category, gibbonExternalAssessmentField.order';
                        $resultField = $connection2->prepare($sqlField);
                        $resultField->execute($dataField);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }

                    if ($resultField->rowCount() < 1) {
                        echo "<tr class='break'>";
                        echo '<td colspan=3> ';
                        echo "<div class='warning'>";
                        echo __($guid, 'There are no fields in this assessment.');
                        echo '</div>';
                        echo '</td>';
                        echo '</tr>';
                    } else {
                        $lastCategory = '';
                        $count = 0;

                        while ($rowField = $resultField->fetch()) {
                            if ($rowField['category'] != $lastCategory) {
                                echo "<tr class='break'>";
                                echo '<td colspan=3> ';
                                echo '<h3>';
                                if (strpos($rowField['category'], '_') === false) {
                                    echo $rowField['category'];
                                } else {
                                    echo substr($rowField['category'], (strpos($rowField['category'], '_') + 1));
                                }
                                echo '</h3>';
                                echo '</td>';
                                echo '</tr>';
                                echo '<tr>';
                                echo '<td> ';

                                echo '</td>';
                                echo "<td class='right'>";
                                echo "<span style='font-weight: bold'>".__($guid, 'Grade').'</span>';
                                echo '</td>';
                                echo '</tr>';
                            }
                            ?>
							<tr>
								<td>
									<span style='font-weight: bold' title='<?php echo $rowField['usage'] ?>'><?php echo __($guid, $rowField['name']) ?></span><br/>
								</td>
								<td class="right">
									<input name="<?php echo $count?>-gibbonExternalAssessmentFieldID" id="<?php echo $count?>-gibbonExternalAssessmentFieldID" value="<?php echo $rowField['gibbonExternalAssessmentFieldID'] ?>" type="hidden">
									<?php
										$preselectValue = null;
                                        $mode = 'id';
                                        if ($copyToGCSECheck == 'on' and $rowField['category'] == '0_Target Grade') {
                                            if (isset($grades[$rowField['name']][0]))
                                                $preselectValue = $grades[$rowField['name']][0];
                                            else
                                                $preselectValue = '';
                                        }
										if (($copyToIBCheck == 'Target' or $copyToIBCheck == 'Final') and $rowField['category'] == '0_Target Grade') {
											//Compare subject name to $regression and find entry for current subject
											foreach ($regression as $subject) {
												$match = true;
												$subjectName = explode(' ', $subject[1]);
												foreach ($subjectName as $subjectToken) {
													//General/rough match check for all subjects
													if (stripos($rowField['name'], $subjectToken) === false) {
														$match = false;
													}
													//Exact check for mathematics SL & HL
													if (stripos($rowField['name'], 'Mathematics')) {
														if ($rowField['name'] != $subject) {
															$match = false;
														}
													}
												}

												if ($match == true) {
													$preselectValue = $subject[4];
												}
											}
                                            $mode = 'value';
										}

                                        echo renderGradeScaleSelect($connection2, $guid, $rowField['gibbonScaleID'], "$count-gibbonScaleGradeID", 'id', false, '150', $mode, $preselectValue);
										?>
									</td>
								</tr>
								<?php

									    $lastCategory = $rowField['category'];
										++$count;
									}
								}
							}
							?>
						<tr>
							<td>
								<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?>
								<?php
                                if ($rowSelect['allowFileUpload'] == 'Y') {
                                    echo getMaxUpload($guid);
                                }
                				?>
								</span>
							</td>
							<td class="right" colspan=2>
								<input name="count" id="count" value="<?php echo $count ?>" type="hidden">
								<input name="gibbonPersonID" id="gibbonPersonID" value="<?php echo $gibbonPersonID ?>" type="hidden">
								<input name="gibbonExternalAssessmentID" id="gibbonExternalAssessmentID" value="<?php echo $gibbonExternalAssessmentID ?>" type="hidden">
								<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
								<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
							</td>
						</tr>
					</table>
				</form>
				<?php

            }
        }
    }
}
?>
