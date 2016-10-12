                        if (!($this->getSecurity()->isActionAccessible('/modules/Planner/planner_edit.php') || ! $this->getSecurity()->isActionAccessible('/modules/Planner/planner_view_full.php'))) {
                            $this->displayMessage('Your request failed because you do not have access to this action.');
                        } else {
                            echo '<h4>';
                            echo trans::__('Upcoming Deadlines');
                            echo '</h4>';

                            try {
                                $dataDeadlines = array('gibbonPersonID' => $details->personID, 'gibbonSchoolYearID' => $_SESSION['gibbonSchoolYearID']);
                                $sqlDeadlines = "
								(SELECT 'teacherRecorded' AS type, gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkDueDateTime, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND homework='Y' AND (role='Teacher' OR (role='Student' AND viewableStudents='Y')) AND homeworkDueDateTime>'".date('Y-m-d H:i:s')."' AND ((date<'".date('Y-m-d')."') OR (date='".date('Y-m-d')."' AND timeEnd<='".date('H:i:s')."')))
								UNION
								(SELECT 'studentRecorded' AS type, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, 'Y' AS viewableStudents, 'Y' AS viewableParents, 'Y' AS homework, gibbonPlannerEntryStudentHomework.homeworkDueDateTime, role FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonPlannerEntryStudentHomework ON (gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID AND gibbonPlannerEntryStudentHomework.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND (role='Teacher' OR (role='Student' AND viewableStudents='Y')) AND gibbonPlannerEntryStudentHomework.homeworkDueDateTime>'".date('Y-m-d H:i:s')."' AND ((date<'".date('Y-m-d')."') OR (date='".date('Y-m-d')."' AND timeEnd<='".date('H:i:s')."')))
								ORDER BY homeworkDueDateTime, type";
                                $resultDeadlines = $connection2->prepare($sqlDeadlines);
                                $resultDeadlines->execute($dataDeadlines);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }

                            if ($resultDeadlines->rowCount() < 1) {
                                echo "<div class='success'>";
                                echo trans::__('No upcoming deadlines!');
                                echo '</div>';
                            } else {
                                echo '<ol>';
                                while ($rowDeadlines = $resultDeadlines->fetch()) {
                                    $diff = (strtotime(substr($rowDeadlines['homeworkDueDateTime'], 0, 10)) - strtotime(date('Y-m-d'))) / 86400;
                                    $style = "style='padding-right: 3px;'";
                                    if ($diff < 2) {
                                        $style = "style='padding-right: 3px; border-right: 10px solid #cc0000'";
                                    } elseif ($diff < 4) {
                                        $style = "style='padding-right: 3px; border-right: 10px solid #D87718'";
                                    }
                                    echo "<li $style>";
                                    echo "<a href='".$_SESSION['absoluteURL']."/index.php?q=/modules/Planner/planner_view_full.php&search=$details->personID&gibbonPlannerEntryID=".$rowDeadlines['gibbonPlannerEntryID'].'&viewBy=date&date='.$rowDeadlines['date']."&width=1000&height=550'>".$rowDeadlines['course'].'.'.$rowDeadlines['class'].'</a><br/>';
                                    echo "<span style='font-style: italic'>".sprintf(trans::__('Due at %1$s on %2$s'), substr($rowDeadlines['homeworkDueDateTime'], 11, 5), dateConvertBack($guid, substr($rowDeadlines['homeworkDueDateTime'], 0, 10)));
                                    echo '</li>';
                                }
                                echo '</ol>';
                            }

                            $style = '';

                            echo '<h4>';
                            echo trans::__('Homework History');
                            echo '</h4>';

                            $gibbonCourseClassIDFilter = null;
                            $filter = null;
                            $filter2 = null;
                            if (isset($_GET['gibbonCourseClassIDFilter'])) {
                                $gibbonCourseClassIDFilter = $_GET['gibbonCourseClassIDFilter'];
                            }
                            $dataHistory = array();
                            if ($gibbonCourseClassIDFilter != '') {
                                $dataHistory['gibbonCourseClassIDFilter'] = $gibbonCourseClassIDFilter;
                                $dataHistory['gibbonCourseClassIDFilter2'] = $gibbonCourseClassIDFilter;
                                $filter = ' AND gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassIDFilter';
                                $filte2 = ' AND gibbonPlannerEntry.gibbonCourseClassID=:gibbonCourseClassIDFilte2';
                            }

                            try {
                                $dataHistory['gibbonPersonID'] = $details->personID;
                                $dataHistory['gibbonSchoolYearID'] = $_SESSION['gibbonSchoolYearID'];
                                $sqlHistory = "
								(SELECT 'teacherRecorded' AS type, gibbonPlannerEntryID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, viewableStudents, viewableParents, homework, role, homeworkDueDateTime, homeworkDetails, homeworkSubmission, homeworkSubmissionRequired FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND homework='Y' AND gibbonSchoolYearID=:gibbonSchoolYearID AND (date<'".date('Y-m-d')."' OR (date='".date('Y-m-d')."' AND timeEnd<='".date('H:i:s')."')) $filter)
								UNION
								(SELECT 'studentRecorded' AS type, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonHookID, gibbonPlannerEntry.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, date, timeStart, timeEnd, 'Y' AS viewableStudents, 'Y' AS viewableParents, 'Y' AS homework, role, gibbonPlannerEntryStudentHomework.homeworkDueDateTime AS homeworkDueDateTime, gibbonPlannerEntryStudentHomework.homeworkDetails AS homeworkDetails, 'N' AS homeworkSubmission, '' AS homeworkSubmissionRequired FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) JOIN gibbonPlannerEntryStudentHomework ON (gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID AND gibbonPlannerEntryStudentHomework.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND gibbonSchoolYearID=:gibbonSchoolYearID AND (date<'".date('Y-m-d')."' OR (date='".date('Y-m-d')."' AND timeEnd<='".date('H:i:s')."')) $filter)
								ORDER BY date DESC, timeStart DESC";
                                $resultHistory = $connection2->prepare($sqlHistory);
                                $resultHistory->execute($dataHistory);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }

                            if ($resultHistory->rowCount() < 1) {
                                $this->displayMessage('There are no records to display.');
                            } else {
                                echo "<div class='linkTop'>";
                                echo "<form method='get' action='".$_SESSION['absoluteURL']."/index.php'>";
                                echo"<table class='blank' cellspacing='0' style='float: right; width: 250px; margin: 0px 0px'>";
                                echo'<tr>';
                                echo"<td style='width: 190px'>";
                                echo"<select name='gibbonCourseClassIDFilter' id='gibbonCourseClassIDFilter' style='width:190px'>";
                                echo"<option value=''></option>";
                                try {
                                    $dataSelect = array('gibbonPersonID' => $details->personID, 'gibbonSchoolYearID' => $_SESSION['gibbonSchoolYearID'], 'date' => date('Y-m-d'));
                                    $sqlSelect = "SELECT DISTINCT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.gibbonCourseClassID FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND homework='Y' AND gibbonSchoolYearID=:gibbonSchoolYearID AND date<=:date ORDER BY course, class";
                                    $resultSelect = $connection2->prepare($sqlSelect);
                                    $resultSelect->execute($dataSelect);
                                } catch (PDOException $e) {
                                }
                                while ($rowSelect = $resultSelect->fetch()) {
                                    $selected = '';
                                    if ($rowSelect['gibbonCourseClassID'] == $gibbonCourseClassIDFilter) {
                                        $selected = 'selected';
                                    }
                                    echo"<option $selected value='".$rowSelect['gibbonCourseClassID']."'>".htmlPrep($rowSelect['course']).'.'.htmlPrep($rowSelect['class']).'</option>';
                                }
                                echo'</select>';
                                echo'</td>';
                                echo"<td class='right'>";
                                echo"<input type='submit' value='".trans::__('Go')."' style='margin-right: 0px'>";
                                echo"<input type='hidden' name='q' value='/modules/Students/student_view_details.php'>";
                                echo"<input type='hidden' name='subpage' value='Homework'>";
                                echo"<input type='hidden' name='gibbonPersonID' value='$details->personID'>";
                                echo'</td>';
                                echo'</tr>';
                                echo'</table>';
                                echo'</form>';
                                echo '</div>';
                                echo "<table cellspacing='0' style='width: 100%'>";
                                echo "<tr class='head'>";
                                echo '<th>';
                                echo trans::__('Class').'</br>';
                                echo "<span style='font-size: 85%; font-style: italic'>".trans::__('Date').'</span>';
                                echo '</th>';
                                echo '<th>';
                                echo trans::__('Lesson').'</br>';
                                echo "<span style='font-size: 85%; font-style: italic'>".trans::__('Unit').'</span>';
                                echo '</th>';
                                echo "<th style='min-width: 25%'>";
                                echo trans::__('Type').'<br/>';
                                echo "<span style='font-size: 85%; font-style: italic'>".trans::__('Details').'</span>';
                                echo '</th>';
                                echo '<th>';
                                echo trans::__('Deadline');
                                echo '</th>';
                                echo '<th>';
                                echo trans::__('Online Submission');
                                echo '</th>';
                                echo '<th>';
                                echo trans::__('Actions');
                                echo '</th>';
                                echo '</tr>';

                                $count = 0;
                                $rowNum = 'odd';
                                while ($rowHistory = $resultHistory->fetch()) {
                                    if (!($rowHistory['role'] == 'Student' and $rowHistory['viewableParents'] == 'N')) {
                                        if ($count % 2 == 0) {
                                            $rowNum = 'even';
                                        } else {
                                            $rowNum = 'odd';
                                        }
                                        ++$count;

                                            //Highlight class in progress
                                            if ((date('Y-m-d') == $rowHistory['date']) and (date('H:i:s') > $rowHistory['timeStart']) and (date('H:i:s') < $rowHistory['timeEnd'])) {
                                                $rowNum = 'current';
                                            }

                                            //COLOR ROW BY STATUS!
                                            echo "<tr class=$rowNum>";
                                        echo '<td>';
                                        echo '<b>'.$rowHistory['course'].'.'.$rowHistory['class'].'</b></br>';
                                        echo "<span style='font-size: 85%; font-style: italic'>".dateConvertBack($guid, $rowHistory['date']).'</span>';
                                        echo '</td>';
                                        echo '<td>';
                                        echo '<b>'.$rowHistory['name'].'</b><br/>';
                                        echo "<span style='font-size: 85%; font-style: italic'>";
                                        if ($rowHistory['gibbonUnitID'] != '') {
                                            try {
                                                $dataUnit = array('gibbonUnitID' => $rowHistory['gibbonUnitID']);
                                                $sqlUnit = 'SELECT * FROM gibbonUnit WHERE gibbonUnitID=:gibbonUnitID';
                                                $resultUnit = $connection2->prepare($sqlUnit);
                                                $resultUnit->execute($dataUnit);
                                            } catch (PDOException $e) {
                                                echo "<div class='error'>".$e->getMessage().'</div>';
                                            }
                                            if ($resultUnit->rowCount() == 1) {
                                                $rowUnit = $resultUnit->fetch();
                                                echo $rowUnit['name'];
                                            }
                                        }
                                        echo '</span>';
                                        echo '</td>';
                                        echo '<td>';
                                        if ($rowHistory['type'] == 'teacherRecorded') {
                                            echo 'Teacher Recorded';
                                        } else {
                                            echo 'Student Recorded';
                                        }
                                        echo  '<br/>';
                                        echo "<span style='font-size: 85%; font-style: italic'>";
                                        if ($rowHistory['homeworkDetails'] != '') {
                                            if (strlen(strip_tags($rowHistory['homeworkDetails'])) < 21) {
                                                echo strip_tags($rowHistory['homeworkDetails']);
                                            } else {
                                                echo "<span $style title='".htmlPrep(strip_tags($rowHistory['homeworkDetails']))."'>".substr(strip_tags($rowHistory['homeworkDetails']), 0, 20).'...</span>';
                                            }
                                        }
                                        echo '</span>';
                                        echo '</td>';
                                        echo '<td>';
                                        echo dateConvertBack($guid, substr($rowHistory['homeworkDueDateTime'], 0, 10));
                                        echo '</td>';
                                        echo '<td>';
                                        if ($rowHistory['homeworkSubmission'] == 'Y') {
                                            echo '<b>'.$rowHistory['homeworkSubmissionRequired'].'<br/></b>';
                                            if ($rowHistory['role'] == 'Student') {
                                                try {
                                                    $dataVersion = array('gibbonPlannerEntryID' => $rowHistory['gibbonPlannerEntryID'], 'gibbonPersonID' => $details->personID);
                                                    $sqlVersion = 'SELECT * FROM gibbonPlannerEntryHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID ORDER BY count DESC';
                                                    $resultVersion = $connection2->prepare($sqlVersion);
                                                    $resultVersion->execute($dataVersion);
                                                } catch (PDOException $e) {
                                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                                }
                                                if ($resultVersion->rowCount() < 1) {
                                                    //Before deadline
                                                                if (date('Y-m-d H:i:s') < $rowHistory['homeworkDueDateTime']) {
                                                                    echo "<span title='".trans::__('Pending')."'>".trans::__('Pending').'</span>';
                                                                }
                                                                //After
                                                                else {
                                                                    if (@$rowHistory['dateStart'] > @$rowSub['date']) {
                                                                        echo "<span title='".trans::__('Student joined school after assessment was given.')."' style='color: #000; font-weight: normal; border: 2px none #ff0000; padding: 2px 4px'>".trans::__('NA').'</span>';
                                                                    } else {
                                                                        if ($rowHistory['homeworkSubmissionRequired'] == 'Compulsory') {
                                                                            echo "<div style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px; margin: 2px 0px'>".trans::__('Incomplete').'</div>';
                                                                        } else {
                                                                            echo trans::__('Not submitted online');
                                                                        }
                                                                    }
                                                                }
                                                } else {
                                                    $rowVersion = $resultVersion->fetch();
                                                    if ($rowVersion['status'] == 'On Time' or $rowVersion['status'] == 'Exemption') {
                                                        echo $rowVersion['status'];
                                                    } else {
                                                        if ($rowHistory['homeworkSubmissionRequired'] == 'Compulsory') {
                                                            echo "<div style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px; margin: 2px 0px'>".$rowVersion['status'].'</div>';
                                                        } else {
                                                            echo $rowVersion['status'];
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        echo '</td>';
                                        echo '<td>';
                                        echo "<a href='".$_SESSION['absoluteURL']."/index.php?q=/modules/Planner/planner_view_full.php&search=$details->personID&gibbonPlannerEntryID=".$rowHistory['gibbonPlannerEntryID'].'&viewBy=class&gibbonCourseClassID='.$rowHistory['gibbonCourseClassID']."&width=1000&height=550'><img title='".trans::__('View Details')."' src='./themes/".$_SESSION['gibbonThemeName']."/img/plus.png'/></a> ";
                                        echo '</td>';
                                        echo '</tr>';
                                    }
                                }
                                echo '</table>';
                            }
                        }
