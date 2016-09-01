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

//PHPMailer include
require $_SESSION[$guid]['absolutePath'].'/lib/PHPMailer/class.phpmailer.php';
$from = getSettingByScope($connection2, 'Finance', 'email');

//Module includes
include './moduleFunctions.php';

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]['timezone']);

$action = $_POST['action'];
$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
$status = $_GET['status'];
$gibbonFinanceInvoiceeID = $_GET['gibbonFinanceInvoiceeID'];
$monthOfIssue = $_GET['monthOfIssue'];
$gibbonFinanceBillingScheduleID = $_GET['gibbonFinanceBillingScheduleID'];

if ($gibbonSchoolYearID == '' or $action == '') { echo 'Fatal error loading this page!';
} else {
    if ($action == 'issue') {
        $URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/invoices_manage.php&gibbonSchoolYearID=$gibbonSchoolYearID&status=Issued&gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID&monthOfIssue=$monthOfIssue&gibbonFinanceBillingScheduleID=$gibbonFinanceBillingScheduleID";
    } else {
        $URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/invoices_manage.php&gibbonSchoolYearID=$gibbonSchoolYearID&status=$status&gibbonFinanceInvoiceeID=$gibbonFinanceInvoiceeID&monthOfIssue=$monthOfIssue&gibbonFinanceBillingScheduleID=$gibbonFinanceBillingScheduleID";
    }

    if (isActionAccessible($guid, $connection2, '/modules/Finance/invoices_manage.php') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        $gibbonFinanceInvoiceIDs = $_POST['gibbonFinanceInvoiceIDs'];
        if (count($gibbonFinanceInvoiceIDs) < 1) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            $partialFail = false;
            //DELETE
            if ($action == 'delete') {
                foreach ($gibbonFinanceInvoiceIDs as $gibbonFinanceInvoiceID) {
                    try {
                        $data = array('gibbonFinanceInvoiceID' => $gibbonFinanceInvoiceID);
                        $sql = 'DELETE FROM gibbonFinanceInvoice WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $partialFail = true;
                    }

                    try {
                        $data = array('gibbonFinanceInvoiceID' => $gibbonFinanceInvoiceID);
                        $sql = 'DELETE FROM gibbonFinanceInvoiceFee WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $partialFail = true;
                    }
                }
                if ($partialFail == true) {
                    $URL .= '&return=error?';
                    header("Location: {$URL}");
                } else {
                    $URL .= '&return=success0';
                    header("Location: {$URL}");
                }
            }
            //ISSUE
            elseif ($action == 'issue') {
                $thisLockFail = false;
                //LOCK INVOICE TABLES
                try {
                    $data = array();
                    $sql = 'LOCK TABLES gibbonFinanceInvoice WRITE, gibbonFinanceInvoiceFee WRITE, gibbonFinanceInvoicee WRITE, gibbonFinanceFee WRITE, gibbonFinanceFeeCategory WRITE, gibbonFinanceBillingSchedule WRITE';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $partialFail = true;
                    $thisLockFail = true;
                }

                if ($thisLockFail == false) {
                    $emailFail = false;
                    foreach ($gibbonFinanceInvoiceIDs as $gibbonFinanceInvoiceID) {
                        try {
                            $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonFinanceInvoiceID' => $gibbonFinanceInvoiceID);
                            $sql = "SELECT gibbonFinanceInvoice.*, gibbonFinanceBillingSchedule.invoiceDueDate AS invoiceDueDateScheduled FROM gibbonFinanceInvoice LEFT JOIN gibbonFinanceBillingSchedule ON (gibbonFinanceInvoice.gibbonFinanceBillingScheduleID=gibbonFinanceBillingSchedule.gibbonFinanceBillingScheduleID) WHERE gibbonFinanceInvoice.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID AND status='Pending'";
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                        }

                        if ($result->rowCount() != 1) {
                            $partialFail = true;
                        } else {
                            $row = $result->fetch();
                            $status = 'Issued';
                            if ($row['billingScheduleType'] == 'Scheduled') {
                                $separated = 'Y';
                                $invoiceDueDate = $row['invoiceDueDateScheduled'];
                            } else {
                                $separated = null;
                                $invoiceDueDate = $row['invoiceDueDate'];
                            }
                            $invoiceIssueDate = date('Y-m-d');

                            if ($invoiceDueDate == '') {
                                $partialFail = true;
                            } else {
                                //Write to database
                                try {
                                    $data = array('status' => $status, 'separated' => $separated, 'invoiceDueDate' => $invoiceDueDate, 'invoiceIssueDate' => $invoiceIssueDate, 'gibbonPersonIDUpdate' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonFinanceInvoiceID' => $gibbonFinanceInvoiceID);
                                    $sql = "UPDATE gibbonFinanceInvoice SET status=:status, separated=:separated, invoiceDueDate=:invoiceDueDate, invoiceIssueDate=:invoiceIssueDate, gibbonPersonIDUpdate=:gibbonPersonIDUpdate, timestampUpdate='".date('Y-m-d H:i:s')."' WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID";
                                    $result = $connection2->prepare($sql);
                                    $result->execute($data);
                                } catch (PDOException $e) {
                                    $URL .= '&return=error2';
                                    header("Location: {$URL}");
                                    exit();
                                }

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
                            }
                        }
                    }
                }

                //Unlock invoice table
                try {
                    $sql = 'UNLOCK TABLES';
                    $result = $connection2->query($sql);
                } catch (PDOException $e) {
                }

                //Loop through invoices again, this time to send invoices....they can not be sent in first loop due to table locking issues.
                foreach ($gibbonFinanceInvoiceIDs as $gibbonFinanceInvoiceID) {
                    try {
                        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonFinanceInvoiceID' => $gibbonFinanceInvoiceID);
                        $sql = 'SELECT gibbonFinanceInvoice.*, gibbonFinanceBillingSchedule.invoiceDueDate AS invoiceDueDateScheduled FROM gibbonFinanceInvoice LEFT JOIN gibbonFinanceBillingSchedule ON (gibbonFinanceInvoice.gibbonFinanceBillingScheduleID=gibbonFinanceBillingSchedule.gibbonFinanceBillingScheduleID) WHERE gibbonFinanceInvoice.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                    }

                    if ($result->rowCount() != 1) {
                        $emailFail = true;
                    } else {
                        $row = $result->fetch();

                        //DEAL WITH EMAILS
                        $emails = array();
                        $emailsCount = 0;
                        if ($row['invoiceTo'] == 'Company') {
                            try {
                                $dataCompany = array('gibbonFinanceInvoiceeID' => $row['gibbonFinanceInvoiceeID']);
                                $sqlCompany = 'SELECT * FROM gibbonFinanceInvoicee WHERE gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID';
                                $resultCompany = $connection2->prepare($sqlCompany);
                                $resultCompany->execute($dataCompany);
                            } catch (PDOException $e) {
                                $emailFail = true;
                            }
                            if ($resultCompany->rowCount() != 1) {
                                $emailFail = true;
                            } else {
                                $rowCompany = $resultCompany->fetch();
                                if ($rowCompany['companyEmail'] != '' and $rowCompany['companyContact'] != '' and $rowCompany['companyName'] != '') {
                                    $emailsInner = explode(',', $rowCompany['companyEmail']);
                                    for ($n = 0; $n < count($emailsInner); ++$n) {
                                        if ($n == 0) {
                                            $emails[$emailsCount] = trim($emailsInner[$n]);
                                            ++$emailsCount;
                                        } else {
                                            array_push($emails, trim($emailsInner[$n]));
                                            ++$emailsCount;
                                        }
                                    }
                                    if ($rowCompany['companyCCFamily'] == 'Y') {
                                        try {
                                            $dataParents = array('gibbonFinanceInvoiceeID' => $row['gibbonFinanceInvoiceeID']);
                                            $sqlParents = "SELECT parent.title, parent.surname, parent.preferredName, parent.email, parent.address1, parent.address1District, parent.address1Country, homeAddress, homeAddressDistrict, homeAddressCountry FROM gibbonFinanceInvoicee JOIN gibbonPerson AS student ON (gibbonFinanceInvoicee.gibbonPersonID=student.gibbonPersonID) JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=student.gibbonPersonID) JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamily.gibbonFamilyID=gibbonFamilyAdult.gibbonFamilyID) JOIN gibbonPerson AS parent ON (gibbonFamilyAdult.gibbonPersonID=parent.gibbonPersonID) WHERE gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID AND (contactPriority=1 OR (contactPriority=2 AND contactEmail='Y')) ORDER BY contactPriority, surname, preferredName";
                                            $resultParents = $connection2->prepare($sqlParents);
                                            $resultParents->execute($dataParents);
                                        } catch (PDOException $e) {
                                            $emailFail = true;
                                        }
                                        if ($resultParents->rowCount() < 1) {
                                            $emailFail = true;
                                        } else {
                                            while ($rowParents = $resultParents->fetch()) {
                                                if ($rowParents['preferredName'] != '' and $rowParents['surname'] != '' and $rowParents['email'] != '') {
                                                    $emails[$emailsCount] = $rowParents['email'];
                                                    ++$emailsCount;
                                                }
                                            }
                                        }
                                    }
                                } else {
                                    $emailFail = true;
                                }
                            }
                        } else {
                            try {
                                $dataParents = array('gibbonFinanceInvoiceeID' => $row['gibbonFinanceInvoiceeID']);
                                $sqlParents = "SELECT parent.title, parent.surname, parent.preferredName, parent.email, parent.address1, parent.address1District, parent.address1Country, homeAddress, homeAddressDistrict, homeAddressCountry FROM gibbonFinanceInvoicee JOIN gibbonPerson AS student ON (gibbonFinanceInvoicee.gibbonPersonID=student.gibbonPersonID) JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=student.gibbonPersonID) JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamily.gibbonFamilyID=gibbonFamilyAdult.gibbonFamilyID) JOIN gibbonPerson AS parent ON (gibbonFamilyAdult.gibbonPersonID=parent.gibbonPersonID) WHERE gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID AND (contactPriority=1 OR (contactPriority=2 AND contactEmail='Y')) ORDER BY contactPriority, surname, preferredName";
                                $resultParents = $connection2->prepare($sqlParents);
                                $resultParents->execute($dataParents);
                            } catch (PDOException $e) {
                                $emailFail = true;
                            }
                            if ($resultParents->rowCount() < 1) {
                                $emailFail = true;
                            } else {
                                while ($rowParents = $resultParents->fetch()) {
                                    if ($rowParents['preferredName'] != '' and $rowParents['surname'] != '' and $rowParents['email'] != '') {
                                        $emails[$emailsCount] = $rowParents['email'];
                                        ++$emailsCount;
                                    }
                                }
                            }
                        }

                        if ($from == '' or count($emails) < 1) {
                            $emailFail = true;
                        } else {
                            //Prep message
                            $body = invoiceContents($guid, $connection2, $gibbonFinanceInvoiceID, $gibbonSchoolYearID, $_SESSION[$guid]['currency'], true)."<p style='font-style: italic;'>Email sent via ".$_SESSION[$guid]['systemName'].' at '.$_SESSION[$guid]['organisationName'].'.</p>';
                            $bodyPlain = 'This email is not viewable in plain text: enable rich text/HTML in your email client to view the invoice. Please reply to this email if you have any questions.';

                            $mail = getGibbonMailer();
                            $mail->SetFrom($from, $_SESSION[$guid]['preferredName'].' '.$_SESSION[$guid]['surname']);
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
                }

                if ($partialFail == true) {
                    $URL .= '&return=error?';
                    header("Location: {$URL}");
                } elseif ($emailFail == true) {
                    $URL .= '&return=success1';
                    header("Location: {$URL}");
                } else {
                    $URL .= '&return=success0';
                    header("Location: {$URL}");
                }
            }
            //REMINDERS
            elseif ($action == 'reminders') {
                foreach ($gibbonFinanceInvoiceIDs as $gibbonFinanceInvoiceID) {
                    try {
                        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonFinanceInvoiceID' => $gibbonFinanceInvoiceID);
                        $sql = "SELECT gibbonFinanceInvoice.*, gibbonFinanceBillingSchedule.invoiceDueDate AS invoiceDueDateScheduled FROM gibbonFinanceInvoice LEFT JOIN gibbonFinanceBillingSchedule ON (gibbonFinanceInvoice.gibbonFinanceBillingScheduleID=gibbonFinanceBillingSchedule.gibbonFinanceBillingScheduleID) WHERE gibbonFinanceInvoice.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID AND status='Issued'";
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                    }

                    if ($result->rowCount() != 1) {
                        $partialFail = true;
                    } else {
                        $row = $result->fetch();

                        //DEAL WITH EMAILS
                        $emailFail = false;
                        $emails = array();
                        $emailsCount = 0;
                        if ($row['invoiceTo'] == 'Company') {
                            try {
                                $dataCompany = array('gibbonFinanceInvoiceeID' => $row['gibbonFinanceInvoiceeID']);
                                $sqlCompany = 'SELECT * FROM gibbonFinanceInvoicee WHERE gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID';
                                $resultCompany = $connection2->prepare($sqlCompany);
                                $resultCompany->execute($dataCompany);
                            } catch (PDOException $e) {
                                $emailFail = true;
                            }
                            if ($resultCompany->rowCount() != 1) {
                                $emailFail = true;
                            } else {
                                $rowCompany = $resultCompany->fetch();
                                if ($rowCompany['companyEmail'] != '' and $rowCompany['companyContact'] != '' and $rowCompany['companyName'] != '') {
                                    $emails[$emailsCount] = $rowCompany['companyEmail'];
                                    ++$emailsCount;
                                    if ($rowCompany['companyCCFamily'] == 'Y') {
                                        try {
                                            $dataParents = array('gibbonFinanceInvoiceeID' => $row['gibbonFinanceInvoiceeID']);
                                            $sqlParents = "SELECT parent.title, parent.surname, parent.preferredName, parent.email, parent.address1, parent.address1District, parent.address1Country, homeAddress, homeAddressDistrict, homeAddressCountry FROM gibbonFinanceInvoicee JOIN gibbonPerson AS student ON (gibbonFinanceInvoicee.gibbonPersonID=student.gibbonPersonID) JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=student.gibbonPersonID) JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamily.gibbonFamilyID=gibbonFamilyAdult.gibbonFamilyID) JOIN gibbonPerson AS parent ON (gibbonFamilyAdult.gibbonPersonID=parent.gibbonPersonID) WHERE gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID AND (contactPriority=1 OR (contactPriority=2 AND contactEmail='Y')) ORDER BY contactPriority, surname, preferredName";
                                            $resultParents = $connection2->prepare($sqlParents);
                                            $resultParents->execute($dataParents);
                                        } catch (PDOException $e) {
                                            $emailFail = true;
                                        }
                                        if ($resultParents->rowCount() < 1) {
                                            $emailFail = true;
                                        } else {
                                            while ($rowParents = $resultParents->fetch()) {
                                                if ($rowParents['preferredName'] != '' and $rowParents['surname'] != '' and $rowParents['email'] != '') {
                                                    $emails[$emailsCount] = $rowParents['email'];
                                                    ++$emailsCount;
                                                }
                                            }
                                        }
                                    }
                                } else {
                                    $emailFail = true;
                                }
                            }
                        } else {
                            try {
                                $dataParents = array('gibbonFinanceInvoiceeID' => $row['gibbonFinanceInvoiceeID']);
                                $sqlParents = "SELECT parent.title, parent.surname, parent.preferredName, parent.email, parent.address1, parent.address1District, parent.address1Country, homeAddress, homeAddressDistrict, homeAddressCountry FROM gibbonFinanceInvoicee JOIN gibbonPerson AS student ON (gibbonFinanceInvoicee.gibbonPersonID=student.gibbonPersonID) JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonPersonID=student.gibbonPersonID) JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamily.gibbonFamilyID=gibbonFamilyAdult.gibbonFamilyID) JOIN gibbonPerson AS parent ON (gibbonFamilyAdult.gibbonPersonID=parent.gibbonPersonID) WHERE gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID AND (contactPriority=1 OR (contactPriority=2 AND contactEmail='Y')) ORDER BY contactPriority, surname, preferredName";
                                $resultParents = $connection2->prepare($sqlParents);
                                $resultParents->execute($dataParents);
                            } catch (PDOException $e) {
                                $emailFail = true;
                            }
                            if ($resultParents->rowCount() < 1) {
                                $emailFail = true;
                            } else {
                                while ($rowParents = $resultParents->fetch()) {
                                    if ($rowParents['preferredName'] != '' and $rowParents['surname'] != '' and $rowParents['email'] != '') {
                                        $emails[$emailsCount] = $rowParents['email'];
                                        ++$emailsCount;
                                    }
                                }
                            }
                        }
                    }

                    if ($from == '' or count($emails) < 1) {
                        $emailFail = true;
                    } else {
                        //Prep message
                        $body = '';
                        if ($row['reminderCount'] == '0') {
                            $reminderText = getSettingByScope($connection2, 'Finance', 'reminder1Text');
                        } elseif ($row['reminderCount'] == '1') {
                            $reminderText = getSettingByScope($connection2, 'Finance', 'reminder2Text');
                        } elseif ($row['reminderCount'] >= '2') {
                            $reminderText = getSettingByScope($connection2, 'Finance', 'reminder3Text');
                        }
                        if ($reminderText != '') {
                            $reminderOutput = $row['reminderCount'] + 1;
                            if ($reminderOutput > 3) {
                                $reminderOutput = '3+';
                            }
                            $body .= '<p>Reminder '.$reminderOutput.': '.$reminderText.'</p><br/>';
                        }
                        $body .= invoiceContents($guid, $connection2, $gibbonFinanceInvoiceID, $gibbonSchoolYearID, $_SESSION[$guid]['currency'], true)."<p style='font-style: italic;'>Email sent via ".$_SESSION[$guid]['systemName'].' at '.$_SESSION[$guid]['organisationName'].'.</p>';
                        $bodyPlain = 'This email is not viewable in plain text: enable rich text/HTML in your email client to view the reminder. Please reply to this email if you have any questions.';

                        //Update reminder count
                        if ($row['reminderCount'] < 3) {
                            try {
                                $data = array('gibbonFinanceInvoiceID' => $gibbonFinanceInvoiceID);
                                $sql = 'UPDATE gibbonFinanceInvoice SET reminderCount='.($row['reminderCount'] + 1).' WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID';
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                            }
                        }

                        $mail = getGibbonMailer();
                        $mail->SetFrom($from, $_SESSION[$guid]['preferredName'].' '.$_SESSION[$guid]['surname']);
                        foreach ($emails as $address) {
                            $mail->AddBCC($address);
                        }
                        $mail->CharSet = 'UTF-8';
                        $mail->Encoding = 'base64';
                        $mail->IsHTML(true);
                        $mail->Subject = 'Reminder From '.$_SESSION[$guid]['organisationNameShort'].' via '.$_SESSION[$guid]['systemName'];
                        $mail->Body = $body;
                        $mail->AltBody = $bodyPlain;

                        if (!$mail->Send()) {
                            $emailFail = true;
                        }
                    }
                }

                if ($partialFail == true) {
                    $URL .= '&return=error?';
                    header("Location: {$URL}");
                } elseif ($emailFail == true) {
                    $URL .= '&return=success1';
                    header("Location: {$URL}");
                } else {
                    $URL .= '&return=success0';
                    header("Location: {$URL}");
                }
            }
            //Export
            elseif ($action == 'export') {
                $_SESSION[$guid]['financeInvoiceExportIDs'] = $gibbonFinanceInvoiceIDs;

				include ('./invoices_manage_processBulkExportContents.php');
            } else {
                $URL .= '&return=error1';
                header("Location: {$URL}");
            }
        }
    }
}
