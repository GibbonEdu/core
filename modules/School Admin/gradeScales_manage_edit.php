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
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\School\GradeScaleGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/School Admin/gradeScales_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('Manage Grade Scales'), 'gradeScales_manage.php')
        ->add(__('Edit Grade Scale'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    $gibbonScaleID = (isset($_GET['gibbonScaleID']))? $_GET['gibbonScaleID'] : null;
    if (empty($gibbonScaleID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        
            $data = array('gibbonScaleID' => $gibbonScaleID);
            $sql = 'SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $values = $result->fetch();

            $form = Form::create('gradeScaleEdit', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/gradeScales_manage_editProcess.php?gibbonScaleID='.$gibbonScaleID);

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);
            $form->addHiddenValue('gibbonScaleID', $gibbonScaleID);

            $row = $form->addRow();
                $row->addLabel('name', __('Name'))->description(__('Must be unique.'));
                $row->addTextField('name')->required()->maxLength(40);

            $row = $form->addRow();
                $row->addLabel('nameShort', __('Short Name'))->description(__('Must be unique.'));
                $row->addTextField('nameShort')->required()->maxLength(5);

            $row = $form->addRow();
                $row->addLabel('usage', __('Usage'))->description(__('Brief description of how scale is used.'));
                $row->addTextField('usage')->required()->maxLength(50);

            $row = $form->addRow();
                $row->addLabel('active', __('Active'));
                $row->addYesNo('active')->required();

            $row = $form->addRow();
                $row->addLabel('numeric', __('Numeric'))->description(__('Does this scale use only numeric grades? Note, grade "Incomplete" is exempt.'));
                $row->addYesNo('numeric')->required();

            $data = array('gibbonScaleID' => $gibbonScaleID);
            $sql = "SELECT sequenceNumber as value, gibbonScaleGrade.value as name FROM gibbonScaleGrade WHERE gibbonScaleID=:gibbonScaleID ORDER BY sequenceNumber";

            $row = $form->addRow();
                $row->addLabel('lowestAcceptable', __('Lowest Acceptable'))->description(__('This is the lowest grade a student can get without being unsatisfactory.'));
                $row->addSelect('lowestAcceptable')->fromQuery($pdo, $sql, $data)->placeholder();

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            $form->loadAllValuesFrom($values);

            echo $form->getOutput();

            echo '<h2>';
            echo __('Edit Grades');
            echo '</h2>';

            $gradeScaleGateway = $container->get(GradeScaleGateway::class);

            // QUERY
            $criteria = $gradeScaleGateway->newQueryCriteria(true)
                ->sortBy('sequenceNumber')
                ->fromPOST();

            $grades = $gradeScaleGateway->queryGradeScaleGrades($criteria, $gibbonScaleID);

            // DATA TABLE
            $table = DataTable::createPaginated('gradeScaleManage', $criteria);

            $table->addHeaderAction('add', __('Add'))
                ->setURL('/modules/School Admin/gradeScales_manage_edit_grade_add.php')
                ->addParam('gibbonScaleID', $gibbonScaleID)
                ->displayLabel();

            $table->addColumn('value', __('Value'));
            $table->addColumn('descriptor', __('Descriptor'));
            $table->addColumn('sequenceNumber', __('Sequence Number'));
            $table->addColumn('isDefault', __('Is Default?'))->format(Format::using('yesNo', ['isDefault']));
                
            // ACTIONS
            $table->addActionColumn()
                ->addParam('gibbonScaleID')
                ->addParam('gibbonScaleGradeID')
                ->format(function ($grade, $actions) {
                    $actions->addAction('edit', __('Edit'))
                            ->setURL('/modules/School Admin/gradeScales_manage_edit_grade_edit.php');

                    $actions->addAction('delete', __('Delete'))
                            ->setURL('/modules/School Admin/gradeScales_manage_edit_grade_delete.php');
                });

            echo $table->render($grades);
        }
    }
}
