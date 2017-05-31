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

@session_start();

use Gibbon\Forms\Form;
use Gibbon\FileUploader;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/house_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/house_manage.php'>".__($guid, 'Manage Houses')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit House').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    $gibbonHouseID = $_GET['gibbonHouseID'];
    if ($gibbonHouseID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonHouseID' => $gibbonHouseID);
            $sql = 'SELECT * FROM gibbonHouse WHERE gibbonHouseID=:gibbonHouseID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The specified record cannot be found.');
            echo '</div>';
        } else {
            //Let's go!
            $values = $result->fetch();

            $form = Form::create('houses', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/house_manage_editProcess.php?gibbonHouseID='.$gibbonHouseID);

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);
            $form->addHiddenValue('attachment1', $values['logo']);

            $row = $form->addRow();
                $row->addLabel('name', __('Name'))->description(__('Must be unique.'));
                $row->addTextField('name')->isRequired()->maxLength(10)->setValue($values['name']);

            $row = $form->addRow();
                $row->addLabel('nameShort', __('Short Name'))->description(__('Must be unique.'));
                $row->addTextField('nameShort')->isRequired()->maxLength(4)->setValue($values['nameShort']);

            $fileUploader = new FileUploader($pdo, $gibbon->session);

            $row = $form->addRow();
                $row->addLabel('file1', __('Logo'));
                $file = $row->addFileUpload('file1')
                    ->addClass('right')
                    ->accepts($fileUploader->getFileExtensions('Graphics/Design'))
                    ->setAttachment('logo', $_SESSION[$guid]['absoluteURL'], $values['logo'])
                    ->setDeleteAction('/modules/School Admin/house_manage_edit_photoDeleteProcess.php?gibbonHouseID='.$gibbonHouseID);

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();
        }
    }
}
?>
