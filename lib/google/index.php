<?php

use Gibbon\Comms\NotificationEvent;

include "../../gibbon.php";

setCurrentSchoolYear($guid, $connection2);

//The current/actual school year info, just in case we are working in a different year
$session->set("gibbonSchoolYearIDCurrent", $session->get("gibbonSchoolYearID"));
$session->set("gibbonSchoolYearNameCurrent", $session->get("gibbonSchoolYearName"));
$session->set("gibbonSchoolYearSequenceNumberCurrent", $session->get("gibbonSchoolYearSequenceNumber"));

$session->set("pageLoads", NULL);

$URL = "index.php";

$redirect_uri = getSettingByScope($connection2, "System", "googleRedirectUri" );

/************************************************
  Make an API request on behalf of a user. In
  this case we need to have a valid OAuth 2.0
  token for the user, so we need to send them
  through a login flow. To do this we need some
  information from our API console project.
 ************************************************/
$client = $container->get('Google_Client');

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

if (isset($_GET['error'])) {
    header('Location: '.getSettingByScope($connection2, 'System', 'absoluteURL').'?loginReturn=fail7');
    exit;
}

if (isset($_GET['code'])) {
  $client->authenticate($_GET['code']);
  $session->set('googleAPIAccessToken', $client->getAccessToken());

  if (isset($_GET['state'])) {
    $redirect_uri .= '?state='.$_GET['state'];
  }

  header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
  exit;
}

/************************************************
  If we have an access token, we can make
  requests, else we generate an authentication URL.
 ************************************************/
$refreshToken = !empty($session->get('googleAPIAccessToken')['refresh_token'])? $session->get('googleAPIAccessToken')['refresh_token'] : '';

if ($session->has('googleAPIAccessToken') && $session->get('googleAPIAccessToken') ) {
  $client->setAccessToken($session->get('googleAPIAccessToken'));
} else {
  $authUrl = $client->createAuthUrl();
}


//Display user info or display login url as per the info we have.

if (isset($authUrl)){
	//show login url
    echo '<div>';
        $themeName = $session->has('gibbonThemeName')? $session->get('gibbonThemeName') : 'Default';
        echo '<a target=\'_top\' class="login" href="' . $authUrl . '" onclick="addGoogleLoginParams(this)">';
            echo '<button class="w-full bg-white rounded shadow border border-gray-400 flex items-center px-2 py-1 mb-2 text-gray-600 hover:shadow-md hover:border-blue-600 hover:text-blue-600">';
                echo '<img class="w-10 h-10" src="themes/'.$themeName.'/img/google-login.svg">';
                echo '<span class="flex-grow text-lg">'.__('Sign in with Google').'</span>';
            echo '</button>';
        echo '</a>';

        $form = \Gibbon\Forms\Form::create('loginFormGoogle', '#');
        $form->setFactory(\Gibbon\Forms\DatabaseFormFactory::create($pdo));
        $form->setClass('blank fullWidth loginTableGoogle');

        $loginIcon = '<img src="'.$session->get('absoluteURL').'/themes/'.$themeName.'/img/%1$s.png" style="width:20px;height:20px;margin:2px 15px 0 12px;" title="%2$s">';

        $row = $form->addRow()->setClass('loginOptionsGoogle');
            $row->addContent(sprintf($loginIcon, 'planner', __('School Year')));
            $row->addSelectSchoolYear('gibbonSchoolYearIDGoogle')
                ->setClass('fullWidth p-1')
                ->placeholder(null)
                ->selected($session->get('gibbonSchoolYearID'));

        $row = $form->addRow()->setClass('loginOptionsGoogle');
            $row->addContent(sprintf($loginIcon, 'language', __('Language')));
            $row->addSelectI18n('gibboni18nIDGoogle')
                ->setClass('fullWidth p-1')
                ->placeholder(null)
                ->selected($session->get('i18n')['gibboni18nID']);

        $row = $form->addRow();
            $row->addContent('<a class="showGoogleOptions" onclick="false" href="#">'.__('Options').'</a>')
                ->wrap('<span class="small">', '</span>')
                ->setClass('right');

        echo $form->getOutput();
        ?>

        <script>
        $(".loginOptionsGoogle").hide();
        $(".showGoogleOptions").click(function(){
            if ($('.loginOptionsGoogle').is(':hidden')) $(".loginTableGoogle").removeClass('blank').addClass('noIntBorder');
            $(".loginOptionsGoogle").fadeToggle(1000, function() {
                if ($('.loginOptionsGoogle').is(':hidden')) $(".loginTableGoogle").removeClass('noIntBorder').addClass('blank');
            });
        });

        function addGoogleLoginParams(element)
        {
            $(element).attr('href', function() {
                if ($('#gibbonSchoolYearIDGoogle').is(':visible')) {
                    var googleSchoolYear = $('#gibbonSchoolYearIDGoogle').val();
                    var googleLanguage = $('#gibboni18nIDGoogle').val();
                    return this.href.replace('&state&', '&state='+googleSchoolYear+':'+googleLanguage+'&');
                }
            });
        }
        </script>
        <?php
	echo '</div>';
} else {
	$user = $service->userinfo->get(); //get user info
	$email = $user->email;
	$session->set('gplusuer', $user);

	try {
		$data = array("email"=>$email);
		$sql = "SELECT * FROM gibbonPerson WHERE email=:email";
		$result = $connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) {}

    $gibbonSchoolYearID = $session->get('gibbonSchoolYearID');
    $gibboni18nID = $session->get('i18n')['gibboni18nID'];

    // If available, load school year and language from state passed back from OAuth redirect
    if (isset($_GET['state']) && stripos($_GET['state'], ':') !== false) {
        list($gibbonSchoolYearID, $gibboni18nID) = explode(':', $_GET['state']);
    }

	//Test to see if email exists in logintable
	if ($result->rowCount() != 1) {
        setLog($connection2, $session->get('gibbonSchoolYearIDCurrent'), null, null, 'Google Login - Failed', array('username' => $email, 'reason' => 'No matching email found', 'email' => $email), $_SERVER['REMOTE_ADDR']);
        $session->remove('googleAPIAccessToken');
		$session->remove('gplusuer');
 		session_destroy();
		$URL = "../../index.php?loginReturn=fail8";
		header("Location: {$URL}");
		exit;
	}
	//Start to collect User Info and test
	try {
		$data = array("email"=>$email);
		$sql = "SELECT gibbonPerson.*, futureYearsLogin, pastYearsLogin FROM gibbonPerson LEFT JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE email=:email AND status='Full'";
		$result = $connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { }

	//Test to see if gmail matches email in gibbon
	if ($result->rowCount() != 1) {
		$session->remove('googleAPIAccessToken');
		$session->remove('gplusuer');
		@session_destroy();
		$URL = "../../index.php?loginReturn=fail8";
        header("Location: {$URL}");
        exit;
	}
	else {
        $row = $result->fetch();

        // Get primary role info
        $data = array('gibbonRoleIDPrimary' => $row['gibbonRoleIDPrimary']);
        $sql = "SELECT * FROM gibbonRole WHERE gibbonRoleID=:gibbonRoleIDPrimary";
        $role = $pdo->selectOne($sql, $data);

        // Insufficient privileges to login
        if ($row['canLogin'] != 'Y' || (!empty($role['canLoginRole']) && $role['canLoginRole'] != 'Y')) {
            $session->remove('googleAPIAccessToken');
            $session->remove('gplusuer');
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

                $event->addRecipient($session->get('organisationAdministrator'));
                $event->setNotificationText(sprintf(__('Someone failed to login to account "%1$s" 3 times in a row.'), $username));
                $event->setActionLink('/index.php?q=/modules/User Admin/user_manage.php&search='.$username);

                $event->sendNotifications($pdo, $gibbon->session);
			}

            setLog($connection2, $session->get('gibbonSchoolYearIDCurrent'), null, $row['gibbonPersonID'], 'Google Login - Failed', array('username' => $username, 'reason' => 'Too many failed logins'), $_SERVER['REMOTE_ADDR']);
            $session->remove('googleAPIAccessToken');
            $session->get('gplusuer');
            @session_destroy();
            $URL = "../../index.php?loginReturn=fail6";
			header("Location: {$URL}");
			exit;
		}

		if ($row["passwordForceReset"] == "Y") {
            // Sends the user to the password reset page after login
            $session->set('passwordForceReset', 'Y');
		}


		if ($row["gibbonRoleIDPrimary"] == "" OR count(getRoleList($row["gibbonRoleIDAll"], $connection2)) == 0) {
			//FAILED TO SET ROLES
            setLog($connection2, $session->get('gibbonSchoolYearIDCurrent'), null, $row['gibbonPersonID'], 'Google Login - Failed', array('username' => $username, 'reason' => 'Failed to set role(s)'), $_SERVER['REMOTE_ADDR']);
            $session->remove('googleAPIAccessToken');
            $session->remove('gplusuer');
            @session_destroy();
            $URL = "../../index.php?loginReturn=fail2";
			header("Location: {$URL}");
			exit;
		} else {
            //Allow for non-current school years to be specified
            if ($gibbonSchoolYearID != $session->get('gibbonSchoolYearID')) {
                if ($row['futureYearsLogin'] != 'Y' and $row['pastYearsLogin'] != 'Y') { //NOT ALLOWED DUE TO CONTROLS ON ROLE, KICK OUT!
                    setLog($connection2, $session->get('gibbonSchoolYearIDCurrent'), null, $row['gibbonPersonID'], 'Login - Failed', array('username' => $username, 'reason' => 'Not permitted to access non-current school year'), $_SERVER['REMOTE_ADDR']);
                    $session->remove('googleAPIAccessToken');
                    $session->remove('gplusuer');
                    session_destroy();
                    $URL = "../../index.php?loginReturn=fail9";
                    header("Location: {$URL}");
                    exit;
                } else {
                    //Get details on requested school year
                    try {
                        $dataYear = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
                        $sqlYear = 'SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
                        $resultYear = $connection2->prepare($sqlYear);
                        $resultYear->execute($dataYear);
                    } catch (PDOException $e) {
                    }

                    //Check number of rows returned.
                    //If it is not 1, show error
                    if (!($resultYear->rowCount() == 1)) {
                        die(__('Configuration Error: there is a problem accessing the current Academic Year from the database.'));
                    }
                    //Else get year details
                    else {
                        $rowYear = $resultYear->fetch();
                        if ($row['futureYearsLogin'] != 'Y' and $session->get('gibbonSchoolYearSequenceNumber') < $rowYear['sequenceNumber']) { //POSSIBLY NOT ALLOWED DUE TO CONTROLS ON ROLE, CHECK YEAR
                            setLog($connection2, $session->get('gibbonSchoolYearIDCurrent'), null, $row['gibbonPersonID'], 'Login - Failed', array('username' => $username, 'reason' => 'Not permitted to access non-current school year'), $_SERVER['REMOTE_ADDR']);
                            $session->remove('googleAPIAccessToken');
                            $session->get('gplusuer');
                            session_destroy();
                            $URL = "../../index.php?loginReturn=fail9";
                            header("Location: {$URL}");
                            exit;
                        } elseif ($row['pastYearsLogin'] != 'Y' and $session->get('gibbonSchoolYearSequenceNumber') > $rowYear['sequenceNumber']) { //POSSIBLY NOT ALLOWED DUE TO CONTROLS ON ROLE, CHECK YEAR
                            setLog($connection2, $session->get('gibbonSchoolYearIDCurrent'), null, $row['gibbonPersonID'], 'Login - Failed', array('username' => $username, 'reason' => 'Not permitted to access non-current school year'), $_SERVER['REMOTE_ADDR']);
                            $session->remove('googleAPIAccessToken');
                            $session->remove('gplusuer');
                            session_destroy();
                            $URL = "../../index.php?loginReturn=fail9";
                            header("Location: {$URL}");
                            exit;
                        } else { //ALLOWED
                            $session->set('gibbonSchoolYearID', $rowYear['gibbonSchoolYearID']);
                            $session->set('gibbonSchoolYearName', $rowYear['name']);
                            $session->set('gibbonSchoolYearSequenceNumber', $rowYear['sequenceNumber']);
                        }
                    }
                }
            }
        }

		//USER EXISTS, SET SESSION VARIABLES
		$gibbon->session->createUserSession($username, $row);

        // If user has personal language set, load it
        if ($session->has('gibboni18nIDPersonal') && $gibboni18nID == $session->get('i18n')['gibboni18nID']) {
            $gibboni18nID = $session->get('gibboni18nIDPersonal');
        }

        // Allow for non-system default language to be specified (from login form or personal)
        if (!empty($gibboni18nID)) {
            try {
                $dataLanguage = array('gibboni18nID' => $gibboni18nID);
                $sqlLanguage = 'SELECT * FROM gibboni18n WHERE gibboni18nID=:gibboni18nID';
                $resultLanguage = $connection2->prepare($sqlLanguage);
                $resultLanguage->execute($dataLanguage);
            } catch (PDOException $e) {
            }
            if ($resultLanguage->rowCount() == 1) {
                $rowLanguage = $resultLanguage->fetch();
                setLanguageSession($guid, $rowLanguage, false);
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
		if (!empty($refreshToken)) {
			$session->set("googleAPIRefreshToken", $refreshToken);
			try {
				$data = array( "googleAPIRefreshToken"=> $session->get("googleAPIRefreshToken"), "username"=> $username );
				$sql = "UPDATE gibbonPerson SET googleAPIRefreshToken=:googleAPIRefreshToken WHERE username=:username";
				$result = $connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { }
		} else {
            // No refresh token and none saved in gibbonPerson: force a re-authorization of this account
            if (empty($row['googleAPIRefreshToken'])) {
                $client->setApprovalPrompt('force');
                $authUrl = $client->createAuthUrl();
                header('Location: ' . $authUrl);
                exit;
            }
        }

        //The final reckoning...does email match?
		if ($session->has("username")) { //YES!
            setLog($connection2, $session->get('gibbonSchoolYearIDCurrent'), null, $row['gibbonPersonID'], 'Google Login - Success', array('username' => $username), $_SERVER['REMOTE_ADDR']);
            $URL = "../../index.php";
    		header("Location: {$URL}");
    		exit;
		}
		else { //NO
            setLog($connection2, $session->get('gibbonSchoolYearIDCurrent'), null, null, 'Google Login - Failed', array('username' => $username, 'reason' => 'No matching email found', 'email' => $email), $_SERVER['REMOTE_ADDR']);
            $session->remove('googleAPIAccessToken');
			$session->remove('gplusuer');
			session_destroy();
			$URL = "../../index.php?loginReturn=fail8";
    		header("Location: {$URL}");
    		exit;
		}
	}


    if (isset($_GET['logout'])) {
     $session->remove('googleAPIAccessToken');
     $session->remove('gplusuer');

      session_destroy();
      header('Location: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']); // it will simply destroy the current seesion which you started before
      exit;
    }
}
?>
