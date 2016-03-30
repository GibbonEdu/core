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

if (isActionAccessible($guid, $connection2, "/modules/School Admin/space_manage_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/space_manage.php'>" . __($guid, 'Manage Facilities') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Edit Facility') . "</div>" ;
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
	$gibbonSpaceID=$_GET["gibbonSpaceID"] ;
	if ($gibbonSpaceID=="") {
		print "<div class='error'>" ;
			print __($guid, "You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonSpaceID"=>$gibbonSpaceID); 
			$sql="SELECT * FROM gibbonSpace WHERE gibbonSpaceID=:gibbonSpaceID" ;
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
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/space_manage_editProcess.php?gibbonSpaceID=" . $gibbonSpaceID ?>">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr>
						<td style='width: 275px'> 
							<b><?php print __($guid, 'Name') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'Must be unique.') ; ?></i></span>
						</td>
						<td class="right">
							<input name="name" id="name" maxlength=30 value="<?php print htmlPrep($row["name"]) ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var name2=new LiveValidation('name');
								name2.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<?php
					$types=getSettingByScope($connection2, "School Admin", "facilityTypes") ;
					$types=explode(",", $types) ;
					?>
					<tr>
						<td> 
							<b><?php print __($guid, 'Type') ?> *</b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<select name="type" id="type" style="width: 302px">
								<option value="Please select..."><?php print __($guid, 'Please select...') ?></option>
								<?php
								for ($i=0; $i<count($types); $i++) {
									$selected="" ;
									if ($row["type"]==$types[$i]) {
										$selected="selected" ;
									}
									?>
									<option <?php print $selected ?> value="<?php print trim($types[$i]) ?>"><?php print trim($types[$i]) ?></option>
								<?php
								}
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
							<b><?php print __($guid, 'User 1') ?></b>
						</td>
						<td class="right">
							<select style="width: 302px" name="gibbonPersonID1">
								<?php
								print "<option value=''></option>" ;
								try {
									$dataStaff=array(); 
									$sqlStaff="SELECT * FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) WHERE status='Full' ORDER BY surname, preferredName" ;
									$resultStaff=$connection2->prepare($sqlStaff);
									$resultStaff->execute($dataStaff);
								}
								catch(PDOException $e) { }
								while ($rowStaff=$resultStaff->fetch()) {
									print "<option" ; if ($row["gibbonPersonID1"]==$rowStaff["gibbonPersonID"]) { print " selected" ;} ; print " value='" . $rowStaff["gibbonPersonID"] . "'>" . formatName("", htmlPrep($rowStaff["preferredName"]), htmlPrep($rowStaff["surname"]), "Staff", true, true) . "</option>" ;
								}
								?>				
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'User 2') ?></b>
						</td>
						<td class="right">
							<select style="width: 302px" name="gibbonPersonID2">
								<?php
								print "<option value=''></option>" ;
								try {
									$dataStaff=array(); 
									$sqlStaff="SELECT * FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) WHERE status='Full' ORDER BY surname, preferredName" ;
									$resultStaff=$connection2->prepare($sqlStaff);
									$resultStaff->execute($dataStaff);
								}
								catch(PDOException $e) { }
								while ($rowStaff=$resultStaff->fetch()) {
									print "<option" ; if ($row["gibbonPersonID2"]==$rowStaff["gibbonPersonID"]) { print " selected" ;} ; print " value='" . $rowStaff["gibbonPersonID"] . "'>" . formatName("", htmlPrep($rowStaff["preferredName"]), htmlPrep($rowStaff["surname"]), "Staff", true, true) . "</option>" ;
								}
								?>				
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Capacity') ?></b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<input name="capacity" id="capacity" maxlength=5 value="<?php print htmlPrep($row["capacity"]) ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var capacity=new LiveValidation('capacity');
								capacity.add(Validate.Numericality);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Teacher\'s Computer') ?> *</b><br/>
						</td>
						<td class="right">
							<select name="computer" id="computer" style="width: 302px">
								<option <?php if ($row["computer"]=="N") {print "selected ";} ?>value="N">N</option>
								<option <?php if ($row["computer"]=="Y") {print "selected ";} ?>value="Y">Y</option>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Student Computers') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'How many are there?') ?></i></span>
						</td>
						<td class="right">
							<input name="computerStudent" id="computerStudent" maxlength=5 value="<?php print htmlPrep($row["computerStudent"]) ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var computerStudent=new LiveValidation('computerStudent');
								computerStudent.add(Validate.Numericality);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Projector') ?> *</b><br/>
						</td>
						<td class="right">
							<select name="projector" id="projector" style="width: 302px">
								<option <?php if ($row["projector"]=="N") {print "selected ";} ?>value="N">N</option>
								<option <?php if ($row["projector"]=="Y") {print "selected ";} ?>value="Y">Y</option>
							</select>
						</td>
					</tr>
					
					<tr>
						<td> 
							<b><?php print __($guid, 'TV') ?> *</b><br/>
						</td>
						<td class="right">
							<select name="tv" id="tv" style="width: 302px">
								<option <?php if ($row["tv"]=="N") {print "selected ";} ?>value="N">N</option>
								<option <?php if ($row["tv"]=="Y") {print "selected ";} ?>value="Y">Y</option>
							</select>
						</td>
					</tr>
					
					<tr>
						<td> 
							<b><?php print __($guid, 'DVD Player') ?> *</b><br/>
						</td>
						<td class="right">
							<select name="dvd" id="dvd" style="width: 302px">
								<option <?php if ($row["dvd"]=="N") {print "selected ";} ?>value="N">N</option>
								<option <?php if ($row["dvd"]=="Y") {print "selected ";} ?>value="Y">Y</option>
							</select>
						</td>
					</tr>
					
					<tr>
						<td> 
							<b><?php print __($guid, 'Hifi') ?> *</b><br/>
						</td>
						<td class="right">
							<select name="hifi" id="hifi" style="width: 302px">
								<option <?php if ($row["hifi"]=="N") {print "selected ";} ?>value="N">N</option>
								<option <?php if ($row["hifi"]=="Y") {print "selected ";} ?>value="Y">Y</option>
							</select>
						</td>
					</tr>
					
					<tr>
						<td> 
							<b><?php print __($guid, 'Speakers') ?> *</b><br/>
						</td>
						<td class="right">
							<select name="speakers" id="speakers" style="width: 302px">
								<option <?php if ($row["speakers"]=="N") {print "selected ";} ?>value="N">N</option>
								<option <?php if ($row["speakers"]=="Y") {print "selected ";} ?>value="Y">Y</option>
							</select>
						</td>
					</tr>
					
					<tr>
						<td> 
							<b><?php print __($guid, 'Interactive White Board') ?> *</b><br/>
						</td>
						<td class="right">
							<select name="iwb" id="iwb" style="width: 302px">
								<option <?php if ($row["iwb"]=="N") {print "selected ";} ?>value="N">N</option>
								<option <?php if ($row["iwb"]=="Y") {print "selected ";} ?>value="Y">Y</option>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Extension') ?></b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'Room\'s internal phone number.') ?></i></span>
						</td>
						<td class="right">
							<input name="phoneInternal" id="phoneInternal" maxlength=5 value="<?php print htmlPrep($row["phoneInternal"]) ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Phone Number') ?></b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'Room\'s external phone number.') ?></i></span>
						</td>
						<td class="right">
							<input name="phoneExternal" id="phoneExternal" maxlength=20 value="<?php print htmlPrep($row["phoneExternal"]) ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Comment') ?></b><br/>
						</td>
						<td class="right">
							<textarea name="comment" id="comment" rows=8 style="width: 300px"><?php print $row["comment"] ?></textarea>
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