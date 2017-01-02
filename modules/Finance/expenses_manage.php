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
include './modules/Finance/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Finance/expenses_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        //Proceed!
        echo "<div class='trail'>";
        echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Manage Expenses').'</div>';
        echo '</div>';

        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], null, array('success0' => 'Your request was completed successfully.', 'success1' => 'Your request was completed successfully, but notifications could not be sent out.'));
        }

        echo '<p>';
        if ($highestAction == 'Manage Expenses_all') {
            echo __($guid, 'This action allows you to manage all expenses for all budgets, regardless of your access rights to individual budgets.').'<br/>';
        } else {
            echo __($guid, 'This action allows you to manage expenses for the budgets in which you have relevant access rights.').'<br/>';
        }
        echo '</p>';

        //Check if have Full, Write or Read access in any budgets
        $budgetsAccess = false;
        $budgets = getBudgetsByPerson($connection2, $_SESSION[$guid]['gibbonPersonID']);
        $budgetsAll = null;
        if ($highestAction == 'Manage Expenses_all') {
            $budgetsAll = getBudgets($connection2);
            $budgetsAccess = true;
        } else {
            if (is_array($budgets) && count($budgets)>0) {
                foreach ($budgets as $budget) {
                    if ($budget[2] == 'Full' or $budget[2] == 'Write' or $budget[2] == 'READ') {
                        $budgetsAccess = true;
                    }
                }
            }
        }

        if ($budgetsAccess == false) {
            echo "<div class='error'>";
            echo __($guid, 'You do not have Full or Write access to any budgets.');
            echo '</div>';
        } else {
            //Get and check settings
            $expenseApprovalType = getSettingByScope($connection2, 'Finance', 'expenseApprovalType');
            $budgetLevelExpenseApproval = getSettingByScope($connection2, 'Finance', 'budgetLevelExpenseApproval');
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
                    //Ready to go!
                    $gibbonFinanceBudgetCycleID = '';
                    if (isset($_GET['gibbonFinanceBudgetCycleID'])) {
                        $gibbonFinanceBudgetCycleID = $_GET['gibbonFinanceBudgetCycleID'];
                    }
                    if ($gibbonFinanceBudgetCycleID == '') {
                        try {
                            $data = array();
                            $sql = "SELECT * FROM gibbonFinanceBudgetCycle WHERE status='Current'";
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        if ($result->rowcount() != 1) {
                            echo "<div class='error'>";
                            echo __($guid, 'The Current budget cycle cannot be determined.');
                            echo '</div>';
                        } else {
                            $row = $result->fetch();
                            $gibbonFinanceBudgetCycleID = $row['gibbonFinanceBudgetCycleID'];
                            $gibbonFinanceBudgetCycleName = $row['name'];
                        }
                    }
                    if ($gibbonFinanceBudgetCycleID != '') {
                        try {
                            $data = array('gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID);
                            $sql = 'SELECT * FROM gibbonFinanceBudgetCycle WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            echo "<div class='error'>".$e->getMessage().'</div>';
                        }
                        if ($result->rowcount() != 1) {
                            echo "<div class='error'>";
                            echo __($guid, 'The specified budget cycle cannot be determined.');
                            echo '</div>';
                        } else {
                            $row = $result->fetch();
                            $gibbonFinanceBudgetCycleName = $row['name'];
                        }

                        echo '<h2>';
                        echo $gibbonFinanceBudgetCycleName;
                        echo '</h2>';

                        echo "<div class='linkTop'>";
                            //Print year picker
                            $previousCycle = getPreviousBudgetCycleID($gibbonFinanceBudgetCycleID, $connection2);
                        if ($previousCycle != false) {
                            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/expenses_manage.php&gibbonFinanceBudgetCycleID='.$previousCycle."'>".__($guid, 'Previous Cycle').'</a> ';
                        } else {
                            echo __($guid, 'Previous Cycle').' ';
                        }
                        echo ' | ';
                        $nextCycle = getNextBudgetCycleID($gibbonFinanceBudgetCycleID, $connection2);
                        if ($nextCycle != false) {
                            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/expenses_manage.php&gibbonFinanceBudgetCycleID='.$nextCycle."'>".__($guid, 'Next Cycle').'</a> ';
                        } else {
                            echo __($guid, 'Next Cycle').' ';
                        }
                        echo '</div>';

                        $status2 = null;
                        if (isset($_GET['status2'])) {
                            $status2 = $_GET['status2'];
                        }
                        $gibbonFinanceBudgetID2 = null;
                        if (isset($_GET['gibbonFinanceBudgetID2'])) {
                            $gibbonFinanceBudgetID2 = $_GET['gibbonFinanceBudgetID2'];
                        }

                        echo '<h3>';
                        echo __($guid, 'Filters');
                        echo '</h3>';
                        echo "<form method='get' action='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Finance/expenses_manage.php'>";
                        echo "<table class='noIntBorder' cellspacing='0' style='width: 100%'>";
                        ?>
						<tr>
							<td>
								<b><?php echo __($guid, 'Status') ?></b><br/>
								<span class="emphasis small"></span>
							</td>
							<td class="right">
								<?php
								echo "<select name='status2' id='status2' style='width:302px'>";
									$selected = '';
									if ($status2 == '') {
										$selected = 'selected';
									}
									echo "<option $selected value=''>".__($guid, 'All').'</option>';
									$selected = '';
									if ($status2 == 'Requested') {
										$selected = 'selected';
									}
									echo "<option $selected value='Requested'>".__($guid, 'Requested').'</option>';
									$selected = '';
									if ($status2 == 'Requested - Approval Required') {
										$selected = 'selected';
									}
									echo "<option $selected value='Requested - Approval Required'>".__($guid, 'Requested - Approval Required').'</option>';
									$selected = '';
									if ($status2 == 'Approved') {
										$selected = 'selected';
									}
									echo "<option $selected value='Approved'>".__($guid, 'Approved').'</option>';
									$selected = '';
									if ($status2 == 'Rejected') {
										$selected = 'selected';
									}
									echo "<option $selected value='Rejected'>".__($guid, 'Rejected').'</option>';
									$selected = '';
									if ($status2 == 'Cancelled') {
										$selected = 'selected';
									}
									echo "<option $selected value='Cancelled'>".__($guid, 'Cancelled').'</option>';
									$selected = '';
									if ($status2 == 'Ordered') {
										$selected = 'selected';
									}
									echo "<option $selected value='Ordered'>".__($guid, 'Ordered').'</option>';
									$selected = '';
									if ($status2 == 'Paid') {
										$selected = 'selected';
									}
									echo "<option $selected value='Paid'>".__($guid, 'Paid').'</option>';
									echo '</select>';
									?>
									</td>
								</tr>
								<tr>
									<td>
										<b><?php echo __($guid, 'Budget') ?></b><br/>
										<span class="emphasis small"></span>
									</td>
									<td class="right">
										<?php
                                        echo "<select name='gibbonFinanceBudgetID2' id='gibbonFinanceBudgetID2' style='width:302px'>";
										$selected = '';
										if ($gibbonFinanceBudgetID2 == '') {
											$selected = 'selected';
										}
										echo "<option $selected value=''>".__($guid, 'All').'</option>';
										if ($budgetsAll == null) {
											$budgetsAll = $budgets;
										}
										foreach ($budgetsAll as $budget) {
											$selected = '';
											if ($gibbonFinanceBudgetID2 == $budget[0]) {
												$selected = 'selected';
											}
											echo "<option $selected value='".$budget[0]."'>".$budget[1].'</option>';
										}
										echo '</select>';
										?>
									</td>
								</tr>
								<?php

                                echo '<tr>';
								echo "<td class='right' colspan=2>";
								echo "<input type='hidden' name='gibbonFinanceBudgetCycleID' value='$gibbonFinanceBudgetCycleID'>";
								echo "<input type='hidden' name='q' value='".$_GET['q']."'>";
								echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Finance/expenses_manage.php&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID'>".__($guid, 'Clear Filters').'</a> ';
								echo "<input type='submit' value='".__($guid, 'Go')."'>";
								echo '</td>';
								echo '</tr>';
								echo '</table>';
								echo '</form>';

								try {
									//Set Up filter wheres
									$data = array('gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID);
									$whereBudget = '';
									if ($gibbonFinanceBudgetID2 != '') {
										$data['gibbonFinanceBudgetID'] = $gibbonFinanceBudgetID2;
										$whereBudget .= ' AND gibbonFinanceBudget.gibbonFinanceBudgetID=:gibbonFinanceBudgetID';
									}
									$approvalRequiredFilter = false;
									$whereStatus = '';
									if ($status2 != '') {
										if ($status2 == 'Requested - Approval Required') {
											$data['status'] = 'Requested';
											$approvalRequiredFilter = true;
										} else {
											$data['status'] = $status2;
										}
										$whereStatus .= ' AND gibbonFinanceExpense.status=:status';
									}
									//GET THE DATA ACCORDING TO FILTERS
									if ($highestAction == 'Manage Expenses_all') { //Access to everything
										$sql = "SELECT gibbonFinanceExpense.*, gibbonFinanceBudget.name AS budget, surname, preferredName, 'Full' AS access
											FROM gibbonFinanceExpense
											JOIN gibbonFinanceBudget ON (gibbonFinanceExpense.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID)
											JOIN gibbonPerson ON (gibbonFinanceExpense.gibbonPersonIDCreator=gibbonPerson.gibbonPersonID)
											WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID $whereBudget $whereStatus
											ORDER BY FIND_IN_SET(gibbonFinanceExpense.status, 'Pending,Issued,Paid,Refunded,Cancelled'), timestampCreator DESC";
									} else { //Access only to own budgets
										$data['gibbonPersonID'] = $_SESSION[$guid]['gibbonPersonID'];
										$sql = "SELECT gibbonFinanceExpense.*, gibbonFinanceBudget.name AS budget, surname, preferredName, access
											FROM gibbonFinanceExpense
											JOIN gibbonFinanceBudget ON (gibbonFinanceExpense.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID)
											JOIN gibbonFinanceBudgetPerson ON (gibbonFinanceBudgetPerson.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID)
											JOIN gibbonPerson ON (gibbonFinanceExpense.gibbonPersonIDCreator=gibbonPerson.gibbonPersonID)
											WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID AND gibbonFinanceBudgetPerson.gibbonPersonID=:gibbonPersonID $whereBudget $whereStatus
											ORDER BY FIND_IN_SET(gibbonFinanceExpense.status, 'Pending,Issued,Paid,Refunded,Cancelled'), timestampCreator DESC";
									}
									$result = $connection2->prepare($sql);
									$result->execute($data);
								} catch (PDOException $e) {
									echo "<div class='error'>".$e->getMessage().'</div>';
								}

								echo '<h3>';
								echo __($guid, 'View');
								echo '</h3>';

								$allowExpenseAdd = getSettingByScope($connection2, 'Finance', 'allowExpenseAdd');
								if ($highestAction == 'Manage Expenses_all' and $allowExpenseAdd == 'Y') { //Access to everything
									echo "<div class='linkTop' style='text-align: right'>";
									echo "<a style='margin-right: 3px' href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/expenses_manage_add.php&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status2=$status2&gibbonFinanceBudgetID2=$gibbonFinanceBudgetID2'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a><br/>";
									echo '</div>';
								}

								echo "<form onsubmit='return confirm(\"".__($guid, 'Are you sure you wish to process this action? It cannot be undone.')."\")' method='post' action='".$_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/expenses_manage_processBulk.php?gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status2=$status2&gibbonFinanceBudgetID2=$gibbonFinanceBudgetID2'>";
								echo "<fieldset style='border: none'>";
								echo "<div class='linkTop' style='text-align: right; margin-bottom: 40px'>";
								?>
									<input style='margin-top: 0px; float: right' type='submit' value='<?php echo __($guid, 'Go') ?>'>
									<select name="action" id="action" style='width:120px; float: right; margin-right: 1px;'>
										<option value="Select action"><?php echo __($guid, 'Select action') ?></option>
										<option value="export"><?php echo __($guid, 'Export') ?></option>
									</select>
									<script type="text/javascript">
										var action=new LiveValidation('action');
										action.add(Validate.Exclusion, { within: ['Select action'], failureMessage: "<?php echo __($guid, 'Select something!') ?>"});
									</script>
									<?php
                                	echo '</div>';
									echo "<table cellspacing='0' style='width: 100%'>";
									echo "<tr class='head'>";
									echo "<th style='width: 110px'>";
									echo __($guid, 'Title').'<br/>';
									echo "<span style='font-size: 85%; font-style: italic'>".__($guid, 'Budget').'</span>';
									echo '</th>';
									echo "<th style='width: 110px'>";
									echo __($guid, 'Staff');
									echo '</th>';
									echo "<th style='width: 100px'>";
									echo __($guid, 'Status');
									echo '</th>';
									echo "<th style='width: 90px'>";
									echo __($guid, 'Cost')."<br/><span style='font-style: italic; font-size: 75%'>(".$_SESSION[$guid]['currency'].')</span><br/>';
									echo '</th>';
									echo "<th style='width: 120px'>";
									echo __($guid, 'Date');
									echo '</th>';
									echo "<th style='width: 140px'>";
									echo __($guid, 'Actions');
									echo '</th>';
									echo '<th>';
									?>
									<script type="text/javascript">
										$(function () {
											$('.checkall').click(function () {
												$(this).parents('fieldset:eq(0)').find(':checkbox').attr('checked', this.checked);
											});
										});
									</script>
									<?php
									echo "<input type='checkbox' class='checkall'>";
									echo '</th>';
									echo '</tr>';

									$count = 0;
									$rowNum = 'odd';
									while ($row = $result->fetch()) {
										$approvalRequired = approvalRequired($guid, $_SESSION[$guid]['gibbonPersonID'], $row['gibbonFinanceExpenseID'], $gibbonFinanceBudgetCycleID, $connection2, false);
										if ($approvalRequiredFilter == false or ($approvalRequiredFilter and $approvalRequired)) {
											if ($count % 2 == 0) {
												$rowNum = 'even';
											} else {
												$rowNum = 'odd';
											}
											++$count;

                                            //Color row by status
                                            if ($row['status'] == 'Approved') {
                                                $rowNum = 'current';
                                            }
                                if ($row['status'] == 'Rejected' or $row['status'] == 'Cancelled') {
                                    $rowNum = 'error';
                                }

                                echo "<tr class=$rowNum>";
                                echo '<td>';
                                echo '<b>'.$row['title'].'</b><br/>';
                                echo "<span style='font-size: 85%; font-style: italic'>".$row['budget'].'</span>';
                                echo '</td>';
                                echo '<td>';
                                echo formatName('', $row['preferredName'], $row['surname'], 'Staff', false, true);
                                echo '</td>';
                                echo '<td>';
                                echo $row['status'];
                                echo '</td>';
                                echo '<td>';
                                echo number_format($row['cost'], 2, '.', ',');
                                echo '</td>';
                                echo '<td>';
                                echo dateConvertBack($guid, substr($row['timestampCreator'], 0, 10));
                                echo '</td>';
                                echo '<td>';
                                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/expenses_manage_view.php&gibbonFinanceExpenseID='.$row['gibbonFinanceExpenseID']."&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status2=$status2&gibbonFinanceBudgetID2=$gibbonFinanceBudgetID2'><img title='".__($guid, 'View')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a> ";
                                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/expenses_manage_print.php&gibbonFinanceExpenseID='.$row['gibbonFinanceExpenseID']."&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status2=$status2&gibbonFinanceBudgetID2=$gibbonFinanceBudgetID2'><img title='Print' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/print.png'/></a>";
                                if (isActionAccessible($guid, $connection2, '/modules/Finance/expenses_manage_add.php', 'Manage Expenses_all')) {
                                    if ($row['status'] == 'Requested' or $row['status'] == 'Approved' or $row['status'] == 'Ordered') {
                                        echo "<a style='margin-left: 4px' href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/expenses_manage_edit.php&gibbonFinanceExpenseID='.$row['gibbonFinanceExpenseID']."&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status2=$status2&gibbonFinanceBudgetID2=$gibbonFinanceBudgetID2'><img title='".__($guid, 'Edit')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/config.png'/></a> ";
                                    }
                                }
                                if ($row['status'] == 'Requested') {
                                    if ($approvalRequired == true) {
                                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/expenses_manage_approve.php&gibbonFinanceExpenseID='.$row['gibbonFinanceExpenseID']."&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status2=$status2&gibbonFinanceBudgetID2=$gibbonFinanceBudgetID2'><img title='".__($guid, 'Approve/Reject')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/iconTick.png'/></a> ";
                                    }
                                }
                                echo '</td>';
                                echo '<td>';
                                echo "<input style='margin-left: -6px' type='checkbox' name='gibbonFinanceExpenseIDs[]' value='".$row['gibbonFinanceExpenseID']."'>";
                                echo '</td>';
                                echo '</tr>';
                            }
                        }
                        if ($count < 1) {
                            echo '<tr>';
                            echo '<td colspan=7>';
                            echo __($guid, 'There are no records to display.');
                            echo '</td>';
                            echo '</tr>';
                        }
                        echo '<input type="hidden" name="address" value="'.$_SESSION[$guid]['address'].'">';

                        echo '</fieldset>';
                        echo '</table>';
                        echo '</form>';
                    }
                }
            }
        }
    }
}
?>
