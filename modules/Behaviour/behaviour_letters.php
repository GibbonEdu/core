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

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Behaviour/behaviour_letters.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'View Behaviour Letters') . "</div>" ;
	print "</div>" ;
	
	$gibbonPersonID=NULL ;
	if (isset($_GET["gibbonPersonID"])) {
		$gibbonPersonID=$_GET["gibbonPersonID"] ;
	}	
	
	print "<h3>" ;
		print __($guid, "Filter") ;
	print "</h3>" ;
	print "<form method='get' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Behaviour/behaviour_letters.php'>" ;
		print "<table class='noIntBorder' cellspacing='0' style='width: 100%'>" ;
			?>
			<tr>
				<td> 
					<b><?php print __($guid, 'Student') ?></b><br/>
					<span class="emphasis small"></span>
				</td>
				<td class="right">
					<select name="gibbonPersonID" id="gibbonPersonID" class="standardWidth">
						<option value=""></option>
						<?php
						try {
							$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
							$sqlSelect="SELECT * FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') ORDER BY surname, preferredName" ;
							$resultSelect=$connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						}
						catch(PDOException $e) { }
						while ($rowSelect=$resultSelect->fetch()) {
							if ($gibbonPersonID==$rowSelect["gibbonPersonID"]) {
								print "<option selected value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . " (" . htmlPrep($rowSelect["nameShort"]) . ")</option>" ;
							}
							else {
								print "<option value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . " (" . htmlPrep($rowSelect["nameShort"]) . ")</option>" ;
							}
						}
						?>			
					</select>
				</td>
			</tr>
			<?php
		
			print "<tr>" ;
				print "<td class='right' colspan=2>" ;
					print "<input type='hidden' name='q' value='" . $_GET["q"] . "'>" ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Behaviour/behaviour_letters.php'>" . __($guid, 'Clear Filters') . "</a> " ;
					print "<input type='submit' value='" . __($guid, 'Go') . "'>" ;
				print "</td>" ;
			print "</tr>" ;
		print "</table>" ;
	print "</form>" ;
	
	
	print "<h3>" ;
		print __($guid, "Behaviour Records") ;
	print "</h3>" ;
	print "<p>" ;
		print __($guid, "This interface displays automated behaviour letters that have been issued within the current school year.") ;
	print "</p>" ;
	//Set pagination variable
	$page=1 ; if (isset($_GET["page"])) { $page=$_GET["page"] ; }
	if ((!is_numeric($page)) OR $page<1) {
		$page=1 ;
	}
	
	//Search with filters applied
	try {
		$data=array() ;
		$sqlWhere="AND " ;
		if ($gibbonPersonID!="") {
			$data["gibbonPersonID"]=$gibbonPersonID ;
			$sqlWhere.="gibbonBehaviourLetter.gibbonPersonID=:gibbonPersonID AND " ; 
		}
		if ($sqlWhere=="AND ") {
			$sqlWhere="" ;
		}
		else {
			$sqlWhere=substr($sqlWhere,0,-5) ;
		}
		$data["gibbonSchoolYearID"]=$_SESSION[$guid]["gibbonSchoolYearID"] ;
		$data["gibbonSchoolYearID2"]=$_SESSION[$guid]["gibbonSchoolYearID"] ;
		$sql="SELECT gibbonBehaviourLetter.*, surname, preferredName FROM gibbonBehaviourLetter JOIN gibbonPerson ON (gibbonBehaviourLetter.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonBehaviourLetter.gibbonSchoolYearID=:gibbonSchoolYearID2 $sqlWhere ORDER BY timestamp DESC" ; 
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}
	$sqlPage=$sql . " LIMIT " . $_SESSION[$guid]["pagination"] . " OFFSET " . (($page-1)*$_SESSION[$guid]["pagination"]) ;
	
	if ($result->rowCount()<1) {
		print "<div class='error'>" ;
		print __($guid, "There are no records to display.") ;
		print "</div>" ;
	}
	else {
		if ($result->rowCount()>$_SESSION[$guid]["pagination"]) {
			printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]["pagination"], "top", "gibbonPersonID=$gibbonPersonID&gibbonRollGroupID=$gibbonRollGroupID&gibbonYearGroupID=$gibbonYearGroupID&type=$type") ;
		}
	
		print "<table cellspacing='0' style='width: 100%'>" ;
			print "<tr class='head'>" ;
				print "<th>" ;
					print __($guid, "Student") . "<br/>" ;
					print "<span style='font-size: 85%; font-style: italic'>" . __($guid, 'Date') . "<span>" ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Letter") ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Status") ;
				print "</th>" ;
				print "<th style='min-width: 70px'>" ;
					print __($guid, "Actions") ;
				print "</th>" ;
			print "</tr>" ;
			
			$count=0;
			$rowNum="odd" ;
			try {
				$resultPage=$connection2->prepare($sqlPage);
				$resultPage->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}		
			while ($row=$resultPage->fetch()) {
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
						print "<a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $row["gibbonPersonID"] . "&subpage=Behaviour&search=&allStudents=&sort=surname, preferredName'>" . formatName("", $row["preferredName"], $row["surname"], "Student", true) . "</a><br/>" ;
						print "<span style='font-size: 85%; font-style: italic'>" . dateConvertBack($guid, substr($row["timestamp"],0,10)) . "<span>" ;
					print "</td>" ;
					print "<td>" ;
						print $row["letterLevel"] ;
					print "</td>" ;
					print "<td>" ;
						print $row["status"] ;
					print "</td>" ;
					print "<td>" ;
						print "<script type='text/javascript'>" ;	
							print "$(document).ready(function(){" ;
								print "\$(\".comment-$count\").hide();" ;
								print "\$(\".show_hide-$count\").fadeIn(1000);" ;
								print "\$(\".show_hide-$count\").click(function(){" ;
								print "\$(\".comment-$count\").fadeToggle(1000);" ;
								print "});" ;
							print "});" ;
						print "</script>" ;
						if ($row["body"]!="" OR $row["recipientList"]!="") {
							print "<a title='" . __($guid, 'View Details') . "' class='show_hide-$count' onclick='false' href='#'><img style='padding-right: 5px' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/page_down.png' alt='" . __($guid, 'View Details') . "' onclick='return false;' /></a>" ;
						}
					print "</td>" ;
				print "</tr>" ;
				if ($row["body"]!="" OR $row["recipientList"]!="") {
					print "<tr class='comment-$count' id='comment-$count'>" ;
						print "<td colspan=4>" ;
							if ($row["body"]!="") {
								print "<b>" . __($guid, 'Letter Body') . "</b><br/>" ;
								print nl2brr($row["body"]) . "<br/><br/>" ;
							}
							if ($row["recipientList"]!="") {
								print "<b>" . __($guid, 'Recipients') . "</b><br/>" ;
								$reipients=explode(',', $row["recipientList"]) ;
								foreach ($reipients AS $reipient) {
									print trim($reipient) . "<br/>" ;
								}
							}
						print "</td>" ;
					print "</tr>" ;
				}
			}
		print "</table>" ;
		
		if ($result->rowCount()>$_SESSION[$guid]["pagination"]) {
			printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]["pagination"], "bottom", "gibbonPersonID=$gibbonPersonID&gibbonRollGroupID=$gibbonRollGroupID&gibbonYearGroupID=$gibbonYearGroupID&type=$type") ;
		}
	}
}	
?>