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

//Module includes
include './moduleFunctions.php';

$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
$gibbonFinanceInvoiceID = $_POST['gibbonFinanceInvoiceID'];
$status = $_GET['status'];
$gibbonFinanceInvoiceeID = $_GET['gibbonFinanceInvoiceeID'];
$monthOfIssue = $_GET['monthOfIssue'];
$gibbonFinanceBillingScheduleID = $_GET['gibbonFinanceBillingScheduleID'];
$gibbonFinanceFeeCategoryID = $_GET['gibbonFinanceFeeCategoryID'];

if ($gibbonFinanceInvoiceID == '' or $gibbonSchoolYearID == '') { echo 'Fatal error loading this page!';
} else {
    $URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/invoices_manage_issue.php&gibbonFinanceInvoiceID=$gibbonFinanceInvoiceID&gibbonSchoolYearID=$gibbonSchoolYearID&status=$status&gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID&monthOfIssue=$monthOfIssue&gibbonFinanceBillingScheduleID=$gibbonFinanceBillingScheduleID&gibbonFinanceFeeCategoryID=$gibbonFinanceFeeCategoryID";
    $URLSuccess = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/invoices_manage.php&gibbonFinanceInvoiceID=$gibbonFinanceInvoiceID&gibbonSchoolYearID=$gibbonSchoolYearID&status=$status&gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID&monthOfIssue=$monthOfIssue&gibbonFinanceBillingScheduleID=$gibbonFinanceBillingScheduleID&gibbonFinanceFeeCategoryID=$gibbonFinanceFeeCategoryID";

    if (isActionAccessible($guid, $connection2, '/modules/Finance/invoices_manage_edit.php') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        //Proceed!
        //Check if person specified
        if ($gibbonFinanceInvoiceID == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            //LOCK INVOICE TABLES
            try {
                $data = array();
                $sql = 'LOCK TABLES gibbonFinanceInvoice WRITE, gibbonFinanceInvoiceFee WRITE, gibbonFinanceInvoicee WRITE, gibbonFinanceFee WRITE, gibbonFinanceFeeCategory WRITE';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            try {
                $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonFinanceInvoiceID' => $gibbonFinanceInvoiceID);
                $sql = "SELECT * FROM gibbonFinanceInvoice WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID AND status='Pending'";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            if ($result->rowCount() != 1) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
            } else {
                $row = $result->fetch();
                $notes = $_POST['notes'];
                $status = 'Issued';
                $invoiceDueDate = $_POST['invoiceDueDate'];
                if ($row['billingScheduleType'] == 'Scheduled') {
                    $separated = 'Y';
                } else {
                    $separated = null;
                }
                $invoiceIssueDate = date('Y-m-d');

                if ($invoiceDueDate == '') {
                    $URL .= '&return=error1';
                    header("Location: {$URL}");
                } else {
                    //Write to database
                    try {
                        $data = array('status' => $status, 'notes' => $notes, 'separated' => $separated, 'invoiceDueDate' => dateConvert($guid, $invoiceDueDate), 'invoiceIssueDate' => $invoiceIssueDate, 'gibbonPersonIDUpdate' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonFinanceInvoiceID' => $gibbonFinanceInvoiceID);
                        $sql = "UPDATE gibbonFinanceInvoice SET status=:status, notes=:notes, separated=:separated, invoiceDueDate=:invoiceDueDate, invoiceIssueDate=:invoiceIssueDate, gibbonPersonIDUpdate=:gibbonPersonIDUpdate, timestampUpdate='".date('Y-m-d H:i:s')."' WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID";
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    $partialFail = false;
                    $emailFail = false;
                    
                    //Read & Organise Fees
                    $fees = array();
                    $count = 0;
                    //Standard Fees
                    try {
                        $dataFees['gibbonFinanceInvoiceID'] = $gibbonFinanceInvoiceID;
                        $sqlFees = "SELECT gibbonFinanceInvoiceFee.gibbonFinanceInvoiceFeeID, gibbonFinanceInvoiceFee.feeType, gibbonFinanceFeeCategory.name AS category, gibbonFinanceFee.name AS name, gibbonFinanceFee.fee AS fee, gibbonFinanceFee.description AS description, gibbonFinanceInvoiceFee.gibbonFinanceFeeID AS gibbonFinanceFeeID, gibbonFinanceFee.gibbonFinanceFeeCategoryID AS gibbonFinanceFeeCategoryID, sequenceNumber FROM gibbonFinanceInvoiceFee JOIN gibbonFinanceFee ON (gibbonFinanceInvoiceFee.gibbonFinanceFeeID=gibbonFinanceFee.gibbonFinanceFeeID) JOIN gibbonFinanceFeeCategory ON (gibbonFinanceFee.gibbonFinanceFeeCategoryID=gibbonFinanceFeeCategory.gibbonFinanceFeeCategoryID) WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID AND feeType='Standard' ORDER BY sequenceNumber";
                        $resultFees = $connection2->prepare($sqlFees);
                        $resultFees->execute($dataFees);
                    } catch (PDOException $e) {
                        $partialFail = true;
                    }
                    while ($rowFees = $resultFees->fetch()) {
                        $fees[$count]['name'] = $rowFees['name'];
                        $fees[$count]['gibbonFinanceFeeCategoryID'] = $rowFees['gibbonFinanceFeeCategoryID'];
                        $fees[$count]['fee'] = $rowFees['fee'];
                        $fees[$count]['feeType'] = 'Standard';
                        $fees[$count]['gibbonFinanceFeeID'] = $rowFees['gibbonFinanceFeeID'];
                        $fees[$count]['separated'] = 'Y';
                        $fees[$count]['description'] = $rowFees['description'];
                        $fees[$count]['sequenceNumber'] = $rowFees['sequenceNumber'];
                        ++$count;
                    }

                    //Ad Hoc Fees
                    try {
                        $dataFees['gibbonFinanceInvoiceID'] = $gibbonFinanceInvoiceID;
                        $sqlFees = "SELECT gibbonFinanceInvoiceFee.gibbonFinanceInvoiceFeeID, gibbonFinanceInvoiceFee.feeType, gibbonFinanceFeeCategory.name AS category, gibbonFinanceInvoiceFee.name AS name, gibbonFinanceInvoiceFee.fee, gibbonFinanceInvoiceFee.description AS description, NULL AS gibbonFinanceFeeID, gibbonFinanceInvoiceFee.gibbonFinanceFeeCategoryID AS gibbonFinanceFeeCategoryID, sequenceNumber FROM gibbonFinanceInvoiceFee JOIN gibbonFinanceFeeCategory ON (gibbonFinanceInvoiceFee.gibbonFinanceFeeCategoryID=gibbonFinanceFeeCategory.gibbonFinanceFeeCategoryID) WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID AND feeType='Ad Hoc' ORDER BY sequenceNumber";
                        $resultFees = $connection2->prepare($sqlFees);
                        $resultFees->execute($dataFees);
                    } catch (PDOException $e) {
                        $partialFail = true;
                    }
                    while ($rowFees = $resultFees->fetch()) {
                        $fees[$count]['name'] = $rowFees['name'];
                        $fees[$count]['gibbonFinanceFeeCategoryID'] = $rowFees['gibbonFinanceFeeCategoryID'];
                        $fees[$count]['fee'] = $rowFees['fee'];
                        $fees[$count]['feeType'] = 'Ad Hoc';
                        $fees[$count]['gibbonFinanceFeeID'] = null;
                        $fees[$count]['separated'] = null;
                        $fees[$count]['description'] = $rowFees['description'];
                        $fees[$count]['sequenceNumber'] = $rowFees['sequenceNumber'];
                        ++$count;
                    }

                    //Remove fees
                    try {
                        $data = array('gibbonFinanceInvoiceID' => $gibbonFinanceInvoiceID);
                        $sql = 'DELETE FROM gibbonFinanceInvoiceFee WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $partialFail = true;
                    }

                    //Add fees to invoice
                    foreach ($fees as $fee) {
                        try {
                            $dataInvoiceFee = array('gibbonFinanceInvoiceID' => $gibbonFinanceInvoiceID, 'feeType' => $fee['feeType'], 'gibbonFinanceFeeID' => $fee['gibbonFinanceFeeID'], 'name' => $fee['name'], 'description' => $fee['description'], 'gibbonFinanceFeeCategoryID' => $fee['gibbonFinanceFeeCategoryID'], 'fee' => $fee['fee'], 'separated' => $fee['separated'], 'sequenceNumber' => $fee['sequenceNumber']);
                            $sqlInvoiceFee = 'INSERT INTO gibbonFinanceInvoiceFee SET gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID, feeType=:feeType, gibbonFinanceFeeID=:gibbonFinanceFeeID, name=:name, description=:description, gibbonFinanceFeeCategoryID=:gibbonFinanceFeeCategoryID, fee=:fee, separated=:separated, sequenceNumber=:sequenceNumber';
                            $resultInvoiceFee = $connection2->prepare($sqlInvoiceFee);
                            $resultInvoiceFee->execute($dataInvoiceFee);
                        } catch (PDOException $e) {
                            $partialFail = true;
                        }
                    }

                    //Unlock module table
                    try {
                        $sql = 'UNLOCK TABLES';
                        $result = $connection2->query($sql);
                    } catch (PDOException $e) {
                    }

                    $from = $_POST['email'];
                    if ($partialFail == false and $from != '') {
                        //Send emails
                        $emails = array() ;
                        if (isset($_POST['emails'])) {
                            $emails = $_POST['emails'];
                            for ($i = 0; $i < count($emails); ++$i) {
                                $emailsInner = explode(',', $emails[$i]);
                                for ($n = 0; $n < count($emailsInner); ++$n) {
                                    if ($n == 0) {
                                        $emails[$i] = trim($emailsInner[$n]);
                                    } else {
                                        array_push($emails, trim($emailsInner[$n]));
                                    }
                                }
                            }
                        }

                        if (count($emails) > 0) {
                            require $_SESSION[$guid]['absolutePath'].'/lib/PHPMailer/PHPMailerAutoload.php';

                            //Prep message
                            $body = invoiceContents($guid, $connection2, $gibbonFinanceInvoiceID, $gibbonSchoolYearID, $_SESSION[$guid]['currency'], true)."<p style='font-style: italic;'>Email sent via ".$_SESSION[$guid]['systemName'].' at '.$_SESSION[$guid]['organisationName'].'.</p>';
                            $bodyPlain = 'This email is not viewable in plain text: enable rich text/HTML in your email client to view the invoice. Please reply to this email if you have any questions.';

                            $mail = getGibbonMailer($guid);
                            $mail->SetFrom($from, sprintf(__($guid, '%1$s Finance'), $_SESSION[$guid]['organisationName']));
                            foreach ($emails as $address) {
                                $mail->AddBCC($address);
                            }
                            $mail->CharSet = 'UTF-8';
                            $mail->Encoding = 'base64';
                            $mail->IsHTML(true);
                            $mail->Subject = 'Invoice From '.$_SESSION[$guid]['organisationNameShort'].' via '.$_SESSION[$guid]['systemName'];
                            $mail->Body = $body;
                            $mail->AltBody = $bodyPlain;

                            if (!$mail->Send()) {
                                $emailFail = true;
                            }
                        }
                    }

                    if ($partialFail == true) {
                        $URL .= '&return=error3';
                        header("Location: {$URL}");
                    } elseif ($emailFail == true) {
                        $URLSuccess = $URLSuccess.'&return=success1';
                        header("Location: {$URLSuccess}");
                    } else {
                        $URLSuccess = $URLSuccess.'&return=success0';
                        header("Location: {$URLSuccess}");
                    }
                }
            }
        }
    }
}
