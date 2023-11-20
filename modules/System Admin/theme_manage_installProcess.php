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
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

//Get URL from calling page, and set returning URL
$URL = $session->get('absoluteURL').'/index.php?q=/modules/System Admin/theme_manage.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/theme_manage_install.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $themeName = $_GET['name'] ?? '';

    if ($themeName == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        if (!(include $session->get('absolutePath')."/themes/$themeName/manifest.php")) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            //Validate Inputs
            if ($name == '' or $description == '' or $version == '') {
                $URL .= '&return=error1';
                header("Location: {$URL}");
            } else {
                $themeGateway = $container->get(ThemeGateway::class);
                //Check for existence of theme
                $data = array('name' => $name);
                $theme = $themeGateway->selectBy($data)->rowCount();

                if ($theme > 0) {
                    $URL .= '&return=error3';
                    header("Location: {$URL}");
                } else {
                    //Insert new theme row
                    $dataTheme = array('name' => $name, 'description' => $description, 'version' => $version, 'author' => $author, 'url' => $url);
                    $themeGateway->insert($dataTheme);

                    //Success 1
                    $URL .= '&return=success1';
                    header("Location: {$URL}");
                }
            }
        }
    }
}
