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

include '../../gibbon.php';
include '../../config.php';

//Module includes
include './moduleFunctions.php';

$URL = $gibbon->session->get('absoluteURL').'/index.php?q=/modules/System Admin/cacheManager.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/cacheManager.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    $cachePath = $gibbon->session->has('cachePath') ? $gibbon->session->get('cachePath') : '/uploads/cache';

    // Clear the templates cache folder
    if (!empty($_POST['templateCache']) && $_POST['templateCache'] == 'Y') {
        removeDirectoryContents($gibbon->session->get('absolutePath').$cachePath.'/templates');
    }

    if (!empty($_POST['reportsCache']) && $_POST['reportsCache'] == 'Y') {
        removeDirectoryContents($gibbon->session->get('absolutePath').$cachePath.'/reports');
    }

    $URL .= '&return=success0';
    header("Location: {$URL}");
    exit;
}
