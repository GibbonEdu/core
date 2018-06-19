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

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Finance/budgets_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/budgets_manage.php'>".__($guid, 'Manage Budgets')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Budget').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, array('error3' => 'Your request failed because some inputs did not meet a requirement for uniqueness.', 'error4' => 'Your request failed due to an attachment error.'));
    }

    //Check if school year specified
    $gibbonFinanceBudgetID = $_GET['gibbonFinanceBudgetID'];
    if ($gibbonFinanceBudgetID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonFinanceBudgetID' => $gibbonFinanceBudgetID);
            $sql = 'SELECT * FROM gibbonFinanceBudget WHERE gibbonFinanceBudgetID=:gibbonFinanceBudgetID';
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
            //Let's go!
            $values = $result->fetch();

            $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/budgets_manage_editProcess.php?gibbonFinanceBudgetID=$gibbonFinanceBudgetID");

            $form->setFactory(DatabaseFormFactory::create($pdo));
            $form->setClass('smallIntBorder fullWidth');

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);

            $form->addRow()->addHeading(__('General Settings'));

            $row = $form->addRow();
                $row->addLabel('name', __('Name'))->description(__('Must be unique.'));
                $row->addTextField('name')->maxLength(100)->isRequired();

            $row = $form->addRow();
                $row->addLabel('nameShort', __('Short Name'))->description(__('Must be unique.'));
                $row->addTextField('nameShort')->maxLength(14)->isRequired();

            $row = $form->addRow();
                $row->addLabel('active', __('Active'));
                $row->addYesNo('active')->isRequired();

            $categories = getSettingByScope($connection2, 'Finance', 'budgetCategories');
            if (empty($categories)) {
                $categories = 'Other';
            }
            $row = $form->addRow();
                $row->addLabel('category', __('Category'));
                $row->addSelect('category')->fromString($categories)->placeholder()->isRequired();

            $form->addRow()->addHeading(__('Current Staff'));

            $data = array('gibbonFinanceBudgetID' => $gibbonFinanceBudgetID);
            $sql = "SELECT preferredName, surname, gibbonFinanceBudgetPerson.* FROM gibbonFinanceBudgetPerson JOIN gibbonPerson ON (gibbonFinanceBudgetPerson.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFinanceBudgetID=:gibbonFinanceBudgetID AND gibbonPerson.status='Full' ORDER BY FIELD(access,'Full','Write','Read'), surname, preferredName";

            $results = $pdo->executeQuery($data, $sql);

            if ($results->rowCount() == 0) {
                $form->addRow()->addAlert(__('There are no records to display.'), 'error');
            } else {
                $form->addRow()->addContent('<b>'.__('Warning').'</b>: '.__('If you delete a member of staff, any unsaved changes to this record will be lost!'))->wrap('<i>', '</i>');

                $table = $form->addRow()->addTable()->addClass('colorOddEven');

                $header = $table->addHeaderRow();
                $header->addContent(__('Name'));
                $header->addContent(__('Access'));
                $header->addContent(__('Action'));

                while ($staff = $results->fetch()) {
                    $row = $table->addRow();
                    $row->addContent(formatName('', $staff['preferredName'], $staff['surname'], 'Staff', true, true));
                    $row->addContent($staff['access']);
                    $row->addContent("<a onclick='return confirm(\"".__($guid, 'Are you sure you wish to delete this record?')."\")' href='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/budgets_manage_edit_staff_deleteProcess.php?address='.$_GET['q'].'&gibbonFinanceBudgetPersonID='.$staff['gibbonFinanceBudgetPersonID']."&gibbonFinanceBudgetID=$gibbonFinanceBudgetID'><img title='".__($guid, 'Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>");
                }
            }

            $form->addRow()->addHeading(__('New Staff'));

            $row = $form->addRow();
                $row->addLabel('staff', __('Staff'));
                $row->addSelectStaff('staff')->selectMultiple();

            $access = array(
                "Full" => __("Full"),
                "Write" => __("Write"),
                "Read" => __("Read")
            );
            $row = $form->addRow();
                $row->addLabel('access', 'Access');
                $row->addSelect('access')->fromArray($access);

            $form->loadAllValuesFrom($values);

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();
        }
    }
}
?>
