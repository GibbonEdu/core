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

if (isActionAccessible($guid, $connection2, "/modules/Behaviour/behaviour_manage.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>Find Behaviour Patterns</div>" ;
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
	
	$descriptor=$_GET["descriptor"] ;
	$level=$_GET["level"] ;
	$fromDate=$_GET["fromDate"] ;
	$gibbonRollGroupID=$_GET["gibbonRollGroupID"] ;
	$gibbonYearGroupID=$_GET["gibbonYearGroupID"] ;
	$minimumCount=$_GET["minimumCount"] ;
	
	print "<h3>" ;
		print "Filter" ;
	print "</h3>" ;
	print "<div class='linkTop'>" ;
		print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Behaviour/behaviour_pattern.php'>Clear Filters</a>" ;
	print "</div>" ;
	print "<form method='get' action='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Behaviour/2Fbehaviour_pattern.php'>" ;
		print "<table style='width: 200px'>" ;
			print "<tr>" ;
				print "<td>" ;
					print "<b>Descriptor</b>" ;
				print "</td>" ;
				print "<td>" ;
					print "<b>Level</b>" ;
				print "</td>" ;
				print "<td>" ;
					print "<b>From Date</b>" ;
				print "</td>" ;
				print "<td>" ;
					
				print "</td>" ;
			print "</tr>" ;
			print "<tr>" ;
				print "<td style='padding: 0px 2px 0px 0px'>" ;
					try {
						$sqlNegative="SELECT * FROM gibbonSetting WHERE scope='Behaviour' AND name='negativeDescriptors'" ;
						$resultNegative=$connection2->query($sqlNegative);   
					}
					catch(PDOException $e) { }

					if ($resultNegative->rowCount()==1) {
						$rowNegative=$resultNegative->fetch() ;
						$optionsNegative=$rowNegative["value"] ;
						if ($optionsNegative!="") {
							$optionsNegative=explode(",", $optionsNegative) ;
						}
					}
					
					print "<select name='descriptor' id='descriptor' style='width:205px; margin-left: 0px'>" ;
						print "<option value=''></option>" ;
						for ($i=0; $i<count($optionsNegative); $i++) {
						?>
							<option <? if ($descriptor==$optionsNegative[$i]) {print "selected ";}?>value="<? print trim($optionsNegative[$i]) ?>"><? print trim($optionsNegative[$i]) ?></option>
						<?
						}
					print "</select>" ;
				print "</td>" ;
				print "<td style='padding: 0px 2px 0px 0px'>" ;
					$optionsLevels=getSettingByScope($connection2, "Behaviour", "levels") ;
					if ($optionsLevels!="") {
						$optionsLevels=explode(",", $optionsLevels) ;
					}
					
					print "<select name='level' id='level' style='width:235px; margin-left: 0px'>" ;
						print "<option value=''></option>" ;
						for ($i=0; $i<count($optionsLevels); $i++) {
						?>
							<option <? if ($level==$optionsLevels[$i]) {print "selected ";}?>value="<? print trim($optionsLevels[$i]) ?>"><? print trim($optionsLevels[$i]) ?></option>
						<?
						}
					print "</select>" ;
				print "</td>" ;
				print "<td style='padding: 0px 2px 0px 0px' colspan=2>" ;
					?>
					<input name="fromDate" id="fromDate" maxlength=10 value="<? if ($fromDate!="") { print $fromDate ; } ?>" type="text" style="width: 295px">
					<script type="text/javascript">
						var fromDate = new LiveValidation('fromDate');
						fromDate.add( Validate.Format, {pattern: /^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i, failureMessage: "Use dd/mm/yyyy." } ); 
					 </script>
					 <script type="text/javascript">
						$(function() {
							$( "#fromDate" ).datepicker();
						});
					</script>
					<?
				print "</td>" ;
			print "</tr>" ;
			print "<tr>" ;
				print "<td style='padding-top: 15px'>" ;
					print "<b>Roll Group</b>" ;
				print "</td>" ;
				print "<td style='padding-top: 15px'>" ;
					print "<b>Year Group</b>" ;
				print "</td>" ;
				print "<td style='padding-top: 15px'>" ;
					print "<b>Minimum Count</b>" ;
				print "</td>" ;
				print "<td>" ;
					
				print "</td>" ;
			print "</tr>" ;
			print "<tr>" ;
				print "<td style='padding: 0px 2px 0px 0px'>" ;
					try {
						$dataPurpose=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
						$sqlPurpose="SELECT * FROM gibbonRollGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name" ;
						$resultPurpose=$connection2->prepare($sqlPurpose);
						$resultPurpose->execute($dataPurpose);
					}
					catch(PDOException $e) { }
					
					print "<select name='gibbonRollGroupID' id='gibbonRollGroupID' style='width:205px; margin-left: 0px'>" ;
						print "<option value=''></option>" ;
						while ($rowPurpose=$resultPurpose->fetch()) {
							$selected="" ;
							if ($rowPurpose["gibbonRollGroupID"]==$gibbonRollGroupID) {
								$selected="selected" ;
							}
							print "<option $selected value='" . $rowPurpose["gibbonRollGroupID"] . "'>" . $rowPurpose["name"] . "</option>" ;
						}
					print "</select>" ;
				print "</td>" ;
				print "<td style='padding: 0px 2px 0px 0px'>" ;
					try {
						$dataPurpose=array(); 
						$sqlPurpose="SELECT * FROM gibbonYearGroup ORDER BY sequenceNumber" ;
						$resultPurpose=$connection2->prepare($sqlPurpose);
						$resultPurpose->execute($dataPurpose);
					}
					catch(PDOException $e) { }
					
					print "<select name='gibbonYearGroupID' id='gibbonYearGroupID' style='width:235px; margin-left: 0px'>" ;
						print "<option value=''></option>" ;
						while ($rowPurpose=$resultPurpose->fetch()) {
							$selected="" ;
							if ($rowPurpose["gibbonYearGroupID"]==$gibbonYearGroupID) {
								$selected="selected" ;
							}
							print "<option $selected value='" . $rowPurpose["gibbonYearGroupID"] . "'>" . $rowPurpose["name"] . "</option>" ;
						}
					print "</select>" ;
				print "</td>" ;
				print "<td style='padding: 0px 2px 0px 0px'>" ;
					print "<select name='minimumCount' id='minimumCount' style='width:235px; margin-left: 0px'>" ;
						for ($i=0; $i<51; $i++) {
							if ($i==0 OR $i==1 OR $i==2 OR $i==3 OR $i==4 OR $i==5 OR $i==10 OR $i==25 OR $i==50) {
								?>
								<option <? if ($minimumCount==$i) {print "selected ";}?>value="<? print $i ?>"><? print $i ?></option>
								<?
							}
						}
					print "</select>" ;
				print "</td>" ;
				
				print "<td style='padding: 0px 0px 0px 2px'>" ;
					print "<input type='hidden' name='q' value='" . $_GET["q"] . "'>" ;
					print "<input type='submit' value='Go'>" ;
				print "</td>" ;
			print "</tr>" ;
		print "</table>" ;
	print "</form>" ;
	
	
	print "<h3>" ;
		print "Behaviour Records" ;
	print "</h3>" ;
	print "<p>" ;
	print "The students listed below match the criteria above, for negative behaviour records in the current school year. The count is updated according to the criteria above." ;
	print "</p>" ;
	
	//Set pagination variable
	$page=$_GET["page"] ;
	if ((!is_numeric($page)) OR $page<1) {
		$page=1 ;
	}
	
	$search=$_GET["search"] ;
	try {
		$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonSchoolYearID2"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
		$sqlWhere1="AND " ;
		if ($gibbonRollGroupID!="") {
			$data["gibbonRollGroupID"]=$gibbonRollGroupID ;
			$sqlWhere1.="gibbonStudentEnrolment.gibbonRollGroupID=:gibbonRollGroupID AND " ; 
		}
		if ($gibbonYearGroupID!="") {
			$data["gibbonYearGroupID"]=$gibbonYearGroupID ;
			$sqlWhere1.="gibbonStudentEnrolment.gibbonYearGroupID=:gibbonYearGroupID AND " ; 
		}
		if ($sqlWhere1=="AND ") {
			$sqlWhere1="" ;
		}
		else {
			$sqlWhere1=substr($sqlWhere1,0,-5) ;
		}
		
		$sqlWhere2="AND " ;
		if ($descriptor!="") {
			$data["descriptor"]=$descriptor ;
			$sqlWhere2.="gibbonBehaviour.descriptor=:descriptor AND " ; 
		}
		if ($level!="") {
			$data["level"]=$level ;
			$sqlWhere2.="gibbonBehaviour.level=:level AND " ; 
		}
		if ($fromDate!="") {
			$data["fromDate"]=dateConvert($fromDate) ;
			$sqlWhere2.="gibbonBehaviour.date>=:fromDate AND " ; 
		}
		if ($sqlWhere2=="AND ") {
			$sqlWhere2="" ;
		}
		else {
			$sqlWhere2=substr($sqlWhere2,0,-5) ;
		}
		
		$sqlWhere3="HAVING " ;
		if ($minimumCount!="") {
			$data["minimumCount"]=$minimumCount ;
			$sqlWhere3.="count>=:minimumCount" ; 
		}
		if ($sqlWhere3=="HAVING ") {
			$sqlWhere3="" ;
		}
		
		$sql="SELECT DISTINCT gibbonPerson.gibbonPersonID, gibbonStudentEnrolmentID, surname, preferredName, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, dateStart, dateEnd, (SELECT COUNT(*) FROM gibbonBehaviour WHERE type='Negative' AND gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID2 $sqlWhere2) AS count FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' $sqlWhere1 $sqlWhere3 ORDER BY rollGroup, surname, preferredName" ; 
		$sqlPage= $sql . " LIMIT " . $_SESSION[$guid]["pagination"] . " OFFSET " . (($page-1)*$_SESSION[$guid]["pagination"]) ; 
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}

	if ($result->rowCount()<1) {
		print "<div class='error'>" ;
		print "There are no students who match the specified criteria." ;
		print "</div>" ;
	}
	else {
		if ($result->rowCount()>$_SESSION[$guid]["pagination"]) {
			printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]["pagination"], "top", "gibbonSchoolYearID=$gibbonSchoolYearID&search=$search") ;
		}
	
		print "<table style='width: 100%'>" ;
			print "<tr class='head'>" ;
				print "<th>" ;
					print "Name" ;
				print "</th>" ;
				print "<th>" ;
					print "Negative Count<br/>" ;
					print "<span style='font-size: 75%; font-style: italic'>(Current Year Only)</span>" ;
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
						print $row["count"] ;
					print "</td>" ;
					print "<td>" ;
						print $row["yearGroup"] ;
					print "</td>" ;
					print "<td>" ;
						print $row["rollGroup"] ;
					print "</td>" ;
					print "<td>" ;
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/behaviour_view_details.php&gibbonPersonID=" . $row["gibbonPersonID"] . "&descriptor=$descriptor&level=$level&fromDate=$fromDate&gibbonRollGroupID=$gibbonRollGroupID&gibbonYearGroupID=$gibbonYearGroupID&minimumCount=$minimumCount&source=pattern'><img title='View Behaviour Records' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/zoom.png'/></a> " ;
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