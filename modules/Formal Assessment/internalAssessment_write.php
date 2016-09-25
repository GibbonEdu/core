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

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

//Get alternative header names
$attainmentAlternativeName = getSettingByScope($connection2, 'Markbook', 'attainmentAlternativeName');
$attainmentAlternativeNameAbrev = getSettingByScope($connection2, 'Markbook', 'attainmentAlternativeNameAbrev');
$effortAlternativeName = getSettingByScope($connection2, 'Markbook', 'effortAlternativeName');
$effortAlternativeNameAbrev = getSettingByScope($connection2, 'Markbook', 'effortAlternativeNameAbrev');

if (isActionAccessible($guid, $connection2, '/modules/Formal Assessment/internalAssessment_write.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'Your request failed because you do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        $alert = getAlert($guid, $connection2, 002);

        //Proceed!
        //Get class variable
        $gibbonCourseClassID = null;
        if (isset($_GET['gibbonCourseClassID'])) {
            $gibbonCourseClassID = $_GET['gibbonCourseClassID'];
        }
        if ($gibbonCourseClassID == '') {
            try {
                $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                $sql = 'SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourseClass.gibbonCourseClassID FROM gibbonCourse, gibbonCourseClass, gibbonCourseClassPerson WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID AND gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID ORDER BY course, class';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            if ($result->rowCount() > 0) {
                $row = $result->fetch();
                $gibbonCourseClassID = $row['gibbonCourseClassID'];
            }
        }
        if ($gibbonCourseClassID == '') {
            echo "<div class='trail'>";
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Write Internal Assessments').'</div>';
            echo '</div>';
            echo "<div class='warning'>";
            echo 'Use the class listing on the right to choose a Internal Assessment to write.';
            echo '</div>';
        }
        //Check existence of and access to this class.
        else {
            try {
                if ($highestAction == 'Write Internal Assessments_all') {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = 'SELECT gibbonCourse.nameShort AS course, gibbonCourse.name AS courseName, gibbonCourseClass.nameShort AS class, gibbonYearGroupIDList FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassID=:gibbonCourseClassID';
                } else {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                    $sql = "SELECT gibbonCourse.nameShort AS course, gibbonCourse.name AS courseName, gibbonCourseClass.nameShort AS class, gibbonYearGroupIDList FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID AND gibbonPersonID=:gibbonPersonID AND role='Teacher'";
                }
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            if ($result->rowCount() != 1) {
                echo "<div class='trail'>";
                echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Write Internal Assessments').'</div>';
                echo '</div>';
                echo "<div class='error'>";
                echo __($guid, 'The specified record does not exist or you do not have access to it.');
                echo '</div>';
            } else {
                $row = $result->fetch();
                $courseName = $row['courseName'];
                $gibbonYearGroupIDList = $row['gibbonYearGroupIDList'];
                echo "<div class='trail'>";
                echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>Write ".$row['course'].'.'.$row['class'].' Internal Assessments</div>';
                echo '</div>';

                if (isset($_GET['return'])) {
                    returnProcess($guid, $_GET['return'], null, array('success0' => 'Your request was completed successfully.'));
                }

                //Get teacher list
                $teaching = false;
                try {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = "SELECT gibbonPerson.gibbonPersonID, title, surname, preferredName FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE role='Teacher' AND gibbonCourseClassID=:gibbonCourseClassID ORDER BY surname, preferredName";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($result->rowCount() > 0) {
                    echo "<h3 style='margin-top: 0px'>";
                    echo __($guid, 'Teachers');
                    echo '</h3>';
                    echo '<ul>';
                    while ($row = $result->fetch()) {
                        echo '<li>'.formatName($row['title'], $row['preferredName'], $row['surname'], 'Staff').'</li>';
                        if ($row['gibbonPersonID'] == $_SESSION[$guid]['gibbonPersonID']) {
                            $teaching = true;
                        }
                    }
                    echo '</ul>';
                }

                //Print marks
                echo '<h3>';
                echo __($guid, 'Marks');
                echo '</h3>';

                //Count number of columns
                try {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = 'SELECT * FROM gibbonInternalAssessmentColumn WHERE gibbonCourseClassID=:gibbonCourseClassID ORDER BY complete, completeDate DESC';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                $columns = $result->rowCount();
                if ($columns < 1) {
                    echo "<div class='warning'>";
                    echo __($guid, 'There are no records to display.');
                    echo '</div>';
                } else {
                    $x = null;
                    if (isset($_GET['page'])) {
                        $x = $_GET['page'];
                    }
                    if ($x == '') {
                        $x = 0;
                    }
                    $columnsPerPage = 3;
                    $columnsThisPage = 3;

                    if ($columns < 1) {
                        echo "<div class='warning'>";
                        echo __($guid, 'There are no records to display.');
                        echo '</div>';
                    } else {
                        if ($columns < 3) {
                            $columnsThisPage = $columns;
                        }
                        if ($columns - ($x * $columnsPerPage) < 3) {
                            $columnsThisPage = $columns - ($x * $columnsPerPage);
                        }
                        try {
                            $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                            $sql = 'SELECT * FROM gibbonInternalAssessmentColumn WHERE gibbonCourseClassID=:gibbonCourseClassID ORDER BY complete, completeDate DESC LIMIT '.($x * $columnsPerPage).', '.$columnsPerPage;
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }

                        //Work out details for external assessment display
                        $externalAssessment = false;
                        if (isActionAccessible($guid, $connection2, '/modules/Formal Assessment/externalAssessment_details.php')) {
                            $gibbonYearGroupIDListArray = (explode(',', $gibbonYearGroupIDList));
                            if (count($gibbonYearGroupIDListArray) == 1) {
                                $primaryExternalAssessmentByYearGroup = unserialize(getSettingByScope($connection2, 'School Admin', 'primaryExternalAssessmentByYearGroup'));
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
                                        echo "<div class='error'>".$e->getMessage().'</div>';
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
                        echo __($guid, 'To see more detail on an item (such as a comment or a grade), hover your mouse over it.');
                        if ($externalAssessment == true) {
                            echo ' '.__($guid, 'The Baseline column is populated based on student performance in external assessments, and can be used as a reference point for the grades in the Internal Assessment.');
                        }
                        echo '</p>';

                        echo "<div class='linkTop'>";
                        echo "<div style='padding-top: 12px; margin-left: 10px; float: right'>";
                        if ($x <= 0) {
                            echo __($guid, 'Newer');
                        } else {
                            echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Formal Assessment/internalAssessment_write.php&gibbonCourseClassID=$gibbonCourseClassID&page=".($x - 1)."'>".__($guid, 'Newer').'</a>';
                        }
                        echo ' | ';
                        if ((($x + 1) * $columnsPerPage) >= $columns) {
                            echo __($guid, 'Older');
                        } else {
                            echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Formal Assessment/internalAssessment_write.php&gibbonCourseClassID=$gibbonCourseClassID&page=".($x + 1)."'>".__($guid, 'Older').'</a>';
                        }
                        echo '</div>';
                        echo '</div>';

                        echo "<table class='mini' cellspacing='0' style='width: 100%; margin-top: 0px'>";
                        echo "<tr class='head' style='height: 120px'>";
                        echo "<th style='width: 150px; max-width: 200px'rowspan=2>";
                        echo __($guid, 'Student');
                        echo '</th>';

						//Show Baseline data header
						if ($externalAssessment == true) {
							echo "<th rowspan=2 style='width: 20px'>";
							$title = __($guid, $externalAssessmentFields[2]).' | ';
							$title .= __($guid, substr($externalAssessmentFields[3], (strpos($externalAssessmentFields[3], '_') + 1))).' | ';
							$title .= __($guid, $externalAssessmentFields[1]);
                            $title .= ' | '.$externalAssessmentFields[4].' '.__($guid, 'Scale').' ';

                            echo "<div style='-webkit-transform: rotate(-90deg); -moz-transform: rotate(-90deg); -ms-transform: rotate(-90deg); -o-transform: rotate(-90deg); transform: rotate(-90deg);' title='$title'>";
							echo __($guid, 'Baseline').'<br/>';
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
                                echo __($guid, 'Marked on').' '.dateConvertBack($guid, $row['completeDate']).'<br/>';
                            } else {
                                echo __($guid, 'Unmarked').'<br/>';
                            }
                            echo $row['type'];
                            if ($row['attachment'] != '' and file_exists($_SESSION[$guid]['absolutePath'].'/'.$row['attachment'])) {
                                echo "<a 'title='".__($guid, 'Download more information')."' href='".$_SESSION[$guid]['absoluteURL'].'/'.$row['attachment']."'>More info</a>";
                            }
                            echo '</span><br/>';
                            if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_edit.php')) {
                                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Formal Assessment/internalAssessment_write_data.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonInternalAssessmentColumnID=".$row['gibbonInternalAssessmentColumnID']."'><img style='margin-top: 3px' title='".__($guid, 'Enter Data')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/markbook.png'/></a> ";
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
                                    try {
                                        $dataScale = array('gibbonScaleID' => $attainmentID[$i]);
                                        $sqlScale = 'SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID';
                                        $resultScale = $connection2->prepare($sqlScale);
                                        $resultScale->execute($dataScale);
                                    } catch (PDOException $e) {
                                        echo "<div class='error'>".$e->getMessage().'</div>';
                                    }
                                    $scale = '';
                                    if ($resultScale->rowCount() == 1) {
                                        $rowScale = $resultScale->fetch();
                                        $scale = ' - '.$rowScale['name'];
                                        if ($rowScale['usage'] != '') {
                                            $scale = $scale.': '.$rowScale['usage'];
                                        }
                                    }
                                    if ($attainmentAlternativeName != '' and $attainmentAlternativeNameAbrev != '') {
                                        echo "<span title='".$attainmentAlternativeName.htmlPrep($scale)."'>".$attainmentAlternativeNameAbrev.'</span>';
                                    } else {
                                        echo "<span title='".__($guid, 'Attainment').htmlPrep($scale)."'>".__($guid, 'Att').'</span>';
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
                                    try {
                                        $dataScale = array('gibbonScaleID' => $effortID[$i]);
                                        $sqlScale = 'SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID';
                                        $resultScale = $connection2->prepare($sqlScale);
                                        $resultScale->execute($dataScale);
                                    } catch (PDOException $e) {
                                        echo "<div class='error'>".$e->getMessage().'</div>';
                                    }
                                    $scale = '';
                                    if ($resultScale->rowCount() == 1) {
                                        $rowScale = $resultScale->fetch();
                                        $scale = ' - '.$rowScale['name'];
                                        if ($rowScale['usage'] != '') {
                                            $scale = $scale.': '.$rowScale['usage'];
                                        }
                                    }
                                    if ($effortAlternativeName != '' and $effortAlternativeNameAbrev != '') {
                                        echo "<span title='".$effortAlternativeName.htmlPrep($scale)."'>".$effortAlternativeNameAbrev.'</span>';
                                    } else {
                                        echo "<span title='".__($guid, 'Effort').htmlPrep($scale)."'>".__($guid, 'Eff').'</span>';
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
                                    echo "<span title='".__($guid, 'Comment')."'>".__($guid, 'Com').'</span>';
                                    echo '</th>';
                                }
                                if ($uploadedResponse[$i] == 'Y') {
                                    $leftBorderStyle = '';
                                    if ($leftBorder == false) {
                                        $leftBorder = true;
                                        $leftBorderStyle = 'border-left: 2px solid #666;';
                                    }
                                    echo "<th style='$leftBorderStyle text-align: center; width: 30px'>";
                                    echo "<span title='".__($guid, 'Uploaded Response')."'>".__($guid, 'Upl').'</span>';
                                    echo '</th>';
                                }
                            }
                        }
                        echo '</tr>';

                        $count = 0;
                        $rowNum = 'odd';

                        try {
                            $dataStudents = array('gibbonCourseClassID' => $gibbonCourseClassID);
                            $sqlStudents = "SELECT title, surname, preferredName, gibbonPerson.gibbonPersonID, dateStart FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE role='Student' AND gibbonCourseClassID=:gibbonCourseClassID AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonCourseClassPerson.reportable='Y' ORDER BY surname, preferredName";
                            $resultStudents = $connection2->prepare($sqlStudents);
                            $resultStudents->execute($dataStudents);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        if ($resultStudents->rowCount() < 1) {
                            echo '<tr>';
                            echo '<td colspan='.($columns + 1).'>';
                            echo '<i>'.__($guid, 'There are no records to display.').'</i>';
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
                                echo "<div style='padding: 2px 0px'><b><a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=".$rowStudents['gibbonPersonID'].'&subpage=Markbook#'.$gibbonCourseClassID."'>".formatName('', $rowStudents['preferredName'], $rowStudents['surname'], 'Student', true).'</a><br/></div>';
                                echo '</td>';

                                if ($externalAssessment == true) {
                                    echo "<td style='text-align: center'>";
                                    try {
                                        $dataEntry = array('gibbonPersonID' => $rowStudents['gibbonPersonID'], 'gibbonExternalAssessmentFieldID' => $externalAssessmentFields[0]);
                                        $sqlEntry = "SELECT gibbonScaleGrade.value, gibbonScaleGrade.descriptor, gibbonExternalAssessmentStudent.date
                                            FROM gibbonExternalAssessmentStudentEntry
                                                JOIN gibbonExternalAssessmentStudent ON (gibbonExternalAssessmentStudentEntry.gibbonExternalAssessmentStudentID=gibbonExternalAssessmentStudent.gibbonExternalAssessmentStudentID)
                                                JOIN gibbonScaleGrade ON (gibbonExternalAssessmentStudentEntry.gibbonScaleGradeID=gibbonScaleGrade.gibbonScaleGradeID)
                                            WHERE gibbonPersonID=:gibbonPersonID
                                                AND gibbonExternalAssessmentFieldID=:gibbonExternalAssessmentFieldID
                                                AND NOT gibbonExternalAssessmentStudentEntry.gibbonScaleGradeID=''
                                                ORDER BY date DESC";
                                        $resultEntry = $connection2->prepare($sqlEntry);
                                        $resultEntry->execute($dataEntry);
                                    } catch (PDOException $e) {
                                        echo "<div class='error'>".$e->getMessage().'</div>';
                                    }
                                    if ($resultEntry->rowCount() >= 1) {
                                        $rowEntry = $resultEntry->fetch();
                                        echo "<a title='".__($guid, $rowEntry['descriptor']).' | '.__($guid, 'Test taken on').' '.dateConvertBack($guid, $rowEntry['date'])."' href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=".$rowStudents['gibbonPersonID']."&subpage=External Assessment'>".__($guid, $rowEntry['value']).'</a>';
                                    }
                                    echo '</td>';
                                }

                                for ($i = 0; $i < $columnsThisPage; ++$i) {
                                    $row = $result->fetch();
                                    try {
                                        $dataEntry = array('gibbonInternalAssessmentColumnID' => $columnID[($i)], 'gibbonPersonIDStudent' => $rowStudents['gibbonPersonID']);
                                        $sqlEntry = 'SELECT * FROM gibbonInternalAssessmentEntry WHERE gibbonInternalAssessmentColumnID=:gibbonInternalAssessmentColumnID AND gibbonPersonIDStudent=:gibbonPersonIDStudent';
                                        $resultEntry = $connection2->prepare($sqlEntry);
                                        $resultEntry->execute($dataEntry);
                                    } catch (PDOException $e) {
                                        echo "<div class='error'>".$e->getMessage().'</div>';
                                    }
                                    if ($resultEntry->rowCount() == 1) {
                                        $rowEntry = $resultEntry->fetch();
                                        $leftBorder = false;

                                        if ($attainmentOn[$i] == 'Y' and $attainmentID[$i] != '') {
                                            $leftBorder = true;
                                            echo "<td style='border-left: 2px solid #666; text-align: center'>";
                                            if ($attainmentID[$i] != '') {
                                                $styleAttainment = '';
                                                $attainment = '';
                                                if ($rowEntry['attainmentValue'] != '') {
                                                    $attainment = __($guid, $rowEntry['attainmentValue']);
                                                }
                                                if ($rowEntry['attainmentValue'] == 'Complete') {
                                                    $attainment = __($guid, 'Com');
                                                } elseif ($rowEntry['attainmentValue'] == 'Incomplete') {
                                                    $attainment = __($guid, 'Inc');
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
                                                    $effort = __($guid, $rowEntry['effortValue']);
                                                }
                                                if ($rowEntry['effortValue'] == 'Complete') {
                                                    $effort = __($guid, 'Com');
                                                } elseif ($rowEntry['effortValue'] == 'Incomplete') {
                                                    $effort = __($guid, 'Inc');
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
                                                echo "<a title='".__($guid, 'Uploaded Response')."' href='".$_SESSION[$guid]['absoluteURL'].'/'.$rowEntry['response']."'>Up</a><br/>";
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
                                            try {
                                                $dataWork = array('gibbonPlannerEntryID' => $gibbonPlannerEntryID[$i], 'gibbonPersonID' => $rowStudents['gibbonPersonID']);
                                                $sqlWork = 'SELECT * FROM gibbonPlannerEntryHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID ORDER BY count DESC';
                                                $resultWork = $connection2->prepare($sqlWork);
                                                $resultWork->execute($dataWork);
                                            } catch (PDOException $e) {
                                                echo "<div class='error'>".$e->getMessage().'</div>';
                                            }
                                            if ($resultWork->rowCount() > 0) {
                                                $rowWork = $resultWork->fetch();

                                                if ($rowWork['status'] == 'Exemption') {
                                                    $linkText = __($guid, 'Exe');
                                                } elseif ($rowWork['version'] == 'Final') {
                                                    $linkText = __($guid, 'Fin');
                                                } else {
                                                    $linkText = __($guid, 'Dra').$rowWork['count'];
                                                }

                                                $style = '';
                                                $status = 'On Time';
                                                if ($rowWork['status'] == 'Exemption') {
                                                    $status = __($guid, 'Exemption');
                                                } elseif ($rowWork['status'] == 'Late') {
                                                    $style = "style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px'";
                                                    $status = __($guid, 'Late');
                                                }

                                                if ($rowWork['type'] == 'File') {
                                                    echo "<span title='".$rowWork['version'].". $status. ".__($guid, 'Submitted at').' '.substr($rowWork['timestamp'], 11, 5).' '.__($guid, 'on').' '.dateConvertBack($guid, substr($rowWork['timestamp'], 0, 10))."' $style><a href='".$_SESSION[$guid]['absoluteURL'].'/'.$rowWork['location']."'>$linkText</a></span>";
                                                } elseif ($rowWork['type'] == 'Link') {
                                                    echo "<span title='".$rowWork['version'].". $status. ".__($guid, 'Submitted at').' '.substr($rowWork['timestamp'], 11, 5).' '.__($guid, 'on').' '.dateConvertBack($guid, substr($rowWork['timestamp'], 0, 10))."' $style><a target='_blank' href='".$rowWork['location']."'>$linkText</a></span>";
                                                } else {
                                                    echo "<span title='$status. ".__($guid, 'Recorded at').' '.substr($rowWork['timestamp'], 11, 5).' '.__($guid, 'on').' '.dateConvertBack($guid, substr($rowWork['timestamp'], 0, 10))."' $style>$linkText</span>";
                                                }
                                            } else {
                                                if (date('Y-m-d H:i:s') < $homeworkDueDateTime[$i]) {
                                                    echo "<span title='".__($guid, 'Pending')."'>Pen</span>";
                                                } else {
                                                    if ($rowStudents['dateStart'] > $lessonDate[$i]) {
                                                        echo "<span title='".__($guid, 'Student joined school after assessment was given.')."' style='color: #000; font-weight: normal; border: 2px none #ff0000; padding: 2px 4px'>".__($guid, 'NA').'</span>';
                                                    } else {
                                                        if ($rowSub['homeworkSubmissionRequired'] == 'Compulsory') {
                                                            echo "<span title='".__($guid, 'Incomplete')."' style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px'>".__($guid, 'Inc').'</span>';
                                                        } else {
                                                            echo "<span title='".__($guid, 'Not submitted online')."'>".__($guid, 'NA').'</span>';
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
        $_SESSION[$guid]['sidebarExtra'] = sidebarExtra($guid, $connection2, $gibbonCourseClassID, 'write');
    }
}
