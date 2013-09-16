<?
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

session_start() ;

if (isActionAccessible($guid, $connection2, "/modules/School Admin/rollGroup_manage_add.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/rollGroup_manage.php&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "'>Manage Roll Groups</a> > </div><div class='trailEnd'>Add Roll Group</div>" ;
	print "</div>" ;
	
	$addReturn = $_GET["addReturn"] ;
	$addReturnMessage ="" ;
	$class="error" ;
	if (!($addReturn=="")) {
		if ($addReturn=="fail0") {
			$addReturnMessage ="Add failed because you do not have access to this action." ;	
		}
		else if ($addReturn=="fail2") {
			$addReturnMessage ="Add failed due to a database error." ;	
		}
		else if ($addReturn=="fail3") {
			$addReturnMessage ="Add failed because your inputs were invalid." ;	
		}
		else if ($addReturn=="fail4") {
			$addReturnMessage ="Update failed some values need to be unique but were not." ;	
		}
		else if ($addReturn=="success0") {
			$addReturnMessage ="Add was successful. You can add another record if you wish." ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $addReturnMessage;
		print "</div>" ;
	} 
	
	$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"]; 
	if ($gibbonSchoolYearID=="") {
		print "<div class='error'>" ;
			print "You have not specified a school year." ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID); 
			$sql="SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print "The specified school year does not exist." ;
			print "</div>" ;
		}
		else {
			$row=$result->fetch() ;
			?>
	
			<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/rollGroup_manage_addProcess.php" ?>">
				<table style="width: 100%">	
					<tr><td style="width: 30%"></td><td></td></tr>
					<tr>
						<td> 
							<b>School Year *</b><br/>
							<span style="font-size: 90%"><i>This value cannot be changed.</i></span>
						</td>
						<td class="right">
							<input readonly name="schoolYearName" id="schoolYearName" maxlength=20 value="<? print $row["name"] ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var schoolYearName = new LiveValidation('schoolYearName');
								schoolYearName.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Roll Group Name *</b><br/>
							<span style="font-size: 90%"><i>Needs to be unique in school year.</i></span>
						</td>
						<td class="right">
							<input name="name" id="name" maxlength=10 value="" type="text" style="width: 300px">
							<script type="text/javascript">
								var name = new LiveValidation('name');
								name.add(Validate.Presence);
							 </script>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Short Name *</b><br/>
							<span style="font-size: 90%"><i>Needs to be unique in school year.</i></span>
						</td>
						<td class="right">
							<input name="nameShort" id="nameShort" maxlength=5 value="<? print $row["nameShort"] ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var nameShort = new LiveValidation('nameShort');
								nameShort.add(Validate.Presence);
							 </script>
						</td>
					</tr>
					<tr>
						<td rowspan=3> 
							<b>Form Tutors</b><br/>
							<span style="font-size: 90%"><i>Up to 3 per form. The first-listed will be marked as "Main Tutor".</i></span>
						</td>
						<td class="right">
							<select style="width: 302px" name="gibbonPersonIDTutor">
								<?
								print "<option value=''></option>" ;
								try {
									$data=array(); 
									$sql="SELECT * FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) WHERE status='Full' ORDER BY surname, preferredName" ;
									$result=$connection2->prepare($sql);
									$result->execute($data);
								}
								catch(PDOException $e) { }
								while ($row=$result->fetch()) {
									print "<option value='" . $row["gibbonPersonID"] . "'>" . formatName("", htmlPrep($row["preferredName"]), htmlPrep($row["surname"]), "Staff", true, true) . "</option>" ;
								}
								?>				
							</select>
						</td>
					</tr>
					<tr>
						<td class="right">
							<select style="width: 302px" name="gibbonPersonIDTutor2">
								<?
								print "<option value=''></option>" ;
								try {
									$data=array(); 
									$sql="SELECT * FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) WHERE status='Full' ORDER BY surname, preferredName" ;
									$result=$connection2->prepare($sql);
									$result->execute($data);
								}
								catch(PDOException $e) { }
								while ($row=$result->fetch()) {
									print "<option value='" . $row["gibbonPersonID"] . "'>" . formatName("", htmlPrep($row["preferredName"]), htmlPrep($row["surname"]), "Staff", true, true) . "</option>" ;
								}
								?>				
							</select>
						</td>
					</tr>
					<tr>
						<td class="right">
							<select style="width: 302px" name="gibbonPersonIDTutor3">
								<?
								print "<option value=''></option>" ;
								try {
									$data=array(); 
									$sql="SELECT * FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) WHERE status='Full' ORDER BY surname, preferredName" ;
									$result=$connection2->prepare($sql);
									$result->execute($data);
								}
								catch(PDOException $e) { }
								while ($row=$result->fetch()) {
									print "<option value='" . $row["gibbonPersonID"] . "'>" . formatName("", htmlPrep($row["preferredName"]), htmlPrep($row["surname"]), "Staff", true, true) . "</option>" ;
								}
								?>				
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Location</b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<select name="gibbonSpaceID" id="gibbonSpaceID" style="width: 302px">
								<?
								print "<option value=''></option>" ;
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT * FROM gibbonSpace ORDER BY name" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								while ($rowSelect=$resultSelect->fetch()) {
									$selected="" ;
									if ($row["gibbonSpaceID"]==$rowSelect["gibbonSpaceID"]) {
										$selected="selected" ;
									}
									print "<option $selected value='" . $rowSelect["gibbonSpaceID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
								}
								?>				
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Next Roll Group</b><br/>
							<span style="font-size: 90%"><i>Set student progression on rollover.</i></span>
						</td>
						<td class="right">
							<?
							 $nextYear=getNextSchoolYearID($gibbonSchoolYearID, $connection2) ;
							 
							 if ($nextYear=="") {
								print "<div class='warning'>" ;
									print "The next school year cannot be determined, so this value cannot be set." ;
								print "</div>" ;
							 }
							 else {
								print "<select style='width: 302px' name='gibbonRollGroupIDNext'>" ;
									print "<option value=''></option>" ;
									try {
										$dataSelect=array("gibbonSchoolYearID"=>$nextYear); 
										$sqlSelect="SELECT * FROM gibbonRollGroup WHERE gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name" ;
										$resultSelect=$connection2->prepare($sqlSelect);
										$resultSelect->execute($dataSelect);
									}
									catch(PDOException $e) { }
									while ($rowSelect=$resultSelect->fetch()) {
										print "<option value='" . $rowSelect["gibbonRollGroupID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
									}			
								print "</select>" ;
							 }
							?>		
						</td>
					</tr>
					<tr>
						<td class="right" colspan=2>
							<input name="gibbonSchoolYearID" id="gibbonSchoolYearID" value="<? print $gibbonSchoolYearID ?>" type="hidden">
							<input type="hidden" name="address" value="<? print $_SESSION[$guid]["address"] ?>">
							<input type="reset" value="Reset"> <input type="submit" value="Submit">
						</td>
					</tr>
					<tr>
						<td class="right" colspan=2>
							<span style="font-size: 90%"><i>* denotes a required field</i></span>
						</td>
					</tr>
				</table>
			</form>
			<?
		}
	}
}
?>