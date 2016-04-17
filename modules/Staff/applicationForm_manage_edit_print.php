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

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Staff/applicationForm_manage_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	print "<h2>" ;
	print __($guid, "Staff Application Form Printout") ;
	print "</h2>" ;
		
	$gibbonStaffApplicationFormID=$_GET["gibbonStaffApplicationFormID"] ;
	$search="" ;
	if (isset($_GET["search"])) {
		$search=$_GET["search"] ;
	}
	
	if ($gibbonStaffApplicationFormID=="") {
		print "<div class='error'>" ;
		print __($guid, "You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		//Proceed!
		try {
			$data=array("gibbonStaffApplicationFormID"=>$gibbonStaffApplicationFormID);
			$sql="SELECT gibbonStaffApplicationForm.*, gibbonStaffJobOpening.jobTitle, gibbonStaffJobOpening.type FROM gibbonStaffApplicationForm JOIN gibbonStaffJobOpening ON (gibbonStaffApplicationForm.gibbonStaffJobOpeningID=gibbonStaffJobOpening.gibbonStaffJobOpeningID) LEFT JOIN gibbonPerson ON (gibbonStaffApplicationForm.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonStaffApplicationFormID=:gibbonStaffApplicationFormID" ; 
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
	
		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print __($guid, "There is no data to display, or an error has occurred.") ;
			print "</div>" ;
		}
		else {
			$row=$result->fetch() ;
			print "<h4>" . __($guid, 'For Office Use') . "</h4>" ;
			print "<table cellspacing='0' style='width: 100%'>" ;
				print "<tr>" ;
					print "<td style='width: 25%; padding-top: 15px; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>" . __($guid, 'Application ID') . "</span><br/>" ;
						print "<i>" . htmlPrep($row["gibbonStaffApplicationFormID"]) . "</i>" ;
					print "</td>" ;
					print "<td style='width: 25%; padding-top: 15px; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>" . __($guid, 'Priority') . "</span><br/>" ;
						print "<i>" . htmlPrep($row["priority"]) . "</i>" ;
					print "</td>" ;
					print "<td style='width: 50%; padding-top: 15px; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>" . __($guid, 'Status') . "</span><br/>" ;
						print "<i>" . htmlPrep($row["status"]) . "</i>" ;
					print "</td>" ;
				print "</tr>" ;
				print "<tr>" ;
					print "<td style='padding-top: 15px; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>" . __($guid, 'Start Date') . "</span><br/>" ;
						print "<i>" . dateConvertBack($guid, $row["dateStart"]). "</i>" ;
					print "</td>" ;
					print "<td style='padding-top: 15px; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>" . __($guid, 'Milestones') . "</span><br/>" ;
						print "<i>" . htmlPrep($row["milestones"]) . "</i>" ;
					print "</td>" ;
					print "<td style='padding-top: 15px; vertical-align: top'>" ;
						
					print "</td>" ;
				print "</tr>" ;
				if ($row["notes"]!="") {
					print "<tr>" ;
						print "<td style='padding-top: 15px; vertical-align: top' colspan=3>" ;
							print "<span style='font-size: 115%; font-weight: bold'>" . __($guid, 'Notes') . "</span><br/>" ;
							print "<i>" . $row["notes"] . "</i>" ;
						print "</td>" ;
					print "</tr>" ;
				}
			print "</table>" ;
			
			print "<h4>" . __($guid, 'Job Related Information') . "</h4>" ;
			print "<table cellspacing='0' style='width: 100%'>" ;
				print "<tr>" ;
					print "<td style='width: 33%; padding-top: 15px; vertical-align: top' colspan=2>" ;
						print "<span style='font-size: 115%; font-weight: bold'>" . __($guid, 'Job Opening') . "</span><br/>" ;
						print "<i>" . htmlPrep($row["jobTitle"]) . "</i>" ;
					print "</td>" ;
					print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
						print "<span style='font-size: 115%; font-weight: bold'>" . __($guid, 'Job Type') . "</span><br/>" ;
						print "<i>" . htmlPrep($row["type"]) . "</i>" ;
					print "</td>" ;
				print "</tr>" ;
				print "<tr>" ;
					print "<td style='width: 33%; padding-top: 15px; vertical-align: top' colspan=3>" ;
						print "<span style='font-size: 115%; font-weight: bold'>" . __($guid, 'Application Questions') . "</span><br/>" ;
						print "<i>" . addSlashes($row["questions"]) . "</i>" ;
					print "</td>" ;
				print "</tr>" ;
			print "</table>" ;
			
			print "<h4>" . __($guid, 'Applicant Details') . "</h4>" ;
			print "<table cellspacing='0' style='width: 100%'>" ;
				if ($row["gibbonPersonID"]!="") {
					print "<tr>" ;
						print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
							print "<span style='font-size: 115%; font-weight: bold'>" . __($guid, 'Internal Candidate') . "</span><br/>" ;
						print "</td>" ;
						print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
							print "<span style='font-size: 115%; font-weight: bold'>" . __($guid, 'Surname') . "</span><br/>" ;
							print "<i>" . htmlPrep($row["surname"]) . "</i>" ;
						print "</td>" ;
						print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
							print "<span style='font-size: 115%; font-weight: bold'>" . __($guid, 'Preferred Name') . "</span><br/>" ;
							print "<i>" . htmlPrep($row["preferredName"]) . "</i>" ;
						print "</td>" ;
					print "</tr>" ;
				}
				else {
					print "<tr>" ;
						print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
							print "<span style='font-size: 115%; font-weight: bold'>" . __($guid, 'Surname') . "</span><br/>" ;
							print "<i>" . htmlPrep($row["surname"]) . "</i>" ;
						print "</td>" ;
						print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
							print "<span style='font-size: 115%; font-weight: bold'>" . __($guid, 'Preferred Name') . "</span><br/>" ;
							print "<i>" . htmlPrep($row["preferredName"]) . "</i>" ;
						print "</td>" ;
						print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
							print "<span style='font-size: 115%; font-weight: bold'>" . __($guid, 'Official Name') . "</span><br/>" ;
							print "<i>" . htmlPrep($row["officialName"]) . "</i>" ;
						print "</td>" ;
					print "</tr>" ;
					print "<tr>" ;
						print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
							print "<span style='font-size: 115%; font-weight: bold'>" . __($guid, 'Gender') . "</span><br/>" ;
							print "<i>" . htmlPrep($row["gender"]). "</i>" ;
						print "</td>" ;
						print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
							print "<span style='font-size: 115%; font-weight: bold'>" . __($guid, 'Date of Birth') . "</span><br/>" ;
							print "<i>" . dateConvertBack($guid, $row["dob"]). "</i>" ;
						print "</td>" ;
						print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
							
						print "</td>" ;
					print "</tr>" ;
					print "<tr>" ;
						print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
							print "<span style='font-size: 115%; font-weight: bold'>" . __($guid, 'First Language') . "</span><br/>" ;
							print "<i>" . htmlPrep($row["languageFirst"]). "</i>" ;
						print "</td>" ;
						print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
							print "<span style='font-size: 115%; font-weight: bold'>" . __($guid, 'Second Language') . "</span><br/>" ;
							print "<i>" . htmlPrep($row["languageSecond"]). "</i>" ;
						print "</td>" ;
						print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
							print "<span style='font-size: 115%; font-weight: bold'>" . __($guid, 'Third Language') . "</span><br/>" ;
							print "<i>" . htmlPrep($row["languageThird"]). "</i>" ;
						print "</td>" ;
					print "</tr>" ;
					print "<tr>" ;
						print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
							print "<span style='font-size: 115%; font-weight: bold'>" . __($guid, 'Country of Birth') . "</span><br/>" ;
							print "<i>" . htmlPrep($row["countryOfBirth"]). "</i>" ;
						print "</td>" ;
						print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
							print "<span style='font-size: 115%; font-weight: bold'>" . __($guid, 'Citizenship') . "</span><br/>" ;
							print "<i>" . htmlPrep($row["citizenship1"]). "</i>" ;
						print "</td>" ;
						print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
							print "<span style='font-size: 115%; font-weight: bold'>" . __($guid, 'Passport Number') . "</span><br/>" ;
							print "<i>" . htmlPrep($row["citizenship1Passport"]). "</i>" ;
						print "</td>" ;
					print "</tr>" ;
					print "<tr>" ;
						print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
							print "<span style='font-size: 115%; font-weight: bold'>" ;
								if ($_SESSION[$guid]["country"]=="") {
									print "<b>" . __($guid, 'National ID Card Number') . "</b>" ;
								}
								else {
									print "<b>" . $_SESSION[$guid]["country"] . " " . __($guid, 'ID Card Number') . "</b>" ;
								}
							print "</span><br/>" ;
							print "<i>" . htmlPrep($row["nationalIDCardNumber"]). "</i>" ;
						print "</td>" ;
						print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
							print "<span style='font-size: 115%; font-weight: bold'>" ;
								if ($_SESSION[$guid]["country"]=="") {
									print "<b>" . __($guid, 'Residency/Visa Type') . "</b>" ;
								}
								else {
									print "<b>" . $_SESSION[$guid]["country"] . " " . __($guid, 'Residency/Visa Type') . "</b>" ;
								}
							print "</span><br/>" ;
							print "<i>" . htmlPrep($row["residencyStatus"]). "</i>" ;
						print "</td>" ;
						print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
							print "<span style='font-size: 115%; font-weight: bold'>" ;
								if ($_SESSION[$guid]["country"]=="") {
									print "<b>" . __($guid, 'Visa Expiry Date') . "</b>" ;
								}
								else {
									print "<b>" . $_SESSION[$guid]["country"] . " " . __($guid, 'Visa Expiry Date') . "</b>" ;
								}
							print "</span><br/>" ;
							print "<i>" . dateConvertBack($guid, $row["visaExpiryDate"]). "</i>" ;
						print "</td>" ;
					print "</tr>" ;
					print "<tr>" ;
						print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
							print "<span style='font-size: 115%; font-weight: bold'>" . __($guid, 'Email') . "</span><br/>" ;
							print "<i>" . htmlPrep($row["email"]). "</i>" ;
						print "</td>" ;
						print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
							print "<span style='font-size: 115%; font-weight: bold'>" . __($guid, 'Phone') . "</span><br/>" ;
							print "<i>" ;
							if ($row["phone1Type"]!="") {
								print htmlPrep($row["phone1Type"]) . ": " ;
							}
							if ($row["phone1CountryCode"]!="") {
								print htmlPrep($row["phone1CountryCode"]) . " " ;
							}
							print htmlPrep(formatPhone($row["phone1"])) . " " ;
							print "</i>" ;
						print "</td>" ;
						print "<td style='width: 33%; padding-top: 15px; vertical-align: top'>" ;
						
						print "</td>" ;
					print "</tr>" ;
				}
			print "</table>" ;
		}
	}
}
?>