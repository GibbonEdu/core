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

use Gibbon\Domain\System\HookGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\User\RoleGateway;
use Gibbon\Http\Url;
use Gibbon\Module\Students\Profile\Sidebar;
use Gibbon\Services\Format;
use Gibbon\Services\ModuleLoader;
use Gibbon\Support\Facades\Access;
use Gibbon\UI\Components\Alert;

//Module includes for User Admin (for custom fields)
include './modules/User Admin/moduleFunctions.php';

if (!Access::allows('Students', 'student_view_details')) {
    $page->addError(__('You do not have access to this action.'));
    return;
} else {

    $page->scripts->add('chart');

    $roleGateway = $container->get(RoleGateway::class);

    //Get action with highest precendence
    $highestAction = Access::get('Students', 'student_view_details');
    if (empty($highestAction)) {
        $page->addError(__('The highest grouped action cannot be determined.'));
        return;
    }

    $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
    $search = $_GET['search'] ?? '';
    $allStudents = $_GET['allStudents'] ?? '';
    $sort = $_GET['sort'] ?? '';

    if ($gibbonPersonID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $settingGateway = $container->get(SettingGateway::class);
    $hookGateway = $container->get(HookGateway::class);

    $skipBrief = false;

    //Skip brief for those with _full or _fullNoNotes
    if ($highestAction->allowsAny('View Student Profile_full', 'View Student Profile_fullEditAllNotes', 'View Student Profile_fullNoNotes')) {
        $skipBrief = true;
    }

    //Test if View Student Profile_myChildren is available and parent has access to this student
    if (Access::allows('Students', 'student_view_details', 'View Student Profile_myChildren')) {
        $data = ['gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID1' => $_GET['gibbonPersonID'], 'gibbonPersonID2' => $session->get('gibbonPersonID')];
        $sql = "SELECT * FROM gibbonFamilyChild JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonFamilyChild.gibbonPersonID=:gibbonPersonID1 AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID2 AND childDataAccess='Y'";
        $result = $pdo->select($sql, $data);
        if ($result->rowCount() == 1) {
            $skipBrief = true;
        }
    }

    if (Access::allows('Students', 'student_view_details', 'View Student Profile_my')) {
        if ($gibbonPersonID == $session->get('gibbonPersonID')) {
            $skipBrief = true;
        } elseif (!Access::allows('Students', 'student_view_details', 'View Student Profile_brief')) {
            $page->addError(__('You do not have access to this action.'));
            return;
        }
    }

    // Handle brief profile view
    if (Access::allows('Students', 'student_view_details', 'View Student Profile_brief') && !$skipBrief) {
        $briefPage = $container->get(\Gibbon\Module\Students\Profile\BriefPage::class);
        $briefPage->setStudent($session->get('gibbonSchoolYearID'), $gibbonPersonID);
        
        if (!$briefPage->checkAccess()) {
            $page->addError(__('You do not have access to this action.'));
            return;
        }
        
        echo $briefPage->getOutput();
        return;
    }

    // Handle full profile view
    try {
        if ($highestAction->allows('View Student Profile_myChildren')) {
            $data = ['gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID1' => $_GET['gibbonPersonID'], 'gibbonPersonID2' => $session->get('gibbonPersonID'), 'today' => date('Y-m-d')];
            $sql = "SELECT gibbonPerson.*, gibbonStudentEnrolment.gibbonSchoolYearID, gibbonStudentEnrolment.gibbonYearGroupID, gibbonStudentEnrolment.gibbonFormGroupID, gibbonStudentEnrolment.rollOrder FROM gibbonFamilyChild
                JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID)
                JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID)
                JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID)
                JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full'
                AND (dateStart IS NULL OR dateStart<=:today) AND (dateEnd IS NULL  OR dateEnd>=:today)
                AND gibbonFamilyChild.gibbonPersonID=:gibbonPersonID1
                AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID2
                AND childDataAccess='Y'";
        } elseif ($highestAction->allows('View Student Profile_my')) {
            $gibbonPersonID = $session->get('gibbonPersonID');
            $data = ['gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $gibbonPersonID, 'today' => date('Y-m-d')];
            $sql = "SELECT gibbonPerson.*, gibbonStudentEnrolment.gibbonSchoolYearID, gibbonStudentEnrolment.gibbonYearGroupID, gibbonStudentEnrolment.gibbonFormGroupID, gibbonStudentEnrolment.rollOrder FROM gibbonPerson
                LEFT JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID
                AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full'
                AND (dateStart IS NULL OR dateStart<=:today) AND (dateEnd IS NULL OR dateEnd>=:today)";
        } elseif ($highestAction->allowsAny('View Student Profile_full', 'View Student Profile_fullEditAllNotes', 'View Student Profile_fullNoNotes')) {
            if ($allStudents != 'on') {
                $data = ['gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $gibbonPersonID, 'today' => date('Y-m-d')];
                $sql = "SELECT gibbonPerson.*, gibbonStudentEnrolment.gibbonSchoolYearID, gibbonStudentEnrolment.gibbonYearGroupID, gibbonStudentEnrolment.gibbonFormGroupID, gibbonStudentEnrolment.rollOrder FROM gibbonPerson
                    JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
                    WHERE gibbonSchoolYearID=:gibbonSchoolYearID
                    AND gibbonPerson.gibbonPersonID=:gibbonPersonID AND status='Full'
                    AND (dateStart IS NULL OR dateStart<=:today) AND (dateEnd IS NULL  OR dateEnd>=:today) ";
            } else {
                $data = ['gibbonPersonID' => $gibbonPersonID, 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID')];
                $sql = "SELECT gibbonPerson.*, gibbonStudentEnrolment.gibbonSchoolYearID, gibbonStudentEnrolment.gibbonYearGroupID, gibbonStudentEnrolment.gibbonFormGroupID, gibbonStudentEnrolment.rollOrder FROM gibbonPerson
                    LEFT JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID)
                    WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID";
            }
        } else {
            $page->addError(__('You do not have access to this action.'));
            return;
        }
        $result = $pdo->select($sql, $data);
    } catch (PDOException $e) {
        return;
    }

    if ($result->rowCount() != 1) {
        $page->addError(__('The selected record does not exist, or you do not have access to it.'));
        return;
    }

    $row = $result->fetch();
    $subpage = $_GET['subpage'] ?? '';
    $hook = $_GET['hook'] ?? '';

    $page->breadcrumbs
        ->add(__('View Student Profiles'), 'student_view.php')
        ->add(Format::name('', $row['preferredName'], $row['surname'], 'Student'));

    // When viewing left students, they won't have a year group ID
    if (empty($row['gibbonYearGroupID'])) {
        $row['gibbonYearGroupID'] = '';
    }

    if ($subpage == '' && $hook == '') {
        $subpage = 'Overview';
    }

    if ($search != '' || $allStudents != '') {
        $params = [
            "search" => $search,
            "allStudents" => $allStudents,
        ];
        $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Students', 'student_view.php')->withQueryParams($params));
    }

    // Map subpage names to class names for class-based routing
    $subpageClasses = [
        'Overview'            => \Gibbon\Module\Students\Profile\OverviewPage::class,
        'Personal'            => \Gibbon\Module\Students\Profile\PersonalPage::class,
        'Family'              => \Gibbon\Module\Students\Profile\FamilyPage::class,
        'Emergency Contacts'  => \Gibbon\Module\Students\Profile\EmergencyContactsPage::class,
        'Medical'             => \Gibbon\Module\Students\Profile\MedicalPage::class,
        'First Aid'           => \Gibbon\Module\Students\Profile\FirstAidPage::class,
        'Notes'               => \Gibbon\Module\Students\Profile\NotesPage::class,
        'Attendance'          => \Gibbon\Module\Students\Profile\AttendancePage::class,
        'Markbook'            => \Gibbon\Module\Students\Profile\MarkbookPage::class,
        'Internal Assessment' => \Gibbon\Module\Students\Profile\InternalAssessmentPage::class,
        'External Assessment' => \Gibbon\Module\Students\Profile\ExternalAssessmentPage::class,
        'Reports'             => \Gibbon\Module\Students\Profile\ReportsPage::class,
        'Individual Needs'    => \Gibbon\Module\Students\Profile\IndividualNeedsPage::class,
        'Library Borrowing'   => \Gibbon\Module\Students\Profile\LibraryBorrowingPage::class,
        'Timetable'           => \Gibbon\Module\Students\Profile\TimetablePage::class,
        'Activities'          => \Gibbon\Module\Students\Profile\ActivitiesPage::class,
        'Homework'            => \Gibbon\Module\Students\Profile\HomeworkPage::class,
        'Behaviour'           => \Gibbon\Module\Students\Profile\BehaviourPage::class,
    ];

    echo '<h2>';
    if ($subpage == 'Homework') {
        $homeworkNamePlural = $settingGateway->getSettingByScope('Planner', 'homeworkNamePlural');
        echo __($homeworkNamePlural);
    } elseif ($subpage != '') {
        echo __($subpage);
    } else {
        echo $hook;
    }
    echo '</h2>';

    // Handle hook-based subpages (third-party integrations)
    if (!empty($hook)) {
        $rowHook = $hookGateway->getByID($_GET['gibbonHookID'] ?? '');
        if (empty($rowHook)) {
            echo $page->getBlankSlate();
        } else {
            $options = unserialize($rowHook['options']);

            // Check for permission to hook
            $hookPermission = $hookGateway->getHookPermission($rowHook['gibbonHookID'], $session->get('gibbonRoleIDCurrent'), $options['sourceModuleName'] ?? '', $options['sourceModuleAction'] ?? '');

            if (empty($options) || empty($hookPermission)) {
                echo Format::alert(__('Your request failed because you do not have access to this action.'), 'error');
            } else {
                $include = $session->get('absolutePath').'/modules/'.$options['sourceModuleName'].'/'.$options['sourceModuleInclude'];
                if (!file_exists($include)) {
                    echo Format::alert(__('The selected page cannot be displayed due to a hook error.'), 'error');
                } else {
                    include $include;
                }
            }
        }
    } elseif (isset($subpageClasses[$subpage])) {
        $container->get(ModuleLoader::class)->registerModuleNamespace('Attendance');
        $container->get(ModuleLoader::class)->registerModuleNamespace('Reports');
        $container->get(ModuleLoader::class)->registerModuleNamespace('Planner');

        // Handle class-based subpages
        $pageClass = $subpageClasses[$subpage];
        $profilePage = $container->get($pageClass);
        
        // Set student context
        $profilePage->setStudent($session->get('gibbonSchoolYearID'), $gibbonPersonID);
        
        // Check access
        if (!$profilePage->checkAccess()) {
            echo Format::alert(__('Your request failed because you do not have access to this action.'), 'error');
        } else {
            // Render output
            echo $profilePage->getOutput();
        }
    } elseif ($subpage != '') {
        // Invalid subpage
        echo Format::alert(__('Invalid subpage specified.'), 'error');
    }

    // Set sidebar
    $sidebar = $container->get(Sidebar::class);
    $sidebar->setStudent($session->get('gibbonSchoolYearID'), $gibbonPersonID, $row['image_240']);

    $session->set('sidebarExtra', $sidebar->getOutput());
}
