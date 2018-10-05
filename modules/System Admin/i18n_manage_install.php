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

use Gibbon\Forms\Form;
use Gibbon\Domain\System\I18nGateway;

if (isActionAccessible($guid, $connection2, '/modules/System Admin/i18n_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    $gibboni18nID = isset($_GET['gibboni18nID'])? $_GET['gibboni18nID'] : '';
    $mode = isset($_GET['mode'])? $_GET['mode'] : 'install';

    if (empty($gibboni18nID)) {
        echo "<div class='error'>";
        echo __('You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        
        $i18nGateway = $container->get(I18nGateway::class);

        $i18n = $i18nGateway->getI18nByID($gibboni18nID);

        if (empty($i18n)) {
            echo "<div class='error'>";
            echo __('The specified record cannot be found.');
            echo '</div>';
        } else {

            $form = Form::create('install', $_SESSION[$guid]['absoluteURL'].'/modules/System Admin/i18n_manage_installProcess.php');
            $form->addHiddenValue('address', $_GET['q']);
            $form->addHiddenValue('gibboni18nID', $gibboni18nID);

            $row = $form->addRow();
                $col = $row->addColumn();
                $col->addContent( ($mode == 'update'? __('Update') : __('Install')).' '.$i18n['name'])->wrap('<strong style="font-size: 18px;">', '</strong><br/><br/>');
                $col->addContent(sprintf(__('This action will download the required files and place them in the %1$s folder on your server.'), '<b>'.$_SESSION[$guid]['absolutePath'].'/i18n/'.'</b>').' '.__('Are you sure you want to continue?'));

            $form->addRow()->addConfirmSubmit();

            echo $form->getOutput();
        }
    }
}
