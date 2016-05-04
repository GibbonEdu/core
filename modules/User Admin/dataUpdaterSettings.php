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

@session_start();

if (isActionAccessible($guid, $connection2, '/modules/User Admin/applicationFormSettings.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Data Updater Settings').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }
    ?>
	
	<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/dataUpdaterSettingsProcess.php' ?>">
		<h2><?php echo __($guid, 'Required Fields for Personal Updates') ?></h2>
		<p><?php echo __($guid, 'These required field settings apply to all users, except those who hold the ability to submit a data update request for all users in the system (generally just admins).') ?></p>
		<?php

        //Get setting and unserialize
        $required = unserialize(getSettingByScope($connection2, 'User Admin', 'personalDataUpdaterRequiredFields'));

    echo "<table cellspacing='0' style='width: 100%'>";
    echo "<tr class='head'>";
    echo '<th>';
    echo __($guid, 'Field');
    echo '</th>';
    echo '<th>';
    echo __($guid, 'Required');
    echo '</th>';
    echo '</tr>';

    $rowNum = 'even';

            //COLOR ROW BY STATUS!
            echo "<tr class='odd'>";
    echo '<td>';
    echo __($guid, 'Title');
    echo '</td>';
    echo '<td>';
    $checked = '';
    if (isset($required['title'])) {
        if (is_array($required) and $required['title'] == 'Y') {
            $checked = 'checked';
        }
    }
    echo "<input $checked type='checkbox' name='title'>";
    echo '</td>';
    echo '</tr>';
    echo "<tr class='even'>";
    echo '<td>';
    echo __($guid, 'Surname');
    echo '</td>';
    echo '<td>';
    $checked = '';
    if (isset($required['surname'])) {
        if (is_array($required) and $required['surname'] == 'Y') {
            $checked = 'checked';
        }
    }
    echo "<input $checked type='checkbox' name='surname'>";
    echo '</td>';
    echo '</tr>';
    echo "<tr class='odd'>";
    echo '<td>';
    echo __($guid, 'First Name');
    echo '</td>';
    echo '<td>';
    $checked = '';
    if (isset($required['firstName'])) {
        if (is_array($required) and $required['firstName'] == 'Y') {
            $checked = 'checked';
        }
    }
    echo "<input $checked type='checkbox' name='firstName'>";
    echo '</td>';
    echo '</tr>';
    echo "<tr class='odd'>";
    echo '<td>';
    echo __($guid, 'Preferred Names');
    echo '</td>';
    echo '<td>';
    $checked = '';
    if (isset($required['preferredName'])) {
        if (is_array($required) and $required['preferredName'] == 'Y') {
            $checked = 'checked';
        }
    }
    echo "<input $checked type='checkbox' name='preferredName'>";
    echo '</td>';
    echo '</tr>';
    echo "<tr class='even'>";
    echo '<td>';
    echo __($guid, 'Official Name');
    echo '</td>';
    echo '<td>';
    $checked = '';
    if (isset($required['officialName'])) {
        if (is_array($required) and $required['officialName'] == 'Y') {
            $checked = 'checked';
        }
    }
    echo "<input $checked type='checkbox' name='officialName'>";
    echo '</td>';
    echo '</tr>';
    echo "<tr class='odd'>";
    echo '<td>';
    echo __($guid, 'Name In Characters');
    echo '</td>';
    echo '<td>';
    $checked = '';
    if (isset($required['nameInCharacters'])) {
        if (is_array($required) and $required['nameInCharacters'] == 'Y') {
            $checked = 'checked';
        }
    }
    echo "<input $checked type='checkbox' name='nameInCharacters'>";
    echo '</td>';
    echo '</tr>';
    echo "<tr class='even'>";
    echo '<td>';
    echo __($guid, 'Date of Birth');
    echo '</td>';
    echo '<td>';
    $checked = '';
    if (isset($required['dob'])) {
        if (is_array($required) and $required['dob'] == 'Y') {
            $checked = 'checked';
        }
    }
    echo "<input $checked type='checkbox' name='dob'>";
    echo '</td>';
    echo '</tr>';
    echo "<tr class='odd'>";
    echo '<td>';
    echo __($guid, 'Email');
    echo '</td>';
    echo '<td>';
    $checked = '';
    if (isset($required['email'])) {
        if (is_array($required) and $required['email'] == 'Y') {
            $checked = 'checked';
        }
    }
    echo "<input $checked type='checkbox' name='email'>";
    echo '</td>';
    echo '</tr>';
    echo "<tr class='even'>";
    echo '<td>';
    echo __($guid, 'Alternate Email');
    echo '</td>';
    echo '<td>';
    $checked = '';
    if (isset($required['emailAlternate'])) {
        if (is_array($required) and $required['emailAlternate'] == 'Y') {
            $checked = 'checked';
        }
    }
    echo "<input $checked type='checkbox' name='emailAlternate'>";
    echo '</td>';
    echo '</tr>';
    echo "<tr class='odd'>";
    echo '<td>';
    echo __($guid, 'Address 1');
    echo '</td>';
    echo '<td>';
    echo "<input disabled='disabled' type='checkbox' name='address1'> <i>".__($guid, 'This field cannot be required').'</i>.';
    echo '</td>';
    echo '</tr>';
    echo "<tr class='even'>";
    echo '<td>';
    echo __($guid, 'Address 1 District');
    echo '</td>';
    echo '<td>';
    echo "<input disabled='disabled' type='checkbox' name='address1District'> <i>".__($guid, 'This field cannot be required').'</i>.';
    echo '</td>';
    echo '</tr>';
    echo "<tr class='odd'>";
    echo '<td>';
    echo __($guid, 'Address 1 Country');
    echo '</td>';
    echo '<td>';
    echo "<input disabled='disabled' type='checkbox' name='address1Country'> <i>".__($guid, 'This field cannot be required').'</i>.';
    echo '</td>';
    echo '</tr>';
    echo "<tr class='even'>";
    echo '<td>';
    echo __($guid, 'Address 2');
    echo '</td>';
    echo '<td>';
    echo "<input disabled='disabled' type='checkbox' name='address2'> <i>".__($guid, 'This field cannot be required').'</i>.';
    echo '</td>';
    echo '</tr>';
    echo "<tr class='odd'>";
    echo '<td>';
    echo __($guid, 'Address 2 District');
    echo '</td>';
    echo '<td>';
    echo "<input disabled='disabled' type='checkbox' name='address2District'> <i>".__($guid, 'This field cannot be required').'</i>.';
    echo '</td>';
    echo '</tr>';
    echo "<tr class='even'>";
    echo '<td>';
    echo __($guid, 'Address 2 Country');
    echo '</td>';
    echo '<td>';
    echo "<input disabled='disabled' type='checkbox' name='address2Country'> <i>".__($guid, 'This field cannot be required').'</i>.';
    echo '</td>';
    echo '</tr>';
    $phoneCount = 0;
    for ($i = 1; $i < 5; ++$i) {
        ++$phoneCount;
        $class = 'odd';
        if ($phoneCount % 2 == 0) {
            $class = 'even';
        }
        echo "<tr class='$class'>";
        echo '<td>';
        echo sprintf(__($guid, 'Phone %1$s'), $i);
        echo '</td>';
        echo '<td>';
        $checked = '';
        if (isset($required['phone'.$i])) {
            if (is_array($required) and $required['phone'.$i] == 'Y') {
                $checked = 'checked';
            }
        }
        echo "<input $checked type='checkbox' name='phone".$i."'>";
        echo '</td>';
        echo '</tr>';
    }
    echo "<tr class='odd'>";
    echo '<td>';
    echo __($guid, 'First Language');
    echo '</td>';
    echo '<td>';
    $checked = '';
    if (isset($required['languageFirst'])) {
        if (is_array($required) and $required['languageFirst'] == 'Y') {
            $checked = 'checked';
        }
    }
    echo "<input $checked type='checkbox' name='languageFirst'>";
    echo '</td>';
    echo '</tr>';
    echo "<tr class='even'>";
    echo '<td>';
    echo __($guid, 'Second Language');
    echo '</td>';
    echo '<td>';
    $checked = '';
    if (isset($required['languageSecond'])) {
        if (is_array($required) and $required['languageSecond'] == 'Y') {
            $checked = 'checked';
        }
    }
    echo "<input $checked type='checkbox' name='languageSecond'>";
    echo '</td>';
    echo '</tr>';
    echo "<tr class='odd'>";
    echo '<td>';
    echo __($guid, 'Third Language');
    echo '</td>';
    echo '<td>';
    $checked = '';
    if (isset($required['languageThird'])) {
        if (is_array($required) and $required['languageThird'] == 'Y') {
            $checked = 'checked';
        }
    }
    echo "<input $checked type='checkbox' name='languageThird'>";
    echo '</td>';
    echo '</tr>';

    echo "<tr class='even'>";
    echo '<td>';
    echo __($guid, 'Country of Birth');
    echo '</td>';
    echo '<td>';
    $checked = '';
    if (isset($required['countryOfBirth'])) {
        if (is_array($required) and $required['countryOfBirth'] == 'Y') {
            $checked = 'checked';
        }
    }
    echo "<input $checked type='checkbox' name='countryOfBirth'>";
    echo '</td>';
    echo '</tr>';
    echo "<tr class='odd'>";
    echo '<td>';
    echo __($guid, 'Ethnicity');
    echo '</td>';
    echo '<td>';
    $checked = '';
    if (isset($required['ethnicity'])) {
        if (is_array($required) and $required['ethnicity'] == 'Y') {
            $checked = 'checked';
        }
    }
    echo "<input $checked type='checkbox' name='ethnicity'>";
    echo '</td>';
    echo '</tr>';
    echo "<tr class='even'>";
    echo '<td>';
    echo __($guid, 'Citizenship 1');
    echo '</td>';
    echo '<td>';
    $checked = '';
    if (isset($required['citizenship1'])) {
        if (is_array($required) and $required['citizenship1'] == 'Y') {
            $checked = 'checked';
        }
    }
    echo "<input $checked type='checkbox' name='citizenship1'>";
    echo '</td>';
    echo '</tr>';
    echo "<tr class='odd'>";
    echo '<td>';
    echo __($guid, 'Citizenship 1 Passport');
    echo '</td>';
    echo '<td>';
    $checked = '';
    if (isset($required['citizenship1Passport'])) {
        if (is_array($required) and $required['citizenship1Passport'] == 'Y') {
            $checked = 'checked';
        }
    }
    echo "<input $checked type='checkbox' name='citizenship1Passport'>";
    echo '</td>';
    echo '</tr>';
    echo "<tr class='even'>";
    echo '<td>';
    echo __($guid, 'Citizenship 2');
    echo '</td>';
    echo '<td>';
    $checked = '';
    if (isset($required['citizenship2'])) {
        if (is_array($required) and $required['citizenship2'] == 'Y') {
            $checked = 'checked';
        }
    }
    echo "<input $checked type='checkbox' name='citizenship2'>";
    echo '</td>';
    echo '</tr>';
    echo "<tr class='odd'>";
    echo '<td>';
    echo __($guid, 'Citizenship 2 Passport');
    echo '</td>';
    echo '<td>';
    $checked = '';
    if (isset($required['citizenship2Passport'])) {
        if (is_array($required) and $required['citizenship2Passport'] == 'Y') {
            $checked = 'checked';
        }
    }
    echo "<input $checked type='checkbox' name='citizenship2Passport'>";
    echo '</td>';
    echo '</tr>';
    echo "<tr class='even'>";
    echo '<td>';
    echo __($guid, 'Religion');
    echo '</td>';
    echo '<td>';
    $checked = '';
    if (isset($required['religion'])) {
        if (is_array($required) and $required['religion'] == 'Y') {
            $checked = 'checked';
        }
    }
    echo "<input $checked type='checkbox' name='religion'>";
    echo '</td>';
    echo '</tr>';
    echo "<tr class='odd'>";
    echo '<td>';
    echo __($guid, 'National ID Card Number');
    echo '</td>';
    echo '<td>';
    $checked = '';
    if (isset($required['nationalIDCardNumber'])) {
        if (is_array($required) and $required['nationalIDCardNumber'] == 'Y') {
            $checked = 'checked';
        }
    }
    echo "<input $checked type='checkbox' name='nationalIDCardNumber'>";
    echo '</td>';
    echo '</tr>';
    echo "<tr class='even'>";
    echo '<td>';
    echo __($guid, 'Residency Status');
    echo '</td>';
    echo '<td>';
    $checked = '';
    if (isset($required['residencyStatus'])) {
        if (is_array($required) and $required['residencyStatus'] == 'Y') {
            $checked = 'checked';
        }
    }
    echo "<input $checked type='checkbox' name='residencyStatus'>";
    echo '</td>';
    echo '</tr>';
    echo "<tr class='odd'>";
    echo '<td>';
    echo __($guid, 'Visa Expiry Date');
    echo '</td>';
    echo '<td>';
    $checked = '';
    if (isset($required['visaExpiryDate'])) {
        if (is_array($required) and $required['visaExpiryDate'] == 'Y') {
            $checked = 'checked';
        }
    }
    echo "<input $checked type='checkbox' name='visaExpiryDate'>";
    echo '</td>';
    echo '</tr>';

    echo "<tr class='even'>";
    echo '<td>';
    echo __($guid, 'Profession');
    echo '</td>';
    echo '<td>';
    $checked = '';
    if (isset($required['profession'])) {
        if (is_array($required) and $required['profession'] == 'Y') {
            $checked = 'checked';
        }
    }
    echo "<input $checked type='checkbox' name='profession'>";
    echo '</td>';
    echo '</tr>';
    echo "<tr class='odd'>";
    echo '<td>';
    echo __($guid, 'Employer');
    echo '</td>';
    echo '<td>';
    $checked = '';
    if (isset($required['employer'])) {
        if (is_array($required) and $required['employer'] == 'Y') {
            $checked = 'checked';
        }
    }
    echo "<input $checked type='checkbox' name='employer'>";
    echo '</td>';
    echo '</tr>';
    echo "<tr class='even'>";
    echo '<td>';
    echo __($guid, 'Job Title');
    echo '</td>';
    echo '<td>';
    $checked = '';
    if (isset($required['jobTitle'])) {
        if (is_array($required) and $required['jobTitle'] == 'Y') {
            $checked = 'checked';
        }
    }
    echo "<input $checked type='checkbox' name='jobTitle'>";
    echo '</td>';
    echo '</tr>';
    echo "<tr class='odd'>";
    echo '<td>';
    echo __($guid, 'Emergency 1 Name');
    echo '</td>';
    echo '<td>';
    $checked = '';
    if (isset($required['emergency1Name'])) {
        if (is_array($required) and $required['emergency1Name'] == 'Y') {
            $checked = 'checked';
        }
    }
    echo "<input $checked type='checkbox' name='emergency1Name'>";
    echo '</td>';
    echo '</tr>';
    echo "<tr class='even'>";
    echo '<td>';
    echo __($guid, 'Emergency 1 Number 1');
    echo '</td>';
    echo '<td>';
    $checked = '';
    if (isset($required['emergency1Number1'])) {
        if (is_array($required) and $required['emergency1Number1'] == 'Y') {
            $checked = 'checked';
        }
    }
    echo "<input $checked type='checkbox' name='emergency1Number1'>";
    echo '</td>';
    echo '</tr>';
    echo "<tr class='odd'>";
    echo '<td>';
    echo __($guid, 'Emergency 1 Number 2');
    echo '</td>';
    echo '<td>';
    $checked = '';
    if (isset($required['emergency1Number2'])) {
        if (is_array($required) and $required['emergency1Number2'] == 'Y') {
            $checked = 'checked';
        }
    }
    echo "<input $checked type='checkbox' name='emergency1Number2'>";
    echo '</td>';
    echo '</tr>';
    echo "<tr class='even'>";
    echo '<td>';
    echo __($guid, 'Emergency 1 Relationship');
    echo '</td>';
    echo '<td>';
    $checked = '';
    if (isset($required['emergency1Relationship'])) {
        if (is_array($required) and $required['emergency1Relationship'] == 'Y') {
            $checked = 'checked';
        }
    }
    echo "<input $checked type='checkbox' name='emergency1Relationship'>";
    echo '</td>';
    echo '</tr>';
    echo "<tr class='odd'>";
    echo '<td>';
    echo __($guid, 'Emergency 2 Name');
    echo '</td>';
    echo '<td>';
    $checked = '';
    if (isset($required['emergency2Name'])) {
        if (is_array($required) and $required['emergency2Name'] == 'Y') {
            $checked = 'checked';
        }
    }
    echo "<input $checked type='checkbox' name='emergency2Name'>";
    echo '</td>';
    echo '</tr>';
    echo "<tr class='even'>";
    echo '<td>';
    echo __($guid, 'Emergency 2 Number 1');
    echo '</td>';
    echo '<td>';
    $checked = '';
    if (isset($required['emergency2Number1'])) {
        if (is_array($required) and $required['emergency2Number1'] == 'Y') {
            $checked = 'checked';
        }
    }
    echo "<input $checked type='checkbox' name='emergency2Number1'>";
    echo '</td>';
    echo '</tr>';
    echo "<tr class='odd'>";
    echo '<td>';
    echo __($guid, 'Emergency 2 Number 2');
    echo '</td>';
    echo '<td>';
    $checked = '';
    if (isset($required['emergency2Number2'])) {
        if (is_array($required) and $required['emergency2Number2'] == 'Y') {
            $checked = 'checked';
        }
    }
    echo "<input $checked type='checkbox' name='emergency2Number2'>";
    echo '</td>';
    echo '</tr>';
    echo "<tr class='even'>";
    echo '<td>';
    echo __($guid, 'Emergency 2 Relationship');
    echo '</td>';
    echo '<td>';
    $checked = '';
    if (isset($required['emergency2Relationship'])) {
        if (is_array($required) and $required['emergency2Relationship'] == 'Y') {
            $checked = 'checked';
        }
    }
    echo "<input $checked type='checkbox' name='emergency2Relationship'>";
    echo '</td>';
    echo '</tr>';
    echo "<tr class='odd'>";
    echo '<td>';
    echo __($guid, 'Vehicle Registration');
    echo '</td>';
    echo '<td>';
    $checked = '';
    if (isset($required['vehicleRegistration'])) {
        if (is_array($required) and $required['vehicleRegistration'] == 'Y') {
            $checked = 'checked';
        }
    }
    echo "<input $checked type='checkbox' name='vehicleRegistration'>";
    echo '</td>';
    echo '</tr>';
    echo '<tr>';
    echo "<td class='right' colspan=2>";
    echo "<input name='address' type='hidden' value='".$_GET['q']."'>";
    echo "<input type='submit' value='Submit'>";
    echo '</td>';
    echo '</tr>';
    echo '</table>';?>
	</form>
<?php

}
?>