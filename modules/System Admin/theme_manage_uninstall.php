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

use Gibbon\Forms\Prefab\DeleteForm;

$orphaned = '';
if (isset($_GET['orphaned'])) {
    if ($_GET['orphaned'] == 'true') {
        $orphaned = 'true';
    }
}

if (isActionAccessible($guid, $connection2, '/modules/System Admin/theme_manage_uninstall.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('Manage Themes'), 'theme_manage.php')
        ->add(__('Uninstall Theme'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    $gibbonThemeID = $_GET['gibbonThemeID'];
    if ($gibbonThemeID == '') {
        echo "<div class='error'>";
        echo __('You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonThemeID' => $gibbonThemeID);
            $sql = "SELECT * FROM gibbonTheme WHERE gibbonThemeID=:gibbonThemeID AND active='N'";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo 'The specified theme cannot be found or is active and so cannot be removed.';
            echo '</div>';
        } else {
            $form = DeleteForm::createForm($_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/theme_manage_uninstallProcess.php?gibbonThemeID=$gibbonThemeID&orphaned=$orphaned");
            echo $form->getOutput();
        }
    }
}
