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

use Gibbon\Http\Url;
use Gibbon\Services\Format;
use Gibbon\Services\ModuleLoader;
use Gibbon\Support\Facades\Access;
use Gibbon\Module\Students\Profile\Sidebar;
use Gibbon\Module\Students\Profile\HookPage;

if (!Access::allows('Students', 'student_view_details')) {
    $page->addError(__('You do not have access to this action.'));
    return;
} else {
    $page->scripts->add('chart');

    // Get action with highest precedence
    $highestAction = Access::get('Students', 'student_view_details');
    if (empty($highestAction)) {
        $page->addError(__('The highest grouped action cannot be determined.'));
        return;
    }

    $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
    $search = $_GET['search'] ?? '';
    $allStudents = $_GET['allStudents'] ?? '';
    $sort = $_GET['sort'] ?? '';
    $subpage = $_GET['subpage'] ?? '';
    $hook = $_GET['hook'] ?? '';

    if (empty($gibbonPersonID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $studentGateway = $container->get(\Gibbon\Domain\Students\StudentGateway::class);

    $skipBrief = false;

    // Skip brief for those with _full or _fullNoNotes
    if ($highestAction->allowsAny('View Student Profile_full', 'View Student Profile_fullEditAllNotes', 'View Student Profile_fullNoNotes')) {
        $skipBrief = true;
    }

    // Test if View Student Profile_myChildren is available and parent has access to this student
    if (Access::allows('Students', 'student_view_details', 'View Student Profile_myChildren')) {
        $student = $studentGateway->getStudentByFamilyAdult($gibbonPersonID, $session->get('gibbonPersonID'));
        if (!empty($student)) {
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
        $student = $studentGateway->selectActiveStudentByPerson($session->get('gibbonSchoolYearID'), $gibbonPersonID)->fetch();

        $briefPage = $container->get(\Gibbon\Module\Students\Profile\BriefPage::class);
        $briefPage->setStudent($session->get('gibbonSchoolYearID'), $gibbonPersonID);

        $session->set('sidebarExtra', Format::userPhoto($student['image_240'] ?? '', 240));
        
        if (!$briefPage->checkAccess()) {
            $page->addError(__('You do not have access to this action.'));
            return;
        }
        
        echo $briefPage->getOutput();
        return;
    }

    // Handle full profile view
    if ($highestAction->allows('View Student Profile_myChildren')) {
        $student = $studentGateway->getStudentByFamilyAdult($gibbonPersonID, $session->get('gibbonPersonID'));
    } elseif ($highestAction->allows('View Student Profile_my')) {
        $gibbonPersonID = $session->get('gibbonPersonID');
        $student = $studentGateway->selectActiveStudentByPerson($session->get('gibbonSchoolYearID'), $gibbonPersonID)->fetch();
    } elseif ($highestAction->allowsAny('View Student Profile_full', 'View Student Profile_fullEditAllNotes', 'View Student Profile_fullNoNotes')) {
        $student = $studentGateway->selectActiveStudentByPerson($session->get('gibbonSchoolYearID'), $gibbonPersonID, $allStudents == 'on')->fetch();
    } else {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    if (empty($student)) {
        $page->addError(__('The selected record does not exist, or you do not have access to it.'));
        return;
    }

    $page->breadcrumbs
        ->add(__('View Student Profiles'), 'student_view.php')
        ->add(Format::name('', $student['preferredName'], $student['surname'], 'Student'));

    // When viewing left students, they won't have a year group ID
    if (empty($student['gibbonYearGroupID'])) {
        $student['gibbonYearGroupID'] = '';
    }

    if (empty($subpage) && empty($hook)) {
        $subpage = 'Overview';
    }

    if (!empty($search) || !empty($allStudents)) {
        $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Students', 'student_view.php')->withQueryParams([
            'search' => $search,
            'allStudents' => $allStudents,
        ]));
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

    // Handle hook-based subpages (third-party integrations)
    if (!empty($hook)) {
        $hook = preg_replace('/[^a-zA-Z0-9-_\s]/', '', $hook);

        $hookPage = $container->get(HookPage::class);
        $hookPage->setStudent($session->get('gibbonSchoolYearID'), $gibbonPersonID);
        $hookPage->setHook($hook, $_GET['gibbonHookID'] ?? '');
        
        echo '<h2>';
        echo $hookPage->getPageName();
        echo '</h2>';
        
        if (!$hookPage->checkAccess()) {
            echo Format::alert(__('Your request failed because you do not have access to this action.'), 'error');
        } else {
            echo $hookPage->getOutput();
        }
    } elseif (isset($subpageClasses[$subpage])) {
        $container->get(ModuleLoader::class)->registerModuleNamespace('Attendance');
        $container->get(ModuleLoader::class)->registerModuleNamespace('Reports');
        $container->get(ModuleLoader::class)->registerModuleNamespace('Planner');

        // Handle class-based subpages
        $pageClass = $subpageClasses[$subpage];
        $profilePage = $container->get($pageClass);

        echo '<h2>';
        echo $profilePage->getPageName();
        echo '</h2>';
        
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
        echo Format::alert(__('You do not have access to this action.'), 'error');
    }

    // Set sidebar
    $sidebar = $container->get(Sidebar::class);
    $sidebar->setStudent($session->get('gibbonSchoolYearID'), $gibbonPersonID, $student['image_240']);

    $session->set('sidebarExtra', $sidebar->getOutput());
}
