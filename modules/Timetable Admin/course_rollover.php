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

if (isActionAccessible($guid, $connection2, "/modules/Timetable Admin/course_rollover.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Course Enrolment Rollover') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["addReturn"])) { $addReturn=$_GET["addReturn"] ; } else { $addReturn="" ; }
	$addReturnMessage="" ;
	$class="error" ;
	if (!($addReturn=="")) {
		if ($addReturn=="fail0") {
			$addReturnMessage=__($guid, "Your request failed because you do not have access to this action.") ;	
		}
		else if ($addReturn=="fail2") {
			$addReturnMessage=__($guid, "Your request failed due to a database error.") ;	
		}
		else if ($addReturn=="fail3") {
			$addReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
		}
		else if ($addReturn=="fail5") {
			$updateReturnMessage=__($guid, "Your request was successful, but some data was not properly saved.") ;
		}
		else if ($addReturn=="success0") {
			$addReturnMessage=__($guid, "Your request was completed successfully. You can now add another record if you wish.") ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $addReturnMessage;
		print "</div>" ;
	} 

	$step=NULL ;
	if (isset($_GET["step"])) {
		$step=$_GET["step"] ;
	}
	if ($step!=1 AND $step!=2 AND $step!=3) {
		$step=1 ;
	}
	
	//Step 1
	if ($step==1) {
		print "<h3>" ;
		print __($guid, "Step 1") ;
		print "</h3>" ;
		
		$nextYear=getNextSchoolYearID($_SESSION[$guid]["gibbonSchoolYearID"], $connection2) ;
		if ($nextYear==FALSE) {
			print "<div class='error'>" ;
			print __($guid, "The next school year cannot be determined, so this action cannot be performed.") ;
			print "</div>" ;
			}
		else {
			try {
				$dataNext=array("gibbonSchoolYearID"=>$nextYear); 
				$sqlNext="SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID" ;
				$resultNext=$connection2->prepare($sqlNext);
				$resultNext->execute($dataNext);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			if ($resultNext->rowCount()==1) {
				$rowNext=$resultNext->fetch() ;	
			}
			$nameNext=$rowNext["name"] ;
			if ($nameNext=="") {
				print "<div class='error'>" ;
				print __($guid, "The next school year cannot be determined, so this action cannot be performed.") ;
				print "</div>" ;
			}
			else {
				?>
				<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/course_rollover.php&step=2" ?>">
					<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
						<tr>
							<td colspan=2 style='text-align: justify'> 
								<?php
								print sprintf(__($guid, 'By clicking the "Proceed" button below you will initiate the course enrolment rollover from %1$s to %2$s. In a big school this operation may take some time to complete. %3$sYou are really, very strongly advised to backup all data before you proceed%4$s.'), "<b>" . $_SESSION[$guid]["gibbonSchoolYearName"] . "</b>", "<b>" . $nameNext. "</b>", "<span style=\"color: #cc0000\"><i>", "</i></span>") ;
								?>
							</td>
						</tr>
						<tr>
							<td class="right" colspan=2>
								<input type="hidden" name="nextYear" value="<?php print $nextYear ?>">
								<input type="submit" value="Proceed">
							</td>
						</tr>
					</table>
				<?php
			}
		}
	}
	else if ($step==2) {
		print "<h3>" ;
		print __($guid, "Step 2") ;
		print "</h3>" ;
		
		$nextYear=$_POST["nextYear"] ;
		if ($nextYear=="" OR $nextYear!=getNextSchoolYearID($_SESSION[$guid]["gibbonSchoolYearID"], $connection2)) {
			print "<div class='error'>" ;
			print __($guid, "The next school year cannot be determined, so this action cannot be performed.") ;
			print "</div>" ;
		}
		else {
			try {
				$dataNext=array("gibbonSchoolYearID"=>$nextYear); 
				$sqlNext="SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID" ;
				$resultNext=$connection2->prepare($sqlNext);
				$resultNext->execute($dataNext);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			if ($resultNext->rowCount()==1) {
				$rowNext=$resultNext->fetch() ;	
			}
			$nameNext=$rowNext["name"] ;
			$sequenceNext=$rowNext["sequenceNumber"] ;
			if ($nameNext=="" OR $sequenceNext=="") {
				print "<div class='error'>" ;
				print __($guid, "The next school year cannot be determined, so this action cannot be performed.") ;
				print "</div>" ;
				}
			else {
				print "<p>" ;
				print sprintf(__($guid, 'In rolling over to %1$s, the following actions will take place. You may need to adjust some fields below to get the result you desire.'), $nameNext) ;
				print "</p>" ;
				
				print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/course_rollover.php&step=3'>" ;
					print "<h4>" ;
					print sprintf(__($guid, 'Options'), $nameNext) ;
					print "</h4>" ;
					?>
					<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
						<tr>
							<td style='width: 275px'> 
								<b><?php print __($guid, 'Include Students') ?> *</b><br/>
							</td>
							<td class="right">
								<input checked type='checkbox' name='rollStudents'>
							</td>
						</tr>
						<tr>
							<td style='width: 275px'> 
								<b><?php print __($guid, 'Include Teachers') ?> *</b><br/>
							</td>
							<td class="right">
								<input type='checkbox' name='rollTeachers'>
							</td>
						</tr>
					</table>
					<?php
					
					print "<h4>" ;
					print __($guid, "Map Classess") ;
					print "</h4>" ;
					print "<p>" ;
					print __($guid, "Determine which classes from this year roll to which classes in next year, and which not to rollover at all.") ;
					print "</p>" ;
					
					$students=array() ;
					$count=0 ;
					//Get current courses/classes
					try {
						$dataEnrol=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
						$sqlEnrol="SELECT gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY course, class" ;
						$resultEnrol=$connection2->prepare($sqlEnrol);
						$resultEnrol->execute($dataEnrol);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					
					//Store next years courses/classes in an array
					$coursesNext=array() ;
					$coursesNextCount=0 ;
					try {
						$dataNext=array("gibbonSchoolYearID"=>$nextYear); 
						$sqlNext="SELECT gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY course, class" ;
						$resultNext=$connection2->prepare($sqlNext);
						$resultNext->execute($dataNext);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					while ($rowNext=$resultNext->fetch()) {
						$coursesNext[$coursesNextCount][0]=$rowNext["gibbonCourseClassID"] ;
						$coursesNext[$coursesNextCount][1]=$rowNext["course"] ;
						$coursesNext[$coursesNextCount][2]=$rowNext["class"] ;
						$coursesNext[$coursesNextCount][3]=NULL ;
						//Prep for matching
						$matches=array() ;
						preg_match_all('!\d+!', $rowNext["course"], $matches);
						if (count($matches)==1) {
							if (isset($matches[0][0])) {
								$coursesNext[$coursesNextCount][3]=str_replace($matches[0][0], str_pad(($matches[0][0]-1),strlen($matches[0][0]),"0", STR_PAD_LEFT), $rowNext["course"]) ;
							}
						}
						
						$coursesNextCount++ ;
					}
					
					if ($resultEnrol->rowCount()<1) {
						print "<div class='error'>" ;
						print __($guid, "There are no records to display.") ;
						print "</div>" ;
					}
					else {
						print "<table cellspacing='0' style='width: 100%'>" ;
							print "<tr class='head'>" ;
								print "<th>" ;
									print __($guid, "Class") ;
								print "</th>" ;
								print "<th>" ;
									print __($guid, "New Class") ;
								print "</th>" ;
							print "</tr>" ;
							
							$count=0;
							$rowNum="odd" ;
							while ($rowEnrol=$resultEnrol->fetch()) {
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
										print "<input type='hidden' name='$count-gibbonCourseClassID' value='" . $rowEnrol["gibbonCourseClassID"] . "'>" ;
										print $rowEnrol["course"] . "." . $rowEnrol["class"] ;
									print "</td>" ;
									print "<td>" ;
										print "<select name='$count-gibbonCourseClassIDNext' id='$count-gibbonCourseClassIDNext' style='float: left; width:110px'>" ;
											print "<option value=''></option>" ;
											foreach ($coursesNext AS $courseNext) {
												$selected="" ;
												//Attempt to select...may not be 100%
												if ($courseNext[3]!=NULL) {
													if ($courseNext[3]==$rowEnrol["course"]) {
														if ($courseNext[2]==$rowEnrol["class"]) {
															$selected="selected" ;
														}
													}
												}
												print "<option $selected value='" . $courseNext[0] . "'>" . htmlPrep($courseNext[1]) . "." . htmlPrep($courseNext[2]) . "</option>" ;
											}		
										print "</select>" ;
									print "</td>" ;
								print "</tr>" ;
							}
						print "</table>" ;
						
						print "<input type='hidden' name='count' value='$count'>" ;
					}
					
					
					print "<table cellspacing='0' style='width: 100%'>" ;	
						print "<tr>" ;
							print "<td>" ;
								print "<span style='font-size: 90%'><i>* " . __($guid, "denotes a required field") . "</i></span>" ;
							print "</td>" ;
							print "<td class='right'>" ;
								print "<input type='hidden' name='nextYear' value='$nextYear'>" ;
								print "<input type='submit' value='Proceed'>" ;
							print "</td>" ;
						print "</tr>" ;
					print "</table>" ;
				print "</form>" ;
			}
		}
	}
	else if ($step==3) {
		$nextYear=$_POST["nextYear"] ;
		if ($nextYear=="" OR $nextYear!=getNextSchoolYearID($_SESSION[$guid]["gibbonSchoolYearID"], $connection2)) {
			print "<div class='error'>" ;
			print __($guid, "The next school year cannot be determined, so this action cannot be performed.") ;
			print "</div>" ;
		}
		else {
			try {
				$dataNext=array("gibbonSchoolYearID"=>$nextYear); 
				$sqlNext="SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID" ;
				$resultNext=$connection2->prepare($sqlNext);
				$resultNext->execute($dataNext);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			if ($resultNext->rowCount()==1) {
				$rowNext=$resultNext->fetch() ;	
			}
			$nameNext=$rowNext["name"] ;
			$sequenceNext=$rowNext["sequenceNumber"] ;
			if ($nameNext=="" OR $sequenceNext=="") {
				print "<div class='error'>" ;
				print __($guid, "The next school year cannot be determined, so this action cannot be performed.") ;
				print "</div>" ;
			}
			else {
				print "<h3>" ;
				print __($guid, "Step 3") ;
				print "</h3>" ;
				
				$partialFail=FALSE ;
				
				$count=$_POST["count"] ;
				$rollStudents="" ;
				if (isset($_POST["rollStudents"])) {
					$rollStudents=$_POST["rollStudents"] ;
				}
				$rollTeachers="" ;
				if (isset($_POST["rollTeachers"])) {
					$rollTeachers=$_POST["rollTeachers"] ;
				}
				
				if ($rollStudents!="on" AND $rollTeachers!="on") {
					print "<div class='error'>" ;
						print __($guid, "Your request failed because your inputs were invalid.") ;
					print "</div>" ;
				}
				else {
					for ($i=1; $i<=$count; $i++) {
						if (isset($_POST[$i . "-gibbonCourseClassID"])) {
							$gibbonCourseClassID=$_POST[$i . "-gibbonCourseClassID"] ;
							if (isset($_POST[$i . "-gibbonCourseClassIDNext"])) {
								$gibbonCourseClassIDNext=$_POST[$i . "-gibbonCourseClassIDNext"] ;
								
								//Get staff and students and copy them over
								if ($rollStudents=="on" AND $rollTeachers=="on") {
									$sqlWhere=" AND (role='Student' OR role='Teacher')" ;
								}
								else if ($rollStudents=="on" AND $rollTeachers=="") {
									$sqlWhere=" AND role='Student'" ;
								}
								else {
									$sqlWhere=" AND role='Teacher'" ;
								}
								//Get current enrolment
								try {
									$dataCurrent=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
									$sqlCurrent="SELECT gibbonPersonID, role FROM gibbonCourseClassPerson WHERE gibbonCourseClassID=:gibbonCourseClassID $sqlWhere" ;
									$resultCurrent=$connection2->prepare($sqlCurrent);
									$resultCurrent->execute($dataCurrent);
								}
								catch(PDOException $e) { 
									$partialFail=TRUE ;
								}
								if ($resultCurrent->rowCount()>0) {
									while ($rowCurrent=$resultCurrent->fetch()) {
										try {
											$dataInsert=array("gibbonCourseClassID"=>$gibbonCourseClassIDNext, "gibbonPersonID"=>$rowCurrent["gibbonPersonID"], "role"=>$rowCurrent["role"]); 
											$sqlInsert="INSERT INTO gibbonCourseClassPerson SET gibbonCourseClassID=:gibbonCourseClassID, gibbonPersonID=:gibbonPersonID, role=:role" ;
											$resultInsert=$connection2->prepare($sqlInsert);
											$resultInsert->execute($dataInsert);
										}
										catch(PDOException $e) { 
											$partialFail=TRUE ;
										}
									}
								}
							} 
						}
					}
					
					//Feedback result!
					if ($partialFail==TRUE) {
						print "<div class='error'>" ;
						print __($guid, "Your request was successful, but some data was not properly saved.") ;
						print "</div>" ;
					}
					else {
						print "<div class='success'>" ;
						print __($guid, "Your request was completed successfully.") ;
						print "</div>" ;
					}
				}				
			}
		}
	}
}
?>