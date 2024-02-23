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
use Gibbon\Forms\Form;
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Forms\PersonalDocumentHandler;
use Gibbon\Domain\User\PersonalDocumentGateway;

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

    //Check if gibbonStaffApplicationFormID specified
    $gibbonStaffApplicationFormID = $_GET['gibbonStaffApplicationFormID'] ?? '';
    $search = $_GET['search'] ?? '';
    if ($gibbonStaffApplicationFormID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {

        $data = array('gibbonStaffApplicationFormID' => $gibbonStaffApplicationFormID);
        $sql = 'SELECT gibbonStaffApplicationForm.*, gibbonStaffJobOpening.jobTitle, gibbonStaffJobOpening.type FROM gibbonStaffApplicationForm JOIN gibbonStaffJobOpening ON (gibbonStaffApplicationForm.gibbonStaffJobOpeningID=gibbonStaffJobOpening.gibbonStaffJobOpeningID) LEFT JOIN gibbonPerson ON (gibbonStaffApplicationForm.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonStaffApplicationFormID=:gibbonStaffApplicationFormID';
        $result = $connection2->prepare($sql);
        $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The specified record does not exist.'));
        } else {
            //Let's go!
            $values = $result->fetch();
            $proceed = true;

            $customFieldHandler = $container->get(CustomFieldHandler::class);

            $form = Form::create('action', $session->get('absoluteURL').'/modules/'.$session->get('module')."/applicationForm_manage_editProcess.php?search=$search");
            $form->setFactory(DatabaseFormFactory::create($pdo));

            $form->addHiddenValue('address', $session->get('address'));

            if ($search != '') {
                $form->addHeaderAction('back', __('Back to Search Results'))
                ->setURL('/modules/Staff/applicationForm_manage.php')
                ->addParam('search', $search)
                ->displayLabel()
                ->append(' | ');
            }

            $form->addHeaderAction('print', __('Print'))
                ->setURL('/report.php')
                ->addParam('q', '/modules/Staff/applicationForm_manage_edit_print.php')
                ->addParam('gibbonStaffApplicationFormID', $gibbonStaffApplicationFormID)
                ->setTarget('_blank')
                ->directLink()
                ->displayLabel();

            $form->addRow()->addHeading('For Office Use', __('For Office Use'));

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
            $settingGateway = $container->get(SettingGateway::class);
            $milestonesList = $settingGateway->getSettingByScope('Staff', 'staffApplicationFormMilestones');
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
                $row->addLabel('dateStart', __('Start Date'))->description(__('Intended first day at school.'));
                $row->addDate('dateStart');

            $row = $form->addRow();
                $column = $row->addColumn();
                $column->addLabel('notes', __('Notes'));
                $column->addTextArea('notes')->setRows(5)->setClass('fullWidth');

            $form->addRow()->addHeading('Job Related Information', __('Job Related Information'));

            $form->addHiddenValue('type', $values['type']);

            $row = $form->addRow();
                $row->addLabel('typeRole', __('Job Type'));
                $row->addTextField('typeRole')->readOnly()->required()->setValue(__($values['type']));

            $form->addHiddenValue('gibbonStaffJobOpeningID', $values['gibbonStaffJobOpeningID']);
            $row = $form->addRow();
                $row->addLabel('jobTitle', __('Job Opening'));
                $row->addTextField('jobTitle')->readOnly()->required();

            $staffApplicationFormQuestions = $settingGateway->getSettingByScope('Staff', 'staffApplicationFormQuestions');
            if ($staffApplicationFormQuestions != '') {
                $row = $form->addRow();
                    $column = $row->addColumn();
                    $column->addLabel('applicationQuestions', __('Application Questions'));
                    $column->addContent($values['questions']);
            }

            $form->addRow()->addHeading('Personal Data', __('Personal Data'));

            if ($values['gibbonPersonID'] != null) { //Logged in
                $form->addHiddenValue('gibbonPersonID', $values['gibbonPersonID']);
                $row = $form->addRow();
                    $row->addLabel('surname', __('Surname'));
                    $row->addTextField('surname')->required()->maxLength(30)->readonly()->setValue($session->get('surname'));

                $row = $form->addRow();
                    $row->addLabel('preferredName', __('Preferred Name'));
                    $row->addTextField('preferredName')->required()->maxLength(30)->readonly()->setValue($session->get('preferredName'));

                // PERSONAL DOCUMENTS
                $params = ['staff' => true, 'notEmpty' => true];
                $documents = $container->get(PersonalDocumentGateway::class)->selectPersonalDocuments('gibbonPerson', $values['gibbonPersonID'], $params)->fetchAll();

                if (!empty($documents)) {
                    $col = $form->addRow()->addColumn();
                    $col->addLabel('personalDocuments', __('Personal Documents'));
                    $col->addContent($page->fetchFromTemplate('ui/personalDocuments.twig.html', ['documents' => $documents, 'noTitle' => true]));
                }
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
                    $row->addLabel('dob', __('Date of Birth'));
                    $row->addDate('dob')->required();

                $form->addRow()->addHeading('Background Data', __('Background Data'));

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

                $nationalityList = $settingGateway->getSettingByScope('User Admin', 'nationality');
                $residencyStatusList = $settingGateway->getSettingByScope('User Admin', 'residencyStatus');

                // PERSONAL DOCUMENTS
                $params = ['staff' => true, 'applicationForm' => true];
                $container->get(PersonalDocumentHandler::class)->addPersonalDocumentsToForm($form, 'gibbonStaffApplicationForm', $gibbonStaffApplicationFormID, $params);

                $form->addRow()->addHeading('Contacts', __('Contacts'));

                $row = $form->addRow();
                    $row->addLabel('email', __('Email'));
                    $email = $row->addEmail('email')->required();

                    $uniqueEmailAddress = $settingGateway->getSettingByScope('User Admin', 'uniqueEmailAddress');
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

            // CUSTOM FIELDS FOR USER: STAFF
            $params = ['staff' => 1, 'applicationForm' => 1, 'headingLevel' => 'h4'];
            $customFieldHandler->addCustomFieldsToForm($form, 'User', $params, $values['fields']);

            // REQURIED DOCUMENTS
            $staffApplicationFormRequiredDocuments = $settingGateway->getSettingByScope('Staff', 'staffApplicationFormRequiredDocuments');

            if (!empty($staffApplicationFormRequiredDocuments)) {
                $staffApplicationFormRequiredDocumentsText = $settingGateway->getSettingByScope('Staff', 'staffApplicationFormRequiredDocumentsText');
                $staffApplicationFormRequiredDocumentsCompulsory = $settingGateway->getSettingByScope('Staff', 'staffApplicationFormRequiredDocumentsCompulsory');

                $heading = $form->addRow()->addHeading('Supporting Documents', __('Supporting Documents'));

                $fileUploader = new Gibbon\FileUploader($pdo, $session);

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
                            ->setURL($session->get('absoluteURL').'/'.$rowFile['path'])
                            ->setTarget('_blank');
                    }
                }

                $row = $form->addRow()->addContent(getMaxUpload());
                $form->addHiddenValue('fileCount', count($requiredDocumentsList));
            }

            //REFERENCES
            $applicationFormRefereeLink = $settingGateway->getSettingByScope('Staff', 'applicationFormRefereeLink');
            if ($applicationFormRefereeLink != '') {
                $heading = $form->addRow()->addHeading('References', __('References'));

                $row = $form->addRow();
                    $row->addLabel('referenceEmail1', __('Referee 1'))->description(__('An email address for a referee at the applicant\'s current school.'));
                    $row->addEmail('referenceEmail1')->required();

                $row = $form->addRow();
                    $row->addLabel('referenceEmail2', __('Referee 2'))->description(__('An email address for a second referee.'));
                    $row->addEmail('referenceEmail2')->required();

            }

            // CUSTOM FIELDS FOR STAFF RECORD
            $params = ['applicationForm' => 1, 'prefix' => 'customStaff'];
            $customFieldHandler->addCustomFieldsToForm($form, 'Staff', $params, $values['staffFields']);

            $form->loadAllValuesFrom($values);

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();

        }
    }
}
