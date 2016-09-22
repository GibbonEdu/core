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

//Module includes for User Admin (for custom fields)
include './modules/User Admin/moduleFunctions.php';

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]['timezone']);

if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_view_details.php') == false) {
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
        $gibbonPersonID = $_GET['gibbonPersonID'];
        if ($gibbonPersonID == false) {
            echo "<div class='error'>";
            echo __($guid, 'You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            $search = null;
            if (isset($_GET['search'])) {
                $search = $_GET['search'];
            }
            $allStaff = '';
            if (isset($_GET['allStaff'])) {
                $allStaff = $_GET['allStaff'];
            }

            if ($highestAction == 'View Staff Profile_brief') {
                //Proceed!
                try {
                    $data = array('gibbonPersonID' => $gibbonPersonID);
                    $sql = "SELECT title, surname, preferredName, type, gibbonStaff.jobTitle, email, website, countryOfOrigin, qualifications, biography, image_240 FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonPerson.gibbonPersonID=:gibbonPersonID";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($result->rowCount() != 1) {
                    echo "<div class='error'>";
                    echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                    echo '</div>';
                    echo '</div>';
                } else {
                    $row = $result->fetch();

                    echo "<div class='trail'>";
                    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/staff_view.php'>".__($guid, 'View Staff Profiles')."</a> > </div><div class='trailEnd'>".formatName('', $row['preferredName'], $row['surname'], 'Student').'</div>';
                    echo '</div>';

                    if ($search != '') {
                        echo "<div class='linkTop'>";
                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Staff/staff_view.php&search='.$search."'>".__($guid, 'Back to Search Results').'</a>';
                        echo '</div>';
                    }

                    echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                    echo '<tr>';
                    echo "<td style='width: 33%; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Name').'</span><br/>';
                    echo '<i>'.formatName($row['title'], $row['preferredName'], $row['surname'], 'Parent').'</i>';
                    echo '</td>';
                    echo "<td style='width: 33%; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Staff Type').'</span><br/>';
                    echo '<i>'.$row['type'].'</i>';
                    echo '</td>';
                    echo "<td style='width: 33%; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Job Title').'</span><br/>';
                    echo '<i>'.$row['jobTitle'].'</i>';
                    echo '</td>';
                    echo '</tr>';
                    echo '<tr>';
                    echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Email').'</span><br/>';
                    if ($row['email'] != '') {
                        echo "<i><a href='mailto:".$row['email']."'>".$row['email'].'</a></i>';
                    }
                    echo '</td>';
                    echo "<td style='width: 67%; padding-top: 15px; vertical-align: top' colspan=2>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Website').'</span><br/>';
                    if ($row['website'] != '') {
                        echo "<i><a href='".$row['website']."'>".$row['website'].'</a></i>';
                    }
                    echo '</td>';
                    echo '</tr>';
                    echo '</table>';

                    echo '<h4>';
                    echo __($guid, 'Biography');
                    echo '</h4>';
                    echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                    echo '<tr>';
                    echo "<td style='width: 33%; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Country Of Origin').'</span><br/>';
                    echo '<i>'.$row['countryOfOrigin'].'</i>';
                    echo '</td>';
                    echo "<td style='width: 67%; vertical-align: top' colspan=2>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Qualifications').'</span><br/>';
                    echo '<i>'.$row['qualifications'].'</i>';
                    echo '</td>';
                    echo '</tr>';
                    echo '<tr>';
                    echo "<td style='width: 100%; vertical-align: top' colspan=3>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Biography').'</span><br/>';
                    echo '<i>'.$row['biography'].'</i>';
                    echo '</td>';
                    echo '</tr>';
                    echo '</table>';

                    // Show Roll Group - Link to Details
                    if (isActionAccessible($guid, $connection2, '/modules/Roll Groups/rollGroups.php') == true) {

                        try {
                            $dataDetail = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID);
                            $sqlDetail = "SELECT gibbonRollGroupID, name FROM gibbonRollGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND (gibbonPersonIDTutor=:gibbonPersonID OR gibbonPersonIDTutor2=:gibbonPersonID OR gibbonPersonIDTutor3=:gibbonPersonID )";
                            $resultDetail = $connection2->prepare($sqlDetail);
                            $resultDetail->execute($dataDetail);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        if ($resultDetail->rowCount() > 0) {

                            echo '<h4>';
                            echo __($guid, "Teacher's Roll Group");
                            echo '</h4>';

                            echo '<ul>';
                            while ($rowDetail = $resultDetail->fetch()) {
                                echo '<li>';
                                    echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Roll Groups/rollGroups_details.php&gibbonRollGroupID=".$rowDetail['gibbonRollGroupID']."'>";
                                    echo $rowDetail['name'].'</a>';

                                echo '</li>';
                            }
                            echo '</ul>';
                        }
                    }

                    // Show Classes - Link to Departments
                    if (isActionAccessible($guid, $connection2, '/modules/Departments/departments.php') == true) {

                        try {
                            $dataDetail = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID);
                            $sqlDetail = "SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourseClass.nameShort as className, gibbonCourse.name as courseName, gibbonCourse.nameShort as courseNameShort FROM gibbonCourseClassPerson, gibbonCourseClass, gibbonCourse WHERE gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID AND gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID =:gibbonPersonID AND gibbonCourseClassPerson.role='Teacher' AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID";
                            $resultDetail = $connection2->prepare($sqlDetail);
                            $resultDetail->execute($dataDetail);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        if ($resultDetail->rowCount() > 0) {

                            echo '<h4>';
                            echo __($guid, "Teacher's Classes");
                            echo '</h4>';

                            echo '<ul>';
                            while ($rowDetail = $resultDetail->fetch()) {
                                echo '<li>';
                                    echo '<span style="min-width:120px;display:inline-block;">'.$rowDetail['courseNameShort'].'.'.$rowDetail['className'].'</span>';
                                    echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Departments/department_course_class.php&gibbonCourseClassID=".$rowDetail['gibbonCourseClassID']."' title='".$rowDetail['courseNameShort'].'.'.$rowDetail['className']."'>";
                                    echo $rowDetail['courseName'].'</a>';

                                echo '</li>';
                            }
                            echo '</ul>';
                        }
                    }

                    //Show timetable
                    if (isActionAccessible($guid, $connection2, '/modules/Timetable/tt_view.php') == true) {

                        $highestTTAction = getHighestGroupedAction($guid, '/modules/Timetable/tt_view.php', $connection2);
                            if ($highestTTAction == 'View Timetable by Person' || $highestTTAction == 'View Timetable by Person_allYears') {

                            echo "<a name='timetable'></a>";
                            echo '<h4>';
                            echo __($guid, 'Timetable');
                            echo '</h4>';

                            include './modules/Timetable/moduleFunctions.php';
                            $ttDate = '';
                            if (isset($_POST['ttDate'])) {
                                $ttDate = dateConvertToTimestamp(dateConvert($guid, $_POST['ttDate']));
                            }
                            $gibbonTTID = null;
                            if (isset($_GET['gibbonTTID'])) {
                                $gibbonTTID = $_GET['gibbonTTID'];
                            }
                            $tt = renderTT($guid, $connection2, $gibbonPersonID, $gibbonTTID, false, $ttDate, '/modules/Staff/staff_view_details.php', "&gibbonPersonID=$gibbonPersonID&search=$search#timetable");
                            if ($tt != false) {
                                echo $tt;
                            } else {
                                echo "<div class='error'>";
                                echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                                echo '</div>';
                            }

                        }
                    }

                    //Set sidebar
                    $_SESSION[$guid]['sidebarExtra'] = getUserPhoto($guid, $row['image_240'], 240);
                }
            } else {
                try {
                    $data = array('gibbonPersonID' => $gibbonPersonID);
                    if ($allStaff != 'on') {
                        $sql = "SELECT gibbonPerson.*, gibbonStaff.initials, gibbonStaff.type, gibbonStaff.jobTitle, countryOfOrigin, qualifications, biography FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonPerson.gibbonPersonID=:gibbonPersonID";
                    } else {
                        $sql = 'SELECT gibbonPerson.*, gibbonStaff.initials, gibbonStaff.type, gibbonStaff.jobTitle, countryOfOrigin, qualifications, biography FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID';
                    }
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                }

                if ($result->rowCount() != 1) {
                    echo "<div class='error'>";
                    echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                    echo '</div>';
                } else {
                    $row = $result->fetch();

                    echo "<div class='trail'>";
                    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/staff_view.php&search=$search&allStaff=$allStaff'>".__($guid, 'View Staff Profiles')."</a> > </div><div class='trailEnd'>".formatName('', $row['preferredName'], $row['surname'], 'Student').'</div>';
                    echo '</div>';

                    $subpage = null;
                    if (isset($_GET['subpage'])) {
                        $subpage = $_GET['subpage'];
                    }
                    if ($subpage == '') {
                        $subpage = 'Overview';
                    }

                    if ($search != '') {
                        echo "<div class='linkTop'>";
                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Staff/staff_view.php&search='.$search."'>".__($guid, 'Back to Search Results').'</a>';
                        echo '</div>';
                    }

                    echo '<h2>';
                    if ($subpage != '') {
                        echo $subpage;
                    }
                    echo '</h2>';

                    if ($subpage == 'Overview') {
                        if (isActionAccessible($guid, $connection2, '/modules/User Admin/user_manage.php') == true) {
                            echo "<div class='linkTop'>";
                            echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/User Admin/user_manage_edit.php&gibbonPersonID=$gibbonPersonID'>".__($guid, 'Edit')."<img style='margin: 0 0 -4px 5px' title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                            echo '</div>';
                        }

                        //General Information
                        echo '<h4>';
                        echo __($guid, 'General Information');
                        echo '</h4>';
                        echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                        echo '<tr>';
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Name').'</span><br/>';
                        echo '<i>'.formatName($row['title'], $row['preferredName'], $row['surname'], 'Parent').'</i>';
                        echo '</td>';
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Staff Type').'</span><br/>';
                        echo '<i>'.$row['type'].'</i>';
                        echo '</td>';
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Job Title').'</span><br/>';
                        echo '<i>'.$row['jobTitle'].'</i>';
                        echo '</td>';
                        echo '</tr>';
                        echo '<tr>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Username').'</span><br/>';
                        echo '<i>'.$row['username'].'</i>';
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Website').'</span><br/>';
                        if ($row['website'] != '') {
                            echo "<i><a href='".$row['website']."'>".$row['website'].'</a></i>';
                        }
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Email').'</span><br/>';
                        if ($row['email'] != '') {
                            echo "<i><a href='mailto:".$row['email']."'>".$row['email'].'</a></i>';
                        }
                        echo '</td>';
                        echo '</tr>';
                        echo '</table>';

                        echo '<h4>';
                        echo __($guid, 'Biography');
                        echo '</h4>';
                        echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                        echo '<tr>';
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Country Of Origin').'</span><br/>';
                        echo '<i>'.$row['countryOfOrigin'].'</i>';
                        echo '</td>';
                        echo "<td style='width: 67%; vertical-align: top' colspan=2>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Qualifications').'</span><br/>';
                        echo '<i>'.$row['qualifications'].'</i>';
                        echo '</td>';
                        echo '</tr>';
                        echo '<tr>';
                        echo "<td style='width: 100%; vertical-align: top' colspan=3>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Biography').'</span><br/>';
                        echo '<i>'.$row['biography'].'</i>';
                        echo '</td>';
                        echo '</tr>';
                        echo '</table>';

                        // Show Roll Group - Link to Details
                        if (isActionAccessible($guid, $connection2, '/modules/Roll Groups/rollGroups.php') == true) {

                            try {
                                $dataDetail = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID);
                                $sqlDetail = "SELECT gibbonRollGroupID, name FROM gibbonRollGroup WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND (gibbonPersonIDTutor=:gibbonPersonID OR gibbonPersonIDTutor2=:gibbonPersonID OR gibbonPersonIDTutor3=:gibbonPersonID )";
                                $resultDetail = $connection2->prepare($sqlDetail);
                                $resultDetail->execute($dataDetail);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
                            if ($resultDetail->rowCount() > 0) {

                                echo '<h4>';
                                echo __($guid, "Teacher's Roll Group");
                                echo '</h4>';

                                echo '<ul>';
                                while ($rowDetail = $resultDetail->fetch()) {
                                    echo '<li>';
                                        echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Roll Groups/rollGroups_details.php&gibbonRollGroupID=".$rowDetail['gibbonRollGroupID']."'>";
                                        echo $rowDetail['name'].'</a>';

                                    echo '</li>';
                                }
                                echo '</ul>';
                            }
                        }

                        // Show Classes - Link to Departments
                        if (isActionAccessible($guid, $connection2, '/modules/Departments/departments.php') == true) {

                            try {
                                $dataDetail = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID);
                                $sqlDetail = "SELECT gibbonCourseClass.gibbonCourseClassID, gibbonCourseClass.nameShort as className, gibbonCourse.name as courseName, gibbonCourse.nameShort as courseNameShort FROM gibbonCourseClassPerson, gibbonCourseClass, gibbonCourse WHERE gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID AND gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID =:gibbonPersonID AND gibbonCourseClassPerson.role='Teacher' AND gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID";
                                $resultDetail = $connection2->prepare($sqlDetail);
                                $resultDetail->execute($dataDetail);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
                            if ($resultDetail->rowCount() > 0) {

                                echo '<h4>';
                                echo __($guid, "Teacher's Classes");
                                echo '</h4>';

                                echo '<ul>';
                                while ($rowDetail = $resultDetail->fetch()) {
                                    echo '<li>';
                                        echo '<span style="min-width:120px;display:inline-block;">'.$rowDetail['courseNameShort'].'.'.$rowDetail['className'].'</span>';
                                        echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Departments/department_course_class.php&gibbonCourseClassID=".$rowDetail['gibbonCourseClassID']."' title='".$rowDetail['courseNameShort'].'.'.$rowDetail['className']."'>";
                                        echo $rowDetail['courseName'].'</a>';

                                    echo '</li>';
                                }
                                echo '</ul>';
                            }
                        }

                        //Show timetable
                        echo "<a name='timetable'></a>";
                        echo '<h4>';
                        echo __($guid, 'Timetable');
                        echo '</h4>';
                        if (isActionAccessible($guid, $connection2, '/modules/Timetable/tt_view.php') == true) {
                            if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php') == true) {
                                echo "<div class='linkTop'>";
                                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php&gibbonPersonID=$gibbonPersonID&gibbonSchoolYearID=".$_SESSION[$guid]['gibbonSchoolYearID']."&type=Staff&allUsers='>".__($guid, 'Edit')."<img style='margin: 0 0 -4px 5px' title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                                echo '</div>';
                            }

                            include './modules/Timetable/moduleFunctions.php';
                            $ttDate = '';
                            if (isset($_POST['ttDate'])) {
                                $ttDate = dateConvertToTimestamp(dateConvert($guid, $_POST['ttDate']));
                            }
                            $gibbonTTID = null;
                            if (isset($_GET['gibbonTTID'])) {
                                $gibbonTTID = $_GET['gibbonTTID'];
                            }
                            $tt = renderTT($guid, $connection2, $gibbonPersonID, $gibbonTTID, false, $ttDate, '/modules/Staff/staff_view_details.php', "&gibbonPersonID=$gibbonPersonID&search=$search#timetable");
                            if ($tt != false) {
                                echo $tt;
                            } else {
                                echo "<div class='error'>";
                                echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                                echo '</div>';
                            }
                        }
                    } elseif ($subpage == 'Personal') {
                        if (isActionAccessible($guid, $connection2, '/modules/User Admin/user_manage.php') == true) {
                            echo "<div class='linkTop'>";
                            echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/User Admin/user_manage_edit.php&gibbonPersonID=$gibbonPersonID'>".__($guid, 'Edit')."<img style='margin: 0 0 -4px 5px' title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                            echo '</div>';
                        }

                        echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                        echo '<tr>';
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Name').'</span><br/>';
                        echo '<i>'.formatName($row['title'], $row['preferredName'], $row['surname'], 'Parent').'</i>';
                        echo '</td>';
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Staff Type').'</span><br/>';
                        echo '<i>'.$row['type'].'</i>';
                        echo '</td>';
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Job Title').'</span><br/>';
                        echo '<i>'.$row['jobTitle'].'</i>';
                        echo '</td>';
                        echo '</tr>';
                        echo '<tr>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Initials').'</span><br/>';
                        echo $row['initials'];
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Gender').'</span><br/>';
                        echo $row['gender'];
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";

                        echo '</td>';
                        echo '</tr>';
                        echo '</table>';

                        echo '<h4>';
                        echo 'Contacts';
                        echo '</h4>';

                        echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                        $numberCount = 0;
                        if ($row['phone1'] != '' or $row['phone2'] != '' or $row['phone3'] != '' or $row['phone4'] != '') {
                            echo '<tr>';
                            for ($i = 1; $i < 5; ++$i) {
                                if ($row['phone'.$i] != '') {
                                    ++$numberCount;
                                    echo "<td width: 33%; style='vertical-align: top'>";
                                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Phone')." $numberCount</span><br/>";
                                    if ($row['phone'.$i.'Type'] != '') {
                                        echo '<i>'.$row['phone'.$i.'Type'].':</i> ';
                                    }
                                    if ($row['phone'.$i.'CountryCode'] != '') {
                                        echo '+'.$row['phone'.$i.'CountryCode'].' ';
                                    }
                                    echo formatPhone($row['phone'.$i]).'<br/>';
                                    echo '</td>';
                                }
                            }
                            for ($i = ($numberCount + 1); $i < 5; ++$i) {
                                echo "<td width: 33%; style='vertical-align: top'></td>";
                            }
                            echo '</tr>';
                        }
                        echo '<tr>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Email').'</span><br/>';
                        if ($row['email'] != '') {
                            echo "<i><a href='mailto:".$row['email']."'>".$row['email'].'</a></i>';
                        }
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Alternate Email').'</span><br/>';
                        if ($row['emailAlternate'] != '') {
                            echo "<i><a href='mailto:".$row['emailAlternate']."'>".$row['emailAlternate'].'</a></i>';
                        }
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Website').'</span><br/>';
                        if ($row['website'] != '') {
                            echo "<i><a href='".$row['website']."'>".$row['website'].'</a></i>';
                        }
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";

                        echo '</td>';
                        echo '</tr>';
                        if ($row['address1'] != '') {
                            echo '<tr>';
                            echo "<td style='width: 33%; padding-top: 15px; vertical-align: top' colspan=3>";
                            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Address 1').'</span><br/>';
                            $address1 = addressFormat($row['address1'], $row['address1District'], $row['address1Country']);
                            if ($address1 != false) {
                                echo $address1;
                            }
                            echo '</td>';
                            echo '</tr>';
                        }
                        if ($row['address2'] != '') {
                            echo '<tr>';
                            echo "<td style='width: 33%; padding-top: 15px; vertical-align: top' colspan=3>";
                            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Address 2').'</span><br/>';
                            $address2 = addressFormat($row['address2'], $row['address2District'], $row['address2Country']);
                            if ($address2 != false) {
                                echo $address2;
                            }
                            echo '</td>';
                            echo '</tr>';
                        }
                        echo '</table>';

                        echo '<h4>';
                        echo __($guid, 'Miscellaneous');
                        echo '</h4>';

                        echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                        echo '<tr>';
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Transport').'</span><br/>';
                        echo $row['transport'];
                        echo '</td>';
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Vehicle Registration').'</span><br/>';
                        echo $row['vehicleRegistration'];
                        echo '</td>';
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Locker Number').'</span><br/>';
                        echo $row['lockerNumber'];
                        echo '</td>';
                        echo '</tr>';
                        echo '</table>';

                        //Custom Fields
                        $fields = unserialize($row['fields']);
                        $resultFields = getCustomFields($connection2, $guid, false, true);
                        if ($resultFields->rowCount() > 0) {
                            echo '<h4>';
                            echo __($guid, 'Custom Fields');
                            echo '</h4>';

                            echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                            $count = 0;
                            $columns = 3;

                            while ($rowFields = $resultFields->fetch()) {
                                if ($count % $columns == 0) {
                                    echo '<tr>';
                                }
                                echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                                echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, $rowFields['name']).'</span><br/>';
                                if (isset($fields[$rowFields['gibbonPersonFieldID']])) {
                                    if ($rowFields['type'] == 'date') {
                                        echo dateConvertBack($guid, $fields[$rowFields['gibbonPersonFieldID']]);
                                    } elseif ($rowFields['type'] == 'url') {
                                        echo "<a target='_blank' href='".$fields[$rowFields['gibbonPersonFieldID']]."'>".$fields[$rowFields['gibbonPersonFieldID']].'</a>';
                                    } else {
                                        echo $fields[$rowFields['gibbonPersonFieldID']];
                                    }
                                }
                                echo '</td>';

                                if ($count % $columns == ($columns - 1)) {
                                    echo '</tr>';
                                }
                                ++$count;
                            }

                            if ($count % $columns != 0) {
                                for ($i = 0;$i < $columns - ($count % $columns);++$i) {
                                    echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'></td>";
                                }
                                echo '</tr>';
                            }

                            echo '</table>';
                        }
                    } elseif ($subpage == 'Emergency Contacts') {
                        if (isActionAccessible($guid, $connection2, '/modules/User Admin/user_manage.php') == true) {
                            echo "<div class='linkTop'>";
                            echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/User Admin/user_manage_edit.php&gibbonPersonID=$gibbonPersonID'>".__($guid, 'Edit')."<img style='margin: 0 0 -4px 5px' title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                            echo '</div>';
                        }

                        echo '<p>';
                        echo __($guid, 'In an emergency, please try and contact the adult family members listed below first. If these cannot be reached, then try the emergency contacts below.');
                        echo '</p>';

                        echo '<h4>';
                        echo __($guid, 'Adult Family Members');
                        echo '</h4>';

                        try {
                            $dataFamily = array('gibbonPersonID' => $gibbonPersonID);
                            $sqlFamily = 'SELECT * FROM gibbonFamily JOIN gibbonFamilyChild ON (gibbonFamily.gibbonFamilyID=gibbonFamilyChild.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID';
                            $resultFamily = $connection2->prepare($sqlFamily);
                            $resultFamily->execute($dataFamily);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }

                        if ($resultFamily->rowCount() != 1) {
                            echo "<div class='error'>";
                            echo __($guid, 'There is no family information available for the current staff member.');
                            echo '</div>';
                        } else {
                            $rowFamily = $resultFamily->fetch();
                            $count = 1;
                            //Get adults
                            try {
                                $dataMember = array('gibbonFamilyID' => $rowFamily['gibbonFamilyID']);
                                $sqlMember = 'SELECT * FROM gibbonFamilyAdult JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID ORDER BY contactPriority, surname, preferredName';
                                $resultMember = $connection2->prepare($sqlMember);
                                $resultMember->execute($dataMember);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }

                            while ($rowMember = $resultMember->fetch()) {
                                echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                                echo '<tr>';
                                echo "<td style='width: 33%; vertical-align: top'>";
                                echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Name').'</span><br/>';
                                echo formatName($rowMember['title'], $rowMember['preferredName'], $rowMember['surname'], 'Parent');
                                echo '</td>';
                                echo "<td style='width: 33%; vertical-align: top'>";
                                echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Relationship').'</span><br/>';
                                if ($rowMember['role'] == 'Parent') {
                                    if ($rowMember['gender'] == 'M') {
                                        echo __($guid, 'Father');
                                    } elseif ($rowMember['gender'] == 'F') {
                                        echo __($guid, 'Mother');
                                    } else {
                                        echo $rowMember['role'];
                                    }
                                } else {
                                    echo $rowMember['role'];
                                }
                                echo '</td>';
                                echo "<td style='width: 34%; vertical-align: top'>";
                                echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Contact By Phone').'</span><br/>';
                                for ($i = 1; $i < 5; ++$i) {
                                    if ($rowMember['phone'.$i] != '') {
                                        if ($rowMember['phone'.$i.'Type'] != '') {
                                            echo '<i>'.$rowMember['phone'.$i.'Type'].':</i> ';
                                        }
                                        if ($rowMember['phone'.$i.'CountryCode'] != '') {
                                            echo '+'.$rowMember['phone'.$i.'CountryCode'].' ';
                                        }
                                        echo formatPhone($rowMember['phone'.$i]).'<br/>';
                                    }
                                }
                                echo '</td>';
                                echo '</tr>';
                                echo '</table>';
                                ++$count;
                            }
                        }

                        echo '<h4>';
                        echo __($guid, 'Emergency Contacts');
                        echo '</h4>';
                        echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                        echo '<tr>';
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Contact 1').'</span><br/>';
                        echo '<i>'.$row['emergency1Name'].'</i>';
                        if ($row['emergency1Relationship'] != '') {
                            echo ' ('.$row['emergency1Relationship'].')';
                        }
                        echo '</td>';
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Number 1').'</span><br/>';
                        echo $row['emergency1Number1'];
                        echo '</td>';
                        echo "<td style=width: 34%; 'vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Number 2').'</span><br/>';
                        if ($row['emergency1Number2'] != '') {
                            echo $row['emergency1Number2'];
                        }
                        echo '</td>';
                        echo '</tr>';
                        echo '<tr>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Contact 2').'</span><br/>';
                        echo '<i>'.$row['emergency2Name'].'</i>';
                        if ($row['emergency2Relationship'] != '') {
                            echo ' ('.$row['emergency2Relationship'].')';
                        }
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Number 1').'</span><br/>';
                        echo $row['emergency2Number1'];
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Number 2').'</span><br/>';
                        if ($row['emergency2Number2'] != '') {
                            echo $row['emergency2Number2'];
                        }
                        echo '</td>';
                        echo '</tr>';
                        echo '</table>';
                    } elseif ($subpage == 'Timetable') {
                        if (isActionAccessible($guid, $connection2, '/modules/Timetable/tt_view.php') == false) {
                            echo "<div class='error'>";
                            echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                            echo '</div>';
                        } else {
                            if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php') == true) {
                                echo "<div class='linkTop'>";
                                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php&gibbonPersonID=$gibbonPersonID&gibbonSchoolYearID=".$_SESSION[$guid]['gibbonSchoolYearID']."&type=Staff&allUsers='>".__($guid, 'Edit')."<img style='margin: 0 0 -4px 5px' title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                                echo '</div>';
                            }

                            include './modules/Timetable/moduleFunctions.php';
                            $ttDate = '';
                            if (isset($_POST['ttDate'])) {
                                $ttDate = dateConvertToTimestamp(dateConvert($guid, $_POST['ttDate']));
                            }
                            $gibbonTTID = null;
                            if (isset($_GET['gibbonTTID'])) {
                                $gibbonTTID = $_GET['gibbonTTID'];
                            }
                            $tt = renderTT($guid, $connection2, $gibbonPersonID, $gibbonTTID, false, $ttDate, '/modules/Staff/staff_view_details.php', "&gibbonPersonID=$gibbonPersonID&subpage=Timetable&search=$search");
                            if ($tt != false) {
                                echo $tt;
                            } else {
                                echo "<div class='error'>";
                                echo __($guid, 'The selected record does not exist, or you do not have access to it.');
                                echo '</div>';
                            }
                        }
                    }

                    //Set sidebar
                    $_SESSION[$guid]['sidebarExtra'] = '';

                    //Show pic
                    $_SESSION[$guid]['sidebarExtra'] .= getUserPhoto($guid, $row['image_240'], 240);

                    //PERSONAL DATA MENU ITEMS
                    $_SESSION[$guid]['sidebarExtra'] .= '<h4>Personal</h4>';
                    $_SESSION[$guid]['sidebarExtra'] .= "<ul class='moduleMenu'>";
                    $style = '';
                    if ($subpage == 'Overview') {
                        $style = "style='font-weight: bold'";
                    }
                    $_SESSION[$guid]['sidebarExtra'] .= "<li><a $style href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&allStaff=$allStaff&subpage=Overview'>".__($guid, 'Overview').'</a></li>';
                    $style = '';
                    if ($subpage == 'Personal') {
                        $style = "style='font-weight: bold'";
                    }
                    $_SESSION[$guid]['sidebarExtra'] .= "<li><a $style href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&allStaff=$allStaff&subpage=Personal'>".__($guid, 'Personal').'</a></li>';
                    $style = '';
                    if ($subpage == 'Emergency Contacts') {
                        $style = "style='font-weight: bold'";
                    }
                    $_SESSION[$guid]['sidebarExtra'] .= "<li><a $style href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&allStaff=$allStaff&subpage=Emergency Contacts'>".__($guid, 'Emergency Contacts').'</a></li>';
                    if (isActionAccessible($guid, $connection2, '/modules/Timetable/tt_view.php')) {
                        $style = '';
                        if ($subpage == 'Timetable') {
                            $style = "style='font-weight: bold'";
                        }
                        $_SESSION[$guid]['sidebarExtra'] .= "<li><a $style href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&allStaff=$allStaff&subpage=Timetable'>".__($guid, 'Timetable').'</a></li>';
                    }
                    $_SESSION[$guid]['sidebarExtra'] .= '</ul>';
                }
            }
        }
    }
}
