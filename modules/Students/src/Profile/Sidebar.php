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

use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\System\HookGateway;
use Gibbon\Domain\System\ModuleGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Services\Format;
use Gibbon\Support\Facades\Access;
use Gibbon\UI\Components\Alert;
use Gibbon\View\View;

/**
 * Sidebar
 * 
 * @package Gibbon\Module\Students\Profile
 */
class Sidebar extends ProfilePage
{
    private View $view;
    private Alert $alert;
    private SettingGateway $settingGateway;
    private ModuleGateway $moduleGateway;
    private HookGateway $hookGateway;

    public function __construct(
        Session $session,
        View $view,
        Alert $alert,
        SettingGateway $settingGateway,
        ModuleGateway $moduleGateway,
        HookGateway $hookGateway,
    ) {
        parent::__construct($session);
        $this->view = $view;
        $this->alert = $alert;
        $this->settingGateway = $settingGateway;
        $this->moduleGateway = $moduleGateway;
        $this->hookGateway = $hookGateway;
    }

    /**
     * Check if the current user has permission to view the sidebar
     * 
     * @return bool True if user has access, false otherwise
     */
    public function checkAccess(): bool
    {
        return Access::allows('Students', 'student_view_details');
    }

    /**
     * Generate HTML output for the brief profile page
     * 
     * @return string HTML content for display
     */
    public function getOutput(): string
    {
        if (!$this->checkAccess()) {
            return '';
        }

        $highestAction = Access::get('Students', 'student_view_details');

        $search = $_GET['search'] ?? '';
        $allStudents = $_GET['allStudents'] ?? '';
        $subpage = $_GET['subpage'] ?? '';
        $hook = $_GET['hook'] ?? '';
        $address = $_GET['q'] ?? '';

        // Prepare alert bar
        $alert = '';
        if ($highestAction->allowsAny('View Student Profile_full', 'View Student Profile_fullEditAllNotes', 'View Student Profile_fullNoNotes')) {
            $alert = $this->alert->getAlertBar($this->gibbonPersonID, ['wrap' => false, 'large' => true]);
        }

        // Build base URL for menu items
        $baseURL = $this->session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonPersonID=$this->gibbonPersonID&search=$search&allStudents=$allStudents";

        // Build personal menu items
        $personalMenu = [];

        $personalMenu[] = [
            'name' => 'Overview',
            'url' => $baseURL.'&subpage=Overview',
            'active' => $subpage == 'Overview'
        ];

        $personalMenu[] = [
            'name' => 'Personal',
            'url' => $baseURL.'&subpage=Personal',
            'active' => $subpage == 'Personal'
        ];

        $personalMenu[] = [
            'name' => 'Family',
            'url' => $baseURL.'&subpage=Family',
            'active' => $subpage == 'Family'
        ];

        $personalMenu[] = [
            'name' => 'Emergency Contacts',
            'url' => $baseURL.'&subpage=Emergency Contacts',
            'active' => $subpage == 'Emergency Contacts'
        ];

        // Medical - only show if NOT "View Student Profile_my"
        if (!Access::allows('Students', 'student_view_details', 'View Student Profile_my')) {
            $personalMenu[] = [
                'name' => 'Medical',
                'url' => $baseURL.'&subpage=Medical',
                'active' => $subpage == 'Medical'
            ];
        }

        // First Aid - check permission
        if (Access::allows('Students', 'firstAidRecord')) {
            $personalMenu[] = [
                'name' => 'First Aid',
                'url' => $baseURL.'&subpage=First Aid',
                'active' => $subpage == 'First Aid'
            ];
        }

        $enableStudentNotes = $this->settingGateway->getSettingByScope('Students', 'enableStudentNotes');
        // Notes - check permission and setting
        if (Access::allows('Students', 'student_view_details_notes_add') && $enableStudentNotes == 'Y') {
            $personalMenu[] = [
                'name' => 'Notes',
                'url' => $baseURL.'&subpage=Notes',
                'active' => $subpage == 'Notes'
            ];
        }

        // Get all modules with categories
        $modules = $this->moduleGateway->selectAllActiveModules();
        $mainMenu = [];

        foreach ($modules as $module) {
            $mainMenu[$module['name']] = $module['category'];
        }

        // Build dynamic menu items array
        $studentMenuItems = [];

        // Markbook
        if (Access::allows('Markbook', 'markbook_view')) {
            $studentMenuItems[] = [
                'category' => $mainMenu['Markbook'],
                'name' => 'Markbook',
                'url' => $baseURL.'&subpage=Markbook',
                'active' => $subpage == 'Markbook'
            ];
        }

        // Internal Assessment
        if (Access::allows('Formal Assessment', 'internalAssessment_view')) {
            $studentMenuItems[] = [
                'category' => $mainMenu['Formal Assessment'],
                'name' => 'Internal Assessment',
                'url' => $baseURL.'&subpage=Internal%20Assessment',
                'active' => $subpage == 'Internal Assessment'
            ];
        }

        // External Assessment
        if (Access::allows('Formal Assessment', 'externalAssessment_details') || Access::allows('Formal Assessment', 'externalAssessment_view')) {
            $studentMenuItems[] = [
                'category' => $mainMenu['Formal Assessment'],
                'name' => 'External Assessment',
                'url' => $baseURL.'&subpage=External Assessment',
                'active' => $subpage == 'External Assessment'
            ];
        }

        // Reports
        if (Access::allows('Reports', 'archive_byStudent_view')) {
            $studentMenuItems[] = [
                'category' => $mainMenu['Reports'],
                'name' => 'Reports',
                'url' => $baseURL.'&subpage=Reports',
                'active' => $subpage == 'Reports'
            ];
        }

        // Activities
        if (Access::allows('Activities', 'report_activityChoices_byStudent') 
            || Access::allows('Activities', 'activities_view_myChildren') 
            || Access::allows('Activities', 'activities_my')) {
            $studentMenuItems[] = [
                'category' => $mainMenu['Activities'],
                'name' => 'Activities',
                'url' => $baseURL.'&subpage=Activities',
                'active' => $subpage == 'Activities'
            ];
        }

        // Homework
        if (Access::allows('Planner', 'planner_edit') || Access::allows('Planner', 'planner_view_full')) {
            $homeworkNamePlural = $this->settingGateway->getSettingByScope('Planner', 'homeworkNamePlural');
            $studentMenuItems[] = [
                'category' => $mainMenu['Planner'],
                'name' => $homeworkNamePlural,
                'url' => $baseURL.'&subpage=Homework',
                'active' => $subpage == 'Homework'
            ];
        }

        // Individual Needs
        if (Access::allows('Individual Needs', 'in_view')) {
            $studentMenuItems[] = [
                'category' => $mainMenu['Individual Needs'],
                'name' => 'Individual Needs',
                'url' => $baseURL.'&subpage=Individual Needs',
                'active' => $subpage == 'Individual Needs'
            ];
        }

        // Library Borrowing
        if (Access::allows('Library', 'library_browse')) {
            $studentMenuItems[] = [
                'category' => $mainMenu['Library'],
                'name' => 'Library Borrowing',
                'url' => $baseURL.'&subpage=Library Borrowing',
                'active' => $subpage == 'Library Borrowing'
            ];
        }

        // Timetable
        if (Access::allows('Timetable', 'tt_view')) {
            $studentMenuItems[] = [
                'category' => $mainMenu['Timetable'],
                'name' => 'Timetable',
                'url' => $baseURL.'&subpage=Timetable',
                'active' => $subpage == 'Timetable'
            ];
        }

        // Attendance
        if (Access::allows('Attendance', 'report_studentHistory')) {
            $studentMenuItems[] = [
                'category' => $mainMenu['Attendance'],
                'name' => 'Attendance',
                'url' => $baseURL.'&subpage=Attendance',
                'active' => $subpage == 'Attendance'
            ];
        }

        // Behaviour
        if (Access::allows('Behaviour', 'behaviour_view')) {
            $studentMenuItems[] = [
                'category' => $mainMenu['Behaviour'],
                'name' => 'Behaviour',
                'url' => $baseURL.'&subpage=Behaviour',
                'active' => $subpage == 'Behaviour'
            ];
        }

        // Check for hooks and add them to the menu
        $hooks = $this->hookGateway->selectHooksByType('Student Profile')->fetchGroupedUnique();

        if (!empty($hooks)) {
            foreach ($hooks as $rowHook) {
                if (empty($rowHook) || empty($rowHook['options'])) continue;

                $options = unserialize($rowHook['options']);
                $hookPermission = $this->hookGateway->getHookPermission($rowHook['gibbonHookID'], $this->session->get('gibbonRoleIDCurrent'), $options['sourceModuleName'] ?? '', $options['sourceModuleAction'] ?? '');

                if (!empty($hookPermission)) {
                    $studentMenuItems[] = [
                        'category' => $mainMenu[$options['sourceModuleName']],
                        'name' => $rowHook['name'],
                        'url' => $baseURL.'&hook='.$rowHook['name'].'&module='.$options['sourceModuleName'].'&action='.$options['sourceModuleAction'].'&gibbonHookID='.$rowHook['gibbonHookID'],
                        'active' => $hook == $rowHook['name']
                    ];
                }
            }
        }

        // Sort menu items by category and name
        usort($studentMenuItems, function($a, $b) {
            if ($a['category'] == $b['category']) {
                return strcmp($a['name'], $b['name']);
            }
            return strcmp($a['category'], $b['category']);
        });

        // Group menu items by category according to mainMenuCategoryOrder
        $mainMenuCategoryOrder = $this->settingGateway->getSettingByScope('System', 'mainMenuCategoryOrder');
        $orders = explode(',', $mainMenuCategoryOrder);

        $dynamicMenuSections = [];
        foreach ($orders as $order) {
            $categoryItems = array_filter($studentMenuItems, function($item) use ($order) {
                return $item['category'] == $order;
            });

            if (!empty($categoryItems)) {
                $dynamicMenuSections[] = [
                    'category' => $order,
                    'items' => array_values($categoryItems)
                ];
            }
        }

        // Prepare data for Twig template
        $sidebarData = [
            'alert' => $alert,
            'studentImage' => Format::userPhoto($this->studentImage, 240),
            'personalMenu' => $personalMenu,
            'dynamicMenuSections' => $dynamicMenuSections
        ];

        // Render sidebar using Twig template
        return $this->view->fetchFromTemplate('studentProfileSidebar.twig.html', $sidebarData);
    }
}
