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

if (isActionAccessible($guid, $connection2, "/modules/Activities/report_activityEnrollmentSummary.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>Activity Enrollment Summary</div>" ;
	print "</div>" ;
	print "<p>" ;
	print "This report displays a summary of enrollment in active activities the current year." ;
	print "</p>" ;
	
	try {
		$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
		$sql="SELECT * FROM gibbonActivity WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' ORDER BY name" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}

	if ($result->rowCount()<1) {
		print "<div class='error'>" ;
			print _("There are no records to display.") ;
		print "</div>" ;
	}
	else {
		$lastPerson="" ;
		
		print "<table cellspacing='0' style='width: 100%'>" ;
			print "<tr class='head'>" ;
				print "<th>" ;
					print "Activity" ;
				print "</th>" ;
				print "<th>" ;
					print "Accepted" ;
				print "</th>" ;
				print "<th>" ;
					print "Registered<br/>" ;
					print "<span style='font-size: 85%; font-style: italic'>Excludes \"Not Accepted\"<span>" ;
				print "</th>" ;
				print "<th>" ;
					print "Max Participants" ;
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
				$count++ ;
				
				//COLOR ROW BY STATUS!
				print "<tr class=$rowNum>" ;
					print "<td>" ;
						 print $row["name"] ;
					print "</td>" ;
					print "<td>" ;
						try {
							$dataEnrollment=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonActivityID"=>$row["gibbonActivityID"]); 
							$sqlEnrollment="SELECT gibbonActivityStudent.* FROM gibbonActivityStudent JOIN gibbonPerson ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonActivityID=:gibbonActivityID AND gibbonActivityStudent.status='Accepted'" ;
							$resultEnrollment=$connection2->prepare($sqlEnrollment);
							$resultEnrollment->execute($dataEnrollment);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}

						if ($resultEnrollment->rowCount()<0) {
							print "<i>Unknown</i>" ;
						}
						else {
							if ($resultEnrollment->rowCount()>$row["maxParticipants"]) {
								print "<span style='color: #f00; font-weight: bold'>" . $resultEnrollment->rowCount() . "</span>" ;
							}
							else {
								print $resultEnrollment->rowCount() ;
							}
							
						}
					print "</td>" ;
					print "<td>" ;
						try {
							$dataEnrollment=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonActivityID"=>$row["gibbonActivityID"]); 
							$sqlEnrollment="SELECT gibbonActivityStudent.* FROM gibbonActivityStudent JOIN gibbonPerson ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonActivityID=:gibbonActivityID AND NOT gibbonActivityStudent.status='Not Accepted'" ;
							$resultEnrollment=$connection2->prepare($sqlEnrollment);
							$resultEnrollment->execute($dataEnrollment);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}

						if ($resultEnrollment->rowCount()<0) {
							print "<i>Unknown</i>" ;
						}
						else {
							print $resultEnrollment->rowCount() ;
						}
					print "</td>" ;
					print "<td>" ;
						 print $row["maxParticipants"] ;
					print "</td>" ;
				print "</tr>" ;
				
			}
		print "</table>" ;
	}
}
?>