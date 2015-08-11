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

if (isActionAccessible($guid, $connection2, "/modules/Tracking/graphing.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Get action with highest precendence
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print _("The highest grouped action cannot be determined.") ;
		print "</div>" ;
	}
	else {
		//Get action with highest precendence
		print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . _('Graphing') . "</div>" ;
		print "</div>" ;
	
		print "<h2>" ;
		print _("Choose Student") ;
		print "</h2>" ;
	
		$gibbonPersonID=NULL ;
		if (isset($_GET["gibbonPersonID"])) {
			$gibbonPersonID=$_GET["gibbonPersonID"] ;
		}
		?>
	
		<form method="get" action="<?php print $_SESSION[$guid]["absoluteURL"]?>/index.php">
			<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
				<tr>
					<td style='width: 275px'> 
						<b><?php print _('Student') ?> *</b><br/>
					</td>
					<td class="right">
						<select name="gibbonPersonID" id="gibbonPersonID" style="width: 302px">
							<option></option>
							<optgroup label='--<?php print _('Students by Roll Group') ?>--'>
								<?php
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
							<optgroup label='--<?php print _('Students by Name') ?>--'>
								<?php
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
						<input type="hidden" name="q" value="/modules/<?php print $_SESSION[$guid]["module"] ?>/graphing.php">
						<input type="submit" value="<?php print _("Submit") ; ?>">
					</td>
				</tr>
			</table>
		</form>
		<?php
	
		if ($gibbonPersonID!="") {
			$output="" ;
			print "<h2>" ;
			print _("Report Data") ;
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
				print _("There are no records to display.") ;
				print "</div>" ;
			}
			else {
				//GET DEPARTMENTS
				$departments=array() ;
				$departmentCount=0 ;
				$colours=getColourArray() ;	
				try {
					$dataDepartments=array("gibbonPersonIDStudent"=>$gibbonPersonID); 
					$sqlDepartments="SELECT DISTINCT gibbonDepartment.name AS department
						FROM gibbonMarkbookEntry 
						JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID)
						JOIN gibbonCourseClass ON (gibbonMarkbookColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
						JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
						JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) 
						JOIN gibbonScale ON (gibbonMarkbookColumn.gibbonScaleIDAttainment=gibbonScale.gibbonScaleID) 
						JOIN gibbonSchoolYearTerm ON (gibbonSchoolYearTerm.firstDay<=completeDate AND gibbonSchoolYearTerm.lastDay>=completeDate)
						JOIN gibbonSchoolYear ON (gibbonSchoolYearTerm.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
						WHERE gibbonPersonIDStudent=:gibbonPersonIDStudent AND complete='Y' AND completeDate<='" . date("Y-m-d") . "' AND (SELECT count(*) FROM gibbonScaleGrade WHERE gibbonScaleID=gibbonScale.gibbonScaleID)>3 AND attainmentValue!='' AND attainmentValue IS NOT NULL
						ORDER BY gibbonDepartment.name" ;
					$resultDepartments=$connection2->prepare($sqlDepartments);
					$resultDepartments->execute($dataDepartments);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				while ($rowDepartments=$resultDepartments->fetch()) { 
					$departments[$departmentCount]["department"]=$rowDepartments["department"] ;
					$departments[$departmentCount]["colour"]=$colours[$departmentCount%12] ;
					$departmentCount++ ;
				}
			
				//GET GRADES & TERMS
				try {
					$dataGrades=array("gibbonPersonIDStudent"=>$gibbonPersonID); 
					$sqlGrades="SELECT gibbonSchoolYear.name AS year, gibbonSchoolYearTerm.nameShort AS term, gibbonSchoolYearTerm.gibbonSchoolYearTermID AS termID, gibbonDepartment.name AS department, gibbonMarkbookColumn.name AS markbook, completeDate, attainment, gibbonScaleIDAttainment, attainmentValue, attainmentDescriptor, (SELECT count(*) FROM gibbonScaleGrade WHERE gibbonScaleID=gibbonScale.gibbonScaleID) AS totalGrades, (SELECT count(*) FROM gibbonScaleGrade WHERE gibbonScaleID=gibbonScale.gibbonScaleID AND sequenceNumber>=(SELECT sequenceNumber FROM gibbonScaleGrade WHERE gibbonScaleID=gibbonScale.gibbonScaleID AND value=gibbonMarkbookEntry.attainmentValue) ORDER BY sequenceNumber DESC) AS gradePosition
						FROM gibbonMarkbookEntry 
						JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID)
						JOIN gibbonCourseClass ON (gibbonMarkbookColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
						JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
						JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) 
						JOIN gibbonScale ON (gibbonMarkbookColumn.gibbonScaleIDAttainment=gibbonScale.gibbonScaleID) 
						JOIN gibbonSchoolYearTerm ON (gibbonSchoolYearTerm.firstDay<=completeDate AND gibbonSchoolYearTerm.lastDay>=completeDate)
						JOIN gibbonSchoolYear ON (gibbonSchoolYearTerm.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
						WHERE gibbonPersonIDStudent=:gibbonPersonIDStudent AND complete='Y' AND completeDate<='" . date("Y-m-d") . "' AND (SELECT count(*) FROM gibbonScaleGrade WHERE gibbonScaleID=gibbonScale.gibbonScaleID)>3 AND attainmentValue!='' AND attainmentValue IS NOT NULL
						ORDER BY gibbonSchoolYear.sequenceNumber, gibbonSchoolYearTerm.sequenceNumber, completeDate, gibbonMarkbookColumn.name" ;
					$resultGrades=$connection2->prepare($sqlGrades);
					$resultGrades->execute($dataGrades);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				
				if ($resultGrades->rowCount()<1) {
					print "<div class='error'>" ;
					print _("There are no records to display.") ;
					print "</div>" ;
				}
				else {
					//Prep grades & terms
					$grades=array() ;
					$gradeCount=0 ;
					$lastDepartment="" ;
					$terms=array() ;
					$termCount=0 ;
					$lastTerm="" ;
					while ($rowGrades=$resultGrades->fetch()) { 
						//Store grades
						$grades[$gradeCount]["department"]=$rowGrades["department"] ;
						$grades[$gradeCount]["year"]=$rowGrades["year"] ;
						$grades[$gradeCount]["term"]=$rowGrades["term"] ;
						$grades[$gradeCount]["termID"]=$rowGrades["termID"] ;
						$grades[$gradeCount]["markbook"]=$rowGrades["markbook"] ;
						$grades[$gradeCount]["completeDate"]=$rowGrades["completeDate"] ;
						$grades[$gradeCount]["attainment"]=$rowGrades["attainment"] ;
						$grades[$gradeCount]["gibbonScaleIDAttainment"]=$rowGrades["gibbonScaleIDAttainment"] ;
						$grades[$gradeCount]["attainmentValue"]=$rowGrades["attainmentValue"] ;
						$grades[$gradeCount]["attainmentDescriptor"]=$rowGrades["attainmentDescriptor"] ;
						$grades[$gradeCount]["totalGrades"]=$rowGrades["totalGrades"] ;
						$grades[$gradeCount]["gradePosition"]=$rowGrades["gradePosition"] ;
						$grades[$gradeCount]["gradeWeighted"]=round($rowGrades["gradePosition"]/$rowGrades["totalGrades"], 2) ;
						
						//Store terms for axis
						if ($lastTerm!=$rowGrades["term"]) {
							$terms[$termCount]["year"]=$rowGrades["year"] ;
							$terms[$termCount]["term"]=$rowGrades["term"] ;
							$terms[$termCount]["termID"]=$rowGrades["termID"] ;
							$terms[$termCount]["termFullName"]=$rowGrades["year"] . " " . $rowGrades["term"] ;
							$termCount++ ;
						}
						$lastTerm=$rowGrades["term"] ;
						
						$gradeCount++ ;
					}
				
					//POPULATE FINAL DATA
					$finalData=array() ;
					foreach ($terms AS $term) {
						foreach ($departments AS $department) {
							$finalData[$term["termID"]][$department["department"]]["termID"]=$term["termID"] ;
							$finalData[$term["termID"]][$department["department"]]["termFullName"]=$term["termFullName"] ;
							$finalData[$term["termID"]][$department["department"]]["department"]=$department["department"] ;
							$finalData[$term["termID"]][$department["department"]]["gradeWeightedTotal"]=NULL ;
							$finalData[$term["termID"]][$department["department"]]["gradeWeightedDivisor"]=0 ;
							$finalData[$term["termID"]][$department["department"]]["gradeWeightedMean"]=NULL ;
						
							foreach ($grades AS $grade) {
								if ($grade["termID"]==$term["termID"] AND $grade["department"]==$department["department"]) {
									$finalData[$term["termID"]][$department["department"]]["gradeWeightedTotal"]+=$grade["gradeWeighted"] ;
									$finalData[$term["termID"]][$department["department"]]["gradeWeightedDivisor"]++ ;
								}
							}
						}
					}
				
					//CALCULATE AVERAGES
					foreach($departments AS $department) {
						foreach ($terms AS $term) {
							if ($finalData[$term["termID"]][$department["department"]]["gradeWeightedDivisor"]>0) {
								$finalData[$term["termID"]][$department["department"]]["gradeWeightedMean"]=round(($finalData[$term["termID"]][$department["department"]]["gradeWeightedTotal"]/$finalData[$term["termID"]][$department["department"]]["gradeWeightedDivisor"]) , 2) ;
							}	
							else {
								$finalData[$term["termID"]][$department["department"]]["gradeWeightedMean"]="null" ;
							}
						}
					}

				
					if (count($grades)<5) {
						print "<div class='error'>" ;
						print _("The are less than 4 data points, so no graph can be produced.") ;
						print "</div>" ;
					}
					else {
						//CREATE LEGEND
						print "<p style='margin-top: 20px; margin-bottom: 5px'><b>" . _('Legend') . "</b></p>" ;
						print "<table class='noIntBorder' style='width: 100%'>" ;
							print "<tr>" ;
								foreach ($departments AS $department) {
									print "<td style='vertical-align: middle!important; height: 35px; width: 25px; border-right-width: 0px!important'>" ;
										print "<div style='width: 25px; height: 25px; border: 2px solid rgb(" . $department["colour"] . "); background-color: rgba(" . $department["colour"] . ", 0.8) '></div>" ;
									print "</td>" ;
									print "<td style='vertical-align: middle!important; height: 35px'>" ;
										print "<b>" . $department["department"] . "</b>" ;
									print "</td>" ;
								}
							print "</tr>" ;
						print "</table>" ;
					
					
						//PLOT DATA
						print "<script type=\"text/javascript\" src=\"" . $_SESSION[$guid]["absoluteURL"] . "/lib/Chart.js/Chart.min.js\"></script>" ;
				
						print "<p style='margin-top: 20px; margin-bottom: 5px'><b>" . _('Data') . "</b></p>" ;
						print "<div style=\"width:100%\">" ;
							print "<div>" ;
								print "<canvas id=\"canvas\"></canvas>" ;
							print "</div>" ;
						print "</div>" ;
				
						?>
						<script>
							var lineChartData = {
								labels : [
									<?php
										foreach ($terms AS $term) {
											print "'" . $term["termFullName"] . "'," ;
										}
									?>
								],
								datasets : [
									<?php
										foreach($departments AS $department) {
											?>
											{
												fillColor : "rgba(<?php print $department["colour"] ?>,0)",
												strokeColor : "rgba(<?php print $department["colour"] ?>,1)",
												pointColor : "rgba(<?php print $department["colour"] ?>,1)",
												pointStrokeColor : "rgba(<?php print $department["colour"] ?>,0.4)",
												pointHighlightFill : "rgba(<?php print $department["colour"] ?>,4)",
												pointHighlightStroke : "rgba(<?php print $department["colour"] ?>,0.1)",
												data : [
													<?php
														foreach ($terms AS $term) {
															if ($finalData[$term["termID"]][$department["department"]]["termID"]==$term["termID"]) {
																if ($finalData[$term["termID"]][$department["department"]]["department"]==$department["department"]) {
																	print $finalData[$term["termID"]][$department["department"]]["gradeWeightedMean"] . "," ;
																}
															}
														}
													?>
												]
											},
										<?php
										}
									?>
								]

							}

							window.onload = function(){
								var ctx = document.getElementById("canvas").getContext("2d");
								window.myLine = new Chart(ctx).Line(lineChartData, {
									responsive: true,
									showTooltips: false
								});
							}
						</script>
						<?php
					}
				}
			}
		}
	}	
}
?>