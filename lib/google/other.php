<?php

use Gibbon\Domain\User\RoleGateway;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\System\SettingGateway;

include "../../gibbon.php";

if (isset($_GET['error'])) {
    echo $_GET['error'];
    echo $_GET['error_description'];
    exit;
}

$oauthProvider = $container->get('Generic_Auth');

$ssoSettings = $container->get(SettingGateway::class)->getSettingByScope('System Admin', 'ssoOther');
$ssoSettings = json_decode($ssoSettings, true);

if (!isset($_GET['code'])) {

    // If we don't have an authorization code then get one
    $authUrl = $oauthProvider->getAuthorizationUrl();

    $_SESSION['oauth2stateGeneric'] = $oauthProvider->getState();

    $themeName = isset($_SESSION[$guid]['gibbonThemeName'])? $_SESSION[$guid]['gibbonThemeName'] : 'Default';
    echo '<a target=\'_top\' class="login block mb-4" href="' . $authUrl . '" onclick="addOAuth2LoginParams(this)">';
        echo '<button class="w-full bg-white rounded shadow border border-gray-400 flex items-center px-2 py-1 mb-2 text-gray-600 hover:shadow-md hover:border-blue-600 hover:text-blue-600">';
            echo '<img class="w-10 h-10" src="themes/'.$themeName.'/img/other-login.svg">';
            echo '<span class="flex-grow text-lg">'.__('Sign in with {service}', ['service' => $ssoSettings['clientName']]).'</span>';
        echo '</button>';
    echo '</a>';

// Check given state against previously stored one to mitigate CSRF attack
} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2stateGeneric'])) {

    unset($_SESSION['oauth2stateGeneric']);
    exit('Invalid state');

} else {

    // If available, load school year and language from state passed back from OAuth redirect
    if (isset($_GET['state']) && stripos($_GET['state'], ':') !== false) {
        list($gibbonSchoolYearID, $gibboni18nID, $state) = explode(':', $_GET['state']);
    }

    // Try to get an access token (using the authorization code grant)
    $token = $oauthProvider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
    ]);

    try {
        // We got an access token, let's now get the user's details
        $resourceOwner = $oauthProvider->getResourceOwner($token);

        $user = $resourceOwner->toArray();
        $email = $user['email'] ?? $user['emailAddress'] ?? $user['email-address'] ?? $user['email_address'];

    } catch (Exception $e) {
        // Failed to get user details
        echo $e->getMessage();
        exit('Oh dear...');
    }

    $gibbon->session->set('genericAPIAccessToken', $token->getToken());
    $refreshToken = $token->getRefreshToken();

    $userGateway = $container->get(UserGateway::class);
    $roleGateway = $container->get(RoleGateway::class);

    $userSelect = $userGateway->selectBy(['email' => $email, 'status' => 'Full']);

    if (empty($email) || $userSelect->rowCount() != 1) {
        setLog($connection2, $session->get('gibbonSchoolYearIDCurrent'), null, null, 'OAuth2 Login - Failed', array('username' => $email, 'reason' => 'No matching email found', 'email' => $email), $_SERVER['REMOTE_ADDR']);
        $session->forget('genericAPIAccessToken');
        session_destroy();
        $_SESSION[$guid] = NULL;
        $URL = "../../index.php?loginReturn=fail8";
        header("Location: {$URL}");
        exit;
    }

    $user = $userSelect->fetch();
    $role = $roleGateway->getByID($user['gibbonRoleIDPrimary']);

    if ($user['canLogin'] != 'Y' || empty($role) || (!empty($role['canLoginRole']) && $role['canLoginRole'] != 'Y')) {
        $session->forget('genericAPIAccessToken');
        @session_destroy();
        $URL = "../../index.php?loginReturn=fail2";
        header("Location: {$URL}");
        exit;
    }

    //USER EXISTS, SET SESSION VARIABLES
	$gibbon->session->createUserSession($user['username'], $user);

    $userGateway->update($user['gibbonPersonID'], [
        'lastIPAddress' => $_SERVER["REMOTE_ADDR"],
        'lastTimestamp' => date("Y-m-d H:i:s"),
    ]);

    if (!empty($refreshToken)) {
        $gibbon->session->set('genericAPIRefreshToken', $token->getRefreshToken());
        $userGateway->update($user['gibbonPersonID'], [
            'genericAPIRefreshToken' => $refreshToken,
        ]);
    }

    if ($session->has('username')) {
        setLog($connection2, $session->get('gibbonSchoolYearIDCurrent'), null, $user['gibbonPersonID'], 'OAuth2 Login - Success', ['username' => $user['username']], $_SERVER['REMOTE_ADDR']);
        $URL = "../../index.php";
        header("Location: {$URL}");
        exit;
    } else {
        setLog($connection2, $session->get('gibbonSchoolYearIDCurrent'), null, null, 'OAuth2 Login - Failed', array('username' => $user['username'], 'reason' => 'No matching email found', 'email' => $email), $_SERVER['REMOTE_ADDR']);
        $session->forget('genericAPIAccessToken');
        session_destroy();
        $_SESSION[$guid] = NULL;
        $URL = "../../index.php?loginReturn=fail8";
        header("Location: {$URL}");
        exit;
    }
}
