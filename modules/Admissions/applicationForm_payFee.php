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

use Gibbon\Http\Url;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Forms\Builder\FormPayment;
use Gibbon\Domain\Forms\FormGateway;
use Gibbon\Domain\Admissions\AdmissionsAccountGateway;
use Gibbon\Domain\Admissions\AdmissionsApplicationGateway;

$gibbonFormID = $_GET['form'] ?? $_GET['gibbonFormID'] ?? '';
$identifier = $_GET['id'] ?? $_GET['identifier'] ?? null;
$accessID = $_GET['acc'] ?? $_GET['accessID'] ?? '';
$accessToken = $_GET['tok'] ?? $session->get('admissionsAccessToken') ?? '';

$proceed = false;
$public = false;

if (!$session->has('gibbonPersonID')) {
    $public = true;
    if (!empty($accessID) && !empty($accessToken)) {
        $proceed = true;
    }
} else if (isActionAccessible($guid, $connection2, '/modules/Admissions/applicationFormView.php') != false) {
    $proceed = true;
}

if (!$proceed) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('Application Fee'));

    if (empty($accessID) || empty($identifier)) {
        echo Format::alert(__('You have not specified one or more required parameters.'));
        return;
    }

    $admissionsAccountGateway = $container->get(AdmissionsAccountGateway::class);
    $admissionsApplicationGateway = $container->get(AdmissionsApplicationGateway::class);

    $account = $public
        ? $admissionsAccountGateway->getAccountByAccessToken($accessID, $accessToken)
        : $admissionsAccountGateway->getAccountByPerson($session->get('gibbonPersonID'));

    if ($public && empty($account)) {
        $page->addError(__('The application link does not match an existing record in our system. The record may have been removed or the link is no longer valid.'));
        $session->forget('admissionsAccessToken');
        return;
    }

    $application = $container->get(AdmissionsApplicationGateway::class)->getApplicationByIdentifier($gibbonFormID, $identifier, 'gibbonAdmissionsAccount', $account['gibbonAdmissionsAccountID'] ?? 0);

    $formPayment = $container->get(FormPayment::class);
    $formPayment->setForeignTable('gibbonApplicationForm', $gibbonApplicationFormID);

    $form = $container->get(FormGateway::class)->getByID($gibbonFormID);
    $formConfig = json_decode($form['config'] ?? '', true);

    if (empty($form) || empty($account) || empty($application)) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    if (!$formPayment->isEnabled() || empty($formConfig['formProcessingFee'])) {
        $page->addError(__('Online payment options are not available at this time.'));
        return;
    }

    if (!empty($application['gibbonPaymentIDProcess'])) {
        $page->addError(__('A payment has already been made for this application form.'), 'success');
        return;
    }

    $page->return->addReturns($formPayment->getReturnMessages() + ['error8' => __('A payment has already been made for this application form.')]);


    $form = Form::create('action', $session->get('absoluteURL').'/modules/Students/applicationForm_payFeeProcess.php');
                
    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('accessID', $accessID);
    $form->addHiddenValue('gibbonFormID', $gibbonFormID);
    $form->addHiddenValue('identifier', $identifier);
    $form->addHiddenValue('feeType', 'formProcessingFee');
    $form->addHiddenValue('feeAmount', $formConfig['formProcessingFee']);


    $form->addRow()->addHeading('Application Fee', __('Application Fee'));

    $row = $form->addRow()->addContent($formPayment->getProcessingFeeInfo())->wrap('<p class="my-2">', '</p>');

    $row = $form->addRow();
        $row->addLabel('gibbonApplicationFormIDLabel', __('Application ID'));
        $row->addTextField('gibbonApplicationFormID')->readOnly()->setValue($application['gibbonAdmissionsApplicationID']);

    $row = $form->addRow();
        $row->addLabel('applicationProcessFeeLabel', __('Application Processing Fee'));
        $row->addTextField('applicationProcessFee')->readOnly()->setValue($session->get('currency').$formConfig['formProcessingFee']);

    $row = $form->addRow();
        $row->addSubmit(__('Pay Online Now'));

    echo $form->getOutput();
}
