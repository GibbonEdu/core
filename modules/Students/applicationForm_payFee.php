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

use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Domain\Students\ApplicationFormGateway;

$gibbonApplicationFormHash = $_GET['key'] ?? '';
$gibbonApplicationFormID = $_GET['gibbonApplicationFormID'] ?? '';

$page->breadcrumbs->add(__('Application Fee'));

if (isset($_GET['return'])) {
    returnProcess($guid, $_GET['return'], null, [
        'error3' => __("Your payment could not be made as the payment gateway does not support the system's currency."),
        'error4' => __('Online payment options are not available at this time.'),
        'success1' => __('Your payment has been successfully made to your credit card. A receipt has been emailed to you.'), 'success2' => __('Your payment could not be made to your credit card. Please try an alternative payment method.'),
        'success3' => sprintf(__('Your payment has been successfully made to your credit card, but there has been an error recording your payment in %1$s. Please print this screen and contact the school ASAP, quoting code %2$s.'), $_SESSION[$guid]['systemName'], $gibbonApplicationFormID)
    ]);
    return;
}

if ($gibbonApplicationFormHash == '') {
    echo Format::alert(__('You have not specified one or more required parameters.'));
    return;
}

$application = $container->get(ApplicationFormGateway::class)->selectBy(['gibbonApplicationFormHash' => $gibbonApplicationFormHash])->fetch();
if (empty($application)) {
    echo Format::alert(__('The specified record does not exist.'));
    return;
}

if (!empty($application['gibbonPaymentID2']) || $application['paymentMade2'] != 'N') {
    echo Format::alert(__('A payment has already been made for this application form.'), 'success');
    return;
}

$enablePayments = getSettingByScope($connection2, 'System', 'enablePayments');
$paypalAPIUsername = getSettingByScope($connection2, 'System', 'paypalAPIUsername');
$paypalAPIPassword = getSettingByScope($connection2, 'System', 'paypalAPIPassword');
$paypalAPISignature = getSettingByScope($connection2, 'System', 'paypalAPISignature');
if ($enablePayments != 'Y' || empty($paypalAPIUsername) || empty($paypalAPIPassword) || empty($paypalAPISignature)) {
    echo Format::alert(__('Online payment options are not available at this time.'));
    return;
}

$currency = getSettingByScope($connection2, 'System', 'currency');
$applicationProcessFee = getSettingByScope($connection2, 'Application Form', 'applicationProcessFee');

$form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/Students/applicationForm_payFeeProcess.php');
            
$form->addHiddenValue('address', $_SESSION[$guid]['address']);
$form->addHiddenValue('key', $gibbonApplicationFormHash);
$form->addHiddenValue('gibbonApplicationFormID', $application['gibbonApplicationFormID']);

$form->addRow()->addHeading(__('Application Fee'));

$row = $form->addRow()->addContent(sprintf(__('Payment can be made by credit card, using our secure PayPal payment gateway. When you press Pay Online Now, you will be directed to PayPal in order to make payment. During this process we do not see or store your credit card details. Once the transaction is complete you will be returned to %1$s.'), $_SESSION[$guid]['systemName']))->wrap('<p class="my-2">', '</p>');

$row = $form->addRow();
    $row->addLabel('gibbonApplicationFormIDLabel', __('Application ID'));
    $row->addTextField('gibbonApplicationFormID')->readOnly()->setValue($application['gibbonApplicationFormID']);

$row = $form->addRow();
    $row->addLabel('applicationProcessFeeLabel', __('Application Processing Fee'));
    $row->addTextField('applicationProcessFee')->readOnly()->setValue($currency.$applicationProcessFee);

$row = $form->addRow();
    $row->addSubmit(__('Pay Online Now'));

echo $form->getOutput();
