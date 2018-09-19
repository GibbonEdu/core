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

$gibboni18nID = $_POST['gibboni18nID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/System Admin/i18n_manage.php';

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
        $i18n = $i18nGateway->getI18nByID($gibboni18nID);

        if (empty($i18n)) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
            exit;
        }

        // Grab the file contents from GitHub repo
        $gitHubURL = 'https://github.com/GibbonEdu/core/blob/master/i18n/'.$i18n['code'].'/LC_MESSAGES/gibbon.mo?raw=true';
        $gitHubContents = file_get_contents($gitHubURL);

        if (empty($gitHubContents)) {
            $URL .= '&return=error3';
            header("Location: {$URL}");
            exit;
        }

        // Locate where the i18n files will be copied to on the server
        $localPath = $_SESSION[$guid]['absolutePath'].'/i18n/'.$i18n['code'].'/LC_MESSAGES/gibbon.mo';
        $localDir = dirname($localPath);
        if (!is_dir($localDir)) {
            mkdir($localDir, 0755, true);
        }

        // Copy files
        $bytesWritten = file_put_contents($localPath, $gitHubContents);

        if ($bytesWritten === false) {
            $URL .= '&return=error3';
            header("Location: {$URL}");
            exit;
        } else {
            $URL .= '&return=success0';
            header("Location: {$URL}");
            exit;
        }
    }
}
