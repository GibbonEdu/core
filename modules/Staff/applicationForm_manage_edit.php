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
use Gibbon\Forms\DatabaseFormFactory;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

//Module includes from User Admin (for custom fields)
include './modules/User Admin/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Staff/applicationForm_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('Manage Applications'), 'applicationForm_manage.php')
        ->add(__('Edit Form'));

    //Check if school year specified
    $gibbonStaffApplicationFormID = $_GET['gibbonStaffApplicationFormID'];
    $search = $_GET['search'];
    if ($gibbonStaffApplicationFormID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        
        $data = array('gibbonStaffApplicationFormID' => $gibbonStaffApplicationFormID);
        $sql = 'SELECT gibbonStaffApplicationForm.*, gibbonStaffJobOpening.jobTitle, gibbonStaffJobOpening.type FROM gibbonStaffApplicationForm JOIN gibbonStaffJobOpening ON (gibbonStaffApplicationForm.gibbonStaffJobOpeningID=gibbonStaffJobOpening.gibbonStaffJobOpeningID) LEFT JOIN gibbonPerson ON (gibbonStaffApplicationForm.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonStaffApplicationFormID=:gibbonStaffApplicationFormID';
        $result = $connection2->prepare($sql);
        $result->execute($data);

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __('The specified record does not exist.');
            echo '</div>';
        } else {
            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
            }

            //Let's go!
            $values = $result->fetch();
            $proceed = true;

            echo "<div class='linkTop'>";
            if ($search != '') {
                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Staff/applicationForm_manage.php&search=$search'>".__('Back to Search Results').'</a> | ';
            }
            echo "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/report.php?q=/modules/'.$_SESSION[$guid]['module']."/applicationForm_manage_edit_print.php&gibbonStaffApplicationFormID=$gibbonStaffApplicationFormID'>".__('Print')."<img style='margin-left: 5px' title='".__('Print')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/print.png'/></a>";
            echo '</div>';

            $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/applicationForm_manage_editProcess.php?search=$search");
            $form->setFactory(DatabaseFormFactory::create($pdo));

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);

            $form->addRow()->addHeading(__('For Office Use'));

            $row = $form->addRow();
                $row->addLabel('gibbonStaffApplicationFormID', __('Application ID'));
                $row->addTextField('gibbonStaffApplicationFormID')->readOnly()->required();

            $row = $form->addRow();
                $row->addLabel('priority', __('Priority'))->description(__('Higher priority applicants appear first in list of applications.'));
                $row->addSelect('priority')->fromArray(range(-9, 9))->required();

            // STATUS
            if ($values['status'] != 'Accepted') {
                $statuses = array(
                    'Pending'      => __('Pending'),
                    'Rejected'     => __('Rejected'),
                    'Withdrawn'    => __('Withdrawn'),
                );
                $row = $form->addRow();
                        $row->addLabel('status', __('Status'))->description(__('Manually set status. "Approved" not permitted.'));
                        $row->addSelect('status')->required()->fromArray($statuses)->selected($values['status']);
            } else {
                $row = $form->addRow();
                    $row->addLabel('status', __('Status'))->description(__('Manually set status. "Approved" not permitted.'));
                    $row->addTextField('status')->required()->readOnly()->setValue($values['status']);
            }

            // MILESTONES
            $milestonesList = getSettingByScope($connection2, 'Staff', 'staffApplicationFormMilestones');
            if (!empty($milestonesList)) {
                $row = $form->addRow();
                    $row->addLabel('milestones', __('Milestones'));
                    $column = $row->addColumn()->setClass('flex-col items-end');

                $milestonesChecked = array_map('trim', explode(',', $values['milestones']));
                $milestonesArray = array_map('trim', explode(',', $milestonesList));

                foreach ($milestonesArray as $milestone) {
                    $name = 'milestone_'.preg_replace('/\s+/', '', $milestone);
                    $checked = in_array($milestone, $milestonesChecked);

                    $column->addCheckbox($name)->setValue('on')->description($milestone)->checked($checked);
                }
            }

            $row = $form->addRow();
                $row->addLabel('dateStart', __('Start Date'))->description(__('Intended first day at school.'))->append(__('Format:').' '.$_SESSION[$guid]['i18n']['dateFormat']);
                $row->addDate('dateStart');

            $row = $form->addRow();
                $column = $row->addColumn();
                $column->addLabel('notes', __('Notes'));
                $column->addTextArea('notes')->setRows(5)->setClass('fullWidth');

            $form->addRow()->addHeading(__('Job Related Information'));
            
            $form->addHiddenValue('type', $values['type']);

            $row = $form->addRow();
                $row->addLabel('typeRole', __('Job Type'));
                $row->addTextField('typeRole')->readOnly()->required()->setValue(__($values['type']));

            $form->addHiddenValue('gibbonStaffJobOpeningID', $values['gibbonStaffJobOpeningID']);
            $row = $form->addRow();
                $row->addLabel('jobTitle', __('Job Opening'));
                $row->addTextField('jobTitle')->readOnly()->required();

            $staffApplicationFormQuestions = getSettingByScope($connection2, 'Staff', 'staffApplicationFormQuestions');
            if ($staffApplicationFormQuestions != '') {
                $row = $form->addRow();
                    $column = $row->addColumn();
                    $column->addLabel('applicationQuestions', __('Application Questions'));
                    $column->addContent($values['questions']);
            }

            $form->addRow()->addHeading(__('Personal Data'));

            if ($values['gibbonPersonID'] != null) { //Logged in
                $form->addHiddenValue('gibbonPersonID', $values['gibbonPersonID']);
                $row = $form->addRow();
                    $row->addLabel('surname', __('Surname'));
                    $row->addTextField('surname')->required()->maxLength(30)->readonly()->setValue($_SESSION[$guid]['surname']);

                $row = $form->addRow();
                    $row->addLabel('preferredName', __('Preferred Name'));
                    $row->addTextField('preferredName')->required()->maxLength(30)->readonly()->setValue($_SESSION[$guid]['preferredName']);
            }
            else { //Not logged in
                $row = $form->addRow();
                    $row->addLabel('surname', __('Surname'))->description(__('Family name as shown in ID documents.'));
                    $row->addTextField('surname')->required()->maxLength(30);

                $row = $form->addRow();
                    $row->addLabel('firstName', __('First Name'))->description(__('First name as shown in ID documents.'));
                    $row->addTextField('firstName')->required()->maxLength(30);

                $row = $form->addRow();
                    $row->addLabel('preferredName', __('Preferred Name'))->description(__('Most common name, alias, nickname, etc.'));
                    $row->addTextField('preferredName')->required()->maxLength(30);

                $row = $form->addRow();
                    $row->addLabel('officialName', __('Official Name'))->description(__('Full name as shown in ID documents.'));
                    $row->addTextField('officialName')->required()->maxLength(150)->setTitle(__('Please enter full name as shown in ID documents'));

                $row = $form->addRow();
                    $row->addLabel('nameInCharacters', __('Name In Characters'))->description(__('Chinese or other character-based name.'));
                    $row->addTextField('nameInCharacters')->maxLength(20);

                $row = $form->addRow();
                    $row->addLabel('gender', __('Gender'));
                    $row->addSelectGender('gender')->required();

                $row = $form->addRow();
                    $row->addLabel('dob', __('Date of Birth'))->description($_SESSION[$guid]['i18n']['dateFormat'])->prepend(__('Format:'));
                    $row->addDate('dob')->required();

                $form->addRow()->addHeading(__('Background Data'));

                $row = $form->addRow();
                    $row->addLabel('languageFirst', __('First Language'))->description(__('Student\'s native/first/mother language.'));
                    $row->addSelectLanguage('languageFirst')->required();

                $row = $form->addRow();
                    $row->addLabel('languageSecond', __('Second Language'));
                    $row->addSelectLanguage('languageSecond')->placeholder('');

                $row = $form->addRow();
                    $row->addLabel('languageThird', __('Third Language'));
                    $row->addSelectLanguage('languageThird')->placeholder('');

                $row = $form->addRow();
                    $row->addLabel('countryOfBirth', __('Country of Birth'));
                    $row->addSelectCountry('countryOfBirth')->required();

                $row = $form->addRow();
                    $row->addLabel('citizenship1', __('Citizenship'));
                    $nationalityList = getSettingByScope($connection2, 'User Admin', 'nationality');
                    if (!empty($nationalityList)) {
                        $row->addSelect('citizenship1')->required()->fromString($nationalityList)->placeholder(__('Please select...'));
                    } else {
                        $row->addSelectCountry('citizenship1')->required();
                    }

                $countryName = (isset($_SESSION[$guid]['country']))? __($_SESSION[$guid]['country']).' ' : '';
                $row = $form->addRow();
                    $row->addLabel('citizenship1Passport', __('Citizenship Passport Number'))->description('');
                    $row->addTextField('citizenship1Passport')->maxLength(30);

                $row = $form->addRow();
                    $row->addLabel('nationalIDCardNumber', $countryName.__('National ID Card Number'));
                    $row->addTextField('nationalIDCardNumber')->maxLength(30);

                $row = $form->addRow();
                    $row->addLabel('residencyStatus', $countryName.__('Residency/Visa Type'));
                    $residencyStatusList = getSettingByScope($connection2, 'User Admin', 'residencyStatus');
                    if (!empty($residencyStatusList)) {
                        $row->addSelect('residencyStatus')->fromString($residencyStatusList)->placeholder();
                    } else {
                        $row->addTextField('residencyStatus')->maxLength(30);
                    }

                $row = $form->addRow();
                    $row->addLabel('visaExpiryDate', $countryName.__('Visa Expiry Date'))->description($_SESSION[$guid]['i18n']['dateFormat'])->prepend(__('Format:'))->append(__('If relevant.'));
                    $row->addDate('visaExpiryDate');

                $form->addRow()->addHeading(__('Contacts'));

                $row = $form->addRow();
                    $row->addLabel('email', __('Email'));
                    $email = $row->addEmail('email')->required();

                    $uniqueEmailAddress = getSettingByScope($connection2, 'User Admin', 'uniqueEmailAddress');
                    if ($uniqueEmailAddress == 'Y') {
                        $email->uniqueField('./modules/User Admin/user_manage_emailAjax.php');
                    }

                $row = $form->addRow();
                    $row->addLabel('phone1', __('Phone'))->description(__('Type, country code, number.'));
                    $row->addPhoneNumber('phone1')->required();

                $row = $form->addRow();
                    $row->addLabel('homeAddress', __('Home Address'))->description(__('Unit, Building, Street'));
                    $row->addTextField('homeAddress')->required()->maxLength(255);

                $row = $form->addRow();
                    $row->addLabel('homeAddressDistrict', __('Home Address (District)'))->description(__('County, State, District'));
                    $row->addTextFieldDistrict('homeAddressDistrict')->required();

                $row = $form->addRow();
                    $row->addLabel('homeAddressCountry', __('Home Address (Country)'));
                    $row->addSelectCountry('homeAddressCountry')->required();
            }

            // CUSTOM FIELDS FOR STAFF
            $existingFields = isset($values['fields'])? json_decode($values['fields'], true) : null;
            $resultFields = getCustomFields($connection2, $guid, false, true, false, false, true, null);
            if ($resultFields->rowCount() > 0) {
                $form->addRow()->addHeading(__('Other Information'));

                while ($rowFields = $resultFields->fetch()) {
                    $name = 'custom'.$rowFields['gibbonPersonFieldID'];
                    $value = (isset($existingFields[$rowFields['gibbonPersonFieldID']]))? $existingFields[$rowFields['gibbonPersonFieldID']] : '';
                    $row = $form->addRow();
                        $row->addLabel($name, $rowFields['name'])->description($rowFields['description']);
                        $row->addCustomField($name, $rowFields)->setValue($value);
                }
            }

            // REQURIED DOCUMENTS
            $staffApplicationFormRequiredDocuments = getSettingByScope($connection2, 'Staff', 'staffApplicationFormRequiredDocuments');

            if (!empty($staffApplicationFormRequiredDocuments)) {
                $staffApplicationFormRequiredDocumentsText = getSettingByScope($connection2, 'Staff', 'staffApplicationFormRequiredDocumentsText');
                $staffApplicationFormRequiredDocumentsCompulsory = getSettingByScope($connection2, 'Staff', 'staffApplicationFormRequiredDocumentsCompulsory');

                $heading = $form->addRow()->addHeading(__('Supporting Documents'));

                $fileUploader = new Gibbon\FileUploader($pdo, $gibbon->session);

                $requiredDocumentsList = explode(',', $staffApplicationFormRequiredDocuments);

                for ($i = 0; $i < count($requiredDocumentsList); $i++) {
                    $form->addHiddenValue('fileName'.$i, $requiredDocumentsList[$i]);

                    $row = $form->addRow();
                        $row->addLabel('file'.$i, $requiredDocumentsList[$i]);

                    
                        $dataFile = array('gibbonStaffApplicationFormID' => $gibbonStaffApplicationFormID, 'name' => $requiredDocumentsList[$i]);
                        $sqlFile = 'SELECT * FROM gibbonStaffApplicationFormFile WHERE gibbonStaffApplicationFormID=:gibbonStaffApplicationFormID AND name=:name ORDER BY name';
                        $resultFile = $connection2->prepare($sqlFile);
                        $resultFile->execute($dataFile);
                    if ($resultFile->rowCount() == 0) {
                            $row->addFileUpload('file'.$i)
                                ->accepts($fileUploader->getFileExtensions())
                                ->setMaxUpload(false);
                    }
                    else {
                        $rowFile = $resultFile->fetch();
                        $row->addWebLink(__('Download'))
                            ->addClass('right')
                            ->setURL($_SESSION[$guid]['absoluteURL'].'/'.$rowFile['path'])
                            ->setTarget('_blank');
                    }
                }

                $row = $form->addRow()->addContent(getMaxUpload($guid));
                $form->addHiddenValue('fileCount', count($requiredDocumentsList));
            }

            //REFERENCES
            $applicationFormRefereeLink = getSettingByScope($connection2, 'Staff', 'applicationFormRefereeLink');
            if ($applicationFormRefereeLink != '') {
                $heading = $form->addRow()->addHeading(__('References'));

                $row = $form->addRow();
                    $row->addLabel('referenceEmail1', __('Referee 1'))->description(__('An email address for a referee at the applicant\'s current school.'));
                    $row->addEmail('referenceEmail1')->required();

                $row = $form->addRow();
                    $row->addLabel('referenceEmail2', __('Referee 2'))->description(__('An email address for a second referee.'));
                    $row->addEmail('referenceEmail2')->required();

            }

            $form->loadAllValuesFrom($values);

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();

        }
    }
}
