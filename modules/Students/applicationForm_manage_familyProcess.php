<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

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

use Gibbon\Data\UsernameGenerator;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\User\FamilyGateway;
use Gibbon\Domain\Students\ApplicationFormGateway;

include '../../gibbon.php';

//Module includes from User Admin (for custom fields)
include '../User Admin/moduleFunctions.php';

$gibbonFamilyIDExisting = isset($_POST['gibbonFamilyIDExisting']) ? $_POST['gibbonFamilyIDExisting'] : '';
$gibbonApplicationFormID = isset($_POST['gibbonApplicationFormID']) ? $_POST['gibbonApplicationFormID'] : '';
$gibbonSchoolYearID = isset($_POST['gibbonSchoolYearID']) ? $_POST['gibbonSchoolYearID'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

$URL = $_SESSION[$guid]['absoluteURL'] . '/index.php?q=/modules/' . getModuleName($_POST['address']) . "/applicationForm_manage_edit.php&gibbonApplicationFormID=$gibbonApplicationFormID&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search";

if (isActionAccessible($guid, $connection2, '/modules/Students/applicationForm_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    if (empty($gibbonApplicationFormID) || empty($gibbonFamilyIDExisting) || empty($gibbonSchoolYearID)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    $applicationGateway = $container->get(ApplicationFormGateway::class);
    $familyGateway = $container->get(FamilyGateway::class);
    $userGateway = $container->get(UserGateway::class);

    $application = $applicationGateway->getApplicationFormByID($gibbonApplicationFormID);

    if (empty($application)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    $partialFail = false;

    $studentUserType = isset($_POST['studentUserType']) ? $_POST['studentUserType'] : '';
    $gibbonPersonIDStudent = isset($_POST['gibbonPersonIDStudent']) ? $_POST['gibbonPersonIDStudent'] : '';

    // Update the student details if the user exists (returning student)
    if ($studentUserType == 'existing' && !empty($gibbonPersonIDStudent)) {
        $student = $userGateway->getUserByID($gibbonPersonIDStudent);

        // Merge the new custom fields into the existing field data
        $existingFields = !empty($student['fields']) ? unserialize($student['fields']) : []; 
        $newFields = !empty($application['fields']) ? unserialize($application['fields']) : []; 
        $newFields = array_replace($existingFields, $newFields);
        
        // Update the student user details
        $updated = $userGateway->updateUser([
            'gibbonPersonID'       => $gibbonPersonIDStudent,
            'surname'              => $application['surname'],
            'firstName'            => $application['firstName'],
            'preferredName'        => $application['preferredName'],
            'officialName'         => $application['officialName'],
            'nameInCharacters'     => $application['nameInCharacters'],
            'gender'               => $application['gender'],
            'dob'                  => $application['dob'],
            'languageFirst'        => $application['languageFirst'],
            'languageSecond'       => $application['languageSecond'],
            'languageThird'        => $application['languageThird'],
            'countryOfBirth'       => $application['countryOfBirth'],
            'citizenship1'         => $application['citizenship1'],
            'citizenship1Passport' => $application['citizenship1Passport'],
            'nationalIDCardNumber' => $application['nationalIDCardNumber'],
            'residencyStatus'      => $application['residencyStatus'],
            'visaExpiryDate'       => $application['visaExpiryDate'],
            'email'                => $application['email'],
            'phone1Type'           => $application['phone1Type'],
            'phone1CountryCode'    => $application['phone1CountryCode'],
            'phone1'               => $application['phone1'],
            'phone2Type'           => $application['phone2Type'],
            'phone2CountryCode'    => $application['phone2CountryCode'],
            'phone2'               => $application['phone2'],
            'fields'               => serialize($newFields),
        ]);

        $partialFail &= !$updated;
    }

    // Update users for parent1 and/or parent2 if they already exist
    for ($i = 1; $i <= 2; $i++) {
        $parentUserType = isset($_POST["parent{$i}UserType"]) ? $_POST["parent{$i}UserType"] : '';
        $parentRelationship = isset($_POST["parent{$i}relationship"]) ? $_POST["parent{$i}relationship"] : '';
        $gibbonPersonIDParent = isset($_POST["parent{$i}gibbonPersonID"]) ? $_POST["parent{$i}gibbonPersonID"] : '';

        if ($parentUserType == 'existing' && !empty($gibbonPersonIDParent)) {
            $parent = $userGateway->getUserByID($gibbonPersonIDParent);

            // Insert the parent's application form relationship, if it doesn't exist
            $relationship = $applicationGateway->getApplicationFormRelationship($gibbonApplicationFormID, $gibbonPersonIDParent);
            if (empty($relationship)) {
                $applicationGateway->insertApplicationFormRelationship([
                    'gibbonApplicationFormID' => $gibbonApplicationFormID,
                    'gibbonPersonID'          => $gibbonPersonIDParent,
                    'relationship'            => $parentRelationship,
                ]);
            }

            // Merge the new custom fields into the existing field data
            $existingFields = !empty($parent['fields']) ? unserialize($parent['fields']) : []; 
            $newFields = !empty($application["parent{$i}field"]) ? unserialize($application["parent{$i}field"]) : []; 
            $newFields = array_replace($existingFields, $newFields);

            // Update the parent user details
            $updated = $userGateway->updateUser([
                'gibbonPersonID'       => $gibbonPersonIDParent,
                'title'                => $application["parent{$i}title"],
                'surname'              => $application["parent{$i}surname"],
                'firstName'            => $application["parent{$i}firstName"],
                'preferredName'        => $application["parent{$i}preferredName"],
                'officialName'         => $application["parent{$i}officialName"],
                'nameInCharacters'     => $application["parent{$i}nameInCharacters"],
                'gender'               => $application["parent{$i}gender"],
                'languageFirst'        => $application["parent{$i}languageFirst"],
                'languageSecond'       => $application["parent{$i}languageSecond"],
                'citizenship1'         => $application["parent{$i}citizenship1"],
                'nationalIDCardNumber' => $application["parent{$i}nationalIDCardNumber"],
                'residencyStatus'      => $application["parent{$i}residencyStatus"],
                'visaExpiryDate'       => $application["parent{$i}visaExpiryDate"],
                'email'                => $application["parent{$i}email"],
                'phone1Type'           => $application["parent{$i}phone1Type"],
                'phone1CountryCode'    => $application["parent{$i}phone1CountryCode"],
                'phone1'               => $application["parent{$i}phone1"],
                'phone2Type'           => $application["parent{$i}phone2Type"],
                'phone2CountryCode'    => $application["parent{$i}phone2CountryCode"],
                'phone2'               => $application["parent{$i}phone2"],
                'profession'           => $application["parent{$i}profession"],
                'employer'             => $application["parent{$i}employer"],
                'fields'               => serialize($newFields),
            ]);
            $partialFail &= !$updated;
        }
    }

    $parent1gibbonPersonID = isset($_POST['parent1gibbonPersonID']) ? $_POST['parent1gibbonPersonID'] : null;
    $parent2gibbonPersonID = isset($_POST['parent2gibbonPersonID']) ? $_POST['parent2gibbonPersonID'] : null;
    
    // Link the application form to the existing family
    $applicationGateway->updateApplicationForm([
        'gibbonApplicationFormID' => $gibbonApplicationFormID,
        'gibbonFamilyID'          => $gibbonFamilyIDExisting,
        'gibbonPersonIDStudent'   => $gibbonPersonIDStudent,
        'parent1gibbonPersonID'   => $parent1gibbonPersonID,
        'parent2gibbonPersonID'   => $parent2gibbonPersonID,
    ]);

    // Update the address of the existing family
    $familyGateway->updateFamily([
        'gibbonFamilyID'        => $gibbonFamilyIDExisting,
        'homeAddress'           => $application['homeAddress'],
        'homeAddressDistrict'   => $application['homeAddressDistrict'],
        'homeAddressCountry'    => $application['homeAddressCountry'],
    ]);

    if ($partialFail == true) {
        $URL .= '&return=warning1';
        header("Location: {$URL}");
        exit;
    } else {
        $URL .= '&return=success0';
        header("Location: {$URL}");
        exit;
    }
}
