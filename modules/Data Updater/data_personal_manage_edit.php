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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\User\RoleGateway;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Forms\PersonalDocumentHandler;

//Module includes
include './modules/User Admin/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_personal_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');

    $urlParams = ['gibbonSchoolYearID' => $gibbonSchoolYearID];

    $page->breadcrumbs
        ->add(__('Personal Data Updates'), 'data_personal_manage.php', $urlParams)
        ->add(__('Edit Request'));

    //Check if gibbonPersonUpdateID specified
    $gibbonPersonUpdateID = $_GET['gibbonPersonUpdateID'] ?? '';
    if ($gibbonPersonUpdateID == 'Y') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {

            $data = array('gibbonPersonUpdateID' => $gibbonPersonUpdateID);
            $sql = 'SELECT gibbonPerson.* FROM gibbonPersonUpdate JOIN gibbonPerson ON (gibbonPersonUpdate.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPersonUpdateID=:gibbonPersonUpdateID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The selected record does not exist, or you do not have access to it.'));
        } else {
            $data = array('gibbonPersonUpdateID' => $gibbonPersonUpdateID);
            $sql = "SELECT gibbonPersonUpdate.* FROM gibbonPersonUpdate JOIN gibbonPerson ON (gibbonPersonUpdate.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPersonUpdateID=:gibbonPersonUpdateID";
            $newResult = $pdo->executeQuery($data, $sql);

            //Let's go!
            $oldValues = $result->fetch();
            $newValues = $newResult->fetch();

            /** @var RoleGateway */
            $roleGateway = $container->get(RoleGateway::class);

            // Provide a link back to edit the associated record
            if (isActionAccessible($guid, $connection2, '/modules/User Admin/user_manage_edit.php') == true) {
                $page->navigator->addHeaderAction('edit', __('Edit User'))
                    ->setURL('/modules/User Admin/user_manage_edit.php')
                    ->addParam('gibbonPersonID', $oldValues['gibbonPersonID'])
                    ->setIcon('config')
                    ->displayLabel();
            }
            if (
                isActionAccessible($guid, $connection2, '/modules/Students/student_view_details.php') == true
                && $roleGateway->getRoleCategory($oldValues['gibbonRoleIDPrimary']) == 'Student'
            ) {
                $page->navigator->addHeaderAction('view', __('View Student'))
                    ->setURL('/modules/Students/student_view_details.php')
                    ->addParam('gibbonPersonID', $oldValues['gibbonPersonID'])
                    ->setIcon('plus')
                    ->displayLabel();
             }

            //Get categories
            $staff = false;
            $student = false;
            $parent = false;
            $other = false;
            $roles = explode(',', $oldValues['gibbonRoleIDAll']);
            foreach ($roles as $role) {
                $roleCategory = $roleGateway->getRoleCategory($role);
                $staff = $staff || ($roleCategory == 'Staff');
                $student = $student || ($roleCategory == 'Student');
                $parent = $parent || ($roleCategory == 'Parent');
                $other = $other || ($roleCategory == 'Other');
            }

            // An array of common fields to compare in each data set, and the field label
            $compare = array(
                'title'                  => __('Title'),
                'surname'                => __('Surname'),
                'firstName'              => __('First Name'),
                'preferredName'          => __('Preferred Name'),
                'officialName'           => __('Official Name'),
                'nameInCharacters'       => __('Name In Characters'),
                'dob'                    => __('Date of Birth'),
                'email'                  => __('Email'),
                'emailAlternate'         => __('Alternate Email'),
                'address1'               => __('Address 1'),
                'address1District'       => __('Address 1 District'),
                'address1Country'        => __('Address 1 Country'),
                'address2'               => __('Address 2'),
                'address2District'       => __('Address 2 District'),
                'address2Country'        => __('Address 2 Country'),
                'phone1Type'             => __('Phone').' 1 '.__('Type'),
                'phone1CountryCode'      => __('Phone').' 1 '.__('Country Code'),
                'phone1'                 => __('Phone').' 1 ',
                'phone2Type'             => __('Phone').' 2 '.__('Type'),
                'phone2CountryCode'      => __('Phone').' 2 '.__('Country Code'),
                'phone2'                 => __('Phone').' 2 ',
                'phone3Type'             => __('Phone').' 3 '.__('Type'),
                'phone3CountryCode'      => __('Phone').' 3 '.__('Country Code'),
                'phone3'                 => __('Phone').' 3 ',
                'phone4Type'             => __('Phone').' 4 '.__('Type'),
                'phone4CountryCode'      => __('Phone').' 4 '.__('Country Code'),
                'phone4'                 => __('Phone').' 4 ',
                'languageFirst'          => __('First Language'),
                'languageSecond'         => __('Second Language'),
                'languageThird'          => __('Third Language'),
                'countryOfBirth'         => __('Country of Birth'),
                'ethnicity'              => __('Ethnicity'),
                'religion'               => __('Religion'),
                'vehicleRegistration'    => __('Vehicle Registration'),
            );

            if ($student || $staff) {
                $compare['emergency1Name']         = __('Emergency 1 Name');
                $compare['emergency1Number1']      = __('Emergency 1 Number 1');
                $compare['emergency1Number2']      = __('Emergency 1 Number 2');
                $compare['emergency1Relationship'] = __('Emergency 1 Relationship');
                $compare['emergency2Name']         = __('Emergency 2 Name');
                $compare['emergency2Number1']      = __('Emergency 2 Number 1');
                $compare['emergency2Number2']      = __('Emergency 2 Number 2');
                $compare['emergency2Relationship'] = __('Emergency 2 Relationship');
            }

            if ($student) {
                $compare['privacy'] = __('Privacy');
            }

            if ($parent) {
                $compare['profession'] = __('Profession');
                $compare['employer']   = __('Employer');
                $compare['jobTitle']   = __('Job Title');
            }

            $form = Form::createTable('updatePerson', $session->get('absoluteURL').'/modules/'.$session->get('module').'/data_personal_manage_editProcess.php?gibbonPersonUpdateID='.$gibbonPersonUpdateID);

            $form->setClass('fullWidth colorOddEven');
            $form->addHiddenValue('address', $session->get('address'));
            $form->addHiddenValue('gibbonPersonID', $oldValues['gibbonPersonID']);

            $row = $form->addRow()->setClass('head heading');
                $row->addContent(__('Field'));
                $row->addContent(__('Current Value'));
                $row->addContent(__('New Value'));
                $row->addContent(__('Accept'));

            $changeCount = 0;
            foreach ($compare as $fieldName => $label) {
                $oldValue = isset($oldValues[$fieldName])? $oldValues[$fieldName] : '';
                $newValue = isset($newValues[$fieldName])? $newValues[$fieldName] : '';
                $isNotMatching = ($oldValue != $newValue);
                $isNonUnique = false;

                if ($fieldName == 'dob') {
                    $oldValue = Format::date($oldValue);
                    $newValue = Format::date($newValue);
                }

                if ($fieldName == 'email') {
                    $uniqueEmailAddress = $container->get(SettingGateway::class)->getSettingByScope('User Admin', 'uniqueEmailAddress');
                    if ($uniqueEmailAddress == 'Y') {
                        $data = array('gibbonPersonID' => $oldValues['gibbonPersonID'], 'email' => $newValues['email']);
                        $sql = "SELECT COUNT(*) FROM gibbonPerson WHERE email=:email AND gibbonPersonID<>:gibbonPersonID";
                        $result = $pdo->executeQuery($data, $sql);
                        $isNonUnique = ($result && $result->rowCount() == 1)? $result->fetchColumn(0) > 0 : false;
                    }
                }

                $row = $form->addRow();
                $row->addLabel('new'.$fieldName.'On', $label);
                $row->addContent($oldValue);
                $row->addContent($newValue)->addClass($isNotMatching ? 'matchHighlightText' : '');

                if ($isNonUnique) {
                    $row->addContent(__('Must be unique.'));
                } else if ($isNotMatching) {
                    $row->addCheckbox('new'.$fieldName.'On')->checked(true)->setClass('textCenter');
                    $form->addHiddenValue('new'.$fieldName, $newValues[$fieldName]);
                    $changeCount++;
                } else {
                    $row->addContent();
                }
            }

            // CUSTOM FIELDS
            $params = compact('student', 'staff', 'parent', 'other') + ['dataUpdater' => 1];
            $changeCount += $container->get(CustomFieldHandler::class)->addCustomFieldsToDataUpdate($form, 'User', $params, $oldValues, $newValues);

            // PERSONAL DOCUMENTS
            $params = compact('student', 'staff', 'parent', 'other') + ['dataUpdater' => 1];
            $changeCount += $container->get(PersonalDocumentHandler::class)->addPersonalDocumentsToDataUpdate($form, $oldValues['gibbonPersonID'], $gibbonPersonUpdateID, $params);

            $row = $form->addRow();
                $row->addSubmit();

            echo $form->getOutput();
        }
    }
}
?>
