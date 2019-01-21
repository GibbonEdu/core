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

if (isActionAccessible($guid, $connection2, '/modules/Finance/invoices_view.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __('The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        $entryCount = 0;

        $page->breadcrumbs->add(__('View Invoices'));

        if ($highestAction=="View Invoices_myChildren") {
            //Test data access field for permission
            try {
                $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                $sql = "SELECT * FROM gibbonFamilyAdult WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y'";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }

            if ($result->rowCount() < 1) {
                echo "<div class='error'>";
                echo __('Access denied.');
                echo '</div>';
            } else {
                //Get child list
                $count = 0;
                $options = array();
                while ($row = $result->fetch()) {
                    try {
                        $dataChild = array('gibbonFamilyID' => $row['gibbonFamilyID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                        $sqlChild = "SELECT * FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonFamilyID=:gibbonFamilyID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName ";
                        $resultChild = $connection2->prepare($sqlChild);
                        $resultChild->execute($dataChild);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }
                    while ($rowChild = $resultChild->fetch()) {
                        $options[$rowChild['gibbonPersonID']]=formatName('', $rowChild['preferredName'], $rowChild['surname'], 'Student', true);
                    }
                }

                if (count($options) == 0) {
                    echo "<div class='error'>";
                    echo __('Access denied.');
                    echo '</div>';
                } elseif (count($options) == 1) {
                    $_GET['search'] = key($options);
                } else {
                    echo '<h2>';
                    echo 'Choose Student';
                    echo '</h2>';

                    $gibbonPersonID = (isset($_GET['search']))? $_GET['search'] : null;

                    $form = Form::create('filter', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
                    $form->setClass('noIntBorder fullWidth standardForm');

                    $form->addHiddenValue('q', '/modules/Finance/invoices_view.php');
                    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

                    $row = $form->addRow();
                        $row->addLabel('search', __('Student'));
                        $row->addSelect('search')->fromArray($options)->selected($gibbonPersonID)->placeholder();

                    $row = $form->addRow();
                        $row->addSearchSubmit($gibbon->session);

                    echo $form->getOutput();
                }

                $gibbonPersonID = null;
                if (isset($_GET['search'])) {
                    $gibbonPersonID = $_GET['search'];
                }
            }
        } else if ($highestAction=="View Invoices_mine") {
            $count = 1;
            $gibbonPersonID = $_SESSION[$guid]["gibbonPersonID"];
        }

        if (!empty($gibbonPersonID) and count($options) > 0) {
            //Confirm access to this student
            try {
                if ($highestAction=="View Invoices_myChildren") {
                    $dataChild = array('gibbonPersonID' => $gibbonPersonID, 'gibbonPersonID2' => $_SESSION[$guid]['gibbonPersonID']);
                    $sqlChild = "SELECT gibbonPerson.gibbonPersonID FROM gibbonFamilyChild JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonFamilyChild.gibbonPersonID=:gibbonPersonID AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID2 AND childDataAccess='Y'";
                } else if ($highestAction=="View Invoices_mine") {
                    $dataChild = array('gibbonPersonID' => $gibbonPersonID);
                    $sqlChild = "SELECT gibbonPerson.gibbonPersonID FROM gibbonPerson WHERE status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonPersonID=:gibbonPersonID" ;
                }
                $resultChild = $connection2->prepare($sqlChild);
                $resultChild->execute($dataChild);
            } catch (PDOException $e) {
                echo "<div class='error'>".$e->getMessage().'</div>';
            }
            if ($resultChild->rowCount() < 1) {
                echo "<div class='error'>";
                echo __('The selected record does not exist, or you do not have access to it.');
                echo '</div>';
            } else {
                $rowChild = $resultChild->fetch();

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
                        echo __('The specified record does not exist.');
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
                            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/invoices_view.php&search=$gibbonPersonID&gibbonSchoolYearID=".getPreviousSchoolYearID($gibbonSchoolYearID, $connection2)."'>".__('Previous Year').'</a> ';
                        } else {
                            echo __('Previous Year').' ';
                        }
                    echo ' | ';
                    if (getNextSchoolYearID($gibbonSchoolYearID, $connection2) != false) {
                        echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/invoices_view.php&search=$gibbonPersonID&gibbonSchoolYearID=".getNextSchoolYearID($gibbonSchoolYearID, $connection2)."'>".__('Next Year').'</a> ';
                    } else {
                        echo __('Next Year').' ';
                    }
                    echo '</div>';

                    try {
                        //Add in filter wheres
                        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonSchoolYearID2' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID);
                        //SQL for NOT Pending
                        $sql = "SELECT gibbonFinanceInvoice.gibbonFinanceInvoiceID, surname, preferredName, gibbonFinanceInvoice.invoiceTo, gibbonFinanceInvoice.status, gibbonFinanceInvoice.invoiceIssueDate, gibbonFinanceInvoice.invoiceDueDate, paidDate, paidAmount, billingScheduleType AS billingSchedule, gibbonFinanceBillingSchedule.name AS billingScheduleExtra, notes, gibbonRollGroup.name AS rollGroup FROM gibbonFinanceInvoice LEFT JOIN gibbonFinanceBillingSchedule ON (gibbonFinanceInvoice.gibbonFinanceBillingScheduleID=gibbonFinanceBillingSchedule.gibbonFinanceBillingScheduleID) JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoice.gibbonFinanceInvoiceeID=gibbonFinanceInvoicee.gibbonFinanceInvoiceeID) JOIN gibbonPerson ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) LEFT JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonFinanceInvoice.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND NOT gibbonFinanceInvoice.status='Pending' AND gibbonFinanceInvoicee.gibbonPersonID=:gibbonPersonID ORDER BY invoiceIssueDate, surname, preferredName";
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }

                    if ($result->rowCount() < 1) {
                        echo '<h3>';
                        echo __('View');
                        echo '</h3>';

                        echo "<div class='error'>";
                        echo __('There are no records to display.');
                        echo '</div>';
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
                        echo __('Roll Group');
                        echo '</th>';
                        echo "<th style='width: 100px'>";
                        echo __('Status');
                        echo '</th>';
                        echo "<th style='width: 90px'>";
                        echo __('Schedule');
                        echo '</th>';
                        echo "<th style='width: 120px'>";
                        echo __('Total')." <span style='font-style: italic; font-size: 75%'>(".$_SESSION[$guid]['currency'].')</span><br/>';
                        echo "<span style='font-style: italic; font-size: 75%'>".__('Paid').' ('.$_SESSION[$guid]['currency'].')</span>';
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
                            echo '<b>'.formatName('', htmlPrep($row['preferredName']), htmlPrep($row['surname']), 'Student', true).'</b><br/>';
                            echo "<span style='font-style: italic; font-size: 85%'>".$row['invoiceTo'].'</span>';
                            echo '</td>';
                            echo '<td>';
                            echo $row['rollGroup'];
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
                                echo $e->getMessage();
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
                                if (substr($_SESSION[$guid]['currency'], 4) != '') {
                                    echo substr($_SESSION[$guid]['currency'], 4).' ';
                                }
                                echo number_format($totalFee, 2, '.', ',').'<br/>';
                                if ($row['paidAmount'] != '') {
                                    $styleExtra = '';
                                    if ($row['paidAmount'] != $totalFee) {
                                        $styleExtra = 'color: #c00;';
                                    }
                                    echo "<span style='$styleExtra font-style: italic; font-size: 85%'>";
                                    if (substr($_SESSION[$guid]['currency'], 4) != '') {
                                        echo substr($_SESSION[$guid]['currency'], 4).' ';
                                    }
                                    echo number_format($row['paidAmount'], 2, '.', ',').'</span>';
                                }
                            }
                            echo '</td>';
                            echo '<td>';
                            if (is_null($row['invoiceIssueDate'])) {
                                echo 'NA<br/>';
                            } else {
                                echo dateConvertBack($guid, $row['invoiceIssueDate']).'<br/>';
                            }
                            echo "<span style='font-style: italic; font-size: 75%'>".dateConvertBack($guid, $row['invoiceDueDate']).'</span>';
                            echo '</td>';
                            echo '<td>';
                            if ($row['status'] == 'Issued') {
                                echo "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/report.php?q=/modules/'.$_SESSION[$guid]['module'].'/invoices_view_print.php&type=invoice&gibbonFinanceInvoiceID='.$row['gibbonFinanceInvoiceID']."&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonPersonID=$gibbonPersonID'><img title='".__('Print')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/print.png'/></a>";
                            } elseif ($row['status'] == 'Paid' or $row['status'] == 'Paid - Partial') {
                                echo "<a target='_blank' href='".$_SESSION[$guid]['absoluteURL'].'/report.php?q=/modules/'.$_SESSION[$guid]['module'].'/invoices_view_print.php&type=receipt&gibbonFinanceInvoiceID='.$row['gibbonFinanceInvoiceID']."&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonPersonID=$gibbonPersonID'><img title='".__('Print')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/print.png'/></a>";
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
                            if ($row['notes'] != '') {
                                echo "<a title='View Notes' class='show_hide-$count' onclick='false' href='#'><img style='margin-left: 5px' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/page_down.png' alt='".__('Show Comment')."' onclick='return false;' /></a>";
                            }
                            echo '</td>';
                            echo '</tr>';
                            if ($row['notes'] != '') {
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
