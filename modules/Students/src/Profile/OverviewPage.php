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

namespace Gibbon\Module\Students\Profile;

use Gibbon\Support\Facades\Access;
use Gibbon\Contracts\Services\Session;
use Gibbon\Contracts\Database\Connection;
use Gibbon\Domain\Students\MedicalGateway;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\User\RoleGateway;
use Gibbon\Domain\School\YearGroupGateway;
use Gibbon\Domain\FormGroups\FormGroupGateway;
use Gibbon\Domain\School\HouseGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\Students\StudentAttendanceStatus;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\DataSet;
use Gibbon\Tables\View\GridView;
use Gibbon\UI\Timetable\Timetable;
use Gibbon\UI\Timetable\TimetableContext;

/**
 * OverviewPage
 * 
 * Displays student overview information including photo, alerts, medical summary,
 * general information, list of teachers, and timetable.
 * 
 * @package Gibbon\Module\Students\Profile
 */
class OverviewPage extends ProfilePage implements ContainerAwareInterface
{
    use ContainerAwareTrait;
    
    private MedicalGateway $medicalGateway;
    private StudentGateway $studentGateway;
    private UserGateway $userGateway;
    private RoleGateway $roleGateway;
    private YearGroupGateway $yearGroupGateway;
    private FormGroupGateway $formGroupGateway;
    private HouseGateway $houseGateway;
    private SettingGateway $settingGateway;
    private StudentAttendanceStatus $attendanceStatus;
    private Connection $pdo;

    public function __construct(
        Session $session,
        MedicalGateway $medicalGateway,
        StudentGateway $studentGateway,
        UserGateway $userGateway,
        RoleGateway $roleGateway,
        YearGroupGateway $yearGroupGateway,
        FormGroupGateway $formGroupGateway,
        HouseGateway $houseGateway,
        SettingGateway $settingGateway,
        StudentAttendanceStatus $attendanceStatus,
        Connection $pdo
    ) {
        parent::__construct($session);
        $this->medicalGateway = $medicalGateway;
        $this->studentGateway = $studentGateway;
        $this->userGateway = $userGateway;
        $this->roleGateway = $roleGateway;
        $this->yearGroupGateway = $yearGroupGateway;
        $this->formGroupGateway = $formGroupGateway;
        $this->houseGateway = $houseGateway;
        $this->settingGateway = $settingGateway;
        $this->attendanceStatus = $attendanceStatus;
        $this->pdo = $pdo;
    }

    /**
     * Check if the current user has permission to view the overview page
     * 
     * @return bool True if user has access, false otherwise
     */
    public function checkAccess(): bool
    {
        return Access::allows('Students', 'student_view_details', 'View Student Profile_full');
    }

    /**
     * Get the page name for display
     *
     * @return string Translated page name
     */
    public function getPageName(): string
    {
        return __('Overview');
    }


    /**
     * Generate HTML output for the overview page
     * 
     * @return string HTML content for display
     */
    public function getOutput(): string
    {
        // Guard clause: validate student context
        if (empty($this->gibbonPersonID)) {
            return Format::alert(__('Invalid student ID.'));
        }

        // Fetch student data
        $student = $this->fetchStudentInfo();
        
        // Guard clause: check if student exists
        if (empty($student)) {
            return Format::alert(__('The selected record does not exist, or you do not have access to it.'));
        }

        $output = '';

        // Display medical alerts
        $output .= $this->renderMedicalAlerts();

        // Display current attendance status
        $output .= $this->renderAttendanceStatus($student);

        // Display general information table
        $output .= $this->renderGeneralInfo($student);

        // Display list of student's teachers
        $output .= $this->renderTeachersList($student);

        // Display timetable
        $output .= $this->renderTimetable($student);

        return $output;
    }

    /**
     * Fetch student information from database
     * 
     * @return array Student data or empty array if not found
     */
    protected function fetchStudentInfo(): array
    {
        $data = [
            'gibbonSchoolYearID' => $this->gibbonSchoolYearID,
            'gibbonPersonID' => $this->gibbonPersonID
        ];
        
        $sql = "SELECT gibbonPerson.*, gibbonStudentEnrolment.gibbonSchoolYearID, 
                gibbonStudentEnrolment.gibbonYearGroupID, gibbonStudentEnrolment.gibbonFormGroupID, 
                gibbonStudentEnrolment.rollOrder 
                FROM gibbonPerson 
                JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) 
                WHERE gibbonSchoolYearID=:gibbonSchoolYearID 
                AND status='Full' 
                AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') 
                AND (dateEnd IS NULL OR dateEnd>='".date('Y-m-d')."') 
                AND gibbonPerson.gibbonPersonID=:gibbonPersonID";
        
        $result = $this->pdo->select($sql, $data);
        
        if ($result->rowCount() != 1) {
            return [];
        }
        
        return $result->fetch();
    }

    /**
     * Render medical alerts for the student
     * 
     * @return string HTML for medical alerts
     */
    protected function renderMedicalAlerts(): string
    {
        $alert = $this->medicalGateway->getHighestMedicalRisk($this->gibbonPersonID);
        
        if (empty($alert)) {
            return '';
        }
        
        $output = "<div class='error' style='background-color: #".$alert['colorBG'].
                  '; border: 1px solid #'.$alert['color'].'; color: #'.$alert['color']."'>";
        $output .= '<b>'.__('This student has one or more {level} risk medical conditions.', 
                           ['level' => __($alert['name'])]).'</b>';
        $output .= '</div>';
        
        return $output;
    }

    /**
     * Render current attendance status
     * 
     * @param array $student Student data
     * @return string HTML for attendance status
     */
    protected function renderAttendanceStatus(array $student): string
    {
        $currentAttendanceStatus = $this->attendanceStatus->getCurrentAttendanceStatus(
            $this->gibbonSchoolYearID,
            $this->gibbonPersonID,
            $student['preferredName'],
            $student['surname']
        );
        
        return $currentAttendanceStatus ?? '';
    }

    /**
     * Render general information table
     * 
     * @param array $student Student data
     * @return string HTML for general information table
     */
    protected function renderGeneralInfo(array $student): string
    {
        $table = DataTable::createDetails('generalInfo');
        $table->setTitle(__('General Information'));

        // Add header actions if user has permission
        if (Access::allows('User Admin', 'Manage Users_view')) {
            $table->addHeaderAction('view', __('View Status Log'))
                ->displayLabel()
                ->addParam('gibbonPersonID', $this->gibbonPersonID)
                ->setURL('/modules/User Admin/user_manage_view_status_log.php')
                ->modalWindow();

            $table->addHeaderAction('edit', __('Edit'))
                ->displayLabel()
                ->addParam('gibbonPersonID', $this->gibbonPersonID)
                ->setURL('/modules/User Admin/user_manage_edit.php');
        }

        // Add columns
        $this->addGeneralInfoColumns($table, $student);

        return $table->render([$student]);
    }

    /**
     * Add columns to general information table
     * 
     * @param DataTable $table The table to add columns to
     * @param array $student Student data
     */
    protected function addGeneralInfoColumns(DataTable $table, array $student): void
    {
        $table->addColumn('name', __('Name'))
            ->format(Format::using('name', ['', 'preferredName', 'surname', 'Student']));

        $table->addColumn('officialName', __('Official Name'));

        $table->addColumn('nameInCharacters', __('Name In Characters'));

        $table->addColumn('yearGroup', __('Year Group'))
            ->format(function($row) {
                if (isset($row['gibbonYearGroupID'])) {
                    $yearGroup = $this->yearGroupGateway->getByID($row['gibbonYearGroupID']);
                    $output = '';
                    if (!empty($yearGroup)) {
                        $output .= __($yearGroup['name']);
                        $dayTypeOptions = $this->settingGateway->getSettingByScope('User Admin', 'dayTypeOptions');
                        if (!empty($dayTypeOptions) && !empty($row['dayType'])) {
                            $output .= ' ('.$row['dayType'].')';
                        }
                        $output .= '</i><br/>';
                    }
                    return $output;
                }
            });

        $table->addColumn('formGroup', __('Form Group'))
            ->format(function($row) {
                if (isset($row['gibbonFormGroupID'])) {
                    $formGroup = $this->formGroupGateway->getByID($row['gibbonFormGroupID']);
                    $output = '';
                    if (!empty($formGroup)) {
                        if (Access::allows('Form Groups', 'View Form Groups_all')) {
                            $output .= Format::link('./index.php?q=/modules/Form Groups/formGroups_details.php&gibbonFormGroupID='.$formGroup['gibbonFormGroupID'], $formGroup['name']);
                        } else {
                            $output .= $formGroup['name'];
                        }
                    }
                    return $output;
                }
            });

        $table->addColumn('tutors', __('Tutors'))
            ->format(function($row) {
                $output = '';
                $formGroup = $this->formGroupGateway->getByID($row['gibbonFormGroupID']);

                if (isset($formGroup['gibbonPersonIDTutor'])) {
                    $dataDetail = ['gibbonFormGroupID' => $row['gibbonFormGroupID']];
                    $sqlDetail = 'SELECT gibbonPersonID, title, surname, preferredName FROM gibbonFormGroup JOIN gibbonPerson ON (gibbonFormGroup.gibbonPersonIDTutor=gibbonPerson.gibbonPersonID OR gibbonFormGroup.gibbonPersonIDTutor2=gibbonPerson.gibbonPersonID OR gibbonFormGroup.gibbonPersonIDTutor3=gibbonPerson.gibbonPersonID) WHERE gibbonFormGroupID=:gibbonFormGroupID ORDER BY surname, preferredName';
                    $resultDetail = $this->pdo->select($sqlDetail, $dataDetail);

                    while ($rowDetail = $resultDetail->fetch()) {
                        if (Access::allows('Staff', 'View Staff Profile_brief')) {
                            $output .= Format::nameLinked($rowDetail['gibbonPersonID'], '', $rowDetail['preferredName'], $rowDetail['surname'], 'Staff', false, true);
                        } else {
                            $output .= Format::name($rowDetail['title'], $rowDetail['preferredName'], $rowDetail['surname'], 'Staff');
                        }
                        if ($rowDetail['gibbonPersonID'] == $formGroup['gibbonPersonIDTutor'] && $resultDetail->rowCount() > 1) {
                            $output .= ' ('.__('Main Tutor').')';
                        }
                        $output .= '<br/>';
                    }
                }
                return $output;
            });

        $table->addColumn('username', __('Username'));

        $table->addColumn('age', __('Age'))
            ->format(function($row) {
                if (!is_null($row['dob']) && $row['dob'] != '0000-00-00') {
                    return Format::age($row['dob']);
                }
                return '';
            });

        $table->addColumn('headOfYear', __('Head of Year'))
            ->format(function($row) {
                $yearGroup = $this->yearGroupGateway->getByID($row['gibbonYearGroupID']);
                if (!empty($yearGroup) && !empty($yearGroup['gibbonPersonIDHOY'])) {
                    $hoy = $this->userGateway->getByID($yearGroup['gibbonPersonIDHOY']);
                    if (!empty($hoy) && $hoy['status'] == 'Full') {
                        if (Access::allows('Staff', 'View Staff Profile_brief')) {
                            return Format::nameLinked($hoy['gibbonPersonID'], $hoy['title'], $hoy['preferredName'], $hoy['surname'], 'Staff');
                        } else {
                            return Format::name($hoy['title'], $hoy['preferredName'], $hoy['surname'], 'Staff');
                        }
                    }
                }
                return '';
            });

        $table->addColumn('website', __('Website'))
            ->format(Format::using('link', ['website']));

        $table->addColumn('email', __('Email'))
            ->format(Format::using('link', ['email']));

        $table->addColumn('schoolHistory', __('School History'))
            ->format(function($row) {
                $output = '';
                if ($row['dateStart'] != '') {
                    $output .= '<u>'.__('Start Date').'</u>: '.Format::date($row['dateStart']).'</br>';
                }

                $resultSelect = $this->studentGateway->selectStudentEnrolmentHistory($row['gibbonPersonID']);
                
                while ($rowSelect = $resultSelect->fetch()) {
                    $output .= '<u>'.$rowSelect['schoolYear'].'</u>: '.$rowSelect['formGroup'].' ('.$rowSelect['studyYear'].')'.'<br/>';
                }

                if ($row['dateEnd'] != '') {
                    $output .= '<u>'.__('End Date').'</u>: '.Format::date($row['dateEnd']).'</br>';
                }
                
                return $output;
            });

        $table->addColumn('lockerNumber', __('Locker Number'));

        $table->addColumn('studentID', __('Student ID'));

        $table->addColumn('house', __('House'))
            ->format(function($row) {
                $house = $this->houseGateway->getByID($row['gibbonHouseID']);
                if (!empty($house)) {
                    return $house['name'];
                }
                return '';
            });

        $privacySetting = $this->settingGateway->getSettingByScope('User Admin', 'privacy');
        if ($privacySetting == 'Y') {
            $table->addColumn('privacy', __('Privacy'))
                ->format(function($row) {
                    $output = '';
                    if ($row['privacy'] != '') {
                        $output .= "<span style='color: #cc0000; background-color: #F6CECB'>";
                        $output .= __('Privacy required:').' '.$row['privacy'];
                        $output .= '</span>';
                    } else {
                        $output .= "<span style='color: #390; background-color: #D4F6DC;'>";
                        $output .= __('Privacy not required or not set.');
                        $output .= '</span>';
                    }
                    return $output;
                });
        }

        $studentAgreementOptions = $this->settingGateway->getSettingByScope('School Admin', 'studentAgreementOptions');
        if ($studentAgreementOptions != '') {
            $table->addColumn('studentAgreements', __('Student Agreements'))
                ->format(function($row) {
                    return __('Agreements Signed:').' '.$row['studentAgreements'];
                });
        }
    }

    /**
     * Render list of student's teachers
     * 
     * @param array $student Student data
     * @return string HTML for teachers list
     */
    protected function renderTeachersList(array $student): string
    {
        $staff = $this->studentGateway->selectAllRelatedUsersByStudent(
            $this->gibbonSchoolYearID,
            $student['gibbonYearGroupID'],
            $student['gibbonFormGroupID'],
            $this->gibbonPersonID
        )->fetchAll();
        
        if (empty($staff)) {
            return '';
        }

        $canViewStaff = Access::allows('Staff', 'View Staff Profile_brief');
        $criteria = $this->studentGateway->newQueryCriteria();

        $output = '<h4>';
        $output .= __('Teachers Of {student}', ['student' => $student['preferredName']]);
        $output .= '</h4>';
        $output .= '<p>';
        $output .= __('Includes Teachers, Tutors, Educational Assistants and Head of Year.');
        $output .= '</p>';

        $table = DataTable::createPaginated('staffView', $criteria);
        $table->addMetaData('listOptions', [
            'list' => __('List'),
            'grid' => __('Grid'),
        ]);

        $view = $_GET['view'] ?? 'grid';
        if ($view == 'grid') {
            $gridView = $this->getContainer()->get(GridView::class);
            $table->setRenderer($gridView->setCriteria($criteria));

            $table->addMetaData('gridClass', 'rounded-sm bg-gray-100 border');
            $table->addMetaData('gridItemClass', 'w-1/2 sm:w-1/4 md:w-1/5 my-4 text-center text-xs');

            $table->addColumn('image_240', __('Photo'))
                ->context('primary')
                ->format(function ($person) use ($canViewStaff) {
                    $photo = Format::userPhoto($person['image_240'], 'sm');
                    $url = './index.php?q=/modules/Staff/staff_view_details.php&gibbonPersonID='.$person['gibbonPersonID'];
                    return $canViewStaff
                        ? Format::link($url, $photo)
                        : $photo;
                });

            $table->addColumn('fullName', __('Name'))
                ->context('primary')
                ->sortable(['surname', 'preferredName'])
                ->width('20%')
                ->format(function ($person) use ($canViewStaff) {
                    $text = Format::name('', $person['preferredName'], $person['surname'], 'Staff', false, true);
                    $url = './index.php?q=/modules/Staff/staff_view_details.php&gibbonPersonID='.$person['gibbonPersonID'];
                    return $canViewStaff
                        ? Format::link($url, $text, ['class' => 'font-bold underline leading-normal'])
                        : $text;
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
                    $person['email'] = filter_var(trim($person['email']), FILTER_SANITIZE_EMAIL);
                    return htmlPrep('<'.$person['email'].'>');
                });
        }

        $table->addColumn('context', __('Context'))
            ->notSortable()
            ->format(function ($person) use ($view) {
                $class = $view == 'grid'? 'unselectable text-xxs italic text-gray-800' : 'unselectable';
                $context = $person['type'] == 'Class Teacher' ? $person['context'] : $person['type'];
                if (!empty($person['classID'])) {
                    return Format::link('./index.php?q=/modules/Departments/department_course_class.php&gibbonCourseClassID='.$person['classID'], __($context), ['class' => $class.' underline']);
                } else {
                    return '<span class="'.$class.'">'.__($context).'</span>';
                }
            });

        $output .= $table->render(new DataSet($staff));
        
        return $output;
    }

    /**
     * Render student timetable
     * 
     * @param array $student Student data
     * @return string HTML for timetable
     */
    protected function renderTimetable(array $student): string
    {
        $output = "<a name='timetable'></a>";
        
        // Check if user has access to timetable
        if (Access::allows('Timetable', 'View Timetables_view')) {
            $output .= '<h4>';
            $output .= __('Timetable');
            $output .= '</h4>';

            // Timetable Links
            $table = DataTable::createDetails('timetable');

            if (Access::allows('Timetable Admin', 'Course Enrolment by Person_view')) {
                $role = $this->roleGateway->getRoleCategory($student['gibbonRoleIDPrimary']);
                if ($role == 'Student' or $role == 'Staff') {
                    $table->addHeaderAction('edit', __('Edit'))
                        ->setURL('/modules/Timetable Admin/courseEnrolment_manage_byPerson_edit.php')
                        ->addParam('gibbonPersonID', $this->gibbonPersonID)
                        ->addParam('gibbonSchoolYearID', $this->gibbonSchoolYearID)
                        ->addParam('type', $role)
                        ->addParam('allUsers', $_GET['allStudents'] ?? '')
                        ->displayLabel();
                }
            }

            if ($this->gibbonPersonID == $this->session->get('gibbonPersonID')) {
                $table->addHeaderAction('export', __('Export'))
                    ->modalWindow()
                    ->setURL('/modules/Timetable/tt_manage_subscription.php')
                    ->addParam('gibbonPersonID', $this->gibbonPersonID)
                    ->setIcon('download')
                    ->displayLabel();
            }

            $output .= $table->render([['' => '']]);

            $ttDate = !empty($_REQUEST['ttDate']) ? Format::dateConvert($_REQUEST['ttDate']) : null;
            $gibbonTTID = $_REQUEST['gibbonTTID'] ?? '';
            
            // Create timetable context
            $context = $this->getContainer()->get(TimetableContext::class)
                ->set('gibbonSchoolYearID', $this->gibbonSchoolYearID)
                ->set('gibbonPersonID', $this->gibbonPersonID)
                ->set('gibbonTTID', $gibbonTTID);

            // Build and render timetable
            $output .= $this->getContainer()->get(Timetable::class)
                ->setDate($ttDate)
                ->setContext($context)
                ->addCoreLayers($this->getContainer())
                ->getOutput();
        } else {
            // Display class list if no timetable access
            $output .= '<h4>';
            $output .= __('Class List');
            $output .= '</h4>';

            $dataDetail = ['gibbonPersonID' => $this->gibbonPersonID];
            $sqlDetail = "SELECT DISTINCT gibbonCourse.name AS courseFull, gibbonCourse.nameShort AS course, gibbonCourseClass.nameShort AS class
                FROM gibbonCourseClassPerson
                    JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
                    JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                WHERE gibbonCourseClassPerson.role='Student' AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonID AND gibbonCourse.gibbonSchoolYearID=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE status='Current') ORDER BY course, class";
            $resultDetail = $this->pdo->select($sqlDetail, $dataDetail);
            
            if ($resultDetail->rowCount() < 1) {
                $output .= '<div class="warning">'.__('There are no records to display.').'</div>';
            } else {
                $output .= '<ul>';
                while ($rowDetail = $resultDetail->fetch()) {
                    $output .= '<li>';
                    $output .= htmlPrep($rowDetail['courseFull'].' ('.$rowDetail['course'].'.'.$rowDetail['class'].')');
                    $output .= '</li>';
                }
                $output .= '</ul>';
            }
        }
        
        return $output;
    }
}
