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

if (isActionAccessible($guid, $connection2, '/modules/Formal Assessment/externalAssessment_manage_details_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/externalAssessment.php'>".__($guid, 'View All Assessments')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/externalAssessment_details.php&gibbonPersonID='.$_GET['gibbonPersonID']."'>".__($guid, 'Student Details')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Assessment').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, array('success0' => 'Your request was completed successfully.'));
    }

    //Check if school year specified
    $gibbonExternalAssessmentStudentID = $_GET['gibbonExternalAssessmentStudentID'];
    $gibbonPersonID = $_GET['gibbonPersonID'];
    $search = $_GET['search'];
    $allStudents = '';
    if (isset($_GET['allStudents'])) {
        $allStudents = $_GET['allStudents'];
    }
    if ($gibbonExternalAssessmentStudentID == '' or $gibbonPersonID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonExternalAssessmentStudentID' => $gibbonExternalAssessmentStudentID);
            $sql = 'SELECT gibbonExternalAssessmentStudent.*, gibbonExternalAssessment.name AS assessment, gibbonExternalAssessment.allowFileUpload FROM gibbonExternalAssessmentStudent JOIN gibbonExternalAssessment ON (gibbonExternalAssessmentStudent.gibbonExternalAssessmentID=gibbonExternalAssessment.gibbonExternalAssessmentID) WHERE gibbonExternalAssessmentStudentID=:gibbonExternalAssessmentStudentID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }
        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The specified record cannot be found.');
            echo '</div>';
        } else {
            //Let's go!
            $row = $result->fetch();

            if ($search != '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Formal Assessment/externalAssessment_details.php&gibbonPersonID=$gibbonPersonID&search=$search&allStudents=$allStudents'>".__($guid, 'Back').'</a>';
                echo '</div>';
            }
            ?>
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/externalAssessment_manage_details_editProcess.php?search=$search&allStudents=$allStudents" ?>" enctype="multipart/form-data">
				<table class='smallIntBorder fullWidth' cellspacing='0'>	
					<tr>
						<td style='width: 275px'> 
							<b><?php echo __($guid, 'Assessment Type') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right" colspan=2>
							<input readonly name="name" id="name" maxlength=20 value="<?php echo __($guid, $row['assessment']) ?>" type="text" style="width: 300px; text-align: right">
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
							<input name="date" id="date" maxlength=10 value="<?php if ($row['date'] != '') {
    echo dateConvertBack($guid, $row['date']);
}
            ?>" type="text" class="standardWidth">
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
                    if ($row['allowFileUpload'] == 'Y') {
                        ?>
						<tr>
							<td style='width: 275px'> 
								<b><?php echo __($guid, 'Upload File') ?></b><br/>
								<span class="emphasis small"><?php echo __($guid, 'Use this to attach raw data, graphical summary, etc.') ?></i><br/></span>
								<?php if ($row['attachment'] != '') {
    ?>
									<span class="emphasis small"><?php echo __($guid, 'Will overwrite existing attachment.') ?></span>
								<?php 
}
                        ?>
							</td>
							<td class="right" colspan=2>
								<?php
                                if ($row['attachment'] != '') {
                                    echo __($guid, 'Current attachment:')." <a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/'.$row['attachment']."'>".$row['attachment'].'</a><br/><br/>';
                                }
                        ?>
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

                    //Check for all fields
                    try {
                        $dataCheck = array('gibbonExternalAssessmentID' => $row['gibbonExternalAssessmentID']);
                        $sqlCheck = 'SELECT * FROM gibbonExternalAssessmentField WHERE gibbonExternalAssessmentID=:gibbonExternalAssessmentID';
                        $resultCheck = $connection2->prepare($sqlCheck);
                        $resultCheck->execute($dataCheck);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }

            while ($rowCheck = $resultCheck->fetch()) {
                try {
                    $dataCheck2 = array('gibbonExternalAssessmentFieldID' => $rowCheck['gibbonExternalAssessmentFieldID'], 'gibbonExternalAssessmentStudentID' => $row['gibbonExternalAssessmentStudentID']);
                    $sqlCheck2 = 'SELECT * FROM gibbonExternalAssessmentStudentEntry WHERE gibbonExternalAssessmentFieldID=:gibbonExternalAssessmentFieldID AND gibbonExternalAssessmentStudentID=:gibbonExternalAssessmentStudentID';
                    $resultCheck2 = $connection2->prepare($sqlCheck2);
                    $resultCheck2->execute($dataCheck2);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($resultCheck2->rowCount() < 1) {
                    try {
                        $dataCheck3 = array('gibbonExternalAssessmentStudentID' => $row['gibbonExternalAssessmentStudentID'], 'gibbonExternalAssessmentFieldID' => $rowCheck['gibbonExternalAssessmentFieldID']);
                        $sqlCheck3 = 'INSERT INTO gibbonExternalAssessmentStudentEntry SET gibbonExternalAssessmentStudentID=:gibbonExternalAssessmentStudentID, gibbonExternalAssessmentFieldID=:gibbonExternalAssessmentFieldID';
                        $resultCheck3 = $connection2->prepare($sqlCheck3);
                        $resultCheck3->execute($dataCheck3);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }
                }
            }

            try {
                $dataField = array('gibbonExternalAssessmentID' => $row['gibbonExternalAssessmentID'], 'gibbonExternalAssessmentStudentID' => $gibbonExternalAssessmentStudentID);
                $sqlField = 'SELECT gibbonExternalAssessmentStudentEntryID, gibbonExternalAssessmentField.*, gibbonScale.usage, gibbonExternalAssessmentStudentEntry.gibbonScaleGradeID, gibbonExternalAssessmentStudentEntry.gibbonScaleGradeIDPrimaryAssessmentScale FROM gibbonExternalAssessmentField JOIN gibbonScale ON (gibbonExternalAssessmentField.gibbonScaleID=gibbonScale.gibbonScaleID) LEFT JOIN gibbonExternalAssessmentStudentEntry ON (gibbonExternalAssessmentField.gibbonExternalAssessmentFieldID=gibbonExternalAssessmentStudentEntry.gibbonExternalAssessmentFieldID) WHERE gibbonExternalAssessmentID=:gibbonExternalAssessmentID AND gibbonExternalAssessmentStudentID=:gibbonExternalAssessmentStudentID ORDER BY category, gibbonExternalAssessmentField.order';
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
                        echo "<tr class='break' >";
                        echo '<td> ';
                        echo '<h3>';
                        if (strpos($rowField['category'], '_') === false) {
                            echo $rowField['category'];
                        } else {
                            echo substr($rowField['category'], (strpos($rowField['category'], '_') + 1));
                        }
                        echo '</h3>';
                        echo '</td>';
                        echo "<td class='right'>";
                        echo "<span style='font-weight: bold'>".__($guid, 'Grade').'</span>';
                        echo '</td>';
                        echo "<td class='right'>";
                        echo "<span style='font-weight: bold' title='".__($guid, 'Primary Assessment Scale Grade')."'>".__($guid, 'PAS Grade').'</span>';
                        echo '</td>';
                        echo '</tr>';
                    }
                    ?>
							<tr>
								<td> 
									<span style='font-weight: bold' title='<?php echo $rowField['usage'] ?>'><?php echo __($guid, $rowField['name']) ?></span><br/>
								</td>
								<td class="right">
									<input name="<?php echo $count?>-gibbonExternalAssessmentStudentEntryID" id="<?php echo $count?>-gibbonExternalAssessmentStudentEntryID" value="<?php echo $rowField['gibbonExternalAssessmentStudentEntryID'] ?>" type="hidden">
									<?php 
                                        echo renderGradeScaleSelect($connection2, $guid, $rowField['gibbonScaleID'], "$count-gibbonScaleGradeID", 'id', false, '150', 'id', $rowField['gibbonScaleGradeID']);
                    ?>
								</td>
								<td class="right">
									<?php 
                                        echo renderGradeScaleSelect($connection2, $guid, $_SESSION[$guid]['primaryAssessmentScale'], "$count-gibbonScaleGradeIDPAS", 'id', false, '150', 'id', $rowField['gibbonScaleGradeIDPrimaryAssessmentScale']);
                    ?>
								</td>
							</tr>
							<?php

                            $lastCategory = $rowField['category'];
                    ++$count;
                }
            }
            ?>
					<tr>
						<td>
							<span class="emphasis small">* <?php echo __($guid, 'denotes a required field');
            ?>
							<?php
                            if ($row['allowFileUpload'] == 'Y') {
                                echo getMaxUpload($guid);
                            }
            				?>
							</span>
						</td>
						<td class="right" colspan=2>
							<input name="count" id="count" value="<?php echo $count ?>" type="hidden">
							<input name="gibbonPersonID" id="gibbonPersonID" value="<?php echo $gibbonPersonID ?>" type="hidden">
							<input name="gibbonExternalAssessmentStudentID" id="gibbonExternalAssessmentStudentID" value="<?php echo $gibbonExternalAssessmentStudentID ?>" type="hidden">
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
?>