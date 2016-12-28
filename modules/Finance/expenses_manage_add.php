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

if (isActionAccessible($guid, $connection2, '/modules/Finance/expenses_manage_add.php', 'Manage Expenses_all') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    $allowExpenseAdd = getSettingByScope($connection2, 'Finance', 'allowExpenseAdd');
    if ($allowExpenseAdd != 'Y') {
        echo "<div class='error'>";
        echo __($guid, 'You do not have access to this action.');
        echo '</div>';
    } else {
        //Proceed!
        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Finance/expenses_manage.php&gibbonFinanceBudgetCycleID='.$_GET['gibbonFinanceBudgetCycleID']."'>".__($guid, 'Manage Expenses')."</a> > </div><div class='trailEnd'>".__($guid, 'Add Expense').'</div>';
        echo '</div>';

        echo "<div class='warning'>";
        echo __($guid, 'Expenses added here do not require authorisation: this is for pre-authorised, or recurring expenses only.');
        echo '</div>';

        $editLink = '';
        if (isset($_GET['editID'])) {
            $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Finance/expenses_manage_edit.php&gibbonFinanceExpenseID='.$_GET['editID'].'&gibbonFinanceBudgetCycleID='.$_GET['gibbonFinanceBudgetCycleID'].'&status2='.$_GET['status2'].'&gibbonFinanceBudgetID2='.$_GET['gibbonFinanceBudgetID2'];
        }
        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], $editLink, null);
        }

        //Check if school year specified
        $gibbonFinanceBudgetCycleID = $_GET['gibbonFinanceBudgetCycleID'];
        $status2 = $_GET['status2'];
        $gibbonFinanceBudgetID2 = $_GET['gibbonFinanceBudgetID2'];
        if ($gibbonFinanceBudgetCycleID == '') {
            echo "<div class='error'>";
            echo __($guid, 'You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            if ($status2 != '' or $gibbonFinanceBudgetID2 != '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Finance/expenses_manage.php&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status2=$status2&gibbonFinanceBudgetID2=$gibbonFinanceBudgetID2'>".__($guid, 'Back to Search Results').'</a>';
                echo '</div>';
            }
            ?>
			<form method="post" action="<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/expenses_manage_addProcess.php' ?>">
				<table class='smallIntBorder fullWidth' cellspacing='0'>
					<tr>
						<tr class='break'>
							<td colspan=2>
								<h3><?php echo __($guid, 'Basic Information') ?></h3>
							</td>
						</tr>

						<td style='width: 275px'>
							<b><?php echo __($guid, 'Budget Cycle') ?> *</b><br/>
							<span class="emphasis small"><?php echo __($guid, 'This value cannot be changed.') ?></span>
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
							<?php
                            try {
                                $data = array();
                                $sql = "SELECT * FROM gibbonFinanceBudget WHERE active='Y' ORDER BY name";
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                            }

							echo "<select name='gibbonFinanceBudgetID' id='gibbonFinanceBudgetID' style='width:302px'>";
							$selected = '';
							if ($gibbonFinanceBudgetID == '') {
								$selected = 'selected';
							}
							echo "<option $selected value='Please select...'>".__($guid, 'Please select...').'</option>';
							while ($row = $result->fetch()) {
								$selected = '';
								if ($gibbonFinanceBudgetID == $row['gibbonFinanceBudgetID']) {
									$selected = 'selected';
								}
								echo "<option $selected value='".$row['gibbonFinanceBudgetID']."'>".$row['name'].'</option>';
							}
							echo '</select>'; ?>
							<script type="text/javascript">
								var gibbonFinanceBudgetID=new LiveValidation('gibbonFinanceBudgetID');
								gibbonFinanceBudgetID.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
							</script>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Title') ?> *</b><br/>
						</td>
						<td class="right">
							<input name="title" id="title" maxlength=60 value="" type="text" class="standardWidth">
							<script type="text/javascript">
								var title=new LiveValidation('title');
								title.add(Validate.Presence);
							</script>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Status') ?> *</b><br/>
						</td>
						<td class="right">
							<?php
                            echo "<select name='status' id='status3' style='width:302px'>";
							echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
							echo "<option value='Approved'>".__($guid, 'Approved').'</option>';
							echo "<option value='Ordered'>".__($guid, 'Ordered').'</option>';
							echo "<option value='Paid'>".__($guid, 'Paid').'</option>';
							echo '</select>'; ?>
							<script type="text/javascript">
								var status3=new LiveValidation('status3');
								status3.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
							</script>
						</td>
					</tr>
					<tr>
						<td colspan=2>
							<b><?php echo __($guid, 'Description') ?></b>
							<?php $expenseRequestTemplate = getSettingByScope($connection2, 'Finance', 'expenseRequestTemplate') ?>
							<?php echo getEditor($guid,  true, 'body', $expenseRequestTemplate, 25, true, false, false) ?>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Total Cost') ?> *</b><br/>
							<span style="font-size: 90%">
								<i>
								<?php
                                if ($_SESSION[$guid]['currency'] != '') {
                                    echo sprintf(__($guid, 'Numeric value of the fee in %1$s.'), $_SESSION[$guid]['currency']);
                                } else {
                                    echo __($guid, 'Numeric value of the fee.');
                                }
           	 					?>
								</i>
							</span>
						</td>
						<td class="right">
							<input name="cost" id="cost" maxlength=15 value="" type="text" class="standardWidth">
							<script type="text/javascript">
								var cost=new LiveValidation('cost');
								cost.add(Validate.Presence);
								cost.add( Validate.Format, { pattern: /^(?:\d*\.\d{1,2}|\d+)$/, failureMessage: "Invalid number format!" } );
							</script>
						</td>
					</tr>
					<tr>
						<td>
							<b><?php echo __($guid, 'Count Against Budget') ?> *</b><br/>
							<span class="emphasis small">
								<?php echo __($guid, 'For tracking purposes, should the item be counted against the budget? If immediately offset by some revenue, perhaps not.'); ?>
							</span>
						</td>
						<td class="right">
							<select name="countAgainstBudget" id="countAgainstBudget" class="standardWidth">
								<?php
                                echo "<option selected value='Y'>".ynExpander($guid, 'Y').'</option>';
            					echo "<option value='N'>".ynExpander($guid, 'N').'</option>'; ?>
							</select>
						</td>
					</tr>

					<tr>
						<td style='width: 275px'>
							<b><?php echo __($guid, 'Purchase By') ?> *</b><br/>
						</td>
						<td class="right">
							<?php
                            echo "<select name='purchaseBy' id='purchaseBy' style='width:302px'>";
							echo "<option value='School'>School</option>";
							echo "<option value='Self'>Self</option>";
							echo '</select>'; ?>
						</td>
					</tr>

					<tr>
						<td colspan=2>
							<b><?php echo __($guid, 'Purchase Details') ?></b><br/>
							<textarea name="purchaseDetails" id="purchaseDetails" rows=8 style="width: 100%"></textarea>
						</td>
					</tr>
					<script type="text/javascript">
						$(document).ready(function(){
							$("#paidTitle").css("display","none");
							$("#paymentDateRow").css("display","none");
							$("#paymentAmountRow").css("display","none");
							$("#payeeRow").css("display","none");
							$("#paymentMethodRow").css("display","none");
							$("#paymentIDRow").css("display","none");
							paymentDate.disable() ;
							paymentAmount.disable() ;
							gibbonPersonIDPayment.disable() ;
							paymentMethod.disable() ;
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
									gibbonPersonIDPayment.enable() ;
									paymentMethod.enable() ;
								} else {
									$("#paidTitle").css("display","none");
									$("#paymentDateRow").css("display","none");
									$("#paymentAmountRow").css("display","none");
									$("#payeeRow").css("display","none");
									$("#paymentMethodRow").css("display","none");
									$("#paymentIDRow").css("display","none");
									paymentDate.disable() ;
									paymentAmount.disable() ;
									gibbonPersonIDPayment.disable() ;
									paymentMethod.disable() ;
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
							<select name="gibbonPersonIDPayment" id="gibbonPersonIDPayment" class="standardWidth">
								<?php
                                echo "<option value='Please select...'>".__($guid, 'Please select...').'</option>';
								try {
									$dataSelect = array();
									$sqlSelect = "SELECT * FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) WHERE status='Full' ORDER BY surname, preferredName";
									$resultSelect = $connection2->prepare($sqlSelect);
									$resultSelect->execute($dataSelect);
								} catch (PDOException $e) {
								}
								while ($rowSelect = $resultSelect->fetch()) {
									echo "<option value='".$rowSelect['gibbonPersonID']."'>".formatName(htmlPrep($rowSelect['title']), ($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Staff', true, true).'</option>';
								}
								?>
							</select>
							<script type="text/javascript">
								var gibbonPersonIDPayment=new LiveValidation('gibbonPersonIDPayment');
								gibbonPersonIDPayment.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
							</script>
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
							echo '</select>'; ?>
							<script type="text/javascript">
								var paymentMethod=new LiveValidation('paymentMethod');
								paymentMethod.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
							</script>
						</td>
					</tr>
					<tr id="paymentIDRow">
						<td>
							<b><?php echo __($guid, 'Payment ID') ?></b><br/>
							<span class="emphasis small"><?php echo __($guid, 'Transaction ID to identify this payment.') ?></span>
						</td>
						<td class="right">
							<input name="paymentID" id="paymentID" maxlength=100 value="" type="text" class="standardWidth">
						</td>
					</tr>


					<tr>
						<td>
							<span class="emphasis small">* <?php echo __($guid, 'denotes a required field'); ?></span>
						</td>
						<td class="right">
							<input name="status2" id="status2" value="<?php echo $status2 ?>" type="hidden">
							<input name="gibbonFinanceBudgetID2" id="gibbonFinanceBudgetID2" value="<?php echo $gibbonFinanceBudgetID2 ?>" type="hidden">
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
