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

use Gibbon\Domain\System\I18nGateway;

include '../../gibbon.php';

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/System Admin/i18n_manage.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/i18n_manage.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $partialFail = false;

    $i18nGateway = $container->get(I18nGateway::class);
    $languages = $i18nGateway->queryI18n($i18nGateway->newQueryCriteria(), 'Y')->toArray();

    foreach ($languages as $i18n) {
        // Download & install the required language files
        $installed = i18nFileInstall($gibbon->session->get('absolutePath'), $i18n['code']);

        if ($installed) {
            // Tag this i18n with the current version it was installed at
            $updated = $i18nGateway->update($i18n['gibboni18nID'], ['version' => $version]);
        }

        $partialFail &= !$installed || !$updated;
    }
    
    $URL .= $partialFail
        ? '&return=warning1'
        : '&return=success0';
    header("Location: {$URL}");
    exit;
}
