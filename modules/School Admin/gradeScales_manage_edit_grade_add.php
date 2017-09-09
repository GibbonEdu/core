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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/gradeScales_manage_edit_grade_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $gibbonScaleID = $_GET['gibbonScaleID'];

    if ($gibbonScaleID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonScaleID' => $gibbonScaleID);
            $sql = 'SELECT name FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The specified record does not exist.');
            echo '</div>';
        } else {
            $values = $result->fetch();

            echo "<div class='trail'>";
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/gradeScales_manage.php'>".__($guid, 'Manage Grade Scales')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/gradeScales_manage_edit.php&gibbonScaleID=$gibbonScaleID'>".__($guid, 'Edit Grade Scale')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Grade').'</div>';
            echo '</div>';

            $editLink = '';
            if (isset($_GET['editID'])) {
                $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/School Admin/gradeScales_manage_edit_grade_edit.php&gibbonScaleGradeID='.$_GET['editID']."&gibbonScaleID=$gibbonScaleID";
            }
            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], $editLink, null);
            }

            $form = Form::create('gradeScaleGradeAdd', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/gradeScales_manage_edit_grade_addProcess.php');

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);
            $form->addHiddenValue('gibbonScaleID', $gibbonScaleID);

            $row = $form->addRow();
                $row->addLabel('name', __('Grade Scale'));
                $row->addTextField('name')->readonly()->setValue($values['name']);

            $row = $form->addRow();
                $row->addLabel('value', __('Value'))->description(__('Must be unique for this grade scale.'));
                $row->addTextField('value')->isRequired()->maxLength(10);

            $row = $form->addRow();
                $row->addLabel('descriptor', __('Descriptor'));
                $row->addTextField('descriptor')->isRequired()->maxLength(50);

            $row = $form->addRow();
                $row->addLabel('sequenceNumber', __('Sequence Number'))->description(__('Must be unique for this grade scale.'));
                $row->addNumber('sequenceNumber')->isRequired()->maxLength(5);

            $row = $form->addRow();
                $row->addLabel('isDefault', __('Is Default?'))->description(__('Preselects this option when using this grade scale in appropriate contexts.'));
                $row->addYesNo('isDefault')->selected('N');

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();
        }
    }
}
