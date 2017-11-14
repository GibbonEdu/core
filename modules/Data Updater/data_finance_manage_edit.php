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

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_finance_manage_edit.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Data Updater/data_finance_manage.php'>".__($guid, 'Finance Data Updates')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Request').'</div>';
    echo '</div>';

    //Check if school year specified
    $gibbonFinanceInvoiceeUpdateID = $_GET['gibbonFinanceInvoiceeUpdateID'];
    if ($gibbonFinanceInvoiceeUpdateID == 'Y') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonFinanceInvoiceeUpdateID' => $gibbonFinanceInvoiceeUpdateID);
            $sql = "SELECT gibbonFinanceInvoicee.* FROM gibbonFinanceInvoiceeUpdate JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoiceeUpdate.gibbonFinanceInvoiceeID=gibbonFinanceInvoicee.gibbonFinanceInvoiceeID) WHERE gibbonFinanceInvoiceeUpdateID=:gibbonFinanceInvoiceeUpdateID";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The selected record does not exist, or you do not have access to it.');
            echo '</div>';
        } else {
            if (isset($_GET['return'])) {
                returnProcess($guid, $_GET['return'], null, null);
            }

            $data = array('gibbonFinanceInvoiceeUpdateID' => $gibbonFinanceInvoiceeUpdateID);
            $sql = "SELECT gibbonFinanceInvoiceeUpdate.* FROM gibbonFinanceInvoiceeUpdate JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoiceeUpdate.gibbonFinanceInvoiceeID=gibbonFinanceInvoicee.gibbonFinanceInvoiceeID) WHERE gibbonFinanceInvoiceeUpdateID=:gibbonFinanceInvoiceeUpdateID";
            $newResult = $pdo->executeQuery($data, $sql);
            
            //Let's go!
            $oldValues = $result->fetch();
            $newValues = $newResult->fetch();
            
            // An array of common fields to compare in each data set, and the field label
            $compare = array(
                'invoiceTo'                      => __('Invoice To'),
                'companyName'                    => __('Company Name'),
                'companyContact'                 => __('Company Contact Person'),
                'companyAddress'                 => __('Company Address'),
                'companyEmail'                   => __('Company Email'),
                'companyCCFamily'                => __('CC Family?'),
                'companyPhone'                   => __('Company Phone'),
                'companyAll'                     => __('Company All?'),
                'gibbonFinanceFeeCategoryIDList' => __('Company Fee Categories'),
            );

            $form = Form::create('updateFinance', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/data_finance_manage_editProcess.php?gibbonFinanceInvoiceeUpdateID='.$gibbonFinanceInvoiceeUpdateID);
            
            $form->setClass('fullWidth colorOddEven');
            $form->addHiddenValue('address', $_SESSION[$guid]['address']);
            $form->addHiddenValue('gibbonFinanceInvoiceeID', $oldValues['gibbonFinanceInvoiceeID']);

            $row = $form->addRow()->setClass('head heading');
                $row->addContent(__('Field'));
                $row->addContent(__('Current Value'));
                $row->addContent(__('New Value'));
                $row->addContent(__('Accept'));

            foreach ($compare as $fieldName => $label) {
                $isMatching = ($oldValues[$fieldName] != $newValues[$fieldName]);

                $row = $form->addRow();
                $row->addLabel('new'.$fieldName.'On', $label);
                $row->addContent($oldValues[$fieldName]);
                $row->addContent($newValues[$fieldName])->addClass($isMatching ? 'matchHighlightText' : '');
                
                if ($isMatching) {
                    $row->addCheckbox('new'.$fieldName.'On')->checked(true)->setClass('textCenter');
                    $form->addHiddenValue('new'.$fieldName, $newValues[$fieldName]);
                } else {
                    $row->addContent();
                }
            }
            
            $row = $form->addRow();
                $row->addSubmit();

            echo $form->getOutput();
        }
    }
}
?>