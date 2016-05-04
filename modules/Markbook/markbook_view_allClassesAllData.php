<?php
	// Lock the file so other scripts cannot call it
	if (MARKBOOK_VIEW_LOCK !== sha1( $highestAction . $_SESSION[$guid]['gibbonPersonID'] ) . date('zWy') ) return;

	require_once './modules/'.$_SESSION[$guid]['module'].'/src/markbookView.php';
	require_once './modules/'.$_SESSION[$guid]['module'].'/src/markbookColumn.php';

    //Check for access to multiple column add
    $multiAdd = false;
    //Add multiple columns
    if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_edit.php')) {
        if ($highestAction2 == 'Edit Markbook_multipleClassesAcrossSchool' or $highestAction2 == 'Edit Markbook_multipleClassesInDepartment' or $highestAction2 == 'Edit Markbook_everything') {
            //Check highest role in any department
            $isCoordinator = isDepartmentCoordinator( $pdo, $_SESSION[$guid]['gibbonPersonID'] );

            if ($isCoordinator == true or $highestAction2 == 'Edit Markbook_multipleClassesAcrossSchool' or $highestAction2 == 'Edit Markbook_everything') {
                $multiAdd = true;
            }
        }
    }

    //Get class variable
    $gibbonCourseClassID = null;
    if (isset($_GET['gibbonCourseClassID'])) {
        $gibbonCourseClassID = $_GET['gibbonCourseClassID'];
    }

    // Grab any taught class
    if ($gibbonCourseClassID == '') {
        $row = getAnyTaughtClass( $pdo, $_SESSION[$guid]['gibbonPersonID'], $_SESSION[$guid]['gibbonSchoolYearID'] );
        $gibbonCourseClassID = (isset($row['gibbonCourseClassID']))? $row['gibbonCourseClassID'] : '';
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
        echo classChooser($guid, $pdo, $gibbonCourseClassID);
        return;
    }

    //Check existence of and access to this class.
    $class = getClass($pdo, $_SESSION[$guid]['gibbonPersonID'], $gibbonCourseClassID);

    if ($class == NULL) {
        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'View Markbook').'</div>';
        echo '</div>';
        echo "<div class='error'>";
        echo __($guid, 'The specified record does not exist.');
        echo '</div>';
        return;
    }

    $courseName = $class['courseName'];
    $gibbonYearGroupIDList = $class['gibbonYearGroupIDList'];
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>View ".$class['course'].'.'.$class['class'].' Markbook</div>';
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
    echo classChooser($guid, $pdo, $gibbonCourseClassID);

    //Get teacher list
    $teacherList = getTeacherList( $pdo, $gibbonCourseClassID );
    $teaching = (isset($teacherList[ $_SESSION[$guid]['gibbonPersonID'] ]) );

    // Build the markbook object for this class
    $markbook = new Module\Markbook\markbookView(NULL, NULL, $pdo, $gibbonCourseClassID );

    if ($markbook == NULL || $markbook->getColumnCountTotal() < 1) {
        echo "<div class='linkTop'>";
        if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_view.php') and $teaching) {
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/markbook_edit_add.php&gibbonCourseClassID=$gibbonCourseClassID'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
        }
        echo '</div>';

        echo "<div class='warning'>";
        echo __($guid, 'There are no records to display.');
        echo '</div>';
    } else {

    	// Load the columns for the current page
        $pageNum = (isset($_GET['page']))? $_GET['page'] : 0;
        $markbook->getColumns( $pageNum );

        //Work out details for external assessment display
        if (isActionAccessible($guid, $connection2, '/modules/Formal Assessment/externalAssessment_details.php')) {
            $markbook->cacheExternalAssessments( $courseName, $gibbonYearGroupIDList );
        }

        echo '<h3>';
        echo __($guid, 'Results');
        echo '</h3>';

        //Print table header
        echo '<p>';
            if (!empty($teacherList)) {
                echo sprintf(__($guid, 'Class taught by %1$s'), implode(',', $teacherList) ).'. ';
            }
            echo __($guid, 'To see more detail on an item (such as a comment or a grade), hover your mouse over it. To see more columns, using the Newer and Older links.');
            if ($markbook->hasExternalAssessments() == true) {
                echo ' '.__($guid, 'The Baseline column is populated based on student performance in external assessments, and can be used as a reference point for the grades in the markbook.');
            }
        echo '</p>';

        // Display the Top Links
        echo "<div class='linkTop'>";
        if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_edit.php')) {
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/markbook_edit_add.php&gibbonCourseClassID=$gibbonCourseClassID'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a> | ";
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/markbook_edit_targets.php&gibbonCourseClassID=$gibbonCourseClassID'>".__($guid, 'Targets')."<img style='margin-left: 5px' title='".__($guid, 'Set Personalised Attainment Targets')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/target.png'/></a> | ";
            echo "<a href='".$_SESSION[$guid]['absoluteURL']."/modules/Markbook/markbook_viewExportAll.php?gibbonCourseClassID=$gibbonCourseClassID&return=markbook_view.php'>".__($guid, 'Export to Excel')."<img style='margin-left: 5px' title='".__($guid, 'Export to Excel')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/download.png'/></a> | ";
            echo "<div style='padding-top: 16px; margin-left: 10px; float: right'>";
            if ($pageNum <= 0) {
                echo __($guid, 'Newer');
            } else {
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/markbook_view.php&gibbonCourseClassID=$gibbonCourseClassID&page=".($pageNum - 1)."'>".__($guid, 'Newer').'</a>';
            }
            echo ' | ';
            if ((($pageNum + 1) * $markbook->getColumnsPerPage() ) >= $markbook->getColumnCountTotal() ) {
                echo __($guid, 'Older');
            } else {
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/markbook_view.php&gibbonCourseClassID=$gibbonCourseClassID&page=".($pageNum + 1)."'>".__($guid, 'Older').'</a>';
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
                        data: { order: $(this).dragtable('order'), sequence: <?php echo $markbook->getMinimumSequenceNumber(); ?> },
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
        echo "<div class='doublescroll-top'><div class='doublescroll-top-tablewidth'></div></div>";
        echo "<div class='doublescroll-container'>";

        echo "<table id='myTable' class='mini' cellspacing='0' style='margin-top: 0px'>";
        echo "<thead>";
        echo "<tr class='head' style='height: 120px'>";
        echo "<th class='notdraggable firstColumn' data-header='student' rowspan=2>";
            echo "<span>";
            echo __($guid, 'Student');
            echo "</span>";
        echo '</th>';

        //Show Baseline data header
        if ($markbook->hasExternalAssessments() == true) {
            echo "<th data-header='assessment' class='notdraggable dragtable-drag-boundary' style='width: 40px; min-width: 40px; max-width: 40px' rowspan=2>";
            $title = __($guid, $externalAssessmentFields[2]).' | ';
            $title .= __($guid, substr($externalAssessmentFields[3], (strpos($externalAssessmentFields[3], '_') + 1))).' | ';
            $title .= __($guid, $externalAssessmentFields[1]);

            //Get PAS
            $PAS = $markbook->getPrimaryAssessmentScale();
            if (!empty($PAS)) {
                $title .= ' | '.$PAS.' '.__($guid, 'Scale').' ';
            }

            echo "<div class='verticalText' title='$title'>";
            echo __($guid, 'Baseline').'<br/>';
            echo '</div>';
            echo '</th>';
        }

        $markbook->cachePersonalizedTargets( $gibbonCourseClassID );

        //Show target grade header
        if ($markbook->getPersonalizedTargetsCount() > 0) {
            echo "<th class='notdraggable dragtable-drag-boundary' data-header='target' style='width: 40px; min-width: 40px; max-width: 40px' rowspan=2>";
            $title = __($guid, 'Personalised attainment target grade');

            //Get PAS
            $PAS = $markbook->getPrimaryAssessmentScale();
            if (!empty($PAS)) {
                $title .= ' | '.$PAS.' '.__($guid, 'Scale').' ';
            }

            echo "<div class='verticalText' title='$title'>";
            echo __($guid, 'Target').'<br/>';
            echo '</div>';
            echo '</th>';
        }

        //Show weighted scrore
        if ($markbook->getSetting('enableColumnWeighting') == 'Y') {
            echo "<th class='notdraggable dragtable-drag-boundary' data-header='weighting' style='width: 40px; min-width: 40px; max-width: 40px' rowspan=2>";
           
            $title = sprintf(__($guid, 'Weighted mean of all marked columns using Primary Assessment Scale for %1$s, if numeric'), 
                    $markbook->getSetting('attainmentName') );

            echo "<div class='verticalText' title='$title'>";
            echo __($guid, 'Total').'<br/>';
            echo '</div>';
            echo '</th>';

            //Cache all weighting data for efficient use below
            $markbook->cacheWeightings( $gibbonCourseClassID );
        }

        $columnID = array();
        $attainmentID = array();
        $effortID = array();
        for ($i = 0; $i < $markbook->getColumnCountThisPage(); ++$i) {

            $column = $markbook->getColumn( $i );

            echo "<th class='notdraggable' data-header='".$column->gibbonMarkbookColumnID."' style='margin-left: 100px; text-align: center; min-width: 140px' >"; 

            echo "<div class='dragtable-drag-handle'></div>";
            echo "<span title='".htmlPrep($column->getData('description') )."'>".$column->getData('name').'</span><br/>';
            echo "<span style='font-size: 90%; font-style: italic; font-weight: normal'>";
            $unit = getUnit($connection2, $column->getData('gibbonUnitID'), '', $column->getData('gibbonCourseClassID') );
            if (isset($unit[0])) {
                echo $unit[0].'<br/>';
            } else {
                echo '<br/>';
            }
            if ($column->getData('completeDate') != '') {
                echo __($guid, 'Marked on').' '.dateConvertBack($guid, $column->getData('completeDate') ).'<br/>';
            } else {
                echo __($guid, 'Unmarked').'<br/>';
            }
            echo $column->getData('type');
            if ($markbook->getSetting('enableColumnWeighting') == 'Y' and $column->hasAttainmentWeighting() ) {
                echo ' . '.__($guid, 'Weighting').' '.$column->getData('attainmentWeighting');
            }
            if ($column->hasAttachment( $_SESSION[$guid]['absolutePath'] )) {
                echo " | <a 'title='".__($guid, 'Download more information')."' href='".$_SESSION[$guid]['absoluteURL'].'/'.$column->getData('attachment')."'>More info</a>";
            }
            echo '</span><br/>';
            if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_edit.php')) {
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/markbook_edit_edit.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=".$column->gibbonMarkbookColumnID."'><img style='margin-top: 3px' title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/markbook_edit_data.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=".$column->gibbonMarkbookColumnID."'><img style='margin-top: 3px' title='".__($guid, 'Enter Data')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/markbook.png'/></a> ";
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/markbook_edit_delete.php&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=".$column->gibbonMarkbookColumnID."'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a> ";
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/modules/Markbook/markbook_viewExport.php?gibbonMarkbookColumnID='.$column->gibbonMarkbookColumnID."&gibbonCourseClassID=$gibbonCourseClassID&return=markbook_view.php'><img title='".__($guid, 'Export to Excel')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/download.png'/></a>";
            }
            echo '</th>';
        }
        echo '</tr>';
        echo "</thead>";

        echo "<tbody>";
        echo "<tr class='head'>";

        echo "<th class='firstColumn blankColumn' style='text-align: center'></th>";

        if ($markbook->hasExternalAssessments() == true) {
        	echo "<th class='blankColumn' style='text-align: center'></th>";
        }

        if ($markbook->getPersonalizedTargetsCount() > 0) {
        	echo "<th class='blankColumn' style='text-align: center'></th>";
        }

        if ($markbook->getSetting('enableColumnWeighting') == 'Y') {
        	echo "<th class='blankColumn' style='text-align: center'></th>";
        }

        for ($i = 0; $i < $markbook->getColumnCountThisPage(); ++$i) {

        	$column = $markbook->getColumn( $i );

        	echo '<th class="columnLabel" style="padding: 0 !important; text-align: center">';
        	echo '<table class="blank miniData" cellspacing=0><tr>';

            if ($column->gibbonMarkbookColumnID == false ) { //or $contents == false
            	echo '<th>';
            	echo '</th>';
            } else {
                $leftBorder = false;
                if ($column->displayAttainment() AND ($column->hasAttainmentGrade() OR $column->hasAttainmentRuberic())) {
                    $leftBorder = true;

                    echo "<th style='border-left: 2px solid #666; text-align: center; width: 40px'>";
                    try {
                        $dataScale = array('gibbonScaleID' => $column->getData('gibbonScaleIDAttainment'));
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
                    
                    echo "<span title='".$markbook->getSetting('attainmentName').htmlPrep($scale)."'>".$markbook->getSetting('attainmentAbrev').'</span>';
                    echo '</th>';
                }
                if ($column->displayEffort() AND ($column->hasEffortGrade() OR $column->hasEffortRubric() )) {
                    $leftBorderStyle = '';
                    if ($leftBorder == false) {
                        $leftBorder = true;
                        $leftBorderStyle = 'border-left: 2px solid #666;';
                    }
 
                    echo "<th style='$leftBorderStyle text-align: center; width: 40px'>";
                    try {
                        $dataScale = array('gibbonScaleID' => $column->getData('gibbonScaleIDEffort'));
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

                    echo "<span title='".$markbook->getSetting('effortName').htmlPrep($scale)."'>".$markbook->getSetting('effortAbrev').'</span>';
                    echo '</th>';
                }
                if ($column->displayComment()) {
                    $leftBorderStyle = '';
                    if ($leftBorder == false) {
                        $leftBorder = true;
                        $leftBorderStyle = 'border-left: 2px solid #666;';
                    }
                    echo "<th style='$leftBorderStyle text-align: center; width: 80px'>";
                    echo "<span title='".__($guid, 'Comment')."'>".__($guid, 'Com').'</span>';
                    echo '</th>';
                }
                if ($column->displayUploadedResponse()) {
                    $leftBorderStyle = '';
                    if ($leftBorder == false) {
                        $leftBorder = true;
                        $leftBorderStyle = 'border-left: 2px solid #666;';
                    }
                    echo "<th style='$leftBorderStyle text-align: center; width: 30px'>";
                    echo "<span title='".__($guid, 'Uploaded Response')."'>".__($guid, 'Upl').'</span>';
                    echo '</th>';
                }
                if ($column->displaySubmission()) {

                    $leftBorderStyle = '';
                    if ($leftBorder == false) {
                        $leftBorder = true;
                        $leftBorderStyle = 'border-left: 2px solid #666;';
                    }
                    echo "<th style='$leftBorderStyle text-align: center; width: 30px'>";
                    echo "<span title='".__($guid, 'Submitted Work')."'>".__($guid, 'Sub').'</span>';
                    echo '</th>';
                
                }
            }
            echo '</tr></table>';
            echo '</th>';
        }
        echo '</tr>';

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
            echo '<td colspan='.($markbook->getColumnCountTotal() + 1).'>';
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
                echo '<td class="firstColumn">';
                echo "<div style='padding: 2px 0px'><b><a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=".$rowStudents['gibbonPersonID'].'&subpage=Markbook#'.$gibbonCourseClassID."'>".formatName('', $rowStudents['preferredName'], $rowStudents['surname'], 'Student', true).'</a><br/></div>';
                echo '</td>';

                if ($markbook->hasExternalAssessments() == true) {
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

                // Display personalized target
                if ($markbook->getPersonalizedTargetsCount() > 0) {
                    echo "<td style='text-align: center'>";
                        echo $markbook->getTargetForStudent( $rowStudents['gibbonPersonID'] );
                    echo '</td>';
                }

                //Calculate and output weighted totals
                if ($markbook->getSetting('enableColumnWeighting') == 'Y') {
                    echo "<td style='text-align: center'>";
                        echo $markbook->getWeightingForStudent( $rowStudents['gibbonPersonID'] );
                    echo '</td>';
                }

                for ($i = 0; $i < $markbook->getColumnCountThisPage(); ++$i) {

                	$column = $markbook->getColumn( $i );

                	echo "<td style='padding: 0 !important;'>";
                	echo '<table class="blank miniData" cellspacing=0><tr>';
                    //$row = $result->fetch();
                    try {
                        $dataEntry = array('gibbonMarkbookColumnID' => $column->gibbonMarkbookColumnID, 'gibbonPersonIDStudent' => $rowStudents['gibbonPersonID']);
                        $sqlEntry = 'SELECT * FROM gibbonMarkbookEntry WHERE gibbonMarkbookColumnID=:gibbonMarkbookColumnID AND gibbonPersonIDStudent=:gibbonPersonIDStudent';
                        $resultEntry = $connection2->prepare($sqlEntry);
                        $resultEntry->execute($dataEntry);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }
                    if ($resultEntry->rowCount() == 1) {
                        $rowEntry = $resultEntry->fetch();
                        $leftBorder = false;

                        if ($column->displayAttainment()) {
                            $leftBorder = true;
                            echo "<td style='border-left: 2px solid #666; text-align: center;width: 40px'>";

                            if ($column->hasAttainmentGrade()) {
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
                                echo "<div $styleAttainment title='".htmlPrep($rowEntry['attainmentDescriptor'])."'>" . $attainment;
                            }
                            if ($column->hasAttainmentRubric()) {
                                echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/'.$_SESSION[$guid]['module'].'/markbook_view_rubric.php&gibbonRubricID='.$column->getData('gibbonRubricIDAttainment')."&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=".$column->gibbonMarkbookColumnID.'&gibbonPersonID='.$rowStudents['gibbonPersonID']."&mark=FALSE&type=attainment&width=1100&height=550'><img style='margin-bottom: -3px; margin-left: 3px' title='".__($guid, 'View Rubric')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/rubric.png'/></a>";
                            }
                            if ($column->hasEffortGrade()) {
                                echo '</div>';
                            }
                            echo '</td>';
                        }

                        if ($column->displayEffort()) {
                            $leftBorderStyle = '';
                            if ($leftBorder == false) {
                                $leftBorder = true;
                                $leftBorderStyle = 'border-left: 2px solid #666;';
                            }
                            echo "<td style='$leftBorderStyle text-align: center;width: 40px'>";
                            if ($column->hasEffortGrade()) {
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
                                echo "<div $styleEffort title='".htmlPrep($rowEntry['effortDescriptor'])."'>" . $effort;
                            }
                            if ($column->hasEffortRubric()) {
                                echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/'.$_SESSION[$guid]['module'].'/markbook_view_rubric.php&gibbonRubricID='.$column->getData('gibbonRubricIDEffort')."&gibbonCourseClassID=$gibbonCourseClassID&gibbonMarkbookColumnID=".$column->gibbonMarkbookColumnID.'&gibbonPersonID='.$rowStudents['gibbonPersonID']."&mark=FALSE&type=effort&width=1100&height=550'><img style='margin-bottom: -3px; margin-left: 3px' title='".__($guid, 'View Rubric')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/rubric.png'/></a>";
                            }
                            if ($column->hasEffortGrade()) {
                                echo '</div>';
                            }
                            echo '</td>';
                        }
                        if ($column->displayComment()) {
                            $leftBorderStyle = '';
                            if ($leftBorder == false) {
                                $leftBorder = true;
                                $leftBorderStyle = 'border-left: 2px solid #666;';
                            }
                            echo "<td style='$leftBorderStyle text-align: center;width: 80px'>";
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
                        if ($column->displayUploadedResponse()) {
                            $leftBorderStyle = '';
                            if ($leftBorder == false) {
                                $leftBorder = true;
                                $leftBorderStyle = 'border-left: 2px solid #666;';
                            }
                            echo "<td style='$leftBorderStyle text-align: center;width: 30px'>";
                            if ($rowEntry['response'] != '') {
                                echo "<a title='".__($guid, 'Uploaded Response')."' href='".$_SESSION[$guid]['absoluteURL'].'/'.$rowEntry['response']."'>Up</a><br/>";
                            }
                        }
                        echo '</td>';
                    }

                    if ($column->displaySubmission()) {

                        $leftBorderStyle = '';
                        if ($leftBorder == false) {
                            $leftBorder = true;
                            $leftBorderStyle = 'border-left: 2px solid #666;';
                        }
                        echo "<td style='$leftBorderStyle text-align: center;'>";
                        try {
                            $dataWork = array('gibbonPlannerEntryID' => $column->getData('gibbonPlannerEntryID'), 'gibbonPersonID' => $rowStudents['gibbonPersonID']);
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
                            if (date('Y-m-d H:i:s') < $column->getData('homeworkDueDateTime') ) {
                                echo "<span title='".__($guid, 'Pending')."'>Pen</span>";
                            } else {
                                if ($rowStudents['dateStart'] > $column->getData('lessonDate') ) {
                                    echo "<span title='".__($guid, 'Student joined school after assessment was given.')."' style='color: #000; font-weight: normal; border: 2px none #ff0000; padding: 2px 4px'>".__($guid, 'NA').'</span>';
                                } else {
                                    if ($column->getData('homeworkSubmissionRequired') == 'Compulsory') {
                                        echo "<span title='".__($guid, 'Incomplete')."' style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px'>".__($guid, 'Inc').'</span>';
                                    } else {
                                        echo "<span title='".__($guid, 'Not submitted online')."'>".__($guid, 'NA').'</span>';
                                    }
                                }
                            }
                        }
                        echo '</td>';

                    }
                	echo '</tr></table>';
                }
                echo '</tr>';
            }
        }
        echo "</tbody>";
        echo '</table>';

        echo '</div>';
        echo '</div><br/>';

    }
        
        

?>