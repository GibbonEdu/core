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


if (isActionAccessible($guid, $connection2, "/modules/Finance/budgets_manage_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/budgets_manage.php'>" . __($guid, 'Manage Budgets') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Edit Budget') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["return"])) { returnProcess($_GET["return"], null, array("error3" => "Your request failed because some inputs did not meet a requirement for uniqueness.", "error4" => "Your request failed due to an attachment error.")); }

	//Check if school year specified
	$gibbonFinanceBudgetID=$_GET["gibbonFinanceBudgetID"];
	if ($gibbonFinanceBudgetID=="") {
		print "<div class='error'>" ;
			print __($guid, "You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonFinanceBudgetID"=>$gibbonFinanceBudgetID); 
			$sql="SELECT * FROM gibbonFinanceBudget WHERE gibbonFinanceBudgetID=:gibbonFinanceBudgetID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print __($guid, "The specified record does not exist.") ;
			print "</div>" ;
		}
		else {
			//Let's go!
			$row=$result->fetch() ;
			?>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/budgets_manage_editProcess.php?gibbonFinanceBudgetID=$gibbonFinanceBudgetID" ?>">
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
							<input name="name" id="name" maxlength=100 value="<?php print $row["name"] ?>" type="text" class="standardWidth">
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
							<input name="nameShort" id="nameShort" maxlength=14 value="<?php print $row["nameShort"] ?>" type="text" class="standardWidth">
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
								<option <?php if ($row["active"]=="Y") { print "selected" ; } ?> value="Y"><?php print __($guid, 'Yes') ?></option>
								<option <?php if ($row["active"]=="N") { print "selected" ; } ?> value="N"><?php print __($guid, 'No') ?></option>
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
										$selected="" ;
										if (trim($categories[$i])==$row["category"]) {
											$selected="selected" ;
										}
										print "<option $selected value=\"" . trim($categories[$i]) . "\">" . trim($categories[$i]) . "</option>" ;
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
							<h3><?php print __($guid, 'Current Staff') ?></h3>
						</td>
					</tr>
					<tr>
						<td colspan=2> 
							<?php
							try {
								$data=array("gibbonFinanceBudgetID"=>$gibbonFinanceBudgetID); 
								$sql="SELECT preferredName, surname, gibbonFinanceBudgetPerson.* FROM gibbonFinanceBudgetPerson JOIN gibbonPerson ON (gibbonFinanceBudgetPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFinanceBudgetID=:gibbonFinanceBudgetID AND gibbonPerson.status='Full' ORDER BY FIELD(access,'Full','Write','Read'), surname, preferredName" ; 
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
											print __($guid, "Access") ;
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
												print $row["access"] ;
											print "</td>" ;
											print "<td>" ;
												print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/budgets_manage_edit_staff_deleteProcess.php?address=" . $_GET["q"] . "&gibbonFinanceBudgetPersonID=" . $row["gibbonFinanceBudgetPersonID"] . "&gibbonFinanceBudgetID=$gibbonFinanceBudgetID'><img title='" . __($guid, 'Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a>" ;
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
	}
}
?>