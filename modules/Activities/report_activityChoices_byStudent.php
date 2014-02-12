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

if (isActionAccessible($guid, $connection2, "/modules/Activities/report_activitySpread_rollGroup.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>Activity Choices By Student</div>" ;
	print "</div>" ;
	print "<p>" ;
	print "This report shows the current and historical activities that a student has enrolled in." ;
	print "</p>" ;
	
	print "<h2>" ;
	print "Choose Student" ;
	print "</h2>" ;
	
	$gibbonPersonID=NULL ;
	if (isset($_GET["gibbonPersonID"])) {
		$gibbonPersonID=$_GET["gibbonPersonID"] ;
	}
	?>
	
	<form method="get" action="<? print $_SESSION[$guid]["absoluteURL"]?>/index.php">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<tr>
				<td> 
					<b>Student *</b><br/>
				</td>
				<td class="right">
					<select name="gibbonPersonID" id="gibbonPersonID" style="width: 302px">
						<option></option>
						<optgroup label='--Students by Roll Group--'>
							<?
							try {
								$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
								$sqlSelect="SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS name FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID AND status='FULL' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name, surname, preferredName" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) { }
							while ($rowSelect=$resultSelect->fetch()) {
								$selected="" ;
								if ($rowSelect["gibbonPersonID"]==$gibbonPersonID) {
									$selected="selected" ;
								}
								print "<option $selected value='" . $rowSelect["gibbonPersonID"] . "'>" . htmlPrep($rowSelect["name"]) . " - " . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . "</option>" ;
							}
							?>
						</optgroup>
						<optgroup label='--Students by Name--'>
							<?
							try {
								$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
								$sqlSelect="SELECT gibbonPerson.gibbonPersonID, preferredName, surname, gibbonRollGroup.name AS name FROM gibbonPerson, gibbonStudentEnrolment, gibbonRollGroup WHERE gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID AND status='FULL' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName" ;
								$resultSelect=$connection2->prepare($sqlSelect);
								$resultSelect->execute($dataSelect);
							}
							catch(PDOException $e) { }
							while ($rowSelect=$resultSelect->fetch()) {
								print "<option value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . " (" . htmlPrep($rowSelect["name"]) . ")</option>" ;
							}
							?>
						</optgroup>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan=2 class="right">
					<input type="hidden" name="q" value="/modules/<? print $_SESSION[$guid]["module"] ?>/report_activityChoices_byStudent.php">
					<input type="submit" value="Submit">
				</td>
			</tr>
		</table>
	</form>
	<?
	
	if ($gibbonPersonID!="") {
		$output="" ;
		print "<h2>" ;
		print "Report Data" ;
		print "</h2>" ;
		
		try {
			$dataYears=array("gibbonPersonID"=>$gibbonPersonID); 
			$sqlYears="SELECT * FROM gibbonStudentEnrolment JOIN gibbonSchoolYear ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonPersonID=:gibbonPersonID ORDER BY sequenceNumber DESC" ;
			$resultYears=$connection2->prepare($sqlYears);
			$resultYears->execute($dataYears);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}

		if ($resultYears->rowCount()<1) {
			print "<div class='error'>" ;
			print "The specified student has not been enrolled in any school years." ;
			print "</div>" ;
		}
		else {
			$yearCount=0 ;
			while ($rowYears=$resultYears->fetch()) {
			
				$class="" ;
				if ($yearCount==0) {
					$class="class='top'" ;
				}
				print "<h3 $class>" ;
				print $rowYears["name"] ;
				print "</h3>" ;
			
				$yearCount++ ;
		
				try {
					$data=array("gibbonPersonID"=>$gibbonPersonID, "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
					$sql="SELECT gibbonPerson.gibbonPersonID, surname, preferredName, name FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonPersonID=:gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}

				if ($result->rowCount()!=1) {
					print "<div class='error'>" ;
						print "The selected student does not seem to exist." ;
					print "</div>" ;
				}
				else {
					$dateType=getSettingByScope($connection2, 'Activities', 'dateType') ;
					if ($dateType=="Term" ) {
						$maxPerTerm=getSettingByScope($connection2, 'Activities', 'maxPerTerm') ;
					}
				
					try {
						$data=array("gibbonPersonID"=>$gibbonPersonID, "gibbonSchoolYearID"=>$rowYears["gibbonSchoolYearID"]); 
						$sql="SELECT gibbonActivity.*, gibbonActivityStudent.status, NULL AS role FROM gibbonActivity JOIN gibbonActivityStudent ON (gibbonActivity.gibbonActivityID=gibbonActivityStudent.gibbonActivityID) WHERE gibbonActivityStudent.gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' ORDER BY name" ; 
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
		
					if ($result->rowCount()<1) {
						print "<div class='error'>" ;
						print "There are no records to display." ;
						print "</div>" ;
					}
					else {
						print "<table cellspacing='0' style='width: 100%'>" ;
							print "<tr class='head'>" ;
								print "<th>" ;
									print "Activity" ;
								print "</th>" ;
								$options=getSettingByScope($connection2, "Activities", "activityTypes") ;
								if ($options!="") {
									print "<th>" ;
										print "Type" ;
									print "</th>" ;
								}
								print "<th>" ;
									if ($dateType!="Date") {
										print "Term" ;
									}
									else {
										print "Dates" ;
									}
								print "</th>" ;
								print "<th>" ;
									print "Status" ;
								print "</th>" ;
								print "<th>" ;
									print "Actions" ;
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
										print $row["name"] ;
									print "</td>" ;
									if ($options!="") {
										print "<td>" ;
											print trim($row["type"]) ;
										print "</td>" ;
									}
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
													print date("F", mktime(0, 0, 0, substr($row["programStart"],5,2))) . " - " . date("F", mktime(0, 0, 0, substr($row["programEnd"],5,2))) . "<br/>" . substr($row["programStart"],0,4) ;
												}
											}
											else {
												print date("F", mktime(0, 0, 0, substr($row["programStart"],5,2))) . " " . substr($row["programStart"],0,4) . " -<br/>" . date("F", mktime(0, 0, 0, substr($row["programEnd"],5,2))) . " " . substr($row["programEnd"],0,4) ;
											}
										}
									print "</td>" ;
									print "<td>" ;
										if ($row["status"]!="") {
											print $row["status"] ;
										}
										else {
											print "<i>NA</i>" ;
										}
									print "</td>" ;
									print "<td>" ;
										print "<a class='thickbox' href='" . $_SESSION[$guid]["absoluteURL"] . "/fullscreen.php?q=/modules/" . $_SESSION[$guid]["module"] . "/activities_my_full.php&gibbonActivityID=" . $row["gibbonActivityID"] . "&width=1000&height=550'><img title='View Details' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a> " ;
									print "</td>" ;
								print "</tr>" ;
							}
						print "</table>" ;		
					}
				}
			}
		}
	}
}
?>