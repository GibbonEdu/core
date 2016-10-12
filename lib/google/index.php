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
	if (isset($_GET['q']))
		$this->session->set('calledPage',$_GET['q']);
	else 
		$this->session->clear('calledPage');
}


//Display user info or display login url as per the info we have.

if (isset($authUrl)){
	//show login url
	print '<a target=\'_top\' class="login" href="' . $authUrl . '"><img src="src/themes/' . $this->session->get("theme.Name") . '/img/g_login_btn.png" /></a>';
} else {
	$user = $service->userinfo->get(); //get user info
	$email = $user->email;
	$this->session->set('gplusuer', $user);

	$result = $this->getRecord('person')->findOneBy(array('email' => $email));
	$URL = GIBBON_URL;

	//Test to see if email exists in logintable
	if ($this->getRecord('person')->returnRecord() === false) {
		$this->session->clear('googleAPIAccessToken');
		$this->session->clear('gplusuer');
 		$this->session->destroy();
 		$this->session->start();
		$this->insertMessage(array('Gmail account does not match the email stored in %1$s. If you have logged in with your school Gmail account please contact %2$s if you have any questions.', array($this->session->get('systemName'), "<a href='mailto:".$this->session->get('organisationDBAEmail')."'>".$this->session->get('organisationDBAName').'</a>')));
		$this->redirect($URL);
	}
	//Start to collect User Info and test
	$result = $this->getRecord('person')->findOneBy(array('email' => $email, 'status' => 'Full', 'canLogin' => 'Y'));
	//Test to see if gmail matches email in gibbon
	if ($this->getRecord('person')->returnRecord() === false) {
		$this->session->clear('googleAPIAccessToken');
		$this->session->clear('gplusuer');
		$this->session->destroy();
		$this->session->start();
		$this->insertMessage(array('Gmail account does not match the email stored in %1$s. If you have logged in with your school Gmail account please contact %2$s if you have any questions.', array($this->session->get('systemName'), "<a href='mailto:".$this->session->get('organisationDBAEmail')."'>".$this->session->get('organisationDBAName').'</a>')));
		$this->redirect($URL);
	}
	else {
		$row = (array) $result ;

		$username = $row['username'];
		if ($row["failCount"] >= 3) {
			$this->getRecord('person')->recordLoginFailure();
			$this->insertMessage(array('Too many failed logins: please %1$sreset password%2$s.', array("<a href='".GIBBON_URL."index.php?q=/passwordReset.php'>", '</a>')));
			$this->redirect($URL);
		}
		if ($row["passwordForceReset"] == "Y")
		{
			$this->getRecord('person')->forcePasswordReset();
			$this->session->clear('googleAPIAccessToken');
			$this->session->clear('gplusuer');
			$this->session->destroy();
			$this->session->start();
			$this->insertMessage('Your account password must be reset before you can used this site!', 'error', false, 'login.flash');
			$this->redirect($URL);
		}


		if (empty($row["gibbonRoleIDPrimary"]) || count($this->getSecurity()->getRoleList($row["gibbonRoleIDAll"])) == 0) {
			//FAILED TO SET ROLES
			$this->insertMessage("You do not have sufficient privileges to login.", 'error', false, 'login.flash');
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
		$this->session->set("viewCalendar.School", $row['viewCalendarSchool']) ;
		$this->session->set("viewCalendar.Personal", $row["viewCalendarPersonal"]) ;
		$this->session->set("viewCalendar.SpaceBooking", $row["viewCalendarSpaceBooking"]) ;
		$this->session->set("dateStart", $row["dateStart"]) ;
		$this->session->set("personalBackground", $row["personalBackground"]) ;
		$this->session->set("messengerLastBubble", $row["messengerLastBubble"]) ;
		$this->session->set("gibbonThemeIDPersonal", $row["gibbonThemeIDPersonal"]) ;
		$this->session->set("theme.IDPersonal", $row["gibbonThemeIDPersonal"]) ;
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

		$this->getRecord('person')->recordLoginSuccess();

		//Set Goolge API refresh token where appropriate, and update user
		if (! empty($refreshToken)) {
			$this->session->set("googleAPIRefreshToken", $refreshToken) ;
			
			$this->getRecord('person')->setField("googleAPIRefreshToken", $this->session->get("googleAPIRefreshToken"));
			$this->writeRecord(array("googleAPIRefreshToken"));
		}
		if ($this->session->notEmpty("username")) {
			$URL = GIBBON_URL . 'index.php' ;
		}
		else {
			$this->session->clear('googleAPIAccessToken');
			$this->session->clear('gplusuer');
			$this->session->destroy();
			$this->session->start();
			$this->insertMessage(array('Gmail account does not match the email stored in %1$s. If you have logged in with your school Gmail account please contact %2$s if you have any questions.', array($this->session->get('systemName'), "<a href='mailto:".$this->session->get('organisationDBAEmail')."'>".$this->session->get('organisationDBAName').'</a>')));
			$this->redirect($URL);
		}
		if ($this->session->get("theme.IDPersonal") != $this->session->get("theme.ID") && $this->session->notEmpty("theme.IDPersonal"))
		{
			$tObj = new theme($this);
			$tObj->setDefaultTheme();
		} else {
			$tObj = new theme($this, $this->session->get("theme.ID"));
			$tObj->setDefaultTheme();
		}
		logger::__("Login - Success - Google API ", 'Info', 'Security', array("username"=>$username), $this->pdo) ;
		if ($this->session->notEmpty('calledPage')) {
			if ($this->session->get('calledPage') == "/publicRegistration.php") {
				$URL = GIBBON_URL."index.php";
			}
			else {
				$URL = GIBBON_URL."index.php?q=" . $this->session->get('calledPage') ;
			}
			$this->session->clear('calledPage');
		}
		else {
			$URL = GIBBON_URL."index.php";
		}	
		$this->redirect($URL);
	}






	//print user details
	
	if (isset($_GET['logout'])) {
		$URL = GIBBON_URL . 'index.php' ;
		$this->session->clear('googleAPIAccessToken');
		$this->session->clear('gplusuer');
		$this->session->destroy();  //Clear the Session
		$this->session->start();	// and start again . 
		$this->redirect($URL); // it will simply destroy the current seesion which you started before
		//NOTE: for logout and clear all the session direct google just uncomment the above line and comment the first header function
	}
}
