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

@session_start();

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Finance/invoices_manage_print.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Check if school year specified
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
    $gibbonFinanceInvoiceID = $_GET['gibbonFinanceInvoiceID'];
    $status = $_GET['status'];
    $gibbonFinanceInvoiceeID = $_GET['gibbonFinanceInvoiceeID'];
    $monthOfIssue = $_GET['monthOfIssue'];
    $gibbonFinanceBillingScheduleID = $_GET['gibbonFinanceBillingScheduleID'];

    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Finance/invoices_manage.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID']."&gibbonFinanceInvoiceID=$gibbonFinanceInvoiceID&gibbonSchoolYearID=$gibbonSchoolYearID&status=$status&gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID&monthOfIssue=$monthOfIssue&gibbonFinanceBillingScheduleID=$gibbonFinanceBillingScheduleID'>".__($guid, 'Manage Invoices')."</a> > </div><div class='trailEnd'>".__($guid, 'Print Invoices, Receipts & Reminders').'</div>';
    echo '</div>';

    if ($gibbonFinanceInvoiceID == '' or $gibbonSchoolYearID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonFinanceInvoiceID' => $gibbonFinanceInvoiceID);
            $sql = 'SELECT * FROM gibbonFinanceInvoice WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID';
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
            $row = $result->fetch();

            if ($status != '' or $gibbonFinanceInvoiceeID != '' or $monthOfIssue != '' or $gibbonFinanceBillingScheduleID != '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Finance/invoices_manage.php&gibbonSchoolYearID=$gibbonSchoolYearID&status=$status&gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID&monthOfIssue=$monthOfIssue&gibbonFinanceBillingScheduleID=$gibbonFinanceBillingScheduleID'>".__($guid, 'Back to Search Results').'</a>';
                echo '</div>';
            }

            if ($row['status'] == 'Pending') {
                echo "<div class='error'>";
                echo __($guid, 'There is nothing to print, as the invoice has yet to be issued.');
                echo '</div>';
            } else {
                echo "<table cellspacing='0' style='width: 100%'>";
                echo "<tr class='head'>";
                echo '<th>';
                echo __($guid, 'Item');
                echo '</th>';
                echo "<th style='width: 120px'>";
                echo __($guid, 'Actions');
                echo '</th>';
                echo '</tr>';

                $count = 0;
                $rowNum = 'even';

                ?>
					<tr class='<?php echo $rowNum ?>'>
						<td>
							<b><?php echo __($guid, 'Invoice') ?></b><br/>
						</td>
						<td class="left">
							<?php
                            echo "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/report.php?q=/modules/'.$_SESSION[$guid]['module'].'/invoices_manage_print_print.php&type=invoice&gibbonFinanceInvoiceID='.$row['gibbonFinanceInvoiceID']."&gibbonSchoolYearID=$gibbonSchoolYearID'>".__($guid, 'Print')."<img style='margin-left: 5px' title='".__($guid, 'Print')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/print.png'/></a>"; ?>
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
                    if ($row['status'] == 'Issued') {
                        if ($row['reminderCount'] >= 0) {
                            ?>
							<tr class='<?php echo $rowNum ?>'>
								<td>
									<b><?php echo __($guid, 'Reminder 1') ?></b><br/>
								</td>
								<td class="left">
									<?php
                                    echo "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/report.php?q=/modules/'.$_SESSION[$guid]['module'].'/invoices_manage_print_print.php&type=reminder1&gibbonFinanceInvoiceID='.$row['gibbonFinanceInvoiceID']."&gibbonSchoolYearID=$gibbonSchoolYearID'>".__($guid, 'Print')."<img style='margin-left: 5px' title='".__($guid, 'Print')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/print.png'/></a>";
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
									<b><?php echo __($guid, 'Reminder 2') ?></b><br/>
								</td>
								<td class="left">
									<?php
                                    echo "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/report.php?q=/modules/'.$_SESSION[$guid]['module'].'/invoices_manage_print_print.php&type=reminder2&gibbonFinanceInvoiceID='.$row['gibbonFinanceInvoiceID']."&gibbonSchoolYearID=$gibbonSchoolYearID'>".__($guid, 'Print')."<img style='margin-left: 5px' title='".__($guid, 'Print')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/print.png'/></a>";
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
									<b><?php echo __($guid, 'Reminder 3') ?></b><br/>
								</td>
								<td class="left">
									<?php
                                    echo "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/report.php?q=/modules/'.$_SESSION[$guid]['module'].'/invoices_manage_print_print.php&type=reminder3&gibbonFinanceInvoiceID='.$row['gibbonFinanceInvoiceID']."&gibbonSchoolYearID=$gibbonSchoolYearID'>".__($guid, 'Print')."<img style='margin-left: 5px' title='".__($guid, 'Print')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/print.png'/></a>";
                            ?>
								</td>
							</tr>
							<?php

                        }
                    }
                	if ($row['status'] == 'Paid' OR $row['status'] == 'Paid - Partial') {
                    //Get individual payments that make up receipt
                        try {
                            $data = array('foreignTable' => 'gibbonFinanceInvoice', 'foreignTableID' => $gibbonFinanceInvoiceID);
                            $sql = 'SELECT gibbonPayment.*, surname, preferredName FROM gibbonPayment JOIN gibbonPerson ON (gibbonPayment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE foreignTable=:foreignTable AND foreignTableID=:foreignTableID ORDER BY timestamp, gibbonPaymentID';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $return .= "<div class='error'>".$e->getMessage().'</div>';
                        }

                    if ($result->rowCount() < 1) {
                        ?>
							<tr class='<?php echo $rowNum ?>'>
								<td>
									<b><?php echo __($guid, 'Receipt') ?></b><br/>
								</td>
								<td class="left">
									<?php
                                    echo "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/report.php?q=/modules/'.$_SESSION[$guid]['module'].'/invoices_manage_print_print.php&type=receipt&gibbonFinanceInvoiceID='.$row['gibbonFinanceInvoiceID']."&gibbonSchoolYearID=$gibbonSchoolYearID'>".__($guid, 'Print')."<img style='margin-left: 5px' title='".__($guid, 'Print')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/print.png'/></a>";
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
									<b><?php echo sprintf(__($guid, 'Receipt %1$s'), ($count2 + 1)) ?></b><br/>
								</td>
								<td class="left">
									<?php
                                    echo "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/report.php?q=/modules/'.$_SESSION[$guid]['module']."/invoices_manage_print_print.php&type=receipt&gibbonFinanceInvoiceID=$gibbonFinanceInvoiceID&gibbonSchoolYearID=$gibbonSchoolYearID&receiptNumber=$count2'>".__($guid, 'Print')."<img style='margin-left: 5px' title='".__($guid, 'Print')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/print.png'/></a>";
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
