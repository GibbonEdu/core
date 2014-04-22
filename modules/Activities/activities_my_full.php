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

if (isActionAccessible($guid, $connection2, "/modules/Activities/activities_my_full.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "Your request failed because you do not have access to this action." ;
	print "</div>" ;
}
else {
	//Get action with highest precendence
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print _("The highest grouped action cannot be determined.") ;
		print "</div>" ;
	}
	else {
		//Proceed!
		//Get class variable
		$gibbonActivityID=$_GET["gibbonActivityID"] ;
		if ($gibbonActivityID=="") {
			print "<div class='warning'>" ;
				print "Activity has not been specified ." ;
			print "</div>" ;
		}
		//Check existence of and access to this class.
		else {
			$today=date("Y-m-d") ;
			
			try {
				$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonActivityID"=>$gibbonActivityID); 
				$sql="SELECT * FROM gibbonActivity WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' AND gibbonActivityID=:gibbonActivityID" ; 
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			
			if ($result->rowCount()!=1) {
				print "<div class='warning'>" ;
					print _("The selected record does not exist, or you do not have access to it.") ;
				print "</div>" ;
			}
			else {
				$row=$result->fetch() ;
				//Should we show date as term or date?
				$dateType=getSettingByScope( $connection2, "Activities", "dateType" ) ; 
				
				print "<h1>" ;
					print $row["name"] . "<br/>" ;
					$options=getSettingByScope($connection2, "Activities", "activityTypes") ;
					if ($options!="") {
						print "<div style='padding-top: 5px; font-size: 65%; font-style: italic'>" ;
							print trim($row["type"]) ;
						print "</div>" ;
					}
				print "</h1>" ;
				
				print "<table class='blank' cellspacing='0' style='width: 550px; float: left;'>" ;
					print "<tr>" ;
						if ($dateType!="Date") {
							print "<td style='width: 33%; vertical-align: top'>" ;
								print "<span style='font-size: 115%; font-weight: bold'>Terms</span><br/>" ;
								$terms=getTerms($connection2, $_SESSION[$guid]["gibbonSchoolYearID"]) ;
								$termList="" ;
								for ($i=0; $i<count($terms); $i=$i+2) {
									if (is_numeric(strpos($row["gibbonSchoolYearTermIDList"], $terms[$i]))) {
										$termList.=$terms[($i+1)] . ", " ;
									}
								}
								if ($termList=="") {
									print "<i>NA</i>" ;
								}
								else {
									print substr($termList,0,-2) ;
								}
							print "</td>" ;
						}
						else {
							print "<td style='width: 33%; vertical-align: top'>" ;
								print "<span style='font-size: 115%; font-weight: bold'>Start Date</span><br/>" ;
								print dateConvertBack($guid, $row["programStart"]) ;
							print "</td>" ;
							print "<td style='width: 33%; vertical-align: top'>" ;
								print "<span style='font-size: 115%; font-weight: bold'>End Date</span><br/>" ;
								print dateConvertBack($guid, $row["programEnd"]) ;
							print "</td>" ;
						}
						print "<td style='width: 33%; vertical-align: top'>" ;
							print "<span style='font-size: 115%; font-weight: bold'>Year Groups</span><br/>" ;
							print getYearGroupsFromIDList($connection2, $row["gibbonYearGroupIDList"]) ;
						print "</td>" ;
					print "</tr>" ;
					print "<tr>" ;
						print "<td style='padding-top: 15px; width: 33%; vertical-align: top'>" ;
							print "<span style='font-size: 115%; font-weight: bold'>Payment</span><br/>" ;
							if ($row["payment"]==0) {
								print "<i>None</i>" ;
							}
							else {
								print "$" . $row["payment"] ;
							}
						print "</td>" ;
						print "<td style='padding-top: 15px; width: 33%; vertical-align: top'>" ;
							print "<span style='font-size: 115%; font-weight: bold'>Maximum Participants</span><br/>" ;
							print $row["maxParticipants"] ;
						print "</td>" ;
						print "<td style='padding-top: 15px; width: 33%; vertical-align: top'>" ;
							print "<span style='font-size: 115%; font-weight: bold'>Staff</span><br/>" ;
							try {
								$dataStaff=array("gibbonActivityID"=>$row["gibbonActivityID"]); 
								$sqlStaff="SELECT title, preferredName, surname, role FROM gibbonActivityStaff JOIN gibbonPerson ON (gibbonActivityStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonActivityID=:gibbonActivityID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') ORDER BY surname, preferredName" ;
								$resultStaff=$connection2->prepare($sqlStaff);
								$resultStaff->execute($dataStaff);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							
							if ($resultStaff->rowCount()<1) {
								print "<i>None</i>" ;
							}
							else {
								print "<ul style='margin-left: 15px'>" ;
								while ($rowStaff=$resultStaff->fetch()) {
									print "<li>" . formatName($rowStaff["title"], $rowStaff["preferredName"], $rowStaff["surname"], "Staff") . "</li>" ; 
								}
								print "</ul>" ;
							}
						print "</td>" ;
					print "</tr>" ;
					print "<tr>" ;
						print "<td style='padding-top: 15px; width: 33%; vertical-align: top' colspan=3>" ;
							print "<span style='font-size: 115%; font-weight: bold'>Provider</span><br/>" ;
							print "<i>" ; if ($row["provider"]=="School") { print $_SESSION[$guid]["organisationNameShort"] ; } else { print "External" ; } ; print "</i>" ;
						print "</td>" ;
					print "</tr>" ;
					if ($row["description"]!="") {
						print "<tr>" ;
							print "<td style='text-align: justify; padding-top: 15px; width: 33%; vertical-align: top' colspan=3>" ;
								print "<h2>Description</h2>" ;
								print $row["description"] ;
							print "</td>" ;
						print "</tr>" ;
					}
				print "</table>" ;
				
				
					
				//Participants & Attendance
				print "<div style='width:400px; float: right; font-size: 115%; padding-top: 6px'>
					<h3 style='padding-top: 0px; margin-top: 5px'>Time Slots</h3>" ;
					
					try {
						$dataSlots=array("gibbonActivityID"=>$row["gibbonActivityID"]); 
						$sqlSlots="SELECT * FROM gibbonActivitySlot JOIN gibbonDaysOfWeek ON (gibbonActivitySlot.gibbonDaysOfWeekID=gibbonDaysOfWeek.gibbonDaysOfWeekID) WHERE gibbonActivityID=:gibbonActivityID ORDER BY sequenceNumber" ;
						$resultSlots=$connection2->prepare($sqlSlots);
						$resultSlots->execute($dataSlots);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}

					$count=0 ;
					while ($rowSlots=$resultSlots->fetch()) {
						print "<h4>" . $rowSlots["name"] . "</h4>" ;
						print "<p>" ;
							print "<i>Time</i>: " . substr($rowSlots["timeStart"], 0, 5) . " - " . substr($rowSlots["timeEnd"], 0, 5) . "<br/>" ;
							if ($rowSlots["gibbonSpaceID"]!="") {
								try {
									$dataSpace=array("gibbonSpaceID"=>$rowSlots["gibbonSpaceID"]); 
									$sqlSpace="SELECT * FROM gibbonSpace WHERE gibbonSpaceID=:gibbonSpaceID" ;
									$resultSpace=$connection2->prepare($sqlSpace);
									$resultSpace->execute($dataSpace);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								
								if ($resultSpace->rowCount()>0) {
									$rowSpace=$resultSpace->fetch() ;
									print "<i>Location</i>: " . $rowSpace["name"] ;
								}
							}
							else {
								print "<i>Location</i>: " . $rowSlots["locationExternal"] ;
							}
						print "</p>" ;
						
						$count++ ;
					}
					if ($count==0) {
						print "<i>None</i>" ;
					}
					
					$role=getRoleCategory($_SESSION[$guid]["gibbonRoleIDCurrent"], $connection2) ;
					if ($role=="Staff") {
						print "<h3>Participants</h3>" ;
						
						try {
							$dataStudents=array("gibbonActivityID"=>$row["gibbonActivityID"]); 
							$sqlStudents="SELECT title, preferredName, surname FROM gibbonActivityStudent JOIN gibbonPerson ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonActivityID=:gibbonActivityID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonActivityStudent.status='Accepted' ORDER BY surname, preferredName" ;
							$resultStudents=$connection2->prepare($sqlStudents);
							$resultStudents->execute($dataStudents);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}

						if ($resultStudents->rowCount()<1) {
							print "<i>None</i>" ;
						}
						else {
							print "<ul style='margin-left: 15px'>" ;
							while ($rowStudent=$resultStudents->fetch()) {
								print "<li>" . formatName("", $rowStudent["preferredName"], $rowStudent["surname"], "Student") . "</li>" ; 
							}
							print "</ul>" ;
						}
					}
				print "</div>" ;
			}
		}
	}
}
?>