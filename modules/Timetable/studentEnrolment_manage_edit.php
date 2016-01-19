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

if (isActionAccessible($guid, $connection2, "/modules/Timetable/studentEnrolment_manage_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Check if school year specified
	$gibbonCourseClassID=$_GET["gibbonCourseClassID"] ;
	$gibbonCourseID=$_GET["gibbonCourseID"] ;
	if ($gibbonCourseClassID=="" OR $gibbonCourseID=="") {
		print "<div class='error'>" ;
			print _("You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "gibbonCourseClassID"=>$gibbonCourseClassID); 
			$sql="SELECT gibbonCourseClassID, gibbonCourseClass.name, gibbonCourseClass.nameShort, gibbonCourse.gibbonCourseID, gibbonCourse.name AS courseName, gibbonCourse.nameShort as courseNameShort, gibbonCourse.description AS courseDescription, gibbonCourse.gibbonSchoolYearID, gibbonSchoolYear.name as yearName, gibbonYearGroupIDList FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonSchoolYear ON (gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE (role='Coordinator' OR role='Assistant Coordinator') AND gibbonPersonID=:gibbonPersonID AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassID=:gibbonCourseClassID" ; 	
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
			print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/studentEnrolment_manage.php'>" . _('Manage Student Enrolment') . "</a> > </div><div class='trailEnd'>" . sprintf(_('Edit %1$s.%2$s Enrolment'), $row["courseNameShort"], $row["name"]) . "</div>" ;
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
			
			print "<h2>" ;
			print _("Add Participants") ;
			print "</h2>" ;
			?>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/studentEnrolment_manage_edit_addProcess.php?gibbonCourseClassID=$gibbonCourseClassID&gibbonCourseID=$gibbonCourseID" ?>">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr>
						<td style='width: 275px'> 
							<b><?php print _('Enrolable Students') ?></b><br/>
							<span style="font-size: 90%"><i><?php print _('Use Control, Command and/or Shift to select multiple.') ?></i></span>
						</td>
						<td class="right">
							<select name="Members[]" id="Members[]" multiple style="width: 302px; height: 150px">
								<?php
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
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Role') ?> *</b><br/>
						</td>
						<td class="right">
							<select style="width: 302px" name="role">
								<option value="Student"><?php print _('Student') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<span style="font-size: 90%"><i>* <?php print _("denotes a required field") ; ?></i></span>
						</td>
						<td class="right">
							<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
							<input type="submit" value="<?php print _("Submit") ; ?>">
						</td>
					</tr>
				</table>
			</form>

			<?php	
			print "<h2>" ;
			print _("Current Participants") ;
			print "</h2>" ;
			
			try {
				$data=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
				$sql="SELECT * FROM gibbonPerson, gibbonCourseClassPerson WHERE (gibbonPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) AND gibbonCourseClassID=:gibbonCourseClassID AND (status='Full' OR status='Expected') AND (role='Student' OR role='Teacher') ORDER BY role DESC, surname, preferredName" ; 
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
				print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/studentEnrolment_manage_editProcessBulk.php'>" ;
					print "<fieldset style='border: none'>" ;
					print "<div class='linkTop' style='height: 27px'>" ;
						?>
						<input style='margin-top: 0px; float: right' type='submit' value='<?php print _('Go') ?>'>
						<select name="action" id="action" style='width:120px; float: right; margin-right: 1px;'>
							<option value="Select action"><?php print _('Select action') ?></option>
							<option value="Mark as left"><?php print _('Mark as left') ?></option>
						</select>
						<script type="text/javascript">
							var action=new LiveValidation('action');
							action.add(Validate.Exclusion, { within: ['<?php print _('Select action') ?>'], failureMessage: "<?php print _('Select something!') ?>"});
						</script>
						<?php
					print "</div>" ;
					
					print "<table cellspacing='0' style='width: 100%'>" ;
						print "<tr class='head'>" ;
							print "<th>" ;
								print _("Name") ;
							print "</th>" ;
							print "<th>" ;
								print _("Email") ;
							print "</th>" ;
							print "<th>" ;
								print _("Role") ;
							print "</th>" ;
							print "<th>" ;
								print _("Actions") ;
							print "</th>" ;
							print "<th>" ;
								?>
								<script type="text/javascript">
									$(function () {
										$('.checkall').click(function () {
											$(this).parents('fieldset:eq(0)').find(':checkbox').attr('checked', this.checked);
										});
									});
								</script>
								<?php
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
									if ($row["role"]=="Student") {
										print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $row["gibbonPersonID"] . "&subpage=Timetable'>" . formatName("", htmlPrep($row["preferredName"]), htmlPrep($row["surname"]), "Student", true) . "</a>" ;
									}
									else {
										print formatName("", htmlPrep($row["preferredName"]), htmlPrep($row["surname"]), "Student", true) ;
									}
								print "</td>" ;
								print "<td>" ;
									print $row["email"] ;
								print "</td>" ;
								print "<td>" ;
									print $row["role"] ;
								print "</td>" ;
								print "<td>" ;
									if ($row["role"]=="Student") {
										print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/studentEnrolment_manage_edit_edit.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonCourseID=$gibbonCourseID&gibbonPersonID=" . $row["gibbonPersonID"] . "'><img title='" . _('Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
									}
								print "</td>" ;
								print "<td>" ;
									if ($row["role"]=="Student") {
										print "<input name='gibbonPersonID-$count' value='" . $row["gibbonPersonID"] . "' type='hidden'>" ;
										print "<input name='role-$count' value='" . $row["role"] . "' type='hidden'>" ;
										print "<input type='checkbox' name='check-$count' id='check-$count'>" ;
									}
								print "</td>" ;
							print "</tr>" ;
						}
					print "</table>" ;
					
					print "<input name='count' value='$count' type='hidden'>" ;
					print "<input name='gibbonCourseClassID' value='$gibbonCourseClassID' type='hidden'>" ;
					print "<input name='gibbonCourseID' value='$gibbonCourseID' type='hidden'>" ;
					print "<input name='address' value='" . $_GET["q"] . "' type='hidden'>" ;	
					print "</fieldset>" ;
				print "</form>" ;
			}
			
			print "<h2>" ;
			print _("Former Students") ;
			print "</h2>" ;
			
			try {
				$data=array("gibbonCourseClassID"=>$gibbonCourseClassID); 
				$sql="SELECT * FROM gibbonPerson, gibbonCourseClassPerson WHERE (gibbonPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) AND gibbonCourseClassID=:gibbonCourseClassID AND (status='Full' OR status='Expected') AND role='Student - Left' ORDER BY role DESC, surname, preferredName" ; 
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
				print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/studentEnrolment_manage_editProcessBulk.php'>" ;
					print "<table cellspacing='0' style='width: 100%'>" ;
						print "<tr class='head'>" ;
							print "<th>" ;
								print _("Name") ;
							print "</th>" ;
							print "<th>" ;
								print _("Email") ;
							print "</th>" ;
							print "<th>" ;
								print _("Class Role") ;
							print "</th>" ;
							print "<th>" ;
								print _("Actions") ;
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
									if ($row["role"]=="Student - Left") {
										print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $row["gibbonPersonID"] . "&subpage=Timetable'>" . formatName("", htmlPrep($row["preferredName"]), htmlPrep($row["surname"]), "Student", true) . "</a>" ;
									}
									else {
										print formatName("", htmlPrep($row["preferredName"]), htmlPrep($row["surname"]), "Student", true) ;
									}
								print "</td>" ;
								print "<td>" ;
									print $row["email"] ;
								print "</td>" ;
								print "<td>" ;
									print $row["role"] ;
								print "</td>" ;
								print "<td>" ;
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/studentEnrolment_manage_edit_edit.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonCourseID=$gibbonCourseID&gibbonPersonID=" . $row["gibbonPersonID"] . "'><img title='" . _('Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
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