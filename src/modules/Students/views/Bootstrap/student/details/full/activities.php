                        if (!($this->getSecurity()->isActionAccessible('/modules/Activities/report_activityChoices_byStudent'))) {
                            $this->displayMessage('Your request failed because you do not have access to this action.');
                        } else {
                            echo '<p>';
                            echo trans::__('This report shows the current and historical activities that a student has enroled in.');
                            echo '</p>';

                            $dateType = getSettingByScope($connection2, 'Activities', 'dateType');
                            if ($dateType == 'Term') {
                                $maxPerTerm = getSettingByScope($connection2, 'Activities', 'maxPerTerm');
                            }

                            try {
                                $dataYears = array('gibbonPersonID' => $details->personID);
                                $sqlYears = 'SELECT * FROM gibbonStudentEnrolment JOIN gibbonSchoolYear ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonPersonID=:gibbonPersonID ORDER BY sequenceNumber DESC';
                                $resultYears = $connection2->prepare($sqlYears);
                                $resultYears->execute($dataYears);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }

                            if ($resultYears->rowCount() < 1) {
                                $this->displayMessage('There are no records to display.');
                            } else {
                                $yearCount = 0;
                                while ($rowYears = $resultYears->fetch()) {
                                    $class = '';
                                    if ($yearCount == 0) {
                                        $class = "class='top'";
                                    }
                                    echo "<h3 $class>";
                                    echo $rowYears['name'];
                                    echo '</h3>';

                                    ++$yearCount;
                                    try {
                                        $data = array('gibbonPersonID' => $details->personID, 'gibbonSchoolYearID' => $rowYears['gibbonSchoolYearID']);
                                        $sql = "SELECT gibbonActivity.*, gibbonActivityStudent.status, NULL AS role FROM gibbonActivity JOIN gibbonActivityStudent ON (gibbonActivity.gibbonActivityID=gibbonActivityStudent.gibbonActivityID) WHERE gibbonActivityStudent.gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' ORDER BY name";
                                        $result = $connection2->prepare($sql);
                                        $result->execute($data);
                                    } catch (PDOException $e) {
                                        echo "<div class='error'>".$e->getMessage().'</div>';
                                    }

                                    if ($result->rowCount() < 1) {
                                        $this->displayMessage('There are no records to display.');
                                    } else {
                                        echo "<table cellspacing='0' style='width: 100%'>";
                                        echo "<tr class='head'>";
                                        echo '<th>';
                                        echo trans::__('Activity');
                                        echo '</th>';
                                        $options = getSettingByScope($connection2, 'Activities', 'activityTypes');
                                        if ($options != '') {
                                            echo '<th>';
                                            echo trans::__('Type');
                                            echo '</th>';
                                        }
                                        echo '<th>';
                                        if ($dateType != 'Date') {
                                            echo trans::__('Term');
                                        } else {
                                            echo trans::__('Dates');
                                        }
                                        echo '</th>';
                                        echo '<th>';
                                        echo trans::__('Status');
                                        echo '</th>';
                                        echo '<th>';
                                        echo trans::__('Actions');
                                        echo '</th>';
                                        echo '</tr>';

                                        $count = 0;
                                        $rowNum = 'odd';
                                        while ($row = $result->fetch()) {
                                            if ($count % 2 == 0) {
                                                $rowNum = 'even';
                                            } else {
                                                $rowNum = 'odd';
                                            }
                                            ++$count;

                                                //COLOR ROW BY STATUS!
                                                echo "<tr class=$rowNum>";
                                            echo '<td>';
                                            echo $details->student->getField('name');
                                            echo '</td>';
                                            if ($options != '') {
                                                echo '<td>';
                                                echo trim($details->student->getField('type'));
                                                echo '</td>';
                                            }
                                            echo '<td>';
                                            if ($dateType != 'Date') {
                                                $terms = getTerms($connection2, $_SESSION['gibbonSchoolYearID'], true);
                                                $termList = '';
                                                for ($i = 0; $i < count($terms); $i = $i + 2) {
                                                    if (is_numeric(strpos($details->student->getField('gibbonSchoolYearTermIDList'), $terms[$i]))) {
                                                        $termList .= $terms[($i + 1)].'<br/>';
                                                    }
                                                }
                                                echo $termList;
                                            } else {
                                                if (substr($details->student->getField('programStart'), 0, 4) == substr($details->student->getField('programEnd'), 0, 4)) {
                                                    if (substr($details->student->getField('programStart'), 5, 2) == substr($details->student->getField('programEnd'), 5, 2)) {
                                                        echo date('F', mktime(0, 0, 0, substr($details->student->getField('programStart'), 5, 2))).' '.substr($details->student->getField('programStart'), 0, 4);
                                                    } else {
                                                        echo date('F', mktime(0, 0, 0, substr($details->student->getField('programStart'), 5, 2))).' - '.date('F', mktime(0, 0, 0, substr($details->student->getField('programEnd'), 5, 2))).'<br/>'.substr($details->student->getField('programStart'), 0, 4);
                                                    }
                                                } else {
                                                    echo date('F', mktime(0, 0, 0, substr($details->student->getField('programStart'), 5, 2))).' '.substr($details->student->getField('programStart'), 0, 4).' -<br/>'.date('F', mktime(0, 0, 0, substr($details->student->getField('programEnd'), 5, 2))).' '.substr($details->student->getField('programEnd'), 0, 4);
                                                }
                                            }
                                            echo '</td>';
                                            echo '<td>';
                                            if ($details->student->getField('status') != '') {
                                                echo $details->student->getField('status');
                                            } else {
                                                echo '<em>'.trans::__('NA').'</em>';
                                            }
                                            echo '</td>';
                                            echo '<td>';
                                            echo "<a class='thickbox' href='".$_SESSION['absoluteURL'].'/fullscreen.php?q=/modules/Activities/activities_my_full.php&gibbonActivityID='.$details->student->getField('gibbonActivityID')."&width=1000&height=550'><img title='".trans::__('View Details')."' src='./themes/".$_SESSION['gibbonThemeName']."/img/plus.png'/></a> ";
                                            echo '</td>';
                                            echo '</tr>';
                                        }
                                        echo '</table>';
                                    }
                                }
                            }
                        }
