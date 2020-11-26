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

if (isActionAccessible($guid, $connection2, '/modules/Finance/budgetCycles_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('Manage Budget Cycles'), 'budgetCycles_manage.php')
        ->add(__('Add Budget Cycle'));    

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Finance/budgetCycles_manage_edit.php&gibbonFinanceBudgetCycleID='.$_GET['editID'];
    }
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], $editLink);
    }

    $form = Form::create('budgetCycle', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/budgetCycles_manage_addProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue("address", $_SESSION[$guid]['address']);

    $row = $form->addRow();
        $row->addHeading(__("Basic Information"));

    $row = $form->addRow();
        $row->addLabel("name", __("Name"))->description(__("Must be unique."));
	$row->addTextField("name")->required()->maxLength(7);

    $statusTypes = array(
        'Upcoming' => __("Upcoming"),
        'Current' =>  __("Current"),
        'Past' => __("Past")
    );
    
    $row = $form->addRow();
	$row->addLabel("status", __("Status"));
	$row->addSelect("status")->fromArray($statusTypes);

    $row = $form->addRow();
        $row->addLabel('sequenceNumber', __('Sequence Number'))->description(__('Must be unique. Controls chronological ordering.'));
        $row->addSequenceNumber('sequenceNumber', 'gibbonFinanceBudgetCycle')->required()->maxLength(3);

    $row = $form->addRow();
        $row->addLabel("dateStart", __("Start Date"))->description(__('Format:').' ')->append($_SESSION[$guid]['i18n']['dateFormat']);
        $row->addDate("dateStart")->required();

    $row = $form->addRow();
        $row->addLabel("dateEnd", __("End Date"))->description(__('Format:').' ')->append($_SESSION[$guid]['i18n']['dateFormat']);
        $row->addDate("dateEnd")->required();

    $row = $form->addRow();
        $row->addHeading(__("Budget Allocations"));

    
        $dataBudget = array();
        $sqlBudget = 'SELECT * FROM gibbonFinanceBudget ORDER BY name';
        $resultBudget = $connection2->prepare($sqlBudget);
        $resultBudget->execute($dataBudget);
    
    if ($resultBudget->rowCount() < 1) {
        $row = $form->addRow();
            $row->addAlert(__('There are no records to display.'), "error");
    } else {
        while ($rowBudget = $resultBudget->fetch()) {

            $description = "";
            
            if ($_SESSION[$guid]['currency'] != '') {
                $description = sprintf(__('Numeric value in %1$s.'), $_SESSION[$guid]['currency']);
            } else {
                $description = __('Numeric value.');
            }
            
            $row = $form->addRow();
                $row->addLabel('values[]', $rowBudget['name'])->description($description);
                $row->addNumber("values[]")->maxLength(15)->decimalPlaces(2)->setValue("0.00");
            
            $form->addHiddenValue("gibbonFinanceBudgetIDs[]", $rowBudget['gibbonFinanceBudgetID']);
        }
    }

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    print $form->getOutput();
}
?>
