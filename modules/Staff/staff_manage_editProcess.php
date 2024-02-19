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

use Gibbon\Services\Format;
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonStaffID = $_GET['gibbonStaffID'] ?? '';
$allStaff = $_GET['allStaff'] ?? '';
$search = $_GET['search'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/staff_manage_edit.php&gibbonStaffID=$gibbonStaffID&search=$search&allStaff=$allStaff";

if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if gibbonStaffID specified
    if ($gibbonStaffID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        try {
            $data = array('gibbonStaffID' => $gibbonStaffID);
            $sql = 'SELECT * FROM gibbonStaff WHERE gibbonStaffID=:gibbonStaffID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        if ($result->rowCount() != 1) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
        } else {
            //Validate Inputs
            $initials = $_POST['initials'] ?? '';
            if ($initials == '') {
                $initials = null;
            }
            $type = $_POST['type'] ?? '';
            $jobTitle = $_POST['jobTitle'] ?? '';
            $dateStart = $_POST['dateStart'] ?? '';
            if ($dateStart == '') {
                $dateStart = null;
            } else {
                $dateStart = Format::dateConvert($dateStart);
            }
            $dateEnd = $_POST['dateEnd'] ?? '';
            if ($dateEnd == '') {
                $dateEnd = null;
            } else {
                $dateEnd = Format::dateConvert($dateEnd);
            }
            $firstAidQualified = $_POST['firstAidQualified'] ?? '';
            $firstAidQualification = $_POST['firstAidQualification'] ?? null;
            $firstAidExpiry = ($firstAidQualified == 'Y' and !empty($_POST['firstAidExpiry'])) ? Format::dateConvert($_POST['firstAidExpiry']) : null;
            $countryOfOrigin = $_POST['countryOfOrigin'] ?? '';
            $qualifications = $_POST['qualifications'] ?? '';
            $biographicalGrouping = $_POST['biographicalGrouping'] ?? '';
            $biographicalGroupingPriority = $_POST['biographicalGroupingPriority'] ?? '';
            $biography = $_POST['biography'] ?? '';

            if ($type == '') {
                $URL .= '&return=error3';
                header("Location: {$URL}");
            } else {
                //Check unique inputs for uniquness
                try {
                    $data = array('gibbonStaffID' => $gibbonStaffID, 'initials' => $initials);
                    $sql = "SELECT * FROM gibbonStaff WHERE initials=:initials AND NOT gibbonStaffID=:gibbonStaffID AND NOT initials=''";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                }

                $customRequireFail = false;
                $fields = $container->get(CustomFieldHandler::class)->getFieldDataFromPOST('Staff', [], $customRequireFail);

                if ($customRequireFail) {
                    $URL .= '&return=error1';
                    header("Location: {$URL}");
                    exit;
                }

                if ($result->rowCount() > 0) {
                    $URL .= '&return=error3';
                    header("Location: {$URL}");
                } else {
                    //Write to database
                    try {
                        $data = array('initials' => $initials, 'type' => $type, 'jobTitle' => $jobTitle, 'dateStart' => $dateStart, 'dateEnd' => $dateEnd, 'firstAidQualified' => $firstAidQualified, 'firstAidQualification' => $firstAidQualification, 'firstAidExpiry' => $firstAidExpiry, 'countryOfOrigin' => $countryOfOrigin, 'qualifications' => $qualifications, 'biographicalGrouping' => $biographicalGrouping, 'biographicalGroupingPriority' => $biographicalGroupingPriority, 'biography' => $biography, 'fields' => $fields, 'gibbonStaffID' => $gibbonStaffID);
                        $sql = 'UPDATE gibbonStaff JOIN gibbonPerson ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) SET initials=:initials, type=:type, gibbonStaff.jobTitle=:jobTitle, dateStart=:dateStart, dateEnd=:dateEnd, firstAidQualified=:firstAidQualified, firstAidQualification=:firstAidQualification, firstAidExpiry=:firstAidExpiry, countryOfOrigin=:countryOfOrigin, qualifications=:qualifications, biographicalGrouping=:biographicalGrouping, biographicalGroupingPriority=:biographicalGroupingPriority, biography=:biography, gibbonStaff.fields=:fields WHERE gibbonStaffID=:gibbonStaffID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    $URL .= '&return=success0';
                    header("Location: {$URL}");
                }
            }
        }
    }
}
