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

if (isActionAccessible($guid, $connection2, "/modules/Formal Assessment/import_results.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . _('Sync Results') . "</div>" ;
	print "</div>" ;
	
	$step=NULL ;
	if (isset($_GET["step"])) {
		$step=$_GET["step"] ;
	}
	if ($step=="") {
		$step=1 ;
	}
	else if (($step!=1) AND ($step!=2)) {
		$step=1 ;
	}
	
	//STEP 1, SELECT TERM
	if ($step==1) {
		?>
		<h2>
			<?php print _('Step 1 - Select CSV Files') ?>
		</h2>
		<p>
			<?php print _('This page allows you to import external assessment results from a CSV file. The import includes one row for each student result. The system will match assessments by type and date, updating any matching results, whilst creating new results not already existing in the system.') ?><br/>
		</p>
		<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/import_results.php&step=2" ?>" enctype="multipart/form-data">
			<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
				<tr>
					<td style='width: 275px'> 
						<b><?php print _('CSV File') ?> *</b><br/>
						<span style="font-size: 90%"><i><?php print _('See Notes below for specification.') ?></i></span>
					</td>
					<td class="right">
						<input type="file" name="file" id="file" size="chars">
						<script type="text/javascript">
							var file=new LiveValidation('file');
							file.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print _('Field Delimiter') ?> *</b><br/>
					</td>
					<td class="right">
						<input type="text" style="width: 300px" name="fieldDelimiter" value="," maxlength=1>
						<script type="text/javascript">
							var fieldDelimiter=new LiveValidation('fieldDelimiter');
							fieldDelimiter.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print _('String Enclosure') ?> *</b><br/>
						<span style="font-size: 90%"><i></i></span>
					</td>
					<td class="right">
						<input type="text" style="width: 300px" name="stringEnclosure" value='"' maxlength=1>
						<script type="text/javascript">
							var stringEnclosure=new LiveValidation('stringEnclosure');
							stringEnclosure.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td>
						<span style="font-size: 90%"><i>* <?php print _("denotes a required field") ; ?></i></span>
					</td>
					<td class="right">
						<input name="gibbonSchoolYearID" id="gibbonSchoolYearID" value="<?php print $gibbonSchoolYearID ?>" type="hidden">
						<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
						<input type="submit" value="<?php print _("Submit") ; ?>">
					</td>
				</tr>
			</table>
		</form>
		
		
		
		<h4>
			<?php print _('Notes') ?>
		</h4>
		<ol>
			<li style='color: #c00; font-weight: bold'><?php print _('THE SYSTEM WILL NOT PROMPT YOU TO PROCEED, IT WILL JUST DO THE IMPORT. BACKUP YOUR DATA.') ?></li>
			<li><?php print _('You may only submit CSV files.') ?></li>
			<li><?php print _('Imports cannot be run concurrently (e.g. make sure you are the only person importing at any one time).') ?></li>
			<li><?php print _('Your import can only include users whose status is set "Expected", "Full" or "Left" (e.g. all users).') ?></li>
			<li><?php print _('The submitted file must have the following fields in the following order (* denotes required field):') ?></li> 
				<ol>
					<li><b><?php print _('Assessment Name') ?>* </b> - <?php print _('Must match value of gibbonExternalAssessment.name in database.') ?></li>
					<li><b><?php print _('Official Name') ?> *</b> - <?php print _('Must match value of gibbonPerson.officialName in database,') ?></li>
					<li><b><?php print _('Assessment Date') ?> *</b> - <?php print _('dd/mm/yyyy') ?></li>
					<li><b><?php print _('Field Name') ?> *</b> - <?php print _('Must match value of gibbonExternalAssessmentField.name in database.') ?></li>
					<li><b><?php print _('Field Name Category') ?> *</b> - <?php print _('Must match value of gibbonExternalAssessmentField.category in database, less [numeric_] prefix.') ?></li>
					<li><b><?php print _('Result') ?> *</b> - <?php print _('Must match value of gibbonScaleGrade.value in database.') ?></li>
					<li><b><?php print _('PAS Result') ?></b> - <?php print _('The Primary Assessment Scale equivalent grade.') . " " . _('Must match value of gibbonScaleGrade.value in database.') ?></li>
				</ol>
			</li>
			<li><?php print _('Do not include a header row in the CSV files.') ?></li>
		</ol>
	<?php
	}
	else if ($step==2) {
		?>
		<h2>
			<?php print _('Step 2 - Data Check & Confirm') ?>
		</h2>
		<?php
		
		//Check file type
		if (($_FILES['file']['type']!="text/csv") AND ($_FILES['file']['type']!="text/comma-separated-values") AND ($_FILES['file']['type']!="text/x-comma-separated-values") AND ($_FILES['file']['type']!="application/vnd.ms-excel") AND ($_FILES['file']['type']!="application/csv")) {
			?>
			<div class='error'>
				<?php print sprintf(_('Import cannot proceed, as the submitted file has a MIME-TYPE of %1$s, and as such does not appear to be a CSV file.'), $_FILES['file']['type']) ?><br/>
			</div>
			<?php
		}
		else if (($_POST["fieldDelimiter"]=="") OR ($_POST["stringEnclosure"]=="")) {
			?>
			<div class='error'>
				<?php print _('Import cannot proceed, as the "Field Delimiter" and/or "String Enclosure" fields have been left blank.') ?><br/>
			</div>
			<?php
		}
		else {
			$proceed=true ;
			
			//PREPARE TABLES
			print "<h4>" ;
				print _("Prepare Database Tables") ;
			print "</h4>" ;
			//Lock tables
			$lockFail=false ;
			try {
				$sql="LOCK TABLES gibbonPerson WRITE, gibbonExternalAssessment WRITE, gibbonExternalAssessmentField WRITE, gibbonExternalAssessmentStudent WRITE, gibbonExternalAssessmentStudentEntry WRITE, gibbonScale WRITE, gibbonScaleGrade WRITE" ;
				$result=$connection2->query($sql);   
			}
			catch(PDOException $e) {
				$lockFail=true ; 
				$proceed=false ;
			}
			if ($lockFail==true) {
				print "<div class='error'>" ;
					print _("The database could not be locked for use.") ;
				print "</div>" ;	
			}
			else if ($lockFail==false) {
				print "<div class='success'>" ;
					print _("The database was successfully locked.") ;
				print "</div>" ;	
			}	
			
			if ($lockFail==FALSE) {	
				//READ IN DATA
				if ($proceed==true) {
					print "<h4>" ;
						print _("File Import") ;
					print "</h4>" ;
					$importFail=false ;
					$csvFile=$_FILES['file']['tmp_name'] ;
					$handle=fopen($csvFile, "r");
					$results=array() ;
					$resultCount=0 ;
					$resultSuccessCount=0 ;
					while (($data=fgetcsv($handle, 100000, stripslashes($_POST["fieldDelimiter"]), stripslashes($_POST["stringEnclosure"]))) !==FALSE) {
						if ($data[0]!="" AND $data[1]!="" AND $data[2]!="" AND $data[3]!="" AND $data[4]!="" AND $data[5]!="" ) {
							$results[$resultSuccessCount]["assessmentName"]="" ; if (isset($data[0])) { $results[$resultSuccessCount]["assessmentName"]=$data[0] ; }
							$results[$resultSuccessCount]["officialName"]="" ; if (isset($data[1])) { $results[$resultSuccessCount]["officialName"]=$data[1] ;  }
							$results[$resultSuccessCount]["assessmentDate"]="" ; if (isset($data[2])) { $results[$resultSuccessCount]["assessmentDate"]=$data[2] ; }
							$results[$resultSuccessCount]["fieldName"]="" ; if (isset($data[3])) { $results[$resultSuccessCount]["fieldName"]=$data[3] ; }
							$results[$resultSuccessCount]["fieldCategory"]="" ; if (isset($data[4])) { $results[$resultSuccessCount]["fieldCategory"]=$data[4] ; }
							$results[$resultSuccessCount]["result"]="" ; if (isset($data[5])) { $results[$resultSuccessCount]["result"]=$data[5] ; }
							$results[$resultSuccessCount]["resultPAS"]="" ; if (isset($data[5])) { $results[$resultSuccessCount]["resultPAS"]=$data[6] ; }
							$resultSuccessCount++ ;
						}
						else {
							print "<div class='error'>" ;
								print sprintf(_('User with official Name %1$s had some information malformations.'), $data[1]) ;
							print "</div>" ;
						}
						$resultCount++ ;
					}
					fclose($handle);
					if ($resultSuccessCount==0) {
						print "<div class='error'>" ;
							print _("No useful results were detected in the import file (perhaps they did not meet minimum requirements), so the import will be aborted.") ;
						print "</div>" ;
						$proceed=false ;
					}
					else if ($resultSuccessCount<$resultCount) {
						print "<div class='error'>" ;
							print _("Some results could not be successfully read or used, so the import will be aborted.") ;
						print "</div>" ;
						$proceed=false ;
					}
					else if ($resultSuccessCount==$resultCount) {
						print "<div class='success'>" ;
							print _("All results could be read and used, so the import will proceed.") ;
						print "</div>" ;
					}
					else {
						print "<div class='error'>" ;
							print _("An unknown error occured, so the import will be aborted.") ;
						print "</div>" ;
						$proceed=false ;
					}
				}
				
				
				if ($proceed==TRUE) {
					print "<h4>" ;
						print _("Results") ;
					print "</h4>" ;
					
					$users=array() ;
					$assessments=array() ;
					$fields=array() ;
					
					//Scroll through all records
					foreach ($results AS $result) {
					
						//Turn officialName into gibbonPersonID in a db-efficient manner
						if (isset($users[$result["officialName"]])==FALSE) {
							try {
								$dataUser=array("officialName"=>$result["officialName"]); 
								$sqlUser="SELECT gibbonPersonID FROM gibbonPerson WHERE officialName=:officialName" ;
								$resultUser=$connection2->prepare($sqlUser);
								$resultUser->execute($dataUser);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" ;
									print _('Your request failed due to a database error.') ;
								print "</div>" ;
								$users[$result["officialName"]]=NULL ;
							}
							if ($resultUser->rowCount()!=1) {
								print "<div class='error'>" ;
									print sprintf(_('User with official name %1$s in import cannot be found.'), $result["officialName"]) ;
								print "</div>" ;
								$users[$result["officialName"]]=NULL ;
							}
							else {
								$rowUser=$resultUser->fetch() ;
								$users[$result["officialName"]]=$rowUser["gibbonPersonID"] ;
							}
						}
						
						//If we have gibbonPersonID, move on
						if ($users[$result["officialName"]]!="") {
							
							//Turn assessmentName into gibbonExternalAssessmentID in a db-efficient manner
							if (isset($assessments[$result["assessmentName"]])==FALSE) {
								try {
									$dataAssessment=array("assessmentName"=>$result["assessmentName"]); 
									$sqlAssessment="SELECT gibbonExternalAssessmentID FROM gibbonExternalAssessment WHERE name=:assessmentName" ;
									$resultAssessment=$connection2->prepare($sqlAssessment);
									$resultAssessment->execute($dataAssessment);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" ;
										print _('Your request failed due to a database error.') ;
									print "</div>" ;
									$assessments[$result["assessmentName"]]=NULL ;
								}
								if ($resultAssessment->rowCount()!=1) {
									print "<div class='error'>" ;
										print sprintf(_('External Assessment with name %1$s in import cannot be found.'), $result["assessmentName"]) ;
									print "</div>" ;
									$assessments[$result["assessmentName"]]=NULL ;
								}
								else {
									$rowAssessment=$resultAssessment->fetch() ;
									$assessments[$result["assessmentName"]]=$rowAssessment["gibbonExternalAssessmentID"] ;
								}
							}
							
							//If we have gibbonExternalAssessmentID, move on
							if ($assessments[$result["assessmentName"]]!="") {
									
								//Turn fieldName into gibbonExternalAssessmentFieldID in a db-efficient manner
								if (isset($fields[$result["fieldName"] . $result["fieldCategory"]])==FALSE) {
									//Check for existence of field in assessment
									try {
										$dataAssessmentField=array("gibbonExternalAssessmentID"=>$assessments[$result["assessmentName"]], "name"=>$result["fieldName"], "category"=>"%" . $result["fieldCategory"]); 
										$sqlAssessmentField="SELECT gibbonExternalAssessmentFieldID FROM gibbonExternalAssessmentField WHERE gibbonExternalAssessmentID=:gibbonExternalAssessmentID AND name=:name AND category LIKE :category" ;
										$resultAssessmentField=$connection2->prepare($sqlAssessmentField);
										$resultAssessmentField->execute($dataAssessmentField);
									}
									catch(PDOException $e) { 
										print "<div class='error'>" ;
											print _('Your request failed due to a database error.') ;
										print "</div>" ;
										$fields[$result["fieldName"] . $result["fieldCategory"]]=NULL ;
									}
									if ($resultAssessmentField->rowCount()!=1) {
										print "<div class='error'>" ;
											print sprintf(_('External Assessment field with name %1$s in import cannot be found.'), $result["fieldName"]) ;
										print "</div>" ;
										$fields[$result["fieldName"] . $result["fieldCategory"]]=NULL ;
									}
									else {
										$rowAssessmentField=$resultAssessmentField->fetch() ;
										$fields[$result["fieldName"] . $result["fieldCategory"]]=$rowAssessmentField["gibbonExternalAssessmentFieldID"] ;
									}
								}
								
									
								//If we have the field, we can proceed
								if ($fields[$result["fieldName"] . $result["fieldCategory"]]!="" ) {
									//Check for record assessment for student
									try {
										$dataAssessmentStudent=array("gibbonExternalAssessmentID"=>$assessments[$result["assessmentName"]], "gibbonPersonID"=>$users[$result["officialName"]], "date"=>dateConvert($guid, $result["assessmentDate"])); 
										$sqlAssessmentStudent="SELECT gibbonExternalAssessmentStudentID FROM gibbonExternalAssessmentStudent WHERE gibbonExternalAssessmentID=:gibbonExternalAssessmentID AND gibbonPersonID=:gibbonPersonID AND date=:date" ;
										$resultAssessmentStudent=$connection2->prepare($sqlAssessmentStudent);
										$resultAssessmentStudent->execute($dataAssessmentStudent);
									}
									catch(PDOException $e) { 
										print "<div class='error'>" ;
											print _('Your request failed due to a database error.') ;
										print "</div>" ;
									}
									if ($resultAssessmentStudent->rowCount()==1) { //Assessment exists for this student
										$rowAssessmentStudent=$resultAssessmentStudent->fetch() ;
										
										//Check for field entry for student
										try {
											$dataAssessmentStudentField=array("gibbonExternalAssessmentStudentID"=>$rowAssessmentStudent["gibbonExternalAssessmentStudentID"], "gibbonExternalAssessmentFieldID"=>$fields[$result["fieldName"] . $result["fieldCategory"]]); 
											$sqlAssessmentStudentField="SELECT gibbonExternalAssessmentStudentEntryID FROM gibbonExternalAssessmentStudentEntry WHERE gibbonExternalAssessmentStudentID=:gibbonExternalAssessmentStudentID AND gibbonExternalAssessmentFieldID=:gibbonExternalAssessmentFieldID" ;
											$resultAssessmentStudentField=$connection2->prepare($sqlAssessmentStudentField);
											$resultAssessmentStudentField->execute($dataAssessmentStudentField);
										}
										catch(PDOException $e) { 
											print "<div class='error'>" ;
												print _('Your request failed due to a database error.') ;
											print "</div>" ;
										}
										if ($resultAssessmentStudentField->rowCount()==1) { //If exists, update
											$updateFail=FALSE ;
											$rowAssessmentStudentField=$resultAssessmentStudentField->fetch() ;
											try {
												//Grade
												$dataAssessmentStudentFieldUpdate=array("gibbonExternalAssessmentStudentEntryID"=>$rowAssessmentStudentField["gibbonExternalAssessmentStudentEntryID"], "result"=>$result["result"], "gibbonExternalAssessmentFieldID"=>$fields[$result["fieldName"] . $result["fieldCategory"]]); 
												$sqlAssessmentStudentFieldUpdate="UPDATE gibbonExternalAssessmentStudentEntry SET gibbonScaleGradeID=(SELECT gibbonScaleGradeID FROM gibbonExternalAssessmentField JOIN gibbonScale ON (gibbonExternalAssessmentField.gibbonScaleID=gibbonScale.gibbonScaleID) JOIN gibbonScaleGrade ON (gibbonScaleGrade.gibbonScaleID=gibbonScale.gibbonScaleID) WHERE gibbonScaleGrade.value=:result AND gibbonExternalAssessmentFieldID=:gibbonExternalAssessmentFieldID) WHERE gibbonExternalAssessmentStudentEntryID=:gibbonExternalAssessmentStudentEntryID" ;
												$resultAssessmentStudentFieldUpdate=$connection2->prepare($sqlAssessmentStudentFieldUpdate);
												$resultAssessmentStudentFieldUpdate->execute($dataAssessmentStudentFieldUpdate);
												//Grade PAS
												if ($_SESSION[$guid]["primaryAssessmentScale"]!="") {
													$dataAssessmentStudentFieldUpdate=array("gibbonExternalAssessmentStudentEntryID"=>$rowAssessmentStudentField["gibbonExternalAssessmentStudentEntryID"], "result"=>$result["resultPAS"], "gibbonScaleID"=>$_SESSION[$guid]["primaryAssessmentScale"]); 
													$sqlAssessmentStudentFieldUpdate="UPDATE gibbonExternalAssessmentStudentEntry SET gibbonScaleGradeIDPrimaryAssessmentScale=(SELECT gibbonScaleGradeID FROM gibbonScale JOIN gibbonScaleGrade ON (gibbonScaleGrade.gibbonScaleID=gibbonScale.gibbonScaleID) WHERE gibbonScaleGrade.value=:result AND gibbonScale.gibbonScaleID=:gibbonScaleID) WHERE gibbonExternalAssessmentStudentEntryID=:gibbonExternalAssessmentStudentEntryID" ;
													$resultAssessmentStudentFieldUpdate=$connection2->prepare($sqlAssessmentStudentFieldUpdate);
													$resultAssessmentStudentFieldUpdate->execute($dataAssessmentStudentFieldUpdate);
												}
											}
											catch(PDOException $e) { 
												print "<div class='error'>" ;
													print _('Your request failed due to a database error.') ;
												print "</div>" ;
												$updateFail=TRUE ;
											}
											if ($updateFail==FALSE) {
												print "<div class='success'>" ;
													print sprintf(_('%1$s %2$s grade %3$s was successfully recorded for %4$s.'), $result["assessmentName"], $result["fieldName"], $result["result"], $result["officialName"] ) ;
												print "</div>" ;
											}
										}
										else { //If not, insert
											$insertFail=FALSE ;
											try {
												//Grade
												$dataAssessmentStudentFieldUpdate=array("gibbonExternalAssessmentStudentID"=>$rowAssessmentStudent["gibbonExternalAssessmentStudentID"], "gibbonExternalAssessmentFieldID1"=>$fields[$result["fieldName"] . $result["fieldCategory"]], "result"=>$result["result"], "gibbonExternalAssessmentFieldID2"=>$fields[$result["fieldName"] . $result["fieldCategory"]]); 
												$sqlAssessmentStudentFieldUpdate="INSERT INTO gibbonExternalAssessmentStudentEntry SET gibbonScaleGradeID=(SELECT gibbonScaleGradeID FROM gibbonExternalAssessmentField JOIN gibbonScale ON (gibbonExternalAssessmentField.gibbonScaleID=gibbonScale.gibbonScaleID) JOIN gibbonScaleGrade ON (gibbonScaleGrade.gibbonScaleID=gibbonScale.gibbonScaleID) WHERE gibbonScaleGrade.value=:result AND gibbonExternalAssessmentFieldID=:gibbonExternalAssessmentFieldID1), gibbonExternalAssessmentStudentID=:gibbonExternalAssessmentStudentID, gibbonExternalAssessmentFieldID=:gibbonExternalAssessmentFieldID2" ;
												$resultAssessmentStudentFieldUpdate=$connection2->prepare($sqlAssessmentStudentFieldUpdate);
												$resultAssessmentStudentFieldUpdate->execute($dataAssessmentStudentFieldUpdate);
												//Grade PAS
												$gibbonExternalAssessmentStudentEntryID=$connection2->lastInsertID() ;
												if ($_SESSION[$guid]["primaryAssessmentScale"]!="" AND $gibbonExternalAssessmentStudentEntryID!="") {
													$dataAssessmentStudentFieldUpdate=array("gibbonExternalAssessmentStudentEntryID"=>$gibbonExternalAssessmentStudentEntryID, "result"=>$result["resultPAS"], "gibbonScaleID"=>$_SESSION[$guid]["primaryAssessmentScale"]); 
													$sqlAssessmentStudentFieldUpdate="UPDATE gibbonExternalAssessmentStudentEntry SET gibbonScaleGradeIDPrimaryAssessmentScale=(SELECT gibbonScaleGradeID FROM gibbonScale JOIN gibbonScaleGrade ON (gibbonScaleGrade.gibbonScaleID=gibbonScale.gibbonScaleID) WHERE gibbonScaleGrade.value=:result AND gibbonScale.gibbonScaleID=:gibbonScaleID) WHERE gibbonExternalAssessmentStudentEntryID=:gibbonExternalAssessmentStudentEntryID" ;
													$resultAssessmentStudentFieldUpdate=$connection2->prepare($sqlAssessmentStudentFieldUpdate);
													$resultAssessmentStudentFieldUpdate->execute($dataAssessmentStudentFieldUpdate);
												}
											}
											
											catch(PDOException $e) { 
												print "<div class='error'>" ;
													print _('Your request failed due to a database error.') ;
												print "</div>" ;
												$insertFail=TRUE ;
											}
											if ($insertFail==FALSE) {
												print "<div class='success'>" ;
													print sprintf(_('%1$s %2$s grade %3$s was successfully recorded for %4$s.'), $result["assessmentName"], $result["fieldName"], $result["result"], $result["officialName"] ) ;
												print "</div>" ;
											}
										}	
									}
									else { //Assessment does not exist for this student
										//Insert assessment
										$insertFail=FALSE ;
										try {
											$dataAssessmentStudentInsert=array("gibbonExternalAssessmentID"=>$assessments[$result["assessmentName"]], "gibbonPersonID"=>$users[$result["officialName"]], "date"=>dateConvert($guid, $result["assessmentDate"])) ; 
											$sqlAssessmentStudentInsert="INSERT INTO gibbonExternalAssessmentStudent SET gibbonExternalAssessmentID=:gibbonExternalAssessmentID, gibbonPersonID=:gibbonPersonID, date=:date" ;
											$resultAssessmentStudentInsert=$connection2->prepare($sqlAssessmentStudentInsert);
											$resultAssessmentStudentInsert->execute($dataAssessmentStudentInsert);
										}
										catch(PDOException $e) { 
											print "<div class='error'>" ;
												print _('Your request failed due to a database error.') ;
											print "</div>" ;
											$insertFail=TRUE ;
										}
										if ($insertFail==FALSE) {
											$gibbonExternalAssessmentStudentID=$connection2->lastInsertID() ;
											
											//Insert field
											if ($gibbonExternalAssessmentStudentID=="") {
												print "<div class='error'>" ;
													print _('Your request failed due to a database error.') ;
												print "</div>" ;
											}
											else {
												try {
													//Grade
													$dataAssessmentStudentFieldUpdate=array("gibbonExternalAssessmentStudentID"=>$gibbonExternalAssessmentStudentID, "gibbonExternalAssessmentFieldID1"=>$fields[$result["fieldName"] . $result["fieldCategory"]], "result"=>$result["result"], "gibbonExternalAssessmentFieldID2"=>$fields[$result["fieldName"] . $result["fieldCategory"]]); 
													$sqlAssessmentStudentFieldUpdate="INSERT INTO gibbonExternalAssessmentStudentEntry SET gibbonScaleGradeID=(SELECT gibbonScaleGradeID FROM gibbonExternalAssessmentField JOIN gibbonScale ON (gibbonExternalAssessmentField.gibbonScaleID=gibbonScale.gibbonScaleID) JOIN gibbonScaleGrade ON (gibbonScaleGrade.gibbonScaleID=gibbonScale.gibbonScaleID) WHERE gibbonScaleGrade.value=:result AND gibbonExternalAssessmentFieldID=:gibbonExternalAssessmentFieldID1), gibbonExternalAssessmentStudentID=:gibbonExternalAssessmentStudentID, gibbonExternalAssessmentFieldID=:gibbonExternalAssessmentFieldID2" ;
													$resultAssessmentStudentFieldUpdate=$connection2->prepare($sqlAssessmentStudentFieldUpdate);
													$resultAssessmentStudentFieldUpdate->execute($dataAssessmentStudentFieldUpdate);
													//Grade PAS
													$gibbonExternalAssessmentStudentEntryID=$connection2->lastInsertID() ;
													if ($_SESSION[$guid]["primaryAssessmentScale"]!="" AND $gibbonExternalAssessmentStudentEntryID!="") {
														$dataAssessmentStudentFieldUpdate=array("gibbonExternalAssessmentStudentEntryID"=>$gibbonExternalAssessmentStudentEntryID, "result"=>$result["resultPAS"], "gibbonScaleID"=>$_SESSION[$guid]["primaryAssessmentScale"]); 
														$sqlAssessmentStudentFieldUpdate="UPDATE gibbonExternalAssessmentStudentEntry SET gibbonScaleGradeIDPrimaryAssessmentScale=(SELECT gibbonScaleGradeID FROM gibbonScale JOIN gibbonScaleGrade ON (gibbonScaleGrade.gibbonScaleID=gibbonScale.gibbonScaleID) WHERE gibbonScaleGrade.value=:result AND gibbonScale.gibbonScaleID=:gibbonScaleID) WHERE gibbonExternalAssessmentStudentEntryID=:gibbonExternalAssessmentStudentEntryID" ;
														$resultAssessmentStudentFieldUpdate=$connection2->prepare($sqlAssessmentStudentFieldUpdate);
														$resultAssessmentStudentFieldUpdate->execute($dataAssessmentStudentFieldUpdate);
													}
												}
											
												catch(PDOException $e) { 
													print "<div class='error'>" ;
														print _('Your request failed due to a database error.') ;
													print "</div>" ;
													$insertFail=TRUE ;
												}
												if ($insertFail==FALSE) {
													print "<div class='success'>" ;
														print sprintf(_('%1$s %2$s grade %3$s was successfully recorded for %4$s.'), $result["assessmentName"], $result["fieldName"], $result["result"], $result["officialName"] ) ;
													print "</div>" ;
												}
											}
										}	
									}
								}
							}
						}
					}
				}
				
				//UNLOCK TABLES
				try {
					$sql="UNLOCK TABLES" ;
					$result=$connection2->query($sql);   
				}
				catch(PDOException $e) { }	
			}			
		}
	}
}
?>