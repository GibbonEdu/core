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

use Gibbon\Services\Format;
use Gibbon\Forms\OutputableInterface;
use Gibbon\Contracts\Services\Session;
use Gibbon\Contracts\Database\Connection;

/**
 * Student Dashboard View Composer
 *
 * @version  v18
 * @since    v18
 */
class StudentDashboard implements OutputableInterface
{
    protected $db;
    protected $session;

    public function __construct(Connection $db, Session $session)
    {
        $this->db = $db;
        $this->session = $session;
    }

    public function getOutput()
    {
        $output = '<h2>'.
            __('Student Dashboard').
            '</h2>'.
            "<div style='margin-bottom: 30px; float: left; width: 100%'>";

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

        $homeworkNameSingular = getSettingByScope($connection2, 'Planner', 'homeworkNameSingular');

        $return = false;

        //GET PLANNER
        $planner = false;
        $date = date('Y-m-d');
        try {
            $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'date' => $date, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonSchoolYearID2' => $_SESSION[$guid]['gibbonSchoolYearID'], 'date2' => $date, 'gibbonPersonID2' => $_SESSION[$guid]['gibbonPersonID']);
            $sql = "(SELECT gibbonCourseClass.gibbonCourseClassID, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonPlannerEntry.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkSubmission, homeworkCrowdAssess, role, date, summary, gibbonPlannerEntryStudentHomework.homeworkDueDateTime AS myHomeworkDueDateTime FROM gibbonPlannerEntry 
            JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) 
            JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) 
            JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) 
            LEFT JOIN gibbonPlannerEntryStudentHomework ON (gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID AND gibbonPlannerEntryStudentHomework.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID) 
            LEFT JOIN (
                SELECT gibbonTTDayRowClass.gibbonCourseClassID, gibbonTTDayRowClass.gibbonTTDayRowClassID FROM gibbonTTDayDate JOIN gibbonTTDayRowClass ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDayRowClass.gibbonTTDayID) WHERE gibbonTTDayDate.date=:date) AS gibbonTTDayRowClassSubset ON (gibbonTTDayRowClassSubset.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID) 
            LEFT JOIN gibbonTTDayRowClassException ON (gibbonTTDayRowClassException.gibbonTTDayRowClassID=gibbonTTDayRowClassSubset.gibbonTTDayRowClassID AND gibbonTTDayRowClassException.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID)

            WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND date=:date 
            AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left'
            GROUP BY gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClassPerson.gibbonCourseClassPersonID
            HAVING COUNT(gibbonTTDayRowClassExceptionID) = 0
            ) 
            UNION 
            (SELECT gibbonCourseClass.gibbonCourseClassID, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonPlannerEntry.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkSubmission, homeworkCrowdAssess,  role, date, summary, NULL AS myHomeworkDueDateTime FROM gibbonPlannerEntry JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonPlannerEntryGuest ON (gibbonPlannerEntryGuest.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID2 AND date=:date2 AND gibbonPlannerEntryGuest.gibbonPersonID=:gibbonPersonID2) ORDER BY date, timeStart, course, class";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $planner .= "<div class='error'>".$e->getMessage().'</div>';
        }
        $planner .= '<h2>';
        $planner .= __("Today's Lessons");
        $planner .= '</h2>';
        if ($result->rowCount() < 1) {
            $planner .= "<div class='warning'>";
            $planner .= __('There are no records to display.');
            $planner .= '</div>';
        } else {
            $planner .= "<div class='linkTop'>";
            $planner .= "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner.php'>".__('View Planner').'</a>';
            $planner .= '</div>';

            $planner .= "<table cellspacing='0' style='width: 100%'>";
            $planner .= "<tr class='head'>";
            $planner .= '<th>';
            $planner .= __('Class').'<br/>';
            $planner .= '</th>';
            $planner .= '<th>';
            $planner .= __('Lesson').'</br>';
            $planner .= "<span style='font-size: 85%; font-style: italic'>".__('Unit').'</span>';
            $planner .= '</th>';
            $planner .= '<th>';
            $planner .=  __($homeworkNameSingular);
            $planner .= '</th>';
            $planner .= '<th>';
            $planner .= __('Summary');
            $planner .= '</th>';
            $planner .= '<th>';
            $planner .= __('Action');
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
                    $planner .= '<td class="wordWrap break-words">';
                    $planner .= Format::truncate($row['summary'], 360);
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
        if (isActionAccessible($guid, $connection2, '/modules/Timetable/tt.php') and $_SESSION[$guid]['username'] != '' and getRoleCategory($_SESSION[$guid]['gibbonRoleIDCurrent'], $connection2) == 'Student') {

            $timetable .= '
            <script type="text/javascript">
                $(document).ready(function(){
                    $("#tt").load("'.$_SESSION[$guid]['absoluteURL'].'/index_tt_ajax.php",{"gibbonTTID": "'.@$_GET['gibbonTTID'].'", "ttDate": "'.@$_POST['ttDate'].'", "fromTT": "'.@$_POST['fromTT'].'", "personalCalendar": "'.@$_POST['personalCalendar'].'", "schoolCalendar": "'.@$_POST['schoolCalendar'].'", "spaceBookingCalendar": "'.@$_POST['spaceBookingCalendar'].'"});
                });
            </script>';

            $timetable .= '<h2>'.__('My Timetable').'</h2>';
            $timetable .= "<div id='tt' name='tt' style='width: 100%; min-height: 40px; text-align: center'>";
            $timetable .= "<img style='margin: 10px 0 5px 0' src='".$_SESSION[$guid]['absoluteURL']."/themes/Default/img/loading.gif' alt='".__('Loading')."' onclick='return false;' /><br/><p style='text-align: center'>".__('Loading').'</p>';
            $timetable .= '</div>';
        }

        //GET HOOKS INTO DASHBOARD
        $hooks = array();
        
            $dataHooks = array();
            $sqlHooks = "SELECT * FROM gibbonHook WHERE type='Student Dashboard'";
            $resultHooks = $connection2->prepare($sqlHooks);
            $resultHooks->execute($dataHooks);
        if ($resultHooks->rowCount() > 0) {
            $count = 0;
            while ($rowHooks = $resultHooks->fetch()) {
                $options = unserialize($rowHooks['options']);
                //Check for permission to hook
                
                    $dataHook = array('gibbonRoleIDCurrent' => $_SESSION[$guid]['gibbonRoleIDCurrent'], 'sourceModuleName' => $options['sourceModuleName']);
                    $sqlHook = "SELECT gibbonHook.name, gibbonModule.name AS module, gibbonAction.name AS action FROM gibbonHook JOIN gibbonModule ON (gibbonHook.gibbonModuleID=gibbonModule.gibbonModuleID) JOIN gibbonAction ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) JOIN gibbonPermission ON (gibbonPermission.gibbonActionID=gibbonAction.gibbonActionID) WHERE gibbonAction.gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE name=:sourceModuleName) AND gibbonPermission.gibbonRoleID=:gibbonRoleIDCurrent AND gibbonHook.type='Student Dashboard' AND gibbonAction.name='".$options['sourceModuleAction']."' AND gibbonModule.name='".$options['sourceModuleName']."' ORDER BY name";
                    $resultHook = $connection2->prepare($sqlHook);
                    $resultHook->execute($dataHook);
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
            $studentDashboardDefaultTab = getSettingByScope($connection2, 'School Admin', 'studentDashboardDefaultTab');
            $studentDashboardDefaultTabCount = null;

            $return .= "<div id='".$gibbonPersonID."tabs' style='margin: 0 0'>";
            $return .= '<ul>';
            $tabCount = 1;
            if ($planner != false or $timetable != false) {
                $return .= "<li><a href='#tabs".$tabCount."'>".__('Planner').'</a></li>';
                if ($studentDashboardDefaultTab == 'Planner')
                    $studentDashboardDefaultTabCount = $tabCount;
                ++$tabCount;
            }
            foreach ($hooks as $hook) {
                $return .= "<li><a href='#tabs".$tabCount."'>".__($hook['name']).'</a></li>';
                if ($studentDashboardDefaultTab == $hook['name'])
                    $studentDashboardDefaultTabCount = $tabCount;
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
        else if (!is_null($studentDashboardDefaultTabCount)) {
            $defaultTab = $studentDashboardDefaultTabCount-1;
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
