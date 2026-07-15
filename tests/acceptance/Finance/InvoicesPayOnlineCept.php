<?php
/**
 * @covers modules/Finance/invoices_payOnline.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check online payment page');
$I->loginAsAdmin();

$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']);

// Get an invoicee
$gibbonFinanceInvoiceeID = $I->grabFromDatabase('gibbonFinanceInvoicee', 'gibbonFinanceInvoiceeID', []);

if (!$gibbonFinanceInvoiceeID) {
    $I->comment('No invoicees found, skipping pay online test');
    return;
}

// Get admin person ID for creator field
$gibbonPersonIDCreator = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', ['username' => 'admin']);

// Create an issued invoice for payment
$invoiceKey = uniqid('test_', true);
$gibbonFinanceInvoiceID = $I->haveInDatabase('gibbonFinanceInvoice', [
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
    'gibbonFinanceInvoiceeID' => $gibbonFinanceInvoiceeID,
    'invoiceTo' => 'Family',
    'billingScheduleType' => 'Ad Hoc',
    'status' => 'Issued',
    'invoiceIssueDate' => date('Y-m-d'),
    'invoiceDueDate' => date('Y-m-d', strtotime('+30 days')),
    'key' => $invoiceKey,
    'notes' => 'Test invoice for online payment',
    'gibbonPersonIDCreator' => $gibbonPersonIDCreator,
]);

// Add a fee to the invoice
$I->haveInDatabase('gibbonFinanceInvoiceFee', [
    'gibbonFinanceInvoiceID' => $gibbonFinanceInvoiceID,
    'feeType' => 'Ad Hoc',
    'name' => 'Test Fee',
    'fee' => 100.00,
    'gibbonFinanceFeeCategoryID' => 1,
    'sequenceNumber' => 1,
]);

// Navigate to the pay online page
$I->amOnModulePage('Finance', 'invoices_payOnline.php', [
    'gibbonFinanceInvoiceID' => $gibbonFinanceInvoiceID,
    'key' => $invoiceKey
]);

$I->dontSeeErrors();
