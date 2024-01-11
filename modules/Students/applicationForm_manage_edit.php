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
use Gibbon\Domain\Finance\PaymentGateway;
use Gibbon\Forms\PersonalDocumentHandler;
use Gibbon\Domain\User\PersonalDocumentGateway;
use Gibbon\Http\Url;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

//Module includes from User Admin (for custom fields)
include './modules/User Admin/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Students/applicationForm_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $gibbonApplicationFormID = $_GET['gibbonApplicationFormID'] ?? '';
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
    $search = $_GET['search'] ?? '';

    $urlParams = compact('gibbonApplicationFormID', 'gibbonSchoolYearID', 'search');

    $page->breadcrumbs
        ->add(__('Manage Applications'), 'applicationForm_manage.php', $urlParams)
        ->add(__('Edit Form'));

    $settingGateway = $container->get(SettingGateway::class);
    $customFieldHandler = $container->get(CustomFieldHandler::class);
    $personalDocumentHandler = $container->get(PersonalDocumentHandler::class);

    //Check if gibbonApplicationFormID and gibbonSchoolYearID specified
    if ($gibbonApplicationFormID == '' or $gibbonSchoolYearID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }
    
    $data = array('gibbonApplicationFormID' => $gibbonApplicationFormID);
    $sql = "SELECT *, gibbonApplicationForm.status AS 'applicationStatus', gibbonPayment.status AS 'paymentStatus' FROM gibbonApplicationForm LEFT JOIN gibbonPayment ON (gibbonApplicationForm.gibbonPaymentID=gibbonPayment.gibbonPaymentID AND foreignTable='gibbonApplicationForm') WHERE gibbonApplicationFormID=:gibbonApplicationFormID";
    $result = $connection2->prepare($sql);
    $result->execute($data);

    if ($result->rowCount() != 1) {
        $page->addError(__('The specified record does not exist.'));
        return;
    }

    //Let's go!
    $application = $result->fetch();
    $proceed = true;

    $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Students', 'applicationForm_manage')->withQueryParams($urlParams));

    $applicationProcessFee = $settingGateway->getSettingByScope('Application Form', 'applicationProcessFee');
    if ($application['paymentMade2'] == 'N' && !empty($applicationProcessFee) && is_numeric($applicationProcessFee)) {
        $page->navigator->addHeaderAction('payment', __('Send Payment Request'))
            ->setUrl('/index.php')
            ->addParam('q', '/modules/Students/applicationForm_manage_edit_fee.php')
            ->setIcon('page_right')
            ->addParams($urlParams)
            ->displayLabel(true);
    }

    $page->navigator->addHeaderAction('print', __('Print'))
        ->setUrl('/report.php')
        ->addParam('q', '/modules/Students/applicationForm_manage_edit_print.php')
        ->addParams($urlParams)
        ->setTarget('_blank')
        ->directLink()
        ->displayLabel(true);

    $form = Form::create('applicationFormEdit', $session->get('absoluteURL').'/modules/'.$session->get('module').'/applicationForm_manage_editProcess.php?search='.$search);
    $form->setAutocomplete('on');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);
    $form->addHiddenValue('gibbonApplicationFormID', $application['gibbonApplicationFormID']);

    $row = $form->addRow();
        $row->addHeading('For Office Use', __('For Office Use'));
        $row->addContent(__('Fix Block Caps'))->wrap('<small class="emphasis small" style="float:right;margin-top:16px;"><a id="fixCaps">', '</a></small>');

    $row = $form->addRow();
        $row->addLabel('gibbonApplicationFormID', __('Application ID'));
        $row->addTextField('gibbonApplicationFormID')->readOnly();

    $row = $form->addRow();
        $row->addLabel('priority', __('Priority'))->description(__('Higher priority applicants appear first in list of applications.'));
        $row->addSelect('priority')->fromArray(range(-9, 9))->required();

    // STATUS
    if ($application['applicationStatus'] != 'Accepted') {
        $statuses = array(
            'Pending'      => __('Pending'),
            'Waiting List' => __('Waiting List'),
            'Rejected'     => __('Rejected'),
            'Withdrawn'    => __('Withdrawn'),
        );
        $row = $form->addRow();
                $row->addLabel('status', __('Status'))->description(__('Manually set status. "Approved" not permitted.'));
                $row->addSelect('status')->required()->fromArray($statuses)->selected($application['applicationStatus']);
    } else {
        $row = $form->addRow();
            $row->addLabel('status', __('Status'))->description(__('Manually set status. "Approved" not permitted.'));
            $row->addTextField('status')->required()->readOnly()->setValue($application['applicationStatus']);
    }

    // MILESTONES
    $milestonesList = $settingGateway->getSettingByScope('Application Form', 'milestones');
    if (!empty($milestonesList)) {
        $row = $form->addRow();
            $row->addLabel('milestones', __('Milestones'));
            $column = $row->addColumn()->setClass('flex-col items-end');

        $milestonesChecked = array_map('trim', explode(',', $application['milestones']));
        $milestonesArray = array_map('trim', explode(',', $milestonesList));

        foreach ($milestonesArray as $milestone) {
            $name = 'milestone_'.preg_replace('/\s+/', '', $milestone);
            $checked = in_array($milestone, $milestonesChecked);

            $column->addCheckbox($name)->setValue('on')->description($milestone)->checked($checked);
        }
    }

    $row = $form->addRow();
        $row->addLabel('dateStart', __('Start Date'))->description(__('Student\'s intended first day at school.'))->append(__('Format:').' '.$session->get('i18n')['dateFormat']);
        $row->addDate('dateStart')->required();

    $row = $form->addRow();
        $row->addLabel('gibbonSchoolYearIDEntry', __('Year of Entry'))->description(__('When will the student join?'));
        $row->addSelectSchoolYear('gibbonSchoolYearIDEntry', 'Active')->required();

    // YEAR GROUP
    $row = $form->addRow();
        $row->addLabel('gibbonYearGroupIDEntry', __('Year Group at Entry'))->description(__('Which year level will student enter.'));
        $row->addSelectYearGroup('gibbonYearGroupIDEntry')->required();

    // FORM GROUP
    $sqlSelect = "SELECT gibbonFormGroupID as value, name, gibbonSchoolYearID FROM gibbonFormGroup ORDER BY gibbonSchoolYearID, name";
    $resultSelect = $pdo->executeQuery(array(), $sqlSelect);

    $formGroups = ($resultSelect->rowCount() > 0)? $resultSelect->fetchAll() : array();
    $formGroupsChained = array_combine(array_column($formGroups, 'value'), array_column($formGroups, 'gibbonSchoolYearID'));
    $formGroupsOptions = array_combine(array_column($formGroups, 'value'), array_column($formGroups, 'name'));

    $row = $form->addRow();
        $row->addLabel('gibbonFormGroupID', __('Form Group at Entry'))->description(__('If set, the student will automatically be enrolled on Accept.'));
        $row->addSelect('gibbonFormGroupID')
            ->fromArray($formGroupsOptions)
            ->chainedTo('gibbonSchoolYearIDEntry', $formGroupsChained)
            ->placeholder();

    // DAY TYPE
    $dayTypeOptions = $settingGateway->getSettingByScope('User Admin', 'dayTypeOptions');
    if (!empty($dayTypeOptions)) {
        $row = $form->addRow();
            $row->addLabel('dayType', __('Day Type'))->description($settingGateway->getSettingByScope('User Admin', 'dayTypeText'));
            $row->addSelect('dayType')->fromString($dayTypeOptions);
    }

    // PAYMENT
    $currency = $settingGateway->getSettingByScope('System', 'currency');
    $applicationFee = $settingGateway->getSettingByScope('Application Form', 'applicationFee');
    $applicationProcessFee = $settingGateway->getSettingByScope('Application Form', 'applicationProcessFee');
    $enablePayments = $settingGateway->getSettingByScope('System', 'enablePayments');
    $paymentAPIUsername = $settingGateway->getSettingByScope('System', 'paymentAPIUsername');
    $paymentAPIPassword = $settingGateway->getSettingByScope('System', 'paymentAPIPassword');
    $paymentAPISignature = $settingGateway->getSettingByScope('System', 'paymentAPISignature');
    $uniqueEmailAddress = $settingGateway->getSettingByScope('User Admin', 'uniqueEmailAddress');
    $ccPayment = false;

    $paymentMadeOptions = array(
        'N'         => __('N'),
        'Y'         => __('Y'),
        'Exemption' => __('Exemption'),
    );

    if ($applicationFee > 0 and is_numeric($applicationFee)) {
        // PAYMENT MADE
        $row = $form->addRow();
            $row->addLabel('paymentMade', __('Payment on Submission'))->description(sprintf(__('Has payment (%1$s %2$s) been made for this application.'), $currency, $applicationFee));
            $row->addSelect('paymentMade')->fromArray($paymentMadeOptions)->required();

        // PAYMENT DETAILS
        if (!empty($application['paymentToken']) || !empty($application['paymentPayerID']) || !empty($application['paymentTransactionID']) || !empty($application['paymentReceiptID'])) {

            $row = $form->addRow();
                $column = $row->addColumn()->addClass('right');
                $column->addContent(__('Payment Token:').' '.$application['paymentToken']);
                $column->addContent(__('Payment Payer ID:').' '.$application['paymentPayerID']);
                $column->addContent(__('Payment Transaction ID:').' '.$application['paymentTransactionID']);
                $column->addContent(__('Payment Receipt ID:').' '.$application['paymentReceiptID']);
        }
    }

    if ($applicationProcessFee > 0 and is_numeric($applicationProcessFee)) {
        // PAYMENT MADE
        $row = $form->addRow();
            $row->addLabel('paymentMade2', __('Payment for Processing'))->description(sprintf(__('Has payment (%1$s %2$s) been made for this application.'), $currency, $applicationProcessFee));
            $row->addSelect('paymentMade2')->fromArray($paymentMadeOptions)->required();

        // PAYMENT DETAILS
        $payment2 = $container->get(PaymentGateway::class)->getByID($application['gibbonPaymentID2']);
        if (!empty($payment2)) {
            $row = $form->addRow();
                $column = $row->addColumn()->addClass('right');
                $column->addContent(__('Payment Token:').' '.$payment2['paymentToken']);
                $column->addContent(__('Payment Payer ID:').' '.$payment2['paymentPayerID']);
                $column->addContent(__('Payment Transaction ID:').' '.$payment2['paymentTransactionID']);
                $column->addContent(__('Payment Receipt ID:').' '.$payment2['paymentReceiptID']);
        }
    }

    // USERNAME & STUDENT ID
    $row = $form->addRow();
        $row->addLabel('username', __('Username'))->description(__('System login name.'));
        $row->addUsername('username')
            ->readonly($application['applicationStatus'] == 'Accepted')
            ->addGenerateUsernameButton($form);

    $row = $form->addRow();
        $row->addLabel('studentID', __('Student ID'));
        $row->addTextField('studentID')
            ->maxLength(10)
            ->uniqueField('./modules/Students/applicationForm_manage_studentIDAjax.php', ['gibbonApplicationFormID' => $gibbonApplicationFormID])
            ->readonly($application['applicationStatus'] == 'Accepted');

    // NOTES
    $row = $form->addRow();
        $column = $row->addColumn();
        $column->addLabel('notes', __('Notes'));
        $column->addTextArea('notes')->setRows(5)->setClass('fullWidth');

    // SIBLING APPLICATIONS
    $heading = $form->addRow()->addSubheading(__('Sibling Applications'));

    $messageDelete = __('Removing a linked application will NOT delete the application, but the students will no longer be added to the same family.')." ".__('Are you sure you want to proceed with this request?');
    $messageConfirm = __('This will link the current application to the family of the selected application, including all other applications within that family.')." ".__('Are you sure you want to proceed with this request?');

    $data = array( 'gibbonApplicationFormID' => $application['gibbonApplicationFormID'] );
    $sql = "SELECT DISTINCT gibbonApplicationFormID, preferredName, surname, status FROM gibbonApplicationForm
            JOIN gibbonApplicationFormLink ON (gibbonApplicationForm.gibbonApplicationFormID=gibbonApplicationFormLink.gibbonApplicationFormID1 OR gibbonApplicationForm.gibbonApplicationFormID=gibbonApplicationFormLink.gibbonApplicationFormID2)
            WHERE gibbonApplicationFormID <> :gibbonApplicationFormID
            AND (gibbonApplicationFormID1=:gibbonApplicationFormID OR gibbonApplicationFormID2=:gibbonApplicationFormID)
            ORDER BY gibbonApplicationFormID";
    $resultLinked = $pdo->executeQuery($data, $sql);

    if ($resultLinked && $resultLinked->rowCount() > 0) {
        // Display Sibling Applications
        $heading->append('<small>'.__('If accepted, these students will be part of the same family. Accepting or deleting this application does NOT change other Sibling Applications.').'</small>');

        $table = $form->addRow()->addTable()->addClass('colorOddEven');

        $header = $table->addHeaderRow();
        $header->addContent(__('Application ID'));
        $header->addContent(__('Student'));
        $header->addContent(__('Status'));

        $linkedApplications = $resultLinked->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_UNIQUE);

        foreach ($linkedApplications as $linkedApplicationFormID => $rowLinked) {
            $row = $table->addRow();
            $row->addContent(str_pad(intval($linkedApplicationFormID), 7, '0', STR_PAD_LEFT));
            $row->addContent(Format::name('', $rowLinked['preferredName'], $rowLinked['surname'], 'Student', true));
            $row->addContent($rowLinked['status']);

        }
        $row = $table->addRow();
        $row->addContent("<a href='#' onclick='if (confirm(\"".$messageDelete."\")) window.location = \"".$session->get('absoluteURL').'/modules/'.$session->get('module').'/applicationForm_manage_deleteLinkProcess.php?gibbonApplicationFormID='.$gibbonApplicationFormID."&gibbonSchoolYearID=".$gibbonSchoolYearID."\"; else return false;'><img style='margin-left: 4px' title='".__('Remove').' '.__('Sibling Applications')."' src='./themes/".$session->get('gibbonThemeName')."/img/garbage.png'/></a>")->addClass('right');

    } else {
        // Or add a new link (mutually exclusive, to prevent linking multiple families)
        $data = array();
        $sql = "SELECT gibbonApplicationFormID, surname, preferredName, gibbonApplicationForm.status, gibbonSchoolYearID, gibbonSchoolYear.name as schoolYearName FROM gibbonApplicationForm JOIN gibbonSchoolYear ON (gibbonApplicationForm.gibbonSchoolYearIDEntry=gibbonSchoolYear.gibbonSchoolYearID) LEFT JOIN gibbonYearGroup ON (gibbonApplicationForm.gibbonYearGroupIDEntry=gibbonYearGroup.gibbonYearGroupID) WHERE gibbonApplicationForm.gibbonSchoolYearIDEntry >= (SELECT gibbonSchoolYearID from gibbonSchoolYear WHERE status='Current') ORDER BY gibbonSchoolYearID, surname, preferredName";
        $resultApplications = $pdo->executeQuery($data, $sql);

        if (isset($resultApplications) && $resultApplications->rowCount() > 0) {

            // Iterate through the results and build an array of application value => name pairs, grouped by school year
            $linkedApplications = array_reduce($resultApplications->fetchAll(), function($applications, $item) {
                $group = $item['schoolYearName'];
                $value = $item['gibbonApplicationFormID'];
                $applications[$group][$value] = Format::name('', $item['preferredName'], $item['surname'], 'Student', true);

                return $applications;
            }, array());

            // Add a select with it's own submit button, since the other one is all the way at the bottom of the page
            $row = $form->addRow();
                $row->addLabel('linkedApplicationFormID', __('Add linked application(s)'));
                $row->addSelect('linkedApplicationFormID')
                    ->fromArray($linkedApplications)
                    ->placeholder()
                    ->setClass('mediumWidth')
                    ->prepend("<input type='submit' style='float:right' value='".__('Go')."' onclick='if(confirm(\"".$messageConfirm."\")) document.forms[0].submit(); else return false;'>");
        }
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
    $personalDocumentHandler->addPersonalDocumentsToForm($form, 'gibbonApplicationForm', $gibbonApplicationFormID, $params);
    
    // STUDENT CONTACT
    $form->addRow()->addSubheading(__('Student Contact'));

    $row = $form->addRow();
        $row->addLabel('email', __('Email'));
        $email = $row->addEmail('email');
        if ($uniqueEmailAddress == 'Y') {
            $email->uniqueField('./modules/User Admin/user_manage_emailAjax.php');
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
            $heading->append('<p>'.$applicationFormSENText.'<p>');
        }

        $row = $form->addRow();
            $row->addLabel('sen', __('Special Educational Needs (SEN)'))->description(__('Are there any known or suspected SEN concerns, or previous SEN assessments?'));
            $row->addYesNo('sen')->required()->placeholder();

        $form->toggleVisibilityByClass('senDetailsRow')->onSelect('sen')->when('Y');

        $row = $form->addRow()->setClass('senDetailsRow');
            $column = $row->addColumn();
            $column->addLabel('', __('SEN Details'))->description(__('Provide any comments or information concerning your child\'s development and SEN history.'));
            $column->addTextArea('senDetails')->setRows(5)->setClass('fullWidth');

    } else {
        $form->addHiddenValue('sen', 'N');
    }

    $row = $form->addRow();
        $column = $row->addColumn();
        $column->addLabel('', __('Medical Information'))->description(__('Please indicate any medical conditions.'));
        $column->addTextArea('medicalInformation')->setRows(5)->setClass('fullWidth');


    // STUDENT EDUCATION
    $row = $form->addRow()->addSubheading(__('Previous Schools'))->append(__('Please give information on the last two schools attended by the applicant.'));

    // REFEREE EMAIL
    $applicationFormRefereeLink = $settingGateway->getSettingByScope('Students', 'applicationFormRefereeLink');
    if (!empty($applicationFormRefereeLink)) {
        $row = $form->addRow();
            $row->addLabel('referenceEmail', __('Current School Reference Email'))->description(__('An email address for a referee at the applicant\'s current school.'));
            $row->addEmail('referenceEmail')->required();
    }

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
    $customFieldHandler->addCustomFieldsToForm($form, 'User', $params, $application['fields']);

    // FAMILY
    if (empty($application['gibbonFamilyID'])) {

        $form->addHiddenValue('gibbonFamily', 'FALSE');

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


        // PARENT 1 - IF EXISTS
        if (!empty($application['parent1gibbonPersonID']) ) {

            $form->addRow()->addHeading(__('Parent/Guardian').' 1');

            $form->addHiddenValue('parent1email', $application['parent1email']);
            $email = $form->addHiddenValue('parent1gibbonPersonID', $application['parent1gibbonPersonID']);

            $row = $form->addRow();
                $row->addLabel('parent1surname', __('Surname'))->description(__('Family name as shown in ID documents.'));
                $row->addTextField('parent1surname')->maxLength(30)->readOnly();

            $row = $form->addRow();
                $row->addLabel('parent1preferredName', __('Preferred Name'))->description(__('Most common name, alias, nickname, etc.'));
                $row->addTextField('parent1preferredName')->maxLength(30)->readOnly();

            $row = $form->addRow();
                $row->addLabel('parent1relationship', __('Relationship'));
                $row->addSelectRelationship('parent1relationship')->required();

            // CUSTOM FIELDS FOR PARENT 1 WITH FAMILY
            $params = ['parent' => 1, 'applicationForm' => 1, 'prefix' => 'parent1custom', 'headingPrefix' => __('Parent/Guardian').' 1', 'headingLevel' => 'h4'];
            $customFieldHandler->addCustomFieldsToForm($form, 'User', $params, $application['parent1fields'] ?? '');

            $start = 2;
        } else {
            $start = 1;
        }

        // PARENTS
        for ($i = $start; $i < 3; ++$i) {
            $subheading = '';
            if ($i == 1) {
                $subheading = '<span style="font-size: 75%">'.__('(e.g. mother)').'</span>';
            } elseif ($i == 2) {
                $subheading = '<span style="font-size: 75%">'.__('(e.g. father)').'</span>';
            }

            $form->addRow()->addHeading(__('Parent/Guardian').' '.$i)->append($subheading);

            if ($i == 2) {
                $checked = (!empty($application['parent2gibbonPersonID']) || !empty($application['parent2surname']))? 'Yes' : 'No';
                $form->addRow()->addCheckbox('secondParent')->setValue('No')->checked($checked)->prepend(__('Do not include a second parent/guardian'));
                $form->toggleVisibilityByClass('parentSection2')->onCheckbox('secondParent')->whenNot('No');
            }

            // PARENT PERSONAL DATA
            $row = $form->addRow()->setClass("parentSection{$i}");
                $row->addSubheading(__('Parent/Guardian')." $i ".__('Personal Data'));

            $row = $form->addRow()->setClass("parentSection{$i}");
                $row->addLabel("parent{$i}title", __('Title'));
                $row->addSelectTitle("parent{$i}title")->required();

            $row = $form->addRow()->setClass("parentSection{$i}");
                $row->addLabel("parent{$i}surname", __('Surname'))->description(__('Family name as shown in ID documents.'));
                $row->addTextField("parent{$i}surname")->required()->maxLength(30);

            $row = $form->addRow()->setClass("parentSection{$i}");
                $row->addLabel("parent{$i}firstName", __('First Name'))->description(__('First name as shown in ID documents.'));
                $row->addTextField("parent{$i}firstName")->required()->maxLength(30);

            $row = $form->addRow()->setClass("parentSection{$i}");
                $row->addLabel("parent{$i}preferredName", __('Preferred Name'))->description(__('Most common name, alias, nickname, etc.'));
                $row->addTextField("parent{$i}preferredName")->required()->maxLength(30);

            $row = $form->addRow()->setClass("parentSection{$i}");
                $row->addLabel("parent{$i}officialName", __('Official Name'))->description(__('Full name as shown in ID documents.'));
                $row->addTextField("parent{$i}officialName")->required()->maxLength(150);

            $row = $form->addRow()->setClass("parentSection{$i}");
                $row->addLabel("parent{$i}nameInCharacters", __('Name In Characters'))->description(__('Chinese or other character-based name.'));
                $row->addTextField("parent{$i}nameInCharacters")->maxLength(20);

            $row = $form->addRow()->setClass("parentSection{$i}");
                $row->addLabel("parent{$i}gender", __('Gender'));
                $row->addSelectGender("parent{$i}gender")->required();

            $row = $form->addRow()->setClass("parentSection{$i}");
                $row->addLabel("parent{$i}relationship", __('Relationship'));
                $row->addSelectRelationship("parent{$i}relationship")->required();

            // PARENT PERSONAL BACKGROUND
            $row = $form->addRow()->setClass("parentSection{$i}");
                $row->addSubheading(__('Parent/Guardian')." $i ".__('Personal Background'));

            $row = $form->addRow()->setClass("parentSection{$i}");
                $row->addLabel("parent{$i}languageFirst", __('First Language'));
                $row->addSelectLanguage("parent{$i}languageFirst")->placeholder();

            $row = $form->addRow()->setClass("parentSection{$i}");
                $row->addLabel("parent{$i}languageSecond", __('Second Language'));
                $row->addSelectLanguage("parent{$i}languageSecond")->placeholder();

            // PERSONAL DOCUMENTS

            $params = ['parent' => true, 'applicationForm' => true, 'prefix' => "parent{$i}", 'class' => "parentSection{$i}"];
            $personalDocumentHandler->addPersonalDocumentsToForm($form, 'gibbonApplicationFormParent'.$i, $gibbonApplicationFormID, $params);

            // PARENT CONTACT
            $row = $form->addRow()->setClass("parentSection{$i}");
                $row->addSubheading(__('Parent/Guardian')." $i ".__('Contact'));

            $row = $form->addRow()->setClass("parentSection{$i}");
                $row->addLabel("parent{$i}email", __('Email'));
                $email = $row->addEmail("parent{$i}email")->required($i == 1);

                if ($uniqueEmailAddress == 'Y') {
                    $email->uniqueField('./modules/User Admin/user_manage_emailAjax.php', array('fieldName' => 'email'));
                }

            for ($y = 1; $y < 3; ++$y) {
                $row = $form->addRow()->setClass("parentSection{$i}");
                    $row->addLabel("parent{$i}phone{$y}", __('Phone').' '.$y)->description(__('Type, country code, number.'));
                    $row->addPhoneNumber("parent{$i}phone{$y}")->setRequired($i == 1 && $y == 1);
            }

            // PARENT EMPLOYMENT
            $row = $form->addRow()->setClass("parentSection{$i}");
                $row->addSubheading(__('Parent/Guardian')." $i ".__('Employment'));

            $row = $form->addRow()->setClass("parentSection{$i}");
                $row->addLabel("parent{$i}profession", __('Profession'));
                $row->addTextField("parent{$i}profession")->required($i == 1)->maxLength(90);

            $row = $form->addRow()->setClass("parentSection{$i}");
                $row->addLabel("parent{$i}employer", __('Employer'));
                $row->addTextField("parent{$i}employer")->maxLength(90);

            // CUSTOM FIELDS FOR PARENTS
            $params = ['parent' => 1, 'applicationForm' => 1, 'prefix' => "parent{$i}custom", 'headingPrefix' => __('Parent/Guardian')." $i", 'headingLevel' => 'h4', 'class' => "parentSection{$i}"];
            $customFieldHandler->addCustomFieldsToForm($form, 'User', $params, $application["parent{$i}fields"] ?? '');
        }
    } else {
        // EXISTING FAMILY
        $form->addHiddenValue('gibbonFamily', 'TRUE');
        $form->addHiddenValue('gibbonFamilyID', $application['gibbonFamilyID']);

        $row = $form->addRow();
            $row->addHeading('Family', __('Family'))->append(sprintf(__('The applying family is already a member of %1$s.'), $session->get('organisationName')));

        $dataFamily = array('gibbonFamilyID' => $application['gibbonFamilyID']);
        $sqlFamily = 'SELECT * FROM gibbonFamily WHERE gibbonFamilyID=:gibbonFamilyID';
        $resultFamily = $pdo->executeQuery($dataFamily, $sqlFamily);

        if ($resultFamily->rowCount() != 1) {
            $proceed = false;
            $form->addRow()->addTextField('gibbonFamilyError')->readOnly()->setValue(__('There is an error with this form!'));
        } else {
            $rowFamily = $resultFamily->fetch();

            $table = $form->addRow()->addTable()->addClass('colorOddEven');
            $header = $table->addHeaderRow();
            $header->addContent(__('Family Name'));
            $header->addContent(__('Relationships'));

            // Get the family relationships
            
                $dataRelationships = array('gibbonApplicationFormID' => $gibbonApplicationFormID);
                $sqlRelationships = 'SELECT surname, preferredName, title, gender, gibbonApplicationFormRelationship.gibbonPersonID, relationship FROM gibbonApplicationFormRelationship JOIN gibbonPerson ON (gibbonApplicationFormRelationship.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonApplicationFormRelationship.gibbonApplicationFormID=:gibbonApplicationFormID';
                $resultRelationships = $connection2->prepare($sqlRelationships);
                $resultRelationships->execute($dataRelationships);

            $row = $table->addRow()->setClass('break');
            $row->addContent($rowFamily['name'])->wrap('<strong>','</strong>')->addClass('shortWidth');

            $column = $row->addColumn()->setClass('blank');

            while ($rowRelationships = $resultRelationships->fetch()) {
                $column->addContent(Format::name($rowRelationships['title'], $rowRelationships['preferredName'], $rowRelationships['surname'], 'Parent').' ('.$rowRelationships['relationship'].')');
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

    // Add additional sibling rows up to 3
    for ($i = 1; $i <= 3; ++$i) {
        $row = $table->addRow();
        $nameField = $row->addTextField('siblingName'.$i)->maxLength(50)->setSize(26);
        $dobField = $row->addDate('siblingDOB'.$i)->setSize(10);
        $row->addTextField('siblingSchool'.$i)->maxLength(50)->setSize(30);
        $row->addDate('siblingSchoolJoiningDate'.$i)->setSize(10);
    }

    // LANGUAGE OPTIONS
    $languageOptionsActive = $settingGateway->getSettingByScope('Application Form', 'languageOptionsActive');
    $languageOptionsBlurb = $settingGateway->getSettingByScope('Application Form', 'languageOptionsBlurb');
    $languageOptionsLanguageList = $settingGateway->getSettingByScope('Application Form', 'languageOptionsLanguageList');

    if ($languageOptionsActive == 'Y' && ($languageOptionsBlurb != '' OR $languageOptionsLanguageList != '')) {

        $heading = $form->addRow()->addHeading('Language Selection', __('Language Selection'));

        if (!empty($languageOptionsBlurb)) {
            $heading->append($languageOptionsBlurb)->wrap('<p>','</p>');
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
            $heading->append($scholarship)->wrap('<p>','</p>');
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
                ;

        $form->toggleVisibilityByClass('paymentCompany')->onRadio('payment')->when('Company');

        // COMPANY DETAILS
        $row = $form->addRow()->addClass('paymentCompany');
            $row->addLabel('companyName', __('Company Name'));
            $row->addTextField('companyName')->required()->maxLength(100);

        $row = $form->addRow()->addClass('paymentCompany');
            $row->addLabel('companyContact', __('Company Contact Person'));
            $row->addTextField('companyContact')->required()->maxLength(100);

        $row = $form->addRow()->addClass('paymentCompany');
            $row->addLabel('companyAddress', __('Company Address'));
            $row->addTextField('companyAddress')->required()->maxLength(255);

        $row = $form->addRow()->addClass('paymentCompany');
            $row->addLabel('companyEmail', __('Company Emails'))->description(__('Comma-separated list of email address'));
            $row->addTextField('companyEmail')->required();

        $row = $form->addRow()->addClass('paymentCompany');
            $row->addLabel('companyCCFamily', __('CC Family?'))->description(__('Should the family be sent a copy of billing emails?'));
            $row->addYesNo('companyCCFamily')->selected('N');

        $row = $form->addRow()->addClass('paymentCompany');
            $row->addLabel('companyPhone', __('Company Phone'));
            $row->addTextField('companyPhone')->maxLength(20);

        // COMPANY FEE CATEGORIES
        $sqlFees = "SELECT gibbonFinanceFeeCategoryID as value, name FROM gibbonFinanceFeeCategory WHERE active='Y' AND NOT gibbonFinanceFeeCategoryID=1 ORDER BY name";
        $resultFees = $pdo->executeQuery(array(), $sqlFees);

        if (!$resultFees || $resultFees->rowCount() == 0) {
            $form->addHiddenValue('companyAll', 'Y');
        } else {
            $row = $form->addRow()->addClass('paymentCompany');
                $row->addLabel('companyAll', __('Company All?'))->description(__('Should all items be billed to the specified company, or just some?'));
                $row->addRadio('companyAll')->fromArray(array('Y' => __('All'), 'N' => __('Selected')))->checked('Y')->inline();

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
    $internalDocuments = $settingGateway->getSettingByScope('Application Form', 'internalDocuments');

    if (!empty($internalDocuments)) {
        $requiredDocuments .= ','.$internalDocuments;
    }

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
            $heading->wrap('<p>', '</p>');
        }

        $fileUploader = new Gibbon\FileUploader($pdo, $session);

        $requiredDocumentsList = array_map('trim', explode(',', $requiredDocuments));

        for ($i = 0; $i < count($requiredDocumentsList); $i++) {

            $dataFile = array('gibbonApplicationFormID' => $gibbonApplicationFormID, 'name' => $requiredDocumentsList[$i]);
            $sqlFile = "SELECT CONCAT('attachment[', gibbonApplicationFormFileID, ']') as id, path FROM gibbonApplicationFormFile WHERE gibbonApplicationFormID=:gibbonApplicationFormID AND name=:name ORDER BY gibbonApplicationFormFileID DESC";
            $resultFile = $pdo->executeQuery($dataFile, $sqlFile);

            $attachments = ($resultFile && $resultFile->rowCount() > 0)? $resultFile->fetchAll(\PDO::FETCH_KEY_PAIR) : array();

            $form->addHiddenValue('fileName'.$i, $requiredDocumentsList[$i]);

            $row = $form->addRow();
            $row->addLabel('file'.$i, $requiredDocumentsList[$i]);
                $row->addFileUpload('file'.$i)
                    ->accepts($fileUploader->getFileExtensions())
                    ->setAttachments($session->get('absoluteURL'), $attachments)
                    ->setRequired($requiredDocumentsCompulsory == 'Y' && stripos($requiredDocumentsList[$i], $internalDocuments) === false)
                    ->uploadMultiple(true)
                    ->canDelete(true);
        }

        $form->addHiddenValue('fileCount', count($requiredDocumentsList));
    }


    // MISCELLANEOUS
    $form->addRow()->addHeading('Miscellaneous', __('Miscellaneous'));

    $howDidYouHear = $settingGateway->getSettingByScope('Application Form', 'howDidYouHear');
    $howDidYouHearList = explode(',', $howDidYouHear);

    $row = $form->addRow();
        $row->addLabel('howDidYouHear', __('How Did You Hear About Us?'));

    if (empty($howDidYouHear)) {
        $row->addTextField('howDidYouHear')->required()->maxLength(30);
    } else {
        $row->addSelect('howDidYouHear')->fromArray($howDidYouHearList)->required()->placeholder();

        $form->toggleVisibilityByClass('tellUsMore')->onSelect('howDidYouHear')->whenNot('Please select...');

        $row = $form->addRow()->addClass('tellUsMore');
            $row->addLabel('howDidYouHearMore', __('Tell Us More'))->description(__('The name of a person or link to a website, etc.'));
            $row->addTextField('howDidYouHearMore')->maxLength(255);
    }

    // PRIVACY
    $privacySetting = $settingGateway->getSettingByScope('User Admin', 'privacy');
    $privacyBlurb = $settingGateway->getSettingByScope('User Admin', 'privacyBlurb');
    $privacyOptions = $settingGateway->getSettingByScope('User Admin', 'privacyOptions');

    if ($privacySetting == 'Y' && !empty($privacyOptions)) {

        $form->addRow()->addSubheading(__('Privacy'))->append($privacyBlurb);

        $options = array_map('trim', explode(',', $privacyOptions));
        $checked = !empty($application['privacy']) ? array_map('trim', explode(',', $application['privacy'])) : [];

        $row = $form->addRow();
            $row->addLabel('privacyOptions[]', __('Privacy'));
            $row->addCheckbox('privacyOptions[]')->fromArray($options)->checked($checked)->addClass('md:max-w-lg');
    }

    // ⋆⋆⋆ Magic ⋆⋆⋆
    $form->loadAllValuesFrom($application);

    if ($proceed == true) {
        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();
    }

    echo $form->getOutput();

    ?>
    <script type="text/javascript">
    $(document).ready(function(){

        /* Replaces fields in all caps with title case */
        $('a#fixCaps').click(function(){
            $('input[type=text]').val (function () {
                if (this.value.toUpperCase() == this.value) {
                    return this.value.replace(/\b\w+/g, function(txt){return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();});
                } else {
                    return this.value;
                }
            });
            alert('<?php echo __('Fields with all caps have been changed to title case. Please check the updated values and save the form to keep changes.'); ?>');
        });
    });
    </script>
    <?php
}
