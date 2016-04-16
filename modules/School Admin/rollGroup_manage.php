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

if (isActionAccessible($guid, $connection2, "/modules/School Admin/rollGroup_manage.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Manage Roll Groups') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["deleteReturn"])) { $deleteReturn=$_GET["deleteReturn"] ; } else { $deleteReturn="" ; }
	$deleteReturnMessage="" ;
	$class="error" ;
	if (!($deleteReturn=="")) {
		if ($deleteReturn=="success0") {
			$deleteReturnMessage="Your request was completed successfully." ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $deleteReturnMessage;
		print "</div>" ;
	}
	
	if (isset($_GET["copyReturn"])) { $copyReturn=$_GET["copyReturn"] ; } else { $copyReturn="" ; }
	$copyReturnMessage="" ;
	$class="error" ;
	if (!($copyReturn=="")) {
		if ($copyReturn=="fail0") {
			$copyReturnMessage=__($guid, "Your request failed because you do not have access to this action.") ;	
		}
		else if ($copyReturn=="fail1") {
			$copyReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
		}
		else if ($copyReturn=="fail2") {
			$copyReturnMessage=__($guid, "Your request failed due to a database error.") ;	
		}
		else if ($copyReturn=="success0") {
			$copyReturnMessage=__($guid, "Your request was completed successfully.") ;		
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $copyReturnMessage;
		print "</div>" ;
	} 
	
	$gibbonSchoolYearID="" ;
	if (isset($_GET["gibbonSchoolYearID"])) {
		$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"] ;
	}
	if ($gibbonSchoolYearID=="" OR $gibbonSchoolYearID==$_SESSION[$guid]["gibbonSchoolYearID"]) {
		$gibbonSchoolYearID=$_SESSION[$guid]["gibbonSchoolYearID"] ;
		$gibbonSchoolYearName=$_SESSION[$guid]["gibbonSchoolYearName"] ;
	}
	
	if ($gibbonSchoolYearID!=$_SESSION[$guid]["gibbonSchoolYearID"]) {
		try {
			$data=array("gibbonSchoolYearID"=>$_GET["gibbonSchoolYearID"]); 
			$sql="SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print __($guid, "The specified record does not exist.") ;
			print "</div>" ;
		}
		else {
			$row=$result->fetch() ;
			$gibbonSchoolYearID=$row["gibbonSchoolYearID"] ;
			$gibbonSchoolYearName=$row["name"] ;
		}
	}
	
	if ($gibbonSchoolYearID!="") {
		print "<h2>" ;
			print $gibbonSchoolYearName ;
		print "</h2>" ;
		
		print "<div class='linkTop'>" ;
			//Print year picker
			$previousYear=getPreviousSchoolYearID($gibbonSchoolYearID, $connection2) ;
			$nextYear=getNextSchoolYearID($gibbonSchoolYearID, $connection2) ;
			if ($previousYear!=FALSE) {
				print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/rollGroup_manage.php&gibbonSchoolYearID=" . getPreviousSchoolYearID($gibbonSchoolYearID, $connection2) . "'>" . __($guid, 'Previous Year') . "</a> " ;
			}
			else {
				print __($guid, "Previous Year") . " " ;
			}
			print " | " ;
			if ($nextYear!=FALSE) {
				print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/rollGroup_manage.php&gibbonSchoolYearID=" . getNextSchoolYearID($gibbonSchoolYearID, $connection2) . "'>" . __($guid, 'Next Year') . "</a> " ;
			}
			else {
				print __($guid, "Next Year") . " " ;
			}
		print "</div>" ;
	
		try {
			$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID); 
			$sql="SELECT gibbonSchoolYear.gibbonSchoolYearID, gibbonRollGroupID, gibbonSchoolYear.name as yearName, gibbonRollGroup.name, gibbonRollGroup.nameShort, gibbonPersonIDTutor, gibbonPersonIDTutor2, gibbonPersonIDTutor3, gibbonSpace.name AS space, website FROM gibbonRollGroup JOIN gibbonSchoolYear ON (gibbonRollGroup.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) LEFT JOIN gibbonSpace ON (gibbonRollGroup.gibbonSpaceID=gibbonSpace.gibbonSpaceID) WHERE gibbonSchoolYear.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY sequenceNumber, gibbonRollGroup.name" ; 
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		print "<div class='linkTop'>" ;
			if ($nextYear!=FALSE) {
				print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/rollGroup_manage_copyProcess.php?gibbonSchoolYearID=$gibbonSchoolYearID&gibbonSchoolYearIDNext=$nextYear'> " . __($guid, 'Copy All To Next Year') . "<img style='margin-left: 3px' title='" . __($guid, 'Copy All To Next Year') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/copy.png'/></a> | " ;
			}
			print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/rollGroup_manage_add.php&gibbonSchoolYearID=$gibbonSchoolYearID'>" .  __($guid, 'Add') . "<img style='margin-left: 5px' title='" . __($guid, 'Add') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.png'/></a>" ;
		print "</div>" ;
		
		if ($result->rowCount()<1) {
			print "<div class='error'>" ;
			print __($guid, "There are no records to display.") ;
			print "</div>" ;
		}
		else {
			print "<table cellspacing='0' style='width: 100%'>" ;
				print "<tr class='head'>" ;
					print "<th>" ;
						print __($guid, "Name") . "<br/>" ;
						print "<span style='font-size: 85%; font-style: italic'>" . __($guid, "Short Name") . "</span>" ;
					print "</th>" ;
					print "<th>" ;
						print __($guid, "Form Tutors") ;
					print "</th>" ;
					print "<th>" ;
						print __($guid, "Space") ;
					print "</th>" ;
					print "<th>" ;
						print __($guid, "Website") ;
					print "</th>" ;
					print "<th>" ;
						print __($guid, "Actions") ;
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
					
					//COLOR ROW BY STATUS!
					print "<tr class=$rowNum>" ;
						print "<td>" ;
							print "<b>" . $row["name"] ."</b><br/>" ;
							print "<span style='font-size: 85%; font-style: italic'>" . $row["nameShort"] . "</span>" ;
						print "</td>" ;
						print "<td>" ;
							try {
								$dataTutor=array("gibbonPersonID1"=>$row["gibbonPersonIDTutor"], "gibbonPersonID2"=>$row["gibbonPersonIDTutor2"], "gibbonPersonID3"=>$row["gibbonPersonIDTutor3"] );
								$sqlTutor="SELECT surname, preferredName FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID1 OR gibbonPersonID=:gibbonPersonID2 OR gibbonPersonID=:gibbonPersonID3" ;
								$resultTutor=$connection2->prepare($sqlTutor);
								$resultTutor->execute($dataTutor);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							while ($rowTutor=$resultTutor->fetch()) {
								print formatName("", $rowTutor["preferredName"], $rowTutor["surname"], "Staff", false, true) . "<br/>" ;
							}
						print "</td>" ;
						print "<td>" ;
							print $row["space"] ;
						print "</td>" ;
						print "<td>" ;
							if ($row["website"]!="") {
								print "<a target='_blank' href='" . $row["website"] . "'>" . $row["website"] . "</a>" ;
							}
						print "</td>" ;
						print "<td>" ;
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/rollGroup_manage_edit.php&gibbonRollGroupID=" . $row["gibbonRollGroupID"] . "&gibbonSchoolYearID=$gibbonSchoolYearID'><img title='" . __($guid, 'Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/rollGroup_manage_delete.php&gibbonRollGroupID=" . $row["gibbonRollGroupID"] . "&gibbonSchoolYearID=$gibbonSchoolYearID'><img title='" . __($guid, 'Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a> " ;
						print "</td>" ;
					print "</tr>" ;
					
					$count++ ;
				}
			print "</table>" ;
		}
	}
}
?>