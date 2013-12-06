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

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Activities/report_activitySpread_rollGroup.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>Activity Spread by Roll Group</div>" ;
	print "</div>" ;
	print "<p>" ;
	print "This report shows the way student activity enrolments are spread over days and terms, with students grouped by roll group." ;
	print "</p>" ;
	
	print "<h2>" ;
	print "Choose Roll Group" ;
	print "</h2>" ;
	
	$gibbonRollGroupID=NULL ;
	if (isset($_GET["gibbonRollGroupID"])) {
		$gibbonRollGroupID=$_GET["gibbonRollGroupID"] ;
	}
	?>
	
	<form method="get" action="<? print $_SESSION[$guid]["absoluteURL"]?>/index.php">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<tr>
				<td> 
					<b>Roll Group *</b><br/>
				</td>
				<td class="right">
					<select style="width: 302px" name="gibbonRollGroupID">
						<?
						print "<option value=''></option>" ;
						try {
							$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
							$sqlSelect="SELECT * FROM gibbonRollGroup WHERE gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name" ;
							$resultSelect=$connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						}
						catch(PDOException $e) { }
						while ($rowSelect=$resultSelect->fetch()) {
							if ($gibbonRollGroupID==$rowSelect["gibbonRollGroupID"]) {
								print "<option selected value='" . $rowSelect["gibbonRollGroupID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
							}
							else {
								print "<option value='" . $rowSelect["gibbonRollGroupID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
							}
						}
						?>				
					</select>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Status*</b><br/>
				</td>
				<td class="right">
					<select style="width: 302px" name="status">
						<?
						print "<option value='Accepted'>Accepted</option>" ;
						$selected="" ;
						if ($_GET["status"]=="Registered") {
							$selected="selected" ;
						}
						print "<option $selected value='Registered'>Registered</option>" ;
						?>				
					</select>
				</td>
			</tr>
			<tr>
				<td colspan=2 class="right">
					<input type="hidden" name="q" value="/modules/<? print $_SESSION[$guid]["module"] ?>/report_activitySpread_rollGroup.php">
					<input type="submit" value="Submit">
				</td>
			</tr>
		</table>
	</form>
	<?
	
	if ($gibbonRollGroupID!="") {
		$output="" ;
		print "<h2>" ;
		print "Report Data" ;
		print "</h2>" ;
		
		try {
			$data=array("gibbonRollGroupID"=>$gibbonRollGroupID); 
			$sql="SELECT gibbonPerson.gibbonPersonID, surname, preferredName, name FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonRollGroupID=:gibbonRollGroupID ORDER BY surname, preferredName" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}

		if ($result->rowCount()<1) {
			print "<div class='error'>" ;
				print "There are no records to display in this report." ;
			print "</div>" ;
		}
		else {
			print "<table class='mini' cellspacing='0' style='width: 100%'>" ;
				print "<tr class='head'>" ;
					print "<th rowspan=2>" ;
						print "Roll Group" ;
					print "</th>" ;
					print "<th rowspan=2>" ;
						print "Student" ;
					print "</th>" ;
					//Get terms and days of week
					$terms=getTerms($connection2, $_SESSION[$guid]["gibbonSchoolYearID"]) ;
					$days=FALSE ;
					
					try {
						$dataDays=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
						$sqlDays="SELECT DISTINCT gibbonDaysOfWeek.* FROM gibbonDaysOfWeek JOIN gibbonActivitySlot ON (gibbonActivitySlot.gibbonDaysOfWeekID=gibbonDaysOfWeek.gibbonDaysOfWeekID) JOIN gibbonActivity ON (gibbonActivitySlot.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND schoolDay='Y' ORDER BY sequenceNumber" ;
						$resultDays=$connection2->prepare($sqlDays);
						$resultDays->execute($dataDays);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}

					while ($rowDays=$resultDays->fetch()) {
						$days=$days . $rowDays["gibbonDaysOfWeekID"] . "," ;
						$days=$days . $rowDays["nameShort"] . "," ;
					}
					if ($days!=FALSE) {
						$days=substr($days,0,(strlen($days)-1)) ;
						$days=explode(",", $days) ;
					}
					
					//Create columns
					$columns=array() ;
					$columnCount=0 ;
					for ($i=0; $i<count($terms); $i=$i+2) {
						print "<th colspan=" . count($days)/2 . ">" ;
							print $terms[($i+1)] ;
						print "</th>" ;		
					}
				print "</tr>" ;
				print "<tr class='head'>" ;
					for ($i=0; $i<count($terms); $i=$i+2) {
						for ($j=0; $j<count($days); $j=$j+2) {
							print "<th>" ;
								print $days[($j+1)] ;
								$columns[$columnCount][0]=$terms[$i] ;
								$columns[$columnCount][1]=$days[$j] ;
								$columnCount++ ;
							print "</th>" ;		
						}
					}
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
					$count++ ;
					
					//COLOR ROW BY STATUS!
					print "<tr class=$rowNum>" ;
						print "<td>" ;
							print $row["name"] ;
						print "</td>" ;
						print "<td>" ;
							//List activities seleted in title of student name
							try {
								$dataActivities=array("gibbonPersonID"=>$row["gibbonPersonID"], "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
								$sqlActivities="SELECT gibbonActivity.* FROM gibbonActivity JOIN gibbonActivityStudent ON (gibbonActivity.gibbonActivityID=gibbonActivityStudent.gibbonActivityID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND NOT status='Not Accepted' ORDER BY name" ;
								$resultActivities=$connection2->prepare($sqlActivities);
								$resultActivities->execute($dataActivities);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}

							
							$title="" ;
							while ($rowActivities=$resultActivities->fetch()) {
								$title=$title . $rowActivities["name"] . " | " ;
							}
							$title=substr($title,0,-3) ;
							print "<span title='$title'>" . formatName("", $row["preferredName"], $row["surname"], "Student", true) . "</span>" ;
						print "</td>" ;
						for ($i=0; $i<$columnCount; $i++) {
							print "<td>" ;
								try {
									$dataReg=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$row["gibbonPersonID"], "gibbonDaysOfWeekID"=>$columns[$i][1], "gibbonSchoolYearTermIDList"=>"%" . $columns[$i][0] . "%"); 
									if ($_GET["status"]=="Accepted") {
										$sqlReg="SELECT * FROM gibbonActivity JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) JOIN gibbonActivitySlot ON (gibbonActivitySlot.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND gibbonDaysOfWeekID=:gibbonDaysOfWeekID AND gibbonSchoolYearTermIDList LIKE :gibbonSchoolYearTermIDList AND status='Accepted'" ;
									}
									else {
										$sqlReg="SELECT * FROM gibbonActivity JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) JOIN gibbonActivitySlot ON (gibbonActivitySlot.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=:gibbonPersonID AND gibbonDaysOfWeekID=:gibbonDaysOfWeekID AND gibbonSchoolYearTermIDList LIKE :gibbonSchoolYearTermIDList AND NOT status='Not Accepted'" ;
									}
									$resultReg=$connection2->prepare($sqlReg);
									$resultReg->execute($dataReg);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}

								$title="" ;
								$notAccepted=FALSE ;
								while ($rowReg=$resultReg->fetch()) {
									$title.=$rowReg["name"] . ", " ;
									if ($rowReg["status"]!="Accepted") {
										$notAccepted=TRUE ;
									}
								}
								if ($title=="") {
									$title="No activities registered" ;
								}
								else {
									$title=substr($title,0,-2) ;
								}
								print "<span title='" . htmlPrep($title) . "'>" . $resultReg->rowCount() . "<span>" ;
								if ($notAccepted==TRUE AND $_GET["status"]=="Registered") {
									print "<span style='color: #cc0000' title='Some activities not accepted.'> *</span>" ;
								}
							print "</td>" ;
						}
						
					print "</tr>" ;
				}
				if ($count==0) {
					print "<tr class=$rowNum>" ;
						print "<td colspan=2>" ;
							print "There are no results in this report." ;
						print "</td>" ;
					print "</tr>" ;
				}
			print "</table>" ;
		}
	}
}
?>