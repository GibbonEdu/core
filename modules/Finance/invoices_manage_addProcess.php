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

include '../../functions.php';
include '../../config.php';

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start();

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]['timezone']);

$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
$status = $_GET['status'];
$gibbonFinanceInvoiceeID = $_GET['gibbonFinanceInvoiceeID'];
$monthOfIssue = $_GET['monthOfIssue'];
$gibbonFinanceBillingScheduleID = $_GET['gibbonFinanceBillingScheduleID'];

if ($gibbonSchoolYearID == '') { echo 'Fatal error loading this page!';
} else {
    $URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/invoices_manage_add.php&gibbonSchoolYearID=$gibbonSchoolYearID&status=$status&gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID&monthOfIssue=$monthOfIssue&gibbonFinanceBillingScheduleID=$gibbonFinanceBillingScheduleID";

    if (isActionAccessible($guid, $connection2, '/modules/Finance/invoices_manage_add.php') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        $gibbonFinanceInvoiceeIDs = $_POST['gibbonFinanceInvoiceeIDs'];
        $scheduling = $_POST['scheduling'];
        if ($scheduling == 'Scheduled') {
            $gibbonFinanceBillingScheduleID = $_POST['gibbonFinanceBillingScheduleID'];
            $invoiceDueDate = null;
        } elseif ($scheduling == 'Ad Hoc') {
            $gibbonFinanceBillingScheduleID = null;
            $invoiceDueDate = $_POST['invoiceDueDate'];
        }
        $notes = $_POST['notes'];
        $order = $_POST['order'];

        if (count($gibbonFinanceInvoiceeIDs) == 0 or $scheduling == '' or ($scheduling == 'Scheduled' and $gibbonFinanceBillingScheduleID == '') or ($scheduling == 'Ad Hoc' and $invoiceDueDate == '') or count($order) == 0) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            $studentFailCount = 0;
            $invoiceFailCount = 0;
            $invoiceFeeFailCount = 0;
            $feeFail = false;

            //PROCESS FEES
            $fess = array();
            foreach ($order as $fee) {
                $fees[$fee]['name'] = $_POST['name'.$fee];
                $fees[$fee]['gibbonFinanceFeeCategoryID'] = $_POST['gibbonFinanceFeeCategoryID'.$fee];
                $fees[$fee]['fee'] = $_POST['fee'.$fee];
                $fees[$fee]['feeType'] = $_POST['feeType'.$fee];
                $fees[$fee]['gibbonFinanceFeeID'] = $_POST['gibbonFinanceFeeID'.$fee];
                $fees[$fee]['description'] = $_POST['description'.$fee];

                if ($fees[$fee]['name'] == '' or $fees[$fee]['gibbonFinanceFeeCategoryID'] == '' or $fees[$fee]['fee'] == '' or is_numeric($fees[$fee]['fee']) == false or $fees[$fee]['feeType'] == '' or ($fees[$fee]['feeType'] == 'Standard' and $fees[$fee]['gibbonFinanceFeeID'] == '')) {
                    $feeFail = true;
                }
            }

            if ($feeFail == true) {
                $URL .= '&return=error1';
                header("Location: {$URL}");
                exit();
            } else {
                //LOCK INVOICE TABLES
                try {
                    $data = array();
                    $sql = 'LOCK TABLES gibbonFinanceInvoice WRITE, gibbonFinanceInvoiceFee WRITE, gibbonFinanceInvoicee WRITE';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                //CYCLE THROUGH STUDENTS
                foreach ($gibbonFinanceInvoiceeIDs as $gibbonFinanceInvoiceeID) {
                    $thisStudentFailed = false;
                    $invoiceTo = '';
                    $companyAll = '';
                    $gibbonFinanceFeeCategoryIDList = '';

                    //GET INVOICE RECORD, set $invoiceTo and $companyCategories if required
                    try {
                        $data = array('gibbonFinanceInvoiceeID' => $gibbonFinanceInvoiceeID);
                        $sql = 'SELECT * FROM gibbonFinanceInvoicee WHERE gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        ++$studentFailCount;
                        $thisStudentFailed = true;
                    }
                    if ($result->rowCount() != 1) {
                        if ($thisStudentFailed != true) {
                            ++$studentFailCount;
                            $thisStudentFailed = true;
                        }
                    } else {
                        $row = $result->fetch();
                        $invoiceTo = $row['invoiceTo'];
                        if ($invoiceTo != 'Family' and $invoiceTo != 'Company') {
                            ++$studentFailCount;
                            $thisStudentFailed = true;
                        } else {
                            if ($invoiceTo == 'Company') {
                                $companyAll = $row['companyAll'];
                                if ($companyAll == 'N') {
                                    $gibbonFinanceFeeCategoryIDList = $row['gibbonFinanceFeeCategoryIDList'];
                                    if ($gibbonFinanceFeeCategoryIDList != '') {
                                        $gibbonFinanceFeeCategoryIDs = explode(',', $gibbonFinanceFeeCategoryIDList);
                                    } else {
                                        $gibbonFinanceFeeCategoryIDs = null;
                                    }
                                }

                                $companyFamily = false; //This holds true when company is set, companyAll=N and there are some fees for the family to pay...
                                foreach ($fees as $fee) {
                                    if ($invoiceTo == 'Company' and $companyAll == 'N' and strpos($gibbonFinanceFeeCategoryIDList, $fee['gibbonFinanceFeeCategoryID']) === false) {
                                        $companyFamily = true;
                                    }
                                }
                                $companyFamilyCompanyHasCharges = false; //This holds true when company is set, companyAll=N and there are some fees for the company to pay...e.g.  they are not all held by the family
                                if ($invoiceTo == 'Company' and $companyAll == 'N') {
                                    foreach ($fees as $fee) {
                                        if ($invoiceTo == 'Company' and $companyAll == 'N' and is_numeric(strpos($gibbonFinanceFeeCategoryIDList, $fee['gibbonFinanceFeeCategoryID']))) {
                                            $companyFamilyCompanyHasCharges = true;
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if ($thisStudentFailed == false) {
                        //CHECK FOR INVOICE AND UPDATE/ADD FOR FAMILY (INC WHEN COMPANY IS PAYING ONLY SOME FEES)
                        if ($invoiceTo == 'Family' or $companyFamily == true) {
                            $thisInvoiceFailed = false;
                            try {
                                if ($scheduling == 'Scheduled') {
                                    $dataInvoice = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonFinanceInvoiceeID' => $gibbonFinanceInvoiceeID, 'gibbonFinanceBillingScheduleID' => $gibbonFinanceBillingScheduleID);
                                    $sqlInvoice = "SELECT * FROM gibbonFinanceInvoice WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID AND invoiceTo='Family' AND billingScheduleType='Scheduled' AND gibbonFinanceBillingScheduleID=:gibbonFinanceBillingScheduleID AND status='Pending'";
                                } else {
                                    $dataInvoice = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonFinanceInvoiceeID' => $gibbonFinanceInvoiceeID);
                                    $sqlInvoice = "SELECT * FROM gibbonFinanceInvoice WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID AND invoiceTo='Family' AND billingScheduleType='Ad Hoc' AND status='Pending'";
                                }
                                $resultInvoice = $connection2->prepare($sqlInvoice);
                                $resultInvoice->execute($dataInvoice);
                            } catch (PDOException $e) {
                                ++$invoiceFailCount;
                                $thisInvoiceFailed = true;
                            }
                            if ($resultInvoice->rowCount() == 0 and $thisInvoiceFailed == false) {
                                //ADD INVOICE
                                //Get next autoincrement
                                try {
                                    $dataAI = array();
                                    $sqlAI = "SHOW TABLE STATUS LIKE 'gibbonFinanceInvoice'";
                                    $resultAI = $connection2->prepare($sqlAI);
                                    $resultAI->execute($dataAI);
                                } catch (PDOException $e) {
                                    ++$invoiceFailCount;
                                    $thisInvoiceFailed = true;
                                }
                                if ($resultAI->rowCount() == 1) {
                                    $rowAI = $resultAI->fetch();
                                    $AI = str_pad($rowAI['Auto_increment'], 14, '0', STR_PAD_LEFT);
                                }

                                if ($AI == '') {
                                    if ($thisInvoiceFailed == false) {
                                        ++$invoiceFailCount;
                                        $thisInvoiceFailed = true;
                                    }
                                } else {
                                    //Add invoice
                                    //Make and store unique code for confirmation. add it to email text.
                                    $key = '';

                                    //Let's go! Create key, send the invite							
                                    $continue = false;
                                    $count = 0;
                                    while ($continue == false and $count < 100) {
                                        $key = randomPassword(40);
                                        try {
                                            $dataUnique = array('key' => $key);
                                            $sqlUnique = 'SELECT * FROM gibbonFinanceInvoice WHERE gibbonFinanceInvoice.`key`=:key';
                                            $resultUnique = $connection2->prepare($sqlUnique);
                                            $resultUnique->execute($dataUnique);
                                        } catch (PDOException $e) {
                                        }

                                        if ($resultUnique->rowCount() == 0) {
                                            $continue = true;
                                        }
                                        ++$count;
                                    }

                                    if ($continue == false) {
                                        $URL .= '&return=error2';
                                        header("Location: {$URL}");
                                        exit();
                                    } else {
                                        try {
                                            if ($scheduling == 'Scheduled') {
                                                $dataInvoiceAdd = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonFinanceInvoiceeID' => $gibbonFinanceInvoiceeID, 'gibbonFinanceBillingScheduleID' => $gibbonFinanceBillingScheduleID, 'notes' => $notes, 'key' => $key, 'gibbonPersonIDCreator' => $_SESSION[$guid]['gibbonPersonID']);
                                                $sqlInvoiceAdd = "INSERT INTO gibbonFinanceInvoice SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID, invoiceTo='Family', billingScheduleType='Scheduled', gibbonFinanceBillingScheduleID=:gibbonFinanceBillingScheduleID, notes=:notes, `key`=:key, status='Pending', separated='N', gibbonPersonIDCreator=:gibbonPersonIDCreator, timeStampCreator='".date('Y-m-d H:i:s')."'";
                                            } else {
                                                $dataInvoiceAdd = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonFinanceInvoiceeID' => $gibbonFinanceInvoiceeID, 'invoiceDueDate' => dateConvert($guid, $invoiceDueDate), 'notes' => $notes, 'key' => $key, 'gibbonPersonIDCreator' => $_SESSION[$guid]['gibbonPersonID']);
                                                $sqlInvoiceAdd = "INSERT INTO gibbonFinanceInvoice SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID, invoiceTo='Family', billingScheduleType='Ad Hoc', status='Pending', invoiceDueDate=:invoiceDueDate, notes=:notes, `key`=:key, gibbonPersonIDCreator=:gibbonPersonIDCreator, timeStampCreator='".date('Y-m-d H:i:s')."'";
                                            }
                                            $resultInvoiceAdd = $connection2->prepare($sqlInvoiceAdd);
                                            $resultInvoiceAdd->execute($dataInvoiceAdd);
                                        } catch (PDOException $e) {
                                            echo $e->getMessage();
                                            ++$invoiceFailCount;
                                            $thisInvoiceFailed = true;
                                        }
                                        if ($thisInvoiceFailed == false) {
                                            //Add fees to invoice
                                            $count = 0;
                                            foreach ($fees as $fee) {
                                                ++$count;
                                                if ($invoiceTo == 'Family' or ($invoiceTo == 'Company' and $companyAll == 'N' and strpos($gibbonFinanceFeeCategoryIDList, $fee['gibbonFinanceFeeCategoryID']) === false)) {
                                                    try {
                                                        if ($fee['feeType'] == 'Standard') {
                                                            $dataInvoiceFee = array('gibbonFinanceInvoiceID' => $AI, 'feeType' => $fee['feeType'], 'gibbonFinanceFeeID' => $fee['gibbonFinanceFeeID']);
                                                            $sqlInvoiceFee = "INSERT INTO gibbonFinanceInvoiceFee SET gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID, feeType=:feeType, gibbonFinanceFeeID=:gibbonFinanceFeeID, separated='N', sequenceNumber=$count";
                                                        } else {
                                                            $dataInvoiceFee = array('gibbonFinanceInvoiceID' => $AI, 'feeType' => $fee['feeType'], 'name' => $fee['name'], 'description' => $fee['description'], 'gibbonFinanceFeeCategoryID' => $fee['gibbonFinanceFeeCategoryID'], 'fee' => $fee['fee']);
                                                            $sqlInvoiceFee = "INSERT INTO gibbonFinanceInvoiceFee SET gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID, feeType=:feeType, name=:name, description=:description, gibbonFinanceFeeCategoryID=:gibbonFinanceFeeCategoryID, fee=:fee, sequenceNumber=$count";
                                                        }
                                                        $resultInvoiceFee = $connection2->prepare($sqlInvoiceFee);
                                                        $resultInvoiceFee->execute($dataInvoiceFee);
                                                    } catch (PDOException $e) {
                                                        ++$invoiceFeeFailCount;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            } elseif ($resultInvoice->rowCount() == 1 and $thisInvoiceFailed == false) {
                                $rowInvoice = $resultInvoice->fetch();

                                //Add fees to invoice
                                $count = 0;
                                foreach ($fees as $fee) {
                                    ++$count;
                                    if ($invoiceTo == 'Family' or ($invoiceTo == 'Company' and $companyAll == 'N' and strpos($gibbonFinanceFeeCategoryIDList, $fee['gibbonFinanceFeeCategoryID']) === false)) {
                                        try {
                                            if ($fee['feeType'] == 'Standard') {
                                                $dataInvoiceFee = array('gibbonFinanceInvoiceID' => $rowInvoice['gibbonFinanceInvoiceID'], 'feeType' => $fee['feeType'], 'gibbonFinanceFeeID' => $fee['gibbonFinanceFeeID']);
                                                $sqlInvoiceFee = "INSERT INTO gibbonFinanceInvoiceFee SET gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID, feeType=:feeType, gibbonFinanceFeeID=:gibbonFinanceFeeID, separated='N', sequenceNumber=$count";
                                            } else {
                                                $dataInvoiceFee = array('gibbonFinanceInvoiceID' => $rowInvoice['gibbonFinanceInvoiceID'], 'feeType' => $fee['feeType'], 'name' => $fee['name'], 'description' => $fee['description'], 'gibbonFinanceFeeCategoryID' => $fee['gibbonFinanceFeeCategoryID'], 'fee' => $fee['fee']);
                                                $sqlInvoiceFee = "INSERT INTO gibbonFinanceInvoiceFee SET gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID, feeType=:feeType, name=:name, description=:description, gibbonFinanceFeeCategoryID=:gibbonFinanceFeeCategoryID, fee=:fee, sequenceNumber=$count";
                                            }
                                            $resultInvoiceFee = $connection2->prepare($sqlInvoiceFee);
                                            $resultInvoiceFee->execute($dataInvoiceFee);
                                        } catch (PDOException $e) {
                                            ++$invoiceFeeFailCount;
                                        }
                                    }
                                }

                                //Update invoice
                                try {
                                    if ($scheduling == 'Scheduled') {
                                        $dataInvoiceAdd = array('gibbonPersonIDUpdate' => $_SESSION[$guid]['gibbonPersonID'], 'notes' => $rowInvoice['notes'].' '.$notes, 'gibbonFinanceInvoiceID' => $rowInvoice['gibbonFinanceInvoiceID']);
                                        $sqlInvoiceAdd = "UPDATE gibbonFinanceInvoice SET gibbonPersonIDUpdate=:gibbonPersonIDUpdate, notes=:notes, timeStampUpdate='".date('Y-m-d H:i:s')."' WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID";
                                    } else {
                                        $dataInvoiceAdd = array('invoiceDueDate' => dateConvert($guid, $invoiceDueDate), 'gibbonPersonIDUpdate' => $_SESSION[$guid]['gibbonPersonID'], 'notes' => $rowInvoice['notes'].' '.$notes, 'gibbonFinanceInvoiceID' => $rowInvoice['gibbonFinanceInvoiceID']);
                                        $sqlInvoiceAdd = "UPDATE gibbonFinanceInvoice SET invoiceDueDate=:invoiceDueDate, gibbonPersonIDUpdate=:gibbonPersonIDUpdate, notes=:notes, timeStampUpdate='".date('Y-m-d H:i:s')."' WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID";
                                    }
                                    $resultInvoiceAdd = $connection2->prepare($sqlInvoiceAdd);
                                    $resultInvoiceAdd->execute($dataInvoiceAdd);
                                } catch (PDOException $e) {
                                    ++$invoiceFailCount;
                                    $thisInvoiceFailed = true;
                                }
                            } else {
                                if ($thisInvoiceFailed == false) {
                                    ++$invoiceFailCount;
                                    $thisInvoiceFailed = true;
                                }
                            }
                        }

                        //CHECK FOR INVOICE AND UPDATE/ADD FOR COMPANY
                        if (($invoiceTo == 'Company' and $companyAll == 'Y') or ($invoiceTo == 'Company' and $companyAll == 'N' and $companyFamilyCompanyHasCharges == true)) {
                            $thisInvoiceFailed = false;
                            try {
                                if ($scheduling == 'Scheduled') {
                                    $dataInvoice = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonFinanceInvoiceeID' => $gibbonFinanceInvoiceeID, 'gibbonFinanceBillingScheduleID' => $gibbonFinanceBillingScheduleID);
                                    $sqlInvoice = "SELECT * FROM gibbonFinanceInvoice WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID AND invoiceTo='Company' AND billingScheduleType='Scheduled' AND gibbonFinanceBillingScheduleID=:gibbonFinanceBillingScheduleID AND status='Pending'";
                                } else {
                                    $dataInvoice = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonFinanceInvoiceeID' => $gibbonFinanceInvoiceeID);
                                    $sqlInvoice = "SELECT * FROM gibbonFinanceInvoice WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID AND invoiceTo='Company' AND billingScheduleType='Ad Hoc' AND status='Pending'";
                                }
                                $resultInvoice = $connection2->prepare($sqlInvoice);
                                $resultInvoice->execute($dataInvoice);
                            } catch (PDOException $e) {
                                ++$invoiceFailCount;
                                $thisInvoiceFailed = true;
                            }
                            if ($resultInvoice->rowCount() == 0 and $thisInvoiceFailed == false) {
                                //ADD INVOICE
                                //Get next autoincrement
                                try {
                                    $dataAI = array();
                                    $sqlAI = "SHOW TABLE STATUS LIKE 'gibbonFinanceInvoice'";
                                    $resultAI = $connection2->prepare($sqlAI);
                                    $resultAI->execute($dataAI);
                                } catch (PDOException $e) {
                                    ++$invoiceFailCount;
                                    $thisInvoiceFailed = true;
                                }
                                if ($resultAI->rowCount() == 1) {
                                    $rowAI = $resultAI->fetch();
                                    $AI = str_pad($rowAI['Auto_increment'], 14, '0', STR_PAD_LEFT);
                                }

                                if ($AI == '') {
                                    if ($thisInvoiceFailed == false) {
                                        ++$invoiceFailCount;
                                        $thisInvoiceFailed = true;
                                    }
                                } else {
                                    //Add invoice
                                    //Make and store unique code for confirmation. add it to email text.
                                    $key = '';

                                    //Let's go! Create key, send the invite							
                                    $continue = false;
                                    $count = 0;
                                    while ($continue == false and $count < 100) {
                                        $key = randomPassword(40);
                                        try {
                                            $dataUnique = array('key' => $key);
                                            $sqlUnique = 'SELECT * FROM gibbonFinanceInvoice WHERE gibbonFinanceInvoice.`key`=key';
                                            $resultUnique = $connection2->prepare($sqlUnique);
                                            $resultUnique->execute($dataUnique);
                                        } catch (PDOException $e) {
                                        }

                                        if ($resultUnique->rowCount() == 0) {
                                            $continue = true;
                                        }
                                        ++$count;
                                    }

                                    if ($continue == false) {
                                        $URL .= '&return=error2';
                                        header("Location: {$URL}");
                                        exit();
                                    } else {
                                        try {
                                            if ($scheduling == 'Scheduled') {
                                                $dataInvoiceAdd = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonFinanceInvoiceeID' => $gibbonFinanceInvoiceeID, 'gibbonFinanceBillingScheduleID' => $gibbonFinanceBillingScheduleID, 'notes' => $notes, 'key' => $key, 'gibbonPersonIDCreator' => $_SESSION[$guid]['gibbonPersonID']);
                                                $sqlInvoiceAdd = "INSERT INTO gibbonFinanceInvoice SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID, invoiceTo='Company', billingScheduleType='Scheduled', gibbonFinanceBillingScheduleID=:gibbonFinanceBillingScheduleID, notes=:notes, `key`=:key, status='Pending', separated='N', gibbonPersonIDCreator=:gibbonPersonIDCreator, timeStampCreator='".date('Y-m-d H:i:s')."'";
                                            } else {
                                                $dataInvoiceAdd = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonFinanceInvoiceeID' => $gibbonFinanceInvoiceeID, 'invoiceDueDate' => dateConvert($guid, $invoiceDueDate), 'notes' => $notes, 'key' => $key, 'gibbonPersonIDCreator' => $_SESSION[$guid]['gibbonPersonID']);
                                                $sqlInvoiceAdd = "INSERT INTO gibbonFinanceInvoice SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID, invoiceTo='Company', billingScheduleType='Ad Hoc', status='Pending', invoiceDueDate=:invoiceDueDate, notes=:notes, `key`=:key, gibbonPersonIDCreator=:gibbonPersonIDCreator, timeStampCreator='".date('Y-m-d H:i:s')."'";
                                            }
                                            $resultInvoiceAdd = $connection2->prepare($sqlInvoiceAdd);
                                            $resultInvoiceAdd->execute($dataInvoiceAdd);
                                        } catch (PDOException $e) {
                                            echo $e->getMessage();
                                            ++$invoiceFailCount;
                                            $thisInvoiceFailed = true;
                                        }
                                        if ($thisInvoiceFailed == false) {
                                            //Add fees to invoice
                                            $count = 0;
                                            foreach ($fees as $fee) {
                                                ++$count;
                                                if (($invoiceTo == 'Company' and $companyAll == 'Y') or ($invoiceTo == 'Company' and $companyAll == 'N' and is_numeric(strpos($gibbonFinanceFeeCategoryIDList, $fee['gibbonFinanceFeeCategoryID'])))) {
                                                    try {
                                                        if ($fee['feeType'] == 'Standard') {
                                                            $dataInvoiceFee = array('gibbonFinanceInvoiceID' => $AI, 'feeType' => $fee['feeType'], 'gibbonFinanceFeeID' => $fee['gibbonFinanceFeeID']);
                                                            $sqlInvoiceFee = "INSERT INTO gibbonFinanceInvoiceFee SET gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID, feeType=:feeType, gibbonFinanceFeeID=:gibbonFinanceFeeID, separated='N', sequenceNumber=$count";
                                                        } else {
                                                            $dataInvoiceFee = array('gibbonFinanceInvoiceID' => $AI, 'feeType' => $fee['feeType'], 'name' => $fee['name'], 'description' => $fee['description'], 'gibbonFinanceFeeCategoryID' => $fee['gibbonFinanceFeeCategoryID'], 'fee' => $fee['fee']);
                                                            $sqlInvoiceFee = "INSERT INTO gibbonFinanceInvoiceFee SET gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID, feeType=:feeType, name=:name, description=:description, gibbonFinanceFeeCategoryID=:gibbonFinanceFeeCategoryID, fee=:fee, sequenceNumber=$count";
                                                        }
                                                        $resultInvoiceFee = $connection2->prepare($sqlInvoiceFee);
                                                        $resultInvoiceFee->execute($dataInvoiceFee);
                                                    } catch (PDOException $e) {
                                                        ++$invoiceFeeFailCount;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            } elseif ($resultInvoice->rowCount() == 1 and $thisInvoiceFailed == false) {
                                $rowInvoice = $resultInvoice->fetch();

                                //Add fees to invoice
                                $count = 0;
                                foreach ($fees as $fee) {
                                    ++$count;
                                    if (($invoiceTo == 'Company' and $companyAll == 'Y') or ($invoiceTo == 'Company' and $companyAll == 'N' and is_numeric(strpos($gibbonFinanceFeeCategoryIDList, $fee['gibbonFinanceFeeCategoryID'])))) {
                                        try {
                                            if ($fee['feeType'] == 'Standard') {
                                                $dataInvoiceFee = array('gibbonFinanceInvoiceID' => $rowInvoice['gibbonFinanceInvoiceID'], 'feeType' => $fee['feeType'], 'gibbonFinanceFeeID' => $fee['gibbonFinanceFeeID']);
                                                $sqlInvoiceFee = "INSERT INTO gibbonFinanceInvoiceFee SET gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID, feeType=:feeType, gibbonFinanceFeeID=:gibbonFinanceFeeID, separated='N', sequenceNumber=$count";
                                            } else {
                                                $dataInvoiceFee = array('gibbonFinanceInvoiceID' => $rowInvoice['gibbonFinanceInvoiceID'], 'feeType' => $fee['feeType'], 'name' => $fee['name'], 'description' => $fee['description'], 'gibbonFinanceFeeCategoryID' => $fee['gibbonFinanceFeeCategoryID'], 'fee' => $fee['fee']);
                                                $sqlInvoiceFee = "INSERT INTO gibbonFinanceInvoiceFee SET gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID, feeType=:feeType, name=:name, description=:description, gibbonFinanceFeeCategoryID=:gibbonFinanceFeeCategoryID, fee=:fee, sequenceNumber=$count";
                                            }
                                            $resultInvoiceFee = $connection2->prepare($sqlInvoiceFee);
                                            $resultInvoiceFee->execute($dataInvoiceFee);
                                        } catch (PDOException $e) {
                                            ++$invoiceFeeFailCount;
                                        }
                                    }
                                }

                                //Update invoice
                                try {
                                    if ($scheduling == 'Scheduled') {
                                        $dataInvoiceAdd = array('gibbonPersonIDUpdate' => $_SESSION[$guid]['gibbonPersonID'], 'notes' => $rowInvoice['notes'].' '.$notes, 'gibbonFinanceInvoiceID' => $rowInvoice['gibbonFinanceInvoiceID']);
                                        $sqlInvoiceAdd = "UPDATE gibbonFinanceInvoice SET gibbonPersonIDUpdate=:gibbonPersonIDUpdate, notes=:notes, timeStampUpdate='".date('Y-m-d H:i:s')."' WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID";
                                    } else {
                                        $dataInvoiceAdd = array('invoiceDueDate' => dateConvert($guid, $invoiceDueDate), 'gibbonPersonIDUpdate' => $_SESSION[$guid]['gibbonPersonID'], 'notes' => $rowInvoice['notes'].' '.$notes, 'gibbonFinanceInvoiceID' => $rowInvoice['gibbonFinanceInvoiceID']);
                                        $sqlInvoiceAdd = "UPDATE gibbonFinanceInvoice SET invoiceDueDate=:invoiceDueDate, gibbonPersonIDUpdate=:gibbonPersonIDUpdate, notes=:notes, timeStampUpdate='".date('Y-m-d H:i:s')."' WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID";
                                    }
                                    $resultInvoiceAdd = $connection2->prepare($sqlInvoiceAdd);
                                    $resultInvoiceAdd->execute($dataInvoiceAdd);
                                } catch (PDOException $e) {
                                    ++$invoiceFailCount;
                                    $thisInvoiceFailed = true;
                                }
                            } else {
                                if ($thisInvoiceFailed == false) {
                                    ++$invoiceFailCount;
                                    $thisInvoiceFailed = true;
                                }
                            }
                        }
                    }
                }

                //Unlock module table
                try {
                    $sql = 'UNLOCK TABLES';
                    $result = $connection2->query($sql);
                } catch (PDOException $e) {
                }

                //Return results, include three types of fail and counts
                if ($studentFailCount != 0 or $invoiceFailCount != 0 or $invoiceFeeFailCount != 0) {
                    $URL .= "&return=error3&studentFailCount=$studentFailCount&invoiceFailCount=$invoiceFailCount&invoiceFeeFailCount=$invoiceFeeFailCount";
                    header("Location: {$URL}");
                } else {
                    $URL .= '&return=success0';
                    header("Location: {$URL}");
                }
            }
        }
    }
}
