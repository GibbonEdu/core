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

if (isActionAccessible($guid, $connection2, "/modules/Finance/expenseApprovers_manage_add.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/expenseApprovers_manage.php'>" . __($guid, 'Manage Expense Approvers') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Add Expense Approver') . "</div>" ;
	print "</div>" ;

	if (isset($_GET["return"])) { returnProcess($_GET["return"], null, array("error3" => "Your request failed because some inputs did not meet a requirement for uniqueness.")); }
	
	?>
	<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/expenseApprovers_manage_addProcess.php" ?>">
		<table class='smallIntBorder fullWidth' cellspacing='0'>	
			<tr>
				<td> 
					<b><?php print __($guid, 'Staff') ?> *</b><br/>
				</td>
				<td class="right">
					<select name="gibbonPersonID" id="gibbonPersonID" class="standardWidth">
						<option value="Please select..."><?php print __($guid, 'Please select...') ?></option>
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
					<script type="text/javascript">
						var gibbonPersonID=new LiveValidation('gibbonPersonID');
						gibbonPersonID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
					</script>
				</td>
			</tr>
			<?php
			$expenseApprovalType=getSettingByScope($connection2, "Finance", "expenseApprovalType") ;
			if ($expenseApprovalType=="Chain Of All") {
				?>
				<tr>
					<td> 
						<b><?php print __($guid, 'Sequence Number') ?> *</b><br/>
						<span class="emphasis small"><?php print __($guid, 'Must be unique.') ?></span>
					</td>
					<td class="right">
						<input name="sequenceNumber" ID="sequenceNumber" value="" type="text" class="standardWidth">
						<script type="text/javascript">
							var sequenceNumber=new LiveValidation('sequenceNumber');
							sequenceNumber.add(Validate.Numericality, { minimum: 0 } );
							sequenceNumber.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<?php
			}
			?>
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
