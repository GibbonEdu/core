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

if (isActionAccessible($guid, $connection2, "/modules/School Admin/externalAssessments_manage_add.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/externalAssessments_manage.php'>" . __($guid, 'Manage External Assessments') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Add External Assessment') . "</div>" ;
	print "</div>" ;
	
	$editLink="" ;
	if (isset($_GET["editID"])) {
		$editLink=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/School Admin/externalAssessments_manage_edit.php&gibbonExternalAssessmentID=" . $_GET["editID"] ;
	}
	if (isset($_GET["return"])) { returnProcess($guid, $_GET["return"], $editLink, null); }
	

	
	?>
	
	<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/externalAssessments_manage_addProcess.php" ?>">
		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<tr>
				<td style='width: 275px'> 
					<b><?php print __($guid, 'Name') ?> *</b><br/>
					<span class="emphasis small"><?php print __($guid, 'Must be unique.') ; ?></span>
				</td>
				<td class="right">
					<input name="name" id="name" maxlength=50 value="" type="text" class="standardWidth">
					<script type="text/javascript">
						var name2=new LiveValidation('name');
						name2.add(Validate.Presence);
					</script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Short Name') ?> *</b><br/>
					<span class="emphasis small"><?php print __($guid, 'Must be unique.') ; ?></span>
				</td>
				<td class="right">
					<input name="nameShort" id="nameShort" maxlength=10 value="" type="text" class="standardWidth">
					<script type="text/javascript">
						var nameShort=new LiveValidation('nameShort');
						nameShort.add(Validate.Presence);
					</script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Description') ?> *</b><br/>
					<span class="emphasis small"><?php print __($guid, 'Brief description of assessment and how it is used.') ; ?> </span>
				</td>
				<td class="right">
					<input name="description" id="description" maxlength=255 value="" type="text" class="standardWidth">
					<script type="text/javascript">
						var description=new LiveValidation('description');
						description.add(Validate.Presence);
					</script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Active') ; ?> *</b><br/>
				</td>
				<td class="right">
					<select name="active" id="active" class="standardWidth">
						<option value="Y"><?php print __($guid, 'Yes') ?></option>
						<option value="N"><?php print __($guid, 'No') ?></option>
					</select>
				</td>
			</tr>
			
			<tr>
				<td> 
					<b><?php print __($guid, 'Allow File Upload') ; ?> *</b><br/>
					<span class="emphasis small"><?php print __($guid, 'Should the student record include the option of a file upload?') ; ?> </span>
				</td>
				<td class="right">
					<select name="allowFileUpload" id="allowFileUpload" class="standardWidth">
						<option value="N"><?php print __($guid, 'No') ?></option>
						<option value="Y"><?php print __($guid, 'Yes') ?></option>
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
}
?>