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

$I->amOnModulePage('Finance', 'invoices_manage.php', [
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
]);

$I->submitForm('#bulkAction', [
    'action' => 'export',
    'gibbonFinanceInvoiceIDs' => [$gibbonFinanceInvoiceID],
]);

$I->dontSeeErrors();
