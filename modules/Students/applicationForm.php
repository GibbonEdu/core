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
use Gibbon\Contracts\Services\Payment;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\PersonalDocumentHandler;

//Module includes from User Admin (for custom fields)
include './modules/User Admin/moduleFunctions.php';

$proceed = false;
$public = false;

$settingGateway = $container->get(SettingGateway::class);

if (!$session->has('username')) {
    $public = true;

    //Get public access
    $publicApplications = $settingGateway->getSettingByScope('Application Form', 'publicApplications');
    if ($publicApplications == 'Y') {
        $proceed = true;
    }
} else {
    if (isActionAccessible($guid, $connection2, '/modules/Students/applicationForm.php') != false) {
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
    $page->breadcrumbs->add(__('Application Form'));

    if (!$session->has('username')) {
        echo "<div class='warning' style='font-weight: bold'>".sprintf(__('If you already have an account for %1$s %2$s, please log in now to prevent creation of duplicate data about you! Once logged in, you can find the form under People > Students in the main menu.'), $session->get('organisationNameShort'), $session->get('systemName')).' '.sprintf(__('If you do not have an account for %1$s %2$s, please use the form below.'), $session->get('organisationNameShort'), $session->get('systemName')).'</div>';
    } else {
        // Application Manager
        if (isActionAccessible($guid, $connection2, '/modules/Students/applicationForm_manage.php')) {
            $applicationType = (isset($_POST['applicationType']))? $_POST['applicationType'] : '';

            if ($applicationType == 'blank') {
                $public = true;
                $gibbonFamilyID = null;
                $gibbonPersonID = null;
            } else if ($applicationType == 'family') {
                $gibbonFamilyID = (isset($_POST['gibbonFamilyID']))? $_POST['gibbonFamilyID'] : '';
            } else if ($applicationType == 'person') {
                $gibbonPersonID = (isset($_POST['gibbonPersonID']))? $_POST['gibbonPersonID'] : '';
            }
        }
    }

    $returnExtra = '';
    $gibbonApplicationFormID = null;

    if (!empty($_GET['id'])) {
    	// Use the returned hash to get the actual ID from the database
    	$data = array( 'gibbonApplicationFormHash' => $_GET['id'] );
        $sql = "SELECT * FROM gibbonApplicationForm WHERE gibbonApplicationFormHash=:gibbonApplicationFormHash";
        $resultID = $pdo->executeQuery($data, $sql);

        if ($resultID && $resultID->rowCount() == 1) {
            $application = $resultID->fetch();
            $gibbonApplicationFormID = str_pad( intval($application['gibbonApplicationFormID']), 7, '0', STR_PAD_LEFT);
        } else {
        	echo "<div class='error'>";
		    echo __('The application link does not match an existing record in our system. The record may have been removed or the link is no longer valid.');
		    echo '</div>';
        }

        $returnExtra = '<br/><br/>'.__('If you need to contact the school in reference to this application, please quote the following number:').' <b><u>'.$gibbonApplicationFormID.'</b></u>.';
    }
    if ($session->get('organisationAdmissionsName') != '' and $session->get('organisationAdmissionsEmail') != '') {
        $returnExtra .= '<br/><br/>'.sprintf(__('Please contact %1$s if you have any questions, comments or complaints.'), "<a href='mailto:".$session->get('organisationAdmissionsEmail')."'>".$session->get('organisationAdmissionsName').'</a>');
    }

    $payment = $container->get(Payment::class);

    if ($payment->isEnabled()) {
        $payment->setForeignTable('gibbonApplicationForm', $gibbonApplicationFormID);
        $page->return->addReturns($payment->getReturnMessages());
    }

    $returns = array();
    $returns['success0'] = __('Your application was successfully submitted. Our admissions team will review your application and be in touch in due course.').$returnExtra;
    $returns['success1'] = __('Your application was successfully submitted and payment has been made to your credit card. Our admissions team will review your application and be in touch in due course.').$returnExtra;
    $returns['success2'] = __('Your application was successfully submitted, but payment could not be made to your credit card. Our admissions team will review your application and be in touch in due course.').$returnExtra;
    $returns['success3'] = __('Your application was successfully submitted, payment has been made to your credit card, but there has been an error recording your payment. Please print this screen and contact the school ASAP. Our admissions team will review your application and be in touch in due course.').$returnExtra;
    $returns['success4'] = __("Your application was successfully submitted, but payment could not be made as the payment gateway does not support the system's currency. Our admissions team will review your application and be in touch in due course.").$returnExtra;
    $page->return->addReturns($returns);

    // JS success return addition
    $return = (isset($_GET['return']))? $_GET['return'] : '';

    if ($return == 'success0' or $return == 'success1' or $return == 'success2' or $return == 'success3' or $return == 'success4') {
        echo "<script type='text/javascript'>";
        echo '$(document).ready(function(){';
        echo "alert('Your application was successfully submitted. Please read the information in the green box above the application form for additional information.') ;";
        echo '});';
        echo '</script>';
    }

    // Get intro
    $intro = $settingGateway->getSettingByScope('Application Form', 'introduction');
    if ($intro != '') {
        echo '<p>';
        echo $intro;
        echo '</p>';
    }

    $currency = $settingGateway->getSettingByScope('System', 'currency');
    $applicationFee = $settingGateway->getSettingByScope('Application Form', 'applicationFee');
    $applicationProcessFee = $settingGateway->getSettingByScope('Application Form', 'applicationProcessFee');
    $uniqueEmailAddress = $settingGateway->getSettingByScope('User Admin', 'uniqueEmailAddress');
    $paymentGateway = $settingGateway->getSettingByScope('System', 'paymentGateway');

    if (!empty($applicationFee) || !empty($applicationProcessFee)) {
        echo "<div class='warning'>";
        if ($applicationFee > 0 and is_numeric($applicationFee)) {
            echo __('Please note that there is an application fee of:').' <b><u>'.$currency.$applicationFee.'</u></b>. ';
        }
        if ($applicationProcessFee > 0 and is_numeric($applicationProcessFee)) {
            echo __('A processing fee of {fee} may be sent by email after your application has been submitted.', ['fee' => '<b><u>'.$currency.$applicationProcessFee.'</u></b>']);
        }
        if ($payment->isEnabled() && !empty($applicationFee)) {
            echo ' '.__('Payment must be made by credit card, using our secure {gateway} payment gateway. When you press Submit at the end of this form, you will be directed to {gateway} in order to make payment. During this process we do not see or store your credit card details.', ['gateway' => $paymentGateway]);
        }
        echo '</div>';
    }

    $siblingApplicationMode = !empty($gibbonApplicationFormID);

    $customFieldHandler = $container->get(CustomFieldHandler::class);
    $personalDocumentHandler = $container->get(PersonalDocumentHandler::class);

    $form = Form::create('applicationForm', $session->get('absoluteURL').'/modules/'.$session->get('module').'/applicationFormProcess.php');
    $form->setAutocomplete('on');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));

    // SIBLING APPLICATIONS
    if ($siblingApplicationMode == true) {
        $gibbonFamilyID = (!empty($application['gibbonFamilyID']))? $application['gibbonFamilyID'] : null;
        $gibbonPersonID = null;

        $form->addHiddenValue('linkedApplicationFormID', $gibbonApplicationFormID);

        $row = $form->addRow()->setClass('break');
            $heading = $row->addSubheading(__('Add Another Application'));
            $heading->append(__('You may continue submitting applications for siblings with the form below and they will be linked to your family data.'));
            $heading->append(__('Some information has been pre-filled for you, feel free to change this information as needed.'));

        $data = array( 'gibbonApplicationFormID' => $gibbonApplicationFormID );
        $sql = 'SELECT DISTINCT gibbonApplicationFormID, preferredName, surname, officialName, dob FROM gibbonApplicationForm
                LEFT JOIN gibbonApplicationFormLink ON (gibbonApplicationForm.gibbonApplicationFormID=gibbonApplicationFormLink.gibbonApplicationFormID1 OR gibbonApplicationForm.gibbonApplicationFormID=gibbonApplicationFormLink.gibbonApplicationFormID2)
                WHERE (gibbonApplicationFormID=:gibbonApplicationFormID AND gibbonApplicationFormLinkID IS NULL)
                OR gibbonApplicationFormID1=:gibbonApplicationFormID
                OR gibbonApplicationFormID2=:gibbonApplicationFormID
                ORDER BY gibbonApplicationFormID';
        $resultLinked = $pdo->executeQuery($data, $sql);

        $linkedApplicationText = '';
        if ($resultLinked && $resultLinked->rowCount() > 0) {
            $linkedApplicationText .= '<ul style="width:302px;display:inline-block">';
            $linkedApplications = $resultLinked->fetchAll();

            foreach ($linkedApplications as $rowLinked) {
                $linkedApplicationText .= '<li>'. Format::name('', $rowLinked['preferredName'], $rowLinked['surname'], 'Student', true);
                $linkedApplicationText .= ' ('.str_pad( intval($rowLinked['gibbonApplicationFormID']), 7, '0', STR_PAD_LEFT).')</li>';
            }
            $linkedApplicationText .= '</ul>';
        }

        $row = $form->addRow();
            $row->addLabel('', __('Current Applications'));
            $row->addContent($linkedApplicationText);
    }

    // STUDENT PERSONAL DATA
    $form->addRow()->addHeading('Student', __('Student'));
    $form->addRow()->addSubheading(__('Student Personal Data'));

    $row = $form->addRow();
        $row->addLabel('surname', __('Surname'))->description(__('Family name as shown in ID documents.'));
        $row->addTextField('surname')->required()->maxLength(60);

    $row = $form->addRow();
        $row->addLabel('firstName', __('First Name'))->description(__('First name as shown in ID documents.'));
        $row->addTextField('firstName')->required()->maxLength(60);

    $row = $form->addRow();
        $row->addLabel('preferredName', __('Preferred Name'))->description(__('Most common name, alias, nickname, etc.'));
        $row->addTextField('preferredName')->required()->maxLength(60);

    $row = $form->addRow();
        $row->addLabel('officialName', __('Official Name'))->description(__('Full name as shown in ID documents.'));
        $row->addTextField('officialName')->required()->maxLength(150)->setTitle(__('Please enter full name as shown in ID documents'));

    $row = $form->addRow();
        $row->addLabel('nameInCharacters', __('Name In Characters'))->description(__('Chinese or other character-based name.'));
        $row->addTextField('nameInCharacters')->maxLength(60);

    $row = $form->addRow();
        $row->addLabel('gender', __('Gender'));
        $row->addSelectGender('gender')->required();

    $row = $form->addRow();
        $row->addLabel('dob', __('Date of Birth'));
        $row->addDate('dob')->required();

    // STUDENT BACKGROUND
    $form->addRow()->addSubheading(__('Student Background'));

    $row = $form->addRow();
        $row->addLabel('languageHomePrimary', __('Home Language - Primary'))->description(__('The primary language used in the student\'s home.'));
        $row->addSelectLanguage('languageHomePrimary')->required();

    $row = $form->addRow();
        $row->addLabel('languageHomeSecondary', __('Home Language - Secondary'));
        $row->addSelectLanguage('languageHomeSecondary')->placeholder('');

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

    $countryName = ($session->has('country')) ? __($session->get('country')).' ' : '';
    $nationalityList = $settingGateway->getSettingByScope('User Admin', 'nationality');
    $residencyStatusList = $settingGateway->getSettingByScope('User Admin', 'residencyStatus');

    // PERSONAL DOCUMENTS
    $params = ['student' => true, 'applicationForm' => true];
    $personalDocumentHandler->addPersonalDocumentsToForm($form, null, null, $params);

    // STUDENT CONTACT
    $form->addRow()->addSubheading(__('Student Contact'));

    $row = $form->addRow();
        $row->addLabel('email', __('Email'));
        $email = $row->addEmail('email');
        if ($uniqueEmailAddress == 'Y') {
            $email->uniqueField('./publicRegistrationCheck.php');
        }

    for ($i = 1; $i < 3; ++$i) {
        $row = $form->addRow();
            $row->addLabel('', __('Phone').' '.$i)->description(__('Type, country code, number.'));
            $row->addPhoneNumber('phone'.$i);
    }

    // SPECIAL EDUCATION & MEDICAL
    $senOptionsActive = $settingGateway->getSettingByScope('Application Form', 'senOptionsActive');

    if ($senOptionsActive == 'Y') {
        $heading = $form->addRow()->addSubheading(__('Special Educational Needs & Medical'));

        $applicationFormSENText = $settingGateway->getSettingByScope('Students', 'applicationFormSENText');
        if (!empty($applicationFormSENText)) {
            $heading->append($applicationFormSENText);
        }

        $row = $form->addRow();
            $row->addLabel('sen', __('Special Educational Needs (SEN)'))->description(__('Are there any known or suspected SEN concerns, or previous SEN assessments?'));
            $row->addYesNo('sen')->required()->placeholder();

        $form->toggleVisibilityByClass('senDetailsRow')->onSelect('sen')->when('Y');

        $row = $form->addRow()->setClass('senDetailsRow');
            $column = $row->addColumn();
            $column->addLabel('senDetails', __('SEN Details'))->description(__('Provide any comments or information concerning your child\'s development and SEN history.'));
            $column->addTextArea('senDetails')->setRows(5)->required()->setClass('fullWidth');

    } else {
        $form->addHiddenValue('sen', 'N');
    }

    $row = $form->addRow();
        $row->addLabel('medical', __('Medical Conditions'))->description(__('Does your child have any medical conditions or concerns?'));
        $row->addYesNo('medical')->required()->placeholder();

    $form->toggleVisibilityByClass('medicalDetailsRow')->onSelect('medical')->when('Y');

    $col = $form->addRow()->setClass('medicalDetailsRow')->addColumn();
        $col->addLabel('medicalInformation', __('Medical Information'))->description(__('Please indicate any medical conditions.'));
        $col->addTextArea('medicalInformation')->setRows(5)->required()->setClass('fullWidth');

    // STUDENT EDUCATION
    $heading = $form->addRow()->addSubheading(__('Student Education'));

    $row = $form->addRow();
        $row->addLabel('gibbonSchoolYearIDEntry', __('Anticipated Year of Entry'))->description(__('What school year will the student join in?'));

        $enableLimitedYearsOfEntry = $settingGateway->getSettingByScope('Application Form', 'enableLimitedYearsOfEntry');
        $availableYearsOfEntry = $settingGateway->getSettingByScope('Application Form', 'availableYearsOfEntry');
        if ($enableLimitedYearsOfEntry == 'Y' && !empty($availableYearsOfEntry)) {
            $data = array('gibbonSchoolYearIDList' => $availableYearsOfEntry);
            $sql = "SELECT gibbonSchoolYearID as value, name FROM gibbonSchoolYear WHERE FIND_IN_SET(gibbonSchoolYearID, :gibbonSchoolYearIDList) ORDER BY sequenceNumber";
        } else {
            $data = array();
            $sql = "SELECT gibbonSchoolYearID as value, name FROM gibbonSchoolYear WHERE (status='Current' OR status='Upcoming') ORDER BY sequenceNumber";
        }
        $row->addSelect('gibbonSchoolYearIDEntry')->fromQuery($pdo, $sql, $data)->required()->placeholder();

    $row = $form->addRow();
        $row->addLabel('dateStart', __('Intended Start Date'))->description(__('Student\'s intended first day at school.'))->append('<br/>'.__('Format:'))->append(' '.$session->get('i18n')['dateFormat']);
        $row->addDate('dateStart')->required();

    $row = $form->addRow();
        $row->addLabel('gibbonYearGroupIDEntry', __('Year Group at Entry'))->description('Which year level will student enter.');
        $sql = "SELECT gibbonYearGroupID as value, name FROM gibbonYearGroup ORDER BY sequenceNumber";
        $row->addSelect('gibbonYearGroupIDEntry')->fromQuery($pdo, $sql)->required()->placeholder();

    // DAY TYPE
    $dayTypeOptions = $settingGateway->getSettingByScope('User Admin', 'dayTypeOptions');
    if (!empty($dayTypeOptions)) {
        $row = $form->addRow();
            $row->addLabel('dayType', __('Day Type'))->description($settingGateway->getSettingByScope('User Admin', 'dayTypeText'));
            $row->addSelect('dayType')->fromString($dayTypeOptions);
    }

    // REFEREE EMAIL
    $applicationFormRefereeLink = $settingGateway->getSettingByScope('Students', 'applicationFormRefereeLink');
    if (!empty($applicationFormRefereeLink)) {
        $applicationFormRefereeRequired = $settingGateway->getSettingByScope('Students', 'applicationFormRefereeRequired', true);
        $row = $form->addRow();
            $row->addLabel('referenceEmail', __('Current School Reference Email'))->description(__('An email address for a referee at the applicant\'s current school.'));
            if ($applicationFormRefereeRequired["value"] == "Y") {
                $row->addEmail('referenceEmail')->required();
            }
            else {
                $row->addEmail('referenceEmail');
            }
    }

    $row = $form->addRow();
        $row->addLabel('', __('Previous Schools'))->description(__('Please give information on the last two schools attended by the applicant.'));

    // PREVIOUS SCHOOLS TABLE
    $table = $form->addRow()->addTable()->addClass('colorOddEven');

    $header = $table->addHeaderRow();
    $header->addContent(__('School Name'));
    $header->addContent(__('Address'));
    $header->addContent(sprintf(__('Grades%1$sAttended'), '<br/>'));
    $header->addContent(sprintf(__('Language of%1$sInstruction'), '<br/>'));
    $header->addContent(__('Joining Date'))->append('<br/><small>'.$session->get('i18n')['dateFormat'].'</small>');

    // Grab some languages, for auto-complete
    $results = $pdo->executeQuery(array(), "SELECT name FROM gibbonLanguage ORDER BY name");
    $languages = ($results && $results->rowCount() > 0)? $results->fetchAll(PDO::FETCH_COLUMN) : array();

    for ($i = 1; $i < 3; ++$i) {
        $row = $table->addRow();
        $row->addTextField('schoolName'.$i)->maxLength(50)->setSize(18);
        $row->addTextField('schoolAddress'.$i)->maxLength(255)->setSize(20);
        $row->addTextField('schoolGrades'.$i)->maxLength(20)->setSize(8);
        $row->addTextField('schoolLanguage'.$i)->autocomplete($languages)->setSize(10);
        $row->addDate('schoolDate'.$i)->setSize(10);
    }

    // CUSTOM FIELDS FOR STUDENT
    $params = ['student' => 1, 'applicationForm' => 1, 'headingLevel' => 'h4'];
    $customFieldHandler->addCustomFieldsToForm($form, 'User', $params);

    // FAMILY
    if (!empty($gibbonFamilyID)) {
        $dataSelect = array('gibbonFamilyID' => $gibbonFamilyID);
        $sqlSelect = 'SELECT * FROM gibbonFamily WHERE gibbonFamily.gibbonFamilyID=:gibbonFamilyID ORDER BY name';
    } else {
        $dataSelect = array('gibbonPersonID' => $gibbonPersonID);
        $sqlSelect = 'SELECT * FROM gibbonFamily JOIN gibbonFamilyAdult ON (gibbonFamily.gibbonFamilyID=gibbonFamilyAdult.gibbonFamilyID) WHERE gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID GROUP BY gibbonFamily.gibbonFamilyID ORDER BY name';
    }

    $resultSelect = $pdo->executeQuery($dataSelect, $sqlSelect);

    if ($public == true or $resultSelect->rowCount() < 1) {

        $form->addHiddenValue('gibbonFamily', 'FALSE');

        if ($siblingApplicationMode == true) {
            $form->addHiddenValue('homeAddress', isset($application['homeAddress'])? $application['homeAddress'] : '');
            $form->addHiddenValue('homeAddressDistrict', isset($application['homeAddressDistrict'])? $application['homeAddressDistrict'] : '');
            $form->addHiddenValue('homeAddressCountry', isset($application['homeAddressCountry'])? $application['homeAddressCountry'] : '');
        } else {
            // HOME ADDRESS
            $form->addRow()->addHeading('Home Address', __('Home Address'))->append(__('This address will be used for all members of the family. If an individual within the family needs a different address, this can be set through Data Updater after admission.'));

            $row = $form->addRow();
                $row->addLabel('homeAddress', __('Home Address'))->description(__('Unit, Building, Street'));
                $row->addTextArea('homeAddress')->required()->maxLength(255)->setRows(2);

            $row = $form->addRow();
                $row->addLabel('homeAddressDistrict', __('Home Address (District)'))->description(__('County, State, District'));
                $row->addTextFieldDistrict('homeAddressDistrict')->required();

            $row = $form->addRow();
                $row->addLabel('homeAddressCountry', __('Home Address (Country)'));
                $row->addSelectCountry('homeAddressCountry')->required();
        }

        // PARENT 1 - IF EXISTS
        if (!empty($gibbonPersonID) || !empty($application['parent1gibbonPersonID'])) {

            if (!empty($application['parent1gibbonPersonID'])) {
                // Get parent info from sibling application
                $parent1gibbonPersonID = $application['parent1gibbonPersonID'];
            } else {
                // Get parent info from gibbonPersonID
                $parent1gibbonPersonID = $gibbonPersonID;
            }

            $dataParent = array('gibbonPersonID' => $parent1gibbonPersonID);
            $sqlParent = 'SELECT username, email, surname, preferredName, fields FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
            $resultParent= $pdo->executeQuery($dataParent, $sqlParent);

            if ($parent = $resultParent->fetch()) {
                $parent1username = $parent['username'];
                $parent1email = $parent['email'];
                $parent1surname = $parent['surname'];
                $parent1preferredName = $parent['preferredName'];
                $parent1fields = $parent['fields'];
            }

            $form->addRow()->addHeading(__('Parent/Guardian').' 1');

            $form->addHiddenValue('parent1email', $parent1email);
            $form->addHiddenValue('parent1gibbonPersonID', $parent1gibbonPersonID);

            $row = $form->addRow();
                $row->addLabel('parent1username', __('Username'))->description(__('System login ID.'));
                $row->addTextField('parent1username')->setValue($parent1username)->maxLength(30)->readOnly();

            $row = $form->addRow();
                $row->addLabel('parent1surname', __('Surname'))->description(__('Family name as shown in ID documents.'));
                $row->addTextField('parent1surname')->setValue($parent1surname)->maxLength(30)->readOnly();

            $row = $form->addRow();
                $row->addLabel('parent1preferredName', __('Preferred Name'))->description(__('Most common name, alias, nickname, etc.'));
                $row->addTextField('parent1preferredName')->setValue($parent1preferredName)->maxLength(30)->readOnly();

            $row = $form->addRow();
                $row->addLabel('parent1relationship', __('Relationship'));
                $row->addSelectRelationship('parent1relationship')->required();

            // CUSTOM FIELDS FOR PARENT 1 WITH FAMILY
            $params = ['parent' => 1, 'applicationForm' => 1, 'prefix' => 'parent1custom', 'headingPrefix' => __('Parent/Guardian').' 1', 'headingLevel' => 'h4'];
            $customFieldHandler->addCustomFieldsToForm($form, 'User', $params, $parent1fields);

            $start = 2;
        } else {
            $start = 1;
        }

        // PARENTS
        for ($i = $start;$i < 3;++$i) {
            $subheading = '';
            if ($i == 1) {
                $subheading = '<span style="font-size: 75%">'.__('(e.g. mother)').'</span>';
            } elseif ($i == 2 and $gibbonPersonID == '') {
                $subheading = '<span style="font-size: 75%">'.__('(e.g. father)').'</span>';
            }

            $form->addRow()->addHeading(__('Parent/Guardian').' '.$i)->append($subheading);

            if ($i == 2) {
                $checked = ($siblingApplicationMode && !empty($application['parent2gibbonPersonID']))? 'No' : 'Yes';
                $form->addRow()->addCheckbox('secondParent')->setValue('No')->checked($checked)->prepend(__('Do not include a second parent/guardian'));
                $form->toggleVisibilityByClass('parentSection2')->onCheckbox('secondParent')->whenNot('No');
            }

            // PARENT PERSONAL DATA
            $row = $form->addRow()->setClass("parentSection{$i}");
                $row->addSubheading(__('Parent/Guardian')." $i ".__('Personal Data'));

            $row = $form->addRow()->setClass("parentSection{$i}");
                $row->addLabel("parent{$i}title", __('Title'));
                $row->addSelectTitle("parent{$i}title")->required()->loadFrom($application);

            $row = $form->addRow()->setClass("parentSection{$i}");
                $row->addLabel("parent{$i}surname", __('Surname'))->description(__('Family name as shown in ID documents.'));
                $row->addTextField("parent{$i}surname")->required()->maxLength(30)->loadFrom($application);

            $row = $form->addRow()->setClass("parentSection{$i}");
                $row->addLabel("parent{$i}firstName", __('First Name'))->description(__('First name as shown in ID documents.'));
                $row->addTextField("parent{$i}firstName")->required()->maxLength(30)->loadFrom($application);

            $row = $form->addRow()->setClass("parentSection{$i}");
                $row->addLabel("parent{$i}preferredName", __('Preferred Name'))->description(__('Most common name, alias, nickname, etc.'));
                $row->addTextField("parent{$i}preferredName")->required()->maxLength(30)->loadFrom($application);

            $row = $form->addRow()->setClass("parentSection{$i}");
                $row->addLabel("parent{$i}officialName", __('Official Name'))->description(__('Full name as shown in ID documents.'));
                $row->addTextField("parent{$i}officialName")->required()->maxLength(150)->loadFrom($application);

            $row = $form->addRow()->setClass("parentSection{$i}");
                $row->addLabel("parent{$i}nameInCharacters", __('Name In Characters'))->description(__('Chinese or other character-based name.'));
                $row->addTextField("parent{$i}nameInCharacters")->maxLength(20)->loadFrom($application);

            $row = $form->addRow()->setClass("parentSection{$i}");
                $row->addLabel("parent{$i}gender", __('Gender'));
                $row->addSelectGender("parent{$i}gender")->required()->loadFrom($application);

            $row = $form->addRow()->setClass("parentSection{$i}");
                $row->addLabel("parent{$i}relationship", __('Relationship'));
                $row->addSelectRelationship("parent{$i}relationship")->required();

            // PARENT PERSONAL BACKGROUND
            $row = $form->addRow()->setClass("parentSection{$i}");
                $row->addSubheading(__('Parent/Guardian')." $i ".__('Personal Background'));

            $row = $form->addRow()->setClass("parentSection{$i}");
                $row->addLabel("parent{$i}languageFirst", __('First Language'));
                $row->addSelectLanguage("parent{$i}languageFirst")->placeholder()->loadFrom($application);

            $row = $form->addRow()->setClass("parentSection{$i}");
                $row->addLabel("parent{$i}languageSecond", __('Second Language'));
                $row->addSelectLanguage("parent{$i}languageSecond")->placeholder()->loadFrom($application);

            // PERSONAL DOCUMENTS
            $params = ['parent' => true, 'applicationForm' => true, 'prefix' => "parent{$i}", 'class' => "parentSection{$i}"];
            $personalDocumentHandler->addPersonalDocumentsToForm($form, null, null, $params);

            // PARENT CONTACT
            $row = $form->addRow()->setClass("parentSection{$i}");
                $row->addSubheading(__('Parent/Guardian')." $i ".__('Contact'));

            $row = $form->addRow()->setClass("parentSection{$i}");
                $row->addLabel("parent{$i}email", __('Email'));
                $email = $row->addEmail("parent{$i}email")->required($i == 1)->loadFrom($application);
                if ($uniqueEmailAddress == 'Y') {
                    $email->uniqueField('./publicRegistrationCheck.php', array('fieldName' => 'email'));
                }

            for ($y = 1; $y < 3; ++$y) {
                $row = $form->addRow()->setClass("parentSection{$i}");
                    $row->addLabel("parent{$i}phone{$y}", __('Phone').' '.$y)->description(__('Type, country code, number.'));
                    $row->addPhoneNumber("parent{$i}phone{$y}")->setRequired($i == 1 && $y == 1)->loadFrom($application);
            }

            // PARENT EMPLOYMENT
            $row = $form->addRow()->setClass("parentSection{$i}");
                $row->addSubheading(__('Parent/Guardian')." $i ".__('Employment'));

            $row = $form->addRow()->setClass("parentSection{$i}");
                $row->addLabel("parent{$i}profession", __('Profession'));
                $row->addTextField("parent{$i}profession")->required($i == 1)->maxLength(90)->loadFrom($application);

            $row = $form->addRow()->setClass("parentSection{$i}");
                $row->addLabel("parent{$i}employer", __('Employer'));
                $row->addTextField("parent{$i}employer")->maxLength(90)->loadFrom($application);

            // CUSTOM FIELDS FOR PARENTS
            $params = ['parent' => 1, 'applicationForm' => 1, 'prefix' => "parent{$i}custom", 'headingPrefix' => __('Parent/Guardian')." $i", 'headingLevel' => 'h4', 'class' => "parentSection{$i}"];
            $customFieldHandler->addCustomFieldsToForm($form, 'User', $params, $application["parent{$i}fields"] ?? '');
        }
    } else {
        // LOGGED IN PARENT WITH FAMILY
        $form->addHiddenValue('gibbonFamily', 'TRUE');

        $row = $form->addRow();
            $row->addHeading('Family', __('Family'))->append(__('Choose the family you wish to associate this application with.'));

        $table = $form->addRow()->addTable()->addClass('colorOddEven');

        $header = $table->addHeaderRow();
        $header->addContent(__('Family Name'));
        $header->addContent(__('Selected'));
        $header->addContent(__('Relationships'));

        $checked = null;
        while ($rowSelect = $resultSelect->fetch()) {
            // Re-select the family for sibling applications, otherwise select the first family
            if (isset($application['gibbonFamilyID'])) {
                $checked = $application['gibbonFamilyID'];
            } else if (is_null($checked)) {
                $checked = $rowSelect['gibbonFamilyID'];
            }

            // Get the family relationships

                $dataRelationships = array('gibbonFamilyID' => $rowSelect['gibbonFamilyID']);
                $sqlRelationships = 'SELECT surname, preferredName, title, gender, gibbonFamilyAdult.gibbonPersonID FROM gibbonFamilyAdult JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID';
                $resultRelationships = $connection2->prepare($sqlRelationships);
                $resultRelationships->execute($dataRelationships);

            $row = $table->addRow()->setClass('break');
            $row->addContent($rowSelect['name'])->wrap('<strong>','</strong>')->addClass('shortWidth');
            $row->addRadio('gibbonFamilyID')->fromArray(array($rowSelect['gibbonFamilyID'] => ''))->checked($checked);
            $subTable = $row->addTable()->setClass('blank');

            while ($rowRelationships = $resultRelationships->fetch()) {
                $selected = ($rowRelationships['gender'] == 'F')? 'Mother' : (($rowRelationships['gender'] == 'M')? 'Father' : '');

                $subTableRow = $subTable->addRow()->addClass('right');
                $subTableRow->addContent(Format::name($rowRelationships['title'], $rowRelationships['preferredName'], $rowRelationships['surname'], 'Parent'))->setClass('mediumWidth');
                $subTableRow->addSelectRelationship($rowSelect['gibbonFamilyID'].'-relationships[]')->selected($selected)->setClass('mediumWidth');
                $form->addHiddenValue($rowSelect['gibbonFamilyID'].'-relationshipsGibbonPersonID[]', $rowRelationships['gibbonPersonID']);
            }

            // If there's only one family, set this now so the Siblings section works
            if ($resultSelect->rowCount() == 1) {
                $gibbonFamilyID = $rowSelect['gibbonFamilyID'];
            }
        }
    }

    // SIBLINGS
    $form->addRow()->addHeading('Siblings', __('Siblings'))->append(__('Please give information on the applicants\'s siblings.'));

    $table = $form->addRow()->addTable()->addClass('colorOddEven');

    $header = $table->addHeaderRow();
    $header->addContent(__('Sibling Name'));
    $header->addContent(__('Date of Birth'))->append('<br/><small>'.$session->get('i18n')['dateFormat'].'</small>');
    $header->addContent(__('School Attending'));
    $header->addContent(__('Joining Date'))->append('<br/><small>'.$session->get('i18n')['dateFormat'].'</small>');

    $rowCount = 1;

    // List siblings who have been to or are at the school
    if (isset($gibbonFamilyID)) {

            $dataSibling = array('gibbonFamilyID' => $gibbonFamilyID);
            $sqlSibling = 'SELECT surname, preferredName, dob, dateStart FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID ORDER BY dob ASC, surname, preferredName';
            $resultSibling = $connection2->prepare($sqlSibling);
            $resultSibling->execute($dataSibling);

        while ($rowSibling = $resultSibling->fetch()) {
            $name = Format::name('', $rowSibling['preferredName'], $rowSibling['surname'], 'Student');

            $row = $table->addRow();
            $row->addTextField('siblingName'.$rowCount)->maxLength(50)->setSize(26)->setValue($name);
            $row->addDate('siblingDOB'.$rowCount)->setSize(10)->setValue(Format::date($rowSibling['dob']));
            $row->addTextField('siblingSchool'.$rowCount)->maxLength(50)->setSize(30)->setValue($session->get('organisationName'));
            $row->addDate('siblingSchoolJoiningDate'.$rowCount)->setSize(10)->setValue(Format::date($rowSibling['dateStart']));

            $rowCount++;
        }
    }

    // Add additional sibling rows up to 3
    for ($i = $rowCount; $i <= 3; ++$i) {
        $row = $table->addRow();
        $nameField = $row->addTextField('siblingName'.$i)->maxLength(50)->setSize(26);
        $dobField = $row->addDate('siblingDOB'.$i)->setSize(10);
        $row->addTextField('siblingSchool'.$i)->maxLength(50)->setSize(30);
        $row->addDate('siblingSchoolJoiningDate'.$i)->setSize(10);

        // Fill in some info from any sibling applications
        if (!empty($linkedApplications[$i-1])) {
            $nameField->setValue($linkedApplications[$i-1]['officialName']);
            $dobField->setValue(Format::date($linkedApplications[$i-1]['dob']));
        }
    }

    // LANGUAGE OPTIONS
    $languageOptionsActive = $settingGateway->getSettingByScope('Application Form', 'languageOptionsActive');
    $languageOptionsBlurb = $settingGateway->getSettingByScope('Application Form', 'languageOptionsBlurb');
    $languageOptionsLanguageList = $settingGateway->getSettingByScope('Application Form', 'languageOptionsLanguageList');

    if ($languageOptionsActive == 'Y' && ($languageOptionsBlurb != '' OR $languageOptionsLanguageList != '')) {

        $heading = $form->addRow()->addHeading('Language Selection', __('Language Selection'));

        if (!empty($languageOptionsBlurb)) {
            $heading->append($languageOptionsBlurb);
        }

        if ($languageOptionsLanguageList != '') {
            $languages = array_map(function($item) { return trim($item); }, explode(',', $languageOptionsLanguageList));

            $row = $form->addRow();
                $row->addLabel('languageChoice', __('Language Choice'))->description(__('Please choose preferred additional language to study.'));
                $row->addSelect('languageChoice')->fromArray($languages)->required()->placeholder();

            $row = $form->addRow();
                $column = $row->addColumn();
                $column->addLabel('languageChoiceExperience', __('Language Choice Experience'))->description(__('Has the applicant studied the selected language before? If so, please describe the level and type of experience.'));
                $column->addTextArea('languageChoiceExperience')->required()->setRows(5)->setClass('fullWidth');
        }
    }

    // SCHOLARSHIPS
    $scholarshipOptionsActive = $settingGateway->getSettingByScope('Application Form', 'scholarshipOptionsActive');

    if ($scholarshipOptionsActive == 'Y') {
        $heading = $form->addRow()->addHeading('Scholarships', __('Scholarships'));

        $scholarship = $settingGateway->getSettingByScope('Application Form', 'scholarships');
        if (!empty($scholarship)) {
            $heading->append($scholarship);
        }

        $row = $form->addRow();
            $row->addLabel('scholarshipInterest', __('Interest'))->description(__('Indicate if you are interested in a scholarship.'));
            $row->addRadio('scholarshipInterest')->fromArray(array('Y' => __('Yes'), 'N' => __('No')))->checked('N')->inline();

        $row = $form->addRow();
            $row->addLabel('scholarshipRequired', __('Required?'))->description(__('Is a scholarship required for you to take up a place at the school?'));
            $row->addRadio('scholarshipRequired')->fromArray(array('Y' => __('Yes'), 'N' => __('No')))->checked('N')->inline();
    }


    // PAYMENT
    $paymentOptionsActive = $settingGateway->getSettingByScope('Application Form', 'paymentOptionsActive');

    if ($paymentOptionsActive == 'Y') {
        $form->addRow()->addHeading('Payment', __('Payment'));

        $form->addRow()->addContent(__('If you choose family, future invoices will be sent according to your family\'s contact preferences, which can be changed at a later date by contacting the school. For example you may wish both parents to receive the invoice, or only one. Alternatively, if you choose Company, you can choose for all or only some fees to be covered by the specified company.'))->wrap('<p>','</p>');

        $row = $form->addRow();
            $row->addLabel('payment', __('Send Future Invoices To'));
            $row->addRadio('payment')
                ->fromArray(array('Family' => __('Family'), 'Company' => __('Company')))
                ->checked('Family')
                ->inline()
                ->loadFrom($application);

        $form->toggleVisibilityByClass('paymentCompany')->onRadio('payment')->when('Company');

        // COMPANY DETAILS
        $row = $form->addRow()->addClass('paymentCompany');
            $row->addLabel('companyName', __('Company Name'));
            $row->addTextField('companyName')->required()->maxLength(100)->loadFrom($application);

        $row = $form->addRow()->addClass('paymentCompany');
            $row->addLabel('companyContact', __('Company Contact Person'));
            $row->addTextField('companyContact')->required()->maxLength(100)->loadFrom($application);

        $row = $form->addRow()->addClass('paymentCompany');
            $row->addLabel('companyAddress', __('Company Address'));
            $row->addTextField('companyAddress')->required()->maxLength(255)->loadFrom($application);

        $row = $form->addRow()->addClass('paymentCompany');
            $row->addLabel('companyEmail', __('Company Emails'))->description(__('Comma-separated list of email address'));
            $row->addTextField('companyEmail')->required()->loadFrom($application);

        $row = $form->addRow()->addClass('paymentCompany');
            $row->addLabel('companyCCFamily', __('CC Family?'))->description(__('Should the family be sent a copy of billing emails?'));
            $row->addYesNo('companyCCFamily')->selected('N')->loadFrom($application);

        $row = $form->addRow()->addClass('paymentCompany');
            $row->addLabel('companyPhone', __('Company Phone'));
            $row->addTextField('companyPhone')->maxLength(20)->loadFrom($application);

        // COMPANY FEE CATEGORIES
        $sqlFees = "SELECT gibbonFinanceFeeCategoryID as value, name FROM gibbonFinanceFeeCategory WHERE active='Y' AND NOT gibbonFinanceFeeCategoryID=1 ORDER BY name";
        $resultFees = $pdo->executeQuery(array(), $sqlFees);

        if (!$resultFees || $resultFees->rowCount() == 0) {
            $form->addHiddenValue('companyAll', 'Y');
        } else {
            $row = $form->addRow()->addClass('paymentCompany');
                $row->addLabel('companyAll', __('Company All?'))->description(__('Should all items be billed to the specified company, or just some?'));
                $row->addRadio('companyAll')->fromArray(array('Y' => __('All'), 'N' => __('Selected')))->checked('Y')->inline()->loadFrom($application);

            $form->toggleVisibilityByClass('paymentCompanyCategories')->onRadio('companyAll')->when('N');

            $existingFeeCategoryIDList = (isset($application['gibbonFinanceFeeCategoryIDList']) && is_array($application['gibbonFinanceFeeCategoryIDList']))? $application['gibbonFinanceFeeCategoryIDList'] : array();

            $row = $form->addRow()->addClass('paymentCompany')->addClass('paymentCompanyCategories');
                $row->addLabel('gibbonFinanceFeeCategoryIDList[]', __('Company Fee Categories'))->description(__('If the specified company is not paying all fees, which categories are they paying?'));
                $row->addCheckbox('gibbonFinanceFeeCategoryIDList[]')
                    ->fromResults($resultFees)
                    ->fromArray(array('0001' => __('Other')))

                    ->loadFromCSV($application);
        }
    } else {
        $form->addHiddenValue('payment', 'Family');
    }

    // REQURIED DOCUMENTS
    $requiredDocuments = $settingGateway->getSettingByScope('Application Form', 'requiredDocuments');

    if (!empty($requiredDocuments)) {
        $requiredDocumentsText = $settingGateway->getSettingByScope('Application Form', 'requiredDocumentsText');
        $requiredDocumentsCompulsory = $settingGateway->getSettingByScope('Application Form', 'requiredDocumentsCompulsory');

        $heading = $form->addRow()->addHeading('Supporting Documents', __('Supporting Documents'));

        if (!empty($requiredDocumentsText)) {
            $heading->append($requiredDocumentsText);

            if ($requiredDocumentsCompulsory == 'Y') {
                $heading->append(__('All documents must all be included before the application can be submitted.'));
            } else {
                $heading->append(__('These documents are all required, but can be submitted separately to this form if preferred. Please note, however, that your application will be processed faster if the documents are included here.'));
            }
        }

        $fileUploader = new Gibbon\FileUploader($pdo, $session);

        $requiredDocumentsList = explode(',', $requiredDocuments);

        for ($i = 0; $i < count($requiredDocumentsList); $i++) {
            $form->addHiddenValue('fileName'.$i, $requiredDocumentsList[$i]);

            $row = $form->addRow();
                $row->addLabel('file'.$i, $requiredDocumentsList[$i]);
                $row->addFileUpload('file'.$i)
                    ->accepts($fileUploader->getFileExtensions())
                    ->setRequired($requiredDocumentsCompulsory == 'Y')
                    ->setMaxUpload(false);
        }

        $row = $form->addRow()->addContent(getMaxUpload());
        $form->addHiddenValue('fileCount', count($requiredDocumentsList));
    }


    // MISCELLANEOUS
    $form->addRow()->addHeading('Miscellaneous', __('Miscellaneous'));

    $howDidYouHear = $settingGateway->getSettingByScope('Application Form', 'howDidYouHear');
    $howDidYouHearList = explode(',', $howDidYouHear);

    $row = $form->addRow();
        $row->addLabel('howDidYouHear', __('How Did You Hear About Us?'));

    if (empty($howDidYouHear)) {
        $row->addTextField('howDidYouHear')->required()->maxLength(30)->loadFrom($application);
    } else {
        $row->addSelect('howDidYouHear')->fromArray($howDidYouHearList)->required()->placeholder()->loadFrom($application);

        $form->toggleVisibilityByClass('tellUsMore')->onSelect('howDidYouHear')->whenNot('Please select...');

        $row = $form->addRow()->addClass('tellUsMore');
            $row->addLabel('howDidYouHearMore', __('Tell Us More'))->description(__('The name of a person or link to a website, etc.'));
            $row->addTextField('howDidYouHearMore')->maxLength(255)->loadFrom($application);
    }

    // PRIVACY
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
            $row = $form->addRow();
                $row->addLabel('privacyOptions[]', __('Privacy Options'));
                $row->addCheckbox('privacyOptions[]')->fromArray($options)->addClass('md:max-w-lg');
        }
    }

    // Honey pot field
    $form->addRow()->addClass('hidden')->addTextField('emailAddress');

    // AGREEMENT
    $agreement = $settingGateway->getSettingByScope('Application Form', 'agreement');
    if (!empty($agreement)) {
        $form->addRow()->addHeading('Agreement', __('Agreement'))->append($agreement);

        $row = $form->addRow();
            $row->addLabel('agreement', '<b>'.__('Do you agree to the above?').'</b>');
            $row->addCheckbox('agreement')->description(__('Yes'))->setValue('on')->required();
    }

    // OFFICE ONLY
    if (isActionAccessible($guid, $connection2, '/modules/Students/applicationForm_manage.php')) {
        $form->addRow()->addHeading('For Office Use', __('For Office Use'));

        $row = $form->addRow();
            $row->addLabel('skipEmailNotification', __('Skip sending a notification email to parents?'));
            $row->addCheckbox('skipEmailNotification')->description(__('Yes'))->setValue('on')->checked('on');
    }

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();

    //Get postscrript
    $postscript = $settingGateway->getSettingByScope('Application Form', 'postscript');
    if ($postscript != '') {
        echo '<h2>';
        echo __('Further Information');
        echo '</h2>';
        echo "<p style='padding-bottom: 15px'>";
        echo $postscript;
        echo '</p>';
    }
}
