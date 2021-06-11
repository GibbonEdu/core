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
use Gibbon\Domain\DataSet;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Tables\View\GridView;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Domain\School\HouseGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\School\YearGroupGateway;
use Gibbon\Domain\Students\MedicalGateway;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Domain\School\SchoolYearGateway;
use Gibbon\Domain\FormGroups\FormGroupGateway;
use Gibbon\Domain\Planner\PlannerEntryGateway;
use Gibbon\Domain\Students\StudentNoteGateway;
use Gibbon\Domain\Library\LibraryReportGateway;
use Gibbon\Domain\User\PersonalDocumentGateway;
use Gibbon\Module\Planner\Tables\HomeworkTable;
use Gibbon\Module\Attendance\StudentHistoryData;
use Gibbon\Module\Attendance\StudentHistoryView;
use Gibbon\Module\Reports\Domain\ReportArchiveEntryGateway;

//Module includes for User Admin (for custom fields)
include './modules/User Admin/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Students/student_view_details.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $page->scripts->add('chart');

    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __('The highest grouped action cannot be determined.');
        echo '</div>';
        return;
    } else {
        $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
        $search = $_GET['search'] ?? '';
        $allStudents = $_GET['allStudents'] ?? '';
        $sort = $_GET['sort'] ?? '';

        if ($gibbonPersonID == '') {
            echo "<div class='error'>";
            echo __('You have not specified one or more required parameters.');
            echo '</div>';
            return;
        } else {
            $enableStudentNotes = getSettingByScope($connection2, 'Students', 'enableStudentNotes');
            $skipBrief = false;

            //Skip brief for those with _full or _fullNoNotes, and _brief
            if ($highestAction == 'View Student Profile_fullEditAllNotes' || $highestAction == 'View Student Profile_full' || $highestAction == 'View Student Profile_fullNoNotes') {
                $skipBrief = true;
            }

            //Test if View Student Profile_brief and View Student Profile_myChildren are both available and parent has access to this student...if so, skip brief, and go to full.
            if (isActionAccessible($guid, $connection2, '/modules/Students/student_view_details.php', 'View Student Profile_brief') and isActionAccessible($guid, $connection2, '/modules/Students/student_view_details.php', 'View Student Profile_myChildren')) {
                    $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID1' => $_GET['gibbonPersonID'], 'gibbonPersonID2' => $session->get('gibbonPersonID'));
                    $sql = "SELECT * FROM gibbonFamilyChild JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonFamilyChild.gibbonPersonID=:gibbonPersonID1 AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID2 AND childDataAccess='Y'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                if ($result->rowCount() == 1) {
                    $skipBrief = true;
                }
            }

            if (isActionAccessible($guid, $connection2, '/modules/Students/student_view_details.php', 'View Student Profile_my')) {
                if ($gibbonPersonID == $session->get('gibbonPersonID')) {
                    $skipBrief = true;
                } elseif (isActionAccessible($guid, $connection2, '/modules/Students/student_view_details.php', 'View Student Profile_brief')) {
                    $highestAction = 'View Student Profile_brief';
                } else {
                    //Acess denied
                    echo "<div class='error'>";
                    echo __('You do not have access to this action.');
                    echo '</div>';
                    return;
                }
            }

            if (isActionAccessible($guid, $connection2, '/modules/Students/student_view_details.php', 'View Student Profile_brief') and $skipBrief == false) {
                //Proceed!
                $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $gibbonPersonID);
                $sql = "SELECT * FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonPerson.gibbonPersonID=:gibbonPersonID";
                $result = $connection2->prepare($sql);
                $result->execute($data);

                if ($result->rowCount() != 1) {
                    echo "<div class='error'>";
                    echo __('The selected record does not exist, or you do not have access to it.');
                    echo '</div>';
                } else {
                    $row = $result->fetch();
                    $studentImage=$row['image_240'] ;

                    $page->breadcrumbs
                        ->add(__('View Student Profiles'), 'student_view.php')
                        ->add(Format::name('', $row['preferredName'], $row['surname'], 'Student'));

                    echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                    echo '<tr>';
                    echo "<td style='width: 33%; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__('Year Group').'</span><br/>';

                    $dataDetail = array('gibbonYearGroupID' => $row['gibbonYearGroupID']);
                    $sqlDetail = 'SELECT * FROM gibbonYearGroup WHERE gibbonYearGroupID=:gibbonYearGroupID';
                    $resultDetail = $connection2->prepare($sqlDetail);
                    $resultDetail->execute($dataDetail);
                    if ($resultDetail->rowCount() == 1) {
                        $rowDetail = $resultDetail->fetch();
                        echo __($rowDetail['name']);
                    }
                    echo '</td>';
                    echo "<td style='width: 34%; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__('Form Group').'</span><br/>';

                    $dataDetail = array('gibbonFormGroupID' => $row['gibbonFormGroupID']);
                    $sqlDetail = 'SELECT * FROM gibbonFormGroup WHERE gibbonFormGroupID=:gibbonFormGroupID';
                    $resultDetail = $connection2->prepare($sqlDetail);
                    $resultDetail->execute($dataDetail);
                    if ($resultDetail->rowCount() == 1) {
                        $rowDetail = $resultDetail->fetch();
                        echo $rowDetail['name'];
                    }
                    echo '</td>';
                    echo "<td style='width: 34%; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__('House').'</span><br/>';

                    $dataDetail = array('gibbonHouseID' => $row['gibbonHouseID']);
                    $sqlDetail = 'SELECT * FROM gibbonHouse WHERE gibbonHouseID=:gibbonHouseID';
                    $resultDetail = $connection2->prepare($sqlDetail);
                    $resultDetail->execute($dataDetail);
                    if ($resultDetail->rowCount() == 1) {
                        $rowDetail = $resultDetail->fetch();
                        echo $rowDetail['name'];
                    }
                    echo '</td>';
                    echo '</tr>';
                    echo '<tr>';
                    echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__('Email').'</span><br/>';
                    if ($row['email'] != '') {
                        echo "<i><a href='mailto:".$row['email']."'>".$row['email'].'</a></i>';
                    }
                    echo '</td>';
                    echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                    echo "<span style='font-size: 115%; font-weight: bold'>".__('Website').'</span><br/>';
                    if ($row['website'] != '') {
                        echo "<i><a href='".$row['website']."'>".$row['website'].'</a></i>';
                    }
                    echo '</td>';
                    echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'></td>";
                    echo '</tr>';
                    echo '</table>';

                    //Set sidebar
                    $session->set('sidebarExtra', getUserPhoto($guid, $row['image_240'], 240));
                }
                return;
            } else {
                try {
                    if ($highestAction == 'View Student Profile_myChildren') {
                        $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID1' => $_GET['gibbonPersonID'], 'gibbonPersonID2' => $session->get('gibbonPersonID'), 'today' => date('Y-m-d'));
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
                    } elseif ($highestAction == 'View Student Profile_my') {
                        $gibbonPersonID = $session->get('gibbonPersonID');
                        $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $gibbonPersonID, 'today' => date('Y-m-d'));
                        $sql = "SELECT gibbonPerson.*, gibbonStudentEnrolment.* FROM gibbonPerson
                            LEFT JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                            WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID
                            AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full'
                            AND (dateStart IS NULL OR dateStart<=:today) AND (dateEnd IS NULL OR dateEnd>=:today)";
                    } elseif ($highestAction == 'View Student Profile_fullEditAllNotes' || $highestAction == 'View Student Profile_full' || $highestAction == 'View Student Profile_fullNoNotes') {
                        if ($allStudents != 'on') {
                            $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $gibbonPersonID, 'today' => date('Y-m-d'));
                            $sql = "SELECT * FROM gibbonPerson
                                JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                                WHERE gibbonSchoolYearID=:gibbonSchoolYearID
                                AND gibbonPerson.gibbonPersonID=:gibbonPersonID AND status='Full'
                                AND (dateStart IS NULL OR dateStart<=:today) AND (dateEnd IS NULL  OR dateEnd>=:today) ";
                        } else {
                            $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
                            $sql = "SELECT gibbonStudentEnrolment.*, gibbonPerson.* FROM gibbonPerson
                                LEFT JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID)
                                WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID";
                        }
                    } else {
                        //Acess denied
                        echo "<div class='error'>";
                        echo __('You do not have access to this action.');
                        echo '</div>';
                        return;
                    }
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    echo "<div class='error'>".$e->getMessage().'</div>';
                    return;
                }

                if ($result->rowCount() != 1) {
                    echo "<div class='error'>";
                    echo __('The selected record does not exist, or you do not have access to it.');
                    echo '</div>';
                    return;
                } else {
                    $row = $result->fetch();
                    $studentImage=$row['image_240'] ;

                    $page->breadcrumbs
                    ->add(__('View Student Profiles'), 'student_view.php')
                    ->add(Format::name('', $row['preferredName'], $row['surname'], 'Student'));

                    $subpage = $_GET['subpage'] ?? '';
                    $hook = $_GET['hook'] ?? '';
                    $module = $_GET['module'] ?? '';
                    $action = $_GET['action'] ?? '';

                    // When viewing left students, they won't have a year group ID
                    if (empty($row['gibbonYearGroupID'])) {
                        $row['gibbonYearGroupID'] = '';
                    }

                    if ($subpage == '' and ($hook == '' or $module == '' or $action == '')) {
                        $subpage = 'Overview';
                    }

                    if ($search != '' or $allStudents != '') {
                        echo "<div class='linkTop'>";
                        echo "<a href='".$session->get('absoluteURL').'/index.php?q=/modules/Students/student_view.php&search='.$search."&allStudents=$allStudents'>".__('Back to Search Results').'</a>';
                        echo '</div>';
                    }

                    echo '<h2>';
                    if ($subpage == 'Homework') {
                        $homeworkNamePlural = getSettingByScope($connection2, 'Planner', 'homeworkNamePlural');
                        echo __($homeworkNamePlural);
                    } elseif ($subpage != '') {
                        echo __($subpage);
                    } else {
                        echo $hook;
                    }
                    echo '</h2>';

                    if ($subpage == 'Overview') {
                        if (isActionAccessible($guid, $connection2, '/modules/User Admin/user_manage.php') == true) {
                            echo "<div class='linkTop'>";
                            echo "<a href='".$session->get('absoluteURL')."/index.php?q=/modules/User Admin/user_manage_edit.php&gibbonPersonID=$gibbonPersonID'>".__('Edit')."<img style='margin: 0 0 -4px 5px' title='".__('Edit')."' src='./themes/".$session->get('gibbonThemeName')."/img/config.png'/></a> ";
                            echo '</div>';
                        }

                        echo '<h4>';
                        echo __('General Information');
                        echo '</h4>';

                        //Medical alert!
                        $alert = getHighestMedicalRisk($guid, $gibbonPersonID, $connection2);
                        if ($alert != false) {
                            $highestLevel = $alert[1];
                            $highestColour = $alert[3];
                            $highestColourBG = $alert[4];
                            echo "<div class='error' style='background-color: #".$highestColourBG.'; border: 1px solid #'.$highestColour.'; color: #'.$highestColour."'>";
                            echo '<b>'.sprintf(__('This student has one or more %1$s risk medical conditions.'), strToLower(__($highestLevel))).'</b>';
                            echo '</div>';
                        }

                        echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                        echo '<tr>';
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Preferred Name').'</span><br/>';
                        echo Format::name('', $row['preferredName'], $row['surname'], 'Student');
                        echo '</td>';
                        echo "<td style='width: 34%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Official Name').'</span><br/>';
                        echo $row['officialName'];
                        echo '</td>';
                        echo "<td style='width: 34%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Name In Characters').'</span><br/>';
                        echo $row['nameInCharacters'];
                        echo '</td>';
                        echo '</tr>';
                        echo '<tr>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Year Group').'</span><br/>';
                        if (isset($row['gibbonYearGroupID'])) {

                                $dataDetail = array('gibbonYearGroupID' => $row['gibbonYearGroupID']);
                                $sqlDetail = 'SELECT * FROM gibbonYearGroup WHERE gibbonYearGroupID=:gibbonYearGroupID';
                                $resultDetail = $connection2->prepare($sqlDetail);
                                $resultDetail->execute($dataDetail);
                            if ($resultDetail->rowCount() == 1) {
                                $rowDetail = $resultDetail->fetch();
                                echo __($rowDetail['name']);
                                $dayTypeOptions = getSettingByScope($connection2, 'User Admin', 'dayTypeOptions');
                                if (!empty($dayTypeOptions) && !empty($row['dayType'])) {
                                    echo ' ('.$row['dayType'].')';
                                }
                                echo '</i><br/>';
                            }
                        }
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Form Group').'</span><br/>';
                        if (isset($row['gibbonFormGroupID'])) {

                                $dataDetail = array('gibbonFormGroupID' => $row['gibbonFormGroupID']);
                                $sqlDetail = 'SELECT * FROM gibbonFormGroup WHERE gibbonFormGroupID=:gibbonFormGroupID';
                                $resultDetail = $connection2->prepare($sqlDetail);
                                $resultDetail->execute($dataDetail);
                            if ($resultDetail->rowCount() == 1) {
                                $rowDetail = $resultDetail->fetch();
                                if (isActionAccessible($guid, $connection2, '/modules/Form Groups/formGroups_details.php')) {
                                    echo "<a href='".$session->get('absoluteURL').'/index.php?q=/modules/Form Groups/formGroups_details.php&gibbonFormGroupID='.$rowDetail['gibbonFormGroupID']."'>".$rowDetail['name'].'</a>';
                                } else {
                                    echo $rowDetail['name'];
                                }
                                $primaryTutor = $rowDetail['gibbonPersonIDTutor'];
                            }
                        }
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Tutors').'</span><br/>';
                        if (isset($rowDetail['gibbonPersonIDTutor'])) {

                                $dataDetail = array('gibbonFormGroupID' => $row['gibbonFormGroupID']);
                                $sqlDetail = 'SELECT gibbonPersonID, title, surname, preferredName FROM gibbonFormGroup JOIN gibbonPerson ON (gibbonFormGroup.gibbonPersonIDTutor=gibbonPerson.gibbonPersonID OR gibbonFormGroup.gibbonPersonIDTutor2=gibbonPerson.gibbonPersonID OR gibbonFormGroup.gibbonPersonIDTutor3=gibbonPerson.gibbonPersonID) WHERE gibbonFormGroupID=:gibbonFormGroupID ORDER BY surname, preferredName';
                                $resultDetail = $connection2->prepare($sqlDetail);
                                $resultDetail->execute($dataDetail);
                            while ($rowDetail = $resultDetail->fetch()) {
                                if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_view_details.php')) {
                                    echo "<a href='".$session->get('absoluteURL').'/index.php?q=/modules/Staff/staff_view_details.php&gibbonPersonID='.$rowDetail['gibbonPersonID']."'>".Format::name('', $rowDetail['preferredName'], $rowDetail['surname'], 'Staff', false, true).'</a>';
                                } else {
                                    echo Format::name($rowDetail['title'], $rowDetail['preferredName'], $rowDetail['surname'], 'Staff');
                                }
                                if ($rowDetail['gibbonPersonID'] == $primaryTutor and $resultDetail->rowCount() > 1) {
                                    echo ' ('.__('Main Tutor').')';
                                }
                                echo '<br/>';
                            }
                        }
                        echo '</td>';
                        echo '</tr>';
                        echo '<tr>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Username').'</span><br/>';
                        echo $row['username'];
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Age').'</span><br/>';
                        if (is_null($row['dob']) == false and $row['dob'] != '0000-00-00') {
                            echo Format::age($row['dob']);
                        }
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";

                            $dataDetail = array('gibbonYearGroupID' => $row['gibbonYearGroupID']);
                            $sqlDetail = "SELECT DISTINCT gibbonPersonID, title, surname, preferredName FROM gibbonPerson JOIN gibbonYearGroup ON (gibbonYearGroup.gibbonPersonIDHOY=gibbonPersonID) WHERE status='Full' AND gibbonYearGroupID=:gibbonYearGroupID";
                            $resultDetail = $connection2->prepare($sqlDetail);
                            $resultDetail->execute($dataDetail);
                        if ($resultDetail->rowCount() == 1) {
                            echo "<span style='font-size: 115%; font-weight: bold;'>".__('Head of Year').'</span><br/>';
                            $rowDetail = $resultDetail->fetch();
                            if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_view_details.php')) {
                                echo "<a href='".$session->get('absoluteURL').'/index.php?q=/modules/Staff/staff_view_details.php&gibbonPersonID='.$rowDetail['gibbonPersonID']."'>".Format::name('', $rowDetail['preferredName'], $rowDetail['surname'], 'Staff', false, true).'</a>';
                            } else {
                                echo Format::name($rowDetail['title'], $rowDetail['preferredName'], $rowDetail['surname'], 'Staff');
                            }
                            echo '<br/>';
                        }
                        echo '</td>';
                        echo '</tr>';
                        echo '<tr>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Website').'</span><br/>';
                        if ($row['website'] != '') {
                            echo "<i><a href='".$row['website']."'>".$row['website'].'</a></i>';
                        }
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Email').'</span><br/>';
                        if ($row['email'] != '') {
                            echo "<i><a href='mailto:".$row['email']."'>".$row['email'].'</a></i>';
                        }
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('School History').'</span><br/>';
                        if ($row['dateStart'] != '') {
                            echo '<u>'.__('Start Date').'</u>: '.Format::date($row['dateStart']).'</br>';
                        }

                            $dataSelect = array('gibbonPersonID' => $row['gibbonPersonID']);
                            $sqlSelect = "SELECT gibbonFormGroup.name AS formGroup, gibbonSchoolYear.name AS schoolYear
                                FROM gibbonStudentEnrolment
                                JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID)
                                JOIN gibbonSchoolYear ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
                                WHERE gibbonPersonID=:gibbonPersonID
                                AND (gibbonSchoolYear.status = 'Current' OR gibbonSchoolYear.status='Past')
                                ORDER BY gibbonStudentEnrolment.gibbonSchoolYearID";
                            $resultSelect = $connection2->prepare($sqlSelect);
                            $resultSelect->execute($dataSelect);
                        while ($rowSelect = $resultSelect->fetch()) {
                            echo '<u>'.$rowSelect['schoolYear'].'</u>: '.$rowSelect['formGroup'].'<br/>';
                        }
                        if ($row['dateEnd'] != '') {
                            echo '<u>'.__('End Date').'</u>: '.Format::date($row['dateEnd']).'</br>';
                        }
                        echo '</td>';
                        echo '</tr>';
                        echo '<tr>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Locker Number').'</span><br/>';
                        if ($row['lockerNumber'] != '') {
                            echo $row['lockerNumber'];
                        }
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Student ID').'</span><br/>';
                        if ($row['studentID'] != '') {
                            echo $row['studentID'];
                        }
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('House').'</span><br/>';

                            $dataDetail = array('gibbonHouseID' => $row['gibbonHouseID']);
                            $sqlDetail = 'SELECT * FROM gibbonHouse WHERE gibbonHouseID=:gibbonHouseID';
                            $resultDetail = $connection2->prepare($sqlDetail);
                            $resultDetail->execute($dataDetail);
                        if ($resultDetail->rowCount() == 1) {
                            $rowDetail = $resultDetail->fetch();
                            echo $rowDetail['name'];
                        }
                        echo '</td>';
                        echo '</tr>';
                        $privacySetting = getSettingByScope($connection2, 'User Admin', 'privacy');
                        if ($privacySetting == 'Y') {
                            echo '<tr>';
                            echo "<td style='width: 33%; padding-top: 15px; vertical-align: top' colspan=3>";
                            echo "<span style='font-size: 115%; font-weight: bold'>".__('Privacy').'</span><br/>';
                            if ($row['privacy'] != '') {
                                echo "<span style='color: #cc0000; background-color: #F6CECB'>";
                                echo __('Privacy required:').' '.$row['privacy'];
                                echo '</span>';
                            } else {
                                echo "<span style='color: #390; background-color: #D4F6DC;'>";
                                echo __('Privacy not required or not set.');
                                echo '</span>';
                            }

                            echo '</td>';
                            echo '</tr>';
                        }
                        $studentAgreementOptions = getSettingByScope($connection2, 'School Admin', 'studentAgreementOptions');
                        if ($studentAgreementOptions != '') {
                            echo '<tr>';
                            echo "<td style='width: 33%; padding-top: 15px; vertical-align: top' colspan=3>";
                            echo "<span style='font-size: 115%; font-weight: bold'>".__('Student Agreements').'</span><br/>';
                            echo __('Agreements Signed:').' '.$row['studentAgreements'];
                            echo '</td>';
                            echo '</tr>';
                        }
                        echo '</table>';

                        //Get and display a list of student's teachers
                        $studentGateway = $container->get(StudentGateway::class);
                        $staff = $studentGateway->selectAllRelatedUsersByStudent($gibbon->session->get('gibbonSchoolYearID'), $row['gibbonYearGroupID'], $row['gibbonFormGroupID'], $gibbonPersonID)->fetchAll();
                        $criteria = $studentGateway->newQueryCriteria();

                        if ($staff) {
                            echo '<h4>';
                            echo __('Teachers Of {student}', ['student' => $row['preferredName']]);
                            echo '</h4>';
                            echo '<p>';
                            echo __('Includes Teachers, Tutors, Educational Assistants and Head of Year.');
                            echo '</p>';

                            $table = DataTable::createPaginated('staffView', $criteria);
                            $table->addMetaData('listOptions', [
                                'list' => __('List'),
                                'grid' => __('Grid'),
                            ]);

                            $view = $_GET['view'] ?? 'grid';
                            if ($view == 'grid') {
                                $table->setRenderer(new GridView($container->get('twig')));
                                $table->getRenderer()->setCriteria($criteria);

                                $table->addMetaData('gridClass', 'rounded-sm bg-gray-100 border');
                                $table->addMetaData('gridItemClass', 'w-1/2 sm:w-1/4 md:w-1/5 my-4 text-center text-xs');

                                $table->addColumn('image_240', __('Photo'))
                                    ->context('primary')
                                    ->format(function ($person) {
                                        $url = './index.php?q=/modules/Staff/staff_view_details.php&gibbonPersonID='.$person['gibbonPersonID'];
                                        return Format::link($url, Format::userPhoto($person['image_240'], 'sm'));
                                    });

                                $table->addColumn('fullName', __('Name'))
                                    ->context('primary')
                                    ->sortable(['surname', 'preferredName'])
                                    ->width('20%')
                                    ->format(function ($person) {
                                        $text = Format::name('', $person['preferredName'], $person['surname'], 'Staff', false, true);
                                        $url = './index.php?q=/modules/Staff/staff_view_details.php&gibbonPersonID='.$person['gibbonPersonID'];
                                        return Format::link($url, $text, ['class' => 'font-bold underline leading-normal']);
                                    });
                            } else {
                                $table->addColumn('fullName', __('Name'))
                                    ->notSortable()
                                    ->format(function ($person) {
                                        return Format::name('', $person['preferredName'], $person['surname'], 'Staff', false, true);
                                    });
                                $table->addColumn('email', __('Email'))
                                    ->notSortable()
                                    ->format(function ($person) {
                                        return htmlPrep('<'.$person['email'].'>');
                                    });
                            }

                            $table->addColumn('context', __('Context'))
                                ->notSortable()
                                ->format(function ($person) use ($view) {
                                    $class = $view == 'grid'? 'unselectable text-xxs italic text-gray-800' : 'unselectable';
                                    if (!empty($person['classID'])) {
                                        return Format::link('./index.php?q=/modules/Departments/department_course_class.php&gibbonCourseClassID='.$person['classID'], __($person['type']), ['class' => $class.' underline']);
                                    } else {
                                        return '<span class="'.$class.'">'.__($person['type']).'</span>';
                                    }
                                });

                            echo $table->render(new DataSet($staff));
                        }


                        //Show timetable
                        echo "<a name='timetable'></a>";
                        //Display timetable if available, otherwise just list classes
                        if (isActionAccessible($guid, $connection2, '/modules/Timetable/tt_view.php') == true) {
                            echo '<h4>';
                            echo __('Timetable');
                            echo '</h4>';

                            if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php') == true) {
                                $role = getRoleCategory($row['gibbonRoleIDPrimary'], $connection2);
                                if ($role == 'Student' or $role == 'Staff') {
                                    echo "<div class='linkTop'>";
                                    echo "<a href='".$session->get('absoluteURL')."/index.php?q=/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php&gibbonPersonID=$gibbonPersonID&gibbonSchoolYearID=".$session->get('gibbonSchoolYearID')."&type=$role&allUsers=$allStudents'>".__('Edit')."<img style='margin: 0 0 -4px 5px' title='".__('Edit')."' src='./themes/".$session->get('gibbonThemeName')."/img/config.png'/></a> ";
                                    echo '</div>';
                                }
                            }

                            include './modules/Timetable/moduleFunctions.php';
                            $ttDate = null;
                            if (isset($_POST['ttDate'])) {
                                $ttDate = Format::timestamp(Format::dateConvert($_POST['ttDate']));
                            }
                            $tt = renderTT($guid, $connection2, $gibbonPersonID, $_GET['gibbonTTID'] ?? '', false, $ttDate, '/modules/Students/student_view_details.php', "&gibbonPersonID=$gibbonPersonID&search=$search&allStudents=$allStudents#timetable");
                            if ($tt != false) {
                                $page->addData('preventOverflow', false);
                                echo $tt;
                            } else {
                                echo "<div class='error'>";
                                echo __('There are no records to display.');
                                echo '</div>';
                            }
                        } else {
                            echo '<h4>';
                            echo __('Class List');
                            echo '</h4>';

                                $dataDetail = array('gibbonPersonID' => $gibbonPersonID);
                                $sqlDetail = "SELECT DISTINCT gibbonCourse.name AS courseFull, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class
                                    FROM gibbonCourseClassPerson
                                        JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                                        JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                                    WHERE gibbonCourseClassPerson.role='Student' AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND gibbonCourse.gibbonSchoolYearID=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE status='Current') ORDER BY course, class";
                                $resultDetail = $connection2->prepare($sqlDetail);
                                $resultDetail->execute($dataDetail);
                            if ($resultDetail->rowCount() < 1) {
                                echo "<div class='error'>";
                                echo __('There are no records to display.');
                                echo '</div>';
                            } else {
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
                        $schoolYearGateway = $container->get(SchoolYearGateway::class);
                        $yearGroupGateway = $container->get(YearGroupGateway::class);
                        $formGroupGateway = $container->get(FormGroupGateway::class);
                        $studentGateway = $container->get(StudentGateway::class);

                        $student = $studentGateway->selectActiveStudentByPerson($gibbon->session->get('gibbonSchoolYearID'), $gibbonPersonID, false)->fetch();
                        $tutors = $formGroupGateway->selectTutorsByFormGroup($student['gibbonFormGroupID'] ?? '')->fetchAll();
                        $yearGroup = $yearGroupGateway->getByID($student['gibbonYearGroupID'] ?? '', ['name', 'gibbonPersonIDHOY']);
                        $headOfYear = $container->get(UserGateway::class)->getByID($yearGroup['gibbonPersonIDHOY'] ?? '', ['title', 'surname', 'preferredName', 'gibbonPersonID']);
                        $house = $container->get(HouseGateway::class)->getByID($row['gibbonHouseID'] ?? '', ['name']);

                        $table = DataTable::createDetails('overview');

                        if (isActionAccessible($guid, $connection2, '/modules/User Admin/user_manage.php')) {
                            $table->addHeaderAction('edit', __('Edit User'))
                                ->setURL('/modules/User Admin/user_manage_edit.php')
                                ->addParam('gibbonPersonID', $gibbonPersonID)
                                ->displayLabel();
                        }

                        $col = $table->addColumn('Basic Information');

                        $col->addColumn('surname', __('Surname'));
                        $col->addColumn('firstName', __('First Name'))->addClass('col-span-2');
                        $col->addColumn('preferredName', __('Preferred Name'));
                        $col->addColumn('officialName', __('Official Name'));
                        $col->addColumn('nameInCharacters', __('Name In Characters'));
                        $col->addColumn('gender', __('Gender'))
                                ->format(Format::using('genderName', 'gender'));
                        $col->addColumn('dob', __('Date of Birth'))->format(Format::using('date', 'dob'));
                        $col->addColumn('age', __('Age'))->format(Format::using('age', 'dob'));

                        $col = $table->addColumn('Contact Information', __('Contact Information'));

                        for ($i = 1; $i <= 4; $i++) {
                            if (empty($row["phone$i"])) continue;
                            $col->addColumn("phone$i", __('Phone '.$i))->format(Format::using('phone', ["phone{$i}", "phone{$i}CountryCode", "phone{$i}Type"]));
                        }
                        $col->addColumn('email', __('Email'))->format(Format::using('link', 'email'));
                        $col->addColumn('emailAlternate', __('Alternate Email'))->format(Format::using('link', 'emailAlternate'));
                        $col->addColumn('website', __('Website'))->format(Format::using('link', 'website'));

                        $col = $table->addColumn('School Information', __('School Information'));

                        if (!empty($student)) {
                            $col->addColumn('yearGroup', __('Year Group'))->format(function ($values) use ($student) {
                                return $student['yearGroupName'];
                            });
                            $col->addColumn('gibbonFormGroupID', __('Form Group'))->format(function ($values) use ($student) {
                                return Format::link('./index.php?q=/modules/Form Groups/formGroups_details.php&gibbonFormGroupID='.$student['gibbonFormGroupID'], $student['formGroupName']);
                            });
                        }
                        $col->addColumn('email', __('Tutors'))->format(function ($values) use ($tutors) {
                            if (count($tutors) > 1) $tutors[0]['surname'] .= ' ('.__('Main Tutor').')';
                            return Format::nameList($tutors, 'Staff', false, true);
                        });
                        $col->addColumn('gibbonHouseID', __('House'))->format(function ($values) use ($house) {
                            return !empty($house['name']) ? $house['name'] : '';
                        });
                        $col->addColumn('studentID', __('Student ID'));
                        $col->addColumn('headOfYear', __('Head of Year'))->format(function ($values) use ($headOfYear) {
                            return !empty($headOfYear)
                                ? Format::nameLinked($headOfYear['gibbonPersonID'], '', $headOfYear['preferredName'], $headOfYear['surname'], 'Staff')
                                : '';
                        });

                        $col->addColumn('lastSchool', __('Last School'));
                        $col->addColumn('dateStart', __('Start Date'))->format(Format::using('date', 'dateStart'));
                        $col->addColumn('classOf', __('Class Of'))->format(function ($values) use ($schoolYearGateway) {
                            if (empty($values['gibbonSchoolYearIDClassOf'])) return Format::small(__('N/A'));
                            $schoolYear = $schoolYearGateway->getByID($values['gibbonSchoolYearIDClassOf'], ['name']);
                            return $schoolYear['name'] ?? '';
                        });
                        $col->addColumn('nextSchool', __('Next School'));
                        $col->addColumn('dateEnd', __('End Date'))->format(Format::using('date', 'dateEnd'));
                        $col->addColumn('departureReason', __('Departure Reason'));

                        $col = $table->addColumn('Background Information', __('Background Information'));
                        $country = $gibbon->session->get('country');

                        $col->addColumn('countryOfBirth', __('Country of Birth'))->translatable();
                        $col->addColumn('ethnicity', __('Ethnicity'));
                        $col->addColumn('religion', __('Religion'));

                        $col->addColumn('languageFirst', __('First Language'));
                        $col->addColumn('languageSecond', __('Second Language'));
                        $col->addColumn('languageThird', __('Third Language'));

                        $col = $table->addColumn('System Access', __('System Access'));

                        $col->addColumn('username', __('Username'));
                        $col->addColumn('canLogin', __('Can Login?'))->format(Format::using('yesNo', 'canLogin'));
                        $col->addColumn('lastIPAddress', __('Last IP Address'));

                        $col = $table->addColumn('Miscellaneous', __('Miscellaneous'));

                        $col->addColumn('transport', __('Transport'))->format(function ($values) {
                            $output = $values['transport'];
                            if (!empty($values['transportNotes'])) {
                                $output .= '<br/>'.$values['transportNotes'];
                            }
                            return $output;
                        });
                        $col->addColumn('vehicleRegistration', __('Vehicle Registration'));
                        $col->addColumn('lockerNumber', __('Locker Number'));

                        $privacySetting = getSettingByScope($connection2, 'User Admin', 'privacy');
                        if ($privacySetting == 'Y') {
                            $col->addColumn('privacy', __('Privacy'))->format(function ($values) {
                                if (!empty($values['privacy'])) {
                                    return Format::tag(__('Privacy required:').' '.$values['privacy'], 'error');
                                } else {
                                    return Format::tag(__('Privacy not required or not set.'), 'success');
                                }
                            });
                        }
                        $studentAgreementOptions = getSettingByScope($connection2, 'School Admin', 'studentAgreementOptions');
                        if (!empty($studentAgreementOptions)) {
                            $col->addColumn('studentAgreements', __('Student Agreements:'))->format(function ($values) {
                                return __('Agreements Signed:') .' '.$values['studentAgreements'];
                            });
                        }

                        // CUSTOM FIELDS
                        $container->get(CustomFieldHandler::class)->addCustomFieldsToTable($table, 'User', ['student' => 1], $row['fields']);
                        echo $table->render([$row]);

                        // PERSONAL DOCUMENTS
                        if (isActionAccessible($guid, $connection2, '/modules/Students/report_student_personalDocumentSummary.php')) {
                            $params = ['student' => true, 'notEmpty' => true];
                            $documents = $container->get(PersonalDocumentGateway::class)->selectPersonalDocuments('gibbonPerson', $gibbonPersonID, $params)->fetchAll();

                            echo $page->fetchFromTemplate('ui/personalDocuments.twig.html', ['documents' => $documents]);
                        }


                    } elseif ($subpage == 'Family') {

                            $dataFamily = array('gibbonPersonID' => $gibbonPersonID);
                            $sqlFamily = 'SELECT * FROM gibbonFamily JOIN gibbonFamilyChild ON (gibbonFamily.gibbonFamilyID=gibbonFamilyChild.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID';
                            $resultFamily = $connection2->prepare($sqlFamily);
                            $resultFamily->execute($dataFamily);

                        if ($resultFamily->rowCount() < 1) {
                            echo "<div class='error'>";
                            echo __('There are no records to display.');
                            echo '</div>';
                        } else {
                            while ($rowFamily = $resultFamily->fetch()) {
                                $count = 1;

                                if (isActionAccessible($guid, $connection2, '/modules/User Admin/family_manage.php') == true) {
                                    echo "<div class='linkTop'>";
                                    echo "<a href='".$session->get('absoluteURL').'/index.php?q=/modules/User Admin/family_manage_edit.php&gibbonFamilyID='.$rowFamily['gibbonFamilyID']."'>".__('Edit')."<img style='margin: 0 0 -4px 5px' title='".__('Edit')."' src='./themes/".$session->get('gibbonThemeName')."/img/config.png'/></a> ";
                                    echo '</div>';
                                } else {
                                    echo '<br/><br/>';
                                }

                                //Print family information
                                echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                                echo '<tr>';
                                echo "<td style='width: 33%; vertical-align: top'>";
                                echo "<span style='font-size: 115%; font-weight: bold'>".__('Family Name').'</span><br/>';
                                echo $rowFamily['name'];
                                echo '</td>';
                                echo "<td style='width: 33%; vertical-align: top'>";
                                echo "<span style='font-size: 115%; font-weight: bold'>".__('Family Status').'</span><br/>';
                                echo $rowFamily['status'];
                                echo '</td>';
                                echo "<td style='width: 34%; vertical-align: top' colspan=2>";
                                echo "<span style='font-size: 115%; font-weight: bold'>".__('Home Languages').'</span><br/>';
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
                                echo "<span style='font-size: 115%; font-weight: bold'>".__('Address Name').'</span><br/>';
                                echo $rowFamily['nameAddress'];
                                echo '</td>';
                                echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                                echo '</td>';
                                echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                                echo '</td>';
                                echo '</tr>';

                                echo '<tr>';
                                echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                                echo "<span style='font-size: 115%; font-weight: bold'>".__('Home Address').'</span><br/>';
                                echo $rowFamily['homeAddress'];
                                echo '</td>';
                                echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                                echo "<span style='font-size: 115%; font-weight: bold'>".__('Home Address (District)').'</span><br/>';
                                echo $rowFamily['homeAddressDistrict'];
                                echo '</td>';
                                echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                                echo "<span style='font-size: 115%; font-weight: bold'>".__('Home Address (Country)').'</span><br/>';
                                echo $rowFamily['homeAddressCountry'];
                                echo '</td>';
                                echo '</tr>';
                                echo '</table>';

                                //Get adults

                                    $dataMember = array('gibbonFamilyID' => $rowFamily['gibbonFamilyID']);
                                    $sqlMember = 'SELECT * FROM gibbonFamilyAdult JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID ORDER BY contactPriority, surname, preferredName';
                                    $resultMember = $connection2->prepare($sqlMember);
                                    $resultMember->execute($dataMember);

                                while ($rowMember = $resultMember->fetch()) {
                                    $class='';
                                    if ($rowMember['status'] != 'Full') {
                                        $class = "class='error'";
                                    }
                                    echo '<h4>';
                                    echo __('Adult').' '.$count;
                                    echo '</h4>';
                                    echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                                    echo '<tr>';
                                    echo "<td $class style='width: 33%; vertical-align: top' rowspan=2>";
                                    echo getUserPhoto($guid, $rowMember['image_240'], 75);
                                    echo '</td>';
                                    echo "<td $class style='width: 33%; vertical-align: top'>";
                                    echo "<span style='font-size: 115%; font-weight: bold'>".__('Name').'</span><br/>';
                                    echo Format::name($rowMember['title'], $rowMember['preferredName'], $rowMember['surname'], 'Parent');
                                    if ($rowMember['status'] != 'Full') {
                                        echo "<span style='font-weight: normal; font-style: italic'> (".$rowMember['status'].')</span>';
                                    }
                                    echo "<div style='font-size: 85%; font-style: italic'>";

                                        $dataRelationship = array('gibbonPersonID1' => $rowMember['gibbonPersonID'], 'gibbonPersonID2' => $gibbonPersonID, 'gibbonFamilyID' => $rowFamily['gibbonFamilyID']);
                                        $sqlRelationship = 'SELECT * FROM gibbonFamilyRelationship WHERE gibbonPersonID1=:gibbonPersonID1 AND gibbonPersonID2=:gibbonPersonID2 AND gibbonFamilyID=:gibbonFamilyID';
                                        $resultRelationship = $connection2->prepare($sqlRelationship);
                                        $resultRelationship->execute($dataRelationship);
                                    if ($resultRelationship->rowCount() == 1) {
                                        $rowRelationship = $resultRelationship->fetch();
                                        echo $rowRelationship['relationship'];
                                    } else {
                                        echo '<i>'.__('Relationship Unknown').'</i>';
                                    }
                                    echo '</div>';
                                    echo '</td>';
                                    echo "<td $class style='width: 34%; vertical-align: top' colspan=2>";
                                    echo "<span style='font-size: 115%; font-weight: bold'>".__('Contact Priority').'</span><br/>';
                                    echo $rowMember['contactPriority'];
                                    echo '</td>';
                                    echo '</tr>';
                                    echo '<tr>';
                                    echo "<td $class style='width: 33%; padding-top: 15px; vertical-align: top'>";
                                    echo "<span style='font-size: 115%; font-weight: bold'>".__('First Language').'</span><br/>';
                                    echo $rowMember['languageFirst'];
                                    echo '</td>';
                                    echo "<td $class style='width: 33%; padding-top: 15px; vertical-align: top'>";
                                    echo "<span style='font-size: 115%; font-weight: bold'>".__('Second Language').'</span><br/>';
                                    echo $rowMember['languageSecond'];
                                    echo '</td>';
                                    echo '</tr>';
                                    echo '<tr>';
                                    echo "<td $class style='width: 33%; padding-top: 15px; width: 33%; vertical-align: top'>";
                                    echo "<span style='font-size: 115%; font-weight: bold'>".__('Contact By Phone').'</span><br/>';
                                    if ($rowMember['contactCall'] == 'N') {
                                        echo __('Do not contact by phone.');
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
                                    echo "<span style='font-size: 115%; font-weight: bold'>".__('Contact By SMS').'</span><br/>';
                                    if ($rowMember['contactSMS'] == 'N') {
                                        echo __('Do not contact by SMS.');
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
                                    echo "<span style='font-size: 115%; font-weight: bold'>".__('Contact By Email').'</span><br/>';
                                    if ($rowMember['contactEmail'] == 'N') {
                                        echo __('Do not contact by email.');
                                    } elseif ($rowMember['contactEmail'] == 'Y' and ($rowMember['email'] != '' or $rowMember['emailAlternate'] != '')) {
                                        if ($rowMember['email'] != '') {
                                            echo __('Email').": <a href='mailto:".$rowMember['email']."'>".$rowMember['email'].'</a><br/>';
                                        }
                                        if ($rowMember['emailAlternate'] != '') {
                                            echo __('Email')." 2: <a href='mailto:".$rowMember['emailAlternate']."'>".$rowMember['emailAlternate'].'</a><br/>';
                                        }
                                        echo '<br/>';
                                    }
                                    echo '</td>';
                                    echo '</tr>';
                                    echo '<tr>';
                                    echo "<td $class style='width: 33%; padding-top: 15px; vertical-align: top'>";
                                    echo "<span style='font-size: 115%; font-weight: bold'>".__('Profession').'</span><br/>';
                                    echo $rowMember['profession'];
                                    echo '</td>';
                                    echo "<td $class style='width: 33%; padding-top: 15px; vertical-align: top'>";
                                    echo "<span style='font-size: 115%; font-weight: bold'>".__('Employer').'</span><br/>';
                                    echo $rowMember['employer'];
                                    echo '</td>';
                                    echo "<td $class style='width: 33%; padding-top: 15px; vertical-align: top'>";
                                    echo "<span style='font-size: 115%; font-weight: bold'>".__('Job Title').'</span><br/>';
                                    echo $rowMember['jobTitle'];
                                    echo '</td>';
                                    echo '</tr>';

                                    echo '<tr>';
                                    echo "<td $class style='width: 33%; padding-top: 15px; vertical-align: top'>";
                                    echo "<span style='font-size: 115%; font-weight: bold'>".__('Vehicle Registration').'</span><br/>';
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
                                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Comment').'</span><br/>';
                                        echo $rowMember['comment'];
                                        echo '</td>';
                                        echo '</tr>';
                                    }
                                    echo '</table>';
                                    ++$count;
                                }

                                //Get siblings

                                    $dataMember = array('gibbonFamilyID' => $rowFamily['gibbonFamilyID'], 'gibbonPersonID' => $gibbonPersonID, 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
                                    $sqlMember = 'SELECT gibbonPerson.gibbonPersonID, image_240, preferredName, surname, status, gibbonStudentEnrolmentID FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID) WHERE gibbonFamilyID=:gibbonFamilyID AND NOT gibbonPerson.gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName';
                                    $resultMember = $connection2->prepare($sqlMember);
                                    $resultMember->execute($dataMember);

                                if ($resultMember->rowCount() > 0) {
                                    echo '<h4>';
                                    echo __('Siblings');
                                    echo '</h4>';

                                    echo "<table class='smallIntBorder' cellspacing='0' style='width:100%'>";
                                    $count = 0;
                                    $columns = 3;
                                    $highlightClass = '';

                                    while ($rowMember = $resultMember->fetch()) {
                                        if ($count % $columns == 0) {
                                            echo '<tr>';
                                        }
                                        $highlightClass = $rowMember['status'] != 'Full'? 'error' : '';
                                        echo "<td style='width:30%; text-align: left; vertical-align: top' class='".$highlightClass."'>";
                                        //User photo
                                        echo getUserPhoto($guid, $rowMember['image_240'], 75);
                                        echo "<div style='padding-top: 5px'><b>";

                                        if ($rowMember['gibbonStudentEnrolmentID'] == null) {
                                            $allStudents = 'on';
                                        }

                                        echo "<a href='index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=".$rowMember['gibbonPersonID']."&allStudents=".$allStudents."'>".Format::name('', $rowMember['preferredName'], $rowMember['surname'], 'Student').'</a><br/>';

                                        echo "<span style='font-weight: normal; font-style: italic'>".__('Status').': '.$rowMember['status'].'</span>';
                                        echo '</div>';
                                        echo '</td>';

                                        if ($count % $columns == ($columns - 1)) {
                                            echo '</tr>';
                                        }
                                        ++$count;
                                    }

                                    for ($i = 0; $i < $columns - ($count % $columns); ++$i) {
                                        echo '<td class="'.$highlightClass.'"></td>';
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
                            echo "<a href='".$session->get('absoluteURL')."/index.php?q=/modules/User Admin/user_manage_edit.php&gibbonPersonID=$gibbonPersonID'>".__('Edit')."<img style='margin: 0 0 -4px 5px' title='".__('Edit')."' src='./themes/".$session->get('gibbonThemeName')."/img/config.png'/></a> ";
                            echo '</div>';
                        }

                        echo '<p>';
                        echo __('In an emergency, please try and contact the adult family members listed below first. If these cannot be reached, then try the emergency contacts below.');
                        echo '</p>';

                        echo '<h4>';
                        echo __('Adult Family Members');
                        echo '</h4>';


                            $dataFamily = array('gibbonPersonID' => $gibbonPersonID);
                            $sqlFamily = 'SELECT * FROM gibbonFamily JOIN gibbonFamilyChild ON (gibbonFamily.gibbonFamilyID=gibbonFamilyChild.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID';
                            $resultFamily = $connection2->prepare($sqlFamily);
                            $resultFamily->execute($dataFamily);

                        if ($resultFamily->rowCount() == 0) {
                            echo "<div class='error'>";
                            echo __('There are no records to display.');
                            echo '</div>';
                        } else {
                            while ($rowFamily = $resultFamily->fetch()) {
                                $count = 1;
                                //Get adults

                                $dataMember = array('gibbonFamilyID' => $rowFamily['gibbonFamilyID']);
                                $sqlMember = 'SELECT * FROM gibbonFamilyAdult JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID ORDER BY contactPriority, surname, preferredName';
                                $resultMember = $connection2->prepare($sqlMember);
                                $resultMember->execute($dataMember);

                                while ($rowMember = $resultMember->fetch()) {
                                    echo "<table class='smallIntBorder mb-2' cellspacing='0' style='width: 100%'>";
                                    echo '<tr>';
                                    echo "<td style='width: 33%; vertical-align: top'>";
                                    echo "<span style='font-size: 115%; font-weight: bold'>".__('Name').'</span><br/>';
                                    echo Format::name($rowMember['title'], $rowMember['preferredName'], $rowMember['surname'], 'Parent');
                                    echo '</td>';
                                    echo "<td style='width: 33%; vertical-align: top'>";
                                    echo "<span style='font-size: 115%; font-weight: bold'>".__('Relationship').'</span><br/>';

                                    $dataRelationship = array('gibbonPersonID1' => $rowMember['gibbonPersonID'], 'gibbonPersonID2' => $gibbonPersonID, 'gibbonFamilyID' => $rowFamily['gibbonFamilyID']);
                                    $sqlRelationship = 'SELECT * FROM gibbonFamilyRelationship WHERE gibbonPersonID1=:gibbonPersonID1 AND gibbonPersonID2=:gibbonPersonID2 AND gibbonFamilyID=:gibbonFamilyID';
                                    $resultRelationship = $connection2->prepare($sqlRelationship);
                                    $resultRelationship->execute($dataRelationship);
                                    if ($resultRelationship->rowCount() == 1) {
                                        $rowRelationship = $resultRelationship->fetch();
                                        echo $rowRelationship['relationship'];
                                    } else {
                                        echo '<i>'.__('Unknown').'</i>';
                                    }

                                    echo '</td>';
                                    echo "<td style='width: 34%; vertical-align: top'>";
                                    echo "<span style='font-size: 115%; font-weight: bold'>".__('Contact By Phone').'</span><br/>';
                                    for ($i = 1; $i < 5; ++$i) {
                                        if ($rowMember['phone'.$i] != '') {
                                            if ($rowMember['phone'.$i.'Type'] != '') {
                                                echo $rowMember['phone'.$i.'Type'].':</i> ';
                                            }
                                            if ($rowMember['phone'.$i.'CountryCode'] != '') {
                                                echo '+'.$rowMember['phone'.$i.'CountryCode'].' ';
                                            }
                                            echo __($rowMember['phone'.$i]).'<br/>';
                                        }
                                    }
                                    echo '</td>';
                                    echo '</tr>';
                                    echo '</table>';
                                    ++$count;
                                }
                            }
                        }

                        echo '<h4>';
                        echo __('Emergency Contacts');
                        echo '</h4>';
                        echo "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>";
                        echo '<tr>';
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Contact 1').'</span><br/>';
                        echo $row['emergency1Name'];
                        if ($row['emergency1Relationship'] != '') {
                            echo ' ('.$row['emergency1Relationship'].')';
                        }
                        echo '</td>';
                        echo "<td style='width: 33%; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Number 1').'</span><br/>';
                        echo $row['emergency1Number1'];
                        echo '</td>';
                        echo "<td style=width: 34%; 'vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Number 2').'</span><br/>';
                        if ($row['website'] != '') {
                            echo $row['emergency1Number2'];
                        }
                        echo '</td>';
                        echo '</tr>';
                        echo '<tr>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Contact 2').'</span><br/>';
                        echo $row['emergency2Name'];
                        if ($row['emergency2Relationship'] != '') {
                            echo ' ('.$row['emergency2Relationship'].')';
                        }
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Number 1').'</span><br/>';
                        echo $row['emergency2Number1'];
                        echo '</td>';
                        echo "<td style='width: 33%; padding-top: 15px; vertical-align: top'>";
                        echo "<span style='font-size: 115%; font-weight: bold'>".__('Number 2').'</span><br/>';
                        if ($row['website'] != '') {
                            echo $row['emergency2Number2'];
                        }
                        echo '</td>';
                        echo '</tr>';
                        echo '</table>';
                    } elseif ($subpage == 'Medical') {
                        $medicalGateway = $container->get(MedicalGateway::class);

                        $medical = $medicalGateway->getMedicalFormByPerson($gibbonPersonID);
                        $conditions = $medicalGateway->selectMedicalConditionsByID($medical['gibbonPersonMedicalID'] ?? null)->fetchAll();

                        //Medical alert!
                        $alert = getHighestMedicalRisk($guid, $gibbonPersonID, $connection2);
                        if ($alert != false) {
                            $highestLevel = $alert[1];
                            $highestColour = $alert[3];
                            $highestColourBG = $alert[4];
                            echo "<div class='error' style='background-color: #".$highestColourBG.'; border: 1px solid #'.$highestColour.'; color: #'.$highestColour."'>";
                            echo '<b>'.sprintf(__('This student has one or more %1$s risk medical conditions.'), strToLower($highestLevel)).'</b>';
                            echo '</div>';
                        }

                        // MEDICAL DETAILS
                        $table = DataTable::createDetails('medical');

                        if (isActionAccessible($guid, $connection2, '/modules/Students/medicalForm_manage.php')) {
                            if (empty($medical)) {
                                $table->addHeaderAction('add', __('Add Medical Form'))
                                    ->setURL('/modules/Students/medicalForm_manage_add.php')
                                    ->addParam('gibbonPersonID', $gibbonPersonID)
                                    ->addParam('search', $search)
                                    ->displayLabel();
                            } else {
                                $table->addHeaderAction('edit', __('Edit'))
                                    ->setURL('/modules/Students/medicalForm_manage_edit.php')
                                    ->addParam('gibbonPersonID', $gibbonPersonID)
                                    ->addParam('gibbonPersonMedicalID', $medical['gibbonPersonMedicalID'])
                                    ->addParam('search', $search)
                                    ->displayLabel();
                            }
                        }

                        $col = $table->addColumn('General Information');

                        $col->addColumn('longTermMedication', __('Long Term Medication'))
                            ->format(Format::using('yesno', 'longTermMedication'));

                        $col->addColumn('longTermMedicationDetails', __('Details'))
                            ->addClass('col-span-2')
                            ->format(function ($medical) {
                                return !empty($medical['longTermMedication'])
                                    ? $medical['longTermMedicationDetails']
                                    : Format::small(__('Unknown'));
                            });

                        $container->get(CustomFieldHandler::class)->addCustomFieldsToTable($table, 'Medical Form', [], $medical['fields'] ?? '', $table);

                        $col->addColumn('medicalConditions', __('Medical Conditions?'))
                            ->addClass('col-span-3')
                            ->format(function ($medical) use ($conditions) {
                                return count($conditions) > 0
                                    ? __('Yes').'. '.__('Details below.')
                                    : __('No');
                            });

                        if (!empty($medical['comment'])) {
                            $col->addColumn('comment', __('Comment'))->addClass('col-span-3');
                        }



                        $fields = !empty($medical['fields']) && is_string($medical['fields']) ? json_decode($medical['fields'], true) : [];
                        echo $table->render(!empty($medical) ? [$medical + $fields] : []);

                        // MEDICAL CONDITIONS
                        $canManageMedical = isActionAccessible($guid, $connection2, '/modules/Students/medicalForm_manage.php');

                        foreach ($conditions as $condition) {
                            $table = DataTable::createDetails('medicalConditions');
                            $table->setTitle(__($condition['name'])." <span style='color: ".$condition['alertColor']."'>(".__($condition['risk']).' '.__('Risk').')</span>');
                            $table->setDescription($condition['description']);
                            $table->addMetaData('gridClass', 'grid-cols-1 md:grid-cols-2');

                            $table->addColumn('triggers', __('Triggers'));
                            $table->addColumn('reaction', __('Reaction'));
                            $table->addColumn('response', __('Response'));
                            $table->addColumn('medication', __('Medication'));
                            $table->addColumn('lastEpisode', __('Last Episode Date'))
                                ->format(Format::using('date', 'lastEpisode'));
                            $table->addColumn('lastEpisodeTreatment', __('Last Episode Treatment'));
                            $table->addColumn('comment', __('Comments'))->addClass('col-span-2');

                            if ($canManageMedical && !empty($condition['attachment'])) {
                                $table->addColumn('attachment', __('Attachment'))
                                    ->addClass('col-span-2')
                                    ->format(function ($condition) {
                                        return Format::link('./'.$condition['attachment'], __('View Attachment'), ['target' => '_blank']);
                                    });
                            }

                            echo $table->render([$condition]);
                        }


                    } elseif ($subpage == 'Notes') {
                        if ($enableStudentNotes != 'Y') {
                            echo "<div class='error'>";
                            echo __('You do not have access to this action.');
                            echo '</div>';
                        } else {
                            if (isActionAccessible($guid, $connection2, '/modules/Students/student_view_details_notes_add.php') == false) {
                                echo "<div class='error'>";
                                echo __('Your request failed because you do not have access to this action.');
                                echo '</div>';
                            } else {
                                echo '<p>';
                                echo __('Student Notes provide a way to store information on students which does not fit elsewhere in the system, or which you want to be able to see quickly in one place.').' <b>'.__('Please remember that notes are visible to other users who have access to full student profiles (this should not generally include parents).').'</b>';
                                echo '</p>';

                                $categories = false;
                                $category = null;
                                if (isset($_GET['category'])) {
                                    $category = $_GET['category'];
                                }


                                    $dataCategories = array();
                                    $sqlCategories = "SELECT * FROM gibbonStudentNoteCategory WHERE active='Y' ORDER BY name";
                                    $resultCategories = $connection2->prepare($sqlCategories);
                                    $resultCategories->execute($dataCategories);
                                if ($resultCategories->rowCount() > 0) {
                                    $categories = true;

                                    $form = Form::create('filter', $session->get('absoluteURL').'/index.php', 'get');
                                    $form->setTitle(__('Filter'));
                                    $form->setClass('noIntBorder fullWidth');

                                    $form->addHiddenValue('q', '/modules/'.$session->get('module').'/student_view_details.php');
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

                                $notes = $pdo->select($sql, $data);
                                $noteGateway = $container->get(StudentNoteGateway::class);

                                // DATA TABLE
                                $table = DataTable::createPaginated('studentNotes', $noteGateway->newQueryCriteria(true));

                                $table->addExpandableColumn('note');

                                $table->addHeaderAction('add', __('Add'))
                                    ->setURL('/modules/Students/student_view_details_notes_add.php')
                                    ->addParam('gibbonPersonID', $gibbonPersonID)
                                    ->addParam('allStudents', $allStudents)
                                    ->addParam('search', $search)
                                    ->addParam('subpage', 'Notes')
                                    ->addParam('category', $category ?? '')
                                    ->displayLabel();

                                $table->addColumn('date', __('Date'))
                                    ->description(__('Time'))
                                    ->format(function ($note) {
                                        return Format::date($note['timestamp']).'<br/>'.Format::small(Format::time($note['timestamp']));
                                    });

                                $table->addColumn('category', __('Category'))
                                    ->translatable();

                                $table->addColumn('title', __('Title'))
                                    ->description(__('Overview'))
                                    ->format(function ($note) {
                                        $title = !empty($note['title'])? $note['title'] : __('N/A');
                                        $overview = substr(strip_tags($note['note']), 0, 60);

                                        return $title.'<br/><span style="font-size: 75%; font-style: italic">'.$overview.'</span>';
                                    });

                                $table->addColumn('noteTaker', __('Note Taker'))
                                      ->format(Format::using('name', ['', 'preferredName', 'surname', 'Staff', false, true]));

                                // ACTIONS
                                $table->addActionColumn()
                                    ->addParam('gibbonStudentNoteID')
                                    ->addParam('gibbonPersonID', $gibbonPersonID)
                                    ->addParam('allStudents', $allStudents)
                                    ->addParam('search', $search)
                                    ->addParam('subpage', 'Notes')
                                    ->addParam('category', $category ?? '')
                                    ->format(function ($note, $actions) use ($highestAction, $session) {
                                        if ($note['gibbonPersonIDCreator'] == $session->get('gibbonPersonID') || $highestAction == "View Student Profile_fullEditAllNotes") {
                                            $actions->addAction('edit', __('Edit'))
                                                    ->setURL('/modules/Students/student_view_details_notes_edit.php');
                                        }

                                        if ($highestAction == "View Student Profile_fullEditAllNotes") {
                                            $actions->addAction('delete', __('Delete'))
                                                    ->setURL('/modules/Students/student_view_details_notes_delete.php');
                                        }
                                    });

                                echo $table->render($notes->toDataSet());
                            }
                        }
                    } elseif ($subpage == 'Attendance') {
                        if (isActionAccessible($guid, $connection2, '/modules/Attendance/report_studentHistory.php') == false) {
                            echo "<div class='error'>";
                            echo __('Your request failed because you do not have access to this action.');
                            echo '</div>';
                        } else {
                            include './modules/Attendance/moduleFunctions.php';
                            include './modules/Attendance/src/StudentHistoryData.php';
                            include './modules/Attendance/src/StudentHistoryView.php';

                            // ATTENDANCE DATA
                            $attendanceData = $container->get(StudentHistoryData::class)
                                ->getAttendanceData($session->get('gibbonSchoolYearID'), $gibbonPersonID, $row['dateStart'], $row['dateEnd']);

                            // DATA TABLE
                            $renderer = $container->get(StudentHistoryView::class);
                            $renderer->addData('canTakeAttendanceByPerson', isActionAccessible($guid, $connection2, '/modules/Attendance/attendance_take_byPerson.php'));

                            $table = DataTable::create('studentHistory', $renderer);
                            echo $table->render($attendanceData);
                        }
                    } elseif ($subpage == 'Markbook') {
                        if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_view.php') == false) {
                            echo "<div class='error'>";
                            echo __('Your request failed because you do not have access to this action.');
                            echo '</div>';
                        } else {
                            // Register scripts available to the core, but not included by default
                            $page->scripts->add('chart');

                            $highestAction2 = getHighestGroupedAction($guid, '/modules/Markbook/markbook_view.php', $connection2);
                            if ($highestAction2 == false) {
                                echo "<div class='error'>";
                                echo __('The highest grouped action cannot be determined.');
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
                                $enableModifiedAssessment = getSettingByScope($connection2, 'Markbook', 'enableModifiedAssessment');

                                $alert = getAlert($guid, $connection2, 002);
                                $role = getRoleCategory($session->get('gibbonRoleIDCurrent'), $connection2);
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
                                $filter = isset($_REQUEST['filter'])? $_REQUEST['filter'] : $session->get('gibbonSchoolYearID');

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
                                echo __('This page displays academic results for a student throughout their school career. Only subjects with published results are shown.');
                                echo '</p>';

                                $form = Form::create('filter', $session->get('absoluteURL').'/index.php', 'get');
                                $form->setClass('noIntBorder fullWidth');

                                $form->addHiddenValue('q', '/modules/'.$session->get('module').'/student_view_details.php');
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
                                $sqlSelect = "SELECT gibbonSchoolYear.gibbonSchoolYearID as value, CONCAT(gibbonSchoolYear.name, ' (', gibbonYearGroup.name, ')') AS name FROM gibbonStudentEnrolment JOIN gibbonSchoolYear ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) WHERE gibbonPersonID=:gibbonPersonID ORDER BY gibbonSchoolYear.sequenceNumber";
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
                                if ($highestAction2 == 'View Markbook_myClasses') {
                                    // Get class list (limited to a teacher's classes)

                                        $dataList['gibbonPersonIDTeacher'] = $session->get('gibbonPersonID');
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
                                } else {
                                    // Get class list (all classes)

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
                                }


                                if ($resultList->rowCount() > 0) {
                                    while ($rowList = $resultList->fetch()) {
                                        try {
                                            $dataEntry['gibbonPersonID'] = $gibbonPersonID;
                                            $dataEntry['gibbonCourseClassID'] = $rowList['gibbonCourseClassID'];
                                            if ($highestAction2 == 'View Markbook_viewMyChildrensClasses') {
                                                $sqlEntry = "SELECT *, gibbonMarkbookColumn.comment AS commentOn, gibbonMarkbookColumn.uploadedResponse AS uploadedResponseOn, gibbonMarkbookEntry.comment AS comment FROM gibbonMarkbookEntry JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) WHERE gibbonPersonIDStudent=:gibbonPersonID AND gibbonCourseClassID=:gibbonCourseClassID AND complete='Y' AND completeDate<='".date('Y-m-d')."' AND viewableParents='Y' $and2 ORDER BY completeDate";
                                            } elseif ($highestAction2 == 'View Markbook_myMarks') {
                                                $sqlEntry = "SELECT *, gibbonMarkbookColumn.comment AS commentOn, gibbonMarkbookColumn.uploadedResponse AS uploadedResponseOn, gibbonMarkbookEntry.comment AS comment FROM gibbonMarkbookEntry JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) WHERE gibbonPersonIDStudent=:gibbonPersonID AND gibbonCourseClassID=:gibbonCourseClassID AND complete='Y' AND completeDate<='".date('Y-m-d')."' AND viewableStudents='Y' $and2 ORDER BY completeDate";
                                            } else {
                                                $sqlEntry = "SELECT *, gibbonMarkbookColumn.comment AS commentOn, gibbonMarkbookColumn.uploadedResponse AS uploadedResponseOn, gibbonMarkbookEntry.comment AS comment FROM gibbonMarkbookEntry JOIN gibbonMarkbookColumn ON (gibbonMarkbookEntry.gibbonMarkbookColumnID=gibbonMarkbookColumn.gibbonMarkbookColumnID) WHERE gibbonPersonIDStudent=:gibbonPersonID AND gibbonCourseClassID=:gibbonCourseClassID AND complete='Y' AND completeDate<='".date('Y-m-d')."' $and2 ORDER BY completeDate";
                                            }
                                            $resultEntry = $connection2->prepare($sqlEntry);
                                            $resultEntry->execute($dataEntry);
                                        } catch (PDOException $e) {
                                            echo "<div class='error'>".$e->getMessage().'</div>';
                                        }

                                        if ($resultEntry->rowCount() > 0) {
                                            echo "<a name='".$rowList['gibbonCourseClassID']."'></a><h4>".$rowList['course'].'.'.$rowList['class']." <span style='font-size:85%; font-style: italic'>(".$rowList['name'].')</span></h4>';


                                                $dataTeachers = array('gibbonCourseClassID' => $rowList['gibbonCourseClassID']);
                                                $sqlTeachers = "SELECT title, surname, preferredName, gibbonCourseClassPerson.reportable FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE role='Teacher' AND gibbonCourseClassID=:gibbonCourseClassID ORDER BY surname, preferredName";
                                                $resultTeachers = $connection2->prepare($sqlTeachers);
                                                $resultTeachers->execute($dataTeachers);

                                            $teachers = '<p><b>'.__('Taught by:').'</b> ';
                                            while ($rowTeachers = $resultTeachers->fetch()) {
                                                if ($rowTeachers['reportable'] != 'Y') continue;
                                                $teachers = $teachers.Format::name($rowTeachers['title'], $rowTeachers['preferredName'], $rowTeachers['surname'], 'Staff', false, false).', ';
                                            }
                                            $teachers = substr($teachers, 0, -2);
                                            $teachers = $teachers.'</p>';
                                            echo $teachers;

                                            if ($rowList['target'] != '') {
                                                echo "<div style='font-weight: bold' class='linkTop'>";
                                                echo __('Target').': '.$rowList['target'];
                                                echo '</div>';
                                            }

                                            echo "<table cellspacing='0' style='width: 100%'>";
                                            echo "<tr class='head'>";
                                            echo "<th style='width: 120px'>";
                                            echo __('Assessment');
                                            echo '</th>';
                                            if ($enableModifiedAssessment == 'Y') {
                                                echo "<th style='width: 75px'>";
                                                    echo __('Modified');
                                                echo '</th>';
                                            }
                                            echo "<th style='width: 75px; text-align: center'>";
                                            if ($attainmentAlternativeName != '') {
                                                echo $attainmentAlternativeName;
                                            } else {
                                                echo __('Attainment');
                                            }
                                            echo '</th>';
                                            if ($enableEffort == 'Y') {
                                                echo "<th style='width: 75px; text-align: center'>";
                                                if ($effortAlternativeName != '') {
                                                    echo $effortAlternativeName;
                                                } else {
                                                    echo __('Effort');
                                                }
                                                echo '</th>';
                                            }
                                            echo '<th>';
                                            echo __('Comment');
                                            echo '</th>';
                                            echo "<th style='width: 75px'>";
                                            echo __('Submission');
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
                                                $unit = getUnit($connection2, $rowEntry['gibbonUnitID'], $rowEntry['gibbonCourseClassID']);
                                                if (isset($unit[0])) {
                                                    echo $unit[0].'<br/>';
                                                }
                                                if (isset($unit[1])) {
                                                    if ($unit[1] != '') {
                                                        echo $unit[1].' '.__('Unit').'</i><br/>';
                                                    }
                                                }
                                                if ($rowEntry['completeDate'] != '') {
                                                    echo __('Marked on').' '.Format::date($rowEntry['completeDate']).'<br/>';
                                                } else {
                                                    echo __('Unmarked').'<br/>';
                                                }
                                                echo $rowEntry['type'];
                                                if ($rowEntry['attachment'] != '' and file_exists($session->get('absolutePath').'/'.$rowEntry['attachment'])) {
                                                    echo " | <a 'title='".__('Download more information')."' href='".$session->get('absoluteURL').'/'.$rowEntry['attachment']."'>".__('More info').'</a>';
                                                }
                                                echo '</span><br/>';
                                                echo '</td>';
                                                if ($enableModifiedAssessment == 'Y') {
                                                    if (!is_null($rowEntry['modifiedAssessment'])) {
                                                        echo "<td>";
                                                        echo ynExpander($guid, $rowEntry['modifiedAssessment']);
                                                        echo '</td>';
                                                    } else {
                                                        echo "<td class='dull' style='color: #bbb; text-align: center'>";
                                                        echo __('N/A');
                                                        echo '</td>';
                                                    }
                                                }
                                                if ($rowEntry['attainment'] == 'N' or ($rowEntry['gibbonScaleIDAttainment'] == '' and $rowEntry['gibbonRubricIDAttainment'] == '')) {
                                                    echo "<td class='dull' style='color: #bbb; text-align: center'>";
                                                    echo __('N/A');
                                                    echo '</td>';
                                                } else {
                                                    echo "<td style='text-align: center'>";
                                                    $attainmentExtra = '';

                                                        $dataAttainment = array('gibbonScaleIDAttainment' => $rowEntry['gibbonScaleIDAttainment']);
                                                        $sqlAttainment = 'SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleIDAttainment';
                                                        $resultAttainment = $connection2->prepare($sqlAttainment);
                                                        $resultAttainment->execute($dataAttainment);
                                                    if ($resultAttainment->rowCount() == 1) {
                                                        $rowAttainment = $resultAttainment->fetch();
                                                        $attainmentExtra = '<br/>'.__($rowAttainment['usage']);
                                                    }
                                                    $styleAttainment = "style='font-weight: bold'";
                                                    if ($rowEntry['attainmentConcern'] == 'Y' and $showParentAttainmentWarning == 'Y') {
                                                        $styleAttainment = "style='color: ".$alert['color'].'; font-weight: bold; border: 2px solid '.$alert['color'].'; padding: 2px 4px; background-color: '.$alert['colorBG']."'";
                                                    } elseif ($rowEntry['attainmentConcern'] == 'P' and $showParentAttainmentWarning == 'Y') {
                                                        $styleAttainment = "style='color: #390; font-weight: bold; border: 2px solid #390; padding: 2px 4px; background-color: #D4F6DC'";
                                                    }
                                                    echo "<div $styleAttainment>".$rowEntry['attainmentValue'];
                                                    if ($rowEntry['gibbonRubricIDAttainment'] != '' and $enableRubrics =='Y') {
                                                        echo "<a class='thickbox' href='".$session->get('absoluteURL').'/fullscreen.php?q=/modules/Markbook/markbook_view_rubric.php&gibbonRubricID='.$rowEntry['gibbonRubricIDAttainment'].'&gibbonCourseClassID='.$rowList['gibbonCourseClassID'].'&gibbonMarkbookColumnID='.$rowEntry['gibbonMarkbookColumnID']."&gibbonPersonID=$gibbonPersonID&mark=FALSE&type=attainment&width=1100&height=550'><img style='margin-bottom: -3px; margin-left: 3px' title='View Rubric' src='./themes/".$session->get('gibbonThemeName')."/img/rubric.png'/></a>";
                                                    }
                                                    echo '</div>';
                                                    if ($rowEntry['attainmentValue'] != '') {
                                                        echo "<div class='detailItem' style='font-size: 75%; font-style: italic; margin-top: 2px'><b>".htmlPrep(__($rowEntry['attainmentDescriptor'])).'</b>'.__($attainmentExtra).'</div>';
                                                    }
                                                    echo '</td>';
                                                }
                                                if ($enableEffort == 'Y') {
                                                    if ($rowEntry['effort'] == 'N' or ($rowEntry['gibbonScaleIDEffort'] == '' and $rowEntry['gibbonRubricIDEffort'] == '')) {
                                                        echo "<td class='dull' style='color: #bbb; text-align: center'>";
                                                        echo __('N/A');
                                                        echo '</td>';
                                                    } else {
                                                        echo "<td style='text-align: center'>";
                                                        $effortExtra = '';

                                                            $dataEffort = array('gibbonScaleIDEffort' => $rowEntry['gibbonScaleIDEffort']);
                                                            $sqlEffort = 'SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleIDEffort';
                                                            $resultEffort = $connection2->prepare($sqlEffort);
                                                            $resultEffort->execute($dataEffort);

                                                        if ($resultEffort->rowCount() == 1) {
                                                            $rowEffort = $resultEffort->fetch();
                                                            $effortExtra = '<br/>'.__($rowEffort['usage']);
                                                        }
                                                        $styleEffort = "style='font-weight: bold'";
                                                        if ($rowEntry['effortConcern'] == 'Y' and $showParentEffortWarning == 'Y') {
                                                            $styleEffort = "style='color: ".$alert['color'].'; font-weight: bold; border: 2px solid '.$alert['color'].'; padding: 2px 4px; background-color: '.$alert['colorBG']."'";
                                                        }
                                                        echo "<div $styleEffort>".$rowEntry['effortValue'];
                                                        if ($rowEntry['gibbonRubricIDEffort'] != '' and $enableRubrics =='Y') {
                                                            echo "<a class='thickbox' href='".$session->get('absoluteURL').'/fullscreen.php?q=/modules/Markbook/markbook_view_rubric.php&gibbonRubricID='.$rowEntry['gibbonRubricIDEffort'].'&gibbonCourseClassID='.$rowList['gibbonCourseClassID'].'&gibbonMarkbookColumnID='.$rowEntry['gibbonMarkbookColumnID']."&gibbonPersonID=$gibbonPersonID&mark=FALSE&type=effort&width=1100&height=550'><img style='margin-bottom: -3px; margin-left: 3px' title='View Rubric' src='./themes/".$session->get('gibbonThemeName')."/img/rubric.png'/></a>";
                                                        }
                                                        echo '</div>';
                                                        if ($rowEntry['effortValue'] != '') {
                                                            echo "<div class='detailItem' style='font-size: 75%; font-style: italic; margin-top: 2px'><b>".htmlPrep(__($rowEntry['effortDescriptor'])).'</b>'.__($effortExtra).'</div>';
                                                        }
                                                        echo '</td>';
                                                    }
                                                }
                                                if ($rowEntry['commentOn'] == 'N' and $rowEntry['uploadedResponseOn'] == 'N') {
                                                    echo "<td class='dull' style='color: #bbb; text-align: center'>";
                                                    echo __('N/A');
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
                                                            echo "<a title='".__('View Description')."' class='show_hide-$entryCount' onclick='return false;' href='#'>".__('Read more').'</a></span><br/>';
                                                        } else {
                                                            echo nl2br($rowEntry['comment']).'<br/>';
                                                        }
                                                    }
                                                    if ($rowEntry['response'] != '') {
                                                        echo "<a title='Uploaded Response' href='".$session->get('absoluteURL').'/'.$rowEntry['response']."'>".__('Uploaded Response').'</a><br/>';
                                                    }
                                                    echo '</td>';
                                                }
                                                if ($rowEntry['gibbonPlannerEntryID'] == 0) {
                                                    echo "<td class='dull' style='color: #bbb; text-align: center'>";
                                                    echo __('N/A');
                                                    echo '</td>';
                                                } else {

                                                        $dataSub = array('gibbonPlannerEntryID' => $rowEntry['gibbonPlannerEntryID']);
                                                        $sqlSub = "SELECT * FROM gibbonPlannerEntry WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND homeworkSubmission='Y'";
                                                        $resultSub = $connection2->prepare($sqlSub);
                                                        $resultSub->execute($dataSub);
                                                    if ($resultSub->rowCount() != 1) {
                                                        echo "<td class='dull' style='color: #bbb; text-align: center'>";
                                                        echo __('N/A');
                                                        echo '</td>';
                                                    } else {
                                                        echo '<td>';
                                                        $rowSub = $resultSub->fetch();


                                                            $dataWork = array('gibbonPlannerEntryID' => $rowEntry['gibbonPlannerEntryID'], 'gibbonPersonID' => $_GET['gibbonPersonID']);
                                                            $sqlWork = 'SELECT * FROM gibbonPlannerEntryHomework WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID AND gibbonPersonID=:gibbonPersonID ORDER BY count DESC';
                                                            $resultWork = $connection2->prepare($sqlWork);
                                                            $resultWork->execute($dataWork);
                                                        if ($resultWork->rowCount() > 0) {
                                                            $rowWork = $resultWork->fetch();

                                                            if ($rowWork['status'] == 'Exemption') {
                                                                $linkText = __('Exemption');
                                                            } elseif ($rowWork['version'] == 'Final') {
                                                                $linkText = __('Final');
                                                            } else {
                                                                $linkText = __('Draft').' '.$rowWork['count'];
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
                                                                echo "<span title='".$rowWork['version'].". $status. ".sprintf(__('Submitted at %1$s on %2$s'), substr($rowWork['timestamp'], 11, 5), Format::date(substr($rowWork['timestamp'], 0, 10)))."' $style><a href='".$session->get('absoluteURL').'/'.$rowWork['location']."'>$linkText</a></span>";
                                                            } elseif ($rowWork['type'] == 'Link') {
                                                                echo "<span title='".$rowWork['version'].". $status. ".sprintf(__('Submitted at %1$s on %2$s'), substr($rowWork['timestamp'], 11, 5), Format::date(substr($rowWork['timestamp'], 0, 10)))."' $style><a target='_blank' href='".$rowWork['location']."'>$linkText</a></span>";
                                                            } else {
                                                                echo "<span title='$status. ".sprintf(__('Recorded at %1$s on %2$s'), substr($rowWork['timestamp'], 11, 5), Format::date(substr($rowWork['timestamp'], 0, 10)))."' $style>$linkText</span>";
                                                            }
                                                        } else {
                                                            if (date('Y-m-d H:i:s') < $rowSub['homeworkDueDateTime']) {
                                                                echo "<span title='Pending'>".__('Pending').'</span>';
                                                            } else {
                                                                if ($row['dateStart'] > $rowSub['date']) {
                                                                    echo "<span title='".__('Student joined school after assessment was given.')."' style='color: #000; font-weight: normal; border: 2px none #ff0000; padding: 2px 4px'>".__('NA').'</span>';
                                                                } else {
                                                                    if ($rowSub['homeworkSubmissionRequired'] == 'Required') {
                                                                        echo "<div style='color: #ff0000; font-weight: bold; border: 2px solid #ff0000; padding: 2px 4px; margin: 2px 0px'>".__('Incomplete').'</div>';
                                                                    } else {
                                                                        echo __('Not submitted online');
                                                                    }
                                                                }
                                                            }
                                                        }
                                                        echo '</td>';
                                                    }
                                                }
                                                echo '</tr>';
                                                if ($rowEntry['commentOn'] == 'Y' && mb_strlen($rowEntry['comment']) > 200) {
                                                    echo "<tr class='comment-$entryCount' id='comment-$entryCount'>";
                                                    echo '<td colspan=6>';
                                                    echo nl2br($rowEntry['comment']);
                                                    echo '</td>';
                                                    echo '</tr>';
                                                }
                                            }

                                            $enableColumnWeighting = getSettingByScope($connection2, 'Markbook', 'enableColumnWeighting');
                                            $enableDisplayCumulativeMarks = getSettingByScope($connection2, 'Markbook', 'enableDisplayCumulativeMarks');

                                            if ($enableColumnWeighting == 'Y' && $enableDisplayCumulativeMarks == 'Y') {
                                                renderStudentCumulativeMarks($gibbon, $pdo, $_GET['gibbonPersonID'], $rowList['gibbonCourseClassID']);
                                            }

                                            echo '</table>';
                                        }
                                    }
                                }
                                if ($entryCount < 1) {
                                    echo "<div class='error'>";
                                    echo __('There are no records to display.');
                                    echo '</div>';
                                }
                            }
                        }
                    } elseif ($subpage == 'Internal Assessment') {
                        if (isActionAccessible($guid, $connection2, '/modules/Formal Assessment/internalAssessment_view.php') == false) {
                            echo "<div class='error'>";
                            echo __('Your request failed because you do not have access to this action.');
                            echo '</div>';
                        } else {
                            $highestAction2 = getHighestGroupedAction($guid, '/modules/Formal Assessment/internalAssessment_view.php', $connection2);
                            if ($highestAction2 == false) {
                                echo "<div class='error'>";
                                echo __('The highest grouped action cannot be determined.');
                                echo '</div>';
                            } else {
                                //Module includes
                                include './modules/Formal Assessment/moduleFunctions.php';

                                if ($highestAction2 == 'View Internal Assessments_all') {
                                    echo getInternalAssessmentRecord($guid, $connection2, $gibbonPersonID);
                                } elseif ($highestAction2 == 'View Internal Assessments_myChildrens') {
                                    echo getInternalAssessmentRecord($guid, $connection2, $gibbonPersonID, 'parent');
                                } elseif ($highestAction2 == 'View Internal Assessments_mine') {
                                    echo getInternalAssessmentRecord($guid, $connection2, $session->get('gibbonPersonID'), 'student');
                                }
                            }
                        }
                    } elseif ($subpage == 'External Assessment') {
                        if (isActionAccessible($guid, $connection2, '/modules/Formal Assessment/externalAssessment_details.php') == false and isActionAccessible($guid, $connection2, '/modules/Formal Assessment/externalAssessment_view.php') == false) {
                            echo "<div class='error'>";
                            echo __('Your request failed because you do not have access to this action.');
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
                    } elseif ($subpage == 'Reports') {
                        if (isActionAccessible($guid, $connection2, '/modules/Reports/archive_byStudent_view.php') == false) {
                            echo "<div class='error'>";
                            echo __('Your request failed because you do not have access to this action.');
                            echo '</div>';
                        } else {
                            $highestActionReports = getHighestGroupedAction($guid, '/modules/Reports/archive_byStudent_view.php', $connection2);
                            $gibbonSchoolYearID = $gibbon->session->get('gibbonSchoolYearID');

                            if ($highestActionReports == 'View by Student') {
                                $student = $container->get(UserGateway::class)->getByID($gibbonPersonID);
                            } else if ($highestActionReports == 'View Reports_myChildren') {
                                $studentGateway = $container->get(StudentGateway::class);
                                $children = $studentGateway
                                    ->selectAnyStudentsByFamilyAdult($gibbonSchoolYearID, $gibbon->session->get('gibbonPersonID'))
                                    ->fetchGroupedUnique();

                                if (!empty($children[$gibbonPersonID])) {
                                    $student = $container->get(UserGateway::class)->getByID($gibbonPersonID);
                                }
                            } else if ($highestActionReports == 'View Reports_mine') {
                                $gibbonPersonID = $gibbon->session->get('gibbonPersonID');
                                $student =  $container->get(StudentGateway::class)->selectActiveStudentByPerson($gibbonSchoolYearID, $gibbonPersonID)->fetch();
                            }

                            if (empty($student)) {
                                $page->addError(__('You do not have access to this action.'));
                                return;
                            }

                            $archiveInformation = $container->get(SettingGateway::class)->getSettingByScope('Reports', 'archiveInformation');

                            // CRITERIA
                            include './modules/Reports/src/Domain/ReportArchiveEntryGateway.php';
                            $reportArchiveEntryGateway = $container->get(ReportArchiveEntryGateway::class);
                            $criteria = $reportArchiveEntryGateway->newQueryCriteria()
                                ->sortBy('sequenceNumber', 'DESC')
                                ->sortBy(['timestampCreated'])
                                ->fromPOST();

                            // QUERY
                            $canViewDraftReports = isActionAccessible($guid, $connection2, '/modules/Reports/archive_byStudent.php', 'View Draft Reports');
                            $canViewPastReports = isActionAccessible($guid, $connection2, '/modules/Reports/archive_byStudent.php', 'View Past Reports');
                            $roleCategory = getRoleCategory($gibbon->session->get('gibbonRoleIDCurrent'), $connection2);

                            $reports = $reportArchiveEntryGateway->queryArchiveByStudent($criteria, $gibbonPersonID, $roleCategory, $canViewDraftReports, $canViewPastReports);

                            $reportsBySchoolYear = array_reduce($reports->toArray(), function ($group, $item) {
                                $group[$item['schoolYear']][] = $item;
                                return $group;
                            }, []);

                            if (empty($reportsBySchoolYear)) {
                                $reportsBySchoolYear = [__('Reports') => []];
                            }

                            foreach ($reportsBySchoolYear as $schoolYear => $reports) {
                                // DATA TABLE
                                $table = DataTable::create('reportsView');
                                if ($schoolYear != 'Reports') {
                                    $table->setTitle($schoolYear);
                                }

                                $table->addColumn('reportName', __('Report'))
                                    ->width('30%')
                                    ->format(function ($report) {
                                        return !empty($report['reportName'])? $report['reportName'] : $report['reportIdentifier'];
                                    });

                                $table->addColumn('yearGroup', __('Year Group'))->width('15%');
                                $table->addColumn('formGroup', __('Form Group'))->width('15%');
                                $table->addColumn('timestampModified', __('Date'))
                                    ->width('30%')
                                    ->format(function ($report) {
                                        $output = Format::dateReadable($report['timestampModified']);
                                        if ($report['status'] == 'Draft') {
                                            $output .= '<span class="tag ml-2 dull">'.__($report['status']).'</span>';
                                        }

                                        if (!empty($report['timestampAccessed'])) {
                                            $title = Format::name($report['parentTitle'], $report['parentPreferredName'], $report['parentSurname'], 'Parent', false).': '.Format::relativeTime($report['timestampAccessed'], false);
                                            $output .= '<span class="tag ml-2 success" title="'.$title.'">'.__('Read').'</span>';
                                        }

                                        return $output;
                                    });

                                $table->addActionColumn()
                                    ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
                                    ->format(function ($report, $actions) {
                                        $actions->addAction('view', __('View'))
                                            ->directLink()
                                            ->addParam('action', 'view')
                                            ->addParam('gibbonReportArchiveEntryID', $report['gibbonReportArchiveEntryID'] ?? '')
                                            ->addParam('gibbonPersonID', $report['gibbonPersonID'] ?? '')
                                            ->setURL('/modules/Reports/archive_byStudent_download.php');

                                        $actions->addAction('download', __('Download'))
                                            ->setIcon('download')
                                            ->directLink()
                                            ->addParam('gibbonReportArchiveEntryID', $report['gibbonReportArchiveEntryID'] ?? '')
                                            ->addParam('gibbonPersonID', $report['gibbonPersonID'] ?? '')
                                            ->setURL('/modules/Reports/archive_byStudent_download.php');
                                    });

                                echo $table->render(new DataSet($reports));
                            }
                        }
                    } elseif ($subpage == 'Individual Needs') {
                        if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/in_view.php') == false) {
                            echo "<div class='error'>";
                            echo __('Your request failed because you do not have access to this action.');
                            echo '</div>';
                        } else {
                            //Edit link
                            if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/in_edit.php') == true) {
                                echo "<div class='linkTop'>";
                                echo "<a href='".$session->get('absoluteURL')."/index.php?q=/modules/Individual Needs/in_edit.php&gibbonPersonID=$gibbonPersonID'>".__('Edit')."<img style='margin: 0 0 -4px 5px' title='".__('Edit')."' src='./themes/".$session->get('gibbonThemeName')."/img/config.png'/></a> ";
                                echo '</div>';
                            }

                            //Module includes
                            include './modules/Individual Needs/moduleFunctions.php';

                            $statusTable = printINStatusTable($connection2, $guid, $gibbonPersonID, 'disabled');
                            if ($statusTable == false) {
                                echo "<div class='error'>";
                                echo __('Your request failed due to a database error.');
                                echo '</div>';
                            } else {
                                echo $statusTable;
                            }

                            //Get and display a list of student's educational assistants

                                $dataDetail = array('gibbonPersonID1' => $gibbonPersonID, 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID2' => $gibbonPersonID);
                                $sqlDetail = "(SELECT DISTINCT surname, preferredName, email
                                    FROM gibbonPerson
                                        JOIN gibbonINAssistant ON (gibbonINAssistant.gibbonPersonIDAssistant=gibbonPerson.gibbonPersonID)
                                    WHERE status='Full'
                                        AND gibbonPersonIDStudent=:gibbonPersonID1)
                                UNION
                                (SELECT DISTINCT surname, preferredName, email
                                    FROM gibbonPerson
                                        JOIN gibbonFormGroup ON (gibbonFormGroup.gibbonPersonIDEA=gibbonPerson.gibbonPersonID OR gibbonFormGroup.gibbonPersonIDEA2=gibbonPerson.gibbonPersonID OR gibbonFormGroup.gibbonPersonIDEA3=gibbonPerson.gibbonPersonID)
                                        JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID)
                                        JOIN gibbonSchoolYear ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
                                    WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                                        AND gibbonStudentEnrolment.gibbonPersonID=:gibbonPersonID2
                                )
                                ORDER BY preferredName, surname, email";
                                $resultDetail = $connection2->prepare($sqlDetail);
                                $resultDetail->execute($dataDetail);
                            if ($resultDetail->rowCount() > 0) {
                                echo '<h3>';
                                echo __('Educational Assistants');
                                echo '</h3>';

                                echo '<ul>';
                                while ($rowDetail = $resultDetail->fetch()) {
                                    echo '<li>'.htmlPrep(Format::name('', $rowDetail['preferredName'], $rowDetail['surname'], 'Student', false));
                                    if ($rowDetail['email'] != '') {
                                        echo htmlPrep(' <'.$rowDetail['email'].'>');
                                    }
                                    echo '</li>';
                                }
                                echo '</ul>';
                            }


                            echo '<h3>';
                            echo __('Individual Education Plan');
                            echo '</h3>';

                                $dataIN = array('gibbonPersonID' => $gibbonPersonID);
                                $sqlIN = 'SELECT * FROM gibbonIN WHERE gibbonPersonID=:gibbonPersonID';
                                $resultIN = $connection2->prepare($sqlIN);
                                $resultIN->execute($dataIN);

                            if ($resultIN->rowCount() != 1) {
                                echo "<div class='error'>";
                                echo __('There are no records to display.');
                                echo '</div>';
                            } else {
                                $rowIN = $resultIN->fetch();

                                echo "<div style='font-weight: bold'>".__('Targets').'</div>';
                                echo '<p>'.$rowIN['targets'].'</p>';

                                echo "<div style='font-weight: bold; margin-top: 30px'>".__('Teaching Strategies').'</div>';
                                echo '<p>'.$rowIN['strategies'].'</p>';

                                echo "<div style='font-weight: bold; margin-top: 30px'>".__('Notes & Review').'s</div>';
                                echo '<p>'.$rowIN['notes'].'</p>';
                            }
                        }
                    } elseif ($subpage == 'Library Borrowing') {
                        if (isActionAccessible($guid, $connection2, '/modules/Library/report_studentBorrowingRecord.php') == false) {
                            echo "<div class='error'>";
                            echo __('Your request failed because you do not have access to this action.');
                            echo '</div>';
                        } else {
                            //Print borrowing record
                            $libraryGateway = $container->get(LibraryReportGateway::class);
                            $criteria = $libraryGateway->newQueryCriteria(true)
                                ->sortBy('gibbonLibraryItemEvent.timestampOut', 'DESC')
                                ->filterBy('gibbonPersonID', $gibbonPersonID)
                                ->fromPOST('lendingLog');

                            $items = $libraryGateway->queryStudentReportData($criteria);
                            $lendingTable = DataTable::createPaginated('lendingLog', $criteria);
                            $lendingTable
                              ->modifyRows(function ($item, $row) {
                                if ($item['status'] == 'On Loan') {
                                    return $item['pastDue'] == 'Y' ? $row->addClass('error') : $row;
                                }
                                return $row;
                              });
                            $lendingTable
                              ->addExpandableColumn('details')
                              ->format(function ($item) {
                                $detailTable = "<table>";
                                $fields = json_decode($item['fields'], true) ?? [];
                                $typeFields = json_decode($item['typeFields'], true) ?? [];
                                foreach ($typeFields as $typeField) {
                                    $detailTable .= sprintf('<tr><td><b>%1$s</b></td><td>%2$s</td></tr>', $typeField['name'], $fields[$typeField['name']]);
                                }
                                $detailTable .= '</table>';
                                return $detailTable;
                              });
                            $lendingTable
                              ->addColumn('imageLocation')
                              ->width('120px')
                              ->format(function ($item) {
                                return Format::photo($item['imageLocation'], 75);
                              });
                            $lendingTable
                              ->addColumn('name', __('Name'))
                              ->description(__('Author/Producer'))
                              ->format(function ($item) {
                                return sprintf('<b>%1$s</b><br/>%2$s', $item['name'], Format::small($item['producer']));
                              });
                            $lendingTable
                              ->addColumn('id', __('ID'))
                              ->format(function ($item) {
                                return sprintf('<b>%1$s</b>', $item['id']);
                              });
                            $lendingTable
                              ->addColumn('spaceName', __('Location'))
                              ->format(function ($item) {
                                return sprintf('<b>%1$s</b><br/>%2$s', $item['spaceName'], Format::small($item['locationDetail']));
                              });
                            $lendingTable
                              ->addColumn('timestampOut', __('Return Date'))
                              ->description(__('Borrow Date'))
                              ->format(function ($item) {
                                return sprintf('<b>%1$s</b><br/>%2$s', $item['status'] == 'On Loan' ? Format::date($item['returnExpected']) : 'N/A', Format::small(Format::date($item['timestampOut'])));
                              });
                            $lendingTable
                              ->addColumn('status', __('Status'));
                            echo $lendingTable->render($items);
                        }
                    } elseif ($subpage == 'Timetable') {
                        if (isActionAccessible($guid, $connection2, '/modules/Timetable/tt_view.php') == false) {
                            echo "<div class='error'>";
                            echo __('Your request failed because you do not have access to this action.');
                            echo '</div>';
                        } else {
                            if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php') == true) {
                                $role = getRoleCategory($row['gibbonRoleIDPrimary'], $connection2);
                                if ($role == 'Student' or $role == 'Staff') {
                                    echo "<div class='linkTop'>";
                                    echo "<a href='".$session->get('absoluteURL')."/index.php?q=/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php&gibbonPersonID=$gibbonPersonID&gibbonSchoolYearID=".$session->get('gibbonSchoolYearID')."&type=$role'>".__('Edit')."<img style='margin: 0 0 -4px 5px' title='".__('Edit')."' src='./themes/".$session->get('gibbonThemeName')."/img/config.png'/></a> ";
                                    echo '</div>';
                                }
                            }

                            include './modules/Timetable/moduleFunctions.php';
                            $ttDate = null;
                            if (isset($_POST['ttDate'])) {
                                $ttDate = Format::timestamp(Format::dateConvert($_POST['ttDate']));
                            }
                            $tt = renderTT($guid, $connection2, $gibbonPersonID, $_GET['gibbonTTID'] ?? '', false, $ttDate, '/modules/Students/student_view_details.php', "&gibbonPersonID=$gibbonPersonID&search=$search&allStudents=$allStudents&subpage=Timetable");
                            if ($tt != false) {
                                echo $tt;
                            } else {
                                echo "<div class='error'>";
                                echo __('There are no records to display.');
                                echo '</div>';
                            }
                        }
                    } elseif ($subpage == 'Activities') {
                        if (!(isActionAccessible($guid, $connection2, '/modules/Activities/report_activityChoices_byStudent'))) {
                            echo "<div class='error'>";
                            echo __('Your request failed because you do not have access to this action.');
                            echo '</div>';
                        } else {
                            echo '<p>';
                            echo __('This report shows the current and historical activities that a student has enrolled in.');
                            echo '</p>';

                            $dateType = getSettingByScope($connection2, 'Activities', 'dateType');
                            if ($dateType == 'Term') {
                                $maxPerTerm = getSettingByScope($connection2, 'Activities', 'maxPerTerm');
                            }


                                $dataYears = array('gibbonPersonID' => $gibbonPersonID);
                                $sqlYears = 'SELECT * FROM gibbonStudentEnrolment JOIN gibbonSchoolYear ON (gibbonStudentEnrolment.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE gibbonPersonID=:gibbonPersonID ORDER BY sequenceNumber DESC';
                                $resultYears = $connection2->prepare($sqlYears);
                                $resultYears->execute($dataYears);

                            if ($resultYears->rowCount() < 1) {
                                echo "<div class='error'>";
                                echo __('There are no records to display.');
                                echo '</div>';
                            } else {
                                $yearCount = 0;
                                while ($rowYears = $resultYears->fetch()) {
                                    $class = '';
                                    if ($yearCount == 0) {
                                        $class = "class='top'";
                                    }

                                    ++$yearCount;
                                    try {
                                        $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonSchoolYearID' => $rowYears['gibbonSchoolYearID']);
                                        $sql = "SELECT gibbonActivity.gibbonActivityID, gibbonActivity.name, gibbonActivity.type, gibbonActivity.programStart, gibbonActivity.programEnd, GROUP_CONCAT(gibbonSchoolYearTerm.nameShort ORDER BY gibbonSchoolYearTerm.sequenceNumber SEPARATOR ', ') as terms, gibbonActivityStudent.status, NULL AS role FROM gibbonActivity JOIN gibbonActivityStudent ON (gibbonActivity.gibbonActivityID=gibbonActivityStudent.gibbonActivityID) LEFT JOIN gibbonSchoolYearTerm ON (FIND_IN_SET(gibbonSchoolYearTerm.gibbonSchoolYearTermID, gibbonActivity.gibbonSchoolYearTermIDList)) WHERE gibbonActivityStudent.gibbonPersonID=:gibbonPersonID AND gibbonActivity.gibbonSchoolYearID=:gibbonSchoolYearID AND active='Y' GROUP BY gibbonActivity.gibbonActivityID, gibbonActivityStudent.status ORDER BY gibbonActivity.name";
                                        $result = $connection2->prepare($sql);
                                        $result->execute($data);
                                        $resultData = $result->fetchAll();
                                    } catch (PDOException $e) {
                                        echo "<div class='error'>".$e->getMessage().'</div>';
                                        exit;
                                    }

                                    $table = DataTable::create('activities');
                                    $table->setTitle($rowYears['name']);
                                    $table->addColumn('name', __('Activity'));
                                    $table->addColumn('type', __('Type'));
                                    $table->addColumn('date', $dateType == "Date" ? __('Dates') : __('Term'))
                                          ->format(function ($row) use ($dateType) {
                                            if ($dateType != 'Date') {
                                                return $row['terms'];
                                            } else {
                                                return Format::dateRangeReadable($row['programStart'], $row['programEnd']);
                                            }
                                          });
                                    $table->addColumn('status', __('Status'))->translatable();
                                    $table->addActionColumn()
                                          ->format(function ($activity, $actions) {
                                            $actions->addAction('view', __('View Details'))
                                              ->setURL('/modules/Activities/activities_view_full.php')
                                              ->addParam('gibbonActivityID', $activity['gibbonActivityID'])
                                              ->modalWindow(1000, 500);
                                          });
                                    echo $table->render($resultData);
                                }
                            }
                        }
                    } elseif ($subpage == 'Homework') {
                        if (!(isActionAccessible($guid, $connection2, '/modules/Planner/planner_edit.php') or isActionAccessible($guid, $connection2, '/modules/Planner/planner_view_full.php'))) {
                            echo "<div class='error'>";
                            echo __('Your request failed because you do not have access to this action.');
                            echo '</div>';
                        } else {
                            $role = getRoleCategory($session->get('gibbonRoleIDCurrent'), $connection2);
                            $plannerGateway = $container->get(PlannerEntryGateway::class);

                            // DEADLINES
                            $deadlines = $plannerGateway->selectUpcomingHomeworkByStudent($gibbon->session->get('gibbonSchoolYearID'), $gibbonPersonID, $role == 'Student' ? 'viewableStudents' : 'viewableParents')->fetchAll();

                            echo $page->fetchFromTemplate('ui/upcomingDeadlines.twig.html', [
                                'gibbonPersonID' => $gibbonPersonID,
                                'deadlines' => $deadlines,
                                'heading' => 'h4'
                            ]);

                            // HOMEWORK TABLE
                            include './modules/Planner/src/Tables/HomeworkTable.php';
                            $page->scripts->add('planner', '/modules/Planner/js/module.js');

                            $table = $container->get(HomeworkTable::class)->create($gibbon->session->get('gibbonSchoolYearID'), $gibbonPersonID, $role == 'Student' ? 'Student' : 'Parent');

                            echo $table->getOutput();
                        }
                    } elseif ($subpage == 'Behaviour') {
                        if (isActionAccessible($guid, $connection2, '/modules/Behaviour/behaviour_view.php') == false) {
                            echo "<div class='error'>";
                            echo __('Your request failed because you do not have access to this action.');
                            echo '</div>';
                        } else {
                            include './modules/Behaviour/moduleFunctions.php';

                            //Print assessments
                            echo getBehaviourRecord($container, $gibbonPersonID);
                        }
                    }

                    //GET HOOK IF SPECIFIED
                    if ($hook != '' and $module != '' and $action != '') {
                        //GET HOOKS AND DISPLAY LINKS
                        //Check for hook

                            $dataHook = array('gibbonHookID' => $_GET['gibbonHookID']);
                            $sqlHook = 'SELECT * FROM gibbonHook WHERE gibbonHookID=:gibbonHookID';
                            $resultHook = $connection2->prepare($sqlHook);
                            $resultHook->execute($dataHook);
                        if ($resultHook->rowCount() != 1) {
                            echo "<div class='error'>";
                            echo __('There are no records to display.');
                            echo '</div>';
                        } else {
                            $rowHook = $resultHook->fetch();
                            $options = unserialize($rowHook['options']);

                            //Check for permission to hook
                                $dataHook = array('gibbonRoleIDCurrent' => $session->get('gibbonRoleIDCurrent'), 'sourceModuleName' => $options['sourceModuleName'], 'sourceModuleAction' => $options['sourceModuleAction']);
                                $sqlHook = "SELECT gibbonHook.name, gibbonModule.name AS module, gibbonAction.name AS action
                                    FROM gibbonHook
                                    JOIN gibbonModule ON (gibbonHook.gibbonModuleID=gibbonModule.gibbonModuleID)
                                    JOIN gibbonAction ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID)
                                    JOIN gibbonPermission ON (gibbonPermission.gibbonActionID=gibbonAction.gibbonActionID)
                                    WHERE gibbonModule.name=:sourceModuleName
                                    AND FIND_IN_SET(gibbonAction.name, :sourceModuleAction)
                                    AND gibbonPermission.gibbonRoleID=:gibbonRoleIDCurrent
                                    AND gibbonAction.gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name=:sourceModuleName)
                                    AND gibbonHook.type='Student Profile' ORDER BY name";
                                $resultHook = $connection2->prepare($sqlHook);
                                $resultHook->execute($dataHook);
                            if ($resultHook->rowCount() == 0) {
                                echo "<div class='error'>";
                                echo __('Your request failed because you do not have access to this action.');
                                echo '</div>';
                            } else {
                                $include = $session->get('absolutePath').'/modules/'.$options['sourceModuleName'].'/'.$options['sourceModuleInclude'];
                                if (!file_exists($include)) {
                                    echo "<div class='error'>";
                                    echo __('The selected page cannot be displayed due to a hook error.');
                                    echo '</div>';
                                } else {
                                    include $include;
                                }
                            }
                        }
                    }

                    //Set sidebar
                    $session->set('sidebarExtra', '');

                    $sidebarExtra = '';
                    //Show alerts
                    if ($highestAction == 'View Student Profile_fullEditAllNotes' || $highestAction == 'View Student Profile_full' || $highestAction == 'View Student Profile_fullNoNotes') {
                        $alert = getAlertBar($guid, $connection2, $gibbonPersonID, $row['privacy'], '', false, true);

                        $sidebarExtra .= '<div class="w-48 sm:w-64 h-10 mb-2">';
                        if ($alert == '') {
                             $sidebarExtra .= '<span class="text-gray-500 text-xs">'.__('No Current Alerts').'</span>';
                        } else {
                             $sidebarExtra .= $alert;
                        }
                         $sidebarExtra .= '</div>';
                    }

                     $sidebarExtra .= getUserPhoto($guid, $studentImage, 240);

                    //PERSONAL DATA MENU ITEMS
                     $sidebarExtra .= '<div class="column-no-break">';
                     $sidebarExtra .= '<h4>'.__('Personal').'</h4>';
                     $sidebarExtra .= "<ul class='moduleMenu'>";
                    $style = '';
                    if ($subpage == 'Overview') {
                        $style = "style='font-weight: bold'";
                    }
                     $sidebarExtra .= "<li><a $style href='".$session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=Overview'>".__('Overview').'</a></li>';
                    $style = '';
                    if ($subpage == 'Personal') {
                        $style = "style='font-weight: bold'";
                    }
                     $sidebarExtra .= "<li><a $style href='".$session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=Personal'>".__('Personal').'</a></li>';
                    $style = '';
                    if ($subpage == 'Family') {
                        $style = "style='font-weight: bold'";
                    }
                     $sidebarExtra .= "<li><a $style href='".$session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=Family'>".__('Family').'</a></li>';
                    $style = '';
                    if ($subpage == 'Emergency Contacts') {
                        $style = "style='font-weight: bold'";
                    }
                     $sidebarExtra .= "<li><a $style href='".$session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=Emergency Contacts'>".__('Emergency Contacts').'</a></li>';
                    $style = '';
                    if ($subpage == 'Medical') {
                        $style = "style='font-weight: bold'";
                    }
                     $sidebarExtra .= "<li><a $style href='".$session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=Medical'>".__('Medical').'</a></li>';
                    if (isActionAccessible($guid, $connection2, '/modules/Students/student_view_details_notes_add.php')) {
                        if ($enableStudentNotes == 'Y') {
                            $style = '';
                            if ($subpage == 'Notes') {
                                $style = "style='font-weight: bold'";
                            }
                             $sidebarExtra .= "<li><a $style href='".$session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=Notes'>".__('Notes').'</a></li>';
                        }
                    }
                     $sidebarExtra .= '</ul>';

                    //OTHER MENU ITEMS, DYANMICALLY ARRANGED TO MATCH CUSTOM TOP MENU
                    //Get all modules, with the categories

                        $dataMenu = array();
                        $sqlMenu = "SELECT gibbonModuleID, category, name FROM gibbonModule WHERE active='Y' ORDER BY category, name";
                        $resultMenu = $connection2->prepare($sqlMenu);
                        $resultMenu->execute($dataMenu);
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
                        $studentMenuName[$studentMenuCount] = __('Markbook');
                        $studentMenuLink[$studentMenuCount] = "<li><a $style href='".$session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=Markbook'>".__('Markbook').'</a></li>';
                        ++$studentMenuCount;
                    }
                    if (isActionAccessible($guid, $connection2, '/modules/Formal Assessment/internalAssessment_view.php')) {
                        $style = '';
                        if ($subpage == 'Internal Assessment') {
                            $style = "style='font-weight: bold'";
                        }
                        $studentMenuCategory[$studentMenuCount] = $mainMenu['Formal Assessment'];
                        $studentMenuName[$studentMenuCount] = __('Formal Assessment');
                        $studentMenuLink[$studentMenuCount] = "<li><a $style href='".$session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=Internal%20Assessment'>".__('Internal Assessment').'</a></li>';
                        ++$studentMenuCount;
                    }
                    if (isActionAccessible($guid, $connection2, '/modules/Formal Assessment/externalAssessment_details.php') or isActionAccessible($guid, $connection2, '/modules/Formal Assessment/externalAssessment_view.php')) {
                        $style = '';
                        if ($subpage == 'External Assessment') {
                            $style = "style='font-weight: bold'";
                        }
                        $studentMenuCategory[$studentMenuCount] = $mainMenu['Formal Assessment'];
                        $studentMenuName[$studentMenuCount] = __('External Assessment');
                        $studentMenuLink[$studentMenuCount] = "<li><a $style href='".$session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=External Assessment'>".__('External Assessment').'</a></li>';
                        ++$studentMenuCount;
                    }
                    if (isActionAccessible($guid, $connection2, '/modules/Reports/archive_byStudent_view.php')) {
                        $style = '';
                        if ($subpage == 'Reports') {
                            $style = "style='font-weight: bold'";
                        }
                        $studentMenuCategory[$studentMenuCount] = $mainMenu['Reports'];
                        $studentMenuName[$studentMenuCount] = __('Reports');
                        $studentMenuLink[$studentMenuCount] = "<li><a $style href='".$session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=Reports'>".__('Reports').'</a></li>';
                        ++$studentMenuCount;
                    }

                    if (isActionAccessible($guid, $connection2, '/modules/Activities/report_activityChoices_byStudent.php')) {
                        $style = '';
                        if ($subpage == 'Activities') {
                            $style = "style='font-weight: bold'";
                        }
                        $studentMenuCategory[$studentMenuCount] = $mainMenu['Activities'];
                        $studentMenuName[$studentMenuCount] = __('Activities');
                        $studentMenuLink[$studentMenuCount] = "<li><a $style href='".$session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=Activities'>".__('Activities').'</a></li>';
                        ++$studentMenuCount;
                    }
                    if (isActionAccessible($guid, $connection2, '/modules/Planner/planner_edit.php') or isActionAccessible($guid, $connection2, '/modules/Planner/planner_view_full.php')) {
                        $style = '';
                        if ($subpage == 'Homework') {
                            $style = "style='font-weight: bold'";
                        }
                        $homeworkNamePlural = getSettingByScope($connection2, 'Planner', 'homeworkNamePlural');
                        $studentMenuCategory[$studentMenuCount] = $mainMenu['Planner'];
                        $studentMenuName[$studentMenuCount] = __($homeworkNamePlural);
                        $studentMenuLink[$studentMenuCount] = "<li><a $style href='".$session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=Homework'>".__($homeworkNamePlural).'</a></li>';
                        ++$studentMenuCount;
                    }
                    if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/in_view.php')) {
                        $style = '';
                        if ($subpage == 'Individual Needs') {
                            $style = "style='font-weight: bold'";
                        }
                        $studentMenuCategory[$studentMenuCount] = $mainMenu['Individual Needs'];
                        $studentMenuName[$studentMenuCount] = __('Individual Needs');
                        $studentMenuLink[$studentMenuCount] = "<li><a $style href='".$session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=Individual Needs'>".__('Individual Needs').'</a></li>';
                        ++$studentMenuCount;
                    }
                    if (isActionAccessible($guid, $connection2, '/modules/Library/report_studentBorrowingRecord.php')) {
                        $style = '';
                        if ($subpage == 'Library Borrowing') {
                            $style = "style='font-weight: bold'";
                        }
                        $studentMenuCategory[$studentMenuCount] = $mainMenu['Library'];
                        $studentMenuName[$studentMenuCount] = __('Library Borrowing');
                        $studentMenuLink[$studentMenuCount] = "<li><a $style href='".$session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=Library Borrowing'>".__('Library Borrowing').'</a></li>';
                        ++$studentMenuCount;
                    }
                    if (isActionAccessible($guid, $connection2, '/modules/Timetable/tt_view.php')) {
                        $style = '';
                        if ($subpage == 'Timetable') {
                            $style = "style='font-weight: bold'";
                        }
                        $studentMenuCategory[$studentMenuCount] = $mainMenu['Timetable'];
                        $studentMenuName[$studentMenuCount] = __('Timetable');
                        $studentMenuLink[$studentMenuCount] = "<li><a $style href='".$session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=Timetable'>".__('Timetable').'</a></li>';
                        ++$studentMenuCount;
                    }if (isActionAccessible($guid, $connection2, '/modules/Attendance/report_studentHistory.php')) {
                        $style = '';
                        if ($subpage == 'Attendance') {
                            $style = "style='font-weight: bold'";
                        }
                        $studentMenuCategory[$studentMenuCount] = $mainMenu['Attendance'];
                        $studentMenuName[$studentMenuCount] = __('Attendance');
                        $studentMenuLink[$studentMenuCount] = "<li><a $style href='".$session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=Attendance'>".__('Attendance').'</a></li>';
                        ++$studentMenuCount;
                    }
                    if (isActionAccessible($guid, $connection2, '/modules/Behaviour/behaviour_view.php')) {
                        $style = '';
                        if ($subpage == 'Behaviour') {
                            $style = "style='font-weight: bold'";
                        }
                        $studentMenuCategory[$studentMenuCount] = $mainMenu['Behaviour'];
                        $studentMenuName[$studentMenuCount] = __('Behaviour');
                        $studentMenuLink[$studentMenuCount] = "<li><a $style href='".$session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search."&search=$search&allStudents=$allStudents&subpage=Behaviour'>".__('Behaviour').'</a></li>';
                        ++$studentMenuCount;
                    }


                    //Check for hooks, and slot them into array

                        $dataHooks = array();
                        $sqlHooks = "SELECT * FROM gibbonHook WHERE type='Student Profile'";
                        $resultHooks = $connection2->prepare($sqlHooks);
                        $resultHooks->execute($dataHooks);

                    if ($resultHooks->rowCount() > 0) {
                        $hooks = array();
                        $count = 0;
                        while ($rowHooks = $resultHooks->fetch()) {
                            $options = unserialize($rowHooks['options']);
                            //Check for permission to hook

                                $dataHook = array('gibbonRoleIDCurrent' => $session->get('gibbonRoleIDCurrent'), 'sourceModuleName' => $options['sourceModuleName'],  'sourceModuleAction' => $options['sourceModuleAction']);
                                $sqlHook = "SELECT gibbonHook.name, gibbonModule.name AS module, gibbonAction.name AS action
                                        FROM gibbonHook
                                        JOIN gibbonModule ON (gibbonHook.gibbonModuleID=gibbonModule.gibbonModuleID)
                                        JOIN gibbonAction ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID)
                                        JOIN gibbonPermission ON (gibbonPermission.gibbonActionID=gibbonAction.gibbonActionID)
                                        WHERE gibbonModule.name=:sourceModuleName
                                        AND FIND_IN_SET(gibbonAction.name, :sourceModuleAction)
                                        AND gibbonPermission.gibbonRoleID=:gibbonRoleIDCurrent
                                        AND gibbonAction.gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name=:sourceModuleName)
                                        AND gibbonHook.type='Student Profile'
                                        ORDER BY name";
                                $resultHook = $connection2->prepare($sqlHook);
                                $resultHook->execute($dataHook);
                            if ($resultHook->rowCount() >= 1) {
                                $style = '';
                                if ($hook == $rowHooks['name'] and $_GET['module'] == $options['sourceModuleName']) {
                                    $style = "style='font-weight: bold'";
                                }
                                $studentMenuCategory[$studentMenuCount] = $mainMenu[$options['sourceModuleName']];
                                $studentMenuName[$studentMenuCount] = __($rowHooks['name']);
                                $studentMenuLink[$studentMenuCount] = "<li><a $style href='".$session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonPersonID=$gibbonPersonID&search=".$search.'&hook='.$rowHooks['name'].'&module='.$options['sourceModuleName'].'&action='.$options['sourceModuleAction'].'&gibbonHookID='.$rowHooks['gibbonHookID']."'>".__($rowHooks['name']).'</a></li>';
                                ++$studentMenuCount;
                                ++$count;
                            }
                        }
                    }

                    //Menu ordering categories
                    $mainMenuCategoryOrder = getSettingByScope($connection2, 'System', 'mainMenuCategoryOrder');
                    $orders = explode(',', $mainMenuCategoryOrder);

                    //Sort array
                    @array_multisort($studentMenuCategory, $studentMenuName, $studentMenuLink);

                    //Spit out array whilt sorting by $mainMenuCategoryOrder
                    if (count($studentMenuCategory) > 0) {
                        foreach ($orders as $order) {
                            //Check for entries
                            $countEntries = 0;
                            for ($i = 0; $i < count($studentMenuCategory); ++$i) {
                                if ($studentMenuCategory[$i] == $order) {
                                    $countEntries ++;
                                }
                            }

                            if ($countEntries > 0) {
                                 $sidebarExtra .= '<h4>'.__($order).'</h4>';
                                 $sidebarExtra .= "<ul class='moduleMenu'>";
                                for ($i = 0; $i < count($studentMenuCategory); ++$i) {
                                    if ($studentMenuCategory[$i] == $order) {
                                         $sidebarExtra .= $studentMenuLink[$i];
                                    }
                                }

                                 $sidebarExtra .= '</ul>';
                            }
                        }
                    }

                    $sidebarExtra .= '</div>';

                    $session->set('sidebarExtra', $sidebarExtra);
                }
            }
        }
    }
}
