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

if (isActionAccessible($guid, $connection2, "/modules/Markbook/markbook_view.php")==FALSE) {
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
		
		//VIEW ACCESS TO ALL MARKBOOK DATA
		if ($highestAction=="View Markbook_allClassesAllData") {
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
				print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" ._('View Markbook') . "</div>" ;
				print "</div>" ;
				print "<div class='warning'>" ;
					print "Use the class listing on the right to choose a Markbook to view." ;
				print "</div>" ;
			}
			//Check existence of and access to this class.
			else {
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
					print "<div class='trail'>" ;
					print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . _('View Markbook') . "</div>" ;
					print "</div>" ;
					print "<div class='error'>" ;
						print _("The specified record does not exist.") ;
					print "</div>" ;	
				}
				else {
					$row=$result->fetch() ;
					$courseName=$row["courseName"] ;
					$gibbonYearGroupIDList=$row["gibbonYearGroupIDList"] ;
					print "<div class='trail'>" ;
					print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>View " . $row["course"] . "." . $row["class"] . " Markbook</div>" ;
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
					
					//Get Smart Workflow help message
					$category=getRoleCategory($_SESSION[$guid]["gibbonRoleIDCurrent"], $connection2) ;
					if ($category=="Staff") {
						$smartWorkflowHelp=getSmartWorkflowHelp($connection2, $guid, 5) ;
						if ($smartWorkflowHelp!=false) {
							print $smartWorkflowHelp ;
						}
					}
					
					//Add multiple columns
					if (isActionAccessible($guid, $connection2, "/modules/Markbook/markbook_edit.php")) {
						$highestAction2=getHighestGroupedAction($guid, "/modules/Markbook/markbook_edit.php", $connection2) ;
						if ($highestAction2=="Edit Markbook_multipleClassesAcrossSchool" OR $highestAction2=="Edit Markbook_multipleClassesInDepartment" OR $highestAction2=="Edit Markbook_everything") {
							//Check highest role in any department
							try {
								$dataRole=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
								$sqlRole="SELECT role FROM gibbonDepartmentStaff WHERE gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)')" ;
								$resultRole=$connection2->prepare($sqlRole);
								$resultRole->execute($dataRole);
							}
							catch(PDOException $e) { }
							if ($resultRole->rowCount()>=1 OR $highestAction2=="Edit Markbook_multipleClassesAcrossSchool" OR $highestAction2=="Edit Markbook_everything") {
								print "<div class='linkTop'>" ;
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/markbook_edit_addMulti.php&gibbonCourseClassID=$gibbonCourseClassID'>" . _('Add Multiple Records') . "<img style='margin-left: 5px' title='" . _('Add Multiple Records') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new_multi.png'/></a>" ;
								print "</div>" ;
							}
						}
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
						$sql="SELECT * FROM gibbonMarkbookColumn WHERE gibbonCourseClassID=:gibbonCourseClassID ORDER BY complete, completeDate DESC" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					$columns=$result->rowCount() ;
					if ($columns<1) {
						print "<div class='linkTop'>" ;
							if (isActionAccessible($guid, $connection2, "/modules/Markbook/markbook_view.php") AND $teaching) {
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/markbook_edit_add.php&gibbonCourseClassID=$gibbonCourseClassID'>" . _('Add') . "<img style='margin-left: 5px' title='" . _('Add') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.png'/></a>" ;
							}
						print "</div>" ;
						
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
				
						$columns=$result->rowCount() ;
						
						if ($columns<1) {
							print "<div class='linkTop'>" ;
								if (isActionAccessible($guid, $connection2, "/modules/Markbook/markbook_view.php") AND $teaching) {
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/markbook_edit_add.php&gibbonCourseClassID=$gibbonCourseClassID'><img style='margin-right: 3px' title='" . _('Add') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.png'/></a>" ;
								}
							print "</div>" ;
						
							print "<div class='warning'>" ;
								print _("There are no records to display.") ;
							print "</div>" ;	
						}
						else {
							try {
								$data=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
								$sql="SELECT * FROM gibbonMarkbookColumn WHERE gibbonCourseClassID=:gibbonCourseClassID ORDER BY complete, completeDate DESC LIMIT " . ($x*$columnsPerPage) . ", " . $columnsPerPage ;
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
									print " " . _('The Baseline column is populated based on student performance in external assessments, and can be used as a reference point for the grades in the markbook.') ;
								}
							print "</p>" ;
						
							print "<div class='linkTop'>" ;
								if (isActionAccessible($guid, $connection2, "/modules/Markbook/markbook_edit.php")) {
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/markbook_edit_add.php&gibbonCourseClassID=$gibbonCourseClassID'>" . _('Add') . "<img style='margin-left: 5px' title='" . _('Add') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.png'/></a> | " ;
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/markbook_edit_targets.php&gibbonCourseClassID=$gibbonCourseClassID'>" . _('Targets') . "<img style='margin-left: 5px' title='" . _('Set Personalised Attainment Targets') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/target.png'/></a> | " ;
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/Markbook/markbook_viewExportAll.php?gibbonCourseClassID=$gibbonCourseClassID&return=markbook_view.php'>" . _('Export to Excel') . "<img style='margin-left: 5px' title='" . _('Export to Excel') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/download.png'/></a> | " ;
								}
								print "<div style='padding-top: 12px; margin-left: 10px; float: right'>" ;
									if ($x<=0) {
										print _("Newer") ;
									}
									else {
										print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/markbook_view.php&gibbonCourseClassID=$gibbonCourseClassID&page=" . ($x-1) . "'>" . _('Newer') . "</a>" ;
									}
									print " | " ;
									if ((($x+1)*$columnsPerPage)>=$columns) {
										print _("Older") ;
									}
									else {
										print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/markbook_view.php&gibbonCourseClassID=$gibbonCourseClassID&page=" . ($x+1) . "'>" . _('Older') . "</a>" ;
									}
								print "</div>" ;
							print "</div>" ;
				
							print "<table class='mini' cellspacing='0' style='width: 100%; margin-top: 0px'>" ;
								print "<tr class='head'>" ;
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
								
									//Show target grade header
									print "<th rowspan=2 style='width: 20px'>" ;
										$title=_("Personalised attainment target grade") ;
									
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
											print _("Target") . "<br/>" ;
										print "</div>" ;
									print "</th>" ;
								
									$columnID=array() ;
									$attainmentID=array() ;
									$effortID=array() ;
									for ($i=0;$i<3;$i++) {
										$row=$result->fetch() ;
										if ($row===FALSE) {
											$columnID[$i]=FALSE ;
										}
										else {
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
										}
									
										if (isset($submission[$i])==FALSE) {
											$span=4 ;
										}
										else {
											$span=5 ;
										}
										if ($columnID[$i]==FALSE) {
											print "<th style='text-align: center' colspan=$span>" ;
										
											print "</th>" ;
										}
										else {
											print "<th style='text-align: center' colspan=$span>" ;
												print "<span title='" . htmlPrep($row["description"]) . "'>" . $row["name"] . "</span><br/>" ;
												print "<span style='font-size: 90%; font-style: italic; font-weight: normal'>" ;
												$unit=getUnit($connection2, $row["gibbonUnitID"], "", $row["gibbonCourseClassID"]) ;
												if (isset($unit[0])) {
													print $unit[0] . "<br/>" ;
												}
												else {
													print "<br/>" ;
												}
												if ($row["completeDate"]!="") {
													print _("Marked on") . " " . dateConvertBack($guid, $row["completeDate"]) . "<br/>" ;
												}
												else {
													print _("Unmarked") . "<br/>" ;
												}
												print $row["type"] ;
												if ($row["attachment"]!="" AND file_exists($_SESSION[$guid]["absolutePath"] . "/" . $row["attachment"])) {
													print " | <a 'title='" . _('Download more information') . "' href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $row["attachment"] . "'>More info</a>"; 
												}
												print "</span><br/>" ;
												if (isActionAccessible($guid, $connection2, "/modules/Markbook/markbook_edit.php")) {
													print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/markbook_edit_edit.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=" . $row["gibbonMarkbookColumnID"] . "'><img style='margin-top: 3px' title='" . _('Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
													print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/markbook_edit_data.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=" . $row["gibbonMarkbookColumnID"] . "'><img style='margin-top: 3px' title='" . _('Enter Data') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/markbook.png'/></a> " ;
													print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/markbook_edit_delete.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=" . $row["gibbonMarkbookColumnID"] . "'><img title='" . _('Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a> " ;
													print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/Markbook/markbook_viewExport.php?gibbonMarkbookColumnID=" . $row["gibbonMarkbookColumnID"] . "&gibbonCourseClassID=$gibbonCourseClassID&return=markbook_view.php'><img title='" . _('Export to Excel') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/download.png'/></a>" ;
												}
											print "</th>" ;
										}
									}
								print "</tr>" ;
							
								print "<tr class='head'>" ;
									for ($i=0;$i<3;$i++) {
										if ($columnID[$i]==FALSE) {
											print "<th style='text-align: center' colspan=$span>" ;
										
											print "</th>" ;
										}
										else {
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
													print "<span title='" . $attainmentAlternativeName . "$scale'>" . $attainmentAlternativeNameAbrev . "</span>" ;
												}
												else {
													print "<span title='" . _('Attainment') . "$scale'>" . _('Att') . "</span>" ;
												}
											print "</th>" ;
											print "<th style='text-align: center; width: 40px'>" ;
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
													print "<span title='" . $effortAlternativeName . "$scale'>" . $effortAlternativeNameAbrev . "</span>" ;
												}
												else {
													print "<span title='" . _('Effort') . "$scale'>" . _('Eff') . "</span>" ;
												}
											print "</th>" ;
											print "<th style='text-align: center; width: 80px'>" ;
												print "<span title='" . _('Comment') . "'>" . _('Com') . "</span>" ;
											print "</th>" ;
											print "<th style='text-align: center; width: 30px'>" ;
												print "<span title='" . _('Uploaded Response') . "'>" . _('Upl') . "</span>" ;
											print "</th>" ;
											if (isset($submission[$i])) {
												if ($submission[$i]==TRUE) {
													print "<th style='text-align: center; width: 30px'>" ;
														print "<span title='" . _('Submitted Work') . "'>" . _('Sub') . "</span>" ;
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
												print _($rowEntry["value"]) ;
											}	
										print "</td>" ;
									
										for ($i=0;$i<3;$i++) {
											$row=$result->fetch() ;
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
													print "<td style='border-left: 2px solid #666; text-align: center'>" ;
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
														if ($gibbonRubricIDAttainment[$i]!="") {
															print "<a class='thickbox' href='" . $_SESSION[$guid]["absoluteURL"] . "/fullscreen.php?q=/modules/" . $_SESSION[$guid]["module"] . "/markbook_view_rubric.php&gibbonRubricID=" . $gibbonRubricIDAttainment[$i] . "&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=" . $columnID[$i] . "&gibbonPersonID=" . $rowStudents["gibbonPersonID"] . "&mark=FALSE&type=attainment&width=1100&height=550'><img style='margin-bottom: -3px; margin-left: 3px' title='" . _('View Rubric') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/rubric.png'/></a>" ;
														}
														print "</div>" ;
													print "</td>" ;
													$styleEffort="" ;
													if ($rowEntry["effortConcern"]=="Y") {
														$styleEffort="style='color: #" . $alert["color"] . "; font-weight: bold; border: 2px solid #" . $alert["color"] . "; padding: 2px 4px; background-color: #" . $alert["colorBG"] . "'" ;
													}
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
													print "<td style='text-align: center;'>" ;
														print "<div $styleEffort title='" . htmlPrep($rowEntry["effortDescriptor"]) . "'>$effort" ;
															if ($gibbonRubricIDEffort[$i]!="") {
																print "<a class='thickbox' href='" . $_SESSION[$guid]["absoluteURL"] . "/fullscreen.php?q=/modules/" . $_SESSION[$guid]["module"] . "/markbook_view_rubric.php&gibbonRubricID=" . $gibbonRubricIDEffort[$i] . "&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=" . $columnID[$i] . "&gibbonPersonID=" . $rowStudents["gibbonPersonID"] . "&mark=FALSE&type=effort&width=1100&height=550'><img style='margin-bottom: -3px; margin-left: 3px' title='" . _('View Rubric') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/rubric.png'/></a>" ;
															}
														print "</div>" ;
													print "</td>" ;
														print "<td style='text-align: center;'>" ;
														$style="" ;
														if ($rowEntry["comment"]!="") {
															print "<span $style title='" . htmlPrep($rowEntry["comment"]) . "'>" . substr($rowEntry["comment"], 0, 10) . "...</span>" ;
														}
													print "</td>" ;
													print "<td style='text-align: center;'>" ;
													if ($rowEntry["response"]!="") {
														print "<a title='" . _('Uploaded Response') . "' href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $rowEntry["response"] . "'>Up</a><br/>" ;
													}
													print "</td>" ;
												}
												else {
													$span=4 ;
													if (isset($gibbonRubricID[$i])) {
														$span=5 ;
													}
													print "<td style='text-align: center' colspan=$span>" ;
													print "</td>" ;
												}
												if (isset($submission[$i])) {
													if ($submission[$i]==TRUE) {
														print "<td style='text-align: center; width: 30px'>" ;
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
			$_SESSION[$guid]["sidebarExtra"]=sidebarExtra($guid, $connection2, $gibbonCourseClassID) ;
		}
		//VIEW ACCESS TO MY OWN MARKBOOK DATA
		else if ($highestAction=="View Markbook_myMarks") {
			$showStudentAttainmentWarning=getSettingByScope($connection2, "Markbook", "showStudentAttainmentWarning" ) ; 
			$showStudentEffortWarning=getSettingByScope($connection2, "Markbook", "showStudentEffortWarning" ) ; 														
			
			$entryCount=0 ;
			print "<div class='trail'>" ;
					print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . _('View Markbook') . "</div>" ;
			print "</div>" ;
			print "<p>" ;
				print _("This page shows you your academic results throughout your school career. Only subjects with published results are shown.") ;
			print "</p>" ;
			
			$and="" ;
			$filter=NULL ;
			if (isset($_GET["filter"])) {
				$filter=$_GET["filter"] ;
			}
			else if (isset($_POST["filter"])) {
				$filter=$_POST["filter"] ;
			}
			if ($filter=="") {
				$filter=$_SESSION[$guid]["gibbonSchoolYearID"] ;
			}
			if ($filter!="*") {
				$and=" AND gibbonSchoolYearID='$filter'" ;
			}
			
			$filter2=NULL ;
			if (isset($_GET["filter2"])) {
				$filter2=$_GET["filter2"] ;
			}
			else if (isset($_POST["filter2"])) {
				$filter2=$_POST["filter2"] ;
			}
			if ($filter2!="") {
				$and.=" AND gibbonDepartmentID='$filter2'" ;
			}
			
			print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_GET["q"] . "'>" ;
				print"<table class='noIntBorder' cellspacing='0' style='width: 100%'>" ;	
					?>
					<tr>
						<td> 
							<b><?php print _('Learning Area') ?></b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<?php
							print "<select name='filter2' id='filter2' style='width:302px'>" ;
								print "<option value=''>" . _('All Learning Areas') . "</option>" ;
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT * FROM gibbonDepartment WHERE type='Learning Area' ORDER BY name" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								while ($rowSelect=$resultSelect->fetch()) {
									$selected="" ;
									if ($rowSelect["gibbonDepartmentID"]==$filter2) {
										$selected="selected" ;
									}
									print "<option $selected value='" . $rowSelect["gibbonDepartmentID"] . "'>" . $rowSelect["name"] . "</option>" ;
								}
							print "</select>" ;
							?>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('School Year') ?></b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<?php
							print "<select name='filter' id='filter' style='width:302px'>" ;
								print "<option value='*'>" . _('All Years') . "</option>" ;
								try {
									$dataSelect=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
									$sqlSelect="SELECT gibbonSchoolYear.gibbonSchoolYearID, gibbonSchoolYear.name AS year, gibbonYearGroup.name AS yearGroup FROM gibbonStudentEnrolment JOIN gibbonSchoolYear ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) WHERE gibbonPersonID=:gibbonPersonID ORDER BY gibbonSchoolYear.sequenceNumber" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								while ($rowSelect=$resultSelect->fetch()) {
									$selected="" ;
									if ($rowSelect["gibbonSchoolYearID"]==$filter) {
										$selected="selected" ;
									}
									print "<option $selected value='" . $rowSelect["gibbonSchoolYearID"] . "'>" . $rowSelect["year"] . " (" . _($rowSelect["yearGroup"]) . ")</option>" ;
								}
							print "</select>" ;
							?>
						</td>
					</tr>
					<?php
					print "<tr>" ;
						print "<td class='right' colspan=2>" ;
							print "<input type='hidden' name='q' value='" . $_GET["q"] . "'>" ;
							print "<input checked type='checkbox' name='details' class='details' value='Yes' />" ;
							print "<span style='font-size: 85%; font-weight: normal; font-style: italic'> " . _('Show/Hide Details') . "</span>" ;
							?>
							<script type="text/javascript">
								/* Show/Hide detail control */
								$(document).ready(function(){
									$(".details").click(function(){
										if ($('input[name=details]:checked').val()=="Yes" ) {
											$(".detailItem").slideDown("fast", $("#detailItem").css("{'display' : 'table-row'}")); 
										} 
										else {
											$(".detailItem").slideUp("fast"); 
										}
									 });
								});
							</script>
							<?php
							print "<input type='submit' value='" . _('Go') . "'>" ;
						print "</td>" ;
					print "</tr>" ;
				print"</table>" ;
			print "</form>" ;
			
			//Get class list
			
			try {
				$dataList=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
				$sqlList="SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourse.name, gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID $and ORDER BY course, class" ;
				$resultList=$connection2->prepare($sqlList);
				$resultList->execute($dataList);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			if ($resultList->rowCount()>0) {
				while ($rowList=$resultList->fetch()) {
					try {
						$dataEntry=array("gibbonPersonIDStudent"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonCourseClassID"=>$rowList["gibbonCourseClassID"]); 
						$sqlEntry="SELECT *, gibbonMarkbookEntry.comment AS comment FROM gibbonMarkbookEntry JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) WHERE gibbonPersonIDStudent=:gibbonPersonIDStudent AND gibbonCourseClassID=:gibbonCourseClassID AND complete='Y' AND completeDate<='" . date("Y-m-d") . "' ORDER BY completeDate" ;
						$resultEntry=$connection2->prepare($sqlEntry);
						$resultEntry->execute($dataEntry);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					if ($resultEntry->rowCount()>0) {
						print "<h4>" . $rowList["course"] . "." . $rowList["class"] . " <span style='font-size:85%; font-style: italic'>(" . $rowList["name"] . ")</span></h4>" ;
					
						try {
							$dataTeachers=array("gibbonCourseClassID"=>$rowList["gibbonCourseClassID"]); 
							$sqlTeachers="SELECT title, surname, preferredName FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE role='Teacher' AND gibbonCourseClassID=:gibbonCourseClassID ORDER BY surname, preferredName" ;
							$resultTeachers=$connection2->prepare($sqlTeachers);
							$resultTeachers->execute($dataTeachers);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}

						$teachers="<p><b>" . _('Taught by:') . "</b> " ;
						while ($rowTeachers=$resultTeachers->fetch()) {
							$teachers=$teachers . $rowTeachers["title"] . " " . $rowTeachers["surname"] . ", " ;
						}
						$teachers=substr($teachers,0,-2) ;
						$teachers=$teachers . "</p>" ;
						print $teachers ;
	
						print "<table cellspacing='0' style='width: 100%'>" ;
						print "<tr class='head'>" ;
							print "<th style='width: 120px'>" ;
								print _("Assessment") ;
							print "</th>" ;
							print "<th style='width: 75px; text-align: center'>" ;
								if ($attainmentAlternativeName!="") { print $attainmentAlternativeName ; } else { print _('Attainment') ; }
							print "</th>" ;
							print "<th style='width: 75px; text-align: center'>" ;
								if ($effortAlternativeName!="") { print $effortAlternativeName ; } else { print _('Effort') ; }
							print "</th>" ;
							print "<th>" ;
								print _("Comment") ;
							print "</th>" ;
							print "<th style='width: 75px'>" ;
								print _("Submission") ;
							print "</th>" ;
						print "</tr>" ;
						
						$count=0 ;
						while ($rowEntry=$resultEntry->fetch()) {
							if ($count%2==0) {
								$rowNum="even" ;
							}
							else {
								$rowNum="odd" ;
							}
							$count++ ;
							$entryCount++ ;
							
							print "<a name='" . $rowEntry["gibbonMarkbookEntryID"] . "'></a>" ; 
							print "<tr class=$rowNum>" ;
								print "<td>" ;
									print "<span title='" . htmlPrep($rowEntry["description"]) . "'><b><u>" . $rowEntry["name"] . "</u></b></span><br/>" ;
									print "<span style='font-size: 90%; font-style: italic; font-weight: normal'>" ;
									$unit=getUnit($connection2, $rowEntry["gibbonUnitID"], $rowEntry["gibbonHookID"], $rowEntry["gibbonCourseClassID"]) ;
									if (isset($unit[0])) {
										print $unit[0] . "<br/>" ;
										if ($unit[1]!="") {
											print "<i>" . $unit[1] . " " . _('Unit') . "</i><br/>" ;
										}
									}
									if ($rowEntry["completeDate"]!="") {
										print _('Marked on') . " " . dateConvertBack($guid, $rowEntry["completeDate"]) . "<br/>" ;
									}
									else {
										print _("Unmarked") . "<br/>" ;
									}
									print $rowEntry["type"] ;
									if ($rowEntry["attachment"]!="" AND file_exists($_SESSION[$guid]["absolutePath"] . "/" . $rowEntry["attachment"])) {
										print " | <a 'title='" . _('Download more information') . "' href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $rowEntry["attachment"] . "'>" . _('More info') . "</a>"; 
									}
									print "</span><br/>" ;
								print "</td>" ;
								print "<td style='text-align: center'>" ;
									$attainmentExtra="" ;
									try {
										$dataAttainment=array("gibbonScaleID"=>$rowEntry["gibbonScaleIDAttainment"]); 
										$sqlAttainment="SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID" ;
										$resultAttainment=$connection2->prepare($sqlAttainment);
										$resultAttainment->execute($dataAttainment);
									}
									catch(PDOException $e) { 
										print "<div class='error'>" . $e->getMessage() . "</div>" ; 
									}
									if ($resultAttainment->rowCount()==1) {
										$rowAttainment=$resultAttainment->fetch() ;
										$attainmentExtra="<br/>" . _($rowAttainment["usage"]) ;
									}
									$styleAttainment="style='font-weight: bold'" ;
									if ($rowEntry["attainmentConcern"]=="Y" AND $showStudentAttainmentWarning=="Y") {
										$styleAttainment="style='color: #" . $alert["color"] . "; font-weight: bold; border: 2px solid #" . $alert["color"] . "; padding: 2px 4px; background-color: #" . $alert["colorBG"] . "'" ;
									}
									else if ($rowEntry["attainmentConcern"]=="P" AND $showStudentAttainmentWarning=="Y") {
										$styleAttainment="style='color: #390; font-weight: bold; border: 2px solid #390; padding: 2px 4px; background-color: #D4F6DC'" ;
									}
									print "<div $styleAttainment>" . $rowEntry["attainmentValue"] ;
										if ($rowEntry["gibbonRubricIDAttainment"]!="") {
											print "<a class='thickbox' href='" . $_SESSION[$guid]["absoluteURL"] . "/fullscreen.php?q=/modules/Markbook/markbook_view_rubric.php&gibbonRubricID=" . $rowEntry["gibbonRubricIDAttainment"] . "&gibbonCourseClassID=" . $rowEntry["gibbonCourseClassID"] . "&gibbonMarkbookColumnID=" . $rowEntry["gibbonMarkbookColumnID"] . "&gibbonPersonID=" . $_SESSION[$guid]["gibbonPersonID"] . "&mark=FALSE&type=attainment&width=1100&height=550'><img style='margin-bottom: -3px; margin-left: 3px' title='" . _('View Rubric') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/rubric.png'/></a>" ;
										}
									print "</div>" ;
									if ($rowEntry["attainmentValue"]!="") {
										print "<div class='detailItem' style='font-size: 75%; font-style: italic; margin-top: 2px'><b>" . htmlPrep(_($rowEntry["attainmentDescriptor"])) . "</b>" . _($attainmentExtra) . "</div>" ;
									}
								print "</td>" ;
								print "<td style='text-align: center'>" ;
									$effortExtra="" ;
									try {
										$dataEffort=array("gibbonScaleID"=>$rowEntry["gibbonScaleIDEffort"]); 
										$sqlEffort="SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID" ;
										$resultEffort=$connection2->prepare($sqlEffort);
										$resultEffort->execute($dataEffort);
									}
									catch(PDOException $e) { 
										print "<div class='error'>" . $e->getMessage() . "</div>" ; 
									}
									if ($resultEffort->rowCount()==1) {
										$rowEffort=$resultEffort->fetch() ;
										$effortExtra="<br/>" . _($rowEffort["usage"]) ;
									}
									$styleEffort="style='font-weight: bold'" ;
									if ($rowEntry["effortConcern"]=="Y" AND $showStudentEffortWarning=="Y") {
										$styleEffort="style='color: #" . $alert["color"] . "; font-weight: bold; border: 2px solid #" . $alert["color"] . "; padding: 2px 4px; background-color: #" . $alert["colorBG"] . "'" ;
									}
									print "<div $styleEffort>" . $rowEntry["effortValue"] ;
										if ($rowEntry["gibbonRubricIDEffort"]!="") {
											print "<a class='thickbox' href='" . $_SESSION[$guid]["absoluteURL"] . "/fullscreen.php?q=/modules/Markbook/markbook_view_rubric.php&gibbonRubricID=" . $rowEntry["gibbonRubricIDEffort"] . "&gibbonCourseClassID=" . $rowEntry["gibbonCourseClassID"] . "&gibbonMarkbookColumnID=" . $rowEntry["gibbonMarkbookColumnID"] . "&gibbonPersonID=" . $_SESSION[$guid]["gibbonPersonID"] . "&mark=FALSE&type=effort&width=1100&height=550'><img style='margin-bottom: -3px; margin-left: 3px' title='" . _('View Rubric') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/rubric.png'/></a>" ;
										}
									print "</div>" ;
									if ($rowEntry["effortValue"]!="") {
										print "<div class='detailItem' style='font-size: 75%; font-style: italic; margin-top: 2px'><b>" . htmlPrep(_($rowEntry["effortDescriptor"])) . "</b>" . _($effortExtra) . "</div>" ;
									}
								print "</td>" ;
								print "<td>" ;
									if ($rowEntry["comment"]!="") {
										if (strlen($rowEntry["comment"])>50) {
											print "<script type='text/javascript'>" ;	
												print "$(document).ready(function(){" ;
													print "\$(\".comment-$entryCount\").hide();" ;
													print "\$(\".show_hide-$entryCount\").fadeIn(1000);" ;
													print "\$(\".show_hide-$entryCount\").click(function(){" ;
													print "\$(\".comment-$entryCount\").fadeToggle(1000);" ;
													print "});" ;
												print "});" ;
											print "</script>" ;
											print "<span>" . substr($rowEntry["comment"], 0, 50) . "...<br/>" ;
											print "<a title='" . _('View Description') . "' class='show_hide-$entryCount' onclick='return false;' href='#'>" . _('Read more') . "</a></span><br/>" ;
										}
										else {
											print $rowEntry["comment"] ;
										}
										if ($rowEntry["response"]!="") {
											print "<a title='" . _('Uploaded Response') . "' href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $rowEntry["response"] . "'>" . _('Uploaded Response') . "</a><br/>" ;
										}
									}
								print "</td>" ;
								print "<td>" ;
									if ($rowEntry["gibbonPlannerEntryID"]!="") {
										try {
											$dataSub=array("gibbonPlannerEntryID"=>$rowEntry["gibbonPlannerEntryID"]); 
											$sqlSub="SELECT * FROM gibbonPlannerEntry WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND homeworkSubmission='Y'" ;
											$resultSub=$connection2->prepare($sqlSub);
											$resultSub->execute($dataSub);
										}
										catch(PDOException $e) { 
											print "<div class='error'>" . $e->getMessage() . "</div>" ; 
										}
										if ($resultSub->rowCount()==1) {
											$rowSub=$resultSub->fetch() ;
											try {
												$dataWork=array("gibbonPlannerEntryID"=>$rowEntry["gibbonPlannerEntryID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
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
												$status=_("On Time") ;
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
												if (date("Y-m-d H:i:s")<$rowSub["homeworkDueDateTime"]) {
													print "<span title='" . _('Pending') . "'>" . _('Pending') . "</span>" ;
												}
												else {
													if ($_SESSION[$guid]["dateStart"]>$rowSub["date"]) {
														print "<span title='" . _('Student joined school after assessment was given.') . "' style='color: #000; font-weight: normal; border: 2px none #ff0000; padding: 2px 4px'>NA</span>" ;
													}
													else {
														if ($rowSub["homeworkSubmissionRequired"]=="Compulsory") {
															print "<span title='Incomplete' style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px'>Incomplete</span>" ;
														}
														else {
															print "Not submitted online" ;
														}
													}
												}
											}
										}
									}
								print "</td>" ;
							print "</tr>" ;
							if (strlen($rowEntry["comment"])>50) {
								print "<tr class='comment-$entryCount' id='comment-$entryCount'>" ;
									print "<td colspan=6>" ;
										print $rowEntry["comment"] ;
									print "</td>" ;
								print "</tr>" ;
							}
							
						}
						print "</table>" ;
					}
				}
			}
			
			if ($entryCount<1) {
				print "<div class='error'>" ;
					print "There are currently no grades to display in this view." ;
				print "</div>" ;
			}
		}
		//VIEW ACCESS TO MY CHILDREN'S MARKBOOK DATA
		else if ($highestAction=="Markbook_viewMyChildrensClasses") {
			$entryCount=0; 
			print "<div class='trail'>" ;
				print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>View Markbook</div>" ;
			print "</div>" ;
			print "<p>" ;
				print "This page shows your children's academic results throughout your school career. Only subjects with published results are shown." ;
			print "</p>" ;
			
			//Test data access field for permission
			try {
				$data=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
				$sql="SELECT * FROM gibbonFamilyAdult WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y'" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}

			if ($result->rowCount()<1) {
				print "<div class='error'>" ;
				print _("Access denied.") ;
				print "</div>" ;
			}
			else {
				//Get child list
				$count=0 ;
				$options="" ;
				while ($row=$result->fetch()) {
					try {
						$dataChild=array("gibbonFamilyID"=>$row["gibbonFamilyID"], "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
						$sqlChild="SELECT * FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonFamilyID=:gibbonFamilyID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName " ;
						$resultChild=$connection2->prepare($sqlChild);
						$resultChild->execute($dataChild);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					while ($rowChild=$resultChild->fetch()) {
						$select="" ;
						if (isset($_GET["search"])) {
							if ($rowChild["gibbonPersonID"]==$_GET["search"]) {
								$select="selected" ;
							}
						}
						
						$options=$options . "<option $select value='" . $rowChild["gibbonPersonID"] . "'>" . formatName("", $rowChild["preferredName"], $rowChild["surname"], "Student", true). "</option>" ;
						$gibbonPersonID[$count]=$rowChild["gibbonPersonID"] ;
						$count++ ;
					}
				}
				
				if ($count==0) {
					print "<div class='error'>" ;
					print _("Access denied.") ;
					print "</div>" ;
				}
				else if ($count==1) {
					$_GET["search"]=$gibbonPersonID[0] ;
				}
				else {
					print "<h2>" ;
					print "Choose Student" ;
					print "</h2>" ;
					
					?>
					<form method="get" action="<?php print $_SESSION[$guid]["absoluteURL"]?>/index.php">
						<table class='noIntBorder' cellspacing='0' style="width: 100%">	
							<tr><td style="width: 30%"></td><td></td></tr>
							<tr>
								<td> 
									<b><?php print _('Search For') ?></b><br/>
									<span style="font-size: 90%"><i>Preferred, surname, username.</i></span>
								</td>
								<td class="right">
									<select name="search" id="search" style="width: 302px">
										<option value=""></value>
										<?php print $options ; ?> 
									</select>
								</td>
							</tr>
							<tr>
								<td colspan=2 class="right">
									<input type="hidden" name="q" value="/modules/<?php print $_SESSION[$guid]["module"] ?>/markbook_view.php">
									<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
									<?php
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/markbook_view.php'>" . _('Clear Search') . "</a>" ;
									?>
									<input type="submit" value="<?php print _("Submit") ; ?>">
								</td>
							</tr>
						</table>
					</form>
					<?php
				}
				
				$gibbonPersonID=NULL ;
				if (isset($_GET["search"])) {
					$gibbonPersonID=$_GET["search"] ;
				}
				$showParentAttainmentWarning=getSettingByScope($connection2, "Markbook", "showParentAttainmentWarning" ) ; 
				$showParentEffortWarning=getSettingByScope($connection2, "Markbook", "showParentEffortWarning" ) ; 
														
				if ($gibbonPersonID!="" AND $count>0) {
					//Confirm access to this student
					try {
						$dataChild=array(); 
						$sqlChild="SELECT * FROM gibbonFamilyChild JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonFamilyChild.gibbonPersonID=$gibbonPersonID AND gibbonFamilyAdult.gibbonPersonID=" . $_SESSION[$guid]["gibbonPersonID"] . " AND childDataAccess='Y'" ;
						$resultChild=$connection2->prepare($sqlChild);
						$resultChild->execute($dataChild);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					if ($resultChild->rowCount()<1) {
						print "<div class='error'>" ;
						print _("The selected record does not exist, or you do not have access to it.") ;
						print "</div>" ;
					}
					else {
						$rowChild=$resultChild->fetch() ;
						
						if ($count>1) {
							print "<h2>" ;
							print "Filter & Options" ;
							print "</h2>" ;
						}
						
						
						$and="" ;
						$dataList=array() ;
						$filter=NULL ;
						if (isset($_POST["filter"])) {
							$filter=$_POST["filter"] ;
						}
						if ($filter=="") {
							$filter=$_SESSION[$guid]["gibbonSchoolYearID"] ;
						}
						if ($filter!="*") {
							$dataList["filter"]=$filter ;
							$and.=" AND gibbonSchoolYearID=:filter" ;
						}
						$filter2=NULL ;
						if (isset($_POST["filter2"])) {
							$filter2=$_POST["filter2"] ;
						}
						if ($filter2!="") {
							$dataList["filter2"]=$filter2 ;
							$and.=" AND gibbonDepartmentID=:filter2" ;
						}
						
						print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=" . $_GET["q"] . "&search=$gibbonPersonID'>" ;
							print"<table class='noIntBorder' cellspacing='0' style='width: 100%'>" ;	
								?>
								<tr>
									<td> 
										<b>Learning Area</b><br/>
										<span style="font-size: 90%"><i></i></span>
									</td>
									<td class="right">
										<?php
										print "<select name='filter2' id='filter2' style='width:302px'>" ;
											print "<option value=''>All Learning Areas</option>" ;
											try {
												$dataSelect=array(); 
												$sqlSelect="SELECT * FROM gibbonDepartment WHERE type='Learning Area' ORDER BY name" ;
												$resultSelect=$connection2->prepare($sqlSelect);
												$resultSelect->execute($dataSelect);
											}
											catch(PDOException $e) { }
											while ($rowSelect=$resultSelect->fetch()) {
												$selected="" ;
												if ($rowSelect["gibbonDepartmentID"]==$filter2) {
													$selected="selected" ;
												}
												print "<option $selected value='" . $rowSelect["gibbonDepartmentID"] . "'>" . $rowSelect["name"] . "</option>" ;
											}
										print "</select>" ;
										?>
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php print _('School Year') ?></b><br/>
										<span style="font-size: 90%"><i></i></span>
									</td>
									<td class="right">
										<?php
										print "<select name='filter' id='filter' style='width:302px'>" ;
											print "<option value='*'>All Years</option>" ;
											try {
												$dataSelect=array("gibbonPersonID"=>$gibbonPersonID); 
												$sqlSelect="SELECT gibbonSchoolYear.gibbonSchoolYearID, gibbonSchoolYear.name AS year, gibbonYearGroup.name AS yearGroup FROM gibbonStudentEnrolment JOIN gibbonSchoolYear ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) WHERE gibbonPersonID=:gibbonPersonID ORDER BY gibbonSchoolYear.sequenceNumber" ;
												$resultSelect=$connection2->prepare($sqlSelect);
												$resultSelect->execute($dataSelect);
											}
											catch(PDOException $e) { 
												print "<div class='error'>" . $e->getMessage() . "</div>" ; 
											}
											while ($rowSelect=$resultSelect->fetch()) {
												$selected="" ;
												if ($rowSelect["gibbonSchoolYearID"]==$filter) {
													$selected="selected" ;
												}
												print "<option $selected value='" . $rowSelect["gibbonSchoolYearID"] . "'>" . $rowSelect["year"] . " (" . _($rowSelect["yearGroup"]) . ")</option>" ;
											}
										print "</select>" ;
										?>
									</td>
								</tr>
								<?php
								print "<tr>" ;
									print "<td class='right' colspan=2>" ;
										print "<input type='hidden' name='q' value='" . $_GET["q"] . "'>" ;
										print "<input checked type='checkbox' name='details' class='details' value='Yes' />" ;
										print "<span style='font-size: 85%; font-weight: normal; font-style: italic'> Show/Hide Details</span>" ;
										?>
										<script type="text/javascript">
											/* Show/Hide detail control */
											$(document).ready(function(){
												$(".details").click(function(){
													if ($('input[name=details]:checked').val()=="Yes" ) {
														$(".detailItem").slideDown("fast", $("#detailItem").css("{'display' : 'table-row'}")); 
													} 
													else {
														$(".detailItem").slideUp("fast"); 
													}
												 });
											});
										</script>
										<?php
										print "<input type='submit' value='" . _('Go') . "'>" ;
									print "</td>" ;
								print "</tr>" ;
							print"</table>" ;
						print "</form>" ;
	
						//Get class list
						try {
							$dataList["gibbonPersonID"]=$gibbonPersonID; 
							$sqlList="SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourse.name, gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID $and ORDER BY course, class" ;
							$resultList=$connection2->prepare($sqlList);
							$resultList->execute($dataList);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($resultList->rowCount()>0) {
							while ($rowList=$resultList->fetch()) {
								try {
									$dataEntry=array("gibbonCourseClassID"=>$rowList["gibbonCourseClassID"], "gibbonPersonID"=>$gibbonPersonID); 
									$sqlEntry="SELECT *, gibbonMarkbookEntry.comment AS comment FROM gibbonMarkbookEntry JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) WHERE gibbonPersonIDStudent=:gibbonPersonID AND gibbonCourseClassID=:gibbonCourseClassID AND complete='Y' AND completeDate<='" . date("Y-m-d") . "' AND viewableParents='Y' ORDER BY completeDate" ;
									$resultEntry=$connection2->prepare($sqlEntry);
									$resultEntry->execute($dataEntry);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								if ($resultEntry->rowCount()>0) {
									print "<h4>" . $rowList["course"] . "." . $rowList["class"] . " <span style='font-size:85%; font-style: italic'>(" . $rowList["name"] . ")</span></h4>" ;
								
									try {
										$dataTeachers=array("gibbonCourseClassID"=>$rowList["gibbonCourseClassID"]); 
										$sqlTeachers="SELECT title, surname, preferredName FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE role='Teacher' AND gibbonCourseClassID=:gibbonCourseClassID ORDER BY surname, preferredName" ;
										$resultTeachers=$connection2->prepare($sqlTeachers);
										$resultTeachers->execute($dataTeachers);
									}
									catch(PDOException $e) { 
										print "<div class='error'>" . $e->getMessage() . "</div>" ; 
									}
									
									$teachers="<p><b>Taught by:</b> " ;
									while ($rowTeachers=$resultTeachers->fetch()) {
										$teachers=$teachers . $rowTeachers["title"] . " " . $rowTeachers["surname"] . ", " ;
									}
									$teachers=substr($teachers,0,-2) ;
									$teachers=$teachers . "</p>" ;
									print $teachers ;
				
									print "<table cellspacing='0' style='width: 100%'>" ;
									print "<tr class='head'>" ;
										print "<th style='width: 120px'>" ;
											print "Assessment" ;
										print "</th>" ;
										print "<th style='width: 75px; text-align: center'>" ;
											if ($attainmentAlternativeName!="") { print $attainmentAlternativeName ; } else { print _('Attainment') ; }
										print "</th>" ;
										print "<th style='width: 75px; text-align: center'>" ;
											if ($effortAlternativeName!="") { print $effortAlternativeName ; } else { print _('Effort') ; }
										print "</th>" ;
										print "<th>" ;
											print "Comment" ;
										print "</th>" ;
										print "<th style='width: 75px'>" ;
											print "Submission" ;
										print "</th>" ;
									print "</tr>" ;
									
									$count=0 ;
									while ($rowEntry=$resultEntry->fetch()) {
										if ($count%2==0) {
											$rowNum="even" ;
										}
										else {
											$rowNum="odd" ;
										}
										$count++ ;
										$entryCount++ ;
										
										print "<tr class=$rowNum>" ;
											print "<td>" ;
												print "<span title='" . htmlPrep($rowEntry["description"]) . "'><b><u>" . $rowEntry["name"] . "</u></b></span><br/>" ;
												print "<span style='font-size: 90%; font-style: italic; font-weight: normal'>" ;
												$unit=getUnit($connection2, $rowEntry["gibbonUnitID"], $rowEntry["gibbonHookID"], $rowEntry["gibbonCourseClassID"]) ;
												if (isset($unit[0])) {
													print $unit[0] . "<br/>" ;
													if ($unit[1]!="") {
														print "<i>" . $unit[1] . " Unit</i><br/>" ;
													}
												}
												if ($rowEntry["completeDate"]!="") {
													print "Marked on " . dateConvertBack($guid, $rowEntry["completeDate"]) . "<br/>" ;
												}
												else {
													print "Unmarked<br/>" ;
												}
												print $rowEntry["type"] ;
												if ($rowEntry["attachment"]!="" AND file_exists($_SESSION[$guid]["absolutePath"] . "/" . $rowEntry["attachment"])) {
													print " | <a 'title='Download more information' href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $rowEntry["attachment"] . "'>More info</a>"; 
												}
												print "</span><br/>" ;
											print "</td>" ;
											print "<td style='text-align: center'>" ;
												$attainmentExtra="" ;
												try {
													$dataAttainment=array("gibbonScaleID"=>$rowEntry["gibbonScaleIDAttainment"]); 
													$sqlAttainment="SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID" ;
													$resultAttainment=$connection2->prepare($sqlAttainment);
													$resultAttainment->execute($dataAttainment);
												}
												catch(PDOException $e) { 
													print "<div class='error'>" . $e->getMessage() . "</div>" ; 
												}
												if ($resultAttainment->rowCount()==1) {
													$rowAttainment=$resultAttainment->fetch() ;
													$attainmentExtra="<br/>" . _($rowAttainment["usage"]) ;
												}
												$styleAttainment="style='font-weight: bold'" ;
												if ($rowEntry["attainmentConcern"]=="Y" AND $showParentAttainmentWarning=="Y") {
													$styleAttainment="style='color: #" . $alert["color"] . "; font-weight: bold; border: 2px solid #" . $alert["color"] . "; padding: 2px 4px; background-color: #" . $alert["colorBG"] . "'" ;
												}
												else if ($rowEntry["attainmentConcern"]=="P" AND $showParentAttainmentWarning=="Y") {
													$styleAttainment="style='color: #390; font-weight: bold; border: 2px solid #390; padding: 2px 4px; background-color: #D4F6DC'" ;
												}
												print "<div $styleAttainment>" . $rowEntry["attainmentValue"] ;
													if ($rowEntry["gibbonRubricIDAttainment"]!="") {
														print "<a class='thickbox' href='" . $_SESSION[$guid]["absoluteURL"] . "/fullscreen.php?q=/modules/Markbook/markbook_view_rubric.php&gibbonRubricID=" . $rowEntry["gibbonRubricIDAttainment"] . "&gibbonCourseClassID=" . $rowEntry["gibbonCourseClassID"] . "&gibbonMarkbookColumnID=" . $rowEntry["gibbonMarkbookColumnID"] . "&gibbonPersonID=$gibbonPersonID&mark=FALSE&type=attainment&width=1100&height=550'><img style='margin-bottom: -3px; margin-left: 3px' title='View Rubric' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/rubric.png'/></a>" ;
													}
												print "</div>" ;
												if ($rowEntry["attainmentValue"]!="") {
													print "<div class='detailItem' style='font-size: 75%; font-style: italic; margin-top: 2px'><b>" . htmlPrep(_($rowEntry["attainmentDescriptor"])) . "</b>" . _($attainmentExtra) . "</div>" ;
												}
											print "</td>" ;
											print "<td style='text-align: center'>" ;
												$effortExtra="" ;
												try {
													$dataEffort=array("gibbonScaleID"=>$rowEntry["gibbonScaleIDEffort"]); 
													$sqlEffort="SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID" ;
													$resultEffort=$connection2->prepare($sqlEffort);
													$resultEffort->execute($dataEffort);
												}
												catch(PDOException $e) { 
													print "<div class='error'>" . $e->getMessage() . "</div>" ; 
												}
												if ($resultEffort->rowCount()==1) {
													$rowEffort=$resultEffort->fetch() ;
													$effortExtra="<br/>" . _($rowEffort["usage"]) ;
												}
												$styleEffort="style='font-weight: bold'" ;
												if ($rowEntry["effortConcern"]=="Y" AND $showParentEffortWarning=="Y") {
													$styleEffort="style='color: #" . $alert["color"] . "; font-weight: bold; border: 2px solid #" . $alert["color"] . "; padding: 2px 4px; background-color: #" . $alert["colorBG"] . "'" ;
												}
												print "<div $styleEffort>" . $rowEntry["effortValue"] ;
													if ($rowEntry["gibbonRubricIDEffort"]!="") {
														print "<a class='thickbox' href='" . $_SESSION[$guid]["absoluteURL"] . "/fullscreen.php?q=/modules/Markbook/markbook_view_rubric.php&gibbonRubricID=" . $rowEntry["gibbonRubricIDEffort"] . "&gibbonCourseClassID=" . $rowEntry["gibbonCourseClassID"] . "&gibbonMarkbookColumnID=" . $rowEntry["gibbonMarkbookColumnID"] . "&gibbonPersonID=$gibbonPersonID&mark=FALSE&type=effort&width=1100&height=550'><img style='margin-bottom: -3px; margin-left: 3px' title='View Rubric' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/rubric.png'/></a>" ;
													}
												print "</div>" ;
												if ($rowEntry["effortValue"]!="") {
													print "<div class='detailItem' style='font-size: 75%; font-style: italic; margin-top: 2px'><b>" . htmlPrep(_($rowEntry["effortDescriptor"])) . "</b>" . _($effortExtra) . "</div>" ;
												}
											print "</td>" ;
											print "<td>" ;
												if ($rowEntry["comment"]!="") {
													if (strlen($rowEntry["comment"])>50) {
														print "<script type='text/javascript'>" ;	
															print "$(document).ready(function(){" ;
																print "\$(\".comment-$entryCount\").hide();" ;
																print "\$(\".show_hide-$entryCount\").fadeIn(1000);" ;
																print "\$(\".show_hide-$entryCount\").click(function(){" ;
																print "\$(\".comment-$entryCount\").fadeToggle(1000);" ;
																print "});" ;
															print "});" ;
														print "</script>" ;
														print "<span>" . substr($rowEntry["comment"], 0, 50) . "...<br/>" ;
														print "<a title='" . _('View Description') . "' class='show_hide-$entryCount' onclick='return false;' href='#'>Read more</a></span><br/>" ;
													}
													else {
														print $rowEntry["comment"] ;
													}
													if ($rowEntry["response"]!="") {
														print "<a title='Uploaded Response' href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $rowEntry["response"] . "'>Uploaded Response</a><br/>" ;
													}
												}
											print "</td>" ;
											print "<td>" ;
												if ($rowEntry["gibbonPlannerEntryID"]!="") {
													try {
														$dataSub=array(); 
														$sqlSub="SELECT * FROM gibbonPlannerEntry WHERE gibbonPlannerEntryID=" . $rowEntry["gibbonPlannerEntryID"] . " AND homeworkSubmission='Y'" ;
														$resultSub=$connection2->prepare($sqlSub);
														$resultSub->execute($dataSub);
													}
													catch(PDOException $e) { 
														print "<div class='error'>" . $e->getMessage() . "</div>" ; 
													}
													if ($resultSub->rowCount()==1) {
														$rowSub=$resultSub->fetch() ;
														try {
															$dataWork=array("gibbonPlannerEntryID"=>$rowEntry["gibbonPlannerEntryID"], "gibbonPersonID"=>$gibbonPersonID); 
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
															if ($rowWork["status"]=="Exemption") {
																$status="Exemption" ;
															}
															else if ($rowWork["status"]=="Late") {
																$style="style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px'" ;
																$status="Late" ;
															}
															else {
																$status="On Time" ;
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
															if (date("Y-m-d H:i:s")<$rowSub["homeworkDueDateTime"]) {
																print "<span title='Pending'>Pending</span>" ;
															}
															else {
																if ($row["dateStart"]>$rowSub["date"]) {
																	print "<span title='" . _('Student joined school after assessment was given.') . "' style='color: #000; font-weight: normal; border: 2px none #ff0000; padding: 2px 4px'>NA</span>" ;
																}
																else {
																	if ($rowSub["homeworkSubmissionRequired"]=="Compulsory") {
																		print "<span title='" . _('Incomplete') . "' style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px'>" . _('Incomplete') . "</span>" ;
																	}
																	else {
																		print _("Not submitted online") ;
																	}
																}
															}
														}
													}
												}
											print "</td>" ;
										print "</tr>" ;
										if (strlen($rowEntry["comment"])>50) {
											print "<tr class='comment-$entryCount' id='comment-$entryCount'>" ;
												print "<td colspan=6>" ;
													print $rowEntry["comment"] ;
												print "</td>" ;
											print "</tr>" ;
										}
										
									}
									print "</table>" ;
									
									try {
										$dataEntry=array("gibbonPersonIDStudent"=>$_SESSION[$guid]["gibbonPersonID"]); 
										$sqlEntry="SELECT gibbonMarkbookEntryID, gibbonMarkbookColumn.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonMarkbookEntry JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) JOIN gibbonCourseClass ON (gibbonMarkbookColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonPersonIDStudent=:gibbonPersonIDStudent AND complete='Y' AND completeDate<='" . date("Y-m-d") . "' AND viewableStudents='Y' ORDER BY completeDate DESC, name" ;
										$resultEntry=$connection2->prepare($sqlEntry);
										$resultEntry->execute($dataEntry);
									}
									catch(PDOException $e) { 
										print "<div class='error'>" . $e->getMessage() . "</div>" ; 
									}
									if ($resultEntry->rowCount()>0) {
										$_SESSION[$guid]["sidebarExtra"]="<h2 class='sidebar'>" ;
										$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . _("Recent Marks") ;
										$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "</h2>" ;
										
										$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<ol>" ;
										$count=0 ;
										
										while ($rowEntry=$resultEntry->fetch() AND $count<5) {
											$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "<li><a href='#" . $rowEntry["gibbonMarkbookEntryID"] . "'>" . $rowEntry["course"] . "." . $rowEntry["class"] . "<br/><span style='font-size: 85%; font-style: italic'>" . $rowEntry["name"] . "</span></a></li>" ;
											$count++ ;
										}
										
										$_SESSION[$guid]["sidebarExtra"]=$_SESSION[$guid]["sidebarExtra"] . "</ol>" ;
									}
								}
							}
						}
					}
				}
			}
			if ($entryCount<1) {
				print "<div class='error'>" ;
					print _('There are no records to display.') ;
				print "</div>" ;
			}
		}
	}
}		
?>