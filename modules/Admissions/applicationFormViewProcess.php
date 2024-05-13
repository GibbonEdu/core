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
use Gibbon\Domain\Admissions\AdmissionsAccountGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonPersonID = $session->get('gibbonPersonID');

$URL = Url::fromModuleRoute('Admissions', 'applicationFormView');

if (isActionAccessible($guid, $connection2, '/modules/Admissions/applicationFormView.php') == false) {
    header("Location: {$URL->withReturn('error0')}");
    exit;
} else {
    // Proceed!
    $user = $container->get(UserGateway::class)->getByID($gibbonPersonID, ['email']);
    if (empty($gibbonPersonID) || empty($user['email'])) {
        header("Location: {$URL->withReturn('error1')}");
        exit;
    }

    // New account
    $admissionsAccountGateway = $container->get(AdmissionsAccountGateway::class);
    $accessID = $admissionsAccountGateway->getUniqueAccessID($guid.$user['email']);
    $accessToken = $admissionsAccountGateway->getUniqueAccessToken($guid.$accessID);
    $accessExpiry = date('Y-m-d H:i:s', strtotime("+2 days"));

    $created = $admissionsAccountGateway->insert([
        'email'                => $user['email'],
        'accessID'             => $accessID,
        'gibbonPersonID'       => $session->get('gibbonPersonID'),
        'gibbonFamilyID'       => $_POST['gibbonFamilyID'] ?? null,
        'accessToken'          => $accessToken,
        'timestampTokenExpire' => $accessExpiry,
        'timestampActive'      => date('Y-m-d H:i:s'),
        'ipAddress'            => $_SERVER['REMOTE_ADDR'] ?? '',
    ]);

    $session->get('admissionsAccessToken', $accessToken);

    header("Location: {$URL->withReturn(empty($created) ? 'error2' : 'success1')}");
}
