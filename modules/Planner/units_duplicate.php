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

if (isActionAccessible($guid, $connection2, "/modules/Planner/units_duplicate.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
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
		print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/units.php&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "&gibbonCourseID=" . $_GET["gibbonCourseID"] . "'>Manage Units</a> > </div><div class='trailEnd'>Duplicate Unit</div>" ;
		print "</div>" ;
		
		//Proceed!
		if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
		$updateReturnMessage ="" ;
		$class="error" ;
		if (!($updateReturn=="")) {
			if ($updateReturn=="fail0") {
				$updateReturnMessage ="Your request failed because you do not have access to this action." ;	
			}
			else if ($updateReturn=="fail1") {
				$updateReturnMessage ="Your request failed because your inputs were invalid." ;	
			}
			else if ($updateReturn=="fail2") {
				$updateReturnMessage ="Your request failed due to a database error." ;	
			}
			else if ($updateReturn=="fail3") {
				$updateReturnMessage ="Your request failed because your inputs were invalid." ;	
			}
			else if ($updateReturn=="fail4") {
				$updateReturnMessage ="Your request failed because your inputs were invalid." ;	
			}
			else if ($updateReturn=="fail5") {
				$updateReturnMessage ="Your request failed due to an attachment error." ;	
			}
			else if ($updateReturn=="fail6") {
				$updateReturnMessage ="Update succeeded, although some lessons or resources could not be saved." ;	
			}
			else if ($updateReturn=="success0") {
				$updateReturnMessage ="Your request was completed successfully." ;	
				$class="success" ;
			}
			print "<div class='$class'>" ;
				print $updateReturnMessage;
			print "</div>" ;
		} 
		
		//Check if courseschool year specified
		$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"];
		$gibbonCourseID=$_GET["gibbonCourseID"]; 
		$gibbonUnitID=$_GET["gibbonUnitID"]; 
		if ($gibbonCourseID=="" OR $gibbonSchoolYearID=="") {
			print "<div class='error'>" ;
				print "You have not specified a course." ;
			print "</div>" ;
		}
		else {
			try {
				if ($highestAction=="Manage Units_all") {
					$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonCourseID"=>$gibbonCourseID); 
					$sql="SELECT * FROM gibbonCourse WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseID=:gibbonCourseID" ;
				}
				else if ($highestAction=="Manage Units_learningAreas") {
					$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonCourseID"=>$gibbonCourseID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
					$sql="SELECT gibbonCourseID, gibbonCourse.name, gibbonCourse.nameShort FROM gibbonCourse JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseID=:gibbonCourseID ORDER BY gibbonCourse.nameShort" ;
				}
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}

			if ($result->rowCount()!=1) {
				print "<div class='error'>" ;
					print "The specified course cannot be found or you do not have access to it." ;
				print "</div>" ;
			}
			else {
				$row=$result->fetch() ;
				$yearName=$row["name"] ;
				
				//Check if unit specified
				if ($gibbonUnitID=="") {
					print "<div class='error'>" ;
						print "You have not specified a unit." ;
					print "</div>" ;
				}
				else {
					if ($gibbonUnitID=="") {
						print "<div class='error'>" ;
							print "You have not specified a unit." ;
						print "</div>" ;
					}
					else {
						try {
							$data=array(); 
							$sql="SELECT gibbonCourse.nameShort AS courseName, gibbonSchoolYearID, gibbonUnit.* FROM gibbonUnit JOIN gibbonCourse ON (gibbonUnit.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonUnitID=$gibbonUnitID AND gibbonUnit.gibbonCourseID=$gibbonCourseID" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						
						if ($result->rowCount()!=1) {
							print "<div class='error'>" ;
								print "The specified unit cannot be found." ;
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
							
							//Step 1
							if ($step==1) {
								print "<h2>" ;
								print "Step 1" ;
								print "</h2>" ;
								?>
								<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/units_duplicate.php&step=2&gibbonUnitID=$gibbonUnitID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseID=$gibbonCourseID" ?>">
									<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
										<tr class='break'>
											<td colspan=2> 
												<h3>Source</h3>
											</td>
										</tr>
										<tr>
											<td> 
												<b>School Year *</b><br/>
												<span style="font-size: 90%"><i>This value cannot be changed.</i></span>
											</td>
											<td class="right">
												<?
												try {
													$dataYear=array("gibbonSchoolYearID"=>$row["gibbonSchoolYearID"]); 
													$sqlYear="SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID" ;
													$resultYear=$connection2->prepare($sqlYear);
													$resultYear->execute($dataYear);
												}
												catch(PDOException $e) { 
													print "<div class='error'>" . $e->getMessage() . "</div>" ; 
												}
												
												if ($resultYear->rowCount()!=1) {
													print "<i>Unknown</i>" ;
												}
												else {
													$rowYear=$resultYear->fetch() ;
													print "<input readonly value='" . $rowYear["name"] . "' type='text' style='width: 300px'>" ;
												}
												?>
											</td>
										</tr>
										<tr>
											<td> 
												<b>Course *</b><br/>
												<span style="font-size: 90%"><i>This value cannot be changed.</i></span>
											</td>
											<td class="right">
												<? print "<input readonly value='" . $row["courseName"] . "' type='text' style='width: 300px'>" ; ?>
											</td>
										</tr>
										<tr>
											<td> 
												<b>Unit *</b><br/>
												<span style="font-size: 90%"><i>This value cannot be changed.</i></span>
											</td>
											<td class="right">
												<? print "<input readonly value='" . $row["name"] . "' type='text' style='width: 300px'>" ; ?>
											</td>
										</tr>
										
										<tr class='break'>
											<td colspan=2> 
												<h3>Target</h3>
											</td>
										</tr>
												
										<tr>
											<td> 
												<b>Year*</b><br/>
											</td>
											<td class="right">
												<select name="gibbonSchoolYearIDCopyTo" id="gibbonSchoolYearIDCopyTo" style="width: 302px">
													<?
													print "<option value='Please select...'>Please select...</option>" ;
													try {
														$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
														$sqlSelect="SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID" ;
														$resultSelect=$connection2->prepare($sqlSelect);
														$resultSelect->execute($dataSelect);
													}
													catch(PDOException $e) { }
													if ($resultSelect->rowCount()==1) {
														$rowSelect=$resultSelect->fetch() ;
														try {
															$dataSelect2=array("sequenceNumber"=>$rowSelect["sequenceNumber"]); 
															$sqlSelect2="SELECT * FROM gibbonSchoolYear WHERE sequenceNumber>=:sequenceNumber ORDER BY sequenceNumber ASC" ;
															$resultSelect2=$connection2->prepare($sqlSelect2);
															$resultSelect2->execute($dataSelect2);
														}
														catch(PDOException $e) { }
														while ($rowSelect2=$resultSelect2->fetch()) {
															print "<option value='" . $rowSelect2["gibbonSchoolYearID"] . "'>" . htmlPrep($rowSelect2["name"]) . "</option>" ;
														}
													}		
													?>				
												</select>
												<script type="text/javascript">
													var gibbonSchoolYearIDCopyTo=new LiveValidation('gibbonSchoolYearIDCopyTo');
													gibbonSchoolYearIDCopyTo.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "Select something!"});
												 </script>
											</td>
										</tr>
										<tr>
											<td> 
												<b>Course *</b><br/>
											</td>
											<td class="right">
												<select name="gibbonCourseIDTarget" id="gibbonCourseIDTarget" style="width: 302px">
													<?
													try {
														if ($highestAction=="Manage Units_all") {
															$dataSelect=array(); 
															$sqlSelect="SELECT gibbonCourse.nameShort AS course, gibbonSchoolYear.name AS year, gibbonCourseID, gibbonSchoolYear.gibbonSchoolYearID FROM gibbonCourse JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) ORDER BY nameShort" ;
														}
														else if ($highestAction=="Manage Units_learningAreas") {
															$dataSelect=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
															$sqlSelect="SELECT gibbonCourse.nameShort AS course, gibbonSchoolYear.name AS year, gibbonCourseID, gibbonSchoolYear.gibbonSchoolYearID FROM gibbonCourse JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') ORDER BY gibbonCourse.nameShort" ;
														}
														$resultSelect=$connection2->prepare($sqlSelect);
														$resultSelect->execute($dataSelect);
													}
													catch(PDOException $e) { }
													while ($rowSelect=$resultSelect->fetch()) {
														print "<option class='" . $rowSelect["gibbonSchoolYearID"] . "' value='" . $rowSelect["gibbonCourseID"] . "'>" . htmlPrep($rowSelect["course"]) . "</option>" ;
													}
													
													
														
													?>				
												</select>
												<script type="text/javascript">
													$("#gibbonCourseIDTarget").chainedTo("#gibbonSchoolYearIDCopyTo");
												</script>
											</td>
										</tr>
										<tr>
											<td> 
												<b>Unit *</b><br/>
												<span style="font-size: 90%"><i>This value cannot be changed.</i></span>
											</td>
											<td class="right">
												<? print "<input readonly value='" . $row["name"] . "' type='text' style='width: 300px'>" ; ?>
											</td>
										</tr>
										
										<tr>
											<td>
												<span style="font-size: 90%"><i>* denotes a required field</i></span>
											</td>
											<td class="right">
												<input type="hidden" name="address" value="<? print $_SESSION[$guid]["address"] ?>">
												<input type="submit" value="Submit">
											</td>
										</tr>
									</table>
								</form>
								<?
							}
							else if ($step==2) {
								print "<h2>" ;
								print "Step 2" ;
								print "</h2>" ;
								
								$gibbonCourseIDTarget=$_POST["gibbonCourseIDTarget"] ;
								if ($gibbonCourseIDTarget=="") {
									print "<div class='error'>" ;
										print "The target course has not been specified." ;
									print "</div>" ;
								}
								else {
									if ($gibbonCourseIDTarget==$gibbonCourseID) {
										print "<div class='error'>" ;
											print "Source and target courses cannot be the same!" ;
										print "</div>" ;
									}
									else {
										?>
										<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/units_duplicateProcess.php?gibbonUnitID=$gibbonUnitID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseID=$gibbonCourseID&address=" . $_GET["q"] ?>">
											<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
												<script type="text/javascript">
													/* Resource 1 Option Control */
													$(document).ready(function(){
														$(".copyLessons").click(function(){
															if ($('input[name=copyLessons]:checked').val() == "Yes" ) {
																$("#sourceClass").slideDown("fast", $("#sourceClass").css("display","table-row")); 
																$("#targetClass").slideDown("fast", $("#targetClass").css("display","table-row")); 
															} else {
																$("#sourceClass").css("display","none");
																$("#targetClass").css("display","none");
															}
														 });
													});
												</script>
												<tr>
													<td> 
														<b>Copy Lessons? *</b><br/>
														<span style="font-size: 90%"><i>Also turns unit on for selected classes.</i></span>
													</td>
													<td class="right">
														<input checked type="radio" name="copyLessons" value="Yes" class="copyLessons" /> Yes
														<input type="radio" name="copyLessons" value="No" class="copyLessons" /> No
													</td>
												</tr>
												<tr class='break'>
													<td colspan=2> 
														<h3>Source</h3>
													</td>
												</tr>
												<tr>
													<td> 
														<b>School Year *</b><br/>
														<span style="font-size: 90%"><i>This value cannot be changed.</i></span>
													</td>
													<td class="right">
														<?
														try {
															$dataYear=array("gibbonSchoolYearID"=>$row["gibbonSchoolYearID"]); 
															$sqlYear="SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID" ;
															$resultYear=$connection2->prepare($sqlYear);
															$resultYear->execute($dataYear);
														}
														catch(PDOException $e) { 
															print "<div class='error'>" . $e->getMessage() . "</div>" ; 
														}
														if ($resultYear->rowCount()!=1) {
															print "<i>Unknown</i>" ;
														}
														else {
															$rowYear=$resultYear->fetch() ;
															print "<input readonly value='" . $rowYear["name"] . "' type='text' style='width: 300px'>" ;
														}
														?>
													</td>
												</tr>
												<tr>
													<td> 
														<b>Course *</b><br/>
														<span style="font-size: 90%"><i>This value cannot be changed.</i></span>
													</td>
													<td class="right">
														<? print "<input readonly value='" . $row["courseName"] . "' type='text' style='width: 300px'>" ; ?>
													</td>
												</tr>
												<tr>
													<td> 
														<b>Unit *</b><br/>
														<span style="font-size: 90%"><i>This value cannot be changed.</i></span>
													</td>
													<td class="right">
														<? print "<input readonly value='" . $row["name"] . "' type='text' style='width: 300px'>" ; ?>
													</td>
												</tr>
												<tr id="sourceClass">
													<td> 
														<b>Source Class *</b><br/>
													</td>
													<td class="right">
														<select name="gibbonCourseClassIDSource" id="gibbonCourseClassIDSource" style="width: 302px">
															<?
															print "<option value='Please select...'>Please select...</option>" ;
															try {
																$dataSelect=array("gibbonCourseID"=>$gibbonCourseID); 
																$sqlSelect="SELECT gibbonCourseClassID, gibbonCourseClass.nameShort AS class, gibbonCourse.nameShort AS course FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourseClass.gibbonCourseID=:gibbonCourseID" ;
																$resultSelect=$connection2->prepare($sqlSelect);
																$resultSelect->execute($dataSelect);
															}
															catch(PDOException $e) { }
															while ($rowSelect=$resultSelect->fetch()) {
																print "<option value='" . $rowSelect["gibbonCourseClassID"] . "'>" . htmlPrep($rowSelect["course"]) . "." . htmlPrep($rowSelect["class"]) . "</option>" ;
															}		
															?>				
														</select>
													</td>
												</tr>
												
												<?
												try {
													$dataSelect2=array("gibbonCourseID"=>$gibbonCourseIDTarget); 
													$sqlSelect2="SELECT gibbonCourse.name AS course, gibbonSchoolYear.name AS year FROM gibbonCourse JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonCourseID=:gibbonCourseID" ;
													$resultSelect2=$connection2->prepare($sqlSelect2);
													$resultSelect2->execute($dataSelect2);
												}
												catch(PDOException $e) { }
												if ($resultSelect2->rowCount()==1) {
													$rowSelect2=$resultSelect2->fetch() ;
													$access=TRUE ;
													$course=$rowSelect2["course"] ;
													$year=$rowSelect2["year"] ;
												}
												?>
												
												<tr class='break'>
													<td colspan=2> 
														<h3>Target</h3>
													</td>
												</tr>
												
												<tr>
													<td> 
														<b>School Year*</b><br/>
														<span style="font-size: 90%"><i>This value cannot be changed.</i></span>
													</td>
													<td class="right">
														<? print "<input readonly value='$year' type='text' style='width: 300px'>" ; ?>
													</td>
												</tr>
												<tr>
													<td> 
														<b>Course *</b><br/>
														<span style="font-size: 90%"><i>This value cannot be changed.</i></span>
													</td>
													<td class="right">
														<? print "<input readonly value='$course' type='text' style='width: 300px'>" ; ?>
													</td>
												</tr>
												<tr>
													<td> 
														<b>Unit *</b><br/>
														<span style="font-size: 90%"><i>This value cannot be changed.</i></span>
													</td>
													<td class="right">
														<? print "<input readonly value='" . $row["name"] . "' type='text' style='width: 300px'>" ; ?>
													</td>
												</tr>
												<tr id="targetClass">
													<td> 
														<b>Classes *</b><br/>
														<span style="font-size: 90%"><i>Use Control and/or Shift to select multiple.</i></span>
													</td>
													<td class="right">
														<select name="gibbonCourseClassIDTarget[]" id="gibbonCourseClassIDTarget[]" multiple style="width: 302px; height: 100px">
															<?
															try {
																$dataSelect=array("gibbonCourseIDTarget"=>$gibbonCourseIDTarget); 
																$sqlSelect="SELECT gibbonCourseClassID, gibbonCourseClass.nameShort AS class, gibbonCourse.nameShort AS course FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonCourseClass.gibbonCourseID=:gibbonCourseIDTarget" ;
																$resultSelect=$connection2->prepare($sqlSelect);
																$resultSelect->execute($dataSelect);
															}
															catch(PDOException $e) { }
															while ($rowSelect=$resultSelect->fetch()) {
																print "<option value='" . $rowSelect["gibbonCourseClassID"] . "'>" . htmlPrep($rowSelect["course"]) . "." . htmlPrep($rowSelect["class"]) . "</option>" ;
															}		
															?>				
														</select>
													</td>
												</tr>
												<tr>
													<td>
														<span style="font-size: 90%"><i>* denotes a required field</i></span>
													</td>
													<td class="right">
														<input type="hidden" name="gibbonCourseIDTarget" value="<? print $gibbonCourseIDTarget ?>">
														<input type="hidden" name="address" value="<? print $_SESSION[$guid]["address"] ?>">
														<input type="submit" value="Submit">
													</td>
												</tr>
											</table>
										</form>
										<?
									}
								}
							}
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