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
use Gibbon\View\View;
use Gibbon\Services\Format;
use Gibbon\Data\Validator;
use Gibbon\Forms\OutputableInterface;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Contracts\Database\Connection;
use Gibbon\Contracts\Services\Session;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Gibbon\Domain\System\HookGateway;
use Gibbon\Tables\Prefab\TodaysLessonsTable;

/**
 * Student Dashboard View Composer
 *
 * @version  v18
 * @since    v18
 */
class StudentDashboard implements OutputableInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected $db;
    protected $session;
    protected $settingGateway;

    /**
     * @var View
     */
    private $view;

    public function __construct(Connection $db, Session $session, SettingGateway $settingGateway, View $view)
    {
        $this->db = $db;
        $this->session = $session;
        $this->settingGateway = $settingGateway;
        $this->view = $view;
    }

    public function getOutput()
    {
        $output = '<h2>'.
            __('Student Dashboard').
            '</h2>'.
            "<div class='w-full' style='height:calc(100% - 6rem)'>";

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

        $homeworkNameSingular = $this->settingGateway->getSettingByScope('Planner', 'homeworkNameSingular');

        $return = false;

        $planner = false;

       // PLANNER
       if (isActionAccessible($guid, $connection2, '/modules/Planner/planner.php')) {
            $planner = $this
                ->getContainer()
                ->get(TodaysLessonsTable::class)
                ->create($session->get('gibbonSchoolYearID'), $this->session->get('gibbonPersonID'), 'Student')
                ->getOutput();
        }

        //GET TIMETABLE
        $timetable = false;
        if (
            isActionAccessible($guid, $connection2, '/modules/Timetable/tt.php') and $this->session->get('username') != ''
            && $this->session->get('gibbonRoleIDCurrentCategory')
        ) {
            $_POST = (new Validator(''))->sanitize($_POST);
            $jsonQuery = [
                'gibbonTTID' => $_GET['gibbonTTID'] ?? '',
                'ttDate' => $_POST['ttDate'] ?? '',
            ];

            $apiEndpoint = (string)Url::fromHandlerRoute('index_tt_ajax.php')->withQueryParams($jsonQuery);
            
            $timetable .= '<h2>'.__('My Timetable').'</h2>';
            $timetable .= "<div hx-get='".$apiEndpoint."' hx-trigger='load' style='width: 100%; min-height: 40px; text-align: center'>";
            $timetable .= "<img style='margin: 10px 0 5px 0' src='".$this->session->get('absoluteURL')."/themes/Default/img/loading.gif' alt='".__('Loading')."' onclick='return false;' /><br/><p style='text-align: center'>".__('Loading').'</p>';
            $timetable .= '</div>';
        }

        // TABS
        $tabs = [];

        if (!empty($planner) || !empty($timetable)) {
            $tabs['Planner'] = [
                'label'   => __('Planner'),
                'content' => $planner.$timetable,
                'icon'    => 'book-open',
            ];
        }

        // Dashboard Hooks
        $hooks = $this->getContainer()->get(HookGateway::class)->getAccessibleHooksByType('Student Dashboard', $this->session->get('gibbonRoleIDCurrent'));
        foreach ($hooks as $hookData) {

            // Set the module for this hook for translations
            $this->session->set('module', $hookData['sourceModuleName']);
            $include = $this->session->get('absolutePath').'/modules/'.$hookData['sourceModuleName'].'/'.$hookData['sourceModuleInclude'];

            if (!file_exists($include)) {
                $hookOutput = Format::alert(__('The selected page cannot be displayed due to a hook error.'), 'error');
            } else {
                $hookOutput = include $include;
            }

            $tabs[$hookData['name']] = [
                'label'   => __($hookData['name'], [], $hookData['sourceModuleName']),
                'content' => $hookOutput,
                'icon'    => $hookData['name'],
            ];
        }

        // Set the default tab
        $studentDashboardDefaultTab = $this->settingGateway->getSettingByScope('School Admin', 'studentDashboardDefaultTab');
        $defaultTab = !isset($_GET['tab']) && !empty($studentDashboardDefaultTab)
            ? array_search($studentDashboardDefaultTab, array_keys($tabs))+1
            : preg_replace('/[^0-9]/', '', $_GET['tab'] ?? 1);

        $return .= $this->view->fetchFromTemplate('ui/tabs.twig.html', [
            'selected' => $defaultTab ?? 1,
            'tabs'     => $tabs,
            'outset'   => true,
            'icons'    => true,
        ]);

        return $return;
    }
}
