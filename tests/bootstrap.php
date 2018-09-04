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

global $gibbon, $guid, $connection2;

// Prevent installer redirect
if (!file_exists(__DIR__ . '/../config.php')) {
    $_SERVER['PHP_SELF'] = 'installer/install.php';
}

require_once __DIR__ . '/../gibbon.php';

if ($gibbon->isInstalled()) {
    $installType = getSettingByScope($connection2, 'System', 'installType');
    if ($installType == 'Production') {
        die('ERROR: Test suite cannot run on a production system.'."\n");
    }
}