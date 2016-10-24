<?php
	// Lock the file so other scripts cannot call it
	if (MARKBOOK_VIEW_LOCK !== sha1( $highestAction . $_SESSION[$guid]['gibbonPersonID'] ) . date('zWy') ) return;

	//Get settings
	$enableEffort = getSettingByScope($connection2, 'Markbook', 'enableEffort');
	$enableRubrics = getSettingByScope($connection2, 'Markbook', 'enableRubrics');
	$attainmentAltName = getSettingByScope($connection2, 'Markbook', 'attainmentAlternativeName');
	$effortAltName = getSettingByScope($connection2, 'Markbook', 'effortAlternativeName');

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
                                echo __($guid, 'Assessment');
                            echo '</th>';
							if ($enableEffort == 'Y') {
	                            echo "<th style='width: 75px; text-align: center'>";
	                                echo (!empty($attainmentAltName))? $attainmentAltName : __($guid, 'Attainment');
	                            echo '</th>';
							}
                            echo "<th style='width: 75px; text-align: center'>";
                                echo (!empty($effortAltName))? $effortAltName : __($guid, 'Effort');
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
                                    if ( ($rowEntry['attainmentConcern'] == 'Y' || $rowEntry['attainmentConcern'] == 'P') and $showParentAttainmentWarning == 'Y') {
                                        $styleAttainment = getAlertStyle($alert, $rowEntry['attainmentConcern'] );
                                    }
                                    echo "<div $styleAttainment>".$rowEntry['attainmentValue'];
                                    if ($rowEntry['gibbonRubricIDAttainment'] != '' AND $enableRubrics =='Y') {
                                        echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/Markbook/markbook_view_rubric.php&gibbonRubricID='.$rowEntry['gibbonRubricIDAttainment'].'&gibbonCourseClassID='.$rowEntry['gibbonCourseClassID'].'&gibbonMarkbookColumnID='.$rowEntry['gibbonMarkbookColumnID']."&gibbonPersonID=$gibbonPersonID&mark=FALSE&type=attainment&width=1100&height=550'><img style='margin-bottom: -3px; margin-left: 3px' title='View Rubric' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/rubric.png'/></a>";
                                    }
                                    echo '</div>';
                                    if ($rowEntry['attainmentValue'] != '') {
                                        echo "<div class='detailItem' style='font-size: 75%; font-style: italic; margin-top: 2px'><b>".htmlPrep(__($guid, $rowEntry['attainmentDescriptor'])).'</b>'.__($guid, $attainmentExtra).'</div>';
                                    }
                                    echo '</td>';
                                }
								if ($enableEffort == 'Y') {
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
	                                        $styleEffort = getAlertStyle($alert, $rowEntry['effortConcern'] );
	                                    }
	                                    echo "<div $styleEffort>".$rowEntry['effortValue'];
	                                    if ($rowEntry['gibbonRubricIDEffort'] != '' AND $enableRubrics =='Y') {
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

?>
