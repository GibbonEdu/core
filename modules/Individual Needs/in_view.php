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

if (isActionAccessible($guid, $connection2, "/modules/Individual Needs/in_view.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Get action with highest precendence
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print "The highest grouped action cannot be determined." ;
		print "</div>" ;
	}
	else {
		print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>View Student Records</div>" ;
		print "</div>" ;
		
		print "<h2>" ;
		print "Search" ;
		print "</h2>" ;
		
		$gibbonPersonID=$_GET["gibbonPersonID"] ;
	
		?>
		<form method="get" action="<? print $_SESSION[$guid]["absoluteURL"]?>/index.php">
			<table class='noIntBorder' cellspacing='0' style="width: 100%">	
				<tr><td style="width: 30%"></td><td></td></tr>
				<tr>
					<td> 
						<b>Search For</b><br/>
						<span style="font-size: 90%"><i>Preferred, surname, username.</i></span>
					</td>
					<td class="right">
						<input name="search" id="search" maxlength=20 value="<? print $_GET["search"] ?>" type="text" style="width: 300px">
					</td>
				</tr>
				<tr>
					<td colspan=2 class="right">
						<input type="hidden" name="q" value="/modules/<? print $_SESSION[$guid]["module"] ?>/in_view.php">
						<input type="hidden" name="address" value="<? print $_SESSION[$guid]["address"] ?>">
						<?
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/in_view.php'>Clear Search</a>" ;
						?>
						<input type="submit" value="Submit">
					</td>
				</tr>
			</table>
		</form>
		<?
		
		print "<h2>" ;
		print "Choose A Student" ;
		print "</h2>" ;
		print "<p>" ;
		print "This page displays all students enrolled in the school, including those who have not yet met their start date. With the right permissions, you can set Individual Needs status and Individual Education Plan details for any student." ;
		print "</p>" ;
		
		//Set pagination variable
		$page=$_GET["page"] ;
		if ((!is_numeric($page)) OR $page<1) {
			$page=1 ;
		}
		
		$search=$_GET["search"] ;
		try {
			$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
			$sql="SELECT gibbonPerson.gibbonPersonID, gibbonStudentEnrolmentID, surname, preferredName, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, dateStart, dateEnd FROM gibbonPerson, gibbonStudentEnrolment, gibbonYearGroup, gibbonRollGroup WHERE (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) AND (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) AND (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' ORDER BY surname, preferredName" ; 
			if ($search!="") {
				$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "search1"=>"%$search%", "search2"=>"%$search%", "search3"=>"%$search%"); 
				$sql="SELECT gibbonPerson.gibbonPersonID, gibbonStudentEnrolmentID, surname, preferredName, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, dateStart, dateEnd FROM gibbonPerson, gibbonStudentEnrolment, gibbonYearGroup, gibbonRollGroup WHERE (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) AND (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) AND (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND (preferredName LIKE :search1 OR surname LIKE :search2 OR username LIKE :search3) AND gibbonPerson.status='Full' ORDER BY surname, preferredName" ; 
			}
			$sqlPage= $sql . " LIMIT " . $_SESSION[$guid]["pagination"] . " OFFSET " . (($page-1)*$_SESSION[$guid]["pagination"]) ; 
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}

		if ($result->rowCount()<1) {
			print "<div class='error'>" ;
			print "There are no students with individual needs to display." ;
			print "</div>" ;
		}
		else {
			if ($result->rowCount()>$_SESSION[$guid]["pagination"]) {
				printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]["pagination"], "top", "gibbonSchoolYearID=$gibbonSchoolYearID&search=$search") ;
			}
		
			print "<table cellspacing='0' style='width: 100%'>" ;
				print "<tr class='head'>" ;
					print "<th>" ;
						print "Name" ;
					print "</th>" ;
					print "<th>" ;
						print "Year Group" ;
					print "</th>" ;
					print "<th>" ;
						print "Roll Group" ;
					print "</th>" ;
					print "<th>" ;
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
					
					//Color rows based on start and end date
					if (!($row["dateStart"]=="" OR $row["dateStart"]<=date("Y-m-d")) AND ($row["dateEnd"]=="" OR $row["dateEnd"]>=date("Y-m-d"))) {
						$rowNum="error" ;
					}
					
					//COLOR ROW BY STATUS!
					print "<tr class=$rowNum>" ;
						print "<td>" ;
							print formatName("", $row["preferredName"], $row["surname"], "Student", true) ;
						print "</td>" ;
						print "<td>" ;
							print $row["yearGroup"] ;
						print "</td>" ;
						print "<td>" ;
							print $row["rollGroup"] ;
						print "</td>" ;
						print "<td>" ;
							if ($highestAction=="Individual Needs Records_view") {
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/in_edit.php&gibbonPersonID=" . $row["gibbonPersonID"] . "&search=" . $_GET["search"] . "'><img title='View Individual Needs Details' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a> " ;
							}
							else if ($highestAction=="Individual Needs Records_viewEdit" OR $highestAction=="Individual Needs Records_viewContribute") {
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/in_edit.php&gibbonPersonID=" . $row["gibbonPersonID"] . "&search=" . $_GET["search"] . "'><img title='Edit Individual Needs Details' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
							}
						print "</td>" ;
					print "</tr>" ;
				}
			print "</table>" ;
			
			if ($result->rowCount()>$_SESSION[$guid]["pagination"]) {
				printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]["pagination"], "bottom", "gibbonSchoolYearID=$gibbonSchoolYearID&search=$search") ;
			}
		}
	}
}
?>