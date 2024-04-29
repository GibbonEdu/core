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
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Forms\Prefab\BulkActionForm;
use Gibbon\Domain\Finance\InvoiceGateway;
use Gibbon\Module\Finance\Forms\FinanceFormFactory;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Finance/invoices_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Manage Invoices'));

    $page->return->addReturns(['success1' => __('Your request was completed successfully, but one or more requested emails could not be sent.'), 'error3' => __('Some elements of your request failed, but others were successful.')]);

    echo '<p>';
    echo __('This section allows you to generate, view, edit and delete invoices, either for an individual or in bulk. You can use the filters below to pick up certain invoices types (e.g. those that are overdue) or view all invoices for a particular user. Invoices, reminders and receipts can be sent out using the Email function, shown in the right-hand side menu.').'<br/>';
    echo '<br/>';
    echo __('When you create invoices using the billing schedule or pre-defined fee features, the invoice will remain linked to these areas whilst pending. Thus, changes made to the billing schedule and pre-defined fees will be reflected in any pending invoices. Once invoices are issued, this link is removed, and the values are fixed at the levels when the invoice was issued.');
    echo '</p>';

    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');

    if ($gibbonSchoolYearID != '') {
       $page->navigator->addSchoolYearNavigation($gibbonSchoolYearID);

        $request = array(
            'gibbonSchoolYearID'             => $gibbonSchoolYearID,
            'status'                         => isset($_GET['status'])? $_GET['status'] : '',
            'gibbonFinanceInvoiceeID'        => isset($_GET['gibbonFinanceInvoiceeID'])? $_GET['gibbonFinanceInvoiceeID'] : '',
            'monthOfIssue'                   => isset($_GET['monthOfIssue'])? $_GET['monthOfIssue'] : '',
            'gibbonFinanceBillingScheduleID' => isset($_GET['gibbonFinanceBillingScheduleID'])? $_GET['gibbonFinanceBillingScheduleID'] : '',
            'gibbonFinanceFeeCategoryID'     => isset($_GET['gibbonFinanceFeeCategoryID'])? $_GET['gibbonFinanceFeeCategoryID'] : '',
        );

        if (empty($_POST) && !isset($_GET['status'])) $request['status'] = 'Pending';

        echo '<h3>';
        echo __('Filters');
        echo '</h3>';

        $form = Form::create('manageInvoices', $session->get('absoluteURL').'/index.php', 'get');
        $form->setFactory(FinanceFormFactory::create($pdo));
        $form->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('q', '/modules/Finance/invoices_manage.php');
        $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        $row = $form->addRow();
            $row->addLabel('status', __('Status'));
            $row->addSelectInvoiceStatus('status');

        $row = $form->addRow();
            $row->addLabel('gibbonFinanceInvoiceeID', __('Student'));
            $row->addSelectInvoicee('gibbonFinanceInvoiceeID', $gibbonSchoolYearID, array('allStudents' => true));

        $row = $form->addRow();
            $row->addLabel('monthOfIssue', __('Month of Issue'));
            $row->addSelectMonth('monthOfIssue');

        $row = $form->addRow();
            $row->addLabel('gibbonFinanceBillingScheduleID', __('Billing Schedule'));
            $row->addSelectBillingSchedule('gibbonFinanceBillingScheduleID', $gibbonSchoolYearID)
                ->fromArray(array('Ad Hoc' => __('Ad Hoc')));

        $row = $form->addRow();
            $row->addLabel('gibbonFinanceFeeCategoryID', __('Fee Category'));
            $row->addSelectFeeCategory('gibbonFinanceFeeCategoryID')->placeholder();

        $row = $form->addRow();
            $row->addSearchSubmit($session, __('Clear Filters'), array('gibbonSchoolYearID'));

        $form->loadAllValuesFrom($request);

        echo $form->getOutput();

        echo '<h3>';
        echo __('View');
        echo '</h3>';

        echo '<p class="bulkPaid">';
        echo __('This bulk action can be used to update the status for more than one invoice to Paid (in full). It does NOT email receipts or work with payments requiring a Transaction ID. If you need to include email receipts, add a Transaction ID or process a partial payment use the Edit action for each individual invoice.');
        echo '</p>';

        // QUERY
        $invoiceGateway = $container->get(InvoiceGateway::class);

        $criteria = $invoiceGateway->newQueryCriteria(true)
            ->sortBy(['defaultSortOrder', 'invoiceIssueDate', 'surname', 'preferredName'])
            ->filterBy('status', $request['status'])
            ->filterBy('invoicee', $request['gibbonFinanceInvoiceeID'])
            ->filterBy('month', $request['monthOfIssue'])
            ->filterBy('billingSchedule', $request['gibbonFinanceBillingScheduleID'])
            ->filterBy('feeCategory', $request['gibbonFinanceFeeCategoryID'])
            ->fromPOST();
        $invoices = $invoiceGateway->queryInvoicesByYear($criteria, $gibbonSchoolYearID);

        // FORM
        $form = BulkActionForm::create('bulkAction', $session->get('absoluteURL') . '/modules/' . $session->get('module') . '/invoices_manage_processBulk.php?'.http_build_query($request));
        $form->setFactory(FinanceFormFactory::create($pdo));

        $form->addHiddenValue('address', $session->get('address'));

        // BULK ACTIONS
        $bulkActions = array('export' => __('Export'));
        switch($criteria->getFilterValue('status')) {
            case 'Pending':
                $bulkActions = $bulkActions + array('delete' => __('Delete'), 'issue' => __('Issue'), 'issueNoEmail' => __('Issue (Without Email)')); break;
            case 'Issued - Overdue':
                $bulkActions = $bulkActions + array('reminders' => __('Issue Reminders'), 'paid' => __('Mark as Paid')); break;
            case 'Paid - Partial':
                $bulkActions = $bulkActions + array('reminders' => __('Issue Reminders')); break;
            case 'Issued':
                $bulkActions = $bulkActions + array('paid' => __('Mark as Paid')); break;
        }

        $form->toggleVisibilityByClass('bulkPaid')->onSelect('action')->when('paid');

        $col = $form->createBulkActionColumn($bulkActions);
            $col->addSelectPaymentMethod('paymentType')
                ->setClass('bulkPaid shortWidth displayNone')
                ->required()
                ->addValidationOption('onlyOnSubmit: true')
                ->placeholder(__('Payment Type').'...');
            $col->addDate('paidDate')
                ->setClass('bulkPaid shortWidth displayNone')
                ->required()
                ->addValidationOption('onlyOnSubmit: true')
                ->placeholder(__('Date Paid'));
            $col->addSubmit(__('Go'));

        // DATA TABLE
        $table = $form->addRow()->addDataTable('invoices', $criteria)->withData($invoices);

        $table->addHeaderAction('add', __('Add'))
            ->setURL('/modules/Finance/invoices_manage_add.php')
            ->setIcon('page_new_multi')
            ->addParams($request)
            ->displayLabel()
            ->append('<br/>');

        $table->modifyRows(function ($invoice, $row) {
            if ($invoice['status'] == 'Issued' && $invoice['invoiceDueDate'] < date('Y-m-d')) $row->addClass('error');
            else if ($invoice['status'] == 'Paid') $row->addClass('current');
            return $row;
        });

        $table->addMetaData('bulkActions', $col);
        $table->addMetaData('post', ['gibbonSchoolYearID' => $gibbonSchoolYearID]);

        $table->addMetaData('filterOptions', [
            'status:Pending'          => __('Status').': '.__('Pending'),
            'status:Issued'           => __('Status').': '.__('Issued'),
            'status:Issued - Overdue' => __('Status').': '.__('Issued - Overdue'),
            'status:Paid'             => __('Status').': '.__('Paid'),
            'status:Paid - Partial'   => __('Status').': '.__('Paid - Partial'),
            'status:Paid - Late'      => __('Status').': '.__('Paid - Late'),
            'status:Cancelled'        => __('Status').': '.__('Cancelled'),
            'status:Refunded'         => __('Status').': '.__('Refunded'),
        ]);

        // COLUMNS
        $table->addExpandableColumn('notes');

        $table->addColumn('student', __('Student'))
            ->description(__('Invoice To'))
            ->sortable(['surname', 'preferredName'])
            ->format(function($invoice) {
                $output = '<b>'.Format::name('', $invoice['preferredName'], $invoice['surname'], 'Student', true).'</b>';
                $output .= '<br/><span class="small emphasis">'.__($invoice['invoiceTo']).'</span>';
                return $output;
            });

        $table->addColumn('formGroup', __('Form Group'));

        $table->addColumn('status', __('Status'))
            ->format(function ($invoice) {
                if ($invoice['status'] == 'Issued' && $invoice['invoiceDueDate'] < date('Y-m-d')) {
                    return __('Issued - Overdue');
                } else if ($invoice['status'] == 'Paid' && $invoice['invoiceDueDate'] < $invoice['paidDate']) {
                    return __('Paid - Late');
                }
                return __($invoice['status']);
            });

        $table->addColumn('billingSchedule', __('Schedule'));

        $table->addColumn('total', __('Total').' <small><i>('.$session->get('currency').')</i></small>')
            ->description(__('Paid').' ('.$session->get('currency').')')
            ->notSortable()
            ->format(function ($invoice) use ($pdo) {
                $totalFee = getInvoiceTotalFee($pdo, $invoice['gibbonFinanceInvoiceID'], $invoice['status']);
                if (is_null($totalFee)) return '';

                $output = Format::currency($totalFee);
                if (!empty($invoice['paidAmount'])) {
                    $class = Format::number($invoice['paidAmount']) != Format::number($totalFee)? 'textOverBudget' : '';
                    $output .= '<br/><span class="small emphasis '.$class.'">'.Format::currency($invoice['paidAmount']).'</span>';
                }
                return $output;
            });

        $table->addColumn('invoiceIssueDate', __('Issue Date'))
            ->description(__('Due Date'))
            ->format(function ($invoice) {
                $output = !is_null($invoice['invoiceIssueDate'])? Format::date($invoice['invoiceIssueDate']) : __('N/A');
                $output .= '<br/><span class="small emphasis">'.Format::date($invoice['invoiceDueDate']).'</span>';
                return $output;
            });

        // ACTIONS
        $table->addActionColumn()
            ->addParam('gibbonFinanceInvoiceID')
            ->addParams($request)
            ->format(function ($invoice, $actions) {
                if ($invoice['status'] != 'Cancelled' && $invoice['status'] != 'Refunded') {
                    $actions->addAction('edit', __('Edit'))
                        ->setURL('/modules/Finance/invoices_manage_edit.php');
                }

                if ($invoice['status'] == 'Pending') {
                    $actions->addAction('issue', __('Issue'))
                        ->setURL('/modules/Finance/invoices_manage_issue.php')
                        ->setIcon('page_right');

                    $actions->addAction('delete', __('Delete'))
                        ->setURL('/modules/Finance/invoices_manage_delete.php');

                    $actions->addAction('preview', __('Preview Invoice'))
                        ->setURL('/modules/Finance/invoices_manage_print_print.php')
                        ->addParam('type', 'invoice')
                        ->addParam('preview', 'true')
                        ->setIcon('print');
                } else {
                    $actions->addAction('print', __('Print Invoices, Receipts & Reminders'))
                        ->setURL('/modules/Finance/invoices_manage_print.php')
                        ->setIcon('print');
                }
            });

        $table->addCheckboxColumn('gibbonFinanceInvoiceIDs', 'gibbonFinanceInvoiceID');

        echo $form->getOutput();
        echo '<br/>';
    }
}
