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
use Gibbon\Forms\Prefab\BulkActionForm;
use Gibbon\Finance\Forms\FinanceFormFactory;

//Module includes
include './modules/Finance/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Finance/invoices_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Invoices').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, array('success0' => 'Your request was completed successfully.', 'success1' => 'Your request was completed successfully, but one or more requested emails could not be sent.', 'error3' => 'Some elements of your request failed, but others were successful.'));
    }

    echo '<p>';
    echo __($guid, 'This section allows you to generate, view, edit and delete invoices, either for an individual or in bulk. You can use the filters below to pick up certain invoices types (e.g. those that are overdue) or view all invoices for a particular user. Invoices, reminders and receipts can be sent out using the Email function, shown in the right-hand side menu.').'<br/>';
    echo '<br/>';
    echo __($guid, 'When you create invoices using the billing schedule or pre-defined fee features, the invoice will remain linked to these areas whilst pending. Thus, changes made to the billing schedule and pre-defined fees will be reflected in any pending invoices. Once invoices are issued, this link is removed, and the values are fixed at the levels when the invoice was issued.');
    echo '</p>';

    $gibbonSchoolYearID = '';
    if (isset($_GET['gibbonSchoolYearID'])) {
        $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
    }
    if ($gibbonSchoolYearID == '' or $gibbonSchoolYearID == $_SESSION[$guid]['gibbonSchoolYearID']) {
        $gibbonSchoolYearID = $_SESSION[$guid]['gibbonSchoolYearID'];
        $gibbonSchoolYearName = $_SESSION[$guid]['gibbonSchoolYearName'];
    }

    if ($gibbonSchoolYearID != $_SESSION[$guid]['gibbonSchoolYearID']) {
        try {
            $data = array('gibbonSchoolYearID' => $_GET['gibbonSchoolYearID']);
            $sql = 'SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }
        if ($result->rowcount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The specified record does not exist.');
            echo '</div>';
        } else {
            $row = $result->fetch();
            $gibbonSchoolYearID = $row['gibbonSchoolYearID'];
            $gibbonSchoolYearName = $row['name'];
        }
    }

    if ($gibbonSchoolYearID != '') {
        echo '<h2>';
        echo $gibbonSchoolYearName;
        echo '</h2>';

        echo "<div class='linkTop'>";
            //Print year picker
            if (getPreviousSchoolYearID($gibbonSchoolYearID, $connection2) != false) {
                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/invoices_manage.php&gibbonSchoolYearID='.getPreviousSchoolYearID($gibbonSchoolYearID, $connection2)."'>".__($guid, 'Previous Year').'</a> ';
            } else {
                echo __($guid, 'Previous Year').' ';
            }
        echo ' | ';
        if (getNextSchoolYearID($gibbonSchoolYearID, $connection2) != false) {
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/invoices_manage.php&gibbonSchoolYearID='.getNextSchoolYearID($gibbonSchoolYearID, $connection2)."'>".__($guid, 'Next Year').'</a> ';
        } else {
            echo __($guid, 'Next Year').' ';
        }
        echo '</div>';

        $status = isset($_GET['status'])? $_GET['status'] : null;
        if ($status == '') $status = 'Pending';

        $gibbonFinanceInvoiceeID = isset($_GET['gibbonFinanceInvoiceeID'])? $_GET['gibbonFinanceInvoiceeID'] : '';
        $monthOfIssue = isset($_GET['monthOfIssue'])? $_GET['monthOfIssue'] : '';
        $gibbonFinanceBillingScheduleID = isset($_GET['gibbonFinanceBillingScheduleID'])? $_GET['gibbonFinanceBillingScheduleID'] : '';
        $gibbonFinanceFeeCategoryID = isset($_GET['gibbonFinanceFeeCategoryID'])? $_GET['gibbonFinanceFeeCategoryID'] : '';

        echo '<h3>';
        echo __($guid, 'Filters');
        echo '</h3>';

        $form = Form::create('manageInvoices', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
        $form->setFactory(FinanceFormFactory::create($pdo));
        $form->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('q', '/modules/Finance/invoices_manage.php');

        $row = $form->addRow();
            $row->addLabel('status', __('Status'));
            $row->addSelectInvoiceStatus('status')->selected($status, 'All');

        $row = $form->addRow();
            $row->addLabel('gibbonFinanceInvoiceeID', __('Student'));
            $row->addSelectInvoicee('gibbonFinanceInvoiceeID')->selected($gibbonFinanceInvoiceeID);

        $row = $form->addRow();
            $row->addLabel('monthOfIssue', __('Month of Issue'));
            $row->addSelectMonth('monthOfIssue')->selected($monthOfIssue);

        $row = $form->addRow();
            $row->addLabel('gibbonFinanceBillingScheduleID', __('Billing Schedule'));
            $row->addSelectBillingSchedule('gibbonFinanceBillingScheduleID', $gibbonSchoolYearID)->selected($gibbonFinanceBillingScheduleID);

        $row = $form->addRow();
            $row->addLabel('gibbonFinanceFeeCategoryID', __('Fee Category'));
            $row->addSelectFeeCategory('gibbonFinanceFeeCategoryID')->selected($gibbonFinanceFeeCategoryID);
        
        $row = $form->addRow();
            $row->addSearchSubmit($gibbon->session, __('Clear Filters'), array('gibbonSchoolYearID'));

        echo $form->getOutput();

        try {
            //Add in filter wheres
            $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonSchoolYearID2' => $gibbonSchoolYearID);
            $whereSched = '';
            $whereAdHoc = '';
            $whereNotPending = '';
            $today = date('Y-m-d');
            if ($status != '') {
                if ($status == 'Pending') {
                    $data['status1'] = 'Pending';
                    $whereSched .= ' AND gibbonFinanceInvoice.status=:status1';
                    $data['status2'] = 'Pending';
                    $whereAdHoc .= ' AND gibbonFinanceInvoice.status=:status2';
                    $data['status3'] = 'Pending';
                    $whereNotPending .= ' AND gibbonFinanceInvoice.status=:status3';
                } elseif ($status == 'Issued') {
                    $data['status1'] = 'Issued';
                    $data['dateTest1'] = $today;
                    $whereSched .= ' AND gibbonFinanceInvoice.status=:status1 AND gibbonFinanceInvoice.invoiceDueDate>=:dateTest1';
                    $data['status2'] = 'Issued';
                    $data['dateTest2'] = $today;
                    $whereAdHoc .= ' AND gibbonFinanceInvoice.status=:status2 AND gibbonFinanceInvoice.invoiceDueDate>=:dateTest2';
                    $data['status3'] = 'Issued';
                    $data['dateTest3'] = $today;
                    $whereNotPending .= ' AND gibbonFinanceInvoice.status=:status3 AND gibbonFinanceInvoice.invoiceDueDate>=:dateTest3';
                } elseif ($status == 'Issued - Overdue') {
                    $data['status1'] = 'Issued';
                    $data['dateTest1'] = $today;
                    $whereSched .= ' AND gibbonFinanceInvoice.status=:status1 AND gibbonFinanceInvoice.invoiceDueDate<:dateTest1';
                    $data['status2'] = 'Issued';
                    $data['dateTest2'] = $today;
                    $whereAdHoc .= ' AND gibbonFinanceInvoice.status=:status2 AND gibbonFinanceInvoice.invoiceDueDate<:dateTest2';
                    $data['status3'] = 'Issued';
                    $data['dateTest3'] = $today;
                    $whereNotPending .= ' AND gibbonFinanceInvoice.status=:status3 AND gibbonFinanceInvoice.invoiceDueDate<:dateTest3';
                } elseif ($status == 'Paid') {
                    $data['status1'] = 'Paid';
                    $whereSched .= ' AND gibbonFinanceInvoice.status=:status1 AND gibbonFinanceInvoice.invoiceDueDate>=paidDate';
                    $data['status2'] = 'Paid';
                    $whereAdHoc .= ' AND gibbonFinanceInvoice.status=:status2 AND gibbonFinanceInvoice.invoiceDueDate>=paidDate';
                    $data['status3'] = 'Paid';
                    $whereNotPending .= ' AND gibbonFinanceInvoice.status=:status3 AND gibbonFinanceInvoice.invoiceDueDate>=paidDate';
                } elseif ($status == 'Paid - Partial') {
                    $data['status1'] = 'Paid - Partial';
                    $whereSched .= ' AND gibbonFinanceInvoice.status=:status1';
                    $data['status2'] = 'Paid - Partial';
                    $whereAdHoc .= ' AND gibbonFinanceInvoice.status=:status2';
                    $data['status3'] = 'Paid - Partial';
                    $whereNotPending .= ' AND gibbonFinanceInvoice.status=:status3';
                } elseif ($status == 'Paid - Late') {
                    $data['status1'] = 'Paid';
                    $whereSched .= ' AND gibbonFinanceInvoice.status=:status1 AND gibbonFinanceInvoice.invoiceDueDate<paidDate';
                    $data['status2'] = 'Paid';
                    $whereAdHoc .= ' AND gibbonFinanceInvoice.status=:status2 AND gibbonFinanceInvoice.invoiceDueDate<paidDate';
                    $data['status3'] = 'Paid';
                    $whereNotPending .= ' AND gibbonFinanceInvoice.status=:status3 AND gibbonFinanceInvoice.invoiceDueDate<paidDate';
                } elseif ($status == 'Cancelled') {
                    $data['status1'] = 'Cancelled';
                    $whereSched .= ' AND gibbonFinanceInvoice.status=:status1';
                    $data['status2'] = 'Cancelled';
                    $whereAdHoc .= ' AND gibbonFinanceInvoice.status=:status2';
                    $data['status3'] = 'Cancelled';
                    $whereNotPending .= ' AND gibbonFinanceInvoice.status=:status3';
                } elseif ($status == 'Refunded') {
                    $data['status1'] = 'Refunded';
                    $whereSched .= ' AND gibbonFinanceInvoice.status=:status1';
                    $data['status2'] = 'Refunded';
                    $whereAdHoc .= ' AND gibbonFinanceInvoice.status=:status2';
                    $data['status3'] = 'Refunded';
                    $whereNotPending .= ' AND gibbonFinanceInvoice.status=:status3';
                }
            }
            if ($gibbonFinanceInvoiceeID != '') {
                $data['gibbonFinanceInvoiceeID1'] = $gibbonFinanceInvoiceeID;
                $whereSched .= ' AND gibbonFinanceInvoice.gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID1';
                $data['gibbonFinanceInvoiceeID2'] = $gibbonFinanceInvoiceeID;
                $whereAdHoc .= ' AND gibbonFinanceInvoice.gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID2';
                $data['gibbonFinanceInvoiceeID3'] = $gibbonFinanceInvoiceeID;
                $whereNotPending .= ' AND gibbonFinanceInvoice.gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID3';
            }
            if ($monthOfIssue != '') {
                $data['monthOfIssue1'] = "%-$monthOfIssue-%";
                $whereSched .= ' AND gibbonFinanceInvoice.invoiceIssueDate LIKE :monthOfIssue1';
                $data['monthOfIssue2'] = "%-$monthOfIssue-%";
                $whereAdHoc .= ' AND gibbonFinanceInvoice.invoiceIssueDate LIKE :monthOfIssue2';
                $data['monthOfIssue3'] = "%-$monthOfIssue-%";
                $whereNotPending .= ' AND gibbonFinanceInvoice.invoiceIssueDate LIKE :monthOfIssue3';
            }
            if ($gibbonFinanceBillingScheduleID != '') {
                if ($gibbonFinanceBillingScheduleID == 'Ad Hoc') {
                    $data['billingScheduleType1'] = 'Ah Hoc';
                    $whereSched .= ' AND gibbonFinanceInvoice.billingScheduleType=:billingScheduleType1';
                    $data['billingScheduleType2'] = 'Ad Hoc';
                    $whereAdHoc .= ' AND gibbonFinanceInvoice.billingScheduleType=:billingScheduleType2';
                    $data['billingScheduleType3'] = 'Ad Hoc';
                    $whereNotPending .= ' AND gibbonFinanceInvoice.billingScheduleType=:billingScheduleType3';
                } elseif ($gibbonFinanceBillingScheduleID != '') {
                    $data['gibbonFinanceBillingScheduleID1'] = $gibbonFinanceBillingScheduleID;
                    $whereSched .= ' AND gibbonFinanceInvoice.gibbonFinanceBillingScheduleID=:gibbonFinanceBillingScheduleID1';
                    $data['gibbonFinanceBillingScheduleID2'] = $gibbonFinanceBillingScheduleID;
                    $whereAdHoc .= ' AND gibbonFinanceInvoice.gibbonFinanceBillingScheduleID=:gibbonFinanceBillingScheduleID2';
                    $data['gibbonFinanceBillingScheduleID3'] = $gibbonFinanceBillingScheduleID;
                    $whereNotPending .= ' AND gibbonFinanceInvoice.gibbonFinanceBillingScheduleID=:gibbonFinanceBillingScheduleID3';
                }
            }
            if ($gibbonFinanceFeeCategoryID != '') {
                $data['gibbonFinanceFeeCategoryID1'] = '%'.$gibbonFinanceFeeCategoryID.'%';
                $whereSched .= ' AND gibbonFinanceInvoice.gibbonFinanceFeeCategoryIDList LIKE :gibbonFinanceFeeCategoryID1';
                $data['gibbonFinanceFeeCategoryID2'] = '%'.$gibbonFinanceFeeCategoryID.'%';
                $whereAdHoc .= ' AND gibbonFinanceInvoice.gibbonFinanceFeeCategoryIDList LIKE :gibbonFinanceFeeCategoryID2';
                $data['gibbonFinanceFeeCategoryID3'] = '%'.$gibbonFinanceFeeCategoryID.'%';
                $whereNotPending .= ' AND gibbonFinanceInvoice.gibbonFinanceFeeCategoryIDList LIKE :gibbonFinanceFeeCategoryID3';
            }

            //SQL for billing schedule AND pending
            $sql = "(SELECT gibbonFinanceInvoice.gibbonFinanceInvoiceID, surname, preferredName, gibbonFinanceInvoice.invoiceTo, gibbonFinanceInvoice.status, gibbonFinanceInvoice.invoiceIssueDate, gibbonFinanceBillingSchedule.invoiceDueDate, paidDate, paidAmount, gibbonFinanceBillingSchedule.name AS billingSchedule, NULL AS billingScheduleExtra, notes, gibbonRollGroup.name AS rollGroup FROM gibbonFinanceInvoice JOIN gibbonFinanceBillingSchedule ON (gibbonFinanceInvoice.gibbonFinanceBillingScheduleID=gibbonFinanceBillingSchedule.gibbonFinanceBillingScheduleID) JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoice.gibbonFinanceInvoiceeID=gibbonFinanceInvoicee.gibbonFinanceInvoiceeID) JOIN gibbonPerson ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonFinanceInvoice.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND billingScheduleType='Scheduled' AND gibbonFinanceInvoice.status='Pending' $whereSched)";
            $sql .= ' UNION ';
            //SQL for Ad Hoc AND pending
            $sql .= "(SELECT gibbonFinanceInvoice.gibbonFinanceInvoiceID, surname, preferredName, gibbonFinanceInvoice.invoiceTo, gibbonFinanceInvoice.status, invoiceIssueDate, invoiceDueDate, paidDate, paidAmount, 'Ad Hoc' AS billingSchedule, NULL AS billingScheduleExtra, notes, gibbonRollGroup.name AS rollGroup FROM gibbonFinanceInvoice JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoice.gibbonFinanceInvoiceeID=gibbonFinanceInvoicee.gibbonFinanceInvoiceeID) JOIN gibbonPerson ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)  WHERE gibbonFinanceInvoice.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND billingScheduleType='Ad Hoc' AND gibbonFinanceInvoice.status='Pending' $whereAdHoc)";
            $sql .= ' UNION ';
            //SQL for NOT Pending
            $sql .= "(SELECT gibbonFinanceInvoice.gibbonFinanceInvoiceID, surname, preferredName, gibbonFinanceInvoice.invoiceTo, gibbonFinanceInvoice.status, gibbonFinanceInvoice.invoiceIssueDate, gibbonFinanceInvoice.invoiceDueDate, paidDate, paidAmount, billingScheduleType AS billingSchedule, gibbonFinanceBillingSchedule.name AS billingScheduleExtra, notes, gibbonRollGroup.name AS rollGroup FROM gibbonFinanceInvoice LEFT JOIN gibbonFinanceBillingSchedule ON (gibbonFinanceInvoice.gibbonFinanceBillingScheduleID=gibbonFinanceBillingSchedule.gibbonFinanceBillingScheduleID) JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoice.gibbonFinanceInvoiceeID=gibbonFinanceInvoicee.gibbonFinanceInvoiceeID) JOIN gibbonPerson ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)  WHERE gibbonFinanceInvoice.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND NOT gibbonFinanceInvoice.status='Pending' $whereNotPending)";
            $sql .= " ORDER BY FIND_IN_SET(status, 'Pending,Issued,Paid,Refunded,Cancelled'), invoiceIssueDate, surname, preferredName";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>".$e->getMessage().'</div>';
        }


        echo '<h3>';
        echo __($guid, 'View');
        echo "<span style='font-weight: normal; font-style: italic; font-size: 55%'> ".sprintf(__($guid, '%1$s records(s) in current view'), $result->rowCount()).'</span>';
        echo '</h3>';
        
        echo "<div class='linkTop'>";
        echo "<a style='margin-right: 3px' href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/invoices_manage_add.php&gibbonSchoolYearID=$gibbonSchoolYearID&status=$status&gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID&monthOfIssue=$monthOfIssue&gibbonFinanceBillingScheduleID=$gibbonFinanceBillingScheduleID&gibbonFinanceFeeCategoryID=$gibbonFinanceFeeCategoryID'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new_multi.png'/></a><br/>";
        echo '</div>'; 

        $linkParams = array(
            'gibbonSchoolYearID'             => $gibbonSchoolYearID,
            'status'                         => $status,
            'gibbonFinanceInvoiceeID'        => $gibbonFinanceInvoiceeID,
            'monthOfIssue'                   => $monthOfIssue,
            'gibbonFinanceBillingScheduleID' => $gibbonFinanceBillingScheduleID,
            'gibbonFinanceFeeCategoryID'     => $gibbonFinanceFeeCategoryID,
        );

        $form = BulkActionForm::create('bulkAction', $_SESSION[$guid]['absoluteURL'] . '/modules/' . $_SESSION[$guid]['module'] . '/invoices_manage_processBulk.php?'.http_build_query($linkParams));

        $form->addHiddenValue('address', $_SESSION[$guid]['address']);

        $bulkActions = array('export' => __('Export'));
        if ($status == 'Pending') {
            $bulkActions = array('delete' => __('Delete'), 'issue' => __('Issue'), 'issueNoEmail' => __('Issue (Without Email)')) + $bulkActions;
        } else if ($status == 'Issued - Overdue') {
            $bulkActions = array('reminders' => __('Issue Reminders')) + $bulkActions;
        }

        $row = $form->addBulkActionRow($bulkActions);
            $row->addSubmit(__('Go'));

        $table = $form->addRow()->addTable()->setClass('colorOddEven fullWidth');

        $header = $table->addHeaderRow();
            $header->addContent(__('Student'))->append('<br/><small><i>'.__('Invoice To').'</i></small>');
            $header->addContent(__('Roll Group'));
            $header->addContent(__('Status'));
            $header->addContent(__('Schedule'));
            $header->addContent(__('Total'))
                ->append(' <small><i>('.$_SESSION[$guid]['currency'].')</i></small>')
                ->append('<br/><small><i>'.__('Paid').' ('.$_SESSION[$guid]['currency'].')</i></small>');
            $header->addContent(__('Issue Date'))->append('<br/><small><i>'.__('Due Date').'</i></small>');
            $header->addContent(__('Actions'))->addClass('shortWidth');
            $header->addCheckAll();

        $invoices = $result->rowCount() > 0? $result->fetchAll() : array();

        if (count($invoices) == 0) {
            $table->addRow()->addTableCell(__('There are no records to display.'))->colSpan(8);
        }

        foreach ($invoices as $invoice) {
            $statusExtra = '';
            if ($invoice['status'] == 'Issued' and $invoice['invoiceDueDate'] < date('Y-m-d')) {
                $statusExtra = 'Overdue';
            } else if ($invoice['status'] == 'Paid' and $invoice['invoiceDueDate'] < $invoice['paidDate']) {
                $statusExtra = 'Late';
            }
            
            $rowClass = ($invoice['status'] == 'Paid')? 'current' : (($invoice['status'] == 'Issued' and $statusExtra == 'Overdue')? 'error' : '');

            $totalFee = getInvoiceTotalFee($pdo, $invoice['gibbonFinanceInvoiceID'], $invoice['status']);

            $row = $table->addRow()->addClass($rowClass);
                $row->addContent(formatName('', htmlPrep($invoice['preferredName']), htmlPrep($invoice['surname']), 'Student', true))
                    ->wrap('<b>', '</b>')
                    ->append('<br/><span class="small emphasis">'.$invoice['invoiceTo'].'</span>');
                $row->addContent($invoice['rollGroup']);
                $row->addContent($invoice['status'])->append(!empty($statusExtra)? ' - '.$statusExtra : '');
                $row->addContent(!empty($invoice['billingScheduleExtra'])? $invoice['billingScheduleExtra'] : $invoice['billingSchedule']);
                if (!is_null($totalFee)) {
                    $fee = $row->addContent(number_format($totalFee, 2, '.', ','));
                    if (!empty($invoice['paidAmount'])) {
                        $fee->setClass($invoice['paidAmount'] != $totalFee ? 'textOverBudget' : '')
                            ->append('<br/><span class="small emphasis">'.number_format($invoice['paidAmount'], 2, '.', ',').'</span>');
                    }
                } else {
                    $row->addContent('');
                }
                $row->addContent(!is_null($invoice['invoiceIssueDate'])? dateConvertBack($guid, $invoice['invoiceIssueDate']) : __('N/A') )
                    ->append('<br/><span class="small emphasis">'.dateConvertBack($guid, $invoice['invoiceDueDate']).'</span>');

                $col = $row->addColumn()->addClass('inline');
                    $col->if($invoice['status'] != 'Cancelled' && $invoice['status'] != 'Refunded')
                        ->addWebLink('<img title="'.__('Edit').'" src="./themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/config.png" style="margin-right:4px;" />')
                        ->setURL($_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/invoices_manage_edit.php')
                        ->addParam('gibbonFinanceInvoiceID', $invoice['gibbonFinanceInvoiceID'])
                        ->addParams($linkParams);

                    $col->if($invoice['status'] == 'Pending')
                        ->addWebLink('<img title="'.__('Issue').'" src="./themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/page_right.png" style="margin-right:4px;" />')
                        ->setURL($_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/invoices_manage_issue.php')
                        ->addParam('gibbonFinanceInvoiceID', $invoice['gibbonFinanceInvoiceID'])
                        ->addParams($linkParams);

                    $col->if($invoice['status'] == 'Pending')
                        ->addWebLink('<img title="'.__('Delete').'" src="./themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/garbage.png" style="margin-right:4px;" />')
                        ->setURL($_SESSION[$guid]['absoluteURL'].'/fullscreen.php?q=/modules/'.$_SESSION[$guid]['module'].'/invoices_manage_delete.php&width=650&height=135')
                        ->addParam('gibbonFinanceInvoiceID', $invoice['gibbonFinanceInvoiceID'])
                        ->addParams($linkParams)
                        ->addClass('thickbox');

                    $col->if($invoice['status'] == 'Pending')
                        ->addWebLink('<img title="'.__('Preview Invoice').'" src="./themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/print.png" style="margin-right:4px;" />')
                        ->setURL($_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/invoices_manage_print_print.php&type=invoice')
                        ->addParam('gibbonFinanceInvoiceID', $invoice['gibbonFinanceInvoiceID'])
                        ->addParams($linkParams)
                        ->addParams('preview', 'true');

                    $col->if($invoice['status'] != 'Pending')
                        ->addWebLink('<img title="'.__('Print Invoices, Receipts & Reminders').'" src="./themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/print.png" style="margin-right:4px;" />')
                        ->setURL($_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/invoices_manage_print.php')
                        ->addParam('gibbonFinanceInvoiceID', $invoice['gibbonFinanceInvoiceID'])
                        ->addParams($linkParams);

                    $col->if(!empty($invoice['notes']))
                        ->addWebLink('<img title="'.__('View Notes').'" src="./themes/'.$_SESSION[$guid]['gibbonThemeName'].'/img/page_down.png" style="margin-right:4px;"/>')
                        ->setURL('#')->onClick('return false;')->addClass('invoiceNotesView');

                $row->addCheckbox('gibbonFinanceInvoiceIDs[]')->setValue($invoice['gibbonFinanceInvoiceID'])->setClass('textCenter');

                if (!empty($invoice['notes'])) {
                    $table->addRow()->addClass('invoiceNotes')->addTableCell(htmlPrep($invoice['notes']))->colSpan(8);
                }
                
        }

        echo $form->getOutput();
        echo '<br/>';
    }
}
