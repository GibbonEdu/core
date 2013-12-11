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

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Planner/planner.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this page." ;
	print "</div>" ;
}
else {
	//Get action with highest precendence
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print "The highest grouped action cannot be determined." ;
		print "</div>" ;
	}
	else {
		//Set variables
		$today=date("Y-m-d");
		
		//Proceed!
		//Get viewBy, date and class variables
		$viewBy=NULL ;
		if (isset($_GET["viewBy"])) {
			$viewBy=$_GET["viewBy"] ;
		}
		$subView=NULL ;
		if (isset($_GET["subView"])) {
			$subView=$_GET["subView"] ;
		}
		if ($viewBy!="date" AND $viewBy!="class") {
			$viewBy="date" ;
		}
		$gibbonCourseClassID=NULL ;
		$date=NULL ;
		$dateStamp=NULL ;
		if ($viewBy=="date") {
			if (isset($_GET["date"])) {
				$date=$_GET["date"] ;
			}
			if (isset($_GET["dateHuman"])) {
				$date=dateConvert($_GET["dateHuman"]) ;
			}
			if ($date=="") {
				$date=date("Y-m-d");
			}
			list($dateYear, $dateMonth, $dateDay)=explode('-', $date);
			$dateStamp=mktime(0, 0, 0, $dateMonth, $dateDay, $dateYear);	
		}
		else if ($viewBy=="class") {
			$class=NULL ;
			if (isset($_GET["class"])) {
				$class=$_GET["class"] ;
			}
			$gibbonCourseClassID=$_GET["gibbonCourseClassID"] ;
		}
		list($todayYear, $todayMonth, $todayDay)=explode('-', $today);
		$todayStamp=mktime(0, 0, 0, $todayMonth, $todayDay, $todayYear);
		$gibbonPersonID="" ;
		
		//My children's classes
		if ($highestAction=="Lesson Planner_viewMyChildrensClasses") {
			$search=NULL ;
			if (isset($_GET["search"])) {
				$search=$_GET["search"] ;
			}
			print "<div class='trail'>" ;
			print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>My Children's Classes</div>" ;
			print "</div>" ;
			
			if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
			$updateReturnMessage ="" ;
			$class="error" ;
			if (!($updateReturn=="")) {
				if ($updateReturn=="fail0") {
					$updateReturnMessage ="Update failed because you do not have access to this action." ;	
				}
				else if ($updateReturn=="fail1") {
					$updateReturnMessage ="Update failed because a required parameter was not set." ;	
				}
				else if ($updateReturn=="fail2") {
					$updateReturnMessage ="Update failed due to a database error." ;	
				}
				else if ($updateReturn=="success0") {
					$updateReturnMessage ="Update was successful." ;	
					$class="success" ;
				}
				print "<div class='$class'>" ;
					print $updateReturnMessage;
				print "</div>" ;
			} 
				
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
				print "Access denied." ;
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
					catch(PDOException $e) { }
					while ($rowChild=$resultChild->fetch()) {
						$select="" ;
						if ($rowChild["gibbonPersonID"]==$search) {
							$select="selected" ;
						}
						
						$options=$options . "<option $select value='" . $rowChild["gibbonPersonID"] . "'>" . formatName("", $rowChild["preferredName"], $rowChild["surname"], "Student") . "</option>" ;
						$gibbonPersonID[$count]=$rowChild["gibbonPersonID"] ;
						$count++ ;
					}
				}
				
				if ($count==0) {
					print "<div class='error'>" ;
					print "Access denied." ;
					print "</div>" ;
				}
				else if ($count==1) {
					$search=$gibbonPersonID[0] ;
				}
				else {
					print "<h2>" ;
					print "Choose" ;
					print "</h2>" ;
					?>
					<form method="get" action="<? print $_SESSION[$guid]["absoluteURL"]?>/index.php">
						<table class='noIntBorder' cellspacing='0' style="width: 100%">	
							<tr><td style="width: 30%"></td><td></td></tr>
							<tr>
								<td> 
									<b>Search For</b><br/>
									<span style="font-size: 90%"><i>Preferred, surname, username.</i></span>
								</td>
								<td class="right">
									<select name="search" id="search" style="width: 302px">
										<option value=""></value>
										<? print $options ; ?> 
									</select>
								</td>
							</tr>
							<tr>
								<td colspan=2 class="right">
									<input type="hidden" name="q" value="/modules/<? print $_SESSION[$guid]["module"] ?>/planner.php">
									<input type="hidden" name="address" value="<? print $_SESSION[$guid]["address"] ?>">
									<input type="submit" value="Submit">
								</td>
							</tr>
						</table>
					</form>
					<?
				}
				
				$gibbonPersonID=$search ;
				
				if ($search!="" AND $count>0) {
					
					//Confirm access to this student
					try {
						$dataChild=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
						$sqlChild="SELECT * FROM gibbonFamilyChild JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonFamilyChild.gibbonPersonID=$gibbonPersonID AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID AND childDataAccess='Y'" ;
						$resultChild=$connection2->prepare($sqlChild);
						$resultChild->execute($dataChild);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
				
					if ($resultChild->rowCount()<1) {
						print "<div class='error'>" ;
						print "You do not have access to the specified student." ;
						print "</div>" ;
					}
					else {
						$rowChild=$resultChild->fetch() ;
						
						if ($count>1) {
							print "<h2>" ;
							print "Lessons" ;
							print "</h2>" ;
						}
						
						//Print planner
						if ($viewBy=="date") {
							if (isSchoolOpen($guid, date("Y-m-d", $dateStamp), $connection2)==FALSE) {
								print "<div class='warning'>" ;
									print "School is closed on the specified day." ;
								print "</div>" ;
							}
							else {
								if (isset($_GET["deleteReturn"])) { $deleteReturn=$_GET["deleteReturn"] ; } else { $deleteReturn="" ; }
								$deleteReturnMessage ="" ;
								$class="error" ;
								if (!($deleteReturn=="")) {
									if ($deleteReturn=="success0") {
										$deleteReturnMessage ="Delete was successful." ;	
										$class="success" ;
									}
									print "<div class='$class'>" ;
										print $deleteReturnMessage;
									print "</div>" ;
								} 
							
								try {
									$data=array("date1"=>$date, "gibbonPersonID1"=>$gibbonPersonID, "date2"=>$date, "gibbonPersonID2"=>$gibbonPersonID); 
									$sql="(SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, role, homeworkSubmission, homeworkCrowdAssess, date FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE date=:date1 AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID1 AND NOT role='Student - Left' AND NOT role='Teacher - Left') UNION (SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, role, homeworkSubmission, homeworkCrowdAssess, date FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonPlannerEntryGuest ON (gibbonPlannerEntryGuest.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE date=:date2 AND gibbonPlannerEntryGuest.gibbonPersonID=:gibbonPersonID2) ORDER BY date, timeStart" ; 
									$result=$connection2->prepare($sql);
									$result->execute($data);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								
								//Only show add if user has edit rights
								if ($highestAction=="Lesson Planner_viewAllEditMyClasses" OR $highestAction=="Lesson Planner_viewEditAllClasses") {
									print "<div class='linkTop'>" ;
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/planner_add.php&date=$date'><img title='New' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.gif'/></a>" ;
									print "</div>" ;
								}
								
								if ($result->rowCount()<1) {
									print "<div class='error'>" ;
									print "There are no lessons to display." ;
									print "</div>" ;
								}
								else {
									print "<table cellspacing='0' style='width: 100%'>" ;
										print "<tr class='head'>" ;
											print "<th>" ;
												print "Class" ;
											print "</th>" ;
											print "<th>" ;
												print "Lesson/Unit" ;
											print "</th>" ;
											print "<th>" ;
												print "Time" ;
											print "</th>" ;
											print "<th>" ;
												print "Homework<br/><span style='font-size: 80%'>Is set?</span>" ;
											print "</th>" ;
											print "<th>" ;
												print "View<br/><span style='font-size: 80%'>Who has access?</span>" ;
											print "</th>" ;
											print "<th>" ;
												print "Like" ;
											print "</th>" ;
											print "<th style='min-width: 140px'>" ;
												print "Action" ;
											print "</th>" ;
										print "</tr>" ;
										
										$count=0;
										$rowNum="odd" ;
										while ($row=$result->fetch()) {
											if (!($row["role"]=="Student" AND $row["viewableParents"]=="N")) {
												if ($count%2==0) {
													$rowNum="even" ;
												}
												else {
													$rowNum="odd" ;
												}
												$count++ ;
												
												//Highlight class in progress
												if ((date("H:i:s")>$row["timeStart"]) AND (date("H:i:s")<$row["timeEnd"]) AND ($date)==date("Y-m-d")) {
													$rowNum="current" ;
												}
												
												//COLOR ROW BY STATUS!
												print "<tr class=$rowNum>" ;
													print "<td>" ;
														print $row["course"] . "." . $row["class"] ;
													print "</td>" ;
													print "<td>" ;
														print "<b>" . $row["name"] . "</b><br/>" ;
														$unit=getUnit($connection2, $row["gibbonUnitID"], $row["gibbonHookID"], $row["gibbonCourseClassID"]) ;
														print $unit[0] ;
														if ($unit[1]!="") {
															print "<br/><i>" . $unit[1] . " Unit</i>" ;
														}
													print "</td>" ;
													print "<td>" ;
														print substr($row["timeStart"],0,5) . "-" . substr($row["timeEnd"],0,5) ;
													print "</td>" ;
													print "<td>" ;
														print $row["homework"] ;
														if ($row["homeworkSubmission"]=="Y") {
															print "+OS" ;
															if ($row["homeworkCrowdAssess"]=="Y") {
																print "+CA" ;
															}
														}
													print "</td>" ;
													print "<td>" ;
														if ($row["viewableStudents"]=="Y") {
															print "Students" ;
														}
														if ($row["viewableStudents"]=="Y" AND $row["viewableParents"]=="Y") {
															print ", " ;
														}
														if ($row["viewableParents"]=="Y") {
															print "Parents" ;
														}
													print "</td>" ;
													print "<td>" ;
														try {
															$dataLike=array(); 
															$sqlLike="SELECT * FROM gibbonPlannerEntryLike WHERE gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . " AND gibbonPersonID=" . $_SESSION[$guid]["gibbonPersonID"] ;
															$resultLike=$connection2->prepare($sqlLike);
															$resultLike->execute($dataLike);
														}
														catch(PDOException $e) { 
															print "<div class='error'>" . $e->getMessage() . "</div>" ; 
														}
														if ($resultLike->rowCount()!=1) {
															print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/Planner/plannerProcess.php?gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&address=" . $_GET["q"] . "&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&date=$date&gibbonPersonID=$gibbonPersonID'><img src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/like_off.png'></a>" ;
														}
														else {
															print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/Planner/plannerProcess.php?gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&address=" . $_GET["q"] . "&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&date=$date&gibbonPersonID=$gibbonPersonID'><img src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/like_on.png'></a>" ;
														}
													print "</td>" ;
													print "<td>" ;
														print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/planner_view_full.php&search=$gibbonPersonID&gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&date=$date&width=1000&height=550'><img title='View Details' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a> " ;
													print "</td>" ;
												print "</tr>" ;
											}
										}
									print "</table>" ;
								}
							}
						}
						else if ($viewBy=="class") {
							if ($gibbonCourseClassID=="") {
								print "<div class='error'>" ;
									print "You have not specified a class." ;
								print "</div>" ;
							}
							else {
								try {
									$data=array(); 
									$sql="SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClassPerson JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=" . $_SESSION[$guid]["gibbonSchoolYearID"] . " AND gibbonCourseClass.gibbonCourseClassID=$gibbonCourseClassID AND gibbonPersonID=$gibbonPersonID" ;
									$result=$connection2->prepare($sql);
									$result->execute($data);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}

								if ($result->rowCount()!=1) {
									print "<div class='error'>" ;
										print "You do not have permission to access the specified class." ;
									print "</div>" ;
								
								}
								else {
									$row=$result->fetch() ;
									
									if (isset($_GET["deleteReturn"])) { $deleteReturn=$_GET["deleteReturn"] ; } else { $deleteReturn="" ; }
									$deleteReturnMessage ="" ;
									$class="error" ;
									if (!($deleteReturn=="")) {
										if ($deleteReturn=="success0") {
											$deleteReturnMessage ="Delete was successful." ;	
											$class="success" ;
										}
										print "<div class='$class'>" ;
											print $deleteReturnMessage;
										print "</div>" ;
									} 
									
									try {
										$data=array("gibbonCourseClassID"=>$gibbonCourseClassID, "gibbonPersonID"=>$gibbonPersonID); 
										$sql="SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, viewableStudents, viewableParents, homework, role, homeworkSubmission, homeworkCrowdAssess FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' ORDER BY date DESC, timeStart DESC" ; 
										$result=$connection2->prepare($sql);
										$result->execute($data);
									}
									catch(PDOException $e) { 
										print "<div class='error'>" . $e->getMessage() . "</div>" ; 
									}
									
									//Only show add if user has edit rights
									if ($highestAction=="Lesson Planner_viewAllEditMyClasses" OR $highestAction=="Lesson Planner_viewEditAllClasses") {
										print "<div class='linkTop'>" ;
										print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/planner_add.php&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID'><img title='New' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.gif'/></a>" ;
										print "</div>" ;
									}
									
									if ($result->rowCount()<1) {
										print "<div class='error'>" ;
										print "There are no lessons to display." ;
										print "</div>" ;
									}
									else {
										print "<table cellspacing='0' style='width: 100%'>" ;
											print "<tr class='head'>" ;
												print "<th>" ;
													print "Date" ;
												print "</th>" ;
												print "<th>" ;
													print "Lesson/Unit" ;
												print "</th>" ;
												print "<th>" ;
													print "Time" ;
												print "</th>" ;
												print "<th>" ;
													print "Homework<br/><span style='font-size: 80%'>Is set?</span>" ;
												print "</th>" ;
												print "<th>" ;
													print "View<br/><span style='font-size: 80%'>Who has access?</span>" ;
												print "</th>" ;
												print "<th>" ;
													print "Like" ;
												print "</th>" ;
												print "<th style='min-width: 140px'>" ;
													print "Action" ;
												print "</th>" ;
											print "</tr>" ;
											
											$count=0;
											$rowNum="odd" ;
											while ($row=$result->fetch()) {
												if (!($row["role"]=="Student" AND $row["viewableParents"]=="N")) {
												if ($count%2==0) {
														$rowNum="even" ;
													}
													else {
														$rowNum="odd" ;
													}
													$count++ ;
													
													//Highlight class in progress
													if ((date("Y-m-d")==$row["date"]) AND (date("H:i:s")>$row["timeStart"]) AND (date("H:i:s")<$row["timeEnd"])) {
														$rowNum="current" ;
													}
													
													//COLOR ROW BY STATUS!
													print "<tr class=$rowNum>" ;
														print "<td>" ;
															if (!(is_null($row["date"]))) {
																print "<b>" . dateConvertBack($row["date"]) . "</b><br/>" ;
																print date("l", dateConvertToTimestamp($row["date"])) ;
															}
														print "</td>" ;
														print "<td>" ;
															print "<b>" . $row["name"] . "</b><br/>" ;
															if ($row["gibbonUnitID"]!="") {
																$unit=getUnit($connection2, $row["gibbonUnitID"], $row["gibbonHookID"], $row["gibbonCourseClassID"]) ;
																print $unit[0] ;
																if ($unit[1]!="") {
																	print "<br/><i>" . $unit[1] . " Unit</i>" ;
																}
															}
														print "</td>" ;
														print "<td>" ;
															if ($row["timeStart"]!="" AND $row["timeEnd"]!="") {
																print substr($row["timeStart"],0,5) . "-" . substr($row["timeEnd"],0,5) ;
															}
														print "</td>" ;
														print "<td>" ;
															print $row["homework"] ;
															if ($row["homeworkSubmission"]=="Y") {
																print "+OS" ;
																if ($row["homeworkCrowdAssess"]=="Y") {
																	print "+CA" ;
																}
															}
														print "</td>" ;
														print "<td>" ;
															if ($row["viewableStudents"]=="Y") {
																print "Students" ;
															}
															if ($row["viewableStudents"]=="Y" AND $row["viewableParents"]=="Y") {
																print ", " ;
															}
															if ($row["viewableParents"]=="Y") {
																print "Parents" ;
															}
														print "</td>" ;
														print "<td>" ;
															try {
																$dataLike=array(); 
																$sqlLike="SELECT * FROM gibbonPlannerEntryLike WHERE gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . " AND gibbonPersonID=" . $_SESSION[$guid]["gibbonPersonID"] ;
																$resultLike=$connection2->prepare($sqlLike);
																$resultLike->execute($dataLike);
															}
															catch(PDOException $e) { 
																print "<div class='error'>" . $e->getMessage() . "</div>" ; 
															}
															if ($resultLike->rowCount()!=1) {
																print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/Planner/plannerProcess.php?gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&address=" . $_GET["q"] . "&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&date=$date&gibbonPersonID=$gibbonPersonID'><img src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/like_off.png'></a>" ;
															}
															else {
																print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/Planner/plannerProcess.php?gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&address=" . $_GET["q"] . "&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&date=$date&gibbonPersonID=$gibbonPersonID'><img src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/like_on.png'></a>" ;
															}
														print "</td>" ;
														print "<td>" ;
															print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/planner_view_full.php&search=$gibbonPersonID&gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&viewBy=class&gibbonCourseClassID=$gibbonCourseClassID&width=1000&height=550'><img title='View Details' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a> " ;
														print "</td>" ;
													print "</tr>" ;
												}
											}
										print "</table>" ;
									}
								}
							}
						}
					}
				}
			}
		}
		//My Classes
		else if ($highestAction=="Lesson Planner_viewMyClasses" OR $highestAction=="Lesson Planner_viewAllEditMyClasses" OR $highestAction=="Lesson Planner_viewEditAllClasses" ) {
			$gibbonPersonID=$_SESSION[$guid]["gibbonPersonID"] ;
			
			if ($viewBy=="date") {
				print "<div class='trail'>" ;
				print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>Planner " . dateConvertBack($date) . "</div>" ;
				print "</div>" ;
				
				//Get Smart Workflow help message
				$category=getRoleCategory($_SESSION[$guid]["gibbonRoleIDCurrent"], $connection2) ;
				if ($category=="Staff") {
					$smartWorkflowHelp=getSmartWorkflowHelp($connection2, $guid, 3) ;
					if ($smartWorkflowHelp!=false) {
						print $smartWorkflowHelp ;
					}
				}
				
				if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
				$updateReturnMessage ="" ;
				$class="error" ;
				if (!($updateReturn=="")) {
					if ($updateReturn=="fail0") {
						$updateReturnMessage ="Update failed because you do not have access to this action." ;	
					}
					else if ($updateReturn=="fail1") {
						$updateReturnMessage ="Update failed because a required parameter was not set." ;	
					}
					else if ($updateReturn=="fail2") {
						$updateReturnMessage ="Update failed due to a database error." ;	
					}
					else if ($updateReturn=="success0") {
						$updateReturnMessage ="Update was successful." ;	
						$class="success" ;
					}
					print "<div class='$class'>" ;
						print $updateReturnMessage;
					print "</div>" ;
				} 
				
				if (isSchoolOpen($guid, date("Y-m-d", $dateStamp), $connection2)==FALSE) {
					print "<div class='warning'>" ;
						print "School is closed on the specified day." ;
					print "</div>" ;
				}
				else {
					if (isset($_GET["deleteReturn"])) { $deleteReturn=$_GET["deleteReturn"] ; } else { $deleteReturn="" ; }
					$deleteReturnMessage ="" ;
					$class="error" ;
					if (!($deleteReturn=="")) {
						if ($deleteReturn=="success0") {
							$deleteReturnMessage ="Delete was successful." ;	
							$class="success" ;
						}
						print "<div class='$class'>" ;
							print $deleteReturnMessage;
						print "</div>" ;
					} 
				
					//Set pagination variable
					$page=1 ; if (isset($_GET["page"])) { $page=$_GET["page"] ; }
					if ((!is_numeric($page)) OR $page<1) {
						$page=1 ;
					}
					
					try {
						if ($highestAction=="Lesson Planner_viewEditAllClasses" ) {
							$data=array("date"=>$date); 
							$sql="SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonHookID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, NULL AS role, homeworkSubmission, homeworkCrowdAssess, date, gibbonPlannerEntry.gibbonCourseClassID FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE date=:date ORDER BY date, timeStart" ; 
						}
						else if ($highestAction=="Lesson Planner_viewMyClasses" OR $highestAction=="Lesson Planner_viewAllEditMyClasses") {
							$data=array("date1"=>$date, "gibbonPersonID1"=>$gibbonPersonID, "date2"=>$date, "gibbonPersonID2"=>$gibbonPersonID); 
							$sql="(SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonHookID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, role, homeworkSubmission, homeworkCrowdAssess, date, gibbonPlannerEntry.gibbonCourseClassID FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE date=:date1 AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID1 AND NOT role='Student - Left' AND NOT role='Teacher - Left') UNION (SELECT gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonHookID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, role, homeworkSubmission, homeworkCrowdAssess, date, gibbonPlannerEntry.gibbonCourseClassID FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonPlannerEntryGuest ON (gibbonPlannerEntryGuest.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE date=:date2 AND gibbonPlannerEntryGuest.gibbonPersonID=:gibbonPersonID2) ORDER BY date, timeStart" ; 
						}
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					
					//Only show add if user has edit rights
					if ($highestAction=="Lesson Planner_viewEditMyClasses" OR $highestAction=="Lesson Planner_viewEditAllClasses") {
						print "<div class='linkTop'>" ;
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/planner_add.php&date=$date'><img title='New' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.gif'/></a>" ;
						print "</div>" ;
					}
					
					if ($result->rowCount()<1) {
						print "<div class='error'>" ;
						print "There are no lessons to display." ;
						print "</div>" ;
					}
					else {
						print "<table cellspacing='0' style='width: 100%'>" ;
							print "<tr class='head'>" ;
								print "<th>" ;
									print "Class" ;
								print "</th>" ;
								print "<th>" ;
									print "Lesson/Unit" ;
								print "</th>" ;
								print "<th>" ;
									print "Time" ;
								print "</th>" ;
								print "<th>" ;
									print "Homework<br/><span style='font-size: 80%'>Is set?</span>" ;
								print "</th>" ;
								print "<th>" ;
									print "View<br/><span style='font-size: 80%'>Who has access?</span>" ;
								print "</th>" ;
								print "<th>" ;
									print "Like" ;
								print "</th>" ;
								print "<th style='min-width: 115px'>" ;
									print "Action" ;
								print "</th>" ;
							print "</tr>" ;
							
							$count=0;
							$rowNum="odd" ;
							while ($row=$result->fetch()) {
								if ((!($row["role"]=="Student" AND $row["viewableStudents"]=="N")) AND (!($row["role"]=="Guest Student" AND $row["viewableStudents"]=="N"))) {
									if ($count%2==0) {
										$rowNum="even" ;
									}
									else {
										$rowNum="odd" ;
									}
									$count++ ;
									
									//Highlight class in progress
									if ((date("H:i:s")>$row["timeStart"]) AND (date("H:i:s")<$row["timeEnd"]) AND ($date)==date("Y-m-d")) {
										$rowNum="current" ;
									}
									//Dull out past classes
									if ((($row["date"])==date("Y-m-d") AND (date("H:i:s")>$row["timeEnd"])) OR ($row["date"])<date("Y-m-d")) {
										$rowNum="past" ;
									}
									
									//COLOR ROW BY STATUS!
									print "<tr class=$rowNum>" ;
										print "<td>" ;
											print $row["course"] . "." . $row["class"] ;
										print "</td>" ;
										print "<td>" ;
											print "<b>" . $row["name"] . "</b><br/>" ;
											$unit=getUnit($connection2, $row["gibbonUnitID"], $row["gibbonHookID"], $row["gibbonCourseClassID"]) ;
											if (isset($unit[0])) {
												print $unit[0] ;
												if ($unit[1]!="") {
													print "<br/><i>" . $unit[1] . " Unit</i>" ;
												}
											}
										print "</td>" ;
										print "<td>" ;
											print substr($row["timeStart"],0,5) . "-" . substr($row["timeEnd"],0,5) ;
										print "</td>" ;
										print "<td>" ;
											print $row["homework"] ;
											if ($row["homeworkSubmission"]=="Y") {
												print "+OS" ;
												if ($row["homeworkCrowdAssess"]=="Y") {
													print "+CA" ;
												}
											}
										print "</td>" ;
										print "<td>" ;
											if ($row["viewableStudents"]=="Y") {
												print "Students" ;
											}
											if ($row["viewableStudents"]=="Y" AND $row["viewableParents"]=="Y") {
												print ", " ;
											}
											if ($row["viewableParents"]=="Y") {
												print "Parents" ;
											}
										print "</td>" ;
										print "<td>" ;
											if ($row["role"]=="Teacher") {
												try {
													$dataLike=array("gibbonPlannerEntryID"=>$row["gibbonPlannerEntryID"]); 
													$sqlLike="SELECT * FROM gibbonPlannerEntryLike WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID" ;
													$resultLike=$connection2->prepare($sqlLike);
													$resultLike->execute($dataLike);
												}
												catch(PDOException $e) { 
													print "<div class='error'>" . $e->getMessage() . "</div>" ; 
												}
												print $resultLike->rowCount() ;
											}
											else {
												try {
													$dataLike=array("gibbonPlannerEntryID"=>$row["gibbonPlannerEntryID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
													$sqlLike="SELECT * FROM gibbonPlannerEntryLike WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID" ;
													$resultLike=$connection2->prepare($sqlLike);
													$resultLike->execute($dataLike);
												}
												catch(PDOException $e) { }
												if ($resultLike->rowCount()!=1) {
													print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/Planner/plannerProcess.php?gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&address=" . $_GET["q"] . "&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&date=$date'><img src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/like_off.png'></a>" ;
												}
												else {
													print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/Planner/plannerProcess.php?gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&address=" . $_GET["q"] . "&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&date=$date'><img src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/like_on.png'></a>" ;
												}
											}
										print "</td>" ;
										print "<td>" ;
											print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/planner_view_full.php&gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&date=$date&width=1000&height=550'><img title='View Details' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a> " ;
											if ($highestAction=="Lesson Planner_viewAllEditMyClasses" OR $highestAction=="Lesson Planner_viewEditAllClasses") {
												print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/planner_edit.php&gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&date=$date'><img title='Edit' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
												print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/planner_delete.php&gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&date=$date'><img title='Delete' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a>" ;
												print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/planner_duplicate.php&gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&date=$date'><img title='Duplicate' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/copy.png'/></a>" ;
											}
										print "</td>" ;
									print "</tr>" ;
								}
							}
						print "</table>" ;
					}
				}
			}
			else if ($viewBy=="class") {
				if ($gibbonCourseClassID=="") {
					print "<div class='error'>" ;
						print "You have not specified a class." ;
					print "</div>" ;
				}
				else {
					if ($highestAction=="Lesson Planner_viewEditAllClasses" OR $highestAction=="Lesson Planner_viewAllEditMyClasses") {
						try {
							$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonCourseClassID"=>$gibbonCourseClassID); 
							$sql="SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						$teacher=FALSE ;
						
						try {
							$dataTeacher=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonCourseClassID"=>$gibbonCourseClassID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
							$sqlTeacher="SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClassPerson JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID AND gibbonPersonID=:gibbonPersonID" ;
							$resultTeacher=$connection2->prepare($sqlTeacher);
							$resultTeacher->execute($dataTeacher);
						}
						catch(PDOException $e) { }
						if ($resultTeacher->rowCount()>0) {
							$teacher=TRUE ;
						}
					}
					else if ($highestAction=="Lesson Planner_viewMyClasses") {
						try {
							$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonCourseClassID"=>$gibbonCourseClassID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
							$sql="SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourseClassPerson JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID AND gibbonPersonID=:gibbonPersonID" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
					}
					
					if ($result->rowCount()!=1) {
						print "<div class='error'>" ;
							print "You do not have permission to access the specified class." ;
						print "</div>" ;
					
					}
					else {
						$row=$result->fetch() ;
						
						print "<div class='trail'>" ;
						print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>Planner " . $row["course"] . "." . $row["class"] . "</div>" ;
						print "</div>" ;
						
						//Get Smart Workflow help message
						$category=getRoleCategory($_SESSION[$guid]["gibbonRoleIDCurrent"], $connection2) ;
						if ($category=="Staff") {
							$smartWorkflowHelp=getSmartWorkflowHelp($connection2, $guid, 3) ;
							if ($smartWorkflowHelp!=false) {
								print $smartWorkflowHelp ;
							}
						}
	
						if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
						$updateReturnMessage ="" ;
						$class="error" ;
						if (!($updateReturn=="")) {
							if ($updateReturn=="fail0") {
								$updateReturnMessage ="Update failed because you do not have access to this action." ;	
							}
							else if ($updateReturn=="fail1") {
								$updateReturnMessage ="Update failed because a required parameter was not set." ;	
							}
							else if ($updateReturn=="fail2") {
								$updateReturnMessage ="Update failed due to a database error." ;	
							}
							else if ($updateReturn=="success0") {
								$updateReturnMessage ="Update was successful." ;	
								$class="success" ;
							}
							print "<div class='$class'>" ;
								print $updateReturnMessage;
							print "</div>" ;
						} 
						
						if (isset($_GET["deleteReturn"])) { $deleteReturn=$_GET["deleteReturn"] ; } else { $deleteReturn="" ; }
						$deleteReturnMessage ="" ;
						$class="error" ;
						if (!($deleteReturn=="")) {
							if ($deleteReturn=="success0") {
								$deleteReturnMessage ="Delete was successful." ;	
								$class="success" ;
							}
							print "<div class='$class'>" ;
								print $deleteReturnMessage;
							print "</div>" ;
						}
						
						if (isset($_GET["bumpReturn"])) { $bumpReturn=$_GET["bumpReturn"] ; } else { $bumpReturn="" ; }
						$bumpReturnMessage ="" ;
						$class="error" ;
						if (!($bumpReturn=="")) {
							if ($bumpReturn=="success0") {
								$bumpReturnMessage ="Bump was successful. It is possible that some lessons have not been moved forward (if there was no space for them), but a reasonable effort has been made." ;	
								$class="success" ;
							}
							print "<div class='$class'>" ;
								print $bumpReturnMessage;
							print "</div>" ;
						}  
					
						try {
							if ($highestAction=="Lesson Planner_viewEditAllClasses" OR $highestAction=="Lesson Planner_viewAllEditMyClasses") {
								if ($subView=="lesson" OR $subView=="") {
									$data=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
									$sql="SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonHookID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, viewableStudents, viewableParents, homework, NULL as role, homeworkSubmission, homeworkCrowdAssess, gibbonPlannerEntry.gibbonCourseClassID FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID ORDER BY date DESC, timeStart DESC" ; 
								}
								else {
									$data=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
									$sql="SELECT timeStart, timeEnd, date, gibbonTTColumnRow.name AS period, gibbonTTDayRowClassID, gibbonTTDayDateID FROM gibbonTTDayRowClass JOIN gibbonTTColumnRow ON (gibbonTTDayRowClass.gibbonTTColumnRowID=gibbonTTColumnRow.gibbonTTColumnRowID) JOIN gibbonTTColumn ON (gibbonTTColumnRow.gibbonTTColumnID=gibbonTTColumn.gibbonTTColumnID) JOIN gibbonTTDay ON (gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) JOIN gibbonTTDayDate ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDay.gibbonTTDayID) WHERE gibbonCourseClassID=:gibbonCourseClassID ORDER BY date, timestart" ;
								}
							}
							else if ($highestAction=="Lesson Planner_viewMyClasses") {
								$data=array("gibbonCourseClassID"=>$gibbonCourseClassID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
								$sql="SELECT gibbonPlannerEntryID, gibbonUnitID, gibbonHookID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, viewableStudents, viewableParents, homework, role, homeworkSubmission, homeworkCrowdAssess, gibbonPlannerEntry.gibbonCourseClassID FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' ORDER BY date DESC, timeStart DESC" ; 
							}
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						
						
						//Only show add if user has edit rights
						if ($highestAction=="Lesson Planner_viewAllEditMyClasses" OR $highestAction=="Lesson Planner_viewEditAllClasses") {
							print "<div class='linkTop'>" ;
							$style="" ;
							if ($subView=="lesson" OR $subView=="") { $style="style='font-weight: bold'" ; }
							print "<a $style href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/planner.php&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&subView=lesson'>Lesson View</a> | " ;
							$style="" ;
							if ($subView=="year") { $style="style='font-weight: bold'" ; }
							print "<a $style href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/planner.php&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&subView=year'>Year Overview</a> | " ;
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/planner_add.php&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&subView=$subView'><img style='margin-bottom: -4px' title='New' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.gif'/></a>" ;
							print "</div>" ;
						}
						
						if ($result->rowCount()<1) {
							print "<div class='error'>" ;
							print "There are no lessons to display." ;
							print "</div>" ;
						}
						else {
							//PRINT LESSON VIEW
							if ($subView=="lesson" OR $subView=="") {
								print "<table cellspacing='0' style='width: 100%'>" ;
									print "<tr class='head'>" ;
										print "<th>" ;
											print "Date" ;
										print "</th>" ;
										print "<th>" ;
											print "Lesson/Unit" ;
										print "</th>" ;
										print "<th>" ;
											print "Time" ;
										print "</th>" ;
										print "<th>" ;
											print "Homework<br/><span style='font-size: 80%'>Is set?</span>" ;
										print "</th>" ;
										print "<th>" ;
											print "View<br/><span style='font-size: 80%'>Who has access?</span>" ;
										print "</th>" ;
										print "<th>" ;
											print "Like" ;
										print "</th>" ;
										print "<th style='min-width: 140px'>" ;
											print "Action" ;
										print "</th>" ;
									print "</tr>" ;
									
									$count=0;
									$pastCount=0;
									$rowNum="odd" ;
									while ($row=$result->fetch()) {
										if ((!($row["role"]=="Student" AND $row["viewableStudents"]=="N")) AND (!($row["role"]=="Guest Student" AND $row["viewableStudents"]=="N"))) {
											if ($count%2==0) {
												$rowNum="even" ;
											}
											else {
												$rowNum="odd" ;
											}
											$count++ ;
											
											//Highlight class in progress
											if ((date("Y-m-d")==$row["date"]) AND (date("H:i:s")>$row["timeStart"]) AND (date("H:i:s")<$row["timeEnd"])) {
												$rowNum="current" ;
											}
											
											//Dull out past classes
											if ((($row["date"])==date("Y-m-d") AND (date("H:i:s")>$row["timeEnd"])) OR ($row["date"])<date("Y-m-d")) {
												$rowNum="past" ;
												if ($pastCount==0) {
													print "<tr style='padding: 0px; height: 2px; background-color: #000'>" ;
														print "<td style='padding: 0px' colspan=8>" ;
													print "</tr>" ;
												}
												$pastCount++;
											}
											
											//COLOR ROW BY STATUS!
											print "<tr class=$rowNum>" ;
												print "<td>" ;
													if (!(is_null($row["date"]))) {
														print "<b>" . dateConvertBack($row["date"]) . "</b><br/>" ;
														print date("l", dateConvertToTimestamp($row["date"])) ;
													}
												print "</td>" ;
												print "<td>" ;
													print "<b>" . $row["name"] . "</b><br/>" ;
													$unit=getUnit($connection2, $row["gibbonUnitID"], $row["gibbonHookID"], $row["gibbonCourseClassID"]) ;
													if (isset($unit[0])) {
														print $unit[0] ;
														if (isset($unit[1])) {
															if ($unit[1]!="") {
																print "<br/><i>" . $unit[1] . " Unit</i>" ;
															}
														}
													}
												print "</td>" ;
													print "<td>" ;
													if ($row["timeStart"]!="" AND $row["timeEnd"]!="") {
														print substr($row["timeStart"],0,5) . "-" . substr($row["timeEnd"],0,5) ;
													}
												print "</td>" ;
												print "<td>" ;
													print $row["homework"] ;
													if ($row["homeworkSubmission"]=="Y") {
														print "+OS" ;
														if ($row["homeworkCrowdAssess"]=="Y") {
															print "+CA" ;
														}
													}
												print "</td>" ;
												print "<td>" ;
													if ($row["viewableStudents"]=="Y") {
														print "Students" ;
													}
													if ($row["viewableStudents"]=="Y" AND $row["viewableParents"]=="Y") {
														print ", " ;
													}
													if ($row["viewableParents"]=="Y") {
														print "Parents" ;
													}
												print "</td>" ;
												print "<td>" ;
													if ($row["role"]=="Teacher") {
														try {
															$dataLike=array("gibbonPlannerEntryID"=>$row["gibbonPlannerEntryID"]); 
															$sqlLike="SELECT * FROM gibbonPlannerEntryLike WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID" ;
															$resultLike=$connection2->prepare($sqlLike);
															$resultLike->execute($dataLike);
														}
														catch(PDOException $e) { 
															print "<div class='error'>" . $e->getMessage() . "</div>" ; 
														}
														print $resultLike->rowCount() ;
													}
													else {
														try {
															$dataLike=array("gibbonPlannerEntryID"=>$row["gibbonPlannerEntryID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
															$sqlLike="SELECT * FROM gibbonPlannerEntryLike WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID" ;
															$resultLike=$connection2->prepare($sqlLike);
															$resultLike->execute($dataLike);
														}
														catch(PDOException $e) { }
														if ($resultLike->rowCount()!=1) {
															print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/Planner/plannerProcess.php?gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&address=" . $_GET["q"] . "&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&date=$date'><img src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/like_off.png'></a>" ;
														}
														else {
															print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/Planner/plannerProcess.php?gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&address=" . $_GET["q"] . "&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&date=$date'><img src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/like_on.png'></a>" ;
														}
													}
												print "</td>" ;
												print "<td>" ;
													print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/planner_view_full.php&gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&viewBy=class&gibbonCourseClassID=$gibbonCourseClassID&width=1000&height=550'><img title='View Details' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a> " ;
													if ((($highestAction=="Lesson Planner_viewAllEditMyClasses" AND $teacher==TRUE) OR $highestAction=="Lesson Planner_viewEditAllClasses")) {
														print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/planner_edit.php&gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID'><img title='Edit' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
														print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/planner_bump.php&gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID'><img title='Bump Forward' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_right.png'/></a>" ;
														print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/planner_delete.php&gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID'><img title='Delete' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a>" ;
														print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/planner_duplicate.php&gibbonPlannerEntryID=" . $row["gibbonPlannerEntryID"] . "&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&date=$date'><img title='Duplicate' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/copy.png'/></a>" ;
													}
												print "</td>" ;
											print "</tr>" ;
										}
									}
								print "</table>" ;
							}
							//PRINT YEAR OVERVIEW
							else {
								$count=0 ;
								$lessons=array() ;
								while ($rowNext=$result->fetch()) {
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
										$lessons[$count][11]=$rowPlanner["gibbonUnitID"] ;
										$lessons[$count][12]=$rowPlanner["gibbonPlannerEntryID"] ;
										$lessons[$count][13]=$rowPlanner["gibbonHookID"] ;
										$lessons[$count][14]=$rowPlanner["gibbonCourseClassID"] ;
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
										$lessons[$count][11]=$rowPlanner["gibbonUnitID"] ;
										$lessons[$count][12]=$rowPlanner["gibbonPlannerEntryID"] ;
										$lessons[$count][13]=$rowPlanner["gibbonHookID"] ;
										$lessons[$count][14]=$rowPlanner["gibbonCourseClassID"] ;
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
									print "There are no lessons to display." ;
									print "</div>" ;
								}
								else {
									//Get term dates
									$terms=array() ;
									$termCount=0 ;
									try {
										$dataTerms=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
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
										$dataSpecial=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
										$sqlSpecial="SELECT gibbonSchoolYearSpecialDay.date, gibbonSchoolYearSpecialDay.name FROM gibbonSchoolYearSpecialDay JOIN gibbonSchoolYearTerm ON (gibbonSchoolYearSpecialDay.gibbonSchoolYearTermID=gibbonSchoolYearTerm.gibbonSchoolYearTermID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND type='School Closure' ORDER BY date" ;
										$resultSpecial=$connection2->prepare($sqlSpecial);
										$resultSpecial->execute($dataSpecial);
									}
									catch(PDOException $e) { 
										print "<div class='error'>" . $e->getMessage() . "</div>" ; 
									}
									
									$lastName="" ;
									$currentName="" ;
									$lastDate="" ;
									$currentDate="" ;
									$originalDate="" ;
									while ($rowSpecial=$resultSpecial->fetch()) {
										$currentName=$rowSpecial["name"] ;
										$currentDate=$rowSpecial["date"] ;
										if ($currentName!=$lastName) {
											$currentName=$rowSpecial["name"] ;
											$specials[$specialCount][0]=$rowSpecial["date"] ;
											$specials[$specialCount][1]=$rowSpecial["name"] ;
											$specials[$specialCount][2]=dateConvertBack($rowSpecial["date"]) ;
											$originalDate=dateConvertBack($rowSpecial["date"]) ;
											$specialCount++ ;
										}
										else {
											if ((strtotime($currentDate)-strtotime($lastDate))==86400) {
												$specials[$specialCount-1][2]=$originalDate . " - " . dateConvertBack($rowSpecial["date"]) ;
											}
											else {
												$currentName=$rowSpecial["name"] ;
												$specials[$specialCount][0]=$rowSpecial["date"] ;
												$specials[$specialCount][1]=$rowSpecial["name"] ;
												$specials[$specialCount][2]=dateConvertBack($rowSpecial["date"]) ;
												$originalDate=dateConvertBack($rowSpecial["date"]) ;
												$specialCount++ ;
											}
										}
										$lastName=$rowSpecial["name"] ;
										$lastDate=$rowSpecial["date"] ;
									}
								
									print "<table cellspacing='0' style='width: 100%'>" ;
										print "<tr class='head'>" ;
											print "<th>" ;
												print "Lesson<br/>Number" ;
											print "</th>" ;
											print "<th>" ;
												print "Date" ;
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
															print dateConvertBack($terms[$termCount][0]) ;
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
														print "<b>" . dateConvertBack($lesson["1"]) . "</b><br/>" ;
														print date("l", dateConvertToTimestamp($lesson["1"])) . "<br/>" ;
														print date("F", dateConvertToTimestamp($lesson["1"])) . "<br/>" ;
														 if ($lesson[8]=="Timing Change") {
															print "<u>" . $lesson[8] . "</u><br/><i>(" . substr($lesson[9],0,5) . "-" . substr($lesson[10],0,5) . ")</i>" ;
														 }
													print "</td>" ;
													print "<td $style>" ;
														print $lesson["4"] . "<br/>" ;
														print substr($lesson["2"],0,5) . " - " . substr($lesson["3"],0,5) ;
													print "</td>" ;
													print "<td $style>" ;
														if ($lesson["0"]=="Planned") {
															print "<b>" . $lesson["5"] . "</b><br/>" ;
															$unit=getUnit($connection2, $lesson[11], $lesson[13], $lesson[14]) ;
															if (isset($unit[0])) {
																print $unit[0] ;
																if (isset($unit[1])) {
																	if ($unit[1]!="") {
																		print "<br/><i>" . $unit[1] . " Unit</i>" ;
																	}
																}
															}
														}
													print "</td>" ;
													print "<td $style>" ;
														if ($lesson["0"]=="Unplanned") {
															print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/planner_add.php&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&date=" . $lesson[1] . "&timeStart=" . $lesson[2] . "&timeEnd=" . $lesson[3] . "&subView=$subView'><img style='margin-bottom: -4px' title='New' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.gif'/></a>" ;
														}
														else {
															print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/planner_view_full.php&gibbonPlannerEntryID=" . $lesson[12] . "&viewBy=class&gibbonCourseClassID=$gibbonCourseClassID&width=1000&height=550&subView=$subView'><img title='View Details' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a> " ;
															if ((($highestAction=="Lesson Planner_viewAllEditMyClasses" AND $teacher==TRUE) OR $highestAction=="Lesson Planner_viewEditAllClasses")) {
																print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/planner_edit.php&gibbonPlannerEntryID=" . $lesson[12] . "&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&subView=$subView'><img title='Edit' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
																print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/planner_bump.php&gibbonPlannerEntryID=" . $lesson[12] . "&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&subView=$subView'><img title='Bump Forward' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_right.png'/></a>" ;
																print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/planner_delete.php&gibbonPlannerEntryID=" . $lesson[12] . "&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&subView=$subView'><img title='Delete' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a>" ;
																print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/planner_duplicate.php&gibbonPlannerEntryID=" . $lesson[12] . "&viewBy=$viewBy&gibbonCourseClassID=$gibbonCourseClassID&date=$date&subView=$subView'><img title='Duplicate' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/copy.png'/></a>" ;
															}
														}
													print "</td>" ;
												print "</tr>" ;
												$classCount++ ;
											}
											
											//Spit out row for end of term/year
											while ($lesson["1"]>=@$terms[$termCount][0] AND $termCount<count($terms) AND substr($terms[$termCount][1],0,3)=="End") {
												print "<tr class='dull'>" ;
													print "<td>" ;
														print "<b>" . $terms[$termCount][1] . "</b>" ;
													print "</td>" ;
													print "<td colspan=6>" ;
														print dateConvertBack($terms[$termCount][0]) ;
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
													print dateConvertBack($terms[$termCount][0]) ;
												print "</td>" ;
											print "</tr>" ;
										}
									print "</table>" ;									
								}	
							}
						}
					}
				}
			}
		}
	}
	if ($gibbonPersonID!="") {
		//Print sidebar
		$_SESSION[$guid]["sidebarExtra"]=sidebarExtra($guid, $connection2, $todayStamp, $gibbonPersonID, $dateStamp, $gibbonCourseClassID ) ;
	}
}		
?>