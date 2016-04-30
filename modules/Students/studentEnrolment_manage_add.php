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

if (isActionAccessible($guid, $connection2, "/modules/Students/studentEnrolment_manage_add.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Students/studentEnrolment_manage.php&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "'>" . __($guid, 'Student Enrolment') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Add Student Enrolment') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["return"])) { returnProcess($guid, $_GET["return"], null, null); }
	
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
				print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Students/studentEnrolment_manage.php&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search'>" . __($guid, 'Back to Search Results') . "</a>" ;
			print "</div>" ;
		}
		?>
		<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/studentEnrolment_manage_addProcess.php?gibbonSchoolYearID=$gibbonSchoolYearID&search=$search" ?>">
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
						<b><?php print __($guid, 'Student') ?> *</b><br/>
						<span class="emphasis small"></span>
					</td>
					<td class="right">
						<select name="gibbonPersonID" id="gibbonPersonID" class="standardWidth">
							<?php
							print "<option value='Please select...'>" . __($guid, 'Please select...') . "</option>" ;
							try {
								$dataSelect=array(); 
								$sqlSelect="SELECT gibbonPersonID, preferredName, surname, username FROM gibbonPerson WHERE gibbonPerson.status='Full' ORDER BY surname, preferredName" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) { }
							while ($rowSelect=$resultSelect->fetch()) {
								print "<option value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . " (" . $rowSelect["username"] . ")</option>" ;
							}
							?>				
						</select>
						<script type="text/javascript">
							var gibbonPersonID=new LiveValidation('gibbonPersonID');
							gibbonPersonID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print __($guid, 'Year Group') ?> *</b><br/>
						<span style="font-size: 90%"></span>
					</td>
					<td class="right">
						<select name="gibbonYearGroupID" id="gibbonYearGroupID" class="standardWidth">
							<?php
							print "<option value='Please select...'>" . __($guid, 'Please select...') . "</option>" ;
							try {
								$dataSelect=array(); 
								$sqlSelect="SELECT gibbonYearGroupID, name FROM gibbonYearGroup ORDER BY sequenceNumber" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) { }
							while ($rowSelect=$resultSelect->fetch()) {
								print "<option value='" . $rowSelect["gibbonYearGroupID"] . "'>" . htmlPrep(__($guid, $rowSelect["name"])) . "</option>" ;
							}
							?>				
						</select>
						<script type="text/javascript">
							var gibbonYearGroupID=new LiveValidation('gibbonYearGroupID');
							gibbonYearGroupID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print __($guid, 'Roll Group') ?> *</b><br/>
						<span style="font-size: 90%"></span>
					</td>
					<td class="right">
						<select name="gibbonRollGroupID" id="gibbonRollGroupID" class="standardWidth">
							<?php
							print "<option value='Please select...'>" . __($guid, 'Please select...') . "</option>" ;
							try {
								$dataSelect=array("gibbonSchoolYearID"=>$gibbonSchoolYearID); 
								$sqlSelect="SELECT gibbonRollGroupID, name FROM gibbonRollGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) { }
							while ($rowSelect=$resultSelect->fetch()) {
								print "<option value='" . $rowSelect["gibbonRollGroupID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
							}
							?>				
						</select>
						<script type="text/javascript">
							var gibbonRollGroupID=new LiveValidation('gibbonRollGroupID');
							gibbonRollGroupID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php print __($guid, 'Select something!') ?>"});
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print __($guid, 'Roll Order') ?></b><br/>
						<span class="emphasis small"><?php print __($guid, 'Must be unique to roll group if set.') ?></span>
					</td>
					<td class="right">
						<input name="rollOrder" id="rollOrder" maxlength=2 value="" type="text" class="standardWidth">
						<script type="text/javascript">
							var rollOrder=new LiveValidation('rollOrder');
							rollOrder.add(Validate.Numericality);
						</script>
					</td>
				</tr>
				<tr>
					<td>
						<span class="emphasis small">* <?php print __($guid, "denotes a required field") ; ?></span>
					</td>
					<td class="right">
						<input name="gibbonStudentEnrolmentID" id="gibbonStudentEnrolmentID" value="<?php print $gibbonStudentEnrolmentID ?>" type="hidden">
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