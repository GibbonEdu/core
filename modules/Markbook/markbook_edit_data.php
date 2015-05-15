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

if (isActionAccessible($guid, $connection2, "/modules/Markbook/markbook_edit_data.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print _("The highest grouped action cannot be determined.") ;
		print "</div>" ;
	}
	else {
		//Check if school year specified
		$gibbonCourseClassID=$_GET["gibbonCourseClassID"] ;
		$gibbonMarkbookColumnID=$_GET["gibbonMarkbookColumnID"] ;
		if ($gibbonCourseClassID=="" OR $gibbonMarkbookColumnID=="") {
			print "<div class='error'>" ;
				print _("You have not specified one or more required parameters.") ;
			print "</div>" ;
		}
		else {
			try {
				if ($highestAction=="Edit Markbook_everything") {
					$data=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
					$sql="SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList FROM gibbonCourse, gibbonCourseClass WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class" ;
				}
				else {
					$data=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonCourseClassID"=>$gibbonCourseClassID); 
					$sql="SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID, gibbonCourse.gibbonDepartmentID, gibbonYearGroupIDList FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND role='Teacher' AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID ORDER BY course, class" ;
				}	
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
		
			if ($result->rowCount()!=1) {
				print "<div class='error'>" ;
					print _("The selected record does not exist, or you do not have access to it.") ;
				print "</div>" ;
			}
			else {
				try {
					$data2=array("gibbonMarkbookColumnID"=>$gibbonMarkbookColumnID); 
					$sql2="SELECT * FROM gibbonMarkbookColumn WHERE gibbonMarkbookColumnID=:gibbonMarkbookColumnID" ;
					$result2=$connection2->prepare($sql2);
					$result2->execute($data2);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
			
				if ($result2->rowCount()!=1) {
					print "<div class='error'>" ;
						print "The selected column does not exist, or you do not have access to it." ;
					print "</div>" ;
				}
				else {
					//Let's go!
					$row=$result->fetch() ;
					$row2=$result2->fetch() ;
				
					print "<div class='trail'>" ;
					print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/markbook_view.php&gibbonCourseClassID=" . $_GET["gibbonCourseClassID"] . "'>" . _('View') . " " . $row["course"] . "." . $row["class"] . " " . _('Markbook') . "</a> > </div><div class='trailEnd'>" . _('Enter Marks') . "</div>" ;
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
							$updateReturnMessage=_("Some updates failed due to a database error.") ;	
						}
						else if ($updateReturn=="fail5") {
							$updateReturnMessage=_("Your request failed due to an attachment error.") ;	
						}
						else if ($updateReturn=="success0") {
							$updateReturnMessage=_("Your request was completed successfully.") ;	
							$class="success" ;
						}
						print "<div class='$class'>" ;
							print $updateReturnMessage;
						print "</div>" ;
					} 
				
					//Setup for WP Comment Push
					$wordpressCommentPush=getSettingByScope( $connection2, "Markbook", "wordpressCommentPush" ) ;
					if ($wordpressCommentPush=="On") {
						print "<div class='warning'>" ;
							print _("WordPress Comment Push is enabled: this feature allows you to push comments to student work submitted using a WordPress site. If you wish to push a comment, just select the checkbox next to the submitted work.") ;
						print "</div>" ;
					}
				
					print "<div class='linkTop'>" ;
					if ($row2["gibbonPlannerEntryID"]!="") {
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner_view_full.php&viewBy=class&gibbonCourseClassID=$gibbonCourseClassID&gibbonPlannerEntryID=" . $row2["gibbonPlannerEntryID"] . "'>" . _('View Linked Lesson') . "<img style='margin: 0 0 -4px 5px' title='" . _('View Linked Lesson') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/planner.png'/></a> | " ;
					}
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/markbook_edit_edit.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=$gibbonMarkbookColumnID'>" . _('Edit') . "<img style='margin: 0 0 -4px 5px' title='" . _('Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
					print "</div>" ;
			
					$columns=1 ;
				
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
				
					print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/markbook_edit_dataProcess.php?gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=$gibbonMarkbookColumnID&address=" . $_SESSION[$guid]["address"] . "' enctype='multipart/form-data'>" ;
						print "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>" ;
							print "<tr class='head'>" ;
								print "<th rowspan=2>" ;
									print _("Student") ;
								print "</th>" ;
								
								print "<th rowspan=2 style='width: 20px; text-align: center'>" ;
									$title=_("Personalised target grade") ;
								
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
										print _("Target") ;
									print "</div>" ;
								print "</th>" ;
							
								$columnID=array() ;
								$attainmentID=array() ;
								$effortID=array() ;
								$submission=FALSE ;
									
								for ($i=0;$i<$columns;$i++) {
									$columnID[$i]=$row2["gibbonMarkbookColumnID"];
									$attainmentID[$i]=$row2["gibbonScaleIDAttainment"];
									$effortID[$i]=$row2["gibbonScaleIDEffort"];
									$gibbonRubricIDAttainment[$i]=NULL ;
									if (isset($row["gibbonRubricIDAttainment"])) {
										$gibbonRubricIDAttainment[$i]=$row["gibbonRubricIDAttainment"] ;
									}
									$gibbonRubricIDEffort[$i]=NULL ;
									if (isset($row["gibbonRubricIDEffort"])) {
										$gibbonRubricIDEffort[$i]=$row["gibbonRubricIDEffort"] ;
									}
								
									//WORK OUT IF THERE IS SUBMISSION
									if (is_null($row2["gibbonPlannerEntryID"])==FALSE) {
										try {
											$dataSub=array("gibbonPlannerEntryID"=>$row2["gibbonPlannerEntryID"]); 
											$sqlSub="SELECT * FROM gibbonPlannerEntry WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND homeworkSubmission='Y'" ;
											$resultSub=$connection2->prepare($sqlSub);
											$resultSub->execute($dataSub);
										}
										catch(PDOException $e) { 
											print "<div class='error'>" . $e->getMessage() . "</div>" ; 
										}
									
										if ($resultSub->rowCount()==1) {
											$submission=TRUE ;
											$rowSub=$resultSub->fetch() ;
											$homeworkDueDateTime=$rowSub["homeworkDueDateTime"] ;
											$lessonDate[$i]=$rowSub["date"] ;
										}
									}
									
									//Column count
									$span=2 ;
									if ($submission==TRUE) {
										$span++ ;
									}
									if ($row2["attainment"]=="Y") {
										$span++ ;
									}
									if ($row2["effort"]=="Y") {
										$span++ ;
									}
									if ($row2["comment"]=="Y" OR $row2["uploadedResponse"]=="Y") {
										$span++ ;
									}
									if ($span==2) {
										$span++ ;
									}
									
									print "<th style='text-align: center' colspan=$span-2>" ;
										print "<span title='" . htmlPrep($row2["description"]) . "'>" . $row2["name"] . "<br/>" ;
										print "<span style='font-size: 90%; font-style: italic; font-weight: normal'>" ;
										if ($row2["gibbonUnitID"]!="") {
											try {
												$dataUnit=array("gibbonUnitID"=>$row2["gibbonUnitID"]); 
												$sqlUnit="SELECT * FROM gibbonUnit WHERE gibbonUnitID=:gibbonUnitID" ;
												$resultUnit=$connection2->prepare($sqlUnit);
												$resultUnit->execute($dataUnit);
											}
											catch(PDOException $e) { 
												print "<div class='error'>" . $e->getMessage() . "</div>" ; 
											}
											if ($resultUnit->rowCount()==1) {
												$rowUnit=$resultUnit->fetch() ;
												print $rowUnit["name"] . "<br/>" ;
											}
										}
										if ($row2["completeDate"]!="") {
											print _("Marked on") . " " . dateConvertBack($guid, $row2["completeDate"]) . "<br/>" ;
										}
										else {
											print _("Unmarked") . "<br/>" ;
										}
										print $row2["type"] ;
										if ($row2["attachment"]!="" AND file_exists($_SESSION[$guid]["absolutePath"] . "/" . $row2["attachment"])) {
											print " | <a style='color: #ffffff' 'title='" . _('Download more information') . "' href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $row2["attachment"] . "'>" . _('More info') . "</a>"; 
										}
										print "</span><br/>" ;
									print "</th>" ;
								}
							print "</tr>" ;
						
							print "<tr class='head'>" ;
								for ($i=0;$i<$columns;$i++) {
									if ($submission==TRUE) {
										print "<th style='text-align: center; max-width: 30px'>" ;
											print "<span title='" . _('Submitted Work') . "'>" . _('Sub') . "</span>" ;
										print "</th>" ;
									}
									if ($row2["attainment"]=="Y") {
										print "<th style='text-align: center; width: 30px'>" ;
											$scale="" ;
											if ($attainmentID[$i]!="") {
												try {
													$dataScale=array("gibbonScaleID"=>$attainmentID[$i]); 
													$sqlScale="SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID" ;
													$resultScale=$connection2->prepare($sqlScale);
													$resultScale->execute($dataScale);
												}
												catch(PDOException $e) { }
												if ($resultScale->rowCount()==1) {
													$rowScale=$resultScale->fetch() ;
													$scale=" - " . $rowScale["name"] ;
													if ($rowScale["usage"]!="") {
														$scale=$scale . ": " . $rowScale["usage"] ;
													}
												}
												$gibbonScaleIDAttainment=$rowScale["gibbonScaleID"] ;
												print "<input name='scaleAttainment' id='scaleAttainment' value='" . $attainmentID[$i] . "' type='hidden'>" ;
												print "<input name='lowestAcceptableAttainment' id='lowestAcceptableAttainment' value='" . $rowScale["lowestAcceptable"] . "' type='hidden'>" ;
											}
											if ($attainmentAlternativeName!="" AND $attainmentAlternativeNameAbrev!="") {
												print "<span title='" . $attainmentAlternativeName . htmlPrep($scale) . "'>" . $attainmentAlternativeNameAbrev . "</span>" ;
											}
											else {
												print "<span title='" . _('Attainment') . htmlPrep($scale) . "'>" . _('Att') . "</span>" ;
											}
										print "</th>" ;
									}
									if ($row2["effort"]=="Y") {
										print "<th style='text-align: center; width: 30px'>" ;
											$scale="" ;
											if ($effortID[$i]!="") {
												try {
													$dataScale=array("gibbonScaleID"=>$effortID[$i]); 
													$sqlScale="SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID" ;
													$resultScale=$connection2->prepare($sqlScale);
													$resultScale->execute($dataScale);
												}
												catch(PDOException $e) { }
												$scale="" ;
												if ($resultScale->rowCount()==1) {
													$rowScale=$resultScale->fetch() ;
													$scale=" - " . $rowScale["name"] ;
													if ($rowScale["usage"]!="") {
														$scale=$scale . ": " . $rowScale["usage"] ;
													}
												}
												$gibbonScaleIDEffort=$rowScale["gibbonScaleID"] ;
												print "<input name='scaleEffort' id='scaleEffort' value='" . $effortID[$i] . "' type='hidden'>" ;
												print "<input name='lowestAcceptableEffort' id='lowestAcceptableEffort' value='" . $rowScale["lowestAcceptable"] . "' type='hidden'>" ;
											}
											if ($effortAlternativeName!="" AND $effortAlternativeNameAbrev!="") {
												print "<span title='" . $effortAlternativeName . htmlPrep($scale) . "'>" . $effortAlternativeNameAbrev . "</span>" ;
											}
											else {
												print "<span title='" . _('Effort') . htmlPrep($scale) . "'>" . _('Eff') . "</span>" ;
											}
										print "</th>" ;
									}
									if ($row2["comment"]=="Y" OR $row2["uploadedResponse"]=="Y") {
										print "<th style='text-align: center; width: 80'>" ;
											print "<span title='" . _('Comment') . "'>" . _('Com') . "</span>" ;
										print "</th>" ;
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
											print "<div style='padding: 2px 0px'>" . ($count) . ") <b><a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $rowStudents["gibbonPersonID"] . "&subpage=Markbook#" . $gibbonCourseClassID . "'>" . formatName("", $rowStudents["preferredName"], $rowStudents["surname"], "Student", true) . "</a><br/></div>" ;
										print "</td>" ;
										
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
									
										for ($i=0;$i<$columns;$i++) {
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
										
										
											$rowEntry=$resultEntry->fetch() ;
											if ($submission==TRUE) {
												print "<td style='text-align: left ; width: 40px'>" ;
													try {
														$dataWork=array("gibbonPlannerEntryID"=>$row2["gibbonPlannerEntryID"], "gibbonPersonID"=>$rowStudents["gibbonPersonID"]); 
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
															print "<span title='" . $rowWork["version"] . ". $status. " . _('Submitted at') . " " . substr($rowWork["timestamp"],11,5) . " " . _('on') . " " . dateConvertBack($guid, substr($rowWork["timestamp"],0,10)) . "' $style><a target='_blank' href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $rowWork["location"] ."'>$linkText</a></span>" ;
														}
														else if ($rowWork["type"]=="Link") {
															print "<span title='" . $rowWork["version"] . ". $status. " . _('Submitted at') . " " . substr($rowWork["timestamp"],11,5) . " " . _('on') . " " . dateConvertBack($guid, substr($rowWork["timestamp"],0,10)) . "' $style><a target='_blank' href='" . $rowWork["location"] ."'>$linkText</a></span>" ;
															if ($wordpressCommentPush=="On") {
																print "<div id='wordpressCommentPush$count' style='float: right'>" ;
																print "</div>" ;
																print "<script type=\"text/javascript\">" ;
																	print "$(\"#wordpressCommentPush$count\").load(\"" . $_SESSION[$guid]["absoluteURL"] . "/modules/Markbook/markbook_edit_dataAjax.php\", { location: \"" . $rowWork["location"] . "\", count: \"" . $count . "\" } );" ;
																print "</script>" ;
															}
														}
														else {
															print "<span title='$status. " . _('Recorded at') . " " . substr($rowWork["timestamp"],11,5) . " " . _('on') . " " . dateConvertBack($guid, substr($rowWork["timestamp"],0,10)) . "' $style>$linkText</span>" ;
														}
													}
													else {
														if (date("Y-m-d H:i:s")<$homeworkDueDateTime) {
															print "<span title='" . _('Pending') . "'>" . _('Pen') . "</span>" ;
														}
														else {
															if ($rowStudents["dateStart"]>$lessonDate[$i]) {
																print "<span title='" . _('Student joined school after assessment was given.') . "' style='color: #000; font-weight: normal; border: 2px none #ff0000; padding: 2px 4px'>NA</span>" ;
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
											if ($row2["attainment"]=="Y") {
												print "<td style='text-align: center'>" ;
													//Create attainment grade select
													if ($row2["gibbonScaleIDAttainment"]!="") {
														print "<select name='$count-attainmentValue' id='$count-attainmentValue' style='width:50px'>" ;
															try {
																$dataSelect=array("gibbonScaleID"=>$gibbonScaleIDAttainment); 
																$sqlSelect="SELECT * FROM gibbonScaleGrade WHERE gibbonScaleID=:gibbonScaleID ORDER BY sequenceNumber" ;
																$resultSelect=$connection2->prepare($sqlSelect);
																$resultSelect->execute($dataSelect);
															}
															catch(PDOException $e) { }
															print "<option value=''></option>" ;
															$sequence="" ;
															$descriptor="" ;
															while ($rowSelect=$resultSelect->fetch()) {
																if ($rowEntry["attainmentValue"]==$rowSelect["value"]) {
																	print "<option selected value='" . htmlPrep($rowSelect["value"]) . "'>" . htmlPrep(_($rowSelect["value"])) . "</option>" ;
																}
																else {
																	print "<option value='" . htmlPrep($rowSelect["value"]) . "'>" . htmlPrep(_($rowSelect["value"])) . "</option>" ;
																}
															}			
														print "</select>" ;
													}
													print "<div style='height: 20px'>" ;
														if ($row2["gibbonRubricIDAttainment"]!="") {
															print "<a class='thickbox' href='" . $_SESSION[$guid]["absoluteURL"] . "/fullscreen.php?q=/modules/" . $_SESSION[$guid]["module"] . "/markbook_view_rubric.php&gibbonRubricID=" . $row2["gibbonRubricIDAttainment"] . "&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=$gibbonMarkbookColumnID&gibbonPersonID=" . $rowStudents["gibbonPersonID"] . "&type=attainment&width=1100&height=550'><img style='margin-top: 3px' title='" . _('Mark Rubric') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/rubric.png'/></a>" ;
														}
													print "</div>" ;
												print "</td>" ;
											}
											if ($row2["effort"]=="Y") {
												print "<td style='text-align: center'>" ;
													if ($row2["gibbonScaleIDEffort"]!="") {
														print "<select name='$count-effortValue' id='$count-effortValue' style='width:50px'>" ;
															try {
																$dataSelect=array("gibbonScaleID"=>$gibbonScaleIDEffort); 
																$sqlSelect="SELECT * FROM gibbonScaleGrade WHERE gibbonScaleID=:gibbonScaleID ORDER BY sequenceNumber" ;
																$resultSelect=$connection2->prepare($sqlSelect);
																$resultSelect->execute($dataSelect);
															}
															catch(PDOException $e) { }
															print "<option value=''></option>" ;
															$sequence="" ;
															$descriptor="" ;
															while ($rowSelect=$resultSelect->fetch()) {
																if ($rowEntry["effortValue"]==$rowSelect["value"]) {
																	print "<option selected value='" . htmlPrep($rowSelect["value"]) . "'>" . htmlPrep(_($rowSelect["value"])) . "</option>" ;
																}
																else {
																	print "<option value='" . htmlPrep($rowSelect["value"]) . "'>" . htmlPrep(_($rowSelect["value"])) . "</option>" ;
																}
															}
														print "</select>" ;
													}
													print "<div style='height: 20px'>" ;
														if ($row2["gibbonRubricIDEffort"]!="") {
															print "<a class='thickbox' href='" . $_SESSION[$guid]["absoluteURL"] . "/fullscreen.php?q=/modules/" . $_SESSION[$guid]["module"] . "/markbook_view_rubric.php&gibbonRubricID=" . $row2["gibbonRubricIDEffort"] . "&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=$gibbonMarkbookColumnID&gibbonPersonID=" . $rowStudents["gibbonPersonID"] . "&type=effort&width=1100&height=550'><img style='margin-top: 3px' title='" . _('Mark Rubric') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/rubric.png'/></a>" ;
														}
													print "</div>" ;
												print "</td>" ;
											}
											if ($row2["comment"]=="Y" OR $row2["uploadedResponse"]=="Y") {
												print "<td style='text-align: right'>" ;
													if ($row2["comment"]=="Y") {
														print "<script type='text/javascript'>" ;
															print "$(document).ready(function(){" ;
																print "$('textarea').autosize();" ;    
															print "});" ;
														print "</script>" ;
												
														print "<textarea name='comment" . $count . "' id='comment" . $count . "' rows=6 style='width: 330px'>" . $rowEntry["comment"] . "</textarea>" ;
														if ($row2["uploadedResponse"]=="Y") {
															print "<br/>" ;
														}
													}
													if ($row2["uploadedResponse"]=="Y") {
														if ($rowEntry["response"]!="") {
															print "<input type='hidden' name='response$count' id='response$count' value='" . $rowEntry["response"] . "'>" ;														
															print "<div style='width: 330px; float: right'><a target='_blank' href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $rowEntry["response"] . "'>" . _('Uploaded Response') . "</a> <a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/Markbook/markbook_edit_data_responseDeleteProcess.php?gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=$gibbonMarkbookColumnID&gibbonPersonID=" . $rowStudents["gibbonPersonID"] . "' onclick='return confirm(\"" . _('Are you sure you want to delete this record? Unsaved changes will be lost.') . "\")'><img style='margin-bottom: -8px' id='image_75_delete' title='" . _('Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a><br/></div>" ;
														}
														else {
															print "<input style='margin-top: 5px' type='file' name='response$count' id='response$count'>" ;														
															?>
															<script type="text/javascript">
																var <?php print "response$count" ?>=new LiveValidation('<?php print "response$count" ?>');
																<?php print "response$count" ?>.add( Validate.Inclusion, { within: [<?php print $ext ;?>], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
															</script>
															<?php
														}
													}
												print "</td>" ;
											}
											print "<input name='$count-gibbonPersonID' id='$count-gibbonPersonID' value='" . $rowStudents["gibbonPersonID"] . "' type='hidden'>" ;
										}
									print "</tr>" ;
								}
							}
							?>
							<tr class='break'>
								<?php
								print "<td colspan=" . ($span) . ">" ;
								?>
									<h3>Assessment Complete?</h3>
								</td>
							</tr>
							<tr>
								<?php
								print "<td>" ;
								?>
									<b><?php print _('Go Live Date') ?></b><br/>
								<span style="font-size: 90%"><i><?php print _('1. Format') ?> <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?><br/><?php print _('2. Column is hidden until date is reached.') ?></i></span>
								</td>
								<td class="right" colspan="<?php print $span-1 ?>">
									<input name="completeDate" id="completeDate" maxlength=10 value="<?php print dateConvertBack($guid, $row2["completeDate"]) ?>" type="text" style="width: 300px">
									<script type="text/javascript">
										var completeDate=new LiveValidation('completeDate');
										completeDate.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  print "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { print $_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } ?>, failureMessage: "Use <?php if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; }?>." } ); 
									 </script>
									 <script type="text/javascript">
										$(function() {
											$( "#completeDate" ).datepicker();
										});
									</script>
								</td>
							</tr>
							<tr>
								<?php
								print "<td style='text-align: left'>" ;
									print getMaxUpload(TRUE) ;
								print "</td>" ;
								print "<td class='right' colspan=" . ($span-1) . ">" ;
								?>
									<input name="count" id="count" value="<?php print $count ?>" type="hidden">
									<input type="submit" value="<?php print _("Submit") ; ?>">
								
								</td>
							</tr>
							<?php
						print "</table>" ;
					print "</form>" ;
				}
			}
		}
	
		//Print sidebar
		$_SESSION[$guid]["sidebarExtra"]=sidebarExtra($guid, $connection2, $gibbonCourseClassID) ;
	}
}
?>