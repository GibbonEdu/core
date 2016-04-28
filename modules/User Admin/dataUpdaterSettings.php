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

@session_start() ;

if (isActionAccessible($guid, $connection2, "/modules/User Admin/applicationFormSettings.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Data Updater Settings') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["return"])) { returnProcess($guid, $_GET["return"], null, null); }
	?>
	
	<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/dataUpdaterSettingsProcess.php" ?>">
		<h2><?php print __($guid, 'Required Fields for Personal Updates') ?></h2>
		<p><?php print __($guid, 'These required field settings apply to all users, except those who hold the ability to submit a data update request for all users in the system (generally just admins).') ?></p>
		<?php
		
		//Get setting and unserialize
		$required=unserialize(getSettingByScope( $connection2, "User Admin", "personalDataUpdaterRequiredFields")) ;
		
		print "<table cellspacing='0' style='width: 100%'>" ;
			print "<tr class='head'>" ;
				print "<th>" ;
					print __($guid, "Field") ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Required") ;
				print "</th>" ;
			print "</tr>" ;
			
			$rowNum="even" ;
				
			//COLOR ROW BY STATUS!
			print "<tr class='odd'>" ;
				print "<td>" ;
					print __($guid, "Title") ;
				print "</td>" ;
				print "<td>" ;
					$checked="" ;
					if (isset($required["title"])) {
						if (is_array($required) AND $required["title"]=="Y") {
							$checked="checked" ;
						}
					}
					print "<input $checked type='checkbox' name='title'>" ;
				print "</td>" ;
			print "</tr>" ;
			print "<tr class='even'>" ;
				print "<td>" ;
					print __($guid, "Surname") ;
				print "</td>" ;
				print "<td>" ;
					$checked="" ;
					if (isset($required["surname"])) {
						if (is_array($required) AND $required["surname"]=="Y") {
							$checked="checked" ;
						}
					}
					print "<input $checked type='checkbox' name='surname'>" ;
				print "</td>" ;
			print "</tr>" ;
			print "<tr class='odd'>" ;
				print "<td>" ;
					print __($guid, "First Name") ;
				print "</td>" ;
				print "<td>" ;
					$checked="" ;
					if (isset($required["firstName"])) {
						if (is_array($required) AND $required["firstName"]=="Y") {
							$checked="checked" ;
						}
					}
					print "<input $checked type='checkbox' name='firstName'>" ;
				print "</td>" ;
			print "</tr>" ;
			print "<tr class='odd'>" ;
				print "<td>" ;
					print __($guid, "Preferred Names") ;
				print "</td>" ;
				print "<td>" ;
					$checked="" ;
					if (isset($required["preferredName"])) {
						if (is_array($required) AND $required["preferredName"]=="Y") {
							$checked="checked" ;
						}
					}
					print "<input $checked type='checkbox' name='preferredName'>" ;
				print "</td>" ;
			print "</tr>" ;
			print "<tr class='even'>" ;
				print "<td>" ;
					print __($guid, "Official Name") ;
				print "</td>" ;
				print "<td>" ;
					$checked="" ;
					if (isset($required["officialName"])) {
						if (is_array($required) AND $required["officialName"]=="Y") {
							$checked="checked" ;
						}
					}
					print "<input $checked type='checkbox' name='officialName'>" ;
				print "</td>" ;
			print "</tr>" ;
			print "<tr class='odd'>" ;
				print "<td>" ;
					print __($guid, "Name In Characters") ;
				print "</td>" ;
				print "<td>" ;
					$checked="" ;
					if (isset($required["nameInCharacters"])) {
						if (is_array($required) AND $required["nameInCharacters"]=="Y") {
							$checked="checked" ;
						}
					}
					print "<input $checked type='checkbox' name='nameInCharacters'>" ;
				print "</td>" ;
			print "</tr>" ;
			print "<tr class='even'>" ;
				print "<td>" ;
					print __($guid, "Date of Birth") ;
				print "</td>" ;
				print "<td>" ;
					$checked="" ;
					if (isset($required["dob"])) {
						if (is_array($required) AND $required["dob"]=="Y") {
							$checked="checked" ;
						}
					}
					print "<input $checked type='checkbox' name='dob'>" ;
				print "</td>" ;
			print "</tr>" ;
			print "<tr class='odd'>" ;
				print "<td>" ;
					print __($guid, "Email") ;
				print "</td>" ;
				print "<td>" ;
					$checked="" ;
					if (isset($required["email"])) {
						if (is_array($required) AND $required["email"]=="Y") {
							$checked="checked" ;
						}
					}
					print "<input $checked type='checkbox' name='email'>" ;
				print "</td>" ;
			print "</tr>" ;
			print "<tr class='even'>" ;
				print "<td>" ;
					print __($guid, "Alternate Email") ;
				print "</td>" ;
				print "<td>" ;
					$checked="" ;
					if (isset($required["emailAlternate"])) {
						if (is_array($required) AND $required["emailAlternate"]=="Y") {
							$checked="checked" ;
						}
					}
					print "<input $checked type='checkbox' name='emailAlternate'>" ;
				print "</td>" ;
			print "</tr>" ;
			print "<tr class='odd'>" ;
				print "<td>" ;
					print __($guid, "Address 1") ;
				print "</td>" ;
				print "<td>" ;
					print "<input disabled='disabled' type='checkbox' name='address1'> <i>" . __($guid, 'This field cannot be required') . "</i>." ;
				print "</td>" ;
			print "</tr>" ;
			print "<tr class='even'>" ;
				print "<td>" ;
					print __($guid, "Address 1 District") ;
				print "</td>" ;
				print "<td>" ;
					print "<input disabled='disabled' type='checkbox' name='address1District'> <i>" . __($guid, 'This field cannot be required') . "</i>." ;
				print "</td>" ;
			print "</tr>" ;
			print "<tr class='odd'>" ;
				print "<td>" ;
					print __($guid, "Address 1 Country") ;
				print "</td>" ;
				print "<td>" ;
					print "<input disabled='disabled' type='checkbox' name='address1Country'> <i>" . __($guid, 'This field cannot be required') . "</i>." ;
				print "</td>" ;
			print "</tr>" ;
			print "<tr class='even'>" ;
				print "<td>" ;
					print __($guid, "Address 2") ;
				print "</td>" ;
				print "<td>" ;
					print "<input disabled='disabled' type='checkbox' name='address2'> <i>" . __($guid, 'This field cannot be required') . "</i>." ;
				print "</td>" ;
			print "</tr>" ;
			print "<tr class='odd'>" ;
				print "<td>" ;
					print __($guid, "Address 2 District") ;
				print "</td>" ;
				print "<td>" ;
					print "<input disabled='disabled' type='checkbox' name='address2District'> <i>" . __($guid, 'This field cannot be required') . "</i>." ;
				print "</td>" ;
			print "</tr>" ;
			print "<tr class='even'>" ;
				print "<td>" ;
					print __($guid, "Address 2 Country") ;
				print "</td>" ;
				print "<td>" ;
					print "<input disabled='disabled' type='checkbox' name='address2Country'> <i>" . __($guid, 'This field cannot be required') . "</i>." ;
				print "</td>" ;
			print "</tr>" ;
			$phoneCount=0 ;
			for ($i=1; $i<5; $i++) {
				$phoneCount++ ;
				$class="odd" ;
				if ($phoneCount%2==0) {
					$class="even" ;
				}
				print "<tr class='$class'>" ;
					print "<td>" ;
						print sprintf(__($guid, 'Phone %1$s'), $i) ;
					print "</td>" ;
					print "<td>" ;
						$checked="" ;
						if (isset($required["phone" . $i])) {
							if (is_array($required) AND $required["phone" . $i]=="Y") {
								$checked="checked" ;
							}
						}
						print "<input $checked type='checkbox' name='phone" . $i . "'>" ;
					print "</td>" ;
				print "</tr>" ;
			}
			print "<tr class='odd'>" ;
				print "<td>" ;
					print __($guid, "First Language") ;
				print "</td>" ;
				print "<td>" ;
					$checked="" ;
					if (isset($required["languageFirst"])) {
						if (is_array($required) AND $required["languageFirst"]=="Y") {
							$checked="checked" ;
						}
					}
					print "<input $checked type='checkbox' name='languageFirst'>" ;
				print "</td>" ;
			print "</tr>" ;
			print "<tr class='even'>" ;
				print "<td>" ;
					print __($guid, "Second Language") ;
				print "</td>" ;
				print "<td>" ;
					$checked="" ;
					if (isset($required["languageSecond"])) {
						if (is_array($required) AND $required["languageSecond"]=="Y") {
							$checked="checked" ;
						}
					}
					print "<input $checked type='checkbox' name='languageSecond'>" ;
				print "</td>" ;
			print "</tr>" ;
			print "<tr class='odd'>" ;
				print "<td>" ;
					print __($guid, "Third Language") ;
				print "</td>" ;
				print "<td>" ;
					$checked="" ;
					if (isset($required["languageThird"])) {
						if (is_array($required) AND $required["languageThird"]=="Y") {
							$checked="checked" ;
						}
					}
					print "<input $checked type='checkbox' name='languageThird'>" ;
				print "</td>" ;
			print "</tr>" ;
			
			print "<tr class='even'>" ;
				print "<td>" ;
					print __($guid, "Country of Birth") ;
				print "</td>" ;
				print "<td>" ;
					$checked="" ;
					if (isset($required["countryOfBirth"])) {
						if (is_array($required) AND $required["countryOfBirth"]=="Y") {
							$checked="checked" ;
						}
					}
					print "<input $checked type='checkbox' name='countryOfBirth'>" ;
				print "</td>" ;
			print "</tr>" ;
			print "<tr class='odd'>" ;
				print "<td>" ;
					print __($guid, "Ethnicity") ;
				print "</td>" ;
				print "<td>" ;
					$checked="" ;
					if (isset($required["ethnicity"])) {
						if (is_array($required) AND $required["ethnicity"]=="Y") {
							$checked="checked" ;
						}
					}
					print "<input $checked type='checkbox' name='ethnicity'>" ;
				print "</td>" ;
			print "</tr>" ;
			print "<tr class='even'>" ;
				print "<td>" ;
					print __($guid, "Citizenship 1") ;
				print "</td>" ;
				print "<td>" ;
					$checked="" ;
					if (isset($required["citizenship1"])) {
						if (is_array($required) AND $required["citizenship1"]=="Y") {
							$checked="checked" ;
						}
					}
					print "<input $checked type='checkbox' name='citizenship1'>" ;
				print "</td>" ;
			print "</tr>" ;
			print "<tr class='odd'>" ;
				print "<td>" ;
					print __($guid, "Citizenship 1 Passport") ;
				print "</td>" ;
				print "<td>" ;
					$checked="" ;
					if (isset($required["citizenship1Passport"])) {
						if (is_array($required) AND $required["citizenship1Passport"]=="Y") {
							$checked="checked" ;
						}
					}
					print "<input $checked type='checkbox' name='citizenship1Passport'>" ;
				print "</td>" ;
			print "</tr>" ;
			print "<tr class='even'>" ;
				print "<td>" ;
					print __($guid, "Citizenship 2") ;
				print "</td>" ;
				print "<td>" ;
					$checked="" ;
					if (isset($required["citizenship2"])) {
						if (is_array($required) AND $required["citizenship2"]=="Y") {
							$checked="checked" ;
						}
					}
					print "<input $checked type='checkbox' name='citizenship2'>" ;
				print "</td>" ;
			print "</tr>" ;
			print "<tr class='odd'>" ;
				print "<td>" ;
					print __($guid, "Citizenship 2 Passport") ;
				print "</td>" ;
				print "<td>" ;
					$checked="" ;
					if (isset($required["citizenship2Passport"])) {
						if (is_array($required) AND $required["citizenship2Passport"]=="Y") {
							$checked="checked" ;
						}
					}
					print "<input $checked type='checkbox' name='citizenship2Passport'>" ;
				print "</td>" ;
			print "</tr>" ;
			print "<tr class='even'>" ;
				print "<td>" ;
					print __($guid, "Religion") ;
				print "</td>" ;
				print "<td>" ;
					$checked="" ;
					if (isset($required["religion"])) {
						if (is_array($required) AND $required["religion"]=="Y") {
							$checked="checked" ;
						}
					}
					print "<input $checked type='checkbox' name='religion'>" ;
				print "</td>" ;
			print "</tr>" ;
			print "<tr class='odd'>" ;
				print "<td>" ;
					print __($guid, "National ID Card Number") ;
				print "</td>" ;
				print "<td>" ;
					$checked="" ;
					if (isset($required["nationalIDCardNumber"])) {
						if (is_array($required) AND $required["nationalIDCardNumber"]=="Y") {
							$checked="checked" ;
						}
					}
					print "<input $checked type='checkbox' name='nationalIDCardNumber'>" ;
				print "</td>" ;
			print "</tr>" ;
			print "<tr class='even'>" ;
				print "<td>" ;
					print __($guid, "Residency Status") ;
				print "</td>" ;
				print "<td>" ;
					$checked="" ;
					if (isset($required["residencyStatus"])) {
						if (is_array($required) AND $required["residencyStatus"]=="Y") {
							$checked="checked" ;
						}
					}
					print "<input $checked type='checkbox' name='residencyStatus'>" ;
				print "</td>" ;
			print "</tr>" ;
			print "<tr class='odd'>" ;
				print "<td>" ;
					print __($guid, "Visa Expiry Date") ;
				print "</td>" ;
				print "<td>" ;
					$checked="" ;
					if (isset($required["visaExpiryDate"])) {
						if (is_array($required) AND $required["visaExpiryDate"]=="Y") {
							$checked="checked" ;
						}
					}
					print "<input $checked type='checkbox' name='visaExpiryDate'>" ;
				print "</td>" ;
			print "</tr>" ;
			
			print "<tr class='even'>" ;
				print "<td>" ;
					print __($guid, "Profession") ;
				print "</td>" ;
				print "<td>" ;
					$checked="" ;
					if (isset($required["profession"])) {
						if (is_array($required) AND $required["profession"]=="Y") {
							$checked="checked" ;
						}
					}
					print "<input $checked type='checkbox' name='profession'>" ;
				print "</td>" ;
			print "</tr>" ;
			print "<tr class='odd'>" ;
				print "<td>" ;
					print __($guid, "Employer") ;
				print "</td>" ;
				print "<td>" ;
					$checked="" ;
					if (isset($required["employer"])) {
						if (is_array($required) AND $required["employer"]=="Y") {
							$checked="checked" ;
						}
					}
					print "<input $checked type='checkbox' name='employer'>" ;
				print "</td>" ;
			print "</tr>" ;
			print "<tr class='even'>" ;
				print "<td>" ;
					print __($guid, "Job Title") ;
				print "</td>" ;
				print "<td>" ;
					$checked="" ;
					if (isset($required["jobTitle"])) {
						if (is_array($required) AND $required["jobTitle"]=="Y") {
							$checked="checked" ;
						}
					}
					print "<input $checked type='checkbox' name='jobTitle'>" ;
				print "</td>" ;
			print "</tr>" ;
			print "<tr class='odd'>" ;
				print "<td>" ;
					print __($guid, "Emergency 1 Name") ;
				print "</td>" ;
				print "<td>" ;
					$checked="" ;
					if (isset($required["emergency1Name"])) {
						if (is_array($required) AND $required["emergency1Name"]=="Y") {
							$checked="checked" ;
						}
					}
					print "<input $checked type='checkbox' name='emergency1Name'>" ;
				print "</td>" ;
			print "</tr>" ;
			print "<tr class='even'>" ;
				print "<td>" ;
					print __($guid, "Emergency 1 Number 1") ;
				print "</td>" ;
				print "<td>" ;
					$checked="" ;
					if (isset($required["emergency1Number1"])) {
						if (is_array($required) AND $required["emergency1Number1"]=="Y") {
							$checked="checked" ;
						}
					}
					print "<input $checked type='checkbox' name='emergency1Number1'>" ;
				print "</td>" ;
			print "</tr>" ;
			print "<tr class='odd'>" ;
				print "<td>" ;
					print __($guid, "Emergency 1 Number 2") ;
				print "</td>" ;
				print "<td>" ;
					$checked="" ;
					if (isset($required["emergency1Number2"])) {
						if (is_array($required) AND $required["emergency1Number2"]=="Y") {
							$checked="checked" ;
						}
					}
					print "<input $checked type='checkbox' name='emergency1Number2'>" ;
				print "</td>" ;
			print "</tr>" ;
			print "<tr class='even'>" ;
				print "<td>" ;
					print __($guid, "Emergency 1 Relationship") ;
				print "</td>" ;
				print "<td>" ;
					$checked="" ;
					if (isset($required["emergency1Relationship"])) {
						if (is_array($required) AND $required["emergency1Relationship"]=="Y") {
							$checked="checked" ;
						}
					}
					print "<input $checked type='checkbox' name='emergency1Relationship'>" ;
				print "</td>" ;
			print "</tr>" ;
			print "<tr class='odd'>" ;
				print "<td>" ;
					print __($guid, "Emergency 2 Name") ;
				print "</td>" ;
				print "<td>" ;
					$checked="" ;
					if (isset($required["emergency2Name"])) {
						if (is_array($required) AND $required["emergency2Name"]=="Y") {
							$checked="checked" ;
						}
					}
					print "<input $checked type='checkbox' name='emergency2Name'>" ;
				print "</td>" ;
			print "</tr>" ;
			print "<tr class='even'>" ;
				print "<td>" ;
					print __($guid, "Emergency 2 Number 1") ;
				print "</td>" ;
				print "<td>" ;
					$checked="" ;
					if (isset($required["emergency2Number1"])) {
						if (is_array($required) AND $required["emergency2Number1"]=="Y") {
							$checked="checked" ;
						}
					}
					print "<input $checked type='checkbox' name='emergency2Number1'>" ;
				print "</td>" ;
			print "</tr>" ;
			print "<tr class='odd'>" ;
				print "<td>" ;
					print __($guid, "Emergency 2 Number 2") ;
				print "</td>" ;
				print "<td>" ;
					$checked="" ;
					if (isset($required["emergency2Number2"])) {
						if (is_array($required) AND $required["emergency2Number2"]=="Y") {
							$checked="checked" ;
						}
					}
					print "<input $checked type='checkbox' name='emergency2Number2'>" ;
				print "</td>" ;
			print "</tr>" ;
			print "<tr class='even'>" ;
				print "<td>" ;
					print __($guid, "Emergency 2 Relationship") ;
				print "</td>" ;
				print "<td>" ;
					$checked="" ;
					if (isset($required["emergency2Relationship"])) {
						if (is_array($required) AND $required["emergency2Relationship"]=="Y") {
							$checked="checked" ;
						}
					}
					print "<input $checked type='checkbox' name='emergency2Relationship'>" ;
				print "</td>" ;
			print "</tr>" ;
			print "<tr class='odd'>" ;
				print "<td>" ;
					print __($guid, "Vehicle Registration") ;
				print "</td>" ;
				print "<td>" ;
					$checked="" ;
					if (isset($required["vehicleRegistration"])) {
						if (is_array($required) AND $required["vehicleRegistration"]=="Y") {
							$checked="checked" ;
						}
					}
					print "<input $checked type='checkbox' name='vehicleRegistration'>" ;
				print "</td>" ;
			print "</tr>" ;
			print "<tr>" ;
					print "<td class='right' colspan=2>" ;
						print "<input name='address' type='hidden' value='" . $_GET["q"] . "'>" ;
						print "<input type='submit' value='Submit'>" ;
					print "</td>" ;
				print "</tr>" ;
		print "</table>" ;
		?>
	</form>
<?php
}
?>