<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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

use Gibbon\Services\Format;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\School\GradeScaleGateway;
use Gibbon\Domain\System\AlertLevelGateway;
use Gibbon\Domain\Timetable\CourseClassGateway;
use Gibbon\Domain\FormalAssessment\InternalAssessmentColumnGateway;
use Gibbon\Domain\FormalAssessment\ExternalAssessmentStudentGateway;
use Gibbon\Domain\Planner\PlannerEntryHomeworkGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

//Get alternative header names
$settingGateway = $container->get(SettingGateway::class);
$attainmentAlternativeName = $settingGateway->getSettingByScope('Markbook', 'attainmentAlternativeName');
$attainmentAlternativeNameAbrev = $settingGateway->getSettingByScope('Markbook', 'attainmentAlternativeNameAbrev');
$effortAlternativeName = $settingGateway->getSettingByScope('Markbook', 'effortAlternativeName');
$effortAlternativeNameAbrev = $settingGateway->getSettingByScope('Markbook', 'effortAlternativeNameAbrev');

if (isActionAccessible($guid, $connection2, '/modules/Formal Assessment/internalAssessment_write.php') == false) {
    //Access denied
    $page->addError(__('Your request failed because you do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        /**
         * @var AlertLevelGateway
         */
        $alertLevelGateway = $container->get(AlertLevelGateway::class);
        $alert = $alertLevelGateway->getByID(AlertLevelGateway::LEVEL_MEDIUM);

        //Proceed!
        //Get class variable
        $gibbonCourseClassID = null;
        if (isset($_GET['gibbonCourseClassID'])) {
            $gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
        }
        if ($gibbonCourseClassID == '') {

                $result = $container->get(CourseClassGateway::class)->selectClassesByYearAndPerson($session->get('gibbonSchoolYearID'), $session->get('gibbonPersonID'));

            if ($result->rowCount() > 0) {
                $row = $result->fetch();
                $gibbonCourseClassID = $row['gibbonCourseClassID'];
            }
        }
        if ($gibbonCourseClassID == '') {
            $page->breadcrumbs->add(__('Write Internal Assessments'));
            echo "<div class='warning'>";
            echo __('Use the class listing on the right to choose an Internal Assessment to write.');
            echo '</div>';
        }
        //Check existence of and access to this class.
        else {
            try {
                if ($highestAction == 'Write Internal Assessments_all') {
                    $result = $container->get(CourseClassGateway::class)->getCourseClass($gibbonCourseClassID);

                } else {
                    $result = $container->get(CourseClassGateway::class)->getCourseClassByPerson($gibbonCourseClassID, $session->get('gibbonPersonID'));
                }
            } catch (PDOException $e) {
            }
            if (empty($result)) {
                $page->breadcrumbs->add(__('Write Internal Assessments'));
                $page->addError(__('The specified record does not exist or you do not have access to it.'));

            } else {
                $row = $result;
                $courseName = $row['courseName'] ?? '';
                $gibbonYearGroupIDList = $row['gibbonYearGroupIDList'] ?? '';
                $page->breadcrumbs->add(__('Write {courseClass} Internal Assessments', ['courseClass' => $row['course'].'.'.$row['class']]));

                //Get teacher list
                $teaching = false;

                $result = $container->get(CourseClassGateway::class)->selectTeacherListByClass($gibbonCourseClassID);
                    
                if ($result->rowCount() > 0) {
                    echo "<h3 style='margin-top: 0px'>";
                    echo __('Teachers');
                    echo '</h3>';
                    echo '<ul>';
                    while ($row = $result->fetch()) {
                        if ($row['reportable'] != 'Y') continue;

                        echo '<li>'.Format::name($row['title'], $row['preferredName'], $row['surname'], 'Staff').'</li>';
                        if ($row['gibbonPersonID'] == $session->get('gibbonPersonID')) {
                            $teaching = true;
                        }
                    }
                    echo '</ul>';
                }

                //Print marks
                echo '<h3>';
                echo __('Marks');
                echo '</h3>';

                //Count number of columns

                    $result = $container->get(InternalAssessmentColumnGateway::class)->selectColumnsByClass($gibbonCourseClassID);
                    
                $columns = $result->rowCount();
                if ($columns < 1) {
                    echo "<div class='warning'>";
                    echo __('There are no records to display.');
                    echo '</div>';
                } else {
                    $x = null;
                    if (isset($_GET['page'])) {
                        $x = $_GET['page'] ?? '';
                    }
                    if ($x == '') {
                        $x = 0;
                    }
                    $columnsPerPage = 3;
                    $columnsThisPage = 3;

                    if ($columns < 1) {
                        echo "<div class='warning'>";
                        echo __('There are no records to display.');
                        echo '</div>';
                    } else {
                        if ($columns < 3) {
                            $columnsThisPage = $columns;
                        }
                        if ($columns - ($x * $columnsPerPage) < 3) {
                            $columnsThisPage = $columns - ($x * $columnsPerPage);
                        }

                        $limit = intval($x * $columnsPerPage);

                            $result = $container->get(InternalAssessmentColumnGateway::class)->selectLimitedColumns($gibbonCourseClassID, $limit, $columnsPerPage);

                        //Work out details for external assessment display
                        $externalAssessment = false;
                        if (isActionAccessible($guid, $connection2, '/modules/Formal Assessment/externalAssessment_details.php')) {
                            $gibbonYearGroupIDListArray = (explode(',', $gibbonYearGroupIDList));
                            if (count($gibbonYearGroupIDListArray) == 1) {
                                $primaryExternalAssessmentByYearGroup = unserialize($settingGateway->getSettingByScope('School Admin', 'primaryExternalAssessmentByYearGroup'));
                                if ($primaryExternalAssessmentByYearGroup[$gibbonYearGroupIDListArray[0]] != '' and $primaryExternalAssessmentByYearGroup[$gibbonYearGroupIDListArray[0]] != '-') {
                                    $gibbonExternalAssessmentID = substr($primaryExternalAssessmentByYearGroup[$gibbonYearGroupIDListArray[0]], 0, strpos($primaryExternalAssessmentByYearGroup[$gibbonYearGroupIDListArray[0]], '-'));
                                    $gibbonExternalAssessmentIDCategory = substr($primaryExternalAssessmentByYearGroup[$gibbonYearGroupIDListArray[0]], (strpos($primaryExternalAssessmentByYearGroup[$gibbonYearGroupIDListArray[0]], '-') + 1));

                                    try {
                                        $dataExternalAssessment = array('gibbonExternalAssessmentID' => $gibbonExternalAssessmentID, 'category' => $gibbonExternalAssessmentIDCategory);
                                        $courseNameTokens = explode(' ', $courseName);
                                        $courseWhere = ' AND (';
                                        $whereCount = 1;
                                        foreach ($courseNameTokens as $courseNameToken) {
                                            if (strlen($courseNameToken) > 3) {
                                                $dataExternalAssessment['token'.$whereCount] = '%'.$courseNameToken.'%';
                                                $courseWhere .= "gibbonExternalAssessmentField.name LIKE :token$whereCount OR ";
                                                ++$whereCount;
                                            }
                                        }
                                        if ($whereCount < 1) {
                                            $courseWhere = '';
                                        } else {
                                            $courseWhere = substr($courseWhere, 0, -4).')';
                                        }
                                        $sqlExternalAssessment = "SELECT gibbonExternalAssessment.name AS assessment, gibbonExternalAssessmentField.name, gibbonExternalAssessmentFieldID, category, gibbonScale.name AS scale
                    						FROM gibbonExternalAssessmentField
                    							JOIN gibbonExternalAssessment ON (gibbonExternalAssessmentField.gibbonExternalAssessmentID=gibbonExternalAssessment.gibbonExternalAssessmentID)
                    							JOIN gibbonScale ON (gibbonExternalAssessmentField.gibbonScaleID=gibbonScale.gibbonScaleID)
                    						WHERE gibbonExternalAssessmentField.gibbonExternalAssessmentID=:gibbonExternalAssessmentID
                    							AND category=:category $courseWhere
                    						ORDER BY name
                    						LIMIT 1";
                                        $resultExternalAssessment = $connection2->prepare($sqlExternalAssessment);
                                        $resultExternalAssessment->execute($dataExternalAssessment);
                                    } catch (PDOException $e) {
                                    }
                                    if ($resultExternalAssessment->rowCount() >= 1) {
                                        $rowExternalAssessment = $resultExternalAssessment->fetch();
                                        $externalAssessment = true;
                                        $externalAssessmentFields = array();
                                        $externalAssessmentFields[0] = $rowExternalAssessment['gibbonExternalAssessmentFieldID'];
                                        $externalAssessmentFields[1] = $rowExternalAssessment['name'];
                                        $externalAssessmentFields[2] = $rowExternalAssessment['assessment'];
                                        $externalAssessmentFields[3] = $rowExternalAssessment['category'];
                                        $externalAssessmentFields[4] = $rowExternalAssessment['name'];
                                    }
                                }
                            }
                        }

                        //Print table header
                        echo '<p>';
                        echo __('To see more detail on an item (such as a comment or a grade), hover your mouse over it.');
                        if ($externalAssessment == true) {
                            echo ' '.__('The Baseline column is populated based on student performance in external assessments, and can be used as a reference point for the grades in the Internal Assessment.');
                        }
                        echo '</p>';

                        echo "<div class='linkTop'>";
                        echo "<div style='padding-top: 12px; margin-left: 10px; float: right'>";
                        if ($x <= 0) {
                            echo __('Newer');
                        } else {
                            echo "<a href='".$session->get('absoluteURL')."/index.php?q=/modules/Formal Assessment/internalAssessment_write.php&gibbonCourseClassID=$gibbonCourseClassID&page=".($x - 1)."'>".__('Newer').'</a>';
                        }
                        echo ' | ';
                        if ((($x + 1) * $columnsPerPage) >= $columns) {
                            echo __('Older');
                        } else {
                            echo "<a href='".$session->get('absoluteURL')."/index.php?q=/modules/Formal Assessment/internalAssessment_write.php&gibbonCourseClassID=$gibbonCourseClassID&page=".($x + 1)."'>".__('Older').'</a>';
                        }
                        echo '</div>';
                        echo '</div>';

                        echo "<table class='mini' cellspacing='0' style='width: 100%; margin-top: 0px'>";
                        echo "<tr class='head' style='height: 120px'>";
                        echo "<th style='width: 150px; max-width: 200px'rowspan=2>";
                        echo __('Student');
                        echo '</th>';

						//Show Baseline data header
						if ($externalAssessment == true) {
							echo "<th rowspan=2 style='width: 20px'>";
							$title = __($externalAssessmentFields[2]).' | ';
							$title .= __(substr($externalAssessmentFields[3], (strpos($externalAssessmentFields[3], '_') + 1))).' | ';
							$title .= __($externalAssessmentFields[1]);
                            $title .= ' | '.$externalAssessmentFields[4].' '.__('Scale').' ';

                            echo "<div style='-webkit-transform: rotate(-90deg); -moz-transform: rotate(-90deg); -ms-transform: rotate(-90deg); -o-transform: rotate(-90deg); transform: rotate(-90deg);' title='$title'>";
							echo __('Baseline').'<br/>';
							echo '</div>';
							echo '</th>';
                        }

                        $columnID = array();
                        $attainmentID = array();
                        $effortID = array();
                        for ($i = 0; $i < $columnsThisPage; ++$i) {
                            $row = $result->fetch();
                            if ($row === false) {
                                $columnID[$i] = false;
                            } else {
                                $columnID[$i] = $row['gibbonInternalAssessmentColumnID'];
                                $attainmentOn[$i] = $row['attainment'];
                                $attainmentID[$i] = $row['gibbonScaleIDAttainment'];
                                $effortOn[$i] = $row['effort'];
                                $effortID[$i] = $row['gibbonScaleIDEffort'];
                                $comment[$i] = $row['comment'];
                                $uploadedResponse[$i] = $row['uploadedResponse'];
                                $submission[$i] = false;
                            }

                            //Column count
                            $span = 0;
                            $contents = true;
                            if ($attainmentOn[$i] == 'Y' and $attainmentID[$i] != '') {
                                ++$span;
                            }
                            if ($effortOn[$i] == 'Y' and $effortID[$i] != '') {
                                ++$span;
                            }
                            if ($comment[$i] == 'Y') {
                                ++$span;
                            }
                            if ($uploadedResponse[$i] == 'Y') {
                                ++$span;
                            }
                            if ($span == 0) {
                                $contents = false;
                            }

                            echo "<th style='text-align: center; min-width: 140px' colspan=$span>";
                            echo "<span title='".htmlPrep($row['description'])."'>".$row['name'].'</span><br/>';
                            echo "<span style='font-size: 90%; font-style: italic; font-weight: normal'>";
                            if ($row['completeDate'] != '') {
                                echo __('Marked on').' '.Format::date($row['completeDate']).'<br/>';
                            } else {
                                echo __('Unmarked').'<br/>';
                            }
                            echo $row['type'];
                            if ($row['attachment'] != '' and file_exists($session->get('absolutePath').'/'.$row['attachment'])) {
                                echo " | <a 'title='".__('Download more information')."' href='".$session->get('absoluteURL').'/'.$row['attachment']."'>More info</a>";
                            }
                            echo '</span><br/>';
                            if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_edit.php')) {
                                echo "<a href='".$session->get('absoluteURL')."/index.php?q=/modules/Formal Assessment/internalAssessment_write_data.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonInternalAssessmentColumnID=".$row['gibbonInternalAssessmentColumnID']."'><img style='margin-top: 3px' title='".__('Enter Data')."' src='./themes/".$session->get('gibbonThemeName')."/img/markbook.png'/></a> ";
                            }
                            echo '</th>';
                        }
                        echo '</tr>';

                        echo "<tr class='head'>";
                        for ($i = 0; $i < $columnsThisPage; ++$i) {
                            if ($columnID[$i] == false or $contents == false) {
                                echo "<th style='text-align: center' colspan=$span>";

                                echo '</th>';
                            } else {
                                $leftBorder = false;
                                if ($attainmentOn[$i] == 'Y' and $attainmentID[$i] != '') {
                                    $leftBorder = true;
                                    echo "<th style='border-left: 2px solid #666; text-align: center; width: 40px'>";

                                        $resultScale = $container->get(GradeScaleGateway::class)->getByID($attainmentID[$i]);
                                        
                                    $scale = '';
                                    if (!empty($resultScale)) {
                                        $rowScale = $resultScale;
                                        $scale = ' - '.$rowScale['name'];
                                        if ($rowScale['usage'] != '') {
                                            $scale = $scale.': '.$rowScale['usage'];
                                        }
                                    }
                                    if ($attainmentAlternativeName != '' and $attainmentAlternativeNameAbrev != '') {
                                        echo "<span title='".$attainmentAlternativeName.htmlPrep($scale)."'>".$attainmentAlternativeNameAbrev.'</span>';
                                    } else {
                                        echo "<span title='".__('Attainment').htmlPrep($scale)."'>".__('Att').'</span>';
                                    }
                                    echo '</th>';
                                }

                                if ($effortOn[$i] == 'Y' and $effortID[$i] != '') {
                                    $leftBorderStyle = '';
                                    if ($leftBorder == false) {
                                        $leftBorder = true;
                                        $leftBorderStyle = 'border-left: 2px solid #666;';
                                    }
                                    echo "<th style='$leftBorderStyle text-align: center; width: 40px'>";

                                        $resultScale = $container->get(GradeScaleGateway::class)->getByID($effortID[$i]);

                                    $scale = '';
                                    if (!empty($resultScale)) {
                                        $rowScale = $resultScale;
                                        $scale = ' - '.$rowScale['name'];
                                        if ($rowScale['usage'] != '') {
                                            $scale = $scale.': '.$rowScale['usage'];
                                        }
                                    }
                                    if ($effortAlternativeName != '' and $effortAlternativeNameAbrev != '') {
                                        echo "<span title='".$effortAlternativeName.htmlPrep($scale)."'>".$effortAlternativeNameAbrev.'</span>';
                                    } else {
                                        echo "<span title='".__('Effort').htmlPrep($scale)."'>".__('Eff').'</span>';
                                    }
                                    echo '</th>';
                                }

                                if ($comment[$i] == 'Y') {
                                    $leftBorderStyle = '';
                                    if ($leftBorder == false) {
                                        $leftBorder = true;
                                        $leftBorderStyle = 'border-left: 2px solid #666;';
                                    }
                                    echo "<th style='$leftBorderStyle text-align: center; width: 80px'>";
                                    echo "<span title='".__('Comment')."'>".__('Com').'</span>';
                                    echo '</th>';
                                }
                                if ($uploadedResponse[$i] == 'Y') {
                                    $leftBorderStyle = '';
                                    if ($leftBorder == false) {
                                        $leftBorder = true;
                                        $leftBorderStyle = 'border-left: 2px solid #666;';
                                    }
                                    echo "<th style='$leftBorderStyle text-align: center; width: 30px'>";
                                    echo "<span title='".__('Uploaded Response')."'>".__('Upl').'</span>';
                                    echo '</th>';
                                }
                            }
                        }
                        echo '</tr>';

                        $count = 0;
                        $rowNum = 'odd';

                            $resultStudents = $container->get(CourseClassGateway::class)->selectStudentListByClass($gibbonCourseClassID);

                        if ($resultStudents->rowCount() < 1) {
                            echo '<tr>';
                            echo '<td colspan='.($columns + 1).'>';
                            echo '<i>'.__('There are no records to display.').'</i>';
                            echo '</td>';
                            echo '</tr>';
                        } else {
                            while ($rowStudents = $resultStudents->fetch()) {
                                if ($count % 2 == 0) {
                                    $rowNum = 'even';
                                } else {
                                    $rowNum = 'odd';
                                }
                                ++$count;

                                //COLOR ROW BY STATUS!
                                echo "<tr class=$rowNum>";
                                echo '<td>';
                                echo "<div style='padding: 2px 0px'><b><a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=".$rowStudents['gibbonPersonID'].'&subpage=Internal Assessment#'.$gibbonCourseClassID."'>".Format::name('', $rowStudents['preferredName'], $rowStudents['surname'], 'Student', true).'</a><br/></div>';
                                echo '</td>';

                                if ($externalAssessment == true) {
                                    echo "<td style='text-align: center'>";

                                        $resultEntry = $container->get(ExternalAssessmentStudentGateway::class)->selectStudentExternalAssessmentGrades($rowStudents['gibbonPersonID'], $externalAssessmentFields[0]);

                                    if ($resultEntry->rowCount() >= 1) {
                                        $rowEntry = $resultEntry->fetch();
                                        echo "<a title='".__($rowEntry['descriptor']).' | '.__('Test taken on').' '.Format::date($rowEntry['date'])."' href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=".$rowStudents['gibbonPersonID']."&subpage=External Assessment'>".__($rowEntry['value']).'</a>';
                                    }
                                    echo '</td>';
                                }

                                for ($i = 0; $i < $columnsThisPage; ++$i) {
                                    $row = $result->fetch();

                                        $resultEntry = $container->get(InternalAssessmentColumnGateway::class)->getInternalAssessmentEntryByStudent($columnID[($i)], $rowStudents['gibbonPersonID']);

                                    if (!empty($resultEntry)) {
                                        $rowEntry = $resultEntry;
                                        $leftBorder = false;

                                        if ($attainmentOn[$i] == 'Y' and $attainmentID[$i] != '') {
                                            $leftBorder = true;
                                            echo "<td style='border-left: 2px solid #666; text-align: center'>";
                                            if ($attainmentID[$i] != '') {
                                                $styleAttainment = '';
                                                $attainment = '';
                                                if ($rowEntry['attainmentValue'] != '') {
                                                    $attainment = __($rowEntry['attainmentValue']);
                                                }
                                                if ($rowEntry['attainmentValue'] == 'Complete') {
                                                    $attainment = __('Com');
                                                } elseif ($rowEntry['attainmentValue'] == 'Incomplete') {
                                                    $attainment = __('Inc');
                                                }
                                                echo "<div $styleAttainment title='".htmlPrep($rowEntry['attainmentDescriptor'])."'>$attainment";
                                            }
                                            if ($attainmentID[$i] != '') {
                                                echo '</div>';
                                            }
                                            echo '</td>';
                                        }
                                        if ($effortOn[$i] == 'Y' and $effortID[$i] != '') {
                                            $leftBorderStyle = '';
                                            if ($leftBorder == false) {
                                                $leftBorder = true;
                                                $leftBorderStyle = 'border-left: 2px solid #666;';
                                            }
                                            echo "<td style='$leftBorderStyle text-align: center;'>";
                                            if ($effortID[$i] != '') {
                                                $styleEffort = '';
                                                $effort = '';
                                                if ($rowEntry['effortValue'] != '') {
                                                    $effort = __($rowEntry['effortValue']);
                                                }
                                                if ($rowEntry['effortValue'] == 'Complete') {
                                                    $effort = __('Com');
                                                } elseif ($rowEntry['effortValue'] == 'Incomplete') {
                                                    $effort = __('Inc');
                                                }
                                                echo "<div $styleEffort title='".htmlPrep($rowEntry['effortDescriptor'])."'>$effort";
                                            }
                                            if ($effortID[$i] != '') {
                                                echo '</div>';
                                            }
                                            echo '</td>';
                                        }

                                        if ($comment[$i] == 'Y') {
                                            $leftBorderStyle = '';
                                            if ($leftBorder == false) {
                                                $leftBorder = true;
                                                $leftBorderStyle = 'border-left: 2px solid #666;';
                                            }
                                            echo "<td style='$leftBorderStyle text-align: center;'>";
                                            $style = '';
                                            if ($rowEntry['comment'] != '') {
                                                if (strlen($rowEntry['comment']) < 11) {
                                                    echo htmlPrep($rowEntry['comment']);
                                                } else {
                                                    echo "<span $style title='".htmlPrep($rowEntry['comment'])."'>".substr($rowEntry['comment'], 0, 10).'...</span>';
                                                }
                                            }
                                            echo '</td>';
                                        }
                                        if ($uploadedResponse[$i] == 'Y') {
                                            $leftBorderStyle = '';
                                            if ($leftBorder == false) {
                                                $leftBorder = true;
                                                $leftBorderStyle = 'border-left: 2px solid #666;';
                                            }
                                            echo "<td style='$leftBorderStyle text-align: center;'>";
                                            if ($rowEntry['response'] != '') {
                                                echo "<a title='".__('Uploaded Response')."' href='".$session->get('absoluteURL').'/'.$rowEntry['response']."'>Up</a><br/>";
                                            }
                                        }
                                        echo '</td>';
                                    } else {
                                        $emptySpan = 0;
                                        if ($attainmentOn[$i] == 'Y' and $attainmentID[$i] != '') {
                                            ++$emptySpan;
                                        }
                                        if ($effortOn[$i] == 'Y' and $effortID[$i] != '') {
                                            ++$emptySpan;
                                        }
                                        if ($comment[$i] == 'Y') {
                                            ++$emptySpan;
                                        }
                                        if ($uploadedResponse[$i] == 'Y') {
                                            ++$emptySpan;
                                        }
                                        if ($emptySpan > 0) {
                                            echo "<td style='border-left: 2px solid #666; text-align: center' colspan=$emptySpan></td>";
                                        }
                                    }
                                    if (isset($submission[$i])) {
                                        if ($submission[$i] == true) {
                                            $leftBorderStyle = '';
                                            if ($leftBorder == false) {
                                                $leftBorder = true;
                                                $leftBorderStyle = 'border-left: 2px solid #666;';
                                            }
                                            echo "<td style='$leftBorderStyle text-align: center;'>";

                                                $resultWork = $container->get(PlannerEntryHomeworkGateway::class)->selectHomeworkByStudent($gibbonPlannerEntryID[$i], $rowStudents['gibbonPersonID']);

                                            if ($resultWork->rowCount() > 0) {
                                                $rowWork = $resultWork->fetch();

                                                if ($rowWork['status'] == 'Exemption') {
                                                    $linkText = __('Exe');
                                                } elseif ($rowWork['version'] == 'Final') {
                                                    $linkText = __('Fin');
                                                } else {
                                                    $linkText = __('Dra').$rowWork['count'];
                                                }

                                                $style = '';
                                                $status = 'On Time';
                                                if ($rowWork['status'] == 'Exemption') {
                                                    $status = __('Exemption');
                                                } elseif ($rowWork['status'] == 'Late') {
                                                    $style = "style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px'";
                                                    $status = __('Late');
                                                }

                                                if ($rowWork['type'] == 'File') {
                                                    echo "<span title='".$rowWork['version'].". $status. ".__('Submitted at').' '.substr($rowWork['timestamp'], 11, 5).' '.__('on').' '.Format::date(substr($rowWork['timestamp'], 0, 10))."' $style><a href='".$session->get('absoluteURL').'/'.$rowWork['location']."'>$linkText</a></span>";
                                                } elseif ($rowWork['type'] == 'Link') {
                                                    echo "<span title='".$rowWork['version'].". $status. ".__('Submitted at').' '.substr($rowWork['timestamp'], 11, 5).' '.__('on').' '.Format::date(substr($rowWork['timestamp'], 0, 10))."' $style><a target='_blank' href='".$rowWork['location']."'>$linkText</a></span>";
                                                } else {
                                                    echo "<span title='$status. ".__('Recorded at').' '.substr($rowWork['timestamp'], 11, 5).' '.__('on').' '.Format::date(substr($rowWork['timestamp'], 0, 10))."' $style>$linkText</span>";
                                                }
                                            } else {
                                                if (date('Y-m-d H:i:s') < $homeworkDueDateTime[$i]) {
                                                    echo "<span title='".__('Pending')."'>Pen</span>";
                                                } else {
                                                    if ($rowStudents['dateStart'] > $lessonDate[$i]) {
                                                        echo "<span title='".__('Student joined school after assessment was given.')."' style='color: #000; font-weight: normal; border: 2px none #ff0000; padding: 2px 4px'>".__('NA').'</span>';
                                                    } else {
                                                        if ($rowSub['homeworkSubmissionRequired'] == 'Required') {
                                                            echo "<span title='".__('Incomplete')."' style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px'>".__('Inc').'</span>';
                                                        } else {
                                                            echo "<span title='".__('Not submitted online')."'>".__('NA').'</span>';
                                                        }
                                                    }
                                                }
                                            }
                                            echo '</td>';
                                        }
                                    }
                                }
                                echo '</tr>';
                            }
                        }
                        echo '</table>';
                    }
                }
            }
        }

        //Print sidebar
        $session->set('sidebarExtra', sidebarExtra($guid, $connection2, $gibbonCourseClassID, 'write'));
    }
}
