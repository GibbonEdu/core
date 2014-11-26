<?php
session_start() ;
include "../../functions.php" ;
include "../../config.php" ;

try {
  	$connection2=new PDO("mysql:host=$databaseServer;dbname=$databaseName;charset=utf8", $databaseUsername, $databasePassword);
	$connection2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$connection2->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
}
catch(PDOException $e) {
  echo $e->getMessage();
}
setCurrentSchoolYear($guid, $connection2) ;

//The current/actual school year info, just in case we are working in a different year
$_SESSION[$guid]["gibbonSchoolYearIDCurrent"]=$_SESSION[$guid]["gibbonSchoolYearID"] ;
$_SESSION[$guid]["gibbonSchoolYearNameCurrent"]=$_SESSION[$guid]["gibbonSchoolYearName"] ;
$_SESSION[$guid]["gibbonSchoolYearSequenceNumberCurrent"]=$_SESSION[$guid]["gibbonSchoolYearSequenceNumber"] ;

$_SESSION[$guid]["pageLoads"]=NULL ;

$URL="index.php" ;



/*
 * Copyright 2011 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
require_once 'src/Google_Client.php'; // include the required class files for google login
require_once 'src/contrib/Google_PlusService.php';
require_once 'src/contrib/Google_Oauth2Service.php';

//Get Google Oauth Details
try {
		$data=array(); 
		$sql="SELECT value from gibbonSetting where scope='System' and name='googleClientName'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { }
	
	//Test to see if Google Client Name exists.
	if ($result->rowCount()!=1) {
		unset($_SESSION[$guid]['google_api_access_token']);
		unset($_SESSION[$guid]['gplusuer']);
 		session_destroy();
		$_SESSION[$guid]=NULL ;
		
	}
	else {
		$row=$result->fetch() ;
		$googleClientName=$row['value'];
	}
	
	try {
		$data=array(); 
		$sql="SELECT value from gibbonSetting where scope='System' and name='googleClientID'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { }
	
	//Test to see if Google Client id exists.
	if ($result->rowCount()!=1) {
		unset($_SESSION[$guid]['google_api_access_token']);
		unset($_SESSION[$guid]['gplusuer']);
 		session_destroy();
		$_SESSION[$guid]=NULL ;
		
	}
	else {
		$row=$result->fetch() ;
		$googleClientID=$row['value'];
	}


try {
		$data=array(); 
		$sql="SELECT value from gibbonSetting where scope='System' and name='googleClientSecret'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { }
	
	//Test to see if Google Client id exists.
	if ($result->rowCount()!=1) {
		unset($_SESSION[$guid]['google_api_access_token']);
		unset($_SESSION[$guid]['gplusuer']);
 		session_destroy();
		$_SESSION[$guid]=NULL ;
		
	}
	else {
		$row=$result->fetch() ;
		$googleClientSecret=$row['value'];
	}
	
try {
		$data=array(); 
		$sql="SELECT value from gibbonSetting where scope='System' and name='googleRedirectUri'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { }
	
	//Test to see if Google Redirect Url exists.
	if ($result->rowCount()!=1) {
		unset($_SESSION[$guid]['google_api_access_token']);
		unset($_SESSION[$guid]['gplusuer']);
 		session_destroy();
		$_SESSION[$guid]=NULL ;
		
	}
	else {
		$row=$result->fetch() ;
		$googleRedirectUri=$row['value'];
	}
	
try {
		$data=array(); 
		$sql="SELECT value from gibbonSetting where scope='System' and name='googleDeveloperKey'" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { }
	
	//Test to see if Google Developer Key exists.
	if ($result->rowCount()!=1) {
		unset($_SESSION[$guid]['google_api_access_token']);
		unset($_SESSION[$guid]['gplusuer']);
 		session_destroy();
		$_SESSION[$guid]=NULL ;
		
	}
	else {
		$row=$result->fetch() ;
		$googleDeveloperKey=$row['value'];
	}

$client=new Google_Client();
$client->setApplicationName($googleClientName); // Set your applicatio name
$client->setScopes(array('https://www.googleapis.com/auth/userinfo.email', 'https://www.googleapis.com/auth/plus.me', 'https://www.googleapis.com/auth/calendar')); // set scope during user login
$client->setClientId($googleClientID); // paste the client id which you get from google API Console
$client->setClientSecret($googleClientSecret); // set the client secret
$client->setRedirectUri($googleRedirectUri); // paste the redirect URI where you given in APi Console. You will get the Access Token here during login success
$client->setDeveloperKey($googleDeveloperKey); // Developer key
$plus 		=new Google_PlusService($client);
$oauth2 	=new Google_Oauth2Service($client); // Call the OAuth2 class for get email address
if(isset($_GET['code'])) {
	$client->authenticate(); // Authenticate
	$_SESSION[$guid]['google_api_access_token']=$client->getAccessToken(); // get the access token here
	header('Location: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);
}

if(isset($_SESSION[$guid]['google_api_access_token'])) {
	$client->setAccessToken($_SESSION[$guid]['google_api_access_token']);
}

if ($client->getAccessToken()) {
  $user 		=$oauth2->userinfo->get();
  $me 			=$plus->people->get('me');
  $optParams 	=array('maxResults'=> 100);
  $activities 	=$plus->activities->listActivities('me', 'public',$optParams);
  // The access token may have been updated lazily.
  $_SESSION[$guid]['google_api_access_token'] 		=$client->getAccessToken();
  $email 							=filter_var($user['email'], FILTER_SANITIZE_EMAIL); // get the USER EMAIL ADDRESS using OAuth2
  $_SESSION['emailaddress']=$email;
} else {
	$authUrl=$client->createAuthUrl();
}

if(isset($me)){ 
	$_SESSION[$guid]['gplusuer']=$me; // start the session
	try {
		$data=array("email"=>$email); 
		$sql="SELECT * FROM gibbonPerson WHERE (email=:email)" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) {}
	
	//Test to see if email exists in logintable
	if ($result->rowCount()!=1) {
		unset($_SESSION[$guid]['google_api_access_token']);
		unset($_SESSION[$guid]['gplusuer']);
 		session_destroy();
		$_SESSION[$guid]=NULL ;
		$URL="../../index.php?loginReturn=fail8" ;
		header("Location: {$URL}");
	}
	else {
		//logged in	
	}
}

if(isset($_GET['logout'])) {
	 	
  unset($_SESSION[$guid]['google_api_access_token']);
  unset($_SESSION[$guid]['gplusuer']);
 
  session_destroy();
  header('Location: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']); // it will simply destroy the current seesion which you started before
  #header('Location: https://www.google.com/accounts/Logout?continue=https://appengine.google.com/_ah/logout?continue=http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);
  
  /*NOTE: for logout and clear all the session direct google just uncomment the above line and comment the first header function */
}
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Google Apps login using PHP with user email</title>
<style>
body{
	margin: 0;
	padding: 0;
	font-family: arial;
	color: #2C2C2C;
	font-size: 14px;
}
h1 a{
	color:#2C2C2C;
	text-decoration:none;
}
h1 a:hover{
	text-decoration:underline;
}
a{
	color: #069FDF;
}
.wrapper{
	margin: 0 auto;
	width: 254px;
	height: 46px;
}
.mytable{
	width: 700px;
	margin: 0 auto;
	border:2px dashed #17A3F7;
	padding: 20px;
}
</style>
</head>
<body>
<div class="wrapper">
<?php 
if(isset($authUrl)) {
	echo "<a class='login' target='_top' href='$authUrl'><img style='width: 254px; height: 44px;' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/g_login_btn.png' alt='Login With Google' /></a>";
	} else {
	echo "<a class='logout' href='index.php?logout'>Logout</a>";
}
if(isset($_SESSION[$guid]['gplusuer'])){ ?>
<?php

try {
		$data=array("email"=>$email); 
		$sql="SELECT * FROM gibbonPerson WHERE ((email=:email) AND (status='Full') AND (canLogin='Y'))" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { }
	
	//Test to see if gmail matches email in gibbon
	if ($result->rowCount()!=1) {
		unset($_SESSION[$guid]['google_api_access_token']);
		unset($_SESSION[$guid]['gplusuer']);
 		session_destroy();
		$_SESSION[$guid]=NULL ;
		//$URL="../../index.php?loginReturn=fail7" ;
		//header("Location: {$URL}");
	}
	else {
		$row=$result->fetch() ;
		
		$username=$row['username'];
		if ($row["failCount"]>=3) {
			try {
				$data=array("lastFailIPAddress"=> $_SERVER["REMOTE_ADDR"], "lastFailTimestamp"=> date("Y-m-d H:i:s"), "failCount"=>($row["failCount"]+1), "username"=>$username); 
				$sqlSecure="UPDATE gibbonPerson SET lastFailIPAddress=:lastFailIPAddress, lastFailTimestamp=:lastFailTimestamp, failCount=:failCount WHERE (username=:username)";
				$resultSecure=$connection2->prepare($sqlSecure);
				$resultSecure->execute($data); 
			}
			catch(PDOException $e) { }
		
			if ($row["failCount"]==3) {
				$to=getSettingByScope($connection2, "System", "organisationAdministratorEmail") ;
				$subject=$_SESSION[$guid]["organisationNameShort"] . " Failed Login Notification";
				$body="Please note that someone has failed to login to account \"$username\" 3 times in a row.\n\n" . $_SESSION[$guid]["systemName"] . " Administrator";
				$headers="From: " . $to ;
				mail($to, $subject, $body, $headers) ;
			}
		
			$URL.="?loginReturn=fail6" ;
			header("Location: {$URL}");
		}
		
		//Check for forceReset password flag, and if Y, set to N and set random password, emailing user. This prevents lock out.
		if ($row["passwordForceReset"]=="Y") {
			$salt=getSalt() ;
			$password=randomPassword(8);
			$passwordStrong=hash("sha256", $salt.$password) ;
			
			try {
				$data=array("passwordStrong"=>$passwordStrong, "passwordStrongSalt"=>$salt, "username"=>$username); 
				$sql="UPDATE gibbonPerson SET password='', passwordStrong=:passwordStrong, passwordStrongSalt=:passwordStrongSalt, failCount=0, passwordForceReset='N' WHERE username=:username";
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { }
			
			$row["passwordForceReset"]="N" ;
			
			$to=$row["email"];
			$subject=$_SESSION[$guid]["organisationNameShort"] . " Gibbon Password Reset";
			$body="Your new password for account $username is as follows:\n\n$password\n\nPlease log in an change your password as soon as possible.\n\n" . $_SESSION[$guid]["systemName"] . " Administrator";
			$headers="From: " . $_SESSION[$guid]["organisationAdministratorEmail"] ;
			mail($to, $subject, $body, $headers) ;
		}
		
		if ($row["gibbonRoleIDPrimary"]=="" OR count(getRoleList($row["gibbonRoleIDAll"], $connection2))==0) {
					//FAILED TO SET ROLES
					$URL.="?loginReturn=fail2" ;
					header("Location: {$URL}");
				}
				//USER EXISTS, SET SESSION VARIABLES
					$_SESSION[$guid]["username"]=$username ;
					$_SESSION[$guid]["email"]=$email ;
					$_SESSION[$guid]["passwordStrong"]=$passwordStrong ;
					$_SESSION[$guid]["passwordStrongSalt"]=$salt ;
					$_SESSION[$guid]["passwordForceReset"]=$row["passwordForceReset"] ;
					$_SESSION[$guid]["gibbonPersonID"]=$row["gibbonPersonID"] ;
					$_SESSION[$guid]["surname"]=$row["surname"] ;
					$_SESSION[$guid]["firstName"]=$row["firstName"] ;
					$_SESSION[$guid]["preferredName"]=$row["preferredName"] ;
					$_SESSION[$guid]["officialName"]=$row["officialName"] ;
					$_SESSION[$guid]["email"]=$row["email"] ;
					$_SESSION[$guid]["emailAlternate"]=$row["emailAlternate"] ;
					$_SESSION[$guid]["website"]=$row["website"] ;
					$_SESSION[$guid]["gender"]=$row["gender"] ;
					$_SESSION[$guid]["status"]=$row["status"] ;
					$_SESSION[$guid]["gibbonRoleIDPrimary"]=$row["gibbonRoleIDPrimary"] ;
					$_SESSION[$guid]["gibbonRoleIDCurrent"]=$row["gibbonRoleIDPrimary"] ;
					$_SESSION[$guid]["gibbonRoleIDAll"]=getRoleList($row["gibbonRoleIDAll"], $connection2) ;
					$_SESSION[$guid]["image_240"]=$row["image_240"] ;
					$_SESSION[$guid]["image_75"]=$row["image_75"] ;
					$_SESSION[$guid]["lastTimestamp"]=$row["lastTimestamp"] ;
					$_SESSION[$guid]["calendarFeedPersonal"]=$row["calendarFeedPersonal"] ;
					$_SESSION[$guid]["viewCalendarSchool"]=$row["viewCalendarSchool"] ;
					$_SESSION[$guid]["viewCalendarPersonal"]=$row["viewCalendarPersonal"] ;
					$_SESSION[$guid]["viewCalendarSpaceBooking"]=$row["viewCalendarSpaceBooking"] ;
					$_SESSION[$guid]["dateStart"]=$row["dateStart"] ;
					$_SESSION[$guid]["personalBackground"]=$row["personalBackground"] ;
					$_SESSION[$guid]["messengerLastBubble"]=$row["messengerLastBubble"] ;
					$_SESSION[$guid]["gibbonThemeIDPersonal"]=$row["gibbonThemeIDPersonal"] ;
					$_SESSION[$guid]["gibboni18nIDPersonal"]=$row["gibboni18nIDPersonal"] ;
					
					//If user has personal language set, load it to session variable.
					if (!is_null($_SESSION[$guid]["gibboni18nIDPersonal"])) {
						try {
							$dataLanguage=array("gibboni18nID"=>$_SESSION[$guid]["gibboni18nIDPersonal"]); 
							$sqlLanguage="SELECT * FROM gibboni18n WHERE active='Y' AND gibboni18nID=:gibboni18nID" ; 
							$resultLanguage=$connection2->prepare($sqlLanguage);
							$resultLanguage->execute($dataLanguage);
						}
						catch(PDOException $e) { }
						if ($resultLanguage->rowCount()==1) {
							$rowLanguage=$resultLanguage->fetch() ;
							setLanguageSession($guid, $rowLanguage) ;
						}
					}
			
					//Make best effort to set IP address and other details, but no need to error check etc.
					try {
						$data=array( "lastIPAddress"=> $_SERVER["REMOTE_ADDR"], "lastTimestamp"=> date("Y-m-d H:i:s"), "failCount"=>0, "username"=> $username ); 
						$sql="UPDATE gibbonPerson SET lastIPAddress=:lastIPAddress, lastTimestamp=:lastTimestamp, failCount=:failCount WHERE username=:username" ;
						$result=$connection2->prepare($sql);
						$result->execute($data); 
					}
					catch(PDOException $e) { }
			
			
					if (isset($_SESSION[$guid]["username"])) {
						$URL="../../index.php" ;
					}
					else {
						unset($_SESSION[$guid]['google_api_access_token']);
						unset($_SESSION[$guid]['gplusuer']);
						session_destroy();
						$_SESSION[$guid]=NULL ;
						$URL="../../index.php?loginReturn=fail8" ;
			
					}		
					header("Location: {$URL}");		
				
		
	}
}
	
?>	




</div>
</body>
</html>