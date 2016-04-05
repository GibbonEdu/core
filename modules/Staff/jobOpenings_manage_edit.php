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

if (isActionAccessible($guid, $connection2, "/modules/Staff/jobOpenings_manage_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Staff/jobOpenings_manage.php'>" . __($guid, 'Job Openings') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Edit Job Opening') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
	$updateReturnMessage="" ;
	$class="error" ;
	if (!($updateReturn=="")) {
		if ($updateReturn=="fail0") {
			$updateReturnMessage=__($guid, "Your request failed because you do not have access to this action.") ;	
		}
		else if ($updateReturn=="fail1") {
			$updateReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
		}
		else if ($updateReturn=="fail2") {
			$updateReturnMessage=__($guid, "Your request failed due to a database error.") ;	
		}
		else if ($updateReturn=="fail3") {
			$updateReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
		}
		else if ($updateReturn=="fail4") {
			$updateReturnMessage=__($guid, "Your request failed because some inputs did not meet a requirement for uniqueness.") ;	
		}
		else if ($updateReturn=="success0") {
			$updateReturnMessage=__($guid, "Your request was completed successfully.") ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $updateReturnMessage;
		print "</div>" ;
	} 
	
	//Check if school year specified
	$gibbonStaffJobOpeningID=$_GET["gibbonStaffJobOpeningID"] ;
	if ($gibbonStaffJobOpeningID=="") {
		print "<div class='error'>" ;
			print __($guid, "You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonStaffJobOpeningID"=>$gibbonStaffJobOpeningID); 
			$sql="SELECT * FROM gibbonStaffJobOpening WHERE gibbonStaffJobOpeningID=:gibbonStaffJobOpeningID" ;
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
			?>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/jobOpenings_manage_editProcess.php?gibbonStaffJobOpeningID=$gibbonStaffJobOpeningID" ?>">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr>
						<td> 
							<b><?php print __($guid, 'Type') ?> *</b><br/>
						</td>
						<td class="right">
							<select name="type" id="type" style="width: 302px">
								<?php
								print "<option value=\"Please select...\">" . __($guid, 'Please select...') . "</option>" ;
								print "<optgroup label='--" . __($guid, 'Basic') . "--'>" ;
									$selected="" ;
									if ($row["type"]=="Teaching") {
										$selected="selected" ;
									}
									print "<option $selected value=\"Teaching\">" . __($guid, 'Teaching') . "</option>" ;
									$selected="" ;
									if ($row["type"]=="Support") {
										$selected="selected" ;
									}
									print "<option $selected value=\"Support\">" . __($guid, 'Support') . "</option>" ;
								print "</optgroup>" ;
								print "<optgroup label='--" . __($guid, 'System Roles') . "--'>" ;
									try {
										$dataSelect=array(); 
										$sqlSelect="SELECT * FROM gibbonRole WHERE category='Staff' ORDER BY name" ;
										$resultSelect=$connection2->prepare($sqlSelect);
										$resultSelect->execute($dataSelect);
									}
									catch(PDOException $e) { }
									while ($rowSelect=$resultSelect->fetch()) {
										$selected="" ;
										if ($rowSelect["name"]==$row["type"]) {
											$selected="selected" ;
										}
										print "<option $selected value=\"" . $rowSelect["name"] . "\">" . __($guid, $rowSelect["name"]) . "</option>" ;
									}
								print "</optgroup>" ;
								?>
							</select>
							<script type="text/javascript">
								var type=new LiveValidation('type');
								type.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Job Title') ?> *</b><br/>
						</td>
						<td class="right">
							<input name="jobTitle" id="jobTitle" maxlength=100 value="<?php print htmlPrep($row["jobTitle"]) ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var jobTitle=new LiveValidation('jobTitle');
								jobTitle.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Opening Date') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, "Format:") . " " ; if ($_SESSION[$guid]["i18n"]["dateFormat"]=="") { print "dd/mm/yyyy" ; } else { print $_SESSION[$guid]["i18n"]["dateFormat"] ; } ?></i></span>
						</td>
						<td class="right">
							<input name="dateOpen" id="dateOpen" maxlength=10 value="<?php print dateConvertBack($guid, $row["dateOpen"]) ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var dateOpen=new LiveValidation('dateOpen');
								dateOpen.add(Validate.Presence);
								$(function() {
									$( "#dateOpen" ).datepicker();
								});
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Active') ?> *</b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<select name="active" id="active" style="width: 302px">
								<option <?php if ($row["active"]=="Y") { print "selected" ; } ?> value="Y"><?php print __($guid, 'Yes') ?></option>
								<option <?php if ($row["active"]=="N") { print "selected" ; } ?> value="N"><?php print __($guid, 'No') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td colspan=2> 
							<b><?php print __($guid, 'Body') ?> *</b>
							<?php 
							//Attempt to build a signature for the user
							print getEditor($guid,  TRUE, "description", $row["description"], 20, true, true, false, true ) ;
							?>
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
	}
}
?>