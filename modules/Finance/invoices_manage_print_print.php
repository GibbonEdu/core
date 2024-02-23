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

//Module includes
use Gibbon\Domain\System\SettingGateway;

require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Finance/invoices_manage_print.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
    $gibbonFinanceInvoiceID = $_GET['gibbonFinanceInvoiceID'] ?? '';
    $type = $_GET['type'] ?? '';
    $preview = false;
    if (isset($_GET['preview']) && $_GET['preview'] == 'true') {
        $preview = $_GET['preview'] ?? '';
    }
    $receiptNumber = null;
    if (isset($_GET['receiptNumber'])) {
        $receiptNumber = $_GET['receiptNumber'] ?? '';
    }

    if ($gibbonFinanceInvoiceID == '' or $gibbonSchoolYearID == '' or $type == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {

            $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonFinanceInvoiceID' => $gibbonFinanceInvoiceID);
            $sql = 'SELECT surname, preferredName, gibbonFinanceInvoice.* FROM gibbonFinanceInvoice JOIN gibbonFinanceInvoicee ON (gibbonFinanceInvoice.gibbonFinanceInvoiceeID=gibbonFinanceInvoicee.gibbonFinanceInvoiceeID) JOIN gibbonPerson ON (gibbonFinanceInvoicee.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $row = $result->fetch();

            $statusExtra = '';
            if ($row['status'] == 'Issued' and $row['invoiceDueDate'] < date('Y-m-d')) {
                $statusExtra = 'Overdue';
            }
            if ($row['status'] == 'Paid' and $row['invoiceDueDate'] < $row['paidDate']) {
                $statusExtra = 'Late';
            }

            if ($type == 'invoice') {
                echo '<h2>';
                echo __('Invoice');
                echo '</h2>';
                if ($preview) {
                    echo "<p style='font-weight: bold; color: #c00; font-size: 100%; letter-spacing: -0.5px'>";
                    echo __('THIS INVOICE IS A PREVIEW: IT HAS NOT YET BEEN ISSUED AND IS FOR TESTING PURPOSES ONLY!');
                    echo '</p>';
                }

                $invoiceContents = invoiceContents($guid, $connection2, $gibbonFinanceInvoiceID, $gibbonSchoolYearID, $session->get('currency'), false, $preview);
                if ($invoiceContents == false) {
                    $page->addError(__('An error occurred.'));
                } else {
                    echo $invoiceContents;
                }
            } elseif ($type == 'reminder1' or $type == 'reminder2' or $type == 'reminder3') {
                //Update reminder count
                if ($row['reminderCount'] < 3) {

                        $data = array('gibbonFinanceInvoiceID' => $gibbonFinanceInvoiceID, 'reminderCount' => $row['reminderCount'] + 1);
                        $sql = 'UPDATE gibbonFinanceInvoice SET reminderCount=:reminderCount WHERE gibbonFinanceInvoiceID=:gibbonFinanceInvoiceID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                }

                $settingGateway = $container->get(SettingGateway::class);

                //Reminder Text
                if ($type == 'reminder1') {
                    echo '<h2>';
                    echo __('Reminder 1');
                    echo '</h2>';
                    $reminderText = $settingGateway->getSettingByScope('Finance', 'reminder1Text');
                } elseif ($type == 'reminder2') {
                    echo '<h2>';
                    echo __('Reminder 2');
                    echo '</h2>';
                    $reminderText = $settingGateway->getSettingByScope('Finance', 'reminder2Text');
                } elseif ($type == 'reminder3') {
                    echo '<h2>';
                    echo __('Reminder 3');
                    echo '</h2>';
                    $reminderText = $settingGateway->getSettingByScope('Finance', 'reminder3Text');
                }
                if ($reminderText != '') {
                    echo '<p>';
                    echo $reminderText;
                    echo '</p>';
                }

                echo '<h2>';
                echo __('Invoice');
                echo '</h2>';
                $invoiceContents = invoiceContents($guid, $connection2, $gibbonFinanceInvoiceID, $gibbonSchoolYearID, $session->get('currency'));
                if ($invoiceContents == false) {
                    $page->addError(__('An error occurred.'));
                } else {
                    echo $invoiceContents;
                }
            } elseif ($type = 'Receipt') {
                echo '<h2>';
                echo __('Receipt');
                echo '</h2>';
                $receiptContents = receiptContents($guid, $connection2, $gibbonFinanceInvoiceID, $gibbonSchoolYearID, $session->get('currency'), false, $receiptNumber);
                if ($receiptContents == false) {
                    $page->addError(__('An error occurred.'));
                } else {
                    echo $receiptContents;
                }
            }
        }
    }
}
