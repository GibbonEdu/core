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

if (isActionAccessible($guid, $connection2, "/modules/Students/report_students_new")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>Letters Home by Roll Group</div>" ;
	print "</div>" ;
	
	try {
		$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
		$sql="SELECT gibbonPerson.gibbonPersonID, surname, preferredName, gibbonRollGroup.nameShort AS rollGroup, gibbonFamily.gibbonFamilyID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) LEFT JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' ORDER BY rollGroup, surname, preferredName" ;
		$result=$connection2->prepare($sql);
		$result->execute($data); 
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}
	if ($result->rowCount()<1) {
		print "<div class='error'>" ;
			print "There are no records to display." ;
		print "</div>" ;
	}
	else {
		$currentRollGroup="" ;
		$lastRollGroup="" ;
		$count=0;
		$countTotal=0;
		$rowNum="odd" ;
		while ($row=$result->fetch()) {
			$currentRollGroup=$row["rollGroup"] ;
			
			//SPLIT INTO ROLL GROUPS
			if ($currentRollGroup!=$lastRollGroup) {
				if ($lastRollGroup!="") {
					print "</table>" ;
				}
				print "<h2>" . $row["rollGroup"] . "</h2>" ;
				$count=0;
				$rowNum="odd" ;
				print "<table cellspacing='0' style='width: 100%'>" ;
					print "<tr class='head'>" ;
						print "<th>" ;
							print "Total Count" ;
						print "</th>" ;
						print "<th>" ;
							print "Form Count" ;
						print "</th>" ;
						print "<th>" ;
							print "Student" ;
						print "</th>" ;
						print "<th>" ;
							print "Sibling Count" ;
						print "</th>" ;
					print "</tr>" ;
			}
			$lastRollGroup=$row["rollGroup"] ;
			
			//PUMP OUT STUDENT DATA
			//Check for older siblings
			$proceed=FALSE ;
			try {
				$dataSibling=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonFamilyID"=>$row["gibbonFamilyID"]); 
				$sqlSibling="SELECT gibbonPerson.gibbonPersonID, surname, preferredName, gibbonFamily.name FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' AND gibbonFamily.gibbonFamilyID=:gibbonFamilyID ORDER BY gibbonFamily.gibbonFamilyID, dob" ;
				$resultSibling=$connection2->prepare($sqlSibling);
				$resultSibling->execute($dataSibling); 
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			
			if ($resultSibling->rowCount()==1) {
				$proceed=TRUE ;
			}
			else {
				$rowSibling=$resultSibling->fetch() ;
				if ($rowSibling["gibbonPersonID"]==$row["gibbonPersonID"]) {
					$proceed=TRUE ;
				}
			}
			
			if ($proceed==TRUE) {
				if ($count%2==0) {
					$rowNum="even" ;
				}
				else {
					$rowNum="odd" ;
				}
				print "<tr class=$rowNum>" ;
					print "<td style='width: 20%'>" ;
						print $countTotal+1 ;
					print "</td>" ;
					print "<td style='width: 20%'>" ;
						print $count+1 ;
					print "</td>" ;
					print "<td>" ;
						print formatName("", $row["preferredName"], $row["surname"], "Student", TRUE) ;
					print "</td>" ;
					print "<td style='width: 20%'>" ;
						print ($resultSibling->rowCount()-1) ;
					print "</td>" ;
				print "</tr>" ;
				$count++ ;
				$countTotal++ ;
			}
		}
		print "</table>" ;
	}
}
?>