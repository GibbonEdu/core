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

if (isActionAccessible($guid, $connection2, "/modules/Planner/units_edit_working_add.php")==FALSE) {
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
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/units.php&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "&gibbonCourseID=" . $_GET["gibbonCourseID"] . "'>Manage Units</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/units_edit.php&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "&gibbonCourseID=" . $_GET["gibbonCourseID"] . "&gibbonUnitID=" . $_GET["gibbonUnitID"] . "'>Edit Unit</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/units_edit_working.php&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "&gibbonCourseID=" . $_GET["gibbonCourseID"] . "&gibbonUnitID=" . $_GET["gibbonUnitID"] . "&gibbonCourseClassID=" . $_GET["gibbonCourseClassID"] . "'>Edit Working Copy</a> > </div><div class='trailEnd'>Add Lessons</div>" ;
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
		$gibbonUnitID=$_GET["gibbonUnitID"]; 
		$gibbonUnitClassID=$_GET["gibbonUnitClassID"]; 
		if ($gibbonCourseID=="" OR $gibbonSchoolYearID=="" OR $gibbonCourseClassID=="" OR $gibbonUnitClassID=="") {
			print "<div class='error'>" ;
				print _("You have not specified one or more required parameters.") ;
			print "</div>" ;
		}
		else {
			try {
				if ($highestAction=="Manage Units_all") {
					$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonCourseID"=>$gibbonCourseID, "gibbonCourseClassID"=>$gibbonCourseClassID); 
					$sql="SELECT *, gibbonSchoolYear.name AS year, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourse.gibbonCourseID=:gibbonCourseID AND gibbonCourseClassID=:gibbonCourseClassID" ;
				}
				else if ($highestAction=="Manage Units_learningAreas") {
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
									print "<span style='font-size: 115%; font-weight: bold'>School Year</span><br/>" ;
									print "<i>" . $year . "</i>" ;
									print "</td>" ;
								print "<td style='width: 33%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>Class</span><br/>" ;
									print "<i>" . $course . "." . $class . "</i>" ;
								print "</td>" ;
								print "<td style='width: 34%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>Unit</span><br/>" ;
									print "<i>" . $row["name"] . "</i>" ;
								print "</td>" ;
							print "</tr>" ;
						print "</table>" ;
						
						
						print "<h3>" ;
						print "Choose Lessons" ;
						print "</h3>" ;
						print "<p>" ;
						print "Use the table below to select the lessons you wish to deploy this unit to. Only lessons without existing plans can be included in the deployment." ;
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
						
							print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/units_edit_working_addProcess.php?gibbonUnitID=$gibbonUnitID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseID=$gibbonCourseID&gibbonCourseClassID=$gibbonCourseClassID&gibbonUnitClassID=$gibbonUnitClassID&address=" . $_GET["q"] . "'>" ;
								print "<table cellspacing='0' style='width: 100%'>" ;
									print "<tr class='head'>" ;
										print "<th>" ;
											print "Lesson<br/>Number" ;
										print "</th>" ;
										print "<th>" ;
											print "Date" ;
										print "</th>" ;
										print "<th>" ;
											print "Day" ;
										print "</th>" ;
										print "<th>" ;
											print "Month" ;
										print "</th>" ;
										print "<th>" ;
											print "TT Period/<br/>Time" ;
										print "</th>" ;
										print "<th>" ;
											print "Planned<br/>Lesson" ;
										print "</th>" ;
										print "<th>" ;
											print "Include?" ;
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
												print "<b><u>" . $terms[$termCount][1] . "</u></b>" ;
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
				}
			}
		}
	}
	//Print sidebar
	$_SESSION[$guid]["sidebarExtra"]=sidebarExtraUnits($guid, $connection2, $gibbonCourseID, $gibbonSchoolYearID) ;
}
?>