<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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

use Gibbon\Http\Url;
use Gibbon\Data\Validator;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\User\RoleGateway;
use Gibbon\Domain\System\ThemeGateway;
use Gibbon\Domain\System\I18nGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$URL = Url::fromModuleRoute('System Admin', 'impersonateUser');

if (isActionAccessible($guid, $connection2, '/modules/System Admin/impersonateUser.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $userGateway = $container->get(UserGateway::class);
    $roleGateway = $container->get(RoleGateway::class);

    // Validate the current user and that the session data is correct
    $currentUser = $userGateway->getByID($session->get('gibbonPersonID'), ['gibbonRoleIDPrimary']);
    if (empty($currentUser) || $currentUser['gibbonRoleIDPrimary'] != $session->get('gibbonRoleIDCurrent')) {
        header("Location: {$URL->withReturn('error0')}");
        exit;
    }

    // Check that the current user had Administrator access
    $primaryRole = $roleGateway->selectBy(['gibbonRoleID' => $session->get('gibbonRoleIDPrimary')], ['name', 'gibbonRoleID'])->fetch();
    if (empty($primaryRole) || $primaryRole['name'] != 'Administrator' || $primaryRole['gibbonRoleID'] != '001') {
        header("Location: {$URL->withReturn('error0')}");
        exit;
    }

    $gibbonPersonIDAccountSwitch = $_POST['gibbonPersonIDAccountSwitch'] ?? '';
    $userData = $userGateway->getByID($gibbonPersonIDAccountSwitch);

    // Validate that this user exists
    if (empty($gibbonPersonIDAccountSwitch) || empty($userData)) {
        header("Location: {$URL->withReturn('error2')}");
        exit;
    }

    // Get user details to be loaded into the session
    $user = $userGateway->getSafeUserData($gibbonPersonIDAccountSwitch);

    // Setup essential role information
    $primaryRole = $roleGateway->getByID($userData['gibbonRoleIDPrimary']);
    $user['gibbonRoleIDPrimary'] = $primaryRole['gibbonRoleID'];
    $user['gibbonRoleIDCurrent'] = $primaryRole['gibbonRoleID'];
    $user['gibbonRoleIDCurrentCategory'] = $primaryRole['category'] ?? '';
    $user['gibbonRoleIDAll'] = $roleGateway->selectRoleListByIDs($userData['gibbonRoleIDAll'])->fetchAll();

    // Load user data into the session
    $session->set($user);

    // Clear cached FF actions and main menu
    $session->forget('fastFinderActions');
    $session->forget(['menuMainItems', 'menuModuleItems', 'menuModuleName', 'menuItemActive']);

    // Update user personal theme
    if (!empty($userData['gibbonThemeIDPersonal'])) {
        if ($container->get(ThemeGateway::class)->exists($userData['gibbonThemeIDPersonal'])) {
            $session->set('gibbonThemeIDPersonal', $userData['gibbonThemeIDPersonal']);
        }
    }

    // Update user language using personal language choice
    if (!empty($userData['gibboni18nIDPersonal'])) {
        if ($i18n = $container->get(I18nGateway::class)->getByID($userData['gibboni18nIDPersonal'])) {
            $session->set('i18n', $i18n);
        }
    }

    $URL = Url::fromHandlerRoute('index.php');
    header("Location: {$URL->withReturn('success0')}");
}
