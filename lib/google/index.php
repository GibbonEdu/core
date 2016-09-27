<?php
/**
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

if (! isset($this) || ! $this instanceof Gibbon\core\view) {
	$_POST['_token'] = '';
	$dr = dirname(dirname(dirname(__FILE__)));
	$path = rtrim( str_replace("\\", '/', $dr), '/' ) .'/';
	$_POST['action'] = $path . 'lib/google/index.php';
	$_POST['address'] = '/lib/google/index.php';
	$_POST['divert'] = true ;
	$_POST['absoluteAction'] = true ;
	include $path. 'config.php';
	$_POST['_token'] = md5($guid . $_POST['action']);
	if (! class_exists('gibbon'))
		include_once $path.'src/controller/gibbon.php';
}

use Gibbon\core\logger ;
use Gibbon\Record\schoolYear ;
use Gibbon\Record\theme ;

$syObj = new schoolYear($this);
$syObj->setCurrentSchoolYear() ;

//The current/actual school year info, just in case we are working in a different year
$this->session->set("gibbonSchoolYearIDCurrent", $this->session->get("gibbonSchoolYearID")) ;
$this->session->set("gibbonSchoolYearNameCurrent", $this->session->get("gibbonSchoolYearName")) ;
$this->session->set("gibbonSchoolYearSequenceNumberCurrent", $this->session->get("gibbonSchoolYearSequenceNumber")) ;

$this->session->set("pageLoads", -1);

$URL = "index.php" ;

//Cleint ID and Secret
$client_id = $this->config->getSettingByScope("System", "googleClientID" ) ;
$client_secret = $this->config->getSettingByScope("System", "googleClientSecret" ) ;
$redirect_uri = $this->config->getSettingByScope("System", "googleRedirectUri" ) ;

//incase of logout request, just unset the session var

/************************************************
  Make an API request on behalf of a user. In
  this case we need to have a valid OAuth 2.0
  token for the user, so we need to send them
  through a login flow. To do this we need some
  information from our API console project.
 ************************************************/
require GIBBON_ROOT . 'vendor/autoload.php';  
$client = new Google_Client();
$client->setClientId($client_id);
$client->setClientSecret($client_secret);
$client->setRedirectUri($redirect_uri);
//$client->addScope("email");
//$client->addScope("profile");
$client->setScopes(array('https://www.googleapis.com/auth/userinfo.email', 'https://www.googleapis.com/auth/plus.me', 'https://www.googleapis.com/auth/calendar')); // set scope during user login

/************************************************
  When we create the service here, we pass the
  client to it. The client then queries the service
  for the required scopes, and uses that when
  generating the authentication URL later.
 ************************************************/
$service = new Google_Service_Oauth2($client);

/************************************************
  If we have a code back from the OAuth 2.0 flow,
  we need to exchange that with the authenticate()
  function. We store the resultant access token
  bundle in the session, and redirect to ourself.
*/

if (isset($_GET['code'])) {
	$client->authenticate($_GET['code']);
	$this->session->set('googleAPIAccessToken', $client->getAccessToken());
	$this->redirect(filter_var($redirect_uri, FILTER_SANITIZE_URL));
}

/************************************************
  If we have an access token, we can make
  requests, else we generate an authentication URL.
 ************************************************/
@$refreshToken=json_decode($this->session->get('googleAPIAccessToken') )->refresh_token ;

if ($this->session->notEmpty('googleAPIAccessToken')  && $this->session->get('googleAPIAccessToken') ) {
	$client->setAccessToken($this->session->get('googleAPIAccessToken') );
} else {
	$authUrl = $client->createAuthUrl();
}


//Display user info or display login url as per the info we have.

if (isset($authUrl)){
	//show login url
	print '<div style="margin: 0px">';
		print '<a target=\'_top\' class="login" href="' . $authUrl . '"><img width="250" src="src/themes/' . $this->session->get("gibbonThemeName") . '/img/g_login_btn.png" /></a>';
	print '</div>';
} else {
	$user = $service->userinfo->get(); //get user info
	$email = $user->email;
	$this->session->set('gplusuer', $user);

	$data=array("email"=>$email);
	$sql="SELECT * FROM gibbonPerson WHERE (email=:email)" ;
	$result=$this->pdo->executeQuery($data, $sql);

	//Test to see if email exists in logintable
	if ($result->rowCount()!=1) {
		$URL = $this->session->get('absoluteURL');
		$this->session->clear('googleAPIAccessToken');
		$this->session->clear('gplusuer');
 		$this->session->destroy();
 		$this->session->start();
		$URL .= "/index.php?loginReturn=fail8" ;
		$this->redirect($URL);
	}
	//Start to collect User Info and test
	$data=array("email"=>$email);
	$sql="SELECT * FROM gibbonPerson WHERE ((email=:email) AND (status='Full') AND (canLogin='Y'))" ;
	$result=$this->pdo->executeQuery($data, $sql);

	//Test to see if gmail matches email in gibbon
	if ($result->rowCount()!=1) {
		$URL = GIBBON_URL ;
		$URL .= "index.php?loginReturn=fail8" ;
		$this->session->clear('googleAPIAccessToken');
		$this->session->clear('gplusuer');
		$this->session->destroy();
		$this->session->start();
	}
	else {
		$row=$result->fetch() ;

		$username=$row['username'];
		if ($row["failCount"]>=3) {
			$data=array("lastFailIPAddress"=> $_SERVER["REMOTE_ADDR"], "lastFailTimestamp"=> date("Y-m-d H:i:s"), "failCount"=>($row["failCount"]+1), "username"=>$username);
			$sqlSecure="UPDATE gibbonPerson SET lastFailIPAddress=:lastFailIPAddress, lastFailTimestamp=:lastFailTimestamp, failCount=:failCount WHERE (username=:username)";
			$resultSecure=$this->pdo->executeQuery($data, $sqlSecure);

			if ($row["failCount"]==3) {
				$to = $this->getSecurity()->getSettingByScope("System", "organisationAdministratorEmail") ;
				$subject=$this->session->get("organisationNameShort") . " Failed Login Notification";
				$body="Please note that someone has failed to login to account \"$username\" 3 times in a row.\n\n" . $this->session->get("systemName") . " Administrator";
				$headers="From: " . $to ;
				mail($to, $subject, $body, $headers) ;
			}

			$URL.="?loginReturn=fail6" ;
			$this->redirect($URL);
		}
		if ($row["passwordForceReset"]=="Y") {
			$salt=$this->getSecurity()->getSalt() ;
			$password=$this->getSecurity()->randomPassword(8);
			$passwordStrong=$this->getSecurity()->getPasswordHash($password, $salt) ;
			$this->session->set('username', $username);
			$this->getSecurity()->updatePassword($passwordStrong, $salt);
			$data=array("passwordStrong"=>$passwordStrong, "passwordStrongSalt"=>$salt, "username"=>$username);
			$sql="UPDATE gibbonPerson SET password='', passwordStrong=:passwordStrong, passwordStrongSalt=:passwordStrongSalt, failCount=0, passwordForceReset='N' WHERE username=:username";
			$result=$this->pdo->executeQuery($data, $sql);

			$row["passwordForceReset"]="N" ;

			$to=$row["email"];
			$subject=$this->session->get("organisationNameShort") . " Gibbon Password Reset";
			$body="Your new password for account $username is as follows:\n\n$password\n\nPlease log in an change your password as soon as possible.\n\n" . $this->session->get("systemName") . " Administrator";
			$headers="From: " . $this->session->get("organisationAdministratorEmail") ;
			mail($to, $subject, $body, $headers) ;
		}


		if ($row["gibbonRoleIDPrimary"]=="" || count($this->getSecurity()->getRoleList($row["gibbonRoleIDAll"]))==0) {
			//FAILED TO SET ROLES
			$URL.="?loginReturn=fail2" ;
			$this->redirect($URL);
		}
		$this->session->set("username", $username) ;
		$this->session->set("email", $email) ;
		$this->session->set("passwordStrong", $row["passwordStrong"]) ;
		$this->session->set("passwordStrongSalt", $row["passwordStrongSalt"]) ;
		$this->session->set("passwordForceReset", $row["passwordForceReset"]) ;
		$this->session->set("gibbonPersonID", $row["gibbonPersonID"]) ;
		$this->session->set("surname", $row["surname"]) ;
		$this->session->set("firstName", $row["firstName"]) ;
		$this->session->set("preferredName", $row["preferredName"]) ;
		$this->session->set("officialName", $row["officialName"]) ;
		$this->session->set("email", $row["email"]) ;
		$this->session->set("emailAlternate", $row["emailAlternate"]) ;
		$this->session->set("website", $row["website"]) ;
		$this->session->set("gender", $row["gender"]) ;
		$this->session->set("status", $row["status"]) ;
		$this->session->set("gibbonRoleIDPrimary", $row["gibbonRoleIDPrimary"]) ;
		$this->session->set("gibbonRoleIDCurrent", $row["gibbonRoleIDPrimary"]) ;
		$this->session->set("gibbonRoleIDCurrentCategory", $this->getSecurity()->getRoleCategory($row["gibbonRoleIDPrimary"]) ) ;
		$this->session->set("gibbonRoleIDAll", $this->getSecurity()->getRoleList($row["gibbonRoleIDAll"])) ;
		$this->session->set("image_240", $row["image_240"]) ;
		$this->session->set("lastTimestamp", $row["lastTimestamp"]) ;
		$this->session->set("calendarFeedPersonal", $row["calendarFeedPersonal"]) ;
		$this->session->set("viewCalendarSchool", $row["viewCalendarSchool"]) ;
		$this->session->set("viewCalendarPersonal", $row["viewCalendarPersonal"]) ;
		$this->session->set("viewCalendarSpaceBooking", $row["viewCalendarSpaceBooking"]) ;
		$this->session->set("dateStart", $row["dateStart"]) ;
		$this->session->set("personalBackground", $row["personalBackground"]) ;
		$this->session->set("messengerLastBubble", $row["messengerLastBubble"]) ;
		$this->session->set("gibbonThemeIDPersonal", $row["gibbonThemeIDPersonal"]) ;
		$this->session->set("personalLanguageCode", $row["personalLanguageCode"]) ;
		$this->session->set("googleAPIRefreshToken", $row["googleAPIRefreshToken"]) ;
		$this->session->set('receiveNotificationEmails', $row["receiveNotificationEmails"]) ;
		$this->session->set('gibbonHouseID', $row["gibbonHouseID"]) ;
		$this->session->set('lastPageTime', strtotime('now'));
		$this->session->set('sessionDuration', $this->config->getSettingByScope('System', 'sessionDuration'));					
		
		//If user has personal language set, load it to session variable.
		//Allow for non-system default language to be specified from login form
		if (isset($_POST["gibboni18nCode"]))
			$this->session->setLanguageSession($_POST["gibboni18nCode"]) ;
		elseif ($this->session->notEmpty("personalLanguage")) 
			$this->session->setLanguageSession($this->session->get("personalLanguage")) ;
		elseif ($this->config->getSettingByScope('System', 'defaultLangauge') !== NULL) 
			$this->session->setLanguageSession($this->config->getSettingByScope('System', 'defaultLangauge')) ;

		$data=array( "lastIPAddress"=> $_SERVER["REMOTE_ADDR"], "lastTimestamp"=> date("Y-m-d H:i:s"), "failCount"=>0, "username"=> $username );
		$sql="UPDATE gibbonPerson SET lastIPAddress=:lastIPAddress, lastTimestamp=:lastTimestamp, failCount=:failCount WHERE username=:username" ;
		$result=$this->pdo->executeQuery($data, $sql);
		//Set Goolge API refresh token where appropriate, and update user
		if (! empty($refreshToken)) {
			$this->session->set("googleAPIRefreshToken", $refreshToken) ;
			
			$data=array( "googleAPIRefreshToken"=> $this->session->get("googleAPIRefreshToken"), "username"=> $username );
			$sql="UPDATE gibbonPerson SET googleAPIRefreshToken=:googleAPIRefreshToken WHERE username=:username" ;
			$result=$this->pdo->executeQuery($data, $sql);
		}
		if ($this->session->notEmpty("username")) {
			$URL = GIBBON_URL."index.php" ;
		}
		else {
			$URL = $this->session->get('absoluteURL')."/index.php?loginReturn=fail8" ;
			$this->session->clear('googleAPIAccessToken');
			$this->session->clear('gplusuer');
			$this->session->destroy();
			$this->session->start();
		}
		if ($this->session->get("gibbonThemeIDPersonal") != $this->session->get("gibbonThemeID") && $this->session->notEmpty("gibbonThemeIDPersonal"))
		{
			$tObj = new theme($this);
			$tObj->setDefaultTheme();
		} else {
			$tObj = new theme($this, $this->session->get("gibbonThemeID"));
			$tObj->setDefaultTheme();
		}
		logger::__("Login - Success - Google API ", 'Info', 'Security', array("username"=>$username), $this->pdo) ;
		$this->redirect($URL);
	}






	//print user details
	
	if (isset($_GET['logout'])) {
		$URL = $this->session->get('absoluteURL')."/index.php" ;
		$this->session->clear('googleAPIAccessToken' );
		$this->session->clear('gplusuer');
		$this->session->destroy();  //Clear the Session
		$this->session->start();	// and start again . 
		$this->redirect($URL); // it will simply destroy the current seesion which you started before
		//NOTE: for logout and clear all the session direct google just uncomment the above line and comment the first header function
	}
}
