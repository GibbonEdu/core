<?php
/**
 * @covers modules/System Admin/import_run.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Import Run with CSV file upload');
$I->loginAsAdmin();

// Read CSV data to send directly (bypasses PhpBrowser MIME type limitation)
$csvData = file_get_contents(codecept_data_dir() . 'test_import_houses.csv');

// Ensure no leftover test data
$I->deleteFromDatabase('gibbonHouse', ['name' => 'Test House', 'nameShort' => 'TST']);

// Column mappings: name=col0, nameShort=col1, logo=col2
$columnOrder = ['0', '1', '2'];

// Step 3: Dry Run — POST directly with CSV data and column mappings
// (Steps 1-2 are skipped because PhpBrowser sends empty MIME type for file uploads,
//  which the Importer rejects. Steps 3-4 only need the CSV text data, not the file.)
$I->sendAjaxPostRequest('/index.php?q=/modules/System Admin/import_run.php&type=houses&step=3', [
    'address'         => '/modules/System Admin/import_run.php',
    'mode'            => 'insert',
    'csvData'         => $csvData,
    'columnOrder'     => $columnOrder,
    'fieldDelimiter'  => urlencode(','),
    'stringEnclosure' => urlencode('"'),
]);

$I->see('Step 3');
$I->see('Dry Run');
$I->see('successfully validated');

// Step 4: Live Run — POST with JSON-encoded column data (as step 4 expects)
$I->sendAjaxPostRequest('/index.php?q=/modules/System Admin/import_run.php&type=houses&step=4', [
    'address'         => '/modules/System Admin/import_run.php',
    'mode'            => 'insert',
    'csvData'         => $csvData,
    'columnOrder'     => json_encode($columnOrder),
    'fieldDelimiter'  => urlencode(','),
    'stringEnclosure' => urlencode('"'),
]);

$I->see('Step 4');
$I->see('import completed successfully');

// Verify the house was imported
$I->seeInDatabase('gibbonHouse', [
    'name'      => 'Test House',
    'nameShort' => 'TST',
]);

// Cleanup
$I->deleteFromDatabase('gibbonHouse', [
    'name'      => 'Test House',
    'nameShort' => 'TST',
]);
$I->deleteFromDatabase('gibbonLog', [
    'title' => 'Import - houses',
]);
