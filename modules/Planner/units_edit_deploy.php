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

if (isActionAccessible($guid, $connection2, "/modules/Planner/units_edit_deploy.php")==FALSE) {
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
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/units.php&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "&gibbonCourseID=" . $_GET["gibbonCourseID"] . "'>" . _('Unit Planner') . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/units_edit.php&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "&gibbonCourseID=" . $_GET["gibbonCourseID"] . "&gibbonUnitID=" . $_GET["gibbonUnitID"] . "'>" . _('Edit Unit') . "</a> > </div><div class='trailEnd'>" . _('Deploy Working Copy') . "</div>" ;
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
		
		//Check if courseschool year specified
		$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"];
		$gibbonCourseID=$_GET["gibbonCourseID"]; 
		$gibbonCourseClassID=$_GET["gibbonCourseClassID"]; 
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
						
						$step=NULL ;
						if (isset($_GET["step"])) {
							$step=$_GET["step"] ;
						}
						if ($step!=1 AND $step!=2 AND $step!=3) {
							$step=1 ;
						}
						
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
						
						//Step 1
						if ($step==1) {
							print "<h3>" ;
							print _("Step 1 - Select Lessons") ;
							print "</h3>" ;
							print "<p>" ;
							print _("Use the table below to select the lessons you wish to deploy this unit to. Only lessons without existing plans can be included in the deployment.") ;
							print "</p>" ;
							
							//Find all unplanned slots for this class.
							try {
								$dataNext=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
								$sqlNext="SELECT timeStart, timeEnd, date, gibbonTTColumnRow.name AS period, gibbonTTDayRowClassID, gibbonTTDayDateID FROM gibbonTTDayRowClass JOIN gibbonTTColumnRow ON (gibbonTTDayRowClass.gibbonTTColumnRowID=gibbonTTColumnRow.gibbonTTColumnRowID) JOIN gibbonTTColumn ON (gibbonTTColumnRow.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) JOIN gibbonTTDay ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) WHERE gibbonCourseClassID=:gibbonCourseClassID ORDER BY date, timestart" ;
								$resultNext=$connection2->prepare($sqlNext);
								$resultNext->execute($dataNext);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							$count=0 ;
							$lessons=array() ;
							while ($rowNext=$resultNext->fetch()) {
								try {
									$dataPlanner=array("date"=>$rowNext["date"], "timeStart"=>$rowNext["timeStart"], "timeEnd"=>$rowNext["timeEnd"], "gibbonCourseClassID"=>$gibbonCourseClassID); 
									$sqlPlanner="SELECT * FROM gibbonPlannerEntry WHERE date=:date AND timeStart=:timeStart AND timeEnd=:timeEnd AND gibbonCourseClassID=:gibbonCourseClassID" ;
									$resultPlanner=$connection2->prepare($sqlPlanner);
									$resultPlanner->execute($dataPlanner);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								
								if ($resultPlanner->rowCount()==0) {
									$lessons[$count][0]="Unplanned" ;
									$lessons[$count][1]=$rowNext["date"] ;
									$lessons[$count][2]=$rowNext["timeStart"] ;
									$lessons[$count][3]=$rowNext["timeEnd"] ;
									$lessons[$count][4]=$rowNext["period"] ;
									$lessons[$count][6]=$rowNext["gibbonTTDayRowClassID"] ;
									$lessons[$count][7]=$rowNext["gibbonTTDayDateID"] ;
									}
								else {
									$rowPlanner=$resultPlanner->fetch() ;
									$lessons[$count][0]="Planned" ;
									$lessons[$count][1]=$rowNext["date"] ;
									$lessons[$count][2]=$rowNext["timeStart"] ;
									$lessons[$count][3]=$rowNext["timeEnd"] ;
									$lessons[$count][4]=$rowNext["period"] ;
									$lessons[$count][5]=$rowPlanner["name"] ;
									$lessons[$count][6]=false ;
									$lessons[$count][7]=false ;
								}
								
								//Check for special days
								try {
									$dataSpecial=array("date"=>$rowNext["date"]); 
									$sqlSpecial="SELECT * FROM gibbonSchoolYearSpecialDay WHERE date=:date" ;
									$resultSpecial=$connection2->prepare($sqlSpecial);
									$resultSpecial->execute($dataSpecial);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								
								if ($resultSpecial->rowCount()==1) {
									$rowSpecial=$resultSpecial->fetch() ;
									$lessons[$count][8]=$rowSpecial["type"] ;
									$lessons[$count][9]=$rowSpecial["schoolStart"] ;
									$lessons[$count][10]=$rowSpecial["schoolEnd"] ;
								}
								else {
									$lessons[$count][8]=false ;
									$lessons[$count][9]=false ;
									$lessons[$count][10]=false ;
								}
								$count++ ;
							}
							
							if (count($lessons)<1) {
								print "<div class='error'>" ;
								print _("There are no records to display.") ;
								print "</div>" ;
							}
							else {
								//Get term dates
								$terms=array() ;
								$termCount=0 ;
								try {
									$dataTerms=array("gibbonSchoolYearID"=>$gibbonSchoolYearID); 
									$sqlTerms="SELECT * FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY sequenceNumber" ;
									$resultTerms=$connection2->prepare($sqlTerms);
									$resultTerms->execute($dataTerms);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								
								while ($rowTerms=$resultTerms->fetch()) {
									$terms[$termCount][0]=$rowTerms["firstDay"] ;
									$terms[$termCount][1]="Start of " . $rowTerms["nameShort"] ;
									$termCount++ ;
									$terms[$termCount][0]=$rowTerms["lastDay"] ;
									$terms[$termCount][1]="End of " . $rowTerms["nameShort"] ;
									$termCount++ ;
								}
								//Get school closure special days
								$specials=array() ;
								$specialCount=0 ;
								
								try {
									$dataSpecial=array("gibbonSchoolYearID"=>$gibbonSchoolYearID); 
									$sqlSpecial="SELECT gibbonSchoolYearSpecialDay.date, gibbonSchoolYearSpecialDay.name FROM gibbonSchoolYearSpecialDay JOIN gibbonSchoolYearTerm ON (gibbonSchoolYearSpecialDay.gibbonSchoolYearTermID=gibbonSchoolYearTerm.gibbonSchoolYearTermID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND type='School Closure' ORDER BY date" ;
									$resultSpecial=$connection2->prepare($sqlSpecial);
									$resultSpecial->execute($dataSpecial);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								$lastName="" ;
								$currentName="" ;
								$originalDate="" ;
								while ($rowSpecial=$resultSpecial->fetch()) {
									$currentName=$rowSpecial["name"] ;
									$currentDate=$rowSpecial["date"] ;
									if ($currentName!=$lastName) {
										$currentName=$rowSpecial["name"] ;
										$specials[$specialCount][0]=$rowSpecial["date"] ;
										$specials[$specialCount][1]=$rowSpecial["name"] ;
										$specials[$specialCount][2]=dateConvertBack($guid, $rowSpecial["date"]) ;
										$originalDate=dateConvertBack($guid, $rowSpecial["date"]) ;
										$specialCount++ ;
									}
									else {
										if ((strtotime($currentDate)-strtotime($lastDate))==86400) {
											$specials[$specialCount-1][2]=$originalDate . " - " . dateConvertBack($guid, $rowSpecial["date"]) ;
										}
										else {
											$currentName=$rowSpecial["name"] ;
											$specials[$specialCount][0]=$rowSpecial["date"] ;
											$specials[$specialCount][1]=$rowSpecial["name"] ;
											$specials[$specialCount][2]=dateConvertBack($guid, $rowSpecial["date"]) ;
											$originalDate=dateConvertBack($guid, $rowSpecial["date"]) ;
											$specialCount++ ;
										}
									}
									$lastName=$rowSpecial["name"] ;
									$lastDate=$rowSpecial["date"] ;
								}
								
								
							
								print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/units_edit_deploy.php&step=2&gibbonUnitID=$gibbonUnitID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseID=$gibbonCourseID&gibbonCourseClassID=$gibbonCourseClassID&gibbonUnitClassID=$gibbonUnitClassID'>" ;
									print "<table cellspacing='0' style='width: 100%'>" ;
										print "<tr class='head'>" ;
											print "<th>" ;
												print sprintf(_('Lesson%1$sNumber'), "<br/>") ;
											print "</th>" ;
											print "<th>" ;
												print _("Date") ;
											print "</th>" ;
											print "<th>" ;
												print _("Day") ;
											print "</th>" ;
											print "<th>" ;
												print _("Month") ;
											print "</th>" ;
											print "<th>" ;
												print _("TT Period") . "/<br/>" . _('Time') ;
											print "</th>" ;
											print "<th>" ;
												print sprintf(_('Planned%1$sLesson'), "<br/>") ;
											print "</th>" ;
											print "<th>" ;
												print _("Include?") ;
											print "</th>" ;
										print "</tr>" ;
										
										$count=0;
										$termCount=0 ;
										$specialCount=0 ;
										$classCount=0 ;
										$rowNum="odd" ;
										$divide=false ; //Have we passed gotten to today yet?
						
										foreach ($lessons as $lesson) {
											if ($count%2==0) {
												$rowNum="even" ;
											}
											else {
												$rowNum="odd" ;
											}
											
											$style="" ;
											if ($lesson[1]>=date("Y-m-d") AND $divide==false) {
												$divide=true ;
												$style="style='border-top: 2px solid #333'" ;
											}
											
											if ($divide==false) {
												$rowNum="error" ;
											}
											$count++ ;
											
											//Spit out row for start of term
											while ($lesson["1"]>=$terms[$termCount][0] AND $termCount<(count($terms)-1)) {
												if (substr($terms[$termCount][1],0,3)=="End" AND $lesson["1"]==$terms[$termCount][0]) {
													break ;
												}
												else {
													print "<tr class='dull'>" ;
														print "<td>" ;
															print "<b>" . $terms[$termCount][1] . "</b>" ;
														print "</td>" ;
														print "<td colspan=6>" ;
															print dateConvertBack($guid, $terms[$termCount][0]) ;
														print "</td>" ;
													print "</tr>" ;
													$termCount++ ;
												}
											}
											//Spit out row for special day
											while ($lesson["1"]>=@$specials[$specialCount][0] AND $specialCount<count($specials)) {
												print "<tr class='dull'>" ;
													print "<td>" ;
														print "<b>" . $specials[$specialCount][1] . "</b>" ;
													print "</td>" ;
													print "<td colspan=6>" ;
														print $specials[$specialCount][2] ;
													print "</td>" ;
												print "</tr>" ;
												$specialCount++ ;
											}
										
											//COLOR ROW BY STATUS!
											if ($lesson[8]!="School Closure") {
												print "<tr class=$rowNum>" ;
													print "<td $style>" ;
														print "<b>Lesson " . ($classCount+1) . "</b>" ;
													print "</td>" ;
													print "<td $style>" ;
													print dateConvertBack($guid, $lesson["1"]) . "<br/>" ;
														 if ($lesson[8]=="Timing Change") {
															print "<u>" . $lesson[8] . "</u><br/><i>(" . substr($lesson[9],0,5) . "-" . substr($lesson[10],0,5) . ")</i>" ;
														 }
													print "</td>" ;
													print "<td $style>" ;
														print date("D", dateConvertToTimestamp($lesson["1"])) ;
													print "</td>" ;
													print "<td $style>" ;
														print date("M", dateConvertToTimestamp($lesson["1"])) ;
													print "</td>" ;
													print "<td $style>" ;
														print $lesson["4"] . "<br/>" ;
														print substr($lesson["2"],0,5) . " - " . substr($lesson["3"],0,5) ;
													print "</td>" ;
													print "<td $style>" ;
														if ($lesson["0"]=="Planned") {
															print $lesson["5"] . "<br/>" ;
														}
													print "</td>" ;
													print "<td $style>" ;
														if ($lesson["0"]=="Unplanned") {
															print "<input name='deploy$count' type='checkbox'>" ;
															print "<input name='date$count' type='hidden' value='" . $lesson["1"] . "'>" ;
															print "<input name='timeStart$count' type='hidden' value='" . $lesson["2"] . "'>" ;
															print "<input name='timeEnd$count' type='hidden' value='" . $lesson["3"] . "'>" ;
															print "<input name='period$count' type='hidden' value='" . $lesson["4"] . "'>" ;
															print "<input name='gibbonTTDayRowClassID$count' type='hidden' value='" . $lesson["6"] . "'>" ;
															print "<input name='gibbonTTDayDateID$count' type='hidden' value='" . $lesson["7"] . "'>" ;
														}
													print "</td>" ;
												print "</tr>" ;
												$classCount++ ;
											}
											
											//Spit out row for end of term
											while ($lesson["1"]>=@$terms[$termCount][0] AND $termCount<count($terms) AND substr($terms[$termCount][1],0,3)=="End") {
												print "<tr class='dull'>" ;
													print "<td>" ;
														print "<b>" . $terms[$termCount][1] . "</b>" ;
													print "</td>" ;
													print "<td colspan=6>" ;
														print dateConvertBack($guid, $terms[$termCount][0]) ;
													print "</td>" ;
												print "</tr>" ;
												$termCount++ ;
											}
										}
										
										if (@$terms[$termCount][0]!="") {
											print "<tr class='dull'>" ;
												print "<td>" ;
													print "<b>" . $terms[$termCount][1] . "</b>" ;
												print "</td>" ;
												print "<td colspan=6>" ;
													print dateConvertBack($guid, $terms[$termCount][0]) ;
												print "</td>" ;
											print "</tr>" ;
										}
										
										print "<tr>" ;
											print "<td class='right' colspan=7>" ;
												print "<input name='count' id='count' value='$count' type='hidden'>" ;
												print "<input id='submit' type='submit' value='Submit'>" ;
											print "</td>" ;
										print "</tr>" ;
									print "</table>" ;
								print "</form>" ;										
							}
						}
						//Step 2
						if ($step==2) {
							print "<h3>" ;
							print _("Step 2 - Distribute Blocks") ;
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
									$blocks[$blockCount][6]=$rowBlocks["gibbonOutcomeIDList"];
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
								$blocks2[$blockCount2][0]=$rowBlocks2["gibbonUnitBlockID"];
								$blocks2[$blockCount2][1]=$rowBlocks2["title"];
								$blocks2[$blockCount2][2]=$rowBlocks2["type"];
								$blocks2[$blockCount2][3]=$rowBlocks2["length"];
								$blocks2[$blockCount2][4]=$rowBlocks2["contents"];
								$blocks2[$blockCount2][5]=$rowBlocks2["teachersNotes"];
								$blocks2[$blockCount2][6]=$rowBlocks2["gibbonOutcomeIDList"];
								$blockCount2++ ;
							}
							
							//Create drag and drop environment for blocks
							print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/units_edit_deployProcess.php?gibbonUnitID=$gibbonUnitID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseID=$gibbonCourseID&gibbonCourseClassID=$gibbonCourseClassID&address=" . $_GET["q"] . "&gibbonUnitClassID=$gibbonUnitClassID'>" ;
								//LESSONS (SORTABLES)
								print "<div style='width: 100%; height: auto'>" ;
									print "<b>Lessons</b><br/>" ;
									$lessonCount=$_POST["count"] ;
									if ($lessonCount<1) {
										print "<div class='error'>" ;
										print _("There are no records to display.") ;
										print "</div>" ;
									}
									else {
										$lessons=array() ;
										$count=0 ;
										for ($i=1; $i<=$lessonCount; $i++) {
											if (isset($_POST["deploy$i"])) {
												if ($_POST["deploy$i"]=="on") {
													$lessons[$count][0]=$_POST["date$i"] ;
													$lessons[$count][1]=$_POST["timeStart$i"] ;
													$lessons[$count][2]=$_POST["timeEnd$i"] ;
													$lessons[$count][3]=$_POST["period$i"] ;
													$count++ ;
												}
											}
										}
										
										$cells=count($lessons) ;
										if ($cells<1) {
											print "<div class='error'>" ;
											print _("There are no records to display.") ;
											print "</div>" ;
										}
										else {
											$deployCount=0 ;
											$blockCount2=$blockCount ;
											for ($i=0; $i<$cells; $i++) {
												print "<div id='lessonInner$i' style='min-height: 60px; border: 1px solid #333; width: 100%; margin-bottom: 45px; float: left; padding: 2px; background-color: #F7F0E3'>" ;
													$length=((strtotime($lessons[$i][0] . " " . $lessons[$i][2])-strtotime($lessons[$i][0] . " " . $lessons[$i][1]))/60) ;
													print "<div id='sortable$i' style='min-height: 60px; font-size: 120%; font-style: italic'>" ;
														print "<div id='head$i' class='head' style='height: 54px; font-size: 85%; padding: 3px'>" ;
															print "<b>" . ($i+1) . ". " . date("D jS M, Y", dateConvertToTimestamp($lessons[$i][0])) . "</b><br/>" ;
															print "<span style='font-size: 80%'><i>" . $lessons[$i][3] . " (" . substr($lessons[$i][1],0,5) . " - " . substr($lessons[$i][2],0,5) . ")</i></span>" ;
															print "<input type='hidden' name='order[]' value='lessonHeader-$i' >" ;
															print "<input type='hidden' name='date$i' value='" . $lessons[$i][0] . "' >" ;
															print "<input type='hidden' name='timeStart$i' value='" . $lessons[$i][1] . "' >" ;
															print "<input type='hidden' name='timeEnd$i' value='" . $lessons[$i][2] . "' >" ;
															print "<div style='text-align: right; float: right; margin-top: -17px; margin-right: 3px'>" ;
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
														
														//Prep outcomes
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
												
														//Attempt auto deploy
														$spinCount=0 ;
														while ($spinCount<$blockCount AND $length>0) {
															if (isset($blocks[$deployCount])) {
																if ($blocks[$deployCount][3]<1 OR $blocks[$deployCount][3]=="") {
																	$deployCount++ ;
																}
																else {
																	if (($length-$blocks[$deployCount][3])>=0) {
																		makeBlock($guid,  $connection2, $blockCount2, $mode="workingDeploy", $blocks[$deployCount][1], $blocks[$deployCount][2], $blocks[$deployCount][3], $blocks[$deployCount][4], "N", $blocks[$deployCount][0], "", $blocks[$deployCount][5], TRUE, $unitOutcomes, $blocks[$deployCount][6]) ;
																		$length=$length-$blocks[$deployCount][3] ;
																		$deployCount++ ;									
																	}
																}
															}
															
															$spinCount++ ;
															$blockCount2++ ;
														}
													print "</div>" ;
												print "</div>" ;
												print "<script type='text/javascript'>" ;
													print "var count=$blockCount2 ;" ;
												print "</script>" ;
											}
										}
									}
									
									?>
									<b><?php print _('Access') ?></b><br/>
									<table cellspacing='0' style="width: 100%">	
										<tr id="accessRowStudents">
											<td> 
												<b><?php print _('Viewable to Students') ?> *</b><br/>
												<span style="font-size: 90%"><i></i></span>
											</td>
											<td class="right">
												<?php
												$sharingDefaultStudents=getSettingByScope( $connection2, "Planner", "sharingDefaultStudents" ) ;
												?>
												<select name="viewableStudents" id="viewableStudents" style="width: 302px">
													<option <?php if ($sharingDefaultStudents=="Y") { print "selected" ; } ?> value="Y"><?php print _('Yes') ?></option>
													<option <?php if ($sharingDefaultStudents=="N") { print "selected" ; } ?> value="N"><?php print _('No') ?></option>
												</select>
											</td>
										</tr>
										<tr id="accessRowParents">
											<td> 
												<b><?php print _('Viewable to Parents') ?> *</b><br/>
												<span style="font-size: 90%"><i></i></span>
											</td>
											<td class="right">
												<?php
												$sharingDefaultParents=getSettingByScope( $connection2, "Planner", "sharingDefaultParents" ) ;
												?>
												<select name="viewableParents" id="viewableParents" style="width: 302px">
													<option <?php if ($sharingDefaultParents=="Y") { print "selected" ; } ?> value="Y"><?php print _('Yes') ?></option>
													<option <?php if ($sharingDefaultParents=="N") { print "selected" ; } ?> value="N"><?php print _('No') ?></option>
												</select>
											</td>
										</tr>
									</table>
									
									<table class='blank' style='width: 100%' cellspacing=0>
										<tr>
											<td>
												<?php
												print "<div style='width: 100%; margin-bottom: 20px; text-align: right'>" ;
													print "<input type='submit' value='Submit'>" ;
												print "</div>" ;
												?>
											</td>
										</tr>
									</table>
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
												receiveCount++ ;
											},
											beforeStop: function (event, ui) {
											 newItem=ui.item;
											}
										});
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
	}
	//Print sidebar
	$_SESSION[$guid]["sidebarExtra"]=sidebarExtraUnits($guid, $connection2, $gibbonCourseID, $gibbonSchoolYearID) ;
}
?>