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

if (isActionAccessible($guid, $connection2, "/modules/Timetable Admin/courseEnrolment_manage_class_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Check if school year specified
	$gibbonCourseClassID=$_GET["gibbonCourseClassID"] ;
	$gibbonCourseID=$_GET["gibbonCourseID"] ;
	$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"] ;
	if ($gibbonCourseClassID=="" OR $gibbonCourseID=="" OR $gibbonSchoolYearID=="") {
		print "<div class='error'>" ;
			print _("You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonCourseID"=>$gibbonCourseID, "gibbonCourseClassID"=>$gibbonCourseClassID); 
			$sql="SELECT gibbonCourseClassID, gibbonCourseClass.name, gibbonCourseClass.nameShort, gibbonCourse.gibbonCourseID, gibbonCourse.name AS courseName, gibbonCourse.nameShort as courseNameShort, gibbonCourse.description AS courseDescription, gibbonCourse.gibbonSchoolYearID, gibbonSchoolYear.name as yearName, gibbonYearGroupIDList FROM gibbonCourseClass, gibbonCourse, gibbonSchoolYear WHERE gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID AND gibbonCourse.gibbonCourseID=:gibbonCourseID AND gibbonCourseClassID=:gibbonCourseClassID" ;
			$result=$connection2->prepare($sql);
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
			print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/courseEnrolment_manage.php&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "'>Enrolment by Class</a> > </div><div class='trailEnd'>Edit " . $row["courseNameShort"] . "." . $row["name"] . " Enrolment</div>" ;
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
	
			print "<h2>" ;
			print "Add Participants" ;
			print "</h2>" ;
			print "<p>" ;
				print "Selected users will be added as students: you can use the table below to change users to other roles." ;
			print "</p>" ;
			?>
			<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/courseEnrolment_manage_class_edit_addProcess.php?gibbonCourseClassID=$gibbonCourseClassID&gibbonCourseID=$gibbonCourseID&gibbonSchoolYearID=$gibbonSchoolYearID" ?>">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr>
						<td> 
							<b>Participants</b><br/>
							<span style="font-size: 90%"><i>Use Control and/or Shift to select multiple.</i></span>
						</td>
						<td class="right">
							<select name="Members[]" id="Members[]" multiple style="width: 302px; height: 150px">
								<optgroup label='--Enrolable Students--'>
								<?
								try {
									$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
									$sqlSelectWhere="" ;
									if ($row["gibbonYearGroupIDList"]!="") {
										$years=explode(",", $row["gibbonYearGroupIDList"]);
										for ($i=0; $i<count($years); $i++) {
											if ($i==0) {
												$dataSelect[$years[$i]]=$years[$i] ;
												$sqlSelectWhere=$sqlSelectWhere . "AND (gibbonYearGroupID=:" . $years[$i] ;
											}
											else {
												$dataSelect[$years[$i]]=$years[$i] ;
												$sqlSelectWhere=$sqlSelectWhere . " OR gibbonYearGroupID=:" . $years[$i] ;
											}
											
											if ($i==(count($years)-1)) {
												$sqlSelectWhere=$sqlSelectWhere . ")" ;
											}
										}
									}
									else {
										$sqlSelectWhere=" FALSE" ;
									}
									$sqlSelect="SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS name FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID AND status='FULL' AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID $sqlSelectWhere ORDER BY name, surname, preferredName" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								while ($rowSelect=$resultSelect->fetch()) {
									print "<option value='" . $rowSelect["gibbonPersonID"] . "'>" . htmlPrep($rowSelect["name"]) . " - " . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . "</option>" ;
								}
								?>
								</optgroup>
								<optgroup label='--All Users--'>
								<?
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT gibbonPersonID, surname, preferredName, status FROM gibbonPerson WHERE status='Full' OR status='Expected' ORDER BY surname, preferredName" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								while ($rowSelect=$resultSelect->fetch()) {
									$expected="" ;
									if ($rowSelect["status"]=="Expected") {
										$expected=" (Expected)" ;
									}
									print "<option value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . "$expected</option>" ;
								}
								?>
								</optgroup>
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
								<option value="Student">Student</option>
								<option value="Teacher">Teacher</option>
								<option value="Assistant">Assistant</option>
								<option value="Technician">Technician</option>
								<option value="Parent">Parent</option>
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
			print "Current Participants" ;
			print "</h2>" ;
			
			try {
				$data=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
				$sql="SELECT * FROM gibbonPerson, gibbonCourseClassPerson WHERE (gibbonPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) AND gibbonCourseClassID=:gibbonCourseClassID AND (status='Full' OR status='Expected') AND NOT role LIKE '%left' ORDER BY role DESC, surname, preferredName" ; 
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
				print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/courseEnrolment_manage_class_editProcessBulk.php'>" ;
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
								print "Name" ;
							print "</th>" ;
							print "<th>" ;
								print "Email" ;
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
									print formatName("", htmlPrep($row["preferredName"]), htmlPrep($row["surname"]), "Student", true) ;
								print "</td>" ;
								print "<td>" ;
									print $row["email"] ;
								print "</td>" ;
								print "<td>" ;
									print $row["role"] ;
								print "</td>" ;
								print "<td>" ;
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/courseEnrolment_manage_class_edit_edit.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonCourseID=$gibbonCourseID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonPersonID=" . $row["gibbonPersonID"] . "'><img title='" . _('Edit Record') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/courseEnrolment_manage_class_edit_delete.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonCourseID=$gibbonCourseID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonPersonID=" . $row["gibbonPersonID"] . "'><img title='" . _('Delete Record') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a>" ;
								print "</td>" ;
								print "<td>" ;
									print "<input name='gibbonPersonID-$count' value='" . $row["gibbonPersonID"] . "' type='hidden'>" ;
									print "<input name='role-$count' value='" . $row["role"] . "' type='hidden'>" ;
									print "<input type='checkbox' name='check-$count' id='check-$count'>" ;
								print "</td>" ;
							print "</tr>" ;
						}
					print "</table>" ;
					
					print "<input name='count' value='$count' type='hidden'>" ;
					print "<input name='gibbonCourseClassID' value='$gibbonCourseClassID' type='hidden'>" ;
					print "<input name='gibbonCourseID' value='$gibbonCourseID' type='hidden'>" ;
					print "<input name='gibbonSchoolYearID' value='$gibbonSchoolYearID' type='hidden'>" ;	
					print "<input name='address' value='" . $_GET["q"] . "' type='hidden'>" ;	
					print "</fieldset>" ;
				print "</form>" ;
			}
			
			print "<h2>" ;
			print "Former Participants" ;
			print "</h2>" ;
			
			try {
				$data=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
				$sql="SELECT * FROM gibbonPerson, gibbonCourseClassPerson WHERE (gibbonPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) AND gibbonCourseClassID=:gibbonCourseClassID AND (status='Full' OR status='Expected') AND role LIKE '%left' ORDER BY role DESC, surname, preferredName" ; 
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
				print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/courseEnrolment_manage_class_editProcessBulk.php'>" ;
					print "<table cellspacing='0' style='width: 100%'>" ;
						print "<tr class='head'>" ;
							print "<th>" ;
								print "Name" ;
							print "</th>" ;
							print "<th>" ;
								print "Email" ;
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
									print formatName("", htmlPrep($row["preferredName"]), htmlPrep($row["surname"]), "Student", true) ;
								print "</td>" ;
								print "<td>" ;
									print $row["email"] ;
								print "</td>" ;
								print "<td>" ;
									print $row["role"] ;
								print "</td>" ;
								print "<td>" ;
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/courseEnrolment_manage_class_edit_edit.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonCourseID=$gibbonCourseID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonPersonID=" . $row["gibbonPersonID"] . "'><img title='" . _('Edit Record') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/courseEnrolment_manage_class_edit_delete.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonCourseID=$gibbonCourseID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonPersonID=" . $row["gibbonPersonID"] . "'><img title='" . _('Delete Record') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a>" ;
								print "</td>" ;
							print "</tr>" ;
						}
					print "</table>" ;
				print "</form>" ;
			}
		}
	}
}
?>