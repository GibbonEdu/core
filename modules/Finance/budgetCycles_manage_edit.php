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

if (isActionAccessible($guid, $connection2, '/modules/Finance/budgetCycles_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/budgetCycles_manage.php'>".__($guid, 'Manage Budget Cycles')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Budget Cycle').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, array('error3' => 'Your request failed because some inputs did not meet a requirement for uniqueness.', 'warning1' => 'Your request was successful, but some data was not properly saved.'));
    }

    //Check if school year specified
    $gibbonFinanceBudgetCycleID = $_GET['gibbonFinanceBudgetCycleID'];
    if ($gibbonFinanceBudgetCycleID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID);
            $sql = 'SELECT * FROM gibbonFinanceBudgetCycle WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID';
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

            $form = Form::create('budgetCycle', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/budgetCycles_manage_editProcess.php?gibbonFinanceBudgetCycleID='.$gibbonFinanceBudgetCycleID);
            $form->setFactory(DatabaseFormFactory::create($pdo));

        	$form->addHiddenValue("address", $_SESSION[$guid]['address']);

        	$row = $form->addRow();
        		$row->addHeading(__("Basic Information"));

        	$row = $form->addRow();
        		$row->addLabel("name", __("Name"))->description(__("Must be unique."));
        		$row->addTextField("name")->isRequired()->maxLength(7);

        	$row = $form->addRow();
        		$row->addLabel("status", __("Status"));
        		$row->addSelect("status")->fromArray(array(__("Upcoming"), __("Current"), __("Past")));

            $row = $form->addRow();
                $row->addLabel('sequenceNumber', __('Sequence Number'))->description(__('Must be unique. Controls chronological ordering.'));
                $row->addSequenceNumber('sequenceNumber', 'gibbonFinanceBudgetCycle', $values['sequenceNumber'])->isRequired()->maxLength(3);

        	$row = $form->addRow();
        		$row->addLabel("dateStart", __("Start Date"))->description(__('Format:').' ')->append($_SESSION[$guid]['i18n']['dateFormat']);
        		$row->addDate("dateStart")->isRequired();

        	$row = $form->addRow();
        		$row->addLabel("dateEnd", __("End Date"))->description(__('Format:').' ')->append($_SESSION[$guid]['i18n']['dateFormat']);
        		$row->addDate("dateEnd")->isRequired();

        	$row = $form->addRow();
        		$row->addHeading(__("Budget Allocations"));

            try {
                $dataBudget = array('gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID);
                $sqlBudget = 'SELECT gibbonFinanceBudget.*, value FROM gibbonFinanceBudget LEFT JOIN gibbonFinanceBudgetCycleAllocation ON (gibbonFinanceBudgetCycleAllocation.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID AND gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID) ORDER BY name';
                $resultBudget = $connection2->prepare($sqlBudget);
                $resultBudget->execute($dataBudget);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            if ($resultBudget->rowCount() < 1) {
                $form->addRow()->addAlert(__($guid, 'There are no records to display.'), 'error');
            } else {
                while ($rowBudget = $resultBudget->fetch()) {
                    $row = $form->addRow();
                        $row->addLabel($rowBudget['gibbonFinanceBudgetID'], $rowBudget['name']);
                        $row->addCurrency($rowBudget['gibbonFinanceBudgetID'])->setName('values[]')->isRequired()->maxLength(15)->setValue((is_null($rowBudget['value'])) ? '0.00' : $rowBudget['value']);
                        $form->addHiddenValue('gibbonFinanceBudgetIDs[]', $rowBudget['gibbonFinanceBudgetID']);
                }
            }

            $form->loadAllValuesFrom($values);

            $row = $form->addRow();
        		$row->addFooter();
        		$row->addSubmit();

            print $form->getOutput();
        }
    }
}
?>
