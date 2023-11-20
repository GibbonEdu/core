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

use Gibbon\Contracts\Comms\Mailer;
use Gibbon\Contracts\Services\Payment;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

include './moduleFunctions.php';

$gibbonFinanceInvoiceID = $_REQUEST['gibbonFinanceInvoiceID'] ?? '';
$key = $_REQUEST['key'] ?? '';

$URL = $session->get('absoluteURL')."/index.php?q=/modules/Finance/invoices_payOnline.php&gibbonFinanceInvoiceID=$gibbonFinanceInvoiceID&key=$key";
$URLPayment = $session->get('absoluteURL')."/modules/Finance/invoices_payOnlineProcess.php?gibbonFinanceInvoiceID=$gibbonFinanceInvoiceID&key=$key";

$payment = $container->get(Payment::class);
$payment->setReturnURL($URLPayment);
$payment->setCancelURL($URLPayment);
$payment->setForeignTable('gibbonFinanceInvoice', $gibbonFinanceInvoiceID);

$settingGateway = $container->get(SettingGateway::class);

if (!$payment->incomingPayment()) {
    // No incoming payment, let's send a request

    // Check variables
    if (empty($gibbonFinanceInvoiceID)|| empty($key)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    //Check for record
    $keyReadFail = false;
    try {
        $dataKeyRead = array('gibbonFinanceInvoiceID' => $gibbonFinanceInvoiceID, 'key' => $key);
        $sqlKeyRead = "SELECT * FROM gibbonFinanceInvoice WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID AND `key`=:key AND status='Issued'";
        $resultKeyRead = $connection2->prepare($sqlKeyRead);
        $resultKeyRead->execute($dataKeyRead);
    } catch (PDOException $e) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit();
    }

    if ($resultKeyRead->rowCount() != 1) { //If not exists, report error
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit();
    } else {    //If exists check confirmed
        $rowKeyRead = $resultKeyRead->fetch();

        //Get value of the invoice.
        $feeOK = true;
        try {
            $dataFees['gibbonFinanceInvoiceID'] = $gibbonFinanceInvoiceID;
            $sqlFees = 'SELECT gibbonFinanceInvoiceFee.gibbonFinanceInvoiceFeeID, gibbonFinanceInvoiceFee.feeType, gibbonFinanceFeeCategory.name AS category, gibbonFinanceInvoiceFee.name AS name, gibbonFinanceInvoiceFee.fee, gibbonFinanceInvoiceFee.description AS description, NULL AS gibbonFinanceFeeID, gibbonFinanceInvoiceFee.gibbonFinanceFeeCategoryID AS gibbonFinanceFeeCategoryID, sequenceNumber FROM gibbonFinanceInvoiceFee JOIN gibbonFinanceFeeCategory ON (gibbonFinanceInvoiceFee.gibbonFinanceFeeCategoryID=gibbonFinanceFeeCategory.gibbonFinanceFeeCategoryID) WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID ORDER BY sequenceNumber';
            $resultFees = $connection2->prepare($sqlFees);
            $resultFees->execute($dataFees);
        } catch (PDOException $e) {
            $feeOK = false;
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        if ($feeOK == true) {
            $feeTotal = 0;
            while ($rowFees = $resultFees->fetch()) {
                $feeTotal += $rowFees['fee'];
            }

            if ($payment->isEnabled() and $feeTotal > 0) {
                $financeOnlinePaymentEnabled = $settingGateway->getSettingByScope('Finance', 'financeOnlinePaymentEnabled');
                $financeOnlinePaymentThreshold = $settingGateway->getSettingByScope('Finance', 'financeOnlinePaymentThreshold');
                if ($financeOnlinePaymentEnabled == 'Y') {
                    if ($financeOnlinePaymentThreshold == '' or $financeOnlinePaymentThreshold >= $feeTotal) {
                        // Let's make a payment
                        $return = $payment->requestPayment($feeTotal, __('Invoice Number') .' '.$gibbonFinanceInvoiceID);

                        if (!empty($return)) {
                            $URL .= '&return='.$return;
                            header("Location: " . $URL);
                            exit;
                        }

                    } else {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }
                } else {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }
            } else {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }
        }
        
    }
} else { 
    // Handle incoming payment

    $gibbonFinanceInvoiceID = $_GET['gibbonFinanceInvoiceID'] ?? '';
    $key = $_GET['key'] ?? '';

    $gibbonFinanceInvoiceeID = '';
    $invoiceTo = '';
    $gibbonSchoolYearID = '';

    $dataKeyRead = array('gibbonFinanceInvoiceID' => $gibbonFinanceInvoiceID, 'key' => $key);
    $sqlKeyRead = 'SELECT gibbonFinanceInvoice.gibbonFinanceInvoiceeID, gibbonFinanceInvoice.invoiceTo, gibbonFinanceInvoice.gibbonSchoolYearID FROM gibbonFinanceInvoice WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID AND `key`=:key';
    $resultKeyRead = $connection2->prepare($sqlKeyRead);
    $resultKeyRead->execute($dataKeyRead);
    if ($resultKeyRead->rowCount() == 1) {
        $rowKeyRead = $resultKeyRead->fetch();
        $gibbonFinanceInvoiceeID = $rowKeyRead['gibbonFinanceInvoiceeID'];
        $invoiceTo = $rowKeyRead['invoiceTo'];
        $gibbonSchoolYearID = $rowKeyRead['gibbonSchoolYearID'];
    }

    //Check return values to see if we can proceed
    if ($gibbonFinanceInvoiceID == '' or $key == '' or $gibbonFinanceInvoiceeID == '' or $invoiceTo = '' or $gibbonSchoolYearID == '') {
        $URL .= '&return=warning3';
        header("Location: {$URL}");
        exit();
    } else {
        //PROCEED AND FINALISE PAYMENT
        $return = $payment->confirmPayment();
        $result = $payment->getPaymentResult();
        $gibbonPaymentID = $result['gibbonPaymentID'];

        //Payment was successful. Yeah!
        if ($result['success']) {
            $updateFail = false;

            //Link gibbonPayment record to gibbonFinanceInvoice, and make note that payment made
            if (!empty($gibbonPaymentID)) {
                $data = array('paidDate' => date('Y-m-d'), 'paidAmount' => $result['amount'], 'gibbonPaymentID' => $gibbonPaymentID, 'gibbonFinanceInvoiceID' => $gibbonFinanceInvoiceID);
                $sql = "UPDATE gibbonFinanceInvoice SET status='Paid', paidDate=:paidDate, paidAmount=:paidAmount, gibbonPaymentID=:gibbonPaymentID WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } else {
                $updateFail = true;
            }

            if ($updateFail == true) {
                $URL .= "&addReturn=success3&gibbonFinanceInvoiceID=$gibbonFinanceInvoiceID&key=$key";
                header("Location: {$URL}");
                exit;
            }

            //EMAIL RECEIPT (no error reporting)
            //Populate to email.
            $emails = [];
            $emailsCount = 0;
            if ($invoiceTo == 'Company') {

                $dataCompany = array('gibbonFinanceInvoiceeID' => $gibbonFinanceInvoiceeID);
                $sqlCompany = 'SELECT * FROM gibbonFinanceInvoicee WHERE gibbonFinanceInvoiceeID=:gibbonFinanceInvoiceeID';
                $resultCompany = $connection2->prepare($sqlCompany);
                $resultCompany->execute($dataCompany);
                if ($resultCompany->rowCount() != 1) {
                } else {
                    $rowCompany = $resultCompany->fetch();
                    if ($rowCompany['companyEmail'] != '' and $rowCompany['companyContact'] != '' and $rowCompany['companyName'] != '') {
                        $emails = array_map('trim', explode(',', $rowCompany['companyEmail']));
                        $emailsCount += count($emails);

                        $rowCompany['companyCCFamily'];
                        if ($rowCompany['companyCCFamily'] == 'Y') {
                            try {
                                $dataParents = array('gibbonFinanceInvoiceeID' => $gibbonFinanceInvoiceeID);
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
                    $dataParents = array('gibbonFinanceInvoiceeID' => $gibbonFinanceInvoiceeID);
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

            //Send emails
            if (count($emails) > 0) {
                //Get receipt number

                $dataPayments = array('foreignTable' => 'gibbonFinanceInvoice', 'foreignTableID' => $gibbonFinanceInvoiceID);
                $sqlPayments = 'SELECT gibbonPayment.*, surname, preferredName FROM gibbonPayment JOIN gibbonPerson ON (gibbonPayment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE foreignTable=:foreignTable AND foreignTableID=:foreignTableID ORDER BY timestamp, gibbonPaymentID';
                $resultPayments = $connection2->prepare($sqlPayments);
                $resultPayments->execute($dataPayments);
                $receiptCount = $resultPayments->rowCount();

                //Prep message
                $body = receiptContents($guid, $connection2, $gibbonFinanceInvoiceID, $gibbonSchoolYearID, $session->get('currency'), true, $receiptCount)."<p style='font-style: italic;'>Email sent via ".$session->get('systemName').' at '.$session->get('organisationName').'.</p>';

                $mail = $container->get(Mailer::class);
                $mail->SetFrom($settingGateway->getSettingByScope('Finance', 'email'), sprintf(__('%1$s Finance'), $session->get('organisationName')));
                foreach ($emails as $address) {
                    $mail->AddBCC($address);
                }

                $mail->Subject = __('Receipt from {organisation} via {system}', [
                    'organisation' => $session->get('organisationNameShort'),
                    'system' => $session->get('systemName'),
                ]);

                $mail->renderBody('mail/email.twig.html', [
                    'title'  => $mail->Subject,
                    'body'   => $body,
                    'maxWidth' => '900px',
                ]);

                $mail->Send();
            }

            $URL .= "&return=success1&gibbonFinanceInvoiceID=$gibbonFinanceInvoiceID&key=$key";
            header("Location: {$URL}");
        } else {
            if ($return == 'warning3') {
                $URL .= "&return=warning3&gibbonFinanceInvoiceID=$gibbonFinanceInvoiceID&key=$key";
                header("Location: {$URL}");
                exit;
            }
            $updateFail = false;

            //Link gibbonPayment record to gibbonApplicationForm, and make note that payment made
            if (!empty($gibbonPaymentID)) {
                $data = array('gibbonPaymentID' => $gibbonPaymentID, 'gibbonFinanceInvoiceID' => $gibbonFinanceInvoiceID);
                $sql = 'UPDATE gibbonFinanceInvoice gibbonPaymentID=:gibbonPaymentID WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } else {
                $updateFail = true;
            }

            if ($updateFail == true) {
                //Success 2
                $URL .= "&return=success2&gibbonFinanceInvoiceID=$gibbonFinanceInvoiceID&key=$key";
                header("Location: {$URL}");
                exit;
            }

            //Success 2
            $URL .= "&return=success2&gibbonFinanceInvoiceID=$gibbonFinanceInvoiceID&key=$key";
            header("Location: {$URL}");
        }
    }
}
