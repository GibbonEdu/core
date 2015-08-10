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

if (isActionAccessible($guid, $connection2, "/modules/Timetable Admin/course_manage_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/course_manage.php&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "'>" . _('Manage Courses & Classes') . "</a> > </div><div class='trailEnd'>" . _('Edit Course & Classes') . "</div>" ;
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
			$updateReturnMessage=_("Your request failed because some inputs did not meet a requirement for uniqueness.") ;	
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
	
	//Check if school year specified
	$gibbonCourseID=$_GET["gibbonCourseID"] ;
	if ($gibbonCourseID=="") {
		print "<div class='error'>" ;
			print _("You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonCourseID"=>$gibbonCourseID); 
			$sql="SELECT gibbonCourseID, gibbonDepartmentID, gibbonCourse.name AS name, gibbonCourse.nameShort as nameShort, orderBy, gibbonCourse.description, gibbonCourse.gibbonSchoolYearID, gibbonSchoolYear.name as yearName, gibbonYearGroupIDList FROM gibbonCourse, gibbonSchoolYear WHERE gibbonCourse.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID AND gibbonCourseID=:gibbonCourseID" ;
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
			?>				
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/course_manage_editProcess.php?gibbonCourseID=" . $row["gibbonCourseID"] ?>">
			<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
				<tr>
					<td style='width: 275px'> 
						<b><?php print _('School Year') ?> *</b><br/>
						<span style="font-size: 90%"><i><?php print _('This value cannot be changed.') ?></i></span>
					</td>
					<td class="right">
						<input readonly name="gibbonSchoolYearID" id="gibbonSchoolYearID" maxlength=20 value="<?php print $row["yearName"] ?>" type="text" style="width: 300px">
						<script type="text/javascript">
							var schoolYearName=new LiveValidation('schoolYearName');
							schoolYearname2.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print _('Learning Area') ?></b><br/>
					</td>
					<td class="right">
						<select style="width: 302px" name="gibbonDepartmentID">
							<?php
							print "<option value=''></option>" ;
							try {
								$dataSelect=array(); 
								$sqlSelect="SELECT * FROM gibbonDepartment WHERE type='Learning Area' ORDER BY name" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) { }
							while ($rowSelect=$resultSelect->fetch()) {
								if ($row["gibbonDepartmentID"]==$rowSelect["gibbonDepartmentID"]) {
									print "<option selected value='" . $rowSelect["gibbonDepartmentID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
								}
								else {
									print "<option value='" . $rowSelect["gibbonDepartmentID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
								}
							}
							?>				
						</select>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print _('Name') ?> *</b><br/>
						<span style="font-size: 90%"><i><?php print _('Must be unique for this school year.') ?></i></span>
					</td>
					<td class="right">
						<input name="name" id="name" maxlength=45 value="<?php print htmlPrep($row["name"]) ?>" type="text" style="width: 300px">
						<script type="text/javascript">
							var name2=new LiveValidation('name');
							name2.add(Validate.Presence);
						 </script>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print _('Short Name') ?> *</b><br/>
						<span style="font-size: 90%"><i></i></span>
					</td>
					<td class="right">
						<input name="nameShort" id="nameShort" maxlength=6 value="<?php print htmlPrep($row["nameShort"]) ?>" type="text" style="width: 300px">
						<script type="text/javascript">
							var nameShort=new LiveValidation('nameShort');
							nameShort.add(Validate.Presence);
						 </script>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print _('Order') ?></b><br/>
						<span style="font-size: 90%"><i><?php print _('May be used to adjust arrangement of courses in reports.') ?></i></span>
					</td>
					<td class="right">
						<input name="orderBy" id="orderBy" maxlength=6 value="<?php print $row["orderBy"] ; ?>" type="text" style="width: 300px">
						<script type="text/javascript">
							var orderBy=new LiveValidation('orderBy');
							orderBy.add(Validate.Numericality);
						</script>
					</td>
				</tr>
				<tr>
					<td colspan=2> 
						<b><?php print _('Blurb') ?></b> 
						<?php print getEditor($guid,  TRUE, "description", $row["description"], 20 ) ?>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print _('Year Groups') ?></b><br/>
						<span style="font-size: 90%"><i><?php print _('Enrolable year groups.') ?></i></span>
					</td>
					<td class="right">
						<?php 
						$yearGroups=getYearGroups($connection2) ;
						if ($yearGroups=="") {
							print "<i>" . _('No year groups available.') . "</i>" ;
						}
						else {
							for ($i=0; $i<count($yearGroups); $i=$i+2) {
								$checked="" ;
								if (is_numeric(strpos($row["gibbonYearGroupIDList"], $yearGroups[$i]))) {
									$checked="checked " ;
								}
								print _($yearGroups[($i+1)]) . " <input $checked type='checkbox' name='gibbonYearGroupIDCheck" . ($i)/2 . "'><br/>" ; 
								print "<input type='hidden' name='gibbonYearGroupID" . ($i)/2 . "' value='" . $yearGroups[$i] . "'>" ;
							}
						}
						?>
						<input type="hidden" name="count" value="<?php print (count($yearGroups))/2 ?>">
					</td>
				</tr>
				<tr>
					<td>
						<span style="font-size: 90%"><i>* <?php print _("denotes a required field") ; ?></i></span>
					</td>
					<td class="right">
						<input name="gibbonSchoolYearID" id="gibbonSchoolYearID" value="<?php print $_GET["gibbonSchoolYearID"] ?>" type="hidden">
						<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
						<input type="submit" value="<?php print _("Submit") ; ?>">
					</td>
				</tr>
			</table>
			</form>
			<?php
			
			print "<h2>" ;
			print _("Edit Classes") ;
			print "</h2>" ;
			
			//Set pagination variable
			try {
				$data=array("gibbonCourseID"=>$gibbonCourseID); 
				$sql="SELECT * FROM gibbonCourseClass WHERE gibbonCourseID=:gibbonCourseID ORDER BY name" ; 
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			
			print "<div class='linkTop'>" ;
			print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/course_manage_class_add.php&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "&gibbonCourseID=$gibbonCourseID'>" .  _('Add') . "<img style='margin-left: 5px' title='" . _('Add') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.png'/></a>" ;
			print "</div>" ;
			
			if ($result->rowCount()<1) {
				print "<div class='error'>" ;
				print _("There are no records to display.") ;
				print "</div>" ;
			}
			else {
				print "<table cellspacing='0' style='width: 100%'>" ;
					print "<tr class='head'>" ;
						print "<th>" ;
							print _("Name") ;
						print "</th>" ;
						print "<th>" ;
							print _("Short Name") ;
						print "</th>" ;
						print "<th>" ;
							print _("Participants") ;
						print "</th>" ;
						print "<th>" ;
							print _("Reportable") ;
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
						
						
						//COLOR ROW BY STATUS!
						print "<tr class=$rowNum>" ;
							print "<td>" ;
								print $row["name"] ;
							print "</td>" ;
							print "<td>" ;
								print $row["nameShort"] ;
							print "</td>" ;
							print "<td>" ;
								try {
									$dataClasses=array("gibbonCourseClassID"=>$row["gibbonCourseClassID"]); 
									$sqlClasses="SELECT * FROM gibbonCourseClassPerson WHERE gibbonCourseClassID=:gibbonCourseClassID" ;
									$resultClasses=$connection2->prepare($sqlClasses);
									$resultClasses->execute($dataClasses);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								if ($resultClasses->rowCount()>=0) {
									print $resultClasses->rowCount() ;
								}
							print "</td>" ;
							print "<td>" ;
								print $row["reportable"] ;
							print "</td>" ;
							print "<td>" ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/course_manage_class_edit.php&gibbonCourseClassID=" . $row["gibbonCourseClassID"] . "&gibbonCourseID=$gibbonCourseID&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "'><img title='" . _('Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/course_manage_class_delete.php&gibbonCourseClassID=" . $row["gibbonCourseClassID"] . "&gibbonCourseID=$gibbonCourseID&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "'><img title='" . _('Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a> " ;
							print "</td>" ;
						print "</tr>" ;
						
						$count++ ;
					}
				print "</table>" ;
			}
		}
	}
}
?>