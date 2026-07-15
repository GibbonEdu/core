<?php
/**
 * @covers modules/Finance/invoices_manage_processBulkExportContents.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('export invoices in bulk');
$I->loginAsAdmin();

$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']);

// Create a test invoice first
$gibbonFinanceInvoiceeID = $I->grabFromDatabase('gibbonFinanceInvoicee', 'gibbonFinanceInvoiceeID', []);

if (!$gibbonFinanceInvoiceeID) {
    $I->comment('No invoicees found, skipping bulk export test');
    return;
}

// Get admin person ID for creator field
$gibbonPersonIDCreator = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', ['username' => 'admin']);

// Create a pending invoice for export
$gibbonFinanceInvoiceID = $I->haveInDatabase('gibbonFinanceInvoice', [
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
    'gibbonFinanceInvoiceeID' => $gibbonFinanceInvoiceeID,
    'invoiceTo' => 'Family',
    'billingScheduleType' => 'Ad Hoc',
    'status' => 'Pending',
    'invoiceIssueDate' => date('Y-m-d'),
    'invoiceDueDate' => date('Y-m-d', strtotime('+30 days')),
    'notes' => 'Test invoice for export',
    'key' => uniqid('test_', true),
    'gibbonPersonIDCreator' => $gibbonPersonIDCreator,
]);

// Set the session variable for export
$I->haveHttpHeader('Cookie', 'financeInvoiceExportIDs=' . serialize([$gibbonFinanceInvoiceID]));

// Navigate to the export page
$I->amOnModulePage('Finance', 'invoices_manage_processBulkExportContents.php', [
    'gibbonSchoolYearID' => $gibbonSchoolYearID
]);

$I->dontSeeErrors();
