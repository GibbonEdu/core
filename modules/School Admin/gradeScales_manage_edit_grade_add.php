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

if (isActionAccessible($guid, $connection2, "/modules/School Admin/gradeScales_manage_edit_grade_add.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	$gibbonScaleID=$_GET["gibbonScaleID"] ;
	
	if ($gibbonScaleID=="") {
		print "<div class='error'>" ;
			print __($guid, "You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonScaleID"=>$gibbonScaleID); 
			$sql="SELECT name FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID" ;
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
			$row=$result->fetch() ;
			
			print "<div class='trail'>" ;
			print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/gradeScales_manage.php'>" . __($guid, 'Manage Grade Scales') . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/gradeScales_manage_edit.php&gibbonScaleID=$gibbonScaleID'>" . __($guid, 'Edit Grade Scale') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Add Grade') . "</div>" ;
			print "</div>" ;
			
			if (isset($_GET["addReturn"])) { $addReturn=$_GET["addReturn"] ; } else { $addReturn="" ; }
			$addReturnMessage="" ;
			$class="error" ;
			if (!($addReturn=="")) {
				if ($addReturn=="fail0") {
					$addReturnMessage=__($guid, "Your request failed because you do not have access to this action.") ;	
				}
				else if ($addReturn=="fail2") {
					$addReturnMessage=__($guid, "Your request failed due to a database error.") ;	
				}
				else if ($addReturn=="fail3") {
					$addReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
				}
				else if ($addReturn=="fail4") {
					$addReturnMessage=__($guid, "Your request failed because some inputs did not meet a requirement for uniqueness.") ;	
				}
				else if ($addReturn=="fail5") {
					$addReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
				}
				else if ($addReturn=="success0") {
					$addReturnMessage=__($guid, "Your request was completed successfully.") ;	
					$class="success" ;
				}
				print "<div class='$class'>" ;
					print $addReturnMessage;
				print "</div>" ;
			} 
			?>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/gradeScales_manage_edit_grade_addProcess.php" ?>">
				<table class='smallIntBorder fullWidth' cellspacing='0'>	
					<tr>
						<td style='width: 275px'> 
							<b><?php print __($guid, 'Grade Scale') ?> *</b><br/>
							<span class="emphasis small"><?php print __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<input readonly name="name" id="name" maxlength=20 value="<?php print __($guid, $row["name"]) ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Value') ?> *</b><br/>
							<span class="emphasis small"><?php print __($guid, 'Must be unique for this grade scale.') ?></span>
						</td>
						<td class="right">
							<input name="value" id="value" maxlength=10 value="" type="text" class="standardWidth">
							<script type="text/javascript">
								var value=new LiveValidation('value');
								value.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Descriptor') ?> *</b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<input name="descriptor" id="descriptor" maxlength=50 value="" type="text" class="standardWidth">
							<script type="text/javascript">
								var descriptor=new LiveValidation('descriptor');
								descriptor.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Sequence Number') ?> *</b><br/>
							<span class="emphasis small"><?php print __($guid, 'Must be unique for this grade scale.') ?><br/></span>
						</td>
						<td class="right">
							<input name="sequenceNumber" id="sequenceNumber" maxlength=5 value="" type="text" class="standardWidth">
							<script type="text/javascript">
								var sequenceNumber=new LiveValidation('sequenceNumber');
								sequenceNumber.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Is Default?') ?> *</b><br/>
							<span class="emphasis small"><?php print __($guid, 'Preselects this option when using this grade scale in appropriate contexts.') ?><br/></span>
						</td>
						<td class="right">
							<select name="isDefault" id="isDefault" class="standardWidth">
								<option value="N"><?php print ynExpander($guid, 'N') ?></option>
								<option value="Y"><?php print ynExpander($guid, 'Y') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<span class="emphasis small">* <?php print __($guid, "denotes a required field") ; ?></span>
						</td>
						<td class="right">
							<input name="gibbonScaleID" id="gibbonScaleID" value="<?php print $gibbonScaleID ?>" type="hidden">
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