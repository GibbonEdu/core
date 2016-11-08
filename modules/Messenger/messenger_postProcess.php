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

include "../../functions.php" ;
include "../../config.php" ;

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start() ;

//Increase max execution time, as this stuff gets big
ini_set('max_execution_time', 600);

//Module includes
include "./moduleFunctions.php" ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["address"]) . "/messenger_post.php" ;
$time=time() ;

if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php")==FALSE) {
	//Fail 0
	$URL.="&addReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	if (empty($_POST)) {
		//Fail 5
		$URL.="&addReturn=fail5" ;
		header("Location: {$URL}");
	}
	else {
		//Proceed!
		//Setup return variables
		$emailCount=NULL ;
		$smsCount=NULL ;
		$smsBatchCount=NULL ;

		//Validate Inputs
		$email=$_POST["email"] ;
		if ($email!="Y") {
			$email="N" ;
		}
		if ($email=="Y") {
			$from=$_POST["from"] ;
		}
		$emailReplyTo="" ;
		if (isset($_POST["emailReplyTo"])) {
			$emailReplyTo=$_POST["emailReplyTo"] ;
		}
		$messageWall="" ;
		if (isset($_POST["messageWall"])) {
			$messageWall=$_POST["messageWall"] ;
		}
		if ($messageWall!="Y") {
			$messageWall="N" ;
		}
		$date1=NULL ;
		if (isset($_POST["date1"])) {
			if ($_POST["date1"]!="") {
				$date1=dateConvert($guid, $_POST["date1"]) ;
			}
		}
		$date2=NULL ;
		if (isset($_POST["date2"])) {
			if ($_POST["date2"]!="") {
				$date2=dateConvert($guid, $_POST["date2"]) ;
			}
		}
		$date3=NULL ;
		if (isset($_POST["date3"])) {
			if ($_POST["date3"]!="") {
				$date3=dateConvert($guid, $_POST["date3"]) ;
			}
		}
		$sms=NULL ;
		if (isset($_POST["sms"])) {
			$sms=$_POST["sms"] ;
		}
		if ($sms!="Y") {
			$sms="N" ;
		}
		$subject=$_POST["subject"] ;
		$body=stripslashes($_POST["body"]) ;
		$emailReceipt = $_POST["emailReceipt"] ;
		$emailReceiptText = null;
		if (isset($_POST["emailReceiptText"]))
			$emailReceiptText = $_POST["emailReceiptText"] ;

		if ($subject == "" OR $body == "" OR ($email == "Y" AND $from == "") OR $emailReceipt == '' OR ($emailReceipt == "Y" AND $emailReceiptText == "")) {
			//Fail 3
			$URL.="&addReturn=fail3" ;
			header("Location: {$URL}");
		}
		else {
			//Lock table
			try {
				$sql="LOCK TABLES gibbonMessenger WRITE" ;
				$result=$connection2->query($sql);
			}
			catch(PDOException $e) {
				//Fail 2
				$URL.="&addReturn=fail2" ;
				header("Location: {$URL}");
				exit() ;
			}

			//Get next autoincrement
			try {
				$sqlAI="SHOW TABLE STATUS LIKE 'gibbonMessenger'";
				$resultAI=$connection2->query($sqlAI);
			}
			catch(PDOException $e) {
				//Fail 2
				$URL.="&addReturn=fail2" ;
				header("Location: {$URL}");
				exit() ;
			}

			$rowAI=$resultAI->fetch();
			$AI=str_pad($rowAI['Auto_increment'], 12, "0", STR_PAD_LEFT) ;

			//Write to database
			try {
				$data=array("email"=>$email, "messageWall"=>$messageWall, "messageWall_date1"=>$date1, "messageWall_date2"=>$date2, "messageWall_date3"=>$date3, "sms"=>$sms, "subject"=>$subject, "body"=>$body, "emailReceipt" => $emailReceipt, "emailReceiptText" => $emailReceiptText, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "timestamp"=>date("Y-m-d H:i:s"));
				$sql="INSERT INTO gibbonMessenger SET email=:email, messageWall=:messageWall, messageWall_date1=:messageWall_date1, messageWall_date2=:messageWall_date2, messageWall_date3=:messageWall_date3, sms=:sms, subject=:subject, body=:body, emailReceipt=:emailReceipt, emailReceiptText=:emailReceiptText, gibbonPersonID=:gibbonPersonID, timestamp=:timestamp" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) {
				//Fail 2
				$URL.="&addReturn=fail2" ;
				header("Location: {$URL}");
				exit() ;
			}

			try {
				$sql="UNLOCK TABLES" ;
				$result=$connection2->query($sql);
			}
			catch(PDOException $e) {
				//Fail 2
				$URL.="&addReturn=fail2" ;
				header("Location: {$URL}");
				exit() ;
			}

			//TARGETS
			$partialFail=FALSE ;
			$report = array();
			//Get country code
			$countryCode="" ;
			$country=getSettingByScope($connection2, "System", "country") ;
			$countryCodeTemp = '';
			try {
				$dataCountry=array("printable_name"=>$country);
				$sqlCountry="SELECT iddCountryCode FROM gibbonCountry WHERE printable_name=:printable_name" ;
				$resultCountry=$connection2->prepare($sqlCountry);
				$resultCountry->execute($dataCountry);
			}
			catch(PDOException $e) { }
			if ($resultCountry->rowCount()==1) {
				$rowCountry=$resultCountry->fetch() ;
				$countryCode=$rowCountry["iddCountryCode"] ;
			}

			//Roles
			if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_role")) {
				if ($_POST["role"]=="Y") {
					$choices=$_POST["roles"] ;
					if ($choices!="") {
						foreach ($choices as $t) {
							try {
								$data=array("AI"=>$AI, "t"=>$t);
								$sql="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:AI, type='Role', id=:t" ;
								$result=$connection2->prepare($sql);
								$result->execute($data);
							}
							catch(PDOException $e) {
								$partialFail=TRUE;
							}

							//Get email addresses
							$category=getRoleCategory($t, $connection2) ;
							if ($email=="Y") {
								if ($category=="Parent") {
									try {
										$dataEmail=array("gibbonRoleIDAll"=>"%$t%");
										$sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT email='' AND gibbonRoleIDAll LIKE :gibbonRoleIDAll AND status='Full' AND contactEmail='Y'" ;
										$resultEmail=$connection2->prepare($sqlEmail);
										$resultEmail->execute($dataEmail);

									}
									catch(PDOException $e) { }
									while ($rowEmail=$resultEmail->fetch()) {
										$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Role', $t, 'Email', $rowEmail["email"]);
									}
								}
								else {
									try {
										$dataEmail=array("gibbonRoleIDAll"=>"%$t%");
										$sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE NOT email='' AND gibbonRoleIDAll LIKE :gibbonRoleIDAll AND status='Full'" ;
										$resultEmail=$connection2->prepare($sqlEmail);
										$resultEmail->execute($dataEmail);
									}
									catch(PDOException $e) { }
									while ($rowEmail=$resultEmail->fetch()) {
										$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Role', $t, 'Email', $rowEmail["email"]);
									}
								}
							}
							if ($sms=="Y" AND $countryCode!="") {
								if ($category=="Parent") {
									try {
										$dataEmail=array("gibbonRoleIDAll"=>"%$t%");
										$sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone1='' AND phone1Type='Mobile' AND gibbonRoleIDAll LIKE :gibbonRoleIDAll AND status='Full' AND contactSMS='Y')" ;
										$sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone2='' AND phone2Type='Mobile' AND gibbonRoleIDAll LIKE :gibbonRoleIDAll AND status='Full' AND contactSMS='Y')" ;
										$sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone3='' AND phone3Type='Mobile' AND gibbonRoleIDAll LIKE :gibbonRoleIDAll AND status='Full' AND contactSMS='Y')" ;
										$sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone4='' AND phone4Type='Mobile' AND gibbonRoleIDAll LIKE :gibbonRoleIDAll AND status='Full' AND contactSMS='Y')" ;
										$resultEmail=$connection2->prepare($sqlEmail);
										$resultEmail->execute($dataEmail);
									}
									catch(PDOException $e) { }
									while ($rowEmail=$resultEmail->fetch()) {
										$countryCodeTemp = $countryCode;
										if ($rowEmail["countryCode"]=="")
											$countryCodeTemp = $rowEmail["countryCode"];
										$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Role', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
									}
								}
								else {
									try {
										$dataEmail=array("gibbonRoleIDAll"=>"%$t%");
										$sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE NOT phone1='' AND phone1Type='Mobile' AND gibbonRoleIDAll LIKE :gibbonRoleIDAll AND status='Full')" ;
										$sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE NOT phone2='' AND phone2Type='Mobile' AND gibbonRoleIDAll LIKE :gibbonRoleIDAll AND status='Full')" ;
										$sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE NOT phone3='' AND phone3Type='Mobile' AND gibbonRoleIDAll LIKE :gibbonRoleIDAll AND status='Full')" ;
										$sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE NOT phone4='' AND phone4Type='Mobile' AND gibbonRoleIDAll LIKE :gibbonRoleIDAll AND status='Full')" ;
										$resultEmail=$connection2->prepare($sqlEmail);
										$resultEmail->execute($dataEmail);
									}
									catch(PDOException $e) { }
									while ($rowEmail=$resultEmail->fetch()) {
										$countryCodeTemp = $countryCode;
										if ($rowEmail["countryCode"]=="")
											$countryCodeTemp = $rowEmail["countryCode"];
										$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Role', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
									}
								}
							}
						}
					}
				}
			}

			//Role Categories
			if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_role")) {
				if ($_POST["roleCategory"]=="Y") {
					$choices=$_POST["roleCategories"] ;
					if ($choices!="") {
						foreach ($choices as $t) {
							try {
								$data=array("AI"=>$AI, "t"=>$t);
								$sql="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:AI, type='Role Category', id=:t" ;
								$result=$connection2->prepare($sql);
								$result->execute($data);
							}
							catch(PDOException $e) {
								$partialFail=TRUE;
							}
							//Get email addresses
							if ($email=="Y") {
								if ($t=="Parent") {
									try {
										$dataEmail=array("category"=>$t);
										$sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDAll LIKE CONCAT('%', gibbonRole.gibbonRoleID, '%')) WHERE NOT email='' AND category=:category AND status='Full' AND contactEmail='Y'" ;
										$resultEmail=$connection2->prepare($sqlEmail);
										$resultEmail->execute($dataEmail);
									}
									catch(PDOException $e) { }
									while ($rowEmail=$resultEmail->fetch()) {
										$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Role Category', $t, 'Email', $rowEmail["email"]);
									}
								}
								else {
									try {
										$dataEmail=array("category"=>$t);
										$sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDAll LIKE CONCAT('%', gibbonRole.gibbonRoleID, '%')) WHERE NOT email='' AND category=:category AND status='Full'" ;
										$resultEmail=$connection2->prepare($sqlEmail);
										$resultEmail->execute($dataEmail);
									}
									catch(PDOException $e) { }
									while ($rowEmail=$resultEmail->fetch()) {
										$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Role Category', $t, 'Email', $rowEmail["email"]);
									}
								}
							}
							if ($sms=="Y" AND $countryCode!="") {
								if ($t=="Parent") {
									try {
										$dataEmail=array("category"=>$t);
										$sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDAll LIKE CONCAT('%', gibbonRole.gibbonRoleID, '%')) WHERE NOT phone1='' AND phone1Type='Mobile' AND category=:category AND status='Full' AND contactSMS='Y')" ;
										$sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDAll LIKE CONCAT('%', gibbonRole.gibbonRoleID, '%')) WHERE NOT phone2='' AND phone2Type='Mobile' AND category=:category AND status='Full' AND contactSMS='Y')" ;
										$sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDAll LIKE CONCAT('%', gibbonRole.gibbonRoleID, '%')) WHERE NOT phone3='' AND phone3Type='Mobile' AND category=:category AND status='Full' AND contactSMS='Y')" ;
										$sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDAll LIKE CONCAT('%', gibbonRole.gibbonRoleID, '%')) WHERE NOT phone4='' AND phone4Type='Mobile' AND category=:category AND status='Full' AND contactSMS='Y')" ;
										$resultEmail=$connection2->prepare($sqlEmail);
										$resultEmail->execute($dataEmail);
									}
									catch(PDOException $e) { print $e->getMessage() ;}
									while ($rowEmail=$resultEmail->fetch()) {
										$countryCodeTemp = $countryCode;
										if ($rowEmail["countryCode"]=="")
											$countryCodeTemp = $rowEmail["countryCode"];
										$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Role Category', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
									}
								}
								else {
									try {
										$dataEmail=array("category"=>$t);
										$sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDAll LIKE CONCAT('%', gibbonRole.gibbonRoleID, '%')) WHERE NOT phone1='' AND phone1Type='Mobile' AND category=:category AND status='Full')" ;
										$sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDAll LIKE CONCAT('%', gibbonRole.gibbonRoleID, '%')) WHERE NOT phone2='' AND phone2Type='Mobile' AND category=:category AND status='Full')" ;
										$sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDAll LIKE CONCAT('%', gibbonRole.gibbonRoleID, '%')) WHERE NOT phone3='' AND phone3Type='Mobile' AND category=:category AND status='Full')" ;
										$sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDAll LIKE CONCAT('%', gibbonRole.gibbonRoleID, '%')) WHERE NOT phone4='' AND phone4Type='Mobile' AND category=:category AND status='Full')" ;
										$resultEmail=$connection2->prepare($sqlEmail);
										$resultEmail->execute($dataEmail);
									}
									catch(PDOException $e) { }
									while ($rowEmail=$resultEmail->fetch()) {
										$countryCodeTemp = $countryCode;
										if ($rowEmail["countryCode"]=="")
											$countryCodeTemp = $rowEmail["countryCode"];
										$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Role Category', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
									}
								}
							}
						}
					}
				}
			}

			//Year Groups
			if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_yearGroups_any")) {
				if ($_POST["yearGroup"]=="Y") {
					$staff=$_POST["yearGroupsStaff"] ;
					$students=$_POST["yearGroupsStudents"] ;
					$parents="N" ;
					if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_yearGroups_parents")) {
						$parents=$_POST["yearGroupsParents"] ;
					}
					$choices=$_POST["yearGroups"] ;
					if ($choices!="") {
						foreach ($choices as $t) {
							try {
								$data=array("AI"=>$AI, "t"=>$t, "staff"=>$staff, "students"=>$students, "parents"=>$parents);
								$sql="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:AI, type='Year Group', id=:t, staff=:staff, students=:students, parents=:parents" ;
								$result=$connection2->prepare($sql);
								$result->execute($data);
							}
							catch(PDOException $e) {
								$partialFail=TRUE;
							}

							//Get email addresses
							if ($email=="Y") {
								if ($staff=="Y") {
									try {
										$dataEmail=array("gibbonRoleIDAll"=>"%$t%");
										$sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT email='' AND status='Full'" ;
										$resultEmail=$connection2->prepare($sqlEmail);
										$resultEmail->execute($dataEmail);
									}
									catch(PDOException $e) { }
									while ($rowEmail=$resultEmail->fetch()) {
										$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Year Group', $t, 'Email', $rowEmail["email"]);
									}
								}
								if ($students=="Y") {
									try {
										$dataEmail=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonYearGroupID"=>$t);
										$sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT email='' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonYearGroupID=:gibbonYearGroupID" ;
										$resultEmail=$connection2->prepare($sqlEmail);
										$resultEmail->execute($dataEmail);
									}
									catch(PDOException $e) { }
									while ($rowEmail=$resultEmail->fetch()) {
										$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Year Group', $t, 'Email', $rowEmail["email"]);
									}
								}
								if ($parents=="Y") {
									try {
										$dataStudents=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonYearGroupID"=>$t);
										$sqlStudents="SELECT gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonYearGroupID=:gibbonYearGroupID" ;
										$resultStudents=$connection2->prepare($sqlStudents);
										$resultStudents->execute($dataStudents);
									}
									catch(PDOException $e) { }
									while ($rowStudents=$resultStudents->fetch()) {
										try {
											$dataFamily=array("gibbonPersonID"=>$rowStudents["gibbonPersonID"]);
											$sqlFamily="SELECT DISTINCT gibbonFamily.gibbonFamilyID FROM gibbonFamily JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID" ;
											$resultFamily=$connection2->prepare($sqlFamily);
											$resultFamily->execute($dataFamily);
										}
										catch(PDOException $e) { }
										while ($rowFamily=$resultFamily->fetch()) {
											try {
												$dataEmail=array("gibbonFamilyID"=>$rowFamily["gibbonFamilyID"] );
												$sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT email='' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactEmail='Y'" ;
												$resultEmail=$connection2->prepare($sqlEmail);
												$resultEmail->execute($dataEmail);
											}
											catch(PDOException $e) { }
											while ($rowEmail=$resultEmail->fetch()) {
												$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Year Group', $t, 'Email', $rowEmail["email"]);
											}
										}
									}
								}
							}
							if ($sms=="Y" AND $countryCode!="") {
								if ($staff=="Y") {
									try {
										$dataEmail=array();
										$sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone1='' AND phone1Type='Mobile' AND status='Full')" ;
										$sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone2='' AND phone2Type='Mobile' AND status='Full')" ;
										$sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone3='' AND phone3Type='Mobile' AND status='Full')" ;
										$sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone4='' AND phone4Type='Mobile' AND status='Full')" ;
										$resultEmail=$connection2->prepare($sqlEmail);
										$resultEmail->execute($dataEmail);
									}
									catch(PDOException $e) { }
									while ($rowEmail=$resultEmail->fetch()) {
										$countryCodeTemp = $countryCode;
										if ($rowEmail["countryCode"]=="")
											$countryCodeTemp = $rowEmail["countryCode"];
										$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Year Group', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
									}
								}
								if ($students=="Y") {
									try {
										$dataEmail=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonYearGroupID"=>$t);
										$sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone1='' AND phone1Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonYearGroupID=:gibbonYearGroupID)" ;
										$sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone2='' AND phone2Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonYearGroupID=:gibbonYearGroupID)" ;
										$sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone3='' AND phone3Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonYearGroupID=:gibbonYearGroupID)" ;
										$sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone4='' AND phone4Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonYearGroupID=:gibbonYearGroupID)" ;
										$resultEmail=$connection2->prepare($sqlEmail);
										$resultEmail->execute($dataEmail);
									}
									catch(PDOException $e) { }
									while ($rowEmail=$resultEmail->fetch()) {
										$countryCodeTemp = $countryCode;
										if ($rowEmail["countryCode"]=="")
											$countryCodeTemp = $rowEmail["countryCode"];
										$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Year Group', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
									}
								}
								if ($parents=="Y") {
									try {
										$dataStudents=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonYearGroupID"=>$t);
										$sqlStudents="SELECT gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonYearGroupID=:gibbonYearGroupID" ;
										$resultStudents=$connection2->prepare($sqlStudents);
										$resultStudents->execute($dataStudents);
									}
									catch(PDOException $e) { }
									while ($rowStudents=$resultStudents->fetch()) {
										try {
											$dataFamily=array("gibbonPersonID"=>$rowStudents["gibbonPersonID"]);
											$sqlFamily="SELECT DISTINCT gibbonFamily.gibbonFamilyID FROM gibbonFamily JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID" ;
											$resultFamily=$connection2->prepare($sqlFamily);
											$resultFamily->execute($dataFamily);
										}
										catch(PDOException $e) { }
										while ($rowFamily=$resultFamily->fetch()) {
											try {
												$dataEmail=array("gibbonFamilyID"=>$rowFamily["gibbonFamilyID"] );
												$sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone1='' AND phone1Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
												$sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone2='' AND phone2Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
												$sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone3='' AND phone3Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
												$sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone4='' AND phone4Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
												$resultEmail=$connection2->prepare($sqlEmail);
												$resultEmail->execute($dataEmail);
											}
											catch(PDOException $e) { }
											while ($rowEmail=$resultEmail->fetch()) {
												$countryCodeTemp = $countryCode;
												if ($rowEmail["countryCode"]=="")
													$countryCodeTemp = $rowEmail["countryCode"];
												$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Year Group', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
											}
										}
									}
								}
							}
						}
					}
				}
			}

			//Roll Groups
			if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_rollGroups_my") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_rollGroups_any")) {
				if ($_POST["rollGroup"]=="Y") {
					$staff=$_POST["rollGroupsStaff"] ;
					$students=$_POST["rollGroupsStudents"] ;
					$parents="N" ;
					if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_rollGroups_parents")) {
						$parents=$_POST["rollGroupsParents"] ;
					}
					$choices=$_POST["rollGroups"] ;
					if ($choices!="") {
						foreach ($choices as $t) {
							try {
								$data=array("AI"=>$AI, "t"=>$t, "staff"=>$staff, "students"=>$students, "parents"=>$parents);
								$sql="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:AI, type='Roll Group', id=:t, staff=:staff, students=:students, parents=:parents" ;
								$result=$connection2->prepare($sql);
								$result->execute($data);
							}
							catch(PDOException $e) {
								$partialFail=TRUE;
							}

							//Get email addresses
							if ($email=="Y") {
								if ($staff=="Y") {
									try {
										$dataEmail=array("t"=>$t);
										$sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonRollGroup ON (gibbonRollGroup.gibbonPersonIDTutor=gibbonPerson.gibbonPersonID OR gibbonRollGroup.gibbonPersonIDTutor2=gibbonPerson.gibbonPersonID OR gibbonRollGroup.gibbonPersonIDTutor3=gibbonPerson.gibbonPersonID) WHERE NOT email='' AND status='Full' AND gibbonRollGroupID=:t" ;
										$resultEmail=$connection2->prepare($sqlEmail);
										$resultEmail->execute($dataEmail);
									}
									catch(PDOException $e) { }
									while ($rowEmail=$resultEmail->fetch()) {
										$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Roll Group', $t, 'Email', $rowEmail["email"]);
									}
								}
								if ($students=="Y") {
									try {
										$dataEmail=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonRollGroupID"=>$t);
										$sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT email='' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonRollGroupID=:gibbonRollGroupID" ;
										$resultEmail=$connection2->prepare($sqlEmail);
										$resultEmail->execute($dataEmail);
									}
									catch(PDOException $e) { }
									while ($rowEmail=$resultEmail->fetch()) {
										$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Roll Group', $t, 'Email', $rowEmail["email"]);
									}
								}
								if ($parents=="Y") {
									try {
										$dataStudents=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonRollGroupID"=>$t);
										$sqlStudents="SELECT DISTINCT gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonRollGroupID=:gibbonRollGroupID" ;
										$resultStudents=$connection2->prepare($sqlStudents);
										$resultStudents->execute($dataStudents);
									}
									catch(PDOException $e) { }
									while ($rowStudents=$resultStudents->fetch()) {
										try {
											$dataFamily=array("gibbonPersonID"=>$rowStudents["gibbonPersonID"]);
											$sqlFamily="SELECT DISTINCT gibbonFamily.gibbonFamilyID FROM gibbonFamily JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID" ;
											$resultFamily=$connection2->prepare($sqlFamily);
											$resultFamily->execute($dataFamily);
										}
										catch(PDOException $e) { }
										while ($rowFamily=$resultFamily->fetch()) {
											try {
												$dataEmail=array("gibbonFamilyID"=>$rowFamily["gibbonFamilyID"] );
												$sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT email='' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactEmail='Y'" ;
												$resultEmail=$connection2->prepare($sqlEmail);
												$resultEmail->execute($dataEmail);
											}
											catch(PDOException $e) { }
											while ($rowEmail=$resultEmail->fetch()) {
												$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Roll Group', $t, 'Email', $rowEmail["email"]);
											}
										}
									}
								}
							}
							if ($sms=="Y" AND $countryCode!="") {
								if ($staff=="Y") {
									try {
										$dataEmail=array("gibbonRollGroupID"=>$t);
										$sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonRollGroup ON (gibbonRollGroup.gibbonPersonIDTutor=gibbonPerson.gibbonPersonID OR gibbonRollGroup.gibbonPersonIDTutor2=gibbonPerson.gibbonPersonID OR gibbonRollGroup.gibbonPersonIDTutor3=gibbonPerson.gibbonPersonID) WHERE NOT phone1='' AND phone1Type='Mobile' AND status='Full' AND gibbonRollGroupID=:gibbonRollGroupID)" ;
										$sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonRollGroup ON (gibbonRollGroup.gibbonPersonIDTutor=gibbonPerson.gibbonPersonID OR gibbonRollGroup.gibbonPersonIDTutor2=gibbonPerson.gibbonPersonID OR gibbonRollGroup.gibbonPersonIDTutor3=gibbonPerson.gibbonPersonID) WHERE NOT phone2='' AND phone2Type='Mobile' AND status='Full' AND gibbonRollGroupID=:gibbonRollGroupID)" ;
										$sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonRollGroup ON (gibbonRollGroup.gibbonPersonIDTutor=gibbonPerson.gibbonPersonID OR gibbonRollGroup.gibbonPersonIDTutor2=gibbonPerson.gibbonPersonID OR gibbonRollGroup.gibbonPersonIDTutor3=gibbonPerson.gibbonPersonID) WHERE NOT phone3='' AND phone3Type='Mobile' AND status='Full' AND gibbonRollGroupID=:gibbonRollGroupID)" ;
										$sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonRollGroup ON (gibbonRollGroup.gibbonPersonIDTutor=gibbonPerson.gibbonPersonID OR gibbonRollGroup.gibbonPersonIDTutor2=gibbonPerson.gibbonPersonID OR gibbonRollGroup.gibbonPersonIDTutor3=gibbonPerson.gibbonPersonID) WHERE NOT phone4='' AND phone4Type='Mobile' AND status='Full' AND gibbonRollGroupID=:gibbonRollGroupID)" ;
										$resultEmail=$connection2->prepare($sqlEmail);
										$resultEmail->execute($dataEmail);
									}
									catch(PDOException $e) { }
									while ($rowEmail=$resultEmail->fetch()) {
										$countryCodeTemp = $countryCode;
										if ($rowEmail["countryCode"]=="")
											$countryCodeTemp = $rowEmail["countryCode"];
										$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Roll Group', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
									}
								}
								if ($students=="Y") {
									try {
										$dataEmail=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonRollGroupID"=>$t);
										$sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone1='' AND phone1Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonRollGroupID=:gibbonRollGroupID)" ;
										$sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone2='' AND phone2Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonRollGroupID=:gibbonRollGroupID)" ;
										$sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone3='' AND phone3Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonRollGroupID=:gibbonRollGroupID)" ;
										$sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone4='' AND phone4Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonRollGroupID=:gibbonRollGroupID)" ;
										$resultEmail=$connection2->prepare($sqlEmail);
										$resultEmail->execute($dataEmail);
									}
									catch(PDOException $e) { }
									while ($rowEmail=$resultEmail->fetch()) {
										$countryCodeTemp = $countryCode;
										if ($rowEmail["countryCode"]=="")
											$countryCodeTemp = $rowEmail["countryCode"];
										$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Roll Group', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
									}
								}
								if ($parents=="Y") {
									try {
										$dataStudents=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonRollGroupID"=>$t);
										$sqlStudents="SELECT DISTINCT gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonRollGroupID=:gibbonRollGroupID" ;
										$resultStudents=$connection2->prepare($sqlStudents);
										$resultStudents->execute($dataStudents);
									}
									catch(PDOException $e) { }
									while ($rowStudents=$resultStudents->fetch()) {
										try {
											$dataFamily=array("gibbonPersonID"=>$rowStudents["gibbonPersonID"]);
											$sqlFamily="SELECT DISTINCT gibbonFamily.gibbonFamilyID FROM gibbonFamily JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID" ;
											$resultFamily=$connection2->prepare($sqlFamily);
											$resultFamily->execute($dataFamily);
										}
										catch(PDOException $e) { }
										while ($rowFamily=$resultFamily->fetch()) {
											try {
												$dataEmail=array("gibbonFamilyID"=>$rowFamily["gibbonFamilyID"] );
												$sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone1='' AND phone1Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
												$sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone2='' AND phone2Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
												$sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone3='' AND phone3Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
												$sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone4='' AND phone4Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
												$resultEmail=$connection2->prepare($sqlEmail);
												$resultEmail->execute($dataEmail);
											}
											catch(PDOException $e) { }
											while ($rowEmail=$resultEmail->fetch()) {
												$countryCodeTemp = $countryCode;
												if ($rowEmail["countryCode"]=="")
													$countryCodeTemp = $rowEmail["countryCode"];
												$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Roll Group', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
											}
										}
									}
								}
							}
						}
					}
				}
			}

			//Course Groups
			if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_courses_my") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_courses_any")) {
				if ($_POST["course"]=="Y") {
					$staff=$_POST["coursesStaff"] ;
					$students=$_POST["coursesStudents"] ;
					$parents="N" ;
					if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_courses_parents")) {
						$parents=$_POST["coursesParents"] ;
					}
					$choices=$_POST["courses"] ;
					if ($choices!="") {
						foreach ($choices as $t) {
							try {
								$data=array("gibbonMessengerID"=>$AI, "id"=>$t, "staff"=>$staff, "students"=>$students, "parents"=>$parents);
								$sql="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:gibbonMessengerID, type='Course', id=:id, staff=:staff, students=:students, parents=:parents" ;
								$result=$connection2->prepare($sql);
								$result->execute($data);
							}
							catch(PDOException $e) {
								$partialFail=TRUE;
							}

							//Get email addresses
							if ($email=="Y") {
								if ($staff=="Y") {
									try {
										$dataEmail=array("gibbonCourseID"=>$t);
										$sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE (role='Teacher' OR role='Assistant' OR role='Technician') AND NOT email='' AND status='Full' AND gibbonCourse.gibbonCourseID=:gibbonCourseID" ;
										$resultEmail=$connection2->prepare($sqlEmail);
										$resultEmail->execute($dataEmail);
									}
									catch(PDOException $e) { }
									while ($rowEmail=$resultEmail->fetch()) {
										$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Course', $t, 'Email', $rowEmail["email"]);
									}
								}
								if ($students=="Y") {
									try {
										$dataEmail=array("gibbonCourseID"=>$t);
										$sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE role='Student' AND NOT email='' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonCourse.gibbonCourseID=:gibbonCourseID" ;
										$resultEmail=$connection2->prepare($sqlEmail);
										$resultEmail->execute($dataEmail);
									}
									catch(PDOException $e) { }
									while ($rowEmail=$resultEmail->fetch()) {
										$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Course', $t, 'Email', $rowEmail["email"]);
									}
								}
								if ($parents=="Y") {
									try {
										$dataStudents=array("gibbonCourseID"=>$t);
										$sqlStudents="SELECT DISTINCT gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE role='Student' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonCourse.gibbonCourseID=:gibbonCourseID" ;
										$resultStudents=$connection2->prepare($sqlStudents);
										$resultStudents->execute($dataStudents);
									}
									catch(PDOException $e) { }
									while ($rowStudents=$resultStudents->fetch()) {
										try {
											$dataFamily=array("gibbonPersonID"=>$rowStudents["gibbonPersonID"]);
											$sqlFamily="SELECT DISTINCT gibbonFamily.gibbonFamilyID FROM gibbonFamily JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID" ;
											$resultFamily=$connection2->prepare($sqlFamily);
											$resultFamily->execute($dataFamily);
										}
										catch(PDOException $e) { }
										while ($rowFamily=$resultFamily->fetch()) {
											try {
												$dataEmail=array("gibbonFamilyID"=>$rowFamily["gibbonFamilyID"] );
												$sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT email='' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactEmail='Y'" ;
												$resultEmail=$connection2->prepare($sqlEmail);
												$resultEmail->execute($dataEmail);
											}
											catch(PDOException $e) { }
											while ($rowEmail=$resultEmail->fetch()) {
												$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Course', $t, 'Email', $rowEmail["email"]);
											}
										}
									}
									try {
										$dataEmail=array("gibbonCourseID"=>$t);
										$sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE role='Parent' AND NOT email='' AND status='Full' AND gibbonCourse.gibbonCourseID=:gibbonCourseID" ;
										$resultEmail=$connection2->prepare($sqlEmail);
										$resultEmail->execute($dataEmail);
									}
									catch(PDOException $e) { }
									while ($rowEmail=$resultEmail->fetch()) {
										$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Course', $t, 'Email', $rowEmail["email"]);
									}
								}
							}
							if ($sms=="Y" AND $countryCode!="") {
								if ($staff=="Y") {
									try {
										$dataEmail=array("gibbonCourseID"=>$t);
										$sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE (role='Teacher' OR role='Assistant' OR role='Technician') AND NOT phone1='' AND phone1Type='Mobile' AND status='Full' AND gibbonCourse.gibbonCourseID=:gibbonCourseID)" ;
										$sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE (role='Teacher' OR role='Assistant' OR role='Technician') AND NOT phone2='' AND phone2Type='Mobile' AND status='Full' AND gibbonCourse.gibbonCourseID=:gibbonCourseID)" ;
										$sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE (role='Teacher' OR role='Assistant' OR role='Technician') AND NOT phone3='' AND phone3Type='Mobile' AND status='Full' AND gibbonCourse.gibbonCourseID=:gibbonCourseID)" ;
										$sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE (role='Teacher' OR role='Assistant' OR role='Technician') AND NOT phone4='' AND phone4Type='Mobile' AND status='Full' AND gibbonCourse.gibbonCourseID=:gibbonCourseID)" ;
										$resultEmail=$connection2->prepare($sqlEmail);
										$resultEmail->execute($dataEmail);
									}
									catch(PDOException $e) { }
									while ($rowEmail=$resultEmail->fetch()) {
										$countryCodeTemp = $countryCode;
										if ($rowEmail["countryCode"]=="")
											$countryCodeTemp = $rowEmail["countryCode"];
										$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Course', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
									}
								}
								if ($students=="Y") {
									try {
										$dataEmail=array("gibbonCourseID"=>$t);
										$sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE role='Student' AND NOT phone1='' AND phone1Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonCourse.gibbonCourseID=:gibbonCourseID)" ;
										$sqlEmail.=" UNION (SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE role='Student' AND NOT phone1='' AND phone1Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonCourse.gibbonCourseID=:gibbonCourseID)" ;
										$sqlEmail.=" UNION (SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE role='Student' AND NOT phone1='' AND phone1Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonCourse.gibbonCourseID=:gibbonCourseID)" ;
										$sqlEmail.=" UNION (SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE role='Student' AND NOT phone1='' AND phone1Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonCourse.gibbonCourseID=:gibbonCourseID)" ;
										$resultEmail=$connection2->prepare($sqlEmail);
										$resultEmail->execute($dataEmail);
									}
									catch(PDOException $e) { }
									while ($rowEmail=$resultEmail->fetch()) {
										$countryCodeTemp = $countryCode;
										if ($rowEmail["countryCode"]=="")
											$countryCodeTemp = $rowEmail["countryCode"];
										$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Course', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
									}
								}
								if ($parents=="Y") {
									try {
										$dataStudents=array("gibbonCourseID"=>$t);
										$sqlStudents="SELECT DISTINCT gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE role='Student' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonCourse.gibbonCourseID=:gibbonCourseID" ;
										$resultStudents=$connection2->prepare($sqlStudents);
										$resultStudents->execute($dataStudents);
									}
									catch(PDOException $e) { }
									while ($rowStudents=$resultStudents->fetch()) {
										try {
											$dataFamily=array("gibbonPersonID"=>$rowStudents["gibbonPersonID"]);
											$sqlFamily="SELECT DISTINCT gibbonFamily.gibbonFamilyID FROM gibbonFamily JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID" ;
											$resultFamily=$connection2->prepare($sqlFamily);
											$resultFamily->execute($dataFamily);
										}
										catch(PDOException $e) { }
										while ($rowFamily=$resultFamily->fetch()) {
											try {
												$dataEmail=array("gibbonFamilyID"=>$rowFamily["gibbonFamilyID"] );
												$sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone1='' AND phone1Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
												$sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone2='' AND phone2Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
												$sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone3='' AND phone3Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
												$sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone4='' AND phone4Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
												$resultEmail=$connection2->prepare($sqlEmail);
												$resultEmail->execute($dataEmail);
											}
											catch(PDOException $e) { }
											while ($rowEmail=$resultEmail->fetch()) {
												$countryCodeTemp = $countryCode;
												if ($rowEmail["countryCode"]=="")
													$countryCodeTemp = $rowEmail["countryCode"];
												$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Course', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
											}
										}
									}
									try {
										$dataEmail=array("gibbonCourseID"=>$t);
										$sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE role='Parent' AND NOT phone1='' AND phone1Type='Mobile' AND status='Full' AND gibbonCourse.gibbonCourseID=:gibbonCourseID)" ;
										$sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE role='Parent' AND NOT phone2='' AND phone2Type='Mobile' AND status='Full' AND gibbonCourse.gibbonCourseID=:gibbonCourseID)" ;
										$sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE role='Parent' AND NOT phone3='' AND phone3Type='Mobile' AND status='Full' AND gibbonCourse.gibbonCourseID=:gibbonCourseID)" ;
										$sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE role='Parent' AND NOT phone4='' AND phone4Type='Mobile' AND status='Full' AND gibbonCourse.gibbonCourseID=:gibbonCourseID)" ;
										$resultEmail=$connection2->prepare($sqlEmail);
										$resultEmail->execute($dataEmail);
									}
									catch(PDOException $e) { }
									while ($rowEmail=$resultEmail->fetch()) {
										$countryCodeTemp = $countryCode;
										if ($rowEmail["countryCode"]=="")
											$countryCodeTemp = $rowEmail["countryCode"];
										$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Course', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
									}
								}
							}
						}
					}
				}
			}

			//Class Groups
			if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_classes_my") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_classes_any")) {
				if ($_POST["class"]=="Y") {
					$staff=$_POST["classesStaff"] ;
					$students=$_POST["classesStudents"] ;
					$parents="N" ;
					if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_classes_parents")) {
						$parents=$_POST["classesParents"] ;
					}
					$choices=$_POST["classes"] ;
					if ($choices!="") {
						foreach ($choices as $t) {
							try {
								$data=array("gibbonMessengerID"=>$AI, "id"=>$t, "staff"=>$staff, "students"=>$students, "parents"=>$parents);
								$sql="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:gibbonMessengerID, type='Class', id=:id, staff=:staff, students=:students, parents=:parents" ;
								$result=$connection2->prepare($sql);
								$result->execute($data);
							}
							catch(PDOException $e) {
								$partialFail=TRUE;
							}

							//Get email addresses
							if ($email=="Y") {
								if ($staff=="Y") {
									try {
										$dataEmail=array("gibbonCourseClassID"=>$t);
										$sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE (role='Teacher' OR role='Assistant' OR role='Technician') AND NOT email='' AND status='Full' AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID" ;
										$resultEmail=$connection2->prepare($sqlEmail);
										$resultEmail->execute($dataEmail);
									}
									catch(PDOException $e) { }
									while ($rowEmail=$resultEmail->fetch()) {
										$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Class', $t, 'Email', $rowEmail["email"]);
									}
								}
								if ($students=="Y") {
									try {
										$dataEmail=array("gibbonCourseClassID"=>$t);
										$sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE role='Student' AND NOT email='' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonCourseClass.gibbonCourseClassID=$t" ;
										$resultEmail=$connection2->prepare($sqlEmail);
										$resultEmail->execute($dataEmail);
									}
									catch(PDOException $e) { }
									while ($rowEmail=$resultEmail->fetch()) {
										$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Class', $t, 'Email', $rowEmail["email"]);
									}
								}
								if ($parents=="Y") {
									try {
										$dataStudents=array("gibbonCourseClassID"=>$t);
										$sqlStudents="SELECT DISTINCT gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE role='Student' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID" ;
										$resultStudents=$connection2->prepare($sqlStudents);
										$resultStudents->execute($dataStudents);
									}
									catch(PDOException $e) { }
									while ($rowStudents=$resultStudents->fetch()) {
										try {
											$dataFamily=array("gibbonPersonID"=>$rowStudents["gibbonPersonID"]);
											$sqlFamily="SELECT DISTINCT gibbonFamily.gibbonFamilyID FROM gibbonFamily JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID" ;
											$resultFamily=$connection2->prepare($sqlFamily);
											$resultFamily->execute($dataFamily);
										}
										catch(PDOException $e) { }
										while ($rowFamily=$resultFamily->fetch()) {
											try {
												$dataEmail=array("gibbonFamilyID"=>$rowFamily["gibbonFamilyID"] );
												$sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT email='' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactEmail='Y'" ;
												$resultEmail=$connection2->prepare($sqlEmail);
												$resultEmail->execute($dataEmail);
											}
											catch(PDOException $e) { }
											while ($rowEmail=$resultEmail->fetch()) {
												$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Class', $t, 'Email', $rowEmail["email"]);
											}
										}
									}
									try {
										$dataEmail=array("gibbonCourseClassID"=>$t);
										$sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE role='Parent' AND NOT email='' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID" ;
										$resultEmail=$connection2->prepare($sqlEmail);
										$resultEmail->execute($dataEmail);
									}
									catch(PDOException $e) { }
									while ($rowEmail=$resultEmail->fetch()) {
										$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Class', $t, 'Email', $rowEmail["email"]);
									}
								}
							}
							if ($sms=="Y" AND $countryCode!="") {
								if ($staff=="Y") {
									try {
										$dataEmail=array("gibbonCourseClassID"=>$t);
										$sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE (role='Teacher' OR role='Assistant' OR role='Technician') AND NOT phone1='' AND phone1Type='Mobile' AND status='Full' AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID)" ;
										$sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE (role='Teacher' OR role='Assistant' OR role='Technician') AND NOT phone2='' AND phone2Type='Mobile' AND status='Full' AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID)" ;
										$sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE (role='Teacher' OR role='Assistant' OR role='Technician') AND NOT phone3='' AND phone3Type='Mobile' AND status='Full' AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID)" ;
										$sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE (role='Teacher' OR role='Assistant' OR role='Technician') AND NOT phone4='' AND phone4Type='Mobile' AND status='Full' AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID)" ;
										$resultEmail=$connection2->prepare($sqlEmail);
										$resultEmail->execute($dataEmail);
									}
									catch(PDOException $e) { }
									while ($rowEmail=$resultEmail->fetch()) {
										$countryCodeTemp = $countryCode;
										if ($rowEmail["countryCode"]=="")
											$countryCodeTemp = $rowEmail["countryCode"];
										$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Class', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
									}
								}
								if ($students=="Y") {
									try {
										$dataEmail=array("gibbonCourseClassID"=>$t);
										$sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE role='Student' AND NOT phone1='' AND phone1Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonCourseClass.gibbonCourseClassID=$t)" ;
										$sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE role='Student' AND NOT phone2='' AND phone2Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonCourseClass.gibbonCourseClassID=$t)" ;
										$sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE role='Student' AND NOT phone3='' AND phone3Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonCourseClass.gibbonCourseClassID=$t)" ;
										$sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE role='Student' AND NOT phone4='' AND phone4Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonCourseClass.gibbonCourseClassID=$t)" ;
										$resultEmail=$connection2->prepare($sqlEmail);
										$resultEmail->execute($dataEmail);
									}
									catch(PDOException $e) { }
									while ($rowEmail=$resultEmail->fetch()) {
										$countryCodeTemp = $countryCode;
										if ($rowEmail["countryCode"]=="")
											$countryCodeTemp = $rowEmail["countryCode"];
										$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Class', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
									}
								}
								if ($parents=="Y") {
									try {
										$dataStudents=array("gibbonCourseClassID"=>$t);
										$sqlStudents="SELECT DISTINCT gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID) WHERE role='Student' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID" ;
										$resultStudents=$connection2->prepare($sqlStudents);
										$resultStudents->execute($dataStudents);
									}
									catch(PDOException $e) { }
									while ($rowStudents=$resultStudents->fetch()) {
										try {
											$dataFamily=array("gibbonPersonID"=>$rowStudents["gibbonPersonID"]);
											$sqlFamily="SELECT DISTINCT gibbonFamily.gibbonFamilyID FROM gibbonFamily JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID" ;
											$resultFamily=$connection2->prepare($sqlFamily);
											$resultFamily->execute($dataFamily);
										}
										catch(PDOException $e) { }
										while ($rowFamily=$resultFamily->fetch()) {
											try {
												$dataEmail=array("gibbonFamilyID"=>$rowFamily["gibbonFamilyID"] );
												$sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone1='' AND phone1Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
												$sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone2='' AND phone2Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
												$sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone3='' AND phone3Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
												$sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone4='' AND phone4Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
												$resultEmail=$connection2->prepare($sqlEmail);
												$resultEmail->execute($dataEmail);
											}
											catch(PDOException $e) { }
											while ($rowEmail=$resultEmail->fetch()) {
												$countryCodeTemp = $countryCode;
												if ($rowEmail["countryCode"]=="")
													$countryCodeTemp = $rowEmail["countryCode"];
												$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Class', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
											}
										}
									}
									try {
										$dataEmail=array("gibbonCourseClassID"=>$t);
										$sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE role='Parent' AND NOT phone1='' AND phone1Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID)" ;
										$sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE role='Parent' AND NOT phone2='' AND phone2Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID)" ;
										$sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE role='Parent' AND NOT phone3='' AND phone3Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID)" ;
										$sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonCourseClass ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE role='Parent' AND NOT phone4='' AND phone4Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonCourseClass.gibbonCourseClassID=:gibbonCourseClassID)" ;
										$resultEmail=$connection2->prepare($sqlEmail);
										$resultEmail->execute($dataEmail);
									}
									catch(PDOException $e) { }
									while ($rowEmail=$resultEmail->fetch()) {
										$countryCodeTemp = $countryCode;
										if ($rowEmail["countryCode"]=="")
											$countryCodeTemp = $rowEmail["countryCode"];
										$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Class', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
									}
								}
							}
						}
					}
				}
			}

			//Activity Groups
			if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_activities_my") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_activities_any")) {
				if ($_POST["activity"]=="Y") {
					$staff=$_POST["activitiesStaff"] ;
					$students=$_POST["activitiesStudents"] ;
					$parents="N" ;
					if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_activities_parents")) {
						$parents=$_POST["activitiesParents"] ;
					}
					$choices=$_POST["activities"] ;
					if ($choices!="") {
						foreach ($choices as $t) {
							try {
								$data=array("gibbonMessengerID"=>$AI, "id"=>$t, "staff"=>$staff, "students"=>$students, "parents"=>$parents);
								$sql="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:gibbonMessengerID, type='Activity', id=:id, staff=:staff, students=:students, parents=:parents" ;
								$result=$connection2->prepare($sql);
								$result->execute($data);
							}
							catch(PDOException $e) {
								$partialFail=TRUE;
							}

							//Get email addresses
							if ($email=="Y") {
								if ($staff=="Y") {
									try {
										$dataEmail=array("gibbonActivityID"=>$t);
										$sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonActivityStaff ON (gibbonActivityStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonActivity ON (gibbonActivityStaff.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE NOT email='' AND status='Full' AND gibbonActivity.gibbonActivityID=:gibbonActivityID" ;
										$resultEmail=$connection2->prepare($sqlEmail);
										$resultEmail->execute($dataEmail);
									}
									catch(PDOException $e) { }
									while ($rowEmail=$resultEmail->fetch()) {
										$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Activity', $t, 'Email', $rowEmail["email"]);
									}
								}
								if ($students=="Y") {
									try {
										$dataEmail=array("gibbonActivityID"=>$t);
										$sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonActivity ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE NOT email='' AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonActivityStudent.status='Accepted' AND gibbonActivity.gibbonActivityID=:gibbonActivityID" ;
										$resultEmail=$connection2->prepare($sqlEmail);
										$resultEmail->execute($dataEmail);
									}
									catch(PDOException $e) { }
									while ($rowEmail=$resultEmail->fetch()) {
										$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Activity', $t, 'Email', $rowEmail["email"]);
									}
								}
								if ($parents=="Y") {
									try {
										$dataStudents=array("gibbonActivityID"=>$t);
										$sqlStudents="SELECT DISTINCT gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonActivity ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonActivityStudent.status='Accepted' AND gibbonActivity.gibbonActivityID=:gibbonActivityID" ;
										$resultStudents=$connection2->prepare($sqlStudents);
										$resultStudents->execute($dataStudents);
									}
									catch(PDOException $e) { }
									while ($rowStudents=$resultStudents->fetch()) {
										try {
											$dataFamily=array("gibbonPersonID"=>$rowStudents["gibbonPersonID"]);
											$sqlFamily="SELECT DISTINCT gibbonFamily.gibbonFamilyID FROM gibbonFamily JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID" ;
											$resultFamily=$connection2->prepare($sqlFamily);
											$resultFamily->execute($dataFamily);
										}
										catch(PDOException $e) { }
										while ($rowFamily=$resultFamily->fetch()) {
											try {
												$dataEmail=array("gibbonFamilyID"=>$rowFamily["gibbonFamilyID"] );
												$sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT email='' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactEmail='Y'" ;
												$resultEmail=$connection2->prepare($sqlEmail);
												$resultEmail->execute($dataEmail);
											}
											catch(PDOException $e) { }
											while ($rowEmail=$resultEmail->fetch()) {
												$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Activity', $t, 'Email', $rowEmail["email"]);
											}
										}
									}
								}
							}
							if ($sms=="Y" AND $countryCode!="") {
								if ($staff=="Y") {
									try {
										$dataEmail=array("gibbonActivityID"=>$t);
										$sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonActivityStaff ON (gibbonActivityStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonActivity ON (gibbonActivityStaff.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE NOT phone1='' AND phone1Type='Mobile' AND status='Full' AND gibbonActivity.gibbonActivityID=:gibbonActivityID)" ;
										$sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonActivityStaff ON (gibbonActivityStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonActivity ON (gibbonActivityStaff.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE NOT phone2='' AND phone2Type='Mobile' AND status='Full' AND gibbonActivity.gibbonActivityID=:gibbonActivityID)" ;
										$sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonActivityStaff ON (gibbonActivityStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonActivity ON (gibbonActivityStaff.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE NOT phone3='' AND phone3Type='Mobile' AND status='Full' AND gibbonActivity.gibbonActivityID=:gibbonActivityID)" ;
										$sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonActivityStaff ON (gibbonActivityStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonActivity ON (gibbonActivityStaff.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE NOT phone4='' AND phone4Type='Mobile' AND status='Full' AND gibbonActivity.gibbonActivityID=:gibbonActivityID)" ;
										$resultEmail=$connection2->prepare($sqlEmail);
										$resultEmail->execute($dataEmail);
									}
									catch(PDOException $e) { }
									while ($rowEmail=$resultEmail->fetch()) {
										$countryCodeTemp = $countryCode;
										if ($rowEmail["countryCode"]=="")
											$countryCodeTemp = $rowEmail["countryCode"];
										$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Activity', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
									}
								}
								if ($students=="Y") {
									try {
										$dataEmail=array("gibbonActivityID"=>$t);
										$sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonActivity ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE NOT phone1='' AND phone1Type='Mobile' AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonActivityStudent.status='Accepted' AND gibbonActivity.gibbonActivityID=:gibbonActivityID)" ;
										$sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonActivity ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE NOT phone2='' AND phone2Type='Mobile' AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonActivityStudent.status='Accepted' AND gibbonActivity.gibbonActivityID=:gibbonActivityID)" ;
										$sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonActivity ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE NOT phone3='' AND phone3Type='Mobile' AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonActivityStudent.status='Accepted' AND gibbonActivity.gibbonActivityID=:gibbonActivityID)" ;
										$sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonActivity ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE NOT phone4='' AND phone4Type='Mobile' AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonActivityStudent.status='Accepted' AND gibbonActivity.gibbonActivityID=:gibbonActivityID)" ;
										$resultEmail=$connection2->prepare($sqlEmail);
										$resultEmail->execute($dataEmail);
									}
									catch(PDOException $e) { }
									while ($rowEmail=$resultEmail->fetch()) {
										$countryCodeTemp = $countryCode;
										if ($rowEmail["countryCode"]=="")
											$countryCodeTemp = $rowEmail["countryCode"];
										$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Activity', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
									}
								}
								if ($parents=="Y") {
									try {
										$dataStudents=array("gibbonActivityID"=>$t);
										$sqlStudents="SELECT DISTINCT gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonActivity ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonActivityStudent.status='Accepted' AND gibbonActivity.gibbonActivityID=:gibbonActivityID" ;
										$resultStudents=$connection2->prepare($sqlStudents);
										$resultStudents->execute($dataStudents);
									}
									catch(PDOException $e) { }
									while ($rowStudents=$resultStudents->fetch()) {
										try {
											$dataFamily=array("gibbonPersonID"=>$rowStudents["gibbonPersonID"]);
											$sqlFamily="SELECT DISTINCT gibbonFamily.gibbonFamilyID FROM gibbonFamily JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID" ;
											$resultFamily=$connection2->prepare($sqlFamily);
											$resultFamily->execute($dataFamily);
										}
										catch(PDOException $e) { }
										while ($rowFamily=$resultFamily->fetch()) {
											try {
												$dataEmail=array("gibbonFamilyID"=>$rowFamily["gibbonFamilyID"] );
												$sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone1='' AND phone1Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
												$sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone2='' AND phone2Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
												$sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone3='' AND phone3Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
												$sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone4='' AND phone4Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
												$resultEmail=$connection2->prepare($sqlEmail);
												$resultEmail->execute($dataEmail);
											}
											catch(PDOException $e) { print $e->getMessage() ;}
											while ($rowEmail=$resultEmail->fetch()) {
												$countryCodeTemp = $countryCode;
												if ($rowEmail["countryCode"]=="")
													$countryCodeTemp = $rowEmail["countryCode"];
												$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Activity', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
											}
										}
									}
								}
							}
						}
					}
				}
			}

			//Applicants
			if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_applicants")) {
				if ($_POST["applicants"]=="Y") {
					$choices=$_POST["applicantList"] ;
					if ($choices!="") {
						foreach ($choices as $t) {
							try {
								$data=array("gibbonMessengerID"=>$AI, "id"=>$t);
								$sql="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:gibbonMessengerID, type='Applicants', id=:id" ;
								$result=$connection2->prepare($sql);
								$result->execute($data);
							}
							catch(PDOException $e) {
								$partialFail=TRUE;
							}

							if ($email=="Y") {
								//Get applicant emails
								try {
									$dataEmail=array("gibbonSchoolYearIDEntry"=>$t);
									$sqlEmail="SELECT DISTINCT email FROM gibbonApplicationForm WHERE NOT email='' AND gibbonSchoolYearIDEntry=:gibbonSchoolYearIDEntry AND NOT status='Rejected' AND NOT status='Withdrawn'" ;
									$resultEmail=$connection2->prepare($sqlEmail);
									$resultEmail->execute($dataEmail);
								}
								catch(PDOException $e) { }
								while ($rowEmail=$resultEmail->fetch()) {
									$report = reportAdd($report, $emailReceipt, NULL, 'Applicants', $t, 'Email', $rowEmail["email"]);
								}

								//Get parent 1 emails
								try {
									$dataEmail=array("gibbonSchoolYearIDEntry"=>$t);
									$sqlEmail="SELECT DISTINCT parent1email FROM gibbonApplicationForm WHERE NOT parent1email='' AND gibbonSchoolYearIDEntry=:gibbonSchoolYearIDEntry AND NOT status='Rejected' AND NOT status='Withdrawn'" ;
									$resultEmail=$connection2->prepare($sqlEmail);
									$resultEmail->execute($dataEmail);
								}
								catch(PDOException $e) { }
								while ($rowEmail=$resultEmail->fetch()) {
									$report = reportAdd($report, $emailReceipt, NULL, 'Applicants', $t, 'Email', $rowEmail["email"]);
								}

								//Get parent 2 emails
								try {
									$dataEmail=array("gibbonSchoolYearIDEntry"=>$t);
									$sqlEmail="SELECT DISTINCT parent2email FROM gibbonApplicationForm WHERE NOT parent2email='' AND gibbonSchoolYearIDEntry=:gibbonSchoolYearIDEntry AND NOT status='Rejected' AND NOT status='Withdrawn'" ;
									$resultEmail=$connection2->prepare($sqlEmail);
									$resultEmail->execute($dataEmail);
								}
								catch(PDOException $e) { }
								while ($rowEmail=$resultEmail->fetch()) {
									$report = reportAdd($report, $emailReceipt, NULL, 'Applicants', $t, 'Email', $rowEmail["email"]);
								}

								//Get parent ID emails (when no family in system, but user is in system)
								try {
									$dataEmail=array("gibbonSchoolYearIDEntry"=>$t);
									$sqlEmail="SELECT gibbonPerson.email, gibbonPerson.gibbonPersonID FROM gibbonApplicationForm JOIN gibbonPerson ON (gibbonApplicationForm.parent1gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT parent1gibbonPersonID='' AND gibbonSchoolYearIDEntry=:gibbonSchoolYearIDEntry AND NOT gibbonApplicationForm.status='Rejected' AND NOT gibbonApplicationForm.status='Withdrawn'" ;
									$resultEmail=$connection2->prepare($sqlEmail);
									$resultEmail->execute($dataEmail);
								}
								catch(PDOException $e) { }
								while ($rowEmail=$resultEmail->fetch()) {
									$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Applicant', $t, 'Email', $rowEmail["email"]);
								}

								//Get family emails
								try {
									$dataEmail=array("gibbonSchoolYearIDEntry"=>$t);
									$sqlEmail="SELECT * FROM gibbonApplicationForm WHERE NOT gibbonFamilyID='' AND gibbonSchoolYearIDEntry=:gibbonSchoolYearIDEntry AND NOT gibbonApplicationForm.status='Rejected' AND NOT gibbonApplicationForm.status='Withdrawn'" ;
									$resultEmail=$connection2->prepare($sqlEmail);
									$resultEmail->execute($dataEmail);
								}
								catch(PDOException $e) { }
								while ($rowEmail=$resultEmail->fetch()) {
									try {
										$dataEmail2=array("gibbonFamilyID"=>$rowEmail["gibbonFamilyID"]);
										$sqlEmail2="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT email='' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactEmail='Y'" ;
										$resultEmail2=$connection2->prepare($sqlEmail2);
										$resultEmail2->execute($dataEmail2);
									}
									catch(PDOException $e) { }
									while ($rowEmail2=$resultEmail2->fetch()) {
										$report = reportAdd($report, $emailReceipt, $rowEmail2['gibbonPersonID'], 'Applicant', $t, 'Email', $rowEmail2["email"]);
									}
								}
							}
							if ($sms=="Y" AND $countryCode!="") {
								//Get applicant phone numbers
								try {
									$dataEmail=array("gibbonSchoolYearIDEntry"=>$t);
									$sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode FROM gibbonApplicationForm WHERE NOT phone1='' AND phone1Type='Mobile' AND gibbonSchoolYearIDEntry=:gibbonSchoolYearIDEntry AND NOT status='Rejected' AND NOT status='Withdrawn')" ;
									$sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode FROM gibbonApplicationForm WHERE NOT phone2='' AND phone2Type='Mobile' AND gibbonSchoolYearIDEntry=:gibbonSchoolYearIDEntry AND NOT status='Rejected' AND NOT status='Withdrawn')" ;
									$resultEmail=$connection2->prepare($sqlEmail);
									$resultEmail->execute($dataEmail);
								}
								catch(PDOException $e) { }
								while ($rowEmail=$resultEmail->fetch()) {
									$countryCodeTemp = $countryCode;
									if ($rowEmail["countryCode"]=="")
										$countryCodeTemp = $rowEmail["countryCode"];
									$report = reportAdd($report, $emailReceipt, NULL, 'Applicants', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
								}

								//Get parent 1 numbers
								try {
									$dataEmail=array("gibbonSchoolYearIDEntry"=>$t);
									$sqlEmail="(SELECT CONCAT(parent1phone1CountryCode,parent1phone1) AS phone FROM gibbonApplicationForm WHERE NOT parent1phone1='' AND parent1phone1Type='Mobile' AND parent1phone1CountryCode='$countryCode' AND gibbonSchoolYearIDEntry=:gibbonSchoolYearIDEntry AND NOT status='Rejected' AND NOT status='Withdrawn')" ;
									$sqlEmail.=" UNION (SELECT CONCAT(parent1phone2CountryCode,parent1phone2) AS phone FROM gibbonApplicationForm WHERE NOT parent1phone2='' AND parent1phone2Type='Mobile' AND parent1phone2CountryCode='$countryCode' AND gibbonSchoolYearIDEntry=:gibbonSchoolYearIDEntry AND NOT status='Rejected' AND NOT status='Withdrawn')" ;
									$resultEmail=$connection2->prepare($sqlEmail);
									$resultEmail->execute($dataEmail);
								}
								catch(PDOException $e) { }
								while ($rowEmail=$resultEmail->fetch()) {
									$countryCodeTemp = $countryCode;
									if ($rowEmail["countryCode"]=="")
										$countryCodeTemp = $rowEmail["countryCode"];
									$report = reportAdd($report, $emailReceipt, NULL, 'Applicants', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
								}

								//Get parent 2 numbers
								try {
									$dataEmail=array("gibbonSchoolYearIDEntry"=>$t);
									$sqlEmail="(SELECT CONCAT(parent2phone1CountryCode,parent2phone1) AS phone FROM gibbonApplicationForm WHERE NOT parent2phone1='' AND parent2phone1Type='Mobile' AND parent2phone1CountryCode='$countryCode' AND gibbonSchoolYearIDEntry=:gibbonSchoolYearIDEntry AND NOT status='Rejected' AND NOT status='Withdrawn')" ;
									$sqlEmail.=" UNION (SELECT CONCAT(parent2phone2CountryCode,parent2phone2) AS phone FROM gibbonApplicationForm WHERE NOT parent2phone2='' AND parent2phone2Type='Mobile' AND parent2phone2CountryCode='$countryCode' AND gibbonSchoolYearIDEntry=:gibbonSchoolYearIDEntry AND NOT status='Rejected' AND NOT status='Withdrawn')" ;
									$resultEmail=$connection2->prepare($sqlEmail);
									$resultEmail->execute($dataEmail);
								}
								catch(PDOException $e) { }
								while ($rowEmail=$resultEmail->fetch()) {
									$countryCodeTemp = $countryCode;
									if ($rowEmail["countryCode"]=="")
										$countryCodeTemp = $rowEmail["countryCode"];
									$report = reportAdd($report, $emailReceipt, NULL, 'Applicants', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
								}

								//Get parent ID numbers (when no family in system, but user is in system)
								try {
									$dataEmail=array("gibbonSchoolYearIDEntry"=>$t);
									$sqlEmail="(SELECT CONCAT(gibbonPerson.phone1CountryCode,gibbonPerson.phone1) AS phone, gibbonPerson.gibbonPersonID FROM gibbonApplicationForm JOIN gibbonPerson ON (gibbonApplicationForm.parent1gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT gibbonPerson.phone1='' AND gibbonPerson.phone1Type='Mobile' AND gibbonPerson.phone1CountryCode='$countryCode' AND NOT parent1gibbonPersonID='' AND gibbonSchoolYearIDEntry=:gibbonSchoolYearIDEntry AND NOT gibbonApplicationForm.status='Rejected' AND NOT gibbonApplicationForm.status='Withdrawn')" ;
									$sqlEmail.=" UNION (SELECT CONCAT(gibbonPerson.phone2CountryCode,gibbonPerson.phone2) AS phone, gibbonPerson.gibbonPersonID FROM gibbonApplicationForm JOIN gibbonPerson ON (gibbonApplicationForm.parent1gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT gibbonPerson.phone2='' AND gibbonPerson.phone2Type='Mobile' AND gibbonPerson.phone2CountryCode='$countryCode' AND NOT parent1gibbonPersonID='' AND gibbonSchoolYearIDEntry=:gibbonSchoolYearIDEntry AND NOT gibbonApplicationForm.status='Rejected' AND NOT gibbonApplicationForm.status='Withdrawn')" ;
									$sqlEmail.=" UNION (SELECT CONCAT(gibbonPerson.phone3CountryCode,gibbonPerson.phone3) AS phone, gibbonPerson.gibbonPersonID FROM gibbonApplicationForm JOIN gibbonPerson ON (gibbonApplicationForm.parent1gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT gibbonPerson.phone3='' AND gibbonPerson.phone3Type='Mobile' AND gibbonPerson.phone3CountryCode='$countryCode' AND NOT parent1gibbonPersonID='' AND gibbonSchoolYearIDEntry=:gibbonSchoolYearIDEntry AND NOT gibbonApplicationForm.status='Rejected' AND NOT gibbonApplicationForm.status='Withdrawn')" ;
									$sqlEmail.=" UNION (SELECT CONCAT(gibbonPerson.phone4CountryCode,gibbonPerson.phone4) AS phone, gibbonPerson.gibbonPersonID FROM gibbonApplicationForm JOIN gibbonPerson ON (gibbonApplicationForm.parent1gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT gibbonPerson.phone4='' AND gibbonPerson.phone4Type='Mobile' AND gibbonPerson.phone4CountryCode='$countryCode' AND NOT parent1gibbonPersonID='' AND gibbonSchoolYearIDEntry=:gibbonSchoolYearIDEntry AND NOT gibbonApplicationForm.status='Rejected' AND NOT gibbonApplicationForm.status='Withdrawn')" ;
									$resultEmail=$connection2->prepare($sqlEmail);
									$resultEmail->execute($dataEmail);
								}
								catch(PDOException $e) { }
								while ($rowEmail=$resultEmail->fetch()) {
									$countryCodeTemp = $countryCode;
									if ($rowEmail["countryCode"]=="")
										$countryCodeTemp = $rowEmail["countryCode"];
									$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Applicants', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
								}

								//Get family numbers
								try {
									$dataEmail=array("gibbonSchoolYearIDEntry"=>$t);
									$sqlEmail="SELECT * FROM gibbonApplicationForm WHERE NOT gibbonFamilyID='' AND gibbonSchoolYearIDEntry=:gibbonSchoolYearIDEntry AND NOT gibbonApplicationForm.status='Rejected' AND NOT gibbonApplicationForm.status='Withdrawn'" ;
									$resultEmail=$connection2->prepare($sqlEmail);
									$resultEmail->execute($dataEmail);
								}
								catch(PDOException $e) { }
								while ($rowEmail=$resultEmail->fetch()) {
									try {
										$dataEmail2=array("gibbonFamilyID"=>$rowEmail["gibbonFamilyID"]);
										$sqlEmail2="(SELECT CONCAT(gibbonPerson.phone1CountryCode,gibbonPerson.phone1) AS phone, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT gibbonPerson.phone1='' AND gibbonPerson.phone1Type='Mobile' AND gibbonPerson.phone1CountryCode='$countryCode' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID)" ;
										$sqlEmail2.=" UNION (SELECT CONCAT(gibbonPerson.phone2CountryCode,gibbonPerson.phone2) AS phone, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT gibbonPerson.phone2='' AND gibbonPerson.phone2Type='Mobile' AND gibbonPerson.phone2CountryCode='$countryCode' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID)" ;
										$sqlEmail2.=" UNION (SELECT CONCAT(gibbonPerson.phone3CountryCode,gibbonPerson.phone3) AS phone, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT gibbonPerson.phone3='' AND gibbonPerson.phone3Type='Mobile' AND gibbonPerson.phone3CountryCode='$countryCode' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID)" ;
										$sqlEmail2.=" UNION (SELECT CONCAT(gibbonPerson.phone4CountryCode,gibbonPerson.phone4) AS phone, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT gibbonPerson.phone4='' AND gibbonPerson.phone4Type='Mobile' AND gibbonPerson.phone4CountryCode='$countryCode' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID)" ;
										$resultEmail2=$connection2->prepare($sqlEmail2);
										$resultEmail2->execute($dataEmail2);
									}
									catch(PDOException $e) { }
									while ($rowEmail2=$resultEmail2->fetch()) {
										$countryCodeTemp = $countryCode;
										if ($rowEmail2["countryCode"]=="")
											$countryCodeTemp = $rowEmail2["countryCode"];
										$report = reportAdd($report, $emailReceipt, $rowEmail2['gibbonPersonID'], 'Applicants', $t, 'SMS', $countryCodeTemp.$rowEmail2["phone"]);
									}
								}
							}
						}
					}
				}
			}

			//Houses
			if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_houses_all") OR isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_houses_my")) {
				if ($_POST["houses"]=="Y") {
					$choices=$_POST["houseList"] ;
					if ($choices!="") {
						foreach ($choices as $t) {
							try {
								$data=array("gibbonMessengerID"=>$AI, "id"=>$t);
								$sql="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:gibbonMessengerID, type='Houses', id=:id" ;
								$result=$connection2->prepare($sql);
								$result->execute($data);
							}
							catch(PDOException $e) {
								$partialFail=TRUE;
							}

							if ($email=="Y") {
								try {
									$dataEmail=array("gibbonHouseID"=>$t);
									$sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE NOT email='' AND gibbonHouseID=:gibbonHouseID AND status='Full'" ;
									$resultEmail=$connection2->prepare($sqlEmail);
									$resultEmail->execute($dataEmail);
								}
								catch(PDOException $e) { }
								while ($rowEmail=$resultEmail->fetch()) {
									$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Houses', $t, 'Email', $rowEmail["email"]);
								}
							}
							if ($sms=="Y" AND $countryCode!="") {
								try {
									$dataEmail=array("gibbonHouseID"=>$t);
									$sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE NOT phone1='' AND phone1Type='Mobile' AND gibbonHouseID=:gibbonHouseID AND status='Full')" ;
									$sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE NOT phone2='' AND phone2Type='Mobile' AND gibbonHouseID=:gibbonHouseID AND status='Full')" ;
									$sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE NOT phone3='' AND phone3Type='Mobile' AND gibbonHouseID=:gibbonHouseID AND status='Full')" ;
									$sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE NOT phone4='' AND phone4Type='Mobile' AND gibbonHouseID=:gibbonHouseID AND status='Full')" ;
									$resultEmail=$connection2->prepare($sqlEmail);
									$resultEmail->execute($dataEmail);
								}
								catch(PDOException $e) { }
								while ($rowEmail=$resultEmail->fetch()) {
									$countryCodeTemp = $countryCode;
									if ($rowEmail["countryCode"]=="")
										$countryCodeTemp = $rowEmail["countryCode"];
									$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Houses', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
								}
							}
						}
					}
				}
			}

			//Transport
			if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_transport_any")) {
				if ($_POST["transport"]=="Y") {
					$staff=$_POST["transportStaff"] ;
					$students=$_POST["transportStudents"] ;
					$parents="N" ;
					if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_transport_parents")) {
						$parents=$_POST["transportParents"] ;
					}
					$choices=$_POST["transports"] ;
					if ($choices!="") {
						foreach ($choices as $t) {
							try {
								$data=array("AI"=>$AI, "t"=>$t, "staff"=>$staff, "students"=>$students, "parents"=>$parents);
								$sql="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:AI, type='Transport', id=:t, staff=:staff, students=:students, parents=:parents" ;
								$result=$connection2->prepare($sql);
								$result->execute($data);
							}
							catch(PDOException $e) {
								$partialFail=TRUE;
							}

							//Get email addresses
							if ($email=="Y") {
								if ($staff=="Y") {
									try {
										$dataEmail=array("transport"=>$t);
										$sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT email='' AND status='Full' AND transport=:transport" ;
										$resultEmail=$connection2->prepare($sqlEmail);
										$resultEmail->execute($dataEmail);
									}
									catch(PDOException $e) { }
									while ($rowEmail=$resultEmail->fetch()) {
										$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Transport', $t, 'Email', $rowEmail["email"]);
									}
								}
								if ($students=="Y") {
									try {
										$dataEmail=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "transport"=>$t);
										$sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT email='' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND transport=:transport" ;
										$resultEmail=$connection2->prepare($sqlEmail);
										$resultEmail->execute($dataEmail);
									}
									catch(PDOException $e) { }
									while ($rowEmail=$resultEmail->fetch()) {
										$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Transport', $t, 'Email', $rowEmail["email"]);
									}
								}
								if ($parents=="Y") {
									try {
										$dataStudents=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "transport"=>$t);
										$sqlStudents="SELECT gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND transport=:transport" ;
										$resultStudents=$connection2->prepare($sqlStudents);
										$resultStudents->execute($dataStudents);
									}
									catch(PDOException $e) { }
									while ($rowStudents=$resultStudents->fetch()) {
										try {
											$dataFamily=array("gibbonPersonID"=>$rowStudents["gibbonPersonID"]);
											$sqlFamily="SELECT DISTINCT gibbonFamily.gibbonFamilyID FROM gibbonFamily JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID" ;
											$resultFamily=$connection2->prepare($sqlFamily);
											$resultFamily->execute($dataFamily);
										}
										catch(PDOException $e) { }
										while ($rowFamily=$resultFamily->fetch()) {
											try {
												$dataEmail=array("gibbonFamilyID"=>$rowFamily["gibbonFamilyID"] );
												$sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT email='' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactEmail='Y'" ;
												$resultEmail=$connection2->prepare($sqlEmail);
												$resultEmail->execute($dataEmail);
											}
											catch(PDOException $e) { }
											while ($rowEmail=$resultEmail->fetch()) {
												$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Transport', $t, 'Email', $rowEmail["email"]);
											}
										}
									}
								}
							}
							if ($sms=="Y" AND $countryCode!="") {
								if ($staff=="Y") {
									try {
										$dataEmail=array("transport"=>$t);
										$sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone1='' AND phone1Type='Mobile' AND status='Full' AND transport=:transport)" ;
										$sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone2='' AND phone2Type='Mobile' AND status='Full' AND transport=:transport)" ;
										$sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone3='' AND phone3Type='Mobile' AND status='Full' AND transport=:transport)" ;
										$sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStaff ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone4='' AND phone4Type='Mobile' AND status='Full' AND transport=:transport)" ;
										$resultEmail=$connection2->prepare($sqlEmail);
										$resultEmail->execute($dataEmail);
									}
									catch(PDOException $e) { }
									while ($rowEmail=$resultEmail->fetch()) {
										$countryCodeTemp = $countryCode;
										if ($rowEmail["countryCode"]=="")
											$countryCodeTemp = $rowEmail["countryCode"];
										$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Transport', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
									}
								}
								if ($students=="Y") {
									try {
										$dataEmail=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "transport"=>$t);
										$sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone1='' AND phone1Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND transport=:transport)" ;
										$sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone2='' AND phone2Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND transport=:transport)" ;
										$sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone3='' AND phone3Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND transport=:transport)" ;
										$sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone4='' AND phone4Type='Mobile' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND transport=:transport)" ;
										$resultEmail=$connection2->prepare($sqlEmail);
										$resultEmail->execute($dataEmail);
									}
									catch(PDOException $e) { }
									while ($rowEmail=$resultEmail->fetch()) {
										$countryCodeTemp = $countryCode;
										if ($rowEmail["countryCode"]=="")
											$countryCodeTemp = $rowEmail["countryCode"];
										$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Transport', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
									}
								}
								if ($parents=="Y") {
									try {
										$dataStudents=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "transport"=>$t);
										$sqlStudents="SELECT gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND transport=:transport" ;
										$resultStudents=$connection2->prepare($sqlStudents);
										$resultStudents->execute($dataStudents);
									}
									catch(PDOException $e) { }
									while ($rowStudents=$resultStudents->fetch()) {
										try {
											$dataFamily=array("gibbonPersonID"=>$rowStudents["gibbonPersonID"]);
											$sqlFamily="SELECT DISTINCT gibbonFamily.gibbonFamilyID FROM gibbonFamily JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonPersonID=:gibbonPersonID" ;
											$resultFamily=$connection2->prepare($sqlFamily);
											$resultFamily->execute($dataFamily);
										}
										catch(PDOException $e) { }
										while ($rowFamily=$resultFamily->fetch()) {
											try {
												$dataEmail=array("gibbonFamilyID"=>$rowFamily["gibbonFamilyID"] );
												$sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone1='' AND phone1Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
												$sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone2='' AND phone2Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
												$sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone3='' AND phone3Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
												$sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT phone4='' AND phone4Type='Mobile' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y')" ;
												$resultEmail=$connection2->prepare($sqlEmail);
												$resultEmail->execute($dataEmail);
											}
											catch(PDOException $e) { }
											while ($rowEmail=$resultEmail->fetch()) {
												$countryCodeTemp = $countryCode;
												if ($rowEmail["countryCode"]=="")
													$countryCodeTemp = $rowEmail["countryCode"];
												$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Transport', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
											}
										}
									}
								}
							}
						}
					}
				}
			}

            //Target Absent students / Attendance Status
            if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_attendance")) {
                if ($_POST["attendance"]=="Y") {
                    $choices=$_POST["attendanceStatus"];
                    $students=$_POST["attendanceStudents"];
                    $parents=$_POST["attendanceParents"];
                    $selectedDate=dateConvert($guid, $_POST["attendanceDate"]);
                    if ($choices!="") {
                        foreach ($choices as $t) {
      						try {
      							$data=array("AI"=>$AI, "t"=>$t." ".$selectedDate, "students"=>$students, "parents"=>$parents);
      							$sql="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:AI, type='Attendance', id=:t, students=:students, parents=:parents" ;
      							$result=$connection2->prepare($sql);
      							$result->execute($data);
      						}
      						catch(PDOException $e) {
      							$partialFail=TRUE;
      						}
                        }
                        //Get all logs by student, with latest log entry first.
                        try {
                          $data=array("selectedDate"=>$selectedDate, "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "nowDate"=>date("Y-m-d"));
                          $sql="SELECT galp.gibbonPersonID, galp.gibbonAttendanceLogPersonID, galp.type, galp.date FROM gibbonAttendanceLogPerson AS galp JOIN gibbonStudentEnrolment AS gse ON (galp.gibbonPersonID=gse.gibbonPersonID) JOIN gibbonPerson AS gp ON (gse.gibbonPersonID=gp.gibbonPersonID) WHERE gp.status='Full' AND (gp.dateStart IS NULL OR gp.dateStart<=:nowDate) AND (gp.dateEnd IS NULL OR gp.dateEnd>=:nowDate) AND gse.gibbonSchoolYearID=:gibbonSchoolYearID AND galp.date=:selectedDate ORDER BY galp.gibbonPersonID, gibbonAttendanceLogPersonID DESC" ;
                          $result=$connection2->prepare($sql);
                          $result->execute($data);
                        }
                        catch(PDOException $e) { }

                        if ($result->rowCount()<1) { //Check we have some attendance logs for this date
                        //No attendance data
                        }
                        else { //Log the personIDs of the students whose latest attendance log is in list of choices submitted by user
                          $selectedStudents=array();
                          $currentStudent="";
                          $lastStudent="";
                          while ($row=$result->fetch()) {
                            $currentStudent=$row["gibbonPersonID"] ;
                            if (in_array($row["type"], $choices) AND $currentStudent!=$lastStudent) {
                              $selectedStudents[]=$currentStudent ;
                            }
                            $lastStudent=$currentStudent ;
                          }

                          if (count($selectedStudents)<1) {
                          //If we have no students
                          }
                          else {
                            if ($parents=="Y" AND ($email=="Y" OR ($sms=="Y" AND $countryCode!=""))) {
                              try { //Get the familyIDs for each student logged
                                $dataFamily=array();
                                $sqlFamily="SELECT DISTINCT gibbonFamilyID FROM gibbonFamilyChild WHERE gibbonPersonID IN (".implode(",",$selectedStudents).")" ;
                                $resultFamily=$connection2->prepare($sqlFamily);
                                $resultFamily->execute($dataFamily);

                                $resultFamilies = $resultFamily->fetchAll();
                              }
                              catch(PDOException $e) { }
                            }

                            //Get emails
                            if ($email=="Y") {
                              if ($parents=="Y") {
                                foreach ($resultFamilies as $rowFamily) { //Get the emails for each familyID
                                  try {
                                    $dataEmail=array("gibbonFamilyID"=>$rowFamily["gibbonFamilyID"] );
                                    $sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT email='' AND status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactEmail='Y'" ;
                                    $resultEmail=$connection2->prepare($sqlEmail);
                                    $resultEmail->execute($dataEmail);
                                  }
                                  catch(PDOException $e) { }
                                  while ($rowEmail=$resultEmail->fetch()) { //Add emails to list of receivers
                                    $report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Attendance', $t, 'Email', $rowEmail["email"]);
                                  }
                                }
                              }
                              if ($students=="Y") {
                                try { //Get the email for each student
                                  $dataEmail=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonPersonIDs"=>join(",",$selectedStudents));
                                  $sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE NOT email='' AND status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.gibbonPersonID IN (:gibbonPersonIDs)" ;
                                  $resultEmail=$connection2->prepare($sqlEmail);
                                  $resultEmail->execute($dataEmail);
                                }
                                catch(PDOException $e) { }
                                while ($rowEmail=$resultEmail->fetch()) { //Add emails to list of receivers
                                  $report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Attendance', $t, 'Email', $rowEmail["email"]);
                                }
                              }
                            } //end get emails
                            //Get SMS
                            if ($sms=="Y" AND $countryCode!="") {
                              if ($parents=="Y") {
                                foreach ($resultFamilies as $rowFamily) { //Get the people for each familyID
                                  try {
                                    $dataPerson=array("gibbonFamilyID"=>$rowFamily["gibbonFamilyID"] );
                                    $sqlPerson="SELECT DISTINCT gibbonPerson.gibbonPersonID FROM gibbonPerson JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full' AND gibbonFamilyAdult.gibbonFamilyID=:gibbonFamilyID AND contactSMS='Y'" ;
                                    $resultPerson=$connection2->prepare($sqlPerson);
                                    $resultPerson->execute($dataPerson);
                                  }
                                  catch(PDOException $e) { }
                                  while ($rowPerson=$resultPerson->fetch()) { //Add phone numbers to SMS receivers
                                    try {
                                      $dataSMS=array("gibbonPersonID"=>$rowPerson["gibbonPersonID"] );
                                      $sqlSMS="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE NOT phone1='' AND phone1Type='Mobile' AND gibbonPersonID=:gibbonPersonID)" ;
                                      $sqlSMS.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE NOT phone2='' AND phone2Type='Mobile' AND gibbonPersonID=:gibbonPersonID)" ;
                                      $sqlSMS.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE NOT phone3='' AND phone3Type='Mobile' AND gibbonPersonID=:gibbonPersonID)" ;
                                      $sqlSMS.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE NOT phone4='' AND phone4Type='Mobile' AND gibbonPersonID=:gibbonPersonID)" ;
                                      $resultSMS=$connection2->prepare($sqlSMS);
                                      $resultSMS->execute($dataSMS);
                                    }
                                    catch(PDOException $e) { }
                                    while ($rowSMS=$resultSMS->fetch()) {
										$countryCodeTemp = $countryCode;
	  									  if ($rowEmail["countryCode"]=="")
	  										  $countryCodeTemp = $rowEmail["countryCode"];
	  									  $report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Role', $t, 'Attendance', $countryCodeTemp.$rowEmail["phone"]);
                                    }
                                  }
                                }
                              }
                              if ($students=="Y") {
                                try { //Get the phone numbers for each student
                                  foreach ($selectedStudents as $t) {
                                    $dataSMS=array("gibbonPersonID"=>$t);
                                    $sqlSMS="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE NOT phone1='' AND phone1Type='Mobile' AND gibbonPersonID=:gibbonPersonID AND status='Full')" ;
                                    $sqlSMS.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE NOT phone2='' AND phone2Type='Mobile' AND gibbonPersonID=:gibbonPersonID AND status='Full')" ;
                                    $sqlSMS.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE NOT phone3='' AND phone3Type='Mobile' AND gibbonPersonID=:gibbonPersonID AND status='Full')" ;
                                    $sqlSMS.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE NOT  phone4='' AND phone4Type='Mobile' AND gibbonPersonID=:gibbonPersonID AND status='Full')" ;
                                    $resultSMS=$connection2->prepare($sqlSMS);
                                    $resultSMS->execute($dataSMS);
                                  }
                                }
                                catch(PDOException $e) { }
                                while ($rowSMS=$resultSMS->fetch()) {
									$countryCodeTemp = $countryCode;
	   								 if ($rowEmail["countryCode"]=="")
	   									 $countryCodeTemp = $rowEmail["countryCode"];
	   								 $report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Attendance', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
                                }
                              }
                            } //END SMS
                          }
                        }
                      }
                    }
                  }//END Target Absent students / Attendance Status

			//Individuals
			if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php", "New Message_individuals")) {
				if ($_POST["individuals"]=="Y") {
					$choices=$_POST["individualList"] ;
					if ($choices!="") {
						foreach ($choices as $t) {
							try {
								$data=array("gibbonMessengerID"=>$AI, "id"=>$t);
								$sql="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:gibbonMessengerID, type='Individuals', id=:id" ;
								$result=$connection2->prepare($sql);
								$result->execute($data);
							}
							catch(PDOException $e) {
								$partialFail=TRUE;
							}

							if ($email=="Y") {
								try {
									$dataEmail=array("gibbonPersonID"=>$t);
									$sqlEmail="SELECT DISTINCT email, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE NOT email='' AND gibbonPersonID=:gibbonPersonID AND status='Full'" ;
									$resultEmail=$connection2->prepare($sqlEmail);
									$resultEmail->execute($dataEmail);
								}
								catch(PDOException $e) { }
								while ($rowEmail=$resultEmail->fetch()) {
									$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Individuals', $t, 'Email', $rowEmail["email"]);
								}
							}
							if ($sms=="Y" AND $countryCode!="") {
								try {
									$dataEmail=array("gibbonPersonID"=>$t);
									$sqlEmail="(SELECT phone1 AS phone, phone1CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE NOT phone1='' AND phone1Type='Mobile' AND gibbonPersonID=:gibbonPersonID AND status='Full')" ;
									$sqlEmail.=" UNION (SELECT phone2 AS phone, phone2CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE NOT phone2='' AND phone2Type='Mobile' AND gibbonPersonID=:gibbonPersonID AND status='Full')" ;
									$sqlEmail.=" UNION (SELECT phone3 AS phone, phone3CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE NOT phone3='' AND phone3Type='Mobile' AND gibbonPersonID=:gibbonPersonID AND status='Full')" ;
									$sqlEmail.=" UNION (SELECT phone4 AS phone, phone4CountryCode AS countryCode, gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE NOT phone4='' AND phone4Type='Mobile' AND gibbonPersonID=:gibbonPersonID AND status='Full')" ;
									$resultEmail=$connection2->prepare($sqlEmail);
									$resultEmail->execute($dataEmail);
								}
								catch(PDOException $e) { }
								while ($rowEmail=$resultEmail->fetch()) {
									$countryCodeTemp = $countryCode;
									if ($rowEmail["countryCode"]=="")
										$countryCodeTemp = $rowEmail["countryCode"];
									$report = reportAdd($report, $emailReceipt, $rowEmail['gibbonPersonID'], 'Individuals', $t, 'SMS', $countryCodeTemp.$rowEmail["phone"]);
								}
							}
						}
					}
				}
			}

			if ($email=="Y") {
				require $_SESSION[$guid]["absolutePath"] . '/lib/PHPMailer/PHPMailerAutoload.php';

				//Prep message
				$bodyFin = "<p style='font-style: italic'>" . sprintf(__($guid, 'Email sent via %1$s at %2$s.'), $_SESSION[$guid]["systemName"], $_SESSION[$guid]["organisationName"]) ."</p>" ;

				//Send to sender
				$emailCount=1 ;
				$mail=getGibbonMailer($guid);
				$mail->IsSMTP();
				
				if ( empty($emailReplyTo) ) {
					$emailReplyTo = $from;
				}

				if ($from!=$_SESSION[$guid]["email"]) {	//If sender is using school-wide address, send from school
					$mail->SetFrom($from, $_SESSION[$guid]["organisationName"]);
					$mail->AddReplyTo($emailReplyTo, $_SESSION[$guid]["organisationName"] );
				}
				else { //Else, send from individual
					$mail->SetFrom($from, $_SESSION[$guid]["preferredName"] . " " . $_SESSION[$guid]["surname"]);
					$mail->AddReplyTo($emailReplyTo, $_SESSION[$guid]["preferredName"] . " " . $_SESSION[$guid]["surname"]);
				}
				
				$mail->AddAddress($from);
				$mail->CharSet="UTF-8";
				$mail->Encoding="base64" ;
				$mail->IsHTML(true);
				$mail->Subject=$subject ;
				$mail->Body = $body.$bodyFin ;
				$mail->AltBody = emailBodyConvert($body.$bodyFin) ;
				if(!$mail->Send()) {
					$partialFail = TRUE ;
				}

				//If sender is using school-wide address, send to school-wide address
				if ($from!=$_SESSION[$guid]["email"]) { //If sender is using school-wide address, add them to recipient list.
					$emailCount ++;
					$mail->ClearAddresses();
					$mail->AddAddress($_SESSION[$guid]["email"]);
					if(!$mail->Send()) {
						$partialFail = TRUE ;
					}
				}

				//Send to each recipient
				foreach ($report as $reportEntry) {
					if ($reportEntry[3] == 'Email') {
						$emailCount ++;
						$mail->ClearAddresses();
						$mail->AddAddress($reportEntry[4]);
						//Deal with email receipt
						if ($emailReceipt == 'Y') {
							$bodyReadReceipt = "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Messenger/messenger_emailReceiptConfirm.php&gibbonMessengerID=$AI&gibbonPersonID=".$reportEntry[0]."&key=".$reportEntry[5]."'>".$emailReceiptText."</a>";
							$mail->Body = $body.$bodyReadReceipt.$bodyFin ;
							$mail->AltBody = emailBodyConvert($body.$bodyReadReceipt.$bodyFin) ;
						}
						else {
							$mail->Body = $body.$bodyFin ;
							$mail->AltBody = emailBodyConvert($body.$bodyFin) ;
						}
						if(!$mail->Send()) {
							$partialFail = TRUE ;
						}
					}
				}
			}

			if ($sms=="Y") {
				if ($countryCode=="") {
					$partialFail=TRUE ;
				}
				else {
					$smsUsername=getSettingByScope( $connection2, "Messenger", "smsUsername" ) ;
					$smsPassword=getSettingByScope( $connection2, "Messenger", "smsPassword" ) ;
					$smsURL=getSettingByScope( $connection2, "Messenger", "smsURL" ) ;

					if ($smsUsername!="" AND $smsPassword!="" AND $smsURL!="") {
						$smsCount = 0;
						foreach ($report as $reportEntry) {
							if ($reportEntry[3] == 'SMS') {
								$smsCount ++;
							}
						}

						$numbersPerSend=10 ;
						$sendReps=ceil(($smsCount/$numbersPerSend)) ;
						$smsCount=0 ;
						$smsBatchCount=0 ;
						for ($i=0; $i<$sendReps; $i++) {
							$numCache="" ;
							for ($n=0; $n<$numbersPerSend; $n++) {
								if (!(is_null($report[($i*$numbersPerSend)+$n][3]))) {
									if ($report[($i*$numbersPerSend)+$n][3] == 'SMS') {
										$numCache.=$report[($i*$numbersPerSend)+$n][4] . "," ;
										$smsCount++ ;
									}
								}
							}

							$query="?apiusername=" . $smsUsername . "&apipassword=" . $smsPassword . "&senderid=" . rawurlencode($_SESSION[$guid]["organisationNameShort"]) . "&mobileno=" . rawurlencode(substr($numCache,0,-1)) . "&message=" . rawurlencode(stripslashes(strip_tags($body))) . "&languagetype=1" ;
							$result=@implode('', file($smsURL . $query)) ;

							if ($result) {
								if ($result<=0) {
									//error held in $result if needed
									$partialFail=TRUE ;
								}
							}
							else {
								$partialFail=TRUE ;
							}
							$smsBatchCount++ ;
						}
					}
					else {
						$partialFail=TRUE ;
					}
				}
			}

			//Write report entries
			foreach ($report as $reportEntry) {
				try {
					$confirmed = null ;
					if ($reportEntry[5] != '')
						$confirmed = 'N';
					$data=array("gibbonMessengerID"=>$AI, "gibbonPersonID"=>$reportEntry[0], "targetType"=>$reportEntry[1], "targetID"=>$reportEntry[2], "contactType"=>$reportEntry[3], "contactDetail"=>$reportEntry[4], "key"=>$reportEntry[5], "confirmed" => $confirmed);
					$sql="INSERT INTO gibbonMessengerReceipt SET gibbonMessengerID=:gibbonMessengerID, gibbonPersonID=:gibbonPersonID, targetType=:targetType, targetID=:targetID, contactType=:contactType, contactDetail=:contactDetail, `key`=:key, confirmed=:confirmed" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) {
					$partialFail = true;
				}
			}

			if ($partialFail == TRUE) {
				//Fail 4
				$URL.="&addReturn=fail4" ;
				header("Location: {$URL}");
			}
			else {
				//Success 0
				$URL.="&addReturn=success0&emailCount=" . $emailCount . "&smsCount=" . $smsCount . "&smsBatchCount=" . $smsBatchCount ;
				header("Location: {$URL}") ;
			}
		}
	}
}
?>
