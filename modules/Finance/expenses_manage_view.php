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
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Finance\ExpenseGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Finance/expenses_manage_view.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
    } else {
        //Proceed!
        $gibbonFinanceBudgetCycleID = $_GET['gibbonFinanceBudgetCycleID'] ?? '';

        $urlParams = compact('gibbonFinanceBudgetCycleID');

        $page->breadcrumbs
            ->add(__('Manage Expenses'), 'expenses_manage.php', $urlParams)
            ->add(__('View Expense'));

        //Check if params are specified
        $gibbonFinanceExpenseID = $_GET['gibbonFinanceExpenseID'] ?? '';
        $status2 = $_GET['status2'] ?? '';
        $gibbonFinanceBudgetID2 = $_GET['gibbonFinanceBudgetID2'] ?? '';
        if ($gibbonFinanceExpenseID == '' or $gibbonFinanceBudgetCycleID == '') {
            $page->addError(__('You have not specified one or more required parameters.'));
        } else {
            //Check if have Full or Write in any budgets
            $budgets = getBudgetsByPerson($connection2, $session->get('gibbonPersonID'));
            $budgetsAccess = false;
            if ($highestAction == 'Manage Expenses_all') { //Access to everything {
                $budgetsAccess = true;
            } else {
                //Check if have Full or Write in any budgets
                $budgets = getBudgetsByPerson($connection2, $session->get('gibbonPersonID'));
                if (is_array($budgets) && count($budgets)>0) {
                    foreach ($budgets as $budget) {
                        if ($budget[2] == 'Full' or $budget[2] == 'Write') {
                            $budgetsAccess = true;
                        }
                    }
                }
            }

            if ($budgetsAccess == false) {
                $page->addError(__('You do not have Full or Write access to any budgets.'));
            } else {
                //Get and check settings
                $settingGateway = $container->get(SettingGateway::class);
                $expenseApprovalType = $settingGateway->getSettingByScope('Finance', 'expenseApprovalType');
                $budgetLevelExpenseApproval = $settingGateway->getSettingByScope('Finance', 'budgetLevelExpenseApproval');
                $expenseRequestTemplate = $settingGateway->getSettingByScope('Finance', 'expenseRequestTemplate');
                if ($expenseApprovalType == '' or $budgetLevelExpenseApproval == '') {
                    $page->addError(__('An error has occurred with your expense and budget settings.'));
                } else {
                    //Check if there are approvers
                    try {
                        $data = array();
                        $sql = "SELECT * FROM gibbonFinanceExpenseApprover JOIN gibbonPerson ON (gibbonFinanceExpenseApprover.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full'";
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                    }

                    if ($result->rowCount() < 1) {
                        $page->addError(__('An error has occurred with your expense and budget settings.'));
                    } else {
                        //Ready to go! Just check record exists and we have access, and load it ready to use...
                        try {
                            //Set Up filter wheres
                            $data = array('gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID, 'gibbonFinanceExpenseID' => $gibbonFinanceExpenseID);
                            //GET THE DATA ACCORDING TO FILTERS
                            if ($highestAction == 'Manage Expenses_all') { //Access to everything
                                $sql = "SELECT gibbonFinanceExpense.*, gibbonFinanceBudget.name AS budget, surname, preferredName, 'Full' AS access
									FROM gibbonFinanceExpense
									JOIN gibbonFinanceBudget ON (gibbonFinanceExpense.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID)
									JOIN gibbonPerson ON (gibbonFinanceExpense.gibbonPersonIDCreator=gibbonPerson.gibbonPersonID)
									WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID AND gibbonFinanceExpenseID=:gibbonFinanceExpenseID
									ORDER BY FIND_IN_SET(gibbonFinanceExpense.status, 'Pending,Issued,Paid,Refunded,Cancelled'), timestampCreator DESC";
                            } else { //Access only to own budgets
                                $data['gibbonPersonID'] = $session->get('gibbonPersonID');
                                $sql = "SELECT gibbonFinanceExpense.*, gibbonFinanceBudget.name AS budget, surname, preferredName, access
									FROM gibbonFinanceExpense
									JOIN gibbonFinanceBudget ON (gibbonFinanceExpense.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID)
									JOIN gibbonFinanceBudgetPerson ON (gibbonFinanceBudgetPerson.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID)
									JOIN gibbonPerson ON (gibbonFinanceExpense.gibbonPersonIDCreator=gibbonPerson.gibbonPersonID)
									WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID AND gibbonFinanceExpenseID=:gibbonFinanceExpenseID AND gibbonFinanceBudgetPerson.gibbonPersonID=:gibbonPersonID
									ORDER BY FIND_IN_SET(gibbonFinanceExpense.status, 'Pending,Issued,Paid,Refunded,Cancelled'), timestampCreator DESC";
                            }
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                        }

                        if ($result->rowCount() != 1) {
                            $page->addError(__('The specified record cannot be found.'));
                        } else {
                            //Let's go!
                            $row = $result->fetch();

                            if ($status2 != '' or $gibbonFinanceBudgetID2 != '') {
                                $params = [
                                    "gibbonFinanceBudgetCycleID" => $gibbonFinanceBudgetCycleID,
                                    "status2" => $status2,
                                    "gibbonFinanceBudgetID2" => $gibbonFinanceBudgetID2,
                                ];
                                $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Finance', 'expenses_manage.php')->withQueryParams($params));
                            }
                            ?>
                                <table class='smallIntBorder fullWidth' cellspacing='0'>
                                    <tr class='break'>
                                        <td colspan=2>
                                            <h3><?php echo __('Basic Information') ?></h3>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style='width: 275px'>
                                            <b><?php echo __('Budget Cycle') ?></b><br/>
                                        </td>
                                        <td class="right">
                                            <?php
                                            $yearName = '';

                                            $dataYear = array('gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID);
                                            $sqlYear = 'SELECT * FROM gibbonFinanceBudgetCycle WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID';
                                            $resultYear = $connection2->prepare($sqlYear);
                                            $resultYear->execute($dataYear);
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
                                            <b><?php echo __('Budget') ?></b><br/>
                                        </td>
                                        <td class="right">
                                            <input readonly name="name" id="name" maxlength=20 value="<?php echo $row['budget']; ?>" type="text" class="standardWidth">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <b><?php echo __('Title') ?></b><br/>
                                        </td>
                                        <td class="right">
                                            <input readonly name="name" id="name" maxlength=60 value="<?php echo $row['title']; ?>" type="text" class="standardWidth">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <b><?php echo __('Status') ?></b><br/>
                                        </td>
                                        <td class="right">
                                            <input readonly name="name" id="name" maxlength=60 value="<?php echo __($row['status']); ?>" type="text" class="standardWidth">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan=2>
                                            <b><?php echo __('Description') ?></b>
                                            <?php
                                                echo '<p>';
                                                echo $row['body'];
                                                echo '</p>'
                                            ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <b><?php echo __('Total Cost') ?></b><br/>
                                            <span style="font-size: 90%">
                                                <i>
                                                <?php
                                                if ($session->get('currency') != '') {
                                                    echo sprintf(__('Numeric value of the fee in %1$s.'), $session->get('currency'));
                                                } else {
                                                    echo __('Numeric value of the fee.');
                                                }
                                                ?>
                                                </i>
                                            </span>
                                        </td>
                                        <td class="right">
                                            <input readonly name="name" id="name" maxlength=60 value="<?php echo $row['cost']; ?>" type="text" class="standardWidth">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <b><?php echo __('Count Against Budget') ?> *</b><br/>
                                        </td>
                                        <td class="right">
                                            <input readonly name="countAgainstBudget" id="countAgainstBudget" maxlength=60 value="<?php echo Format::yesNo($row['countAgainstBudget']); ?>" type="text" class="standardWidth">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <b><?php echo __('Purchase By') ?></b><br/>
                                        </td>
                                        <td class="right">
                                            <input readonly name="purchaseBy" id="purchaseBy" maxlength=60 value="<?php echo __($row['purchaseBy']); ?>" type="text" class="standardWidth">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan=2>
                                            <b><?php echo __('Purchase Details') ?></b>
                                            <?php
                                                echo '<p>';
                                                echo $row['purchaseDetails'];
                                                echo '</p>'
                                            ?>
                                        </td>
                                    </tr>

                                    <tr class='break'>
                                        <td colspan=2>
                                            <h3><?php echo __('Log') ?></h3>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan=2>
                            <?php
                            $gateway = $container->get(ExpenseGateway::class);
                            $criteria = $gateway->newQueryCriteria()
                                ->sortBy('timestamp')
                                ->fromPOST();
                            $expenses = $gateway->queryExpenseLogByID($criteria, $gibbonFinanceExpenseID);

                            $table = DataTable::create('expenseLog');
                            $table->addColumn('name', __('Person'))
                                ->format(Format::using('name', ['title', 'preferredName', 'surname', 'Staff', false, true]));
                            $table->addColumn('date', __('Date'))
                                ->format(Format::using('date', 'timestamp'));
                            $table->addColumn('action', __('Event'));

                            echo $table->render($expenses);
                            ?>
                                        </td>
                                    </tr>

                                    <?php
                                    if ($row['status'] == 'Paid') {
                                        ?>
                                        <tr class='break' id="paidTitle">
                                            <td colspan=2>
                                                <h3><?php echo __('Payment Information') ?></h3>
                                            </td>
                                        </tr>
                                        <tr id="paymentDateRow">
                                            <td>
                                                <b><?php echo __('Date Paid') ?></b><br/>
                                                <span class="emphasis small"><?php echo __('Date of payment, not entry to system.') ?></span>
                                            </td>
                                            <td class="right">
                                                <input readonly name="paymentDate" id="paymentDate" maxlength=10 value="<?php echo Format::date($row['paymentDate']) ?>" type="text" class="standardWidth">
                                            </td>
                                        </tr>
                                        <tr id="paymentAmountRow">
                                            <td>
                                                <b><?php echo __('Amount Paid') ?></b><br/>
                                                <span class="emphasis small"><?php echo __('Final amount paid.') ?>
                                                <?php
                                                if ($session->get('currency') != '') {
                                                    echo "<span style='font-style: italic; font-size: 85%'>".$session->get('currency').'</span>';
                                                }
                                                ?>
                                                </span>
                                            </td>
                                            <td class="right">
                                                <input readonly name="paymentAmount" id="paymentAmount" maxlength=10 value="<?php echo number_format($row['paymentAmount'], 2, '.', ',') ?>" type="text" class="standardWidth">
                                            </td>
                                        </tr>
                                        <tr id="payeeRow">
                                            <td>
                                                <b><?php echo __('Payee') ?></b><br/>
                                                <span class="emphasis small"><?php echo __('Staff who made, or arranged, the payment.') ?></span>
                                            </td>
                                            <td class="right">
                                                <?php

                                                    $dataSelect = array('gibbonPersonID' => $row['gibbonPersonIDPayment']);
                                                    $sqlSelect = 'SELECT * FROM gibbonPerson JOIN gibbonStaff ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID ORDER BY surname, preferredName';
                                                    $resultSelect = $connection2->prepare($sqlSelect);
                                                    $resultSelect->execute($dataSelect);
                                                if ($resultSelect->rowCount() == 1) {
                                                    $rowSelect = $resultSelect->fetch();
                                                    ?>
                                                            <input readonly name="payee" id="payee" maxlength=10 value="<?php echo Format::name(htmlPrep($rowSelect['title']), ($rowSelect['preferredName']), htmlPrep($rowSelect['surname']), 'Staff', true, true) ?>" type="text" class="standardWidth">
                                                            <?php
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                        <tr id="paymentMethodRow">
                                            <td>
                                                <b><?php echo __('Payment Method') ?></b><br/>
                                            </td>
                                            <td class="right">
                                                <input readonly name="paymentMethod" id="paymentMethod" maxlength=10 value="<?php echo $row['paymentMethod'] ?>" type="text" class="standardWidth">
                                            </td>
                                        </tr>
                                        <tr id="paymentIDRow">
                                            <td>
                                                <b><?php echo __('Payment ID') ?></b><br/>
                                                <span class="emphasis small"><?php echo __('Transaction ID to identify this payment.') ?></span>
                                            </td>
                                            <td class="right">
                                                <input readonly name="paymentID" id="paymentID" maxlength=100 value="<?php echo $row['paymentID'] ?>" type="text" class="standardWidth">
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                    ?>
                                </table>
                            <?php
                        }
                    }
                }
            }
        }
    }
}
?>
