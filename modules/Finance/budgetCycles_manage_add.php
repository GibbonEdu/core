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

if (isActionAccessible($guid, $connection2, '/modules/Finance/budgetCycles_manage_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/budgetCycles_manage.php'>".__($guid, 'Manage Budget Cycles')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Budget Cycle').'</div>';
    echo '</div>';

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Finance/budgetCycles_manage_edit.php&gibbonFinanceBudgetCycleID='.$_GET['editID'];
    }
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], $editLink, array('error3' => 'Your request failed because some inputs did not meet a requirement for uniqueness.'));
    }

    $form = Form::create('budgetCycle', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/budgetCycles_manage_addProcess.php');

    	$form->addHiddenValue("address", $_SESSION[$guid]['address']);

    	$row = $form->addRow();
    		$row->addHeading(__("Basic Information"));

    	$row = $form->addRow();
    		$row->addLabel("name", __("Name"))->description(__("Must be unique."));
    		$row->addTextField("name")->isRequired()->maxLength(7);

    	$row = $form->addRow();
    		$row->addLabel("status", "Status");
    		$row->addSelect("status")->fromArray(array(__("Upcoming"), __("Current"), __("Past")));

    	$row = $form->addRow();
    		$row->addLabel("sequenceNumber", "Sequence Number")->description(__($guid, 'Must be unique. Controls chronological ordering.'));
    		$row->addNumber("sequenceNumber")->isRequired()->maxLength(3);

    	$row = $form->addRow();
    		$row->addLabel("dateStart", "Start Date")->description(__('Format:').' ')->append($_SESSION[$guid]['i18n']['dateFormat']);
    		$row->addDate("dateStart")->isRequired();

    	$row = $form->addRow();
    		$row->addLabel("dateEnd", "End Date")->description(__('Format:').' ')->append($_SESSION[$guid]['i18n']['dateFormat']);
    		$row->addDate("dateEnd")->isRequired();

    	$row = $form->addRow();
    		$row->addHeading(__("Budget Allocations"));


    	try {
            $dataBudget = array();
            $sqlBudget = 'SELECT * FROM gibbonFinanceBudget ORDER BY name';
            $resultBudget = $connection2->prepare($sqlBudget);
            $resultBudget->execute($dataBudget);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }
		if ($resultBudget->rowCount() < 1) {
			$row = $form->addRow();
				$row->addAlert(__('There are no records to display.'), "error");
		} else {
			while ($rowBudget = $resultBudget->fetch()) {

				$description = "";

				if ($_SESSION[$guid]['currency'] != '') {
                    $description = sprintf(__($guid, 'Numeric value in %1$s.'), $_SESSION[$guid]['currency']);
                } else {
                    $description = __($guid, 'Numeric value.');
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
