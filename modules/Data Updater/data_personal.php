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

use Gibbon\View\View;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\User\PersonalDocumentGateway;
use Gibbon\Domain\User\RoleGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';
include './modules/User Admin/moduleFunctions.php'; //for User Admin (for custom fields)

if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_personal.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        //Proceed!
        $page->breadcrumbs->add(__('Update Personal Data'));

        if ($highestAction == 'Update Personal Data_any') {
            echo '<p>';
            echo __('This page allows a user to request selected personal data updates for any user.');
            echo '</p>';
        } else {
            echo '<p>';
            echo __('This page allows any adult with data access permission to request selected personal data updates for any member of their family.');
            echo '</p>';
        }

        $customResponces = array();
        $error3 = __('Your request was successful, but some data was not properly saved. An administrator will process your request as soon as possible. You will not see the updated data in the system until it has been processed.');
        if ($session->get('organisationDBAEmail') != '' and $session->get('organisationDBAName') != '') {
            $error3 .= ' '.sprintf(__('Please contact %1$s if you have any questions.'), "<a href='mailto:".$session->get('organisationDBAEmail')."'>".$session->get('organisationDBAName').'</a>');
        }
        $customResponces['error3'] = $error3;

        $success0 = __('Your request was completed successfully. An administrator will process your request as soon as possible. You will not see the updated data in the system until it has been processed.');
        if ($session->get('organisationDBAEmail') != '' and $session->get('organisationDBAName') != '') {
            $success0 .= ' '.sprintf(__('Please contact %1$s if you have any questions.'), "<a href='mailto:".$session->get('organisationDBAEmail')."'>".$session->get('organisationDBAName').'</a>');
        }
        $customResponces['success0'] = $success0;

        $page->return->addReturns($customResponces);

        echo '<h2>';
        echo __('Choose User');
        echo '</h2>';

        $gibbonPersonID = $_GET['gibbonPersonID'] ?? null;

        $form = Form::create('selectPerson', $session->get('absoluteURL').'/index.php', 'get');
        $form->addHiddenValue('q', '/modules/'.$session->get('module').'/data_personal.php');

        if ($highestAction == 'Update Personal Data_any') {
            $data = array();
            $sql = "SELECT username, surname, preferredName, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE status='Full' ORDER BY surname, preferredName";
        } else {
            $data = array('gibbonPersonID' => $session->get('gibbonPersonID'));
            $sql = "(SELECT gibbonFamilyAdult.gibbonFamilyID, gibbonFamily.name as familyName, child.surname, child.preferredName, child.gibbonPersonID
                    FROM gibbonFamilyAdult
                    JOIN gibbonFamily ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID)
                    JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID)
                    JOIN gibbonPerson as child ON (gibbonFamilyChild.gibbonPersonID=child.gibbonPersonID)
                    WHERE gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID
                    AND gibbonFamilyAdult.childDataAccess='Y' AND child.status='Full')
                UNION (SELECT gibbonFamily.gibbonFamilyID, gibbonFamily.name as familyName, adult.surname, adult.preferredName, adult.gibbonPersonID
                    FROM gibbonFamilyAdult
                    JOIN gibbonFamily ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID)
                    JOIN gibbonFamilyAdult as familyAdult ON (familyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID)
                    JOIN gibbonPerson as adult ON (familyAdult.gibbonPersonID=adult.gibbonPersonID)
                    WHERE gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID AND adult.status='Full')
                ORDER BY surname, preferredName";
        }
        $result = $pdo->executeQuery($data, $sql);
        $resultSet = ($result && $result->rowCount() > 0)? $result->fetchAll() : array();

        // Collect a list of people with formatted names, add username for Data_any access
        $people = array_reduce($resultSet, function($carry, $person) use ($highestAction) {
            $id = str_pad($person['gibbonPersonID'], 10, '0', STR_PAD_LEFT);
            $carry[$id] = Format::name('', htmlPrep($person['preferredName']), htmlPrep($person['surname']), 'Student', true);
            if ($highestAction == 'Update Personal Data_any') {
                $carry[$id] .= ' ('.$person['username'].')';
            }
            return $carry;
        }, array());

        // Add self to people if not in the list
        if (array_key_exists($session->get('gibbonPersonID'), $people) == false) {
            $people[$session->get('gibbonPersonID')] = Format::name('', htmlPrep($session->get('preferredName')), htmlPrep($session->get('surname')), 'Student', true);
        }

        $row = $form->addRow();
            $row->addLabel('gibbonPersonID', __('Person'));
            $row->addSelect('gibbonPersonID')
                ->fromArray($people)
                ->required()
                ->selected($gibbonPersonID)
                ->placeholder();

        $row = $form->addRow();
            $row->addSubmit();

        echo $form->getOutput();

        if ($gibbonPersonID != '') {
            echo '<h2>';
            echo __('Update Data');
            echo '</h2>';

            //Check access to person
            $checkCount = 0;
            $self = false;
            if ($highestAction == 'Update Personal Data_any') {

                    $dataSelect = array();
                    $sqlSelect = "SELECT surname, preferredName, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE status='Full' ORDER BY surname, preferredName";
                    $resultSelect = $connection2->prepare($sqlSelect);
                    $resultSelect->execute($dataSelect);
                $checkCount = $resultSelect->rowCount();
                $self = true;
            } else {

                    $dataCheck = array('gibbonPersonID' => $session->get('gibbonPersonID'));
                    $sqlCheck = "SELECT gibbonFamilyAdult.gibbonFamilyID, name FROM gibbonFamilyAdult JOIN gibbonFamily ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y' ORDER BY name";
                    $resultCheck = $connection2->prepare($sqlCheck);
                    $resultCheck->execute($dataCheck);
                while ($rowCheck = $resultCheck->fetch()) {

                        $dataCheck2 = array('gibbonFamilyID1' => $rowCheck['gibbonFamilyID'], 'gibbonFamilyID2' => $rowCheck['gibbonFamilyID']);
                        $sqlCheck2 = "(SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonFamilyID FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND gibbonFamilyID=:gibbonFamilyID1) UNION (SELECT surname, preferredName, gibbonPerson.gibbonPersonID, gibbonFamilyID FROM gibbonFamilyAdult JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND gibbonFamilyID=:gibbonFamilyID2)";
                        $resultCheck2 = $connection2->prepare($sqlCheck2);
                        $resultCheck2->execute($dataCheck2);
                    while ($rowCheck2 = $resultCheck2->fetch()) {
                        if ($gibbonPersonID == $rowCheck2['gibbonPersonID']) {
                            ++$checkCount;
                        }
                        //Check for self
                        if ($rowCheck2['gibbonPersonID'] == $session->get('gibbonPersonID')) {
                            $self = true;
                        }
                    }
                }
            }

            if ($self == false and $gibbonPersonID == $session->get('gibbonPersonID')) {
                ++$checkCount;
            }

            if ($checkCount < 1) {
                $page->addError(__('The selected record does not exist, or you do not have access to it.'));
            } else {
                //Get categories

                    $dataSelect = array('gibbonPersonID' => $gibbonPersonID);
                    $sqlSelect = 'SELECT gibbonRoleIDAll, gibbonRoleIDPrimary FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName';
                    $resultSelect = $connection2->prepare($sqlSelect);
                    $resultSelect->execute($dataSelect);
                if ($resultSelect->rowCount() == 1) {
                    $rowSelect = $resultSelect->fetch();
                    //Get categories
                    $staff = $student = $parent = $other = false;
                    $roles = explode(',', $rowSelect['gibbonRoleIDAll']);
                    /** @var RoleGateway */
                    $roleGateway = $container->get(RoleGateway::class);
                    $primaryRoleCategory = $roleGateway->getRoleCategory($rowSelect['gibbonRoleIDPrimary']);
                    $roleCategories = [];
                    foreach ($roles as $role) {
                        $roleCategory = $roleGateway->getRoleCategory($role);
                        $staff = $staff || ($roleCategory == 'Staff');
                        $student = $student || ($roleCategory == 'Student');
                        $parent = $parent || ($roleCategory == 'Parent');
                        $other = $other || ($roleCategory == 'Other');
                        $roleCategories[$roleCategory] = $roleCategory;
                    }
                }

                //Check if there is already a pending form for this user
                $existing = false;
                $proceed = false;
                $requiredFields = [];

                $settingGateway = $container->get(SettingGateway::class);

                if ($highestAction != 'Update Personal Data_any') {
                    $requiredFieldsSetting = unserialize($settingGateway->getSettingByScope('User Admin', 'personalDataUpdaterRequiredFields'));
                    if (is_array($requiredFieldsSetting)) {
                        if (!isset($requiredFieldsSetting[$primaryRoleCategory])) {
                            // If there's no per-role settings then handle the original required field Y/N settings
                            $requiredFields = array_map(function ($item) {
                                return $item == 'Y'? 'required' : '';
                            }, $requiredFieldsSetting);
                        } elseif (is_array($roleCategories) && count($roleCategories) > 1) {
                            // Flip the array from role=>field=>value to field=>role=>value
                            // Loop by only the roles categories this user has.
                            foreach ($roleCategories as $roleCategory) {
                                $fields = $requiredFieldsSetting[$roleCategory] ?? [];
                                foreach ($fields as $name => $value) {
                                    $requiredFields[$name][$roleCategory] = $value;
                                }
                            }
                            // Reduce each field to the setting with the greatest priority.
                            // Eg: required by at least one role = a required field.
                            $requiredFields = array_map(function ($field) {
                                if (in_array('required', $field)) return 'required';
                                if (in_array('', $field, true)) return '';
                                if (in_array('readonly', $field)) return 'readonly';
                                if (in_array('hidden', $field)) return 'hidden';
                                return '';
                            }, $requiredFields);
                        } else {
                            // Grab the required fields for the users primary roles
                            $requiredFields = $requiredFieldsSetting[$primaryRoleCategory];
                        }
                    }
                }


                    $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonPersonIDUpdater' => $session->get('gibbonPersonID'));
                    $sql = "SELECT * FROM gibbonPersonUpdate WHERE gibbonPersonID=:gibbonPersonID AND gibbonPersonIDUpdater=:gibbonPersonIDUpdater AND status='Pending'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);

                if ($result->rowCount() > 1) {
                    $page->addError(__('Your request failed due to a database error.'));
                } elseif ($result->rowCount() == 1) {
                    $existing = true;
                    echo "<div class='warning'>";
                    echo __('You have already submitted a form, which is awaiting processing by an administrator. If you wish to make changes, please edit the data below, but remember your data will not appear in the system until it has been processed.');
                    echo '</div>';

                    if ($highestAction != 'Update Personal Data_any') {
                        $proceed = is_array($requiredFields);
                    } else {
                        $proceed = true;
                    }
                } else {
                    //Get user's data

                        $data = array('gibbonPersonID' => $gibbonPersonID);
                        $sql = 'SELECT * FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    if ($result->rowCount() != 1) {
                        $page->addError(__('The specified record cannot be found.'));
                    } else {
                        if ($highestAction != 'Update Personal Data_any') {
                            $proceed = is_array($requiredFields);
                        } else {
                            $proceed = true;
                        }
                    }
                }

                if ($proceed == true) {
                    //Let's go!
                    $values = $result->fetch();

                    // Closure: Check if a field is visible.
                    $isVisible = function ($name) use ($requiredFields) {
                        return empty($requiredFields[$name]) || $requiredFields[$name] != 'hidden';
                    };

                    // Closure: check if any field in a given array are visible.
                    // Useful to hide headings in sections if not needed.
                    $anyVisible = function ($names) use ($requiredFields) {
                        if (empty($requiredFields)) return true;
                        $fields = array_intersect_key($requiredFields, array_flip($names));
                        $visible = array_filter($fields, function ($item) {
                            return empty($item) || $item != 'hidden';
                        });

                        return count($visible) > 0;
                    };

                    $form = Form::create('updateFinance', $session->get('absoluteURL').'/modules/'.$session->get('module').'/data_personalProcess.php?gibbonPersonID='.$gibbonPersonID);
                    $form->setFactory(DatabaseFormFactory::create($pdo));

                    $form->addHiddenValue('address', $session->get('address'));
                    $form->addHiddenValue('existing', isset($values['gibbonPersonUpdateID'])? $values['gibbonPersonUpdateID'] : 'N');

                    // BASIC INFORMATION
                    $form->addRow()->addHeading('Basic Information', __('Basic Information'));

                    $row = $form->addRow()->onlyIf($isVisible('title'));
                        $row->addLabel('title', __('Title'));
                        $row->addSelectTitle('title');

                    $row = $form->addRow()->onlyIf($isVisible('surname'));
                        $row->addLabel('surname', __('Surname'))->description(__('Family name as shown in ID documents.'));
                        $row->addTextField('surname')->maxLength(60);

                    $row = $form->addRow()->onlyIf($isVisible('firstName'));
                        $row->addLabel('firstName', __('First Name'))->description(__('First name as shown in ID documents.'));
                        $row->addTextField('firstName')->maxLength(60);

                    $row = $form->addRow()->onlyIf($isVisible('preferredName'));
                        $row->addLabel('preferredName', __('Preferred Name'))->description(__('Most common name, alias, nickname, etc.'));
                        $row->addTextField('preferredName')->maxLength(60);

                    $row = $form->addRow()->onlyIf($isVisible('officialName'));
                        $row->addLabel('officialName', __('Official Name'))->description(__('Full name as shown in ID documents.'));
                        $row->addTextField('officialName')->maxLength(150)->setTitle(__('Please enter full name as shown in ID documents'));

                    $row = $form->addRow()->onlyIf($isVisible('nameInCharacters'));
                        $row->addLabel('nameInCharacters', __('Name In Characters'))->description(__('Chinese or other character-based name.'));
                        $row->addTextField('nameInCharacters')->maxLength(60);

                    $row = $form->addRow()->onlyIf($isVisible('dob'));
                        $row->addLabel('dob', __('Date of Birth'));
                        $row->addDate('dob');

                    // EMERGENCY CONTACTS
                    if ($student || $staff) {
                        $form->addRow()
                            ->onlyIf($anyVisible(['emergency1Name', 'emergency1Relationship', 'emergency1Number1', 'emergency1Number2', 'emergency2Name', 'emergency2Relationship', 'emergency2Number1', 'emergency2Number2']))
                            ->addHeading('Emergency Contacts', __('Emergency Contacts'));

                        $form->addRow()->addContent(__('These details are used when immediate family members (e.g. parent, spouse) cannot be reached first. Please try to avoid listing immediate family members.'));

                        $row = $form->addRow()->onlyIf($isVisible('emergency1Name'));
                        $row->addLabel('emergency1Name', __('Contact 1 Name'));
                        $row->addTextField('emergency1Name')->maxLength(90);

                        $row = $form->addRow()->onlyIf($isVisible('emergency1Relationship'));
                        $row->addLabel('emergency1Relationship', __('Contact 1 Relationship'));
                        $row->addSelectEmergencyRelationship('emergency1Relationship');

                        $row = $form->addRow()->onlyIf($isVisible('emergency1Number1'));
                        $row->addLabel('emergency1Number1', __('Contact 1 Number 1'));
                        $row->addTextField('emergency1Number1')->maxLength(30);

                        $row = $form->addRow()->onlyIf($isVisible('emergency1Number2'));
                        $row->addLabel('emergency1Number2', __('Contact 1 Number 2'));
                        $row->addTextField('emergency1Number2')->maxLength(30);

                        $row = $form->addRow()->onlyIf($isVisible('emergency2Name'));
                        $row->addLabel('emergency2Name', __('Contact 2 Name'));
                        $row->addTextField('emergency2Name')->maxLength(90);

                        $row = $form->addRow()->onlyIf($isVisible('emergency2Relationship'));
                        $row->addLabel('emergency2Relationship', __('Contact 2 Relationship'));
                        $row->addSelectEmergencyRelationship('emergency2Relationship');

                        $row = $form->addRow()->onlyIf($isVisible('emergency2Number1'));
                        $row->addLabel('emergency2Number1', __('Contact 2 Number 1'));
                        $row->addTextField('emergency2Number1')->maxLength(30);

                        $row = $form->addRow()->onlyIf($isVisible('emergency2Number2'));
                        $row->addLabel('emergency2Number2', __('Contact 2 Number 2'));
                        $row->addTextField('emergency2Number2')->maxLength(30);
                    }

                    // CONTACT INFORMATION
                    $form->addRow()->addHeading('Contact Information', __('Contact Information'));

                    $row = $form->addRow()->onlyIf($isVisible('email'));
                        $row->addLabel('email', __('Email'));
                        $email = $row->addEmail('email');

                    $uniqueEmailAddress = $settingGateway->getSettingByScope('User Admin', 'uniqueEmailAddress');
                    if ($uniqueEmailAddress == 'Y') {
                        $email->uniqueField('./modules/User Admin/user_manage_emailAjax.php', array('gibbonPersonID' => $gibbonPersonID));
                    }

                    $row = $form->addRow()->onlyIf($isVisible('emailAlternate'));
                        $row->addLabel('emailAlternate', __('Alternate Email'));
                        $row->addEmail('emailAlternate');

                    $addressSet = ($values['address1'] != '' or $values['address1District'] != '' or $values['address1Country'] != '' or $values['address2'] != '' or $values['address2District'] != '' or $values['address2Country'] != '')? 'Yes' : '';

                    $row = $form->addRow()->onlyIf($isVisible('address1'));
                        $row->addLabel('showAddresses', __('Enter Personal Address?'));
                        $row->addCheckbox('showAddresses')
                            ->setValue('Yes')
                            ->checked($addressSet)
                            ->setDisabled(isset($requiredFields['address1']) && $requiredFields['address1'] == 'readonly');

                    $form->toggleVisibilityByClass('address')->onCheckbox('showAddresses')->when('Yes');

                    $row = $form->addRow()->onlyIf($isVisible('address1'))->addClass('address');
                    $row->addAlert(__('Address information for an individual only needs to be set under the following conditions:'), 'warning')
                        ->append('<ol>')
                        ->append('<li>'.__('If the user is not in a family.').'</li>')
                        ->append('<li>'.__('If the user\'s family does not have a home address set.').'</li>')
                        ->append('<li>'.__('If the user needs an address in addition to their family\'s home address.').'</li>')
                        ->append('</ol>');

                    $row = $form->addRow()->onlyIf($isVisible('address1'))->addClass('address');
                        $row->addLabel('address1', __('Address 1'))->description(__('Unit, Building, Street'));
                        $row->addTextArea('address1')->maxLength(255)->setRows(2);

                    $row = $form->addRow()->onlyIf($isVisible('address1District'))->addClass('address');
                        $row->addLabel('address1District', __('Address 1 District'))->description(__('County, State, District'));
                        $row->addTextFieldDistrict('address1District');

                    $row = $form->addRow()->onlyIf($isVisible('address1Country'))->addClass('address');
                        $row->addLabel('address1Country', __('Address 1 Country'));
                        $row->addSelectCountry('address1Country');

                    if ($values['address1'] != '' && $isVisible('address1')) {

                            $dataAddress = array(
                                'gibbonPersonID' => $values['gibbonPersonID'],
                                'addressMatch' => '%'.strtolower(preg_replace('/ /', '%', preg_replace('/,/', '%', $values['address1']))).'%',
                                'gibbonFamilyPeople' => implode(',', array_keys($people)),
                            );
                            $sqlAddress = "SELECT gibbonPersonID, title, preferredName, surname, category
                                FROM gibbonPerson JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID)
                                WHERE status='Full' AND address1 LIKE :addressMatch
                                AND FIND_IN_SET(gibbonPersonID, :gibbonFamilyPeople) AND NOT gibbonPersonID=:gibbonPersonID
                                ORDER BY surname, preferredName";
                            $resultAddress = $connection2->prepare($sqlAddress);
                            $resultAddress->execute($dataAddress);

                        if ($resultAddress->rowCount() > 0) {
                            $addressCount = 0;

                            $row = $form->addRow()->addClass('address  matchHighlight');
                            $row->addLabel('matchAddress', __('Matching Address 1'))->description(__('These users have similar Address 1. Do you want to change them too?'));
                            $table = $row->addTable()->setClass('standardWidth');

                            while ($rowAddress = $resultAddress->fetch()) {
                                $adressee = Format::name($rowAddress['title'], $rowAddress['preferredName'], $rowAddress['surname'], $rowAddress['category']).' ('.$rowAddress['category'].')';

                                $row = $table->addRow()->addClass('address');
                                $row->addTextField($addressCount.'-matchAddressLabel')->readOnly()->setValue($adressee)->setClass('fullWidth');
                                $row->addCheckbox($addressCount.'-matchAddress')->setValue($rowAddress['gibbonPersonID']);

                                $addressCount++;
                            }

                            $form->addHiddenValue('matchAddressCount', $addressCount);
                        }
                    }

                    $row = $form->addRow()->onlyIf($isVisible('address2'))->addClass('address');
                        $row->addLabel('address2', __('Address 2'))->description(__('Unit, Building, Street'));
                        $row->addTextArea('address2')->maxLength(255)->setRows(2);

                    $row = $form->addRow()->onlyIf($isVisible('address2District'))->addClass('address');
                        $row->addLabel('address2District', __('Address 2 District'))->description(__('County, State, District'));
                        $row->addTextFieldDistrict('address2District');

                    $row = $form->addRow()->onlyIf($isVisible('address2Country'))->addClass('address');
                        $row->addLabel('address2Country', __('Address 2 Country'));
                        $row->addSelectCountry('address2Country');

                    for ($i = 1; $i < 5; ++$i) {
                        $row = $form->addRow()->onlyIf($isVisible('phone'.$i));
                        $prependRole = $primaryRoleCategory == 'Student' || $primaryRoleCategory == 'Parent' ? __($primaryRoleCategory).' ' : '';
                        $row->addLabel('phone'.$i, $prependRole.__('Phone').' '.$i)->description(__('Type, country code, number.'));
                        $row->addPhoneNumber('phone'.$i);
                    }

                    // BACKGROUND INFORMATION
                    $form->addRow()->addHeading('Background Information', __('Background Information'));

                    $row = $form->addRow()->onlyIf($isVisible('languageFirst'));
                        $row->addLabel('languageFirst', __('First Language'));
                        $row->addSelectLanguage('languageFirst');

                    $row = $form->addRow()->onlyIf($isVisible('languageSecond'));
                        $row->addLabel('languageSecond', __('Second Language'));
                        $row->addSelectLanguage('languageSecond');

                    $row = $form->addRow()->onlyIf($isVisible('languageThird'));
                        $row->addLabel('languageThird', __('Third Language'));
                        $row->addSelectLanguage('languageThird');

                    $row = $form->addRow()->onlyIf($isVisible('countryOfBirth'));
                        $row->addLabel('countryOfBirth', __('Country of Birth'));
                        $row->addSelectCountry('countryOfBirth');

                    $ethnicities = $settingGateway->getSettingByScope('User Admin', 'ethnicity');
                    $row = $form->addRow()->onlyIf($isVisible('ethnicity'));
                        $row->addLabel('ethnicity', __('Ethnicity'));
                        if (!empty($ethnicities)) {
                            $row->addSelect('ethnicity')->fromString($ethnicities)->placeholder();
                        } else {
                            $row->addTextField('ethnicity')->maxLength(255);
                        }

                    $religions = $settingGateway->getSettingByScope('User Admin', 'religions');
                    $row = $form->addRow()->onlyIf($isVisible('religion'));
                        $row->addLabel('religion', __('Religion'));
                        if (!empty($religions)) {
                            $row->addSelect('religion')->fromString($religions)->placeholder();
                        } else {
                            $row->addTextField('religion')->maxLength(30);
                        }

                    $nationalityList = $settingGateway->getSettingByScope('User Admin', 'nationality');
                    $residencyStatusList = $settingGateway->getSettingByScope('User Admin', 'residencyStatus');

                    // PERSONAL DOCUMENTS
                    $params = compact('student', 'staff', 'parent', 'other') + ['dataUpdater' => true];
                    if ($existing) {
                        $documents = $container->get(PersonalDocumentGateway::class)->selectPersonalDocuments('gibbonPersonUpdate', $values['gibbonPersonUpdateID'], $params)->fetchAll();
                    } else {
                        $documents = $container->get(PersonalDocumentGateway::class)->selectPersonalDocuments('gibbonPerson', $gibbonPersonID, $params )->fetchAll();
                    }

                    if (!empty($documents)) {
                        $col = $form->addRow()->addColumn();
                            $col->addLabel('document', __('Personal Documents'));
                            $col->addPersonalDocuments('document', $documents, $container->get(View::class), $settingGateway);
                    }

                    // EMPLOYMENT
                    if ($parent) {
                        $form->addRow()
                            ->onlyIf($anyVisible(['profession', 'employer', 'jobTitle']))
                            ->addHeading('Employment', __('Employment'));

                        $row = $form->addRow()->onlyIf($isVisible('profession'));
                            $row->addLabel('profession', __('Profession'));
                            $row->addTextField('profession')->maxLength(90);

                        $row = $form->addRow()->onlyIf($isVisible('employer'));
                            $row->addLabel('employer', __('Employer'));
                            $row->addTextField('employer')->maxLength(90);

                        $row = $form->addRow()->onlyIf($isVisible('jobTitle'));
                            $row->addLabel('jobTitle', __('Job Title'));
                            $row->addTextField('jobTitle')->maxLength(90);
                    }

                    // MISCELLANEOUS
                    $form->addRow()
                        ->onlyIf($anyVisible(['vehicleRegistration']))
                        ->addHeading('Miscellaneous', __('Miscellaneous'));

                    $row = $form->addRow()->onlyIf($isVisible('vehicleRegistration'));
                        $row->addLabel('vehicleRegistration', __('Vehicle Registration'));
                        $row->addTextField('vehicleRegistration')->maxLength(20);

                    if ($student) {
                        $privacySetting = $settingGateway->getSettingByScope('User Admin', 'privacy');
                        $privacyBlurb = $settingGateway->getSettingByScope('User Admin', 'privacyBlurb');
                        $privacyOptions = $settingGateway->getSettingByScope('User Admin', 'privacyOptions');
                        $privacyOptionVisibility = $settingGateway->getSettingByScope('User Admin', 'privacyOptionVisibility');

                        if ($privacySetting == 'Y' && !empty($privacyOptions)) {

                            if (!empty($privacyBlurb) || $privacyOptionVisibility == 'Y') {
                                $form->addRow()->addSubheading(__('Privacy'))->append($privacyBlurb);
                            }

                            if ($privacyOptionVisibility == 'Y') {
                                $options = array_map(function($item) { return trim($item); }, explode(',', $privacyOptions));
                                $values['privacyOptions'] = $values['privacy'];

                                $row = $form->addRow();
                                    $row->addLabel('privacyOptions[]', __('Privacy Options'));
                                    $row->addCheckbox('privacyOptions[]')->fromArray($options)->loadFromCSV($values)->addClass('md:max-w-lg');
                            }
                        }
                    }

                    // CUSTOM FIELDS
                    $params = compact('student', 'staff', 'parent', 'other');
                    $container->get(CustomFieldHandler::class)->addCustomFieldsToForm($form, 'User', $params + ['dataUpdater' => 1], $values['fields']);

                    $row = $form->addRow();
                        $row->addFooter();
                        $row->addSubmit();

                    $form->loadStateFrom('required', array_filter($requiredFields, function ($item) {
                        return $item == 'required';
                    }));

                    $form->loadStateFrom('readonly', array_filter($requiredFields, function ($item) {
                        return $item == 'readonly';
                    }));

                    $form->loadAllValuesFrom($values);

                    echo $form->getOutput();
                }
            }
        }
    }
}
