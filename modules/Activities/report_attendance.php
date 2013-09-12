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

if (isActionAccessible($guid, $connection2, "/modules/Activities/report_attendance.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>Attendance by Activity</div>" ;
	print "</div>" ;
	
	print "<h2 class='top'>" ;
	print "Choose Activity" ;
	print "</h2>" ;
	
	$gibbonActivityID=$_GET["gibbonActivityID"] ;
	?>
	
	<form method="get" action="<? print $_SESSION[$guid]["absoluteURL"]?>/index.php">
		<table style="width: 100%">	
			<tr><td style="width: 30%"></td><td></td></tr>
			<tr>
				<td> 
					<b>Activity</b><br/>
					<span style="font-size: 90%"><i></i></span>
				</td>
				<td class="right">
					<select style="width: 302px" name="gibbonActivityID">
						<?
						print "<option value=''></option>" ;
						try {
							$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
							$sqlSelect="SELECT * FROM gibbonActivity WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' ORDER BY name, programStart" ;
							$resultSelect=$connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						}
						catch(PDOException $e) { }
						while ($rowSelect=$resultSelect->fetch()) {
							$selected="" ;
							if ($gibbonActivityID==$rowSelect["gibbonActivityID"]) {
								$selected="selected" ;
							}
							$date="" ;
							if (substr($rowSelect["programStart"],0,4)==substr($rowSelect["programEnd"],0,4)) {
								if (substr($rowSelect["programStart"],5,2)==substr($rowSelect["programEnd"],5,2)) {
									$date=" (" . date("F", mktime(0, 0, 0, substr($rowSelect["programStart"],5,2))) . " " . substr($rowSelect["programStart"],0,4) . ")" ;
								}
								else {
									$date=" (" . date("F", mktime(0, 0, 0, substr($rowSelect["programStart"],5,2))) . " - " . date("F", mktime(0, 0, 0, substr($rowSelect["programEnd"],5,2))) . " " . substr($rowSelect["programStart"],0,4) . ")" ;
								}
							}
							else {
								$date=" (" . date("F", mktime(0, 0, 0, substr($rowSelect["programStart"],5,2))) . " " . substr($rowSelect["programStart"],0,4) . " - " . date("F", mktime(0, 0, 0, substr($rowSelect["programEnd"],5,2))) . " " . substr($rowSelect["programEnd"],0,4) . ")" ;
							}
							
							print "<option $selected value='" . $rowSelect["gibbonActivityID"] . "'>" . htmlPrep($rowSelect["name"]) . $date . "</option>" ;
							
						}
						?>				
					</select>
				</td>
			</tr>
			<tr>
				<td colspan=2 class="right">
					<input type="hidden" name="q" value="/modules/<? print $_SESSION[$guid]["module"] ?>/report_attendance.php">
					<input type="submit" value="Submit">
				</td>
			</tr>
		</table>
	</form>
	<?
	
	if ($gibbonActivityID!="") {
		$output="" ;
		print "<h2 class='top'>" ;
		print "Report Data" ;
		print "</h2>" ;
		
		try {
			$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonActivityID"=>$gibbonActivityID); 
			$sql="SELECT gibbonPerson.gibbonPersonID, surname, preferredName, gibbonRollGroupID, gibbonActivityStudent.status FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonSchoolYearID=:gibbonSchoolYearID AND NOT gibbonActivityStudent.status='Not Accepted' AND gibbonActivityID=:gibbonActivityID ORDER BY gibbonActivityStudent.status, surname, preferredName" ;
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
			print "<div class='linkTop'>" ;
			print "<a target='_blank' href='" . $_SESSION[$guid]["absoluteURL"] . "/report.php?q=/modules/" . $_SESSION[$guid]["module"] . "/report_attendance_print.php&gibbonActivityID=$gibbonActivityID'><img title='Print' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/print.png'/></a>" ;
			print "</div>" ;
		
			$lastPerson="" ;
			
			print "<table style='width: 100%'>" ;
				print "<tr class='head'>" ;
					print "<th>" ;
						print "Student" ;
					print "</th>" ;
					print "<th colspan=15>" ;
						print "Attendance" ;
					print "</th>" ;
				print "</tr>" ;
				print "<tr style='height: 75px' class='odd'>" ;
					print "<td style='vertical-align:top; width: 120px'>Date</td>" ;
					print "<td style='color: #bbb; vertical-align:top; width: 15px'>1</td>" ;
					print "<td style='color: #bbb; vertical-align:top; width: 15px'>2</td>" ;
					print "<td style='color: #bbb; vertical-align:top; width: 15px'>3</td>" ;
					print "<td style='color: #bbb; vertical-align:top; width: 15px'>4</td>" ;
					print "<td style='color: #bbb; vertical-align:top; width: 15px'>5</td>" ;
					print "<td style='color: #bbb; vertical-align:top; width: 15px'>6</td>" ;
					print "<td style='color: #bbb; vertical-align:top; width: 15px'>7</td>" ;
					print "<td style='color: #bbb; vertical-align:top; width: 15px'>8</td>" ;
					print "<td style='color: #bbb; vertical-align:top; width: 15px'>9</td>" ;
					print "<td style='color: #bbb; vertical-align:top; width: 15px'>10</td>" ;
					print "<td style='color: #bbb; vertical-align:top; width: 15px'>11</td>" ;
					print "<td style='color: #bbb; vertical-align:top; width: 15px'>12</td>" ;
					print "<td style='color: #bbb; vertical-align:top; width: 15px'>13</td>" ;
					print "<td style='color: #bbb; vertical-align:top; width: 15px'>14</td>" ;
					print "<td style='color: #bbb; vertical-align:top; width: 15px'>15</td>" ;
				print "</tr>" ;
				
				
				$count=0;
				$rowNum="odd" ;
				while ($row=$result->fetch()) {
					if (is_null($log[$row["gibbonPersonID"]])) {
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
								print formatName("", $row["preferredName"], $row["surname"], "Student", true) ;
							print "</td>" ;
							print "<td></td>" ;
							print "<td></td>" ;
							print "<td></td>" ;
							print "<td></td>" ;
							print "<td></td>" ;
							print "<td></td>" ;
							print "<td></td>" ;
							print "<td></td>" ;
							print "<td></td>" ;
							print "<td></td>" ;
							print "<td></td>" ;
							print "<td></td>" ;
							print "<td></td>" ;
							print "<td></td>" ;
							print "<td></td>" ;
						print "</tr>" ;
						
						$lastPerson=$row["gibbonPersonID"] ;
					}
				}
				if ($count==0) {
					print "<tr class=$rowNum>" ;
						print "<td colspan=5>" ;
							print "All students are present." ;
						print "</td>" ;
					print "</tr>" ;
				}
			print "</table>" ;
		}
	}
}
?>