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

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/User Admin/data_personal_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/User Admin/data_personal.php'>".__($guid, 'Personal Data Updates')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Request').'</div>';
    echo '</div>';

    //Check if school year specified
    $gibbonPersonUpdateID = $_GET['gibbonPersonUpdateID'];
    if ($gibbonPersonUpdateID == 'Y') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonPersonUpdateID' => $gibbonPersonUpdateID);
            $sql = 'SELECT gibbonPersonUpdate.gibbonPersonID, gibbonPerson.gibbonRoleIDAll, gibbonPerson.title AS title, gibbonPerson.surname AS surname, gibbonPerson.firstName AS firstName, gibbonPerson.preferredName AS preferredName, gibbonPerson.officialName AS officialName, gibbonPerson.nameInCharacters AS nameInCharacters, gibbonPerson.dob AS dob, gibbonPerson.email AS email, gibbonPerson.emailAlternate AS emailAlternate, gibbonPerson.address1 AS address1, gibbonPerson.address1District AS address1District, gibbonPerson.address1Country AS address1Country, gibbonPerson.address2 AS address2, gibbonPerson.address2District AS address2District, gibbonPerson.address2Country AS address2Country, gibbonPerson.phone1Type AS phone1Type, gibbonPerson.phone1CountryCode AS phone1CountryCode, gibbonPerson.phone1 AS phone1, gibbonPerson.phone2Type AS phone2Type, gibbonPerson.phone2CountryCode AS phone2CountryCode, gibbonPerson.phone2 AS phone2, gibbonPerson.phone3Type AS phone3Type, gibbonPerson.phone3CountryCode AS phone3CountryCode, gibbonPerson.phone3 AS phone3, gibbonPerson.phone4Type AS phone4Type, gibbonPerson.phone4CountryCode AS phone4CountryCode, gibbonPerson.phone4 AS phone4, gibbonPerson.languageFirst AS languageFirst, gibbonPerson.languageSecond AS languageSecond, gibbonPerson.languageThird AS languageThird, gibbonPerson.countryOfBirth AS countryOfBirth, gibbonPerson.ethnicity AS ethnicity, gibbonPerson.citizenship1 AS citizenship1, gibbonPerson.citizenship1Passport AS citizenship1Passport, gibbonPerson.citizenship2 AS citizenship2, gibbonPerson.citizenship2Passport AS citizenship2Passport, gibbonPerson.religion AS religion, gibbonPerson.nationalIDCardNumber AS nationalIDCardNumber, gibbonPerson.residencyStatus AS residencyStatus, gibbonPerson.visaExpiryDate AS visaExpiryDate, gibbonPerson.profession AS profession , gibbonPerson.employer AS employer, gibbonPerson.jobTitle AS jobTitle, gibbonPerson.emergency1Name AS emergency1Name, gibbonPerson.emergency1Number1 AS emergency1Number1, gibbonPerson.emergency1Number2 AS emergency1Number2, gibbonPerson.emergency1Relationship AS emergency1Relationship, gibbonPerson.emergency2Name AS emergency2Name, gibbonPerson.emergency2Number1 AS emergency2Number1, gibbonPerson.emergency2Number2 AS emergency2Number2, gibbonPerson.emergency2Relationship AS emergency2Relationship, gibbonPerson.vehicleRegistration AS vehicleRegistration, gibbonPerson.privacy AS privacy, gibbonPerson.fields AS fields, gibbonPersonUpdate.title AS newtitle, gibbonPersonUpdate.surname AS newsurname, gibbonPersonUpdate.firstName AS newfirstName, gibbonPersonUpdate.preferredName AS newpreferredName, gibbonPersonUpdate.officialName AS newofficialName, gibbonPersonUpdate.nameInCharacters AS newnameInCharacters, gibbonPersonUpdate.dob AS newdob, gibbonPersonUpdate.email AS newemail, gibbonPersonUpdate.emailAlternate AS newemailAlternate, gibbonPersonUpdate.address1 AS newaddress1, gibbonPersonUpdate.address1District AS newaddress1District, gibbonPersonUpdate.address1Country AS newaddress1Country, gibbonPersonUpdate.address2 AS newaddress2, gibbonPersonUpdate.address2District AS newaddress2District, gibbonPersonUpdate.address2Country AS newaddress2Country, gibbonPersonUpdate.phone1Type AS newphone1Type, gibbonPersonUpdate.phone1CountryCode AS newphone1CountryCode, gibbonPersonUpdate.phone1 AS newphone1, gibbonPersonUpdate.phone2Type AS newphone2Type, gibbonPersonUpdate.phone2CountryCode AS newphone2CountryCode, gibbonPersonUpdate.phone2 AS newphone2, gibbonPersonUpdate.phone3Type AS newphone3Type, gibbonPersonUpdate.phone3CountryCode AS newphone3CountryCode, gibbonPersonUpdate.phone3 AS newphone3, gibbonPersonUpdate.phone4Type AS newphone4Type, gibbonPersonUpdate.phone4CountryCode AS newphone4CountryCode, gibbonPersonUpdate.phone4 AS newphone4, gibbonPersonUpdate.languageFirst AS newlanguageFirst, gibbonPersonUpdate.languageSecond AS newlanguageSecond, gibbonPersonUpdate.languageThird AS newlanguageThird, gibbonPersonUpdate.countryOfBirth AS newcountryOfBirth, gibbonPersonUpdate.ethnicity AS newethnicity, gibbonPersonUpdate.citizenship1 AS newcitizenship1, gibbonPersonUpdate.citizenship1Passport AS newcitizenship1Passport, gibbonPersonUpdate.citizenship2 AS newcitizenship2, gibbonPersonUpdate.citizenship2Passport AS newcitizenship2Passport, gibbonPersonUpdate.religion AS newreligion, gibbonPersonUpdate.nationalIDCardNumber AS newnationalIDCardNumber, gibbonPersonUpdate.residencyStatus AS newresidencyStatus, gibbonPersonUpdate.visaExpiryDate AS newvisaExpiryDate, gibbonPersonUpdate.profession AS newprofession , gibbonPersonUpdate.employer AS newemployer, gibbonPersonUpdate.jobTitle AS newjobTitle, gibbonPersonUpdate.emergency1Name AS newemergency1Name, gibbonPersonUpdate.emergency1Number1 AS newemergency1Number1, gibbonPersonUpdate.emergency1Number2 AS newemergency1Number2, gibbonPersonUpdate.emergency1Relationship AS newemergency1Relationship, gibbonPersonUpdate.emergency2Name AS newemergency2Name, gibbonPersonUpdate.emergency2Number1 AS newemergency2Number1, gibbonPersonUpdate.emergency2Number2 AS newemergency2Number2, gibbonPersonUpdate.emergency2Relationship AS newemergency2Relationship, gibbonPersonUpdate.vehicleRegistration AS newvehicleRegistration, gibbonPersonUpdate.privacy AS newprivacy, gibbonPersonUpdate.fields AS newfields FROM gibbonPersonUpdate JOIN gibbonPerson ON (gibbonPersonUpdate.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPersonUpdateID=:gibbonPersonUpdateID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The selected record does not exist, or you do not have access to it.');
            echo '</div>';
        } else {
            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
            }
            //Let's go!
            $row = $result->fetch();

            //Get categories
            $staff = false;
            $student = false;
            $parent = false;
            $other = false;
            $roles = explode(',', $row['gibbonRoleIDAll']);
            foreach ($roles as $role) {
                $roleCategory = getRoleCategory($role, $connection2);
                if ($roleCategory == 'Staff') {
                    $staff = true;
                }
                if ($roleCategory == 'Student') {
                    $student = true;
                }
                if ($roleCategory == 'Parent') {
                    $parent = true;
                }
                if ($roleCategory == 'Other') {
                    $other = true;
                }
            }
            ?>
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/data_personal_editProcess.php?gibbonPersonUpdateID=$gibbonPersonUpdateID" ?>">
				<?php
                echo "<table cellspacing='0' style='width: 100%'>";
            echo "<tr class='head'>";
            echo '<th>';
            echo __($guid, 'Field');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Current Value');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'New Value');
            echo '</th>';
            echo '<th>';
            echo __($guid, 'Accept');
            echo '</th>';
            echo '</tr>';

			//COLOR ROW BY STATUS!
			echo "<tr class='odd'>";
            echo '<td>';
            echo __($guid, 'Title');
            echo '</td>';
            echo '<td>';
            echo $row['title'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['title'] != $row['newtitle']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newtitle'];
            echo '</td>';
            echo '<td>';
            if ($row['title'] != $row['newtitle']) {
                echo "<input checked type='checkbox' name='newtitleOn'><input name='newtitle' type='hidden' value='".htmlprep($row['newtitle'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo '<td>';
            echo __($guid, 'Surname');
            echo '</td>';
            echo '<td>';
            echo $row['surname'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['surname'] != $row['newsurname']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newsurname'];
            echo '</td>';
            echo '<td>';
            if ($row['surname'] != $row['newsurname']) {
                echo "<input checked type='checkbox' name='newsurnameOn'><input name='newsurname' type='hidden' value='".htmlprep($row['newsurname'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo "<tr class='odd'>";
            echo '<td>';
            echo __($guid, 'First Name');
            echo '</td>';
            echo '<td>';
            echo $row['firstName'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['firstName'] != $row['newfirstName']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newfirstName'];
            echo '</span>';
            echo '</td>';
            echo '<td>';
            if ($row['firstName'] != $row['newfirstName']) {
                echo "<input checked type='checkbox' name='newfirstNameOn'><input name='newfirstName' type='hidden' value='".htmlprep($row['newfirstName'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo "<tr class='odd'>";
            echo '<td>';
            echo __($guid, 'Preferred Name');
            echo '</td>';
            echo '<td>';
            echo $row['preferredName'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['preferredName'] != $row['newpreferredName']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newpreferredName'];
            echo '</span>';
            echo '</td>';
            echo '<td>';
            if ($row['preferredName'] != $row['newpreferredName']) {
                echo "<input checked type='checkbox' name='newpreferredNameOn'><input name='newpreferredName' type='hidden' value='".htmlprep($row['newpreferredName'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo '<td>';
            echo __($guid, 'Official Name');
            echo '</td>';
            echo '<td>';
            echo $row['officialName'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['officialName'] != $row['newofficialName']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newofficialName'];
            echo '</span>';
            echo '</td>';
            echo '<td>';
            if ($row['officialName'] != $row['newofficialName']) {
                echo "<input checked type='checkbox' name='newofficialNameOn'><input name='newofficialName' type='hidden' value='".htmlprep($row['newofficialName'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo "<tr class='odd'>";
            echo '<td>';
            echo __($guid, 'Name In Characters');
            echo '</td>';
            echo '<td>';
            echo $row['nameInCharacters'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['nameInCharacters'] != $row['newnameInCharacters']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newnameInCharacters'];
            echo '</span>';
            echo '</td>';
            echo '<td>';
            if ($row['nameInCharacters'] != $row['newnameInCharacters']) {
                echo "<input checked type='checkbox' name='newnameInCharactersOn'><input name='newnameInCharacters' type='hidden' value='".htmlprep($row['newnameInCharacters'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo '<td>';
            echo __($guid, 'Date of Birth');
            echo '</td>';
            echo '<td>';
            echo dateConvertBack($guid, $row['dob']);
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['dob'] != $row['newdob']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo dateConvertBack($guid, $row['newdob']);
            echo '</span>';
            echo '</td>';
            echo '<td>';
            if ($row['dob'] != $row['newdob']) {
                echo "<input checked type='checkbox' name='newdobOn'><input name='newdob' type='hidden' value='".htmlprep($row['newdob'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo "<tr class='odd'>";
            echo '<td>';
            echo __($guid, 'Email');
            echo '</td>';
            echo '<td>';
            echo $row['email'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['email'] != $row['newemail']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newemail'];
            echo '</span>';
            echo '</td>';
            echo '<td>';
            if ($row['email'] != $row['newemail']) {
                echo "<input checked type='checkbox' name='newemailOn'><input name='newemail' type='hidden' value='".htmlprep($row['newemail'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo '<td>';
            echo __($guid, 'Alternate Email');
            echo '</td>';
            echo '<td>';
            echo $row['emailAlternate'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['emailAlternate'] != $row['newemailAlternate']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newemailAlternate'];
            echo '</span>';
            echo '</td>';
            echo '<td>';
            if ($row['emailAlternate'] != $row['newemailAlternate']) {
                echo "<input checked type='checkbox' name='newemailAlternateOn'><input name='newemailAlternate' type='hidden' value='".htmlprep($row['newemailAlternate'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo "<tr class='odd'>";
            echo '<td>';
            echo __($guid, 'Address 1');
            echo '</td>';
            echo '<td>';
            echo $row['address1'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['address1'] != $row['newaddress1']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newaddress1'];
            echo '</span>';
            echo '</td>';
            echo '<td>';
            if ($row['address1'] != $row['newaddress1']) {
                echo "<input checked type='checkbox' name='newaddress1On'><input name='newaddress1' type='hidden' value='".htmlprep($row['newaddress1'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo '<td>';
            echo __($guid, 'Address 1 District');
            echo '</td>';
            echo '<td>';
            echo $row['address1District'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['address1District'] != $row['newaddress1District']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newaddress1District'];
            echo '</span>';
            echo '</td>';
            echo '<td>';
            if ($row['address1District'] != $row['newaddress1District']) {
                echo "<input checked type='checkbox' name='newaddress1DistrictOn'><input name='newaddress1District' type='hidden' value='".htmlprep($row['newaddress1District'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo "<tr class='odd'>";
            echo '<td>';
            echo __($guid, 'Address 1 Country');
            echo '</td>';
            echo '<td>';
            echo $row['address1Country'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['address1Country'] != $row['newaddress1Country']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newaddress1Country'];
            echo '</span>';
            echo '</td>';
            echo '<td>';
            if ($row['address1Country'] != $row['newaddress1Country']) {
                echo "<input checked type='checkbox' name='newaddress1CountryOn'><input name='newaddress1Country' type='hidden' value='".htmlprep($row['newaddress1Country'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo '<td>';
            echo __($guid, 'Address 2');
            echo '</td>';
            echo '<td>';
            echo $row['address2'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['address2'] != $row['newaddress2']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newaddress2'];
            echo '</span>';
            echo '</td>';
            echo '<td>';
            if ($row['address2'] != $row['newaddress2']) {
                echo "<input checked type='checkbox' name='newaddress2On'><input name='newaddress2' type='hidden' value='".htmlprep($row['newaddress2'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo "<tr class='odd'>";
            echo '<td>';
            echo __($guid, 'Address 2 District');
            echo '</td>';
            echo '<td>';
            echo $row['address2District'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['address2District'] != $row['newaddress2District']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newaddress2District'];
            echo '</span>';
            echo '</td>';
            echo '<td>';
            if ($row['address2District'] != $row['newaddress2District']) {
                echo "<input checked type='checkbox' name='newaddress2DistrictOn'><input name='newaddress2District' type='hidden' value='".htmlprep($row['newaddress2District'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo '<td>';
            echo __($guid, 'Address 2 Country');
            echo '</td>';
            echo '<td>';
            echo $row['address2Country'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['address2Country'] != $row['newaddress2Country']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newaddress2Country'];
            echo '</span>';
            echo '</td>';
            echo '<td>';
            if ($row['address2Country'] != $row['newaddress2Country']) {
                echo "<input checked type='checkbox' name='newaddress2CountryOn'><input name='newaddress2Country' type='hidden' value='".htmlprep($row['newaddress2Country'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            $phoneCount = 0;
            for ($i = 1; $i < 5; ++$i) {
                ++$phoneCount;
                $class = 'odd';
                echo "<tr class='$class'>";
                echo '<td>';
                echo sprintf(__($guid, 'Phone %1$s Type'), $i);
                echo '</td>';
                echo '<td>';
                echo $row['phone'.$i.'Type'];
                echo '</td>';
                echo '<td>';
                $style = '';
                if ($row['phone'.$i.'Type'] != $row['newphone'.$i.'Type']) {
                    $style = "style='color: #ff0000'";
                }
                echo "<span $style>";
                echo $row['newphone'.$i.'Type'];
                echo '</span>';
                echo '</td>';
                echo '<td>';
                if ($row['phone'.$i.'Type'] != $row['newphone'.$i.'Type']) {
                    echo "<input checked type='checkbox' name='newphone".$i."TypeOn'><input name='newphone".$i."Type' type='hidden' value='".htmlprep($row['newphone'.$i.'Type'])."'>";
                }
                echo '</td>';
                echo '</tr>';
                ++$phoneCount;
                echo "<tr class='$class'>";
                echo '<td>';
                echo sprintf(__($guid, 'Phone %1$s Country Code'), $i);
                echo '</td>';
                echo '<td>';
                echo $row['phone'.$i.'CountryCode'];
                echo '</td>';
                echo '<td>';
                $style = '';
                if ($row['phone'.$i.'CountryCode'] != $row['newphone'.$i.'CountryCode']) {
                    $style = "style='color: #ff0000'";
                }
                echo "<span $style>";
                echo $row['newphone'.$i.'CountryCode'];
                echo '</span>';
                echo '</td>';
                echo '<td>';
                if ($row['phone'.$i.'CountryCode'] != $row['newphone'.$i.'CountryCode']) {
                    echo "<input checked type='checkbox' name='newphone".$i."CountryCodeOn'><input name='newphone".$i."CountryCode' type='hidden' value='".htmlprep($row['newphone'.$i.'CountryCode'])."'>";
                }
                echo '</td>';
                echo '</tr>';
                ++$phoneCount;
                echo "<tr class='$class'>";
                echo '<td>';
                echo __($guid, 'Phone').' '.$i;
                echo '</td>';
                echo '<td>';
                echo $row['phone'.$i];
                echo '</td>';
                echo '<td>';
                $style = '';
                if ($row['phone'.$i] != $row['newphone'.$i]) {
                    $style = "style='color: #ff0000'";
                }
                echo "<span $style>";
                echo $row['newphone'.$i];
                echo '</span>';
                echo '</td>';
                echo '<td>';
                if ($row['phone'.$i] != $row['newphone'.$i]) {
                    echo "<input checked type='checkbox' name='newphone".$i."On'><input name='newphone".$i."' type='hidden' value='".htmlprep($row['newphone'.$i])."'>";
                }
                echo '</td>';
                echo '</tr>';
            }
            echo "<tr class='odd'>";
            echo '<td>';
            echo __($guid, 'First Language');
            echo '</td>';
            echo '<td>';
            echo $row['languageFirst'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['languageFirst'] != $row['newlanguageFirst']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newlanguageFirst'];
            echo '</span>';
            echo '</td>';
            echo '<td>';
            if ($row['languageFirst'] != $row['newlanguageFirst']) {
                echo "<input checked type='checkbox' name='newlanguageFirstOn'><input name='newlanguageFirst' type='hidden' value='".htmlprep($row['newlanguageFirst'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo '<td>';
            echo __($guid, 'Second Language');
            echo '</td>';
            echo '<td>';
            echo $row['languageSecond'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['languageSecond'] != $row['newlanguageSecond']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newlanguageSecond'];
            echo '</span>';
            echo '</td>';
            echo '<td>';
            if ($row['languageSecond'] != $row['newlanguageSecond']) {
                echo "<input checked type='checkbox' name='newlanguageSecondOn'><input name='newlanguageSecond' type='hidden' value='".htmlprep($row['newlanguageSecond'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo "<tr class='odd'>";
            echo '<td>';
            echo __($guid, 'Third Language');
            echo '</td>';
            echo '<td>';
            echo $row['languageThird'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['languageThird'] != $row['newlanguageThird']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newlanguageThird'];
            echo '</span>';
            echo '</td>';
            echo '<td>';
            if ($row['languageThird'] != $row['newlanguageThird']) {
                echo "<input checked type='checkbox' name='newlanguageThirdOn'><input name='newlanguageThird' type='hidden' value='".htmlprep($row['newlanguageThird'])."'>";
            }
            echo '</td>';
            echo '</tr>';

            echo '<tr>';
            echo '<td>';
            echo __($guid, 'Country of Birth');
            echo '</td>';
            echo '<td>';
            echo $row['countryOfBirth'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['countryOfBirth'] != $row['newcountryOfBirth']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newcountryOfBirth'];
            echo '</span>';
            echo '</td>';
            echo '<td>';
            if ($row['countryOfBirth'] != $row['newcountryOfBirth']) {
                echo "<input checked type='checkbox' name='newcountryOfBirthOn'><input name='newcountryOfBirth' type='hidden' value='".htmlprep($row['newcountryOfBirth'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo "<tr class='odd'>";
            echo '<td>';
            echo __($guid, 'Ethnicity');
            echo '</td>';
            echo '<td>';
            echo $row['ethnicity'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['ethnicity'] != $row['newethnicity']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newethnicity'];
            echo '</span>';
            echo '</td>';
            echo '<td>';
            if ($row['ethnicity'] != $row['newethnicity']) {
                echo "<input checked type='checkbox' name='newethnicityOn'><input name='newethnicity' type='hidden' value='".htmlprep($row['newethnicity'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo '<td>';
            echo __($guid, 'Citizenship 1');
            echo '</td>';
            echo '<td>';
            echo $row['citizenship1'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['citizenship1'] != $row['newcitizenship1']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newcitizenship1'];
            echo '</span>';
            echo '</td>';
            echo '<td>';
            if ($row['citizenship1'] != $row['newcitizenship1']) {
                echo "<input checked type='checkbox' name='newcitizenship1On'><input name='newcitizenship1' type='hidden' value='".htmlprep($row['newcitizenship1'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo "<tr class='odd'>";
            echo '<td>';
            echo __($guid, 'Citizenship 1 Passport');
            echo '</td>';
            echo '<td>';
            echo $row['citizenship1Passport'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['citizenship1Passport'] != $row['newcitizenship1Passport']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newcitizenship1Passport'];
            echo '</span>';
            echo '</td>';
            echo '<td>';
            if ($row['citizenship1Passport'] != $row['newcitizenship1Passport']) {
                echo "<input checked type='checkbox' name='newcitizenship1PassportOn'><input name='newcitizenship1Passport' type='hidden' value='".htmlprep($row['newcitizenship1Passport'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo '<td>';
            echo __($guid, 'Citizenship 2');
            echo '</td>';
            echo '<td>';
            echo $row['citizenship2'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['citizenship2'] != $row['newcitizenship2']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newcitizenship2'];
            echo '</span>';
            echo '</td>';
            echo '<td>';
            if ($row['citizenship2'] != $row['newcitizenship2']) {
                echo "<input checked type='checkbox' name='newcitizenship2On'><input name='newcitizenship2' type='hidden' value='".htmlprep($row['newcitizenship2'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo "<tr class='odd'>";
            echo '<td>';
            echo __($guid, 'Citizenship 2 Passport');
            echo '</td>';
            echo '<td>';
            echo $row['citizenship2Passport'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['citizenship2Passport'] != $row['newcitizenship2Passport']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newcitizenship2Passport'];
            echo '</span>';
            echo '</td>';
            echo '<td>';
            if ($row['citizenship2Passport'] != $row['newcitizenship2Passport']) {
                echo "<input checked type='checkbox' name='newcitizenship2PassportOn'><input name='newcitizenship2Passport' type='hidden' value='".htmlprep($row['newcitizenship2Passport'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo '<td>';
            echo __($guid, 'Religion');
            echo '</td>';
            echo '<td>';
            echo $row['religion'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['religion'] != $row['newreligion']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newreligion'];
            echo '</span>';
            echo '</td>';
            echo '<td>';
            if ($row['religion'] != $row['newreligion']) {
                echo "<input checked type='checkbox' name='newreligionOn'><input name='newreligion' type='hidden' value='".htmlprep($row['newreligion'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo "<tr class='odd'>";
            echo '<td>';
            echo __($guid, 'National ID Card Number');
            echo '</td>';
            echo '<td>';
            echo $row['nationalIDCardNumber'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['nationalIDCardNumber'] != $row['newnationalIDCardNumber']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newnationalIDCardNumber'];
            echo '</span>';
            echo '</td>';
            echo '<td>';
            if ($row['nationalIDCardNumber'] != $row['newnationalIDCardNumber']) {
                echo "<input checked type='checkbox' name='newnationalIDCardNumberOn'><input name='newnationalIDCardNumber' type='hidden' value='".htmlprep($row['newnationalIDCardNumber'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo '<td>';
            echo __($guid, 'Residency Status');
            echo '</td>';
            echo '<td>';
            echo $row['residencyStatus'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['residencyStatus'] != $row['newresidencyStatus']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newresidencyStatus'];
            echo '</span>';
            echo '</td>';
            echo '<td>';
            if ($row['residencyStatus'] != $row['newresidencyStatus']) {
                echo "<input checked type='checkbox' name='newresidencyStatusOn'><input name='newresidencyStatus' type='hidden' value='".htmlprep($row['newresidencyStatus'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo "<tr class='odd'>";
            echo '<td>';
            echo __($guid, 'Visa Expiry Date');
            echo '</td>';
            echo '<td>';
            echo dateConvertBack($guid, $row['visaExpiryDate']);
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['visaExpiryDate'] != $row['newvisaExpiryDate']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo dateConvertBack($guid, $row['newvisaExpiryDate']);
            echo '</span>';
            echo '</td>';
            echo '<td>';
            if ($row['visaExpiryDate'] != $row['newvisaExpiryDate']) {
                echo "<input checked type='checkbox' name='newvisaExpiryDateOn'><input name='newvisaExpiryDate' type='hidden' value='".htmlprep($row['newvisaExpiryDate'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo '<td>';
            echo __($guid, 'Profession');
            echo '</td>';
            echo '<td>';
            echo $row['profession'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['profession'] != $row['newprofession']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newprofession'];
            echo '</span>';
            echo '</td>';
            echo '<td>';
            if ($row['profession'] != $row['newprofession']) {
                echo "<input checked type='checkbox' name='newprofessionOn'><input name='newprofession' type='hidden' value='".htmlprep($row['newprofession'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo "<tr class='odd'>";
            echo '<td>';
            echo __($guid, 'Employer');
            echo '</td>';
            echo '<td>';
            echo $row['employer'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['employer'] != $row['newemployer']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newemployer'];
            echo '</span>';
            echo '</td>';
            echo '<td>';
            if ($row['employer'] != $row['newemployer']) {
                echo "<input checked type='checkbox' name='newemployerOn'><input name='newemployer' type='hidden' value='".htmlprep($row['newemployer'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo '<td>';
            echo __($guid, 'Job Title');
            echo '</td>';
            echo '<td>';
            echo $row['jobTitle'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['jobTitle'] != $row['newjobTitle']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newjobTitle'];
            echo '</span>';
            echo '</td>';
            echo '<td>';
            if ($row['jobTitle'] != $row['newjobTitle']) {
                echo "<input checked type='checkbox' name='newjobTitleOn'><input name='newjobTitle' type='hidden' value='".htmlprep($row['newjobTitle'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo "<tr class='odd'>";
            echo '<td>';
            echo __($guid, 'Emergency 1 Name');
            echo '</td>';
            echo '<td>';
            echo $row['emergency1Name'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['emergency1Name'] != $row['newemergency1Name']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newemergency1Name'];
            echo '</span>';
            echo '</td>';
            echo '<td>';
            if ($row['emergency1Name'] != $row['newemergency1Name']) {
                echo "<input checked type='checkbox' name='newemergency1NameOn'><input name='newemergency1Name' type='hidden' value='".htmlprep($row['newemergency1Name'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo '<td>';
            echo __($guid, 'Emergency 1 Number 1');
            echo '</td>';
            echo '<td>';
            echo $row['emergency1Number1'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['emergency1Number1'] != $row['newemergency1Number1']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newemergency1Number1'];
            echo '</span>';
            echo '</td>';
            echo '<td>';
            if ($row['emergency1Number1'] != $row['newemergency1Number1']) {
                echo "<input checked type='checkbox' name='newemergency1Number1On'><input name='newemergency1Number1' type='hidden' value='".htmlprep($row['newemergency1Number1'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo "<tr class='odd'>";
            echo '<td>';
            echo __($guid, 'Emergency 1 Number 2');
            echo '</td>';
            echo '<td>';
            echo $row['emergency1Number2'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['emergency1Number2'] != $row['newemergency1Number2']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newemergency1Number2'];
            echo '</span>';
            echo '</td>';
            echo '<td>';
            if ($row['emergency1Number2'] != $row['newemergency1Number2']) {
                echo "<input checked type='checkbox' name='newemergency1Number2On'><input name='newemergency1Number2' type='hidden' value='".htmlprep($row['newemergency1Number2'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo '<td>';
            echo __($guid, 'Emergency 1 Relationship');
            echo '</td>';
            echo '<td>';
            echo $row['emergency1Relationship'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['emergency1Relationship'] != $row['newemergency1Relationship']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newemergency1Relationship'];
            echo '</span>';
            echo '</td>';
            echo '<td>';
            if ($row['emergency1Relationship'] != $row['newemergency1Relationship']) {
                echo "<input checked type='checkbox' name='newemergency1RelationshipOn'><input name='newemergency1Relationship' type='hidden' value='".htmlprep($row['newemergency1Relationship'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo "<tr class='odd'>";
            echo '<td>';
            echo __($guid, 'Emergency 2 Name');
            echo '</td>';
            echo '<td>';
            echo $row['emergency2Name'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['emergency2Name'] != $row['newemergency2Name']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newemergency2Name'];
            echo '</span>';
            echo '</td>';
            echo '<td>';
            if ($row['emergency2Name'] != $row['newemergency2Name']) {
                echo "<input checked type='checkbox' name='newemergency2NameOn'><input name='newemergency2Name' type='hidden' value='".htmlprep($row['newemergency2Name'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo '<td>';
            echo __($guid, 'Emergency 2 Number 1');
            echo '</td>';
            echo '<td>';
            echo $row['emergency2Number1'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['emergency2Number1'] != $row['newemergency2Number1']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newemergency2Number1'];
            echo '</span>';
            echo '</td>';
            echo '<td>';
            if ($row['emergency2Number1'] != $row['newemergency2Number1']) {
                echo "<input checked type='checkbox' name='newemergency2Number1On'><input name='newemergency2Number1' type='hidden' value='".htmlprep($row['newemergency2Number1'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo "<tr class='odd'>";
            echo '<td>';
            echo __($guid, 'Emergency 2 Number 2');
            echo '</td>';
            echo '<td>';
            echo $row['emergency2Number2'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['emergency2Number2'] != $row['newemergency2Number2']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newemergency2Number2'];
            echo '</span>';
            echo '</td>';
            echo '<td>';
            if ($row['emergency2Number2'] != $row['newemergency2Number2']) {
                echo "<input checked type='checkbox' name='newemergency2Number2On'><input name='newemergency2Number2' type='hidden' value='".htmlprep($row['newemergency2Number2'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo '<tr>';
            echo '<td>';
            echo __($guid, 'Emergency 2 Relationship');
            echo '</td>';
            echo '<td>';
            echo $row['emergency2Relationship'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['emergency2Relationship'] != $row['newemergency2Relationship']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newemergency2Relationship'];
            echo '</span>';
            echo '</td>';
            echo '<td>';
            if ($row['emergency2Relationship'] != $row['newemergency2Relationship']) {
                echo "<input checked type='checkbox' name='newemergency2RelationshipOn'><input name='newemergency2Relationship' type='hidden' value='".htmlprep($row['newemergency2Relationship'])."'>";
            }
            echo '</td>';
            echo '</tr>';
            echo "<tr class='odd'>";
            echo '<td>';
            echo __($guid, 'Vehicle Registration');
            echo '</td>';
            echo '<td>';
            echo $row['vehicleRegistration'];
            echo '</td>';
            echo '<td>';
            $style = '';
            if ($row['vehicleRegistration'] != $row['newvehicleRegistration']) {
                $style = "style='color: #ff0000'";
            }
            echo "<span $style>";
            echo $row['newvehicleRegistration'];
            echo '</span>';
            echo '</td>';
            echo '<td>';
            if ($row['vehicleRegistration'] != $row['newvehicleRegistration']) {
                echo "<input checked type='checkbox' name='newvehicleRegistrationOn'><input name='newvehicleRegistration' type='hidden' value='".htmlprep($row['newvehicleRegistration'])."'>";
            }
            echo '</td>';
            echo '</tr>';
			//Check if any roles are "Student"
			$privacySet = false;
            if ($student) {
                $privacySetting = getSettingByScope($connection2, 'User Admin', 'privacy');
                $privacyBlurb = getSettingByScope($connection2, 'User Admin', 'privacyBlurb');
                if ($privacySetting == 'Y' and $privacyBlurb != '') {
                    echo '<tr>';
                    echo '<td>';
                    echo __($guid, 'Image Privacy');
                    echo '</td>';
                    echo '<td>';
                    echo $row['privacy'];
                    echo '</td>';
                    echo '<td>';
                    $style = '';
                    if ($row['privacy'] != $row['newprivacy']) {
                        $style = "style='color: #ff0000'";
                    }
                    echo "<span $style>";
                    echo $row['newprivacy'];
                    echo '</span>';
                    echo '</td>';
                    echo '<td>';
                    if ($row['privacy'] != $row['newprivacy']) {
                        echo "<input checked type='checkbox' name='newprivacyOn'><input name='newprivacy' type='hidden' value='".htmlprep($row['newprivacy'])."'>";
                    }
                    echo '</td>';
                    echo '</tr>';
                    $privacySet = true;
                }
            }
            if ($privacySet == false) {
                echo '<input type="hidden" name="newprivacyOn" value="">';
            }

			//CUSTOM FIELDS
			$fields = unserialize($row['fields']);
            $newfields = unserialize($row['newfields']);
            $resultFields = getCustomFields($connection2, $guid, $student, $staff, $parent, $other, null, true);
            if ($resultFields->rowCount() > 0) {
                while ($rowFields = $resultFields->fetch()) {
                    echo "<tr class='odd'>";
                    echo '<td>';
                    echo __($guid, $rowFields['name']);
                    echo '</td>';
                    echo '<td>';
                    $current = '';
                    if (isset($fields[$rowFields['gibbonPersonFieldID']])) {
                        $current = $fields[$rowFields['gibbonPersonFieldID']];
                        if ($rowFields['type'] == 'date') {
                            echo dateConvertBack($guid, $current);
                        } else {
                            echo $current;
                        }
                    }
                    echo '</td>';
                    echo '<td>';
                    if (isset($newfields[$rowFields['gibbonPersonFieldID']])) {
                        $style = '';
                        if ($current != $newfields[$rowFields['gibbonPersonFieldID']]) {
                            $style = "style='color: #ff0000'";
                        }
                        echo "<span $style>";
                        if ($rowFields['type'] == 'date') {
                            echo dateConvertBack($guid, $newfields[$rowFields['gibbonPersonFieldID']]);
                        } else {
                            echo $newfields[$rowFields['gibbonPersonFieldID']];
                        }
                        echo '</span>';
                    }
                    echo '</td>';
                    echo '<td>';
                    if (isset($newfields[$rowFields['gibbonPersonFieldID']])) {
                        if ($current != $newfields[$rowFields['gibbonPersonFieldID']]) {
                            echo "<input checked type='checkbox' name='newcustom".$rowFields['gibbonPersonFieldID']."On'><input name='newcustom".$rowFields['gibbonPersonFieldID']."' type='hidden' value='".htmlprep($newfields[$rowFields['gibbonPersonFieldID']])."'>";
                        }
                    }
                    echo '</td>';
                    echo '</tr>';
                }
            }

            echo '<tr>';
            echo "<td class='right' colspan=4>";
            echo "<input name='gibbonPersonID' type='hidden' value='".$row['gibbonPersonID']."'>";
            echo "<input name='address' type='hidden' value='".$_GET['q']."'>";
            echo "<input type='submit' value='Submit'>";
            echo '</td>';
            echo '</tr>';
            echo '</table>'; ?>
			</form>
			<?php

        }
    }
}
?>