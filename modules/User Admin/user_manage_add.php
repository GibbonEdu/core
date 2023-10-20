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

use Gibbon\Data\PasswordPolicy;
use Gibbon\Http\Url;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Timetable\CourseSyncGateway;

if (isActionAccessible($guid, $connection2, '/modules/User Admin/user_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
         ->add(__('Manage Users'), 'user_manage.php')
         ->add(__('Add User'));

    $returns = array();
    $returns['error5'] = __('Your request failed because your passwords did not match.');
    $returns['error6'] = __('Your request failed due to an attachment error.');
    $returns['error7'] = __('Your request failed because your password does not meet the minimum requirements for strength.');
    $returns['warning3'] = __('Your request was completed successfully, but one or more images were the wrong size and so were not saved.');
    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $session->get('absoluteURL').'/index.php?q=/modules/User Admin/user_manage_edit.php&gibbonPersonID='.$_GET['editID'].'&search='.$_GET['search'];
    }
    $page->return->setEditLink($editLink);
    $page->return->addReturns($returns);

    $search = (isset($_GET['search']))? $_GET['search'] : '';

    if (!empty($search)) {
        $page->navigator->addSearchResultsAction(Url::fromModuleRoute('User Admin', 'user_manage.php')->withQueryParam('search', $search));
    }

    echo Format::alert(__('Note that certain fields are available depending on the role categories (Staff, Student, Parent) that a user is assigned to. These fields, such as personal documents and custom fields, will be editable after the user has been created.'), 'message');

    $form = Form::create('addUser', $session->get('absoluteURL').'/modules/'.$session->get('module').'/user_manage_addProcess.php?search='.$search);
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));

    // BASIC INFORMATION
    $form->addRow()->addHeading('Basic Information', __('Basic Information'));

    $row = $form->addRow();
        $row->addLabel('title', __('Title'));
        $row->addSelectTitle('title');

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
        $row->addDate('dob');

    $row = $form->addRow();
        $row->addLabel('file1', __('User Photo'))
            ->description(__('Displayed at 240px by 320px.'))
            ->description(__('Accepts images up to 360px by 480px.'))
            ->description(__('Accepts aspect ratio between 1:1.2 and 1:1.4.'));
        $row->addFileUpload('file1')
            ->accepts('.jpg,.jpeg,.gif,.png')
            ->setMaxUpload(false);

    // SYSTEM ACCESS
    $form->addRow()->addHeading('System Access', __('System Access'));

    // Put together an array of this user's current roles
    $currentUserRoles = (is_array($session->get('gibbonRoleIDAll'))) ? array_column($session->get('gibbonRoleIDAll'), 0) : array();
    $currentUserRoles[] = $session->get('gibbonRoleIDPrimary');

    $data = array();
    $sql = "SELECT * FROM gibbonRole ORDER BY name";
    $result = $pdo->executeQuery($data, $sql);

    // Get all roles and filter roles based on role restrictions
    $staffRoles = [];
    $studentRoles = [];
    $availableRoles = ($result && $result->rowCount() > 0)? $result->fetchAll() : array();
    $availableRoles = array_reduce($availableRoles, function ($carry, $item) use (&$currentUserRoles, &$staffRoles, &$studentRoles) {
        if ($item['restriction'] == 'Admin Only') {
            if (!in_array('001', $currentUserRoles)) return $carry;
        } else if ($item['restriction'] == 'Same Role') {
            if (!in_array($item['gibbonRoleID'], $currentUserRoles) && !in_array('001', $currentUserRoles)) return $carry;
        }
        if ($item['category'] == 'Staff') {
            $staffRoles[] = $item['gibbonRoleID'];
        }
        if ($item['category'] == 'Student') {
            $studentRoles[] = $item['gibbonRoleID'];
        }
        $carry[$item['gibbonRoleID']] = __($item['name']);
        return $carry;
    }, array());

    $row = $form->addRow();
        $row->addLabel('gibbonRoleIDPrimary', __('Primary Role'))->description(__('Controls what a user can do and see.'));
        $row->addSelect('gibbonRoleIDPrimary')->fromArray($availableRoles)->required()->placeholder();

    $row = $form->addRow();
        $row->addLabel('username', __('Username'))->description(__('System login name.'));
        $row->addUsername('username')
            ->required()
            ->addGenerateUsernameButton($form);

    /** @var PasswordPolicy */
    $policies = $container->get(PasswordPolicy::class);
    if (($policiesHTML = $policies->describeHTML()) !== '') {
        $form->addRow()->addAlert($policiesHTML, 'warning');
    }
    $row = $form->addRow();
        $row->addLabel('passwordNew', __('Password'));
        $row->addPassword('passwordNew')
            ->addPasswordPolicy($pdo)
            ->addGeneratePasswordButton($form)
            ->required()
            ->maxLength(30);

    $row = $form->addRow();
        $row->addLabel('passwordConfirm', __('Confirm Password'));
        $row->addPassword('passwordConfirm')
            ->addConfirmation('passwordNew')
            ->required()
            ->maxLength(30);

    $row = $form->addRow();
        $row->addLabel('status', __('Status'))->description(__('This determines visibility within the system.'));
        $row->addSelectStatus('status')->required();

    $row = $form->addRow();
        $row->addLabel('canLogin', __('Can Login?'));
        $row->addYesNo('canLogin')->required();

    $row = $form->addRow();
        $row->addLabel('passwordForceReset', __('Force Reset Password?'))->description(__('User will be prompted on next login.'));
        $row->addYesNo('passwordForceReset')->required();

    // CONTACT INFORMATION
    $form->addRow()->addHeading('Contact Information', __('Contact Information'));

    $row = $form->addRow();
        $emailLabel = $row->addLabel('email', __('Email'));
        $email = $row->addEmail('email');

    $settingGateway = $container->get(SettingGateway::class);

    $uniqueEmailAddress = $settingGateway->getSettingByScope('User Admin', 'uniqueEmailAddress');
    if ($uniqueEmailAddress == 'Y') {
        $email->uniqueField($session->get('absoluteURL').'/modules/User Admin/user_manage_emailAjax.php');
    }

    $row = $form->addRow();
        $row->addLabel('emailAlternate', __('Alternate Email'));
        $row->addEmail('emailAlternate');

    $row = $form->addRow();
    $row->addAlert(__('Address information for an individual only needs to be set under the following conditions:'), 'warning')
        ->append('<ol>')
        ->append('<li>'.__('If the user is not in a family.').'</li>')
        ->append('<li>'.__('If the user\'s family does not have a home address set.').'</li>')
        ->append('<li>'.__('If the user needs an address in addition to their family\'s home address.').'</li>')
        ->append('</ol>');

    $row = $form->addRow();
        $row->addLabel('showAddresses', __('Enter Personal Address?'));
        $row->addCheckbox('showAddresses')->setValue('Yes');

    $form->toggleVisibilityByClass('address')->onCheckbox('showAddresses')->when('Yes');

    $row = $form->addRow()->addClass('address');
        $row->addLabel('address1', __('Address 1'))->description(__('Unit, Building, Street'));
        $row->addTextField('address1')->maxLength(255);

    $row = $form->addRow()->addClass('address');
        $row->addLabel('address1District', __('Address 1 District'))->description(__('County, State, District'));
        $row->addTextFieldDistrict('address1District');

    $row = $form->addRow()->addClass('address');
        $row->addLabel('address1Country', __('Address 1 Country'));
        $row->addSelectCountry('address1Country');

    $row = $form->addRow()->addClass('address');
        $row->addLabel('address2', __('Address 2'))->description(__('Unit, Building, Street'));
        $row->addTextField('address2')->maxLength(255);

    $row = $form->addRow()->addClass('address');
        $row->addLabel('address2District', __('Address 2 District'))->description(__('County, State, District'));
        $row->addTextFieldDistrict('address2District');

    $row = $form->addRow()->addClass('address');
        $row->addLabel('address2Country', __('Address 2 Country'));
        $row->addSelectCountry('address2Country');

    for ($i = 1; $i < 5; ++$i) {
        $row = $form->addRow();
        $row->addLabel('phone'.$i, __('Phone').' '.$i)->description(__('Type, country code, number.'));
        $row->addPhoneNumber('phone'.$i);
    }

    $row = $form->addRow();
        $row->addLabel('website', __('Website'))->description(__('Include http://'));
        $row->addURL('website');

    // SCHOOL INFORMATION
    $form->addRow()->addHeading('School Information', __('School Information'));

    $dayTypeOptions = $settingGateway->getSettingByScope('User Admin', 'dayTypeOptions');
    if (!empty($dayTypeOptions)) {
        $dayTypeText = $settingGateway->getSettingByScope('User Admin', 'dayTypeText');
        $row = $form->addRow();
            $row->addLabel('dayType', __('Day Type'))->description($dayTypeText);
            $row->addSelect('dayType')->fromString($dayTypeOptions)->placeholder();
    }

    $sql = "SELECT DISTINCT lastSchool FROM gibbonPerson ORDER BY lastSchool";
    $result = $pdo->executeQuery(array(), $sql);
    $schools = ($result && $result->rowCount() > 0)? $result->fetchAll(\PDO::FETCH_COLUMN) : array();

    $row = $form->addRow();
        $row->addLabel('lastSchool', __('Last School'));
        $row->addTextField('lastSchool')->autocomplete($schools);

    $row = $form->addRow();
        $row->addLabel('dateStart', __('Start Date'))->description(__("Users's first day at school."));
        $row->addDate('dateStart');

    $row = $form->addRow();
        $row->addLabel('gibbonSchoolYearIDClassOf', __('Class Of'))->description(__('When is the student expected to graduate?'));
        $row->addSelectSchoolYear('gibbonSchoolYearIDClassOf');

    // BACKGROUND INFORMATION
    $form->addRow()->addHeading('Background Information', __('Background Information'));

    $row = $form->addRow();
        $row->addLabel('languageFirst', __('First Language'));
        $row->addSelectLanguage('languageFirst');

    $row = $form->addRow();
        $row->addLabel('languageSecond', __('Second Language'));
        $row->addSelectLanguage('languageSecond');

    $row = $form->addRow();
        $row->addLabel('languageThird', __('Third Language'));
        $row->addSelectLanguage('languageThird');

    $row = $form->addRow();
        $row->addLabel('countryOfBirth', __('Country of Birth'));
        $row->addSelectCountry('countryOfBirth');

    $ethnicities = $settingGateway->getSettingByScope('User Admin', 'ethnicity');
    $row = $form->addRow();
        $row->addLabel('ethnicity', __('Ethnicity'));
        if (!empty($ethnicities)) {
            $row->addSelect('ethnicity')->fromString($ethnicities)->placeholder();
        } else {
            $row->addTextField('ethnicity')->maxLength(255);
        }

    $religions = $settingGateway->getSettingByScope('User Admin', 'religions');
    $row = $form->addRow();
        $row->addLabel('religion', __('Religion'));
        if (!empty($religions)) {
            $row->addSelect('religion')->fromString($religions)->placeholder();
        } else {
            $row->addTextField('religion')->maxLength(30);
        }

    $nationalityList = $settingGateway->getSettingByScope('User Admin', 'nationality');
    $residencyStatusList = $settingGateway->getSettingByScope('User Admin', 'residencyStatus');

    // EMPLOYMENT
    $form->addRow()->addHeading('Employment', __('Employment'));

    $row = $form->addRow();
        $row->addLabel('profession', __('Profession'));
        $row->addTextField('profession')->maxLength(90);

    $row = $form->addRow();
        $row->addLabel('employer', __('Employer'));
        $row->addTextField('employer')->maxLength(90);

    $row = $form->addRow();
        $row->addLabel('jobTitle', __('Job Title'));
        $row->addTextField('jobTitle')->maxLength(90);

    // EMERGENCY CONTACTS
    $form->addRow()->addHeading('Emergency Contacts', __('Emergency Contacts'));

    $form->addRow()->addContent(__('These details are used when immediate family members (e.g. parent, spouse) cannot be reached first. Please try to avoid listing immediate family members.'));

    $row = $form->addRow();
        $row->addLabel('emergency1Name', __('Contact 1 Name'));
        $row->addTextField('emergency1Name')->maxLength(90);

    $row = $form->addRow();
        $row->addLabel('emergency1Relationship', __('Contact 1 Relationship'));
        $row->addSelectEmergencyRelationship('emergency1Relationship');

    $row = $form->addRow();
        $row->addLabel('emergency1Number1', __('Contact 1 Number 1'));
        $row->addTextField('emergency1Number1')->maxLength(30);

    $row = $form->addRow();
        $row->addLabel('emergency1Number2', __('Contact 1 Number 2'));
        $row->addTextField('emergency1Number2')->maxLength(30);

    $row = $form->addRow();
        $row->addLabel('emergency2Name', __('Contact 2 Name'));
        $row->addTextField('emergency2Name')->maxLength(90);

    $row = $form->addRow();
        $row->addLabel('emergency2Relationship', __('Contact 2 Relationship'));
        $row->addSelectEmergencyRelationship('emergency2Relationship');

    $row = $form->addRow();
        $row->addLabel('emergency2Number1', __('Contact 2 Number 1'));
        $row->addTextField('emergency2Number1')->maxLength(30);

    $row = $form->addRow();
        $row->addLabel('emergency2Number2', __('Contact 2 Number 2'));
        $row->addTextField('emergency2Number2')->maxLength(30);

    // MISCELLANEOUS
    $form->addRow()->addHeading('Miscellaneous', __('Miscellaneous'));

    $sql = "SELECT gibbonHouseID as value, name FROM gibbonHouse ORDER BY name";
    $row = $form->addRow();
        $row->addLabel('gibbonHouseID', __('House'));
        $row->addSelect('gibbonHouseID')->fromQuery($pdo, $sql)->placeholder();

    $row = $form->addRow();
        $row->addLabel('studentID', __('Student ID'));
        $row->addTextField('studentID')
            ->maxLength(15)
            ->uniqueField('./modules/User Admin/user_manage_studentIDAjax.php');

    $sql = "SELECT DISTINCT transport FROM gibbonPerson
            JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
            WHERE gibbonStudentEnrolment.gibbonSchoolYearID=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE status='Current')
            ORDER BY transport";
    $result = $pdo->executeQuery(array(), $sql);
    $transport = ($result && $result->rowCount() > 0)? $result->fetchAll(\PDO::FETCH_COLUMN) : array();

    $row = $form->addRow();
        $row->addLabel('transport', __('Transport'));
        $row->addTextField('transport')->maxLength(255)->autocomplete($transport);

    $row = $form->addRow();
        $row->addLabel('transportNotes', __('Transport Notes'));
        $row->addTextArea('transportNotes')->setRows(4);

    $row = $form->addRow();
        $row->addLabel('lockerNumber', __('Locker Number'));
        $row->addTextField('lockerNumber')->maxLength(20);

    $row = $form->addRow();
        $row->addLabel('vehicleRegistration', __('Vehicle Registration'));
        $row->addTextField('vehicleRegistration')->maxLength(20);

    $privacySetting = $settingGateway->getSettingByScope('User Admin', 'privacy');
    $privacyOptions = $settingGateway->getSettingByScope('User Admin', 'privacyOptions');

    if ($privacySetting == 'Y' && !empty($privacyOptions)) {
        $options = array_map(function($item) { return trim($item); }, explode(',', $privacyOptions));

        $row = $form->addRow();
            $row->addLabel('privacyOptions[]', __('Privacy'))->description(__('Check to indicate which privacy options are required.'));
            $row->addCheckbox('privacyOptions[]')->fromArray($options)->addClass('md:max-w-lg');
    }

    $studentAgreementOptions = $settingGateway->getSettingByScope('School Admin', 'studentAgreementOptions');
    if (!empty($studentAgreementOptions)) {
        $options = array_map(function($item) { return trim($item); }, explode(',', $studentAgreementOptions));

        $row = $form->addRow();
        $row->addLabel('studentAgreements[]', __('Student Agreements'))->description(__('Check to indicate that student has signed the relevant agreement.'));
        $row->addCheckbox('studentAgreements[]')->fromArray($options);
    }

    // STAFF
    $form->toggleVisibilityByClass('staffDetails')->onSelect('gibbonRoleIDPrimary')->when($staffRoles);
    $form->toggleVisibilityByClass('staffRecord')->onCheckbox('staffRecord')->when('Y');
    $form->addRow()->addClass('staffDetails')->addHeading('Staff', __('Staff'))->addClass('staffDetails');

    $row = $form->addRow()->addClass('staffDetails');
        $row->addLabel('staffRecord', __('Add Staff'));
        $row->addCheckbox('staffRecord')->setValue('Y')->description(__('Create a linked staff record?'));

    $types = array ('Teaching' => __('Teaching'), 'Support' => __('Support'));
    $row = $form->addRow()->addClass('staffRecord');
        $row->addLabel('staffType', __('Type'));
        $row->addSelect('staffType')->fromArray($types)->placeholder()->required();

    $row = $form->addRow()->addClass('staffRecord');
        $row->addLabel('jobTitle', __('Job Title'));
        $row->addTextField('jobTitle')->maxlength(100);

    // STUDENT
    $form->toggleVisibilityByClass('studentDetails')->onSelect('gibbonRoleIDPrimary')->when($studentRoles);
    $form->toggleVisibilityByClass('studentRecord')->onCheckbox('studentRecord')->when('Y');
    $form->addRow()->addClass('studentDetails')->addHeading('Student', __('Student'))->addClass('studentDetails');

    $row = $form->addRow()->addClass('studentDetails');
        $row->addLabel('studentRecord', __('Add Student Enrolment'));
        $row->addCheckbox('studentRecord')->setValue('Y')->description(__('Create a linked student record?'));

    $row = $form->addRow()->addClass('studentRecord');
    $row->addLabel('yearName', __('School Year'));
    $row->addTextField('yearName')->readOnly()->maxLength(20)->setValue($session->get('gibbonSchoolYearName'));

    $row = $form->addRow()->addClass('studentRecord');
        $row->addLabel('gibbonYearGroupID', __('Year Group'));
        $row->addSelectYearGroup('gibbonYearGroupID')->required();

    $row = $form->addRow()->addClass('studentRecord');
        $row->addLabel('gibbonFormGroupID', __('Form Group'));
        $row->addSelectFormGroup('gibbonFormGroupID', $session->get('gibbonSchoolYearID'))->required();

    $row = $form->addRow()->addClass('studentRecord');
        $row->addLabel('rollOrder', __('Roll Order'));
        $row->addNumber('rollOrder')->maxLength(2);

    // Check to see if any class mappings exists -- otherwise this feature is inactive, hide it
    $classMapCount = $container->get(CourseSyncGateway::class)->countAll();
    if ($classMapCount > 0) {
        $autoEnrolDefault = $settingGateway->getSettingByScope('Timetable Admin', 'autoEnrolCourses');
        $row = $form->addRow()->addClass('studentRecord');;
            $row->addLabel('autoEnrolStudent', __('Auto-Enrol Courses?'))
                ->description(__('Should this student be automatically enrolled in courses for their Form Group?'));
            $row->addYesNo('autoEnrolStudent')->selected($autoEnrolDefault);
    }

    // SUBMIT
    $row = $form->addRow();
        $row->addFooter()->append('<small>'.getMaxUpload(true).'</small>');
        $row->addSubmit();

    echo $form->getOutput();
}
