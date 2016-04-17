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

if (isActionAccessible($guid, $connection2, "/modules/School Admin/department_manage_add.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/department_manage.php'>" . __($guid, 'Manage Departments') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Add Learning Area') . "</div>" ;
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
		else if ($addReturn=="fail4") {
			$addReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
		}
		else if ($addReturn=="fail5") {
			$addReturnMessage=__($guid, "Your request failed due to an attachment error.") ;	
		}
		else if ($addReturn=="success0") {
			$addReturnMessage=__($guid, "Your request was completed successfully. You can now add another record if you wish.") ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $addReturnMessage;
		print "</div>" ;
	} 
	
	?>
	<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/department_manage_addProcess.php?address=" . $_SESSION[$guid]["address"] ?>" enctype="multipart/form-data">
		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<!-- FIELDS & CONTROLS FOR TYPE -->
			<script type="text/javascript">
				$(document).ready(function(){
					$("#type").change(function(){
						if ($('select.type option:selected').val()=="Learning Area" ) {
							$("#roleAdminRow").css("display","none");
							$("#roleLARow").slideDown("fast", $("#roleLARow").css("display","table-row")); 
						} else if ($('select.type option:selected').val()=="Administration" ) {
							$("#roleLARow").css("display","none");
							$("#roleAdminRow").slideDown("fast", $("#roleAdminRow").css("display","table-row")); 
						} 
					 });
				});
			</script>
			<tr>
				<td style='width: 275px'> 
					<b><?php print __($guid, 'Type') ?> *</b><br/>
				</td>
				<td class="right">
					<select name="type" id="type" class='type' class="standardWidth">
						<option value='Learning Area'>Learning Area</option>
						<option value='Administration'>Administration</option>
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Name') ?> *</b><br/>
				</td>
				<td class="right">
					<input name="name" id="name" maxlength=40 value="" type="text" class="standardWidth">
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
					<input name="nameShort" id="nameShort" maxlength=4 value="" type="text" class="standardWidth">
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
					<input name="subjectListing" id="subjectListing" maxlength=255 value="" type="text" class="standardWidth">
				</td>
			</tr>
			<tr>
				<td colspan=2> 
					<b><?php print __($guid, 'Blurb') ?></b> 
					<?php print getEditor($guid,  TRUE, "blurb", "", 20 ) ?>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Logo') ?></b><br/>
					<span class="emphasis small">125x125px jpg/png/gif</span>
				</td>
				<td class="right">
					<input type="file" name="file" id="file"><br/><br/>
					<?php
					print getMaxUpload($guid) ;
					$ext="'.png','.jpeg','.jpg','.gif'" ;
					?>
					
					<script type="text/javascript">
						var file=new LiveValidation('file');
						file.add( Validate.Inclusion, { within: [<?php print $ext ;?>], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
					</script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Staff') ?></b><br/>
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
			</tr>
			<tr id='roleLARow'>
				<td> 
					<b><?php print __($guid, 'Role') ?></b><br/>
				</td>
				<td class="right">
					<select name="roleLA" id="roleLA" class="standardWidth">
						<option value="Coordinator"><?php print __($guid, 'Coordinator') ?></option>
						<option value="Assistant Coordinator"><?php print __($guid, 'Assistant Coordinator') ?></option>
						<option value="Teacher (Curriculum)"><?php print __($guid, 'Teacher (Curriculum)') ?></option>
						<option value="Teacher"><?php print __($guid, 'Teacher') ?></option>
						<option value="Other"><?php print __($guid, 'Other') ?></option>
					</select>
				</td>
			</tr>
			<tr id='roleAdminRow' style='display: none'>
				<td> 
					<b>Role</b><br/>
				</td>
				<td class="right">
					<select name="roleAdmin" id="roleAdmin" class="standardWidth">
						<option value="Director"><?php print __($guid, 'Director') ?></option>
						<option value="Manager"><?php print __($guid, 'Manager') ?></option>
						<option value="Administrator"><?php print __($guid, 'Administrator') ?></option>
						<option value="Other"><?php print __($guid, 'Other') ?></option>
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
?>