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

if (isActionAccessible($guid, $connection2, "/modules/Timetable Admin/courseEnrolment_manage_class_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Check if school year specified
	$gibbonCourseClassID=$_GET["gibbonCourseClassID"] ;
	$gibbonCourseID=$_GET["gibbonCourseID"] ;
	$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"] ;
	if ($gibbonCourseClassID=="" OR $gibbonCourseID=="" OR $gibbonSchoolYearID=="") {
		print "<div class='error'>" ;
			print __($guid, "You have not specified one or more required parameters.") ;
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
				print __($guid, "The specified record cannot be found.") ;
			print "</div>" ;
		}
		else {
			//Let's go!
			$row=$result->fetch() ;
			print "<div class='trail'>" ;
			print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/courseEnrolment_manage.php&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "'>" . __($guid, 'Enrolment by Class') . "</a> > </div><div class='trailEnd'>" . sprintf(__($guid, 'Edit %1$s.%2$s Enrolment'), $row["courseNameShort"], $row["name"]) . "</div>" ;
			print "</div>" ;

			if (isset($_GET["return"])) { returnProcess($guid, $_GET["return"], null, null); }
			
			print "<h2>" ;
			print __($guid, "Add Participants") ;
			print "</h2>" ;
			?>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/courseEnrolment_manage_class_edit_addProcess.php?gibbonCourseClassID=$gibbonCourseClassID&gibbonCourseID=$gibbonCourseID&gibbonSchoolYearID=$gibbonSchoolYearID" ?>">
				<table class='smallIntBorder fullWidth' cellspacing='0'>	
					<tr>
						<td style='width: 275px'> 
							<b><?php print __($guid, 'Participants') ?></b><br/>
							<span class="emphasis small"><?php print __($guid, 'Use Control, Command and/or Shift to select multiple.') ?></span>
						</td>
						<td class="right">
							<select name="Members[]" id="Members[]" multiple style="width: 302px; height: 150px">
								<optgroup label='--<?php print __($guid, 'Enrolable Students') ?>--'>
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
								</optgroup>
								<optgroup label='--<?php print __($guid, 'All Users') ?>--'>
								<?php
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT gibbonPersonID, surname, preferredName, status, username FROM gibbonPerson WHERE status='Full' OR status='Expected' ORDER BY surname, preferredName" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								while ($rowSelect=$resultSelect->fetch()) {
									$expected="" ;
									if ($rowSelect["status"]=="Expected") {
										$expected=" (Expected)" ;
									}
									print "<option value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . " (" . $rowSelect["username"] . ")" . $expected . "</option>" ;
								}
								?>
								</optgroup>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Role') ?> *</b><br/>
						</td>
						<td class="right">
							<select class="standardWidth" name="role">
								<option value="Student"><?php print __($guid, 'Student') ?></option>
								<option value="Teacher"><?php print __($guid, 'Teacher') ?></option>
								<option value="Assistant"><?php print __($guid, 'Assistant') ?></option>
								<option value="Technician"><?php print __($guid, 'Technician') ?></option>
								<option value="Parent"><?php print __($guid, 'Parent') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<span class="emphasis small">* <?php print __($guid, "denotes a required field") ; ?></span>
						</td>
						<td class="right">
							<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
							<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
						</td>
					</tr>
				</table>
			</form>

			<?php	
			print "<h2>" ;
			print __($guid, "Current Participants") ;
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
				print __($guid, "There are no records to display.") ;
				print "</div>" ;
			}
			else {
				print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/courseEnrolment_manage_class_editProcessBulk.php'>" ;
					print "<fieldset style='border: none'>" ;
					print "<div class='linkTop' style='height: 27px'>" ;
						?>
						<input style='margin-top: 0px; float: right' type='submit' value='<?php print __($guid, 'Go') ?>'>
						<select name="action" id="action" style='width:120px; float: right; margin-right: 1px;'>
							<option value="Select action"><?php print __($guid, 'Select action') ?></option>
							<option value="Mark as left"><?php print __($guid, 'Mark as left') ?></option>
							<option value="Delete"><?php print __($guid, 'Delete') ?></option>
						</select>
						<script type="text/javascript">
							var action=new LiveValidation('action');
							action.add(Validate.Exclusion, { within: ['<?php print __($guid, 'Select action') ?>'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
						</script>
						<?php
					print "</div>" ;
					
					print "<table cellspacing='0' style='width: 100%'>" ;
						print "<tr class='head'>" ;
							print "<th>" ;
								print __($guid, "Name") ;
							print "</th>" ;
							print "<th>" ;
								print __($guid, "Email") ;
							print "</th>" ;
							print "<th>" ;
								print __($guid, "Role") ;
							print "</th>" ;
							print "<th>" ;
								print __($guid, "Reportable") ;
							print "</th>" ;
							print "<th>" ;
								print __($guid, "Actions") ;
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
									print $row["reportable"] ;
								print "</td>" ;
								print "<td>" ;
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/courseEnrolment_manage_class_edit_edit.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonCourseID=$gibbonCourseID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonPersonID=" . $row["gibbonPersonID"] . "'><img title='" . __($guid, 'Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/courseEnrolment_manage_class_edit_delete.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonCourseID=$gibbonCourseID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonPersonID=" . $row["gibbonPersonID"] . "'><img title='" . __($guid, 'Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a>" ;
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
			print __($guid, "Former Participants") ;
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
				print __($guid, "There are no records to display.") ;
				print "</div>" ;
			}
			else {
				print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/courseEnrolment_manage_class_editProcessBulk.php'>" ;
					print "<table cellspacing='0' style='width: 100%'>" ;
						print "<tr class='head'>" ;
							print "<th>" ;
								print __($guid, "Name") ;
							print "</th>" ;
							print "<th>" ;
								print __($guid, "Email") ;
							print "</th>" ;
							print "<th>" ;
								print __($guid, "Class Role") ;
							print "</th>" ;
							print "<th>" ;
								print __($guid, "Actions") ;
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
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/courseEnrolment_manage_class_edit_edit.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonCourseID=$gibbonCourseID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonPersonID=" . $row["gibbonPersonID"] . "'><img title='" . __($guid, 'Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/courseEnrolment_manage_class_edit_delete.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonCourseID=$gibbonCourseID&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonPersonID=" . $row["gibbonPersonID"] . "'><img title='" . __($guid, 'Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a>" ;
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