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

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/School Admin/gradeScales_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/gradeScales_manage.php'>".__($guid, 'Manage Grade Scales')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Grade Scale').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    $gibbonScaleID = (isset($_GET['gibbonScaleID']))? $_GET['gibbonScaleID'] : null;
    if (empty($gibbonScaleID)) {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonScaleID' => $gibbonScaleID);
            $sql = 'SELECT * FROM gibbonScale WHERE gibbonScaleID=:gibbonScaleID';
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

            $form = Form::create('gradeScaleEdit', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/gradeScales_manage_editProcess.php?gibbonScaleID='.$gibbonScaleID);

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);
            $form->addHiddenValue('gibbonScaleID', $gibbonScaleID);

            $row = $form->addRow();
                $row->addLabel('name', __('Name'))->description(__('Must be unique.'));
                $row->addTextField('name')->isRequired()->maxLength(40);

            $row = $form->addRow();
                $row->addLabel('nameShort', __('Short Name'))->description(__('Must be unique.'));
                $row->addTextField('nameShort')->isRequired()->maxLength(5);

            $row = $form->addRow();
                $row->addLabel('usage', __('Usage'))->description(__('Brief description of how scale is used.'));
                $row->addTextField('usage')->isRequired()->maxLength(50);

            $row = $form->addRow();
                $row->addLabel('active', __('Active'));
                $row->addYesNo('active');

            $row = $form->addRow();
                $row->addLabel('numeric', __('Numeric'))->description(__('Does this scale use only numeric grades? Note, grade "Incomplete" is exempt.'));
                $row->addYesNo('numeric');

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
            echo __($guid, 'Edit Grades');
            echo '</h2>';

            try {
                $data = array('gibbonScaleID' => $gibbonScaleID);
                $sql = 'SELECT * FROM gibbonScaleGrade WHERE gibbonScaleID=:gibbonScaleID ORDER BY sequenceNumber';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            echo "<div class='linkTop'>";
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/gradeScales_manage_edit_grade_add.php&gibbonScaleID=$gibbonScaleID'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a>";
            echo '</div>';

            if ($result->rowCount() < 1) {
                echo "<div class='error'>";
                echo __($guid, 'There are no records to display.');
                echo '</div>';
            } else {
                echo "<table cellspacing='0' style='width: 100%'>";
                echo "<tr class='head'>";
                echo '<th>';
                echo __($guid, 'Value');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Descriptor');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Sequence Number');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Is Default?');
                echo '</th>';
                echo '<th>';
                echo __($guid, 'Actions');
                echo '</th>';
                echo '</tr>';

                $count = 0;
                $rowNum = 'odd';
                while ($row = $result->fetch()) {
                    if ($count % 2 == 0) {
                        $rowNum = 'even';
                    } else {
                        $rowNum = 'odd';
                    }

                    //COLOR ROW BY STATUS!
                    echo "<tr class=$rowNum>";
                    echo '<td>';
                    echo __($guid, $row['value']);
                    echo '</td>';
                    echo '<td>';
                    echo __($guid, $row['descriptor']);
                    echo '</td>';
                    echo '<td>';
                    echo $row['sequenceNumber'];
                    echo '</td>';
                    echo '<td>';
                    echo ynExpander($guid, $row['isDefault']);
                    echo '</td>';
                    echo '<td>';
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/gradeScales_manage_edit_grade_edit.php&gibbonScaleGradeID='.$row['gibbonScaleGradeID']."&gibbonScaleID=$gibbonScaleID'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                    echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/gradeScales_manage_edit_grade_delete.php&gibbonScaleGradeID='.$row['gibbonScaleGradeID']."&gibbonScaleID=$gibbonScaleID'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a> ";
                    echo '</td>';
                    echo '</tr>';

                    ++$count;
                }
                echo '</table>';
            }
        }
    }
}
?>
