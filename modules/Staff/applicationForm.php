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

@session_start();

//Module includes from User Admin (for custom fields)
include './modules/User Admin/moduleFunctions.php';

$proceed = false;
$public = false;
if (isset($_SESSION[$guid]['username']) == false) {
    $public = true;
    //Get public access
    $access = getSettingByScope($connection2, 'Staff Application Form', 'staffApplicationFormPublicApplications');
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
if (isset($_SESSION[$guid]['gibbonPersonID'])) {
    $gibbonPersonID = $_SESSION[$guid]['gibbonPersonID'];
}

if ($proceed == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    if (isset($_SESSION[$guid]['username'])) {
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Staff Application Form').'</div>';
    } else {
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > </div><div class='trailEnd'>".__($guid, 'Staff Application Form').'</div>';
    }
    echo '</div>';

    //Get intro
    $intro = getSettingByScope($connection2, 'Staff', 'staffApplicationFormIntroduction');
    if ($intro != '') {
        echo '<p>';
        echo $intro;
        echo '</p>';
    }

    if (isset($_SESSION[$guid]['username']) == false) {
        echo "<div class='warning' style='font-weight: bold'>".sprintf(__($guid, 'If you already have an account for %1$s %2$s, please log in now to prevent creation of duplicate data about you! Once logged in, you can find the form under People > Staff in the main menu.'), $_SESSION[$guid]['organisationNameShort'], $_SESSION[$guid]['systemName']).' '.sprintf(__($guid, 'If you do not have an account for %1$s %2$s, please use the form below.'), $_SESSION[$guid]['organisationNameShort'], $_SESSION[$guid]['systemName']).'</div>';
    }

    $returnExtra = '';
    if (isset($_GET['id'])) {
        if ($_GET['id'] != '') {
            $returnExtra .= '<br/><br/>'.__($guid, 'If you need to contact the school in reference to this application, please quote the following number(s):').' <b><u>'.$_GET['id'].'</b></u>.';
        }
    }
    if ($_SESSION[$guid]['organisationHRName'] != '' and $_SESSION[$guid]['organisationHREmail'] != '') {
        $returnExtra .= '<br/><br/>'.sprintf(__($guid, 'Please contact %1$s if you have any questions, comments or complaints.'), "<a href='mailto:".$_SESSION[$guid]['organisationHREmail']."'>".$_SESSION[$guid]['organisationHRName'].'</a>');
    }

    $returns = array();
    $returns['success0'] = __($guid, 'Your application was successfully submitted. Our Human Resources team will review your application and be in touch in due course.').$returnExtra;
    $returns['warning1'] = __($guid, 'Your application was submitted, but some errors occured. We recommend you contact our Human Resources team to review your application.').$returnExtra;
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, $returns);
    }

    //Check for job openings
    try {
        $data = array('dateOpen' => date('Y-m-d'));
        $sql = "SELECT * FROM gibbonStaffJobOpening WHERE active='Y' AND dateOpen<=:dateOpen ORDER BY jobTitle";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>";
        echo __($guid, 'Your request failed due to a database error.');
        echo '</div>';
    }

    if ($result->rowCount() < 1) {
        echo "<div class='warning'>";
        echo __($guid, 'There are no job openings at this time: please try again later.');
        echo '</div>';
    } else {
        $jobOpenings = $result->fetchAll();

        echo "<div class='linkTop'>";
        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/applicationForm_jobOpenings_view.php'>".__($guid, 'View Current Job Openings')."<img style='margin-left: 5px' title='".__($guid, 'View Current Job Openings')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a>";
        echo '</div>';

        $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/applicationFormProcess.php');

        $form->setFactory(DatabaseFormFactory::create($pdo));
        $form->setClass('smallIntBorder fullWidth');

        $form->addHiddenValue('address', $_SESSION[$guid]['address']);

        $form->addRow()->addHeading(__('Job Related Information'));

        $jobOpeningsProcessed = array();
        foreach ($jobOpenings AS $jobOpening) {
            $jobOpeningsProcessed[$jobOpening['gibbonStaffJobOpeningID']] = $jobOpening['jobTitle'];
        }
        $row = $form->addRow();
            $row->addLabel('gibbonStaffJobOpeningID[]', __('Job Openings'))->description(__('Please select one or more jobs to apply for.'));
            $row->addCheckbox('gibbonStaffJobOpeningID[]')->fromArray($jobOpeningsProcessed)->isRequired();

        $staffApplicationFormQuestions = getSettingByScope($connection2, 'Staff', 'staffApplicationFormQuestions');
        if ($staffApplicationFormQuestions != '') {
            $row = $form->addRow();
                $column = $row->addColumn();
                $column->addLabel('questions', __('Application Questions'))->description(__('Please answer the following questions in relation to your application.'));
                $column->addEditor('questions', $guid)->setRows(10)->setValue($staffApplicationFormQuestions)->isRequired();
        }

        $form->addRow()->addHeading(__('Personal Data'));

        if ($gibbonPersonID != null) { //Logged in
            $form->addHiddenValue('gibbonPersonID', $gibbonPersonID);
            $row = $form->addRow();
                $row->addLabel('surname', __('Surname'));
                $row->addTextField('surname')->isRequired()->maxLength(30)->readonly()->setValue($_SESSION[$guid]['surname']);

            $row = $form->addRow();
                $row->addLabel('preferredName', __('Preferred Name'));
                $row->addTextField('preferredName')->isRequired()->maxLength(30)->readonly()->setValue($_SESSION[$guid]['preferredName']);

        }
        else { //Not logged in
            $row = $form->addRow();
                $row->addLabel('surname', __('Surname'))->description(__('Family name as shown in ID documents.'));
                $row->addTextField('surname')->isRequired()->maxLength(30);

            $row = $form->addRow();
                $row->addLabel('firstName', __('First Name'))->description(__('First name as shown in ID documents.'));
                $row->addTextField('firstName')->isRequired()->maxLength(30);

            $row = $form->addRow();
                $row->addLabel('preferredName', __('Preferred Name'))->description(__('Most common name, alias, nickname, etc.'));
                $row->addTextField('preferredName')->isRequired()->maxLength(30);

            $row = $form->addRow();
                $row->addLabel('officialName', __('Official Name'))->description(__('Full name as shown in ID documents.'));
                $row->addTextField('officialName')->isRequired()->maxLength(150)->setTitle('Please enter full name as shown in ID documents');

            $row = $form->addRow();
                $row->addLabel('nameInCharacters', __('Name In Characters'))->description(__('Chinese or other character-based name.'));
                $row->addTextField('nameInCharacters')->maxLength(20);

            $row = $form->addRow();
                $row->addLabel('gender', __('Gender'));
                $row->addSelectGender('gender')->isRequired();

            $row = $form->addRow();
                $row->addLabel('dob', __('Date of Birth'))->description($_SESSION[$guid]['i18n']['dateFormat'])->prepend(__('Format:'));
                $row->addDate('dob')->isRequired();

            $form->addRow()->addHeading(__('Background Data'));

            $row = $form->addRow();
                $row->addLabel('languageFirst', __('First Language'))->description(__('Student\'s native/first/mother language.'));
                $row->addSelectLanguage('languageFirst')->isRequired();

            $row = $form->addRow();
                $row->addLabel('languageSecond', __('Second Language'));
                $row->addSelectLanguage('languageSecond')->placeholder('');

            $row = $form->addRow();
                $row->addLabel('languageThird', __('Third Language'));
                $row->addSelectLanguage('languageThird')->placeholder('');

            $row = $form->addRow();
                $row->addLabel('countryOfBirth', __('Country of Birth'));
                $row->addSelectCountry('countryOfBirth')->isRequired();

            $row = $form->addRow();
                $row->addLabel('citizenship1', __('Citizenship'));
                $nationalityList = getSettingByScope($connection2, 'User Admin', 'nationality');
                if (!empty($nationalityList)) {
                    $row->addSelect('citizenship1')->isRequired()->fromString($nationalityList)->placeholder(__('Please select...'));
                } else {
                    $row->addSelectCountry('citizenship1')->isRequired();
                }

            $countryName = (isset($_SESSION[$guid]['country']))? $_SESSION[$guid]['country'].' ' : '';
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
                $row->addEmail('email')->maxLength(50)->isRequired();

            $row = $form->addRow();
                $row->addLabel('phone1', __('Phone'))->description(__('Type, country code, number.'));
                $row->addPhoneNumber('phone1')->isRequired();

            $row = $form->addRow();
                $row->addLabel('homeAddress', __('Home Address'))->description(__('Unit, Building, Street'));
                $row->addTextField('homeAddress')->isRequired()->maxLength(255);

            $row = $form->addRow();
                $row->addLabel('homeAddressDistrict', __('Home Address (District)'))->description(__('County, State, District'));
                $row->addTextFieldDistrict('homeAddressDistrict')->isRequired();

            $row = $form->addRow();
                $row->addLabel('homeAddressCountry', __('Home Address (Country)'));
                $row->addSelectCountry('homeAddressCountry')->isRequired();
        }

        // CUSTOM FIELDS FOR STAFF
        $resultFields = getCustomFields($connection2, $guid, false, true, false, false, true, null);
        if ($resultFields->rowCount() > 0) {
            $form->addRow()->addHeading(__('Other Information'));

            while ($rowFields = $resultFields->fetch()) {
                $name = 'custom'.$rowFields['gibbonPersonFieldID'];
                $row = $form->addRow();
                    $row->addLabel($name, $rowFields['name']);
                    $row->addCustomField($name, $rowFields);
            }
        }

        // REQURIED DOCUMENTS
        $staffApplicationFormRequiredDocuments = getSettingByScope($connection2, 'Staff', 'staffApplicationFormRequiredDocuments');

        if (!empty($staffApplicationFormRequiredDocuments)) {
            $staffApplicationFormRequiredDocumentsText = getSettingByScope($connection2, 'Staff', 'staffApplicationFormRequiredDocumentsText');
            $staffApplicationFormRequiredDocumentsCompulsory = getSettingByScope($connection2, 'Staff', 'staffApplicationFormRequiredDocumentsCompulsory');

            $heading = $form->addRow()->addHeading(__('Supporting Documents'));

            if (!empty($staffApplicationFormRequiredDocumentsText)) {
                $heading->append($staffApplicationFormRequiredDocumentsText);

                if ($staffApplicationFormRequiredDocumentsCompulsory == 'Y') {
                    $heading->append(' '.__('All documents must all be included before the application can be submitted.'));
                } else {
                    $heading->append(' '.__('These documents are all required, but can be submitted separately to this form if preferred. Please note, however, that your application will be processed faster if the documents are included here.'));
                }
                $heading->wrap('<p>', '</p>');
            }

            $fileUploader = new Gibbon\FileUploader($pdo, $gibbon->session);

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

            $row = $form->addRow()->addContent(getMaxUpload($guid));
            $form->addHiddenValue('fileCount', count($requiredDocumentsList));
        }

        //REFERENCES
        $applicationFormRefereeLink = getSettingByScope($connection2, 'Staff', 'applicationFormRefereeLink');
        if ($applicationFormRefereeLink != '') {
            $heading = $form->addRow()->addHeading(__('References'));
                $heading->append(__('Your nominated referees will be emailed a confidential form to complete on your behalf.'));

            $row = $form->addRow();
                $row->addLabel('referenceEmail1', __('Referee 1'))->description(__('An email address for a referee at the applicant\'s current school.'));
                $row->addEmail('referenceEmail1')->maxLength(50)->isRequired();

            $row = $form->addRow();
                $row->addLabel('referenceEmail2', __('Referee 2'))->description(__('An email address for a second referee.'));
                $row->addEmail('referenceEmail2')->maxLength(50)->isRequired();

        }

        //AGREEMENT
        $agreement = getSettingByScope($connection2, 'Staff', 'staffApplicationFormAgreement');
        if (!empty($agreement)) {
            $form->addRow()->addHeading(__('Agreement'))->append($agreement)->wrap('<p>','</p>');

            $row = $form->addRow();
                $row->addLabel('agreement', '<b>'.__('Do you agree to the above?').'</b>');
                $row->addCheckbox('agreement')->description(__('Yes'))->setValue('on')->isRequired();
        }

        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

        echo $form->getOutput();

        //POSTSCRIPT
        $postscript = getSettingByScope($connection2, 'Staff', 'staffApplicationFormPostscript');
        if ($postscript != '') {
            echo '<h2>';
            echo __($guid, 'Further Information');
            echo '</h2>';
            echo "<p style='padding-bottom: 15px'>";
            echo $postscript;
            echo '</p>';
        }
    }
}
?>
