                        if (! $this->getSecurity()->isActionAccessible('/modules/Timetable/tt_view.php')) {
                            $this->displayMessage('Your request failed because you do not have access to this action.');
                        } else {
                            if ($this->getSecurity()->isActionAccessible('/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php')) {
                                $role = getRoleCategory($details->student->getField('gibbonRoleIDPrimary'), $connection2);
                                if ($role == 'Student' or $role == 'Staff') {
                                    echo "<div class='linkTop'>";
                                    echo "<a href='".$_SESSION['absoluteURL']."/index.php?q=/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php&gibbonPersonID=$details->personID&gibbonSchoolYearID=".$_SESSION['gibbonSchoolYearID']."&type=$role'>".trans::__('Edit')."<img style='margin: 0 0 -4px 5px' title='".trans::__('Edit')."' src='./themes/".$_SESSION['gibbonThemeName']."/img/config.png'/></a> ";
                                    echo '</div>';
                                }
                            }

                            include './modules/Timetable/moduleFunctions.php';
                            $ttDate = null;
                            if (isset($_POST['ttDate'])) {
                                $ttDate = dateConvertToTimestamp(dateConvert($guid, $_POST['ttDate']));
                            }
                            $tt = renderTT($guid, $connection2, $details->personID, '', false, $ttDate, '/modules/Students/student_view_details.php', "&gibbonPersonID=$details->personID&search=$details->search&allStudents=$details->allStudents&subpage=Timetable");
                            if ($tt != false) {
                                echo $tt;
                            } else {
                                $this->displayMessage('There are no records to display.');
                            }
                        }
