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
use Gibbon\Domain\DataSet;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\User\UserGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

$settingGateway = $container->get(SettingGateway::class);
$enableDescriptors = $settingGateway->getSettingByScope('Behaviour', 'enableDescriptors');
$enableLevels = $settingGateway->getSettingByScope('Behaviour', 'enableLevels');

if (isActionAccessible($guid, $connection2, '/modules/Behaviour/behaviour_view_details.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';

        $page->breadcrumbs
            ->add(__('View Behaviour Records'), 'behaviour_manage.php')
            ->add(__('View Student Record'));

        $search = $_GET['search'] ?? '';
        if (!empty($search)) {
            $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Behaviour', 'behaviour_view.php')->withQueryParam('search', $search));
        }

        try {
            if ($highestAction == 'View Behaviour Records_all') {
                $result = $container->get(UserGateway::class)->getActiveStudentDetails($gibbonPersonID, $session->get('gibbonSchoolYearID'));
            } else {
                $result = $container->get(UserGateway::class)->getChildDetailsByFamilyAdult($session->get('gibbonSchoolYearID'), $session->get('gibbonPersonID'), $gibbonPersonID);
            }
        } catch (PDOException $e) {
        }
        
        if ($result->rowCount() != 1) {
            $page->addError(__('The selected record does not exist, or you do not have access to it.'));
        } else {
            $row = $result->fetch();

            // DISPLAY STUDENT DATA
            $table = DataTable::createDetails('personal');
            $table->addColumn('name', __('Name'))->format(Format::using('name', ['', 'preferredName', 'surname', 'Student', 'true']));
                        $table->addColumn('yearGroup', __('Year Group'));
                        $table->addColumn('formGroup', __('Form Group'));

            echo $table->render([$row]);

            echo getBehaviourRecord($container, $gibbonPersonID);
        }
    }
}
