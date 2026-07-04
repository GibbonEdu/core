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

namespace Gibbon\UI\Dashboard;

use Gibbon\Forms\OutputableInterface;
use Gibbon\Contracts\Services\Session;
use Gibbon\Tables\Prefab\RollGroupTable;
use Gibbon\Contracts\Database\Connection;

/**
 * Staff Dashboard View Composer
 *
 * @version  v18
 * @since    v18
 */
class StaffDashboard implements OutputableInterface
{
    protected $db;
    protected $session;
    protected $rollGroupTable;

    public function __construct(Connection $db, Session $session, RollGroupTable $rollGroupTable)
    {
        $this->db = $db;
        $this->session = $session;
        $this->rollGroupTable = $rollGroupTable;
    }

    public function getOutput()
    {
        $guid = $this->session->get('guid');
        $connection2 = $this->db->getConnection();

        $output = '';
        $smartWorkflowHelp = getSmartWorkflowHelp($connection2, $guid);
        if ($smartWorkflowHelp != false) {
            $output .= $smartWorkflowHelp;
        }

        $output .= '<h2>'.
            __('Staff Dashboard').
            '</h2>'.
            "<div style='margin-bottom: 30px; margin-left: 1%; float: left; width: 100%'>";

        $dashboardContents = $this->renderDashboard();

        if ($dashboardContents == false) {
            $output .= "<div class='error'>".
                __('There are no records to display.').
                '</div>';
        } else {
            $output .= $dashboardContents;
        }
        $output .= '</div>';

        return $output;
    }

    protected function renderDashboard()
    {
        $guid = $this->session->get('guid');
        $connection2 = $this->db->getConnection();
        $gibbonPersonID = $this->session->get('gibbonPersonID');

        $return = false;

        //GET PLANNER
        $planner = false;
        $date = date('Y-m-d');
        try {
            $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
            $sql = "(SELECT 'Total Students' AS label, COUNT(gibbonStudentEnrolment.gibbonPersonID) AS total
                        FROM gibbonStudentEnrolment
                        JOIN gibbonPerson ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                        JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID)
                        WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                            AND gibbonPerson.status='Full'
                            AND gibbonRole.nameShort='Std')
                UNION
                    (SELECT 'Girls' AS label, COUNT(gibbonStudentEnrolment.gibbonPersonID) AS total
                        FROM gibbonStudentEnrolment
                        JOIN gibbonPerson ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                        JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID)
                        WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                            AND gibbonPerson.status='Full'
                            AND gibbonRole.nameShort='Std'
                            AND gibbonPerson.gender='F')
                UNION
                    (SELECT 'Boys' AS label, COUNT(gibbonStudentEnrolment.gibbonPersonID) AS total
                        FROM gibbonStudentEnrolment
                        JOIN gibbonPerson ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                        JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID)
                        WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                            AND gibbonPerson.status='Full'
                            AND gibbonRole.nameShort='Std'
                            AND gibbonPerson.gender='M')
                UNION
                    (SELECT 'Unknown gender' AS label, COUNT(gibbonStudentEnrolment.gibbonPersonID) AS total
                        FROM gibbonStudentEnrolment
                        JOIN gibbonPerson ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                        JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID)
                        WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                            AND gibbonPerson.status='Full'
                            AND gibbonRole.nameShort='Std'
                            AND gibbonPerson.gender='Unspecified')
                UNION
                    (SELECT 'Teachers' AS label, COUNT(gibbonPersonID) AS total
                        FROM gibbonPerson
                        JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID)
                        WHERE gibbonPerson.status='Full'
                        UNION
                            AND gibbonRole.nameShort='Tcr')
                    (SELECT 'Staff' AS label, COUNT(gibbonPersonID) AS total
                        FROM gibbonPerson
                        JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID)
                        WHERE gibbonPerson.status='Full'
                            AND gibbonRole.nameShort='Sst')
                UNION
                    (SELECT 'Centers' AS label, COUNT(name) AS total
                        FROM gibbonRollGroup
                        WHERE gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID)
                            ";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $planner .= "<div class='error'>".$e->getMessage().'</div>';
        }
        $planner .= '<h2>';
        $planner .= __("Statistics - School year: ".$_SESSION[$guid]['gibbonSchoolYearName']); //GS//
        $planner .= '</h2>';
        if ($result->rowCount() < 1) {
            $planner .= "<div class='warning'>";
            $planner .= __('There are no records to display.');
            $planner .= '</div>';
        } else {

            $planner .= "<table cellspacing='0' style='width: 100%'>";
            $planner .= "<tr class='head'>";
            $planner .= '<th>';
            $planner .= __('Type').'<br/>';
            $planner .= '</th>';
            $planner .= '<th>';
            $planner .= __('Number').'</br>';
            $planner .= '</th>';
            $planner .= '</tr>';

            $count = 0;
            $rowNum = 'odd';
            while ($row = $result->fetch()) {
                if (!($row['role'] == 'Student' and $row['viewableStudents'] == 'N')) {
                    if ($count % 2 == 0) {
                        $rowNum = 'even';
                    } else {
                        $rowNum = 'odd';
                    }
                    ++$count;

                    //Highlight class in progress
                    if ((date('H:i:s') > $row['timeStart']) and (date('H:i:s') < $row['timeEnd']) and ($date) == date('Y-m-d')) {
                        $rowNum = 'current';
                    }

                    //COLOR ROW BY STATUS!
                    $planner .= "<tr class=$rowNum>";
                    $planner .= '<td>';
                    $planner .= $row['course'].'.'.$row['class'].'<br/>';
                    $planner .= "<span style='font-style: italic; font-size: 75%'>".substr($row['timeStart'], 0, 5).'-'.substr($row['timeEnd'], 0, 5).'</span>';
                    $planner .= '</td>';
                    $planner .= '<td>';
                    $planner .= '<b>'.$row['name'].'</b><br/>';
                    $planner .= "<div style='font-size: 85%; font-style: italic'>";
                    $unit = getUnit($connection2, $row['gibbonUnitID'], $row['gibbonCourseClassID']);
                    if (isset($unit[0])) {
                        $planner .= $unit[0];
                        if ($unit[1] != '') {
                            $planner .= '<br/><i>'.$unit[1].' '.__('Unit').'</i>';
                        }
                    }
                    $planner .= '</div>';
                    $planner .= '</td>';
                    $planner .= '<td>';
                    if ($row['homework'] == 'N' and $row['myHomeworkDueDateTime'] == '') {
                        $planner .= __('No');
                    } else {
                        if ($row['homework'] == 'Y') {
                            $planner .= __('Yes').': '.__('Teacher Recorded').'<br/>';
                            if ($row['homeworkSubmission'] == 'Y') {
                                $planner .= "<span style='font-size: 85%; font-style: italic'>+".__('Submission').'</span><br/>';
                                if ($row['homeworkCrowdAssess'] == 'Y') {
                                    $planner .= "<span style='font-size: 85%; font-style: italic'>+".__('Crowd Assessment').'</span><br/>';
                                }
                            }
                        }
                        if ($row['myHomeworkDueDateTime'] != '') {
                            $planner .= __('Yes').': '.__('Student Recorded').'</br>';
                        }
                    }
                    $planner .= '</td>';
                    $planner .= '<td id="wordWrap">';
                    $planner .= $row['summary'];
                    $planner .= '</td>';
                    $planner .= '<td>';
                    $planner .= "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Planner/planner_view_full.php&viewBy=class&gibbonCourseClassID='.$row['gibbonCourseClassID'].'&gibbonPlannerEntryID='.$row['gibbonPlannerEntryID']."'><img title='".__('View')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a>";
                    $planner .= '</td>';
                    $planner .= '</tr>';
                }
            }
            $planner .= '</table>';
        }

        //GET TIMETABLE
        $timetable = false;
        if (isActionAccessible($guid, $connection2, '/modules/Timetable/tt.php') and $_SESSION[$guid]['username'] != '' and getRoleCategory($_SESSION[$guid]['gibbonRoleIDCurrent'], $connection2) == 'Staff') {

            $timetable .= '
            <script type="text/javascript">
                $(document).ready(function(){
                    $("#tt").load("'.$_SESSION[$guid]['absoluteURL'].'/index_tt_ajax.php",{"gibbonTTID": "'.@$_GET['gibbonTTID'].'", "ttDate": "'.@$_POST['ttDate'].'", "fromTT": "'.@$_POST['fromTT'].'", "personalCalendar": "'.@$_POST['personalCalendar'].'", "schoolCalendar": "'.@$_POST['schoolCalendar'].'", "spaceBookingCalendar": "'.@$_POST['spaceBookingCalendar'].'"});
                });
            </script>   ';

            $timetable .= '<h2>'.__('My Timetable').'</h2>';
            $timetable .= "<div id='tt' name='tt' style='width: 100%; min-height: 40px; text-align: center'>";
            $timetable .= "<img style='margin: 10px 0 5px 0' src='".$_SESSION[$guid]['absoluteURL']."/themes/Default/img/loading.gif' alt='".__('Loading')."' onclick='return false;' /><br/><p style='text-align: center'>".__('Loading').'</p>';
            $timetable .= '</div>';
        }

        //GET ROLL GROUPS
        $rollGroups = array();
        $rollGroupCount = 0;
        $count = 0;
        try {
            $dataRollGroups = array('gibbonPersonIDTutor' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonPersonIDTutor2' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonPersonIDTutor3' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
            $sqlRollGroups = 'SELECT * FROM gibbonRollGroup WHERE (gibbonPersonIDTutor=:gibbonPersonIDTutor OR gibbonPersonIDTutor2=:gibbonPersonIDTutor2 OR gibbonPersonIDTutor3=:gibbonPersonIDTutor3) AND gibbonSchoolYearID=:gibbonSchoolYearID';
            $resultRollGroups = $connection2->prepare($sqlRollGroups);
            $resultRollGroups->execute($dataRollGroups);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        $attendanceAccess = isActionAccessible($guid, $connection2, '/modules/Attendance/attendance_take_byRollGroup.php');

        while ($rowRollGroups = $resultRollGroups->fetch()) {
            $rollGroups[$count][0] = $rowRollGroups['gibbonRollGroupID'];
            $rollGroups[$count][1] = $rowRollGroups['nameShort'];

            //Roll group table
            $this->rollGroupTable->build($rowRollGroups['gibbonRollGroupID'], true, false, 'rollOrder, surname, preferredName');
            $this->rollGroupTable->setTitle('');

            if ($rowRollGroups['attendance'] == 'Y' AND $attendanceAccess) {
                $this->rollGroupTable->addHeaderAction('attendance', __('Take Attendance'))
                    ->setURL('/modules/Attendance/attendance_take_byRollGroup.php')
                    ->addParam('gibbonRollGroupID', $rowRollGroups['gibbonRollGroupID'])
                    ->setIcon('attendance')
                    ->displayLabel()
                    ->append(' | ');
            }

            $this->rollGroupTable->addHeaderAction('export', __('Export to Excel'))
                ->setURL('/indexExport.php')
                ->addParam('gibbonRollGroupID', $rowRollGroups['gibbonRollGroupID'])
                ->directLink()
                ->displayLabel();

            $rollGroups[$count][2] = $this->rollGroupTable->getOutput();

            $behaviourView = isActionAccessible($guid, $connection2, '/modules/Behaviour/behaviour_view.php');
            if ($behaviourView) {
                //Behaviour
                $rollGroups[$count][3] = '';
                $plural = 's';
                if ($resultRollGroups->rowCount() == 1) {
                    $plural = '';
                }
                try {
                    $dataBehaviour = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonSchoolYearID2' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonRollGroupID' => $rollGroups[$count][0]);
                    $sqlBehaviour = 'SELECT gibbonBehaviour.*, student.surname AS surnameStudent, student.preferredName AS preferredNameStudent, creator.surname AS surnameCreator, creator.preferredName AS preferredNameCreator, creator.title FROM gibbonBehaviour JOIN gibbonPerson AS student ON (gibbonBehaviour.gibbonPersonID=student.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=student.gibbonPersonID) JOIN gibbonPerson AS creator ON (gibbonBehaviour.gibbonPersonIDCreator=creator.gibbonPersonID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonBehaviour.gibbonSchoolYearID=:gibbonSchoolYearID2 AND gibbonRollGroupID=:gibbonRollGroupID ORDER BY timestamp DESC';
                    $resultBehaviour = $connection2->prepare($sqlBehaviour);
                    $resultBehaviour->execute($dataBehaviour);
                } catch (PDOException $e) {
                    $rollGroups[$count][3] .= "<div class='error'>".$e->getMessage().'</div>';
                }

                if (isActionAccessible($guid, $connection2, '/modules/Behaviour/behaviour_manage_add.php')) {
                    $rollGroups[$count][3] .= "<div class='linkTop'>";
                    $rollGroups[$count][3] .= "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Behaviour/behaviour_manage_add.php&gibbonPersonID=&gibbonRollGroupID=&gibbonYearGroupID=&type='>".__('Add')."<img style='margin: 0 0 -4px 5px' title='".__('Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
                    $policyLink = getSettingByScope($connection2, 'Behaviour', 'policyLink');
                    if ($policyLink != '') {
                        $rollGroups[$count][3] .= " | <a target='_blank' href='$policyLink'>".__('View Behaviour Policy').'</a>';
                    }
                    $rollGroups[$count][3] .= '</div>';
                }

                if ($resultBehaviour->rowCount() < 1) {
                    $rollGroups[$count][3] .= "<div class='error'>";
                    $rollGroups[$count][3] .= __('There are no records to display.');
                    $rollGroups[$count][3] .= '</div>';
                } else {
                    $rollGroups[$count][3] .= "<table cellspacing='0' style='width: 100%'>";
                    $rollGroups[$count][3] .= "<tr class='head'>";
                    $rollGroups[$count][3] .= '<th>';
                    $rollGroups[$count][3] .= __('Student & Date');
                    $rollGroups[$count][3] .= '</th>';
                    $rollGroups[$count][3] .= '<th>';
                    $rollGroups[$count][3] .= __('Type');
                    $rollGroups[$count][3] .= '</th>';
                    $rollGroups[$count][3] .= '<th>';
                    $rollGroups[$count][3] .= __('Descriptor');
                    $rollGroups[$count][3] .= '</th>';
                    $rollGroups[$count][3] .= '<th>';
                    $rollGroups[$count][3] .= __('Level');
                    $rollGroups[$count][3] .= '</th>';
                    $rollGroups[$count][3] .= '<th>';
                    $rollGroups[$count][3] .= __('Teacher');
                    $rollGroups[$count][3] .= '</th>';
                    $rollGroups[$count][3] .= '<th>';
                    $rollGroups[$count][3] .= __('Action');
                    $rollGroups[$count][3] .= '</th>';
                    $rollGroups[$count][3] .= '</tr>';

                    $count2 = 0;
                    $rowNum = 'odd';
                    while ($rowBehaviour = $resultBehaviour->fetch()) {
                        if ($count2 % 2 == 0) {
                            $rowNum = 'even';
                        } else {
                            $rowNum = 'odd';
                        }
                        ++$count2;

                        //COLOR ROW BY STATUS!
                        $rollGroups[$count][3] .= "<tr class=$rowNum>";
                        $rollGroups[$count][3] .= '<td>';
                        $rollGroups[$count][3] .= '<b>'.formatName('', $rowBehaviour['preferredNameStudent'], $rowBehaviour['surnameStudent'], 'Student', false).'</b><br/>';
                        if (substr($rowBehaviour['timestamp'], 0, 10) > $rowBehaviour['date']) {
                            $rollGroups[$count][3] .= __('Date Updated').': '.dateConvertBack($guid, substr($rowBehaviour['timestamp'], 0, 10)).'<br/>';
                            $rollGroups[$count][3] .= __('Incident Date').': '.dateConvertBack($guid, $rowBehaviour['date']).'<br/>';
                        } else {
                            $rollGroups[$count][3] .= dateConvertBack($guid, $rowBehaviour['date']).'<br/>';
                        }
                        $rollGroups[$count][3] .= '</td>';
                        $rollGroups[$count][3] .= "<td style='text-align: center'>";
                        if ($rowBehaviour['type'] == 'Negative') {
                            $rollGroups[$count][3] .= "<img title='".__('Negative')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconCross.png'/> ";
                        } elseif ($rowBehaviour['type'] == 'Positive') {
                            $rollGroups[$count][3] .= "<img title='".__('Positive')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconTick.png'/> ";
                        }
                        $rollGroups[$count][3] .= '</td>';
                        $rollGroups[$count][3] .= '<td>';
                        $rollGroups[$count][3] .= trim($rowBehaviour['descriptor']);
                        $rollGroups[$count][3] .= '</td>';
                        $rollGroups[$count][3] .= '<td>';
                        $rollGroups[$count][3] .= trim($rowBehaviour['level']);
                        $rollGroups[$count][3] .= '</td>';
                        $rollGroups[$count][3] .= '<td>';
                        $rollGroups[$count][3] .= formatName($rowBehaviour['title'], $rowBehaviour['preferredNameCreator'], $rowBehaviour['surnameCreator'], 'Staff', false).'<br/>';
                        $rollGroups[$count][3] .= '</td>';
                        $rollGroups[$count][3] .= '<td>';
                        $rollGroups[$count][3] .= "<script type='text/javascript'>";
                        $rollGroups[$count][3] .= '$(document).ready(function(){';
                        $rollGroups[$count][3] .= "\$(\".comment-$count2\").hide();";
                        $rollGroups[$count][3] .= "\$(\".show_hide-$count2\").fadeIn(1000);";
                        $rollGroups[$count][3] .= "\$(\".show_hide-$count2\").click(function(){";
                        $rollGroups[$count][3] .= "\$(\".comment-$count2\").fadeToggle(1000);";
                        $rollGroups[$count][3] .= '});';
                        $rollGroups[$count][3] .= '});';
                        $rollGroups[$count][3] .= '</script>';
                        if ($rowBehaviour['comment'] != '') {
                            $rollGroups[$count][3] .= "<a title='".__('View Description')."' class='show_hide-$count2' onclick='false' href='#'><img style='padding-right: 5px' src='".$_SESSION[$guid]['absoluteURL']."/themes/Default/img/page_down.png' alt='".__('Show Comment')."' onclick='return false;' /></a>";
                        }
                        $rollGroups[$count][3] .= '</td>';
                        $rollGroups[$count][3] .= '</tr>';
                        if ($rowBehaviour['comment'] != '') {
                            if ($rowBehaviour['type'] == 'Positive') {
                                $bg = 'background-color: #D4F6DC;';
                            } else {
                                $bg = 'background-color: #F6CECB;';
                            }
                            $rollGroups[$count][3] .= "<tr class='comment-$count2' id='comment-$count2'>";
                            $rollGroups[$count][3] .= "<td style='$bg' colspan=6>";
                            $rollGroups[$count][3] .= $rowBehaviour['comment'];
                            $rollGroups[$count][3] .= '</td>';
                            $rollGroups[$count][3] .= '</tr>';
                        }
                        $rollGroups[$count][3] .= '</tr>';
                        $rollGroups[$count][3] .= '</tr>';
                    }
                    $rollGroups[$count][3] .= '</table>';
                }
            }

            ++$count;
            ++$rollGroupCount;
        }

        //GET HOOKS INTO DASHBOARD
        $hooks = array();
        try {
            $dataHooks = array();
            $sqlHooks = "SELECT * FROM gibbonHook WHERE type='Staff Dashboard'";
            $resultHooks = $connection2->prepare($sqlHooks);
            $resultHooks->execute($dataHooks);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }
        if ($resultHooks->rowCount() > 0) {
            $count = 0;
            while ($rowHooks = $resultHooks->fetch()) {
                $options = unserialize($rowHooks['options']);
                //Check for permission to hook
                try {
                    $dataHook = array('gibbonRoleIDCurrent' => $_SESSION[$guid]['gibbonRoleIDCurrent'], 'sourceModuleName' => $options['sourceModuleName']);
                    $sqlHook = "SELECT gibbonHook.name, gibbonModule.name AS module, gibbonAction.name AS action FROM gibbonHook JOIN gibbonModule ON (gibbonHook.gibbonModuleID=gibbonModule.gibbonModuleID) JOIN gibbonAction ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) JOIN gibbonPermission ON (gibbonPermission.gibbonActionID=gibbonAction.gibbonActionID) WHERE gibbonAction.gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE gibbonPermission.gibbonRoleID=:gibbonRoleIDCurrent AND name=:sourceModuleName) AND gibbonHook.type='Staff Dashboard'  AND gibbonAction.name='".$options['sourceModuleAction']."' AND gibbonModule.name='".$options['sourceModuleName']."' ORDER BY name";
                    $resultHook = $connection2->prepare($sqlHook);
                    $resultHook->execute($dataHook);
                } catch (PDOException $e) {
                }
                if ($resultHook->rowCount() == 1) {
                    $rowHook = $resultHook->fetch();
                    $hooks[$count]['name'] = $rowHooks['name'];
                    $hooks[$count]['sourceModuleName'] = $rowHook['module'];
                    $hooks[$count]['sourceModuleInclude'] = $options['sourceModuleInclude'];
                    ++$count;
                }
            }
        }

        if ($planner == false and $timetable == false and count($hooks) < 1) {
            $return .= "<div class='warning'>";
            $return .= __('There are no records to display.');
            $return .= '</div>';
        } else {
            $staffDashboardDefaultTab = getSettingByScope($connection2, 'School Admin', 'staffDashboardDefaultTab');
            $staffDashboardDefaultTabCount = null;

            $return .= "<div id='".$gibbonPersonID."tabs' style='margin: 0 0'>";
            $return .= '<ul>';
            $tabCount = 1;
            if ($planner != false or $timetable != false) {
                $return .= "<li><a href='#tabs".$tabCount."'>".__('Planner').'</a></li>';
                if ($staffDashboardDefaultTab == 'Planner')
                    $staffDashboardDefaultTabCount = $tabCount;
                ++$tabCount;
            }
            if (count($rollGroups) > 0) {
                foreach ($rollGroups as $rollGroup) {
                    $return .= "<li><a href='#tabs".$tabCount."'>".$rollGroup[1].'</a></li>';
                    ++$tabCount;
                    if ($behaviourView) {
                        $return .= "<li><a href='#tabs".$tabCount."'>".$rollGroup[1].' '.__('Behaviour').'</a></li>';
                        ++$tabCount;
                    }
                }
            }

            foreach ($hooks as $hook) {
                $return .= "<li><a href='#tabs".$tabCount."'>".__($hook['name']).'</a></li>';
                if ($staffDashboardDefaultTab == $hook['name'])
                    $staffDashboardDefaultTabCount = $tabCount;
                ++$tabCount;
            }
            $return .= '</ul>';

            $tabCount = 1;
            if ($planner != false or $timetable != false) {
                $return .= "<div id='tabs".$tabCount."'>";
                $return .= $planner;
                $return .= $timetable;
                $return .= '</div>';
                ++$tabCount;
            }
            if (count($rollGroups) > 0) {
                foreach ($rollGroups as $rollGroup) {
                    $return .= "<div id='tabs".$tabCount."'>";
                    $return .= $rollGroup[2];
                    $return .= '</div>';
                    ++$tabCount;

                    if ($behaviourView) {
                        $return .= "<div id='tabs".$tabCount."'>";
                        $return .= $rollGroup[3];
                        $return .= '</div>';
                        ++$tabCount;
                    }
                }
            }
            foreach ($hooks as $hook) {
                $return .= "<div style='min-height: 100px' id='tabs".$tabCount."'>";
                $include = $_SESSION[$guid]['absolutePath'].'/modules/'.$hook['sourceModuleName'].'/'.$hook['sourceModuleInclude'];
                if (!file_exists($include)) {
                    $return .= "<div class='error'>";
                    $return .= __('The selected page cannot be displayed due to a hook error.');
                    $return .= '</div>';
                } else {
                    $return .= include $include;
                }
                ++$tabCount;
                $return .= '</div>';
            }
            $return .= '</div>';
        }

        $defaultTab = 0;
        if (isset($_GET['tab'])) {
            $defaultTab = $_GET['tab'];
        }
        else if (!empty($staffDashboardDefaultTabCount)) {
            $defaultTab = $staffDashboardDefaultTabCount-1;
        }

        $return .= "<script type='text/javascript'>";
        $return .= '$( "#'.$gibbonPersonID.'tabs" ).tabs({';
        $return .= 'active: '.$defaultTab.',';
        $return .= 'ajaxOptions: {';
        $return .= 'error: function( xhr, status, index, anchor ) {';
        $return .= '$( anchor.hash ).html(';
        $return .= "\"Couldn't load this tab.\" );";
        $return .= '}';
        $return .= '}';
        $return .= '});';
        $return .= '</script>';

        return $return;
    }
}
