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

use Gibbon\Domain\System\I18nGateway;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

$gibboni18nID = $_POST['gibboni18nID'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/System Admin/i18n_manage.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/i18n_manage.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    //Proceed!
    if (empty($gibboni18nID)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    } else {
        $i18nGateway = $container->get(I18nGateway::class);
        $i18n = $i18nGateway->getByID($gibboni18nID);

        // Check for a valid language
        if (empty($i18n)) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
            exit;
        }

        // Download & install the required language files
        $installed = i18nFileInstall($session->get('absolutePath'), $i18n['code']);

        // Tag this i18n with the current version it was installed at
        $dataUpdate = ['installed' => 'Y', 'version' => $version];
        $updated = $i18nGateway->update($gibboni18nID, $dataUpdate);

        if (!$installed) {
            $URL .= '&return=error3';
            header("Location: {$URL}");
            exit;
        } else if (!$updated) {
            $URL .= '&return=warning1';
            header("Location: {$URL}");
            exit;
        } else {
            $URL .= '&return=success0';
            header("Location: {$URL}");
            exit;
        }
    }
}
