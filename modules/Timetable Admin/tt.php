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

if (isActionAccessible($guid, $connection2, "/modules/Timetable Admin/tt.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Manage Timetables') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["return"])) { returnProcess($guid, $_GET["return"], null, null); }
	
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
			if (getPreviousSchoolYearID($gibbonSchoolYearID, $connection2)!=FALSE) {
				print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/tt.php&gibbonSchoolYearID=" . getPreviousSchoolYearID($gibbonSchoolYearID, $connection2) . "'>" . __($guid, 'Previous Year') . "</a> " ;
			}
			else {
				print __($guid, "Previous Year") . " " ;
			}
			print " | " ;
			if (getNextSchoolYearID($gibbonSchoolYearID, $connection2)!=FALSE) {
				print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/tt.php&gibbonSchoolYearID=" . getNextSchoolYearID($gibbonSchoolYearID, $connection2) . "'>" . __($guid, 'Next Year') . "</a> " ;
			}
			else {
				print __($guid, "Next Year") . " " ;
			}
		print "</div>" ;
		
		try {
			$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID); 
			$sql="SELECT * FROM gibbonTT WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name" ; 
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		print "<div class='linkTop'>" ;
		print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/tt_add.php&gibbonSchoolYearID=$gibbonSchoolYearID'>" .  __($guid, 'Add') . "<img style='margin-left: 5px' title='" . __($guid, 'Add') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.png'/></a>" ;
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
						print __($guid, "Name") ;
					print "</th>" ;
					print "<th>" ;
						print __($guid, "Short Name") ;
					print "</th>" ;
					print "<th>" ;
						print __($guid, "Year Groups") ;
					print "</th>" ;
					print "<th>" ;
						print __($guid, "Active") ;
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
							print $row["name"] ;
						print "</td>" ;
						print "<td>" ;
							print $row["nameShort"] ;
						print "</td>" ;
						print "<td>" ;
							if ($row["gibbonYearGroupIDList"]!="") {
								$years=explode(",", $row["gibbonYearGroupIDList"]);
							
								try {
									$dataYears=array(); 
									$sqlYearsWhere="" ;
									for ($i=0; $i<count($years); $i++) {
										if ($i==0) {
											$dataYears[$years[$i]]=$years[$i] ;
											$sqlYearsWhere.="gibbonYearGroupID=:" . $years[$i] ;
										}
										else {
											$dataYears[$years[$i]]=$years[$i] ;
											$sqlYearsWhere.=" OR gibbonYearGroupID=:" . $years[$i] ;
										}
									}
									$sqlYears="SELECT * FROM gibbonYearGroup WHERE $sqlYearsWhere" ;
									$resultYears=$connection2->prepare($sqlYears);
									$resultYears->execute($dataYears);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								
								if ($resultYears->rowCount()>0) {
									$count=0 ;
									while ($rowYears=$resultYears->fetch()) {
										if ($count>0) {
											print ", " ;
										}
										print __($guid, $rowYears["nameShort"]) ;
										$count++ ;
									}
								}
							}
						print "</td>" ;
						print "<td>" ;
							print ynExpander($guid, $row["active"]) ;
						print "</td>" ;
						print "<td>" ;
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/tt_edit.php&gibbonTTID=" . $row["gibbonTTID"] . "&gibbonSchoolYearID=$gibbonSchoolYearID'><img title='" . __($guid, 'Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/tt_delete.php&gibbonTTID=" . $row["gibbonTTID"] . "&gibbonSchoolYearID=$gibbonSchoolYearID'><img title='" . __($guid, 'Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a> " ;
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/tt_import.php&gibbonTTID=" . $row["gibbonTTID"] . "&gibbonSchoolYearID=$gibbonSchoolYearID'><img title='" . __($guid, 'Import') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/upload.png'/></a> " ;
						print "</td>" ;
					print "</tr>" ;
					
					$count++ ;
				}
			print "</table>" ;
		}
	}
}
?>