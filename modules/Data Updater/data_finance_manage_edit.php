<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_finance_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');

    $urlParams = ['gibbonSchoolYearID' => $gibbonSchoolYearID];

    $page->breadcrumbs
        ->add(__('Finance Data Updates'), 'data_finance_manage.php', $urlParams)
        ->add(__('Edit Request'));

    //Check if gibbonFinanceInvoiceeUpdateID specified
    $gibbonFinanceInvoiceeUpdateID = $_GET['gibbonFinanceInvoiceeUpdateID'] ?? '';
    if ($gibbonFinanceInvoiceeUpdateID == 'Y') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {

            $data = array('gibbonFinanceInvoiceeUpdateID' => $gibbonFinanceInvoiceeUpdateID);
            $sql = "SELECT gibbonFinanceInvoicee.* FROM gibbonFinanceInvoiceeUpdate JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoiceeUpdate.gibbonFinanceInvoiceeID=gibbonFinanceInvoicee.gibbonFinanceInvoiceeID) WHERE gibbonFinanceInvoiceeUpdateID=:gibbonFinanceInvoiceeUpdateID";
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The selected record does not exist, or you do not have access to it.'));
        } else {
            $data = array('gibbonFinanceInvoiceeUpdateID' => $gibbonFinanceInvoiceeUpdateID);
            $sql = "SELECT gibbonFinanceInvoiceeUpdate.* FROM gibbonFinanceInvoiceeUpdate JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoiceeUpdate.gibbonFinanceInvoiceeID=gibbonFinanceInvoicee.gibbonFinanceInvoiceeID) WHERE gibbonFinanceInvoiceeUpdateID=:gibbonFinanceInvoiceeUpdateID";
            $newResult = $pdo->executeQuery($data, $sql);

            //Let's go!
            $oldValues = $result->fetch();
            $newValues = $newResult->fetch();

            // Provide a link back to edit the associated record
            if (isActionAccessible($guid, $connection2, '/modules/Finance/invoicees_manage_edit.php') == true && !empty($oldValues['gibbonFinanceInvoiceeID'])) {
                $params = [
                    'gibbonFinanceInvoiceeID' => $oldValues['gibbonFinanceInvoiceeID'], 
                    'search' => '', 
                    'allUsers' => ''
                ];
                $page->navigator->addHeaderAction('edit', __('Edit Invoicee'))
                    ->setURL('/modules/Finance/invoicees_manage_edit.php')
                    ->addParams($params)
                    ->setIcon('config')
                    ->displayLabel();
            }

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

            $form = Form::createTable('updateFinance', $session->get('absoluteURL').'/modules/'.$session->get('module').'/data_finance_manage_editProcess.php?gibbonFinanceInvoiceeUpdateID='.$gibbonFinanceInvoiceeUpdateID);

            $form->setClass('fullWidth colorOddEven');
            $form->addHiddenValue('address', $session->get('address'));
            $form->addHiddenValue('gibbonFinanceInvoiceeID', $oldValues['gibbonFinanceInvoiceeID']);

            $row = $form->addRow()->setClass('head heading');
                $row->addContent(__('Field'));
                $row->addContent(__('Current Value'));
                $row->addContent(__('New Value'));
                $row->addContent(__('Accept'));

            $changeCount = 0;
            foreach ($compare as $fieldName => $label) {
                $isMatching = ($oldValues[$fieldName] != $newValues[$fieldName]);

                $row = $form->addRow();
                $row->addLabel('new'.$fieldName.'On', $label);
                $row->addContent($oldValues[$fieldName]);
                $row->addContent($newValues[$fieldName])->addClass($isMatching ? 'matchHighlightText' : '');

                if ($isMatching) {
                    $row->addCheckbox('new'.$fieldName.'On')->checked(true)->setClass('textCenter');
                    $form->addHiddenValue('new'.$fieldName, $newValues[$fieldName]);
                    $changeCount++;
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
