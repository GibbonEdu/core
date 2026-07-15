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

use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Students\StudentGateway;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Formal Assessment/externalAssessment_view.php') == false) {
    // Access denied
    $page->addError(__('Your request failed because you do not have access to this action.'));
} else {
    //Get action with highest precedence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        if ($highestAction == 'View External Assessments_myChildrens') { // MY CHILDREN
            $page->breadcrumbs->add(__('View My Childrens\'s External Assessments'));

            $children = $container->get(StudentGateway::class)->selectActiveStudentsByFamilyAdult($session->get('gibbonSchoolYearID'), $session->get('gibbonPersonID'))->fetchGroupedUnique();
            
            if (empty($children)) {
                echo $page->getBlankSlate();
            } else {
                // Get child list
                $options = [];

                foreach($children as $child) {
                    $options[$child['gibbonPersonID']] = Format::name('', $child['preferredName'], $child['surname'], 'Student', true);
                }

                if (count($options) == 0) {
                    echo $page->getBlankSlate();
                } elseif (count($options) == 1) {
                    $gibbonPersonID = key($options);
                } else {
                    echo '<h2>';
                    echo 'Choose Student';
                    echo '</h2>';

                    $gibbonPersonID = (isset($_GET['search'])) ? $_GET['search'] : null;

                    $form = Form::create("filter", $session->get('absoluteURL')."/index.php", "get");
                    $form->setClass('noIntBorder w-full standardForm');

                    $form->addHiddenValue('q', '/modules/Formal Assessment/externalAssessment_view.php');
                    $form->addHiddenValue('address', $session->get('address'));

                    $row = $form->addRow();
                        $row->addLabel('search', __('Student'));
                        $row->addSelect('search')->fromArray($options)->selected($gibbonPersonID)->placeholder();

                    $row = $form->addRow();
                        $row->addSearchSubmit($session);

                    echo $form->getOutput();
                }

                $settingGateway = $container->get(SettingGateway::class);
                $showParentAttainmentWarning = $settingGateway->getSettingByScope('Markbook', 'showParentAttainmentWarning');
                $showParentEffortWarning = $settingGateway->getSettingByScope('Markbook', 'showParentEffortWarning');

                if ($gibbonPersonID != '' and count($options) > 0) {
                    
                    // Confirm access to this student
                    if (empty($children[$gibbonPersonID])) {
                        $page->addError(__('You do not have access to this action.'));
                        return;
                    }
                    
                    externalAssessmentDetails($guid, $gibbonPersonID, $connection2, null, false);
                }
            }
        } else { // My External Assessments
            $page->breadcrumbs->add(__('View My External Assessments'));

            echo '<h3>';
            echo __('External Assessments');
            echo '</h3>';

            echo externalAssessmentDetails($guid, $session->get('gibbonPersonID'), $connection2, null, false);
        }
    }
}
?>
