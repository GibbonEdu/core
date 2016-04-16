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

if (isActionAccessible($guid, $connection2, "/modules/Activities/activities_copy.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Copy Activities') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["return"])) { returnProcess($_GET["return"], null, null); }
	
	?>
	<p>
		<?php print __($guid, 'This action copies all current activities, slots and staff into a specified year.') . " " . __($guid, "Copied activities will be added to any existing activities in the target year.") ; ?>
	</p>
	<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/activities_copyProcess.php" ?>">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<tr>
				<td style='width: 275px'> 
					<b><?php print __($guid, 'Current School Year') ?> *</b><br/>
				</td>
				<td class="right">
					<input readonly name="gibbonSchoolYearName" id="gibbonSchoolYearName" value="<?php print $_SESSION[$guid]["gibbonSchoolYearName"] ?>" type="text" style="width: 300px">
					<input readonly name="gibbonSchoolYearID" id="gibbonSchoolYearID" value="<?php print $_SESSION[$guid]["gibbonSchoolYearID"] ?>" type="hidden">
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print __($guid, 'Target School Year') ?> *</b><br/>
				</td>
				<td class="right">
					<select name="gibbonSchoolYearIDTarget" id="gibbonSchoolYearIDTarget" style="width: 302px">
						<option value='Please select...'><?php print __($guid, 'Please select...') ?></option>
						<?php
						try {
							$dataSelect=array(); 
							$sqlSelect="SELECT * FROM gibbonSchoolYear WHERE status='Upcoming' ORDER BY sequenceNumber" ;
							$resultSelect=$connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						}
						catch(PDOException $e) { }
						while ($rowSelect=$resultSelect->fetch()) {
							print "<option value='" . $rowSelect["gibbonSchoolYearID"] . "'>" . $rowSelect["name"] . "</option>" ; 
						}
						?>
					</select>
					<script type="text/javascript">
						var gibbonSchoolYearIDTarget=new LiveValidation('gibbonSchoolYearIDTarget');
						gibbonSchoolYearIDTarget.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
					</script>	
				</td>
			</tr>
			<tr>
				<td>
					<span style="font-size: 90%"><i>* <?php print __($guid, "denotes a required field") ; ?></i></span>
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