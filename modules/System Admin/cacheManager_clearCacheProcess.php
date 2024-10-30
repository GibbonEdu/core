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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);
include '../../config.php';

//Module includes
include './moduleFunctions.php';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/System Admin/cacheManager.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/cacheManager.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    $cachePath = $session->has('cachePath') ? $session->get('cachePath') : '/uploads/cache';

    // Clear the templates cache folder
    if (!empty($_POST['templateCache']) && $_POST['templateCache'] == 'Y') {
        removeDirectoryContents($session->get('absolutePath').$cachePath.'/templates');
    }

    // Clear the reports cache folder
    if (!empty($_POST['reportsCache']) && $_POST['reportsCache'] == 'Y') {
        removeDirectoryContents($session->get('absolutePath').$cachePath.'/reports');
    }

    // Update the cache invalidation string, for development
    if (!empty($_POST['frontEndCache']) && $_POST['frontEndCache'] == 'Y') {
        $session->set('cacheString', time());
        $container->get(SettingGateway::class)->updateSettingByScope('System', 'cacheString', $session->get('cacheString'));
    }

    $URL .= '&return=success0';
    header("Location: {$URL}");
    exit;
}
