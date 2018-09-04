<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('update Finance Settings');
$I->loginAsAdmin();
$I->amOnModulePage('School Admin', 'financeSettings.php');

// Grab Original Settings --------------------------------------

$originalFormValues = $I->grabAllFormValues();
$I->seeInFormFields('#content form', $originalFormValues);

// Make Changes ------------------------------------------------

$newFormValues = array(
    'email'                         => 'test@testing.test',
    'financeOnlinePaymentEnabled'   => 'Y',
    'financeOnlinePaymentThreshold' => '100.00',
    'invoiceText'                   => 'Invoice Test',
    'invoiceNotes'                  => 'Notes Test',
    'invoiceeNameStyle'             => 'Official Name',
    'invoiceNumber'                 => 'Student ID + Invoice ID',
    'receiptText'                   => 'Receipt Test',
    'receiptNotes'                  => 'Receipt Notes Test',
    'hideItemisation'               => 'Y',
    'reminder1Text'                 => 'Reminder 1 Test',
    'reminder2Text'                 => 'Reminder 2 Test',
    'reminder3Text'                 => 'Reminder 3 Test',
    'budgetCategories'              => 'Category1,Category2,Category3',
    'expenseApprovalType'           => 'Chain Of All',
    'budgetLevelExpenseApproval'    => 'Y',
    'expenseRequestTemplate'        => '<div><p>Expense Tempalte Test</p></div>',
    'allowExpenseAdd'               => 'Y',
);

$I->selectFromDropdown('purchasingOfficer', 2);
$I->selectFromDropdown('reimbursementOfficer', 2);

$I->submitForm('#content form', $newFormValues, 'Submit');

// Verify Results ----------------------------------------------

$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $newFormValues);

// Restore Original Settings -----------------------------------

$I->submitForm('#content form', $originalFormValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $originalFormValues);
