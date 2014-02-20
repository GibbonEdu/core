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

if (isActionAccessible($guid, $connection2, "/modules/Markbook/markbook_view_full.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this page." ;
	print "</div>" ;
}
else {
	//Proceed!
	//Get class variable
	$gibbonCourseClassID=$_GET["gibbonCourseClassID"] ;
	if ($gibbonCourseClassID=="") {
		print "<div class='warning'>" ;
			print "Use the class listing on the right to choose a Markbook to view." ;
		print "</div>" ;
	}
	//Check existence of and access to this class.
	else {
		$alert=getAlert($connection2, 002) ;
		
		try {
			$data=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
			$sql="SELECT gibbonCourse.nameShort AS course, gibbonCourse.name AS courseName, gibbonCourseClass.nameShort AS class, gibbonYearGroupIDList FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassID=:gibbonCourseClassID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print "The selected class does not exist." ;
			print "</div>" ;	
		}
		else {
			$row=$result->fetch() ;
			$courseName=$row["courseName"] ;
			$gibbonYearGroupIDList=$row["gibbonYearGroupIDList"] ;
					
			//Print mark
			//Count number of columns
			try {
				$data=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
				$sql="SELECT * FROM gibbonMarkbookColumn WHERE gibbonCourseClassID=:gibbonCourseClassID ORDER BY complete, completeDate DESC" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			$columns=$result->rowCount() ;
			if ($columns<1) {
				print "<div class='warning'>" ;
					print "There is currently no data to view in this markbook." ;
				print "</div>" ;
			}
			else {
				$x=NULL ;
				if (isset($_GET["page"])) {
					$x=$_GET["page"] ;
				}
				if ($x=="") {
					$x=0 ;
				}
				$columnsPerPage=4 ;
				
				try {
					$data=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
					$sql="SELECT * FROM gibbonMarkbookColumn WHERE gibbonCourseClassID=:gibbonCourseClassID ORDER BY complete, completeDate DESC LIMIT " . ($x*$columnsPerPage) . ", " . $columnsPerPage ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				if ($result->rowCount()<1) {
					print "<div class='warning'>" ;
						print "The markbook cannot be displayed." ;
					print "</div>" ;
				}
				else {
					print "<div style='font-size: 90%; padding: 0px; margin: 3px 0px 0px 0px' class='linkTop'>" ;
						if ($x<=0) {
							print "Newer" ;
						}
						else {
							print "<a class='thickbox' href='" . $_SESSION[$guid]["absoluteURL"] . "/fullscreen.php?q=/modules/" . $_SESSION[$guid]["module"] . "/markbook_view_full.php&gibbonCourseClassID=$gibbonCourseClassID&width=1100&height=550&page=" . ($x-1) . "'>Newer</a>" ;
						}
						print " | " ;
						if ((($x+1)*$columnsPerPage)>=$columns) {
							print "Older" ;
						}
						else {
							print "<a class='thickbox' href='" . $_SESSION[$guid]["absoluteURL"] . "/fullscreen.php?q=/modules/" . $_SESSION[$guid]["module"] . "/markbook_view_full.php&gibbonCourseClassID=$gibbonCourseClassID&width=1100&height=550&page=" . ($x+1) . "'>Older</a>" ;
						}
					print "</div>" ;
					
					//Work out details for external assessment display
					$externalAssessment=FALSE ; 
					if (isActionAccessible($guid, $connection2, "/modules/External Assessment/externalAssessment_details.php")) {
						$gibbonYearGroupIDListArray=(explode(",", $gibbonYearGroupIDList)) ;
						if (count($gibbonYearGroupIDListArray)==1) {
							$primaryExternalAssessmentByYearGroup=unserialize(getSettingByScope($connection2, "School Admin", "primaryExternalAssessmentByYearGroup")) ;
							$primaryExternalAssessmentByYearGroup[$gibbonYearGroupIDListArray[0]] ;
							if ($primaryExternalAssessmentByYearGroup[$gibbonYearGroupIDListArray[0]]!="" AND $primaryExternalAssessmentByYearGroup[$gibbonYearGroupIDListArray[0]]!="-") {
								$gibbonExternalAssessmentID=substr($primaryExternalAssessmentByYearGroup[$gibbonYearGroupIDListArray[0]],0,strpos($primaryExternalAssessmentByYearGroup[$gibbonYearGroupIDListArray[0]],"-")) ;
								$gibbonExternalAssessmentIDCategory=substr($primaryExternalAssessmentByYearGroup[$gibbonYearGroupIDListArray[0]],(strpos($primaryExternalAssessmentByYearGroup[$gibbonYearGroupIDListArray[0]],"-")+1)) ;
								
								try {
									$dataExternalAssessment=array("gibbonExternalAssessmentID"=>$gibbonExternalAssessmentID, "category"=>$gibbonExternalAssessmentIDCategory); 
									$courseNameTokens=explode(" ", $courseName) ;
									$courseWhere=" AND (" ;
									$whereCount=1 ;
									foreach ($courseNameTokens AS $courseNameToken) {
										$dataExternalAssessment["token" . $whereCount]="%" . $courseNameToken . "%" ;
										$courseWhere.="gibbonExternalAssessmentField.name LIKE :token$whereCount OR " ;
										$whereCount++ ;
									}
									if ($whereCount<1) {
										$courseWhere="" ;
									}
									else {
										$courseWhere=substr($courseWhere,0,-4) . ")" ;
									}
									$sqlExternalAssessment="SELECT gibbonExternalAssessment.name AS assessment, gibbonExternalAssessmentField.name, gibbonExternalAssessmentFieldID, category FROM gibbonExternalAssessmentField JOIN gibbonExternalAssessment ON (gibbonExternalAssessmentField.gibbonExternalAssessmentID=gibbonExternalAssessment.gibbonExternalAssessmentID) WHERE gibbonExternalAssessmentField.gibbonExternalAssessmentID=:gibbonExternalAssessmentID AND category=:category $courseWhere" ;
									$resultExternalAssessment=$connection2->prepare($sqlExternalAssessment);
									$resultExternalAssessment->execute($dataExternalAssessment);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								if ($resultExternalAssessment->rowCount()==1) {
									$rowExternalAssessment=$resultExternalAssessment->fetch() ;
									$externalAssessment=TRUE ;
									$externalAssessmentFields=array() ;
									$externalAssessmentFields[0]=$rowExternalAssessment["gibbonExternalAssessmentFieldID"] ;
									$externalAssessmentFields[1]=$rowExternalAssessment["name"] ;
									$externalAssessmentFields[2]=$rowExternalAssessment["assessment"] ;
									$externalAssessmentFields[3]=$rowExternalAssessment["category"] ;
								}
							}
						}
					}
					
					
					print "<table class='mini' cellspacing='0' style='width: 1077px; margin: 0px 10px'>" ;
						print "<tr class='head'>" ;
							print "<th rowspan=2 style='font-size: 90%; padding: 0px 3px; width: 200px'>" ;
								print "Student" ;
							print "</th>" ;
							
							if ($externalAssessment==TRUE) {
								print "<th rowspan=2 style='width: 20px; text-align: center'>" ;
									$title=$externalAssessmentFields[2] . " | " ;
									$title.=substr($externalAssessmentFields[3], (strpos($externalAssessmentFields[3],"_")+1)) . " | " ;
									$title.=$externalAssessmentFields[1] ;
									
									//Get PAS
									$PAS=getSettingByScope($connection2, 'System', 'primaryAssessmentScale') ;
									try {
										$dataPAS=array("gibbonScaleID"=>$PAS); 
										$sqlPAS="SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID" ;
										$resultPAS=$connection2->prepare($sqlPAS);
										$resultPAS->execute($dataPAS);
									}
									catch(PDOException $e) { }
									if ($resultPAS->rowCount()==1) {
										$rowPAS=$resultPAS->fetch() ;
										$title.=" | " . $rowPAS["name"] . " Scale " ;
									}
									
									print "<div style='-webkit-transform: rotate(-90deg); -moz-transform: rotate(-90deg); -ms-transform: rotate(-90deg); -o-transform: rotate(-90deg); transform: rotate(-90deg);' title='$title'>" ;
										print "Baseline<br/>" ;
									print "</div>" ;
								print "</th>" ;
							}
							
							//Show target grade header
							print "<th rowspan=2 style='width: 20px'>" ;
								$title="Personalised attainment target grade" ;
								
								//Get PAS
								$PAS=getSettingByScope($connection2, 'System', 'primaryAssessmentScale') ;
								try {
									$dataPAS=array("gibbonScaleID"=>$PAS); 
									$sqlPAS="SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID" ;
									$resultPAS=$connection2->prepare($sqlPAS);
									$resultPAS->execute($dataPAS);
								}
								catch(PDOException $e) { }
								if ($resultPAS->rowCount()==1) {
									$rowPAS=$resultPAS->fetch() ;
									$title.=" | " . $rowPAS["name"] . " Scale " ;
								}
								
								print "<div style='-webkit-transform: rotate(-90deg); -moz-transform: rotate(-90deg); -ms-transform: rotate(-90deg); -o-transform: rotate(-90deg); transform: rotate(-90deg);' title='$title'>" ;
									print "Target<br/>" ;
								print "</div>" ;
							print "</th>" ;
								
							$columnID=array() ;
							$attainmentID=array() ;
							$effortID=array() ;
							for ($i=($columnsPerPage*$x);$i<ceil(($x+1)*$columnsPerPage);$i++) {
								if ($i<=($columns-1)) {
									$row=$result->fetch() ;
									$columnID[$i]=$row["gibbonMarkbookColumnID"];
									$attainmentID[$i]=$row["gibbonScaleIDAttainment"];
									$effortID[$i]=$row["gibbonScaleIDEffort"];
									$gibbonPlannerEntryID[$i]=$row["gibbonPlannerEntryID"] ;
									$gibbonRubricIDAttainment[$i]=$row["gibbonRubricIDAttainment"] ;
									$gibbonRubricIDEffort[$i]=$row["gibbonRubricIDEffort"] ;
									
									//WORK OUT IF THERE IS SUBMISSION
									if (is_null($row["gibbonPlannerEntryID"])==FALSE) {
										try {
											$dataSub=array("gibbonPlannerEntryID"=>$row["gibbonPlannerEntryID"]); 
											$sqlSub="SELECT * FROM gibbonPlannerEntry WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND homeworkSubmission='Y'" ;
											$resultSub=$connection2->prepare($sqlSub);
											$resultSub->execute($dataSub);
										}
										catch(PDOException $e) { 
											print "<div class='error'>" . $e->getMessage() . "</div>" ; 
										}
										$submission[$i]=FALSE ;
										if ($resultSub->rowCount()==1) {
											$submission[$i]=TRUE ;
											$rowSub=$resultSub->fetch() ;
											$homeworkDueDateTime[$i]=$rowSub["homeworkDueDateTime"] ;
											$lessonDate[$i]=$rowSub["date"] ;
										}
									}
									
									if (isset($submission[$i])) {
										if ($submission[$i]==FALSE) {
											$span=4 ;
										}
										else {
											$span=5 ;
										}
									}
									else {
										$span=5 ;
									}
									
									print "<th style='text-align: center' colspan=$span>" ;
										print "<span title='" . htmlPrep($row["description"]) . "'>" . $row["name"] . "</span><br/>" ;
										print "<span style='font-style: italic; font-weight: normal'>" ;
										$unit=getUnit($connection2, $row["gibbonUnitID"], $row["gibbonHookID"], $row["gibbonCourseClassID"]) ;
										if (isset($unit[0])) {
											print $unit[0] . "<br/>" ;
										}
										else {
											print "<br/>" ;
										}
										if ($row["completeDate"]!="") {
											print "Marked on " . dateConvertBack($guid, $row["completeDate"]) . "<br/>" ;
										}
										else {
											print "Unmarked<br/>" ;
										}
										print $row["type"] ;
										if ($row["attachment"]!="" AND file_exists($_SESSION[$guid]["absolutePath"] . "/" . $row["attachment"])) {
											print " | <a style='color: #ffffff' 'title='Download more information' href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $row["attachment"] . "'>More info</a>"; 
										}
										print "<br/>" ;
										if (isActionAccessible($guid, $connection2, "/modules/Markbook/markbook_edit.php")) {
											print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/markbook_edit_edit.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=" . $row["gibbonMarkbookColumnID"] . "'><img style='margin-top: 3px' title='Edit' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
											print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/markbook_edit_data.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=" . $row["gibbonMarkbookColumnID"] . "'><img style='margin-top: 3px' title='Enter Data' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/markbook.gif'/></a> " ;
										}
										print "</span>" ;
									print "</th>" ;
								}
							}
						print "</tr>" ;
						print "<tr class='head'>" ;
							for ($i=($columnsPerPage*$x);$i<ceil(($x+1)*$columnsPerPage);$i++) {
								if ($i<=($columns-1)) {
									print "<th style='font-size: 90%; padding:0px; text-align: center; width: 40px'>" ;
										try {
											$dataScale=array("gibbonScaleID"=>$attainmentID[$i]); 
											$sqlScale="SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID" ;
											$resultScale=$connection2->prepare($sqlScale);
											$resultScale->execute($dataScale);
										}
										catch(PDOException $e) { 
											print "<div class='error'>" . $e->getMessage() . "</div>" ; 
										}
										$scale="" ;
										if ($resultScale->rowCount()==1) {
											$rowScale=$resultScale->fetch() ;
											$scale=" - " . $rowScale["name"] ;
											if ($rowScale["usage"]!="") {
												$scale=$scale . ": " . $rowScale["usage"] ;
											}
										}
										print "<span title='Attainment$scale'>At</span>" ;
									print "</th>" ;
									print "<th style='font-size: 90%; padding:0px; text-align: center; width: 40px'>" ;
										try {
											$dataScale=array("gibbonScaleID"=>$effortID[$i]); 
											$sqlScale="SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID" ;
											$resultScale=$connection2->prepare($sqlScale);
											$resultScale->execute($dataScale);
										}
										catch(PDOException $e) { 
											print "<div class='error'>" . $e->getMessage() . "</div>" ; 
										}
										
										$scale="" ;
										if ($resultScale->rowCount()==1) {
											$rowScale=$resultScale->fetch() ;
											$scale=" - " . $rowScale["name"] ;
											if ($rowScale["usage"]!="") {
												$scale=$scale . ": " . $rowScale["usage"] ;
											}
										}
										print "<span title='Effort$scale'>Ef</span>" ;
									print "</th>" ;
									print "<th style='font-size: 90%; padding:0px; text-align: center; width: 80px'>" ;
										print "<span title='Comment'>Co</span>" ;
									print "</th>" ;
									print "<th style='text-align: center; width: 30px'>" ;
										print "<span title='Uploaded Response'>Up</span>" ;
									print "</th>" ;
									if (isset($submission[$i])) {
										if ($submission[$i]==TRUE) {
											print "<th style='font-size: 90%; padding:0px; text-align: center; width: 30px'>" ;
												print "<span title='Submitted Work'>Sub</span>" ;
											print "</th>" ;
										}
									}
								}
							}
						print "</tr>" ;
					
						$count=0;
						$rowNum="odd" ;
						
						try {
							$dataStudents=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
							$sqlStudents="SELECT title, surname, preferredName, gibbonPerson.gibbonPersonID, dateStart FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE role='Student' AND gibbonCourseClassID=:gibbonCourseClassID AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') ORDER BY surname, preferredName" ;
							$resultStudents=$connection2->prepare($sqlStudents);
							$resultStudents->execute($dataStudents);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($resultStudents->rowCount()<1) {
							print "<tr>" ;
								print "<td style='padding:0px; font-size: 90%' colspan=" . ($columns+1) . ">" ;
									print "<i>There are no records to display.</i>" ;
								print "</td>" ;
							print "</tr>" ;
						}
						else {
							while ($rowStudents=$resultStudents->fetch()) {
								if ($count%2==0) {
									$rowNum="even" ;
								}
								else {
									$rowNum="odd" ;
								}
								$count++ ;
								
								//COLOR ROW BY STATUS!
								print "<tr class=$rowNum style='padding: 0px'>" ;
									print "<td style='padding: 0px 3px; font-size: 90%; padding: 0px'>" ;
										print "<div style='padding: 0px 1px'><b><a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $rowStudents["gibbonPersonID"] . "&subpage=Markbook#" . $row["gibbonCourseClassID"] . "'>" . $count . ". " . formatName("", $rowStudents["preferredName"], $rowStudents["surname"], "Student", true) . "</a><br/></div>" ;
									print "</td>" ;
									
									if ($externalAssessment==TRUE) {
										print "<td style='text-align: center'>" ;
											try {
												$dataEntry=array("gibbonPersonID"=>$rowStudents["gibbonPersonID"], "gibbonExternalAssessmentFieldID"=>$externalAssessmentFields[0]); 
												$sqlEntry="SELECT gibbonScaleGrade.value, gibbonScaleGrade.descriptor, gibbonExternalAssessmentStudent.date FROM gibbonExternalAssessmentStudentEntry JOIN gibbonExternalAssessmentStudent ON (gibbonExternalAssessmentStudentEntry.gibbonExternalAssessmentStudentID=gibbonExternalAssessmentStudent.gibbonExternalAssessmentStudentID) JOIN gibbonScaleGrade ON (gibbonExternalAssessmentStudentEntry.gibbonScaleGradeIDPrimaryAssessmentScale=gibbonScaleGrade.gibbonScaleGradeID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonExternalAssessmentFieldID=:gibbonExternalAssessmentFieldID AND NOT gibbonScaleGradeIDPrimaryAssessmentScale='' ORDER BY date DESC" ;
												$resultEntry=$connection2->prepare($sqlEntry);
												$resultEntry->execute($dataEntry);
											}
											catch(PDOException $e) { 
												print "<div class='error'>" . $e->getMessage() . "</div>" ; 
											}
											if ($resultEntry->rowCount()>=1) {
												$rowEntry=$resultEntry->fetch() ;
												print "<a title='" . $rowEntry["descriptor"] . " | Test taken on " . dateConvertBack($guid, $rowEntry["date"]) . "' href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $rowStudents["gibbonPersonID"] . "&subpage=External Assessment'>" . $rowEntry["value"] . "</a>" ;
											}	
										print "</td>" ;
									}
									
									print "<td style='text-align: center'>" ;
										try {
											$dataEntry=array("gibbonPersonIDStudent"=>$rowStudents["gibbonPersonID"], "gibbonCourseClassID"=>$gibbonCourseClassID); 
											$sqlEntry="SELECT * FROM gibbonMarkbookTarget JOIN gibbonScaleGrade ON (gibbonMarkbookTarget.gibbonScaleGradeID=gibbonScaleGrade.gibbonScaleGradeID) WHERE gibbonPersonIDStudent=:gibbonPersonIDStudent AND gibbonCourseClassID=:gibbonCourseClassID" ;
											$resultEntry=$connection2->prepare($sqlEntry);
											$resultEntry->execute($dataEntry);
										}
										catch(PDOException $e) { 
											print "<div class='error'>" . $e->getMessage() . "</div>" ; 
										}
										if ($resultEntry->rowCount()>=1) {
											$rowEntry=$resultEntry->fetch() ;
											print $rowEntry["value"] ;
										}	
									print "</td>" ;
								
									for ($i=($columnsPerPage*$x);$i<ceil(($x+1)*$columnsPerPage);$i++) {
										if ($i<=($columns-1)) {
											try {
												$dataEntry=array("gibbonMarkbookColumnID"=>$columnID[($i)], "gibbonPersonIDStudent"=>$rowStudents["gibbonPersonID"]); 
												$sqlEntry="SELECT * FROM gibbonMarkbookEntry WHERE gibbonMarkbookColumnID=:gibbonMarkbookColumnID AND gibbonPersonIDStudent=:gibbonPersonIDStudent" ;
												$resultEntry=$connection2->prepare($sqlEntry);
												$resultEntry->execute($dataEntry);
											}
											catch(PDOException $e) { 
												print "<div class='error'>" . $e->getMessage() . "</div>" ; 
											}
											
											if ($resultEntry->rowCount()==1) {
												$rowEntry=$resultEntry->fetch() ;
												$styleAttainment="" ;
												if ($rowEntry["attainmentConcern"]=="Y") {
													$styleAttainment="style='color: #" . $alert["color"] . "; font-weight: bold; border: 2px solid #" . $alert["color"] . "; padding: 2px 4px; background-color: #" . $alert["colorBG"] . "'" ;
												}
												else if ($rowEntry["attainmentConcern"]=="P") {
													$styleAttainment="style='color: #390; font-weight: bold; border: 2px solid #390; padding: 2px 4px; background-color: #D4F6DC'" ;
												}
												print "<td style='padding: 0px 0px; font-size: 90%; text-align: center'>" ;
													print "<div $styleAttainment title='" . htmlPrep($rowEntry["attainmentDescriptor"]) . "'>" . $rowEntry["attainmentValue"] ;
													if ($gibbonRubricIDAttainment[$i]!="") {
														print "<a class='thickbox' href='" . $_SESSION[$guid]["absoluteURL"] . "/fullscreen.php?q=/modules/" . $_SESSION[$guid]["module"] . "/markbook_view_rubric.php&gibbonRubricID=" . $gibbonRubricIDAttainment[$i] . "&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=" . $columnID[$i] . "&gibbonPersonID=" . $rowStudents["gibbonPersonID"] . "&mark=FALSE&type=attainment&width=1100&height=550'><img style='margin-bottom: -3px; margin-left: 3px' title='View Rubric' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/rubric.png'/></a>" ;
													}
													print "</div>" ;
												print "</td>" ;
												$styleEffort="" ;
												if ($rowEntry["effortConcern"]=="Y") {
													$styleEffort="style='color: #" . $alert["color"] . "; font-weight: bold; border: 2px solid #" . $alert["color"] . "; padding: 2px 4px; background-color: #" . $alert["colorBG"] . "'" ;
												}
												print "<td style='padding: 0px 0px; font-size: 90%; text-align: center'>" ;
													print "<div $styleEffort title='" . htmlPrep($rowEntry["effortDescriptor"]) . "'>" . $rowEntry["effortValue"] ;
													if ($gibbonRubricIDEffort[$i]!="") {
														print "<a class='thickbox' href='" . $_SESSION[$guid]["absoluteURL"] . "/fullscreen.php?q=/modules/" . $_SESSION[$guid]["module"] . "/markbook_view_rubric.php&gibbonRubricID=" . $gibbonRubricIDEffort[$i] . "&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=" . $columnID[$i] . "&gibbonPersonID=" . $rowStudents["gibbonPersonID"] . "&mark=FALSE&type=effort&width=1100&height=550'><img style='margin-bottom: -3px; margin-left: 3px' title='View Rubric' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/rubric.png'/></a>" ;
													}
													print "</div>" ;
												print "</td>" ;
												print "<td style='padding: 0px 0px; font-size: 90%; text-align: center'>" ;
												if ($rowEntry["comment"]!="") {
													print "<span title='" . htmlPrep($rowEntry["comment"]) . "'>" . substr($rowEntry["comment"], 0, 10) . "...</span>" ;
												}
												print "</td>" ;
												print "<td style='text-align: center'>" ;
												if ($rowEntry["response"]!="") {
													print "<a title='Uploaded Response' href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $rowEntry["response"] . "'>Up</a><br/>" ;
												}
												print "</td>" ;
											}
											else {
												$span=4 ;
												print "<td style='text-align: center' colspan=$span>" ;
												print "</td>" ;
											}
											if (isset($submission[$i])) {
												if ($submission[$i]==TRUE) {
													print "<td style='padding: 0px 0px; font-size: 90%; text-align: center; width: 30px'>" ;
														try {
															$dataWork=array("gibbonPlannerEntryID"=>$gibbonPlannerEntryID[$i], "gibbonPersonID"=>$rowStudents["gibbonPersonID"]); 
															$sqlWork="SELECT * FROM gibbonPlannerEntryHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID ORDER BY count DESC" ;
															$resultWork=$connection2->prepare($sqlWork);
															$resultWork->execute($dataWork);
														}
														catch(PDOException $e) { 
															print "<div class='error'>" . $e->getMessage() . "</div>" ; 
														}
													
														if ($resultWork->rowCount()>0) {
															$rowWork=$resultWork->fetch() ;
														
															if ($rowWork["status"]=="Exemption") {
																$linkText="EX" ;
															}
															else if ($rowWork["version"]=="Final") {
																$linkText="FN" ;
															}
															else {
																$linkText="D" . $rowWork["count"] ;
															}
														
															$style="" ;
															$status="On Time" ;
															if ($rowWork["status"]=="Exemption") {
																$status="Exemption" ;
															}
															else if ($rowWork["status"]=="Late") {
																$style="style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px'" ;
																$status="Late" ;
															}
														
															if ($rowWork["type"]=="File") {
																print "<span title='" . $rowWork["version"] . ". $status. Submitted at " . substr($rowWork["timestamp"],11,5) . " on " . dateConvertBack($guid, substr($rowWork["timestamp"],0,10)) . "' $style><a href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $rowWork["location"] ."'>$linkText</a></span>" ;
															}
															else if ($rowWork["type"]=="Link") {
																print "<span title='" . $rowWork["version"] . ". $status. Submitted at " . substr($rowWork["timestamp"],11,5) . " on " . dateConvertBack($guid, substr($rowWork["timestamp"],0,10)) . "' $style><a target='_blank' href='" . $rowWork["location"] ."'>$linkText</a></span>" ;
															}
															else {
																print "<span title='$status. Recorded at " . substr($rowWork["timestamp"],11,5) . " on " . dateConvertBack($guid, substr($rowWork["timestamp"],0,10)) . "' $style>$linkText</span>" ;
															}
														}
														else {
															if (date("Y-m-d H:i:s")<$homeworkDueDateTime[$i]) {
																print "<span title='Pending'>PE</span>" ;
															}
															else {
																if ($rowStudents["dateStart"]>$lessonDate[$i]) {
																	print "<span title='Student joined school after lesson was taught.' style='color: #000; font-weight: normal; border: 2px none #ff0000; padding: 2px 4px'>NA</span>" ;
																}
																else {
																	if ($rowSub["homeworkSubmissionRequired"]=="Compulsory") {
																		print "<span title='Incomplete' style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px'>IC</span>" ;
																	}
																	else {
																		print "<span title='Not submitted online'>NA</span>" ;
																	}
																}
															}
														}
													print "</td>" ;
												}
											}
										}
									}
								print "</tr>" ;
							}
						}
					print "</table>" ;
				}
			}	
		}
	}
}		
?>