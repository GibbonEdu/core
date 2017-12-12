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

@session_start();

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Finance/budgets_manage_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/budgets_manage.php'>".__($guid, 'Manage Budgets')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Budget').'</div>';
    echo '</div>';

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Finance/budgets_manage_edit.php&gibbonFinanceBudgetID='.$_GET['editID'];
    }
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], $editLink, array('error3' => 'Your request failed because some inputs did not meet a requirement for uniqueness.', 'warning1' => 'Your request was successful, but some data was not properly saved.'));
    }

    $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/budgets_manage_addProcess.php');

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

    $form->addRow()->addHeading(__('Staff'));

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

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
?>
