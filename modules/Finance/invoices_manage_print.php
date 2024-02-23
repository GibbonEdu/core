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

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Finance/invoices_manage_print.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
    $gibbonFinanceInvoiceID = $_GET['gibbonFinanceInvoiceID'] ?? '';
    $status = $_GET['status'] ?? '';
    $gibbonFinanceInvoiceeID = $_GET['gibbonFinanceInvoiceeID'] ?? '';
    $monthOfIssue = $_GET['monthOfIssue'] ?? '';
    $gibbonFinanceBillingScheduleID = $_GET['gibbonFinanceBillingScheduleID'] ?? '';
    $gibbonFinanceFeeCategoryID = $_GET['gibbonFinanceFeeCategoryID'] ?? '';

    //Proceed!
    $urlParams = compact('gibbonSchoolYearID', 'status', 'gibbonFinanceInvoiceeID', 'monthOfIssue', 'gibbonFinanceBillingScheduleID', 'gibbonFinanceFeeCategoryID'); 

    //Proceed!
    $page->breadcrumbs
        ->add(__('Manage Invoices'), 'invoices_manage.php', $urlParams)
        ->add(__('Print Invoices, Receipts & Reminders'));    

    //Check if gibbonFinanceInvoiceID and gibbonSchoolYearID specified
    if ($gibbonFinanceInvoiceID == '' or $gibbonSchoolYearID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonFinanceInvoiceID' => $gibbonFinanceInvoiceID);
        $sql = 'SELECT * FROM gibbonFinanceInvoice WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID';
        $result = $connection2->prepare($sql);
        $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $row = $result->fetch();

            if ($status != '' or $gibbonFinanceInvoiceeID != '' or $monthOfIssue != '' or $gibbonFinanceBillingScheduleID != '') {
                $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Finance', 'invoices_manage.php')->withQueryParams($urlParams));
            }

            if ($row['status'] == 'Pending') {
                echo "<div class='error'>";
                echo __('There is nothing to print, as the invoice has yet to be issued.');
                echo '</div>';
            } else {
                echo "<table cellspacing='0' style='width: 100%'>";
                echo "<tr class='head'>";
                echo '<th>';
                echo __('Item');
                echo '</th>';
                echo "<th style='width: 120px'>";
                echo __('Actions');
                echo '</th>';
                echo '</tr>';

                $count = 0;
                $rowNum = 'even';

                ?>
					<tr class='<?php echo $rowNum ?>'>
						<td>
							<b><?php echo __('Invoice') ?></b><br/>
						</td>
						<td class="left">
							<?php
                            echo "<a target='_blank' href='".$session->get('absoluteURL').'/report.php?q=/modules/'.$session->get('module').'/invoices_manage_print_print.php&type=invoice&gibbonFinanceInvoiceID='.$row['gibbonFinanceInvoiceID']."&gibbonSchoolYearID=$gibbonSchoolYearID'>".__('Print')."<img style='margin-left: 5px' title='".__('Print')."' src='./themes/".$session->get('gibbonThemeName')."/img/print.png'/></a>"; ?>
						</td>
					</tr>
					<?php
                    ++$count;
                if ($count % 2 == 0) {
                    $rowNum = 'even';
                } else {
                    $rowNum = 'odd';
                }
                ?>
					<?php
                    if ($row['status'] == 'Issued' || $row['status'] == 'Paid - Partial') {
                        if ($row['reminderCount'] >= 0) {
                            ?>
							<tr class='<?php echo $rowNum ?>'>
								<td>
									<b><?php echo __('Reminder 1') ?></b><br/>
								</td>
								<td class="left">
									<?php
                                    echo "<a target='_blank' href='".$session->get('absoluteURL').'/report.php?q=/modules/'.$session->get('module').'/invoices_manage_print_print.php&type=reminder1&gibbonFinanceInvoiceID='.$row['gibbonFinanceInvoiceID']."&gibbonSchoolYearID=$gibbonSchoolYearID'>".__('Print')."<img style='margin-left: 5px' title='".__('Print')."' src='./themes/".$session->get('gibbonThemeName')."/img/print.png'/></a>";
                            ?>
								</td>
							</tr>
							<?php

                        }
                        ++$count;
                        if ($count % 2 == 0) {
                            $rowNum = 'even';
                        } else {
                            $rowNum = 'odd';
                        }
                        if ($row['reminderCount'] >= 1) {
                            ?>
							<tr class='<?php echo $rowNum ?>'>
								<td>
									<b><?php echo __('Reminder 2') ?></b><br/>
								</td>
								<td class="left">
									<?php
                                    echo "<a target='_blank' href='".$session->get('absoluteURL').'/report.php?q=/modules/'.$session->get('module').'/invoices_manage_print_print.php&type=reminder2&gibbonFinanceInvoiceID='.$row['gibbonFinanceInvoiceID']."&gibbonSchoolYearID=$gibbonSchoolYearID'>".__('Print')."<img style='margin-left: 5px' title='".__('Print')."' src='./themes/".$session->get('gibbonThemeName')."/img/print.png'/></a>";
                            ?>
								</td>
							</tr>
							<?php

                        }
                        ++$count;
                        if ($count % 2 == 0) {
                            $rowNum = 'even';
                        } else {
                            $rowNum = 'odd';
                        }
                        if ($row['reminderCount'] >= 2) {
                            ?>
							<tr class='<?php echo $rowNum ?>'>
								<td>
									<b><?php echo __('Reminder 3') ?></b><br/>
								</td>
								<td class="left">
									<?php
                                    echo "<a target='_blank' href='".$session->get('absoluteURL').'/report.php?q=/modules/'.$session->get('module').'/invoices_manage_print_print.php&type=reminder3&gibbonFinanceInvoiceID='.$row['gibbonFinanceInvoiceID']."&gibbonSchoolYearID=$gibbonSchoolYearID'>".__('Print')."<img style='margin-left: 5px' title='".__('Print')."' src='./themes/".$session->get('gibbonThemeName')."/img/print.png'/></a>";
                            ?>
								</td>
							</tr>
							<?php

                        }
                    }
                	if ($row['status'] == 'Paid' OR $row['status'] == 'Paid - Partial' OR $row['status'] == 'Refunded') {
                    //Get individual payments that make up receipt
                        try {
                            $data = array('foreignTable' => 'gibbonFinanceInvoice', 'foreignTableID' => $gibbonFinanceInvoiceID);
                            $sql = 'SELECT gibbonPayment.*, surname, preferredName FROM gibbonPayment JOIN gibbonPerson ON (gibbonPayment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE foreignTable=:foreignTable AND foreignTableID=:foreignTableID ORDER BY timestamp, gibbonPaymentID';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                        }

                    if ($result->rowCount() < 1) {
                        ?>
							<tr class='<?php echo $rowNum ?>'>
								<td>
									<b><?php echo __('Receipt') ?></b><br/>
								</td>
								<td class="left">
									<?php
                                    echo "<a target='_blank' href='".$session->get('absoluteURL').'/report.php?q=/modules/'.$session->get('module').'/invoices_manage_print_print.php&type=receipt&gibbonFinanceInvoiceID='.$row['gibbonFinanceInvoiceID']."&gibbonSchoolYearID=$gibbonSchoolYearID'>".__('Print')."<img style='margin-left: 5px' title='".__('Print')."' src='./themes/".$session->get('gibbonThemeName')."/img/print.png'/></a>";
                        ?>
								</td>
							</tr>
							<?php

                    } else {
                        $count2 = 0;
                        while ($row = $result->fetch()) {
                            if ($count % 2 == 0) {
                                $rowNum = 'even';
                            } else {
                                $rowNum = 'odd';
                            }
                            ?>
							<tr class='<?php echo $rowNum ?>'>
								<td>
									<b><?php echo sprintf(__('Receipt %1$s'), ($count2 + 1)) ?></b><br/>
								</td>
								<td class="left">
									<?php
                                    echo "<a target='_blank' href='".$session->get('absoluteURL').'/report.php?q=/modules/'.$session->get('module')."/invoices_manage_print_print.php&type=receipt&gibbonFinanceInvoiceID=$gibbonFinanceInvoiceID&gibbonSchoolYearID=$gibbonSchoolYearID&receiptNumber=$count2'>".__('Print')."<img style='margin-left: 5px' title='".__('Print')."' src='./themes/".$session->get('gibbonThemeName')."/img/print.png'/></a>";
                                    ?>
								</td>
							</tr>
							<?php
                            ++$count;
                            ++$count2;
                        }
                    }
                }
                echo '</table>';
            }
        }
    }
}
?>
