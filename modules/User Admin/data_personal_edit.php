<?
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

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;


if (isActionAccessible($guid, $connection2, "/modules/User Admin/data_personal_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/data_personal.php'>Personal Data Updates</a> > </div><div class='trailEnd'>Edit Request</div>" ;
	print "</div>" ;
	
	//Check if school year specified
	$gibbonPersonUpdateID=$_GET["gibbonPersonUpdateID"];
	if ($gibbonPersonUpdateID=="Y") {
		print "<div class='error'>" ;
			print _("You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonPersonUpdateID"=>$gibbonPersonUpdateID); 
			$sql="SELECT gibbonPersonUpdate.gibbonPersonID, gibbonPerson.title AS title, gibbonPerson.surname AS surname, gibbonPerson.firstName AS firstName, gibbonPerson.preferredName AS preferredName, gibbonPerson.officialName AS officialName, gibbonPerson.nameInCharacters AS nameInCharacters, gibbonPerson.dob AS dob, gibbonPerson.email AS email, gibbonPerson.emailAlternate AS emailAlternate, gibbonPerson.address1 AS address1, gibbonPerson.address1District AS address1District, gibbonPerson.address1Country AS address1Country, gibbonPerson.address2 AS address2, gibbonPerson.address2District AS address2District, gibbonPerson.address2Country AS address2Country, gibbonPerson.phone1Type AS phone1Type, gibbonPerson.phone1CountryCode AS phone1CountryCode, gibbonPerson.phone1 AS phone1, gibbonPerson.phone2Type AS phone2Type, gibbonPerson.phone2CountryCode AS phone2CountryCode, gibbonPerson.phone2 AS phone2, gibbonPerson.phone3Type AS phone3Type, gibbonPerson.phone3CountryCode AS phone3CountryCode, gibbonPerson.phone3 AS phone3, gibbonPerson.phone4Type AS phone4Type, gibbonPerson.phone4CountryCode AS phone4CountryCode, gibbonPerson.phone4 AS phone4, gibbonPerson.languageFirst AS languageFirst, gibbonPerson.languageSecond AS languageSecond, gibbonPerson.languageThird AS languageThird, gibbonPerson.countryOfBirth AS countryOfBirth, gibbonPerson.ethnicity AS ethnicity, gibbonPerson.citizenship1 AS citizenship1, gibbonPerson.citizenship1Passport AS citizenship1Passport, gibbonPerson.citizenship2 AS citizenship2, gibbonPerson.citizenship2Passport AS citizenship2Passport, gibbonPerson.religion AS religion, gibbonPerson.nationalIDCardNumber AS nationalIDCardNumber, gibbonPerson.residencyStatus AS residencyStatus, gibbonPerson.visaExpiryDate AS visaExpiryDate, gibbonPerson.profession AS profession , gibbonPerson.employer AS employer, gibbonPerson.jobTitle AS jobTitle, gibbonPerson.emergency1Name AS emergency1Name, gibbonPerson.emergency1Number1 AS emergency1Number1, gibbonPerson.emergency1Number2 AS emergency1Number2, gibbonPerson.emergency1Relationship AS emergency1Relationship, gibbonPerson.emergency2Name AS emergency2Name, gibbonPerson.emergency2Number1 AS emergency2Number1, gibbonPerson.emergency2Number2 AS emergency2Number2, gibbonPerson.emergency2Relationship AS emergency2Relationship, gibbonPerson.vehicleRegistration AS vehicleRegistration, gibbonPerson.privacy AS privacy, gibbonPersonUpdate.title AS newtitle, gibbonPersonUpdate.surname AS newsurname, gibbonPersonUpdate.firstName AS newfirstName, gibbonPersonUpdate.preferredName AS newpreferredName, gibbonPersonUpdate.officialName AS newofficialName, gibbonPersonUpdate.nameInCharacters AS newnameInCharacters, gibbonPersonUpdate.dob AS newdob, gibbonPersonUpdate.email AS newemail, gibbonPersonUpdate.emailAlternate AS newemailAlternate, gibbonPersonUpdate.address1 AS newaddress1, gibbonPersonUpdate.address1District AS newaddress1District, gibbonPersonUpdate.address1Country AS newaddress1Country, gibbonPersonUpdate.address2 AS newaddress2, gibbonPersonUpdate.address2District AS newaddress2District, gibbonPersonUpdate.address2Country AS newaddress2Country, gibbonPersonUpdate.phone1Type AS newphone1Type, gibbonPersonUpdate.phone1CountryCode AS newphone1CountryCode, gibbonPersonUpdate.phone1 AS newphone1, gibbonPersonUpdate.phone2Type AS newphone2Type, gibbonPersonUpdate.phone2CountryCode AS newphone2CountryCode, gibbonPersonUpdate.phone2 AS newphone2, gibbonPersonUpdate.phone3Type AS newphone3Type, gibbonPersonUpdate.phone3CountryCode AS newphone3CountryCode, gibbonPersonUpdate.phone3 AS newphone3, gibbonPersonUpdate.phone4Type AS newphone4Type, gibbonPersonUpdate.phone4CountryCode AS newphone4CountryCode, gibbonPersonUpdate.phone4 AS newphone4, gibbonPersonUpdate.languageFirst AS newlanguageFirst, gibbonPersonUpdate.languageSecond AS newlanguageSecond, gibbonPersonUpdate.languageThird AS newlanguageThird, gibbonPersonUpdate.countryOfBirth AS newcountryOfBirth, gibbonPersonUpdate.ethnicity AS newethnicity, gibbonPersonUpdate.citizenship1 AS newcitizenship1, gibbonPersonUpdate.citizenship1Passport AS newcitizenship1Passport, gibbonPersonUpdate.citizenship2 AS newcitizenship2, gibbonPersonUpdate.citizenship2Passport AS newcitizenship2Passport, gibbonPersonUpdate.religion AS newreligion, gibbonPersonUpdate.nationalIDCardNumber AS newnationalIDCardNumber, gibbonPersonUpdate.residencyStatus AS newresidencyStatus, gibbonPersonUpdate.visaExpiryDate AS newvisaExpiryDate, gibbonPersonUpdate.profession AS newprofession , gibbonPersonUpdate.employer AS newemployer, gibbonPersonUpdate.jobTitle AS newjobTitle, gibbonPersonUpdate.emergency1Name AS newemergency1Name, gibbonPersonUpdate.emergency1Number1 AS newemergency1Number1, gibbonPersonUpdate.emergency1Number2 AS newemergency1Number2, gibbonPersonUpdate.emergency1Relationship AS newemergency1Relationship, gibbonPersonUpdate.emergency2Name AS newemergency2Name, gibbonPersonUpdate.emergency2Number1 AS newemergency2Number1, gibbonPersonUpdate.emergency2Number2 AS newemergency2Number2, gibbonPersonUpdate.emergency2Relationship AS newemergency2Relationship, gibbonPersonUpdate.vehicleRegistration AS newvehicleRegistration, gibbonPersonUpdate.privacy AS newprivacy FROM gibbonPersonUpdate JOIN gibbonPerson ON (gibbonPersonUpdate.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPersonUpdateID=:gibbonPersonUpdateID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}

		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print _("The selected record does not exist, or you do not have access to it.") ;
			print "</div>" ;
		}
		else {
			if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
			$updateReturnMessage="" ;
			$class="error" ;
			if (!($updateReturn=="")) {
				if ($updateReturn=="fail0") {
					$updateReturnMessage=_("Your request failed because you do not have access to this action.") ;	
				}
				else if ($updateReturn=="fail1") {
					$updateReturnMessage=_("Your request failed because your inputs were invalid.") ;	
				}
				else if ($updateReturn=="fail2") {
					$updateReturnMessage=_("Your request failed due to a database error.") ;	
				}
				else if ($updateReturn=="fail3") {
					$updateReturnMessage=_("Your request failed because your inputs were invalid.") ;	
				}
				else if ($updateReturn=="success1") {
					$updateReturnMessage="Your request was completed successfully., but status could not be updated." ;	
				}
				else if ($updateReturn=="success0") {
					$updateReturnMessage=_("Your request was completed successfully.") ;	
					$class="success" ;
				}
				print "<div class='$class'>" ;
					print $updateReturnMessage;
				print "</div>" ;
			} 

			//Let's go!
			$row=$result->fetch() ;
			?>
			<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/data_personal_editProcess.php?gibbonPersonUpdateID=$gibbonPersonUpdateID" ?>">
				<?
				print "<table cellspacing='0' style='width: 100%'>" ;
					print "<tr class='head'>" ;
						print "<th>" ;
							print "Field" ;
						print "</th>" ;
						print "<th>" ;
							print "Current Value" ;
						print "</th>" ;
						print "<th>" ;
							print "New Value" ;
						print "</th>" ;
						print "<th>" ;
							print "Accept" ;
						print "</th>" ;
					print "</tr>" ;
					
					$rowNum="even" ;
						
					//COLOR ROW BY STATUS!
					print "<tr class='odd'>" ;
						print "<td>" ;
							print "Title" ;
						print "</td>" ;
						print "<td>" ;
							print $row["title"] ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($row["title"]!=$row["newtitle"]) {
								$style="style='color: #ff0000'" ;
							}
							print "<span $style>" ;
							print $row["newtitle"] ;
						print "</td>" ;
						print "<td>" ;
							if ($row["title"]!=$row["newtitle"]) { print "<input checked type='checkbox' name='newtitleOn'><input name='newtitle' type='hidden' value='" . htmlprep($row["newtitle"]) . "'>" ; }
						print "</td>" ;
					print "</tr>" ;
					print "<tr class='even'>" ;
						print "<td>" ;
							print "Surname" ;
						print "</td>" ;
						print "<td>" ;
							print $row["surname"] ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($row["surname"]!=$row["newsurname"]) {
								$style="style='color: #ff0000'" ;
							}
							print "<span $style>" ;
							print $row["newsurname"] ;
						print "</td>" ;
						print "<td>" ;
							if ($row["surname"]!=$row["newsurname"]) { print "<input checked type='checkbox' name='newsurnameOn'><input name='newsurname' type='hidden' value='" . htmlprep($row["newsurname"]) . "'>" ; }
						print "</td>" ;
					print "</tr>" ;
					print "<tr class='odd'>" ;
						print "<td>" ;
							print "First Name" ;
						print "</td>" ;
						print "<td>" ;
							print $row["firstName"] ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($row["firstName"]!=$row["newfirstName"]) {
								$style="style='color: #ff0000'" ;
							}
							print "<span $style>" ;
							print $row["newfirstName"] ;
							print "</span>" ;
						print "</td>" ;
						print "<td>" ;
							if ($row["firstName"]!=$row["newfirstName"]) { print "<input checked type='checkbox' name='newfirstNameOn'><input name='newfirstName' type='hidden' value='" . htmlprep($row["newfirstName"]) . "'>" ; }
						print "</td>" ;
					print "</tr>" ;
					print "<tr class='odd'>" ;
						print "<td>" ;
							print "Preferred Names" ;
						print "</td>" ;
						print "<td>" ;
							print $row["preferredName"] ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($row["preferredName"]!=$row["newpreferredName"]) {
								$style="style='color: #ff0000'" ;
							}
							print "<span $style>" ;
							print $row["newpreferredName"] ;
							print "</span>" ;
						print "</td>" ;
						print "<td>" ;
							if ($row["preferredName"]!=$row["newpreferredName"]) { print "<input checked type='checkbox' name='newpreferredNameOn'><input name='newpreferredName' type='hidden' value='" . htmlprep($row["newpreferredName"]) . "'>" ; }
						print "</td>" ;
					print "</tr>" ;
					print "<tr class='even'>" ;
						print "<td>" ;
							print "Official Name" ;
						print "</td>" ;
						print "<td>" ;
							print $row["officialName"] ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($row["officialName"]!=$row["newofficialName"]) {
								$style="style='color: #ff0000'" ;
							}
							print "<span $style>" ;
							print $row["newofficialName"] ;
							print "</span>" ;
						print "</td>" ;
						print "<td>" ;
							if ($row["officialName"]!=$row["newofficialName"]) { print "<input checked type='checkbox' name='newofficialNameOn'><input name='newofficialName' type='hidden' value='" . htmlprep($row["newofficialName"]) . "'>" ; }
						print "</td>" ;
					print "</tr>" ;
					print "<tr class='odd'>" ;
						print "<td>" ;
							print "Name In Characters" ;
						print "</td>" ;
						print "<td>" ;
							print $row["nameInCharacters"] ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($row["nameInCharacters"]!=$row["newnameInCharacters"]) {
								$style="style='color: #ff0000'" ;
							}
							print "<span $style>" ;
							print $row["newnameInCharacters"] ;
							print "</span>" ;
						print "</td>" ;
						print "<td>" ;
							if ($row["nameInCharacters"]!=$row["newnameInCharacters"]) { print "<input checked type='checkbox' name='newnameInCharactersOn'><input name='newnameInCharacters' type='hidden' value='" . htmlprep($row["newnameInCharacters"]) . "'>" ; }
						print "</td>" ;
					print "</tr>" ;
					print "<tr class='even'>" ;
						print "<td>" ;
							print "Date of Birth" ;
						print "</td>" ;
						print "<td>" ;
							print dateConvertBack($guid, $row["dob"]) ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($row["dob"]!=$row["newdob"]) {
								$style="style='color: #ff0000'" ;
							}
							print "<span $style>" ;
							print dateConvertBack($guid, $row["newdob"]) ;
							print "</span>" ;
						print "</td>" ;
						print "<td>" ;
							if ($row["dob"]!=$row["newdob"]) { print "<input checked type='checkbox' name='newdobOn'><input name='newdob' type='hidden' value='" . htmlprep($row["newdob"]) . "'>" ; }
						print "</td>" ;
					print "</tr>" ;
					print "<tr class='odd'>" ;
						print "<td>" ;
							print "Email" ;
						print "</td>" ;
						print "<td>" ;
							print $row["email"] ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($row["email"]!=$row["newemail"]) {
								$style="style='color: #ff0000'" ;
							}
							print "<span $style>" ;
							print $row["newemail"] ;
							print "</span>" ;
						print "</td>" ;
						print "<td>" ;
							if ($row["email"]!=$row["newemail"]) { print "<input checked type='checkbox' name='newemailOn'><input name='newemail' type='hidden' value='" . htmlprep($row["newemail"]) . "'>" ; }
						print "</td>" ;
					print "</tr>" ;
					print "<tr class='even'>" ;
						print "<td>" ;
							print "Alternate Email" ;
						print "</td>" ;
						print "<td>" ;
							print $row["emailAlternate"] ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($row["emailAlternate"]!=$row["newemailAlternate"]) {
								$style="style='color: #ff0000'" ;
							}
							print "<span $style>" ;
							print $row["newemailAlternate"] ;
							print "</span>" ;
						print "</td>" ;
						print "<td>" ;
							if ($row["emailAlternate"]!=$row["newemailAlternate"]) { print "<input checked type='checkbox' name='newemailAlternateOn'><input name='newemailAlternate' type='hidden' value='" . htmlprep($row["newemailAlternate"]) . "'>" ; }
						print "</td>" ;
					print "</tr>" ;
					print "<tr class='odd'>" ;
						print "<td>" ;
							print "Address 1" ;
						print "</td>" ;
						print "<td>" ;
							print $row["address1"] ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($row["address1"]!=$row["newaddress1"]) {
								$style="style='color: #ff0000'" ;
							}
							print "<span $style>" ;
							print $row["newaddress1"] ;
							print "</span>" ;
						print "</td>" ;
						print "<td>" ;
							if ($row["address1"]!=$row["newaddress1"]) { print "<input checked type='checkbox' name='newaddress1On'><input name='newaddress1' type='hidden' value='" . htmlprep($row["newaddress1"]) . "'>" ; }
						print "</td>" ;
					print "</tr>" ;
					print "<tr class='even'>" ;
						print "<td>" ;
							print "Address 1 District" ;
						print "</td>" ;
						print "<td>" ;
							print $row["address1District"] ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($row["address1District"]!=$row["newaddress1District"]) {
								$style="style='color: #ff0000'" ;
							}
							print "<span $style>" ;
							print $row["newaddress1District"] ;
							print "</span>" ;
						print "</td>" ;
						print "<td>" ;
							if ($row["address1District"]!=$row["newaddress1District"]) { print "<input checked type='checkbox' name='newaddress1DistrictOn'><input name='newaddress1District' type='hidden' value='" . htmlprep($row["newaddress1District"]) . "'>" ; }
						print "</td>" ;
					print "</tr>" ;
					print "<tr class='odd'>" ;
						print "<td>" ;
							print "Address 1 Country" ;
						print "</td>" ;
						print "<td>" ;
							print $row["address1Country"] ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($row["address1Country"]!=$row["newaddress1Country"]) {
								$style="style='color: #ff0000'" ;
							}
							print "<span $style>" ;
							print $row["newaddress1Country"] ;
							print "</span>" ;
						print "</td>" ;
						print "<td>" ;
							if ($row["address1Country"]!=$row["newaddress1Country"]) { print "<input checked type='checkbox' name='newaddress1CountryOn'><input name='newaddress1Country' type='hidden' value='" . htmlprep($row["newaddress1Country"]) . "'>" ; }
						print "</td>" ;
					print "</tr>" ;
					print "<tr class='even'>" ;
						print "<td>" ;
							print "Address 2" ;
						print "</td>" ;
						print "<td>" ;
							print $row["address2"] ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($row["address2"]!=$row["newaddress2"]) {
								$style="style='color: #ff0000'" ;
							}
							print "<span $style>" ;
							print $row["newaddress2"] ;
							print "</span>" ;
						print "</td>" ;
						print "<td>" ;
							if ($row["address2"]!=$row["newaddress2"]) { print "<input checked type='checkbox' name='newaddress2On'><input name='newaddress2' type='hidden' value='" . htmlprep($row["newaddress2"]) . "'>" ; }
						print "</td>" ;
					print "</tr>" ;
					print "<tr class='odd'>" ;
						print "<td>" ;
							print "Address 2 District" ;
						print "</td>" ;
						print "<td>" ;
							print $row["address2District"] ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($row["address2District"]!=$row["newaddress2District"]) {
								$style="style='color: #ff0000'" ;
							}
							print "<span $style>" ;
							print $row["newaddress2District"] ;
							print "</span>" ;
						print "</td>" ;
						print "<td>" ;
							if ($row["address2District"]!=$row["newaddress2District"]) { print "<input checked type='checkbox' name='newaddress2DistrictOn'><input name='newaddress2District' type='hidden' value='" . htmlprep($row["newaddress2District"]) . "'>" ; }
						print "</td>" ;
					print "</tr>" ;
					print "<tr class='even'>" ;
						print "<td>" ;
							print "Address 2 Country" ;
						print "</td>" ;
						print "<td>" ;
							print $row["address2Country"] ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($row["address2Country"]!=$row["newaddress2Country"]) {
								$style="style='color: #ff0000'" ;
							}
							print "<span $style>" ;
							print $row["newaddress2Country"] ;
							print "</span>" ;
						print "</td>" ;
						print "<td>" ;
							if ($row["address2Country"]!=$row["newaddress2Country"]) { print "<input checked type='checkbox' name='newaddress2CountryOn'><input name='newaddress2Country' type='hidden' value='" . htmlprep($row["newaddress2Country"]) . "'>" ; }
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
								print "Phone $i Type" ;
							print "</td>" ;
							print "<td>" ;
								print $row["phone" . $i . "Type"] ;
							print "</td>" ;
							print "<td>" ;
								$style="" ;
								if ($row["phone" . $i . "Type"]!=$row["newphone" . $i . "Type"]) {
									$style="style='color: #ff0000'" ;
								}
								print "<span $style>" ;
								print $row["newphone" . $i . "Type"] ;
								print "</span>" ;
							print "</td>" ;
							print "<td>" ;
								if ($row["phone" . $i . "Type"]!=$row["newphone" . $i . "Type"]) { print "<input checked type='checkbox' name='newphone" . $i . "TypeOn'><input name='newphone" . $i . "Type' type='hidden' value='" . htmlprep($row["newphone" . $i . "Type"]) . "'>" ; }
							print "</td>" ;
						print "</tr>" ;
						$phoneCount++ ;
						$class="odd" ;
						if ($phoneCount%2==0) {
							$class="even" ;
						}
						print "<tr class='$class'>" ;
							print "<td>" ;
								print "Phone $i Country Code" ;
							print "</td>" ;
							print "<td>" ;
								print $row["phone" . $i . "CountryCode"] ;
							print "</td>" ;
							print "<td>" ;
								$style="" ;
								if ($row["phone" . $i . "CountryCode"]!=$row["newphone" . $i . "CountryCode"]) {
									$style="style='color: #ff0000'" ;
								}
								print "<span $style>" ;
								print $row["newphone" . $i . "CountryCode"] ;
								print "</span>" ;
							print "</td>" ;
							print "<td>" ;
								if ($row["phone" . $i . "CountryCode"]!=$row["newphone" . $i . "CountryCode"]) { print "<input checked type='checkbox' name='newphone" . $i . "CountryCodeOn'><input name='newphone" . $i . "CountryCode' type='hidden' value='" . htmlprep($row["newphone" . $i . "CountryCode"]) . "'>" ; }
							print "</td>" ;
						print "</tr>" ;
						$phoneCount++ ;
						$class="odd" ;
						if ($phoneCount%2==0) {
							$class="even" ;
						}
						print "<tr class='$class'>" ;
							print "<td>" ;
								print "Phone $i" ;
							print "</td>" ;
							print "<td>" ;
								print $row["phone" . $i] ;
							print "</td>" ;
							print "<td>" ;
								$style="" ;
								if ($row["phone" . $i]!=$row["newphone" . $i]) {
									$style="style='color: #ff0000'" ;
								}
								print "<span $style>" ;
								print $row["newphone" . $i] ;
								print "</span>" ;
							print "</td>" ;
							print "<td>" ;
								if ($row["phone" . $i]!=$row["newphone" . $i]) { print "<input checked type='checkbox' name='newphone" . $i . "On'><input name='newphone" . $i . "' type='hidden' value='" . htmlprep($row["newphone" . $i]) . "'>" ; }
							print "</td>" ;
						print "</tr>" ;
					}
					print "<tr class='odd'>" ;
						print "<td>" ;
							print "First Language" ;
						print "</td>" ;
						print "<td>" ;
							print $row["languageFirst"] ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($row["languageFirst"]!=$row["newlanguageFirst"]) {
								$style="style='color: #ff0000'" ;
							}
							print "<span $style>" ;
							print $row["newlanguageFirst"] ;
							print "</span>" ;
						print "</td>" ;
						print "<td>" ;
							if ($row["languageFirst"]!=$row["newlanguageFirst"]) { print "<input checked type='checkbox' name='newlanguageFirstOn'><input name='newlanguageFirst' type='hidden' value='" . htmlprep($row["newlanguageFirst"]) . "'>" ; }
						print "</td>" ;
					print "</tr>" ;
					print "<tr class='even'>" ;
						print "<td>" ;
							print "Second Language" ;
						print "</td>" ;
						print "<td>" ;
							print $row["languageSecond"] ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($row["languageSecond"]!=$row["newlanguageSecond"]) {
								$style="style='color: #ff0000'" ;
							}
							print "<span $style>" ;
							print $row["newlanguageSecond"] ;
							print "</span>" ;
						print "</td>" ;
						print "<td>" ;
							if ($row["languageSecond"]!=$row["newlanguageSecond"]) { print "<input checked type='checkbox' name='newlanguageSecondOn'><input name='newlanguageSecond' type='hidden' value='" . htmlprep($row["newlanguageSecond"]) . "'>" ; }
						print "</td>" ;
					print "</tr>" ;
					print "<tr class='odd'>" ;
						print "<td>" ;
							print "Third Language" ;
						print "</td>" ;
						print "<td>" ;
							print $row["languageThird"] ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($row["languageThird"]!=$row["newlanguageThird"]) {
								$style="style='color: #ff0000'" ;
							}
							print "<span $style>" ;
							print $row["newlanguageThird"] ;
							print "</span>" ;
						print "</td>" ;
						print "<td>" ;
							if ($row["languageThird"]!=$row["newlanguageThird"]) { print "<input checked type='checkbox' name='newlanguageThirdOn'><input name='newlanguageThird' type='hidden' value='" . htmlprep($row["newlanguageThird"]) . "'>" ; }
						print "</td>" ;
					print "</tr>" ;
					
					print "<tr class='even'>" ;
						print "<td>" ;
							print "Country of Birth" ;
						print "</td>" ;
						print "<td>" ;
							print $row["countryOfBirth"] ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($row["countryOfBirth"]!=$row["newcountryOfBirth"]) {
								$style="style='color: #ff0000'" ;
							}
							print "<span $style>" ;
							print $row["newcountryOfBirth"] ;
							print "</span>" ;
						print "</td>" ;
						print "<td>" ;
							if ($row["countryOfBirth"]!=$row["newcountryOfBirth"]) { print "<input checked type='checkbox' name='newcountryOfBirthOn'><input name='newcountryOfBirth' type='hidden' value='" . htmlprep($row["newcountryOfBirth"]) . "'>" ; }
						print "</td>" ;
					print "</tr>" ;
					print "<tr class='odd'>" ;
						print "<td>" ;
							print "Ethnicity" ;
						print "</td>" ;
						print "<td>" ;
							print $row["ethnicity"] ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($row["ethnicity"]!=$row["newethnicity"]) {
								$style="style='color: #ff0000'" ;
							}
							print "<span $style>" ;
							print $row["newethnicity"] ;
							print "</span>" ;
						print "</td>" ;
						print "<td>" ;
							if ($row["ethnicity"]!=$row["newethnicity"]) { print "<input checked type='checkbox' name='newethnicityOn'><input name='newethnicity' type='hidden' value='" . htmlprep($row["newethnicity"]) . "'>" ; }
						print "</td>" ;
					print "</tr>" ;
					print "<tr class='even'>" ;
						print "<td>" ;
							print "Citizenship 1" ;
						print "</td>" ;
						print "<td>" ;
							print $row["citizenship1"] ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($row["citizenship1"]!=$row["newcitizenship1"]) {
								$style="style='color: #ff0000'" ;
							}
							print "<span $style>" ;
							print $row["newcitizenship1"] ;
							print "</span>" ;
						print "</td>" ;
						print "<td>" ;
							if ($row["citizenship1"]!=$row["newcitizenship1"]) { print "<input checked type='checkbox' name='newcitizenship1On'><input name='newcitizenship1' type='hidden' value='" . htmlprep($row["newcitizenship1"]) . "'>" ; }
						print "</td>" ;
					print "</tr>" ;
					print "<tr class='odd'>" ;
						print "<td>" ;
							print "Citizenship 1 Passport" ;
						print "</td>" ;
						print "<td>" ;
							print $row["citizenship1Passport"] ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($row["citizenship1Passport"]!=$row["newcitizenship1Passport"]) {
								$style="style='color: #ff0000'" ;
							}
							print "<span $style>" ;
							print $row["newcitizenship1Passport"] ;
							print "</span>" ;
						print "</td>" ;
						print "<td>" ;
							if ($row["citizenship1Passport"]!=$row["newcitizenship1Passport"]) { print "<input checked type='checkbox' name='newcitizenship1PassportOn'><input name='newcitizenship1Passport' type='hidden' value='" . htmlprep($row["newcitizenship1Passport"]) . "'>" ; }
						print "</td>" ;
					print "</tr>" ;
					print "<tr class='even'>" ;
						print "<td>" ;
							print "Citizenship 2" ;
						print "</td>" ;
						print "<td>" ;
							print $row["citizenship2"] ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($row["citizenship2"]!=$row["newcitizenship2"]) {
								$style="style='color: #ff0000'" ;
							}
							print "<span $style>" ;
							print $row["newcitizenship2"] ;
							print "</span>" ;
						print "</td>" ;
						print "<td>" ;
							if ($row["citizenship2"]!=$row["newcitizenship2"]) { print "<input checked type='checkbox' name='newcitizenship2On'><input name='newcitizenship2' type='hidden' value='" . htmlprep($row["newcitizenship2"]) . "'>" ; }
						print "</td>" ;
					print "</tr>" ;
					print "<tr class='odd'>" ;
						print "<td>" ;
							print "Citizenship 2 Passport" ;
						print "</td>" ;
						print "<td>" ;
							print $row["citizenship2Passport"] ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($row["citizenship2Passport"]!=$row["newcitizenship2Passport"]) {
								$style="style='color: #ff0000'" ;
							}
							print "<span $style>" ;
							print $row["newcitizenship2Passport"] ;
							print "</span>" ;
						print "</td>" ;
						print "<td>" ;
							if ($row["citizenship2Passport"]!=$row["newcitizenship2Passport"]) { print "<input checked type='checkbox' name='newcitizenship2PassportOn'><input name='newcitizenship2Passport' type='hidden' value='" . htmlprep($row["newcitizenship2Passport"]) . "'>" ; }
						print "</td>" ;
					print "</tr>" ;
					print "<tr class='even'>" ;
						print "<td>" ;
							print "Religion" ;
						print "</td>" ;
						print "<td>" ;
							print $row["religion"] ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($row["religion"]!=$row["newreligion"]) {
								$style="style='color: #ff0000'" ;
							}
							print "<span $style>" ;
							print $row["newreligion"] ;
							print "</span>" ;
						print "</td>" ;
						print "<td>" ;
							if ($row["religion"]!=$row["newreligion"]) { print "<input checked type='checkbox' name='newreligionOn'><input name='newreligion' type='hidden' value='" . htmlprep($row["newreligion"]) . "'>" ; }
						print "</td>" ;
					print "</tr>" ;
					print "<tr class='odd'>" ;
						print "<td>" ;
							print "National ID Card Number" ;
						print "</td>" ;
						print "<td>" ;
							print $row["nationalIDCardNumber"] ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($row["nationalIDCardNumber"]!=$row["newnationalIDCardNumber"]) {
								$style="style='color: #ff0000'" ;
							}
							print "<span $style>" ;
							print $row["newnationalIDCardNumber"] ;
							print "</span>" ;
						print "</td>" ;
						print "<td>" ;
							if ($row["nationalIDCardNumber"]!=$row["newnationalIDCardNumber"]) { print "<input checked type='checkbox' name='newnationalIDCardNumberOn'><input name='newnationalIDCardNumber' type='hidden' value='" . htmlprep($row["newnationalIDCardNumber"]) . "'>" ; }
						print "</td>" ;
					print "</tr>" ;
					print "<tr class='even'>" ;
						print "<td>" ;
							print "Residency Status" ;
						print "</td>" ;
						print "<td>" ;
							print $row["residencyStatus"] ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($row["residencyStatus"]!=$row["newresidencyStatus"]) {
								$style="style='color: #ff0000'" ;
							}
							print "<span $style>" ;
							print $row["newresidencyStatus"] ;
							print "</span>" ;
						print "</td>" ;
						print "<td>" ;
							if ($row["residencyStatus"]!=$row["newresidencyStatus"]) { print "<input checked type='checkbox' name='newresidencyStatusOn'><input name='newresidencyStatus' type='hidden' value='" . htmlprep($row["newresidencyStatus"]) . "'>" ; }
						print "</td>" ;
					print "</tr>" ;
					print "<tr class='odd'>" ;
						print "<td>" ;
							print "Visa Expiry Date" ;
						print "</td>" ;
						print "<td>" ;
							print dateConvertBack($guid, $row["visaExpiryDate"]) ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($row["visaExpiryDate"]!=$row["newvisaExpiryDate"]) {
								$style="style='color: #ff0000'" ;
							}
							print "<span $style>" ;
							print dateConvertBack($guid, $row["newvisaExpiryDate"]) ;
							print "</span>" ;
						print "</td>" ;
						print "<td>" ;
							if ($row["visaExpiryDate"]!=$row["newvisaExpiryDate"]) { print "<input checked type='checkbox' name='newvisaExpiryDateOn'><input name='newvisaExpiryDate' type='hidden' value='" . htmlprep($row["newvisaExpiryDate"]) . "'>" ; }
						print "</td>" ;
					print "</tr>" ;
					print "<tr class='even'>" ;
						print "<td>" ;
							print "Profession" ;
						print "</td>" ;
						print "<td>" ;
							print $row["profession"] ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($row["profession"]!=$row["newprofession"]) {
								$style="style='color: #ff0000'" ;
							}
							print "<span $style>" ;
							print $row["newprofession"] ;
							print "</span>" ;
						print "</td>" ;
						print "<td>" ;
							if ($row["profession"]!=$row["newprofession"]) { print "<input checked type='checkbox' name='newprofessionOn'><input name='newprofession' type='hidden' value='" . htmlprep($row["newprofession"]) . "'>" ; }
						print "</td>" ;
					print "</tr>" ;
					print "<tr class='odd'>" ;
						print "<td>" ;
							print "Employer" ;
						print "</td>" ;
						print "<td>" ;
							print $row["employer"] ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($row["employer"]!=$row["newemployer"]) {
								$style="style='color: #ff0000'" ;
							}
							print "<span $style>" ;
							print $row["newemployer"] ;
							print "</span>" ;
						print "</td>" ;
						print "<td>" ;
							if ($row["employer"]!=$row["newemployer"]) { print "<input checked type='checkbox' name='newemployerOn'><input name='newemployer' type='hidden' value='" . htmlprep($row["newemployer"]) . "'>" ; }
						print "</td>" ;
					print "</tr>" ;
					print "<tr class='even'>" ;
						print "<td>" ;
							print "Job Title" ;
						print "</td>" ;
						print "<td>" ;
							print $row["jobTitle"] ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($row["jobTitle"]!=$row["newjobTitle"]) {
								$style="style='color: #ff0000'" ;
							}
							print "<span $style>" ;
							print $row["newjobTitle"] ;
							print "</span>" ;
						print "</td>" ;
						print "<td>" ;
							if ($row["jobTitle"]!=$row["newjobTitle"]) { print "<input checked type='checkbox' name='newjobTitleOn'><input name='newjobTitle' type='hidden' value='" . htmlprep($row["newjobTitle"]) . "'>" ; }
						print "</td>" ;
					print "</tr>" ;
					print "<tr class='odd'>" ;
						print "<td>" ;
							print "Emergency 1 Name" ;
						print "</td>" ;
						print "<td>" ;
							print $row["emergency1Name"] ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($row["emergency1Name"]!=$row["newemergency1Name"]) {
								$style="style='color: #ff0000'" ;
							}
							print "<span $style>" ;
							print $row["newemergency1Name"] ;
							print "</span>" ;
						print "</td>" ;
						print "<td>" ;
							if ($row["emergency1Name"]!=$row["newemergency1Name"]) { print "<input checked type='checkbox' name='newemergency1NameOn'><input name='newemergency1Name' type='hidden' value='" . htmlprep($row["newemergency1Name"]) . "'>" ; }
						print "</td>" ;
					print "</tr>" ;
					print "<tr class='even'>" ;
						print "<td>" ;
							print "Emergency 1 Number 1" ;
						print "</td>" ;
						print "<td>" ;
							print $row["emergency1Number1"] ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($row["emergency1Number1"]!=$row["newemergency1Number1"]) {
								$style="style='color: #ff0000'" ;
							}
							print "<span $style>" ;
							print $row["newemergency1Number1"] ;
							print "</span>" ;
						print "</td>" ;
						print "<td>" ;
							if ($row["emergency1Number1"]!=$row["newemergency1Number1"]) { print "<input checked type='checkbox' name='newemergency1Number1On'><input name='newemergency1Number1' type='hidden' value='" . htmlprep($row["newemergency1Number1"]) . "'>" ; }
						print "</td>" ;
					print "</tr>" ;
					print "<tr class='odd'>" ;
						print "<td>" ;
							print "Emergency 1 Number 2" ;
						print "</td>" ;
						print "<td>" ;
							print $row["emergency1Number2"] ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($row["emergency1Number2"]!=$row["newemergency1Number2"]) {
								$style="style='color: #ff0000'" ;
							}
							print "<span $style>" ;
							print $row["newemergency1Number2"] ;
							print "</span>" ;
						print "</td>" ;
						print "<td>" ;
							if ($row["emergency1Number2"]!=$row["newemergency1Number2"]) { print "<input checked type='checkbox' name='newemergency1Number2On'><input name='newemergency1Number2' type='hidden' value='" . htmlprep($row["newemergency1Number2"]) . "'>" ; }
						print "</td>" ;
					print "</tr>" ;
					print "<tr class='even'>" ;
						print "<td>" ;
							print "Emergency 1 Relationship" ;
						print "</td>" ;
						print "<td>" ;
							print $row["emergency1Relationship"] ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($row["emergency1Relationship"]!=$row["newemergency1Relationship"]) {
								$style="style='color: #ff0000'" ;
							}
							print "<span $style>" ;
							print $row["newemergency1Relationship"] ;
							print "</span>" ;
						print "</td>" ;
						print "<td>" ;
							if ($row["emergency1Relationship"]!=$row["newemergency1Relationship"]) { print "<input checked type='checkbox' name='newemergency1RelationshipOn'><input name='newemergency1Relationship' type='hidden' value='" . htmlprep($row["newemergency1Relationship"]) . "'>" ; }
						print "</td>" ;
					print "</tr>" ;
					print "<tr class='odd'>" ;
						print "<td>" ;
							print "Emergency 2 Name" ;
						print "</td>" ;
						print "<td>" ;
							print $row["emergency2Name"] ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($row["emergency2Name"]!=$row["newemergency2Name"]) {
								$style="style='color: #ff0000'" ;
							}
							print "<span $style>" ;
							print $row["newemergency2Name"] ;
							print "</span>" ;
						print "</td>" ;
						print "<td>" ;
							if ($row["emergency2Name"]!=$row["newemergency2Name"]) { print "<input checked type='checkbox' name='newemergency2NameOn'><input name='newemergency2Name' type='hidden' value='" . htmlprep($row["newemergency2Name"]) . "'>" ; }
						print "</td>" ;
					print "</tr>" ;
					print "<tr class='even'>" ;
						print "<td>" ;
							print "Emergency 2 Number 1" ;
						print "</td>" ;
						print "<td>" ;
							print $row["emergency2Number1"] ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($row["emergency2Number1"]!=$row["newemergency2Number1"]) {
								$style="style='color: #ff0000'" ;
							}
							print "<span $style>" ;
							print $row["newemergency2Number1"] ;
							print "</span>" ;
						print "</td>" ;
						print "<td>" ;
							if ($row["emergency2Number1"]!=$row["newemergency2Number1"]) { print "<input checked type='checkbox' name='newemergency2Number1On'><input name='newemergency2Number1' type='hidden' value='" . htmlprep($row["newemergency2Number1"]) . "'>" ; }
						print "</td>" ;
					print "</tr>" ;
					print "<tr class='odd'>" ;
						print "<td>" ;
							print "Emergency 2 Number 2" ;
						print "</td>" ;
						print "<td>" ;
							print $row["emergency2Number2"] ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($row["emergency2Number2"]!=$row["newemergency2Number2"]) {
								$style="style='color: #ff0000'" ;
							}
							print "<span $style>" ;
							print $row["newemergency2Number2"] ;
							print "</span>" ;
						print "</td>" ;
						print "<td>" ;
							if ($row["emergency2Number2"]!=$row["newemergency2Number2"]) { print "<input checked type='checkbox' name='newemergency2Number2On'><input name='newemergency2Number2' type='hidden' value='" . htmlprep($row["newemergency2Number2"]) . "'>" ; }
						print "</td>" ;
					print "</tr>" ;
					print "<tr class='even'>" ;
						print "<td>" ;
							print "Emergency 2 Relationship" ;
						print "</td>" ;
						print "<td>" ;
							print $row["emergency2Relationship"] ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($row["emergency2Relationship"]!=$row["newemergency2Relationship"]) {
								$style="style='color: #ff0000'" ;
							}
							print "<span $style>" ;
							print $row["newemergency2Relationship"] ;
							print "</span>" ;
						print "</td>" ;
						print "<td>" ;
							if ($row["emergency2Relationship"]!=$row["newemergency2Relationship"]) { print "<input checked type='checkbox' name='newemergency2RelationshipOn'><input name='newemergency2Relationship' type='hidden' value='" . htmlprep($row["newemergency2Relationship"]) . "'>" ; }
						print "</td>" ;
					print "</tr>" ;
					print "<tr class='odd'>" ;
						print "<td>" ;
							print "Vehicle Registration" ;
						print "</td>" ;
						print "<td>" ;
							print $row["vehicleRegistration"] ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($row["vehicleRegistration"]!=$row["newvehicleRegistration"]) {
								$style="style='color: #ff0000'" ;
							}
							print "<span $style>" ;
							print $row["newvehicleRegistration"] ;
							print "</span>" ;
						print "</td>" ;
						print "<td>" ;
							if ($row["vehicleRegistration"]!=$row["newvehicleRegistration"]) { print "<input checked type='checkbox' name='newvehicleRegistrationOn'><input name='newvehicleRegistration' type='hidden' value='" . htmlprep($row["newvehicleRegistration"]) . "'>" ; }
						print "</td>" ;
					print "</tr>" ;
					//Check if any roles are "Student"
					$privacySet=false ;
					try {
						$dataRoles=array("gibbonPersonID"=>$row["gibbonPersonID"]); 
						$sqlRoles="SELECT gibbonRoleIDAll FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID" ;
						$resultRoles=$connection2->prepare($sqlRoles);
						$resultRoles->execute($dataRoles);
					}
					catch(PDOException $e) { }
					if ($resultRoles->rowCount()==1) {
						$rowRoles=$resultRoles->fetch() ;
					
						$isStudent=false ;
						$roles=explode(",", $rowRoles["gibbonRoleIDAll"]) ;
						foreach ($roles as $role) {
							if (getRoleCategory($role, $connection2)=="Student") {
								$isStudent=true ;
							}
						}
						if ($isStudent) {
							$privacySetting=getSettingByScope( $connection2, "User Admin", "privacy" ) ;
							$privacyBlurb=getSettingByScope( $connection2, "User Admin", "privacyBlurb" ) ;
							if ($privacySetting=="Y" AND $privacyBlurb!="") {
								print "<tr class='even'>" ;
									print "<td>" ;
										print "Image Privacy" ;
									print "</td>" ;
									print "<td>" ;
										print $row["privacy"] ;
									print "</td>" ;
									print "<td>" ;
										$style="" ;
										if ($row["privacy"]!=$row["newprivacy"]) {
											$style="style='color: #ff0000'" ;
										}
										print "<span $style>" ;
										print $row["newprivacy"] ;
										print "</span>" ;
									print "</td>" ;
									print "<td>" ;
										if ($row["privacy"]!=$row["newprivacy"]) { print "<input checked type='checkbox' name='newprivacyOn'><input name='newprivacy' type='hidden' value='" . htmlprep($row["newprivacy"]) . "'>" ; }
									print "</td>" ;
								print "</tr>" ;
								$privacySet=true ;
							}
						}
					}
					if ($privacySet==false) {
						print "<input type=\"hidden\" name=\"newprivacyOn\" value=\"\">" ;
					}
					
					
					print "<tr>" ;
							print "<td class='right' colspan=4>" ;
								print "<input name='gibbonPersonID' type='hidden' value='" . $row["gibbonPersonID"] . "'>" ;
								print "<input name='address' type='hidden' value='" . $_GET["q"] . "'>" ;
								print "<input type='submit' value='Submit'>" ;
							print "</td>" ;
						print "</tr>" ;
				print "</table>" ;
				?>
			</form>
			<?
		}
	}
}
?>