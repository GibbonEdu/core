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

use Gibbon\Forms\Form;

//Module includes
include './modules/User Admin/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_personal_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Data Updater/data_personal_manage.php'>".__($guid, 'Personal Data Updates')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Request').'</div>';
    echo '</div>';

    //Check if school year specified
    $gibbonPersonUpdateID = $_GET['gibbonPersonUpdateID'];
    if ($gibbonPersonUpdateID == 'Y') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonPersonUpdateID' => $gibbonPersonUpdateID);
            $sql = 'SELECT gibbonPerson.* FROM gibbonPersonUpdate JOIN gibbonPerson ON (gibbonPersonUpdate.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPersonUpdateID=:gibbonPersonUpdateID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The selected record does not exist, or you do not have access to it.');
            echo '</div>';
        } else {
            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
            }

            $data = array('gibbonPersonUpdateID' => $gibbonPersonUpdateID);
            $sql = "SELECT gibbonPersonUpdate.* FROM gibbonPersonUpdate JOIN gibbonPerson ON (gibbonPersonUpdate.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPersonUpdateID=:gibbonPersonUpdateID";
            $newResult = $pdo->executeQuery($data, $sql);
            
            //Let's go!
            $oldValues = $result->fetch();
            $newValues = $newResult->fetch();

            //Get categories
            $staff = false;
            $student = false;
            $parent = false;
            $other = false;
            $roles = explode(',', $oldValues['gibbonRoleIDAll']);
            foreach ($roles as $role) {
                $roleCategory = getRoleCategory($role, $connection2);
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
                'citizenship1'           => __('Citizenship 1'),
                'citizenship1Passport'   => __('Citizenship 1 Passport Number'),
                'citizenship2'           => __('Citizenship 2'),
                'citizenship2Passport'   => __('Citizenship 2 Passport Number'),
                'nationalIDCardNumber'   => __('National ID Card Number'),
                'residencyStatus'        => __('Residency/Visa Type'),
                'visaExpiryDate'         => __('Visa Expiry Date'),
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

            $form = Form::create('updatePerson', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/data_personal_manage_editProcess.php?gibbonPersonUpdateID='.$gibbonPersonUpdateID);
            
            $form->setClass('fullWidth colorOddEven');
            $form->addHiddenValue('address', $_SESSION[$guid]['address']);
            $form->addHiddenValue('gibbonPersonID', $oldValues['gibbonPersonID']);

            $row = $form->addRow()->setClass('head heading');
                $row->addContent(__('Field'));
                $row->addContent(__('Current Value'));
                $row->addContent(__('New Value'));
                $row->addContent(__('Accept'));

            foreach ($compare as $fieldName => $label) {
                $oldValue = isset($oldValues[$fieldName])? $oldValues[$fieldName] : '';
                $newValue = isset($newValues[$fieldName])? $newValues[$fieldName] : '';
                $isMatching = ($oldValue != $newValue);
                $isNonUnique = false;

                if ($fieldName == 'dob' || $fieldName == 'visaExpiryDate') {
                    $oldValue = dateConvertBack($guid, $oldValue);
                    $newValue = dateConvertBack($guid, $newValue);
                }

                if ($fieldName == 'email') {
                    $uniqueEmailAddress = getSettingByScope($connection2, 'User Admin', 'uniqueEmailAddress');
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
                $row->addContent($newValue)->addClass($isMatching ? 'matchHighlightText' : '');
                
                if ($isNonUnique) {
                    $row->addContent(__('Must be unique.'));
                } else if ($isMatching) {
                    $row->addCheckbox('new'.$fieldName.'On')->checked(true)->setClass('textCenter');
                    $form->addHiddenValue('new'.$fieldName, $newValues[$fieldName]);
                } else {
                    $row->addContent();
                }
            }

            // CUSTOM FIELDS
			$oldFields = !empty($oldValues['fields'])? unserialize($oldValues['fields']) : array();
            $newFields = !empty($newValues['fields'])? unserialize($newValues['fields']) : array();
            $resultFields = getCustomFields($connection2, $guid, $student, $staff, $parent, $other, null, true);
            if ($resultFields->rowCount() > 0) {
                while ($rowFields = $resultFields->fetch()) {
                    $fieldName = $rowFields['gibbonPersonFieldID'];
                    $label = __($rowFields['name']);

                    $oldValue = isset($oldFields[$fieldName])? $oldFields[$fieldName] : '';
                    $newValue = isset($newFields[$fieldName])? $newFields[$fieldName] : '';

                    $isMatching = ($oldValue != $newValue);

                    $row = $form->addRow();
                    $row->addLabel('new'.$fieldName.'On', $label);
                    $row->addContent($oldValue);
                    $row->addContent($newValue)->addClass($isMatching ? 'matchHighlightText' : '');

                    if ($isMatching) {
                        $row->addCheckbox('newcustom'.$fieldName.'On')->checked(true)->setClass('textCenter');
                        $form->addHiddenValue('newcustom'.$fieldName, $newValue);
                    } else {
                        $row->addContent();
                    }
                }
            }
            
            $row = $form->addRow();
                $row->addSubmit();

            echo $form->getOutput();
        }
    }
}
?>
