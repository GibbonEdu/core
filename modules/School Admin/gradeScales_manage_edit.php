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

if (isActionAccessible($guid, $connection2, "/modules/School Admin/gradeScales_manage_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/gradeScales_manage.php'>" . __($guid, 'Manage Grade Scales') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Edit Grade Scale') . "</div>" ;
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
	
	if (isset($_GET["deleteReturn"])) { $deleteReturn=$_GET["deleteReturn"] ; } else { $deleteReturn="" ; }
	$deleteReturnMessage="" ;
	$class="error" ;
	if (!($deleteReturn=="")) {
		if ($deleteReturn=="success0") {
			$deleteReturnMessage=__($guid, "Your request was completed successfully.") ;		
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $deleteReturnMessage;
		print "</div>" ;
	} 
	
	//Check if school year specified
	$gibbonScaleID=$_GET["gibbonScaleID"] ;
	if ($gibbonScaleID=="") {
		print "<div class='error'>" ;
			print __($guid, "You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonScaleID"=>$gibbonScaleID); 
			$sql="SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID" ;
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
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/gradeScales_manage_editProcess.php?gibbonScaleID=$gibbonScaleID" ?>">
			<table class='smallIntBorder fullWidth' cellspacing='0'>	
				<tr>
					<td style='width: 275px'> 
						<b><?php print __($guid, 'Name') ?> *</b><br/>
						<span class="emphasis small"><?php print __($guid, 'Must be unique for this school year.') ?></span>
					</td>
					<td class="right">
						<input name="name" id="name" maxlength=40 value="<?php if (isset($row["name"])) { print htmlPrep(__($guid, $row["name"])) ; } ?>" type="text" class="standardWidth">
						<script type="text/javascript">
							var name2=new LiveValidation('name');
							name2.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print __($guid, 'Short Name') ?> *</b><br/>
						<span class="emphasis small"></span>
					</td>
					<td class="right">
						<input name="nameShort" id="nameShort" maxlength=5 value="<?php if (isset($row["nameShort"])) { print htmlPrep(__($guid, $row["nameShort"])) ; } ?>" type="text" class="standardWidth">
						<script type="text/javascript">
							var nameShort=new LiveValidation('nameShort');
							nameShort.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print __($guid, 'Usage') ?> *</b><br/>
						<span class="emphasis small"><?php print __($guid, 'Brief description of how scale is used.') ?></span>
					</td>
					<td class="right">
						<input name="usage" id="usage" maxlength=50 value="<?php if (isset($row["usage"])) { print htmlPrep(__($guid, $row["usage"])) ; } ?>" type="text" class="standardWidth">
						<script type="text/javascript">
							var usage=new LiveValidation('usage');
							usage.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print __($guid, 'Active') ?> *</b><br/>
					</td>
					<td class="right">
						<select name="active" id="active" class="standardWidth">
							<option <?php if ($row["active"]=="Y") { print "selected" ; } ?> value="Y"><?php print __($guid, 'Yes') ?></option>
							<option <?php if ($row["active"]=="N") { print "selected" ; } ?> value="N"><?php print __($guid, 'No') ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<td> 
						<b>Numeric? *</b><br/>
						<span class="emphasis small">Does this scale use only numeric grades? Note, grade "Incomplete" is exempt.</span>
					</td>
					<td class="right">
						<select name="numeric" id="numeric" class="standardWidth">
							<option <?php if ($row["numeric"]=="Y") { print "selected" ; } ?> value="Y"><?php print __($guid, 'Yes') ?></option>
							<option <?php if ($row["numeric"]=="N") { print "selected" ; } ?> value="N"><?php print __($guid, 'No') ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print __($guid, 'Lowest Acceptable') ?></b><br/>
						<span class="emphasis small"><?php print __($guid, 'This is the lowest grade a student can get without being unsatisfactory.') ?></span>
					</td>
					<td class="right">
						<select name="lowestAcceptable" id="lowestAcceptable" class="standardWidth">
							<?php
							print "<option value=''></option>" ;
							try {
								$dataSelect=array("gibbonScaleID"=>$gibbonScaleID); 
								$sqlSelect="SELECT * FROM gibbonScaleGrade WHERE gibbonScaleID=:gibbonScaleID ORDER BY sequenceNumber" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) { }	
							while ($rowSelect=$resultSelect->fetch()) {
								
								$selected="" ;
								if ($rowSelect["sequenceNumber"]==$row["lowestAcceptable"]) {
									$selected="selected" ;
								}
								print "<option $selected value='" . $rowSelect["sequenceNumber"] . "'>" . $rowSelect["value"] . "</option>" ;
							}
							?>
						</select>
					</td>
				</tr>
				<tr>
					<td>
						<span class="emphasis small">* <?php print __($guid, "denotes a required field") ; ?></span>
					</td>
					<td class="right">
						<input name="gibbonScaleID" id="gibbonScaleID" value="<?php print $_GET["gibbonScaleID"] ?>" type="hidden">
						<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
						<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
					</td>
				</tr>
			</table>
			</form>
			<?php
			
			print "<h2>" ;
			print __($guid, "Edit Grades") ;
			print "</h2>" ;
			
			try {
				$data=array("gibbonScaleID"=>$gibbonScaleID); 
				$sql="SELECT * FROM gibbonScaleGrade WHERE gibbonScaleID=:gibbonScaleID ORDER BY sequenceNumber" ; 
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}

			print "<div class='linkTop'>" ;
			print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/gradeScales_manage_edit_grade_add.php&gibbonScaleID=$gibbonScaleID'>" .  __($guid, 'Add') . "<img style='margin-left: 5px' title='" . __($guid, 'Add') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.png'/></a>" ;
			print "</div>" ;
			
			if ($result->rowCount()<1) {
				print "<div class='error'>" ;
				print __($guid, "There are no records to display.") ;
				print "</div>" ;
			}
			else {
				print "<table cellspacing='0' style='width: 100%'>" ;
					print "<tr class='head'>" ;
						print "<th>" ;
							print __($guid, "Value") ;
						print "</th>" ;
						print "<th>" ;
							print __($guid, "Descriptor") ;
						print "</th>" ;
						print "<th>" ;
							print __($guid, "Sequence Number") ;
						print "</th>" ;
						print "<th>" ;
							print __($guid, "Is Default?") ;
						print "</th>" ;
						print "<th>" ;
							print __($guid, "Actions") ;
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
						
						//COLOR ROW BY STATUS!
						print "<tr class=$rowNum>" ;
							print "<td>" ;
								print __($guid, $row["value"]) ;
							print "</td>" ;
							print "<td>" ;
								print __($guid, $row["descriptor"]) ;
							print "</td>" ;
							print "<td>" ;
								print $row["sequenceNumber"] ;
							print "</td>" ;
							print "<td>" ;
								print ynExpander($guid, $row["isDefault"]) ;
							print "</td>" ;
							print "<td>" ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/gradeScales_manage_edit_grade_edit.php&gibbonScaleGradeID=" . $row["gibbonScaleGradeID"] . "&gibbonScaleID=$gibbonScaleID'><img title='" . __($guid, 'Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/gradeScales_manage_edit_grade_delete.php&gibbonScaleGradeID=" . $row["gibbonScaleGradeID"] . "&gibbonScaleID=$gibbonScaleID'><img title='" . __($guid, 'Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a> " ;
							print "</td>" ;
						print "</tr>" ;
						
						$count++ ;
					}
				print "</table>" ;
				
			}
		}
	}
}
?>