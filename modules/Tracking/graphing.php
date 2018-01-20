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

@session_start();

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Tracking/graphing.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        //Get action with highest precendence
        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Graphing').'</div>';
        echo '</div>';

        echo '<h2>';
        echo __($guid, 'Filter');
        echo '</h2>';

        $gibbonPersonIDs = (isset($_POST['gibbonPersonIDs']))? $_POST['gibbonPersonIDs'] : null;
        $gibbonDepartmentIDs = (isset($_POST['gibbonDepartmentIDs']))? $_POST['gibbonDepartmentIDs'] : null;
        $dataType = (isset($_POST['dataType']))? $_POST['dataType'] : null;

        $attainmentAlt = getSettingByScope($connection2, 'Markbook', 'attainmentAlternativeName');
        $effortAlt = getSettingByScope($connection2, 'Markbook', 'effortAlternativeName');
        $dataTypes = array(
            'attainment' => (!empty($attainmentAlt))? $attainmentAlt : __('Attainment'),
            'effort' => (!empty($effortAlt))? $effortAlt : __('Effort'),
        );

        $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Tracking/graphing.php');
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->addHiddenValue('address', $_SESSION[$guid]['address']);

        $row = $form->addRow();
            $row->addLabel('gibbonPersonIDs', __('Student'));
            $row->addSelectStudent('gibbonPersonIDs', $_SESSION[$guid]['gibbonSchoolYearID'], array('byName' => true, 'byRoll' => true))->selectMultiple()->isRequired();
        $row = $form->addRow();
            $row->addLabel('dataType', __('Data Type'));
            $row->addSelect('dataType')->fromArray($dataTypes)->isRequired();

        $sql = "SELECT gibbonDepartmentID as value, name FROM gibbonDepartment WHERE type='Learning Area' ORDER BY name";
        $results = $pdo->executeQuery(array(), $sql);

        $row = $form->addRow();
        $row->addLabel('gibbonDepartmentIDs', __('Learning Areas'))
            ->description(__('Only Learning Areas for which the student has data will be displayed.'));
            if ($results->rowCount() == 0) {
                $row->addContent(__('No Learning Areas available.'))->wrap('<i>', '</i>');
            } else {
                $row->addCheckbox('gibbonDepartmentIDs')->fromResults($results)->addCheckAllNone();
            }

        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

        $form->loadAllValuesFrom($_POST);

        echo $form->getOutput();

        if (count($_POST) > 0) {
            if ($gibbonPersonIDs == null or $gibbonDepartmentIDs == null or ($dataType != 'attainment' and $dataType != 'effort')) {
                echo "<div class='error'>";
                echo __($guid, 'There are no records to display.');
                echo '</div>';
            } else {
                $output = '';
                echo '<h2>';
                echo __($guid, 'Report Data');
                echo '</h2>';
                echo '<p>';
                echo __($guid, 'The chart below shows Years and Terms along the X axis, and mean Markbook grades, converted to a 0-1 scale, on the Y axis.');
                echo '</p>';

                //GET DEPARTMENTS
                $departments = array();
                $departmentCount = 0;
                $colours = getColourArray();
                try {
                    $dataDepartments = array();
                    $departmentExtra_MB = '';
                    $departmentExtra_IA = '';
                    foreach ($gibbonDepartmentIDs as $gibbonDepartmentID) { //INCLUDE ONLY SELECTED DEPARTMENTS
                        $dataDepartments['department_MB'.$gibbonDepartmentID] = $gibbonDepartmentID;
                        $departmentExtra_MB .= 'gibbonDepartment.gibbonDepartmentID=:department_MB'.$gibbonDepartmentID.' OR ';
                        $dataDepartments['department_IA'.$gibbonDepartmentID] = $gibbonDepartmentID;
                        $departmentExtra_IA .= 'gibbonDepartment.gibbonDepartmentID=:department_IA'.$gibbonDepartmentID.' OR ';
                    }
                    if ($departmentExtra_MB != '') {
                        $departmentExtra_MB = 'AND ('.substr($departmentExtra_MB, 0, -4).')';
                    }
                    if ($departmentExtra_IA != '') {
                        $departmentExtra_IA = 'AND ('.substr($departmentExtra_IA, 0, -4).')';
                    }
                    $personExtra_MB = '';
                    $personExtra_IA = '';
                    foreach ($gibbonPersonIDs as $gibbonPersonID) { //INCLUDE ONLY SELECTED STUDENTS
                        $dataDepartments['person_MB'.$gibbonPersonID] = $gibbonPersonID;
                        $personExtra_MB .= 'gibbonMarkbookEntry.gibbonPersonIDStudent=:person_MB'.$gibbonPersonID.' OR ';
                        $dataDepartments['person_IA'.$gibbonPersonID] = $gibbonPersonID;
                        $personExtra_IA .= 'gibbonInternalAssessmentEntry.gibbonPersonIDStudent=:person_IA'.$gibbonPersonID.' OR ';
                    }
                    if ($personExtra_MB != '') {
                        $personExtra_MB = 'AND ('.substr($personExtra_MB, 0, -4).')';
                    }
                    if ($personExtra_IA != '') {
                        $personExtra_IA = 'AND ('.substr($personExtra_IA, 0, -4).')';
                    }

                    $sqlDepartments = '(SELECT DISTINCT gibbonDepartment.name AS department
						FROM gibbonMarkbookEntry
						JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID)
						JOIN gibbonCourseClass ON (gibbonMarkbookColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
						JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
						JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID)
						JOIN gibbonScale ON (gibbonMarkbookColumn.gibbonScaleID'.ucfirst($dataType)."=gibbonScale.gibbonScaleID)
						JOIN gibbonSchoolYearTerm ON (gibbonSchoolYearTerm.firstDay<=completeDate AND gibbonSchoolYearTerm.lastDay>=completeDate)
						JOIN gibbonSchoolYear ON (gibbonSchoolYearTerm.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
						WHERE complete='Y' AND completeDate<='".date('Y-m-d')."' AND (SELECT count(*) FROM gibbonScaleGrade WHERE gibbonScaleID=gibbonScale.gibbonScaleID)>3 AND ".$dataType."Value!='' AND ".$dataType."Value IS NOT NULL $departmentExtra_MB $personExtra_MB)
						UNION
						(SELECT DISTINCT gibbonDepartment.name AS department
						FROM gibbonInternalAssessmentEntry
						JOIN gibbonInternalAssessmentColumn ON (gibbonInternalAssessmentEntry.gibbonInternalAssessmentColumnID=gibbonInternalAssessmentColumn.gibbonInternalAssessmentColumnID)
						JOIN gibbonCourseClass ON (gibbonInternalAssessmentColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
						JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
						JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID)
						JOIN gibbonScale ON (gibbonInternalAssessmentColumn.gibbonScaleID".ucfirst($dataType)."=gibbonScale.gibbonScaleID)
						JOIN gibbonSchoolYearTerm ON (gibbonSchoolYearTerm.firstDay<=completeDate AND gibbonSchoolYearTerm.lastDay>=completeDate)
						JOIN gibbonSchoolYear ON (gibbonSchoolYearTerm.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
						WHERE complete='Y' AND completeDate<='".date('Y-m-d')."' AND (SELECT count(*) FROM gibbonScaleGrade WHERE gibbonScaleID=gibbonScale.gibbonScaleID)>3 AND ".$dataType."Value!='' AND ".$dataType."Value IS NOT NULL $departmentExtra_IA $personExtra_IA)
						ORDER BY department";
                    $resultDepartments = $connection2->prepare($sqlDepartments);
                    $resultDepartments->execute($dataDepartments);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                while ($rowDepartments = $resultDepartments->fetch()) {
                    $departments[$departmentCount]['department'] = $rowDepartments['department'];
                    $departments[$departmentCount]['colour'] = $colours[$departmentCount % 12];
                    ++$departmentCount;
                }

                //GET GRADES & TERMS
                try {
                    $dataGrades = array();
                    $departmentExtra_MB = '';
                    $departmentExtra_IA = '';
                    foreach ($gibbonDepartmentIDs as $gibbonDepartmentID) { //INCLUDE ONLY SELECTED DEPARTMENTS
                        $dataGrades['department_MB'.$gibbonDepartmentID] = $gibbonDepartmentID;
                        $departmentExtra_MB .= 'gibbonDepartment.gibbonDepartmentID=:department_MB'.$gibbonDepartmentID.' OR ';
                        $dataGrades['department_IA'.$gibbonDepartmentID] = $gibbonDepartmentID;
                        $departmentExtra_IA .= 'gibbonDepartment.gibbonDepartmentID=:department_IA'.$gibbonDepartmentID.' OR ';
                    }
                    if ($departmentExtra_MB != '') {
                        $departmentExtra_MB = 'AND ('.substr($departmentExtra_MB, 0, -4).')';
                    }
                    if ($departmentExtra_IA != '') {
                        $departmentExtra_IA = 'AND ('.substr($departmentExtra_IA, 0, -4).')';
                    }
                    $personExtra_MB = '';
                    $personExtra_IA = '';
                    foreach ($gibbonPersonIDs as $gibbonPersonID) { //INCLUDE ONLY SELECTED STUDENTS
                        $dataGrades['person_MB'.$gibbonPersonID] = $gibbonPersonID;
                        $personExtra_MB .= 'gibbonMarkbookEntry.gibbonPersonIDStudent=:person_MB'.$gibbonPersonID.' OR ';
                        $dataGrades['person_IA'.$gibbonPersonID] = $gibbonPersonID;
                        $personExtra_IA .= 'gibbonInternalAssessmentEntry.gibbonPersonIDStudent=:person_IA'.$gibbonPersonID.' OR ';
                    }
                    if ($personExtra_MB != '') {
                        $personExtra_MB = 'AND ('.substr($personExtra_MB, 0, -4).')';
                    }
                    if ($personExtra_IA != '') {
                        $personExtra_IA = 'AND ('.substr($personExtra_IA, 0, -4).')';
                    }
                    $sqlGrades = '(SELECT gibbonSchoolYearTerm.sequenceNumber AS termSequence, gibbonSchoolYear.sequenceNumber AS yearSequence, gibbonSchoolYear.name AS year, gibbonSchoolYearTerm.name AS term, gibbonSchoolYearTerm.gibbonSchoolYearTermID AS termID, gibbonDepartment.name AS department, gibbonMarkbookColumn.name AS markbook, completeDate, '.$dataType.', gibbonScaleID'.ucfirst($dataType).', '.$dataType.'Value, '.$dataType.'Descriptor, (SELECT count(*) FROM gibbonScaleGrade WHERE gibbonScaleID=gibbonScale.gibbonScaleID) AS totalGrades, (SELECT count(*) FROM gibbonScaleGrade WHERE gibbonScaleID=gibbonScale.gibbonScaleID AND sequenceNumber>=(SELECT sequenceNumber FROM gibbonScaleGrade WHERE gibbonScaleID=gibbonScale.gibbonScaleID AND value=gibbonMarkbookEntry.'.$dataType.'Value) ORDER BY sequenceNumber DESC) AS gradePosition
						FROM gibbonMarkbookEntry
						JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID)
						JOIN gibbonCourseClass ON (gibbonMarkbookColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
						JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
						JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID)
						JOIN gibbonScale ON (gibbonMarkbookColumn.gibbonScaleID'.ucfirst($dataType)."=gibbonScale.gibbonScaleID)
						JOIN gibbonSchoolYearTerm ON (gibbonSchoolYearTerm.firstDay<=completeDate AND gibbonSchoolYearTerm.lastDay>=completeDate)
						JOIN gibbonSchoolYear ON (gibbonSchoolYearTerm.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
						WHERE complete='Y' AND completeDate<='".date('Y-m-d')."' AND (SELECT count(*) FROM gibbonScaleGrade WHERE gibbonScaleID=gibbonScale.gibbonScaleID)>3 AND ".$dataType."Value!='' AND ".$dataType."Value IS NOT NULL $departmentExtra_MB $personExtra_MB)
						UNION
						(SELECT gibbonSchoolYearTerm.sequenceNumber AS termSequence, gibbonSchoolYear.sequenceNumber AS yearSequence, gibbonSchoolYear.name AS year, gibbonSchoolYearTerm.name AS term, gibbonSchoolYearTerm.gibbonSchoolYearTermID AS termID, gibbonDepartment.name AS department, gibbonInternalAssessmentColumn.name AS markbook, completeDate, ".$dataType.', gibbonScaleID'.ucfirst($dataType).', '.$dataType.'Value, '.$dataType.'Descriptor, (SELECT count(*) FROM gibbonScaleGrade WHERE gibbonScaleID=gibbonScale.gibbonScaleID) AS totalGrades, (SELECT count(*) FROM gibbonScaleGrade WHERE gibbonScaleID=gibbonScale.gibbonScaleID AND sequenceNumber>=(SELECT sequenceNumber FROM gibbonScaleGrade WHERE gibbonScaleID=gibbonScale.gibbonScaleID AND value=gibbonInternalAssessmentEntry.'.$dataType.'Value) ORDER BY sequenceNumber DESC) AS gradePosition
						FROM gibbonInternalAssessmentEntry
						JOIN gibbonInternalAssessmentColumn ON (gibbonInternalAssessmentEntry.gibbonInternalAssessmentColumnID=gibbonInternalAssessmentColumn.gibbonInternalAssessmentColumnID)
						JOIN gibbonCourseClass ON (gibbonInternalAssessmentColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
						JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
						JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID)
						JOIN gibbonScale ON (gibbonInternalAssessmentColumn.gibbonScaleID'.ucfirst($dataType)."=gibbonScale.gibbonScaleID)
						JOIN gibbonSchoolYearTerm ON (gibbonSchoolYearTerm.firstDay<=completeDate AND gibbonSchoolYearTerm.lastDay>=completeDate)
						JOIN gibbonSchoolYear ON (gibbonSchoolYearTerm.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
						WHERE complete='Y' AND completeDate<='".date('Y-m-d')."' AND (SELECT count(*) FROM gibbonScaleGrade WHERE gibbonScaleID=gibbonScale.gibbonScaleID)>3 AND ".$dataType."Value!='' AND ".$dataType."Value IS NOT NULL $departmentExtra_IA $personExtra_IA)
						ORDER BY yearSequence, termSequence, completeDate, markbook";
                    $resultGrades = $connection2->prepare($sqlGrades);
                    $resultGrades->execute($dataGrades);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($resultGrades->rowCount() < 1) {
                    echo "<div class='error'>";
                    echo __($guid, 'There are no records to display.');
                    echo '</div>';
                } else {
                    //Prep grades & terms
                    $grades = array();
                    $gradeCount = 0;
                    $lastDepartment = '';
                    $terms = array();
                    $termCount = 0;
                    $lastTerm = '';
                    while ($rowGrades = $resultGrades->fetch()) {
                        //Store grades
                        $grades[$gradeCount]['department'] = $rowGrades['department'];
                        $grades[$gradeCount]['year'] = $rowGrades['year'];
                        $grades[$gradeCount]['term'] = $rowGrades['term'];
                        $grades[$gradeCount]['termID'] = $rowGrades['termID'];
                        $grades[$gradeCount]['markbook'] = $rowGrades['markbook'];
                        $grades[$gradeCount]['completeDate'] = $rowGrades['completeDate'];
                        $grades[$gradeCount][$dataType] = $rowGrades[$dataType];
                        $grades[$gradeCount]['gibbonScaleID'.ucfirst($dataType)] = $rowGrades['gibbonScaleID'.ucfirst($dataType)];
                        $grades[$gradeCount][$dataType.'Value'] = $rowGrades[$dataType.'Value'];
                        $grades[$gradeCount][$dataType.'Descriptor'] = $rowGrades[$dataType.'Descriptor'];
                        $grades[$gradeCount]['totalGrades'] = $rowGrades['totalGrades'];
                        $grades[$gradeCount]['gradePosition'] = $rowGrades['gradePosition'];
                        $grades[$gradeCount]['gradeWeighted'] = round($rowGrades['gradePosition'] / $rowGrades['totalGrades'], 2);

                        //Store terms for axis
                        if ($lastTerm != $rowGrades['term']) {
                            $terms[$termCount]['year'] = $rowGrades['year'];
                            $terms[$termCount]['term'] = $rowGrades['term'];
                            $terms[$termCount]['termID'] = $rowGrades['termID'];
                            $terms[$termCount]['termFullName'] = $rowGrades['year'].' '.$rowGrades['term'];
                            ++$termCount;
                        }
                        $lastTerm = $rowGrades['term'];

                        ++$gradeCount;
                    }

                    //POPULATE FINAL DATA
                    $finalData = array();
                    foreach ($terms as $term) {
                        foreach ($departments as $department) {
                            $finalData[$term['termID']][$department['department']]['termID'] = $term['termID'];
                            $finalData[$term['termID']][$department['department']]['termFullName'] = $term['termFullName'];
                            $finalData[$term['termID']][$department['department']]['department'] = $department['department'];
                            $finalData[$term['termID']][$department['department']]['gradeWeightedTotal'] = null;
                            $finalData[$term['termID']][$department['department']]['gradeWeightedDivisor'] = 0;
                            $finalData[$term['termID']][$department['department']]['gradeWeightedMean'] = null;

                            foreach ($grades as $grade) {
                                if ($grade['termID'] == $term['termID'] and $grade['department'] == $department['department']) {
                                    $finalData[$term['termID']][$department['department']]['gradeWeightedTotal'] += $grade['gradeWeighted'];
                                    ++$finalData[$term['termID']][$department['department']]['gradeWeightedDivisor'];
                                }
                            }
                        }
                    }

                    //CALCULATE AVERAGES
                    foreach ($departments as $department) {
                        foreach ($terms as $term) {
                            if ($finalData[$term['termID']][$department['department']]['gradeWeightedDivisor'] > 0) {
                                $finalData[$term['termID']][$department['department']]['gradeWeightedMean'] = round(($finalData[$term['termID']][$department['department']]['gradeWeightedTotal'] / $finalData[$term['termID']][$department['department']]['gradeWeightedDivisor']), 2);
                            } else {
                                $finalData[$term['termID']][$department['department']]['gradeWeightedMean'] = 'null';
                            }
                        }
                    }

                    if (count($grades) < 5) {
                        echo "<div class='error'>";
                        echo __($guid, 'The are less than 4 data points, so no graph can be produced.');
                        echo '</div>';
                    } else {
                        //CREATE LEGEND
                        echo "<p style='margin-top: 20px; margin-bottom: 5px'><b>".__($guid, 'Legend').'</b></p>';
                        echo "<table class='noIntBorder' style='width: 100%;  border-spacing: 0; border-collapse: collapse;'>";
                        $columns = 8;
                        $columnCount = 0;
                        foreach ($departments as $department) {
                            if ($columnCount % $columns == 0) {
                                echo '<tr>';
                            }
                            echo "<td style='vertical-align: middle!important; height: 35px; width: 25px; border-right-width: 0px!important'>";
                            echo "<div style='width: 25px; height: 25px; border: 2px solid rgb(".$department['colour'].'); background-color: rgba('.$department['colour'].", 0.8) '></div>";
                            echo '</td>';
                            echo "<td style='vertical-align: middle!important; height: 35px; width: ".(100 / $columns)."%'>";
                            echo '<b>'.$department['department'].'</b>';
                            echo '</td>';

                            if ($columnCount % $columns == 7) {
                                echo '</tr>';
                            }

                            ++$columnCount;
                        }
                        if ($columnCount % $columns != 0) {
                            for ($i = ($columnCount % $columns); $i < $columns; ++$i) {
                                echo "<td colspan=2 style='width: ".(100 / $columns)."%'>";

                                echo '</td>';
                            }
                        }
                        if ($columnCount % $columns != 7) {
                            echo '</tr>';
                        }
                        echo '</table>';

                        //PLOT DATA
                        echo '<script type="text/javascript" src="'.$_SESSION[$guid]['absoluteURL'].'/lib/Chart.js/Chart.min.js"></script>';

                        echo "<p style='margin-top: 20px; margin-bottom: 5px'><b>".__($guid, 'Data').'</b></p>';
                        echo '<div style="width:100%">';
                        echo '<div>';
                        echo '<canvas id="canvas"></canvas>';
                        echo '</div>';
                        echo '</div>';

                        ?>
						<script>
							var lineChartData = {
								labels : [
									<?php
                                        foreach ($terms as $term) {
                                            echo "'".$term['termFullName']."',";
                                        }
                        			?>								],
								datasets : [
									<?php
                                        foreach ($departments as $department) {
										?>
										{
											fillColor : "rgba(<?php echo $department['colour'] ?>,0)",
											strokeColor : "rgba(<?php echo $department['colour'] ?>,1)",
											pointColor : "rgba(<?php echo $department['colour'] ?>,1)",
											pointStrokeColor : "rgba(<?php echo $department['colour'] ?>,0.4)",
											pointHighlightFill : "rgba(<?php echo $department['colour'] ?>,4)",
											pointHighlightStroke : "rgba(<?php echo $department['colour'] ?>,0.1)",
											data : [
												<?php
													foreach ($terms as $term) {
														if ($finalData[$term['termID']][$department['department']]['termID'] == $term['termID']) {
															if ($finalData[$term['termID']][$department['department']]['department'] == $department['department']) {
																echo $finalData[$term['termID']][$department['department']]['gradeWeightedMean'].',';
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
