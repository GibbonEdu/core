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

use Gibbon\Forms\Form;

@session_start();

//Module includes for User Admin (for custom fields)
include './modules/User Admin/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Students/student_view_details.php') == false) {
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
        $search = null;
        if (isset($_GET['search'])) {
            $search = $_GET['search'];
        }
        $allStudents = '';
        if (isset($_GET['allStudents'])) {
            $allStudents = $_GET['allStudents'];
        }
        $sort = '';
        if (isset($_GET['sort'])) {
            $sort = $_GET['sort'];
        }

        if ($gibbonPersonID == false) {
            echo "<div class='error'>";
            echo __($guid, 'You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            $enableStudentNotes = getSettingByScope($connection2, 'Students', 'enableStudentNotes');
            $skipBrief = false;
            //Test if View Student Profile_brief and View Student Profile_myChildren are both available and parent has access to this student...if so, skip brief, and go to full.
            if (isActionAccessible($guid, $connection2, '/modules/Students/student_view_details.php', 'View Student Profile_brief') and isActionAccessible($guid, $connection2, '/modules/Students/student_view_details.php', 'View Student Profile_myChildren')) {
                try {
                    $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID1' => $_GET['gibbonPersonID'], 'gibbonPersonID2' => $_SESSION[$guid]['gibbonPersonID']);
                    $sql = "SELECT * FROM gibbonFamilyChild JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonFamilyChild.gibbonPersonID=:gibbonPersonID1 AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID2 AND childDataAccess='Y'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                }
                if ($result->rowCount() == 1) {
                    $skipBrief = true;
                }
            }

            if (isActionAccessible($guid, $connection2, '/modules/Students/student_view_details.php', 'View Student Profile_my') and $gibbonPersonID == $_SESSION[$guid]['gibbonPersonID']) {
                $skipBrief = true;
            }

            if ($highestAction == 'View Student Profile_brief' and $skipBrief == false) {
                //Proceed!
                try {
                    $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID);
                    $sql = "SELECT * FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonPerson.gibbonPersonID=:gibbonPersonID";
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
                    $studentImage=$row['image_240'] ;

                    echo "<div class='trail'>";
                    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/student_view.php'>".__($guid, 'View Student Profiles')."</a> > </div><div class='trailEnd'>".formatName('', $row['preferredName'], $row['surname'], 'Student').'</div>';
                    echo '</div>';

                    echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                    echo '<tr>';
                    echo "<td style='width: 33%; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Year Group').'</span><br/>';
                    try {
                        $dataDetail = array('gibbonYearGroupID' => $row['gibbonYearGroupID']);
                        $sqlDetail = 'SELECT * FROM gibbonYearGroup WHERE gibbonYearGroupID=:gibbonYearGroupID';
                        $resultDetail = $connection2->prepare($sqlDetail);
                        $resultDetail->execute($dataDetail);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }
                    if ($resultDetail->rowCount() == 1) {
                        $rowDetail = $resultDetail->fetch();
                        echo __($guid, $rowDetail['name']);
                    }
                    echo '</td>';
                    echo "<td style='width: 34%; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Roll Group').'</span><br/>';
                    try {
                        $dataDetail = array('gibbonRollGroupID' => $row['gibbonRollGroupID']);
                        $sqlDetail = 'SELECT * FROM gibbonRollGroup WHERE gibbonRollGroupID=:gibbonRollGroupID';
                        $resultDetail = $connection2->prepare($sqlDetail);
                        $resultDetail->execute($dataDetail);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }
                    if ($resultDetail->rowCount() == 1) {
                        $rowDetail = $resultDetail->fetch();
                        echo $rowDetail['name'];
                    }
                    echo '</td>';
                    echo "<td style='width: 34%; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'House').'</span><br/>';
                    try {
                        $dataDetail = array('gibbonHouseID' => $row['gibbonHouseID']);
                        $sqlDetail = 'SELECT * FROM gibbonHouse WHERE gibbonHouseID=:gibbonHouseID';
                        $resultDetail = $connection2->prepare($sqlDetail);
                        $resultDetail->execute($dataDetail);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }
                    if ($resultDetail->rowCount() == 1) {
                        $rowDetail = $resultDetail->fetch();
                        echo $rowDetail['name'];
                    }
                    echo '</td>';
                    echo '</tr>';
                    echo '<tr>';
                    echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Email').'</span><br/>';
                    if ($row['email'] != '') {
                        echo "<i><a href='mailto:".$row['email']."'>".$row['email'].'</a></i>';
                    }
                    echo '</td>';
                    echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Website').'</span><br/>';
                    if ($row['website'] != '') {
                        echo "<i><a href='".$row['website']."'>".$row['website'].'</a></i>';
                    }
                    echo '</td>';
                    echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Student ID').'</span><br/>';
                    if ($row['studentID'] != '') {
                        echo '<i>'.$row['studentID'].'</a></i>';
                    }
                    echo '</td>';
                    echo '</tr>';
                    echo '</table>';

                    $extendedBriefProfile = getSettingByScope($connection2, 'Students', 'extendedBriefProfile');
                    if ($extendedBriefProfile == 'Y') {
                        echo '<h3>';
                        echo __($guid, 'Family Details');
                        echo '</h3>';

                        try {
                            $dataFamily = array('gibbonPersonID' => $gibbonPersonID);
                            $sqlFamily = 'SELECT * FROM gibbonFamily JOIN gibbonFamilyChild ON (gibbonFamily.gibbonFamilyID=gibbonFamilyChild.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID';
                            $resultFamily = $connection2->prepare($sqlFamily);
                            $resultFamily->execute($dataFamily);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }

                        if ($resultFamily->rowCount() < 1) {
                            echo "<div class='error'>";
                            echo __($guid, 'There are no records to display.');
                            echo '</div>';
                        } else {
                            while ($rowFamily = $resultFamily->fetch()) {
                                $count = 1;

                                //Get adults
                                try {
                                    $dataMember = array('gibbonFamilyID' => $rowFamily['gibbonFamilyID']);
                                    $sqlMember = 'SELECT * FROM gibbonFamilyAdult JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID AND gibbonPerson.status=\'Full\' ORDER BY contactPriority, surname, preferredName';
                                    $resultMember = $connection2->prepare($sqlMember);
                                    $resultMember->execute($dataMember);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }

                                while ($rowMember = $resultMember->fetch()) {
                                    echo '<h4>';
                                    echo __($guid, 'Adult').' '.$count;
                                    echo '</h4>';
                                    echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                                    echo '<tr>';
                                    echo "<td style='width: 33%; vertical-align: top'>";
                                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Name').'</span><br/>';
                                    echo formatName($rowMember['title'], $rowMember['preferredName'], $rowMember['surname'], 'Parent');
                                    echo '</td>';
                                    echo "<td style='width: 33%; vertical-align: top'>";
                                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'First Language').'</span><br/>';
                                    echo $rowMember['languageFirst'];
                                    echo '</td>';
                                    echo "<td style='width: 34%; vertical-align: top' colspan=2>";
                                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Second Language').'</span><br/>';
                                    echo $rowMember['languageSecond'];
                                    echo '</td>';
                                    echo '</tr>';
                                    echo '<tr>';
                                    echo "<td style='width: 33%; padding-top: 15px; width: 33%; vertical-align: top'>";
                                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Contact By Phone').'</span><br/>';
                                    if ($rowMember['contactCall'] == 'N') {
                                        echo __($guid, 'Do not contact by phone.');
                                    } elseif ($rowMember['contactCall'] == 'Y' and ($rowMember['phone1'] != '' or $rowMember['phone2'] != '' or $rowMember['phone3'] != '' or $rowMember['phone4'] != '')) {
                                        for ($i = 1; $i < 5; ++$i) {
                                            if ($rowMember['phone'.$i] != '') {
                                                if ($rowMember['phone'.$i.'Type'] != '') {
                                                    echo $rowMember['phone'.$i.'Type'].':</i> ';
                                                }
                                                if ($rowMember['phone'.$i.'CountryCode'] != '') {
                                                    echo '+'.$rowMember['phone'.$i.'CountryCode'].' ';
                                                }
                                                echo formatPhone($rowMember['phone'.$i]).'<br/>';
                                            }
                                        }
                                    }
                                    echo '</td>';
                                    echo "<td style='width: 33%; padding-top: 15px; width: 34%; vertical-align: top' colspan=2>";
                                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Contact By Email').'</span><br/>';
                                    if ($rowMember['contactEmail'] == 'N') {
                                        echo __($guid, 'Do not contact by email.');
                                    } elseif ($rowMember['contactEmail'] == 'Y' and ($rowMember['email'] != '' or $rowMember['emailAlternate'] != '')) {
                                        if ($rowMember['email'] != '') {
                                            echo "<a href='mailto:".$rowMember['email']."'>".$rowMember['email'].'</a><br/>';
                                        }
                                        echo '<br/>';
                                    }
                                    echo '</td>';
                                    echo '</tr>';
                                    echo '</table>';
                                    ++$count;
                                }
                            }
                        }
                    }
                    //Set sidebar
                    $_SESSION[$guid]['sidebarExtra'] = getUserPhoto($guid, $row['image_240'], 240);
                }
            } else {
                try {
                    if ($highestAction == 'View Student Profile_myChildren') {
                        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID1' => $_GET['gibbonPersonID'], 'gibbonPersonID2' => $_SESSION[$guid]['gibbonPersonID'], 'today' => date('Y-m-d'));
                        $sql = "SELECT * FROM gibbonFamilyChild
                            JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID)
                            JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID)
                            JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID)
                            JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                            WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full'
                            AND (dateStart IS NULL OR dateStart<=:today) AND (dateEnd IS NULL  OR dateEnd>=:today)
                            AND gibbonFamilyChild.gibbonPersonID=:gibbonPersonID1
                            AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID2
                            AND childDataAccess='Y'";
                    }
                    else if ($highestAction == 'View Student Profile_my') {
                        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'today' => date('Y-m-d'));
                        $sql = "SELECT gibbonPerson.* FROM gibbonPerson
                            LEFT JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                            WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID
                            AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full'
                            AND (dateStart IS NULL OR dateStart<=:today) AND (dateEnd IS NULL OR dateEnd>=:today)";
                    } else {
                        if ($allStudents != 'on') {
                            $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID, 'today' => date('Y-m-d'));
                            $sql = "SELECT * FROM gibbonPerson
                                JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                                WHERE gibbonSchoolYearID=:gibbonSchoolYearID
                                AND gibbonPerson.gibbonPersonID=:gibbonPersonID AND status='Full'
                                AND (dateStart IS NULL OR dateStart<=:today) AND (dateEnd IS NULL  OR dateEnd>=:today) ";
                        } else {
                            $data = array('gibbonPersonID' => $gibbonPersonID);
                            $sql = "SELECT DISTINCT gibbonPerson.* FROM gibbonPerson
                                LEFT JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                                WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID";
                        }
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
                    $studentImage=$row['image_240'] ;

                    echo "<div class='trail'>";
                    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/student_view.php&search=$search&allStudents=$allStudents&sort=$sort'>".__($guid, 'View Student Profiles')."</a> > </div><div class='trailEnd'>".formatName('', $row['preferredName'], $row['surname'], 'Student').'</div>';
                    echo '</div>';

                    $subpage = null;
                    if (isset($_GET['subpage'])) {
                        $subpage = $_GET['subpage'];
                    }
                    $hook = null;
                    if (isset($_GET['hook'])) {
                        $hook = $_GET['hook'];
                    }
                    $module = null;
                    if (isset($_GET['module'])) {
                        $module = $_GET['module'];
                    }
                    $action = null;
                    if (isset($_GET['action'])) {
                        $action = $_GET['action'];
                    }

                    if ($subpage == '' and ($hook == '' or $module == '' or $action == '')) {
                        $subpage = 'Overview';
                    }

                    if ($search != '' or $allStudents != '') {
                        echo "<div class='linkTop'>";
                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view.php&search='.$search."&allStudents=$allStudents'>".__($guid, 'Back to Search Results').'</a>';
                        echo '</div>';
                    }

                    echo '<h2>';
                    if ($subpage != '') {
                        echo $subpage;
                    } else {
                        echo $hook;
                    }
                    echo '</h2>';

                    if ($subpage == 'Overview') {
                        if (isActionAccessible($guid, $connection2, '/modules/User Admin/user_manage.php') == true) {
                            echo "<div class='linkTop'>";
                            echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/User Admin/user_manage_edit.php&gibbonPersonID=$gibbonPersonID'>".__($guid, 'Edit')."<img style='margin: 0 0 -4px 5px' title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                            echo '</div>';
                        }

                        echo '<h4>';
                        echo __($guid, 'General Information');
                        echo '</h4>';

                        //Medical alert!
                        $alert = getHighestMedicalRisk($guid,  $gibbonPersonID, $connection2);
                        if ($alert != false) {
                            $highestLevel = $alert[1];
                            $highestColour = $alert[3];
                            $highestColourBG = $alert[4];
                            echo "<div class='error' style='background-color: #".$highestColourBG.'; border: 1px solid #'.$highestColour.'; color: #'.$highestColour."'>";
                            echo '<b>'.sprintf(__($guid, 'This student has one or more %1$s risk medical conditions.'), strToLower(__($guid, $highestLevel))).'</b>';
                            echo '</div>';
                        }

                        echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                        echo '<tr>';
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Preferred Name').'</span><br/>';
                        echo formatName('', $row['preferredName'], $row['surname'], 'Student');
                        echo '</td>';
                        echo "<td style='width: 34%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Official Name').'</span><br/>';
                        echo $row['officialName'];
                        echo '</td>';
                        echo "<td style='width: 34%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Name In Characters').'</span><br/>';
                        echo $row['nameInCharacters'];
                        echo '</td>';
                        echo '</tr>';
                        echo '<tr>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Year Group').'</span><br/>';
                        if (isset($row['gibbonYearGroupID'])) {
                            try {
                                $dataDetail = array('gibbonYearGroupID' => $row['gibbonYearGroupID']);
                                $sqlDetail = 'SELECT * FROM gibbonYearGroup WHERE gibbonYearGroupID=:gibbonYearGroupID';
                                $resultDetail = $connection2->prepare($sqlDetail);
                                $resultDetail->execute($dataDetail);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
                            if ($resultDetail->rowCount() == 1) {
                                $rowDetail = $resultDetail->fetch();
                                echo __($guid, $rowDetail['name']);
                                $dayTypeOptions = getSettingByScope($connection2, 'User Admin', 'dayTypeOptions');
                                if ($dayTypeOptions != '') {
                                    echo ' ('.$row['dayType'].')';
                                }
                                echo '</i>';
                            }
                        }
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Roll Group').'</span><br/>';
                        if (isset($row['gibbonRollGroupID'])) {
                            try {
                                $dataDetail = array('gibbonRollGroupID' => $row['gibbonRollGroupID']);
                                $sqlDetail = 'SELECT * FROM gibbonRollGroup WHERE gibbonRollGroupID=:gibbonRollGroupID';
                                $resultDetail = $connection2->prepare($sqlDetail);
                                $resultDetail->execute($dataDetail);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
                            if ($resultDetail->rowCount() == 1) {
                                $rowDetail = $resultDetail->fetch();
                                if (isActionAccessible($guid, $connection2, '/modules/Roll Groups/rollGroups_details.php')) {
                                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Roll Groups/rollGroups_details.php&gibbonRollGroupID='.$rowDetail['gibbonRollGroupID']."'>".$rowDetail['name'].'</a>';
                                } else {
                                    echo $rowDetail['name'];
                                }
                                $primaryTutor = $rowDetail['gibbonPersonIDTutor'];
                            }
                        }
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Tutors').'</span><br/>';
                        if (isset($rowDetail['gibbonPersonIDTutor'])) {
                            try {
                                $dataDetail = array('gibbonPersonIDTutor' => $rowDetail['gibbonPersonIDTutor'], 'gibbonPersonIDTutor2' => $rowDetail['gibbonPersonIDTutor2'], 'gibbonPersonIDTutor3' => $rowDetail['gibbonPersonIDTutor3']);
                                $sqlDetail = 'SELECT gibbonPersonID, title, surname, preferredName FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonIDTutor OR gibbonPersonID=:gibbonPersonIDTutor2 OR gibbonPersonID=:gibbonPersonIDTutor3';
                                $resultDetail = $connection2->prepare($sqlDetail);
                                $resultDetail->execute($dataDetail);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
                            while ($rowDetail = $resultDetail->fetch()) {
                                if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_view_details.php')) {
                                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Staff/staff_view_details.php&gibbonPersonID='.$rowDetail['gibbonPersonID']."'>".formatName('', $rowDetail['preferredName'], $rowDetail['surname'], 'Staff', false, true).'</a>';
                                } else {
                                    echo formatName($rowDetail['title'], $rowDetail['preferredName'], $rowDetail['surname'], 'Staff');
                                }
                                if ($rowDetail['gibbonPersonID'] == $primaryTutor and $resultDetail->rowCount() > 1) {
                                    echo ' ('.__($guid, 'Main Tutor').')';
                                }
                                echo '<br/>';
                            }
                        }
                        echo '</td>';
                        echo '</tr>';
                        echo '<tr>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Username').'</span><br/>';
                        echo $row['username'];
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Age').'</span><br/>';
                        if (is_null($row['dob']) == false and $row['dob'] != '0000-00-00') {
                            echo getAge($guid, dateConvertToTimestamp($row['dob']));
                        }
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'House').'</span><br/>';
                        try {
                            $dataDetail = array('gibbonHouseID' => $row['gibbonHouseID']);
                            $sqlDetail = 'SELECT * FROM gibbonHouse WHERE gibbonHouseID=:gibbonHouseID';
                            $resultDetail = $connection2->prepare($sqlDetail);
                            $resultDetail->execute($dataDetail);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        if ($resultDetail->rowCount() == 1) {
                            $rowDetail = $resultDetail->fetch();
                            echo $rowDetail['name'];
                        }
                        echo '</td>';
                        echo '</tr>';
                        echo '<tr>';
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
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'School History').'</span><br/>';
                        if ($row['dateStart'] != '') {
                            echo '<u>'.__($guid, 'Start Date').'</u>: '.dateConvertBack($guid, $row['dateStart']).'</br>';
                        }
                        try {
                            $dataSelect = array('gibbonPersonID' => $row['gibbonPersonID']);
                            $sqlSelect = "SELECT gibbonRollGroup.name AS rollGroup, gibbonSchoolYear.name AS schoolYear
                                FROM gibbonStudentEnrolment
                                JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
                                JOIN gibbonSchoolYear ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
                                WHERE gibbonPersonID=:gibbonPersonID
                                AND (gibbonSchoolYear.status = 'Current' OR gibbonSchoolYear.status='Past')
                                ORDER BY gibbonStudentEnrolment.gibbonSchoolYearID";
                            $resultSelect = $connection2->prepare($sqlSelect);
                            $resultSelect->execute($dataSelect);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        while ($rowSelect = $resultSelect->fetch()) {
                            echo '<u>'.$rowSelect['schoolYear'].'</u>: '.$rowSelect['rollGroup'].'<br/>';
                        }
                        if ($row['dateEnd'] != '') {
                            echo '<u>'.__($guid, 'End Date').'</u>: '.dateConvertBack($guid, $row['dateEnd']).'</br>';
                        }
                        echo '</td>';
                        echo '</tr>';
                        echo '<tr>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Locker Number').'</span><br/>';
                        if ($row['lockerNumber'] != '') {
                            echo $row['lockerNumber'];
                        }
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Student ID').'</span><br/>';
                        if ($row['studentID'] != '') {
                            echo $row['studentID'];
                        }
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";

                        echo '</td>';
                        echo '</tr>';
                        $privacySetting = getSettingByScope($connection2, 'User Admin', 'privacy');
                        if ($privacySetting == 'Y') {
                            echo '<tr>';
                            echo "<td style='width: 33%; padding-top: 15px; vertical-align: top' colspan=3>";
                            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Privacy').'</span><br/>';
                            if ($row['privacy'] != '') {
                                echo "<span style='color: #cc0000; background-color: #F6CECB'>";
                                echo __($guid, 'Privacy required:').' '.$row['privacy'];
                                echo '</span>';
                            } else {
                                echo "<span style='color: #390; background-color: #D4F6DC;'>";
                                echo __($guid, 'Privacy not required or not set.');
                                echo '</span>';
                            }

                            echo '</td>';
                            echo '</tr>';
                        }
                        $studentAgreementOptions = getSettingByScope($connection2, 'School Admin', 'studentAgreementOptions');
                        if ($studentAgreementOptions != '') {
                            echo '<tr>';
                            echo "<td style='width: 33%; padding-top: 15px; vertical-align: top' colspan=3>";
                            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Student Agreements').'</span><br/>';
                            echo __($guid, 'Agreements Signed:').' '.$row['studentAgreements'];
                            echo '</td>';
                            echo '</tr>';
                        }
                        echo '</table>';

                        //Get and display a list of student's teachers
                        echo '<h4>';
                        echo __($guid, "Student's Teachers");
                        echo '</h4>';
                        try {
                            $dataDetail = array('gibbonPersonID' => $gibbonPersonID);
                            $sqlDetail = "SELECT DISTINCT teacher.surname, teacher.preferredName, teacher.email, gibbonCourseClass.gibbonCourseClassID, gibbonCourseClass.nameShort as className, gibbonCourse.name as courseName, gibbonCourse.nameShort as courseNameShort FROM gibbonPerson AS teacher JOIN gibbonCourseClassPerson AS teacherClass ON (teacherClass.gibbonPersonID=teacher.gibbonPersonID)  JOIN gibbonCourseClassPerson AS studentClass ON (studentClass.gibbonCourseClassID=teacherClass.gibbonCourseClassID) JOIN gibbonPerson AS student ON (studentClass.gibbonPersonID=student.gibbonPersonID) JOIN gibbonCourseClass ON (studentClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE teacher.status='Full' AND teacherClass.role='Teacher' AND studentClass.role='Student' AND student.gibbonPersonID=:gibbonPersonID AND gibbonCourse.gibbonSchoolYearID=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE status='Current') ORDER BY teacher.preferredName, teacher.surname, teacher.email ;";
                            $resultDetail = $connection2->prepare($sqlDetail);
                            $resultDetail->execute($dataDetail);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        if ($resultDetail->rowCount() < 1) {
                            echo "<div class='warning'>";
                            echo __($guid, 'There are no records to display.');
                            echo '</div>';
                        } else {
                            echo '<ul>';
                            while ($rowDetail = $resultDetail->fetch()) {
                                echo '<li><span style="min-width:320px;display:inline-block;">';
                                    echo htmlPrep(formatName('', $rowDetail['preferredName'], $rowDetail['surname'], 'Student', false).' <'.$rowDetail['email'].'>').' </span>';
                                    echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Departments/department_course_class.php&gibbonCourseClassID=".$rowDetail['gibbonCourseClassID']."' title='".$rowDetail['courseNameShort'].'.'.$rowDetail['className']."'>";
                                    echo $rowDetail['courseName'].'</a>';
                                echo '</li>';
                            }
                            echo '</ul>';
                        }

                        //Get and display a list of student's educational assistants
                        try {
                            $dataDetail = array('gibbonPersonID1' => $gibbonPersonID, 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID2' => $gibbonPersonID);
                            $sqlDetail = "(SELECT DISTINCT surname, preferredName, email
                                FROM gibbonPerson
                                    JOIN gibbonINAssistant ON (gibbonINAssistant.gibbonPersonIDAssistant=gibbonPerson.gibbonPersonID)
                                WHERE status='Full'
                                    AND gibbonPersonIDStudent=:gibbonPersonID1)
                            UNION
                            (SELECT DISTINCT surname, preferredName, email
                                FROM gibbonPerson
                                    JOIN gibbonRollGroup ON (gibbonRollGroup.gibbonPersonIDEA=gibbonPerson.gibbonPersonID OR gibbonRollGroup.gibbonPersonIDEA2=gibbonPerson.gibbonPersonID OR gibbonRollGroup.gibbonPersonIDEA3=gibbonPerson.gibbonPersonID)
                                    JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
                                    JOIN gibbonSchoolYear ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
                                WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                                    AND gibbonStudentEnrolment.gibbonPersonID=:gibbonPersonID2
                            )
                            ORDER BY preferredName, surname, email";
                            $resultDetail = $connection2->prepare($sqlDetail);
                            $resultDetail->execute($dataDetail);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        if ($resultDetail->rowCount() > 0) {
                            echo '<h4>';
                            echo __($guid, "Student's Educational Assistants");
                            echo '</h4>';

                            echo '<ul>';
                            while ($rowDetail = $resultDetail->fetch()) {
                                echo '<li>'.htmlPrep(formatName('', $rowDetail['preferredName'], $rowDetail['surname'], 'Student', false));
                                if ($rowDetail['email'] != '') {
                                    echo htmlPrep(' <'.$rowDetail['email'].'>');
                                }
                                echo '</li>';
                            }
                            echo '</ul>';
                        }

                        //Show timetable
                        echo "<a name='timetable'></a>";
                        //Display timetable if available, otherwise just list classes
                        if (isActionAccessible($guid, $connection2, '/modules/Timetable/tt_view.php') == true) {
                            echo '<h4>';
                            echo __($guid, 'Timetable');
                            echo '</h4>';

                            if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php') == true) {
                                $role = getRoleCategory($row['gibbonRoleIDPrimary'], $connection2);
                                if ($role == 'Student' or $role == 'Staff') {
                                    echo "<div class='linkTop'>";
                                    echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php&gibbonPersonID=$gibbonPersonID&gibbonSchoolYearID=".$_SESSION[$guid]['gibbonSchoolYearID']."&type=$role'>".__($guid, 'Edit')."<img style='margin: 0 0 -4px 5px' title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                                    echo '</div>';
                                }
                            }

                            include './modules/Timetable/moduleFunctions.php';
                            $ttDate = null;
                            if (isset($_POST['ttDate'])) {
                                $ttDate = dateConvertToTimestamp(dateConvert($guid, $_POST['ttDate']));
                            }
                            $tt = renderTT($guid, $connection2, $gibbonPersonID, '', false, $ttDate, '/modules/Students/student_view_details.php', "&gibbonPersonID=$gibbonPersonID&search=$search&allStudents=$allStudents#timetable");
                            if ($tt != false) {
                                echo $tt;
                            } else {
                                echo "<div class='error'>";
                                echo __($guid, 'There are no records to display.');
                                echo '</div>';
                            }
                        }
                        else {
                            echo '<h4>';
                            echo __($guid, 'Class List');
                            echo '</h4>';
                            try {
                                $dataDetail = array('gibbonPersonID' => $gibbonPersonID);
                                $sqlDetail = "SELECT DISTINCT gibbonCourse.name AS courseFull, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class
                                    FROM gibbonCourseClassPerson
                                        JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                                        JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                                    WHERE gibbonCourseClassPerson.role='Student' AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND gibbonCourse.gibbonSchoolYearID=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE status='Current') ORDER BY course, class";
                                $resultDetail = $connection2->prepare($sqlDetail);
                                $resultDetail->execute($dataDetail);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
                            if ($resultDetail->rowCount() < 1 ) {
                                echo "<div class='error'>";
                                echo __($guid, 'There are no records to display.');
                                echo '</div>';
                            }
                            else {
                                echo '<ul>';
                                while ($rowDetail = $resultDetail->fetch()) {
                                    echo '<li>';
                                        echo htmlPrep($rowDetail['courseFull'].' ('.$rowDetail['course'].'.'.$rowDetail['class'].')');
                                    echo '</li>';
                                }
                                echo '</ul>';
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
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Surname').'</span><br/>';
                        echo $row['surname'];
                        echo '</td>';
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'First Name').'</span><br/>';
                        echo $row['firstName'];
                        echo '</td>';
                        echo "<td style='width: 34%; vertical-align: top'>";

                        echo '</td>';
                        echo '</tr>';
                        echo '<tr>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Preferred Name').'</span><br/>';
                        echo formatName('', $row['preferredName'], $row['surname'], 'Student');
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Official Name').'</span><br/>';
                        echo $row['officialName'];
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Name In Characters').'</span><br/>';
                        echo $row['nameInCharacters'];
                        echo '</td>';
                        echo '</tr>';
                        echo '<tr>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Gender').'</span><br/>';
                        echo $row['gender'];
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Date of Birth').'</span><br/>';
                        if (is_null($row['dob']) == false and $row['dob'] != '0000-00-00') {
                            echo dateConvertBack($guid, $row['dob']);
                        }
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Age').'</span><br/>';
                        if (is_null($row['dob']) == false and $row['dob'] != '0000-00-00') {
                            echo getAge($guid, dateConvertToTimestamp($row['dob']));
                        }
                        echo '</td>';
                        echo '</tr>';
                        echo '</table>';

                        echo '<h4>';
                        echo __($guid, 'Contacts');
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
                                        echo $row['phone'.$i.'Type'].':</i> ';
                                    }
                                    if ($row['phone'.$i.'CountryCode'] != '') {
                                        echo '+'.$row['phone'.$i.'CountryCode'].' ';
                                    }
                                    echo formatPhone($row['phone'.$i]).'<br/>';
                                    echo '</td>';
                                } else {
                                    echo "<td width: 33%; style='vertical-align: top'>";

                                    echo '</td>';
                                }
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
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top' colspan=2>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Website').'</span><br/>';
                        if ($row['website'] != '') {
                            echo "<i><a href='".$row['website']."'>".$row['website'].'</a></i>';
                        }
                        echo '</td>';
                        echo '</tr>';
                        if ($row['address1'] != '') {
                            echo '<tr>';
                            echo "<td style='width: 33%; padding-top: 15px; vertical-align: top' colspan=4>";
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
                        echo __($guid, 'School Information');
                        echo '</h4>';

                        echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                        echo '<tr>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Last School').'</span><br/>';
                        echo $row['lastSchool'];
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Start Date').'</span><br/>';
                        echo dateConvertBack($guid, $row['dateStart']);
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Class Of').'</span><br/>';
                        if ($row['gibbonSchoolYearIDClassOf'] == '') {
                            echo '<i>'.__($guid, 'NA').'</i>';
                        } else {
                            try {
                                $dataDetail = array('gibbonSchoolYearIDClassOf' => $row['gibbonSchoolYearIDClassOf']);
                                $sqlDetail = 'SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearIDClassOf';
                                $resultDetail = $connection2->prepare($sqlDetail);
                                $resultDetail->execute($dataDetail);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
                            if ($resultDetail->rowCount() == 1) {
                                $rowDetail = $resultDetail->fetch();
                                echo $rowDetail['name'];
                            }
                        }

                        echo '</td>';
                        echo '</tr>';
                        echo '<tr>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Next School').'</span><br/>';
                        echo $row['nextSchool'];
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'End Date').'</span><br/>';
                        echo dateConvertBack($guid, $row['dateEnd']);
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Departure Reason').'</span><br/>';
                        echo $row['departureReason'];
                        echo '</td>';
                        echo '</tr>';
                        $dayTypeOptions = getSettingByScope($connection2, 'User Admin', 'dayTypeOptions');
                        if ($dayTypeOptions != '') {
                            echo '<tr>';
                            echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Day Type').'</span><br/>';
                            echo $row['dayType'];
                            echo '</td>';
                            echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";

                            echo '</td>';
                            echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";

                            echo '</td>';
                            echo '</tr>';
                        }
                        echo '</table>';

                        echo '<h4>';
                        echo __($guid, 'Background');
                        echo '</h4>';

                        echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                        echo '<tr>';
                        echo "<td width: 33%; style='vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Country of Birth').'</span><br/>';
                        if ($row['countryOfBirth'] != '')
                            echo $row['countryOfBirth']."<br/>";
                        if ($row['birthCertificateScan'] != '')
                            echo "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/'.$row['birthCertificateScan']."'>View Birth Certificate</a>";
                        echo '</td>';
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Ethnicity').'</span><br/>';
                        echo $row['ethnicity'];
                        echo '</td>';
                        echo "<td style='width: 34%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Religion').'</span><br/>';
                        echo $row['religion'];
                        echo '</td>';
                        echo '</tr>';
                        echo '<tr>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Citizenship 1').'</span><br/>';
                        if ($row['citizenship1'] != '')
                            echo $row['citizenship1']."<br/>";
                        if ($row['citizenship1Passport'] != '')
                            echo $row['citizenship1Passport']."<br/>";
                        if ($row['citizenship1PassportScan'] != '')
                            echo "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/'.$row['citizenship1PassportScan']."'>View Passport</a>";
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Citizenship 2').'</span><br/>';
                        echo $row['citizenship2'];
                        if ($row['citizenship2Passport'] != '') {
                            echo '<br/>';
                            echo $row['citizenship2Passport'];
                        }
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        if ($_SESSION[$guid]['country'] == '') {
                            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'National ID Card').'</span><br/>';
                        } else {
                            echo "<span style='font-size: 115%; font-weight: bold'>".$_SESSION[$guid]['country'].' '.__($guid, 'ID Card').'</span><br/>';
                        }
                        if ($row['nationalIDCardNumber'] != '')
                            echo $row['nationalIDCardNumber']."<br/>";
                        if ($row['nationalIDCardScan'] != '')
                            echo "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/'.$row['nationalIDCardScan']."'>View ID Card</a>";
                        echo '</td>';
                        echo '</tr>';
                        echo '<tr>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'First Language').'</span><br/>';
                        echo $row['languageFirst'];
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Second Language').'</span><br/>';
                        echo $row['languageSecond'];
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Third Language').'</span><br/>';
                        echo $row['languageThird'];
                        echo '</td>';
                        echo '</tr>';
                        echo '<tr>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        if ($_SESSION[$guid]['country'] == '') {
                            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Residency/Visa Type').'</span><br/>';
                        } else {
                            echo "<span style='font-size: 115%; font-weight: bold'>".$_SESSION[$guid]['country'].' '.__($guid, 'Residency/Visa Type').'</span><br/>';
                        }
                        echo $row['residencyStatus'];
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        if ($_SESSION[$guid]['country'] == '') {
                            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Visa Expiry Date').'</span><br/>';
                        } else {
                            echo "<span style='font-size: 115%; font-weight: bold'>".$_SESSION[$guid]['country'].' '.__($guid, 'Visa Expiry Date').'</span><br/>';
                        }
                        if ($row['visaExpiryDate'] != '') {
                            echo dateConvertBack($guid, $row['visaExpiryDate']);
                        }
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";

                        echo '</td>';
                        echo '</tr>';
                        echo '</table>';

                        echo '<h4>';
                        echo 'School Data';
                        echo '</h4>';
                        echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                        echo '<tr>';
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Year Group').'</span><br/>';
                        if (isset($row['gibbonYearGroupID'])) {
                            try {
                                $dataDetail = array('gibbonYearGroupID' => $row['gibbonYearGroupID']);
                                $sqlDetail = 'SELECT * FROM gibbonYearGroup WHERE gibbonYearGroupID=:gibbonYearGroupID';
                                $resultDetail = $connection2->prepare($sqlDetail);
                                $resultDetail->execute($dataDetail);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
                            if ($resultDetail->rowCount() == 1) {
                                $rowDetail = $resultDetail->fetch();
                                echo __($guid, $rowDetail['name']);
                            }
                        }
                        echo '</td>';
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Roll Group').'</span><br/>';
                        if (isset($row['gibbonRollGroupID'])) {
                            $sqlDetail = "SELECT * FROM gibbonRollGroup WHERE gibbonRollGroupID='".$row['gibbonRollGroupID']."'";
                            try {
                                $dataDetail = array('gibbonRollGroupID' => $row['gibbonRollGroupID']);
                                $sqlDetail = 'SELECT * FROM gibbonRollGroup WHERE gibbonRollGroupID=:gibbonRollGroupID';
                                $resultDetail = $connection2->prepare($sqlDetail);
                                $resultDetail->execute($dataDetail);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
                            if ($resultDetail->rowCount() == 1) {
                                $rowDetail = $resultDetail->fetch();
                                if (isActionAccessible($guid, $connection2, '/modules/Roll Groups/rollGroups_details.php')) {
                                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Roll Groups/rollGroups_details.php&gibbonRollGroupID='.$rowDetail['gibbonRollGroupID']."'>".$rowDetail['name'].'</a>';
                                } else {
                                    echo $rowDetail['name'];
                                }
                                $primaryTutor = $rowDetail['gibbonPersonIDTutor'];
                            }
                        }
                        echo '</td>';
                        echo "<td style='width: 34%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Tutors').'</span><br/>';
                        if (isset($rowDetail['gibbonPersonIDTutor'])) {
                            try {
                                $dataDetail = array('gibbonPersonIDTutor' => $rowDetail['gibbonPersonIDTutor'], 'gibbonPersonIDTutor2' => $rowDetail['gibbonPersonIDTutor2'], 'gibbonPersonIDTutor3' => $rowDetail['gibbonPersonIDTutor3']);
                                $sqlDetail = 'SELECT gibbonPersonID, title, surname, preferredName FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonIDTutor OR gibbonPersonID=:gibbonPersonIDTutor2 OR gibbonPersonID=:gibbonPersonIDTutor3';
                                $resultDetail = $connection2->prepare($sqlDetail);
                                $resultDetail->execute($dataDetail);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
                            while ($rowDetail = $resultDetail->fetch()) {
                                if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_view_details.php')) {
                                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Staff/staff_view_details.php&gibbonPersonID='.$rowDetail['gibbonPersonID']."'>".formatName('', $rowDetail['preferredName'], $rowDetail['surname'], 'Staff', false, true).'</a>';
                                } else {
                                    echo formatName($rowDetail['title'], $rowDetail['preferredName'], $rowDetail['surname'], 'Staff');
                                }
                                if ($rowDetail['gibbonPersonID'] == $primaryTutor and $resultDetail->rowCount() > 1) {
                                    echo ' ('.__($guid, 'Main Tutor').')';
                                }
                                echo '<br/>';
                            }
                        }
                        echo '</td>';
                        echo '<tr>';
                        echo "<td style='padding-top: 15px ; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'House').'</span><br/>';
                        try {
                            $dataDetail = array('gibbonHouseID' => $row['gibbonHouseID']);
                            $sqlDetail = 'SELECT * FROM gibbonHouse WHERE gibbonHouseID=:gibbonHouseID';
                            $resultDetail = $connection2->prepare($sqlDetail);
                            $resultDetail->execute($dataDetail);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        if ($resultDetail->rowCount() == 1) {
                            $rowDetail = $resultDetail->fetch();
                            echo $rowDetail['name'];
                        }
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Student ID').'</span><br/>';
                        echo $row['studentID'];
                        echo '</td>';
                        echo "<td style='width: 34%; vertical-align: top'>";

                        echo '</td>';
                        echo '</tr>';
                        echo '</table>';

                        echo '<h4>';
                        echo __($guid, 'System Data');
                        echo '</h4>';

                        echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                        echo '<tr>';
                        echo "<td width: 33%; style='vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Username').'</span><br/>';
                        echo $row['username'];
                        echo '</td>';
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Can Login?').'</span><br/>';
                        echo ynExpander($guid, $row['canLogin']);
                        echo '</td>';
                        echo "<td style='width: 34%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Last IP Address').'</span><br/>';
                        echo $row['lastIPAddress'];
                        echo '</td>';
                        echo '</tr>';
                        echo '</table>';

                        echo '<h4>';
                        echo __($guid, 'Miscellaneous');
                        echo '</h4>';

                        echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                        echo '<tr>';
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Transport').'</span><br/>';
                        echo $row['transport'];
                        if ($row['transportNotes'] != '') {
                            echo '<br/>';
                            echo $row['transportNotes'];
                        }
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

                        $privacySetting = getSettingByScope($connection2, 'User Admin', 'privacy');
                        if ($privacySetting == 'Y') {
                            echo '<tr>';
                            echo "<td style='width: 33%; padding-top: 15px; vertical-align: top' colspan=3>";
                            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Image Privacy').'</span><br/>';
                            if ($row['privacy'] != '') {
                                echo "<span style='color: #cc0000; background-color: #F6CECB'>";
                                echo __($guid, 'Privacy required:').' '.$row['privacy'];
                                echo '</span>';
                            } else {
                                echo "<span style='color: #390; background-color: #D4F6DC;'>";
                                echo __($guid, 'Privacy not required or not set.');
                                echo '</span>';
                            }

                            echo '</td>';
                            echo '</tr>';
                        }
                        $studentAgreementOptions = getSettingByScope($connection2, 'School Admin', 'studentAgreementOptions');
                        if ($studentAgreementOptions != '') {
                            echo '<tr>';
                            echo "<td style='width: 33%; padding-top: 15px; vertical-align: top' colspan=3>";
                            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Student Agreements').'</span><br/>';
                            echo __($guid, 'Agreements Signed:').' '.$row['studentAgreements'];
                            echo '</td>';
                            echo '</tr>';
                        }
                        echo '</table>';

                        //Custom Fields
                        $fields = unserialize($row['fields']);
                        $resultFields = getCustomFields($connection2, $guid, true);
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
                    } elseif ($subpage == 'Family') {
                        try {
                            $dataFamily = array('gibbonPersonID' => $gibbonPersonID);
                            $sqlFamily = 'SELECT * FROM gibbonFamily JOIN gibbonFamilyChild ON (gibbonFamily.gibbonFamilyID=gibbonFamilyChild.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID';
                            $resultFamily = $connection2->prepare($sqlFamily);
                            $resultFamily->execute($dataFamily);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }

                        if ($resultFamily->rowCount() < 1) {
                            echo "<div class='error'>";
                            echo __($guid, 'There are no records to display.');
                            echo '</div>';
                        } else {
                            while ($rowFamily = $resultFamily->fetch()) {
                                $count = 1;

                                if (isActionAccessible($guid, $connection2, '/modules/User Admin/family_manage.php') == true) {
                                    echo "<div class='linkTop'>";
                                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/User Admin/family_manage_edit.php&gibbonFamilyID='.$rowFamily['gibbonFamilyID']."'>".__($guid, 'Edit')."<img style='margin: 0 0 -4px 5px' title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                                    echo '</div>';
                                }

                                //Print family information
                                echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                                echo '<tr>';
                                echo "<td style='width: 33%; vertical-align: top'>";
                                echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Family Name').'</span><br/>';
                                echo $rowFamily['name'];
                                echo '</td>';
                                echo "<td style='width: 33%; vertical-align: top'>";
                                echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Family Status').'</span><br/>';
                                echo $rowFamily['status'];
                                echo '</td>';
                                echo "<td style='width: 34%; vertical-align: top' colspan=2>";
                                echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Home Languages').'</span><br/>';
                                if ($rowFamily['languageHomePrimary'] != '') {
                                    echo $rowFamily['languageHomePrimary'].'<br/>';
                                }
                                if ($rowFamily['languageHomeSecondary'] != '') {
                                    echo $rowFamily['languageHomeSecondary'].'<br/>';
                                }
                                echo '</td>';
                                echo '</tr>';
                                echo '<tr>';
                                echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                                echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Address Name').'</span><br/>';
                                echo $rowFamily['nameAddress'];
                                echo '</td>';
                                echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                                echo '</td>';
                                echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                                echo '</td>';
                                echo '</tr>';

                                echo '<tr>';
                                echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                                echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Home Address').'</span><br/>';
                                echo $rowFamily['homeAddress'];
                                echo '</td>';
                                echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                                echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Home Address (District)').'</span><br/>';
                                echo $rowFamily['homeAddressDistrict'];
                                echo '</td>';
                                echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                                echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Home Address (Country)').'</span><br/>';
                                echo $rowFamily['homeAddressCountry'];
                                echo '</td>';
                                echo '</tr>';
                                echo '</table>';

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
                                    $class='';
                                    if ($rowMember['status'] != 'Full') {
                                        $class = "class='error'";
                                    }
                                    echo '<h4>';
                                    echo __($guid, 'Adult').' '.$count;
                                    echo '</h4>';
                                    echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                                    echo '<tr>';
                                    echo "<td $class style='width: 33%; vertical-align: top' rowspan=2>";
                                    echo getUserPhoto($guid, $rowMember['image_240'], 75);
                                    echo '</td>';
                                    echo "<td $class style='width: 33%; vertical-align: top'>";
                                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Name').'</span><br/>';
                                    echo formatName($rowMember['title'], $rowMember['preferredName'], $rowMember['surname'], 'Parent');
                                    if ($rowMember['status'] != 'Full') {
                                        echo "<span style='font-weight: normal; font-style: italic'> (".$rowMember['status'].')</span>';
                                    }
                                    echo "<div style='font-size: 85%; font-style: italic'>";
                                    try {
                                        $dataRelationship = array('gibbonPersonID1' => $rowMember['gibbonPersonID'], 'gibbonPersonID2' => $gibbonPersonID, 'gibbonFamilyID' => $rowFamily['gibbonFamilyID']);
                                        $sqlRelationship = 'SELECT * FROM gibbonFamilyRelationship WHERE gibbonPersonID1=:gibbonPersonID1 AND gibbonPersonID2=:gibbonPersonID2 AND gibbonFamilyID=:gibbonFamilyID';
                                        $resultRelationship = $connection2->prepare($sqlRelationship);
                                        $resultRelationship->execute($dataRelationship);
                                    } catch (PDOException $e) {
                                        echo "<div class='error'>".$e->getMessage().'</div>';
                                    }
                                    if ($resultRelationship->rowCount() == 1) {
                                        $rowRelationship = $resultRelationship->fetch();
                                        echo $rowRelationship['relationship'];
                                    } else {
                                        echo '<i>'.__($guid, 'Relationship Unknown').'</i>';
                                    }
                                    echo '</div>';
                                    echo '</td>';
                                    echo "<td $class style='width: 34%; vertical-align: top' colspan=2>";
                                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Contact Priority').'</span><br/>';
                                    echo $rowMember['contactPriority'];
                                    echo '</td>';
                                    echo '</tr>';
                                    echo '<tr>';
                                    echo "<td $class style='width: 33%; padding-top: 15px; vertical-align: top'>";
                                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'First Language').'</span><br/>';
                                    echo $rowMember['languageFirst'];
                                    echo '</td>';
                                    echo "<td $class style='width: 33%; padding-top: 15px; vertical-align: top'>";
                                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Second Language').'</span><br/>';
                                    echo $rowMember['languageSecond'];
                                    echo '</td>';
                                    echo '</tr>';
                                    echo '<tr>';
                                    echo "<td $class style='width: 33%; padding-top: 15px; width: 33%; vertical-align: top'>";
                                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Contact By Phone').'</span><br/>';
                                    if ($rowMember['contactCall'] == 'N') {
                                        echo __($guid, 'Do not contact by phone.');
                                    } elseif ($rowMember['contactCall'] == 'Y' and ($rowMember['phone1'] != '' or $rowMember['phone2'] != '' or $rowMember['phone3'] != '' or $rowMember['phone4'] != '')) {
                                        for ($i = 1; $i < 5; ++$i) {
                                            if ($rowMember['phone'.$i] != '') {
                                                if ($rowMember['phone'.$i.'Type'] != '') {
                                                    echo $rowMember['phone'.$i.'Type'].':</i> ';
                                                }
                                                if ($rowMember['phone'.$i.'CountryCode'] != '') {
                                                    echo '+'.$rowMember['phone'.$i.'CountryCode'].' ';
                                                }
                                                echo formatPhone($rowMember['phone'.$i]).'<br/>';
                                            }
                                        }
                                    }
                                    echo '</td>';
                                    echo "<td $class style='width: 33%; padding-top: 15px; width: 33%; vertical-align: top'>";
                                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Contact By SMS').'</span><br/>';
                                    if ($rowMember['contactSMS'] == 'N') {
                                        echo __($guid, 'Do not contact by SMS.');
                                    } elseif ($rowMember['contactSMS'] == 'Y' and ($rowMember['phone1'] != '' or $rowMember['phone2'] != '' or $rowMember['phone3'] != '' or $rowMember['phone4'] != '')) {
                                        for ($i = 1; $i < 5; ++$i) {
                                            if ($rowMember['phone'.$i] != '' and $rowMember['phone'.$i.'Type'] == 'Mobile') {
                                                if ($rowMember['phone'.$i.'Type'] != '') {
                                                    echo $rowMember['phone'.$i.'Type'].':</i> ';
                                                }
                                                if ($rowMember['phone'.$i.'CountryCode'] != '') {
                                                    echo '+'.$rowMember['phone'.$i.'CountryCode'].' ';
                                                }
                                                echo formatPhone($rowMember['phone'.$i]).'<br/>';
                                            }
                                        }
                                    }
                                    echo '</td>';
                                    echo "<td $class style='width: 33%; padding-top: 15px; width: 34%; vertical-align: top' colspan=2>";
                                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Contact By Email').'</span><br/>';
                                    if ($rowMember['contactEmail'] == 'N') {
                                        echo __($guid, 'Do not contact by email.');
                                    } elseif ($rowMember['contactEmail'] == 'Y' and ($rowMember['email'] != '' or $rowMember['emailAlternate'] != '')) {
                                        if ($rowMember['email'] != '') {
                                            echo __($guid, 'Email').": <a href='mailto:".$rowMember['email']."'>".$rowMember['email'].'</a><br/>';
                                        }
                                        if ($rowMember['emailAlternate'] != '') {
                                            echo __($guid, 'Email')." 2: <a href='mailto:".$rowMember['emailAlternate']."'>".$rowMember['emailAlternate'].'</a><br/>';
                                        }
                                        echo '<br/>';
                                    }
                                    echo '</td>';
                                    echo '</tr>';
                                    echo '<tr>';
                                    echo "<td $class style='width: 33%; padding-top: 15px; vertical-align: top'>";
                                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Profession').'</span><br/>';
                                    echo $rowMember['profession'];
                                    echo '</td>';
                                    echo "<td $class style='width: 33%; padding-top: 15px; vertical-align: top'>";
                                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Employer').'</span><br/>';
                                    echo $rowMember['employer'];
                                    echo '</td>';
                                    echo "<td $class style='width: 33%; padding-top: 15px; vertical-align: top'>";
                                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Job Title').'</span><br/>';
                                    echo $rowMember['jobTitle'];
                                    echo '</td>';
                                    echo '</tr>';

                                    echo '<tr>';
                                    echo "<td $class style='width: 33%; padding-top: 15px; vertical-align: top'>";
                                    echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Vehicle Registration').'</span><br/>';
                                    echo $rowMember['vehicleRegistration'];
                                    echo '</td>';
                                    echo "<td $class style='width: 33%; padding-top: 15px; vertical-align: top'>";

                                    echo '</td>';
                                    echo "<td $class style='width: 33%; padding-top: 15px; vertical-align: top'>";

                                    echo '</td>';
                                    echo '</tr>';

                                    if ($rowMember['comment'] != '') {
                                        echo '<tr>';
                                        echo "<td $class style='width: 33%; vertical-align: top' colspan=3>";
                                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Comment').'</span><br/>';
                                        echo $rowMember['comment'];
                                        echo '</td>';
                                        echo '</tr>';
                                    }
                                    echo '</table>';
                                    ++$count;
                                }

                                //Get siblings
                                try {
                                    $dataMember = array('gibbonFamilyID' => $rowFamily['gibbonFamilyID'], 'gibbonPersonID' => $gibbonPersonID, 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                                    $sqlMember = 'SELECT gibbonPerson.gibbonPersonID, image_240, preferredName, surname, status, gibbonStudentEnrolmentID FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID) WHERE gibbonFamilyID=:gibbonFamilyID AND NOT gibbonPerson.gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName';
                                    $resultMember = $connection2->prepare($sqlMember);
                                    $resultMember->execute($dataMember);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }

                                if ($resultMember->rowCount() > 0) {
                                    echo '<h4>';
                                    echo __($guid, 'Siblings');
                                    echo '</h4>';

                                    echo "<table class='smallIntBorder' cellspacing='0' style='width:100%'>";
                                    $count = 0;
                                    $columns = 3;

                                    while ($rowMember = $resultMember->fetch()) {
                                        if ($count % $columns == 0) {
                                            echo '<tr>';
                                        }
                                        echo "<td style='width:30%; text-align: left; vertical-align: top'>";
                                        //User photo
                                        echo getUserPhoto($guid, $rowMember['image_240'], 75);
                                        echo "<div style='padding-top: 5px'><b>";
                                        $allStudents = '';
                                        if ($rowMember['gibbonStudentEnrolmentID'] == null)
                                            $allStudents = '&allStudents=on';
                                        if ($rowMember['status'] == 'Full') {
                                            echo "<a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=".$rowMember['gibbonPersonID'].$allStudents."'>".formatName('', $rowMember['preferredName'], $rowMember['surname'], 'Student').'</a><br/>';
                                        } else {
                                            echo formatName('', $rowMember['preferredName'], $rowMember['surname'], 'Student').'<br/>';
                                        }
                                        echo "<span style='font-weight: normal; font-style: italic'>".__($guid, 'Status').': '.$rowMember['status'].'</span>';
                                        echo '</div>';
                                        echo '</td>';

                                        if ($count % $columns == ($columns - 1)) {
                                            echo '</tr>';
                                        }
                                        ++$count;
                                    }

                                    for ($i = 0;$i < $columns - ($count % $columns);++$i) {
                                        echo '<td></td>';
                                    }

                                    if ($count % $columns != 0) {
                                        echo '</tr>';
                                    }

                                    echo '</table>';
                                }
                            }
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
                            echo __($guid, 'There are no records to display.');
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
                                try {
                                    $dataRelationship = array('gibbonPersonID1' => $rowMember['gibbonPersonID'], 'gibbonPersonID2' => $gibbonPersonID, 'gibbonFamilyID' => $rowFamily['gibbonFamilyID']);
                                    $sqlRelationship = 'SELECT * FROM gibbonFamilyRelationship WHERE gibbonPersonID1=:gibbonPersonID1 AND gibbonPersonID2=:gibbonPersonID2 AND gibbonFamilyID=:gibbonFamilyID';
                                    $resultRelationship = $connection2->prepare($sqlRelationship);
                                    $resultRelationship->execute($dataRelationship);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }
                                if ($resultRelationship->rowCount() == 1) {
                                    $rowRelationship = $resultRelationship->fetch();
                                    echo $rowRelationship['relationship'];
                                } else {
                                    echo '<i>'.__($guid, 'Unknown').'</i>';
                                }

                                echo '</td>';
                                echo "<td style='width: 34%; vertical-align: top'>";
                                echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Contact By Phone').'</span><br/>';
                                for ($i = 1; $i < 5; ++$i) {
                                    if ($rowMember['phone'.$i] != '') {
                                        if ($rowMember['phone'.$i.'Type'] != '') {
                                            echo $rowMember['phone'.$i.'Type'].':</i> ';
                                        }
                                        if ($rowMember['phone'.$i.'CountryCode'] != '') {
                                            echo '+'.$rowMember['phone'.$i.'CountryCode'].' ';
                                        }
                                        echo __($guid, $rowMember['phone'.$i]).'<br/>';
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
                        echo $row['emergency1Name'];
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
                        if ($row['website'] != '') {
                            echo $row['emergency1Number2'];
                        }
                        echo '</td>';
                        echo '</tr>';
                        echo '<tr>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Contact 2').'</span><br/>';
                        echo $row['emergency2Name'];
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
                        if ($row['website'] != '') {
                            echo $row['emergency2Number2'];
                        }
                        echo '</td>';
                        echo '</tr>';
                        echo '</table>';
                    } elseif ($subpage == 'Medical') {
                        try {
                            $dataMedical = array('gibbonPersonID' => $gibbonPersonID);
                            $sqlMedical = 'SELECT * FROM gibbonPersonMedical JOIN gibbonPerson ON (gibbonPersonMedical.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID';
                            $resultMedical = $connection2->prepare($sqlMedical);
                            $resultMedical->execute($dataMedical);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }

                        if ($resultMedical->rowCount() != 1) {
                            if (isActionAccessible($guid, $connection2, '/modules/Students/medicalForm_manage_add.php') == true) {
                                echo "<div class='linkTop'>";
                                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Students/medicalForm_manage_add.php&gibbonPersonID=$gibbonPersonID&search='>Add Medical Form<img style='margin: 0 0 -4px 3px' title='Add Medical Form' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a> ";
                                echo '</div>';
                            }

                            echo "<div class='error'>";
                            echo __($guid, 'There are no records to display.');
                            echo '</div>';
                        } else {
                            $rowMedical = $resultMedical->fetch();

                            if (isActionAccessible($guid, $connection2, '/modules/User Admin/medicalForm_manage.php') == true) {
                                echo "<div class='linkTop'>";
                                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/User Admin/medicalForm_manage_edit.php&gibbonPersonMedicalID='.$rowMedical['gibbonPersonMedicalID']."'>".__($guid, 'Edit')."<img style='margin: 0 0 -4px 5px' title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                                echo '</div>';
                            }

                            //Medical alert!
                            $alert = getHighestMedicalRisk($guid,  $gibbonPersonID, $connection2);
                            if ($alert != false) {
                                $highestLevel = $alert[1];
                                $highestColour = $alert[3];
                                $highestColourBG = $alert[4];
                                echo "<div class='error' style='background-color: #".$highestColourBG.'; border: 1px solid #'.$highestColour.'; color: #'.$highestColour."'>";
                                echo '<b>'.sprintf(__($guid, 'This student has one or more %1$s risk medical conditions'), strToLower($highestLevel)).'</b>.';
                                echo '</div>';
                            }

                            //Get medical conditions
                            try {
                                $dataCondition = array('gibbonPersonMedicalID' => $rowMedical['gibbonPersonMedicalID']);
                                $sqlCondition = 'SELECT * FROM gibbonPersonMedicalCondition WHERE gibbonPersonMedicalID=:gibbonPersonMedicalID ORDER BY name';
                                $resultCondition = $connection2->prepare($sqlCondition);
                                $resultCondition->execute($dataCondition);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }

                            echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                            echo '<tr>';
                            echo "<td style='width: 33%; vertical-align: top'>";
                            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Long Term Medication').'</span><br/>';
                            if ($rowMedical['longTermMedication'] == '') {
                                echo '<i>'.__($guid, 'Unknown').'</i>';
                            } else {
                                echo $rowMedical['longTermMedication'];
                            }
                            echo '</td>';
                            echo "<td style='width: 67%; vertical-align: top' colspan=2>";
                            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Details').'</span><br/>';
                            echo $rowMedical['longTermMedicationDetails'];
                            echo '</td>';
                            echo '</tr>';
                            echo '<tr>';
                            echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Tetanus Last 10 Years?').'</span><br/>';
                            if ($rowMedical['tetanusWithin10Years'] == '') {
                                echo '<i>'.__($guid, 'Unknown').'</i>';
                            } else {
                                echo $rowMedical['tetanusWithin10Years'];
                            }
                            echo '</td>';
                            echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Blood Type').'</span><br/>';
                            echo $rowMedical['bloodType'];
                            echo '</td>';
                            echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                            echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Medical Conditions?').'</span><br/>';
                            if ($resultCondition->rowCount() > 0) {
                                echo __($guid, 'Yes').'. '.__($guid, 'Details below.');
                            } else {
                                __($guid, 'No');
                            }
                            echo '</td>';
                            echo '</tr>';
                            echo '</table>';

                            while ($rowCondition = $resultCondition->fetch()) {
                                echo '<h4>';
                                $alert = getAlert($guid, $connection2, $rowCondition['gibbonAlertLevelID']);
                                if ($alert != false) {
                                    echo __($guid, $rowCondition['name'])." <span style='color: #".$alert['color']."'>(".__($guid, $alert['name']).' '.__($guid, 'Risk').')</span>';
                                }
                                echo '</h4>';

                                echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                                echo '<tr>';
                                echo "<td style='width: 50%; vertical-align: top'>";
                                echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Triggers').'</span><br/>';
                                echo $rowCondition['triggers'];
                                echo '</td>';
                                echo "<td style='width: 50%; vertical-align: top' colspan=2>";
                                echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Reaction').'</span><br/>';
                                echo $rowCondition['reaction'];
                                echo '</td>';
                                echo '</tr>';
                                echo '<tr>';
                                echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                                echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Response').'</span><br/>';
                                echo $rowCondition['response'];
                                echo '</td>';
                                echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                                echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Medication').'</span><br/>';
                                echo $rowCondition['medication'];
                                echo '</td>';
                                echo '</tr>';
                                echo '<tr>';
                                echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                                echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Last Episode Date').'</span><br/>';
                                if (is_null($row['dob']) == false and $row['dob'] != '0000-00-00') {
                                    echo dateConvertBack($guid, $rowCondition['lastEpisode']);
                                }
                                echo '</td>';
                                echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                                echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Last Episode Treatment').'</span><br/>';
                                echo $rowCondition['lastEpisodeTreatment'];
                                echo '</td>';
                                echo '</tr>';
                                echo '<tr>';
                                echo "<td style='width: 33%; padding-top: 15px; vertical-align: top' colspan=2>";
                                echo "<span style='font-size: 115%; font-weight: bold'>".__($guid, 'Comments').'</span><br/>';
                                echo $rowCondition['comment'];
                                echo '</td>';
                                echo '</tr>';
                                echo '</table>';
                            }
                        }
                    } elseif ($subpage == 'Notes') {
                        if ($enableStudentNotes != 'Y') {
                            echo "<div class='error'>";
                            echo __($guid, 'You do not have access to this action.');
                            echo '</div>';
                        } else {
                            if (isActionAccessible($guid, $connection2, '/modules/Students/student_view_details_notes_add.php') == false) {
                                echo "<div class='error'>";
                                echo __($guid, 'Your request failed because you do not have access to this action.');
                                echo '</div>';
                            } else {
                                if (isset($_GET['return'])) {
                                    returnProcess($guid, $_GET['return'], null, null);
                                }

                                echo '<p>';
                                echo __($guid, 'Student Notes provide a way to store information on students which does not fit elsewhere in the system, or which you want to be able to see quickly in one place.').' <b>'.__($guid, 'Please remember that notes are visible to other users who have access to full student profiles (this should not generally include parents).').'</b>';
                                echo '</p>';

                                $categories = false;
                                $category = null;
                                if (isset($_GET['category'])) {
                                    $category = $_GET['category'];
                                }

                                try {
                                    $dataCategories = array();
                                    $sqlCategories = "SELECT * FROM gibbonStudentNoteCategory WHERE active='Y' ORDER BY name";
                                    $resultCategories = $connection2->prepare($sqlCategories);
                                    $resultCategories->execute($dataCategories);
                                } catch (PDOException $e) {
                                }
                                if ($resultCategories->rowCount() > 0) {
                                    $categories = true;

                                    echo '<h3>';
                                    echo __($guid, 'Filter');
                                    echo '</h3>';

                                    $form = Form::create('filter', $_SESSION[$guid]['absoluteURL'].'/index.php','get');
                                    $form->setClass('noIntBorder fullWidth');

                                    $form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/student_view_details.php');
                                    $form->addHiddenValue('gibbonPersonID', $gibbonPersonID);
                                    $form->addHiddenValue('allStudents', $allStudents);
                                    $form->addHiddenValue('search', $search);
                                    $form->addHiddenValue('subpage', 'Notes');

                                    $sql = "SELECT gibbonStudentNoteCategoryID as value, name FROM gibbonStudentNoteCategory WHERE active='Y' ORDER BY name";
                                    $rowFilter = $form->addRow();
                                        $rowFilter->addLabel('category', __('Category'));
                                        $rowFilter->addSelect('category')->fromQuery($pdo, $sql)->selected($category)->placeholder();

                                    $rowFilter = $form->addRow();
                                        $rowFilter->addSearchSubmit($gibbon->session, __('Clear Filters'), array('gibbonPersonID', 'allStudents', 'search', 'subpage'));
                                    
                                    echo $form->getOutput();
                                }

                                try {
                                    if ($category == null) {
                                        $data = array('gibbonPersonID' => $gibbonPersonID);
                                        $sql = 'SELECT gibbonStudentNote.*, gibbonStudentNoteCategory.name AS category, surname, preferredName FROM gibbonStudentNote LEFT JOIN gibbonStudentNoteCategory ON (gibbonStudentNote.gibbonStudentNoteCategoryID=gibbonStudentNoteCategory.gibbonStudentNoteCategoryID) JOIN gibbonPerson ON (gibbonStudentNote.gibbonPersonIDCreator=gibbonPerson.gibbonPersonID) WHERE gibbonStudentNote.gibbonPersonID=:gibbonPersonID ORDER BY timestamp DESC';
                                    } else {
                                        $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonStudentNoteCategoryID' => $category);
                                        $sql = 'SELECT gibbonStudentNote.*, gibbonStudentNoteCategory.name AS category, surname, preferredName FROM gibbonStudentNote LEFT JOIN gibbonStudentNoteCategory ON (gibbonStudentNote.gibbonStudentNoteCategoryID=gibbonStudentNoteCategory.gibbonStudentNoteCategoryID) JOIN gibbonPerson ON (gibbonStudentNote.gibbonPersonIDCreator=gibbonPerson.gibbonPersonID) WHERE gibbonStudentNote.gibbonPersonID=:gibbonPersonID AND gibbonStudentNote.gibbonStudentNoteCategoryID=:gibbonStudentNoteCategoryID ORDER BY timestamp DESC';
                                    }
                                    $result = $connection2->prepare($sql);
                                    $result->execute($data);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }

                                echo "<div class='linkTop'>";
                                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/student_view_details_notes_add.php&gibbonPersonID=$gibbonPersonID&search=$search&allStudents=$allStudents&search=$search&allStudents=$allStudents&subpage=Notes&category=$category'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
                                echo '</div>';

                                if ($result->rowCount() < 1) {
                                    echo "<div class='error'>";
                                    echo __($guid, 'There are no records to display.');
                                    echo '</div>';
                                } else {
                                    echo "<table cellspacing='0' style='width: 100%'>";
                                    echo "<tr class='head'>";
                                    echo '<th>';
                                    echo __($guid, 'Date').'<br/>';
                                    echo "<span style='font-size: 75%; font-style: italic'>".__($guid, 'Time').'</span>';
                                    echo '</th>';
                                    echo '<th>';
                                    echo __($guid, 'Category');
                                    echo '</th>';
                                    echo '<th>';
                                    echo __($guid, 'Title').'<br/>';
                                    echo "<span style='font-size: 75%; font-style: italic'>".__($guid, 'Overview').'</span>';
                                    echo '</th>';
                                    echo '<th>';
                                    echo __($guid, 'Note Taker');
                                    echo '</th>';
                                    echo '<th>';
                                    echo __($guid, 'Actions');
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
                                        echo dateConvertBack($guid, substr($row['timestamp'], 0, 10)).'<br/>';
                                        echo "<span style='font-size: 75%; font-style: italic'>".substr($row['timestamp'], 11, 5).'</span>';
                                        echo '</td>';
                                        echo '<td>';
                                        echo $row['category'];
                                        echo '</td>';
                                        echo '<td>';
                                        if ($row['title'] == '') {
                                            echo '<i>'.__($guid, 'NA').'</i><br/>';
                                        } else {
                                            echo $row['title'].'<br/>';
                                        }
                                        echo "<span style='font-size: 75%; font-style: italic'>".substr(strip_tags($row['note']), 0, 60).'</span>';
                                        echo '</td>';
                                        echo '<td>';
                                        echo formatName('', $row['preferredName'], $row['surname'], 'Staff', false, true);
                                        echo '</td>';
                                        echo '<td>';
                                        if ($row['gibbonPersonIDCreator'] == $_SESSION[$guid]['gibbonPersonID']) {
                                            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/student_view_details_notes_edit.php&search='.$search.'&gibbonStudentNoteID='.$row['gibbonStudentNoteID']."&gibbonPersonID=$gibbonPersonID&search=$search&allStudents=$allStudents&subpage=Notes&category=$category'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                                        }
                                        echo "<script type='text/javascript'>";
                                        echo '$(document).ready(function(){';
                                        echo "\$(\".note-$count\").hide();";
                                        echo "\$(\".show_hide-$count\").fadeIn(1000);";
                                        echo "\$(\".show_hide-$count\").click(function(){";
                                        echo "\$(\".note-$count\").fadeToggle(1000);";
                                        echo '});';
                                        echo '});';
                                        echo '</script>';
                                        echo "<a title='".__($guid, 'View Description')."' class='show_hide-$count' onclick='return false;' href='#'><img title='".__($guid, 'View Details')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_down.png'/></a></span><br/>";
                                        echo '</td>';
                                        echo '</tr>';
                                        echo "<tr class='note-$count' id='note-$count'>";
                                        echo '<td colspan=6>';
                                        echo $row['note'];
                                        echo '</td>';
                                        echo '</tr>';
                                    }
                                    echo '</table>';
                                }
                            }
                        }
                    } elseif ($subpage == 'School Attendance') {
                        if (isActionAccessible($guid, $connection2, '/modules/Attendance/report_studentHistory.php') == false) {
                            echo "<div class='error'>";
                            echo __($guid, 'Your request failed because you do not have access to this action.');
                            echo '</div>';
                        } else {
                            include './modules/Attendance/moduleFunctions.php';
                            report_studentHistory($guid, $gibbonPersonID, true, $_SESSION[$guid]['absoluteURL']."/report.php?q=/modules/Attendance/report_studentHistory_print.php&gibbonPersonID=$gibbonPersonID", $connection2, $row['dateStart'], $row['dateEnd']);
                        }
                    } elseif ($subpage == 'Markbook') {
                        if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_view.php') == false) {
                            echo "<div class='error'>";
                            echo __($guid, 'Your request failed because you do not have access to this action.');
                            echo '</div>';
                        } else {
                            $highestAction = getHighestGroupedAction($guid, '/modules/Markbook/markbook_view.php', $connection2);
                            if ($highestAction == false) {
                                echo "<div class='error'>";
                                echo __($guid, 'The highest grouped action cannot be determined.');
                                echo '</div>';
                            } else {
                                //Module includes
                                include './modules/Markbook/moduleFunctions.php';

                                //Get settings
                                $enableEffort = getSettingByScope($connection2, 'Markbook', 'enableEffort');
                                $enableRubrics = getSettingByScope($connection2, 'Markbook', 'enableRubrics');
                                $attainmentAlternativeName = getSettingByScope($connection2, 'Markbook', 'attainmentAlternativeName');
                                $attainmentAlternativeNameAbrev = getSettingByScope($connection2, 'Markbook', 'attainmentAlternativeNameAbrev');
                                $effortAlternativeName = getSettingByScope($connection2, 'Markbook', 'effortAlternativeName');
                                $effortAlternativeNameAbrev = getSettingByScope($connection2, 'Markbook', 'effortAlternativeNameAbrev');

                                $alert = getAlert($guid, $connection2, 002);
                                $role = getRoleCategory($_SESSION[$guid]['gibbonRoleIDCurrent'], $connection2);
                                if ($role == 'Parent') {
                                    $showParentAttainmentWarning = getSettingByScope($connection2, 'Markbook', 'showParentAttainmentWarning');
                                    $showParentEffortWarning = getSettingByScope($connection2, 'Markbook', 'showParentEffortWarning');
                                } else {
                                    $showParentAttainmentWarning = 'Y';
                                    $showParentEffortWarning = 'Y';
                                }
                                $entryCount = 0;

                                $and = '';
                                $and2 = '';
                                $dataList = array();
                                $dataEntry = array();
                                $filter = isset($_REQUEST['filter'])? $_REQUEST['filter'] : $_SESSION[$guid]['gibbonSchoolYearID'];

                                if ($filter != '*') {
                                    $dataList['filter'] = $filter;
                                    $and .= ' AND gibbonSchoolYearID=:filter';
                                }

                                $filter2 = isset($_REQUEST['filter2'])? $_REQUEST['filter2'] : '*';
                                if ($filter2 != '*') {
                                    $dataList['filter2'] = $filter2;
                                    $and .= ' AND gibbonDepartmentID=:filter2';
                                }

                                $filter3 = isset($_REQUEST['filter3'])? $_REQUEST['filter3'] : '';
                                if ($filter3 != '') {
                                    $dataEntry['filter3'] = $filter3;
                                    $and2 .= ' AND type=:filter3';
                                }

                                echo '<p>';
                                echo __($guid, 'This page displays academic results for a student throughout their school career. Only subjects with published results are shown.');
                                echo '</p>';

                                $form = Form::create('filter', $_SESSION[$guid]['absoluteURL'].'/index.php','get');
                                $form->setClass('noIntBorder fullWidth');

                                $form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/student_view_details.php');
                                $form->addHiddenValue('gibbonPersonID', $gibbonPersonID);
                                $form->addHiddenValue('allStudents', $allStudents);
                                $form->addHiddenValue('search', $search);
                                $form->addHiddenValue('subpage', 'Markbook');

                                $sqlSelect = "SELECT gibbonDepartmentID as value, name FROM gibbonDepartment WHERE type='Learning Area' ORDER BY name";
                                $rowFilter = $form->addRow();
                                    $rowFilter->addLabel('filter2', __('Learning Areas'));
                                    $rowFilter->addSelect('filter2')
                                        ->fromArray(array('*' => __('All Learning Areas')))
                                        ->fromQuery($pdo, $sqlSelect)
                                        ->selected($filter2);

                                $dataSelect = array('gibbonPersonID' => $gibbonPersonID);
                                $sqlSelect = "SELECT gibbonSchoolYear.gibbonSchoolYearID as value, CONCAT(gibbonSchoolYear.name, ' (', gibbonYearGroup.name, ')') AS name FROM gibbonStudentEnrolment JOIN gibbonSchoolYear ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) WHERE (gibbonSchoolYear.status='Current' OR gibbonSchoolYear.status='Past') AND gibbonPersonID=:gibbonPersonID ORDER BY gibbonSchoolYear.sequenceNumber";
                                $rowFilter = $form->addRow();
                                    $rowFilter->addLabel('filter', __('School Years'));
                                    $rowFilter->addSelect('filter')
                                        ->fromArray(array('*' => __('All Years')))
                                        ->fromQuery($pdo, $sqlSelect, $dataSelect)
                                        ->selected($filter);

                                $types = getSettingByScope($connection2, 'Markbook', 'markbookType');
                                if (!empty($types)) {
                                    $rowFilter = $form->addRow();
                                    $rowFilter->addLabel('filter3', __('Type'));
                                    $rowFilter->addSelect('filter3')
                                        ->fromString($types)
                                        ->selected($filter3)
                                        ->placeholder();
                                }

                                $details = isset($_GET['details'])? $_GET['details'] : 'Yes';
                                $form->addHiddenValue('details', 'No');
                                $showHide = $form->getFactory()->createCheckbox('details')->addClass('details')->setValue('Yes')->checked($details)->inline(true)
                                    ->description(__('Show/Hide Details'))->wrap('&nbsp;<span class="small emphasis displayInlineBlock">', '</span>');

                                $rowFilter = $form->addRow();
                                    $rowFilter->addSearchSubmit($gibbon->session, __('Clear Filters'), array('gibbonPersonID', 'allStudents', 'search', 'subpage'))->prepend($showHide->getOutput());
                                
                                echo $form->getOutput();
                                ?>

                                <script type="text/javascript">
                                    /* Show/Hide detail control */
                                    $(document).ready(function(){
                                        var updateDetails = function (){
                                            if ($('input[name=details]:checked').val()=="Yes" ) {
                                                $(".detailItem").slideDown("fast", $(".detailItem").css("{'display' : 'table-row'}"));
                                            }
                                            else {
                                                $(".detailItem").slideUp("fast");
                                            }
                                        }
                                        $(".details").click(updateDetails);
                                        updateDetails();
                                    });
                                </script>

                                <?php
                                if ($highestAction == 'View Markbook_myClasses') {
                                    // Get class list (limited to a teacher's classes)
                                    try {
                                        $dataList['gibbonPersonIDTeacher'] = $_SESSION[$guid]['gibbonPersonID'];
                                        $dataList['gibbonPersonIDStudent'] = $gibbonPersonID;
                                        $sqlList = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourse.name, gibbonCourseClass.gibbonCourseClassID, gibbonScaleGrade.value AS target
                                            FROM gibbonCourse
                                            JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                                            JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                                            JOIN gibbonCourseClassPerson as teacherParticipant ON (teacherParticipant.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                                            LEFT JOIN gibbonMarkbookTarget ON (
                                                gibbonMarkbookTarget.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID
                                                AND gibbonMarkbookTarget.gibbonPersonIDStudent=:gibbonPersonIDStudent)
                                            LEFT JOIN gibbonScaleGrade ON (gibbonMarkbookTarget.gibbonScaleGradeID=gibbonScaleGrade.gibbonScaleGradeID)
                                            WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonIDStudent
                                            AND teacherParticipant.gibbonPersonID=:gibbonPersonIDTeacher
                                            $and ORDER BY course, class";
                                        $resultList = $connection2->prepare($sqlList);
                                        $resultList->execute($dataList);
                                    } catch (PDOException $e) {
                                        echo "<div class='error'>".$e->getMessage().'</div>';
                                    }
                                } else {
                                    // Get class list (all classes)
                                    try {
                                        $dataList['gibbonPersonIDStudent'] = $gibbonPersonID;
                                        $sqlList = "SELECT gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonCourse.name, gibbonCourseClass.gibbonCourseClassID, gibbonScaleGrade.value AS target
                                            FROM gibbonCourse
                                            JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                                            JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                                            LEFT JOIN gibbonMarkbookTarget ON (
                                                gibbonMarkbookTarget.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID
                                                AND gibbonMarkbookTarget.gibbonPersonIDStudent=:gibbonPersonIDStudent)
                                            LEFT JOIN gibbonScaleGrade ON (gibbonMarkbookTarget.gibbonScaleGradeID=gibbonScaleGrade.gibbonScaleGradeID)
                                            WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonIDStudent
                                            $and ORDER BY course, class";
                                        $resultList = $connection2->prepare($sqlList);
                                        $resultList->execute($dataList);
                                    } catch (PDOException $e) {
                                        echo "<div class='error'>".$e->getMessage().'</div>';
                                    }
                                }


                                if ($resultList->rowCount() > 0) {
                                    renderStudentGPA( $pdo, $guid, $_GET['gibbonPersonID'] );

                                    // Only visible to teachers and admin for now
                                    if ($highestAction == 'View Markbook_allClassesAllData') {
                                        renderStudentCourseAverage($pdo, $guid, $_GET['gibbonPersonID']);
                                    }

                                    while ($rowList = $resultList->fetch()) {
                                        echo "<a name='".$rowList['gibbonCourseClassID']."'></a><h4>".$rowList['course'].'.'.$rowList['class']." <span style='font-size:85%; font-style: italic'>(".$rowList['name'].')</span></h4>';

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

                                        try {
                                            $dataEntry['gibbonPersonID'] = $gibbonPersonID;
                                            $dataEntry['gibbonCourseClassID'] = $rowList['gibbonCourseClassID'];
                                            if ($highestAction == 'View Markbook_viewMyChildrensClasses') {
                                                $sqlEntry = "SELECT *, gibbonMarkbookColumn.comment AS commentOn, gibbonMarkbookColumn.uploadedResponse AS uploadedResponseOn, gibbonMarkbookEntry.comment AS comment FROM gibbonMarkbookEntry JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) WHERE gibbonPersonIDStudent=:gibbonPersonID AND gibbonCourseClassID=:gibbonCourseClassID AND complete='Y' AND completeDate<='".date('Y-m-d')."' AND viewableParents='Y' $and2 ORDER BY completeDate";
                                            } else {
                                                $sqlEntry = "SELECT *, gibbonMarkbookColumn.comment AS commentOn, gibbonMarkbookColumn.uploadedResponse AS uploadedResponseOn, gibbonMarkbookEntry.comment AS comment FROM gibbonMarkbookEntry JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) WHERE gibbonPersonIDStudent=:gibbonPersonID AND gibbonCourseClassID=:gibbonCourseClassID AND complete='Y' AND completeDate<='".date('Y-m-d')."' $and2 ORDER BY completeDate";
                                            }
                                            $resultEntry = $connection2->prepare($sqlEntry);
                                            $resultEntry->execute($dataEntry);
                                        } catch (PDOException $e) {
                                            echo "<div class='error'>".$e->getMessage().'</div>';
                                        }

                                        if ($resultEntry->rowCount() > 0) {
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
                                            if ($enableEffort == 'Y') {
                                                echo "<th style='width: 75px; text-align: center'>";
                                                if ($effortAlternativeName != '') {
                                                    echo $effortAlternativeName;
                                                } else {
                                                    echo __($guid, 'Effort');
                                                }
                                                echo '</th>';
                                            }
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
                                                }
                                                if (isset($unit[1])) {
                                                    if ($unit[1] != '') {
                                                        echo $unit[1].' '.__($guid, 'Unit').'</i><br/>';
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
                                                        $dataAttainment = array('gibbonScaleIDAttainment' => $rowEntry['gibbonScaleIDAttainment']);
                                                        $sqlAttainment = 'SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleIDAttainment';
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
                                                    if ($rowEntry['gibbonRubricIDAttainment'] != '' AND $enableRubrics =='Y') {
                                                        echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/Markbook/markbook_view_rubric.php&gibbonRubricID='.$rowEntry['gibbonRubricIDAttainment'].'&gibbonCourseClassID='.$rowList['gibbonCourseClassID'].'&gibbonMarkbookColumnID='.$rowEntry['gibbonMarkbookColumnID']."&gibbonPersonID=$gibbonPersonID&mark=FALSE&type=attainment&width=1100&height=550'><img style='margin-bottom: -3px; margin-left: 3px' title='View Rubric' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/rubric.png'/></a>";
                                                    }
                                                    echo '</div>';
                                                    if ($rowEntry['attainmentValue'] != '' && strstr($rowEntry['attainmentValue'], '%') === false) {
                                                        echo "<div class='detailItem' style='font-size: 75%; font-style: italic; margin-top: 2px'><b>".htmlPrep(__($guid, $rowEntry['attainmentDescriptor'])).'</b>'.__($guid, $attainmentExtra).'</div>';
                                                    }
                                                    else {
                                                        if ($rowEntry['attainmentRaw'] == 'Y' and !empty($rowEntry['attainmentValueRaw'])) {
                                                            echo "<div class='detailItem' style='font-size: 75%; font-style: italic; margin-top: 2px'><br/>".$rowEntry['attainmentValueRaw'].' / '.$rowEntry['attainmentRawMax'].'</div>';
                                                        }
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
                                                            $dataEffort = array('gibbonScaleIDEffort' => $rowEntry['gibbonScaleIDEffort']);
                                                            $sqlEffort = 'SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleIDEffort';
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
                                                        if ($rowEntry['gibbonRubricIDEffort'] != '' AND $enableRubrics =='Y') {
                                                            echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/Markbook/markbook_view_rubric.php&gibbonRubricID='.$rowEntry['gibbonRubricIDEffort'].'&gibbonCourseClassID='.$rowList['gibbonCourseClassID'].'&gibbonMarkbookColumnID='.$rowEntry['gibbonMarkbookColumnID']."&gibbonPersonID=$gibbonPersonID&mark=FALSE&type=effort&width=1100&height=550'><img style='margin-bottom: -3px; margin-left: 3px' title='View Rubric' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/rubric.png'/></a>";
                                                        }
                                                        echo '</div>';
                                                        if ($rowEntry['effortValue'] != '') {
                                                            echo "<div class='detailItem' style='font-size: 75%; font-style: italic; margin-top: 2px'><b>".htmlPrep(__($guid, $rowEntry['effortDescriptor'])).'</b>'.__($guid, $effortExtra).'</div>';
                                                        }
                                                        echo '</td>';
                                                    }
                                                }
                                                if ($rowEntry['commentOn'] == 'N' and $rowEntry['uploadedResponseOn'] == 'N') {
                                                    echo "<td class='dull' style='color: #bbb; text-align: center'>";
                                                    echo __($guid, 'N/A');
                                                    echo '</td>';
                                                } else {
                                                    echo '<td>';
                                                    if ($rowEntry['comment'] != '') {
                                                        if (mb_strlen($rowEntry['comment']) > 200) {
                                                            echo "<script type='text/javascript'>";
                                                            echo '$(document).ready(function(){';
                                                            echo "\$(\".comment-$entryCount\").hide();";
                                                            echo "\$(\".show_hide-$entryCount\").fadeIn(1000);";
                                                            echo "\$(\".show_hide-$entryCount\").click(function(){";
                                                            echo "\$(\".comment-$entryCount\").fadeToggle(1000);";
                                                            echo '});';
                                                            echo '});';
                                                            echo '</script>';
                                                            echo '<span>'.mb_substr($rowEntry['comment'], 0, 200).'...<br/>';
                                                            echo "<a title='".__($guid, 'View Description')."' class='show_hide-$entryCount' onclick='return false;' href='#'>".__($guid, 'Read more').'</a></span><br/>';
                                                        } else {
                                                            echo nl2br($rowEntry['comment']);
                                                        }
                                                    }
                                                    if ($rowEntry['response'] != '') {
                                                        echo "<a title='Uploaded Response' href='".$_SESSION[$guid]['absoluteURL'].'/'.$rowEntry['response']."'>".__($guid, 'Uploaded Response').'</a><br/>';
                                                    }
                                                    echo '</td>';
                                                }
                                                if ($rowEntry['gibbonPlannerEntryID'] == 0) {
                                                    echo "<td class='dull' style='color: #bbb; text-align: center'>";
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
                                                        echo "<td class='dull' style='color: #bbb; text-align: center'>";
                                                        echo __($guid, 'N/A');
                                                        echo '</td>';
                                                    } else {
                                                        echo '<td>';
                                                        $rowSub = $resultSub->fetch();

                                                        try {
                                                            $dataWork = array('gibbonPlannerEntryID' => $rowEntry['gibbonPlannerEntryID'], 'gibbonPersonID' => $_GET['gibbonPersonID']);
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
                                                if (strlen($rowEntry['comment']) > 50) {
                                                    echo "<tr class='comment-$entryCount' id='comment-$entryCount'>";
                                                    echo '<td colspan=6>';
                                                    echo nl2br($rowEntry['comment']);
                                                    echo '</td>';
                                                    echo '</tr>';
                                                }
                                            }
                                        }

                                        $enableColumnWeighting = getSettingByScope($connection2, 'Markbook', 'enableColumnWeighting');
                                        $enableDisplayCumulativeMarks = getSettingByScope($connection2, 'Markbook', 'enableDisplayCumulativeMarks');

                                        if ($enableColumnWeighting == 'Y' && $enableDisplayCumulativeMarks == 'Y') {
                                            $gibbonSchoolYearID = (!empty($dataList['filter']))? $dataList['filter'] : $_SESSION[$guid]['gibbonSchoolYearID'];
                                            if (renderStudentCumulativeMarks($gibbon, $pdo, $_GET['gibbonPersonID'], $rowList['gibbonCourseClassID'], $gibbonSchoolYearID)) {
                                                $entryCount++;
                                            }
                                        }

                                        echo '</table>';
                                    }
                                }
                                if ($entryCount < 1) {
                                    echo "<div class='error'>";
                                    echo __($guid, 'There are no records to display.');
                                    echo '</div>';
                                }
                            }
                        }
                    } elseif ($subpage == 'Internal Assessment') {
                        if (isActionAccessible($guid, $connection2, '/modules/Formal Assessment/internalAssessment_view.php') == false) {
                            echo "<div class='error'>";
                            echo __($guid, 'Your request failed because you do not have access to this action.');
                            echo '</div>';
                        } else {
                            $highestAction = getHighestGroupedAction($guid, '/modules/Formal Assessment/internalAssessment_view.php', $connection2);
                            if ($highestAction == false) {
                                echo "<div class='error'>";
                                echo __($guid, 'The highest grouped action cannot be determined.');
                                echo '</div>';
                            } else {
                                //Module includes
                                include './modules/Formal Assessment/moduleFunctions.php';

                                if ($highestAction == 'View Internal Assessments_all') {
                                    echo getInternalAssessmentRecord($guid, $connection2, $gibbonPersonID);
                                } elseif ($highestAction == 'View Internal Assessments_myChildrens') {
                                    echo getInternalAssessmentRecord($guid, $connection2, $gibbonPersonID, 'parent');
                                }
                            }
                        }
                    } elseif ($subpage == 'External Assessment') {
                        if (isActionAccessible($guid, $connection2, '/modules/Formal Assessment/externalAssessment_details.php') == false and isActionAccessible($guid, $connection2, '/modules/Formal Assessment/externalAssessment_view.php') == false) {
                            echo "<div class='error'>";
                            echo __($guid, 'Your request failed because you do not have access to this action.');
                            echo '</div>';
                        } else {
                            //Module includes
                            include './modules/Formal Assessment/moduleFunctions.php';

                            //Print assessments
                            $gibbonYearGroupID = '';
                            if (isset($row['gibbonYearGroupID'])) {
                                $gibbonYearGroupID = $row['gibbonYearGroupID'];
                            }
                            externalAssessmentDetails($guid, $gibbonPersonID, $connection2, $gibbonYearGroupID);
                        }
                    } elseif ($subpage == 'Individual Needs') {
                        if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/in_view.php') == false) {
                            echo "<div class='error'>";
                            echo __($guid, 'Your request failed because you do not have access to this action.');
                            echo '</div>';
                        } else {
                            //Module includes
                            include './modules/Individual Needs/moduleFunctions.php';

                            $statusTable = printINStatusTable($connection2, $guid, $gibbonPersonID, 'disabled');
                            if ($statusTable == false) {
                                echo "<div class='error'>";
                                echo __($guid, 'Your request failed due to a database error.');
                                echo '</div>';
                            } else {
                                echo $statusTable;
                            }

                            //Get and display a list of student's educational assistants
                            try {
                                $dataDetail = array('gibbonPersonID1' => $gibbonPersonID, 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID2' => $gibbonPersonID);
                                $sqlDetail = "(SELECT DISTINCT surname, preferredName, email
                                    FROM gibbonPerson
                                        JOIN gibbonINAssistant ON (gibbonINAssistant.gibbonPersonIDAssistant=gibbonPerson.gibbonPersonID)
                                    WHERE status='Full'
                                        AND gibbonPersonIDStudent=:gibbonPersonID1)
                                UNION
                                (SELECT DISTINCT surname, preferredName, email
                                    FROM gibbonPerson
                                        JOIN gibbonRollGroup ON (gibbonRollGroup.gibbonPersonIDEA=gibbonPerson.gibbonPersonID OR gibbonRollGroup.gibbonPersonIDEA2=gibbonPerson.gibbonPersonID OR gibbonRollGroup.gibbonPersonIDEA3=gibbonPerson.gibbonPersonID)
                                        JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
                                        JOIN gibbonSchoolYear ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
                                    WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                                        AND gibbonStudentEnrolment.gibbonPersonID=:gibbonPersonID2
                                )
                                ORDER BY preferredName, surname, email";
                                $resultDetail = $connection2->prepare($sqlDetail);
                                $resultDetail->execute($dataDetail);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
                            if ($resultDetail->rowCount() > 0) {
                                echo '<h3>';
                                echo __($guid, 'Educational Assistants');
                                echo '</h3>';

                                echo '<ul>';
                                while ($rowDetail = $resultDetail->fetch()) {
                                    echo '<li>'.htmlPrep(formatName('', $rowDetail['preferredName'], $rowDetail['surname'], 'Student', false));
                                    if ($rowDetail['email'] != '') {
                                        echo htmlPrep(' <'.$rowDetail['email'].'>');
                                    }
                                    echo '</li>';
                                }
                                echo '</ul>';
                            }


                            echo '<h3>';
                            echo __($guid, 'Individual Education Plan');
                            echo '</h3>';
                            try {
                                $dataIN = array('gibbonPersonID' => $gibbonPersonID);
                                $sqlIN = 'SELECT * FROM gibbonIN WHERE gibbonPersonID=:gibbonPersonID';
                                $resultIN = $connection2->prepare($sqlIN);
                                $resultIN->execute($dataIN);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }

                            if ($resultIN->rowCount() != 1) {
                                echo "<div class='error'>";
                                echo __($guid, 'There are no records to display.');
                                echo '</div>';
                            } else {
                                $rowIN = $resultIN->fetch();

                                echo "<div style='font-weight: bold'>".__($guid, 'Targets').'</div>';
                                echo '<p>'.$rowIN['targets'].'</p>';

                                echo "<div style='font-weight: bold; margin-top: 30px'>".__($guid, 'Teaching Strategies').'</div>';
                                echo '<p>'.$rowIN['strategies'].'</p>';

                                echo "<div style='font-weight: bold; margin-top: 30px'>".__($guid, 'Notes & Review').'s</div>';
                                echo '<p>'.$rowIN['notes'].'</p>';
                            }
                        }
                    } elseif ($subpage == 'Library Borrowing') {
                        if (isActionAccessible($guid, $connection2, '/modules/Library/report_studentBorrowingRecord.php') == false) {
                            echo "<div class='error'>";
                            echo __($guid, 'Your request failed because you do not have access to this action.');
                            echo '</div>';
                        } else {
                            include './modules/Library/moduleFunctions.php';

                            //Print borrowing record
                            $output = getBorrowingRecord($guid, $connection2, $gibbonPersonID);
                            if ($output == false) {
                                echo "<div class='error'>";
                                echo __($guid, 'Your request failed due to a database error.');
                                echo '</div>';
                            } else {
                                echo $output;
                            }
                        }
                    } elseif ($subpage == 'Timetable') {
                        if (isActionAccessible($guid, $connection2, '/modules/Timetable/tt_view.php') == false) {
                            echo "<div class='error'>";
                            echo __($guid, 'Your request failed because you do not have access to this action.');
                            echo '</div>';
                        } else {
                            if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php') == true) {
                                $role = getRoleCategory($row['gibbonRoleIDPrimary'], $connection2);
                                if ($role == 'Student' or $role == 'Staff') {
                                    echo "<div class='linkTop'>";
                                    echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php&gibbonPersonID=$gibbonPersonID&gibbonSchoolYearID=".$_SESSION[$guid]['gibbonSchoolYearID']."&type=$role'>".__($guid, 'Edit')."<img style='margin: 0 0 -4px 5px' title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                                    echo '</div>';
                                }
                            }

                            include './modules/Timetable/moduleFunctions.php';
                            $ttDate = null;
                            if (isset($_POST['ttDate'])) {
                                $ttDate = dateConvertToTimestamp(dateConvert($guid, $_POST['ttDate']));
                            }
                            $tt = renderTT($guid, $connection2, $gibbonPersonID, '', false, $ttDate, '/modules/Students/student_view_details.php', "&gibbonPersonID=$gibbonPersonID&search=$search&allStudents=$allStudents&subpage=Timetable");
                            if ($tt != false) {
                                echo $tt;
                            } else {
                                echo "<div class='error'>";
                                echo __($guid, 'There are no records to display.');
                                echo '</div>';
                            }
                        }
                    } elseif ($subpage == 'Activities') {
                        if (!(isActionAccessible($guid, $connection2, '/modules/Activities/report_activityChoices_byStudent'))) {
                            echo "<div class='error'>";
                            echo __($guid, 'Your request failed because you do not have access to this action.');
                            echo '</div>';
                        } else {
                            echo '<p>';
                            echo __($guid, 'This report shows the current and historical activities that a student has enroled in.');
                            echo '</p>';

                            $dateType = getSettingByScope($connection2, 'Activities', 'dateType');
                            if ($dateType == 'Term') {
                                $maxPerTerm = getSettingByScope($connection2, 'Activities', 'maxPerTerm');
                            }

                            try {
                                $dataYears = array('gibbonPersonID' => $gibbonPersonID);
                                $sqlYears = "SELECT *
                                    FROM gibbonStudentEnrolment
                                    JOIN gibbonSchoolYear ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
                                    WHERE (gibbonSchoolYear.status='Current' OR gibbonSchoolYear.status='Past')
                                    AND gibbonPersonID=:gibbonPersonID
                                     ORDER BY sequenceNumber DESC";
                                $resultYears = $connection2->prepare($sqlYears);
                                $resultYears->execute($dataYears);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }

                            if ($resultYears->rowCount() < 1) {
                                echo "<div class='error'>";
                                echo __($guid, 'There are no records to display.');
                                echo '</div>';
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
                                        $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonSchoolYearID' => $rowYears['gibbonSchoolYearID']);
                                        $sql = "SELECT gibbonActivity.*, gibbonActivityStudent.status, NULL AS role FROM gibbonActivity JOIN gibbonActivityStudent ON (gibbonActivity.gibbonActivityID=gibbonActivityStudent.gibbonActivityID) WHERE gibbonActivityStudent.gibbonPersonID=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' ORDER BY name";
                                        $result = $connection2->prepare($sql);
                                        $result->execute($data);
                                    } catch (PDOException $e) {
                                        echo "<div class='error'>".$e->getMessage().'</div>';
                                    }

                                    if ($result->rowCount() < 1) {
                                        echo "<div class='error'>";
                                        echo __($guid, 'There are no records to display.');
                                        echo '</div>';
                                    } else {
                                        echo "<table cellspacing='0' style='width: 100%'>";
                                        echo "<tr class='head'>";
                                        echo '<th>';
                                        echo __($guid, 'Activity');
                                        echo '</th>';
                                        $options = getSettingByScope($connection2, 'Activities', 'activityTypes');
                                        if ($options != '') {
                                            echo '<th>';
                                            echo __($guid, 'Type');
                                            echo '</th>';
                                        }
                                        echo '<th>';
                                        if ($dateType != 'Date') {
                                            echo __($guid, 'Term');
                                        } else {
                                            echo __($guid, 'Dates');
                                        }
                                        echo '</th>';
                                        echo '<th>';
                                        echo __($guid, 'Status');
                                        echo '</th>';
                                        echo '<th>';
                                        echo __($guid, 'Actions');
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
                                            echo $row['name'];
                                            echo '</td>';
                                            if ($options != '') {
                                                echo '<td>';
                                                echo trim($row['type']);
                                                echo '</td>';
                                            }
                                            echo '<td>';
                                            if ($dateType != 'Date') {
                                                $terms = getTerms($connection2, $_SESSION[$guid]['gibbonSchoolYearID'], true);
                                                $termList = '';
                                                for ($i = 0; $i < count($terms); $i = $i + 2) {
                                                    if (is_numeric(strpos($row['gibbonSchoolYearTermIDList'], $terms[$i]))) {
                                                        $termList .= $terms[($i + 1)].'<br/>';
                                                    }
                                                }
                                                echo $termList;
                                            } else {
                                                if (substr($row['programStart'], 0, 4) == substr($row['programEnd'], 0, 4)) {
                                                    if (substr($row['programStart'], 5, 2) == substr($row['programEnd'], 5, 2)) {
                                                        echo date('F', mktime(0, 0, 0, substr($row['programStart'], 5, 2))).' '.substr($row['programStart'], 0, 4);
                                                    } else {
                                                        echo date('F', mktime(0, 0, 0, substr($row['programStart'], 5, 2))).' - '.date('F', mktime(0, 0, 0, substr($row['programEnd'], 5, 2))).'<br/>'.substr($row['programStart'], 0, 4);
                                                    }
                                                } else {
                                                    echo date('F', mktime(0, 0, 0, substr($row['programStart'], 5, 2))).' '.substr($row['programStart'], 0, 4).' -<br/>'.date('F', mktime(0, 0, 0, substr($row['programEnd'], 5, 2))).' '.substr($row['programEnd'], 0, 4);
                                                }
                                            }
                                            echo '</td>';
                                            echo '<td>';
                                            if ($row['status'] != '') {
                                                echo $row['status'];
                                            } else {
                                                echo '<i>'.__($guid, 'NA').'</i>';
                                            }
                                            echo '</td>';
                                            echo '<td>';
                                            echo "<a class='thickbox' href='".$_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/Activities/activities_my_full.php&gibbonActivityID='.$row['gibbonActivityID']."&width=1000&height=550'><img title='".__($guid, 'View Details')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a> ";
                                            echo '</td>';
                                            echo '</tr>';
                                        }
                                        echo '</table>';
                                    }
                                }
                            }
                        }
                    } elseif ($subpage == 'Homework') {
                        if (!(isActionAccessible($guid, $connection2, '/modules/Planner/planner_edit.php') or isActionAccessible($guid, $connection2, '/modules/Planner/planner_view_full.php'))) {
                            echo "<div class='error'>";
                            echo __($guid, 'Your request failed because you do not have access to this action.');
                            echo '</div>';
                        } else {
                            echo '<h4>';
                            echo __($guid, 'Upcoming Deadlines');
                            echo '</h4>';

                            try {
                                $dataDeadlines = array('gibbonPersonID' => $gibbonPersonID, 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
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
                                echo __($guid, 'No upcoming deadlines!');
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
                                    echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner_view_full.php&search=$gibbonPersonID&gibbonPlannerEntryID=".$rowDeadlines['gibbonPlannerEntryID'].'&viewBy=date&date='.$rowDeadlines['date']."&width=1000&height=550'>".$rowDeadlines['course'].'.'.$rowDeadlines['class'].'</a><br/>';
                                    echo "<span style='font-style: italic'>".sprintf(__($guid, 'Due at %1$s on %2$s'), substr($rowDeadlines['homeworkDueDateTime'], 11, 5), dateConvertBack($guid, substr($rowDeadlines['homeworkDueDateTime'], 0, 10)));
                                    echo '</li>';
                                }
                                echo '</ol>';
                            }

                            $style = '';

                            echo '<h4>';
                            echo __($guid, 'Homework History');
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
                                $dataHistory['gibbonPersonID'] = $gibbonPersonID;
                                $dataHistory['gibbonSchoolYearID'] = $_SESSION[$guid]['gibbonSchoolYearID'];
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
                                echo "<div class='error'>";
                                echo __($guid, 'There are no records to display.');
                                echo '</div>';
                            } else {
                                echo "<div class='linkTop'>";
                                $form = Form::create('filter', $_SESSION[$guid]['absoluteURL'].'/index.php','get');
                                $form->setClass('blank fullWidth');

                                $form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/student_view_details.php');
                                $form->addHiddenValue('gibbonPersonID', $gibbonPersonID);
                                $form->addHiddenValue('allStudents', $allStudents);
                                $form->addHiddenValue('search', $search);
                                $form->addHiddenValue('subpage', 'Homework');

                                $dataSelect = array('gibbonPersonID' => $gibbonPersonID, 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'date' => date('Y-m-d'));
                                $sqlSelect = "SELECT DISTINCT gibbonCourseClass.gibbonCourseClassID as value, CONCAT(gibbonCourse.nameShort, '.', gibbonCourseClass.nameShort) as name FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left' AND homework='Y' AND gibbonSchoolYearID=:gibbonSchoolYearID AND date<=:date ORDER BY name";

                                $rowFilter = $form->addRow();
                                    $column = $rowFilter->addColumn()->addClass('inline right');
                                    $column->addSelect('gibbonCourseClassIDFilter')
                                        ->fromQuery($pdo, $sqlSelect, $dataSelect)
                                        ->selected($gibbonCourseClassIDFilter)
                                        ->setClass('mediumWidth')
                                        ->placeholder();
                                    $column->addSubmit(__('Go'));

                                echo $form->getOutput();
                                echo '</div>';

                                echo "<table cellspacing='0' style='width: 100%'>";
                                echo "<tr class='head'>";
                                echo '<th>';
                                echo __($guid, 'Class').'</br>';
                                echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Date').'</span>';
                                echo '</th>';
                                echo '<th>';
                                echo __($guid, 'Lesson').'</br>';
                                echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Unit').'</span>';
                                echo '</th>';
                                echo "<th style='min-width: 25%'>";
                                echo __($guid, 'Type').'<br/>';
                                echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Details').'</span>';
                                echo '</th>';
                                echo '<th>';
                                echo __($guid, 'Deadline');
                                echo '</th>';
                                echo '<th>';
                                echo __($guid, 'Online Submission');
                                echo '</th>';
                                echo '<th>';
                                echo __($guid, 'Actions');
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
                                                    $dataVersion = array('gibbonPlannerEntryID' => $rowHistory['gibbonPlannerEntryID'], 'gibbonPersonID' => $gibbonPersonID);
                                                    $sqlVersion = 'SELECT * FROM gibbonPlannerEntryHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID ORDER BY count DESC';
                                                    $resultVersion = $connection2->prepare($sqlVersion);
                                                    $resultVersion->execute($dataVersion);
                                                } catch (PDOException $e) {
                                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                                }
                                                if ($resultVersion->rowCount() < 1) {
                                                    //Before deadline
                                                                if (date('Y-m-d H:i:s') < $rowHistory['homeworkDueDateTime']) {
                                                                    echo "<span title='".__($guid, 'Pending')."'>".__($guid, 'Pending').'</span>';
                                                                }
                                                                //After
                                                                else {
                                                                    if (@$rowHistory['dateStart'] > @$rowSub['date']) {
                                                                        echo "<span title='".__($guid, 'Student joined school after assessment was given.')."' style='color: #000; font-weight: normal; border: 2px none #ff0000; padding: 2px 4px'>".__($guid, 'NA').'</span>';
                                                                    } else {
                                                                        if ($rowHistory['homeworkSubmissionRequired'] == 'Compulsory') {
                                                                            echo "<div style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px; margin: 2px 0px'>".__($guid, 'Incomplete').'</div>';
                                                                        } else {
                                                                            echo __($guid, 'Not submitted online');
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
                                        echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner_view_full.php&search=$gibbonPersonID&gibbonPlannerEntryID=".$rowHistory['gibbonPlannerEntryID'].'&viewBy=class&gibbonCourseClassID='.$rowHistory['gibbonCourseClassID']."&width=1000&height=550'><img title='".__($guid, 'View Details')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a> ";
                                        echo '</td>';
                                        echo '</tr>';
                                    }
                                }
                                echo '</table>';
                            }
                        }
                    } elseif ($subpage == 'Behaviour') {
                        if (isActionAccessible($guid, $connection2, '/modules/Behaviour/behaviour_view.php') == false) {
                            echo "<div class='error'>";
                            echo __($guid, 'Your request failed because you do not have access to this action.');
                            echo '</div>';
                        } else {
                            include './modules/Behaviour/moduleFunctions.php';

                            //Print assessments
                            getBehaviourRecord($guid, $gibbonPersonID, $connection2);
                        }
                    }

                    //GET HOOK IF SPECIFIED
                    if ($hook != '' and $module != '' and $action != '') {
                        //GET HOOKS AND DISPLAY LINKS
                        //Check for hook
                        try {
                            $dataHook = array('gibbonHookID' => $_GET['gibbonHookID']);
                            $sqlHook = 'SELECT * FROM gibbonHook WHERE gibbonHookID=:gibbonHookID';
                            $resultHook = $connection2->prepare($sqlHook);
                            $resultHook->execute($dataHook);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        if ($resultHook->rowCount() != 1) {
                            echo "<div class='error'>";
                            echo __($guid, 'There are no records to display.');
                            echo '</div>';
                        } else {
                            $rowHook = $resultHook->fetch();
                            $options = unserialize($rowHook['options']);

                            //Check for permission to hook
                            try {
                                $dataHook = array('gibbonRoleIDCurrent' => $_SESSION[$guid]['gibbonRoleIDCurrent'], 'sourceModuleName' => $options['sourceModuleName']);
                                $sqlHook = "SELECT gibbonHook.name, gibbonModule.name AS module, gibbonAction.name AS action FROM gibbonHook JOIN gibbonModule ON (gibbonHook.gibbonModuleID=gibbonModule.gibbonModuleID) JOIN gibbonAction ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) JOIN gibbonPermission ON (gibbonPermission.gibbonActionID=gibbonAction.gibbonActionID) WHERE gibbonModule.name='".$options['sourceModuleName']."' AND gibbonAction.name='".$options['sourceModuleAction']."' AND gibbonAction.gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE gibbonPermission.gibbonRoleID=:gibbonRoleIDCurrent AND name=:sourceModuleName) AND gibbonHook.type='Student Profile' ORDER BY name";
                                $resultHook = $connection2->prepare($sqlHook);
                                $resultHook->execute($dataHook);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
                            if ($resultHook->rowcount() != 1) {
                                echo "<div class='error'>";
                                echo __($guid, 'Your request failed because you do not have access to this action.');
                                echo '</div>';
                            } else {
                                $include = $_SESSION[$guid]['absolutePath'].'/modules/'.$options['sourceModuleName'].'/'.$options['sourceModuleInclude'];
                                if (!file_exists($include)) {
                                    echo "<div class='error'>";
                                    echo __($guid, 'The selected page cannot be displayed due to a hook error.');
                                    echo '</div>';
                                } else {
                                    include $include;
                                }
                            }
                        }
                    }

                    //Set sidebar
                    $_SESSION[$guid]['sidebarExtra'] = '';

                    //Show alerts
                    $alert = getAlertBar($guid, $connection2, $gibbonPersonID, $row['privacy'], '', false, true);
                    $_SESSION[$guid]['sidebarExtra'] .= "<div style='background-color: none; font-size: 12px; margin: 3px 0 0px 0; width: 240px; text-align: left; height: 40px; padding: 2px 0px;'>";
                    if ($alert == '') {
                        //$_SESSION[$guid]['sidebarExtra'] .= '<b>'.__($guid, 'No Current Alerts').'</b>';
                    } else {
                        $_SESSION[$guid]['sidebarExtra'] .= $alert;
                    }
                    $_SESSION[$guid]['sidebarExtra'] .= '</div>';

                    $_SESSION[$guid]['sidebarExtra'] .= getUserPhoto($guid, $studentImage, 240);

                    //PERSONAL DATA MENU ITEMS
                    $_SESSION[$guid]['sidebarExtra'] .= '<h4>'.__($guid, 'Personal').'</h4>';
                    $_SESSION[$guid]['sidebarExtra'] .= "<ul class='moduleMenu'>";
                    $style = '';
                    if ($subpage == 'Overview') {
                        $style = "style='font-weight: bold'";
                    }
                    $_SESSION[$guid]['sidebarExtra'] .= "<li><a $style href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=Overview'>".__($guid, 'Overview').'</a></li>';
                    $style = '';
                    if ($subpage == 'Personal') {
                        $style = "style='font-weight: bold'";
                    }
                    $_SESSION[$guid]['sidebarExtra'] .= "<li><a $style href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=Personal'>".__($guid, 'Personal').'</a></li>';
                    $style = '';
                    if ($subpage == 'Family') {
                        $style = "style='font-weight: bold'";
                    }
                    $_SESSION[$guid]['sidebarExtra'] .= "<li><a $style href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=Family'>".__($guid, 'Family').'</a></li>';
                    $style = '';
                    if ($subpage == 'Emergency Contacts') {
                        $style = "style='font-weight: bold'";
                    }
                    $_SESSION[$guid]['sidebarExtra'] .= "<li><a $style href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=Emergency Contacts'>".__($guid, 'Emergency Contacts').'</a></li>';
                    $style = '';
                    if ($subpage == 'Medical') {
                        $style = "style='font-weight: bold'";
                    }
                    $_SESSION[$guid]['sidebarExtra'] .= "<li><a $style href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=Medical'>".__($guid, 'Medical').'</a></li>';
                    if (isActionAccessible($guid, $connection2, '/modules/Students/student_view_details_notes_add.php')) {
                        if ($enableStudentNotes == 'Y') {
                            $style = '';
                            if ($subpage == 'Notes') {
                                $style = "style='font-weight: bold'";
                            }
                            $_SESSION[$guid]['sidebarExtra'] .= "<li><a $style href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=Notes'>".__($guid, 'Notes').'</a></li>';
                        }
                    }
                    $_SESSION[$guid]['sidebarExtra'] .= '</ul>';

                    //OTHER MENU ITEMS, DYANMICALLY ARRANGED TO MATCH CUSTOM TOP MENU
                    //Get all modules, with the categories
                    try {
                        $dataMenu = array();
                        $sqlMenu = "SELECT gibbonModuleID, category, name FROM gibbonModule WHERE active='Y' ORDER BY category, name";
                        $resultMenu = $connection2->prepare($sqlMenu);
                        $resultMenu->execute($dataMenu);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }
                    $mainMenu = array();
                    while ($rowMenu = $resultMenu->fetch()) {
                        $mainMenu[$rowMenu['name']] = $rowMenu['category'];
                    }
                    $studentMenuCateogry = array();
                    $studentMenuName = array();
                    $studentMenuLink = array();
                    $studentMenuCount = 0;

                    //Store items in an array
                    if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_view.php')) {
                        $style = '';
                        if ($subpage == 'Markbook') {
                            $style = "style='font-weight: bold'";
                        }
                        $studentMenuCategory[$studentMenuCount] = $mainMenu['Markbook'];
                        $studentMenuName[$studentMenuCount] = __($guid, 'Markbook');
                        $studentMenuLink[$studentMenuCount] = "<li><a $style href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=Markbook'>".__($guid, 'Markbook').'</a></li>';
                        ++$studentMenuCount;
                    }
                    if (isActionAccessible($guid, $connection2, '/modules/Formal Assessment/internalAssessment_view.php')) {
                        $style = '';
                        if ($subpage == 'Internal Assessment') {
                            $style = "style='font-weight: bold'";
                        }
                        $studentMenuCategory[$studentMenuCount] = $mainMenu['Formal Assessment'];
                        $studentMenuName[$studentMenuCount] = __($guid, 'Formal Assessment');
                        $studentMenuLink[$studentMenuCount] = "<li><a $style href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=Internal%20Assessment'>".__($guid, 'Internal Assessment').'</a></li>';
                        ++$studentMenuCount;
                    }
                    if (isActionAccessible($guid, $connection2, '/modules/Formal Assessment/externalAssessment_details.php') or isActionAccessible($guid, $connection2, '/modules/Formal Assessment/externalAssessment_view.php')) {
                        $style = '';
                        if ($subpage == 'External Assessment') {
                            $style = "style='font-weight: bold'";
                        }
                        $studentMenuCategory[$studentMenuCount] = $mainMenu['Formal Assessment'];
                        $studentMenuName[$studentMenuCount] = __($guid, 'External Assessment');
                        $studentMenuLink[$studentMenuCount] = "<li><a $style href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=External Assessment'>".__($guid, 'External Assessment').'</a></li>';
                        ++$studentMenuCount;
                    }

                    if (isActionAccessible($guid, $connection2, '/modules/Activities/report_activityChoices_byStudent.php')) {
                        $style = '';
                        if ($subpage == 'Activities') {
                            $style = "style='font-weight: bold'";
                        }
                        $studentMenuCategory[$studentMenuCount] = $mainMenu['Activities'];
                        $studentMenuName[$studentMenuCount] = __($guid, 'Activities');
                        $studentMenuLink[$studentMenuCount] = "<li><a $style href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=Activities'>".__($guid, 'Activities').'</a></li>';
                        ++$studentMenuCount;
                    }
                    if (isActionAccessible($guid, $connection2, '/modules/Planner/planner_edit.php') or isActionAccessible($guid, $connection2, '/modules/Planner/planner_view_full.php')) {
                        $style = '';
                        if ($subpage == 'Homework') {
                            $style = "style='font-weight: bold'";
                        }
                        $studentMenuCategory[$studentMenuCount] = $mainMenu['Planner'];
                        $studentMenuName[$studentMenuCount] = __($guid, 'Homework');
                        $studentMenuLink[$studentMenuCount] = "<li><a $style href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=Homework'>".__($guid, 'Homework').'</a></li>';
                        ++$studentMenuCount;
                    }
                    if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/in_view.php')) {
                        $style = '';
                        if ($subpage == 'Individual Needs') {
                            $style = "style='font-weight: bold'";
                        }
                        $studentMenuCategory[$studentMenuCount] = $mainMenu['Individual Needs'];
                        $studentMenuName[$studentMenuCount] = __($guid, 'Individual Needs');
                        $studentMenuLink[$studentMenuCount] = "<li><a $style href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=Individual Needs'>".__($guid, 'Individual Needs').'</a></li>';
                        ++$studentMenuCount;
                    }
                    if (isActionAccessible($guid, $connection2, '/modules/Library/report_studentBorrowingRecord.php')) {
                        $style = '';
                        if ($subpage == 'Library Borrowing') {
                            $style = "style='font-weight: bold'";
                        }
                        $studentMenuCategory[$studentMenuCount] = $mainMenu['Library'];
                        $studentMenuName[$studentMenuCount] = __($guid, 'Library Borrowing');
                        $studentMenuLink[$studentMenuCount] = "<li><a $style href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=Library Borrowing'>".__($guid, 'Library Borrowing').'</a></li>';
                        ++$studentMenuCount;
                    }
                    if (isActionAccessible($guid, $connection2, '/modules/Timetable/tt_view.php')) {
                        $style = '';
                        if ($subpage == 'Timetable') {
                            $style = "style='font-weight: bold'";
                        }
                        $studentMenuCategory[$studentMenuCount] = $mainMenu['Timetable'];
                        $studentMenuName[$studentMenuCount] = __($guid, 'Timetable');
                        $studentMenuLink[$studentMenuCount] = "<li><a $style href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=Timetable'>".__($guid, 'Timetable').'</a></li>';
                        ++$studentMenuCount;
                    }
                    if (isActionAccessible($guid, $connection2, '/modules/Behaviour/behaviour_view.php')) {
                        $style = '';
                        if ($subpage == 'Behaviour') {
                            $style = "style='font-weight: bold'";
                        }
                        $studentMenuCategory[$studentMenuCount] = $mainMenu['Behaviour'];
                        $studentMenuName[$studentMenuCount] = __($guid, 'Behaviour');
                        $studentMenuLink[$studentMenuCount] = "<li><a $style href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=Behaviour'>".__($guid, 'Behaviour').'</a></li>';
                        ++$studentMenuCount;
                    }
                    if (isActionAccessible($guid, $connection2, '/modules/Attendance/report_studentHistory.php')) {
                        $style = '';
                        if ($subpage == 'School Attendance') {
                            $style = "style='font-weight: bold'";
                        }
                        $studentMenuCategory[$studentMenuCount] = $mainMenu['Attendance'];
                        $studentMenuName[$studentMenuCount] = __($guid, 'School Attendance');
                        $studentMenuLink[$studentMenuCount] = "<li><a $style href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=School Attendance'>".__($guid, 'School Attendance').'</a></li>';
                        ++$studentMenuCount;
                    }

                    //Check for hooks, and slot them into array
                    try {
                        $dataHooks = array();
                        $sqlHooks = "SELECT * FROM gibbonHook WHERE type='Student Profile'";
                        $resultHooks = $connection2->prepare($sqlHooks);
                        $resultHooks->execute($dataHooks);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }

                    if ($resultHooks->rowCount() > 0) {
                        $hooks = array();
                        $count = 0;
                        while ($rowHooks = $resultHooks->fetch()) {
                            $options = unserialize($rowHooks['options']);
                            //Check for permission to hook
                            try {
                                $dataHook = array('gibbonRoleIDCurrent' => $_SESSION[$guid]['gibbonRoleIDCurrent'], 'sourceModuleName' => $options['sourceModuleName']);
                                $sqlHook = "SELECT gibbonHook.name, gibbonModule.name AS module, gibbonAction.name AS action FROM gibbonHook JOIN gibbonModule ON (gibbonHook.gibbonModuleID=gibbonModule.gibbonModuleID) JOIN gibbonAction ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) JOIN gibbonPermission ON (gibbonPermission.gibbonActionID=gibbonAction.gibbonActionID) WHERE gibbonModule.name='".$options['sourceModuleName']."' AND  gibbonAction.name='".$options['sourceModuleAction']."' AND gibbonAction.gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE gibbonPermission.gibbonRoleID=:gibbonRoleIDCurrent AND name=:sourceModuleName) AND gibbonHook.type='Student Profile' ORDER BY name";
                                $resultHook = $connection2->prepare($sqlHook);
                                $resultHook->execute($dataHook);
                            } catch (PDOException $e) {
                                echo "<div class='error'>".$e->getMessage().'</div>';
                            }
                            if ($resultHook->rowCount() == 1) {
                                $style = '';
                                if ($hook == $rowHooks['name'] and $_GET['module'] == $options['sourceModuleName']) {
                                    $style = "style='font-weight: bold'";
                                }
                                $studentMenuCategory[$studentMenuCount] = $mainMenu[$options['sourceModuleName']];
                                $studentMenuName[$studentMenuCount] = __($guid, $rowHooks['name']);
                                $studentMenuLink[$studentMenuCount] = "<li><a $style href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search.'&hook='.$rowHooks['name'].'&module='.$options['sourceModuleName'].'&action='.$options['sourceModuleAction'].'&gibbonHookID='.$rowHooks['gibbonHookID']."'>".__($guid, $rowHooks['name']).'</a></li>';
                                ++$studentMenuCount;
                                ++$count;
                            }
                        }
                    }

                    //Menu ordering categories
                    $mainMenuCategoryOrder = getSettingByScope($connection2, 'System', 'mainMenuCategoryOrder');
                    $orders = explode(',', $mainMenuCategoryOrder);

                    //Sort array
                    @array_multisort($orders, $studentMenuCategory, $studentMenuName, $studentMenuLink);

                    //Spit out array whilt sorting by $mainMenuCategoryOrder
                    if (count($studentMenuCategory) > 0) {
                        foreach ($orders AS $order) {
                            //Check for entries
                            $countEntries = 0;
                            for ($i = 0; $i < count($studentMenuCategory); ++$i) {
                                if ($studentMenuCategory[$i] == $order)
                                    $countEntries ++;
                            }

                            if ($countEntries > 0) {
                                $_SESSION[$guid]['sidebarExtra'] .= '<h4>'.__($guid, $order).'</h4>';
                                $_SESSION[$guid]['sidebarExtra'] .= "<ul class='moduleMenu'>";
                                for ($i = 0; $i < count($studentMenuCategory); ++$i) {
                                    if ($studentMenuCategory[$i] == $order)
                                    $_SESSION[$guid]['sidebarExtra'] .= $studentMenuLink[$i];
                                }

                                $_SESSION[$guid]['sidebarExtra'] .= '</ul>';
                            }
                        }
                    }
                }
            }
        }
    }
}
?>
