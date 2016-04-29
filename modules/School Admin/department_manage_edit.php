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


if (isActionAccessible($guid, $connection2, "/modules/School Admin/department_manage_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/department_manage.php'>" . __($guid, 'Manage Departments') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Edit Department') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["return"])) { returnProcess($guid, $_GET["return"], null, null); }
	
	//Check if school year specified
	$gibbonDepartmentID=$_GET["gibbonDepartmentID"];
	if ($gibbonDepartmentID=="Y") {
		print "<div class='error'>" ;
			print __($guid, "You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonDepartmentID"=>$gibbonDepartmentID); 
			$sql="SELECT * FROM gibbonDepartment WHERE gibbonDepartmentID=:gibbonDepartmentID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}

		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print __($guid, "The selected record does not exist, or you do not have access to it.") ;
			print "</div>" ;
		}
		else {
			//Let's go!
			$row=$result->fetch() ;
			?>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/department_manage_editProcess.php?gibbonDepartmentID=$gibbonDepartmentID&address=" . $_SESSION[$guid]["address"] ?>" enctype="multipart/form-data">
				<table class='smallIntBorder fullWidth' cellspacing='0'>	
					<tr class='break'>
						<td colspan=2>
							<h3><?php print __($guid, 'General Information') ?></h3>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php print __($guid, 'Type') ?> *</b><br/>
							<span class="emphasis small"><?php print __($guid, 'This value cannot be changed.') ?></i><br/></span>
						</td>
						<td class="right">
							<?php $type=$row["type"] ; ?>
							<input readonly name="type" id="type" value="<?php print $type ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Name') ?> *</b><br/>
						</td>
						<td class="right">
							<input name="name" id="name" maxlength=40 value="<?php print $row["name"] ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var name2=new LiveValidation('name');
								name2.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Short Name') ?> *</b><br/>
						</td>
						<td class="right">
							<input name="nameShort" id="nameShort" maxlength=4 value="<?php print $row["nameShort"] ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var nameShort=new LiveValidation('nameShort');
								nameShort.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Subject Listing') ?></b><br/>
						</td>
						<td class="right">
							<input name="subjectListing" id="subjectListing" maxlength=255 value="<?php print $row["subjectListing"] ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<tr>
						<td colspan=2> 
							<b><?php print __($guid, 'Blurb') ?></b> 
							<?php print getEditor($guid,  TRUE, "blurb", $row["blurb"], 20 ) ?>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Logo') ?></b><br/>
							<span class="emphasis small">125x125px jpg/png/gif</i><br/></span>
							<?php if ($row["logo"]!="") { ?>
							<span class="emphasis small"><?php print __($guid, 'Will overwrite existing attachment.') ?></span>
							<?php } ?>
						</td>
						<td class="right">
							<?php
							if ($row["logo"]!="") {
								print __($guid, "Current attachment:") . " <a href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $row["logo"] . "'>" . $row["logo"] . "</a><br/><br/>" ;
							}
							?>
							<input type="file" name="file" id="file"><br/><br/>
							<?php
							print getMaxUpload($guid) ;
							$ext="'.png','.jpeg','.jpg','.gif'" ;
							?>
							
							<script type="text/javascript">
								var file=new LiveValidation('file');
								file.add( Validate.Inclusion, { within: [<?php print $ext ;?>], failureMessage: "<?php print __($guid, 'Illegal file type!') ?>", partialMatch: true, caseSensitive: false } );
							</script>
						</td>
					</tr>
					<tr class='break'>
						<td colspan=2> 
							<h3><?php print __($guid, 'Current Staff') ?></h3>
						</td>
					</tr>
					<tr>
						<td colspan=2> 
							<?php
							try {
								$data=array("gibbonDepartmentID"=>$gibbonDepartmentID); 
								$sql="SELECT preferredName, surname, gibbonDepartmentStaff.* FROM gibbonDepartmentStaff JOIN gibbonPerson ON (gibbonDepartmentStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonDepartmentID=:gibbonDepartmentID AND gibbonPerson.status='Full' ORDER BY surname, preferredName" ; 
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
								print "<i><b>Warning</b>: If you delete a member of staff, any unsaved changes to this record will be lost!</i>" ;
								print "<table cellspacing='0' style='width: 100%'>" ;
									print "<tr class='head'>" ;
										print "<th>" ;
											print __($guid, "Name") ;
										print "</th>" ;
										print "<th>" ;
											print __($guid, "Role") ;
										print "</th>" ;
										print "<th>" ;
											print __($guid, "Action") ;
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
												print formatName("", $row["preferredName"], $row["surname"], "Staff", true, true) ;
											print "</td>" ;
											print "<td>" ;
												print $row["role"] ;
											print "</td>" ;
											print "<td>" ;
												print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/department_manage_edit_staff_deleteProcess.php?address=" . $_GET["q"] . "&gibbonDepartmentStaffID=" . $row["gibbonDepartmentStaffID"] . "&gibbonDepartmentID=$gibbonDepartmentID'><img title='" . __($guid, 'Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a>" ;
											print "</td>" ;
										print "</tr>" ;
									}
								print "</table>" ;
							}
							?>
						</td>
					</tr>
					<tr class='break'>
						<td colspan=2> 
							<h3><?php print __($guid, 'New Staff') ?></h3>
						</td>
					</tr>
					<tr>
					<td> 
						<b>Staff</b><br/>
						<span class="emphasis small"><?php print __($guid, 'Use Control, Command and/or Shift to select multiple.') ?></span>
					</td>
					<td class="right">
						<select name="staff[]" id="staff[]" multiple style="width: 302px; height: 150px">
							<?php
							try {
								$dataSelect=array(); 
								$sqlSelect="SELECT * FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) WHERE status='Full' ORDER BY surname, preferredName" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) { }
							while ($rowSelect=$resultSelect->fetch()) {
								print "<option value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Staff", true, true) . "</option>" ;
							}
							?>
						</select>
					</td>
					
					<tr id='roleLARow'>
						<td> 
							<b><?php print __($guid, 'Role') ?></b><br/>
						</td>
						<td class="right">
							<select name="role" id="role" class="standardWidth">
								<?php
								if ($type=="Learning Area") {
									?>
									<option value="Coordinator"><?php print __($guid, 'Coordinator') ?></option>
									<option value="Assistant Coordinator"><?php print __($guid, 'Assistant Coordinator') ?></option>
									<option value="Teacher (Curriculum)"><?php print __($guid, 'Teacher (Curriculum)') ?></option>
									<option value="Teacher"><?php print __($guid, 'Teacher') ?></option>
									<option value="Other"><?php print __($guid, 'Other') ?></option>
									<?php
								}
								else if ($type=="Administration") {
									?>
									<option value="Director"><?php print __($guid, 'Director') ?></option>
									<option value="Manager"><?php print __($guid, 'Manager') ?></option>
									<option value="Administrator"><?php print __($guid, 'Administrator') ?></option>
									<option value="Other"><?php print __($guid, 'Other') ?></option>
									<?php
								}
								else {
									?>
									<option value="Other"><?php print __($guid, 'Other') ?></option>
									<?php
								}
								?>
							</select>
						</td>
					</tr>
					
					<tr>
						<td>
							<span class="emphasis small">* <?php print __($guid, "denotes a required field") ; ?></span>
						</td>
						<td class="right">
							<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
						</td>
					</tr>
				</table>
			</form>
			<?php
		}
	}
}
?>