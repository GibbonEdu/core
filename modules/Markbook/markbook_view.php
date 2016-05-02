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
$enableColumnWeighting = getSettingByScope($connection2, 'Markbook', 'enableColumnWeighting');
$attainmentAlternativeName = getSettingByScope($connection2, 'Markbook', 'attainmentAlternativeName');
$attainmentAlternativeNameAbrev = getSettingByScope($connection2, 'Markbook', 'attainmentAlternativeNameAbrev');
$effortAlternativeName = getSettingByScope($connection2, 'Markbook', 'effortAlternativeName');
$effortAlternativeNameAbrev = getSettingByScope($connection2, 'Markbook', 'effortAlternativeNameAbrev');

if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_view.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'Your request failed because you do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    $highestAction2 = getHighestGroupedAction($guid, '/modules/Markbook/markbook_edit.php', $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        $alert = getAlert($guid, $connection2, 002);

        //VIEW ACCESS TO ALL MARKBOOK DATA
        if ($highestAction == 'View Markbook_allClassesAllData') {
            //Check for access to multiple column add
            $multiAdd = false;
            //Add multiple columns
            if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_edit.php')) {
                if ($highestAction2 == 'Edit Markbook_multipleClassesAcrossSchool' or $highestAction2 == 'Edit Markbook_multipleClassesInDepartment' or $highestAction2 == 'Edit Markbook_everything') {
                    //Check highest role in any department
                    try {
                        $dataRole = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                        $sqlRole = "SELECT role FROM gibbonDepartmentStaff WHERE gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)')";
                        $resultRole = $connection2->prepare($sqlRole);
                        $resultRole->execute($dataRole);
                    } catch (PDOException $e) {
                    }
                    if ($resultRole->rowCount() >= 1 or $highestAction2 == 'Edit Markbook_multipleClassesAcrossSchool' or $highestAction2 == 'Edit Markbook_everything') {
                        $multiAdd = true;
                    }
                }
            }

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
                echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'View Markbook').'</div>';
                echo '</div>';
                //Add multiple columns
                if ($multiAdd) {
                    echo "<div class='linkTop'>";
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/markbook_edit_addMulti.php&gibbonCourseClassID=$gibbonCourseClassID'>".__($guid, 'Add Multiple Records')."<img style='margin-left: 5px' title='".__($guid, 'Add Multiple Records')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new_multi.png'/></a>";
                    echo '</div>';
                }
                //Get class chooser
                echo classChooser($guid, $connection2, $gibbonCourseClassID);
            }
            //Check existence of and access to this class.
            else {
                try {
                    $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                    $sql = 'SELECT gibbonCourse.nameShort AS course, gibbonCourse.name AS courseName, gibbonCourseClass.nameShort AS class, gibbonYearGroupIDList FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassID=:gibbonCourseClassID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }
                if ($result->rowCount() != 1) {
                    echo "<div class='trail'>";
                    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'View Markbook').'</div>';
                    echo '</div>';
                    echo "<div class='error'>";
                    echo __($guid, 'The specified record does not exist.');
                    echo '</div>';
                } else {
                    $row = $result->fetch();
                    $courseName = $row['courseName'];
                    $gibbonYearGroupIDList = $row['gibbonYearGroupIDList'];
                    echo "<div class='trail'>";
                    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>View ".$row['course'].'.'.$row['class'].' Markbook</div>';
                    echo '</div>';

                    if (isset($_GET['return'])) {
                        returnProcess($guid, $_GET['return'], null, null);
                    }

                    //Get Smart Workflow help message
                    $category = getRoleCategory($_SESSION[$guid]['gibbonRoleIDCurrent'], $connection2);
                    if ($category == 'Staff') {
                        $smartWorkflowHelp = getSmartWorkflowHelp($connection2, $guid, 5);
                        if ($smartWorkflowHelp != false) {
                            echo $smartWorkflowHelp;
                        }
                    }

                    //Add multiple columns
                    if ($multiAdd) {
                        echo "<div class='linkTop'>";
                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/markbook_edit_addMulti.php&gibbonCourseClassID=$gibbonCourseClassID'>".__($guid, 'Add Multiple Records')."<img style='margin-left: 5px' title='".__($guid, 'Add Multiple Records')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new_multi.png'/></a>";
                        echo '</div>';
                    }

                    //Get class chooser
                    echo classChooser($guid, $connection2, $gibbonCourseClassID);

                    //Get teacher list
                    $teaching = false;
                    $teachers = '';
                    try {
                        $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                        $sql = "SELECT gibbonPerson.gibbonPersonID, title, surname, preferredName FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE role='Teacher' AND gibbonCourseClassID=:gibbonCourseClassID ORDER BY surname, preferredName";
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }
                    if ($result->rowCount() > 0) {
                        while ($row = $result->fetch()) {
                            $teachers .= formatName($row['title'], $row['preferredName'], $row['surname'], 'Staff', false, true).', ';
                            if ($row['gibbonPersonID'] == $_SESSION[$guid]['gibbonPersonID']) {
                                $teaching = true;
                            }
                        }
                    }

                    //Count number of columns
                    try {
                        $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                        $sql = 'SELECT * FROM gibbonMarkbookColumn WHERE gibbonCourseClassID=:gibbonCourseClassID ORDER BY complete, completeDate DESC';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }
                    $columns = $result->rowCount();
                    if ($columns < 1) {
                        echo "<div class='linkTop'>";
                        if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_view.php') and $teaching) {
                            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/markbook_edit_add.php&gibbonCourseClassID=$gibbonCourseClassID'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
                        }
                        echo '</div>';

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
                        $columnsPerPage = 12;
                        $columnsThisPage = $columnsPerPage;

                        if ($columns < $columnsPerPage) {
                            $columnsThisPage = $columns;
                        }
                        if ($columns - ($x * $columnsPerPage) < $columnsPerPage) {
                            $columnsThisPage = $columns - ($x * $columnsPerPage);
                        }
                        try {
                            $data = array('gibbonCourseClassID' => $gibbonCourseClassID);
                            $sql = 'SELECT * FROM gibbonMarkbookColumn WHERE gibbonCourseClassID=:gibbonCourseClassID ORDER BY sequenceNumber, complete, completeDate DESC LIMIT '.($x * $columnsPerPage).', '.$columnsPerPage;
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
                                        $sqlExternalAssessment = "SELECT gibbonExternalAssessment.name AS assessment, gibbonExternalAssessmentField.name, gibbonExternalAssessmentFieldID, category FROM gibbonExternalAssessmentField JOIN gibbonExternalAssessment ON (gibbonExternalAssessmentField.gibbonExternalAssessmentID=gibbonExternalAssessment.gibbonExternalAssessmentID) WHERE gibbonExternalAssessmentField.gibbonExternalAssessmentID=:gibbonExternalAssessmentID AND category=:category $courseWhere ORDER BY name";
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
                                    }
                                }
                            }
                        }

                        echo '<h3>';
                        echo __($guid, 'Results');
                        echo '</h3>';

                        //Print table header
                        echo '<p>';
                        if ($teachers != '') {
                            echo sprintf(__($guid, 'Class taught by %1$s'), substr($teachers, 0, -2)).'. ';
                        }
                        echo __($guid, 'To see more detail on an item (such as a comment or a grade), hover your mouse over it. To see more columns, using the Newer and Older links.');
                        if ($externalAssessment == true) {
                            echo ' '.__($guid, 'The Baseline column is populated based on student performance in external assessments, and can be used as a reference point for the grades in the markbook.');
                        }
                        echo '</p>';

                        echo "<div class='linkTop'>";
                        if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_edit.php')) {
                            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/markbook_edit_add.php&gibbonCourseClassID=$gibbonCourseClassID'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a> | ";
                            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/markbook_edit_targets.php&gibbonCourseClassID=$gibbonCourseClassID'>".__($guid, 'Targets')."<img style='margin-left: 5px' title='".__($guid, 'Set Personalised Attainment Targets')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/target.png'/></a> | ";
                            echo "<a href='".$_SESSION[$guid]['absoluteURL']."/modules/Markbook/markbook_viewExportAll.php?gibbonCourseClassID=$gibbonCourseClassID&return=markbook_view.php'>".__($guid, 'Export to Excel')."<img style='margin-left: 5px' title='".__($guid, 'Export to Excel')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/download.png'/></a> | ";
                            echo "<div style='padding-top: 16px; margin-left: 10px; float: right'>";
                            if ($x <= 0) {
                                echo __($guid, 'Newer');
                            } else {
                                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/markbook_view.php&gibbonCourseClassID=$gibbonCourseClassID&page=".($x - 1)."'>".__($guid, 'Newer').'</a>';
                            }
                            echo ' | ';
                            if ((($x + 1) * $columnsPerPage) >= $columns) {
                                echo __($guid, 'Older');
                            } else {
                                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/markbook_view.php&gibbonCourseClassID=$gibbonCourseClassID&page=".($x + 1)."'>".__($guid, 'Older').'</a>';
                            }
                            echo '</div>';
                        }
                        echo '</div>';

                        ?>
                        <script type='text/javascript'> 
                            $(document).ready(function(){
                                $("#myTable").on('dragtablestop', function( event ) {
                                    $.ajax({ 
                                        url: "<?php echo $_SESSION[$guid]['absoluteURL'] ?>/modules/Markbook/markbook_viewAjax.php",
                                        data: { order: $(this).dragtable('order') },
                                        method: "POST",
                                    })
                                    .done(function( data ) {
                                        //alert( "success" );
                                        if (data != '') alert( data );
                                    })
                                    .fail(function() {
                                        alert( '<?php echo __($guid, 'Error'); ?>'  );
                                    });
                                });
                            });
                        </script>

                        <?php

                        echo '<div class="doublescroll-wrapper">';

                        echo '<span id="loading"></span>';

                        echo "<div class='doublescroll-top'><div class='doublescroll-top-tablewidth'></div></div>";
                        echo "<div class='doublescroll-container'>";

                        echo "<table id='myTable' class='mini' cellspacing='0' style='margin-top: 0px'>";
                        echo "<thead>";
                        echo "<tr class='head' style='height: 120px'>";
                        echo "<th class='notdraggable' data-header='student' rowspan=2>";
                            echo "<span>";
                            echo __($guid, 'Student');
                            echo "</span>";
                        echo '</th>';

                                //Show Baseline data header
                                if ($externalAssessment == true) {
                                    echo "<th data-header='assessment' style='width: 40px; min-width: 40px; max-width: 40px' rowspan=2>";
                                    $title = __($guid, $externalAssessmentFields[2]).' | ';
                                    $title .= __($guid, substr($externalAssessmentFields[3], (strpos($externalAssessmentFields[3], '_') + 1))).' | ';
                                    $title .= __($guid, $externalAssessmentFields[1]);

                                        //Get PAS
                                        $PAS = getSettingByScope($connection2, 'System', 'primaryAssessmentScale');
                                    try {
                                        $dataPAS = array('gibbonScaleID' => $PAS);
                                        $sqlPAS = 'SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID';
                                        $resultPAS = $connection2->prepare($sqlPAS);
                                        $resultPAS->execute($dataPAS);
                                    } catch (PDOException $e) {
                                    }
                                    if ($resultPAS->rowCount() == 1) {
                                        $rowPAS = $resultPAS->fetch();
                                        $title .= ' | '.$rowPAS['name'].' '.__($guid, 'Scale').' ';
                                    }

                                    echo "<div class='verticalText' title='$title'>";
                                    echo __($guid, 'Baseline').'<br/>';
                                    echo '</div>';
                                    echo '</th>';
                                }

                                //Show target grade header
                            echo "<th class='notdraggable dragtable-drag-boundary' data-header='target' style='width: 40px; min-width: 40px; max-width: 40px' rowspan=2>";
                        $title = __($guid, 'Personalised attainment target grade');

                                    //Get PAS
                                    $PAS = getSettingByScope($connection2, 'System', 'primaryAssessmentScale');
                        try {
                            $dataPAS = array('gibbonScaleID' => $PAS);
                            $sqlPAS = 'SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID';
                            $resultPAS = $connection2->prepare($sqlPAS);
                            $resultPAS->execute($dataPAS);
                        } catch (PDOException $e) {
                        }
                        if ($resultPAS->rowCount() == 1) {
                            $rowPAS = $resultPAS->fetch();
                            $title .= ' | '.$rowPAS['name'].' Scale ';
                        }

                        echo "<div class='verticalText' title='$title'>";
                        echo __($guid, 'Target').'<br/>';
                        echo '</div>';
                        echo '</th>';

                                //Show weighted scrore
                                if ($enableColumnWeighting == 'Y') {
                                    echo "<th class='notdraggable dragtable-drag-boundary' data-header='weighting' style='width: 40px; min-width: 40px; max-width: 40px' rowspan=2>";
                                    if ($attainmentAlternativeName != '' and $attainmentAlternativeNameAbrev != '') {
                                        $title = sprintf(__($guid, 'Weighted mean of all marked columns using Primary Assessment Scale for %1$s, if numeric'), $attainmentAlternativeName);
                                    } else {
                                        $title = sprintf(__($guid, 'Weighted mean of all marked columns using Primary Assessment Scale for %1$s, if numeric'), 'Attainment');
                                    }

                                    echo "<div class='verticalText' title='$title'>";
                                    echo __($guid, 'Total').'<br/>';
                                    echo '</div>';
                                    echo '</th>';

                                    //Cache all weighting data for efficient use below
                                    $weightings = array();
                                    $weightingsCount = 0;
                                    try {
                                        $dataWeighting = array('gibbonCourseClassID' => $gibbonCourseClassID);
                                        $sqlWeighting = "SELECT attainmentWeighting, attainmentValue, gibbonPersonIDStudent FROM gibbonMarkbookEntry JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) JOIN gibbonScale ON (gibbonMarkbookColumn.gibbonScaleIDAttainment=gibbonScale.gibbonScaleID) WHERE gibbonCourseClassID=:gibbonCourseClassID AND gibbonScale.numeric='Y' AND gibbonScaleID=(SELECT value FROM gibbonSetting WHERE scope='System' AND name='primaryAssessmentScale') AND complete='Y' AND NOT attainmentValue='' ORDER BY gibbonPersonIDStudent";
                                        $resultWeighting = $connection2->prepare($sqlWeighting);
                                        $resultWeighting->execute($dataWeighting);
                                    } catch (PDOException $e) {
                                    }
                                    while ($rowWeightings = $resultWeighting->fetch()) {
                                        $weightings[$weightingsCount][0] = $rowWeightings['attainmentWeighting'];
                                        $weightings[$weightingsCount][1] = $rowWeightings['attainmentValue'];
                                        $weightings[$weightingsCount][2] = $rowWeightings['gibbonPersonIDStudent'];
                                        ++$weightingsCount;
                                    }
                                }

                        $columnID = array();
                        $attainmentID = array();
                        $effortID = array();
                        for ($i = 0; $i < $columnsThisPage; ++$i) {
                            $row = $result->fetch();

                            if ($row === false) {
                                $columnID[$i] = false;
                            } else {
                                $columnID[$i] = $row['gibbonMarkbookColumnID'];
                                $attainmentOn[$i] = $row['attainment'];
                                $attainmentID[$i] = $row['gibbonScaleIDAttainment'];
                                $effortOn[$i] = $row['effort'];
                                $effortID[$i] = $row['gibbonScaleIDEffort'];
                                $gibbonPlannerEntryID[$i] = $row['gibbonPlannerEntryID'];
                                $gibbonRubricIDAttainment[$i] = $row['gibbonRubricIDAttainment'];
                                $gibbonRubricIDEffort[$i] = $row['gibbonRubricIDEffort'];
                                $comment[$i] = $row['comment'];
                                $uploadedResponse[$i] = $row['uploadedResponse'];
                                $submission[$i] = false;

                                        //WORK OUT IF THERE IS SUBMISSION
                                        if (is_null($row['gibbonPlannerEntryID']) == false) {
                                            try {
                                                $dataSub = array('gibbonPlannerEntryID' => $row['gibbonPlannerEntryID']);
                                                $sqlSub = "SELECT * FROM gibbonPlannerEntry WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND homeworkSubmission='Y'";
                                                $resultSub = $connection2->prepare($sqlSub);
                                                $resultSub->execute($dataSub);
                                            } catch (PDOException $e) {
                                                echo "<div class='error'>".$e->getMessage().'</div>';
                                            }

                                            if ($resultSub->rowCount() == 1) {
                                                $submission[$i] = true;
                                                $rowSub = $resultSub->fetch();
                                                $homeworkDueDateTime[$i] = $rowSub['homeworkDueDateTime'];
                                                $lessonDate[$i] = $rowSub['date'];
                                            }
                                        }
                            }

                                    //Column count
                                    $span = 0;
                            $contents = true;
                            if ($submission[$i] == true) {
                                ++$span;
                            }
                            if ($attainmentOn[$i] == 'Y' and ($attainmentID[$i] != '' or $gibbonRubricIDAttainment[$i] != '')) {
                                ++$span;
                            }
                            if ($effortOn[$i] == 'Y' and ($effortID[$i] != '' or $gibbonRubricIDEffort[$i] != '')) {
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

                            echo "<th class='notdraggable' data-header='".$row['gibbonMarkbookColumnID']."' style='margin-left: 100px; text-align: center; min-width: 140px' colspan=$span>";
                            echo "<div class='dragtable-drag-handle'></div>";
                            echo "<span title='".htmlPrep($row['description'])."'>".$row['name'].'</span><br/>';
                            echo "<span style='font-size: 90%; font-style: italic; font-weight: normal'>";
                            $unit = getUnit($connection2, $row['gibbonUnitID'], '', $row['gibbonCourseClassID']);
                            if (isset($unit[0])) {
                                echo $unit[0].'<br/>';
                            } else {
                                echo '<br/>';
                            }
                            if ($row['completeDate'] != '') {
                                echo __($guid, 'Marked on').' '.dateConvertBack($guid, $row['completeDate']).'<br/>';
                            } else {
                                echo __($guid, 'Unmarked').'<br/>';
                            }
                            echo $row['type'];
                            if ($enableColumnWeighting == 'Y' and $row['attainmentWeighting'] != null and $row['attainmentWeighting'] != 0) {
                                echo ' . '.__($guid, 'Weighting').' '.$row['attainmentWeighting'];
                            }
                            if ($row['attachment'] != '' and file_exists($_SESSION[$guid]['absolutePath'].'/'.$row['attachment'])) {
                                echo " | <a 'title='".__($guid, 'Download more information')."' href='".$_SESSION[$guid]['absoluteURL'].'/'.$row['attachment']."'>More info</a>";
                            }
                            echo '</span><br/>';
                            if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_edit.php')) {
                                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/markbook_edit_edit.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=".$row['gibbonMarkbookColumnID']."'><img style='margin-top: 3px' title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/markbook_edit_data.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=".$row['gibbonMarkbookColumnID']."'><img style='margin-top: 3px' title='".__($guid, 'Enter Data')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/markbook.png'/></a> ";
                                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/markbook_edit_delete.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=".$row['gibbonMarkbookColumnID']."'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a> ";
                                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/modules/Markbook/markbook_viewExport.php?gibbonMarkbookColumnID='.$row['gibbonMarkbookColumnID']."&gibbonCourseClassID=$gibbonCourseClassID&return=markbook_view.php'><img title='".__($guid, 'Export to Excel')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/download.png'/></a>";
                            }
                            echo '</th>';
                        }
                        echo '</tr>';
                        echo "</thead>";

                        echo "<tbody>";
                        // echo "<tr class='head'>";

                        // echo "<th style='text-align: center'></th>";

                        // for ($i = 0; $i < $columnsThisPage; ++$i) {
                        //     if ($columnID[$i] == false or $contents == false) {
                        //         echo "<th style='text-align: center' colspan=$span>";

                        //         echo '</th>';
                        //     } else {
                        //         $leftBorder = false;
                        //         if ($attainmentOn[$i] == 'Y' and ($attainmentID[$i] != '' or $gibbonRubricIDAttainment[$i] != '')) {
                        //             $leftBorder = true;
                        //             echo "<th style='border-left: 2px solid #666; text-align: center; width: 40px'>";
                        //             try {
                        //                 $dataScale = array('gibbonScaleID' => $attainmentID[$i]);
                        //                 $sqlScale = 'SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID';
                        //                 $resultScale = $connection2->prepare($sqlScale);
                        //                 $resultScale->execute($dataScale);
                        //             } catch (PDOException $e) {
                        //                 echo "<div class='error'>".$e->getMessage().'</div>';
                        //             }
                        //             $scale = '';
                        //             if ($resultScale->rowCount() == 1) {
                        //                 $rowScale = $resultScale->fetch();
                        //                 $scale = ' - '.$rowScale['name'];
                        //                 if ($rowScale['usage'] != '') {
                        //                     $scale = $scale.': '.$rowScale['usage'];
                        //                 }
                        //             }
                        //             if ($attainmentAlternativeName != '' and $attainmentAlternativeNameAbrev != '') {
                        //                 echo "<span title='".$attainmentAlternativeName.htmlPrep($scale)."'>".$attainmentAlternativeNameAbrev.'</span>';
                        //             } else {
                        //                 echo "<span title='".__($guid, 'Attainment').htmlPrep($scale)."'>".__($guid, 'Att').'</span>';
                        //             }
                        //             echo '</th>';
                        //         }
                        //         if ($effortOn[$i] == 'Y' and ($effortID[$i] != '' or $gibbonRubricIDEffort[$i] != '')) {
                        //             $leftBorderStyle = '';
                        //             if ($leftBorder == false) {
                        //                 $leftBorder = true;
                        //                 $leftBorderStyle = 'border-left: 2px solid #666;';
                        //             }
                        //             echo "<th style='$leftBorderStyle text-align: center; width: 40px'>";
                        //             try {
                        //                 $dataScale = array('gibbonScaleID' => $effortID[$i]);
                        //                 $sqlScale = 'SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID';
                        //                 $resultScale = $connection2->prepare($sqlScale);
                        //                 $resultScale->execute($dataScale);
                        //             } catch (PDOException $e) {
                        //                 echo "<div class='error'>".$e->getMessage().'</div>';
                        //             }
                        //             $scale = '';
                        //             if ($resultScale->rowCount() == 1) {
                        //                 $rowScale = $resultScale->fetch();
                        //                 $scale = ' - '.$rowScale['name'];
                        //                 if ($rowScale['usage'] != '') {
                        //                     $scale = $scale.': '.$rowScale['usage'];
                        //                 }
                        //             }
                        //             if ($effortAlternativeName != '' and $effortAlternativeNameAbrev != '') {
                        //                 echo "<span title='".$effortAlternativeName.htmlPrep($scale)."'>".$effortAlternativeNameAbrev.'</span>';
                        //             } else {
                        //                 echo "<span title='".__($guid, 'Effort').htmlPrep($scale)."'>".__($guid, 'Eff').'</span>';
                        //             }
                        //             echo '</th>';
                        //         }
                        //         if ($comment[$i] == 'Y') {
                        //             $leftBorderStyle = '';
                        //             if ($leftBorder == false) {
                        //                 $leftBorder = true;
                        //                 $leftBorderStyle = 'border-left: 2px solid #666;';
                        //             }
                        //             echo "<th style='$leftBorderStyle text-align: center; width: 80px'>";
                        //             echo "<span title='".__($guid, 'Comment')."'>".__($guid, 'Com').'</span>';
                        //             echo '</th>';
                        //         }
                        //         if ($uploadedResponse[$i] == 'Y') {
                        //             $leftBorderStyle = '';
                        //             if ($leftBorder == false) {
                        //                 $leftBorder = true;
                        //                 $leftBorderStyle = 'border-left: 2px solid #666;';
                        //             }
                        //             echo "<th style='$leftBorderStyle text-align: center; width: 30px'>";
                        //             echo "<span title='".__($guid, 'Uploaded Response')."'>".__($guid, 'Upl').'</span>';
                        //             echo '</th>';
                        //         }
                        //         if (isset($submission[$i])) {
                        //             if ($submission[$i] == true) {
                        //                 $leftBorderStyle = '';
                        //                 if ($leftBorder == false) {
                        //                     $leftBorder = true;
                        //                     $leftBorderStyle = 'border-left: 2px solid #666;';
                        //                 }
                        //                 echo "<th style='$leftBorderStyle text-align: center; width: 30px'>";
                        //                 echo "<span title='".__($guid, 'Submitted Work')."'>".__($guid, 'Sub').'</span>';
                        //                 echo '</th>';
                        //             }
                        //         }
                        //     }
                        // }
                        // echo '</tr>';

                        $count = 0;
                        $rowNum = 'odd';

                        try {
                            $dataStudents = array('gibbonCourseClassID' => $gibbonCourseClassID);
                            $sqlStudents = "SELECT title, surname, preferredName, gibbonPerson.gibbonPersonID, dateStart FROM gibbonCourseClassPerson JOIN gibbonPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE role='Student' AND gibbonCourseClassID=:gibbonCourseClassID AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') ORDER BY surname, preferredName";
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
                                        $sqlEntry = "SELECT gibbonScaleGrade.value, gibbonScaleGrade.descriptor, gibbonExternalAssessmentStudent.date FROM gibbonExternalAssessmentStudentEntry JOIN gibbonExternalAssessmentStudent ON (gibbonExternalAssessmentStudentEntry.gibbonExternalAssessmentStudentID=gibbonExternalAssessmentStudent.gibbonExternalAssessmentStudentID) JOIN gibbonScaleGrade ON (gibbonExternalAssessmentStudentEntry.gibbonScaleGradeIDPrimaryAssessmentScale=gibbonScaleGrade.gibbonScaleGradeID) WHERE gibbonPersonID=:gibbonPersonID AND gibbonExternalAssessmentFieldID=:gibbonExternalAssessmentFieldID AND NOT gibbonScaleGradeIDPrimaryAssessmentScale='' ORDER BY date DESC";
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

                                echo "<td style='text-align: center'>";
                                try {
                                    $dataEntry = array('gibbonPersonIDStudent' => $rowStudents['gibbonPersonID'], 'gibbonCourseClassID' => $gibbonCourseClassID);
                                    $sqlEntry = 'SELECT * FROM gibbonMarkbookTarget JOIN gibbonScaleGrade ON (gibbonMarkbookTarget.gibbonScaleGradeID=gibbonScaleGrade.gibbonScaleGradeID) WHERE gibbonPersonIDStudent=:gibbonPersonIDStudent AND gibbonCourseClassID=:gibbonCourseClassID';
                                    $resultEntry = $connection2->prepare($sqlEntry);
                                    $resultEntry->execute($dataEntry);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }
                                if ($resultEntry->rowCount() >= 1) {
                                    $rowEntry = $resultEntry->fetch();
                                    echo __($guid, $rowEntry['value']);
                                }
                                echo '</td>';

                                    //Calculate and output weighted totals
                                    if ($enableColumnWeighting == 'Y') {
                                        echo "<td style='text-align: center'>";
                                        $totalWeight = 0;
                                        $cummulativeWeightedScore = 0;
                                        $percent = false;
                                        foreach ($weightings as $weighting) {
                                            if ($weighting[2] == $rowStudents['gibbonPersonID']) {
                                                $totalWeight += $weighting[0];
                                                if (strpos($weighting[1], '%') !== 0) {
                                                    $weighting[1] = str_replace('%', '', $weighting[1]);
                                                    $percent = true;
                                                }
                                                $cummulativeWeightedScore += ($weighting[1] * $weighting[0]);
                                            }
                                        }
                                        if ($totalWeight > 0) {
                                            echo round($cummulativeWeightedScore / $totalWeight, 0);
                                            if ($percent) {
                                                echo '%';
                                            }
                                        }
                                        echo '</td>';
                                    }

                                for ($i = 0; $i < $columnsThisPage; ++$i) {
                                    $row = $result->fetch();
                                    try {
                                        $dataEntry = array('gibbonMarkbookColumnID' => $columnID[($i)], 'gibbonPersonIDStudent' => $rowStudents['gibbonPersonID']);
                                        $sqlEntry = 'SELECT * FROM gibbonMarkbookEntry WHERE gibbonMarkbookColumnID=:gibbonMarkbookColumnID AND gibbonPersonIDStudent=:gibbonPersonIDStudent';
                                        $resultEntry = $connection2->prepare($sqlEntry);
                                        $resultEntry->execute($dataEntry);
                                    } catch (PDOException $e) {
                                        echo "<div class='error'>".$e->getMessage().'</div>';
                                    }
                                    if ($resultEntry->rowCount() == 1) {
                                        $rowEntry = $resultEntry->fetch();
                                        $leftBorder = false;

                                        if ($attainmentOn[$i] == 'Y' and ($attainmentID[$i] != '' or $gibbonRubricIDAttainment[$i] != '')) {
                                            $leftBorder = true;
                                            echo "<td style='border-left: 2px solid #666; text-align: center'>";
                                            if ($attainmentID[$i] != '') {
                                                $styleAttainment = '';
                                                if ($rowEntry['attainmentConcern'] == 'Y') {
                                                    $styleAttainment = "style='color: #".$alert['color'].'; font-weight: bold; border: 2px solid #'.$alert['color'].'; padding: 2px 4px; background-color: #'.$alert['colorBG']."'";
                                                } elseif ($rowEntry['attainmentConcern'] == 'P') {
                                                    $styleAttainment = "style='color: #390; font-weight: bold; border: 2px solid #390; padding: 2px 4px; background-color: #D4F6DC'";
                                                }
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
                                            if ($gibbonRubricIDAttainment[$i] != '') {
                                                echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/'.$_SESSION[$guid]['module'].'/markbook_view_rubric.php&gibbonRubricID='.$gibbonRubricIDAttainment[$i]."&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=".$columnID[$i].'&gibbonPersonID='.$rowStudents['gibbonPersonID']."&mark=FALSE&type=attainment&width=1100&height=550'><img style='margin-bottom: -3px; margin-left: 3px' title='".__($guid, 'View Rubric')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/rubric.png'/></a>";
                                            }
                                            if ($attainmentID[$i] != '') {
                                                echo '</div>';
                                            }
                                            echo '</td>';
                                        }

                                        if ($effortOn[$i] == 'Y' and ($effortID[$i] != '' or $gibbonRubricIDEffort[$i] != '')) {
                                            $leftBorderStyle = '';
                                            if ($leftBorder == false) {
                                                $leftBorder = true;
                                                $leftBorderStyle = 'border-left: 2px solid #666;';
                                            }
                                            echo "<td style='$leftBorderStyle text-align: center;'>";
                                            if ($effortID[$i] != '') {
                                                $styleEffort = '';
                                                if ($rowEntry['effortConcern'] == 'Y') {
                                                    $styleEffort = "style='color: #".$alert['color'].'; font-weight: bold; border: 2px solid #'.$alert['color'].'; padding: 2px 4px; background-color: #'.$alert['colorBG']."'";
                                                }
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
                                            if ($gibbonRubricIDEffort[$i] != '') {
                                                echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/'.$_SESSION[$guid]['module'].'/markbook_view_rubric.php&gibbonRubricID='.$gibbonRubricIDEffort[$i]."&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=".$columnID[$i].'&gibbonPersonID='.$rowStudents['gibbonPersonID']."&mark=FALSE&type=effort&width=1100&height=550'><img style='margin-bottom: -3px; margin-left: 3px' title='".__($guid, 'View Rubric')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/rubric.png'/></a>";
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
                                        if ($attainmentOn[$i] == 'Y' and ($attainmentID[$i] != '' or $gibbonRubricIDAttainment[$i] != '')) {
                                            ++$emptySpan;
                                        }
                                        if ($effortOn[$i] == 'Y' and ($effortID[$i] != '' or $gibbonRubricIDEffort[$i] != '')) {
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
                        echo "</tbody>";
                        echo '</table>';

                        echo '</div>';
                        echo '</div><br/>';
                    }
                }
            }
        }
        //VIEW ACCESS TO MY OWN MARKBOOK DATA
        elseif ($highestAction == 'View Markbook_myMarks') {
            $showStudentAttainmentWarning = getSettingByScope($connection2, 'Markbook', 'showStudentAttainmentWarning');
            $showStudentEffortWarning = getSettingByScope($connection2, 'Markbook', 'showStudentEffortWarning');

            $entryCount = 0;
            echo "<div class='trail'>";
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'View Markbook').'</div>';
            echo '</div>';
            echo '<p>';
            echo __($guid, 'This page shows you your academic results throughout your school career. Only subjects with published results are shown.');
            echo '</p>';

            $and = '';
            $and2 = '';
            $dataList = array();
            $dataEntry = array();
            $filter = null;
            if (isset($_POST['filter'])) {
                $filter = $_POST['filter'];
            }
            if ($filter == '') {
                $filter = $_SESSION[$guid]['gibbonSchoolYearID'];
            }
            if ($filter != '*') {
                $dataList['filter'] = $filter;
                $and .= ' AND gibbonSchoolYearID=:filter';
            }
            $filter2 = null;
            if (isset($_POST['filter2'])) {
                $filter2 = $_POST['filter2'];
            }
            if ($filter2 != '') {
                $dataList['filter2'] = $filter2;
                $and .= ' AND gibbonDepartmentID=:filter2';
            }
            $filter3 = null;
            if (isset($_GET['filter3'])) {
                $filter3 = $_GET['filter3'];
            } elseif (isset($_POST['filter3'])) {
                $filter3 = $_POST['filter3'];
            }
            if ($filter3 != '') {
                $dataEntry['filter3'] = $filter3;
                $and2 .= ' AND type=:filter3';
            }

            echo "<form method='post' action='".$_SESSION[$guid]['absoluteURL'].'/index.php?q='.$_GET['q']."'>";
            echo"<table class='noIntBorder' cellspacing='0' style='width: 100%'>";
            ?>
					<tr>
						<td> 
							<b><?php echo __($guid, 'Learning Area') ?></b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<?php
                            echo "<select name='filter2' id='filter2' style='width:302px'>";
            echo "<option value=''>".__($guid, 'All Learning Areas').'</option>';
            try {
                $dataSelect = array();
                $sqlSelect = "SELECT * FROM gibbonDepartment WHERE type='Learning Area' ORDER BY name";
                $resultSelect = $connection2->prepare($sqlSelect);
                $resultSelect->execute($dataSelect);
            } catch (PDOException $e) {
            }
            while ($rowSelect = $resultSelect->fetch()) {
                $selected = '';
                if ($rowSelect['gibbonDepartmentID'] == $filter2) {
                    $selected = 'selected';
                }
                echo "<option $selected value='".$rowSelect['gibbonDepartmentID']."'>".$rowSelect['name'].'</option>';
            }
            echo '</select>';
            ?>
						</td>
					</tr>
					<tr>
						<td> 
							<b><?php echo __($guid, 'School Year') ?></b><br/>
							<span class="emphasis small"></span>
						</td>
						<td class="right">
							<?php
                            echo "<select name='filter' id='filter' style='width:302px'>";
            echo "<option value='*'>".__($guid, 'All Years').'</option>';
            try {
                $dataSelect = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                $sqlSelect = 'SELECT gibbonSchoolYear.gibbonSchoolYearID, gibbonSchoolYear.name AS year, gibbonYearGroup.name AS yearGroup FROM gibbonStudentEnrolment JOIN gibbonSchoolYear ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) WHERE gibbonPersonID=:gibbonPersonID ORDER BY gibbonSchoolYear.sequenceNumber';
                $resultSelect = $connection2->prepare($sqlSelect);
                $resultSelect->execute($dataSelect);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            while ($rowSelect = $resultSelect->fetch()) {
                $selected = '';
                if ($rowSelect['gibbonSchoolYearID'] == $filter) {
                    $selected = 'selected';
                }
                echo "<option $selected value='".$rowSelect['gibbonSchoolYearID']."'>".$rowSelect['year'].' ('.__($guid, $rowSelect['yearGroup']).')</option>';
            }
            echo '</select>';
            ?>
						</td>
					</tr>
					<?php
                    $types = getSettingByScope($connection2, 'Markbook', 'markbookType');
            if ($types != false) {
                $types = explode(',', $types);
                ?>
						<tr>
							<td> 
								<b><?php echo __($guid, 'Type') ?></b><br/>
								<span class="emphasis small"></span>
							</td>
							<td class="right">
								<select name="filter3" id="filter3" class="standardWidth">
									<option value=""></option>
									<?php
                                    for ($i = 0; $i < count($types); ++$i) {
                                        $selected = '';
                                        if ($filter3 == $types[$i]) {
                                            $selected = 'selected';
                                        }
                                        ?>
										<option <?php echo $selected ?> value="<?php echo trim($types[$i]) ?>"><?php echo trim($types[$i]) ?></option>
									<?php

                                    }
                ?>
								</select>
							</td>
						</tr>
						<?php

            }
            echo '<tr>';
            echo "<td class='right' colspan=2>";
            echo "<input type='hidden' name='q' value='".$_GET['q']."'>";
            echo "<input checked type='checkbox' name='details' class='details' value='Yes' />";
            echo "<span style='font-size: 85%; font-weight: normal; font-style: italic'> ".__($guid, 'Show/Hide Details').'</span>';
            ?>
							<script type="text/javascript">
								/* Show/Hide detail control */
								$(document).ready(function(){
									$(".details").click(function(){
										if ($('input[name=details]:checked').val()=="Yes" ) {
											$(".detailItem").slideDown("fast", $("#detailItem").css("{'display' : 'table-row'}")); 
										} 
										else {
											$(".detailItem").slideUp("fast"); 
										}
									 });
								});
							</script>
							<?php
                            echo "<input type='submit' value='".__($guid, 'Go')."'>";
            echo '</td>';
            echo '</tr>';
            echo'</table>';
            echo '</form>';

            //Get class list
            try {
                $dataList['gibbonPersonID'] = $_SESSION[$guid]['gibbonPersonID'];
                $dataList['gibbonPersonID2'] = $_SESSION[$guid]['gibbonPersonID'];
                $sqlList = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourse.name, gibbonCourseClass.gibbonCourseClassID, gibbonScaleGrade.value AS target FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) LEFT JOIN gibbonMarkbookTarget ON (gibbonMarkbookTarget.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND gibbonMarkbookTarget.gibbonPersonIDStudent=:gibbonPersonID2) LEFT JOIN gibbonScaleGrade ON (gibbonMarkbookTarget.gibbonScaleGradeID=gibbonScaleGrade.gibbonScaleGradeID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID $and ORDER BY course, class";
                $resultList = $connection2->prepare($sqlList);
                $resultList->execute($dataList);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            if ($resultList->rowCount() > 0) {
                while ($rowList = $resultList->fetch()) {
                    try {
                        $dataEntry['gibbonPersonIDStudent'] = $_SESSION[$guid]['gibbonPersonID'];
                        $dataEntry['gibbonCourseClassID'] = $rowList['gibbonCourseClassID'];
                        $sqlEntry = "SELECT *, gibbonMarkbookColumn.comment AS commentOn, gibbonMarkbookColumn.uploadedResponse AS uploadedResponseOn, gibbonMarkbookEntry.comment AS comment FROM gibbonMarkbookEntry JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) WHERE gibbonPersonIDStudent=:gibbonPersonIDStudent AND gibbonCourseClassID=:gibbonCourseClassID AND complete='Y' AND completeDate<='".date('Y-m-d')."' $and2  ORDER BY completeDate";
                        $resultEntry = $connection2->prepare($sqlEntry);
                        $resultEntry->execute($dataEntry);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }
                    if ($resultEntry->rowCount() > 0) {
                        echo '<h4>'.$rowList['course'].'.'.$rowList['class']." <span style='font-size:85%; font-style: italic'>(".$rowList['name'].')</span></h4>';

                        try {
                            $dataTeachers = array('gibbonCourseClassID' => $rowList['gibbonCourseClassID']);
                            $sqlTeachers = "SELECT title, surname, preferredName FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE role='Teacher' AND gibbonCourseClassID=:gibbonCourseClassID ORDER BY surname, preferredName";
                            $resultTeachers = $connection2->prepare($sqlTeachers);
                            $resultTeachers->execute($dataTeachers);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }

                        $teachers = '<p><b>'.__($guid, 'Taught by:').'</b> ';
                        while ($rowTeachers = $resultTeachers->fetch()) {
                            $teachers = $teachers.$rowTeachers['title'].' '.$rowTeachers['surname'].', ';
                        }
                        $teachers = substr($teachers, 0, -2);
                        $teachers = $teachers.'</p>';
                        echo $teachers;

                        if ($rowList['target'] != '') {
                            echo "<div style='font-weight: bold' class='linkTop'>";
                            echo __($guid, 'Target').': '.$rowList['target'];
                            echo '</div>';
                        }

                        echo "<table cellspacing='0' style='width: 100%'>";
                        echo "<tr class='head'>";
                        echo "<th style='width: 120px'>";
                        echo __($guid, 'Assessment');
                        echo '</th>';
                        echo "<th style='width: 75px; text-align: center'>";
                        if ($attainmentAlternativeName != '') {
                            echo $attainmentAlternativeName;
                        } else {
                            echo __($guid, 'Attainment');
                        }
                        echo '</th>';
                        echo "<th style='width: 75px; text-align: center'>";
                        if ($effortAlternativeName != '') {
                            echo $effortAlternativeName;
                        } else {
                            echo __($guid, 'Effort');
                        }
                        echo '</th>';
                        echo '<th>';
                        echo __($guid, 'Comment');
                        echo '</th>';
                        echo "<th style='width: 75px'>";
                        echo __($guid, 'Submission');
                        echo '</th>';
                        echo '</tr>';

                        $count = 0;
                        while ($rowEntry = $resultEntry->fetch()) {
                            if ($count % 2 == 0) {
                                $rowNum = 'even';
                            } else {
                                $rowNum = 'odd';
                            }
                            ++$count;
                            ++$entryCount;

                            echo "<a name='".$rowEntry['gibbonMarkbookEntryID']."'></a>";
                            echo "<tr class=$rowNum>";
                            echo '<td>';
                            echo "<span title='".htmlPrep($rowEntry['description'])."'><b><u>".$rowEntry['name'].'</u></b></span><br/>';
                            echo "<span style='font-size: 90%; font-style: italic; font-weight: normal'>";
                            $unit = getUnit($connection2, $rowEntry['gibbonUnitID'], $rowEntry['gibbonHookID'], $rowEntry['gibbonCourseClassID']);
                            if (isset($unit[0])) {
                                echo $unit[0].'<br/>';
                                if ($unit[1] != '') {
                                    echo '<i>'.$unit[1].' '.__($guid, 'Unit').'</i><br/>';
                                }
                            }
                            if ($rowEntry['completeDate'] != '') {
                                echo __($guid, 'Marked on').' '.dateConvertBack($guid, $rowEntry['completeDate']).'<br/>';
                            } else {
                                echo __($guid, 'Unmarked').'<br/>';
                            }
                            echo $rowEntry['type'];
                            if ($rowEntry['attachment'] != '' and file_exists($_SESSION[$guid]['absolutePath'].'/'.$rowEntry['attachment'])) {
                                echo " | <a 'title='".__($guid, 'Download more information')."' href='".$_SESSION[$guid]['absoluteURL'].'/'.$rowEntry['attachment']."'>".__($guid, 'More info').'</a>';
                            }
                            echo '</span><br/>';
                            echo '</td>';
                            if ($rowEntry['attainment'] == 'N' or ($rowEntry['gibbonScaleIDAttainment'] == '' and $rowEntry['gibbonRubricIDAttainment'] == '')) {
                                echo "<td class='dull' style='color: #bbb; text-align: center'>";
                                echo __($guid, 'N/A');
                                echo '</td>';
                            } else {
                                echo "<td style='text-align: center'>";
                                $attainmentExtra = '';
                                try {
                                    $dataAttainment = array('gibbonScaleID' => $rowEntry['gibbonScaleIDAttainment']);
                                    $sqlAttainment = 'SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID';
                                    $resultAttainment = $connection2->prepare($sqlAttainment);
                                    $resultAttainment->execute($dataAttainment);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }
                                if ($resultAttainment->rowCount() == 1) {
                                    $rowAttainment = $resultAttainment->fetch();
                                    $attainmentExtra = '<br/>'.__($guid, $rowAttainment['usage']);
                                }
                                $styleAttainment = "style='font-weight: bold'";
                                if ($rowEntry['attainmentConcern'] == 'Y' and $showStudentAttainmentWarning == 'Y') {
                                    $styleAttainment = "style='color: #".$alert['color'].'; font-weight: bold; border: 2px solid #'.$alert['color'].'; padding: 2px 4px; background-color: #'.$alert['colorBG']."'";
                                } elseif ($rowEntry['attainmentConcern'] == 'P' and $showStudentAttainmentWarning == 'Y') {
                                    $styleAttainment = "style='color: #390; font-weight: bold; border: 2px solid #390; padding: 2px 4px; background-color: #D4F6DC'";
                                }
                                echo "<div $styleAttainment>".$rowEntry['attainmentValue'];
                                if ($rowEntry['gibbonRubricIDAttainment'] != '') {
                                    echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/Markbook/markbook_view_rubric.php&gibbonRubricID='.$rowEntry['gibbonRubricIDAttainment'].'&gibbonCourseClassID='.$rowEntry['gibbonCourseClassID'].'&gibbonMarkbookColumnID='.$rowEntry['gibbonMarkbookColumnID'].'&gibbonPersonID='.$_SESSION[$guid]['gibbonPersonID']."&mark=FALSE&type=attainment&width=1100&height=550'><img style='margin-bottom: -3px; margin-left: 3px' title='".__($guid, 'View Rubric')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/rubric.png'/></a>";
                                }
                                echo '</div>';
                                if ($rowEntry['attainmentValue'] != '') {
                                    echo "<div class='detailItem' style='font-size: 75%; font-style: italic; margin-top: 2px'><b>".htmlPrep(__($guid, $rowEntry['attainmentDescriptor'])).'</b>'.__($guid, $attainmentExtra).'</div>';
                                }
                                echo '</td>';
                            }
                            if ($rowEntry['effort'] == 'N' or ($rowEntry['gibbonScaleIDEffort'] == '' and $rowEntry['gibbonRubricIDEffort'] == '')) {
                                echo "<td class='dull' style='color: #bbb; text-align: center'>";
                                echo __($guid, 'N/A');
                                echo '</td>';
                            } else {
                                echo "<td style='text-align: center'>";
                                $effortExtra = '';
                                try {
                                    $dataEffort = array('gibbonScaleID' => $rowEntry['gibbonScaleIDEffort']);
                                    $sqlEffort = 'SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID';
                                    $resultEffort = $connection2->prepare($sqlEffort);
                                    $resultEffort->execute($dataEffort);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }
                                if ($resultEffort->rowCount() == 1) {
                                    $rowEffort = $resultEffort->fetch();
                                    $effortExtra = '<br/>'.__($guid, $rowEffort['usage']);
                                }
                                $styleEffort = "style='font-weight: bold'";
                                if ($rowEntry['effortConcern'] == 'Y' and $showStudentEffortWarning == 'Y') {
                                    $styleEffort = "style='color: #".$alert['color'].'; font-weight: bold; border: 2px solid #'.$alert['color'].'; padding: 2px 4px; background-color: #'.$alert['colorBG']."'";
                                }
                                echo "<div $styleEffort>".$rowEntry['effortValue'];
                                if ($rowEntry['gibbonRubricIDEffort'] != '') {
                                    echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/Markbook/markbook_view_rubric.php&gibbonRubricID='.$rowEntry['gibbonRubricIDEffort'].'&gibbonCourseClassID='.$rowEntry['gibbonCourseClassID'].'&gibbonMarkbookColumnID='.$rowEntry['gibbonMarkbookColumnID'].'&gibbonPersonID='.$_SESSION[$guid]['gibbonPersonID']."&mark=FALSE&type=effort&width=1100&height=550'><img style='margin-bottom: -3px; margin-left: 3px' title='".__($guid, 'View Rubric')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/rubric.png'/></a>";
                                }
                                echo '</div>';
                                if ($rowEntry['effortValue'] != '') {
                                    echo "<div class='detailItem' style='font-size: 75%; font-style: italic; margin-top: 2px'>";
                                    echo '<b>'.htmlPrep(__($guid, $rowEntry['effortDescriptor'])).'</b>';
                                    if ($effortExtra != '') {
                                        echo __($guid, $effortExtra);
                                    }
                                    echo '</div>';
                                }
                                echo '</td>';
                            }
                            if ($rowEntry['commentOn'] == 'N' and $rowEntry['uploadedResponseOn'] == 'N') {
                                echo "<td class='dull' style='color: #bbb; text-align: left'>";
                                echo __($guid, 'N/A');
                                echo '</td>';
                            } else {
                                echo '<td>';
                                if ($rowEntry['comment'] != '') {
                                    if (strlen($rowEntry['comment']) > 200) {
                                        echo "<script type='text/javascript'>";
                                        echo '$(document).ready(function(){';
                                        echo "\$(\".comment-$entryCount\").hide();";
                                        echo "\$(\".show_hide-$entryCount\").fadeIn(1000);";
                                        echo "\$(\".show_hide-$entryCount\").click(function(){";
                                        echo "\$(\".comment-$entryCount\").fadeToggle(1000);";
                                        echo '});';
                                        echo '});';
                                        echo '</script>';
                                        echo '<span>'.substr($rowEntry['comment'], 0, 200).'...<br/>';
                                        echo "<a title='".__($guid, 'View Description')."' class='show_hide-$entryCount' onclick='return false;' href='#'>".__($guid, 'Read more').'</a></span><br/>';
                                    } else {
                                        echo nl2br($rowEntry['comment']);
                                    }
                                    echo '<br/>';
                                }
                                if ($rowEntry['response'] != '') {
                                    echo "<a title='".__($guid, 'Uploaded Response')."' href='".$_SESSION[$guid]['absoluteURL'].'/'.$rowEntry['response']."'>".__($guid, 'Uploaded Response').'</a><br/>';
                                }
                                echo '</td>';
                            }
                            if ($rowEntry['gibbonPlannerEntryID'] == 0) {
                                echo "<td class='dull' style='color: #bbb; text-align: left'>";
                                echo __($guid, 'N/A');
                                echo '</td>';
                            } else {
                                try {
                                    $dataSub = array('gibbonPlannerEntryID' => $rowEntry['gibbonPlannerEntryID']);
                                    $sqlSub = "SELECT * FROM gibbonPlannerEntry WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND homeworkSubmission='Y'";
                                    $resultSub = $connection2->prepare($sqlSub);
                                    $resultSub->execute($dataSub);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }
                                if ($resultSub->rowCount() != 1) {
                                    echo "<td class='dull' style='color: #bbb; text-align: left'>";
                                    echo __($guid, 'N/A');
                                    echo '</td>';
                                } else {
                                    echo '<td>';
                                    $rowSub = $resultSub->fetch();

                                    try {
                                        $dataWork = array('gibbonPlannerEntryID' => $rowEntry['gibbonPlannerEntryID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                                        $sqlWork = 'SELECT * FROM gibbonPlannerEntryHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID ORDER BY count DESC';
                                        $resultWork = $connection2->prepare($sqlWork);
                                        $resultWork->execute($dataWork);
                                    } catch (PDOException $e) {
                                        echo "<div class='error'>".$e->getMessage().'</div>';
                                    }
                                    if ($resultWork->rowCount() > 0) {
                                        $rowWork = $resultWork->fetch();

                                        if ($rowWork['status'] == 'Exemption') {
                                            $linkText = __($guid, 'Exemption');
                                        } elseif ($rowWork['version'] == 'Final') {
                                            $linkText = __($guid, 'Final');
                                        } else {
                                            $linkText = __($guid, 'Draft').' '.$rowWork['count'];
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
                                            echo "<span title='".$rowWork['version'].". $status. ".sprintf(__($guid, 'Submitted at %1$s on %2$s'), substr($rowWork['timestamp'], 11, 5), dateConvertBack($guid, substr($rowWork['timestamp'], 0, 10)))."' $style><a href='".$_SESSION[$guid]['absoluteURL'].'/'.$rowWork['location']."'>$linkText</a></span>";
                                        } elseif ($rowWork['type'] == 'Link') {
                                            echo "<span title='".$rowWork['version'].". $status. ".sprintf(__($guid, 'Submitted at %1$s on %2$s'), substr($rowWork['timestamp'], 11, 5), dateConvertBack($guid, substr($rowWork['timestamp'], 0, 10)))."' $style><a target='_blank' href='".$rowWork['location']."'>$linkText</a></span>";
                                        } else {
                                            echo "<span title='$status. ".sprintf(__($guid, 'Recorded at %1$s on %2$s'), substr($rowWork['timestamp'], 11, 5), dateConvertBack($guid, substr($rowWork['timestamp'], 0, 10)))."' $style>$linkText</span>";
                                        }
                                    } else {
                                        if (date('Y-m-d H:i:s') < $rowSub['homeworkDueDateTime']) {
                                            echo "<span title='Pending'>".__($guid, 'Pending').'</span>';
                                        } else {
                                            if ($row['dateStart'] > $rowSub['date']) {
                                                echo "<span title='".__($guid, 'Student joined school after assessment was given.')."' style='color: #000; font-weight: normal; border: 2px none #ff0000; padding: 2px 4px'>".__($guid, 'NA').'</span>';
                                            } else {
                                                if ($rowSub['homeworkSubmissionRequired'] == 'Compulsory') {
                                                    echo "<div style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px; margin: 2px 0px'>".__($guid, 'Incomplete').'</div>';
                                                } else {
                                                    echo __($guid, 'Not submitted online');
                                                }
                                            }
                                        }
                                    }
                                    echo '</td>';
                                }
                            }
                            echo '</tr>';
                            if (strlen($rowEntry['comment']) > 200) {
                                echo "<tr class='comment-$entryCount' id='comment-$entryCount'>";
                                echo '<td colspan=6>';
                                echo nl2br($rowEntry['comment']);
                                echo '</td>';
                                echo '</tr>';
                            }
                        }
                        echo '</table>';
                    }
                }
            }

            if ($entryCount < 1) {
                echo "<div class='error'>";
                echo 'There are currently no grades to display in this view.';
                echo '</div>';
            }
        }
        //VIEW ACCESS TO MY CHILDREN'S MARKBOOK DATA
        elseif ($highestAction == 'View Markbook_viewMyChildrensClasses') {
            $entryCount = 0;
            echo "<div class='trail'>";
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>View Markbook</div>";
            echo '</div>';
            echo '<p>';
            echo "This page shows your children's academic results throughout your school career. Only subjects with published results are shown.";
            echo '</p>';

            //Test data access field for permission
            try {
                $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                $sql = "SELECT * FROM gibbonFamilyAdult WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y'";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() < 1) {
                echo "<div class='error'>";
                echo __($guid, 'Access denied.');
                echo '</div>';
            } else {
                //Get child list
                $count = 0;
                $options = '';
                while ($row = $result->fetch()) {
                    try {
                        $dataChild = array('gibbonFamilyID' => $row['gibbonFamilyID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                        $sqlChild = "SELECT * FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonFamilyID=:gibbonFamilyID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName ";
                        $resultChild = $connection2->prepare($sqlChild);
                        $resultChild->execute($dataChild);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }
                    while ($rowChild = $resultChild->fetch()) {
                        $select = '';
                        if (isset($_GET['search'])) {
                            if ($rowChild['gibbonPersonID'] == $_GET['search']) {
                                $select = 'selected';
                            }
                        }

                        $options = $options."<option $select value='".$rowChild['gibbonPersonID']."'>".formatName('', $rowChild['preferredName'], $rowChild['surname'], 'Student', true).'</option>';
                        $gibbonPersonID[$count] = $rowChild['gibbonPersonID'];
                        ++$count;
                    }
                }

                if ($count == 0) {
                    echo "<div class='error'>";
                    echo __($guid, 'Access denied.');
                    echo '</div>';
                } elseif ($count == 1) {
                    $_GET['search'] = $gibbonPersonID[0];
                } else {
                    echo '<h2>';
                    echo 'Choose Student';
                    echo '</h2>';

                    ?>
					<form method="get" action="<?php echo $_SESSION[$guid]['absoluteURL']?>/index.php">
						<table class='noIntBorder' cellspacing='0' style="width: 100%">	
							<tr><td style="width: 30%"></td><td></td></tr>
							<tr>
								<td> 
									<b><?php echo __($guid, 'Search For') ?></b><br/>
									<span class="emphasis small">Preferred, surname, username.</span>
								</td>
								<td class="right">
									<select name="search" id="search" class="standardWidth">
										<option value=""></value>
										<?php echo $options;
                    ?> 
									</select>
								</td>
							</tr>
							<tr>
								<td colspan=2 class="right">
									<input type="hidden" name="q" value="/modules/<?php echo $_SESSION[$guid]['module'] ?>/markbook_view.php">
									<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
									<?php
                                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/markbook_view.php'>".__($guid, 'Clear Search').'</a>';
                    ?>
									<input type="submit" value="<?php echo __($guid, 'Submit');
                    ?>">
								</td>
							</tr>
						</table>
					</form>
					<?php

                }

                $gibbonPersonID = null;
                if (isset($_GET['search'])) {
                    $gibbonPersonID = $_GET['search'];
                }
                $showParentAttainmentWarning = getSettingByScope($connection2, 'Markbook', 'showParentAttainmentWarning');
                $showParentEffortWarning = getSettingByScope($connection2, 'Markbook', 'showParentEffortWarning');

                if ($gibbonPersonID != '' and $count > 0) {
                    //Confirm access to this student
                    try {
                        $dataChild = array('gibbonPersonID' => $gibbonPersonID, 'gibbonPersonID2' => $_SESSION[$guid]['gibbonPersonID']);
                        $sqlChild = "SELECT * FROM gibbonFamilyChild JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonFamilyChild.gibbonPersonID=:gibbonPersonID AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID2 AND childDataAccess='Y'";
                        $resultChild = $connection2->prepare($sqlChild);
                        $resultChild->execute($dataChild);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }
                    if ($resultChild->rowCount() < 1) {
                        echo "<div class='error'>";
                        echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                        echo '</div>';
                    } else {
                        $rowChild = $resultChild->fetch();

                        if ($count > 1) {
                            echo '<h2>';
                            echo 'Filter & Options';
                            echo '</h2>';
                        }

                        $and = '';
                        $and2 = '';
                        $dataList = array();
                        $dataEntry = array();
                        $filter = null;
                        if (isset($_POST['filter'])) {
                            $filter = $_POST['filter'];
                        }
                        if ($filter == '') {
                            $filter = $_SESSION[$guid]['gibbonSchoolYearID'];
                        }
                        if ($filter != '*') {
                            $dataList['filter'] = $filter;
                            $and .= ' AND gibbonSchoolYearID=:filter';
                        }
                        $filter2 = null;
                        if (isset($_POST['filter2'])) {
                            $filter2 = $_POST['filter2'];
                        }
                        if ($filter2 != '') {
                            $dataList['filter2'] = $filter2;
                            $and .= ' AND gibbonDepartmentID=:filter2';
                        }
                        $filter3 = null;
                        if (isset($_GET['filter3'])) {
                            $filter3 = $_GET['filter3'];
                        } elseif (isset($_POST['filter3'])) {
                            $filter3 = $_POST['filter3'];
                        }
                        if ($filter3 != '') {
                            $dataEntry['filter3'] = $filter3;
                            $and2 .= ' AND type=:filter3';
                        }

                        echo "<form method='post' action='".$_SESSION[$guid]['absoluteURL'].'/index.php?q='.$_GET['q']."&search=$gibbonPersonID'>";
                        echo"<table class='noIntBorder' cellspacing='0' style='width: 100%'>";
                        ?>
								<tr>
									<td> 
										<b>Learning Area</b><br/>
										<span class="emphasis small"></span>
									</td>
									<td class="right">
										<?php
                                        echo "<select name='filter2' id='filter2' style='width:302px'>";
                        echo "<option value=''>All Learning Areas</option>";
                        try {
                            $dataSelect = array();
                            $sqlSelect = "SELECT * FROM gibbonDepartment WHERE type='Learning Area' ORDER BY name";
                            $resultSelect = $connection2->prepare($sqlSelect);
                            $resultSelect->execute($dataSelect);
                        } catch (PDOException $e) {
                        }
                        while ($rowSelect = $resultSelect->fetch()) {
                            $selected = '';
                            if ($rowSelect['gibbonDepartmentID'] == $filter2) {
                                $selected = 'selected';
                            }
                            echo "<option $selected value='".$rowSelect['gibbonDepartmentID']."'>".$rowSelect['name'].'</option>';
                        }
                        echo '</select>';
                        ?>
									</td>
								</tr>
								<tr>
									<td> 
										<b><?php echo __($guid, 'School Year') ?></b><br/>
										<span class="emphasis small"></span>
									</td>
									<td class="right">
										<?php
                                        echo "<select name='filter' id='filter' style='width:302px'>";
                        echo "<option value='*'>All Years</option>";
                        try {
                            $dataSelect = array('gibbonPersonID' => $gibbonPersonID);
                            $sqlSelect = 'SELECT gibbonSchoolYear.gibbonSchoolYearID, gibbonSchoolYear.name AS year, gibbonYearGroup.name AS yearGroup FROM gibbonStudentEnrolment JOIN gibbonSchoolYear ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) WHERE gibbonPersonID=:gibbonPersonID ORDER BY gibbonSchoolYear.sequenceNumber';
                            $resultSelect = $connection2->prepare($sqlSelect);
                            $resultSelect->execute($dataSelect);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        while ($rowSelect = $resultSelect->fetch()) {
                            $selected = '';
                            if ($rowSelect['gibbonSchoolYearID'] == $filter) {
                                $selected = 'selected';
                            }
                            echo "<option $selected value='".$rowSelect['gibbonSchoolYearID']."'>".$rowSelect['year'].' ('.__($guid, $rowSelect['yearGroup']).')</option>';
                        }
                        echo '</select>';
                        ?>
									</td>
								</tr>
								<?php
                                $types = getSettingByScope($connection2, 'Markbook', 'markbookType');
                        if ($types != false) {
                            $types = explode(',', $types);
                            ?>
									<tr>
										<td> 
											<b><?php echo __($guid, 'Type') ?></b><br/>
											<span class="emphasis small"></span>
										</td>
										<td class="right">
											<select name="filter3" id="filter3" class="standardWidth">
												<option value=""></option>
												<?php
                                                for ($i = 0; $i < count($types); ++$i) {
                                                    $selected = '';
                                                    if ($filter3 == $types[$i]) {
                                                        $selected = 'selected';
                                                    }
                                                    ?>
													<option <?php echo $selected ?> value="<?php echo trim($types[$i]) ?>"><?php echo trim($types[$i]) ?></option>
												<?php

                                                }
                            ?>
											</select>
										</td>
									</tr>
									<?php

                        }
                        echo '<tr>';
                        echo "<td class='right' colspan=2>";
                        echo "<input type='hidden' name='q' value='".$_GET['q']."'>";
                        echo "<input checked type='checkbox' name='details' class='details' value='Yes' />";
                        echo "<span style='font-size: 85%; font-weight: normal; font-style: italic'> Show/Hide Details</span>";
                        ?>
										<script type="text/javascript">
											/* Show/Hide detail control */
											$(document).ready(function(){
												$(".details").click(function(){
													if ($('input[name=details]:checked').val()=="Yes" ) {
														$(".detailItem").slideDown("fast", $("#detailItem").css("{'display' : 'table-row'}")); 
													} 
													else {
														$(".detailItem").slideUp("fast"); 
													}
												 });
											});
										</script>
										<?php
                                        echo "<input type='submit' value='".__($guid, 'Go')."'>";
                        echo '</td>';
                        echo '</tr>';
                        echo'</table>';
                        echo '</form>';

                        //Get class list
                        try {
                            $dataList['gibbonPersonID'] = $gibbonPersonID;
                            $dataList['gibbonPersonID2'] = $gibbonPersonID;
                            $sqlList = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourse.name, gibbonCourseClass.gibbonCourseClassID, gibbonScaleGrade.value AS target FROM gibbonCourse JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) LEFT JOIN gibbonMarkbookTarget ON (gibbonMarkbookTarget.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND gibbonMarkbookTarget.gibbonPersonIDStudent=:gibbonPersonID2) LEFT JOIN gibbonScaleGrade ON (gibbonMarkbookTarget.gibbonScaleGradeID=gibbonScaleGrade.gibbonScaleGradeID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID $and ORDER BY course, class";
                            $resultList = $connection2->prepare($sqlList);
                            $resultList->execute($dataList);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        if ($resultList->rowCount() > 0) {
                            while ($rowList = $resultList->fetch()) {
                                try {
                                    $dataEntry['gibbonPersonID'] = $gibbonPersonID;
                                    $dataEntry['gibbonCourseClassID'] = $rowList['gibbonCourseClassID'];
                                    $sqlEntry = "SELECT *, gibbonMarkbookColumn.comment AS commentOn, gibbonMarkbookColumn.uploadedResponse AS uploadedResponseOn, gibbonMarkbookEntry.comment AS comment FROM gibbonMarkbookEntry JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) WHERE gibbonPersonIDStudent=:gibbonPersonID AND gibbonCourseClassID=:gibbonCourseClassID AND complete='Y' AND completeDate<='".date('Y-m-d')."' AND viewableParents='Y' $and2 ORDER BY completeDate";
                                    $resultEntry = $connection2->prepare($sqlEntry);
                                    $resultEntry->execute($dataEntry);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>".print_r($dataEntry).'<br/>'.$e->getMessage().'</div>';
                                }
                                if ($resultEntry->rowCount() > 0) {
                                    echo '<h4>'.$rowList['course'].'.'.$rowList['class']." <span style='font-size:85%; font-style: italic'>(".$rowList['name'].')</span></h4>';

                                    try {
                                        $dataTeachers = array('gibbonCourseClassID' => $rowList['gibbonCourseClassID']);
                                        $sqlTeachers = "SELECT title, surname, preferredName FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE role='Teacher' AND gibbonCourseClassID=:gibbonCourseClassID ORDER BY surname, preferredName";
                                        $resultTeachers = $connection2->prepare($sqlTeachers);
                                        $resultTeachers->execute($dataTeachers);
                                    } catch (PDOException $e) {
                                        echo "<div class='error'>".$e->getMessage().'</div>';
                                    }

                                    $teachers = '<p><b>Taught by:</b> ';
                                    while ($rowTeachers = $resultTeachers->fetch()) {
                                        $teachers = $teachers.$rowTeachers['title'].' '.$rowTeachers['surname'].', ';
                                    }
                                    $teachers = substr($teachers, 0, -2);
                                    $teachers = $teachers.'</p>';
                                    echo $teachers;

                                    if ($rowList['target'] != '') {
                                        echo "<div style='font-weight: bold' class='linkTop'>";
                                        echo __($guid, 'Target').': '.$rowList['target'];
                                        echo '</div>';
                                    }

                                    echo "<table cellspacing='0' style='width: 100%'>";
                                    echo "<tr class='head'>";
                                    echo "<th style='width: 120px'>";
                                    echo 'Assessment';
                                    echo '</th>';
                                    echo "<th style='width: 75px; text-align: center'>";
                                    if ($attainmentAlternativeName != '') {
                                        echo $attainmentAlternativeName;
                                    } else {
                                        echo __($guid, 'Attainment');
                                    }
                                    echo '</th>';
                                    echo "<th style='width: 75px; text-align: center'>";
                                    if ($effortAlternativeName != '') {
                                        echo $effortAlternativeName;
                                    } else {
                                        echo __($guid, 'Effort');
                                    }
                                    echo '</th>';
                                    echo '<th>';
                                    echo 'Comment';
                                    echo '</th>';
                                    echo "<th style='width: 75px'>";
                                    echo 'Submission';
                                    echo '</th>';
                                    echo '</tr>';

                                    $count = 0;
                                    while ($rowEntry = $resultEntry->fetch()) {
                                        if ($count % 2 == 0) {
                                            $rowNum = 'even';
                                        } else {
                                            $rowNum = 'odd';
                                        }
                                        ++$count;
                                        ++$entryCount;

                                        echo "<tr class=$rowNum>";
                                        echo '<td>';
                                        echo "<span title='".htmlPrep($rowEntry['description'])."'><b><u>".$rowEntry['name'].'</u></b></span><br/>';
                                        echo "<span style='font-size: 90%; font-style: italic; font-weight: normal'>";
                                        $unit = getUnit($connection2, $rowEntry['gibbonUnitID'], $rowEntry['gibbonHookID'], $rowEntry['gibbonCourseClassID']);
                                        if (isset($unit[0])) {
                                            echo $unit[0].'<br/>';
                                            if ($unit[1] != '') {
                                                echo '<i>'.$unit[1].' Unit</i><br/>';
                                            }
                                        }
                                        if ($rowEntry['completeDate'] != '') {
                                            echo 'Marked on '.dateConvertBack($guid, $rowEntry['completeDate']).'<br/>';
                                        } else {
                                            echo 'Unmarked<br/>';
                                        }
                                        echo $rowEntry['type'];
                                        if ($rowEntry['attachment'] != '' and file_exists($_SESSION[$guid]['absolutePath'].'/'.$rowEntry['attachment'])) {
                                            echo " | <a 'title='Download more information' href='".$_SESSION[$guid]['absoluteURL'].'/'.$rowEntry['attachment']."'>More info</a>";
                                        }
                                        echo '</span><br/>';
                                        echo '</td>';
                                        if ($rowEntry['attainment'] == 'N' or ($rowEntry['gibbonScaleIDAttainment'] == '' and $rowEntry['gibbonRubricIDAttainment'] == '')) {
                                            echo "<td class='dull' style='color: #bbb; text-align: center'>";
                                            echo __($guid, 'N/A');
                                            echo '</td>';
                                        } else {
                                            echo "<td style='text-align: center'>";
                                            $attainmentExtra = '';
                                            try {
                                                $dataAttainment = array('gibbonScaleID' => $rowEntry['gibbonScaleIDAttainment']);
                                                $sqlAttainment = 'SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID';
                                                $resultAttainment = $connection2->prepare($sqlAttainment);
                                                $resultAttainment->execute($dataAttainment);
                                            } catch (PDOException $e) {
                                                echo "<div class='error'>".$e->getMessage().'</div>';
                                            }
                                            if ($resultAttainment->rowCount() == 1) {
                                                $rowAttainment = $resultAttainment->fetch();
                                                $attainmentExtra = '<br/>'.__($guid, $rowAttainment['usage']);
                                            }
                                            $styleAttainment = "style='font-weight: bold'";
                                            if ($rowEntry['attainmentConcern'] == 'Y' and $showParentAttainmentWarning == 'Y') {
                                                $styleAttainment = "style='color: #".$alert['color'].'; font-weight: bold; border: 2px solid #'.$alert['color'].'; padding: 2px 4px; background-color: #'.$alert['colorBG']."'";
                                            } elseif ($rowEntry['attainmentConcern'] == 'P' and $showParentAttainmentWarning == 'Y') {
                                                $styleAttainment = "style='color: #390; font-weight: bold; border: 2px solid #390; padding: 2px 4px; background-color: #D4F6DC'";
                                            }
                                            echo "<div $styleAttainment>".$rowEntry['attainmentValue'];
                                            if ($rowEntry['gibbonRubricIDAttainment'] != '') {
                                                echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/Markbook/markbook_view_rubric.php&gibbonRubricID='.$rowEntry['gibbonRubricIDAttainment'].'&gibbonCourseClassID='.$rowEntry['gibbonCourseClassID'].'&gibbonMarkbookColumnID='.$rowEntry['gibbonMarkbookColumnID']."&gibbonPersonID=$gibbonPersonID&mark=FALSE&type=attainment&width=1100&height=550'><img style='margin-bottom: -3px; margin-left: 3px' title='View Rubric' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/rubric.png'/></a>";
                                            }
                                            echo '</div>';
                                            if ($rowEntry['attainmentValue'] != '') {
                                                echo "<div class='detailItem' style='font-size: 75%; font-style: italic; margin-top: 2px'><b>".htmlPrep(__($guid, $rowEntry['attainmentDescriptor'])).'</b>'.__($guid, $attainmentExtra).'</div>';
                                            }
                                            echo '</td>';
                                        }
                                        if ($rowEntry['effort'] == 'N' or ($rowEntry['gibbonScaleIDEffort'] == '' and $rowEntry['gibbonRubricIDEffort'] == '')) {
                                            echo "<td class='dull' style='color: #bbb; text-align: center'>";
                                            echo __($guid, 'N/A');
                                            echo '</td>';
                                        } else {
                                            echo "<td style='text-align: center'>";
                                            $effortExtra = '';
                                            try {
                                                $dataEffort = array('gibbonScaleID' => $rowEntry['gibbonScaleIDEffort']);
                                                $sqlEffort = 'SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID';
                                                $resultEffort = $connection2->prepare($sqlEffort);
                                                $resultEffort->execute($dataEffort);
                                            } catch (PDOException $e) {
                                                echo "<div class='error'>".$e->getMessage().'</div>';
                                            }
                                            if ($resultEffort->rowCount() == 1) {
                                                $rowEffort = $resultEffort->fetch();
                                                $effortExtra = '<br/>'.__($guid, $rowEffort['usage']);
                                            }
                                            $styleEffort = "style='font-weight: bold'";
                                            if ($rowEntry['effortConcern'] == 'Y' and $showParentEffortWarning == 'Y') {
                                                $styleEffort = "style='color: #".$alert['color'].'; font-weight: bold; border: 2px solid #'.$alert['color'].'; padding: 2px 4px; background-color: #'.$alert['colorBG']."'";
                                            }
                                            echo "<div $styleEffort>".$rowEntry['effortValue'];
                                            if ($rowEntry['gibbonRubricIDEffort'] != '') {
                                                echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/Markbook/markbook_view_rubric.php&gibbonRubricID='.$rowEntry['gibbonRubricIDEffort'].'&gibbonCourseClassID='.$rowEntry['gibbonCourseClassID'].'&gibbonMarkbookColumnID='.$rowEntry['gibbonMarkbookColumnID']."&gibbonPersonID=$gibbonPersonID&mark=FALSE&type=effort&width=1100&height=550'><img style='margin-bottom: -3px; margin-left: 3px' title='View Rubric' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/rubric.png'/></a>";
                                            }
                                            echo '</div>';
                                            if ($rowEntry['effortValue'] != '') {
                                                echo "<div class='detailItem' style='font-size: 75%; font-style: italic; margin-top: 2px'>";
                                                echo '<b>'.htmlPrep(__($guid, $rowEntry['effortDescriptor'])).'</b>';
                                                if ($effortExtra != '') {
                                                    echo __($guid, $effortExtra);
                                                }
                                                echo '</div>';
                                            }
                                            echo '</td>';
                                        }
                                        if ($rowEntry['commentOn'] == 'N' and $rowEntry['uploadedResponseOn'] == 'N') {
                                            echo "<td class='dull' style='color: #bbb; text-align: left'>";
                                            echo __($guid, 'N/A');
                                            echo '</td>';
                                        } else {
                                            echo '<td>';
                                            if ($rowEntry['comment'] != '') {
                                                if (strlen($rowEntry['comment']) > 200) {
                                                    echo "<script type='text/javascript'>";
                                                    echo '$(document).ready(function(){';
                                                    echo "\$(\".comment-$entryCount\").hide();";
                                                    echo "\$(\".show_hide-$entryCount\").fadeIn(1000);";
                                                    echo "\$(\".show_hide-$entryCount\").click(function(){";
                                                    echo "\$(\".comment-$entryCount\").fadeToggle(1000);";
                                                    echo '});';
                                                    echo '});';
                                                    echo '</script>';
                                                    echo '<span>'.substr($rowEntry['comment'], 0, 200).'...<br/>';
                                                    echo "<a title='".__($guid, 'View Description')."' class='show_hide-$entryCount' onclick='return false;' href='#'>Read more</a></span><br/>";
                                                } else {
                                                    echo nl2br($rowEntry['comment']);
                                                }
                                                echo '<br/>';
                                            }
                                            if ($rowEntry['response'] != '') {
                                                echo "<a title='Uploaded Response' href='".$_SESSION[$guid]['absoluteURL'].'/'.$rowEntry['response']."'>Uploaded Response</a><br/>";
                                            }
                                            echo '</td>';
                                        }
                                        if ($rowEntry['gibbonPlannerEntryID'] == 0) {
                                            echo "<td class='dull' style='color: #bbb; text-align: left'>";
                                            echo __($guid, 'N/A');
                                            echo '</td>';
                                        } else {
                                            try {
                                                $dataSub = array('gibbonPlannerEntryID' => $rowEntry['gibbonPlannerEntryID']);
                                                $sqlSub = "SELECT * FROM gibbonPlannerEntry WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND homeworkSubmission='Y'";
                                                $resultSub = $connection2->prepare($sqlSub);
                                                $resultSub->execute($dataSub);
                                            } catch (PDOException $e) {
                                                echo "<div class='error'>".$e->getMessage().'</div>';
                                            }
                                            if ($resultSub->rowCount() != 1) {
                                                echo "<td class='dull' style='color: #bbb; text-align: left'>";
                                                echo __($guid, 'N/A');
                                                echo '</td>';
                                            } else {
                                                echo '<td>';
                                                $rowSub = $resultSub->fetch();

                                                try {
                                                    $dataWork = array('gibbonPlannerEntryID' => $rowEntry['gibbonPlannerEntryID'], 'gibbonPersonID' => $gibbonPersonID);
                                                    $sqlWork = 'SELECT * FROM gibbonPlannerEntryHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID ORDER BY count DESC';
                                                    $resultWork = $connection2->prepare($sqlWork);
                                                    $resultWork->execute($dataWork);
                                                } catch (PDOException $e) {
                                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                                }
                                                if ($resultWork->rowCount() > 0) {
                                                    $rowWork = $resultWork->fetch();

                                                    if ($rowWork['status'] == 'Exemption') {
                                                        $linkText = __($guid, 'Exemption');
                                                    } elseif ($rowWork['version'] == 'Final') {
                                                        $linkText = __($guid, 'Final');
                                                    } else {
                                                        $linkText = __($guid, 'Draft').' '.$rowWork['count'];
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
                                                        echo "<span title='".$rowWork['version'].". $status. ".sprintf(__($guid, 'Submitted at %1$s on %2$s'), substr($rowWork['timestamp'], 11, 5), dateConvertBack($guid, substr($rowWork['timestamp'], 0, 10)))."' $style><a href='".$_SESSION[$guid]['absoluteURL'].'/'.$rowWork['location']."'>$linkText</a></span>";
                                                    } elseif ($rowWork['type'] == 'Link') {
                                                        echo "<span title='".$rowWork['version'].". $status. ".sprintf(__($guid, 'Submitted at %1$s on %2$s'), substr($rowWork['timestamp'], 11, 5), dateConvertBack($guid, substr($rowWork['timestamp'], 0, 10)))."' $style><a target='_blank' href='".$rowWork['location']."'>$linkText</a></span>";
                                                    } else {
                                                        echo "<span title='$status. ".sprintf(__($guid, 'Recorded at %1$s on %2$s'), substr($rowWork['timestamp'], 11, 5), dateConvertBack($guid, substr($rowWork['timestamp'], 0, 10)))."' $style>$linkText</span>";
                                                    }
                                                } else {
                                                    if (date('Y-m-d H:i:s') < $rowSub['homeworkDueDateTime']) {
                                                        echo "<span title='Pending'>".__($guid, 'Pending').'</span>';
                                                    } else {
                                                        if ($row['dateStart'] > $rowSub['date']) {
                                                            echo "<span title='".__($guid, 'Student joined school after assessment was given.')."' style='color: #000; font-weight: normal; border: 2px none #ff0000; padding: 2px 4px'>".__($guid, 'NA').'</span>';
                                                        } else {
                                                            if ($rowSub['homeworkSubmissionRequired'] == 'Compulsory') {
                                                                echo "<div style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px; margin: 2px 0px'>".__($guid, 'Incomplete').'</div>';
                                                            } else {
                                                                echo __($guid, 'Not submitted online');
                                                            }
                                                        }
                                                    }
                                                }
                                                echo '</td>';
                                            }
                                        }
                                        echo '</tr>';
                                        if (strlen($rowEntry['comment']) > 200) {
                                            echo "<tr class='comment-$entryCount' id='comment-$entryCount'>";
                                            echo '<td colspan=6>';
                                            echo nl2br($rowEntry['comment']);
                                            echo '</td>';
                                            echo '</tr>';
                                        }
                                    }
                                    echo '</table>';

                                    try {
                                        $dataEntry2 = array('gibbonPersonIDStudent' => $_SESSION[$guid]['gibbonPersonID']);
                                        $sqlEntry2 = "SELECT gibbonMarkbookEntryID, gibbonMarkbookColumn.name, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class FROM gibbonMarkbookEntry JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) JOIN gibbonCourseClass ON (gibbonMarkbookColumn.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE gibbonPersonIDStudent=:gibbonPersonIDStudent AND complete='Y' AND completeDate<='".date('Y-m-d')."' AND viewableStudents='Y' ORDER BY completeDate DESC, name";
                                        $resultEntry2 = $connection2->prepare($sqlEntry2);
                                        $resultEntry2->execute($dataEntry2);
                                    } catch (PDOException $e) {
                                        echo "<div class='error'>".$e->getMessage().'</div>';
                                    }
                                    if ($resultEntry2->rowCount() > 0) {
                                        $_SESSION[$guid]['sidebarExtra'] = "<h2 class='sidebar'>";
                                        $_SESSION[$guid]['sidebarExtra'] = $_SESSION[$guid]['sidebarExtra'].__($guid, 'Recent Marks');
                                        $_SESSION[$guid]['sidebarExtra'] = $_SESSION[$guid]['sidebarExtra'].'</h2>';

                                        $_SESSION[$guid]['sidebarExtra'] = $_SESSION[$guid]['sidebarExtra'].'<ol>';
                                        $count = 0;

                                        while ($rowEntry2 = $resultEntry2->fetch() and $count < 5) {
                                            $_SESSION[$guid]['sidebarExtra'] = $_SESSION[$guid]['sidebarExtra']."<li><a href='#".$rowEntry2['gibbonMarkbookEntryID']."'>".$rowEntry['course'].'.'.$rowEntry['class']."<br/><span style='font-size: 85%; font-style: italic'>".$rowEntry['name'].'</span></a></li>';
                                            ++$count;
                                        }

                                        $_SESSION[$guid]['sidebarExtra'] = $_SESSION[$guid]['sidebarExtra'].'</ol>';
                                    }
                                }
                            }
                        }
                    }
                }
            }
            if ($entryCount < 1) {
                echo "<div class='error'>";
                echo __($guid, 'There are no records to display.');
                echo '</div>';
            }
        }
    }
}
?>