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

if (isActionAccessible($guid, $connection2, "/modules/School Admin/gradeScales_manage_edit_grade_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Check if school year specified
	$gibbonScaleGradeID=$_GET["gibbonScaleGradeID"] ;
	$gibbonScaleID=$_GET["gibbonScaleID"] ;
	if ($gibbonScaleGradeID=="" OR $gibbonScaleID=="") {
		print "<div class='error'>" ;
			print __($guid, "You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonScaleID"=>$gibbonScaleID, "gibbonScaleGradeID"=>$gibbonScaleGradeID); 
			$sql="SELECT gibbonScaleGrade.*, gibbonScale.name AS name FROM gibbonScale JOIN gibbonScaleGrade ON (gibbonScale.gibbonScaleID=gibbonScaleGrade.gibbonScaleID) WHERE gibbonScaleGrade.gibbonScaleID=:gibbonScaleID AND gibbonScaleGrade.gibbonScaleGradeID=:gibbonScaleGradeID" ;
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
			
			print "<div class='trail'>" ;
			print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/gradeScales_manage.php'>" . __($guid, 'Manage Grade Scales') . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/gradeScales_manage_edit.php&gibbonScaleID=$gibbonScaleID'>" . __($guid, 'Edit Grade Scale') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Edit Grade') . "</div>" ;
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
			?>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/gradeScales_manage_edit_grade_editProcess.php?gibbonScaleGradeID=$gibbonScaleGradeID&gibbonScaleID=$gibbonScaleID" ?>">
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
							<input name="value" id="value" maxlength=10 value="<?php if (isset($row["value"])) { print __($guid, $row["value"]) ; } ?>" type="text" class="standardWidth">
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
							<input name="descriptor" id="descriptor" maxlength=50 value="<?php if (isset($row["descriptor"])) { print htmlPrep(__($guid, $row["descriptor"])) ; } ?>" type="text" class="standardWidth">
							<script type="text/javascript">
								var descriptor=new LiveValidation('descriptor');
								descriptor.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Sequence Number') ?> *</b><br/>
							<span class="emphasis small"><?php print __($guid, 'Must be unique for this grade scale.') ?></span>
						</td>
						<td class="right">
							<input name="sequenceNumber" id="sequenceNumber" maxlength=5 value="<?php if (isset($row["sequenceNumber"])) { print $row["sequenceNumber"] ; } ?>" type="text" class="standardWidth">
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
								<option <?php if ($row["isDefault"]=="N") { print "selected" ; } ?> value="N"><?php print ynExpander($guid, 'N') ?></option>
								<option <?php if ($row["isDefault"]=="Y") { print "selected" ; } ?> value="Y"><?php print ynExpander($guid, 'Y') ?></option>
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