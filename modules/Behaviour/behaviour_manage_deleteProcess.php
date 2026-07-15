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

use Gibbon\Domain\Behaviour\BehaviourGateway;
use Gibbon\UI\Components\Alert;

include '../../gibbon.php';

$gibbonBehaviourID = $_POST['gibbonBehaviourID'] ?? '';
$address = $_POST['address'] ?? '';
$gibbonPersonID = $_POST['gibbonPersonID'] ?? '';
$gibbonFormGroupID = $_POST['gibbonFormGroupID'] ?? '';
$gibbonYearGroupID = $_POST['gibbonYearGroupID'] ?? '';
$type = $_GET['type'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($address)."/behaviour_manage_delete.php&gibbonBehaviourID=$gibbonBehaviourID&gibbonPersonID=$gibbonPersonID&gibbonFormGroupID=$gibbonFormGroupID&gibbonYearGroupID=$gibbonYearGroupID&type=$type";
$URLDelete = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($address)."/behaviour_manage.php&gibbonPersonID=$gibbonPersonID&gibbonFormGroupID=$gibbonFormGroupID&gibbonYearGroupID=$gibbonYearGroupID&type=$type";

if (isActionAccessible($guid, $connection2, '/modules/Behaviour/behaviour_manage_delete.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $highestAction = getHighestGroupedAction($guid, $address, $connection2);
    if ($highestAction == false) {
        $URL .= "&return=error0$params";
        header("Location: {$URL}");
    } else {
        // Proceed!
        if ($gibbonBehaviourID == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            $result = $container->get(BehaviourGateway::class)->getByID($gibbonBehaviourID);

            if (empty($result)) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
            } else {
                $row = $result;

                // Write to database
                $deleted = $container->get(BehaviourGateway::class)->delete($gibbonBehaviourID);

                if (!$deleted) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                // ALERTS: possible change to Behaviour alert status, recalculate alerts
                $container->get(Alert::class)->recalculateAlerts($row['gibbonPersonID']);

                $URLDelete = $URLDelete.'&return=success0';
                header("Location: {$URLDelete}");
            }
        }
    }
}
