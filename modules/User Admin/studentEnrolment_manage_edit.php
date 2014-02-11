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

@session_start() ;

if (isActionAccessible($guid, $connection2, "/modules/User Admin/studentEnrolment_manage_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/studentEnrolment_manage.php&gibbonSchoolYearID=" . $_GET["gibbonSchoolYearID"] . "'>Student Enrolment</a> > </div><div class='trailEnd'>Edit Student Enrolment</div>" ;
	print "</div>" ;
	
	if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
	$updateReturnMessage ="" ;
	$class="error" ;
	if (!($updateReturn=="")) {
		if ($updateReturn=="fail0") {
			$updateReturnMessage ="Your request failed because you do not have access to this action." ;	
		}
		else if ($updateReturn=="fail1") {
			$updateReturnMessage ="Your request failed because your inputs were invalid." ;	
		}
		else if ($updateReturn=="fail2") {
			$updateReturnMessage ="Your request failed due to a database error." ;	
		}
		else if ($updateReturn=="fail3") {
			$updateReturnMessage ="Your request failed because your inputs were invalid." ;	
		}
		else if ($updateReturn=="fail4") {
			$updateReturnMessage ="Your request failed because your inputs were invalid." ;	
		}
		else if ($updateReturn=="success0") {
			$updateReturnMessage ="Your request was successful. ." ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $updateReturnMessage;
		print "</div>" ;
	} 
	
	//Check if school year specified
	$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"] ;
	$gibbonStudentEnrolmentID=$_GET["gibbonStudentEnrolmentID"] ;
	$search=$_GET["search"] ;
	if ($gibbonStudentEnrolmentID=="" OR $gibbonSchoolYearID=="") {
		print "<div class='error'>" ;
			print "You have not specified a student or school year." ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "gibbonStudentEnrolmentID"=>$gibbonStudentEnrolmentID); 
			$sql="SELECT gibbonRollGroup.gibbonRollGroupID, gibbonYearGroup.gibbonYearGroupID,gibbonStudentEnrolmentID, surname, preferredName, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, dateStart, dateEnd, gibbonPerson.gibbonPersonID FROM gibbonPerson, gibbonStudentEnrolment, gibbonYearGroup, gibbonRollGroup WHERE (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) AND (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) AND (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonStudentEnrolmentID=:gibbonStudentEnrolmentID ORDER BY surname, preferredName" ; 
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print "The specified student cannot be found." ;
			print "</div>" ;
		}
		else {
			//Let's go!
			$row=$result->fetch() ;
			
			if ($search!="") {
				print "<div class='linkTop'>" ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/studentEnrolment_manage.php&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search'>Back to Search Results</a>" ;
				print "</div>" ;
			}
			?>
			
			<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/studentEnrolment_manage_editProcess.php?gibbonSchoolYearID=$gibbonSchoolYearID&search=$search" ?>">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr>
						<td> 
							<b>School Year *</b><br/>
							<span style="font-size: 90%"><i>This value cannot be changed.</i></span>
						</td>
						<td class="right">
							<?
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
							<input readonly name="yearName" id="yearName" maxlength=20 value="<? print htmlPrep($yearName) ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var yearName=new LiveValidation('yearName');
								yearName.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Student *</b><br/>
							<span style="font-size: 90%"><i>This value cannot be changed.</i></span>
						</td>
						<td class="right">
							<input readonly name="participant" id="participant" maxlength=200 value="<? print formatName("", htmlPrep($row["preferredName"]), htmlPrep($row["surname"]), "Student") ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var participant=new LiveValidation('participant');
								participant.add(Validate.Presence);
							 </script>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Year Group *</b><br/>
							<span style="font-size: 90%"></span>
						</td>
						<td class="right">
							<select name="gibbonYearGroupID" id="gibbonYearGroupID" style="width: 302px">
								<?
								print "<option value='Please select...'>Please select...</option>" ;
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT gibbonYearGroupID, name FROM gibbonYearGroup ORDER BY sequenceNumber" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								while ($rowSelect=$resultSelect->fetch()) {
									$selected="" ;
									if ($row["gibbonYearGroupID"]==$rowSelect["gibbonYearGroupID"]) {
										$selected="selected" ;
									}
									print "<option $selected value='" . $rowSelect["gibbonYearGroupID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
								}
								?>				
							</select>
							<script type="text/javascript">
								var gibbonYearGroupID=new LiveValidation('gibbonYearGroupID');
								gibbonYearGroupID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "Select something!"});
							 </script>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Roll Group *</b><br/>
							<span style="font-size: 90%"></span>
						</td>
						<td class="right">
							<select name="gibbonRollGroupID" id="gibbonRollGroupID" style="width: 302px">
								<?
								print "<option value='Please select...'>Please select...</option>" ;
								try {
									$dataSelect=array("gibbonSchoolYearID"=>$gibbonSchoolYearID); 
									$sqlSelect="SELECT gibbonRollGroupID, name FROM gibbonRollGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								while ($rowSelect=$resultSelect->fetch()) {
									$selected="" ;
									if ($row["gibbonRollGroupID"]==$rowSelect["gibbonRollGroupID"]) {
										$selected="selected" ;
									}
									print "<option $selected value='" . $rowSelect["gibbonRollGroupID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
								}
								?>				
							</select>
							<script type="text/javascript">
								var gibbonRollGroupID=new LiveValidation('gibbonRollGroupID');
								gibbonRollGroupID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "Select something!"});
							 </script>
						</td>
					</tr>
					<tr>
						<td> 
							<b>School History</b><br/>
							<span style="font-size: 90%"></span>
						</td>
						<td class="right">
							<?
							if ($row["dateStart"]!="") {
								print "<u>Start Date</u>: " . dateConvertBack($row["dateStart"]) . "</br>" ;
							}
							try {
								$dataSelect=array("gibbonPersonID"=>$row["gibbonPersonID"]); 
								$sqlSelect="SELECT gibbonRollGroup.name AS rollGroup, gibbonSchoolYear.name AS schoolYear FROM gibbonStudentEnrolment JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) JOIN gibbonSchoolYear ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonPersonID=:gibbonPersonID ORDER BY gibbonStudentEnrolment.gibbonSchoolYearID" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							while ($rowSelect=$resultSelect->fetch()) {
								print "<u>" . $rowSelect["schoolYear"] . "</u>: " . $rowSelect["rollGroup"] . "<br/>" ;
							}
							if ($row["dateEnd"]!="") {
								print "<u>Edn Date</u>: " . dateConvertBack($row["dateEnd"]) . "</br>" ;
							}
							?>
						</td>
					</tr>
					<tr>
						<td>
							<span style="font-size: 90%"><i>* denotes a required field</i></span>
						</td>
						<td class="right">
							<input name="gibbonStudentEnrolmentID" id="gibbonStudentEnrolmentID" value="<? print $gibbonStudentEnrolmentID ?>" type="hidden">
							<input type="hidden" name="address" value="<? print $_SESSION[$guid]["address"] ?>">
							<input type="submit" value="Submit">
						</td>
					</tr>
				</table>
			</form>
			<?
		}
	}
}
?>