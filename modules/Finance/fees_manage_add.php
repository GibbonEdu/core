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

if (isActionAccessible($guid, $connection2, "/modules/Finance/fees_manage_add.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Finance/fees_manage.php&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "'>Manage Fees</a> > </div><div class='trailEnd'>Add Fee</div>" ;
	print "</div>" ;
	
	$editLink="" ;
	if (isset($_GET["editID"])) {
		$editLink=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Finance/fees_manage_edit.php&gibbonFinanceFeeID=" . $_GET["editID"] . "&search=" . $_GET["search"] . "&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] ;
	}
	if (isset($_GET["return"])) { returnProcess($guid, $_GET["return"], $editLink, null); }


	//Check if school year specified
	$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"] ;
	$search=$_GET["search"] ;
	if ($gibbonSchoolYearID=="") {
		print "<div class='error'>" ;
			print __($guid, "You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		if ($search!="") {
			print "<div class='linkTop'>" ;
				print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Finance/fees_manage.php&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search'>" . __($guid, 'Back to Search Results') . "</a>" ;
			print "</div>" ;
		}
		?>
		<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/fees_manage_addProcess.php?gibbonSchoolYearID=$gibbonSchoolYearID&search=$search" ?>">
			<table class='smallIntBorder fullWidth' cellspacing='0'>	
				<tr>
					<td style='width: 275px'> 
						<b><?php print __($guid, 'School Year') ?> *</b><br/>
						<span class="emphasis small"><?php print __($guid, 'This value cannot be changed.') ?></span>
					</td>
					<td class="right">
						<?php
						$yearName="" ;
						try {
							$dataYear=array("gibbonSchoolYearID"=>$gibbonSchoolYearID); 
							$sqlYear="SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID" ;
							$resultYear=$connection2->prepare($sqlYear);
							$resultYear->execute($dataYear);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($resultYear->rowCount()==1) {
							$rowYear=$resultYear->fetch() ;
							$yearName=$rowYear["name"] ;
						}
						?>
						<input readonly name="yearName" id="yearName" maxlength=20 value="<?php print $yearName ?>" type="text" class="standardWidth">
						<script type="text/javascript">
							var yearName=new LiveValidation('yearName');
							yearname2.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print __($guid, 'Name') ?> *</b><br/>
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
					</td>
					<td class="right">
						<input name="nameShort" id="nameShort" maxlength=6 value="" type="text" class="standardWidth">
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
				<tr>
					<td> 
						<b><?php print __($guid, 'Description') ?></b><br/>
					</td>
					<td class="right">
						<textarea name='description' id='description' rows=5 style='width: 300px'></textarea>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print __($guid, 'Category') ?> *</b><br/>
						<span class="emphasis small"></span>
					</td>
					<td class="right">
						<select name="gibbonFinanceFeeCategoryID" id="gibbonFinanceFeeCategoryID" class="standardWidth">
							<?php
							print "<option value='Please select...'>" . __($guid, 'Please select...') . "</option>" ;
							try {
								$dataSelect=array(); 
								$sqlSelect="SELECT * FROM gibbonFinanceFeeCategory WHERE active='Y' AND NOT gibbonFinanceFeeCategoryID=1 ORDER BY name" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) { }
							while ($rowSelect=$resultSelect->fetch()) {
								print "<option value='" . $rowSelect["gibbonFinanceFeeCategoryID"] . "'>" . $rowSelect["name"] . "</option>" ;
							}
							print "<option value='1'>Other</option>" ;
							?>				
						</select>
						<script type="text/javascript">
							var gibbonFinanceFeeCategoryID=new LiveValidation('gibbonFinanceFeeCategoryID');
							gibbonFinanceFeeCategoryID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print __($guid, 'Fee') ?> *</b><br/>
						<span style="font-size: 90%">
							<i>
							<?php
							if ($_SESSION[$guid]["currency"]!="") {
								print sprintf(__($guid, 'Numeric value of the fee in %1$s.'), $_SESSION[$guid]["currency"]) ;
							}
							else {
								print __($guid, "Numeric value of the fee.") ;
							}
							?>
							</i>
						</span>
					</td>
					<td class="right">
						<input name="fee" id="fee" maxlength=13 value="" type="text" class="standardWidth">
						<script type="text/javascript">
							var fee=new LiveValidation('fee');
							fee.add(Validate.Presence);
							fee.add( Validate.Format, { pattern: /^(?:\d*\.\d{1,2}|\d+)$/, failureMessage: "Invalid number format!" } );
						</script>
					</td>
				</tr>
				
				<tr>
					<td>
						<span class="emphasis small">* <?php print __($guid, "denotes a required field") ; ?></span>
					</td>
					<td class="right">
						<input name="gibbonFinanceFeeID" id="gibbonFinanceFeeID" value="<?php print $gibbonFinanceFeeID ?>" type="hidden">
						<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
						<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
					</td>
				</tr>
			</table>
		</form>
		<?php
	}
}
?>