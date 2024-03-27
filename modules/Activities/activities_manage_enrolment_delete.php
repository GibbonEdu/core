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

use Gibbon\Forms\Prefab\DeleteForm;
use Gibbon\Domain\Activities\ActivityGateway;

if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_manage_enrolment_delete.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $gibbonActivityID = (isset($_GET['gibbonActivityID']))? $_GET['gibbonActivityID'] : null;

    $highestAction = getHighestGroupedAction($guid, '/modules/Activities/activities_manage_enrolment.php', $connection2);
    if ($highestAction == 'My Activities_viewEditEnrolment') {

            $result = $container->get(ActivityGateway::class)->selectActivityByYearandStaff($session->get('gibbonPersonID'), $session->get('gibbonSchoolYearID'), $gibbonActivityID);
            
        if (!$result || $result->rowCount() == 0) {
            //Acess denied
            $page->addError(__('You do not have access to this action.'));
            return;
        }
    }

    //Check if gibbonActivityID and gibbonPersonID specified
    $gibbonActivityID = $_GET['gibbonActivityID'] ?? '';
    $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
    if ($gibbonPersonID == '' or $gibbonActivityID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {

            $result = $container->get(ActivityGateway::class)->selectActivityAndStudent($gibbonActivityID, $gibbonPersonID);

        if ($result->rowCount() != 1) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $row = $result->fetch();

            $form = DeleteForm::createForm($session->get('absoluteURL').'/modules/'.$session->get('module')."/activities_manage_enrolment_deleteProcess.php?gibbonActivityID=$gibbonActivityID&gibbonPersonID=$gibbonPersonID&search=".$_GET['search']."&gibbonSchoolYearTermID=".$_GET['gibbonSchoolYearTermID']);
            echo $form->getOutput();
        }
    }
}
?>