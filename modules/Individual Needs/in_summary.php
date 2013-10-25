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

if (isActionAccessible($guid, $connection2, "/modules/Behaviour/behaviour_manage.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>Individual Needs Summary</div>" ;
	print "</div>" ;
	
	if (isset($_GET["deleteReturn"])) { $deleteReturn=$_GET["deleteReturn"] ; } else { $deleteReturn="" ; }
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
	
	$gibbonINDescriptorID=$_GET["gibbonINDescriptorID"] ;
	$gibbonAlertLevelID=$_GET["gibbonAlertLevelID"] ;
	$gibbonRollGroupID=$_GET["gibbonRollGroupID"] ;
	$gibbonYearGroupID=$_GET["gibbonYearGroupID"] ;
	
	print "<h3>" ;
		print "Filter" ;
	print "</h3>" ;
	print "<form method='get' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Individual Needs/in_summary.php'>" ;
		print "<table class='noIntBorder' cellspacing='0' style='width: 100%'>" ;
			?>
			<tr>
				<td> 
					<b>Descriptor</b><br/>
					<span style="font-size: 90%"><i></i></span>
				</td>
				<td class="right">
					<?
					try {
						$dataPurpose=array(); 
						$sqlPurpose="SELECT * FROM gibbonINDescriptor ORDER BY sequenceNumber" ;
						$resultPurpose=$connection2->prepare($sqlPurpose);
						$resultPurpose->execute($dataPurpose);
					}
					catch(PDOException $e) { }
					
					print "<select name='gibbonINDescriptorID' id='gibbonINDescriptorID' style='width:302px'>" ;
						print "<option value=''></option>" ;
						while ($rowPurpose=$resultPurpose->fetch()) {
							$selected="" ;
							if ($rowPurpose["gibbonINDescriptorID"]==$gibbonINDescriptorID) {
								$selected="selected" ;
							}
							print "<option $selected value='" . $rowPurpose["gibbonINDescriptorID"] . "'>" . $rowPurpose["name"] . "</option>" ;
						}
					print "</select>" ;
					?>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Alert Level</b><br/>
					<span style="font-size: 90%"><i></i></span>
				</td>
				<td class="right">
					<?
					try {
						$dataPurpose=array(); 
						$sqlPurpose="SELECT * FROM gibbonAlertLevel ORDER BY sequenceNumber" ;
						$resultPurpose=$connection2->prepare($sqlPurpose);
						$resultPurpose->execute($dataPurpose);
					}
					catch(PDOException $e) { }
					
					print "<select name='gibbonAlertLevelID' id='gibbonAlertLevelID' style='width:302px'>" ;
						print "<option value=''></option>" ;
						while ($rowPurpose=$resultPurpose->fetch()) {
							$selected="" ;
							if ($rowPurpose["gibbonAlertLevelID"]==$gibbonAlertLevelID) {
								$selected="selected" ;
							}
							print "<option $selected value='" . $rowPurpose["gibbonAlertLevelID"] . "'>" . $rowPurpose["name"] . "</option>" ;
						}
					print "</select>" ;
					?>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Roll Group</b><br/>
					<span style="font-size: 90%"><i></i></span>
				</td>
				<td class="right">
					<?
					try {
						$dataPurpose=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
						$sqlPurpose="SELECT * FROM gibbonRollGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name" ;
						$resultPurpose=$connection2->prepare($sqlPurpose);
						$resultPurpose->execute($dataPurpose);
					}
					catch(PDOException $e) { }
					
					print "<select name='gibbonRollGroupID' id='gibbonRollGroupID' style='width:302px'>" ;
						print "<option value=''></option>" ;
						while ($rowPurpose=$resultPurpose->fetch()) {
							$selected="" ;
							if ($rowPurpose["gibbonRollGroupID"]==$gibbonRollGroupID) {
								$selected="selected" ;
							}
							print "<option $selected value='" . $rowPurpose["gibbonRollGroupID"] . "'>" . $rowPurpose["name"] . "</option>" ;
						}
					print "</select>" ;
					?>
				</td>
			</tr>
			<tr>
				<td> 
					<b>Year Group</b><br/>
					<span style="font-size: 90%"><i></i></span>
				</td>
				<td class="right">
					<?
					try {
						$dataPurpose=array(); 
						$sqlPurpose="SELECT * FROM gibbonYearGroup ORDER BY sequenceNumber" ;
						$resultPurpose=$connection2->prepare($sqlPurpose);
						$resultPurpose->execute($dataPurpose);
					}
					catch(PDOException $e) { }
					
					print "<select name='gibbonYearGroupID' id='gibbonYearGroupID' style='width:302px'>" ;
						print "<option value=''></option>" ;
						while ($rowPurpose=$resultPurpose->fetch()) {
							$selected="" ;
							if ($rowPurpose["gibbonYearGroupID"]==$gibbonYearGroupID) {
								$selected="selected" ;
							}
							print "<option $selected value='" . $rowPurpose["gibbonYearGroupID"] . "'>" . $rowPurpose["name"] . "</option>" ;
						}
					print "</select>" ;
					?>
				</td>
			</tr>
			<?
			print "<tr>" ;
				print "<td class='right' colspan=2>" ;
					print "<input type='hidden' name='q' value='" . $_GET["q"] . "'>" ;
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Individual Needs/in_summary.php'>Clear Filters</a> " ;
					print "<input type='submit' value='Go'>" ;
				print "</td>" ;
			print "</tr>" ;
		print "</table>" ;
	print "</form>" ;
	
	
	print "<h3>" ;
		print "Students With Records" ;
	print "</h3>" ;
	print "<p>" ;
	print "Students only show up in this list if they have a IN record with descriptors set. If a student does not show up here, check in Individual Needs Records." ;
	print "</p>" ;
	
	//Set pagination variable
	$page=1 ; if (isset($_GET["page"])) { $page=$_GET["page"] ; }
	if ((!is_numeric($page)) OR $page<1) {
		$page=1 ;
	}
	
	$search=$_GET["search"] ;
	try {
		$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
		$sqlWhere="AND " ;
		if ($gibbonINDescriptorID!="") {
			$data["gibbonINDescriptorID"]=$gibbonINDescriptorID ;
			$sqlWhere.="gibbonINPersonDescriptor.gibbonINDescriptorID=:gibbonINDescriptorID AND " ; 
		}
		if ($gibbonAlertLevelID!="") {
			$data["gibbonAlertLevelID"]=$gibbonAlertLevelID ;
			$sqlWhere.="gibbonINPersonDescriptor.gibbonAlertLevelID=:gibbonAlertLevelID AND " ; 
		}
		if ($gibbonRollGroupID!="") {
			$data["gibbonRollGroupID"]=$gibbonRollGroupID ;
			$sqlWhere.="gibbonStudentEnrolment.gibbonRollGroupID=:gibbonRollGroupID AND " ; 
		}
		if ($gibbonYearGroupID!="") {
			$data["gibbonYearGroupID"]=$gibbonYearGroupID ;
			$sqlWhere.="gibbonStudentEnrolment.gibbonYearGroupID=:gibbonYearGroupID AND " ; 
		}
		if ($sqlWhere=="AND ") {
			$sqlWhere="" ;
		}
		else {
			$sqlWhere=substr($sqlWhere,0,-5) ;
		}
		$sql="SELECT DISTINCT gibbonPerson.gibbonPersonID, gibbonStudentEnrolmentID, surname, preferredName, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, dateStart, dateEnd FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) JOIN gibbonINPersonDescriptor ON (gibbonINPersonDescriptor.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' $sqlWhere ORDER BY rollGroup, surname, preferredName" ; 
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
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/in_edit.php&gibbonPersonID=" . $row["gibbonPersonID"] . "&source=summary&gibbonINDescriptorID=" . $_GET["gibbonINDescriptorID"] . "&gibbonAlertLevelID=" . $_GET["gibbonAlertLevelID"] . "&gibbonRollGroupID=" . $_GET["gibbonRollGroupID"] . "&gibbonYearGroupID=" . $_GET["gibbonYearGroupID"] . "'><img title='Edit Individual Needs Details' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
					print "</td>" ;
				print "</tr>" ;
			}
		print "</table>" ;
		
		if ($result->rowCount()>$_SESSION[$guid]["pagination"]) {
			printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]["pagination"], "bottom", "gibbonSchoolYearID=$gibbonSchoolYearID&search=$search") ;
		}
		
	}
}	
?>