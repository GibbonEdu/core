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

if (isActionAccessible($guid, $connection2, "/modules/Planner/units_edit_working.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
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
		//IF UNIT DOES NOT CONTAIN HYPHEN, IT IS A GIBBON UNIT
		$gibbonUnitID=$_GET["gibbonUnitID"]; 
		if (strpos($gibbonUnitID,"-")==FALSE) {
			$hooked=FALSE ;
		}
		else {
			$hooked=TRUE ;
			$gibbonHookIDToken=substr($gibbonUnitID,11) ;
			$gibbonUnitIDToken=substr($gibbonUnitID,0,10) ;
		}
		
		//Proceed!
		print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/units.php&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "&gibbonCourseID=" . $_GET["gibbonCourseID"] . "'>" . _('Unit Planner') . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/units_edit.php&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "&gibbonCourseID=" . $_GET["gibbonCourseID"] . "&gibbonUnitID=" . $_GET["gibbonUnitID"] . "'>" . _('Edit Unit') . "</a> > </div><div class='trailEnd'>" . _('Edit Working Copy') . "</div>" ;
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
		
		if (isset($_GET["addReturn"])) { $addReturn=$_GET["addReturn"] ; } else { $addReturn="" ; }
		$addReturnMessage="" ;
		$class="error" ;
		if (!($addReturn=="")) {
			if ($addReturn=="fail0") {
				$addReturnMessage=_("Your request failed because you do not have access to this action.") ;	
			}
			else if ($addReturn=="fail2") {
				$addReturnMessage=_("Your request failed due to a database error.") ;	
			}
			else if ($addReturn=="fail3") {
				$addReturnMessage=_("Your request failed because your inputs were invalid.") ;	
			}
			else if ($addReturn=="fail6") {
				$addReturnMessage=_("Your request was successful, but some data was not properly saved.") ;	
				$class="success" ;
			}
			else if ($addReturn=="success0") {
				$addReturnMessage=_("Your request was completed successfully.") ;	
				$class="success" ;
			}
			print "<div class='$class'>" ;
				print $addReturnMessage;
			print "</div>" ;
		} 
		
		//Check if courseschool year specified
		$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"];
		$gibbonCourseID=$_GET["gibbonCourseID"]; 
		$gibbonCourseClassID=$_GET["gibbonCourseClassID"]; 
		$gibbonUnitID=$_GET["gibbonUnitID"]; 
		$gibbonUnitClassID=$_GET["gibbonUnitClassID"]; 
		if ($gibbonCourseID=="" OR $gibbonSchoolYearID=="" OR $gibbonCourseClassID=="" OR $gibbonUnitClassID=="") {
			print "<div class='error'>" ;
				print _("You have not specified one or more required parameters.") ;
			print "</div>" ;
		}
		else {
			try {
				if ($highestAction=="Unit Planner_all") {
					$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonCourseID"=>$gibbonCourseID, "gibbonCourseClassID"=>$gibbonCourseClassID); 
					$sql="SELECT *, gibbonSchoolYear.name AS year, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourse.gibbonCourseID=:gibbonCourseID AND gibbonCourseClassID=:gibbonCourseClassID" ;
				}
				else if ($highestAction=="Unit Planner_learningAreas") {
					$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonCourseID"=>$gibbonCourseID, "gibbonCourseClassID"=>$gibbonCourseClassID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
					$sql="SELECT gibbonCourse.gibbonCourseID, gibbonCourse.name, gibbonCourse.nameShort, gibbonSchoolYear.name AS year, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourse.gibbonCourseID=:gibbonCourseID AND gibbonCourseClassID=:gibbonCourseClassID ORDER BY gibbonCourse.nameShort" ;
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
				$row=$result->fetch() ;
				$year=$row["year"] ;
				$course=$row["course"] ;
				$class=$row["class"] ;
				
				//Check if unit specified
				if ($gibbonUnitID=="") {
					print "<div class='error'>" ;
						print _("You have not specified one or more required parameters.") ;
					print "</div>" ;
				}
				else {
					if ($hooked==FALSE) {
						try {
							$data=array("gibbonUnitID"=>$gibbonUnitID, "gibbonCourseID"=>$gibbonCourseID); 
							$sql="SELECT gibbonCourse.nameShort AS courseName, gibbonUnit.* FROM gibbonUnit JOIN gibbonCourse ON (gibbonUnit.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonUnitID=:gibbonUnitID AND gibbonUnit.gibbonCourseID=:gibbonCourseID" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
					}
					else {
						try {
							$dataHooks=array("gibbonHookID"=>$gibbonHookIDToken); 
							$sqlHooks="SELECT * FROM gibbonHook WHERE type='Unit' AND gibbonHookID=:gibbonHookID ORDER BY name" ;
							$resultHooks=$connection2->prepare($sqlHooks);
							$resultHooks->execute($dataHooks);
						}
						catch(PDOException $e) { }
						if ($resultHooks->rowCount()==1) {
							$rowHooks=$resultHooks->fetch() ;
							$hookOptions=unserialize($rowHooks["options"]) ;
							if ($hookOptions["unitTable"]!="" AND $hookOptions["unitIDField"]!="" AND $hookOptions["unitCourseIDField"]!="" AND $hookOptions["unitNameField"]!="" AND $hookOptions["unitDescriptionField"]!="" AND $hookOptions["classLinkTable"]!="" AND $hookOptions["classLinkJoinFieldUnit"]!="" AND $hookOptions["classLinkJoinFieldClass"]!="" AND $hookOptions["classLinkIDField"]!="") {
								try {
									$data=array("unitIDField"=>$gibbonUnitIDToken); 
									$sql="SELECT " . $hookOptions["unitTable"] . ".*, gibbonCourse.nameShort FROM " . $hookOptions["unitTable"] . " JOIN gibbonCourse ON (" . $hookOptions["unitTable"] . "." . $hookOptions["unitCourseIDField"] . "=gibbonCourse.gibbonCourseID) WHERE " . $hookOptions["unitIDField"] . "=:unitIDField" ;
									$result=$connection2->prepare($sql);
									$result->execute($data);
								}
								catch(PDOException $e) { }									
							}
						}
					}
					
					if ($result->rowCount()!=1) {
						print "<div class='error'>" ;
							print _("The specified record cannot be found.") ;
						print "</div>" ;
					}
					else {
						//Let's go!
						$row=$result->fetch() ;
						
						print "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>" ;
							print "<tr>" ;
								print "<td style='width: 34%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('School Year') . "</span><br/>" ;
									print "<i>" . $year . "</i>" ;
									print "</td>" ;
								print "<td style='width: 33%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Class') . "</span><br/>" ;
									print "<i>" . $course . "." . $class . "</i>" ;
								print "</td>" ;
								print "<td style='width: 34%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>" . _('Unit') . "</span><br/>" ;
									print "<i>" . $row["name"] . "</i>" ;
								print "</td>" ;
							print "</tr>" ;
						print "</table>" ;
						
						
						print "<h3>" ;
						print _("Lessons & Blocks") ;
						print "</h3>" ;
						print "<p>" ;
						print _("You can now add your unit blocks using the dropdown menu in each lesson. Blocks can be dragged from one lesson to another.") ;
						print "</p>" ;
						
						//Store UNIT BLOCKS in array
						$blocks=array() ;
						try {
							if ($hooked==FALSE) {
								$dataBlocks=array("gibbonUnitID"=>$gibbonUnitID); 
								$sqlBlocks="SELECT * FROM gibbonUnitBlock WHERE gibbonUnitID=:gibbonUnitID ORDER BY sequenceNumber" ;
							}
							else {
								$dataBlocks=array("classLinkJoinFieldUnit"=>$gibbonUnitIDToken, "classLinkJoinFieldClass"=>$gibbonCourseClassID); 
								$sqlBlocks="SELECT " . $hookOptions["unitSmartBlockTable"] . ".* FROM " . $hookOptions["unitSmartBlockTable"] . " JOIN " . $hookOptions["classLinkTable"] . " ON (" . $hookOptions["unitSmartBlockTable"] . "." . $hookOptions["unitSmartBlockJoinField"] . "=" . $hookOptions["classLinkTable"] . "." . $hookOptions["classLinkJoinFieldUnit"] . ") JOIN " . $hookOptions["unitTable"] . " ON (" . $hookOptions["classLinkTable"] . "." . $hookOptions["classLinkJoinFieldUnit"] . "=" . $hookOptions["unitTable"] . "." . $hookOptions["unitIDField"] . ") WHERE " . $hookOptions["classLinkTable"] . "." . $hookOptions["classLinkJoinFieldUnit"] . "=:classLinkJoinFieldUnit AND " . $hookOptions["classLinkTable"] . "." . $hookOptions["classLinkJoinFieldClass"] . "=:classLinkJoinFieldClass ORDER BY sequenceNumber" ;
							}
							$resultBlocks=$connection2->prepare($sqlBlocks);
							$resultBlocks->execute($dataBlocks);
							$resultLessonBlocks=$connection2->prepare($sqlBlocks);
							$resultLessonBlocks->execute($dataBlocks);	
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						$blockCount=0 ;
						while ($rowBlocks=$resultBlocks->fetch()) {
							if ($hooked==FALSE) {
								$blocks[$blockCount][0]=$rowBlocks["gibbonUnitBlockID"];
								$blocks[$blockCount][1]=$rowBlocks["title"];
								$blocks[$blockCount][2]=$rowBlocks["type"];
								$blocks[$blockCount][3]=$rowBlocks["length"];
								$blocks[$blockCount][4]=$rowBlocks["contents"];
								$blocks[$blockCount][5]=$rowBlocks["teachersNotes"];
								$blocks[$blockCount][5]=$rowBlocks["teachersNotes"];
							}
							else {
								$blocks[$blockCount][0]=$rowBlocks[$hookOptions["unitSmartBlockIDField"]];
								$blocks[$blockCount][1]=$rowBlocks[$hookOptions["unitSmartBlockTitleField"]];
								$blocks[$blockCount][2]=$rowBlocks[$hookOptions["unitSmartBlockTypeField"]];
								$blocks[$blockCount][3]=$rowBlocks[$hookOptions["unitSmartBlockLengthField"]];
								$blocks[$blockCount][4]=$rowBlocks[$hookOptions["unitSmartBlockContentsField"]];
								$blocks[$blockCount][5]=$rowBlocks[$hookOptions["unitSmartBlockTeachersNotesField"]];
							}
							$blockCount++ ;
						}
						
						//Store STAR BLOCKS in array
						$blocks2=array() ;
						try {
							$dataBlocks2=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
							$sqlBlocks2="SELECT * FROM gibbonUnitBlockStar JOIN gibbonUnitBlock ON (gibbonUnitBlockStar.gibbonUnitBlockID=gibbonUnitBlock.gibbonUnitBlockID) WHERE gibbonPersonID=:gibbonPersonID ORDER BY title" ;
							$resultBlocks2=$connection2->prepare($sqlBlocks2);
							$resultBlocks2->execute($dataBlocks2);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						$blockCount2=0 ;
						while ($rowBlocks2=$resultBlocks2->fetch()) {
							$blocks2[$blockCount2][0]=$rowBlocks2["gibbonUnitBlockID"] ;
							$blocks2[$blockCount2][1]=$rowBlocks2["title"];
							$blocks2[$blockCount2][2]=$rowBlocks2["type"];
							$blocks2[$blockCount2][3]=$rowBlocks2["length"];
							$blocks2[$blockCount2][4]=$rowBlocks2["contents"];
							$blocks2[$blockCount2][5]=$rowBlocks2["teachersNotes"];
							$blocks2[$blockCount2][6]=$rowBlocks2["gibbonOutcomeIDList"];
							$blockCount2++ ;
						}
							
						
						print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/units_edit_workingProcess.php?gibbonUnitID=$gibbonUnitID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseID=$gibbonCourseID&gibbonCourseClassID=$gibbonCourseClassID&address=" . $_GET["q"] . "&gibbonUnitClassID=$gibbonUnitClassID'>" ;
							//LESSONS (SORTABLES)
							print "<div class='linkTop'>" ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/units_edit_working_add.php&gibbonUnitID=$gibbonUnitID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseID=$gibbonCourseID&gibbonCourseClassID=$gibbonCourseClassID&gibbonUnitClassID=$gibbonUnitClassID'>" .  _('Add') . "<img style='margin-left: 5px' title='" . _('Add') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.png'/></a>" ;
							print "</div>" ;
							print "<div style='width: 100%; height: auto'>" ;
								try {
									$dataLessons=array("gibbonCourseClassID"=>$gibbonCourseClassID, "gibbonUnitID"=>$gibbonUnitID); 
									$sqlLessons="SELECT * FROM gibbonPlannerEntry WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonUnitID=:gibbonUnitID ORDER BY date, timeStart" ;
									$resultLessons=$connection2->prepare($sqlLessons);
									$resultLessons->execute($dataLessons);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}

								if ($resultLessons->rowCount()<1) {
									print "<div class='error'>" ;
									print _("There are no records to display.") ;
									print "</div>" ;
								}
								else {
									$i=0 ;
									$blockCount2=$blockCount ;
									while ($rowLessons=$resultLessons->fetch()) {
										print "<div class='lessonInner' id='lessonInner$i' style='min-height: 60px; border: 1px solid #333; width: 100%; margin-bottom: 65px; float: left; padding: 2px; background-color: #F7F0E3'>" ;
											print "<div class='sortable' id='sortable$i' style='height: auto!important; min-height: 60px; font-size: 120%; font-style: italic'>" ;
												print "<div id='head$i' class='head' style='height: 54px; font-size: 85%; padding: 3px'>" ;
													
													print "<a onclick='return confirm(\"Are you sure you want to jump to this lesson? Any unsaved changes will be lost.\")' style='font-weight: bold; font-style: normal; color: #333' href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Planner/planner_view_full.php&viewBy=class&gibbonCourseClassID=" . $rowLessons["gibbonCourseClassID"] . "&gibbonPlannerEntryID=" . $rowLessons["gibbonPlannerEntryID"] . "'>" . ($i+1) . ". " . $rowLessons["name"] . "</a> <a onclick='return confirm(\"" . _('Are you sure you want to delete this record? Any unsaved changes will be lost.') . "\")' href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/units_edit_working_lessonDelete.php?gibbonUnitID=$gibbonUnitID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseID=$gibbonCourseID&gibbonCourseClassID=$gibbonCourseClassID&gibbonUnitClassID=$gibbonUnitClassID&address=" . $_GET["q"] . "&gibbonPlannerEntryID=" . $rowLessons["gibbonPlannerEntryID"] . "'><img title='" . _('Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/ style='position: absolute; margin: -1px 0px 2px 10px'></a><br/>" ;
													
													try {
														$dataTT=array("date"=>$rowLessons["date"], "timeStart"=>$rowLessons["timeStart"], "timeEnd"=>$rowLessons["timeEnd"], "gibbonCourseClassID"=>$gibbonCourseClassID); 
														$sqlTT="SELECT timeStart, timeEnd, date, gibbonTTColumnRow.name AS period, gibbonTTDayRowClassID, gibbonTTDayDateID FROM gibbonTTDayRowClass JOIN gibbonTTColumnRow ON (gibbonTTDayRowClass.gibbonTTColumnRowID=gibbonTTColumnRow.gibbonTTColumnRowID) JOIN gibbonTTColumn ON (gibbonTTColumnRow.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) JOIN gibbonTTDay ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) WHERE date=:date AND timeStart=:timeStart AND timeEnd=:timeEnd AND gibbonCourseClassID=:gibbonCourseClassID ORDER BY date, timestart" ;
														$resultTT=$connection2->prepare($sqlTT);
														$resultTT->execute($dataTT);
													}
													catch(PDOException $e) { 
														print "<div class='error'>" . $e->getMessage() . "</div>" ; 
													}

													if ($resultTT->rowCount()==1) {
														$rowTT=$resultTT->fetch() ;
														print "<span style='font-size: 80%'><i>" . date("D jS M, Y", dateConvertToTimestamp($rowLessons["date"])) . "<br/>" . $rowTT["period"] . " (" . substr($rowLessons["timeStart"],0,5) . " - " . substr($rowLessons["timeEnd"],0,5) . ")</i></span>" ;
													}
													else {
														print "<span style='font-size: 80%'><i>" ;
														if ($rowLessons["date"]!="") {
															print date("D jS M, Y", dateConvertToTimestamp($rowLessons["date"])) . "<br/>" ;
															print substr($rowLessons["timeStart"],0,5) . " - " . substr($rowLessons["timeEnd"],0,5) . "</i>" ;
														}
														else {
															print "Date not set<br/>" ;
														}
														print "</span>" ;
													}
													
													print "<input type='hidden' name='order[]' value='lessonHeader-$i' >" ;
													print "<input type='hidden' name='date$i' value='" . $rowLessons["date"] . "' >" ;
													print "<input type='hidden' name='timeStart$i' value='" . $rowLessons["timeStart"] . "' >" ;
													print "<input type='hidden' name='timeEnd$i' value='" . $rowLessons["timeEnd"] . "' >" ;
													print "<input type='hidden' name='gibbonPlannerEntryID$i' value='" . $rowLessons["gibbonPlannerEntryID"] . "' >" ;
													print "<div style='text-align: right; float: right; margin-top: -33px; margin-right: 3px'>" ;
														print "<span style='font-size: 80%'><i>" . _('Add Block:') . "</i></span><br/>" ; 
														print "<script type='text/javascript'>" ;
															print "$(document).ready(function(){" ;
																print "$(\"#blockAdd$i\").change(function(){" ;
																	print "if ($(\"#blockAdd$i\").val()!='') {" ;
																		print "$(\"#sortable$i\").append('<div id=\'blockOuter' + count + '\' class=\'blockOuter\'><div class=\'odd\' style=\'text-align: center; font-size: 75%; height: 60px; border: 1px solid #d8dcdf; margin: 0 0 5px\' id=\'block$i\' style=\'padding: 0px\'><img style=\'margin: 10px 0 5px 0\' src=\'" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/loading.gif\' alt=\'Loading\' onclick=\'return false;\' /><br/>Loading</div></div>');" ;
																		print "$(\"#blockOuter\" + count).load(\"" . $_SESSION[$guid]["absoluteURL"] . "/modules/Planner/units_add_blockAjax.php?mode=workingDeploy&gibbonUnitID=$gibbonUnitID&gibbonUnitBlockID=\" + $(\"#blockAdd$i\").val(),\"id=\" + count) ;" ;
																		print "count++ ;" ;
																	print "}" ;	
																print "}) ;" ;
															print "}) ;" ;
														print "</script>" ;
														print "<select name='blockAdd$i' id='blockAdd$i' style='width: 150px'>" ;
															print "<option value=''></option>" ;
															print "<optgroup label='--" . _('Unit Blocks') . "--'>" ;
																$blockSelectCount=0 ;
																foreach ($blocks AS $block) {
																	print "<option value='" . $block[0] . "'>" . ($blockSelectCount+1) . ") " . htmlPrep($block[1]) . "</option>" ;
																	$blockSelectCount++ ;
																}
															print "</optgroup>" ;
															print "<optgroup label='--" . _('Star Blocks') . "--'>" ;
																foreach ($blocks2 AS $block2) {
																	print "<option value='" . $block2[0] . "'>" . htmlPrep($block2[1]) . "</option>" ;
																}
															print "</optgroup>" ;
														print "</select>" ;
													print "</div>" ;
												print "</div>" ;
												
												//Get blocks
												try {
													if ($hooked==FALSE) {
														$dataLessonBlocks=array("gibbonPlannerEntryID"=>$rowLessons["gibbonPlannerEntryID"], "gibbonCourseClassID"=>$gibbonCourseClassID); 
														$sqlLessonBlocks="SELECT * FROM gibbonUnitClassBlock JOIN gibbonUnitClass ON (gibbonUnitClassBlock.gibbonUnitClassID=gibbonUnitClass.gibbonUnitClassID) WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonCourseClassID=:gibbonCourseClassID ORDER BY sequenceNumber" ;
													}
													else {
														$dataLessonBlocks=array("gibbonPlannerEntryID"=>$rowLessons["gibbonPlannerEntryID"], "gibbonCourseClassID"=>$gibbonCourseClassID); 
														$sqlLessonBlocks="SELECT " . $hookOptions["classSmartBlockTable"] . ".* FROM " . $hookOptions["classSmartBlockTable"] . " JOIN " . $hookOptions["classLinkTable"] . " ON (" . $hookOptions["classSmartBlockTable"] . "." . $hookOptions["classSmartBlockJoinField"] . "=" . $hookOptions["classLinkTable"] . "." . $hookOptions["classLinkIDField"] . ") WHERE " . $hookOptions["classSmartBlockTable"] . "." . $hookOptions["classSmartBlockPlannerJoin"] . "=:gibbonPlannerEntryID AND " . $hookOptions["classLinkTable"] . "." . $hookOptions["classLinkJoinFieldClass"] . "=:gibbonCourseClassID ORDER BY sequenceNumber" ;
													}
													$resultLessonBlocks=$connection2->prepare($sqlLessonBlocks);
													$resultLessonBlocks->execute($dataLessonBlocks);
												}
												catch(PDOException $e) { 
													print "<div class='error'>" . $e->getMessage() . "</div>" ; 
												}
												
												//Get outcomes
												try {
													$dataOutcomes=array("gibbonUnitID"=>$gibbonUnitID); 
													$sqlOutcomes="SELECT gibbonOutcome.gibbonOutcomeID, gibbonOutcome.name, gibbonOutcome.category, scope, gibbonDepartment.name AS department FROM gibbonUnitOutcome JOIN gibbonOutcome ON (gibbonUnitOutcome.gibbonOutcomeID=gibbonOutcome.gibbonOutcomeID) LEFT JOIN gibbonDepartment ON (gibbonOutcome.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonUnitID=:gibbonUnitID AND active='Y' ORDER BY sequenceNumber" ;
													$resultOutcomes=$connection2->prepare($sqlOutcomes);
													$resultOutcomes->execute($dataOutcomes);
												}
												catch(PDOException $e) { 
													print "<div class='error'>" . $e->getMessage() . "</div>" ; 
												}
												$unitOutcomes=$resultOutcomes->fetchall() ;
												
												while ($rowLessonBlocks=$resultLessonBlocks->fetch()) {
													if ($hooked==FALSE) {
														makeBlock($guid,  $connection2, $blockCount2, $mode="workingEdit", $rowLessonBlocks["title"], $rowLessonBlocks["type"], $rowLessonBlocks["length"], $rowLessonBlocks["contents"], $rowLessonBlocks["complete"], $rowLessonBlocks["gibbonUnitBlockID"], $rowLessonBlocks["gibbonUnitClassBlockID"], $rowLessonBlocks["teachersNotes"], TRUE, $unitOutcomes, $rowLessonBlocks["gibbonOutcomeIDList"]) ;
													}
													else {
														makeBlock($guid,  $connection2, $blockCount2, $mode="workingEdit", $rowLessonBlocks[$hookOptions["classSmartBlockTitleField"]], $rowLessonBlocks[$hookOptions["classSmartBlockTypeField"]], $rowLessonBlocks[$hookOptions["classSmartBlockLengthField"]], $rowLessonBlocks[$hookOptions["classSmartBlockContentsField"]], $rowLessonBlocks["complete"], $rowLessonBlocks["gibbonUnitBlockID"], $rowLessonBlocks["gibbonUnitClassBlockID"], $rowLessonBlocks[$hookOptions["classSmartBlockTeachersNotesField"]], TRUE) ;
													}
													$blockCount2++ ;
												}
											print "</div>" ;
										print "</div>" ;
										$i++ ;
									}
									$cells=$i ;
								}
								?>
								<div class='linkTop' style='margin-top: 0px!important'>
									<?php
									print "<script type='text/javascript'>" ;
										print "var count=$blockCount2 ;" ;
									print "</script>" ;
									print "<input type='submit' value='Submit'>" ;
									?>
								</div>
							<?php
							print "</div>" ;
						print "</form>" ;
						
						//Add drag/drop controls
						$sortableList="" ;
						?>
						<style>
							.default { border: none; background-color: #ffffff }
							.drop { border: none; background-color: #eeeeee }
							.hover { border: none; background-color: #D4F6DC }
						</style>
										
						<script type="text/javascript">
							$(function() {
								var receiveCount=0 ;
								
								//Create list of lesson sortables
								<?php for ($i=0; $i<$cells; $i++) { ?>
									<?php $sortableList.="#sortable$i, " ?>
								<?php } ?>
								
								//Create lesson sortables
								<?php for ($i=0; $i<$cells; $i++) { ?>
									$( "#sortable<?php print $i ?>" ).sortable({
										revert: false,
										tolerance: 15, 
										connectWith: "<?php print substr($sortableList,0, -2) ?>",
										items: "div.blockOuter",
										receive: function(event,ui) {
											var sortid=$(newItem).attr("id", 'receive'+receiveCount) ;
											var receiveid='receive'+receiveCount ;
											$('#' + receiveid + ' .delete').show() ;
											$('#' + receiveid + ' .delete').click(function() {
												$('#' + receiveid).fadeOut(600, function(){ 
													$('#' + receiveid).remove(); 
												});
											});
											$('#' + receiveid + ' .completeDiv').show() ;
											$('#' + receiveid + ' .complete').show() ;
											$('#' + receiveid + ' .complete').click(function() {
												if ($('#' + receiveid + ' .complete').is(':checked')==true) {
													$('#' + receiveid + ' .completeHide').val('on') ;
												} else {
													$('#' + receiveid + ' .completeHide').val('off') ;
												}
											});
											receiveCount++ ;
										},
										beforeStop: function (event, ui) {
										 newItem=ui.item;
										}
									});
									<?php for ($j=$blockCount; $j<$blockCount2; $j++) { ?>
										$("#draggable<?php print $j ?> .delete").show() ;
										$("#draggable<?php print $j ?> .delete").click(function() {
											$("#draggable<?php print $j ?>").fadeOut(600, function(){ 
												$("#draggable<?php print $j ?>").remove(); 
											});
										});
										$("#draggable<?php print $j ?> .completeDiv").show() ;
										$("#draggable<?php print $j ?> .complete").show() ;
										$("#draggable<?php print $j ?> .complete").click(function() {
												if ($("#draggable<?php print $j ?> .complete").is(':checked')==true) {
													$("#draggable<?php print $j ?> .completeHide").val('on') ;
												} else {
													$("#draggable<?php print $j ?> .completeHide").val('off') ;
												}
											});
									<?php } ?>
									
									
								<?php } ?>
								
								//Draggables
								<?php for ($i=0; $i<$blockCount; $i++) { ?>
									$( "#draggable<?php print $i ?>" ).draggable({
										connectToSortable: "<?php print substr($sortableList, 0, -2) ?>",
										helper: "clone"
									});
								<?php } ?>
								
							});
						</script>
						<?php
					}
				}
			}
		}
	}
	//Print sidebar
	$_SESSION[$guid]["sidebarExtra"]=sidebarExtraUnits($guid, $connection2, $gibbonCourseID, $gibbonSchoolYearID) ;
}
?>