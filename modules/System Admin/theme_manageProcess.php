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

use Gibbon\Domain\System\ThemeGateway;

include '../../gibbon.php';

$gibbonThemeID = $_POST['gibbonThemeID'] ?? '';
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/theme_manage.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/theme_manage.php') == false) {
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
        if (!$themeGateway->exists($gibbonThemeID)) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
        } else {    
            //Deactivate theme current
            $themeGateway->updateWhere(array('active' => 'Y'), array('active' => 'N'));
            
            //Activate selected theme
            $themeGateway->update($gibbonThemeID, array('active' => 'Y'));
           
            $URL .= '&return=success0';
            header("Location: {$URL}");
        }
    }
}
