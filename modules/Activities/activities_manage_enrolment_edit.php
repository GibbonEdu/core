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

if (isActionAccessible($guid, $connection2, "/modules/Activities/activities_manage_enrolment_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Activities/activities_manage.php'>Manage Activities</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Activities/activities_manage_enrolment.php&gibbonActivityID=" . $_GET["gibbonActivityID"] . "&search=" . $_GET["search"] . "'>Activity Enrolment</a> > </div><div class='trailEnd'>Edit Enrolment</div>" ;
	print "</div>" ;

	if (isset($_GET["return"])) { returnProcess($_GET["return"], null, null); }
	
	//Check if school year specified
	$gibbonActivityID=$_GET["gibbonActivityID"] ;
	$gibbonPersonID=$_GET["gibbonPersonID"] ;
	if ($gibbonPersonID=="" OR $gibbonActivityID=="") {
		print "<div class='error'>" ;
			print __($guid, "You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonActivityID"=>$gibbonActivityID, "gibbonPersonID"=>$gibbonPersonID); 
			$sql="SELECT gibbonActivity.*, gibbonActivityStudent.*, surname, preferredName FROM gibbonActivity JOIN gibbonActivityStudent ON (gibbonActivity.gibbonActivityID=gibbonActivityStudent.gibbonActivityID) JOIN gibbonPerson ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonActivityStudent.gibbonActivityID=:gibbonActivityID AND gibbonActivityStudent.gibbonPersonID=:gibbonPersonID" ;
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
			if ($_GET["search"]!="") {
				print "<div class='linkTop'>" ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Activities/activities_manage.php&search=" .$_GET["search"] . "'>" . __($guid, 'Back to Search Results') . "</a>" ;
				print "</div>" ;
			}
			?>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/activities_manage_enrolment_editProcess.php?gibbonActivityID=$gibbonActivityID&gibbonPersonID=$gibbonPersonID&search=" . $_GET["search"] ?>">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr>
						<td style='width: 275px'> 
							<b><?php print __($guid, 'Activity') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'This value cannot be changed.') ?></i></span>
						</td>
						<td class="right">
							<input readonly name="yearName" id="yearName" maxlength=20 value="<?php print htmlPrep($row["name"]) ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Student') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print __($guid, 'This value cannot be changed.') ?></i></span>
						</td>
						<td class="right">
							<input readonly name="courseName" id="courseName" maxlength=20 value="<?php print formatName("", htmlPrep($row["preferredName"]), htmlPrep($row["surname"]), "Student") ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php print __($guid, 'Status') ?> *</b><br/>
						</td>
						<td class="right">
							<select style="width: 302px" name="status">
								<option <?php if ($row["status"]=="Accepted") { print "selected ";} ?>value="Accepted"><?php print __($guid, 'Accepted') ?></option>
								<?php
								$enrolment=getSettingByScope($connection2, "Activities", "enrolmentType") ;
								if ($enrolment=="Competitive") {
									?>
									<option <?php if ($row["status"]=="Waiting List") { print "selected ";} ?>value="Waiting List"><?php print __($guid, 'Waiting List') ?></option>
									<?php
								}
								else {
									?>
									<option <?php if ($row["status"]=="Pending") { print "selected ";} ?>value="Pending"><?php print __($guid, 'Pending') ?></option>
									<?php
								}
								?>
								<option <?php if ($row["status"]=="Not Accepted") { print "selected ";} ?>value="Not Accepted"><?php print __($guid, 'Not Accepted') ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<td>
							<span style="font-size: 90%"><i>* <?php print __($guid, "denotes a required field") ; ?></i></span>
						</td>
						<td class="right">
							<input name="gibbonPersonID" id="gibbonPersonID" value="<?php print $gibbonPersonID ?>" type="hidden">
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