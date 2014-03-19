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

if (isActionAccessible($guid, $connection2, "/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	//Check if school year specified
	$gibbonPersonID=$_GET["gibbonPersonID"] ;
	$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"] ;
	$type=$_GET["type"] ;
	$allUsers=$_GET["allUsers"] ;
	$search="" ;
	if (isset($_GET["search"])) {
		$search=$_GET["search"] ;
	}
	
	if ($gibbonPersonID=="" OR $gibbonSchoolYearID=="") {
		print "<div class='error'>" ;
			print _("You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			if ($allUsers=="on") {
				$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$gibbonPersonID); 
				$sql="SELECT gibbonPerson.gibbonPersonID, surname, preferredName, title, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, NULL AS type FROM gibbonPerson LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID) LEFT JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) LEFT JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) WHERE gibbonPerson.status='Full' AND gibbonPerson.gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName" ; 
			}
			else {
				if ($type=="Student") {
					$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonPersonID"=>$gibbonPersonID); 
					$sql="(SELECT gibbonPerson.gibbonPersonID, gibbonStudentEnrolmentID, surname, preferredName, title, gibbonYearGroup.gibbonYearGroupID, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, 'Student' AS type FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' AND gibbonPerson.gibbonPersonID=:gibbonPersonID)" ; 
				}
				else if ($type=="Staff") {
					$data=array("gibbonPersonID"=>$gibbonPersonID); 
					$sql="(SELECT gibbonPerson.gibbonPersonID, NULL AS gibbonStudentEnrolmentID, surname, preferredName, title, NULL AS gibbonYearGroupID, NULL AS yearGroup, NULL AS rollGroup, 'Staff' as type FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) WHERE type='Teaching' AND gibbonPerson.status='Full' AND gibbonPerson.gibbonPersonID=:gibbonPersonID) ORDER BY surname, preferredName" ;
				}
			}$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print _("The specified record cannot be found.") ;
			print "</div>" ;
		}
		else {
			//Let's go!
			$row=$result->fetch() ;
			print "<div class='trail'>" ;
			print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/courseEnrolment_manage_byPerson.php&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "&allUsers=$allUsers'>Enrolment by Person</a> > </div><div class='trailEnd'>" . $row["preferredName"] . " " . $row["surname"] . "</div>" ;
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
					$updateReturnMessage=_("Your request was successful, but some data was not properly saved. You may have tried to mark as left people who are not students or teachers in this class.") ;	
				}
				else if ($updateReturn=="success0") {
					$updateReturnMessage=_("Your request was completed successfully.") ;	
					$class="success" ;
				}
				print "<div class='$class'>" ;
					print $updateReturnMessage;
				print "</div>" ;
			} 
			
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
	
			print "<div class='linkTop'>" ;
				if ($search!="") {
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Timetable Admin/courseEnrolment_manage_byPerson.php&allUsers=$allUsers&search=$search&gibbonSchoolYearID=$gibbonSchoolYearID'>Back to Search Results</a> | " ;
				}
				print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Timetable/tt_view.php&gibbonPersonID=$gibbonPersonID&allUsers=$allUsers'>View Timetable<img style='margin: 0 0 -4px 3px' title='Enter Data' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/planner.gif'/></a> " ;
			print "</div>" ;
			
			print "<h2>" ;
			print "Add Classes" ;
			print "</h2>" ;
			print "<p>" ;
				print "The user will be added to the specified classes. You can use the table below to change users to other roles." ;
			print "</p>" ;
			?>
			<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/courseEnrolment_manage_byPerson_edit_addProcess.php?type=$type&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonPersonID=$gibbonPersonID&allUsers=$allUsers&search=$search" ?>">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr>
						<td> 
							<b>Classes</b><br/>
							<span style="font-size: 90%"><i>Use Control and/or Shift to select multiple.</i></span>
						</td>
						<td class="right">
							<select name="Members[]" id="Members[]" multiple style="width: 302px; height: 150px">
								<?
								if ($row["type"]=="Student" ) {
								?>
									<optgroup label='--Enrolable Classes--'>
									<?
									try {
										$dataSelect=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonYearGroupIDList"=>"%" . $row["gibbonYearGroupID"] . "%"); 
										$sqlSelect="SELECT gibbonCourseClassID, gibbonCourse.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonYearGroupIDList LIKE :gibbonYearGroupIDList ORDER BY course, class" ;
										$resultSelect=$connection2->prepare($sqlSelect);
										$resultSelect->execute($dataSelect);
									}
									catch(PDOException $e) { }
									while ($rowSelect=$resultSelect->fetch()) {
										try {
											$dataSelect2=array("gibbonCourseClassID"=>$rowSelect["gibbonCourseClassID"]); 
											$sqlSelect2="SELECT surname, preferredName, title FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' AND role='Teacher' AND gibbonCourseClassID=:gibbonCourseClassID" ;
											$resultSelect2=$connection2->prepare($sqlSelect2);
											$resultSelect2->execute($dataSelect2);
										}
										catch(PDOException $e) { }
										$teachers="" ;
										while ($rowSelect2=$resultSelect2->fetch()) {
											$teachers.=formatName("",$rowSelect2["preferredName"], $rowSelect2["surname"], "Staff", false) . ", " ;
										}
										print "<option value='" . $rowSelect["gibbonCourseClassID"] . "'>" . htmlPrep($rowSelect["course"]) . "." . htmlPrep($rowSelect["class"]) ;
										if ($teachers!="") {
											print " - " . substr($teachers,0,-2) . "" ;
										
										}
										print "</option>" ;
									}
									?>
									</optgroup>
								<?
								}
								else {
									?>
									<optgroup label='--All Classes--'>
									<?
									try {
										$dataSelect=array("gibbonSchoolYearID"=>$gibbonSchoolYearID); 
										$sqlSelect="SELECT gibbonCourseClassID, gibbonCourse.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY course, class" ;
										$resultSelect=$connection2->prepare($sqlSelect);
										$resultSelect->execute($dataSelect);
									}
									catch(PDOException $e) { }
									while ($rowSelect=$resultSelect->fetch()) {
										print "<option value='" . $rowSelect["gibbonCourseClassID"] . "'>" . htmlPrep($rowSelect["course"]) . "." . htmlPrep($rowSelect["class"]) . " - " . $rowSelect["name"] . "</option>" ;
									}
									?>
									</optgroup>
									<?
								}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Role *</b><br/>
							<span style="font-size: 90%"><i>Must be unique for this course.</i></span>
						</td>
						<td class="right">
							<select style="width: 302px" name="role">
								<option <? if ($type=="Student") { print "selected ";} ?>value="Student">Student</option>
								<option <? if ($type=="Staff") { print "selected ";} ?>value="Teacher">Teacher</option>
								<option value="Assistant">Assistant</option>
								<option value="Technician">Technician</option>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<span style="font-size: 90%"><i>* <? print _("denotes a required field") ; ?></i></span>
						</td>
						<td class="right">
							<input type="hidden" name="address" value="<? print $_SESSION[$guid]["address"] ?>">
							<input type="submit" value="<? print _("Submit") ; ?>">
						</td>
					</tr>
				</table>
			</form>

			<?	
			print "<h2>" ;
			print "Current Enrolment" ;
			print "</h2>" ;
			
			try {
				$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonPersonID"=>$gibbonPersonID); 
				$sql="SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, role FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND NOT role LIKE '%left' ORDER BY course, class" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
		
			if ($result->rowCount()<1) {
				print "<div class='error'>" ;
				print _("There are no records to display.") ;
				print "</div>" ;
			}
			else {
				print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/courseEnrolment_manage_byPerson_editProcessBulk.php?allUsers=$allUsers'>" ;
					print "<fieldset style='border: none'>" ;
					print "<div class='linkTop' style='height: 27px'>" ;
						?>
						<input style='margin-top: 0px; float: right' type='submit' value='Go'>
						<select name="action" id="action" style='width:120px; float: right; margin-right: 1px;'>
							<option value="Select action">Select action</option>
							<option value="Mark as left">Mark as left</option>
							<option value="Delete">Delete</option>
						</select>
						<script type="text/javascript">
							var action=new LiveValidation('action');
							action.add(Validate.Exclusion, { within: ['Select action'], failureMessage: "Select something!"});
						</script>
						<?
					print "</div>" ;
					
					print "<table cellspacing='0' style='width: 100%'>" ;
						print "<tr class='head'>" ;
							print "<th>" ;
								print "Class Coude" ;
							print "</th>" ;
							print "<th>" ;
								print "Course" ;
							print "</th>" ;
							print "<th>" ;
								print "Class Role" ;
							print "</th>" ;
							print "<th>" ;
								print "Actions" ;
							print "</th>" ;
							print "<th>" ;
								?>
								<script type="text/javascript">
									$(function () { // this line makes sure this code runs on page load
										$('.checkall').click(function () {
											$(this).parents('fieldset:eq(0)').find(':checkbox').attr('checked', this.checked);
										});
									});
								</script>
								<?
								print "<input type='checkbox' class='checkall'>" ;
							print "</th>" ;
						print "</tr>" ;
						
						$count=0;
						$rowNum="odd" ;
						while ($row=$result->fetch()) {
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
									print $row["course"] . "." . $row["class"] ;
								print "</td>" ;
								print "<td>" ;
									print $row["name"] ;
								print "</td>" ;
								print "<td>" ;
									print $row["role"] ;
								print "</td>" ;
								print "<td>" ;
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/courseEnrolment_manage_byPerson_edit_edit.php&gibbonCourseClassID=" . $row["gibbonCourseClassID"] . "&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonPersonID=$gibbonPersonID&type=$type&allUsers=$allUsers&search=$search'><img title='" . _('Edit Record') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/courseEnrolment_manage_byPerson_edit_delete.php&gibbonCourseClassID=" . $row["gibbonCourseClassID"] . "&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonPersonID=$gibbonPersonID&type=$type&allUsers=$allUsers&search=$search'><img title='" . _('Delete Record') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a>" ;
								print "</td>" ;
								print "<td>" ;
									print "<input name='gibbonCourseClassID-$count' value='" . $row["gibbonCourseClassID"] . "' type='hidden'>" ;
									print "<input name='role-$count' value='" . $row["role"] . "' type='hidden'>" ;
									print "<input type='checkbox' name='check-$count' id='check-$count'>" ;
								print "</td>" ;
							print "</tr>" ;
						}
					print "</table>" ;
					
					print "<input name='count' value='$count' type='hidden'>" ;
					print "<input name='type' value='$type' type='hidden'>" ;
					print "<input name='gibbonPersonID' value='$gibbonPersonID' type='hidden'>" ;
					print "<input name='gibbonSchoolYearID' value='$gibbonSchoolYearID' type='hidden'>" ;	
					print "<input name='address' value='" . $_GET["q"] . "' type='hidden'>" ;	
					print "</fieldset>" ;
				print "</form>" ;
			}
			
			print "<h2>" ;
			print "Old Enrolment" ;
			print "</h2>" ;
			
			try {
				$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonPersonID"=>$gibbonPersonID); 
				$sql="SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourse.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, role FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND role LIKE '%left' ORDER BY course, class" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			
			if ($result->rowCount()<1) {
				print "<div class='error'>" ;
				print _("There are no records to display.") ;
				print "</div>" ;
			}
			else {
				print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/courseEnrolment_manage_byPerson_editProcessBulk.php'>" ;
					print "<table cellspacing='0' style='width: 100%'>" ;
						print "<tr class='head'>" ;
							print "<th>" ;
								print "Class Coude" ;
							print "</th>" ;
							print "<th>" ;
								print "Course" ;
							print "</th>" ;
							print "<th>" ;
								print "Class Role" ;
							print "</th>" ;
							print "<th>" ;
								print "Actions" ;
							print "</th>" ;
						print "</tr>" ;
						
						$count=0;
						$rowNum="odd" ;
						while ($row=$result->fetch()) {
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
									print $row["course"] . "." . $row["class"] ;
								print "</td>" ;
								print "<td>" ;
									print $row["name"] ;
								print "</td>" ;
								print "<td>" ;
									print $row["role"] ;
								print "</td>" ;
								print "<td>" ;
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/courseEnrolment_manage_byPerson_edit_edit.php&gibbonCourseClassID=" . $row["gibbonCourseClassID"] . "&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonPersonID=$gibbonPersonID&type=$type&allUsers=$allUsers&search=$search'><img title='" . _('Edit Record') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/courseEnrolment_manage_byPerson_edit_delete.php&gibbonCourseClassID=" . $row["gibbonCourseClassID"] . "&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonPersonID=$gibbonPersonID&type=$type&allUsers=$allUsers&search=$search'><img title='" . _('Delete Record') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a>" ;
								print "</td>" ;
							print "</tr>" ;
						}
					print "</table>" ;
					
					print "<input name='count' value='$count' type='hidden'>" ;
					print "<input name='type' value='$type' type='hidden'>" ;
					print "<input name='gibbonPersonID' value='$gibbonPersonID' type='hidden'>" ;
					print "<input name='gibbonSchoolYearID' value='$gibbonSchoolYearID' type='hidden'>" ;	
					print "<input name='address' value='" . $_GET["q"] . "' type='hidden'>" ;	
				print "</form>" ;
			}
		}
	}
}
?>