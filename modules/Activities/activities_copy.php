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
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . _(getModuleName($_GET["q"])) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>" . _('Copy Activities') . "</div>" ;
	print "</div>" ;
	
	
	if (isset($_GET["copyReturn"])) { $copyReturn=$_GET["copyReturn"] ; } else { $copyReturn="" ; }
	$copyReturnMessage="" ;
	$class="error" ;
	if (!($copyReturn=="")) {
		if ($copyReturn=="fail0") {
			$copyReturnMessage=_("Your request failed because you do not have access to this action.") ;	
		}
		else if ($copyReturn=="fail1") {
			$copyReturnMessage=_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($copyReturn=="fail2") {
			$copyReturnMessage=_("Your request failed due to a database error.") ;	
		}
		else if ($copyReturn=="fail3") {
			$copyReturnMessage=_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($copyReturn=="success0") {
			$copyReturnMessage=_("Your request was completed successfully.") ;		
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $copyReturnMessage;
		print "</div>" ;
	} 
	
	?>
	<p>
		<?php print _('This action copies all current activities, slots and staff into a specified year.') . " " . _("Copied activities will be added to any existing activities in the target year.") ; ?>
	</p>
	<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/activities_copyProcess.php" ?>">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<tr>
				<td style='width: 275px'> 
					<b><?php print _('Current School Year') ?> *</b><br/>
				</td>
				<td class="right">
					<input readonly name="gibbonSchoolYearName" id="gibbonSchoolYearName" value="<?php print $_SESSION[$guid]["gibbonSchoolYearName"] ?>" type="text" style="width: 300px">
					<input readonly name="gibbonSchoolYearID" id="gibbonSchoolYearID" value="<?php print $_SESSION[$guid]["gibbonSchoolYearID"] ?>" type="hidden">
				</td>
			</tr>
			<tr>
				<td> 
					<b><?php print _('Target School Year') ?> *</b><br/>
				</td>
				<td class="right">
					<select name="gibbonSchoolYearIDTarget" id="gibbonSchoolYearIDTarget" style="width: 302px">
						<option value='Please select...'><?php print _('Please select...') ?></option>
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
						gibbonSchoolYearIDTarget.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
					 </script>	
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
}
?>