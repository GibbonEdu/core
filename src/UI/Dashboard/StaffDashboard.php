<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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

use Gibbon\Http\Url;
use Gibbon\Services\Format;
use Gibbon\Data\Validator;
use Gibbon\Forms\OutputableInterface;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Contracts\Database\Connection;
use Gibbon\Contracts\Services\Session;
use Gibbon\Tables\Prefab\EnrolmentTable;
use Gibbon\Tables\Prefab\FormGroupTable;
use League\Container\ContainerAwareTrait;
use League\Container\ContainerAwareInterface;
use Gibbon\Domain\System\HookGateway;

/**
 * Staff Dashboard View Composer
 *
 * @version  v18
 * @since    v18
 */
class StaffDashboard implements OutputableInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var \Gibbon\Contracts\Database\Connection
     */
    protected $db;

    /**
     * @var \Gibbon\Contracts\Services\Session
     */
    protected $session;

    /**
     * @var \Gibbon\Tables\Prefab\FormGroupTable
     */
    protected $formGroupTable;

    /**
     * @var \Gibbon\Tables\Prefab\EnrolmentTable
     */
    protected $enrolmentTable;

    /**
     * @var SettingGateway
     */
    private $settingGateway;

    public function __construct(
        Connection $db,
        Session $session,
        FormGroupTable $formGroupTable,
        EnrolmentTable $enrolmentTable,
        SettingGateway $settingGateway
    ) {
        $this->db = $db;
        $this->session = $session;
        $this->formGroupTable = $formGroupTable;
        $this->enrolmentTable = $enrolmentTable;
        $this->settingGateway = $settingGateway;
    }

    public function getOutput()
    {
        $output = '<h2>'.
            __('Staff Dashboard').
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
        $session = $this->session;

        $return = false;

        $homeworkNameSingular = $this->settingGateway->getSettingByScope('Planner', 'homeworkNameSingular');

        //GET PLANNER
        $planner = false;
        $date = date('Y-m-d');
        try {
            $data = array('gibbonSchoolYearID' => $this->session->get('gibbonSchoolYearID'), 'date' => $date, 'gibbonPersonID' => $this->session->get('gibbonPersonID'), 'gibbonSchoolYearID2' => $this->session->get('gibbonSchoolYearID'), 'date2' => $date, 'gibbonPersonID2' => $this->session->get('gibbonPersonID'));
            $sql = "(SELECT gibbonCourseClass.gibbonCourseClassID, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonPlannerEntry.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, gibbonPlannerEntry.timeStart, gibbonPlannerEntry.timeEnd, viewableStudents, viewableParents, homework, homeworkSubmission, homeworkCrowdAssess, role, date, summary, gibbonPlannerEntryStudentHomework.homeworkDueDateTime AS myHomeworkDueDateTime
            FROM gibbonPlannerEntry
            JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
            JOIN gibbonCourseClassPerson ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID)
            JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
            LEFT JOIN gibbonPlannerEntryStudentHomework ON (gibbonPlannerEntryStudentHomework.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID AND gibbonPlannerEntryStudentHomework.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID)

            LEFT JOIN (
                SELECT gibbonTTDayRowClass.gibbonCourseClassID, gibbonTTDayRowClass.gibbonTTDayRowClassID, gibbonTTColumnRow.timeStart, gibbonTTColumnRow.timeEnd FROM gibbonTTDayDate JOIN gibbonTTDayRowClass ON (gibbonTTDayDate.gibbonTTDayID=gibbonTTDayRowClass.gibbonTTDayID) JOIN gibbonTTColumnRow ON (gibbonTTColumnRow.gibbonTTColumnRowID=gibbonTTDayRowClass.gibbonTTColumnRowID) WHERE gibbonTTDayDate.date=:date)
                AS gibbonTTDayRowClassSubset ON (gibbonTTDayRowClassSubset.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID AND gibbonTTDayRowClassSubset.timeStart=gibbonPlannerEntry.timeStart AND gibbonTTDayRowClassSubset.timeEnd=gibbonPlannerEntry.timeEnd)
            LEFT JOIN gibbonTTDayRowClassException ON (gibbonTTDayRowClassException.gibbonTTDayRowClassID=gibbonTTDayRowClassSubset.gibbonTTDayRowClassID AND gibbonTTDayRowClassException.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID)

            WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND date=:date AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND NOT role='Student - Left' AND NOT role='Teacher - Left'
            GROUP BY gibbonPlannerEntry.gibbonPlannerEntryID, gibbonCourseClassPerson.gibbonCourseClassPersonID
            HAVING COUNT(gibbonTTDayRowClassExceptionID) = 0

            )
            UNION
            (SELECT gibbonCourseClass.gibbonCourseClassID, gibbonPlannerEntry.gibbonPlannerEntryID, gibbonUnitID, gibbonPlannerEntry.gibbonCourseClassID, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class, gibbonPlannerEntry.name, timeStart, timeEnd, viewableStudents, viewableParents, homework, homeworkSubmission, homeworkCrowdAssess,  role, date, summary, NULL AS myHomeworkDueDateTime
            FROM gibbonPlannerEntry
            JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
            JOIN gibbonPlannerEntryGuest ON (gibbonPlannerEntryGuest.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID)
            JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
            WHERE gibbonSchoolYearID=:gibbonSchoolYearID2 AND date=:date2 AND gibbonPlannerEntryGuest.gibbonPersonID=:gibbonPersonID2
            )

            ORDER BY date, timeStart, course, class";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (\PDOException $e) {
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
            $planner .= "<a href='".Url::fromModuleRoute('Planner', 'planner')."'>".__('View Planner').'</a>';
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
            $planner .= __($homeworkNameSingular);
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
                    $planner .= "<a href='".Url::fromModuleRoute('Planner', 'planner_view_full')->withQueryParams([
                        'viewBy' => 'class',
                        'gibbonCourseClassID' => $row['gibbonCourseClassID'],
                        'gibbonPlannerEntryID' => $row['gibbonPlannerEntryID'],
                    ])."'><img title='".__('View')."' src='./themes/".$this->session->get('gibbonThemeName')."/img/plus.png'/></a>";
                    $planner .= '</td>';
                    $planner .= '</tr>';
                }
            }
            $planner .= '</table>';
        }

        //GET TIMETABLE
        $timetable = false;
        if (
            isActionAccessible($guid, $connection2, '/modules/Timetable/tt.php') and $this->session->get('username') != ''
            && $this->session->get('gibbonRoleIDCurrentCategory') == 'Staff'
        ) {
            $apiEndpoint = (string)Url::fromHandlerRoute('index_tt_ajax.php');
            $_POST = (new Validator(''))->sanitize($_POST);
            $jsonQuery = [
                'gibbonTTID' => $_GET['gibbonTTID'] ?? '',
                'ttDate' => $_POST['ttDate'] ?? '',
                'fromTT' => $_POST['fromTT'] ?? '',
                'personalCalendar' => $_POST['personalCalendar'] ?? '',
                'schoolCalendar' => $_POST['schoolCalendar'] ?? '',
                'spaceBookingCalendar' => $_POST['spaceBookingCalendar'] ?? '',
            ];
            $timetable .= '
            <script type="text/javascript">
                $(document).ready(function(){
                    $("#tt").load('.json_encode($apiEndpoint).', '.json_encode($jsonQuery).');
                });
            </script>';

            $timetable .= '<h2>'.__('My Timetable').'</h2>';
            $timetable .= "<div id='tt' name='tt' style='width: 100%; min-height: 40px; text-align: center'>";
            $timetable .= "<img style='margin: 10px 0 5px 0' src='".$this->session->get('absoluteURL')."/themes/Default/img/loading.gif' alt='".__('Loading')."' onclick='return false;' /><br/><p style='text-align: center'>".__('Loading').'</p>';
            $timetable .= '</div>';
        }

        //GET FORM GROUPS
        $formGroups = array();
        $formGroupCount = 0;
        $count = 0;

        $dataFormGroups = array('gibbonPersonIDTutor' => $this->session->get('gibbonPersonID'), 'gibbonPersonIDTutor2' => $this->session->get('gibbonPersonID'), 'gibbonPersonIDTutor3' => $this->session->get('gibbonPersonID'), 'gibbonSchoolYearID' => $this->session->get('gibbonSchoolYearID'));
        $sqlFormGroups = 'SELECT * FROM gibbonFormGroup WHERE (gibbonPersonIDTutor=:gibbonPersonIDTutor OR gibbonPersonIDTutor2=:gibbonPersonIDTutor2 OR gibbonPersonIDTutor3=:gibbonPersonIDTutor3) AND gibbonSchoolYearID=:gibbonSchoolYearID';
        $resultFormGroups = $this->db->select($sqlFormGroups, $dataFormGroups);

        $attendanceAccess = isActionAccessible($guid, $connection2, '/modules/Attendance/attendance_take_byFormGroup.php');

        while ($rowFormGroups = $resultFormGroups->fetch()) {
            $formGroups[$count][0] = $rowFormGroups['gibbonFormGroupID'];
            $formGroups[$count][1] = $rowFormGroups['nameShort'];

            //Form group table
            $formGroupTable = clone $this->formGroupTable;

            $formGroupTable->build($rowFormGroups['gibbonFormGroupID'], true, false, 'rollOrder, surname, preferredName');
            $formGroupTable->setTitle('');

            if ($rowFormGroups['attendance'] == 'Y' AND $attendanceAccess) {
                $formGroupTable->addHeaderAction('attendance', __('Take Attendance'))
                    ->setURL('/modules/Attendance/attendance_take_byFormGroup.php')
                    ->addParam('gibbonFormGroupID', $rowFormGroups['gibbonFormGroupID'])
                    ->setIcon('attendance')
                    ->displayLabel()
                    ->append(' | ');
            }

            $formGroupTable->addHeaderAction('export', __('Export to Excel'))
                ->setURL('/indexExport.php')
                ->addParam('gibbonFormGroupID', $rowFormGroups['gibbonFormGroupID'])
                ->directLink()
                ->displayLabel();

            $formGroups[$count][2] = $formGroupTable->getOutput();

            $behaviourView = isActionAccessible($guid, $connection2, '/modules/Behaviour/behaviour_view.php');
            if ($behaviourView) {
                //Behaviour
                $formGroups[$count][3] = '';
                $plural = 's';
                if ($resultFormGroups->rowCount() == 1) {
                    $plural = '';
                }
                try {
                    $dataBehaviour = array('gibbonSchoolYearID' => $this->session->get('gibbonSchoolYearID'), 'gibbonSchoolYearID2' => $this->session->get('gibbonSchoolYearID'), 'gibbonFormGroupID' => $formGroups[$count][0]);
                    $sqlBehaviour = 'SELECT gibbonBehaviour.*, student.surname AS surnameStudent, student.preferredName AS preferredNameStudent, creator.surname AS surnameCreator, creator.preferredName AS preferredNameCreator, creator.title FROM gibbonBehaviour JOIN gibbonPerson AS student ON (gibbonBehaviour.gibbonPersonID=student.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=student.gibbonPersonID) JOIN gibbonPerson AS creator ON (gibbonBehaviour.gibbonPersonIDCreator=creator.gibbonPersonID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonBehaviour.gibbonSchoolYearID=:gibbonSchoolYearID2 AND gibbonFormGroupID=:gibbonFormGroupID ORDER BY timestamp DESC';
                    $resultBehaviour = $connection2->prepare($sqlBehaviour);
                    $resultBehaviour->execute($dataBehaviour);
                } catch (\PDOException $e) {}

                if (isActionAccessible($guid, $connection2, '/modules/Behaviour/behaviour_manage_add.php')) {
                    $formGroups[$count][3] .= "<div class='linkTop'>";
                    $formGroups[$count][3] .= "<a href='".Url::fromModuleRoute('Behaviour', 'behaviour_manage_add')->withQueryParams([
                        'gibbonPersonID' => '',
                        'gibbonFormGroupID' => '',
                        'gibbonYearGroupID' => '',
                        'type' => '',
                    ]) . "'>".__('Add')."<img style='margin: 0 0 -4px 5px' title='".__('Add')."' src='./themes/".$this->session->get('gibbonThemeName')."/img/page_new.png'/></a>";
                    $policyLink = $this->settingGateway->getSettingByScope('Behaviour', 'policyLink');
                    if ($policyLink != '') {
                        $formGroups[$count][3] .= " | <a target='_blank' href='$policyLink'>".__('View Behaviour Policy').'</a>';
                    }
                    $formGroups[$count][3] .= '</div>';
                }

                if ($resultBehaviour->rowCount() < 1) {
                    $formGroups[$count][3] .= "<div class='error'>";
                    $formGroups[$count][3] .= __('There are no records to display.');
                    $formGroups[$count][3] .= '</div>';
                } else {
                    $formGroups[$count][3] .= "<table cellspacing='0' style='width: 100%'>";
                    $formGroups[$count][3] .= "<tr class='head'>";
                    $formGroups[$count][3] .= '<th>';
                    $formGroups[$count][3] .= __('Student & Date');
                    $formGroups[$count][3] .= '</th>';
                    $formGroups[$count][3] .= '<th>';
                    $formGroups[$count][3] .= __('Type');
                    $formGroups[$count][3] .= '</th>';
                    $formGroups[$count][3] .= '<th>';
                    $formGroups[$count][3] .= __('Descriptor');
                    $formGroups[$count][3] .= '</th>';
                    $formGroups[$count][3] .= '<th>';
                    $formGroups[$count][3] .= __('Level');
                    $formGroups[$count][3] .= '</th>';
                    $formGroups[$count][3] .= '<th>';
                    $formGroups[$count][3] .= __('Teacher');
                    $formGroups[$count][3] .= '</th>';
                    $formGroups[$count][3] .= '<th>';
                    $formGroups[$count][3] .= __('Action');
                    $formGroups[$count][3] .= '</th>';
                    $formGroups[$count][3] .= '</tr>';

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
                        $formGroups[$count][3] .= "<tr class=$rowNum>";
                        $formGroups[$count][3] .= '<td>';
                        $formGroups[$count][3] .= '<b>'.Format::name('', $rowBehaviour['preferredNameStudent'], $rowBehaviour['surnameStudent'], 'Student', false).'</b><br/>';
                        if (substr($rowBehaviour['timestamp'], 0, 10) > $rowBehaviour['date']) {
                            $formGroups[$count][3] .= __('Date Updated').': '.Format::date(substr($rowBehaviour['timestamp'], 0, 10)).'<br/>';
                            $formGroups[$count][3] .= __('Incident Date').': '.Format::date($rowBehaviour['date']).'<br/>';
                        } else {
                            $formGroups[$count][3] .= Format::date($rowBehaviour['date']).'<br/>';
                        }
                        $formGroups[$count][3] .= '</td>';
                        $formGroups[$count][3] .= "<td style='text-align: center'>";
                        if ($rowBehaviour['type'] == 'Negative') {
                            $formGroups[$count][3] .= "<img title='".__('Negative')."' src='./themes/".$this->session->get('gibbonThemeName')."/img/iconCross.png'/> ";
                        } elseif ($rowBehaviour['type'] == 'Positive') {
                            $formGroups[$count][3] .= "<img title='".__('Positive')."' src='./themes/".$this->session->get('gibbonThemeName')."/img/iconTick.png'/> ";
                        }
                        $formGroups[$count][3] .= '</td>';
                        $formGroups[$count][3] .= '<td>';
                        $formGroups[$count][3] .= trim($rowBehaviour['descriptor'] ?? '');
                        $formGroups[$count][3] .= '</td>';
                        $formGroups[$count][3] .= '<td>';
                        $formGroups[$count][3] .= trim($rowBehaviour['level'] ?? '');
                        $formGroups[$count][3] .= '</td>';
                        $formGroups[$count][3] .= '<td>';
                        $formGroups[$count][3] .= Format::name($rowBehaviour['title'], $rowBehaviour['preferredNameCreator'], $rowBehaviour['surnameCreator'], 'Staff', false).'<br/>';
                        $formGroups[$count][3] .= '</td>';
                        $formGroups[$count][3] .= '<td>';
                        $formGroups[$count][3] .= "<script type='text/javascript'>";
                        $formGroups[$count][3] .= '$(document).ready(function(){';
                        $formGroups[$count][3] .= "\$(\".comment-$count2\").hide();";
                        $formGroups[$count][3] .= "\$(\".show_hide-$count2\").fadeIn(1000);";
                        $formGroups[$count][3] .= "\$(\".show_hide-$count2\").click(function(){";
                        $formGroups[$count][3] .= "\$(\".comment-$count2\").fadeToggle(1000);";
                        $formGroups[$count][3] .= '});';
                        $formGroups[$count][3] .= '});';
                        $formGroups[$count][3] .= '</script>';
                        if ($rowBehaviour['comment'] != '') {
                            $formGroups[$count][3] .= "<a title='".__('View Description')."' class='show_hide-$count2' onclick='false' href='#'><img style='padding-right: 5px' src='".$this->session->get('absoluteURL')."/themes/Default/img/page_down.png' alt='".__('Show Comment')."' onclick='return false;' /></a>";
                        }
                        $formGroups[$count][3] .= '</td>';
                        $formGroups[$count][3] .= '</tr>';
                        if ($rowBehaviour['comment'] != '') {
                            if ($rowBehaviour['type'] == 'Positive') {
                                $bg = 'background-color: #D4F6DC;';
                            } else {
                                $bg = 'background-color: #F6CECB;';
                            }
                            $formGroups[$count][3] .= "<tr class='comment-$count2' id='comment-$count2'>";
                            $formGroups[$count][3] .= "<td style='$bg' colspan=6>";
                            $formGroups[$count][3] .= $rowBehaviour['comment'];
                            $formGroups[$count][3] .= '</td>';
                            $formGroups[$count][3] .= '</tr>';
                        }
                        $formGroups[$count][3] .= '</tr>';
                        $formGroups[$count][3] .= '</tr>';
                    }
                    $formGroups[$count][3] .= '</table>';
                }
            }

            ++$count;
            ++$formGroupCount;
        }

        // GET HOOKS INTO DASHBOARD
        $hooks = $this->getContainer()->get(HookGateway::class)->getAccessibleHooksByType('Staff Dashboard', $this->session->get('gibbonRoleIDCurrent'));

        if ($planner == false and $timetable == false and count($hooks) < 1) {
            $return .= "<div class='warning'>";
            $return .= __('There are no records to display.');
            $return .= '</div>';
        } else {
            $staffDashboardDefaultTab = $this->settingGateway->getSettingByScope('School Admin', 'staffDashboardDefaultTab');
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
            if (count($formGroups) > 0) {
                foreach ($formGroups as $formGroup) {
                    $return .= "<li><a href='#tabs".$tabCount."'>".$formGroup[1].'</a></li>';
                    ++$tabCount;
                    if ($behaviourView) {
                        $return .= "<li><a href='#tabs".$tabCount."'>".$formGroup[1].' '.__('Behaviour').'</a></li>';
                        ++$tabCount;
                    }
                }
            }

            if (isActionAccessible($guid, $connection2, '/modules/Admissions/report_students_left.php') || isActionAccessible($guid, $connection2, '/modules/Admissions/report_students_new.php')) {
                $return .= "<li><a href='#tabs".$tabCount."'>".__('Enrolment').'</a></li>';
                if ($staffDashboardDefaultTab == 'Enrolment') {
                    $staffDashboardDefaultTabCount = $tabCount;
                }
                ++$tabCount;
            }

            foreach ($hooks as $hook) {
                $return .= "<li><a href='#tabs".$tabCount."'>".__($hook['name'], [], $hook['sourceModuleName']).'</a></li>';
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

            if (count($formGroups) > 0) {
                foreach ($formGroups as $formGroup) {
                    $return .= "<div id='tabs".$tabCount."'>";
                    $return .= $formGroup[2];
                    $return .= '</div>';
                    ++$tabCount;

                    if ($behaviourView) {
                        $return .= "<div id='tabs".$tabCount."'>";
                        $return .= $formGroup[3];
                        $return .= '</div>';
                        ++$tabCount;
                    }
                }
            }

            // Enrolment tab
            if (isActionAccessible($guid, $connection2, '/modules/Admissions/report_students_left.php') || isActionAccessible($guid, $connection2, '/modules/Admissions/report_students_new.php')) {
                $return .= "<div id='tabs".$tabCount."'>";
                $return .= $this->enrolmentTable->getOutput();
                $return .= '</div>';
                ++$tabCount;
            }

            foreach ($hooks as $hook) {
                // Set the module for this hook for translations
                $this->session->set('module', $hook['sourceModuleName']);

                $return .= "<div style='min-height: 100px' id='tabs".$tabCount."'>";
                $include = $this->session->get('absolutePath').'/modules/'.$hook['sourceModuleName'].'/'.$hook['sourceModuleInclude'];
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

        $defaultTab = preg_replace('/[^0-9]/', '', $_GET['tab'] ?? 0);

        if (!isset($_GET['tab']) && !empty($staffDashboardDefaultTabCount)) {
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
