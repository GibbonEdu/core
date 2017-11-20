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

if (isActionAccessible($guid, $connection2, '/modules/User Admin/user_manage_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__('Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__(getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/User Admin/user_manage.php'>".__('Manage Users')."</a> > </div><div class='trailEnd'>".__('Add User').'</div>';
    echo '</div>';

    $returns = array();
    $returns['error5'] = __('Your request failed because your passwords did not match.');
    $returns['error6'] = __('Your request failed due to an attachment error.');
    $returns['error7'] = __('Your request failed because your password to not meet the minimum requirements for strength.');
    $returns['warning1'] = __('Your request was completed successfully, but one or more images were the wrong size and so were not saved.');
    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/User Admin/user_manage_edit.php&gibbonPersonID='.$_GET['editID'].'&search='.$_GET['search'];
    }
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], $editLink, $returns);
    }

    $search = (isset($_GET['search']))? $_GET['search'] : '';

    if (!empty($search)) {
        echo "<div class='linkTop'>";
        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/User Admin/user_manage.php&search='.$search."'>".__('Back to Search Results').'</a>';
        echo '</div>';
    }

    $form = Form::create('addUser', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/user_manage_addProcess.php?search='.$search);
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    // BASIC INFORMATION
    $form->addRow()->addHeading(__('Basic Information'));

    $row = $form->addRow();
        $row->addLabel('title', __('Title'));
        $row->addSelectTitle('title');

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
        $row->addTextField('officialName')->isRequired()->maxLength(150)->setTitle(__('Please enter full name as shown in ID documents'));

    $row = $form->addRow();
        $row->addLabel('nameInCharacters', __('Name In Characters'))->description(__('Chinese or other character-based name.'));
        $row->addTextField('nameInCharacters')->maxLength(20);

    $row = $form->addRow();
        $row->addLabel('gender', __('Gender'));
        $row->addSelectGender('gender')->isRequired();

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
    $form->addRow()->addHeading(__('System Access'));

    // Put together an array of this user's current roles
    $currentUserRoles = (is_array($_SESSION[$guid]['gibbonRoleIDAll'])) ? array_column($_SESSION[$guid]['gibbonRoleIDAll'], 0) : array();
    $currentUserRoles[] = $_SESSION[$guid]['gibbonRoleIDPrimary'];

    $data = array();
    $sql = "SELECT * FROM gibbonRole ORDER BY name";
    $result = $pdo->executeQuery($data, $sql);

    // Get all roles and filter roles based on role restrictions
    $availableRoles = ($result && $result->rowCount() > 0)? $result->fetchAll() : array();
    $availableRoles = array_reduce($availableRoles, function ($carry, $item) use (&$currentUserRoles) {
        if ($item['restriction'] == 'Admin Only') {
            if (!in_array('001', $currentUserRoles)) return $carry;
        } else if ($item['restriction'] == 'Same Role') {
            if (!in_array($item['gibbonRoleID'], $currentUserRoles) && !in_array('001', $currentUserRoles)) return $carry;
        }
        $carry[$item['gibbonRoleID']] = $item['name'];
        return $carry;
    }, array());

    $row = $form->addRow();
        $row->addLabel('gibbonRoleIDPrimary', __('Primary Role'))->description(__('Controls what a user can do and see.'));
        $row->addSelect('gibbonRoleIDPrimary')->fromArray($availableRoles)->isRequired()->placeholder();

    $generateUsername = $form->getFactory()->createButton(__('Generate'))->addClass('generateUsername alignRight')->setTabIndex(-1);
    $row = $form->addRow();
        $row->addLabel('username', __('Username'))->description(__('Must be unique. System login name. Cannot be changed.'));
        $row->addTextField('username')
            ->isRequired()
            ->maxLength(20)
            ->append($generateUsername->getOutput())
            ->isUnique($_SESSION[$guid]['absoluteURL'].'/publicRegistrationCheck.php');

    $policy = getPasswordPolicy($guid, $connection2);
    if ($policy != false) {
        $form->addRow()->addAlert($policy, 'warning');
    }
    $row = $form->addRow();
        $row->addLabel('passwordNew', __('Password'));
        $row->addPassword('passwordNew')
            ->addPasswordPolicy($pdo)
            ->addGeneratePasswordButton($form)
            ->isRequired()
            ->maxLength(30);

    $row = $form->addRow();
        $row->addLabel('passwordConfirm', __('Confirm Password'));
        $row->addPassword('passwordConfirm')
            ->addConfirmation('passwordNew')
            ->isRequired()
            ->maxLength(30);

    $row = $form->addRow();
        $row->addLabel('status', __('Status'))->description(__('This determines visibility within the system.'));
        $row->addSelectStatus('status')->isRequired();

    $row = $form->addRow();
        $row->addLabel('canLogin', __('Can Login?'));
        $row->addYesNo('canLogin')->isRequired();

    $row = $form->addRow();
        $row->addLabel('passwordForceReset', __('Force Reset Password?'))->description(__('User will be prompted on next login.'));
        $row->addYesNo('passwordForceReset')->isRequired();

    // CONTACT INFORMATION
    $form->addRow()->addHeading(__('Contact Information'));

    $row = $form->addRow();
        $emailLabel = $row->addLabel('email', __('Email'));
        $email = $row->addEmail('email')->maxLength(50);
        
    $uniqueEmailAddress = getSettingByScope($connection2, 'User Admin', 'uniqueEmailAddress');
    if ($uniqueEmailAddress == 'Y') {
        $email->isUnique($_SESSION[$guid]['absoluteURL'].'/modules/User Admin/user_manage_emailAjax.php');
    }

    $row = $form->addRow();
        $row->addLabel('emailAlternate', __('Alternate Email'));
        $row->addEmail('emailAlternate')->maxLength(50);

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
    $form->addRow()->addHeading(__('School Information'));

    $dayTypeOptions = getSettingByScope($connection2, 'User Admin', 'dayTypeOptions');
    if (!empty($dayTypeOptions)) {
        $dayTypeText = getSettingByScope($connection2, 'User Admin', 'dayTypeText');
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
    $form->addRow()->addHeading(__('Background Information'));

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

    $row = $form->addRow();
        $row->addLabel('birthCertificateScan', __('Birth Certificate Scan'))->description(__('Less than 1440px by 900px').'. '.__('Accepts PDF files.'));
        $row->addFileUpload('birthCertificateScan')->accepts('.jpg,.jpeg,.gif,.png,.pdf')->setMaxUpload(false);

    $ethnicities = getSettingByScope($connection2, 'User Admin', 'ethnicity');
    $row = $form->addRow();
        $row->addLabel('ethnicity', __('Ethnicity'));
        if (!empty($ethnicities)) {
            $row->addSelect('ethnicity')->fromString($ethnicities)->placeholder();
        } else {
            $row->addTextField('ethnicity')->maxLength(255);
        }

    $religions = getSettingByScope($connection2, 'User Admin', 'religions');
    $row = $form->addRow();
        $row->addLabel('religion', __('Religion'));
        if (!empty($religions)) {
            $row->addSelect('religion')->fromString($religions)->placeholder();
        } else {
            $row->addTextField('religion')->maxLength(30);
        }

    $nationalityList = getSettingByScope($connection2, 'User Admin', 'nationality');
    $row = $form->addRow();
        $row->addLabel('citizenship1', __('Citizenship 1'));
        if (!empty($nationalityList)) {
            $row->addSelect('citizenship1')->fromString($nationalityList)->placeholder();
        } else {
            $row->addSelectCountry('citizenship1');
        }

    $row = $form->addRow();
        $row->addLabel('citizenship1Passport', __('Citizenship 1 Passport Number'));
        $row->addTextField('citizenship1Passport')->maxLength(30);

    $row = $form->addRow();
        $row->addLabel('citizenship1PassportScan', __('Citizenship 1 Passport Scan'))->description(__('Less than 1440px by 900px').'. '.__('Accepts PDF files.'));
        $row->addFileUpload('citizenship1PassportScan')->accepts('.jpg,.jpeg,.gif,.png,.pdf')->setMaxUpload(false);

    $row = $form->addRow();
        $row->addLabel('citizenship2', __('Citizenship 2'));
        if (!empty($nationalityList)) {
            $row->addSelect('citizenship2')->fromString($nationalityList)->placeholder();
        } else {
            $row->addSelectCountry('citizenship2');
        }

    $row = $form->addRow();
        $row->addLabel('citizenship2Passport', __('Citizenship 2 Passport Number'));
        $row->addTextField('citizenship2Passport')->maxLength(30);

    if (!empty($_SESSION[$guid]['country'])) {
        $nationalIDCardNumberLabel = $_SESSION[$guid]['country'].' '.__('ID Card Number');
        $nationalIDCardScanLabel = $_SESSION[$guid]['country'].' '.__('ID Card Scan');
        $residencyStatusLabel = $_SESSION[$guid]['country'].' '.__('Residency/Visa Type');
        $visaExpiryDateLabel = $_SESSION[$guid]['country'].' '.__('Visa Expiry Date');
    } else {
        $nationalIDCardNumberLabel = __('National ID Card Number');
        $nationalIDCardScanLabel = __('National ID Card Scan');
        $residencyStatusLabel = __('Residency/Visa Type');
        $visaExpiryDateLabel = __('Visa Expiry Date');
    }

    $row = $form->addRow();
        $row->addLabel('nationalIDCardNumber', $nationalIDCardNumberLabel);
        $row->addTextField('nationalIDCardNumber')->maxLength(30);

    $row = $form->addRow();
        $row->addLabel('nationalIDCardScan', $nationalIDCardScanLabel)->description(__('Less than 1440px by 900px').'. '.__('Accepts PDF files.'));
        $row->addFileUpload('nationalIDCardScan')->accepts('.jpg,.jpeg,.gif,.png,.pdf')->setMaxUpload(false);

    $residencyStatusList = getSettingByScope($connection2, 'User Admin', 'residencyStatus');

    $row = $form->addRow();
        $row->addLabel('residencyStatus', $residencyStatusLabel);
        if (!empty($residencyStatusList)) {
            $row->addSelect('residencyStatus')->fromString($residencyStatusList)->placeholder();
        } else {
            $row->addTextField('residencyStatus')->maxLength(30);
        }

    $row = $form->addRow();
        $row->addLabel('visaExpiryDate', $visaExpiryDateLabel)->description(__('If relevant.'));
        $row->addDate('visaExpiryDate');

    // EMPLOYMENT
    $form->addRow()->addHeading(__('Employment'));

    $row = $form->addRow();
        $row->addLabel('profession', __('Profession'));
        $row->addTextField('profession')->maxLength(30);

    $row = $form->addRow();
        $row->addLabel('employer', __('Employer'));
        $row->addTextField('employer')->maxLength(30);

    $row = $form->addRow();
        $row->addLabel('jobTitle', __('Job Title'));
        $row->addTextField('jobTitle')->maxLength(30);

    // EMERGENCY CONTACTS
    $form->addRow()->addHeading(__('Emergency Contacts'));

    $form->addRow()->addContent(__('These details are used when immediate family members (e.g. parent, spouse) cannot be reached first. Please try to avoid listing immediate family members.'));

    $row = $form->addRow();
        $row->addLabel('emergency1Name', __('Contact 1 Name'));
        $row->addTextField('emergency1Name')->maxLength(30);

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
        $row->addTextField('emergency2Name')->maxLength(30);

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
    $form->addRow()->addHeading(__('Miscellaneous'));

    $sql = "SELECT gibbonHouseID as value, name FROM gibbonHouse ORDER BY name";
    $row = $form->addRow();
        $row->addLabel('gibbonHouseID', __('House'));
        $row->addSelect('gibbonHouseID')->fromQuery($pdo, $sql)->placeholder();

    $row = $form->addRow();
        $row->addLabel('studentID', __('Student ID'))->description(__('Must be unique if set.'));
        $row->addTextField('studentID')->maxLength(10);

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

    $privacySetting = getSettingByScope($connection2, 'User Admin', 'privacy');
        $privacyBlurb = getSettingByScope($connection2, 'User Admin', 'privacyBlurb');
        $privacyOptions = getSettingByScope($connection2, 'User Admin', 'privacyOptions');

    if ($privacySetting == 'Y' && !empty($privacyBlurb) && !empty($privacyOptions)) {
        $options = array_map(function($item) { return trim($item); }, explode(',', $privacyOptions));

        $row = $form->addRow();
            $row->addLabel('privacyOptions[]', __('Privacy'))->description($privacyBlurb);
            $row->addCheckbox('privacyOptions[]')->fromArray($options);
    }

    $studentAgreementOptions = getSettingByScope($connection2, 'School Admin', 'studentAgreementOptions');
    if (!empty($studentAgreementOptions)) {
        $options = array_map(function($item) { return trim($item); }, explode(',', $studentAgreementOptions));

        $row = $form->addRow();
        $row->addLabel('studentAgreements[]', __('Student Agreements'))->description(__('Check to indicate that student has signed the relevant agreement.'));
        $row->addCheckbox('studentAgreements[]')->fromArray($options);
    }

    $row = $form->addRow();
        $row->addFooter()->append('<small>'.getMaxUpload($guid, true).'</small>');
        $row->addSubmit();

    echo $form->getOutput();
    ?>

    <script type="text/javascript">
        // Username Generation
        $(".generateUsername").click(function(){
            $.ajax({
                type : 'POST',
                data : {
                    gibbonRoleID: $('#gibbonRoleIDPrimary').val(),
                    preferredName: $('#preferredName').val(),
                    firstName: $('#firstName').val(),
                    surname: $('#surname').val(),
                },
                url: "./modules/User Admin/user_manage_usernameAjax.php",
                success: function(responseText){
                    if (responseText == 0) {
                        $('#gibbonRoleIDPrimary').change();
                        $('#preferredName').blur();
                        $('#firstName').blur();
                        $('#surname').blur();
                        alert("<?php
                            echo __('The following fields are required to generate a username:').'\n\n';
                            echo __('Primary Role').', '.__('Preferred Name').', '.__('First Name').', '.__('Surname').'\n';
                        ?>");
                    } else {
                        $('#username').val(responseText);
                        $('#username').trigger('input');
                        $('#username').blur();
                    }
                }
            });
        });
    </script>
    <?php
}
