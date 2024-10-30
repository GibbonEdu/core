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

use Gibbon\Domain\System\ThemeGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

//Module includes
include './moduleFunctions.php';

$gibbonThemeID = $_POST['gibbonThemeID'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address']).'/theme_manage.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/theme_manage.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    // Proceed!
    // Check if theme specified
    if ($gibbonThemeID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        $themeGateway = $container->get(ThemeGateway::class);
        $theme = $themeGateway->getByID($gibbonThemeID);

        //Check for existence of theme
        if (empty($theme)) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
        } else {    
            // Deactivate theme current
            $themeGateway->updateWhere(['active' => 'Y'], ['active' => 'N']);
            
            // Activate selected theme
            $themeGateway->update($gibbonThemeID, ['active' => 'Y']);

            // Clear template cache, invalidate front-end cache
            $cachePath = $session->has('cachePath') ? $session->get('cachePath') : '/uploads/cache';
            removeDirectoryContents($session->get('absolutePath').$cachePath.'/templates', true);
            $container->get(SettingGateway::class)->updateSettingByScope('System', 'cacheString', $session->get('cacheString'));
           
            // Update the theme name in the session
            $session->set('gibbonThemeName', $theme['name']);


            $URL .= '&return=success0';
            header("Location: {$URL}");
        }
    }
}
