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

$gibbonThemeID = $_GET['gibbonThemeID'] ?? '';
$orphaned = $_GET['orphaned'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address']).'/theme_manage_uninstall.php&gibbonThemeID='.$gibbonThemeID;
$URLDelete = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address']).'/theme_manage.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/theme_manage_uninstall.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if theme specified
    if ($gibbonThemeID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        $themeGateway = $container->get(ThemeGateway::class);
        //Check for existence of theme
        $dataTheme = array('gibbonThemeID' => $gibbonThemeID, 'active' => 'N');
        $existsTheme = $themeGateway->selectBy($dataTheme)->rowCount();

        if ($existsTheme == 0) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            //Delete to database
            $themeGateway->delete($gibbonThemeID);

            if ($orphaned != 'true') {
                $URLDelete = $URLDelete.'&return=warning0';
            } else {
                $URLDelete = $URLDelete.'&return=success2';
            }
            header("Location: {$URLDelete}");
        }
    }
}
