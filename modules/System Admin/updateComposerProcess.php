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

use Gibbon\Database\Updater;
use Gibbon\Domain\System\SettingGateway;

$_POST['address'] = '/index.php?q=/modules/System Admin/update.php';

include '../../gibbon.php';

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/System Admin/update.php';
$partialFail = false;

if (isActionAccessible($guid, $connection2, '/modules/System Admin/update.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    // Proceed!
    $updater = $container->get(Updater::class);
    $settingGateway = $container->get(SettingGateway::class);
    
    if (!$updater->isComposerUpdateRequired()) {
        $URL .= '&return=error3';
        header("Location: {$URL}");
        exit;
    }

    $newComposerHash = $updater->getComposerHash();
    $updated = $settingGateway->updateSettingByScope('System Admin', 'composerLockHash', $newComposerHash);

    $URL .= !$updated
        ? '&return=error2'
        : '&return=success0';
    header("Location: {$URL}");
}
