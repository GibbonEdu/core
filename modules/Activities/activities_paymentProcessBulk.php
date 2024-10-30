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

use Gibbon\Data\PasswordPolicy;

include '../../gibbon.php';

$action = $_POST['action'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address']).'/activities_payment.php';

if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_payment.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $gibbonActivityStudentIDList = $_POST['gibbonActivityStudentID'] ?? array();
    $payment = $_POST['payment'] ?? array();

    $students = array();
    foreach ($gibbonActivityStudentIDList as $id => $gibbonActivityStudentID) {
        $students[$id][0] = $gibbonActivityStudentID;
        $students[$id][1] = isset($payment[$id])? $payment[$id] : 0.00;
    }

    //Proceed!
    //Check if person specified
    if (empty($action) || count($students) <= 0) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    } else {


        $partialFail = false;
        if ($action == 'Generate Invoice - Simulate') {
            foreach ($students as $student) {
                $gibbonActivityStudentID = $student[0];

                //Write generation back to gibbonActivityStudent
                try {
                    $data = array('gibbonActivityStudentID' => $gibbonActivityStudentID);
                    $sql = "UPDATE gibbonActivityStudent SET invoiceGenerated='Y' WHERE gibbonActivityStudentID=:gibbonActivityStudentID";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $partialFail = true;
                }
            }
        } else {
            // Check billing schedule specified exists in the current year
            $checkFail = false;
            try {
                $dataCheck = array('gibbonFinanceBillingScheduleID' => $action);
                $sqlCheck = 'SELECT gibbonFinanceBillingScheduleID FROM gibbonFinanceBillingSchedule WHERE gibbonFinanceBillingScheduleID=:gibbonFinanceBillingScheduleID';
                $resultCheck = $connection2->prepare($sqlCheck);
                $resultCheck->execute($dataCheck);
            } catch (PDOException $e) {
                $checkFail = true;
                $partialFail = true;
            }

            if ($checkFail == false) {
                foreach ($students as $student) {
                    $gibbonActivityStudentID = $student[0];
                    $payment = $student[1];

                    //Check student is invoicee
                    $checkFail2 = false;
                    try {
                        $dataCheck2 = array('gibbonActivityStudentID' => $gibbonActivityStudentID);
                        $sqlCheck2 = 'SELECT * FROM gibbonFinanceInvoicee WHERE gibbonPersonID=(SELECT gibbonPersonID FROM gibbonActivityStudent WHERE gibbonActivityStudentID=:gibbonActivityStudentID)';
                        $resultCheck2 = $connection2->prepare($sqlCheck2);
                        $resultCheck2->execute($dataCheck2);
                    } catch (PDOException $e) {
                        $checkFail2 = true;
                        $partialFail = true;
                    }

                    if ($checkFail2 == false) {
                        if ($resultCheck2->rowCount() != 1) {
                            $partialFail = true;
                        } else {
                            $rowCheck2 = $resultCheck2->fetch();

                            //Check for existing pending invoice for this student in this billing schedule
                            $checkFail3 = false;
                            try {
                                $dataCheck3 = array('gibbonFinanceBillingScheduleID' => $action, 'gibbonFinanceInvoiceeID' => $rowCheck2['gibbonFinanceInvoiceeID']);
                                $sqlCheck3 = "SELECT * FROM gibbonFinanceInvoice WHERE gibbonFinanceBillingScheduleID=:gibbonFinanceBillingScheduleID AND gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID AND status='Pending'";
                                $resultCheck3 = $connection2->prepare($sqlCheck3);
                                $resultCheck3->execute($dataCheck3);
                            } catch (PDOException $e) {
                                $checkFail3 = true;
                                $partialFail = true;
                            }

                            if ($checkFail3 == false) {
                                if ($resultCheck3->rowCount() == 0) { //No invoice, so create it
                                    //CREATE NEW INVOICE
                                    //Make and store unique code for confirmation
                                    $key = '';
                                    $continue = false;
                                    $count = 0;

                                    // Use password policy to generate random string
                                    $randStrGenerator = new PasswordPolicy(true, true, false, 40);

                                    while ($continue == false and $count < 100) {
                                        $key = $randStrGenerator->generate();

                                            $dataUnique = array('key' => $key);
                                            $sqlUnique = 'SELECT * FROM gibbonFinanceInvoice WHERE gibbonFinanceInvoice.`key`=:key';
                                            $resultUnique = $connection2->prepare($sqlUnique);
                                            $resultUnique->execute($dataUnique);

                                        if ($resultUnique->rowCount() == 0) {
                                            $continue = true;
                                        }
                                        ++$count;
                                    }

                                    if ($continue == false) {
                                        $partialFail = true;
                                    } else {
                                        $invoiceFail = false;
                                        try {
                                            $dataInvoice = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonFinanceInvoiceeID' => $rowCheck2['gibbonFinanceInvoiceeID'], 'gibbonFinanceBillingScheduleID' => $action, 'notes' => '', 'key' => $key, 'gibbonPersonIDCreator' => $session->get('gibbonPersonID'));
                                            $sqlInvoice = "INSERT INTO gibbonFinanceInvoice SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID, invoiceTo='Family', billingScheduleType='Scheduled', gibbonFinanceBillingScheduleID=:gibbonFinanceBillingScheduleID, notes=:notes, `key`=:key, status='Pending', separated='N', gibbonPersonIDCreator=:gibbonPersonIDCreator, timeStampCreator='".date('Y-m-d H:i:s')."'";
                                            $resultInvoice = $connection2->prepare($sqlInvoice);
                                            $resultInvoice->execute($dataInvoice);
                                        } catch (PDOException $e) {
                                            $invoiceFail = true;
                                            $partialFail = true;
                                        }

                                        if ($invoiceFail == false) {
                                            //Get invoice ID
                                            $gibbonFinanceInvoiceID = str_pad($connection2->lastInsertID(), 14, '0', STR_PAD_LEFT);

                                            //Add fees to invoice
                                            $invoiceFail2 = false;
                                            try {
                                                $dataInvoiceFee = array('gibbonFinanceInvoiceID' => $gibbonFinanceInvoiceID, 'feeType' => 'Ad Hoc', 'name' => 'Activity Fee', 'gibbonActivityStudentID' => $gibbonActivityStudentID, 'gibbonFinanceFeeCategoryID' => 1, 'fee' => $payment);
                                                $sqlInvoiceFee = 'INSERT INTO gibbonFinanceInvoiceFee
                                                    SET gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID,
                                                        feeType=:feeType,
                                                        name=:name,
                                                        description=(SELECT gibbonActivity.name FROM gibbonActivity JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonActivityStudentID=:gibbonActivityStudentID),
                                                        gibbonFinanceFeeCategoryID=:gibbonFinanceFeeCategoryID,
                                                        fee=:fee,
                                                        sequenceNumber=0';
                                                $resultInvoiceFee = $connection2->prepare($sqlInvoiceFee);
                                                $resultInvoiceFee->execute($dataInvoiceFee);
                                            } catch (PDOException $e) {
                                                $invoiceFai2 = true;
                                                $partialFail = true;
                                            }

                                            if ($invoiceFail2 == false) {
                                                //Write invoice and generation back to gibbonActivityStudent
                                                try {
                                                    $data = array('gibbonFinanceInvoiceID' => $gibbonFinanceInvoiceID, 'gibbonActivityStudentID' => $gibbonActivityStudentID);
                                                    $sql = "UPDATE gibbonActivityStudent SET invoiceGenerated='Y', gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID WHERE gibbonActivityStudentID=:gibbonActivityStudentID";
                                                    $result = $connection2->prepare($sql);
                                                    $result->execute($data);
                                                } catch (PDOException $e) {
                                                    $partialFail = true;
                                                }
                                            }
                                        }
                                    }
                                } elseif ($resultCheck3->rowCount() == 1) { //Yes invoice, so update it
                                    $rowCheck3 = $resultCheck3->fetch();

                                    //Get invoice ID
                                    $gibbonFinanceInvoiceID = $rowCheck3['gibbonFinanceInvoiceID'];

                                    //Add fees to invoice
                                    $invoiceFail2 = false;
                                    try {
                                        $dataInvoiceFee = array('gibbonFinanceInvoiceID' => $gibbonFinanceInvoiceID, 'feeType' => 'Ad Hoc', 'name' => 'Activity Fee', 'gibbonActivityStudentID' => $gibbonActivityStudentID, 'gibbonFinanceFeeCategoryID' => 1, 'gibbonActivityStudentID2' => $gibbonActivityStudentID);
                                        $sqlInvoiceFee = 'INSERT INTO gibbonFinanceInvoiceFee SET gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID, feeType=:feeType, name=:name, description=(SELECT gibbonActivity3.name FROM gibbonActivity AS gibbonActivity3 JOIN gibbonActivityStudent AS gibbonActivityStudent3 ON (gibbonActivityStudent3.gibbonActivityID=gibbonActivity3.gibbonActivityID) WHERE gibbonActivityStudentID=:gibbonActivityStudentID), gibbonFinanceFeeCategoryID=:gibbonFinanceFeeCategoryID, fee=(SELECT gibbonActivity.payment FROM gibbonActivity JOIN gibbonActivityStudent ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID) WHERE gibbonActivityStudentID=:gibbonActivityStudentID2), sequenceNumber=0';
                                        $resultInvoiceFee = $connection2->prepare($sqlInvoiceFee);
                                        $resultInvoiceFee->execute($dataInvoiceFee);
                                    } catch (PDOException $e) {
                                        $invoiceFai2 = true;
                                        $partialFail = true;
                                    }

                                    if ($invoiceFail2 == false) {
                                        //Write invoice and generation back to gibbonActivityStudent
                                        try {
                                            $data = array('gibbonFinanceInvoiceID' => $gibbonFinanceInvoiceID, 'gibbonActivityStudentID' => $gibbonActivityStudentID);
                                            $sql = "UPDATE gibbonActivityStudent SET invoiceGenerated='Y', gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID WHERE gibbonActivityStudentID=:gibbonActivityStudentID";
                                            $result = $connection2->prepare($sql);
                                            $result->execute($data);
                                        } catch (PDOException $e) {
                                            $partialFail = true;
                                        }
                                    }
                                } else { //Return error
                                    $partialFail = true;
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($partialFail == true) {
            $URL .= '&return=warning1';
            header("Location: {$URL}");
        } else {
            $URL .= '&return=success0';
            header("Location: {$URL}");
        }
    }
}
