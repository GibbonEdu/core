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

if (isActionAccessible($guid, $connection2, '/modules/Finance/expenseRequest_manage_reimburse.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Finance/expenseRequest_manage.php&gibbonFinanceBudgetCycleID='.$_GET['gibbonFinanceBudgetCycleID']."'>".__($guid, 'My Expense Requests')."</a> > </div><div class='trailEnd'>".__($guid, 'Request Reimbursement').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if params are specified
    $gibbonFinanceExpenseID = $_GET['gibbonFinanceExpenseID'];
    $gibbonFinanceBudgetCycleID = $_GET['gibbonFinanceBudgetCycleID'];
    $status2 = $_GET['status2'];
    $gibbonFinanceBudgetID2 = $_GET['gibbonFinanceBudgetID2'];
    if ($gibbonFinanceExpenseID == '' or $gibbonFinanceBudgetCycleID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        //Get and check settings
        $expenseApprovalType = getSettingByScope($connection2, 'Finance', 'expenseApprovalType');
        $budgetLevelExpenseApproval = getSettingByScope($connection2, 'Finance', 'budgetLevelExpenseApproval');
        $expenseRequestTemplate = getSettingByScope($connection2, 'Finance', 'expenseRequestTemplate');
        if ($expenseApprovalType == '' or $budgetLevelExpenseApproval == '') {
            echo "<div class='error'>";
            echo __($guid, 'An error has occurred with your expense and budget settings.');
            echo '</div>';
        } else {
            //Check if there are approvers
            try {
                $data = array();
                $sql = "SELECT * FROM gibbonFinanceExpenseApprover JOIN gibbonPerson ON (gibbonFinanceExpenseApprover.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full'";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo $e->getMessage();
            }

            if ($result->rowCount() < 1) {
                echo "<div class='error'>";
                echo __($guid, 'An error has occurred with your expense and budget settings.');
                echo '</div>';
            } else {
                //Ready to go! Just check record exists and we have access, and load it ready to use...
                try {
                    //Set Up filter wheres
                    $data = array('gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID, 'gibbonFinanceExpenseID' => $gibbonFinanceExpenseID);
                    $sql = "SELECT gibbonFinanceExpense.*, gibbonFinanceBudget.name AS budget, surname, preferredName, 'Full' AS access 
							FROM gibbonFinanceExpense 
							JOIN gibbonFinanceBudget ON (gibbonFinanceExpense.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID) 
							JOIN gibbonPerson ON (gibbonFinanceExpense.gibbonPersonIDCreator=gibbonPerson.gibbonPersonID) 
							WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID AND gibbonFinanceExpenseID=:gibbonFinanceExpenseID AND gibbonFinanceExpense.status='Approved'";
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

                    if ($status2 != '' or $gibbonFinanceBudgetID2 != '') {
                        echo "<div class='linkTop'>";
                        echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Finance/expenseRequest_manage.php&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status2=$status2&gibbonFinanceBudgetID2=$gibbonFinanceBudgetID2'>".__($guid, 'Back to Search Results').'</a>';
                        echo '</div>';
                    }
                    ?>
					<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/expenseRequest_manage_reimburseProcess.php' ?>" enctype="multipart/form-data">
						<table class='smallIntBorder fullWidth' cellspacing='0'>	
							<tr class='break'>
								<td colspan=2> 
									<h3><?php echo __($guid, 'Basic Information') ?></h3>
								</td>
							</tr>
							<tr>
								<td style='width: 275px'> 
									<b><?php echo __($guid, 'Budget Cycle') ?> *</b><br/>
								</td>
								<td class="right">
									<?php
                                    $yearName = '';
                    try {
                        $dataYear = array('gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID);
                        $sqlYear = 'SELECT * FROM gibbonFinanceBudgetCycle WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID';
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
									<input readonly name="name" id="name" maxlength=20 value="<?php echo $yearName ?>" type="text" class="standardWidth">
									<input name="gibbonFinanceBudgetCycleID" id="gibbonFinanceBudgetCycleID" maxlength=20 value="<?php echo $gibbonFinanceBudgetCycleID ?>" type="hidden" class="standardWidth">
									<script type="text/javascript">
										var gibbonFinanceBudgetCycleID=new LiveValidation('gibbonFinanceBudgetCycleID');
										gibbonFinanceBudgetCycleID.add(Validate.Presence);
									</script>
								</td>
							</tr>
							<tr>
								<td style='width: 275px'> 
									<b><?php echo __($guid, 'Budget') ?> *</b><br/>
								</td>
								<td class="right">
									<input readonly name="name" id="name" maxlength=20 value="<?php echo $row['budget'];
                    ?>" type="text" class="standardWidth">
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php echo __($guid, 'Title') ?> *</b><br/>
								</td>
								<td class="right">
									<input readonly name="name" id="name" maxlength=60 value="<?php echo $row['title'];
                    ?>" type="text" class="standardWidth">
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php echo __($guid, 'Status') ?> *</b><br/><?php echo $row['status'] ?>
								</td>
								<td class="right">
									<?php
                                    if ($row['status'] == 'Requested' or $row['status'] == 'Approved' or $row['status'] == 'Ordered') {
                                        echo "<select name='status' id='status' style='width:302px'>";
                                        echo "<option  value='Please select...'>".__($guid, 'Please select...').'</option>';
                                        if ($row['status'] == 'Approved') {
                                            echo "<option value='Approved'>".__($guid, 'Approved').'</option>';
                                            echo "<option selected value='Paid'>".__($guid, 'Paid').'</option>';
                                        }
                                        echo '</select>';
                                    } else {
                                        ?>
										<input readonly name="status" id="status" maxlength=60 value="<?php echo $row['status'];
                                        ?>" type="text" class="standardWidth">
										<?php

                                    }
                    ?>
									<script type="text/javascript">
										var status=new LiveValidation('status');
										status.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
									</script>
								</td>
							</tr>
							<tr>
								<td colspan=2> 
									<b><?php echo __($guid, 'Description') ?></b>
									<?php 
                                        echo '<p>';
                    echo $row['body'];
                    echo '</p>'
                                    ?>
								</td>
							</tr>
							<tr>
								<td> 
									<b><?php echo __($guid, 'Purchase By') ?> *</b><br/>
								</td>
								<td class="right">
									<input readonly name="purchaseBy" id="purchaseBy" maxlength=60 value="<?php echo $row['purchaseBy'];
                    ?>" type="text" class="standardWidth">
								</td>
							</tr>
							<tr>
								<td colspan=2> 
									<b><?php echo __($guid, 'Purchase Details') ?></b>
									<?php 
                                        echo '<p>';
                    echo $row['purchaseDetails'];
                    echo '</p>'
                                    ?>
								</td>
							</tr>
					
							<tr class='break'>
								<td colspan=2> 
									<h3><?php echo __($guid, 'Log') ?></h3>
								</td>
							</tr>
							<tr>
								<td colspan=2> 
									<?php
                                    echo getExpenseLog($guid, $gibbonFinanceExpenseID, $connection2);
                    ?>
								</td>
							</tr>
							
							<script type="text/javascript">
								$(document).ready(function(){
									$("#status").change(function(){
										if ($('#status option:selected').val()=="Paid" ) {
											$("#paidTitle").slideDown("fast", $("#paidTitle").css("display","table-row")); 
											$("#paymentDateRow").slideDown("fast", $("#paymentDateRow").css("display","table-row")); 
											$("#paymentAmountRow").slideDown("fast", $("#paymentAmountRow").css("display","table-row")); 
											$("#payeeRow").slideDown("fast", $("#payeeRow").css("display","table-row")); 
											$("#paymentMethodRow").slideDown("fast", $("#paymentMethodRow").css("display","table-row")); 
											$("#paymentIDRow").slideDown("fast", $("#paymentIDRow").css("display","table-row")); 
											paymentDate.enable() ;
											paymentAmount.enable() ;
											paymentMethod.enable() ;
											file.enable() ;
										} else {
											$("#paidTitle").css("display","none");
											$("#paymentDateRow").css("display","none");
											$("#paymentAmountRow").css("display","none");
											$("#payeeRow").css("display","none");
											$("#paymentMethodRow").css("display","none");
											$("#paymentIDRow").css("display","none");
											paymentDate.disable() ;
											paymentAmount.disable() ;
											paymentMethod.disable() ;
											file.disable() ;
										}
									 });
								});
							</script>
							<tr class='break' id="paidTitle">
								<td colspan=2> 
									<h3><?php echo __($guid, 'Payment Information') ?></h3>
								</td>
							</tr>
							<tr id="paymentDateRow">
								<td> 
									<b><?php echo __($guid, 'Date Paid') ?> *</b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Date of payment, not entry to system.') ?></span>
								</td>
								<td class="right">
									<input name="paymentDate" id="paymentDate" maxlength=10 value="" type="text" class="standardWidth">
									<script type="text/javascript">
										var paymentDate=new LiveValidation('paymentDate');
										paymentDate.add( Validate.Format, {pattern: <?php if ($_SESSION[$guid]['i18n']['dateFormatRegEx'] == '') {
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
										paymentDate.add(Validate.Presence);
									</script>
									 <script type="text/javascript">
										$(function() {
											$( "#paymentDate" ).datepicker();
										});
									</script>
								</td>
							</tr>
							<tr id="paymentAmountRow">
								<td> 
									<b><?php echo __($guid, 'Amount Paid') ?> *</b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Final amount paid.') ?>
									<?php
                                    if ($_SESSION[$guid]['currency'] != '') {
                                        echo "<span style='font-style: italic; font-size: 85%'>".$_SESSION[$guid]['currency'].'</span>';
                                    }
                    ?>
									</span>
								</td>
								<td class="right">
									<input name="paymentAmount" id="paymentAmount" maxlength=15 value="" type="text" class="standardWidth">
									<script type="text/javascript">
										var paymentAmount=new LiveValidation('paymentAmount');
										paymentAmount.add( Validate.Format, { pattern: /^(?:\d*\.\d{1,2}|\d+)$/, failureMessage: "Invalid number format!" } );
										paymentAmount.add(Validate.Presence);
									</script>
								</td>
							</tr>
							<tr id="payeeRow">
								<td> 
									<b><?php echo __($guid, 'Payee') ?> *</b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Staff who made, or arranged, the payment.') ?></span>
								</td>
								<td class="right">
									<input readonly name="name" id="name" value="<?php echo formatName('', ($_SESSION[$guid]['preferredName']), htmlPrep($_SESSION[$guid]['surname']), 'Staff', true, true) ?>" type="text" class="standardWidth">
									<input name="gibbonPersonIDPayment" id="gibbonPersonIDPayment" value="<?php echo $_SESSION[$guid]['gibbonPersonID'] ?>" type="hidden">
								</td>
							</tr>
							<tr id="paymentMethodRow">
								<td> 
									<b><?php echo __($guid, 'Payment Method') ?> *</b><br/>
								</td>
								<td class="right">
									<?php
                                    echo "<select name='paymentMethod' id='paymentMethod' style='width:302px'>";
                    echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
                    echo "<option value='Bank Transfer'>Bank Transfer</option>";
                    echo "<option value='Cash'>Cash</option>";
                    echo "<option value='Cheque'>Cheque</option>";
                    echo "<option value='Credit Card'>Credit Card</option>";
                    echo "<option value='Other'>Other</option>";
                    echo '</select>';
                    ?>
									<script type="text/javascript">
										var paymentMethod=new LiveValidation('paymentMethod');
										paymentMethod.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
									</script>
								</td>
							</tr>
							<tr id="paymentIDRow">
								<td> 
									<b><?php echo __($guid, 'Payment Receipt') ?> *</b><br/>
									<span class="emphasis small"><?php echo __($guid, 'Digital copy of the receipt for this payment.') ?></span>
								</td>
								<td class="right">
									<input type="file" name="file" id="file"><br/><br/>
										<?php
                                        echo getMaxUpload($guid);
                    $ext = "'.png','.jpeg','.jpg','.gif','.pdf'";
                    ?>
										<script type="text/javascript">
											var file=new LiveValidation('file');
											file.add( Validate.Inclusion, { within: [<?php echo $ext;
                    ?>], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
											file.add(Validate.Presence);
										</script>
									</td>
								</td>
							</tr>
							
							<tr>
								<td>
									<span class="emphasis small">* <?php echo __($guid, 'denotes a required field');
                    ?></span>
								</td>
								<td class="right">
									<input name="gibbonFinanceExpenseID" id="gibbonFinanceExpenseID" value="<?php echo $gibbonFinanceExpenseID ?>" type="hidden">
									<input name="gibbonFinanceBudgetID" id="gibbonFinanceBudgetID" value="<?php echo $row['gibbonFinanceBudgetID'] ?>" type="hidden">
									<input name="status2" id="status2" value="<?php echo $status2 ?>" type="hidden">
									<input name="gibbonFinanceBudgetID2" id="gibbonFinanceBudgetID2" value="<?php echo $gibbonFinanceBudgetID2 ?>" type="hidden">
									<input type="hidden" name="address" value="<?php echo $_SESSION[$guid]['address'] ?>">
									<input type="submit" value="<?php echo __($guid, 'Submit');
                    ?>">
								</td>
							</tr>
						</table>
					</form>
					<?php

                }
            }
        }
    }
}
?>