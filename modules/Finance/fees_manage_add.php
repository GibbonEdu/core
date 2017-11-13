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

if (isActionAccessible($guid, $connection2, '/modules/Finance/fees_manage_add.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Finance/fees_manage.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID']."'>Manage Fees</a> > </div><div class='trailEnd'>Add Fee</div>";
    echo '</div>';

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Finance/fees_manage_edit.php&gibbonFinanceFeeID='.$_GET['editID'].'&search='.$_GET['search'].'&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID'];
    }
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    //Check if school year specified
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
    $search = $_GET['search'];
    if ($gibbonSchoolYearID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        if ($search != '') {
            echo "<div class='linkTop'>";
            echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Finance/fees_manage.php&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search'>".__($guid, 'Back to Search Results').'</a>';
            echo '</div>';
        }

        $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/fees_manage_addProcess.php?gibbonSchoolYearID=$gibbonSchoolYearID&search=$search");

        $form->setClass('smallIntBorder fullWidth');

        $form->addHiddenValue('address', $_SESSION[$guid]['address']);

        try {
            $dataYear = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
            $sqlYear = 'SELECT name AS schoolYear FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
            $resultYear = $connection2->prepare($sqlYear);
            $resultYear->execute($dataYear);
        } catch (PDOException $e) {
            $form->addRow()->addAlert($e->getMessage(), 'error');
        }
        if ($resultYear->rowCount() == 1) {
            $values = $resultYear->fetch();
            $row = $form->addRow();
                $row->addLabel('schoolYear', __('School Year'));
                $row->addTextField('schoolYear')->maxLength(20)->isRequired()->readonly()->setValue($values['schoolYear']);
        }

        $row = $form->addRow();
            $row->addLabel('name', __('Name'));
            $row->addTextField('name')->maxLength(100)->isRequired();

        $row = $form->addRow();
            $row->addLabel('nameShort', __('Short Name'));
            $row->addTextField('nameShort')->maxLength(6)->isRequired();

        $row = $form->addRow();
            $row->addLabel('active', __('Active'));
            $row->addYesNo('active')->isRequired();

        $row = $form->addRow();
            $row->addLabel('description', __('Description'));
            $row->addTextArea('description');

        $data = array();
        $sql = "SELECT gibbonFinanceFeeCategoryID AS value, name FROM gibbonFinanceFeeCategory WHERE active='Y' AND NOT gibbonFinanceFeeCategoryID=1 ORDER BY name";
        $row = $form->addRow();
            $row->addLabel('gibbonFinanceFeeCategoryID', __('Category'));
            $row->addSelect('gibbonFinanceFeeCategoryID')->fromQuery($pdo, $sql, $data)->fromArray(array('1' => __('Other')))->isRequired()->placeholder();

        $row = $form->addRow();
            $row->addLabel('fee', __('Fee'))
                ->description(__('Numeric value of the fee.'))
                ->append(sprintf(__('In %1$s.'), $_SESSION[$guid]['currency']));
            $row->addCurrency('fee')->isRequired();

        $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

        echo $form->getOutput();
    }
}
?>
