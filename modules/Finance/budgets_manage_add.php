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

if (isActionAccessible($guid, $connection2, "/modules/Finance/budgets_manage_add.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/budgets_manage.php'>" . __($guid, 'Manage Budgets') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Add Budget') . "</div>" ;
	print "</div>" ;

	if (isset($_GET["return"])) { returnProcess($_GET["return"], null, array("error3" => "Your request failed because some inputs did not meet a requirement for uniqueness.", "warning1" => "Your request was successful, but some data was not properly saved.")); }
	
	?>
	<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/budgets_manage_addProcess.php" ?>">
		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<tr class='break'>
				<td colspan=2> 
					<h3><?php print __($guid, 'General Settings') ?></h3>
				</td>
			</tr>
			<tr>
				<td style='width: 275px'> 
					<b><?php print __($guid, 'Name') ?> *</b><br/>
					<span class="emphasis small"><?php print __($guid, 'Must be unique.') ?></span>
				</td>
				<td class="right">
					<input name="name" id="name" maxlength=100 value="" type="text" class="standardWidth">
					<script type="text/javascript">
						var name2=new LiveValidation('name');
						name2.add(Validate.Presence);
					</script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Short Name') ?> *</b><br/>
					<span class="emphasis small"><?php print __($guid, 'Must be unique.') ?></span>
				</td>
				<td class="right">
					<input name="nameShort" id="nameShort" maxlength=14 value="" type="text" class="standardWidth">
					<script type="text/javascript">
						var nameShort=new LiveValidation('nameShort');
						nameShort.add(Validate.Presence);
					</script>
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Active') ?> *</b><br/>
					<span class="emphasis small"></span>
				</td>
				<td class="right">
					<select name="active" id="active" class="standardWidth">
						<option value="Y"><?php print __($guid, 'Yes') ?></option>
						<option value="N"><?php print __($guid, 'No') ?></option>
					</select>
				</td>
			</tr>
			<?php
			$categories=getSettingByScope($connection2, "Finance", "budgetCategories") ;
			if ($categories!=FALSE) {
				$categories=explode(",", $categories) ;
				?>
				<tr>
					<td> 
						<b><?php print __($guid, 'Category') ?> *</b><br/>
						<span class="emphasis small"></span>
					</td>
					<td class="right">
						<select name="category" id="category" class="standardWidth">
							<option value="Please select..."><?php print __($guid, 'Please select...') ?></option>
							<?php
							for ($i=0; $i<count($categories); $i++) {
								?>
								<option value="<?php print trim($categories[$i]) ?>"><?php print trim($categories[$i]) ?></option>
							<?php
							}
							?>
						</select>
						<script type="text/javascript">
							var category=new LiveValidation('category');
							category.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
						</script>
					</td>
				</tr>
				<?php
			}
			else {
				?>
				<tr>
					<td> 
						<b><?php print __($guid, 'Category') ?> *</b><br/>
						<span class="emphasis small"></span>
					</td>
					<td class="right">
						<input readonly name="category" id="category" value="Other" type="text" class="standardWidth">
					</td>
				</tr>
				<?php
			}
			?>
			<tr class='break'>
				<td colspan=2> 
					<h3><?php print __($guid, 'Staff') ?></h3>
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
			<tr>
				<td> 
					<b><?php print __($guid, 'Access') ?></b><br/>
				</td>
				<td class="right">
					<select name="access" id="access" class="standardWidth">
						<option value="Full"><?php print __($guid, 'Full') ?></option>
						<option value="Write"><?php print __($guid, 'Write') ?></option>
						<option value="Read"><?php print __($guid, 'Read') ?></option>
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