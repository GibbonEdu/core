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
use Gibbon\Forms\DatabaseFormFactory;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/externalAssessments_manage_edit_field_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Check if school year specified
    $gibbonExternalAssessmentFieldID = $_GET['gibbonExternalAssessmentFieldID'];
    $gibbonExternalAssessmentID = $_GET['gibbonExternalAssessmentID'];
    if ($gibbonExternalAssessmentFieldID == '' or $gibbonExternalAssessmentID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonExternalAssessmentID' => $gibbonExternalAssessmentID, 'gibbonExternalAssessmentFieldID' => $gibbonExternalAssessmentFieldID);
            $sql = 'SELECT gibbonExternalAssessmentField.*, gibbonExternalAssessment.name AS assName FROM gibbonExternalAssessment JOIN gibbonExternalAssessmentField ON (gibbonExternalAssessment.gibbonExternalAssessmentID=gibbonExternalAssessmentField.gibbonExternalAssessmentID) WHERE gibbonExternalAssessmentField.gibbonExternalAssessmentID=:gibbonExternalAssessmentID AND gibbonExternalAssessmentField.gibbonExternalAssessmentFieldID=:gibbonExternalAssessmentFieldID';
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
            $values['gibbonYearGroupIDList'] = explode(',', $values['gibbonYearGroupIDList']);

            echo "<div class='trail'>";
            echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/externalAssessments_manage.php'>".__($guid, 'Manage External Assessments')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/externalAssessments_manage_edit.php&gibbonExternalAssessmentID=$gibbonExternalAssessmentID'>".__($guid, 'Edit External Assessment')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Grade').'</div>';
            echo '</div>';

            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
            }

            $form = Form::create('externalAssessmentField', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/externalAssessments_manage_edit_field_editProcess.php?gibbonExternalAssessmentFieldID='.$gibbonExternalAssessmentFieldID.'&gibbonExternalAssessmentID='.$gibbonExternalAssessmentID);
            $form->setFactory(DatabaseFormFactory::create($pdo));

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);
            $form->addHiddenValue('gibbonExternalAssessmentID', $gibbonExternalAssessmentID);

            $row = $form->addRow();
                $row->addLabel('name', __('External Assessment'));
                $row->addTextField('name')->readonly();

            $row = $form->addRow();
                $row->addLabel('name', __('Name'));
                $row->addTextField('name')->isRequired()->maxLength(50);

            $row = $form->addRow();
                $row->addLabel('category', __('Category'));
                $row->addTextField('category')->isRequired()->maxLength(50);

            $row = $form->addRow();
                $row->addLabel('order', __('Order'))->description(__('Order in which fields appear within category<br/>Should be unique for this category.'));
                $row->addNumber('order')->isRequired()->maxLength(4);

            $sql = "SELECT gibbonScaleID as value, name FROM gibbonScale WHERE (active='Y') ORDER BY name";
            $row = $form->addRow();
                $row->addLabel('gibbonScaleID', __('Grade Scale'))->description(__('Grade scale used to control values that can be assigned.'));
                $row->addSelect('gibbonScaleID')->fromQuery($pdo, $sql)->isRequired()->placeholder();

            $row = $form->addRow();
                $row->addLabel('yearGroups', __('Year Groups'))->description(__('Year groups to which this field is relevant.'));
                $row->addCheckboxYearGroup('gibbonYearGroupIDList')->addCheckAllNone();

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            $form->loadAllValuesFrom($values);

            echo $form->getOutput();
        }
    }
}
