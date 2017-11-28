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

@session_start();

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/School Admin/activitySettings_type_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/activitySettings.php'>".__($guid, 'Manage Activity Settings')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit').'</div>';
    echo '</div>';

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/School Admin/activitySettings_type_edit.php&gibbonActivityTypeID='.$_GET['editID'];
    }
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    $gibbonActivityTypeID = isset($_GET['gibbonActivityTypeID'])? $_GET['gibbonActivityTypeID'] : '';
    
    try {
        $data = array('gibbonActivityTypeID' => $gibbonActivityTypeID);
        $sql = 'SELECT * FROM gibbonActivityType WHERE gibbonActivityTypeID=:gibbonActivityTypeID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo "<div class='error'>".$e->getMessage().'</div>';
    }

    if ($result->rowCount() != 1) {
        echo '<div class="error">';
        echo __('The selected record does not exist, or you do not have access to it.');
        echo '</div>';
    } else {
        $values = $result->fetch();
        
        $form = Form::create('activityType', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/activitySettings_type_editProcess.php?gibbonActivityTypeID='.$gibbonActivityTypeID);
        $form->addHiddenValue('address', $_SESSION[$guid]['address']);

        $row = $form->addRow();
            $row->addLabel('name', __('Name'));
            $row->addTextField('name')->isRequired()->maxLength(60)->readOnly();

        $row = $form->addRow();
            $row->addLabel('description', __('Description'));
            $row->addTextArea('description');

        $accessTypes = array('None' => __('None'), 'View' => __('View'), 'Register' => __('Register'));
        $row = $form->addRow();
            $row->addLabel('access', __('Access'))->description(__('System-wide access control'));
            $row->addSelect('access')->fromArray($accessTypes)->isRequired();

        $enrolmentTypes = array('Competitive' => __('Competitive'), 'Selection' => __('Selection'));
        $row = $form->addRow();
            $row->addLabel('enrolmentType', __('Enrolment Type'))->description(__('Enrolment process type'));
            $row->addSelect('enrolmentType')->fromArray($enrolmentTypes)->isRequired();

        $row = $form->addRow();
            $row->addLabel('maxPerStudent', __('Max per Student'))->description(__('The most a student can sign up for in this activity type. Set to 0 for unlimited.'));
            $row->addNumber('maxPerStudent')->minimum(0)->maximum(99);

        $row = $form->addRow();
            $row->addLabel('waitingList', __('Waiting List'))->description(__('Should students be placed on a waiting list if the enroled activity is full.'));
            $row->addYesNo('waitingList')->isRequired();

        $row = $form->addRow();
            $row->addLabel('backupChoice', __('Backup Choice'))->description(__('Allow students to choose a backup, in case enroled activity is full.'));
            $row->addYesNo('backupChoice')->isRequired();

        $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

        $form->loadAllValuesFrom($values);

        echo $form->getOutput();
    }
}