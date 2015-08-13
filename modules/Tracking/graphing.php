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
		print _("Filter") ;
		print "</h2>" ;
	
		$gibbonPersonIDs=NULL ;
		if (isset($_POST["gibbonPersonIDs"])) {
			$gibbonPersonIDs=$_POST["gibbonPersonIDs"] ;
		}
		
		$gibbonDepartmentIDs=NULL ;
		if (isset($_POST["gibbonDepartmentIDs"])) {
			$gibbonDepartmentIDs=$_POST["gibbonDepartmentIDs"] ;
		}
		
		$dataType=NULL ;
		if (isset($_POST["dataType"])) {
			$dataType=$_POST["dataType"] ;
		}
		?>
	
		<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"]?>/index.php?q=/modules/Tracking/graphing.php">
			<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
				<tr>
					<td style='width: 275px'> 
						<b><?php print _('Student') ?> *</b><br/>
						<span style="font-size: 90%"><i><?php print _('Use Control, Command and/or Shift to select multiple.') ?></i></span>
					</td>
					<td class="right">
						<select name="gibbonPersonIDs[]" id="gibbonPersonIDs[]" multiple style="width: 302px; height: 150px">
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
									if (is_array($gibbonPersonIDs)) {
										foreach ($gibbonPersonIDs AS $gibbonPersonID) {
											if ($rowSelect["gibbonPersonID"]==$gibbonPersonID) {
												$selected="selected" ;
											}
										}
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
									$selected="" ;
									if (is_array($gibbonPersonIDs)) {
										foreach ($gibbonPersonIDs AS $gibbonPersonID) {
											if ($rowSelect["gibbonPersonID"]==$gibbonPersonID) {
												$selected="selected" ;
											}
										}
									}
									print "<option $selected value='" . $rowSelect["gibbonPersonID"] . "'>" . formatName("", htmlPrep($rowSelect["preferredName"]), htmlPrep($rowSelect["surname"]), "Student", true) . " (" . htmlPrep($rowSelect["name"]) . ")</option>" ;
								}
								?>
							</optgroup>
						</select>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print _('Data Type') ?> *</b><br/>
					</td>
					<td class="right">
						<?php
						$attainmentTitle="Attainment" ;
						$attainmentAlt=getSettingByScope($connection2, "Markbook", "attainmentAlternativeName") ;
						if ($attainmentAlt!="") {
							$attainmentTitle=$attainmentAlt ;
						}
						$effortTitle="Effort" ;
						$effortAlt=getSettingByScope($connection2, "Markbook", "effortAlternativeName") ;
						if ($effortAlt!="") {
							$effortTitle=$effortAlt ;
						}
						?>
						<select name="dataType" id="dataType" style="width: 302px">
							<option <?php if ($dataType=="attainment") { print "selected" ; } ?> value="attainment"><?php print _($attainmentTitle) ?></option>
							<option <?php if ($dataType=="effort") { print "selected" ; } ?> value="effort"><?php print _($effortTitle) ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print _('Learning Areas') ?> *</b><br/>
						<span style="font-size: 90%"><i><?php print _('Only Learning Areas for which the student has data will be displayed.') ?></i></span>
					</td>
					<td class="right">
						<?php 
						try {
							$dataLA=array(); 
							$sqlLA="SELECT * FROM gibbonDepartment WHERE type='Learning Area' ORDER BY name" ;
							$resultLA=$connection2->prepare($sqlLA);
							$resultLA->execute($dataLA);
						}
						catch(PDOException $e) { }	
						if ($resultLA->rowCount()<1) {
							print "<i>" . _('No Learning Areas available.') . "</i>" ;
						}
						else {
							while ($rowLA=$resultLA->fetch()) {
								$checked="" ;
								if ($gibbonDepartmentIDs!=NULL) {
									foreach ($gibbonDepartmentIDs AS $gibbonDepartmentID) {
										if ($gibbonDepartmentID==$rowLA["gibbonDepartmentID"]) {
											$checked="checked " ;
										}
									}
								}
								print _($rowLA["name"]) . " <input $checked type='checkbox' name='gibbonDepartmentIDs[]' value='" . $rowLA["gibbonDepartmentID"] . "'><br/>" ;
							}
						}
						?>
						<input type="hidden" name="count" value="<?php print (count($learningAreas))/2 ?>">
					</td>
				</tr>
				<tr>
					<td colspan=2 class="right">
						<input type="submit" value="<?php print _("Submit") ; ?>">
					</td>
				</tr>
			</table>
		</form>
		<?php
		if (count($_POST)>0) {
			if ($gibbonPersonIDs==NULL OR $gibbonDepartmentIDs==NULL OR ($dataType!="attainment" AND $dataType!="effort")) {
				print "<div class='error'>" ;
				print _("There are no records to display.") ;
				print "</div>" ;
			}
			else {
				$output="" ;
				print "<h2>" ;
				print _("Report Data") ;
				print "</h2>" ;
				print "<p>" ;
				print _("The chart below shows Years and Terms along the X axis, and mean Markbook grades, converted to a 0-1 scale, on the Y axis.") ;
				print "</p>" ;
			
				//GET DEPARTMENTS
				$departments=array() ;
				$departmentCount=0 ;
				$colours=getColourArray() ;	
				try {
					$dataDepartments=array(); 
					$departmentExtra_MB="" ;
					$departmentExtra_IA="" ;
					foreach ($gibbonDepartmentIDs AS $gibbonDepartmentID) { //INCLUDE ONLY SELECTED DEPARTMENTS
						$dataDepartments["department_MB" . $gibbonDepartmentID]=$gibbonDepartmentID ;
						$departmentExtra_MB.="gibbonDepartment.gibbonDepartmentID=:department_MB" . $gibbonDepartmentID . " OR " ;
						$dataDepartments["department_IA" . $gibbonDepartmentID]=$gibbonDepartmentID ;
						$departmentExtra_IA.="gibbonDepartment.gibbonDepartmentID=:department_IA" . $gibbonDepartmentID . " OR " ;
					}
					if ($departmentExtra_MB!="") {
						$departmentExtra_MB="AND (" . substr($departmentExtra_MB, 0, -4) . ")" ;
					}
					if ($departmentExtra_IA!="") {
						$departmentExtra_IA="AND (" . substr($departmentExtra_IA, 0, -4) . ")" ;
					}
					$personExtra_MB="" ;
					$personExtra_IA="" ;
					foreach ($gibbonPersonIDs AS $gibbonPersonID) { //INCLUDE ONLY SELECTED STUDENTS
						$dataDepartments["person_MB" . $gibbonPersonID]=$gibbonPersonID ;
						$personExtra_MB.="gibbonMarkbookEntry.gibbonPersonIDStudent=:person_MB" . $gibbonPersonID . " OR " ;
						$dataDepartments["person_IA" . $gibbonPersonID]=$gibbonPersonID ;
						$personExtra_IA.="gibbonInternalAssessmentEntry.gibbonPersonIDStudent=:person_IA" . $gibbonPersonID . " OR " ;
					}
					if ($personExtra_MB!="") {
						$personExtra_MB="AND (" . substr($personExtra_MB, 0, -4) . ")" ;
					}
					if ($personExtra_IA!="") {
						$personExtra_IA="AND (" . substr($personExtra_IA, 0, -4) . ")" ;
					}
					
					$sqlDepartments="(SELECT DISTINCT gibbonDepartment.name AS department
						FROM gibbonMarkbookEntry 
						JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID)
						JOIN gibbonCourseClass ON (gibbonMarkbookColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
						JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
						JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) 
						JOIN gibbonScale ON (gibbonMarkbookColumn.gibbonScaleID" . ucfirst($dataType) . "=gibbonScale.gibbonScaleID) 
						JOIN gibbonSchoolYearTerm ON (gibbonSchoolYearTerm.firstDay<=completeDate AND gibbonSchoolYearTerm.lastDay>=completeDate)
						JOIN gibbonSchoolYear ON (gibbonSchoolYearTerm.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
						WHERE complete='Y' AND completeDate<='" . date("Y-m-d") . "' AND (SELECT count(*) FROM gibbonScaleGrade WHERE gibbonScaleID=gibbonScale.gibbonScaleID)>3 AND " . $dataType . "Value!='' AND " . $dataType . "Value IS NOT NULL $departmentExtra_MB $personExtra_MB)
						UNION
						(SELECT DISTINCT gibbonDepartment.name AS department
						FROM gibbonInternalAssessmentEntry 
						JOIN gibbonInternalAssessmentColumn ON (gibbonInternalAssessmentEntry.gibbonInternalAssessmentColumnID=gibbonInternalAssessmentColumn.gibbonInternalAssessmentColumnID)
						JOIN gibbonCourseClass ON (gibbonInternalAssessmentColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
						JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
						JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) 
						JOIN gibbonScale ON (gibbonInternalAssessmentColumn.gibbonScaleID" . ucfirst($dataType) . "=gibbonScale.gibbonScaleID) 
						JOIN gibbonSchoolYearTerm ON (gibbonSchoolYearTerm.firstDay<=completeDate AND gibbonSchoolYearTerm.lastDay>=completeDate)
						JOIN gibbonSchoolYear ON (gibbonSchoolYearTerm.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
						WHERE complete='Y' AND completeDate<='" . date("Y-m-d") . "' AND (SELECT count(*) FROM gibbonScaleGrade WHERE gibbonScaleID=gibbonScale.gibbonScaleID)>3 AND " . $dataType . "Value!='' AND " . $dataType . "Value IS NOT NULL $departmentExtra_IA $personExtra_IA)
						ORDER BY department" ;
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
					$dataGrades=array(); 
					$departmentExtra_MB="" ;
					$departmentExtra_IA="" ;
					foreach ($gibbonDepartmentIDs AS $gibbonDepartmentID) { //INCLUDE ONLY SELECTED DEPARTMENTS
						$dataGrades["department_MB" . $gibbonDepartmentID]=$gibbonDepartmentID ;
						$departmentExtra_MB.="gibbonDepartment.gibbonDepartmentID=:department_MB" . $gibbonDepartmentID . " OR " ;
						$dataGrades["department_IA" . $gibbonDepartmentID]=$gibbonDepartmentID ;
						$departmentExtra_IA.="gibbonDepartment.gibbonDepartmentID=:department_IA" . $gibbonDepartmentID . " OR " ;
					}
					if ($departmentExtra_MB!="") {
						$departmentExtra_MB="AND (" . substr($departmentExtra_MB, 0, -4) . ")" ;
					}
					if ($departmentExtra_IA!="") {
						$departmentExtra_IA="AND (" . substr($departmentExtra_IA, 0, -4) . ")" ;
					}
					$personExtra_MB="" ;
					$personExtra_IA="" ;
					foreach ($gibbonPersonIDs AS $gibbonPersonID) { //INCLUDE ONLY SELECTED STUDENTS
						$dataGrades["person_MB" . $gibbonPersonID]=$gibbonPersonID ;
						$personExtra_MB.="gibbonMarkbookEntry.gibbonPersonIDStudent=:person_MB" . $gibbonPersonID . " OR " ;
						$dataGrades["person_IA" . $gibbonPersonID]=$gibbonPersonID ;
						$personExtra_IA.="gibbonInternalAssessmentEntry.gibbonPersonIDStudent=:person_IA" . $gibbonPersonID . " OR " ;
					}
					if ($personExtra_MB!="") {
						$personExtra_MB="AND (" . substr($personExtra_MB, 0, -4) . ")" ;
					}
					if ($personExtra_IA!="") {
						$personExtra_IA="AND (" . substr($personExtra_IA, 0, -4) . ")" ;
					}
					$sqlGrades="(SELECT gibbonSchoolYearTerm.sequenceNumber AS termSequence, gibbonSchoolYear.sequenceNumber AS yearSequence, gibbonSchoolYear.name AS year, gibbonSchoolYearTerm.name AS term, gibbonSchoolYearTerm.gibbonSchoolYearTermID AS termID, gibbonDepartment.name AS department, gibbonMarkbookColumn.name AS markbook, completeDate, " . $dataType . ", gibbonScaleID" . ucfirst($dataType) . ", " . $dataType . "Value, " . $dataType . "Descriptor, (SELECT count(*) FROM gibbonScaleGrade WHERE gibbonScaleID=gibbonScale.gibbonScaleID) AS totalGrades, (SELECT count(*) FROM gibbonScaleGrade WHERE gibbonScaleID=gibbonScale.gibbonScaleID AND sequenceNumber>=(SELECT sequenceNumber FROM gibbonScaleGrade WHERE gibbonScaleID=gibbonScale.gibbonScaleID AND value=gibbonMarkbookEntry." . $dataType . "Value) ORDER BY sequenceNumber DESC) AS gradePosition
						FROM gibbonMarkbookEntry 
						JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID)
						JOIN gibbonCourseClass ON (gibbonMarkbookColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
						JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
						JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) 
						JOIN gibbonScale ON (gibbonMarkbookColumn.gibbonScaleID" . ucfirst($dataType) . "=gibbonScale.gibbonScaleID) 
						JOIN gibbonSchoolYearTerm ON (gibbonSchoolYearTerm.firstDay<=completeDate AND gibbonSchoolYearTerm.lastDay>=completeDate)
						JOIN gibbonSchoolYear ON (gibbonSchoolYearTerm.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
						WHERE complete='Y' AND completeDate<='" . date("Y-m-d") . "' AND (SELECT count(*) FROM gibbonScaleGrade WHERE gibbonScaleID=gibbonScale.gibbonScaleID)>3 AND " . $dataType . "Value!='' AND " . $dataType. "Value IS NOT NULL $departmentExtra_MB $personExtra_MB)
						UNION
						(SELECT gibbonSchoolYearTerm.sequenceNumber AS termSequence, gibbonSchoolYear.sequenceNumber AS yearSequence, gibbonSchoolYear.name AS year, gibbonSchoolYearTerm.name AS term, gibbonSchoolYearTerm.gibbonSchoolYearTermID AS termID, gibbonDepartment.name AS department, gibbonInternalAssessmentColumn.name AS markbook, completeDate, " . $dataType . ", gibbonScaleID" . ucfirst($dataType) . ", " . $dataType . "Value, " . $dataType . "Descriptor, (SELECT count(*) FROM gibbonScaleGrade WHERE gibbonScaleID=gibbonScale.gibbonScaleID) AS totalGrades, (SELECT count(*) FROM gibbonScaleGrade WHERE gibbonScaleID=gibbonScale.gibbonScaleID AND sequenceNumber>=(SELECT sequenceNumber FROM gibbonScaleGrade WHERE gibbonScaleID=gibbonScale.gibbonScaleID AND value=gibbonInternalAssessmentEntry." . $dataType . "Value) ORDER BY sequenceNumber DESC) AS gradePosition
						FROM gibbonInternalAssessmentEntry 
						JOIN gibbonInternalAssessmentColumn ON (gibbonInternalAssessmentEntry.gibbonInternalAssessmentColumnID=gibbonInternalAssessmentColumn.gibbonInternalAssessmentColumnID)
						JOIN gibbonCourseClass ON (gibbonInternalAssessmentColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
						JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
						JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) 
						JOIN gibbonScale ON (gibbonInternalAssessmentColumn.gibbonScaleID" . ucfirst($dataType) . "=gibbonScale.gibbonScaleID) 
						JOIN gibbonSchoolYearTerm ON (gibbonSchoolYearTerm.firstDay<=completeDate AND gibbonSchoolYearTerm.lastDay>=completeDate)
						JOIN gibbonSchoolYear ON (gibbonSchoolYearTerm.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
						WHERE complete='Y' AND completeDate<='" . date("Y-m-d") . "' AND (SELECT count(*) FROM gibbonScaleGrade WHERE gibbonScaleID=gibbonScale.gibbonScaleID)>3 AND " . $dataType . "Value!='' AND " . $dataType. "Value IS NOT NULL $departmentExtra_IA $personExtra_IA)
						ORDER BY yearSequence, termSequence, completeDate, markbook" ;
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
						$grades[$gradeCount][$dataType]=$rowGrades[$dataType] ;
						$grades[$gradeCount]["gibbonScaleID" . ucfirst($dataType)]=$rowGrades["gibbonScaleID" . ucfirst($dataType)] ;
						$grades[$gradeCount][$dataType . "Value"]=$rowGrades[$dataType . "Value"] ;
						$grades[$gradeCount][$dataType . "Descriptor"]=$rowGrades[$dataType . "Descriptor"] ;
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
						print "<table class='noIntBorder' style='width: 100%;  border-spacing: 0; border-collapse: collapse;'>" ;
							$columns=8 ;
							$columnCount=0 ;
							foreach ($departments AS $department) {
								if ($columnCount%$columns==0) {
									print "<tr>" ;
								}
								print "<td style='vertical-align: middle!important; height: 35px; width: 25px; border-right-width: 0px!important'>" ;
									print "<div style='width: 25px; height: 25px; border: 2px solid rgb(" . $department["colour"] . "); background-color: rgba(" . $department["colour"] . ", 0.8) '></div>" ;
								print "</td>" ;
								print "<td style='vertical-align: middle!important; height: 35px; width: " . (100/$columns) . "%'>" ;
									print "<b>" . $department["department"] . "</b>" ;
								print "</td>" ;
								
								if ($columnCount%$columns==7) {
									print "</tr>" ;
								}
								
								$columnCount++ ;
							}
							if ($columnCount%$columns!=0) {
								for ($i=($columnCount%$columns); $i<$columns; $i++) {
									print "<td colspan=2 style='width: " . (100/$columns) . "%'>" ;
								
									print "</td>" ;
								}
							}
							if ($columnCount%$columns!=7) {
								print "</tr>" ;
							}
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
									showTooltips: false,
									scaleOverride : true,
									scaleSteps : 10,
									scaleStepWidth : 0.1,
									scaleStartValue : 0
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