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

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

//Get alternative header names
$attainmentAlternativeName=getSettingByScope($connection2, "Markbook", "attainmentAlternativeName") ;
$attainmentAlternativeNameAbrev=getSettingByScope($connection2, "Markbook", "attainmentAlternativeNameAbrev") ;
$effortAlternativeName=getSettingByScope($connection2, "Markbook", "effortAlternativeName") ;
$effortAlternativeNameAbrev=getSettingByScope($connection2, "Markbook", "effortAlternativeNameAbrev") ;

if (isActionAccessible($guid, $connection2, "/modules/Formal Assessment/internalAssessment_write.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("Your request failed because you do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Get action with highest precendence
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print _("The highest grouped action cannot be determined.") ;
		print "</div>" ;
	}
	else {
		$alert=getAlert($connection2, 002) ;
		
		//Proceed!
		//Get class variable
		$gibbonCourseClassID=NULL ;
		if (isset($_GET["gibbonCourseClassID"])) {
			$gibbonCourseClassID=$_GET["gibbonCourseClassID"] ;
		}
		if ($gibbonCourseClassID=="") {
			try {
				$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
				$sql="SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID ORDER BY course, class" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			if ($result->rowCount()>0) {
				$row=$result->fetch() ;
				$gibbonCourseClassID=$row["gibbonCourseClassID"] ;
			}
		}
		if ($gibbonCourseClassID=="") {
			print "<div class='trail'>" ;
			print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" ._('Write Internal Assessments') . "</div>" ;
			print "</div>" ;
			print "<div class='warning'>" ;
				print "Use the class listing on the right to choose a Internal Assessment to write." ;
			print "</div>" ;
		}
		//Check existence of and access to this class.
		else {
			try {
				if ($highestAction=="Write Internal Assessments_all") {
					$data=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
					$sql="SELECT gibbonCourse.nameShort AS course, gibbonCourse.name AS courseName, gibbonCourseClass.nameShort AS class, gibbonYearGroupIDList FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassID=:gibbonCourseClassID" ;
				}
				else {
					$data=array("gibbonCourseClassID"=>$gibbonCourseClassID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
					$sql="SELECT gibbonCourse.nameShort AS course, gibbonCourse.name AS courseName, gibbonCourseClass.nameShort AS class, gibbonYearGroupIDList FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID AND gibbonPersonID=:gibbonPersonID AND role='Teacher'" ;
				}
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			if ($result->rowCount()!=1) {
				print "<div class='trail'>" ;
				print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . _('Write Internal Assessments') . "</div>" ;
				print "</div>" ;
				print "<div class='error'>" ;
					print _("The specified record does not exist or you do not have access to it.") ;
				print "</div>" ;	
			}
			else {
				$row=$result->fetch() ;
				$courseName=$row["courseName"] ;
				$gibbonYearGroupIDList=$row["gibbonYearGroupIDList"] ;
				print "<div class='trail'>" ;
				print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>Write " . $row["course"] . "." . $row["class"] . " Internal Assessments</div>" ;
				print "</div>" ;
				
				if (isset($_GET["deleteReturn"])) { $deleteReturn=$_GET["deleteReturn"] ; } else { $deleteReturn="" ; }
				$deleteReturnMessage="" ;
				$class="error" ;
				if (!($deleteReturn=="")) {
					if ($deleteReturn=="success0") {
						$deleteReturnMessage=_("Your request was completed successfully.") ;		
						$class="success" ;
					}
					print "<div class='$class'>" ;
						print $deleteReturnMessage;
					print "</div>" ;
				} 
				
				//Get teacher list
				$teaching=FALSE ;
				try {
					$data=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
					$sql="SELECT gibbonPerson.gibbonPersonID, title, surname, preferredName FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE role='Teacher' AND gibbonCourseClassID=:gibbonCourseClassID ORDER BY surname, preferredName" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}

				if ($result->rowCount()>0) {
					print "<h3 style='margin-top: 0px'>" ;
						print _("Teachers") ;
					print "</h3>" ;	
					print "<ul>" ;
						while ($row=$result->fetch()) {
							print "<li>" . formatName($row["title"], $row["preferredName"], $row["surname"], "Staff") . "</li>" ;
							if ($row["gibbonPersonID"]==$_SESSION[$guid]["gibbonPersonID"]) {
								$teaching=TRUE ;
							}
						}							
					print "</ul>" ;
				}
				
				//Print marks
				print "<h3>" ;
					print _("Marks") ;
				print "</h3>" ;	
				
				//Count number of columns
				try {
					$data=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
					$sql="SELECT * FROM gibbonInternalAssessmentColumn WHERE gibbonCourseClassID=:gibbonCourseClassID ORDER BY complete, completeDate DESC" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$columns=$result->rowCount() ;
				if ($columns<1) {
					print "<div class='warning'>" ;
						print _("There are no records to display.") ;
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
					$columnsPerPage=3 ;
					$columnsThisPage=3 ;
			
					if ($columns<1) {
						print "<div class='warning'>" ;
							print _("There are no records to display.") ;
						print "</div>" ;	
					}
					else {
						if ($columns<3) {
							$columnsThisPage=$columns ;
						}
						if ($columns-($x*$columnsPerPage)<3) {
							$columnsThisPage=$columns-($x*$columnsPerPage) ;
						}
						try {
							$data=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
							$sql="SELECT * FROM gibbonInternalAssessmentColumn WHERE gibbonCourseClassID=:gibbonCourseClassID ORDER BY complete, completeDate DESC LIMIT " . ($x*$columnsPerPage) . ", " . $columnsPerPage ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						
						//Work out details for external assessment display
						$externalAssessment=FALSE ; 
						if (isActionAccessible($guid, $connection2, "/modules/External Assessment/externalAssessment_details.php")) {
							$gibbonYearGroupIDListArray=(explode(",", $gibbonYearGroupIDList)) ;
							if (count($gibbonYearGroupIDListArray)==1) {
								$primaryExternalAssessmentByYearGroup=unserialize(getSettingByScope($connection2, "School Admin", "primaryExternalAssessmentByYearGroup")) ;
								if ($primaryExternalAssessmentByYearGroup[$gibbonYearGroupIDListArray[0]]!="" AND $primaryExternalAssessmentByYearGroup[$gibbonYearGroupIDListArray[0]]!="-") {
									$gibbonExternalAssessmentID=substr($primaryExternalAssessmentByYearGroup[$gibbonYearGroupIDListArray[0]],0,strpos($primaryExternalAssessmentByYearGroup[$gibbonYearGroupIDListArray[0]],"-")) ;
									$gibbonExternalAssessmentIDCategory=substr($primaryExternalAssessmentByYearGroup[$gibbonYearGroupIDListArray[0]],(strpos($primaryExternalAssessmentByYearGroup[$gibbonYearGroupIDListArray[0]],"-")+1)) ;
								
									try {
										$dataExternalAssessment=array("gibbonExternalAssessmentID"=>$gibbonExternalAssessmentID, "category"=>$gibbonExternalAssessmentIDCategory); 
										$courseNameTokens=explode(" ", $courseName) ;
										$courseWhere=" AND (" ;
										$whereCount=1 ;
										foreach ($courseNameTokens AS $courseNameToken) {
											if (strlen($courseNameToken)>3) {
												$dataExternalAssessment["token" . $whereCount]="%" . $courseNameToken . "%" ;
												$courseWhere.="gibbonExternalAssessmentField.name LIKE :token$whereCount OR " ;
												$whereCount++ ;
											}
										}
										if ($whereCount<1) {
											$courseWhere="" ;
										}
										else {
											$courseWhere=substr($courseWhere,0,-4) . ")" ;
										}
										$sqlExternalAssessment="SELECT gibbonExternalAssessment.name AS assessment, gibbonExternalAssessmentField.name, gibbonExternalAssessmentFieldID, category FROM gibbonExternalAssessmentField JOIN gibbonExternalAssessment ON (gibbonExternalAssessmentField.gibbonExternalAssessmentID=gibbonExternalAssessment.gibbonExternalAssessmentID) WHERE gibbonExternalAssessmentField.gibbonExternalAssessmentID=:gibbonExternalAssessmentID AND category=:category $courseWhere ORDER BY name" ;
										$resultExternalAssessment=$connection2->prepare($sqlExternalAssessment);
										$resultExternalAssessment->execute($dataExternalAssessment);
									}
									catch(PDOException $e) { 
										print "<div class='error'>" . $e->getMessage() . "</div>" ; 
									}
									if ($resultExternalAssessment->rowCount()>=1) {
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
				
						//Print table header
						print "<p>" ;
							print _("To see more detail on an item (such as a comment or a grade), hover your mouse over it.") ;
							if ($externalAssessment==TRUE) {
								print " " . _('The Baseline column is populated based on student performance in external assessments, and can be used as a reference point for the grades in the Internal Assessment.') ;
							}
						print "</p>" ;
					
						print "<div class='linkTop'>" ;
							print "<div style='padding-top: 12px; margin-left: 10px; float: right'>" ;
								if ($x<=0) {
									print _("Newer") ;
								}
								else {
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Formal Assessment/internalAssessment_write.php&gibbonCourseClassID=$gibbonCourseClassID&page=" . ($x-1) . "'>" . _('Newer') . "</a>" ;
								}
								print " | " ;
								if ((($x+1)*$columnsPerPage)>=$columns) {
									print _("Older") ;
								}
								else {
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Formal Assessment/internalAssessment_write.php&gibbonCourseClassID=$gibbonCourseClassID&page=" . ($x+1) . "'>" . _('Older') . "</a>" ;
								}
							print "</div>" ;
						print "</div>" ;
			
						print "<table class='mini' cellspacing='0' style='width: 100%; margin-top: 0px'>" ;
							print "<tr class='head' style='height: 120px'>" ;
								print "<th style='width: 150px; max-width: 200px'rowspan=2>" ;
									print _("Student") ;
								print "</th>" ;
							
								//Show Baseline data header
								if ($externalAssessment==TRUE) {
									print "<th rowspan=2 style='width: 20px'>" ;
										$title=_($externalAssessmentFields[2]) . " | " ;
										$title.=_(substr($externalAssessmentFields[3], (strpos($externalAssessmentFields[3],"_")+1))) . " | " ;
										$title.=_($externalAssessmentFields[1]) ;
									
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
											$title.=" | " . $rowPAS["name"] . " " . _('Scale') . " " ;
										}
									
										print "<div style='-webkit-transform: rotate(-90deg); -moz-transform: rotate(-90deg); -ms-transform: rotate(-90deg); -o-transform: rotate(-90deg); transform: rotate(-90deg);' title='$title'>" ;
											print _("Baseline") . "<br/>" ;
										print "</div>" ;
									print "</th>" ;
								}
							
								$columnID=array() ;
								$attainmentID=array() ;
								$effortID=array() ;
								for ($i=0; $i<$columnsThisPage; $i++) {
									$row=$result->fetch() ;
									if ($row===FALSE) {
										$columnID[$i]=FALSE ;
									}
									else {
										$columnID[$i]=$row["gibbonInternalAssessmentColumnID"];
										$attainmentOn[$i]=$row["attainment"];
										$attainmentID[$i]=$row["gibbonScaleIDAttainment"];
										$effortOn[$i]=$row["effort"];
										$effortID[$i]=$row["gibbonScaleIDEffort"];
										$comment[$i]=$row["comment"];
										$uploadedResponse[$i]=$row["uploadedResponse"];
										$submission[$i]=FALSE ;
									}
								
									
									//Column count
									$span=0 ;
									$contents=TRUE ;
									if ($attainmentOn[$i]=="Y" AND $attainmentID[$i]!="") {
										$span++ ;
									}
									if ($effortOn[$i]=="Y" AND $effortID[$i]!="") {
										$span++ ;
									}
									if ($comment[$i]=="Y") {
										$span++ ;
									}
									if ($uploadedResponse[$i]=="Y") {
										$span++ ;
									}
									if ($span==0) {
										$contents=FALSE ;
									}
									
									print "<th style='text-align: center; min-width: 140px' colspan=$span>" ;
										print "<span title='" . htmlPrep($row["description"]) . "'>" . $row["name"] . "</span><br/>" ;
										print "<span style='font-size: 90%; font-style: italic; font-weight: normal'>" ;
										if ($row["completeDate"]!="") {
											print _("Marked on") . " " . dateConvertBack($guid, $row["completeDate"]) . "<br/>" ;
										}
										else {
											print _("Unmarked") . "<br/>" ;
										}
										print $row["type"] ;
										if ($row["attachment"]!="" AND file_exists($_SESSION[$guid]["absolutePath"] . "/" . $row["attachment"])) {
											print "<a 'title='" . _('Download more information') . "' href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $row["attachment"] . "'>More info</a>"; 
										}
										print "</span><br/>" ;
										if (isActionAccessible($guid, $connection2, "/modules/Markbook/markbook_edit.php")) {
											print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Formal Assessment/internalAssessment_write_data.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonInternalAssessmentColumnID=" . $row["gibbonInternalAssessmentColumnID"] . "'><img style='margin-top: 3px' title='" . _('Enter Data') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/markbook.png'/></a> " ;
										}
									print "</th>" ;
								}
							print "</tr>" ;
						
							print "<tr class='head'>" ;
								for ($i=0; $i<$columnsThisPage; $i++) {
									if ($columnID[$i]==FALSE OR $contents==FALSE) {
										print "<th style='text-align: center' colspan=$span>" ;
									
										print "</th>" ;
									}
									else {
										$leftBorder=FALSE ;
										if ($attainmentOn[$i]=="Y" AND $attainmentID[$i]!="") {
											$leftBorder=TRUE ;
											print "<th style='border-left: 2px solid #666; text-align: center; width: 40px'>" ;
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
												if ($attainmentAlternativeName!="" AND $attainmentAlternativeNameAbrev!="") {
													print "<span title='" . $attainmentAlternativeName . htmlPrep($scale) . "'>" . $attainmentAlternativeNameAbrev . "</span>" ;
												}
												else {
													print "<span title='" . _('Attainment') . htmlPrep($scale) . "'>" . _('Att') . "</span>" ;
												}
											print "</th>" ;
										}
										
										if ($effortOn[$i]=="Y" AND $effortID[$i]!="") {
											$leftBorderStyle='' ;
											if ($leftBorder==FALSE) {
												$leftBorder=TRUE ;
												$leftBorderStyle="border-left: 2px solid #666;" ;
											}
											print "<th style='$leftBorderStyle text-align: center; width: 40px'>" ;
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
												if ($effortAlternativeName!="" AND $effortAlternativeNameAbrev!="") {
													print "<span title='" . $effortAlternativeName . htmlPrep($scale) . "'>" . $effortAlternativeNameAbrev . "</span>" ;
												}
												else {
													print "<span title='" . _('Effort') . htmlPrep($scale) . "'>" . _('Eff') . "</span>" ;
												}
											print "</th>" ;
										}
										
										if ($comment[$i]=="Y") {
											$leftBorderStyle='' ;
											if ($leftBorder==FALSE) {
												$leftBorder=TRUE ;
												$leftBorderStyle="border-left: 2px solid #666;" ;
											}
											print "<th style='$leftBorderStyle text-align: center; width: 80px'>" ;
												print "<span title='" . _('Comment') . "'>" . _('Com') . "</span>" ;
											print "</th>" ;
										}
										if ($uploadedResponse[$i]=="Y") {
											$leftBorderStyle='' ;
											if ($leftBorder==FALSE) {
												$leftBorder=TRUE ;
												$leftBorderStyle="border-left: 2px solid #666;" ;
											}
											print "<th style='$leftBorderStyle text-align: center; width: 30px'>" ;
												print "<span title='" . _('Uploaded Response') . "'>" . _('Upl') . "</span>" ;
											print "</th>" ;
										}
									}
								}
							print "</tr>" ;
					
						$count=0;
						$rowNum="odd" ;
					
						try {
							$dataStudents=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
							$sqlStudents="SELECT title, surname, preferredName, gibbonPerson.gibbonPersonID, dateStart FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE role='Student' AND gibbonCourseClassID=:gibbonCourseClassID AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonCourseClassPerson.reportable='Y' ORDER BY surname, preferredName" ;
							$resultStudents=$connection2->prepare($sqlStudents);
							$resultStudents->execute($dataStudents);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($resultStudents->rowCount()<1) {
							print "<tr>" ;
								print "<td colspan=" . ($columns+1) . ">" ;
									print "<i>" . _('There are no records to display.') . "</i>" ;
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
								print "<tr class=$rowNum>" ;
									print "<td>" ;
										print "<div style='padding: 2px 0px'><b><a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $rowStudents["gibbonPersonID"] . "&subpage=Markbook#" . $gibbonCourseClassID . "'>" . formatName("", $rowStudents["preferredName"], $rowStudents["surname"], "Student", true) . "</a><br/></div>" ;
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
												print "<a title='" . _($rowEntry["descriptor"]) . " | " . _('Test taken on') . " " . dateConvertBack($guid, $rowEntry["date"]) . "' href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $rowStudents["gibbonPersonID"] . "&subpage=External Assessment'>" . _($rowEntry["value"]) . "</a>" ;
											}	
										print "</td>" ;
									}
								
									for ($i=0; $i<$columnsThisPage; $i++) {
										$row=$result->fetch() ;
											try {
												$dataEntry=array("gibbonInternalAssessmentColumnID"=>$columnID[($i)], "gibbonPersonIDStudent"=>$rowStudents["gibbonPersonID"]); 
												$sqlEntry="SELECT * FROM gibbonInternalAssessmentEntry WHERE gibbonInternalAssessmentColumnID=:gibbonInternalAssessmentColumnID AND gibbonPersonIDStudent=:gibbonPersonIDStudent" ;
												$resultEntry=$connection2->prepare($sqlEntry);
												$resultEntry->execute($dataEntry);
											}
											catch(PDOException $e) { 
												print "<div class='error'>" . $e->getMessage() . "</div>" ; 
											}
											if ($resultEntry->rowCount()==1) {
												$rowEntry=$resultEntry->fetch() ;
												$leftBorder=FALSE ;
												
												if ($attainmentOn[$i]=="Y" AND $attainmentID[$i]!="") {
													$leftBorder=TRUE ;
													print "<td style='border-left: 2px solid #666; text-align: center'>" ;
														if ($attainmentID[$i]!="") {
															$styleAttainment="" ;
															$attainment="" ;
															if ($rowEntry["attainmentValue"]!="") {
																$attainment=_($rowEntry["attainmentValue"]) ;
															}
															if ($rowEntry["attainmentValue"]=="Complete") {
																$attainment=_("Com") ;
															}
															else if ($rowEntry["attainmentValue"]=="Incomplete") {
																$attainment=_("Inc") ;
															}
															print "<div $styleAttainment title='" . htmlPrep($rowEntry["attainmentDescriptor"]) . "'>$attainment" ;
														}
														if ($attainmentID[$i]!="") {
															print "</div>" ;
														}
													print "</td>" ;
												}
												if ($effortOn[$i]=="Y" AND $effortID[$i]!="") {
													$leftBorderStyle='' ;
													if ($leftBorder==FALSE) {
														$leftBorder=TRUE ;
														$leftBorderStyle="border-left: 2px solid #666;" ;
													}
													print "<td style='$leftBorderStyle text-align: center;'>" ;
														if ($effortID[$i]!="") {
															$styleEffort="" ;
															$effort="" ;
															if ($rowEntry["effortValue"]!="") {
																$effort=_($rowEntry["effortValue"]) ;
															}
															if ($rowEntry["effortValue"]=="Complete") {
																$effort=_("Com") ;
															}
															else if ($rowEntry["effortValue"]=="Incomplete") {
																$effort=_("Inc") ;
															}
															print "<div $styleEffort title='" . htmlPrep($rowEntry["effortDescriptor"]) . "'>$effort" ;
														}
														if ($effortID[$i]!="") {
															print "</div>" ;
														}
													print "</td>" ;
												}
												
												if ($comment[$i]=="Y") {
													$leftBorderStyle='' ;
													if ($leftBorder==FALSE) {
														$leftBorder=TRUE ;
														$leftBorderStyle="border-left: 2px solid #666;" ;
													}
													print "<td style='$leftBorderStyle text-align: center;'>" ;
														$style="" ;
														if ($rowEntry["comment"]!="") {
															if (strlen($rowEntry["comment"])<11) {
																print htmlPrep($rowEntry["comment"]) ;
															}
															else {
																print "<span $style title='" . htmlPrep($rowEntry["comment"]) . "'>" . substr($rowEntry["comment"], 0, 10) . "...</span>" ;
															}
														}
													print "</td>" ;
												}
												if ($uploadedResponse[$i]=="Y") {
													$leftBorderStyle='' ;
													if ($leftBorder==FALSE) {
														$leftBorder=TRUE ;
														$leftBorderStyle="border-left: 2px solid #666;" ;
													}
													print "<td style='$leftBorderStyle text-align: center;'>" ;
													if ($rowEntry["response"]!="") {
														print "<a title='" . _('Uploaded Response') . "' href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $rowEntry["response"] . "'>Up</a><br/>" ;
													}
												}
												print "</td>" ;
											}
											else {
												$emptySpan=0 ;
												if ($attainmentOn[$i]=="Y" AND $attainmentID[$i]!="") {
													$emptySpan++ ;
												}
												if ($effortOn[$i]=="Y" AND $effortID[$i]!="") {
													$emptySpan++ ;
												}
												if ($comment[$i]=="Y") {
													$emptySpan++ ;
												}
												if ($uploadedResponse[$i]=="Y") {
													$emptySpan++ ;
												}
												if ($emptySpan>0) {
													print "<td style='border-left: 2px solid #666; text-align: center' colspan=$emptySpan></td>" ;
												}
											}
											if (isset($submission[$i])) {
												if ($submission[$i]==TRUE) {
													$leftBorderStyle='' ;
													if ($leftBorder==FALSE) {
														$leftBorder=TRUE ;
														$leftBorderStyle="border-left: 2px solid #666;" ;
													}
													print "<td style='$leftBorderStyle text-align: center;'>" ;
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
																$linkText=_("Exe") ;
															}
															else if ($rowWork["version"]=="Final") {
																$linkText=_("Fin") ;
															}
															else {
																$linkText=_("Dra") . $rowWork["count"] ;
															}
													
															$style="" ;
															$status="On Time" ;
																if ($rowWork["status"]=="Exemption") {
																$status=_("Exemption") ;
															}
															else if ($rowWork["status"]=="Late") {
																$style="style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px'" ;
																$status=_("Late") ;
															}
													
															if ($rowWork["type"]=="File") {
																print "<span title='" . $rowWork["version"] . ". $status. " . _('Submitted at') . " " . substr($rowWork["timestamp"],11,5) . " " . _('on') . " " . dateConvertBack($guid, substr($rowWork["timestamp"],0,10)) . "' $style><a href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $rowWork["location"] ."'>$linkText</a></span>" ;
															}
															else if ($rowWork["type"]=="Link") {
																print "<span title='" . $rowWork["version"] . ". $status. " . _('Submitted at') . " " . substr($rowWork["timestamp"],11,5) . " " . _('on') . " " . dateConvertBack($guid, substr($rowWork["timestamp"],0,10)) . "' $style><a target='_blank' href='" . $rowWork["location"] ."'>$linkText</a></span>" ;
															}
															else {
																print "<span title='$status. " . _('Recorded at') . " " . substr($rowWork["timestamp"],11,5) . " " . _('on') . " " . dateConvertBack($guid, substr($rowWork["timestamp"],0,10)) . "' $style>$linkText</span>" ;
															}
														}
														else {
															if (date("Y-m-d H:i:s")<$homeworkDueDateTime[$i]) {
																print "<span title='" . _('Pending') . "'>Pen</span>" ;
															}
															else {
																if ($rowStudents["dateStart"]>$lessonDate[$i]) {
																	print "<span title='" . _('Student joined school after assessment was given.') . "' style='color: #000; font-weight: normal; border: 2px none #ff0000; padding: 2px 4px'>" . _('NA') . "</span>" ;
																}
																else {
																	if ($rowSub["homeworkSubmissionRequired"]=="Compulsory") {
																		print "<span title='" . _('Incomplete') . "' style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px'>" . _('Inc') . "</span>" ;
																	}
																	else {
																		print "<span title='" . _('Not submitted online') . "'>" . _('NA') . "</span>" ;
																	}
																}
															}
														}
													print "</td>" ;
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
			
		//Print sidebar
		$_SESSION[$guid]["sidebarExtra"]=sidebarExtra($guid, $connection2, $gibbonCourseClassID, "write") ;
	}
}		
?>