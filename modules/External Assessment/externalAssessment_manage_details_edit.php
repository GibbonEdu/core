<?
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

if (isActionAccessible($guid, $connection2, "/modules/External Assessment/externalAssessment_manage_details_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/externalAssessment.php'>View All Assessments</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/externalAssessment_details.php&gibbonPersonID=" . $_GET["gibbonPersonID"] . "'>Student Details</a> > </div><div class='trailEnd'>Edit Assessment</div>" ;
	print "</div>" ;
	
	if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
	$updateReturnMessage ="" ;
	$class="error" ;
	if (!($updateReturn=="")) {
		if ($updateReturn=="fail0") {
			$updateReturnMessage =_("Your request failed because you do not have access to this action.") ;	
		}
		else if ($updateReturn=="fail1") {
			$updateReturnMessage =_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($updateReturn=="fail2") {
			$updateReturnMessage =_("Your request failed due to a database error.") ;	
		}
		else if ($updateReturn=="fail3") {
			$updateReturnMessage =_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($updateReturn=="fail4") {
			$updateReturnMessage =_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($updateReturn=="success0") {
			$updateReturnMessage =_("Your request was completed successfully.") ;	
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
	if ($gibbonExternalAssessmentStudentID=="" OR $gibbonPersonID=="") {
		print "<div class='error'>" ;
			print "You have not specified one or more required parameters." ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonExternalAssessmentStudentID"=>$gibbonExternalAssessmentStudentID); 
			$sql="SELECT gibbonExternalAssessmentStudent.*, gibbonExternalAssessment.name AS assessment FROM gibbonExternalAssessmentStudent JOIN gibbonExternalAssessment ON (gibbonExternalAssessmentStudent.gibbonExternalAssessmentID=gibbonExternalAssessment.gibbonExternalAssessmentID) WHERE gibbonExternalAssessmentStudentID=:gibbonExternalAssessmentStudentID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print "The specified assessment cannot be found." ;
			print "</div>" ;
		}
		else {
			//Let's go!
			$row=$result->fetch() ;
			
			if ($search!="") {
				print "<div class='linkTop'>" ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/External Assessment/externalAssessment_details.php&gibbonPersonID=$gibbonPersonID&search=$search'>Back</a>" ;				
				print "</div>" ;
			}
			?>
			<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/externalAssessment_manage_details_editProcess.php?search=$search" ?>">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr>
						<td> 
							<b>Assessment Type *</b><br/>
							<span style="font-size: 90%"><i>This value cannot be changed.</i></span>
						</td>
						<td class="right" colspan=2>
							<input readonly name="name" id="name" maxlength=20 value="<? print $row["assessment"] ?>" type="text" style="width: 300px; text-align: right">
						</td>
					</tr>
					<tr>
						<td> 
							<b>Date *</b><br/>
							<span style="font-size: 90%"><i>Format <? if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?><br/></i></span>
						</td>
						<td class="right" colspan=2>
							<input name="date" id="date" maxlength=10 value="<? if ($row["date"]!="") { print dateConvertBack($guid, $row["date"]) ; } ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var date=new LiveValidation('date');
								date.add(Validate.Presence);
								date.add( Validate.Format, {pattern: <? if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <? if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
							 </script>
							 <script type="text/javascript">
								$(function() {
									$( "#date" ).datepicker();
								});
							</script>
						</td>
					</tr>
				
					<?
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
						print "<div class='error'>" ;
						print _("There are no records to display.") ;
						print "</div>" ;
					}
					else {
						$lastCategory="" ;
						$count=0 ;
						
						while ($rowField=$resultField->fetch()) {
							if ($rowField["category"]!=$lastCategory) {
								print "<tr class='break' >" ;
									print "<td> " ;
										print "<h3>" . substr($rowField["category"], (strpos($rowField["category"], "_")+1)) . "</h3>" ;
									print "</td>" ;
									print "<td class='right'>" ;
										print "<span style='font-weight: bold'>Grade</span>" ;
									print "</td>" ;
									print "<td class='right'>" ;
										print "<span style='font-weight: bold' title='Primary Assessment Scale Grade'>PAS Grade</span>" ;
									print "</td>" ;
								print "</tr>" ;
							}
							?>
							<tr>
								<td> 
									<span style='font-weight: bold' title='<? print $rowField["usage"] ?>'><? print $rowField["name"] ?></span><br/>
								</td>
								<td class="right">
									<input name="<? print $count?>-gibbonExternalAssessmentStudentEntryID" id="<? print $count?>-gibbonExternalAssessmentStudentEntryID" value="<? print $rowField["gibbonExternalAssessmentStudentEntryID"] ?>" type="hidden">
									<select name="<? print $count?>-gibbonScaleGradeID" id="<? print $count?>-gibbonScaleGradeID" style="width:160px">
										<?
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
												$descriptor=" - " . htmlPrep($rowSelect["descriptor"]) ;
											}
											$selected="" ;
											if ($rowSelect["gibbonScaleGradeID"]==$rowField["gibbonScaleGradeID"]) {
												$selected="selected" ;
											}
											print "<option $selected value='" . $rowSelect["gibbonScaleGradeID"] . "'>" . htmlPrep($rowSelect["value"]) . $descriptor . "</option>" ;
										}
										?>				
									</select>
								</td>
								<td class="right">
									<select name="<? print $count?>-gibbonScaleGradeIDPAS" id="<? print $count?>-gibbonScaleGradeIDPAS" style="width:160px">
										<?
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
												$descriptor=" - " . htmlPrep($rowSelect["descriptor"]) ;
											}
											$selected="" ;
											if ($rowSelect["gibbonScaleGradeID"]==$rowField["gibbonScaleGradeIDPrimaryAssessmentScale"]) {
												$selected="selected" ;
											}
											print "<option $selected value='" . $rowSelect["gibbonScaleGradeID"] . "'>" . htmlPrep($rowSelect["value"]) . $descriptor . "</option>" ;
										}
										?>				
									</select>
								</td>
							</tr>
							<?
							
							$lastCategory=$rowField["category"] ;
							$count++ ;
						}
					}
					?>
					<tr>
						<td>
							<span style="font-size: 90%"><i>* <? print _("denotes a required field") ; ?></i></span>
						</td>
						<td class="right" colspan=2>
							<input name="count" id="count" value="<? print $count ?>" type="hidden">
							<input name="gibbonPersonID" id="gibbonPersonID" value="<? print $gibbonPersonID ?>" type="hidden">
							<input name="gibbonExternalAssessmentStudentID" id="gibbonExternalAssessmentStudentID" value="<? print $gibbonExternalAssessmentStudentID ?>" type="hidden">
							<input type="hidden" name="address" value="<? print $_SESSION[$guid]["address"] ?>">
							<input type="submit" value="<? print _("Submit") ; ?>">
						</td>
					</tr>
				</table>
			</form>
			
			<?			
		}
	}
}
?>