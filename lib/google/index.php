<?php

use Gibbon\Comms\NotificationEvent;

session_start();
include "../../functions.php";
include "../../config.php";

//New PDO DB connection
$pdo = new Gibbon\sqlConnection(false, '');
$connection2 = $pdo->getConnection();

setCurrentSchoolYear($guid, $connection2);

//The current/actual school year info, just in case we are working in a different year
$_SESSION[$guid]["gibbonSchoolYearIDCurrent"] = $_SESSION[$guid]["gibbonSchoolYearID"];
$_SESSION[$guid]["gibbonSchoolYearNameCurrent"] = $_SESSION[$guid]["gibbonSchoolYearName"];
$_SESSION[$guid]["gibbonSchoolYearSequenceNumberCurrent"] = $_SESSION[$guid]["gibbonSchoolYearSequenceNumber"];

$_SESSION[$guid]["pageLoads"] = NULL;

$URL = "index.php";

require_once ('google-api-php-client/vendor/autoload.php');

//Cleint ID and Secret
$client_id = getSettingByScope($connection2, "System", "googleClientID" );
$client_secret = getSettingByScope($connection2, "System", "googleClientSecret" );
$redirect_uri = getSettingByScope($connection2, "System", "googleRedirectUri" );


/************************************************
  Make an API request on behalf of a user. In
  this case we need to have a valid OAuth 2.0
  token for the user, so we need to send them
  through a login flow. To do this we need some
  information from our API console project.
 ************************************************/
$client = new Google_Client();
$client->setClientId($client_id);
$client->setClientSecret($client_secret);
$client->setRedirectUri($redirect_uri);
$client->setAccessType('offline');
$client->setScopes(array('https://www.googleapis.com/auth/userinfo.email',
    'https://www.googleapis.com/auth/plus.me',
    'https://www.googleapis.com/auth/calendar')); // set scope during user login

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
  $_SESSION[$guid]['googleAPIAccessToken']  = $client->getAccessToken();
  header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
  exit;
}

/************************************************
  If we have an access token, we can make
  requests, else we generate an authentication URL.
 ************************************************/
$refreshToken = isset($_SESSION[$guid]['googleAPIAccessToken']['refresh_token'])? $_SESSION[$guid]['googleAPIAccessToken']['refresh_token'] : '';

if (isset($_SESSION[$guid]['googleAPIAccessToken'] ) && $_SESSION[$guid]['googleAPIAccessToken'] ) {
  $client->setAccessToken($_SESSION[$guid]['googleAPIAccessToken'] );
} else {
  $authUrl = $client->createAuthUrl();
}


//Display user info or display login url as per the info we have.

if (isset($authUrl)){
	//show login url
	print '<div style="margin:20px">';
		print '<a target=\'_top\' class="login" href="' . $authUrl . '"><img style=\'width: 260px; height: 55px; margin: -20px 0 0 -24px\' src="themes/' . $_SESSION[$guid]["gibbonThemeName"] . '/img/g_login_btn.png" /></a>';
	print '</div>';
} else {
	$user = $service->userinfo->get(); //get user info
	$email = $user->email;
	$_SESSION[$guid]['gplusuer'] = $user;

	try {
		$data = array("email"=>$email);
		$sql = "SELECT * FROM gibbonPerson WHERE email=:email";
		$result = $connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) {}

	//Test to see if email exists in logintable
	if ($result->rowCount() != 1) {
        setLog($connection2, $_SESSION[$guid]['gibbonSchoolYearIDCurrent'], null, null, 'Google Login - Failed', array('username' => $email, 'reason' => 'No matching email found', 'email' => $email), $_SERVER['REMOTE_ADDR']);
        unset($_SESSION[$guid]['googleAPIAccessToken'] );
		unset($_SESSION[$guid]['gplusuer']);
 		session_destroy();
		$_SESSION[$guid] = NULL;
		$URL = "../../index.php?loginReturn=fail8";
		header("Location: {$URL}");
		exit;
	}
	//Start to collect User Info and test
	try {
		$data = array("email"=>$email);
		$sql = "SELECT * FROM gibbonPerson WHERE email=:email AND status='Full'";
		$result = $connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { }

	//Test to see if gmail matches email in gibbon
	if ($result->rowCount() != 1) {
		unset($_SESSION[$guid]['googleAPIAccessToken'] );
		unset($_SESSION[$guid]['gplusuer']);
		@session_destroy();
		$_SESSION[$guid] = NULL;
        $URL = "../../index.php?loginReturn=fail8";
        header("Location: {$URL}");
        exit;
	}
	else {
		$row = $result->fetch();

        // Insufficient privileges to login
        if ($row['canLogin'] != 'Y') {
            unset($_SESSION[$guid]['googleAPIAccessToken'] );
            unset($_SESSION[$guid]['gplusuer']);
            @session_destroy();
            $URL = "../../index.php?loginReturn=fail2";
            header("Location: {$URL}");
            exit;
        }

		$username = $row['username'];
		if ($row["failCount"] >= 3) {
			try {
				$data = array("lastFailIPAddress"=> $_SERVER["REMOTE_ADDR"], "lastFailTimestamp"=> date("Y-m-d H:i:s"), "failCount"=>($row["failCount"]+1), "username"=>$username);
				$sqlSecure = "UPDATE gibbonPerson SET lastFailIPAddress=:lastFailIPAddress, lastFailTimestamp=:lastFailTimestamp, failCount=:failCount WHERE username=:username";
				$resultSecure = $connection2->prepare($sqlSecure);
				$resultSecure->execute($data);
			}
			catch(PDOException $e) { }

			if ($row["failCount"] == 3) {
                // Raise a new notification event
                $event = new NotificationEvent('User Admin', 'Login - Failed');

                $event->addRecipient($_SESSION[$guid]['organisationAdministrator']);
                $event->setNotificationText(sprintf(__('Someone failed to login to account "%1$s" 3 times in a row.'), $username));
                $event->setActionLink('/index.php?q=/modules/User Admin/user_manage.php&search='.$username);

                $event->sendNotifications($pdo, $gibbon->session);
			}

            setLog($connection2, $_SESSION[$guid]['gibbonSchoolYearIDCurrent'], null, $row['gibbonPersonID'], 'Google Login - Failed', array('username' => $username, 'reason' => 'Too many failed logins'), $_SERVER['REMOTE_ADDR']);
            unset($_SESSION[$guid]['googleAPIAccessToken'] );
            unset($_SESSION[$guid]['gplusuer']);
            @session_destroy();
            $URL = "../../index.php?loginReturn=fail6";
			header("Location: {$URL}");
			exit;
		}

		if ($row["passwordForceReset"] == "Y") {
            // Sends the user to the password reset page after login
            $_SESSION[$guid]['passwordForceReset'] = 'Y';
		}


		if ($row["gibbonRoleIDPrimary"] == "" OR count(getRoleList($row["gibbonRoleIDAll"], $connection2)) == 0) {
			//FAILED TO SET ROLES
            setLog($connection2, $_SESSION[$guid]['gibbonSchoolYearIDCurrent'], null, $row['gibbonPersonID'], 'Google Login - Failed', array('username' => $username, 'reason' => 'Failed to set role(s)'), $_SERVER['REMOTE_ADDR']);
            unset($_SESSION[$guid]['googleAPIAccessToken'] );
            unset($_SESSION[$guid]['gplusuer']);
            @session_destroy();
            $URL = "../../index.php?loginReturn=fail2";
			header("Location: {$URL}");
			exit;
		}

		//USER EXISTS, SET SESSION VARIABLES
		$gibbon->session->createUserSession($username, $row);

		//If user has personal language set, load it to session variable.
		if (!is_null($_SESSION[$guid]["gibboni18nIDPersonal"])) {
			try {
				$dataLanguage = array("gibboni18nID"=>$_SESSION[$guid]["gibboni18nIDPersonal"]);
				$sqlLanguage = "SELECT * FROM gibboni18n WHERE active='Y' AND gibboni18nID=:gibboni18nID";
				$resultLanguage = $connection2->prepare($sqlLanguage);
				$resultLanguage->execute($dataLanguage);
			}
			catch(PDOException $e) { }
			if ($resultLanguage->rowCount() == 1) {
				$rowLanguage = $resultLanguage->fetch();
				setLanguageSession($guid, $rowLanguage);
			}
		}
		try {
			$data = array( "lastIPAddress"=> $_SERVER["REMOTE_ADDR"], "lastTimestamp"=> date("Y-m-d H:i:s"), "failCount"=>0, "username"=> $username );
			$sql = "UPDATE gibbonPerson SET lastIPAddress=:lastIPAddress, lastTimestamp=:lastTimestamp, failCount=:failCount WHERE username=:username";
			$result = $connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { }

		//Set Goolge API refresh token where appropriate, and update user
		if ($refreshToken != "") {
			$_SESSION[$guid]["googleAPIRefreshToken"] = $refreshToken;
			try {
				$data = array( "googleAPIRefreshToken"=> $_SESSION[$guid]["googleAPIRefreshToken"], "username"=> $username );
				$sql = "UPDATE gibbonPerson SET googleAPIRefreshToken=:googleAPIRefreshToken WHERE username=:username";
				$result = $connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { }
		}

        //The final reckoning...does email match?
		if (isset($_SESSION[$guid]["username"])) { //YES!
            setLog($connection2, $_SESSION[$guid]['gibbonSchoolYearIDCurrent'], null, $row['gibbonPersonID'], 'Google Login - Success', array('username' => $username), $_SERVER['REMOTE_ADDR']);
            $URL = "../../index.php";
    		header("Location: {$URL}");
    		exit;
		}
		else { //NO
            setLog($connection2, $_SESSION[$guid]['gibbonSchoolYearIDCurrent'], null, null, 'Google Login - Failed', array('username' => $username, 'reason' => 'No matching email found', 'email' => $email), $_SERVER['REMOTE_ADDR']);
            unset($_SESSION[$guid]['googleAPIAccessToken'] );
			unset($_SESSION[$guid]['gplusuer']);
			session_destroy();
			$_SESSION[$guid] = NULL;
            $URL = "../../index.php?loginReturn=fail8";
    		header("Location: {$URL}");
    		exit;
		}
	}


    if (isset($_GET['logout'])) {
      unset($_SESSION[$guid]['googleAPIAccessToken'] );
      unset($_SESSION[$guid]['gplusuer']);

      session_destroy();
      header('Location: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']); // it will simply destroy the current seesion which you started before
      exit;
    }
}
?>
