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

use Gibbon\Http\Url;
use Gibbon\Domain\Admissions\AdmissionsAccountGateway;

require_once '../../gibbon.php';

$gibbonFormID = $_POST['gibbonFormID'] ?? '';
$email = $_POST['admissionsLoginEmail'] ?? '';

$URL = Url::fromModuleRoute('Admissions', 'applicationSelect');

if (empty($email)) {
    header("Location: {$URL->withReturn('error0')}");
    exit;
} else {
    // Proceed!
    if (empty($gibbonFormID)) {
        header("Location: {$URL->withReturn('error1')}");
        exit;
    }

    $admissionsAccountGateway = $container->get(AdmissionsAccountGateway::class);

    $account = $admissionsAccountGateway->getAccountByEmail($email);

    if (empty($account)) {
        // New account
        $accessID = $admissionsAccountGateway->getUniqueAccessID($guid.$email);
        $gibbonAdmissionsAccountID = $admissionsAccountGateway->insert(['email' => $email, 'accessID' => $accessID]);
        $accountType = 'new';
    } else {   
        // Existing account
        $accessID = $account['accessID'];
        $gibbonAdmissionsAccountID = $account['gibbonAdmissionsAccountID'];
        $accountType = 'existing';
    }

    if (empty($gibbonAdmissionsAccountID) || empty($accessID)) {
        header("Location: {$URL->withReturn('error2')}");
        exit;
    }

    if ($gibbonFormID == 'existing' && $accountType == 'existing') {
        // Handle loading existing forms
    } else {
        $URL = Url::fromModuleRoute('Admissions', 'applicationForm')->withQueryParams([
            'gibbonFormID' => $gibbonFormID,
            'accessID'     => $accessID,
            'accountType'  => $accountType,
        ]);
    }

    header("Location: {$URL}");
}
