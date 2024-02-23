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

if (isActionAccessible($guid, $connection2, '/modules/Finance/invoices_view.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        $entryCount = 0;

        $page->breadcrumbs->add(__('View Invoices'));

        if ($highestAction=="View Invoices_myChildren") {
            //Test data access field for permission
            
                $data = array('gibbonPersonID' => $session->get('gibbonPersonID'));
                $sql = "SELECT * FROM gibbonFamilyAdult WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y'";
                $result = $connection2->prepare($sql);
                $result->execute($data);

            if ($result->rowCount() < 1) {
                echo $page->getBlankSlate();
            } else {
                //Get child list
                $count = 0;
                $options = array();
                while ($row = $result->fetch()) {
                    
                        $dataChild = array('gibbonFamilyID' => $row['gibbonFamilyID'], 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
                        $sqlChild = "SELECT * FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) WHERE gibbonFamilyID=:gibbonFamilyID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName ";
                        $resultChild = $connection2->prepare($sqlChild);
                        $resultChild->execute($dataChild);
                    while ($rowChild = $resultChild->fetch()) {
                        $options[$rowChild['gibbonPersonID']]=Format::name('', $rowChild['preferredName'], $rowChild['surname'], 'Student', true);
                    }
                }

                if (count($options) == 0) {
                    echo $page->getBlankSlate();
                } elseif (count($options) == 1) {
                    $_GET['search'] = key($options);
                } else {
                    echo '<h2>';
                    echo 'Choose Student';
                    echo '</h2>';

                    $gibbonPersonID = (isset($_GET['search']))? $_GET['search'] : null;

                    $form = Form::create('filter', $session->get('absoluteURL').'/index.php', 'get');
                    $form->setClass('noIntBorder fullWidth standardForm');

                    $form->addHiddenValue('q', '/modules/Finance/invoices_view.php');
                    $form->addHiddenValue('address', $session->get('address'));

                    $row = $form->addRow();
                        $row->addLabel('search', __('Student'));
                        $row->addSelect('search')->fromArray($options)->selected($gibbonPersonID)->placeholder();

                    $row = $form->addRow();
                        $row->addSearchSubmit($session);

                    echo $form->getOutput();
                }

                $gibbonPersonID = null;
                if (isset($_GET['search'])) {
                    $gibbonPersonID = $_GET['search'] ?? '';
                }
            }
        } else if ($highestAction=="View Invoices_mine") {
            $gibbonPersonID = $session->get("gibbonPersonID");
            $options = [$gibbonPersonID];
        }

        if (!empty($gibbonPersonID) and count($options) > 0) {
            //Confirm access to this student
            try {
                if ($highestAction=="View Invoices_myChildren") {
                    $dataChild = array('gibbonPersonID' => $gibbonPersonID, 'gibbonPersonID2' => $session->get('gibbonPersonID'));
                    $sqlChild = "SELECT gibbonPerson.gibbonPersonID FROM gibbonFamilyChild JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonFamilyChild.gibbonPersonID=:gibbonPersonID AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID2 AND childDataAccess='Y'";
                } else if ($highestAction=="View Invoices_mine") {
                    $dataChild = array('gibbonPersonID' => $gibbonPersonID);
                    $sqlChild = "SELECT gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonPersonID=:gibbonPersonID" ;
                }
                $resultChild = $connection2->prepare($sqlChild);
                $resultChild->execute($dataChild);
            } catch (PDOException $e) {
            }
            if ($resultChild->rowCount() < 1) {
                $page->addError(__('The selected record does not exist, or you do not have access to it.'));
            } else {
                $rowChild = $resultChild->fetch();

                $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');

                if ($gibbonSchoolYearID != '') {
                   $page->navigator->addSchoolYearNavigation($gibbonSchoolYearID, ['search' => $gibbonPersonID]);

                    //Add in filter wheres
                    $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonSchoolYearID2' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID);
                    //SQL for NOT Pending
                    $sql = "SELECT gibbonFinanceInvoice.gibbonFinanceInvoiceID, surname, preferredName, gibbonFinanceInvoice.invoiceTo, gibbonFinanceInvoice.status, gibbonFinanceInvoice.invoiceIssueDate, gibbonFinanceInvoice.invoiceDueDate, paidDate, paidAmount, billingScheduleType AS billingSchedule, gibbonFinanceBillingSchedule.name AS billingScheduleExtra, notes, gibbonFormGroup.name AS formGroup FROM gibbonFinanceInvoice LEFT JOIN gibbonFinanceBillingSchedule ON (gibbonFinanceInvoice.gibbonFinanceBillingScheduleID=gibbonFinanceBillingSchedule.gibbonFinanceBillingScheduleID) JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoice.gibbonFinanceInvoiceeID=gibbonFinanceInvoicee.gibbonFinanceInvoiceeID) JOIN gibbonPerson ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) WHERE gibbonFinanceInvoice.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND NOT gibbonFinanceInvoice.status='Pending' AND gibbonFinanceInvoicee.gibbonPersonID=:gibbonPersonID ORDER BY invoiceIssueDate, surname, preferredName";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);

                    if ($result->rowCount() < 1) {
                        echo '<h3>';
                        echo __('View');
                        echo '</h3>';

                        echo $page->getBlankSlate();
                    } else {
                        echo '<h3>';
                        echo __('View');
                        echo "<span style='font-weight: normal; font-style: italic; font-size: 55%'> ".sprintf(__('%1$s invoice(s) in current view'), $result->rowCount()).'</span>';
                        echo '</h3>';

                        echo "<table cellspacing='0' style='width: 100%'>";
                        echo "<tr class='head'>";
                        echo "<th style='width: 110px'>";
                        echo __('Student').'<br/>';
                        echo "<span style='font-style: italic; font-size: 85%'>".__('Invoice To').'</span>';
                        echo '</th>';
                        echo "<th style='width: 110px'>";
                        echo __('Form Group');
                        echo '</th>';
                        echo "<th style='width: 100px'>";
                        echo __('Status');
                        echo '</th>';
                        echo "<th style='width: 90px'>";
                        echo __('Schedule');
                        echo '</th>';
                        echo "<th style='width: 120px'>";
                        echo __('Total')." <span style='font-style: italic; font-size: 75%'>(".$session->get('currency').')</span><br/>';
                        echo "<span style='font-style: italic; font-size: 75%'>".__('Paid').' ('.$session->get('currency').')</span>';
                        echo '</th>';
                        echo "<th style='width: 80px'>";
                        echo __('Issue Date').'<br/>';
                        echo "<span style='font-style: italic; font-size: 75%'>".__('Due Date').'</span>';
                        echo '</th>';
                        echo "<th style='width: 140px'>";
                        echo __('Actions');
                        echo '</th>';
                        echo '</tr>';

                        $count = 0;
                        $rowNum = 'odd';
                        while ($row = $result->fetch()) {
                            if ($count % 2 == 0) {
                                $rowNum = 'even';
                            } else {
                                $rowNum = 'odd';
                            }
                            ++$count;

                            //Work out extra status information
                            $statusExtra = '';
                            if ($row['status'] == 'Issued' and $row['invoiceDueDate'] < date('Y-m-d')) {
                                $statusExtra = 'Overdue';
                            }
                            if ($row['status'] == 'Paid' and $row['invoiceDueDate'] < $row['paidDate']) {
                                $statusExtra = 'Late';
                            }

							//Color row by status
							if ($row['status'] == 'Paid') {
								$rowNum = 'current';
							}
                            if ($row['status'] == 'Issued' and $statusExtra == 'Overdue') {
                                $rowNum = 'error';
                            }

                            echo "<tr class=$rowNum>";
                            echo '<td>';
                            echo '<b>'.Format::name('', htmlPrep($row['preferredName']), htmlPrep($row['surname']), 'Student', true).'</b><br/>';
                            echo "<span style='font-style: italic; font-size: 85%'>".$row['invoiceTo'].'</span>';
                            echo '</td>';
                            echo '<td>';
                            echo $row['formGroup'];
                            echo '</td>';
                            echo '<td>';
                            echo $row['status'];
                            if ($statusExtra != '') {
                                echo " - $statusExtra";
                            }
                            echo '</td>';
                            echo '<td>';
                            if ($row['billingScheduleExtra'] != '') {
                                echo $row['billingScheduleExtra'];
                            } else {
                                echo $row['billingSchedule'];
                            }
                            echo '</td>';
                            echo '<td>';
							//Calculate total value
							$totalFee = 0;
                            $feeError = false;
                            try {
                                $dataTotal = array('gibbonFinanceInvoiceID' => $row['gibbonFinanceInvoiceID']);
                                if ($row['status'] == 'Pending') {
                                    $sqlTotal = 'SELECT gibbonFinanceInvoiceFee.fee AS fee, gibbonFinanceFee.fee AS fee2 FROM gibbonFinanceInvoiceFee LEFT JOIN gibbonFinanceFee ON (gibbonFinanceInvoiceFee.gibbonFinanceFeeID=gibbonFinanceFee.gibbonFinanceFeeID) WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID';
                                } else {
                                    $sqlTotal = 'SELECT gibbonFinanceInvoiceFee.fee AS fee, NULL AS fee2 FROM gibbonFinanceInvoiceFee WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID';
                                }
                                $resultTotal = $connection2->prepare($sqlTotal);
                                $resultTotal->execute($dataTotal);
                            } catch (PDOException $e) {
                                echo '<i>Error calculating total</i>';
                                $feeError = true;
                            }
                            while ($rowTotal = $resultTotal->fetch()) {
                                if (is_numeric($rowTotal['fee2'])) {
                                    $totalFee += $rowTotal['fee2'];
                                } else {
                                    $totalFee += $rowTotal['fee'];
                                }
                            }
                            if ($feeError == false) {
                                if (substr($session->get('currency'), 4) != '') {
                                    echo substr($session->get('currency'), 4).' ';
                                }
                                echo number_format($totalFee, 2, '.', ',').'<br/>';
                                if ($row['paidAmount'] != '') {
                                    $styleExtra = '';
                                    if ($row['paidAmount'] != $totalFee) {
                                        $styleExtra = 'color: #c00;';
                                    }
                                    echo "<span style='$styleExtra font-style: italic; font-size: 85%'>";
                                    if (substr($session->get('currency'), 4) != '') {
                                        echo substr($session->get('currency'), 4).' ';
                                    }
                                    echo number_format($row['paidAmount'], 2, '.', ',').'</span>';
                                }
                            }
                            echo '</td>';
                            echo '<td>';
                            if (is_null($row['invoiceIssueDate'])) {
                                echo 'NA<br/>';
                            } else {
                                echo Format::date($row['invoiceIssueDate']).'<br/>';
                            }
                            echo "<span style='font-style: italic; font-size: 75%'>".Format::date($row['invoiceDueDate']).'</span>';
                            echo '</td>';
                            echo '<td>';
                            if ($row['status'] == 'Issued') {
                                echo "<a target='_blank' href='".$session->get('absoluteURL').'/report.php?q=/modules/'.$session->get('module').'/invoices_view_print.php&type=invoice&gibbonFinanceInvoiceID='.$row['gibbonFinanceInvoiceID']."&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonPersonID=$gibbonPersonID'><img title='".__('Print')."' src='./themes/".$session->get('gibbonThemeName')."/img/print.png'/></a>";
                            } elseif ($row['status'] == 'Paid' or $row['status'] == 'Paid - Partial') {
                                echo "<a target='_blank' href='".$session->get('absoluteURL').'/report.php?q=/modules/'.$session->get('module').'/invoices_view_print.php&type=receipt&gibbonFinanceInvoiceID='.$row['gibbonFinanceInvoiceID']."&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonPersonID=$gibbonPersonID'><img title='".__('Print')."' src='./themes/".$session->get('gibbonThemeName')."/img/print.png'/></a>";
                            }
                            echo "<script type='text/javascript'>";
                            echo '$(document).ready(function(){';
                            echo "\$(\".comment-$count\").hide();";
                            echo "\$(\".show_hide-$count\").fadeIn(1000);";
                            echo "\$(\".show_hide-$count\").click(function(){";
                            echo "\$(\".comment-$count\").fadeToggle(1000);";
                            echo '});';
                            echo '});';
                            echo '</script>';
                            if (!empty($row['notes'])) {
                                echo "<a title='View Notes' class='show_hide-$count' onclick='false' href='#'><img style='margin-left: 5px' src='".$session->get('absoluteURL').'/themes/'.$session->get('gibbonThemeName')."/img/page_down.png' alt='".__('Show Comment')."' onclick='return false;' /></a>";
                            }
                            echo '</td>';
                            echo '</tr>';
                            if (!empty($row['notes'])) {
                                echo "<tr class='comment-$count' id='comment-$count'>";
                                echo '<td colspan=7>';
                                echo $row['notes'];
                                echo '</td>';
                                echo '</tr>';
                            }
                        }
                        echo '</table>';
                    }
                }
            }
        }
    }
}
?>
