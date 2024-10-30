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

use Gibbon\Http\Url;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;

if (isActionAccessible($guid, $connection2, '/modules/Finance/billingSchedule_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';

    $urlParams = compact('gibbonSchoolYearID');

    $page->breadcrumbs
        ->add(__('Manage Billing Schedule'), 'billingSchedule_manage.php', $urlParams)
        ->add(__('Edit Entry'));

    $gibbonFinanceBillingScheduleID = $_GET['gibbonFinanceBillingScheduleID'] ?? '';
    $search = $_GET['search'] ?? '';
    //Check if gibbonFinanceBillingScheduleID and gibbonSchoolYearID  specified
    if ($gibbonFinanceBillingScheduleID == '' or $gibbonSchoolYearID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {

            $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonFinanceBillingScheduleID' => $gibbonFinanceBillingScheduleID);
            $sql = 'SELECT * FROM gibbonFinanceBillingSchedule WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFinanceBillingScheduleID=:gibbonFinanceBillingScheduleID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $resultRow = $result->fetch();

                if ($search != '') {
                     $params = [
                    "gibbonSchoolYearID" => $gibbonSchoolYearID,
                    "search" => $search
                ];
                $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Finance', 'billingSchedule_manage.php')->withQueryParams($params));
            }

            $yearName = '';

                $dataYear = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
                $sqlYear = 'SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
                $resultYear = $connection2->prepare($sqlYear);
                $resultYear->execute($dataYear);
            if ($resultYear->rowCount() == 1) {
                $rowYear = $resultYear->fetch();
                $yearName = $rowYear['name'];
            }

            $form = Form::create("edit", $session->get('absoluteURL').'/modules/'.$session->get('module')."/billingSchedule_manage_editProcess.php?gibbonSchoolYearID=$gibbonSchoolYearID&search=$search");

            $form->addHiddenValue("gibbonFinanceBillingScheduleID", $gibbonFinanceBillingScheduleID);
            $form->addHiddenValue("address", $session->get('address'));

            $row = $form->addRow();
                $row->addLabel("yearName", __("School Year"));
                $row->addTextField("yearName")->setValue($yearName)->readonly(true)->required();

            $row = $form->addRow();
                $row->addLabel("name", __("Name"));
                $row->addTextField("name")->setValue($resultRow['name'])->maxLength(100)->required();

            $row = $form->addRow();
                $row->addLabel("active", __("Active"));
                $row->addYesNo("active")->selected($resultRow['active'])->required();

            $row = $form->addRow();
                $row->addLabel("description", __("Description"));
                $row->addTextArea("description")->setValue($resultRow['description'])->setRows(5);

            $row = $form->addRow();
                $row->addLabel("invoiceIssueDate", __('Invoice Issue Date'))->description(__('Intended issue date.').'<br/>')->append(__('Format:').' ')->append($session->get('i18n')['dateFormat']);
                $row->addDate('invoiceIssueDate')->setValue(Format::date($resultRow['invoiceIssueDate']))->required();

            $row = $form->addRow();
                $row->addLabel('invoiceDueDate', __('Invoice Due Date'))->description(__('Final payment date.').'<br/>')->append(__('Format:').' ')->append($session->get('i18n')['dateFormat']);
                $row->addDate('invoiceDueDate')->setValue(Format::date($resultRow['invoiceDueDate']))->required();

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            print $form->getOutput();
        }
    }
}
