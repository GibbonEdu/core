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

use Gibbon\Domain\Messenger\GroupGateway;
use Gibbon\Data\Validator;

include '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$address = $_POST['address'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($address)."/groups_manage_add.php";

if (isActionAccessible($guid, $connection2, '/modules/Messenger/groups_manage_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    //Proceed!
    //Validate Inputs
    $name = $_POST['name'] ?? '';
    $choices = $_POST['members'] ?? array();

    if (empty($name) || empty($choices)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    } else {
        $groupGateway = $container->get(GroupGateway::class);

        //Create the group
        $data = array('gibbonPersonIDOwner' => $session->get('gibbonPersonID'), 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'name' => $name);
        $AI = $groupGateway->insertGroup($data);

        if (!$AI) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            $partialFail = false;

            //Run through each of the selected participants.
            foreach ($choices as $gibbonPersonID) {
                $data = array('gibbonGroupID' => $AI, 'gibbonPersonID' => $gibbonPersonID);
                $inserted = $groupGateway->insertGroupPerson($data);
                $partialFail &= !$inserted;
            }

            //Write to database
            if ($partialFail) {
                $URL .= '&return=warning1';
                header("Location: {$URL}");
                exit;
            } else {
                $URL .= "&return=success0&editID=$AI";
                header("Location: {$URL}");
                exit;
            }
        }
    }
}
