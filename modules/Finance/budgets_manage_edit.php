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
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/budgets_manage.php'>" . _('Manage Budgets') . "</a> > </div><div class='trailEnd'>" . _('Edit Budget') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
	$updateReturnMessage="" ;
	$class="error" ;
	if (!($updateReturn=="")) {
		if ($updateReturn=="fail0") {
			$updateReturnMessage=_("Your request failed because you do not have access to this action.") ;	
		}
		else if ($updateReturn=="fail1") {
			$updateReturnMessage=_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($updateReturn=="fail2") {
			$updateReturnMessage=_("Your request failed due to a database error.") ;	
		}
		else if ($updateReturn=="fail3") {
			$updateReturnMessage=_("Your request failed because your inputs were invalid.") ;	
		}
		else if ($updateReturn=="fail4") {
			$updateReturnMessage=_("Your request failed because some inputs did not meet a requirement for uniqueness.") ;	
		}
		else if ($updateReturn=="fail5") {
			$updateReturnMessage=_("Your request failed due to an attachment error.") ;	
		}
		else if ($updateReturn=="success0") {
			$updateReturnMessage=_("Your request was completed successfully.") ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $updateReturnMessage;
		print "</div>" ;
	} 
	
	//Check if school year specified
	$gibbonFinanceBudgetID=$_GET["gibbonFinanceBudgetID"];
	if ($gibbonFinanceBudgetID=="") {
		print "<div class='error'>" ;
			print _("You have not specified one or more required parameters.") ;
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
				print _("The specified record does not exist.") ;
			print "</div>" ;
		}
		else {
			//Let's go!
			$row=$result->fetch() ;
			?>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/budgets_manage_editProcess.php?gibbonFinanceBudgetID=$gibbonFinanceBudgetID" ?>">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr class='break'>
						<td colspan=2> 
							<h3><?php print _('General Settings') ?></h3>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'> 
							<b><?php print _('Name') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print _('Must be unique.') ?></i></span>
						</td>
						<td class="right">
							<input name="name" id="name" maxlength=100 value="<?php print $row["name"] ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var name2=new LiveValidation('name');
								name2.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Short Name') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print _('Must be unique.') ?></i></span>
						</td>
						<td class="right">
							<input name="nameShort" id="nameShort" maxlength=14 value="<?php print $row["nameShort"] ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var nameShort=new LiveValidation('nameShort');
								nameShort.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print _('Active') ?> *</b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<select name="active" id="active" style="width: 302px">
								<option <?php if ($row["active"]=="Y") { print "selected" ; } ?> value="Y"><?php print _('Yes') ?></option>
								<option <?php if ($row["active"]=="N") { print "selected" ; } ?> value="N"><?php print _('No') ?></option>
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
								<b><?php print _('Category') ?> *</b><br/>
								<span style="font-size: 90%"><i></i></span>
							</td>
							<td class="right">
								<select name="category" id="category" style="width: 302px">
									<option value="Please select..."><?php print _('Please select...') ?></option>
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
									category.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print _('Select something!') ?>"});
								 </script>
							</td>
						</tr>
						<?php
					}
					else {
						?>
						<tr>
							<td> 
								<b><?php print _('Category') ?> *</b><br/>
								<span style="font-size: 90%"><i></i></span>
							</td>
							<td class="right">
								<input readonly name="category" id="category" value="Other" type="text" style="width: 300px">
							</td>
						</tr>
						<?php
					}
					?>
					<tr class='break'>
						<td colspan=2> 
							<h3><?php print _('Current Staff') ?></h3>
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
								print _("There are no records to display.") ;
								print "</div>" ;
							}
							else {
								print "<i><b>Warning</b>: If you delete a member of staff, any unsaved changes to this record will be lost!</i>" ;
								print "<table cellspacing='0' style='width: 100%'>" ;
									print "<tr class='head'>" ;
										print "<th>" ;
											print _("Name") ;
										print "</th>" ;
										print "<th>" ;
											print _("Access") ;
										print "</th>" ;
										print "<th>" ;
											print _("Action") ;
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
												print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/budgets_manage_edit_staff_deleteProcess.php?address=" . $_GET["q"] . "&gibbonFinanceBudgetPersonID=" . $row["gibbonFinanceBudgetPersonID"] . "&gibbonFinanceBudgetID=$gibbonFinanceBudgetID'><img title='" . _('Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a>" ;
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
							<h3><?php print _('New Staff') ?></h3>
						</td>
					</tr>
					<tr>
					<td> 
						<b>Staff</b><br/>
						<span style="font-size: 90%"><i><?php print _('Use Control, Command and/or Shift to select multiple.') ?></i></span>
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
							<b><?php print _('Role') ?></b><br/>
						</td>
						<td class="right">
							<select name="access" id="access" style="width: 302px">
								<option value="Full"><?php print _('Full') ?></option>
								<option value="Write"><?php print _('Write') ?></option>
								<option value="Read"><?php print _('Read') ?></option>
							</select>
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
	}
}
?>