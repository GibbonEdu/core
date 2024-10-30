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

//Module includes from User Admin (for custom fields)
include './modules/User Admin/moduleFunctions.php';

$settingGateway = $container->get(SettingGateway::class);

$proceed = false;
$public = false;
if (!$session->has('username')) {
    $public = true;
    //Get public access
    $access = $settingGateway->getSettingByScope('Staff Application Form', 'staffApplicationFormPublicApplications');
    if ($access == 'Y') {
        $proceed = true;
    }
} else {
    if (isActionAccessible($guid, $connection2, '/modules/Staff/applicationForm.php') != false) {
        $proceed = true;
    }
}

//Set gibbonPersonID of the person completing the application
$gibbonPersonID = null;
if ($session->has('gibbonPersonID')) {
    $gibbonPersonID = $session->get('gibbonPersonID');
}

if ($proceed == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Staff Application Form'));

    //Get intro
    $intro = $settingGateway->getSettingByScope('Staff', 'staffApplicationFormIntroduction');
    if ($intro != '') {
        echo '<p>';
        echo $intro;
        echo '</p>';
    }

    if (!$session->has('username')) {
        echo "<div class='warning' style='font-weight: bold'>".sprintf(__('If you already have an account for %1$s %2$s, please log in now to prevent creation of duplicate data about you! Once logged in, you can find the form under People > Staff in the main menu.'), $session->get('organisationNameShort'), $session->get('systemName')).' '.sprintf(__('If you do not have an account for %1$s %2$s, please use the form below.'), $session->get('organisationNameShort'), $session->get('systemName')).'</div>';
    }

    $returnExtra = '';
    if (isset($_GET['id'])) {
        if ($_GET['id'] != '') {
            $returnExtra .= '<br/><br/>'.__('If you need to contact the school in reference to this application, please quote the following number(s):').' <b><u>'.$_GET['id'].'</b></u>.';
        }
    }
    if ($session->get('organisationHRName') != '' and $session->get('organisationHREmail') != '') {
        $returnExtra .= '<br/><br/>'.sprintf(__('Please contact %1$s if you have any questions, comments or complaints.'), "<a href='mailto:".$session->get('organisationHREmail')."'>".$session->get('organisationHRName').'</a>');
    }

    $returns = array();
    $returns['success0'] = __('Your application was successfully submitted. Our Human Resources team will review your application and be in touch in due course.').$returnExtra;
    $returns['warning1'] = __('Your application was submitted, but some errors occured. We recommend you contact our Human Resources team to review your application.').$returnExtra;
    $page->return->addReturns($returns);

    //Check for job openings
    try {
        $data = array('dateOpen' => date('Y-m-d'));
        $sql = "SELECT * FROM gibbonStaffJobOpening WHERE active='Y' AND dateOpen<=:dateOpen ORDER BY jobTitle";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $page->addError(__('Your request failed due to a database error.'));
    }

    if ($result->rowCount() < 1) {
        echo "<div class='warning'>";
        echo __('There are no job openings at this time: please try again later.');
        echo '</div>';
    } else {
        $jobOpenings = $result->fetchAll();

        $customFieldHandler = $container->get(CustomFieldHandler::class);

        $form = Form::create('action', $session->get('absoluteURL').'/modules/'.$session->get('module').'/applicationFormProcess.php');
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->addHiddenValue('address', $session->get('address'));

        $form->addHeaderAction('view', __('View Current Job Openings'))
            ->setURL('/modules/Staff/applicationForm_jobOpenings_view.php')
            ->displayLabel();

        $form->addRow()->addHeading('Job Related Information', __('Job Related Information'));

        $jobOpeningsProcessed = array();
        foreach ($jobOpenings as $jobOpening) {
            $jobOpeningsProcessed[$jobOpening['gibbonStaffJobOpeningID']] = $jobOpening['jobTitle'];
        }
        $row = $form->addRow();
            $row->addLabel('gibbonStaffJobOpeningID[]', __('Job Openings'))->description(__('Please select one or more jobs to apply for.'));
            $row->addCheckbox('gibbonStaffJobOpeningID[]')->fromArray($jobOpeningsProcessed)->required();

        $staffApplicationFormQuestions = $settingGateway->getSettingByScope('Staff', 'staffApplicationFormQuestions');
        if ($staffApplicationFormQuestions != '') {
            $row = $form->addRow();
                $column = $row->addColumn();
                $column->addLabel('questions', __('Application Questions'))->description(__('Please answer the following questions in relation to your application.'));
                $column->addEditor('questions', $guid)->setRows(10)->setValue($staffApplicationFormQuestions)->required();
        }

        $form->addRow()->addHeading('Personal Data', __('Personal Data'));

        if ($gibbonPersonID != null) { //Logged in
            $form->addHiddenValue('gibbonPersonID', $gibbonPersonID);
            $row = $form->addRow();
                $row->addLabel('surname', __('Surname'));
                $row->addTextField('surname')->required()->maxLength(30)->readonly()->setValue($session->get('surname'));

            $row = $form->addRow();
                $row->addLabel('preferredName', __('Preferred Name'));
                $row->addTextField('preferredName')->required()->maxLength(30)->readonly()->setValue($session->get('preferredName'));
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
            $container->get(PersonalDocumentHandler::class)->addPersonalDocumentsToForm($form, null, null, $params);

            $form->addRow()->addHeading('Contacts', __('Contacts'));

            $row = $form->addRow();
                $row->addLabel('email', __('Email'));
                $email = $row->addEmail('email')->required();

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
        $customFieldHandler->addCustomFieldsToForm($form, 'User', $params);

        // REQURIED DOCUMENTS
        $staffApplicationFormRequiredDocuments = $settingGateway->getSettingByScope('Staff', 'staffApplicationFormRequiredDocuments');

        if (!empty($staffApplicationFormRequiredDocuments)) {
            $staffApplicationFormRequiredDocumentsText = $settingGateway->getSettingByScope('Staff', 'staffApplicationFormRequiredDocumentsText');
            $staffApplicationFormRequiredDocumentsCompulsory = $settingGateway->getSettingByScope('Staff', 'staffApplicationFormRequiredDocumentsCompulsory');

            $heading = $form->addRow()->addHeading('Supporting Documents', __('Supporting Documents'));

            if (!empty($staffApplicationFormRequiredDocumentsText)) {
                $heading->append($staffApplicationFormRequiredDocumentsText);

                if ($staffApplicationFormRequiredDocumentsCompulsory == 'Y') {
                    $heading->append(' '.__('All documents must all be included before the application can be submitted.'));
                } else {
                    $heading->append(' '.__('These documents are all required, but can be submitted separately to this form if preferred. Please note, however, that your application will be processed faster if the documents are included here.'));
                }
                $heading->wrap('<p>', '</p>');
            }

            $fileUploader = new Gibbon\FileUploader($pdo, $session);

            $requiredDocumentsList = explode(',', $staffApplicationFormRequiredDocuments);

            for ($i = 0; $i < count($requiredDocumentsList); $i++) {
                $form->addHiddenValue('fileName'.$i, $requiredDocumentsList[$i]);

                $row = $form->addRow();
                    $row->addLabel('file'.$i, $requiredDocumentsList[$i]);
                    $row->addFileUpload('file'.$i)
                        ->accepts($fileUploader->getFileExtensions())
                        ->setRequired($staffApplicationFormRequiredDocumentsCompulsory == 'Y')
                        ->setMaxUpload(false);
            }

            $row = $form->addRow()->addContent(getMaxUpload());
            $form->addHiddenValue('fileCount', count($requiredDocumentsList));
        }

        //REFERENCES
        $applicationFormRefereeLink = $settingGateway->getSettingByScope('Staff', 'applicationFormRefereeLink');
        if ($applicationFormRefereeLink != '') {
            $heading = $form->addRow()->addHeading('References', __('References'));
                $heading->append(__('Your nominated referees will be emailed a confidential form to complete on your behalf.'));

            $row = $form->addRow();
                $row->addLabel('referenceEmail1', __('Referee 1'))->description(__('An email address for a referee at the applicant\'s current school.'));
                $row->addEmail('referenceEmail1')->required();

            $row = $form->addRow();
                $row->addLabel('referenceEmail2', __('Referee 2'))->description(__('An email address for a second referee.'));
                $row->addEmail('referenceEmail2')->required();
        }

        // CUSTOM FIELDS FOR STAFF RECORD
        $params = ['applicationForm' => 1, 'prefix' => 'customStaff'];
        $customFieldHandler->addCustomFieldsToForm($form, 'Staff', $params);

        //AGREEMENT
        $agreement = $settingGateway->getSettingByScope('Staff', 'staffApplicationFormAgreement');
        if (!empty($agreement)) {
            $form->addRow()->addHeading('Agreement', __('Agreement'))->append($agreement)->wrap('<p>', '</p>');

            $row = $form->addRow();
                $row->addLabel('agreement', '<b>'.__('Do you agree to the above?').'</b>');
                $row->addCheckbox('agreement')->description(__('Yes'))->setValue('on')->required();
        }

        // Honey pot field
        $form->addRow()->addClass('hidden')->addTextField('emailAddress');

        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

        echo $form->getOutput();

        //POSTSCRIPT
        $postscript = $settingGateway->getSettingByScope('Staff', 'staffApplicationFormPostscript');
        if ($postscript != '') {
            echo '<h2>';
            echo __('Further Information');
            echo '</h2>';
            echo "<p style='padding-bottom: 15px'>";
            echo $postscript;
            echo '</p>';
        }
    }
}
