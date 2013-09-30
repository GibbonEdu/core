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

session_start() ;

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/User Admin/applicationForm_manage_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	print "<h2>" ;
	print "Application Form Printout" ;
	print "</h2>" ;
		
	$gibbonApplicationFormID=$_GET["gibbonApplicationFormID"] ;
	$search=$_GET["search"] ;
	
	if ($gibbonApplicationFormID=="") {
		print "<div class='error'>" ;
		print "No application form has been specified." ;
		print "</div>" ;
	}
	else {
		//Proceed!
		try {
			$data=array("gibbonApplicationFormID"=>$gibbonApplicationFormID); 
			$sql="SELECT * FROM gibbonApplicationForm WHERE gibbonApplicationFormID=:gibbonApplicationFormID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
	
		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print "There is no data to display, or an error has occurred." ;
			print "</div>" ;
		}
		else {
			$row=$result->fetch() ;
			print "<h4>For Office Use</h4>" ;
			print "<table cellspacing='0' style='width: 100%'>" ;
				print "<tr>" ;
					print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>Application ID</span><br/>" ;
						print "<i>" . htmlPrep($row["gibbonApplicationFormID"]) . "</i>" ;
					print "</td>" ;
					print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>Priority</span><br/>" ;
						print "<i>" . htmlPrep($row["priority"]) . "</i>" ;
					print "</td>" ;
					print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>Status</span><br/>" ;
						print "<i>" . htmlPrep($row["status"]) . "</i>" ;
					print "</td>" ;
				print "</tr>" ;
				print "<tr>" ;
					print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>Start Date</span><br/>" ;
						print "<i>" . dateConvertBack($row["dateStart"]). "</i>" ;
					print "</td>" ;
					print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>Year of Entry</span><br/>" ;
						try {
							$dataSelect=array("gibbonSchoolYearIDEntry"=>$row["gibbonSchoolYearIDEntry"]); 
							$sqlSelect="SELECT name FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearIDEntry" ;
							$resultSelect=$connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($resultSelect->rowCount()==1) {
							$rowSelect=$resultSelect->fetch() ;
							print "<i>" . $rowSelect["name"] . "</i>" ;
						}
					print "</td>" ;
					print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>Year Group at Entry</span><br/>" ;
						try {
							$dataSelect=array("gibbonYearGroupIDEntry"=>$row["gibbonYearGroupIDEntry"]); 
							$sqlSelect="SELECT name FROM gibbonYearGroup WHERE gibbonYearGroupID=:gibbonYearGroupIDEntry" ;
							$resultSelect=$connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($resultSelect->rowCount()==1) {
							$rowSelect=$resultSelect->fetch() ;
							print "<i>" . $rowSelect["name"] ;
							$dayTypeOptions=getSettingByScope($connection2, 'User Admin', 'dayTypeOptions') ;
							if ($dayTypeOptions!="") {
								print " (" . $row["dayType"] . ")" ;
							}
							print "</i>" ;
						}
					print "</td>" ;
				print "</tr>" ;
				print "<tr>" ;
					print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>Roll Group at Entry</span><br/>" ;
						try {
							$dataSelect=array("gibbonRollGroupID"=>$row["gibbonRollGroupID"]); 
							$sqlSelect="SELECT name FROM gibbonRollGroup WHERE gibbonRollGroupID=:gibbonRollGroupID" ;
							$resultSelect=$connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($resultSelect->rowCount()==1) {
							$rowSelect=$resultSelect->fetch() ;
							print "<i>" . $rowSelect["name"] . "</i>" ;
						}
					print "</td>" ;
					print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>Milestones</span><br/>" ;
						print "<i>" . htmlPrep($row["milestones"]) . "</i>" ;
					print "</td>" ;
					print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
						$currency=getSettingByScope($connection2, "System", "currency") ;
						$applicationFee=getSettingByScope($connection2, "Application Form", "applicationFee") ;
						if ($applicationFee>0 AND is_numeric($applicationFee)) {		
							print "<span style='font-size: 115%; font-weight: bold'>Payment</span><br/>" ;
							print "<i>" . htmlPrep($row["paymentMade"]) . "</i>" ;
							if ($row["paypalPaymentToken"]!="" OR $row["paypalPaymentPayerID"]!="") {
								if ($row["paypalPaymentToken"]!="") {
									print "<i>PayPal Payment Token: " . $row["paypalPaymentToken"] . "</i><br/>" ;
								}
								if ($row["paypalPaymentPayerID"]!="") {
									print "<i>PayPal Payment PayerID: " . $row["paypalPaymentPayerID"] . "</i>" ;
								}
							}
						}
					print "</td>" ;
				print "</tr>" ;
				if ($row["notes"]!="") {
					print "<tr>" ;
						print "<td style='width: 33%; padding-top: 15px; vertical-align: top' colspan=3>" ;
							print "<span style='font-size: 115%; font-weight: bold'>Notes</span><br/>" ;
							print "<i>" . $row["notes"] . "</i>" ;
						print "</td>" ;
					print "</tr>" ;
				}
			print "</table>" ;
			
			
			print "<h4>Student Details</h4>" ;
			print "<table cellspacing='0' style='width: 100%'>" ;
				print "<tr>" ;
					print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>Surname</span><br/>" ;
						print "<i>" . htmlPrep($row["surname"]) . "</i>" ;
					print "</td>" ;
					print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>Preferred Name</span><br/>" ;
						print "<i>" . htmlPrep($row["preferredName"]) . "</i>" ;
					print "</td>" ;
					print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>Official Name</span><br/>" ;
						print "<i>" . htmlPrep($row["officialName"]) . "</i>" ;
					print "</td>" ;
				print "</tr>" ;
				print "<tr>" ;
					print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>Gender</span><br/>" ;
						print "<i>" . htmlPrep($row["gender"]). "</i>" ;
					print "</td>" ;
					print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>Date of Birth</span><br/>" ;
						print "<i>" . dateConvertBack($row["dob"]). "</i>" ;
					print "</td>" ;
					print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>Current/Last School</span><br/>" ;
						$school="" ;
						if ($row["schoolDate1"]>$row["schoolDate2"] AND $row["schoolName1"]!="") {
							$school=$row["schoolName1"] ;
						}
						else if ($row["schoolDate2"]>$row["schoolDate1"] AND $row["schoolName2"]!="") {
							$school=$row["schoolName2"] ;
						}
						else if ($row["schoolName1"]!="") {
							$school=$row["schoolName1"] ;
						}
						if ($school!="") {
							if (strlen($school)<=15) {
								print "<i>" . htmlPrep($school). "</i>" ;
							}
							else {
								print "<i><span title='" . $school . "'>" . substr($school, 0, 15) . "...</span></i>" ;
							}
						}
						else {
							print "<i>Unspecified</i>" ;
						}
					print "</td>" ;
				print "</tr>" ;
				print "<tr>" ;
					print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>Home Language</span><br/>" ;
						print "<i>" . htmlPrep($row["languageHome"]). "</i>" ;
					print "</td>" ;
					print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>First Language</span><br/>" ;
						print "<i>" . htmlPrep($row["languageFirst"]). "</i>" ;
					print "</td>" ;
					print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>Second Language</span><br/>" ;
						print "<i>" . htmlPrep($row["languageSecond"]). "</i>" ;
					print "</td>" ;
				print "</tr>" ;
				print "<tr>" ;
					print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>Country of Birth</span><br/>" ;
						print "<i>" . htmlPrep($row["countryOfBirth"]). "</i>" ;
					print "</td>" ;
					print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>Citizenship</span><br/>" ;
						print "<i>" . htmlPrep($row["citizenship1"]). "</i>" ;
					print "</td>" ;
					print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>Passport Number</span><br/>" ;
						print "<i>" . htmlPrep($row["citizenship1Passport"]). "</i>" ;
					print "</td>" ;
				print "</tr>" ;
				print "<tr>" ;
					print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>" ;
							if ($_SESSION[$guid]["country"]=="") {
								print "<b>National ID Card Number</b>" ;
							}
							else {
								print "<b>" . $_SESSION[$guid]["country"] . " ID Card Number</b>" ;
							}
						print "</span><br/>" ;
						print "<i>" . htmlPrep($row["nationalIDCardNumber"]). "</i>" ;
					print "</td>" ;
					print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>" ;
							if ($_SESSION[$guid]["country"]=="") {
								print "<b>Residency/Visa Type</b>" ;
							}
							else {
								print "<b>" . $_SESSION[$guid]["country"] . " Residency/Visa Type</b>" ;
							}
						print "</span><br/>" ;
						print "<i>" . htmlPrep($row["residencyStatus"]). "</i>" ;
					print "</td>" ;
					print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>" ;
							if ($_SESSION[$guid]["country"]=="") {
								print "<b>Visa Expiry Date</b>" ;
							}
							else {
								print "<b>" . $_SESSION[$guid]["country"] . " Visa Expiry Date</b>" ;
							}
						print "</span><br/>" ;
						print "<i>" . dateConvertBack($row["visaExpiryDate"]). "</i>" ;
					print "</td>" ;
				print "</tr>" ;
				print "<tr>" ;
					print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>Email</span><br/>" ;
						print "<i>" . htmlPrep($row["email"]). "</i>" ;
					print "</td>" ;
					print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>Address</span><br/>" ;
						print "<i>" ;
						print htmlPrep($row["address1"]) . "<br/>" ;
						print htmlPrep($row["address1District"]) . "<br/>" ;
						print htmlPrep($row["address1Country"]) . "<br/>" ;
						print "</i>" ;
					print "</td>" ;
					print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>Phone</span><br/>" ;
						print "<i>" ;
						if ($row["phone1Type"]!="") {
							print htmlPrep($row["phone1Type"]) . ": " ;
						}
						if ($row["phone1CountryCode"]!="") {
							print htmlPrep($row["phone1CountryCode"]) . " " ;
						}
						print htmlPrep($row["phone1"]) . " " ;
						print "</i>" ;
					print "</td>" ;
				print "</tr>" ;
				if ($row["medicalInformation"]!="") {
					print "<tr>" ;
						print "<td style='width: 33%; padding-top: 15px; vertical-align: top' colspan=3>" ;
							print "<span style='font-size: 115%; font-weight: bold'>Medical Information</span><br/>" ;
							print "<i>" . $row["medicalInformation"] . "</i>" ;
						print "</td>" ;
					print "</tr>" ;
				}
				if ($row["developmentInformation"]!="") {
					print "<tr>" ;
						print "<td style='width: 33%; padding-top: 15px; vertical-align: top' colspan=3>" ;
							print "<span style='font-size: 115%; font-weight: bold'>Development Information</span><br/>" ;
							print "<i>" . $row["developmentInformation"] . "</i>" ;
						print "</td>" ;
					print "</tr>" ;
				}
			print "</table>" ;
			
			
			
			
			print "<h4>Parents/Gaurdians</h4>" ;
			//No family in Gibbon
			if ($row["gibbonFamilyID"]=="") {

				//Parent 1 in Gibbon
				if ($row["parent1gibbonPersonID"]!="") {
					$start=2 ;
		
					//Spit out parent 1 data from Gibbon
					try {
						$dataMember=array("gibbonPersonID"=>$row["parent1gibbonPersonID"]); 
						$sqlMember="SELECT * FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID" ;
						$resultMember=$connection2->prepare($sqlMember);
						$resultMember->execute($dataMember);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					
					while ($rowMember=$resultMember->fetch()) {
						print "<table cellspacing='0' style='width: 100%'>" ;
							print "<tr>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>Parent 1 Name</span><br/>" ;
									print formatName($rowMember["title"], $rowMember["preferredName"], $rowMember["surname"], "Parent") ;
								print "</td>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>Parent 1 Relationship</span><br/>" ;
										if ($rowMember["role"]=="Parent") {
											if ($rowMember["gender"]=="M") {
												print "Father" ;
											}
											else if ($rowMember["gender"]=="F") {
												print "Mother" ;
											}
											else {
												print $rowMember["role"] ;
											}
										}
										else {
											print $rowMember["role"] ;
										}
								print "</td>" ;
								print "<td style='padding-top: 15px; width: 34%; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>Parent 1 Contact Priority</span><br/>" ;
									print "1" ;
								print "</td>" ;
							print "</tr>" ;
							print "<tr>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>Parent 1 First Language</span><br/>" ;
									print $rowMember["languageFirst"] ;
								print "</td>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>Parent 1 Phone</span><br/>" ;
									if ($rowMember["phone1"]!="" OR $rowMember["phone2"]!="" OR $rowMember["phone3"]!="" OR $rowMember["phone4"]!="") {
										for ($i=1; $i<5; $i++) {
											if ($rowMember["phone" . $i]!="") {
												if ($rowMember["phone" . $i . "Type"]!="") {
													print "<i>" . $rowMember["phone" . $i . "Type"] . ":</i> " ;
												}
												if ($rowMember["phone" . $i . "CountryCode"]!="") {
													print "+" . $rowMember["phone" . $i . "CountryCode"] . " " ;
												}
												print $rowMember["phone" . $i] . "<br/>" ;
											}
										}
									}
								print "</td>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>Parent 1 Email</span><br/>" ;
									if ($rowMember["email"]!="" OR $rowMember["emailAlternate"]!="") {
										if ($rowMember["email"]!="") {
											print "Email: <a href='mailto:" . $rowMember["email"] . "'>" . $rowMember["email"] . "</a><br/>" ;
										}
										if ($rowMember["emailAlternate"]!="") {
											print "Email 2: <a href='mailto:" . $rowMember["emailAlternate"] . "'>" . $rowMember["emailAlternate"] . "</a><br/>" ;
										}
										print "<br/>" ;
									}
								print "</td>" ;
							print "</tr>" ;
						print "</table>" ;
					}
				}
				//Parent 1 not in Gibbon
				else {
					$start=1 ;
				}
				for ($i=$start;$i<3;$i++) {
					//Spit out parent1/parent2 data from application, depending on parent1 status above.
					print "<table cellspacing='0' style='width: 100%'>" ;
						print "<tr>" ;
							print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
								print "<span style='font-size: 115%; font-weight: bold'>Parent $i Name</span><br/>" ;
								print formatName($row["parent" . $i . "title"], $row["parent" . $i . "preferredName"], $row["parent" . $i . "surname"], "Parent") ;
							print "</td>" ;
							print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
								print "<span style='font-size: 115%; font-weight: bold'>Parent $i Relationship</span><br/>" ;
									if ($row["parent" . $i . "role"]=="Parent") {
										if ($row["parent" . $i . "gender"]=="M") {
											print "Father" ;
										}
										else if ($row["parent" . $i . "gender"]=="F") {
											print "Mother" ;
										}
										else {
											print $row["parent" . $i . "role"] ;
										}
									}
									else {
										print $row["parent" . $i . "role"] ;
									}
							print "</td>" ;
							print "<td style='padding-top: 15px; width: 34%; vertical-align: top'>" ;
								print "<span style='font-size: 115%; font-weight: bold'>Parent $i Contact Priority</span><br/>" ;
								print $i ;
							print "</td>" ;
						print "</tr>" ;
						print "<tr>" ;
							print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
								print "<span style='font-size: 115%; font-weight: bold'>Parent $i First Language</span><br/>" ;
								print $row["parent" . $i . "languageFirst"] ;
							print "</td>" ;
							print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
								print "<span style='font-size: 115%; font-weight: bold'>Parent $i Phone</span><br/>" ;
								if ($row["parent" . $i . "phone1"]!="" OR $row["parent" . $i . "phone2"]!="" OR $row["parent" . $i . "phone3"]!="" OR $row["parent" . $i . "phone4"]!="") {
									for ($n=1; $n<5; $n++) {
										if ($row["parent" . $i . "phone" . $n]!="") {
											if ($row["parent" . $i . "phone" . $n . "Type"]!="") {
												print "<i>" . $row["parent" . $i . "phone" . $n . "Type"] . ":</i> " ;
											}
											if ($row["parent" . $i . "phone" . $n . "CountryCode"]!="") {
												print "+" . $row["parent" . $i . "phone" . $n . "CountryCode"] . " " ;
											}
											print $row["parent" . $i . "phone" . $n] . "<br/>" ;
										}
									}
								}
							print "</td>" ;
							print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
								print "<span style='font-size: 115%; font-weight: bold'>Parent $i Email</span><br/>" ;
								if ($row["parent" . $i . "email"]!="" OR $row["parent" . $i . "emailAlternate"]!="") {
									if ($row["parent" . $i . "email"]!="") {
										print "Email: <a href='mailto:" . $row["parent" . $i . "email"] . "'>" . $row["parent" . $i . "email"] . "</a><br/>" ;
									}
									if ($row["parent" . $i . "emailAlternate"]!="") {
										print "Email 2: <a href='mailto:" . $row["parent" . $i . "emailAlternate"] . "'>" . $row["parent" . $i . "emailAlternate"] . "</a><br/>" ;
									}
									print "<br/>" ;
								}
							print "</td>" ;
						print "</tr>" ;
					print "</table>" ;
				}
			}
			//Yes family
			else {
				//Spit out parent1/parent2 data from Gibbon 
				try {
					$dataFamily=array("gibbonFamilyID"=>$row["gibbonFamilyID"]); 
					$sqlFamily="SELECT * FROM gibbonFamily WHERE gibbonFamilyID=:gibbonFamilyID" ;
					$resultFamily=$connection2->prepare($sqlFamily);
					$resultFamily->execute($dataFamily);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				
				if ($resultFamily->rowCount()<1) {
					print "<div class='error'>" ;
						print "There is no family information available for the current student.";
					print "</div>" ;
				}
				else {
					while ($rowFamily=$resultFamily->fetch()) {
						$count=1 ;
						//Print family information
						print "<table cellspacing='0' style='width: 100%'>" ;
							print "<tr>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>Family Name</span><br/>" ;
									print $rowFamily["name"] ;
								print "</td>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>Family Status</span><br/>" ;
									print $rowFamily["status"] ;
								print "</td>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>Home Language</span><br/>" ;
									print $rowFamily["languageHome"] ;
								print "</td>" ;
							print "</tr>" ;
						print "</table>" ;
						
						
						//Get adults
						try {
							$dataMember=array("gibbonFamilyID"=>$rowFamily["gibbonFamilyID"]); 
							$sqlMember="SELECT * FROM gibbonFamilyAdult JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFamilyID=:gibbonFamilyID ORDER BY contactPriority, surname, preferredName" ;
							$resultMember=$connection2->prepare($sqlMember);
							$resultMember->execute($dataMember);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						
						while ($rowMember=$resultMember->fetch()) {
							print "<table cellspacing='0' style='width: 100%'>" ;
								print "<tr>" ;
									print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
										print "<span style='font-size: 115%; font-weight: bold'>Parent $count Name</span><br/>" ;
										print formatName($rowMember["title"], $rowMember["preferredName"], $rowMember["surname"], "Parent") ;
									print "</td>" ;
									print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
										print "<span style='font-size: 115%; font-weight: bold'>Parent $count Relationship</span><br/>" ;
											if ($rowMember["role"]=="Parent") {
												if ($rowMember["gender"]=="M") {
													print "Father" ;
												}
												else if ($rowMember["gender"]=="F") {
													print "Mother" ;
												}
												else {
													print $rowMember["role"] ;
												}
											}
											else {
												print $rowMember["role"] ;
											}
									print "</td>" ;
									print "<td style='padding-top: 15px; width: 34%; vertical-align: top' colspan=2>" ;
										print "<span style='font-size: 115%; font-weight: bold'>Parent $count Contact Priority</span><br/>" ;
										print $rowMember["contactPriority"] ;
									print "</td>" ;
								print "</tr>" ;
								print "<tr>" ;
									print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
										print "<span style='font-size: 115%; font-weight: bold'>Parent $count First Language</span><br/>" ;
										print $rowMember["languageFirst"] ;
									print "</td>" ;
									print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
										print "<span style='font-size: 115%; font-weight: bold'>Parent $count Phone</span><br/>" ;
										if ($rowMember["contactCall"]=="N") {
											print "Do not contact by phone." ;
										}
										else if ($rowMember["contactCall"]=="Y" AND ($rowMember["phone1"]!="" OR $rowMember["phone2"]!="" OR $rowMember["phone3"]!="" OR $rowMember["phone4"]!="")) {
											for ($i=1; $i<5; $i++) {
												if ($rowMember["phone" . $i]!="") {
													if ($rowMember["phone" . $i . "Type"]!="") {
														print "<i>" . $rowMember["phone" . $i . "Type"] . ":</i> " ;
													}
													if ($rowMember["phone" . $i . "CountryCode"]!="") {
														print "+" . $rowMember["phone" . $i . "CountryCode"] . " " ;
													}
													print $rowMember["phone" . $i] . "<br/>" ;
												}
											}
										}
									print "</td>" ;
									print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
										print "<span style='font-size: 115%; font-weight: bold'>Parent $countContact By Email</span><br/>" ;
										if ($rowMember["contactEmail"]=="N") {
											print "Do not contact by email." ;
										}
										else if ($rowMember["contactEmail"]=="Y" AND ($rowMember["email"]!="" OR $rowMember["emailAlternate"]!="")) {
											if ($rowMember["email"]!="") {
												print "Email: <a href='mailto:" . $rowMember["email"] . "'>" . $rowMember["email"] . "</a><br/>" ;
											}
											if ($rowMember["emailAlternate"]!="") {
												print "Email 2: <a href='mailto:" . $rowMember["emailAlternate"] . "'>" . $rowMember["emailAlternate"] . "</a><br/>" ;
											}
											print "<br/>" ;
										}
									print "</td>" ;
								print "</tr>" ;
							print "</table>" ;
							$count++ ;
						}	
					}
				}
			}
			
			$siblingCount=0 ; 
			print "<h4>Siblings</h4>" ;
			print "<table cellspacing='0' style='width: 100%'>" ;
				//Get siblings from the application
				for ($i=1; $i<4; $i++) {
					if ($row["siblingName$i"]!="" OR $row["siblingDOB$i"]!="" OR $row["siblingName$iSchool"]!="") {
						$siblingCount++ ;
						print "<tr>" ;
							print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
								print "<span style='font-size: 115%; font-weight: bold'>Sibling $siblingCount Name</span><br/>" ;
								print "<i>" . htmlPrep($row["siblingName$i"]) . "</i>" ;
							print "</td>" ;
							print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
								print "<span style='font-size: 115%; font-weight: bold'>Sibling $siblingCount Date of Birth</span><br/>" ;
								print "<i>" . dateConvertBack($row["siblingDOB$i"]) . "</i>" ;
							print "</td>" ;
							print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
								print "<span style='font-size: 115%; font-weight: bold'>Sibling $siblingCount School</span><br/>" ;
								print "<i>" . htmlPrep($row["siblingName$iSchool"]) . "</i>" ;
							print "</td>" ;
						print "</tr>" ;
					}
				}
				//Get siblings from Gibbon family
				if ($row["gibbonFamilyID"]!="") {
					try {
						$dataMember=array("gibbonFamilyID"=>$row["gibbonFamilyID"]); 
						$sqlMember="SELECT * FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE gibbonFamilyID=:gibbonFamilyID ORDER BY surname, preferredName" ;
						$resultMember=$connection2->prepare($sqlMember);
						$resultMember->execute($dataMember);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
				
					if ($resultMember->rowCount()>0) {
						while ($rowMember=$resultMember->fetch()) {
							$siblingCount++ ;
							print "<tr>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>Sibling $siblingCount Name</span><br/>" ;
									print formatName($rowMember["title"], $rowMember["preferredName"], $rowMember["surname"], $rowMember["category"]) ;
								print "</td>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>Sibling $siblingCount Date of Birth</span><br/>" ;
									print "<i>" . dateConvertBack($rowMember["dob"]) . "</i>" ;
								print "</td>" ;
								print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
									print "<span style='font-size: 115%; font-weight: bold'>Sibling $siblingCount School</span><br/>" ;
									print "<i>" . $_SESSION[$guid]["organisationName"] . "</i>" ;
								print "</td>" ;
							print "</tr>" ;
						}
					}
				}

				if ($siblingCount<1) {
					print "<tr>" ;
							print "<td style='width: 33%; padding-top: 15px; vertical-align: top' colspan=3>" ;
								print "<div class='warning' style='margin-top: 0px'>" ;
									print "No known siblings" ;
								print "</div>" ;
							print "</td>" ;
						print "</tr>" ;
				}
			print "</table>" ;
		}
	}
}
?>