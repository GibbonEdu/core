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

if (isActionAccessible($guid, $connection2, "/modules/Students/report_transport_student.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>Student Transport</div>" ;
	print "</div>" ;
	
	print "<div class='linkTop'>" ;
	print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/report_transport_studentExport.php?address=" . $_GET["q"] . "'><img title='" . _('Export to Excel') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/download.png'/></a>" ;
	print "</div>" ;
	
	try {
		$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
		$sql="SELECT gibbonPerson.gibbonPersonID, transport, surname, preferredName, address1, address1District, address1Country, nameShort FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') ORDER BY transport, surname, preferredName" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}

	print "<table cellspacing='0' style='width: 100%'>" ;
		print "<tr class='head'>" ;
			print "<th>" ;
				print "Transport" ;
			print "</th>" ;
			print "<th>" ;
				print "Student" ;
			print "</th>" ;
			print "<th>" ;
				print "Address" ;
			print "</th>" ;
			print "<th>" ;
				print "Parents" ;
			print "</th>" ;
			print "<th>" ;
				print "Roll Group" ;
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
					print $row["transport"] ;
				print "</td>" ;
				print "<td>" ;
					print formatName("", $row["preferredName"], $row["surname"], "Student", true) ;
				print "</td>" ;
				print "<td>" ;
					try {
						$dataFamily=array("gibbonPersonID"=>$row["gibbonPersonID"]); 
						$sqlFamily="SELECT gibbonFamily.gibbonFamilyID, nameAddress, homeAddress, homeAddressDistrict, homeAddressCountry FROM gibbonFamily JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID" ;
						$resultFamily=$connection2->prepare($sqlFamily);
						$resultFamily->execute($dataFamily);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					while ($rowFamily=$resultFamily->fetch()) {
						if ($rowFamily["nameAddress"]!="") {
							print $rowFamily["nameAddress"] . ",<br/>" ;
						}
						if (substr(rtrim($rowFamily["homeAddress"]),-1)==",") {
							$address=substr(rtrim($rowFamily["homeAddress"]),0,-1) ;
						}
						else {
							$address=rtrim($rowFamily["homeAddress"]) ;
						}
						$address=addressFormat($address,rtrim($rowFamily["homeAddressDistrict"]),rtrim($rowFamily["homeAddressCountry"])) ;
						if ($address!=FALSE) {
							$address=explode(",", $address) ;
							for ($i=0; $i<count($address); $i++) {
								print $address[$i] ;
								if ($i<(count($address)-1)) {
									print "," ;
								}
								print "<br/>" ;
							}
						}
					}
				print "</td>" ;
				print "<td>" ;
					try {
						$dataFamily=array("gibbonPersonID"=>$row["gibbonPersonID"]); 
						$sqlFamily="SELECT gibbonFamilyID FROM gibbonFamilyChild WHERE gibbonPersonID=:gibbonPersonID" ;
						$resultFamily=$connection2->prepare($sqlFamily);
						$resultFamily->execute($dataFamily);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					while ($rowFamily=$resultFamily->fetch()) {
						try {
							$dataFamily2=array("gibbonFamilyID"=>$rowFamily["gibbonFamilyID"]); 
							$sqlFamily2="SELECT gibbonPerson.* FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonPerson.gibbonPersonID=gibbonFamilyAdult.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID ORDER BY contactPriority, surname, preferredName" ;
							$resultFamily2=$connection2->prepare($sqlFamily2);
							$resultFamily2->execute($dataFamily2);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						while ($rowFamily2=$resultFamily2->fetch()) {
							print "<u>" . formatName($rowFamily2["title"], $rowFamily2["preferredName"], $rowFamily2["surname"], "Parent") . "</u><br/>" ;
								$numbers=0 ;
								for ($i=1; $i<5; $i++) {
									if ($rowFamily2["phone" . $i]!="") {
										if ($rowFamily2["phone" . $i . "Type"]!="") {
											print "<i>" . $rowFamily2["phone" . $i . "Type"] . ":</i> " ;
										}
										if ($rowFamily2["phone" . $i . "CountryCode"]!="") {
											print "+" . $rowFamily2["phone" . $i . "CountryCode"] . " " ;
										}
										print $rowFamily2["phone" . $i] . "<br/>" ;
										$numbers++ ;
									}
								}
								if ($numbers==0) {
									print "<span style='font-size: 85%; font-style: italic'>No number available.</span><br/>" ;
								}
						}
					}
				print "</td>" ;
				print "<td>" ;
					print $row["nameShort"] ;
				print "</td>" ;
			print "</tr>" ;
		}
		if ($count==0) {
			print "<tr class=$rowNum>" ;
				print "<td colspan=2>" ;
					print _("There are no records to display.") ;
				print "</td>" ;
			print "</tr>" ;
		}
	print "</table>" ;
}
?>