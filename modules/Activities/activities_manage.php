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

if (isActionAccessible($guid, $connection2, "/modules/Activities/activities_manage.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Set returnTo point for upcoming pages
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>Manage Activities</div>" ;
	print "</div>" ;
	
	$deleteReturn = $_GET["deleteReturn"] ;
	$deleteReturnMessage ="" ;
	$class="error" ;
	if (!($deleteReturn=="")) {
		if ($deleteReturn=="success0") {
			$deleteReturnMessage ="Delete was successful." ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $deleteReturnMessage;
		print "</div>" ;
	} 
	
	//Set pagination variable
	$page=$_GET["page"] ;
	if ((!is_numeric($page)) OR $page<1) {
		$page=1 ;
	}
	
	//Should we show date as term or date?
	$dateType=getSettingByScope( $connection2, "Activities", "dateType" ) ; 
	$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
	if ($dateType!="Date") {
		$sql="SELECT * FROM gibbonActivity WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY gibbonSchoolYearTermIDList, name" ; 
	}
	else {
		$sql="SELECT * FROM gibbonActivity WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY programStart DESC, name" ; 
	}
	
	try {
		$result=$connection2->prepare($sql);
		$result->execute($data); 
	}
	catch(PDOException $e) { 
		print "<div class='error'>" ;
		print "Activities cannot be displayed." ;
		print "</div>" ;
	}
	
	$sqlPage= $sql . " LIMIT " . $_SESSION[$guid]["pagination"] . " OFFSET " . (($page-1)*$_SESSION[$guid]["pagination"]) ;
	if ($result) {
		print "<div class='linkTop'>" ;
		print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/activities_manage_add.php'><img title='New' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.gif'/></a>" ;
		print "</div>" ;
		
		if ($result->rowCount()<1) {
			print "<div class='error'>" ;
			print "There are no activities to display." ;
			print "</div>" ;
		}
		else {
			if ($result->rowCount()>$_SESSION[$guid]["pagination"]) {
				printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]["pagination"], "top") ;
			}
		
			print "<table style='width: 100%'>" ;
				print "<tr class='head'>" ;
					print "<th>" ;
						print "Activity" ;
					print "</th>" ;
					print "<th>" ;
						print "Days" ;
					print "</th>" ;
					print "<th>" ;
						print "Years" ;
					print "</th>" ;
					print "<th>" ;
						if ($dateType!="Date") {
							print "Term" ;
						}
						else {
							print "Dates" ;
						}
					print "</th>" ;
					print "<th>" ;
						print "Cost" ;
					print "</th>" ;
					print "<th>" ;
						print "Active" ;
					print "</th>" ;
					print "<th style='width: 70px'>" ;
						print "Actions" ;
					print "</th>" ;
				print "</tr>" ;
				
				$count=0;
				$rowNum="odd" ;
				
				try {
					$resultPage=$connection2->prepare($sqlPage);
					$resultPage->execute($data); 
				}
				catch(PDOException $e) { 
					print "<div class='error'>" ;
					print "Activities cannot be displayed." ;
					print "</div>" ;
				}
	
				if ($result) {
					while ($row=$resultPage->fetch()) {
						if ($count%2==0) {
							$rowNum="even" ;
						}
						else {
							$rowNum="odd" ;
						}
						$count++ ;
						
						if ($row["active"]=="N") {
							$rowNum="error" ;
						}
		
						//COLOR ROW BY STATUS!
						print "<tr class=$rowNum>" ;
							print "<td>" ;
								print $row["name"] . "<br/>" ;
								print "<i>" . trim($row["type"]) . "</i>" ;
							print "</td>" ;
							print "<td>" ;
								try {
									$dataSlots=array("gibbonActivityID"=>$row["gibbonActivityID"]); 
									$sqlSlots="SELECT DISTINCT nameShort, sequenceNumber FROM gibbonActivitySlot JOIN gibbonDaysOfWeek ON (gibbonActivitySlot.gibbonDaysOfWeekID=gibbonDaysOfWeek.gibbonDaysOfWeekID) WHERE gibbonActivityID=:gibbonActivityID ORDER BY sequenceNumber" ;
									$resultSlots=$connection2->prepare($sqlSlots);
									$resultSlots->execute($dataSlots);
								}
								catch(PDOException $e) { }
								
								$count2=0 ;
								while ($rowSlots=$resultSlots->fetch()) {
									if ($count2>0) {
										print ", " ;
									}
									print $rowSlots["nameShort"] ;
									$count2++ ;
								}
								if ($count2==0) {
									print "<i>None</i>" ;
								}
							print "</td>" ;
							print "<td>" ;
								print getYearGroupsFromIDList($connection2, $row["gibbonYearGroupIDList"]) ;
							print "</td>" ;
							print "<td>" ;
								if ($dateType!="Date") {
									$terms=getTerms($connection2, $_SESSION[$guid]["gibbonSchoolYearID"], true) ;
									$termList="" ;
									for ($i=0; $i<count($terms); $i=$i+2) {
										if (is_numeric(strpos($row["gibbonSchoolYearTermIDList"], $terms[$i]))) {
											$termList.=$terms[($i+1)] . "<br/>" ;
										}
									}
									print $termList ;
								}
								else {
									if (substr($row["programStart"],0,4)==substr($row["programEnd"],0,4)) {
										if (substr($row["programStart"],5,2)==substr($row["programEnd"],5,2)) {
											print date("F", mktime(0, 0, 0, substr($row["programStart"],5,2))) . " " . substr($row["programStart"],0,4) ;
										}
										else {
											print date("F", mktime(0, 0, 0, substr($row["programStart"],5,2))) . " - " . date("F", mktime(0, 0, 0, substr($row["programEnd"],5,2))) . " " . substr($row["programStart"],0,4) ;
										}
									}
									else {
										print date("F", mktime(0, 0, 0, substr($row["programStart"],5,2))) . " " . substr($row["programStart"],0,4) . " - " . date("F", mktime(0, 0, 0, substr($row["programEnd"],5,2))) . " " . substr($row["programEnd"],0,4) ;
									}
								}
							print "</td>" ;
							print "<td>" ;
								if ($row["payment"]==0) {
									print "<i>None</i>" ;
								}
								else {
									print "$" . $row["payment"] ;
								}
							print "</td>" ;
							print "<td>" ;
								print $row["active"] ;
							print "</td>" ;
							print "<td>" ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/activities_manage_edit.php&gibbonActivityID=" . $row["gibbonActivityID"] . "'><img title='Edit' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/activities_manage_delete.php&gibbonActivityID=" . $row["gibbonActivityID"] . "'><img title='Delete' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a> " ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/activities_manage_enrolment.php&gibbonActivityID=" . $row["gibbonActivityID"] . "'><img title='Enrolment' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/attendance.gif'/></a> " ;
							print "</td>" ;
						print "</tr>" ;
					}
				}
			print "</table>" ;
			
			if ($result->rowCount()>$_SESSION[$guid]["pagination"]) {
				printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]["pagination"], "bottom") ;
			}
		}
	}
}	
?>