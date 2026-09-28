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

namespace Gibbon\Module\Staff\Profile;

use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\System\HookGateway;
use Gibbon\Domain\System\ModuleGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Services\Format;
use Gibbon\Support\Facades\Access;
use Gibbon\View\View;

/**
 * Sidebar
 * 
 * @package Gibbon\Module\Staff\Profile
 */
class Sidebar extends ProfilePage
{
    private View $view;
    private HookGateway $hookGateway;
    private ModuleGateway $moduleGateway;
    private SettingGateway $settingGateway;

    public function __construct(
        Session $session,
        View $view,
        HookGateway $hookGateway,
        ModuleGateway $moduleGateway,
        SettingGateway $settingGateway,
    ) {
        parent::__construct($session);
        $this->view = $view;
        $this->hookGateway = $hookGateway;
        $this->moduleGateway = $moduleGateway;
        $this->settingGateway = $settingGateway;
    }

    /**
     * Check if the current user has permission to view the sidebar
     * 
     * @return bool True if user has access, false otherwise
     */
    public function checkAccess(): bool
    {
        return Access::allows('Staff', 'staff_view_details');
    }

    /**
     * Get the page name for display
     *
     * @return string Translated page name
     */
    public function getPageName(): string
    {
        return '';
    }

    /**
     * Generate HTML output for the staff profile sidebar
     * 
     * @return string HTML content for display
     */
    public function getOutput(): string
    {
        if (!$this->checkAccess()) {
            return '';
        }

        $search = $_GET['search'] ?? '';
        $allStaff = $_GET['allStaff'] ?? '';
        $subpage = $_GET['subpage'] ?? '';
        $hook = $_GET['hook'] ?? '';

        // Build base URL for menu items
        $baseURL = $this->session->get('absoluteURL').'/index.php?q='.$_GET['q']."&gibbonPersonID=$this->gibbonPersonID&search=$search&allStaff=$allStaff";

        // Build personal menu items
        $personalMenu = [];
        $staffMenuItems = [];

        $personalMenu[] = [
            'name' => 'Overview',
            'url' => $baseURL.'&subpage=Overview',
            'active' => $subpage == 'Overview' || $subpage == ''
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

        // Emergency Contacts - check permission
        $highestActionManage = Access::get('Staff', 'staff_manage');
        if ($highestActionManage && $highestActionManage->allows('Manage Staff_confidential')) {
            $personalMenu[] = [
                'name' => 'Emergency Contacts',
                'url' => $baseURL.'&subpage=Emergency Contacts',
                'active' => $subpage == 'Emergency Contacts'
            ];
        }

        $staffMenuItems[] = [
            'category' => 'School',
            'name' => 'Facilities',
            'url' => $baseURL.'&subpage=Facilities',
            'active' => $subpage == 'Facilities'
        ];

        $staffMenuItems[] = [
            'category' => 'School',
            'name' => 'Activities',
            'url' => $baseURL.'&subpage=Activities',
            'active' => $subpage == 'Activities'
        ];

        // Timetable - check permission
        if (Access::allows('Timetable', 'tt_view')) {
            $staffMenuItems[] = [
                'category' => 'School',
                'name' => 'Timetable',
                'url' => $baseURL.'&subpage=Timetable',
                'active' => $subpage == 'Timetable'
            ];
        }

        // Get all modules with categories
        $modules = $this->moduleGateway->selectAllActiveModules();
        $mainMenu = [];

        foreach ($modules as $module) {
            $mainMenu[$module['name']] = $module['category'];
        }

        // Check for hooks and add them to the menu
        $hooks = $this->hookGateway->selectHooksByType('Staff Profile')->fetchGroupedUnique();

        if (!empty($hooks)) {
            foreach ($hooks as $rowHook) {
                if (empty($rowHook) || empty($rowHook['options'])) continue;

                $options = unserialize($rowHook['options']);
                $hookPermission = $this->hookGateway->getHookPermission($rowHook['gibbonHookID'], $this->session->get('gibbonRoleIDCurrent'), $options['sourceModuleName'] ?? '', $options['sourceModuleAction'] ?? '');

                if (!empty($hookPermission)) {
                    $staffMenuItems[] = [
                        'category' => $mainMenu[$options['sourceModuleName']] ?? '',
                        'name' => $rowHook['name'],
                        'url' => $baseURL.'&hook='.$rowHook['name'].'&module='.$options['sourceModuleName'].'&action='.$options['sourceModuleAction'].'&gibbonHookID='.$rowHook['gibbonHookID'],
                        'active' => $hook == $rowHook['name']
                    ];
                }
            }
        }

        // Sort menu items by category and name
        usort($staffMenuItems, function($a, $b) {
            if ($a['category'] == $b['category']) {
                return strcmp($a['name'], $b['name']);
            }
            return strcmp($a['category'], $b['category']);
        });

        // Group menu items by category according to mainMenuCategoryOrder
        $mainMenuCategoryOrder = $this->settingGateway->getSettingByScope('System', 'mainMenuCategoryOrder');
        $orders = explode(',', $mainMenuCategoryOrder.',School');

        $dynamicMenuSections = [];
        foreach ($orders as $order) {
            $categoryItems = array_filter($staffMenuItems, function($item) use ($order) {
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
            'staffImage' => Format::userPhoto($this->userImage, 240),
            'personalMenu' => $personalMenu,
            'dynamicMenuSections' => $dynamicMenuSections
        ];

        // Render sidebar using Twig template
        return $this->view->fetchFromTemplate('staffProfileSidebar.twig.html', $sidebarData);
    }
}
