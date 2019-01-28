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

use Gibbon\Domain\Finance\FinanceInvoiceFeeGateway;
use Gibbon\Domain\Finance\InvoiceGateway;
use Gibbon\Services\Format;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Tables\Prefab\ReportTable;

include '../../config.php';

if (isActionAccessible($guid, $connection2, '/modules/Finance/invoices_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $invoiceIDList = $gibbon->session->remove('financeInvoiceExportIDs');
    $schoolYear = $_GET['gibbonSchoolYearID'];

    if ($invoiceIDList == '' or $schoolYear == '') {
        echo "<div class='error'>";
        echo __('List of invoices or school year have not been specified, and so this export cannot be completed.');
        echo '</div>';
    } else {

        $invoiceGateway = $container->get(InvoiceGateway::class);
        $invoices = $invoiceGateway->findExportContent($schoolYear, $invoiceIDList);
        $criteria = $invoiceGateway->newQueryCriteria()
            ->sortBy(["FIND_IN_SET(status, 'Pending,Issued,Paid,Refunded,Cancelled')", 'invoiceIssueDate', 'surname', 'preferredName'])
            ->pageSize(0);

		if ($invoices->getResultCount() === 0) {
			echo "<div class='error'>".__('Your request failed due to a database error.')."</div>";
		}
        $settingGateway = $container->get(SettingGateway::class);
        $invoiceNumber = $settingGateway->getSettingByScope("Finance", "invoiceNumber");

        $table = ReportTable::createPaginated('invoiceExportOn'.date('Y-m-d'), $criteria)->setViewMode('export',$gibbon->session);
        $table->setTitle('Invoices');
        $table->addMetaData('Subject','Invoice Export');
        $table->setDescription('Invoice Export');


        $table->addColumn('invoiceNumber',  __("Invoice Number"));
        $table->addColumn('studentName',  __("Student"));
        $table->addColumn('rollGroup',  __("Roll Group"));
        $table->addColumn('invoiceTo',  __("Invoice To"));
        $table->addColumn('dob',  __("DOB"));
        $table->addColumn('gender',  __("Gender"));
        $table->addColumn('status',  __("Status"));
        $table->addColumn('billingSchedule',  __("Schedule"));
        $table->addColumn('totalValue', __("Total Value") . ' (' . $gibbon->session->get('currency') .')');
        $table->addColumn('invoiceIssueDate',  __("Issue Date"));
        $table->addColumn('invoiceDueDate', __("Due Date"));
        $table->addColumn('paidDate', __("Date Paid"));
        $table->addColumn('paidAmount', __("Amount Paid") . ' (' . $gibbon->session->get('currency') .')');

        $invoiceFeeGateway = $container->get(FinanceInvoiceFeeGateway::class);
        $invoices->transform(function(&$item) use ($invoiceNumber, $invoiceFeeGateway) {
            if ($invoiceNumber === "Person ID + Invoice ID") {
                $item['invoiceNumber'] = ltrim($item["gibbonPersonID"],"0") . "-" . ltrim($item["gibbonFinanceInvoiceID"], "0");
            }
            else if ($invoiceNumber === "Student ID + Invoice ID") {
                $item['invoiceNumber'] = ltrim($item["studentID"],"0") . "-" . ltrim($item["gibbonFinanceInvoiceID"], "0");
            }
            else {
                $item['invoiceNumber'] = ltrim($item["gibbonFinanceInvoiceID"], "0");
            }
            $item['studentName'] = Format::name("", htmlPrep($item["preferredName"]), htmlPrep($item["surname"]), "Student", true);

            $item['dob'] = Format::date($item['dob']);
            if (! empty($item["billingScheduleExtra"]))  {
                $item["billingSchedule"] = $item["billingScheduleExtra"];
            }

            $item['invoiceIssueDate'] = Format::date($item["invoiceIssueDate"]);
            $item['invoiceDueDate'] = Format::date($item["invoiceDueDate"]);
            $item['paidDate'] = empty($item['paidDate']) ? '' : Format::date($item["paidDate"]);

            $totalFee = $invoiceFeeGateway->getFee($item["gibbonFinanceInvoiceID"], $item['status']);
            $item['totalValue'] = $totalFee === false ? __('Error calculating total') : number_format($totalFee, 2, ".", "") ;

            return ;
        });

        $table->render($invoices);
	}
}
