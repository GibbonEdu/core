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

@session_start() ;

if (isActionAccessible($guid, $connection2, "/modules/Formal Assessment/externalAssessment_manage_details_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/externalAssessment.php'>" . _('View All Assessments') . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/externalAssessment_details.php&gibbonPersonID=" . $_GET["gibbonPersonID"] . "'>" . _('Student Details') . "</a> > </div><div class='trailEnd'>" . _('Edit Assessment') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
	$updateReturnMessage="" ;
	$class="error" ;
	if (!($updateReturn=="")) {
		if ($updateReturn=="fail0") {
			$updateReturnMessage=_("Your request failed because you do not have access to this action.") ;	
		}
		else if ($updateReturn=="fail1") {
			$updateReturnMessage=_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($updateReturn=="fail2") {
			$updateReturnMessage=_("Your request failed due to a database error.") ;	
		}
		else if ($updateReturn=="fail3") {
			$updateReturnMessage=_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($updateReturn=="fail4") {
			$updateReturnMessage=_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($updateReturn=="success0") {
			$updateReturnMessage=_("Your request was completed successfully.") ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $updateReturnMessage;
		print "</div>" ;
	} 
	
	//Check if school year specified
	$gibbonExternalAssessmentStudentID=$_GET["gibbonExternalAssessmentStudentID"] ;
	$gibbonPersonID=$_GET["gibbonPersonID"] ;
	$search=$_GET["search"] ;
	$allStudents="" ;
	if (isset($_GET["allStudents"])) {
		$allStudents=$_GET["allStudents"] ;
	}
	if ($gibbonExternalAssessmentStudentID=="" OR $gibbonPersonID=="") {
		print "<div class='error'>" ;
			print _("You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonExternalAssessmentStudentID"=>$gibbonExternalAssessmentStudentID); 
			$sql="SELECT gibbonExternalAssessmentStudent.*, gibbonExternalAssessment.name AS assessment, gibbonExternalAssessment.allowFileUpload FROM gibbonExternalAssessmentStudent JOIN gibbonExternalAssessment ON (gibbonExternalAssessmentStudent.gibbonExternalAssessmentID=gibbonExternalAssessment.gibbonExternalAssessmentID) WHERE gibbonExternalAssessmentStudentID=:gibbonExternalAssessmentStudentID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print _("The specified record cannot be found.") ;
			print "</div>" ;
		}
		else {
			//Let's go!
			$row=$result->fetch() ;
			
			if ($search!="") {
				print "<div class='linkTop'>" ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Formal Assessment/externalAssessment_details.php&gibbonPersonID=$gibbonPersonID&search=$search&allStudents=$allStudents'>" . _('Back') . "</a>" ;				
				print "</div>" ;
			}
			?>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/externalAssessment_manage_details_editProcess.php?search=$search&allStudents=$allStudents" ?>" enctype="multipart/form-data">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr>
						<td style='width: 275px'> 
							<b><?php print _('Assessment Type') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print _('This value cannot be changed.') ?></i></span>
						</td>
						<td class="right" colspan=2>
							<input readonly name="name" id="name" maxlength=20 value="<?php print _($row["assessment"]) ?>" type="text" style="width: 300px; text-align: right">
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Date') ?> *</b><br/>
							<span style="font-size: 90%"><i>Format <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?><br/></i></span>
						</td>
						<td class="right" colspan=2>
							<input name="date" id="date" maxlength=10 value="<?php if ($row["date"]!="") { print dateConvertBack($guid, $row["date"]) ; } ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var date=new LiveValidation('date');
								date.add(Validate.Presence);
								date.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
							</script>
							 <script type="text/javascript">
								$(function() {
									$( "#date" ).datepicker();
								});
							</script>
						</td>
					</tr>
					<?php
					if ($row["allowFileUpload"]=="Y") {
						?>
						<tr>
							<td style='width: 275px'> 
								<b><?php print _('Upload File') ?></b><br/>
								<span style="font-size: 90%"><i><?php print _('Use this to attach raw data, graphical summary, etc.') ?></i><br/></span>
								<?php if ($row["attachment"]!="") { ?>
									<span style="font-size: 90%"><i><?php print _('Will overwrite existing attachment.') ?></i></span>
								<?php } ?>
							</td>
							<td class="right" colspan=2>
								<?php
								if ($row["attachment"]!="") {
									print _("Current attachment:") . " <a target='_blank' href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $row["attachment"] . "'>" . $row["attachment"] . "</a><br/><br/>" ;
								}
								?>
								<input type="file" name="file" id="file"><br/><br/>
								<?php
								//Get list of acceptable file extensions
								try {
									$dataExt=array(); 
									$sqlExt="SELECT * FROM gibbonFileExtension" ;
									$resultExt=$connection2->prepare($sqlExt);
									$resultExt->execute($dataExt);
								}
								catch(PDOException $e) { }
								$ext="" ;
								while ($rowExt=$resultExt->fetch()) {
									$ext=$ext . "'." . $rowExt["extension"] . "'," ;
								}
								?>
					
								<script type="text/javascript">
									var file=new LiveValidation('file');
									file.add( Validate.Inclusion, { within: [<?php print $ext ;?>], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
								</script>
							</td>
						</tr>
						<?php
					}	
					
					//Check for all fields
					try {
						$dataCheck=array("gibbonExternalAssessmentID"=>$row["gibbonExternalAssessmentID"]); 
						$sqlCheck="SELECT * FROM gibbonExternalAssessmentField WHERE gibbonExternalAssessmentID=:gibbonExternalAssessmentID" ;
						$resultCheck=$connection2->prepare($sqlCheck);
						$resultCheck->execute($dataCheck);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					
					while ($rowCheck=$resultCheck->fetch()) {
						try {
							$dataCheck2=array("gibbonExternalAssessmentFieldID"=>$rowCheck["gibbonExternalAssessmentFieldID"], "gibbonExternalAssessmentStudentID"=>$row["gibbonExternalAssessmentStudentID"]); 
							$sqlCheck2="SELECT * FROM gibbonExternalAssessmentStudentEntry WHERE gibbonExternalAssessmentFieldID=:gibbonExternalAssessmentFieldID AND gibbonExternalAssessmentStudentID=:gibbonExternalAssessmentStudentID" ;
							$resultCheck2=$connection2->prepare($sqlCheck2);
							$resultCheck2->execute($dataCheck2);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}

						if ($resultCheck2->rowCount()<1) {
							try {
								$dataCheck3=array("gibbonExternalAssessmentStudentID"=>$row["gibbonExternalAssessmentStudentID"], "gibbonExternalAssessmentFieldID"=>$rowCheck["gibbonExternalAssessmentFieldID"]); 
								$sqlCheck3="INSERT INTO gibbonExternalAssessmentStudentEntry SET gibbonExternalAssessmentStudentID=:gibbonExternalAssessmentStudentID, gibbonExternalAssessmentFieldID=:gibbonExternalAssessmentFieldID" ;
								$resultCheck3=$connection2->prepare($sqlCheck3);
								$resultCheck3->execute($dataCheck3);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
						}
					}
					
					try {
						$dataField=array("gibbonExternalAssessmentID"=>$row["gibbonExternalAssessmentID"], "gibbonExternalAssessmentStudentID"=>$gibbonExternalAssessmentStudentID); 
						$sqlField="SELECT gibbonExternalAssessmentStudentEntryID, gibbonExternalAssessmentField.*, gibbonScale.usage, gibbonExternalAssessmentStudentEntry.gibbonScaleGradeID, gibbonExternalAssessmentStudentEntry.gibbonScaleGradeIDPrimaryAssessmentScale FROM gibbonExternalAssessmentField JOIN gibbonScale ON (gibbonExternalAssessmentField.gibbonScaleID=gibbonScale.gibbonScaleID) LEFT JOIN gibbonExternalAssessmentStudentEntry ON (gibbonExternalAssessmentField.gibbonExternalAssessmentFieldID=gibbonExternalAssessmentStudentEntry.gibbonExternalAssessmentFieldID) WHERE gibbonExternalAssessmentID=:gibbonExternalAssessmentID AND gibbonExternalAssessmentStudentID=:gibbonExternalAssessmentStudentID ORDER BY category, gibbonExternalAssessmentField.order" ;
						$resultField=$connection2->prepare($sqlField);
						$resultField->execute($dataField);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					
					if ($resultField->rowCount()<1) {
						print "<tr class='break'>" ;
							print "<td colspan=3> " ;
								print "<div class='warning'>" ;
									print _("There are no fields in this assessment.") ;
								print "</div>" ;
							print "</td>" ;
						print "</tr>" ;
					}
					else {
						$lastCategory="" ;
						$count=0 ;
						
						while ($rowField=$resultField->fetch()) {
							if ($rowField["category"]!=$lastCategory) {
								print "<tr class='break' >" ;
									print "<td> " ;
										print "<h3>" ;
											if (strpos($rowField["category"], "_")===FALSE) {
												print $rowField["category"] ;
											}
											else {
												print substr($rowField["category"], (strpos($rowField["category"], "_")+1)) ;
											}
										print "</h3>" ;
									print "</td>" ;
									print "<td class='right'>" ;
										print "<span style='font-weight: bold'>" . _('Grade') . "</span>" ;
									print "</td>" ;
									print "<td class='right'>" ;
										print "<span style='font-weight: bold' title='" . _('Primary Assessment Scale Grade') . "'>" . _('PAS Grade') . "</span>" ;
									print "</td>" ;
								print "</tr>" ;
							}
							?>
							<tr>
								<td> 
									<span style='font-weight: bold' title='<?php print $rowField["usage"] ?>'><?php print _($rowField["name"]) ?></span><br/>
								</td>
								<td class="right">
									<input name="<?php print $count?>-gibbonExternalAssessmentStudentEntryID" id="<?php print $count?>-gibbonExternalAssessmentStudentEntryID" value="<?php print $rowField["gibbonExternalAssessmentStudentEntryID"] ?>" type="hidden">
									<select name="<?php print $count?>-gibbonScaleGradeID" id="<?php print $count?>-gibbonScaleGradeID" style="width:160px">
										<?php
										try {
											$dataSelect=array("gibbonScaleID"=>$rowField["gibbonScaleID"]); 
											$sqlSelect="SELECT * FROM gibbonScaleGrade WHERE gibbonScaleID=:gibbonScaleID AND NOT value='Incomplete' ORDER BY sequenceNumber" ;
											$resultSelect=$connection2->prepare($sqlSelect);
											$resultSelect->execute($dataSelect);
										}
										catch(PDOException $e) { 
											print "<div class='error'>" . $e->getMessage() . "</div>" ; 
										}
										print "<option value=''></option>" ;
										while ($rowSelect=$resultSelect->fetch()) {
											$descriptor="" ;
											if ($rowSelect["value"]!=$rowSelect["descriptor"]) {
												$descriptor=" - " . htmlPrep(_($rowSelect["descriptor"])) ;
											}
											$selected="" ;
											if ($rowSelect["gibbonScaleGradeID"]==$rowField["gibbonScaleGradeID"]) {
												$selected="selected" ;
											}
											print "<option $selected value='" . $rowSelect["gibbonScaleGradeID"] . "'>" . htmlPrep(_($rowSelect["value"])) . $descriptor . "</option>" ;
										}
										?>				
									</select>
								</td>
								<td class="right">
									<select name="<?php print $count?>-gibbonScaleGradeIDPAS" id="<?php print $count?>-gibbonScaleGradeIDPAS" style="width:160px">
										<?php
										try {
											$dataSelect=array("gibbonScaleID"=>$_SESSION[$guid]["primaryAssessmentScale"]); 
											$sqlSelect="SELECT * FROM gibbonScaleGrade WHERE gibbonScaleID=:gibbonScaleID AND NOT value='Incomplete' ORDER BY sequenceNumber" ;
											$resultSelect=$connection2->prepare($sqlSelect);
											$resultSelect->execute($dataSelect);
										}
										catch(PDOException $e) { 
											print "<div class='error'>" . $e->getMessage() . "</div>" ; 
										}
										print "<option value=''></option>" ;
										while ($rowSelect=$resultSelect->fetch()) {
											$descriptor="" ;
											if ($rowSelect["value"]!=$rowSelect["descriptor"]) {
												$descriptor=" - " . htmlPrep(_($rowSelect["descriptor"])) ;
											}
											$selected="" ;
											if ($rowSelect["gibbonScaleGradeID"]==$rowField["gibbonScaleGradeIDPrimaryAssessmentScale"]) {
												$selected="selected" ;
											}
											print "<option $selected value='" . $rowSelect["gibbonScaleGradeID"] . "'>" . htmlPrep(_($rowSelect["value"])) . $descriptor . "</option>" ;
										}
										?>				
									</select>
								</td>
							</tr>
							<?php
							
							$lastCategory=$rowField["category"] ;
							$count++ ;
						}
					}
					?>
					<tr>
						<td>
							<span style="font-size: 90%"><i>* <?php print _("denotes a required field") ; ?>
							<?php
							if ($row["allowFileUpload"]=="Y") {
								print getMaxUpload() ; 
							}
							?>
							</i></span>
						</td>
						<td class="right" colspan=2>
							<input name="count" id="count" value="<?php print $count ?>" type="hidden">
							<input name="gibbonPersonID" id="gibbonPersonID" value="<?php print $gibbonPersonID ?>" type="hidden">
							<input name="gibbonExternalAssessmentStudentID" id="gibbonExternalAssessmentStudentID" value="<?php print $gibbonExternalAssessmentStudentID ?>" type="hidden">
							<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
							<input type="submit" value="<?php print _("Submit") ; ?>">
						</td>
					</tr>
				</table>
			</form>
			
			<?php			
		}
	}
}
?>