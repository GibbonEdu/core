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

if (isActionAccessible($guid, $connection2, "/modules/User Admin/role_manage_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/role_manage.php'>" . __($guid, 'Manage Roles') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Edit Role') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["return"])) { returnProcess($guid, $_GET["return"], null, null); }
	
	//Check if school year specified
	$gibbonRoleID=$_GET["gibbonRoleID"] ;
	if ($gibbonRoleID=="") {
		print "<div class='error'>" ;
			print __($guid, "You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonRoleID"=>$gibbonRoleID); 
			$sql="SELECT * FROM gibbonRole WHERE gibbonRoleID=:gibbonRoleID" ;
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
			$type=$row["type"] ;
			?>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/role_manage_editProcess.php?gibbonRoleID=$gibbonRoleID" ?>">
				<table class='smallIntBorder fullWidth' cellspacing='0'>	
					<tr>
						<td style='width: 275px'> 
							<b><?php print __($guid, 'Category') ?> *</b><br/>
							<?php
							if ($type=="Core") {
								print "<span style=\"font-size: 90%\"><i>" . __($guid, 'This value cannot be changed.'). "</span>" ;
							}
							?>
						</td>
						<td class="right">
							<?php
							if ($type=="Core") {
								?>
								<input name="category" id="category" readonly="readonly" maxlength=20 value="<?php print __($guid, $row["category"]) ?>" type="text" class="standardWidth">
								<?php
							}
							else {
								?>
								<select name="category" id="category" class="standardWidth">
									<option value="Please select..."><?php print __($guid, 'Please select...') ?></option>
									<option <?php if ($row["category"]=="Staff") { print "selected " ; } ?>value="Staff"><?php print __($guid, 'Staff') ?></option>
									<option <?php if ($row["category"]=="Student") { print "selected " ; } ?>value="Student"><?php print __($guid, 'Student') ?></option>
									<option <?php if ($row["category"]=="Parent") { print "selected " ; } ?>value="Parent"><?php print __($guid, 'Parent') ?></option>
									<option <?php if ($row["category"]=="Other") { print "selected " ; } ?>value="Other"><?php print __($guid, 'Other') ?></option>
								</select>
								<script type="text/javascript">
									var category=new LiveValidation('category');
									category.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
								</script>
								<?php
							}
							?>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Name') ?> *</b><br/>
							<span class="emphasis small"><?php print __($guid, 'Must be unique.') ?></span><br/>
							<?php
							if ($type=="Core") {
								print "<span style=\"font-size: 90%\"><i>" . __($guid, 'This value cannot be changed.'). "</span>" ;
							}
							?>
							
						</td>
						<td class="right">
							<?php
							if ($type=="Core") {
								?>
								<input name="name" id="name" readonly="readonly" maxlength=20 value="<?php print __($guid, $row["name"]) ?>" type="text" class="standardWidth">
								<?php
							}
							else {
								?>
								<input name="name" id="name" maxlength=20 value="<?php print htmlPrep(__($guid, $row["name"])) ?>" type="text" class="standardWidth">
								<script type="text/javascript">
									var name2=new LiveValidation('name');
									name2.add(Validate.Presence);
								</script> 
								<?php
							}
							?>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Short Name') ?> *</b><br/>
							<span class="emphasis small"><?php print __($guid, 'Must be unique.') ?></span><br/>
							<?php
							if ($type=="Core") {
								print "<span style=\"font-size: 90%\"><i>" . __($guid, 'This value cannot be changed.'). "</span>" ;
							}
							?>
						</td>
						<td class="right">
							<?php
							if ($type=="Core") {
								?>
								<input name="nameShort" id="nameShort" readonly="readonly" maxlength=20 value="<?php print __($guid, $row["nameShort"]) ?>" type="text" class="standardWidth">
								<?php
							}
							else {
								?>
								<input name="nameShort" id="nameShort" maxlength=4 value="<?php print htmlPrep(__($guid, $row["nameShort"])) ?>" type="text" class="standardWidth">
								<script type="text/javascript">
									var nameShort=new LiveValidation('nameShort');
									nameShort.add(Validate.Presence);
								</script> 
								<?php
							}
							?>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Description') ?> *</b><br/>
							<?php
							if ($type=="Core") {
								print "<span style=\"font-size: 90%\"><i>" . __($guid, 'This value cannot be changed.'). "</span>" ;
							}
							?>
						</td>
						<td class="right">
							<?php
							if ($type=="Core") {
								?>
								<input name="description" id="description" readonly="readonly" maxlength=60 value="<?php print __($guid, $row["description"]) ?>" type="text" class="standardWidth">
								<?php
							}
							else {
								?>
								<input name="description" id="description" maxlength=60 value="<?php print htmlPrep(__($guid, $row["description"])) ?>" type="text" class="standardWidth">
								<script type="text/javascript">
									var description=new LiveValidation('description');
									description.add(Validate.Presence);
								</script> 
								<?php
							}
							?>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Type') ?> *</b><br/>
							<span class="emphasis small"><?php print __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<input name="type" id="type" readonly="readonly" maxlength=20 value="<?php print __($guid, $row["type"]) ?>" type="text" class="standardWidth">
						</td>
					</tr>
						<td> 
							<b><?php print __($guid, 'Login To Past Years') ?> *</b><br/>
						</td>
						<td class="right">
							<select name="pastYearsLogin" id="pastYearsLogin" class="standardWidth">
								<option <?php if ($row["pastYearsLogin"]=="Y") { print "selected" ; } ?> value="Y"><?php print __($guid, 'Yes') ?></option>
								<option <?php if ($row["pastYearsLogin"]=="N") { print "selected" ; } ?> value="N"><?php print __($guid, 'No') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Login To Future Years') ?> *</b><br/>
						</td>
						<td class="right">
							<select name="futureYearsLogin" id="futureYearsLogin" class="standardWidth">
								<option <?php if ($row["futureYearsLogin"]=="Y") { print "selected" ; } ?> value="Y"><?php print __($guid, 'Yes') ?></option>
								<option <?php if ($row["futureYearsLogin"]=="N") { print "selected" ; } ?> value="N"><?php print __($guid, 'No') ?></option>
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
	}
}
?>