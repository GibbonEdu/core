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

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Activities/activities_manage_enrolment_add.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Activities/activities_manage.php'>Manage Activities</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Activities/activities_manage_enrolment.php&gibbonActivityID=" . $_GET["gibbonActivityID"] . "'>Activity Enrolment</a> > </div><div class='trailEnd'>Add Student</div>" ;
	print "</div>" ;
	
	$addReturn = $_GET["addReturn"] ;
	$addReturnMessage ="" ;
	$class="error" ;
	if (!($addReturn=="")) {
		if ($addReturn=="fail0") {
			$addReturnMessage ="Add failed because you do not have access to this action." ;	
		}
		else if ($addReturn=="fail1") {
			$addReturnMessage ="Add failed due to a lack of students." ;	
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
		else if ($addReturn=="fail5") {
			$addReturnMessage ="Update failed some values need to be unique but were not." ;	
		}
		else if ($addReturn=="success0") {
			$addReturnMessage ="Add was successful." ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $addReturnMessage;
		print "</div>" ;
	} 
	
	$gibbonActivityID=$_GET["gibbonActivityID"] ;
	
	if ($gibbonActivityID=="") {
		print "<div class='error'>" ;
			print "You have not specified a school year or course." ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonActivityID"=>$gibbonActivityID); 
			$sql="SELECT * FROM gibbonActivity WHERE gibbonActivityID=:gibbonActivityID" ;
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
			$dateType=getSettingByScope($connection2, "Activities", "dateType") ;
			?>
			<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/activities_manage_enrolment_addProcess.php?gibbonActivityID=$gibbonActivityID" ?>">
				<table style="width: 100%">	
					<tr><td style="width: 30%"></td><td></td></tr>
					<tr>
						<td> 
							<b>Name</b><br/>
						</td>
						<td class="right">
							<input readonly name="name" id="name" maxlength=20 value="<? print $row["name"] ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<?
					if ($dateType=="Date") {
						?>
						<tr>
							<td> 
								<b>Listing Dates</b><br/>
							</td>
							<td class="right">
								<input readonly name="name" id="name" maxlength=20 value="<? print dateConvertBack($row["listingStart"]) . "-" . dateConvertBack($row["listingEnd"]) ?>" type="text" style="width: 300px">
							</td>
						</tr>
						<tr>
							<td> 
								<b>Program Dates</b><br/>
							</td>
							<td class="right">
								<input readonly name="name" id="name" maxlength=20 value="<? print dateConvertBack($row["programStart"]) . "-" . dateConvertBack($row["programEnd"]) ?>" type="text" style="width: 300px">
							</td>
						</tr>
						<?
					}
					else {
						?>
						<tr>
							<td> 
								<b>Terms</b><br/>
							</td>
							<td class="right">
								<?
								$terms=getTerms($connection2, $_SESSION[$guid]["gibbonSchoolYearID"], true) ;
								$termList="" ;
								for ($i=0; $i<count($terms); $i=$i+2) {
									if (is_numeric(strpos($row["gibbonSchoolYearTermIDList"], $terms[$i]))) {
										$termList.=$terms[($i+1)] . ", " ;
									}
								}
								if ($termList=="") {
									$termList="-, " ;
								}
								?>
								<input readonly name="name" id="name" maxlength=20 value="<? print substr($termList,0,-2) ?>" type="text" style="width: 300px">
							</td>
						</tr>
						<?
					}
					?>
					<tr>
						<td> 
							<b>Students *</b><br/>
							<span style="font-size: 90%"><i>Use Control and/or Shift to select multiple.</i></span>
						</td>
						<td class="right">
							<select name="Members[]" id="Members[]" multiple style="width: 302px; height: 150px">
								<optgroup label='--Enrolable Students--'>
								<?
								try {
									$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
									$sqlSelectWhere="" ;
									if ($row["gibbonYearGroupIDList"]!="") {
										$years=explode(",", $row["gibbonYearGroupIDList"]);
										for ($i=0; $i<count($years); $i++) {
											if ($i==0) {
												$dataSelect[$years[$i]]=$years[$i] ;
												$sqlSelectWhere.="AND (gibbonYearGroupID=:" . $years[$i] ;
											}
											else {
												$dataSelect[$years[$i]]=$years[$i] ;
												$sqlSelectWhere.=" OR gibbonYearGroupID=:" . $years[$i] ;
											}
											
											if ($i==(count($years)-1)) {
												$sqlSelectWhere.=")" ;
											}
										}
									}
									else {
										$sqlSelectWhere=" FALSE" ;
									}
									$sqlSelect="SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS name FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID AND status='FULL' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID $sqlSelectWhere ORDER BY name, surname, preferredName" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								
								while ($rowSelect=$resultSelect->fetch()) {
									print "<option value='" . $rowSelect["gibbonPersonID"] . "'>" . htmlPrep($rowSelect["name"]) . " - " . formatName("", $rowSelect["preferredName"], $rowSelect["surname"], "Student", true) . "</option>" ;
								}
								?>
								</optgroup>
								<optgroup label='--All Users--'>
								<?
								try {
									$dataSelect=array(); 
									$sqlSelect="SELECT gibbonPersonID, surname, preferredName, status FROM gibbonPerson WHERE status='Full' OR status='Expected' ORDER BY surname, preferredName" ;
									$resultSelect=$connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								}
								catch(PDOException $e) { }
								while ($rowSelect=$resultSelect->fetch()) {
									$expected="" ;
									if ($rowSelect["status"]=="Expected") {
										$expected=" (Expected)" ;
									}
									print "<option value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . "$expected</option>" ;
								}
								?>
								</optgroup>
							</select>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Status *</b><br/>
						</td>
						<td class="right">
							<select name="status" id="status" style="width: 302px">
								<option value="Accepted">Accepted</option>
								<?
								$enrolment=getSettingByScope($connection2, "Activities", "enrolmentType") ;
								if ($enrolment=="Competitive") {
									print "<option value='Waiting List'>Waiting List</option>" ;
								}
								else {
									print "<option value='Pending'>Pending</option>" ;
								}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<td class="right" colspan=2>
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