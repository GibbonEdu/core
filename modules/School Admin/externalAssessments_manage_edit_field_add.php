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
use Gibbon\Forms\DatabaseFormFactory;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/externalAssessments_manage_edit_field_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $gibbonExternalAssessmentID = $_GET['gibbonExternalAssessmentID'] ?? '';

    if ($gibbonExternalAssessmentID == '') {
        echo "<div class='error'>";
        echo __('You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonExternalAssessmentID' => $gibbonExternalAssessmentID);
            $sql = 'SELECT name as assessmentName FROM gibbonExternalAssessment WHERE gibbonExternalAssessmentID=:gibbonExternalAssessmentID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __('The specified record does not exist.');
            echo '</div>';
        } else {
            $values = $result->fetch();

            $page->breadcrumbs
                ->add(__('Manage External Assessments'), 'externalAssessments_manage.php')
                ->add(__('Edit External Assessment'), 'externalAssessments_manage_edit.php', ['gibbonExternalAssessmentID' => $gibbonExternalAssessmentID])
                ->add(__('Add Field'));

            $editLink = '';
            if (isset($_GET['editID'])) {
                $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/School Admin/externalAssessments_manage_edit_field_edit.php&gibbonExternalAssessmentFieldID='.$_GET['editID'].'&gibbonExternalAssessmentID='.$_GET['gibbonExternalAssessmentID'];
            }
            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], $editLink, null);
            }

            $form = Form::create('externalAssessmentField', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/externalAssessments_manage_edit_field_addProcess.php');
            $form->setFactory(DatabaseFormFactory::create($pdo));

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);
            $form->addHiddenValue('gibbonExternalAssessmentID', $gibbonExternalAssessmentID);

            $row = $form->addRow();
                $row->addLabel('assessmentName', __('External Assessment'));
                $row->addTextField('assessmentName')->readonly()->setValue($values['assessmentName']);

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

            echo $form->getOutput();
        }
    }
}
