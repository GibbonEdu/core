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

if (isActionAccessible($guid, $connection2, "/modules/School Admin/externalAssessments_manage_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/externalAssessments_manage.php'>" . __($guid, 'Manage External Assessments') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Edit External Assessment') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["return"])) { returnProcess($guid, $_GET["return"], null, null); }
	
	//Check if school year specified
	$gibbonExternalAssessmentID=$_GET["gibbonExternalAssessmentID"] ;
	if ($gibbonExternalAssessmentID=="") {
		print "<div class='error'>" ;
			print __($guid, "You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonExternalAssessmentID"=>$gibbonExternalAssessmentID); 
			$sql="SELECT * FROM gibbonExternalAssessment WHERE gibbonExternalAssessmentID=:gibbonExternalAssessmentID" ;
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
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/externalAssessments_manage_editProcess.php?gibbonExternalAssessmentID=$gibbonExternalAssessmentID" ?>">
			<table class='smallIntBorder fullWidth' cellspacing='0'>	
				<tr>
					<td style='width: 275px'> 
						<b><?php print __($guid, 'Name') ?> *</b><br/>
						<span class="emphasis small"><?php print __($guid, 'Must be unique.') ?></span>
					</td>
					<td class="right">
						<input name="name" id="name" maxlength=50 value="<?php if (isset($row["name"])) { print htmlPrep(__($guid, $row["name"])) ; } ?>" type="text" class="standardWidth">
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
						<input name="nameShort" id="nameShort" maxlength=10 value="<?php if (isset($row["nameShort"])) { print htmlPrep(__($guid, $row["nameShort"])) ; } ?>" type="text" class="standardWidth">
						<script type="text/javascript">
							var nameShort=new LiveValidation('nameShort');
							nameShort.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print __($guid, 'Description') ?> *</b><br/>
						<span class="emphasis small"><?php print __($guid, 'Brief description of how scale is used.') ?></span>
					</td>
					<td class="right">
						<input name="description" id="description" maxlength=50 value="<?php if (isset($row["description"])) { print __($guid, $row["description"]) ; } ?>" type="text" class="standardWidth">
						<script type="text/javascript">
							var description=new LiveValidation('description');
							description.add(Validate.Presence);
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
						<b><?php print __($guid, 'Allow File Upload') ; ?> *</b><br/>
						<span class="emphasis small"><?php print __($guid, 'Should the student record include the option of a file upload?') ; ?> </span>
					</td>
					<td class="right">
						<select name="allowFileUpload" id="allowFileUpload" class="standardWidth">
							<option <?php if ($row["allowFileUpload"]=="N") { print "selected" ; } ?> value="N"><?php print __($guid, 'No') ?></option>
							<option <?php if ($row["allowFileUpload"]=="Y") { print "selected" ; } ?> value="Y"><?php print __($guid, 'Yes') ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<td>
						<span class="emphasis small">* <?php print __($guid, "denotes a required field") ; ?></span>
					</td>
					<td class="right">
						<input name="gibbonExternalAssessmentID" id="gibbonExternalAssessmentID" value="<?php print $_GET["gibbonExternalAssessmentID"] ?>" type="hidden">
						<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
						<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
					</td>
				</tr>
			</table>
			</form>
			<?php
			
			print "<h2>" ;
			print __($guid, "Edit Fields") ;
			print "</h2>" ;
			
			try {
				$data=array("gibbonExternalAssessmentID"=>$gibbonExternalAssessmentID); 
				$sql="SELECT * FROM gibbonExternalAssessmentField WHERE gibbonExternalAssessmentID=:gibbonExternalAssessmentID ORDER BY category, `order`" ; 
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}

			print "<div class='linkTop'>" ;
			print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/externalAssessments_manage_edit_field_add.php&gibbonExternalAssessmentID=$gibbonExternalAssessmentID'>" .  __($guid, 'Add') . "<img style='margin-left: 5px' title='" . __($guid, 'Add') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.png'/></a>" ;
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
							print __($guid, "Name") ;
						print "</th>" ;
						print "<th>" ;
							print __($guid, "Category") ;
						print "</th>" ;
						print "<th>" ;
							print __($guid, "Order") ;
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
								print __($guid, $row["name"]) ;
							print "</td>" ;
							print "<td>" ;
								print $row["category"] ;
							print "</td>" ;
							print "<td>" ;
								print $row["order"] ;
							print "</td>" ;
							print "<td>" ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/externalAssessments_manage_edit_field_edit.php&gibbonExternalAssessmentFieldID=" . $row["gibbonExternalAssessmentFieldID"] . "&gibbonExternalAssessmentID=$gibbonExternalAssessmentID'><img title='" . __($guid, 'Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/externalAssessments_manage_edit_field_delete.php&gibbonExternalAssessmentFieldID=" . $row["gibbonExternalAssessmentFieldID"] . "&gibbonExternalAssessmentID=$gibbonExternalAssessmentID'><img title='" . __($guid, 'Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a> " ;
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