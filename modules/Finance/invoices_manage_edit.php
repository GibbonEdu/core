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

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Finance/invoices_manage_edit.php') == false) {
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
    $gibbonFinanceFeeCategoryID = $_GET['gibbonFinanceFeeCategoryID'];

    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Finance/invoices_manage.php&gibbonSchoolYearID='.$_GET['gibbonSchoolYearID']."&gibbonFinanceInvoiceID=$gibbonFinanceInvoiceID&gibbonSchoolYearID=$gibbonSchoolYearID&status=$status&gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID&monthOfIssue=$monthOfIssue&gibbonFinanceBillingScheduleID=$gibbonFinanceBillingScheduleID&gibbonFinanceFeeCategoryID=$gibbonFinanceFeeCategoryID'>".__($guid, 'Manage Invoices')."</a> > </div><div class='trailEnd'>".__($guid, 'Edit Invoice').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, array('success0' => 'Your request was completed successfully.', 'success1' => 'Your request was completed successfully, but one or more requested emails could not be sent.', 'error3' => 'Some elements of your request failed, but others were successful.'));
    }

    if ($gibbonFinanceInvoiceID == '' or $gibbonSchoolYearID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        try {
            $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonFinanceInvoiceID' => $gibbonFinanceInvoiceID);
            $sql = 'SELECT gibbonFinanceInvoice.*, companyName, companyContact, companyEmail, companyCCFamily FROM gibbonFinanceInvoice LEFT JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoice.gibbonFinanceInvoiceeID=gibbonFinanceInvoicee.gibbonFinanceInvoiceeID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID';
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
                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Finance/invoices_manage.php&gibbonSchoolYearID=$gibbonSchoolYearID&status=$status&gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID&monthOfIssue=$monthOfIssue&gibbonFinanceBillingScheduleID=$gibbonFinanceBillingScheduleID&gibbonFinanceFeeCategoryID=$gibbonFinanceFeeCategoryID'>".__($guid, 'Back to Search Results').'</a>';
                echo '</div>';
            }
            ?>

			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/invoices_manage_editProcess.php?gibbonSchoolYearID=$gibbonSchoolYearID&status=$status&gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID&monthOfIssue=$monthOfIssue&gibbonFinanceBillingScheduleID=$gibbonFinanceBillingScheduleID&gibbonFinanceFeeCategoryID=$gibbonFinanceFeeCategoryID" ?>">
				<table class='smallIntBorder fullWidth' cellspacing='0'>
					<tr class='break'>
						<td colspan=2>
							<h3><?php echo __($guid, 'Basic Information') ?></h3>
						</td>
					</tr>
					<tr>
						<td style='width: 275px'>
							<b><?php echo __($guid, 'School Year') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<?php
                            $yearName = '';
							try {
								$dataYear = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
								$sqlYear = 'SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID';
								$resultYear = $connection2->prepare($sqlYear);
								$resultYear->execute($dataYear);
							} catch (PDOException $e) {
								echo "<div class='error'>".$e->getMessage().'</div>';
							}
							if ($resultYear->rowCount() == 1) {
								$rowYear = $resultYear->fetch();
								$yearName = $rowYear['name'];
							}
							?>
							<input readonly name="yearName" id="yearName" value="<?php echo $yearName ?>" type="text" class="standardWidth">
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Invoicee') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<?php
                            $personName = '';
							try {
								$dataInvoicee = array('gibbonFinanceInvoiceeID' => $row['gibbonFinanceInvoiceeID']);
								$sqlInvoicee = 'SELECT surname, preferredName FROM gibbonPerson JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID';
								$resultInvoicee = $connection2->prepare($sqlInvoicee);
								$resultInvoicee->execute($dataInvoicee);
							} catch (PDOException $e) {
								echo "<div class='error'>".$e->getMessage().'</div>';
							}
							if ($resultInvoicee->rowCount() == 1) {
								$rowInvoicee = $resultInvoicee->fetch();
								$personName = formatName('', htmlPrep($rowInvoicee['preferredName']), htmlPrep($rowInvoicee['surname']), 'Student', true);
							}
							?>
							<input readonly name="personName" id="personName" value="<?php echo $personName ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<?php //BILLING TYPE CHOOSER ?>
					<tr>
						<td>
							<b><?php echo __($guid, 'Scheduling') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
						</td>
						<td class="right">
							<input readonly name="billingScheduleType" id="billingScheduleType" value="<?php echo $row['billingScheduleType'] ?>" type="text" class="standardWidth">
						</td>
					</tr>
					<?php
                    if ($row['billingScheduleType'] == 'Scheduled') {
                        ?>
						<tr>
							<td>
								<b><?php echo __($guid, 'Billing Schedule') ?> *</b><br/>
								<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
							</td>
							<td class="right">
								<?php
                                $schedule = '';
                        try {
                            $dataSchedule = array('gibbonFinanceBillingScheduleID' => $row['gibbonFinanceBillingScheduleID']);
                            $sqlSchedule = 'SELECT * FROM gibbonFinanceBillingSchedule WHERE gibbonFinanceBillingScheduleID=:gibbonFinanceBillingScheduleID';
                            $resultSchedule = $connection2->prepare($sqlSchedule);
                            $resultSchedule->execute($dataSchedule);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        if ($resultSchedule->rowCount() == 1) {
                            $rowSchedule = $resultSchedule->fetch();
                            $schedule = $rowSchedule['name'];
                        }
                        ?>
								<input readonly name="schedule" id="schedule" value="<?php echo $schedule ?>" type="text" class="standardWidth">
							</td>
						</tr>
						<?php

                    } else {
                        if ($row['status'] == 'Pending' or $row['status'] == 'Issued') {
                            ?>
							<tr>
								<td>
									<b><?php echo __($guid, 'Invoice Due Date') ?> *</b><br/>
								</td>
								<td class="right">
									<input name="invoiceDueDate" id="invoiceDueDate" value="<?php echo dateConvertBack($guid, $row['invoiceDueDate']) ?>" type="text" class="standardWidth">
									<script type="text/javascript">
										var invoiceDueDate=new LiveValidation('invoiceDueDate');
										invoiceDueDate.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
											echo "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
										} else {
											echo $_SESSION[$guid]['i18n']['dateFormatRegEx'];
										}
																	?>, failureMessage: "Use <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
											echo 'dd/mm/yyyy';
										} else {
											echo $_SESSION[$guid]['i18n']['dateFormat'];
										}
                            			?>." } );
										invoiceDueDate.add(Validate.Presence);
									</script>
									 <script type="text/javascript">
										$(function() {
											$( "#invoiceDueDate" ).datepicker();
										});
									</script>
								</td>
							</tr>
							<?php

                        } else {
                            ?>
							<tr>
								<td>
									<b><?php echo __($guid, 'Invoice Due Date') ?> *</b><br/>
									<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
								</td>
								<td class="right">
									<input readonly name="invoiceDueDate" id="invoiceDueDate" value="<?php echo dateConvertBack($guid, $row['invoiceDueDate']) ?>" type="text" class="standardWidth">
								</td>
							</tr>
							<?php

                        }
					}
					?>
					<tr>
						<td>
							<b><?php echo __($guid, 'Status') ?> *</b><br/>
							<?php
                            if ($row['status'] == 'Pending') {
                                echo '<span style="font-size: 90%"><i>'.__($guid, 'This value cannot be changed. Use the Issue function to change the status from "Pending" to "Issued".').'</span>';
                            } else {
                                echo '<span style="font-size: 90%"><i>'.__($guid, 'Available options are limited according to current status.').'</span>';
                            }
            				?>
						</td>
						<td class="right">
							<?php
                            if ($row['status'] == 'Pending' or $row['status'] == 'Cancelled' or $row['status'] == 'Refunded') {
                                echo '<input readonly name="status" id="status" value="'.$row['status'].'" type="text" style="width: 300px">';
                            } elseif ($row['status'] == 'Issued') {
                                echo "<select name='status' id='status' style='width:302px'>";
                                echo "<option selected value='Issued'>".__($guid, 'Issued').'</option>';
                                echo "<option value='Paid'>".__($guid, 'Paid').'</option>';
                                echo "<option value='Paid - Partial'>".__($guid, 'Paid - Partial').'</option>';
                                echo "<option value='Cancelled'>".__($guid, 'Cancelled').'</option>';
                                echo '</select>';
                            } elseif ($row['status'] == 'Paid' or $row['status'] == 'Paid - Partial') {
                                echo "<select name='status' id='status' style='width:302px'>";
                                if ($row['status'] == 'Paid') {
                                    echo "<option selected value='Paid'>".__($guid, 'Paid').'</option>';
                                }
                                if ($row['status'] == 'Paid - Partial') {
                                    echo "<option value='Paid - Partial'>".__($guid, 'Paid - Partial').'</option>';
                                    echo "<option value='Paid - Complete'>".__($guid, 'Paid - Complete').'</option>';
                                }
                                echo "<option value='Refunded'>".__($guid, 'Refunded').'</option>';
                                echo '</select>';
                            }
            				?>
						</td>
					</tr>
					<?php
                    if ($row['status'] == 'Issued' or $row['status'] == 'Paid - Partial') {
                        ?>
						<script type="text/javascript">
							$(document).ready(function(){
								<?php
                                if ($row['status'] == 'Issued') {
                                    ?>
									$("#paidDateRow").css("display","none");
									$("#paidAmountRow").css("display","none");
									$("#paymentTypeRow").css("display","none");
									$("#paymentTransactionIDRow").css("display","none");
									paidDate.disable() ;
									paidAmount.disable() ;
									paymentType.disable() ;
									<?php

                                }
                        		?>
								$("#status").change(function(){
									if ($('#status').val()=="Paid" || $('#status').val()=="Paid - Partial" || $('#status').val()=="Paid - Complete") {
										$("#paidDateRow").slideDown("fast", $("#paidDateRow").css("display","table-row"));
										$("#paidAmountRow").slideDown("fast", $("#paidAmountRow").css("display","table-row"));
										$("#paymentTypeRow").slideDown("fast", $("#paymentTypeRow").css("display","table-row"));
										$("#paymentTransactionIDRow").slideDown("fast", $("#paymentTransactionIDRow").css("display","table-row"));
										paidDate.enable() ;
										paidAmount.enable() ;
										paymentType.enable() ;
									} else {
										$("#paidDateRow").css("display","none");
										$("#paidAmountRow").css("display","none");
										$("#paymentTypeRow").css("display","none");
										$("#paymentTransactionIDRow").css("display","none");
										paidDate.disable() ;
										paidAmount.disable() ;
										paymentType.disable() ;
									}
								 });
							});
						</script>
						<tr id="paymentTypeRow">
							<td>
								<b><?php echo __($guid, 'Payment Type') ?> *</b><br/>
							</td>
							<td class="right">
								<?php
                                echo "<select name='paymentType' id='paymentType' style='width:302px'>";
								echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
								echo "<option value='Online'>".__($guid, 'Online').'</option>';
								echo "<option value='Bank Transfer'>".__($guid, 'Bank Transfer').'</option>';
								echo "<option value='Cash'>".__($guid, 'Cash').'</option>';
								echo "<option value='Cheque'>".__($guid, 'Cheque').'</option>';
								echo "<option value='Other'>".__($guid, 'Other').'</option>';
								echo '</select>';
								?>
								<script type="text/javascript">
									var paymentType=new LiveValidation('paymentType');
									paymentType.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
								</script>
							</td>
						</tr>
						<tr id="paymentTransactionIDRow">
							<td>
								<b><?php echo __($guid, 'Transaction ID') ?></b><br/>
								<span class="emphasis small"><?php echo __($guid, 'Date of payment, not entry to system.') ?></span>
							</td>
							<td class="right">
								<input name="paymentTransactionID" id="paymentTransactionID" maxlength=50 value="" type="text" class="standardWidth">
							</td>
						</tr>
						<tr id="paidDateRow">
							<td>
								<b><?php echo __($guid, 'Date Paid') ?> *</b><br/>
								<span class="emphasis small"><?php echo __($guid, 'Date of payment, not entry to system.') ?></span>
							</td>
							<td class="right">
								<input name="paidDate" id="paidDate" maxlength=10 value="" type="text" class="standardWidth">
								<script type="text/javascript">
									var paidDate=new LiveValidation('paidDate');
									paidDate.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
										echo "/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i";
									} else {
										echo $_SESSION[$guid]['i18n']['dateFormatRegEx'];
									}
															?>, failureMessage: "Use <?php if ($_SESSION[$guid]['i18n']['dateFormat'] == '') {
										echo 'dd/mm/yyyy';
									} else {
										echo $_SESSION[$guid]['i18n']['dateFormat'];
									}
                       	 			?>." } );
									paidDate.add(Validate.Presence);
								</script>
								 <script type="text/javascript">
									$(function() {
										$( "#paidDate" ).datepicker();
									});
								</script>
							</td>
						</tr>
						<tr id="paidAmountRow">
							<td>
								<b><?php echo __($guid, 'Amount Paid') ?> *</b><br/>
								<span class="emphasis small"><?php echo __($guid, 'Amount in current payment.') ?>
								<?php
                                if ($_SESSION[$guid]['currency'] != '') {
                                    echo "<span style='font-style: italic; font-size: 85%'>".$_SESSION[$guid]['currency'].'</span>';
                                }
                        		?>
								</span>
							</td>
							<td class="right">
								<?php
                                //Get default paidAmount
                                try {
                                    $dataFees['gibbonFinanceInvoiceID'] = $row['gibbonFinanceInvoiceID'];
                                    $sqlFees = 'SELECT gibbonFinanceInvoiceFee.gibbonFinanceInvoiceFeeID, gibbonFinanceInvoiceFee.feeType, gibbonFinanceFeeCategory.name AS category, gibbonFinanceInvoiceFee.name AS name, gibbonFinanceInvoiceFee.fee, gibbonFinanceInvoiceFee.description AS description, NULL AS gibbonFinanceFeeID, gibbonFinanceInvoiceFee.gibbonFinanceFeeCategoryID AS gibbonFinanceFeeCategoryID, sequenceNumber FROM gibbonFinanceInvoiceFee JOIN gibbonFinanceFeeCategory ON (gibbonFinanceInvoiceFee.gibbonFinanceFeeCategoryID=gibbonFinanceFeeCategory.gibbonFinanceFeeCategoryID) WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID ORDER BY sequenceNumber';
                                    $resultFees = $connection2->prepare($sqlFees);
                                    $resultFees->execute($dataFees);
                                } catch (PDOException $e) {
                                }
								$paidAmountDefault = 0;
								while ($rowFees = $resultFees->fetch()) {
									$paidAmountDefault = $paidAmountDefault + $rowFees['fee'];
								}
                                //If some paid already, work out amount, and subtract it off
                                if ($row['status'] == 'Paid - Partial') {
                                    $alreadyPaid = getAmountPaid($connection2, $guid, 'gibbonFinanceInvoice', $gibbonFinanceInvoiceID);
                                    $paidAmountDefault -= $alreadyPaid;
                                }
                        		?>
								<input name="paidAmount" id="paidAmount" maxlength=14 value="<?php echo number_format($paidAmountDefault, 2, '.', '') ?>" type="text" class="standardWidth">
								<script type="text/javascript">
									var paidAmount=new LiveValidation('paidAmount');
									paidAmount.add( Validate.Format, { pattern: /^(?:\d*\.\d{1,2}|\d+)$/, failureMessage: "Invalid number format!" } );
									paidAmount.add(Validate.Presence);
								</script>
							</td>
						</tr>
						<?php
					}
                    ?>
					<tr>
						<td colspan=2>
							<b><?php echo __($guid, 'Notes') ?></b> <br/>
                            <span class="emphasis small"><?php echo __($guid, 'Notes will be displayed on the final invoice and receipt.') ?></span>
							<textarea name='notes' id='notes' rows=5 style='width: 300px'><?php echo htmlPrep($row['notes']) ?></textarea>
						</td>
					</tr>

					<tr class='break'>
						<td colspan=2>
							<h3><?php echo __($guid, 'Fees') ?></h3>
						</td>
					</tr>
					<?php
                    if ($row['status'] == 'Pending') {
                        $type = 'fee';
                        ?>
						<style>
							#<?php echo $type ?> { list-style-type: none; margin: 0; padding: 0; width: 100%; }
							#<?php echo $type ?> div.ui-state-default { margin: 0 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 58px; }
							div.ui-state-default_dud { margin: 5px 0px 5px 0px; padding: 5px; font-size: 100%; min-height: 58px; }
							html>body #<?php echo $type ?> li { min-height: 58px; line-height: 1.2em; }
							.<?php echo $type ?>-ui-state-highlight { margin-bottom: 5px; min-height: 58px; line-height: 1.2em; width: 100%; }
							.<?php echo $type ?>-ui-state-highlight {border: 1px solid #fcd3a1; background: #fbf8ee url(images/ui-bg_glass_55_fbf8ee_1x400.png) 50% 50% repeat-x; color: #444444; }
						</style>
						<tr>
							<td colspan=2>
								<div class="fee" id="fee" style='width: 100%; padding: 5px 0px 0px 0px; min-height: 66px'>
									<?php
                                    $feeCount = 0;
                        try {
                            //Standard
                            $dataFees['gibbonFinanceInvoiceID1'] = $row['gibbonFinanceInvoiceID'];
                            $sqlFees = "(SELECT gibbonFinanceInvoiceFee.gibbonFinanceInvoiceFeeID, gibbonFinanceInvoiceFee.feeType, gibbonFinanceFeeCategory.name AS category, gibbonFinanceFee.name AS name, gibbonFinanceFee.fee AS fee, gibbonFinanceFee.description AS description, gibbonFinanceInvoiceFee.gibbonFinanceFeeID AS gibbonFinanceFeeID, gibbonFinanceInvoiceFee.gibbonFinanceFeeCategoryID AS gibbonFinanceFeeCategoryID, sequenceNumber FROM gibbonFinanceInvoiceFee JOIN gibbonFinanceFee ON (gibbonFinanceInvoiceFee.gibbonFinanceFeeID=gibbonFinanceFee.gibbonFinanceFeeID) JOIN gibbonFinanceFeeCategory ON (gibbonFinanceFee.gibbonFinanceFeeCategoryID=gibbonFinanceFeeCategory.gibbonFinanceFeeCategoryID) WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID1 AND feeType='Standard')";
                            $sqlFees .= ' UNION ';
							//Ad Hoc
							$dataFees['gibbonFinanceInvoiceID2'] = $row['gibbonFinanceInvoiceID'];
                            $sqlFees .= "(SELECT gibbonFinanceInvoiceFee.gibbonFinanceInvoiceFeeID, gibbonFinanceInvoiceFee.feeType, gibbonFinanceFeeCategory.name AS category, gibbonFinanceInvoiceFee.name AS name, gibbonFinanceInvoiceFee.fee, gibbonFinanceInvoiceFee.description AS description, NULL AS gibbonFinanceFeeID, gibbonFinanceInvoiceFee.gibbonFinanceFeeCategoryID AS gibbonFinanceFeeCategoryID, sequenceNumber FROM gibbonFinanceInvoiceFee JOIN gibbonFinanceFeeCategory ON (gibbonFinanceInvoiceFee.gibbonFinanceFeeCategoryID=gibbonFinanceFeeCategory.gibbonFinanceFeeCategoryID) WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID2 AND feeType='Ad Hoc')";
                            $sqlFees .= ' ORDER BY sequenceNumber';
                            $resultFees = $connection2->prepare($sqlFees);
                            $resultFees->execute($dataFees);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        while ($rowFees = $resultFees->fetch()) {
                            makeFeeBlock($guid, $connection2, $feeCount, 'edit', $rowFees['feeType'], $rowFees['gibbonFinanceFeeID'], $rowFees['name'], $rowFees['description'], $rowFees['gibbonFinanceFeeCategoryID'], $rowFees['fee'], $rowFees['category']);
                            ++$feeCount;
                        }
                        ?>
								</div>
								<div style='width: 100%; padding: 0px 0px 0px 0px'>
									<div class="ui-state-default_dud" style='padding: 0px; height: 40px'>
										<table class='blank' cellspacing='0' style='width: 100%'>
											<tr>
												<td style='width: 50%'>
													<script type="text/javascript">
														var feeCount=<?php echo $feeCount ?> ;
													</script>
													<select id='newFee' onChange='feeDisplayElements(this.value);' style='float: none; margin-left: 3px; margin-top: 0px; margin-bottom: 3px; width: 350px'>
														<option class='all' value='0'><?php echo __($guid, 'Choose a fee to add it') ?></option>
														<?php
                                                        echo "<option value='Ad Hoc'>Ad Hoc Fee</option>";
														$switchContents = 'case "Ad Hoc": ';
														$switchContents .= "$(\"#fee\").append('<div id=\'feeOuter' + feeCount + '\'><img style=\'margin: 10px 0 5px 0\' src=\'".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/loading.gif\' alt=\'Loading\' onclick=\'return false;\' /><br/>Loading</div>');";
														$switchContents .= '$("#feeOuter" + feeCount).load("'.$_SESSION[$guid]['absoluteURL'].'/modules/Finance/invoices_manage_add_blockFeeAjax.php","mode=add&id=" + feeCount + "&feeType='.urlencode('Ad Hoc').'&gibbonFinanceFeeID=&name='.urlencode('Ad Hoc Fee').'&description=&gibbonFinanceFeeCategoryID=1&fee=") ;';
														$switchContents .= 'feeCount++ ;';
														$switchContents .= "$('#newFee').val('0');";
														$switchContents .= 'break;';
														$currentCategory = '';
														$lastCategory = '';
														for ($i = 0; $i < 2; ++$i) {
															try {
																$dataSelect = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
																if ($i == 0) {
																	$sqlSelect = "SELECT gibbonFinanceFee.*, gibbonFinanceFeeCategory.name AS category FROM gibbonFinanceFee LEFT JOIN gibbonFinanceFeeCategory ON (gibbonFinanceFee.gibbonFinanceFeeCategoryID=gibbonFinanceFeeCategory.gibbonFinanceFeeCategoryID) WHERE gibbonFinanceFee.active='Y' AND gibbonSchoolYearID=:gibbonSchoolYearID AND NOT gibbonFinanceFee.gibbonFinanceFeeCategoryID=1 ORDER BY gibbonFinanceFee.gibbonFinanceFeeCategoryID, gibbonFinanceFee.name";
																} else {
																	$sqlSelect = "SELECT gibbonFinanceFee.*, gibbonFinanceFeeCategory.name AS category FROM gibbonFinanceFee LEFT JOIN gibbonFinanceFeeCategory ON (gibbonFinanceFee.gibbonFinanceFeeCategoryID=gibbonFinanceFeeCategory.gibbonFinanceFeeCategoryID) WHERE gibbonFinanceFee.active='Y' AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFinanceFee.gibbonFinanceFeeCategoryID=1 ORDER BY gibbonFinanceFee.gibbonFinanceFeeCategoryID, gibbonFinanceFee.name";
																}
																$resultSelect = $connection2->prepare($sqlSelect);
																$resultSelect->execute($dataSelect);
															} catch (PDOException $e) {
																echo "<div class='error'>".$e->getMessage().'</div>';
															}
															while ($rowSelect = $resultSelect->fetch()) {
																$currentCategory = $rowSelect['category'];
																if (($currentCategory != $lastCategory) and $currentCategory != '') {
																	echo "<optgroup label='--".$currentCategory."--'>";
																	$categories[$categoryCount] = $currentCategory;
																	++$categoryCount;
																}
																echo "<option value='".$rowSelect['gibbonFinanceFeeID']."'>".$rowSelect['name'].'</option>';
																$switchContents .= 'case "'.$rowSelect['gibbonFinanceFeeID'].'": ';
																$switchContents .= "$(\"#fee\").append('<div id=\'feeOuter' + feeCount + '\'><img style=\'margin: 10px 0 5px 0\' src=\'".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/loading.gif\' alt=\'Loading\' onclick=\'return false;\' /><br/>Loading</div>');";
																$switchContents .= '$("#feeOuter" + feeCount).load("'.$_SESSION[$guid]['absoluteURL'].'/modules/Finance/invoices_manage_add_blockFeeAjax.php","mode=add&id=" + feeCount + "&feeType=Standard&gibbonFinanceFeeID='.urlencode($rowSelect['gibbonFinanceFeeID']).'&name='.urlencode($rowSelect['name']).'&description='.urlencode($rowSelect['description']).'&gibbonFinanceFeeCategoryID='.urlencode($rowSelect['gibbonFinanceFeeCategoryID']).'&fee='.urlencode($rowSelect['fee']).'&category='.urlencode($rowSelect['category']).'") ;';
																$switchContents .= 'feeCount++ ;';
																$switchContents .= "$('#newFee').val('0');";
																$switchContents .= 'break;';
																$lastCategory = $rowSelect['category'];
															}
														}
														?>
													</select>
													<script type='text/javascript'>
														function feeDisplayElements(number) {
															$("#<?php echo $type ?>Outer0").css("display", "none") ;
															switch(number) {
																<?php echo $switchContents ?>
															}
														}
													</script>
												</td>
											</tr>
										</table>
									</div>
								</div>
							</td>
						</tr>
					<?php

                    } else {
                        echo '<tr>';
                        echo '<td colspan=2>';
                        $feeTotal = 0;
                        try {
                            $dataFees['gibbonFinanceInvoiceID'] = $row['gibbonFinanceInvoiceID'];
                            $sqlFees = 'SELECT gibbonFinanceInvoiceFee.gibbonFinanceInvoiceFeeID, gibbonFinanceInvoiceFee.feeType, gibbonFinanceFeeCategory.name AS category, gibbonFinanceInvoiceFee.name AS name, gibbonFinanceInvoiceFee.fee, gibbonFinanceInvoiceFee.description AS description, NULL AS gibbonFinanceFeeID, gibbonFinanceInvoiceFee.gibbonFinanceFeeCategoryID AS gibbonFinanceFeeCategoryID, sequenceNumber FROM gibbonFinanceInvoiceFee JOIN gibbonFinanceFeeCategory ON (gibbonFinanceInvoiceFee.gibbonFinanceFeeCategoryID=gibbonFinanceFeeCategory.gibbonFinanceFeeCategoryID) WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID ORDER BY sequenceNumber';
                            $resultFees = $connection2->prepare($sqlFees);
                            $resultFees->execute($dataFees);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        if ($resultFees->rowCount() < 1) {
                            echo "<div class='error'>";
                            echo __($guid, 'There are no records to display.');
                            echo '</div>';
                        } else {
                            echo "<table cellspacing='0' style='width: 100%'>";
                            echo "<tr class='head'>";
                            echo '<th>';
                            echo __($guid, 'Name');
                            echo '</th>';
                            echo '<th>';
                            echo __($guid, 'Category');
                            echo '</th>';
                            echo '<th>';
                            echo __($guid, 'Description');
                            echo '</th>';
                            echo '<th>';
                            echo __($guid, 'Fee').'<br/>';
                            if ($_SESSION[$guid]['currency'] != '') {
                                echo "<span style='font-style: italic; font-size: 85%'>".$_SESSION[$guid]['currency'].'</span>';
                            }
                            echo '</th>';
                            echo '</tr>';

                            $count = 0;
                            $rowNum = 'odd';
                            while ($rowFees = $resultFees->fetch()) {
                                if ($count % 2 == 0) {
                                    $rowNum = 'even';
                                } else {
                                    $rowNum = 'odd';
                                }
                                ++$count;

                                echo "<tr style='height: 25px' class=$rowNum>";
                                echo '<td>';
                                echo $rowFees['name'];
                                echo '</td>';
                                echo '<td>';
                                echo $rowFees['category'];
                                echo '</td>';
                                echo '<td>';
                                echo $rowFees['description'];
                                echo '</td>';
                                echo '<td>';
                                if (substr($_SESSION[$guid]['currency'], 4) != '') {
                                    echo substr($_SESSION[$guid]['currency'], 4).' ';
                                }
                                echo number_format($rowFees['fee'], 2, '.', ',');
                                $feeTotal += $rowFees['fee'];
                                echo '</td>';
                                echo '</tr>';
                            }
                            echo "<tr style='height: 35px' class='current'>";
                            echo "<td colspan=3 style='text-align: right'>";
                            echo '<b>'.__($guid, 'Invoice Total:').'</b>';
                            echo '</td>';
                            echo '<td>';
                            if (substr($_SESSION[$guid]['currency'], 4) != '') {
                                echo substr($_SESSION[$guid]['currency'], 4).' ';
                            }
                            echo '<b>'.number_format($feeTotal, 2, '.', ',').'</b>';
                            echo '</td>';
                            echo '</tr>';
                            echo '</table>';
                            echo '</td>';
                            echo '</tr>';
                        }
                    }

                    //PUT PAYMENT LOG
                    echo "<tr class='break'>";
					echo '<td colspan=2>';
					echo '<h3>'.__($guid, 'Payment Log').'</h3>';
					echo '</td>';
					echo '</tr>';
					echo '<tr>';
					echo '<td colspan=2>';
					echo getPaymentLog($connection2, $guid, 'gibbonFinanceInvoice', $gibbonFinanceInvoiceID);
					echo '</td>';
					echo '</tr>';

                    //Receipt emailing
                    if ($row['status'] == 'Issued' or $row['status'] == 'Paid - Partial') {
                        ?>
						<script type="text/javascript">
							$(document).ready(function(){
								if ($('#status').val()!="Paid - Partial") {
									$(".emailReceipt").css("display","none");
								}
								$("#status").change(function(){
									if ($('#status').val()=="Paid" || $('#status').val()=="Paid - Partial"  || $('#status').val()=="Paid - Complete") {
										$(".emailReceipt").slideDown("fast", $(".emailReceipt").css("display","table-row"));
										$("#emailReceipt").val('Y');
									}
									else {
										$(".emailReceipt").css("display","none");
										$("#emailReceipt").val('N');
									}
								 });
							});
						</script>
						<tr class='break emailReceipt'>
							<td colspan=2>
								<h3><?php echo __($guid, 'Email Receipt') ?></h3>
								<input type='hidden' id='emailReceipt' name='emailReceipt' value='N'/>
							</td>
						</tr>
						<?php
                        $email = getSettingByScope($connection2, 'Finance', 'email');
                        if ($email == '') {
                            echo "<tr class='emailReceipt'>";
                            echo '<td colspan=2>';
                            echo "<div class='error'>";
                            echo __($guid, 'An outgoing email address has not been set up under Invoice & Receipt Settings, and so no emails can be sent.');
                            echo '</div>';
                            echo "<input type='hidden' name='email' value='$email'/>";
                            echo '<td>';
                            echo '<tr>';
                        } else {
                            echo "<input type='hidden' name='email' value='$email'/>";
                            if ($row['invoiceTo'] == 'Company') {
                                if ($row['companyEmail'] != '' and $row['companyContact'] != '' and $row['companyName'] != '') {
                                    ?>
									<tr class='emailReceipt'>
										<td>
											<b><?php echo $row['companyContact'] ?></b> (<?php echo $row['companyName'];
                                    ?>)
											<span class="emphasis small"></span>
										</td>
										<td class="right">
											<?php echo $row['companyEmail'];
                                    ?> <input checked type='checkbox' name='emails[]' value='<?php echo htmlPrep($row['companyEmail']);
                                    ?>'/>
											<input type='hidden' name='names[]' value='<?php echo htmlPrep($row['companyContact']);
                                    ?>'/>
										</td>
									</tr>
									<?php
                                    if ($row['companyCCFamily'] == 'Y') {
                                        try {
                                            $dataParents = array('gibbonFinanceInvoiceeID' => $row['gibbonFinanceInvoiceeID']);
                                            $sqlParents = "SELECT parent.title, parent.surname, parent.preferredName, parent.email, parent.address1, parent.address1District, parent.address1Country, homeAddress, homeAddressDistrict, homeAddressCountry FROM gibbonFinanceInvoicee JOIN gibbonPerson AS student ON (gibbonFinanceInvoicee.gibbonPersonID=student.gibbonPersonID) JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=student.gibbonPersonID) JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamily.gibbonFamilyID=gibbonFamilyAdult.gibbonFamilyID) JOIN gibbonPerson AS parent ON (gibbonFamilyAdult.gibbonPersonID=parent.gibbonPersonID) WHERE gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID AND (contactPriority=1 OR (contactPriority=2 AND contactEmail='Y')) ORDER BY contactPriority, surname, preferredName";
                                            $resultParents = $connection2->prepare($sqlParents);
                                            $resultParents->execute($dataParents);
                                        } catch (PDOException $e) {
                                            echo "<div class='error'>".$e->getMessage().'</div>';
                                        }
                                        if ($resultParents->rowCount() < 1) {
                                            echo "<div class='warning'>".__($guid, 'There are no family members available to send this receipt to.').'</div>';
                                        } else {
                                            while ($rowParents = $resultParents->fetch()) {
                                                if ($rowParents['preferredName'] != '' and $rowParents['surname'] != '' and $rowParents['email'] != '') {
                                                    ?>
													<tr class='emailReceipt'>
														<td>
															<b><?php echo formatName(htmlPrep($rowParents['title']), htmlPrep($rowParents['preferredName']), htmlPrep($rowParents['surname']), 'Parent', false) ?></b> <i><?php echo __($guid, '(Family CC)') ?></i>
															<span class="emphasis small"></span>
														</td>
														<td class="right">
															<?php echo $rowParents['email'];
                                                    ?> <input checked type='checkbox' name='emails[]' value='<?php echo htmlPrep($rowParents['email']);
                                                    ?>'/>
															<input type='hidden' name='names[]' value='<?php echo htmlPrep(formatName(htmlPrep($rowParents['title']), htmlPrep($rowParents['preferredName']), htmlPrep($rowParents['surname']), 'Parent', false));
                                                    ?>'/>
														</td>
													</tr>
													<?php

                                                }
                                            }
                                        }
                                    }
                                } else {
                                    echo "<div class='warning'>".__($guid, 'There is no company contact available to send this invoice to.').'</div>';
                                }
                            } else {
                                try {
                                    $dataParents = array('gibbonFinanceInvoiceeID' => $row['gibbonFinanceInvoiceeID']);
                                    $sqlParents = "SELECT parent.title, parent.surname, parent.preferredName, parent.email, parent.address1, parent.address1District, parent.address1Country, homeAddress, homeAddressDistrict, homeAddressCountry FROM gibbonFinanceInvoicee JOIN gibbonPerson AS student ON (gibbonFinanceInvoicee.gibbonPersonID=student.gibbonPersonID) JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=student.gibbonPersonID) JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamily.gibbonFamilyID=gibbonFamilyAdult.gibbonFamilyID) JOIN gibbonPerson AS parent ON (gibbonFamilyAdult.gibbonPersonID=parent.gibbonPersonID) WHERE gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID AND (contactPriority=1 OR (contactPriority=2 AND contactEmail='Y')) ORDER BY contactPriority, surname, preferredName";
                                    $resultParents = $connection2->prepare($sqlParents);
                                    $resultParents->execute($dataParents);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }
                                if ($resultParents->rowCount() < 1) {
                                    echo "<div class='warning'>".__($guid, 'There are no family members available to send this receipt to.').'</div>';
                                } else {
                                    while ($rowParents = $resultParents->fetch()) {
                                        if ($rowParents['preferredName'] != '' and $rowParents['surname'] != '' and $rowParents['email'] != '') {
                                            ?>
											<tr class='emailReceipt'>
												<td>
													<b><?php echo formatName(htmlPrep($rowParents['title']), htmlPrep($rowParents['preferredName']), htmlPrep($rowParents['surname']), 'Parent', false) ?></b>
													<span class="emphasis small"></span>
												</td>
												<td class="right">
													<?php echo $rowParents['email'];
                                            ?> <input checked type='checkbox' name='emails[]' value='<?php echo htmlPrep($rowParents['email']);
                                            ?>'/>
													<input type='hidden' name='names[]' value='<?php echo htmlPrep(formatName(htmlPrep($rowParents['title']), htmlPrep($rowParents['preferredName']), htmlPrep($rowParents['surname']), 'Parent', false));
                                            ?>'/>
												</td>
											</tr>
											<?php

                                        }
                                    }
                                }
                            }
                            //CC self?
                            if ($_SESSION[$guid]['email'] != '') {
                                ?>
								<tr class='emailReceipt'>
									<td>
										<b><?php echo formatName('', htmlPrep($_SESSION[$guid]['preferredName']), htmlPrep($_SESSION[$guid]['surname']), 'Parent', false) ?></b>
										<span class="emphasis small"><?php echo __($guid, '(CC Self?)') ?></span>
									</td>
									<td class="right">
										<?php echo $_SESSION[$guid]['email'];
                                ?> <input type='checkbox' name='emails[]' value='<?php echo $_SESSION[$guid]['email'];
                                ?>'/>
										<input type='hidden' name='names[]' value='<?php echo formatName('', htmlPrep($_SESSION[$guid]['preferredName']), htmlPrep($_SESSION[$guid]['surname']), 'Parent', false);
                                ?>'/>
									</td>
								</tr>
								<?php

                            }
                        }
                    }

                    //Reminder emailing
                    if ($row['status'] == 'Issued' and $row['invoiceDueDate'] < date('Y-m-d')) {
                        ?>
						<script type="text/javascript">
							$(document).ready(function(){
								$("#status").change(function(){
									if ($('#status').val()=="Paid" || $('#status').val()=="Cancelled" ) {
										$(".emailReminder").css("display","none");
										$("#emailReminder").val('N');
									}
									else {
										$(".emailReminder").slideDown("fast", $(".emailReminder").css("display","table-row"));
										$("#emailReminder").val('Y');
									}
								 });
							});
						</script>
						<tr class='break emailReminder'>
							<td colspan=2>
								<h3><?php echo sprintf(__($guid, 'Email Reminder %1$s'), ($row['reminderCount'])+1) ?></h3>
								<input type='hidden' id='emailReminder' name='emailReminder' value='Y'/>
							</td>
						</tr>
						<?php
                        $email = getSettingByScope($connection2, 'Finance', 'email');
                        if ($email == '') {
                            echo "<tr class='emailReminder'>";
                            echo '<td colspan=2>';
                            echo "<div class='error'>";
                            echo __($guid, 'An outgoing email address has not been set up under Invoice & Receipt Settings, and so no emails can be sent.');
                            echo '</div>';
                            echo "<input type='hidden' name='email' value='$email'/>";
                            echo '<td>';
                            echo '<tr>';
                        } else {
                            echo "<input type='hidden' name='email' value='$email'/>";
                            if ($row['invoiceTo'] == 'Company') {
                                if ($row['companyEmail'] != '' and $row['companyContact'] != '' and $row['companyName'] != '') {
                                    ?>
									<tr class='emailReminder'>
										<td>
											<b><?php echo $row['companyContact'] ?></b> (<?php echo $row['companyName'];
                                    ?>)
											<span class="emphasis small"></span>
										</td>
										<td class="right">
											<?php echo $row['companyEmail'];
                                    ?> <input checked type='checkbox' name='emails2[]' value='<?php echo htmlPrep($row['companyEmail']);
                                    ?>'/>
											<input type='hidden' name='names[]' value='<?php echo htmlPrep($row['companyContact']);
                                    ?>'/>
										</td>
									</tr>
									<?php
                                    if ($row['companyCCFamily'] == 'Y') {
                                        try {
                                            $dataParents = array('gibbonFinanceInvoiceeID' => $row['gibbonFinanceInvoiceeID']);
                                            $sqlParents = "SELECT parent.title, parent.surname, parent.preferredName, parent.email, parent.address1, parent.address1District, parent.address1Country, homeAddress, homeAddressDistrict, homeAddressCountry FROM gibbonFinanceInvoicee JOIN gibbonPerson AS student ON (gibbonFinanceInvoicee.gibbonPersonID=student.gibbonPersonID) JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=student.gibbonPersonID) JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamily.gibbonFamilyID=gibbonFamilyAdult.gibbonFamilyID) JOIN gibbonPerson AS parent ON (gibbonFamilyAdult.gibbonPersonID=parent.gibbonPersonID) WHERE gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID AND (contactPriority=1 OR (contactPriority=2 AND contactEmail='Y')) ORDER BY contactPriority, surname, preferredName";
                                            $resultParents = $connection2->prepare($sqlParents);
                                            $resultParents->execute($dataParents);
                                        } catch (PDOException $e) {
                                            echo "<div class='error'>".$e->getMessage().'</div>';
                                        }
                                        if ($resultParents->rowCount() < 1) {
                                            echo "<div class='warning'>".__($guid, 'There are no family members available to send this receipt to.').'</div>';
                                        } else {
                                            while ($rowParents = $resultParents->fetch()) {
                                                if ($rowParents['preferredName'] != '' and $rowParents['surname'] != '' and $rowParents['email'] != '') {
                                                    ?>
													<tr class='emailReminder'>
														<td>
															<b><?php echo formatName(htmlPrep($rowParents['title']), htmlPrep($rowParents['preferredName']), htmlPrep($rowParents['surname']), 'Parent', false) ?></b> <i><?php echo __($guid, '(Family CC)') ?></i>
															<span class="emphasis small"></span>
														</td>
														<td class="right">
															<?php echo $rowParents['email'];
                                                    ?> <input checked type='checkbox' name='emails2[]' value='<?php echo htmlPrep($rowParents['email']);
                                                    ?>'/>
															<input type='hidden' name='names[]' value='<?php echo htmlPrep(formatName(htmlPrep($rowParents['title']), htmlPrep($rowParents['preferredName']), htmlPrep($rowParents['surname']), 'Parent', false));
                                                    ?>'/>
														</td>
													</tr>
													<?php

                                                }
                                            }
                                        }
                                    }
                                } else {
                                    echo "<div class='warning'>".__($guid, 'There is no company contact available to send this invoice to.').'</div>';
                                }
                            } else {
                                try {
                                    $dataParents = array('gibbonFinanceInvoiceeID' => $row['gibbonFinanceInvoiceeID']);
                                    $sqlParents = "SELECT parent.title, parent.surname, parent.preferredName, parent.email, parent.address1, parent.address1District, parent.address1Country, homeAddress, homeAddressDistrict, homeAddressCountry FROM gibbonFinanceInvoicee JOIN gibbonPerson AS student ON (gibbonFinanceInvoicee.gibbonPersonID=student.gibbonPersonID) JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=student.gibbonPersonID) JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamily.gibbonFamilyID=gibbonFamilyAdult.gibbonFamilyID) JOIN gibbonPerson AS parent ON (gibbonFamilyAdult.gibbonPersonID=parent.gibbonPersonID) WHERE gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID AND (contactPriority=1 OR (contactPriority=2 AND contactEmail='Y')) ORDER BY contactPriority, surname, preferredName";
                                    $resultParents = $connection2->prepare($sqlParents);
                                    $resultParents->execute($dataParents);
                                } catch (PDOException $e) {
                                    echo "<div class='error'>".$e->getMessage().'</div>';
                                }
                                if ($resultParents->rowCount() < 1) {
                                    echo "<div class='warning'>".__($guid, 'There are no family members available to send this receipt to.').'</div>';
                                } else {
                                    while ($rowParents = $resultParents->fetch()) {
                                        if ($rowParents['preferredName'] != '' and $rowParents['surname'] != '' and $rowParents['email'] != '') {
                                            ?>
											<tr class='emailReminder'>
												<td>
													<b><?php echo formatName(htmlPrep($rowParents['title']), htmlPrep($rowParents['preferredName']), htmlPrep($rowParents['surname']), 'Parent', false) ?></b>
													<span class="emphasis small"></span>
												</td>
												<td class="right">
													<?php echo $rowParents['email'];
                                            ?> <input checked type='checkbox' name='emails2[]' value='<?php echo htmlPrep($rowParents['email']);
                                            ?>'/>
													<input type='hidden' name='names[]' value='<?php echo htmlPrep(formatName(htmlPrep($rowParents['title']), htmlPrep($rowParents['preferredName']), htmlPrep($rowParents['surname']), 'Parent', false));
                                            ?>'/>
												</td>
											</tr>
											<?php

                                        }
                                    }
                                }
                            }
                            //CC self?
                            if ($_SESSION[$guid]['email'] != '') {
                                ?>
								<tr class='emailReminder'>
									<td>
										<b><?php echo formatName('', htmlPrep($_SESSION[$guid]['preferredName']), htmlPrep($_SESSION[$guid]['surname']), 'Parent', false) ?></b>
										<span class="emphasis small"><?php echo __($guid, '(CC Self?)') ?></span>
									</td>
									<td class="right">
										<?php echo $_SESSION[$guid]['email'];
                                ?> <input type='checkbox' name='emails[]' value='<?php echo $_SESSION[$guid]['email'];
                                ?>'/>
										<input type='hidden' name='names[]' value='<?php echo formatName('', htmlPrep($_SESSION[$guid]['preferredName']), htmlPrep($_SESSION[$guid]['surname']), 'Parent', false);
                                ?>'/>
									</td>
								</tr>
								<?php

                            }
                        }
                    }
            		?>
					<tr>
						<td>
							<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
						</td>
						<td class="right">
							<input name="gibbonFinanceInvoiceID" id="gibbonFinanceInvoiceID" value="<?php echo $gibbonFinanceInvoiceID ?>" type="hidden">
							<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
							<input type="submit" value="<?php echo __($guid, 'Submit'); ?>">
						</td>
					</tr>
				</table>
			</form>
			<?php

        }
    }
}
?>
