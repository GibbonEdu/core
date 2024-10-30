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
use Gibbon\Data\Validator;
use Gibbon\Domain\FormGroups\FormGroupGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
$gibbonSchoolYearIDNext = $_GET['gibbonSchoolYearIDNext'] ?? '';
$URL = $session->get('absoluteURL')."/index.php?q=/modules/School Admin/formGroup_manage.php&gibbonSchoolYearID=$gibbonSchoolYearIDNext";

if (isActionAccessible($guid, $connection2, '/modules/School Admin/formGroup_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $formGroupGateway = $container->get(FormGroupGateway::class);

    // Check if school years specified (current and next)
    if (empty($gibbonSchoolYearID) || empty($gibbonSchoolYearIDNext)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Get the existing form groups for this school year
    $formGroups = $formGroupGateway->selectFormGroupsBySchoolYear($gibbonSchoolYearID)->fetchAll();
    if (empty($formGroups)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    $partialFail = false;
    $partialFailUnique = false;

    foreach ($formGroups as $formGroup) {
        $data = ['gibbonSchoolYearID' => $gibbonSchoolYearIDNext, 'name' => $formGroup['name'], 'nameShort' => $formGroup['nameShort'], 'gibbonPersonIDTutor' => $formGroup['gibbonPersonIDTutor'], 'gibbonPersonIDTutor2' => $formGroup['gibbonPersonIDTutor2'], 'gibbonPersonIDTutor3' => $formGroup['gibbonPersonIDTutor3'], 'gibbonSpaceID' => $formGroup['gibbonSpaceID'], 'website' => $formGroup['website']];

        // Check for uniqueness in the next school year
        if (!$formGroupGateway->unique($data, ['gibbonSchoolYearID', 'nameShort'])) {
            $partialFailUnique = true;
            continue;
        }

        // Insert the new form group
        $gibbonFormGroupID = $formGroupGateway->insert($data);
        $partialFail &= !$gibbonFormGroupID;
    }

    if ($partialFailUnique == true) {
        $URL .= '&return=error7';
        header("Location: {$URL}");
    } elseif ($partialFail == true) {
        $URL .= '&return=error5';
        header("Location: {$URL}");
    } else {
        $URL .= '&return=success0';
        header("Location: {$URL}");
    }
}
