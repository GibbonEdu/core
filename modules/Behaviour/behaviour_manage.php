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
	//Get action with highest precendence
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print "The highest grouped action cannot be determined." ;
		print "</div>" ;
	}
	else {
		print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>Manage Behaviour Records</div>" ;
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
		
		$gibbonPersonID=NULL ;
		if (isset($_GET["gibbonPersonID"])) {
			$gibbonPersonID=$_GET["gibbonPersonID"] ;
		}	
		$gibbonRollGroupID=NULL ;
		if (isset($_GET["gibbonRollGroupID"])) {
			$gibbonRollGroupID=$_GET["gibbonRollGroupID"] ;
		}	
		$gibbonYearGroupID=NULL ;
		if (isset($_GET["gibbonYearGroupID"])) {
			$gibbonYearGroupID=$_GET["gibbonYearGroupID"] ;
		}		
		$type=NULL ;
		if (isset($_GET["type"])) {
			$type=$_GET["type"] ;
		}
		
		print "<h3>" ;
			print "Filter" ;
		print "</h3>" ;
		print "<form method='get' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Behaviour/behaviour_manage.php'>" ;
			print "<table class='noIntBorder' cellspacing='0' style='width: 100%'>" ;
				?>
				<tr>
					<td> 
						<b>Student</b><br/>
						<span style="font-size: 90%"><i></i></span>
					</td>
					<td class="right">
						<select name="gibbonPersonID" id="gibbonPersonID" style="width: 302px">
							<option value=""></option>
							<?
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
						
						print "<select name='gibbonRollGroupID' id='gibbonRollGroupID' style='width: 302px'>" ;
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
						
						print "<select name='gibbonYearGroupID' id='gibbonYearGroupID' style='width: 302px'>" ;
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
				<tr>
					<td> 
						<b>Type</b><br/>
						<span style="font-size: 90%"><i></i></span>
					</td>
					<td class="right">
						<?
						print "<select name='type' id='type' style='width: 302px'>" ;
							print "<option value=''></option>" ;
							$selected="" ;
							if ($type=="Positive") {
								$selected="selected" ;
							}
							print "<option $selected value='Positive'>Positive</option>" ;
							$selected="" ;
							if ($type=="Negative") {
								$selected="selected" ;
							}
							print "<option $selected value='Negative'>Negative</option>" ;
						print "</select>" ;
						?>
					</td>
				</tr>
				<?
			
				print "<tr>" ;
					print "<td class='right' colspan=2>" ;
						print "<input type='hidden' name='q' value='" . $_GET["q"] . "'>" ;
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Behaviour/behaviour_manage.php'>Clear Filters</a> " ;
						print "<input type='submit' value='Go'>" ;
					print "</td>" ;
				print "</tr>" ;
			print "</table>" ;
		print "</form>" ;
		
		
		print "<h3>" ;
			print "Behaviour Records" ;
		print "</h3>" ;
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
				$sqlWhere.="gibbonBehaviour.gibbonPersonID=:gibbonPersonID AND " ; 
			}
			if ($gibbonRollGroupID!="") {
				$data["gibbonRollGroupID"]=$gibbonRollGroupID ;
				$sqlWhere.="gibbonRollGroupID=:gibbonRollGroupID AND " ; 
			}
			if ($gibbonYearGroupID!="") {
				$data["gibbonYearGroupID"]=$gibbonYearGroupID ;
				$sqlWhere.="gibbonYearGroupID=:gibbonYearGroupID AND " ; 
			}
			if ($type!="") {
				$data["type"]=$type ;
				$sqlWhere.="type=:type AND " ; 
			}
			if ($sqlWhere=="AND ") {
				$sqlWhere="" ;
			}
			else {
				$sqlWhere=substr($sqlWhere,0,-5) ;
			}
			if ($highestAction=="Manage Behaviour Records_all") {
				$data["gibbonSchoolYearID"]=$_SESSION[$guid]["gibbonSchoolYearID"] ;
				$data["gibbonSchoolYearID2"]=$_SESSION[$guid]["gibbonSchoolYearID"] ;
				$sql="SELECT gibbonBehaviour.*, student.surname AS surnameStudent, student.preferredName AS preferredNameStudent, creator.surname AS surnameCreator, creator.preferredName AS preferredNameCreator, creator.title FROM gibbonBehaviour JOIN gibbonPerson AS student ON (gibbonBehaviour.gibbonPersonID=student.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=student.gibbonPersonID) JOIN gibbonPerson AS creator ON (gibbonBehaviour.gibbonPersonIDCreator=creator.gibbonPersonID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonBehaviour.gibbonSchoolYearID=:gibbonSchoolYearID2 $sqlWhere ORDER BY timestamp DESC" ; 
			}
			else if ($highestAction=="Manage Behaviour Records_my") {
				$data["gibbonSchoolYearID"]=$_SESSION[$guid]["gibbonSchoolYearID"] ;
				$data["gibbonSchoolYearID2"]=$_SESSION[$guid]["gibbonSchoolYearID"] ;
				$data["gibbonPersonID2"]=$_SESSION[$guid]["gibbonPersonID"] ; 
				$sql="SELECT gibbonBehaviour.*, student.surname AS surnameStudent, student.preferredName AS preferredNameStudent, creator.surname AS surnameCreator, creator.preferredName AS preferredNameCreator, creator.title FROM gibbonBehaviour JOIN gibbonPerson AS student ON (gibbonBehaviour.gibbonPersonID=student.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=student.gibbonPersonID) JOIN gibbonPerson AS creator ON (gibbonBehaviour.gibbonPersonIDCreator=creator.gibbonPersonID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonBehaviour.gibbonSchoolYearID=:gibbonSchoolYearID2 AND gibbonPersonIDCreator=:gibbonPersonID2 $sqlWhere ORDER BY timestamp DESC" ; 
			}
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		$sqlPage= $sql . " LIMIT " . $_SESSION[$guid]["pagination"] . " OFFSET " . (($page-1)*$_SESSION[$guid]["pagination"]) ;
		
		print "<div class='linkTop'>" ;
			print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/behaviour_manage_add.php&gibbonPersonID=$gibbonPersonID&gibbonRollGroupID=$gibbonRollGroupID&gibbonYearGroupID=$gibbonYearGroupID&type=$type'><img style='margin: 0 0 -4px 3px' title='New' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.gif'/></a>" ;
			$policyLink=getSettingByScope($connection2, "Behaviour", "policyLink") ;
			if ($policyLink!="") {
				print " | <a target='_blank' href='$policyLink'>View Behaviour Policy</a>" ;
			}
		print "</div>" ;
		
		if ($result->rowCount()<1) {
			print "<div class='error'>" ;
			print "There are no behaviour records to display." ;
			print "</div>" ;
		}
		else {
			if ($result->rowCount()>$_SESSION[$guid]["pagination"]) {
				printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]["pagination"], "top", "gibbonPersonID=$gibbonPersonID&gibbonRollGroupID=$gibbonRollGroupID&gibbonYearGroupID=$gibbonYearGroupID&type=$type") ;
			}
		
			print "<table cellspacing='0' style='width: 100%'>" ;
				print "<tr class='head'>" ;
					print "<th>" ;
						print "Student & Date" ;
					print "</th>" ;
					print "<th>" ;
						print "Type" ;
					print "</th>" ;
					print "<th>" ;
						print "Descriptor" ;
					print "</th>" ;
					print "<th>" ;
						print "Level" ;
					print "</th>" ;
					print "<th>" ;
						print "Teacher" ;
					print "</th>" ;
					print "<th style='min-width: 70px'>" ;
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
					
					//COLOR ROW BY STATUS!
					print "<tr class=$rowNum>" ;
						if ($row["comment"]!="") {
							print "<td>" ;
						}
						else {
							print "<td>" ;
						}
							print "<b>" . formatName("", $row["preferredNameStudent"], $row["surnameStudent"], "Student", true) . "</b><br/>" ;
							if (substr($row["timestamp"],0,10)>$row["date"]) {
								print "Updated: " . dateConvertBack(substr($row["timestamp"],0,10)) . "<br/>" ;
								print "Incident: " . dateConvertBack($row["date"]) . "<br/>" ;
							}
							else {
								print dateConvertBack($row["date"]) . "<br/>" ;
							}
						print "</td>" ;
						print "<td style='text-align: center'>" ;
							if ($row["type"]=="Negative") {
								print "<img title='At Risk' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconCross.png'/> " ;
							}
							else if ($row["type"]=="Positive") {
								print "<img title='Excellence' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconTick.png'/> " ;
							}
						print "</td>" ;
						print "<td>" ;
							print trim($row["descriptor"]) ;
						print "</td>" ;
						print "<td>" ;
							print trim($row["level"]) ;
						print "</td>" ;
						print "<td>" ;
							print formatName($row["title"], $row["preferredNameCreator"], $row["surnameCreator"], "Staff") . "</b><br/>" ;
						print "</td>" ;
						print "<td>" ;
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/behaviour_manage_edit.php&gibbonBehaviourID=" . $row["gibbonBehaviourID"] . "&gibbonPersonID=$gibbonPersonID&gibbonRollGroupID=$gibbonRollGroupID&gibbonYearGroupID=$gibbonYearGroupID&type=$type'><img title='Edit' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/behaviour_manage_delete.php&gibbonBehaviourID=" . $row["gibbonBehaviourID"] . "&gibbonPersonID=$gibbonPersonID&gibbonRollGroupID=$gibbonRollGroupID&gibbonYearGroupID=$gibbonYearGroupID&type=$type'><img title='Delete' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a> " ;
							
							print "<script type='text/javascript'>" ;	
								print "$(document).ready(function(){" ;
									print "\$(\".comment-$count\").hide();" ;
									print "\$(\".show_hide-$count\").fadeIn(1000);" ;
									print "\$(\".show_hide-$count\").click(function(){" ;
									print "\$(\".comment-$count\").fadeToggle(1000);" ;
									print "});" ;
								print "});" ;
							print "</script>" ;
							if ($row["comment"]!="") {
								print "<a title='View Description' class='show_hide-$count' onclick='false' href='#'><img style='padding-right: 5px' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/page_down.png' alt='Show Comment' onclick='return false;' /></a>" ;
							}
						print "</td>" ;
					print "</tr>" ;
					if ($row["comment"]!="") {
						if ($row["type"]=="Positive") {
							$bg="background-color: #D4F6DC;" ;
						}
						else {
							$bg="background-color: #F6CECB;" ;
						}
						print "<tr class='comment-$count' id='comment-$count'>" ;
							print "<td style='$bg border-bottom: 1px solid #333' colspan=6>" ;
								print $row["comment"] ;
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
}	
?>