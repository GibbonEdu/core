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

function externalAssessmentDetails($guid,  $gibbonPersonID, $connection2, $gibbonYearGroupID=NULL, $manage=FALSE, $search="" ) {
	try {
		$dataAssessments=array("gibbonPersonID"=>$gibbonPersonID); 
		$sqlAssessments="SELECT * FROM gibbonExternalAssessmentStudent JOIN gibbonExternalAssessment ON (gibbonExternalAssessmentStudent.gibbonExternalAssessmentID=gibbonExternalAssessment.gibbonExternalAssessmentID) WHERE gibbonPersonID=:gibbonPersonID ORDER BY date" ; 
		$resultAssessments=$connection2->prepare($sqlAssessments);
		$resultAssessments->execute($dataAssessments);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}

	if ($resultAssessments->rowCount()<1) {
		print "<div class='error'>" ;
		print "The specified student does not have any external assessments to display." ;
		print "</div>" ;
	}
	else {
		while ($rowAssessments=$resultAssessments->fetch()) {
			print "<h2>" ;
			print $rowAssessments["name"] . " <span style='font-size: 75%; font-style: italic'>(" . substr($rowAssessments["date"], 0, 4) . ")</span>" ;
			if ($manage==TRUE) {
				print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/externalAssessment_manage_details_edit.php&gibbonPersonID=$gibbonPersonID&gibbonExternalAssessmentStudentID=" . $rowAssessments["gibbonExternalAssessmentStudentID"] . "&search=$search'><img style='margin-left: 5px' title='" . _('Edit Record') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
				print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/externalAssessment_manage_details_delete.php&gibbonPersonID=$gibbonPersonID&gibbonExternalAssessmentStudentID=" . $rowAssessments["gibbonExternalAssessmentStudentID"] . "&search=$search'><img title='" . _('Delete Record') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a>" ;
			}
			print "</h2>" ;
			print "<p>" ;
			print $rowAssessments["description"] ;
			print "</p>" ;
			
			//Get results
			try {
				$dataResults=array("gibbonPersonID"=>$gibbonPersonID, "gibbonExternalAssessmentStudentID"=>$rowAssessments["gibbonExternalAssessmentStudentID"]); 
				$sqlResults="SELECT gibbonExternalAssessmentField.name, gibbonExternalAssessmentField.category, resultGrade.value, resultGrade.descriptor, result.usage, result.lowestAcceptable, resultGrade.sequenceNumber, gibbonScaleGradeIDPrimaryAssessmentScale, resultGradePrimary.value AS valuePrimary, resultGradePrimary.descriptor AS descriptorPrimary, resultPrimary.usage AS usagePrimary, resultPrimary.lowestAcceptable AS lowestAcceptablePrimary, resultGradePrimary.sequenceNumber AS sequenceNumberPrimary FROM gibbonExternalAssessmentStudentEntry JOIN gibbonExternalAssessmentStudent ON (gibbonExternalAssessmentStudentEntry.gibbonExternalAssessmentStudentID=gibbonExternalAssessmentStudent.gibbonExternalAssessmentStudentID) JOIN gibbonExternalAssessmentField ON (gibbonExternalAssessmentStudentEntry.gibbonExternalAssessmentFieldID=gibbonExternalAssessmentField.gibbonExternalAssessmentFieldID) JOIN gibbonExternalAssessment ON (gibbonExternalAssessment.gibbonExternalAssessmentID=gibbonExternalAssessmentField.gibbonExternalAssessmentID) JOIN gibbonScaleGrade AS resultGrade ON (gibbonExternalAssessmentStudentEntry.gibbonScaleGradeID=resultGrade.gibbonScaleGradeID) JOIN gibbonScale AS result ON (result.gibbonScaleID=resultGrade.gibbonScaleID) LEFT JOIN gibbonScaleGrade AS resultGradePrimary ON (gibbonExternalAssessmentStudentEntry.gibbonScaleGradeIDPrimaryAssessmentScale=resultGradePrimary.gibbonScaleGradeID) LEFT JOIN gibbonScale AS resultPrimary ON (resultPrimary.gibbonScaleID=resultGradePrimary.gibbonScaleID) WHERE gibbonPersonID=:gibbonPersonID AND result.active='Y' AND gibbonExternalAssessment.active='Y' AND gibbonExternalAssessmentStudentEntry.gibbonExternalAssessmentStudentID=:gibbonExternalAssessmentStudentID ORDER BY category, gibbonExternalAssessmentField.order" ;
				$resultResults=$connection2->prepare($sqlResults);
				$resultResults->execute($dataResults);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}

			if ($resultResults->rowCount()<1) {
				print "<div class='error'>" ;
				print _("There are no records to display.") ;
				print "</div>" ;
			}
			else {
				$lastCategory="" ;
				$count=0 ;
				$rowNum="odd" ;
				while ($rowResults=$resultResults->fetch()) {
					if ($rowResults["category"]!=$lastCategory) {
						if ($count!=0) {
							print "</table>" ;
						}
						print "<p style='font-weight: bold; margin-bottom: 0px'>" ;
						print substr($rowResults["category"], (strpos($rowResults["category"], "_")+1)) ;
						print "</p>" ;
						
						print "<table cellspacing='0' style='width: 100%'>" ;
						print "<tr class='head'>" ;
							print "<th style='width:40%'>" ;
								print "Item" ;
							print "</th>" ;
							print "<th style='width:15%'>" ;
								print "Result" ;
							print "</th>" ;
							print "<th style='width:15%'>" ;
								print "<span title='Primary assessment scale equivalent'>PAS Equivalent</span>" ;
							print "</th>" ;
							print "<th style='width:15%'>" ;
								print "<span title='Weighted average from subject-related markbook grades in the current year'>Markbook<br/>Average</span>" ;
							print "</th>" ;
							print "<th style='width:15%'>" ;
								print "<span title='Plus/Minus Value Added'>+/-</span>" ;
							print "</th>" ;
						print "</tr>" ;
					}
					
					if ($count%2==0) {
						$rowNum="even" ;
					}
					else {
						$rowNum="odd" ;
					}
					
					//COLOR ROW BY STATUS!
					print "<tr class=$rowNum>" ;
						print "<td>" ;
							print $rowResults["name"] ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($rowResults["lowestAcceptable"]!="" AND $rowResults["sequenceNumber"]>$rowResults["lowestAcceptable"]) {
								$style="style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px'" ;
							}
							print "<span $style title='" . $rowResults["usage"] . "'>" . $rowResults["value"] . "</span>" ;
						print "</td>" ;
						print "<td>" ;
							if (!is_null($rowResults["gibbonScaleGradeIDPrimaryAssessmentScale"]) AND !is_null($_SESSION[$guid]["primaryAssessmentScale"])) {
								$style="" ;
								if ($rowResults["lowestAcceptablePrimary"]!="" AND $rowResults["sequenceNumberPrimary"]>$rowResults["lowestAcceptablePrimary"]) {
									$style="style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px'" ;
								}
								print "<span $style title='" . $rowResults["usagePrimary"] . "'>" . $rowResults["valuePrimary"] . "</span>" ;
							}
						print "</td>" ;
						print "<td>" ;
							$av=FALSE ;
							if (!is_null($rowResults["gibbonScaleGradeIDPrimaryAssessmentScale"]) AND !is_null($_SESSION[$guid]["primaryAssessmentScale"])) {
								try {
									$dataMB3=array("name"=>"%" . $rowResults["name"] . "%", "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$gibbonPersonID, "date"=>date("Y-m-d", (time()-(60*60*24*90)))); 
									$sqlMB3="SELECT attainmentValue FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonMarkbookColumn ON (gibbonMarkbookColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonMarkbookEntry ON (gibbonMarkbookColumn.gibbonMarkbookColumnID=gibbonMarkbookEntry.gibbonMarkbookColumnID) WHERE gibbonCourse.name LIKE :name AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND gibbonMarkbookEntry.gibbonPersonIDStudent=$gibbonPersonID AND gibbonScaleIDAttainment=" . $_SESSION[$guid]["primaryAssessmentScale"] . " AND completeDate>=:date" ;
									$resultMB3=$connection2->prepare($sqlMB3);
									$resultMB3->execute($dataMB3);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}

								
								$countMB3=$resultMB3->rowCount() ;
								$sumMB3=0 ;
								while ($rowMB3=$resultMB3->fetch()) {
									$sumMB3+=$rowMB3["attainmentValue"] ;
								}
								
								try {
									$dataMB12=array("name"=>"%" . $rowResults["name"] . "%", "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonID"=>$gibbonPersonID, "date"=> date("Y-m-d", (time()-(60*60*24*90)))); 
									$sqlMB12="SELECT attainmentValue FROM gibbonCourseClass JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonMarkbookColumn ON (gibbonMarkbookColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonMarkbookEntry ON (gibbonMarkbookColumn.gibbonMarkbookColumnID=gibbonMarkbookEntry.gibbonMarkbookColumnID) WHERE gibbonCourse.name LIKE :name AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND gibbonMarkbookEntry.gibbonPersonIDStudent=$gibbonPersonID AND gibbonScaleIDAttainment=" . $_SESSION[$guid]["primaryAssessmentScale"] . " AND completeDate>=:date" ;
									$resultMB12=$connection2->prepare($sqlMB12);
									$resultMB12->execute($dataMB12);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								
								$countMB12=$resultMB12->rowCount() ;
								$sumMB12=0 ;
								while ($rowMB12=$resultMB12->fetch()) {
									$sumMB12+=$rowMB12["attainmentValue"] ;
								}
								
								if ($countMB3>2 AND $countMB12<=2) {
									$av=round($sumMB3/$countMB3,2) ;
								}
								else if ($countMB3<=2 AND $countMB12>2) {
									$av=round($sumMB12/$countMB12,2) ;
								}
								else if ($countMB3>2 AND $countMB12>2) {
									$av=round((($sumMB3/$countMB3)*0.7)+(($sumMB12/$countMB12)*0.3),2) ;	
								}
								
								if ($av==FALSE) {
									print "<i>Insufficient data</i>" ;
								}
								else {
									print "<span title='" . $rowResults["usagePrimary"] . "'>" . $av . "</span>" ;
								}
							}
						print "</td>" ;
						print "<td>" ;
							if ($av!=FALSE) {
								$va=$av-$rowResults["valuePrimary"] ;
								$style="" ;
								if ($va<0) {
									$style="style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px'" ;
								}
								print "<span $style>$va</span>" ;
							}
						print "</td>" ;
					print "</tr>" ;
					
					$lastCategory=$rowResults["category"] ;
					$count++ ;
				}
				print "</table>" ;
			}
		}
	}
}
?>