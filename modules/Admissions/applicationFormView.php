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
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Forms\Form;
use Gibbon\Domain\Forms\FormGateway;
use Gibbon\Domain\Admissions\AdmissionsAccountGateway;
use Gibbon\Domain\Admissions\AdmissionsApplicationGateway;

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
    $page->breadcrumbs->add(__('My Application Forms'));

    $admissionsAccountGateway = $container->get(AdmissionsAccountGateway::class);
    $admissionsApplicationGateway = $container->get(AdmissionsApplicationGateway::class);

    $account = $public
        ? $admissionsAccountGateway->getAccountByAccessToken($accessID, $accessToken)
        : $admissionsAccountGateway->getAccountByPerson($session->get('gibbonPersonID'));

    if ($public && empty($account)) {
        $page->addError(__('The application link does not match an existing record in our system. The record may have been removed or the link is no longer valid.').' '.__('Please visit the {application} page to request a new link.', ['application' => Format::link(Url::fromModuleRoute('Admissions', 'applicationFormSelect')->withAbsoluteUrl(), __('Admissions Welcome'))]));
        $session->forget('admissionsAccessToken');
        return;
    }

    if (!empty($account)) {
        $session->set('admissionsAccessToken', $accessToken);
        $admissionsAccountGateway->update($account['gibbonAdmissionsAccountID'], ['timestampActive' => date('Y-m-d H:i:s')]);

        $foreignTable = 'gibbonAdmissionsAccount';
        $foreignTableID = $account['gibbonAdmissionsAccountID'];
    } else {
        $foreignTable = 'gibbonPerson';
        $foreignTableID = $session->get('gibbonPersonID');
    }
    
    if ($public && !empty($account['timestampTokenExpire'])) {
        echo Format::alert(__('Welcome back! You are accessing this page through a unique link sent to your email address {email}. Please keep this link secret to protect your personal details. This link will expire {expiry}.', ['email' => '<u>'.$account['email'].'</u>', 'expiry' => Format::relativeTime($account['timestampTokenExpire'])]), 'message');
    }    

    $criteria = $admissionsApplicationGateway->newQueryCriteria(true)
        ->sortBy('timestampCreated', 'ASC');

    $submissions = $admissionsApplicationGateway->queryApplicationsByContext($criteria, $foreignTable, $foreignTableID);

    // DATA TABLE
    $table = DataTable::create('submissions');
    $table->setTitle(__('Current Applications'));

    $table->addColumn('formName', __('Application Form'));
    $table->addColumn('status', __('Status'));
    $table->addColumn('timestampCreated', __('Date'))->format(Format::using('date', 'timestampCreated'));

    $table->modifyRows(function ($values, $row) {
        if ($values['status'] == 'Incomplete') $row->addClass('warning');
        if ($values['status'] == 'Accepted') $row->addClass('success');
        return $row;
    });

    $table->addActionColumn()
        ->addParam('accessID', $accessID)
        ->addParam('gibbonFormID')
        ->addParam('identifier')
        ->format(function ($values, $actions) {
            if ($values['status'] == 'Incomplete') {
                $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Admissions/applicationForm.php');
            } else {
                $actions->addAction('view', __('View'))
                    ->setURL('/modules/Admissions/applicationForm.php');
            }
        });

    echo $table->render($submissions);

    // QUERY
    $formGateway = $container->get(FormGateway::class);
    $criteria = $formGateway->newQueryCriteria(true)
        ->sortBy('name', 'ASC')
        ->filterBy('type', 'Application')
        ->filterBy('active', 'Y')
        ->filterBy('public', 'Y');

    $forms = $formGateway->queryForms($criteria);

    if (count($forms) == 0) {
        return;
    } 

    // FORM
    $form = Form::create('admissionsAccount', $session->get('absoluteURL').'/index.php?q=/modules/Admissions/applicationForm.php');

    $form->setTitle(__('New Application Form'));
    $form->setDescription(__('You may continue submitting applications with the form below and they will be linked to your account data.').' '.__('Some information has been pre-filled for you, feel free to change this information as needed.'));

    $form->setClass('w-full blank');
    
    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('accessID', $accessID);
    
    // Display all available public forms
    foreach ($forms as $index => $applicationForm) {
        $table = $form->addRow()->addTable()->setClass('w-full noIntBorder border rounded my-2 bg-blue-100 mb-2');

        $row = $table->addRow();
            $row->addLabel('gibbonFormID'.$index, __($applicationForm['name']))->description($applicationForm['description'])->setClass('block w-full p-6 font-medium text-sm text-gray-700');
            $row->addRadio('gibbonFormID')->setID('gibbonFormID'.$index)->fromArray([$applicationForm['gibbonFormID'] => ''])->required()->addClass('mr-6')->checked(false);
    }

    $form->addRow()->addSubmit(__('Next'));

    echo $form->getOutput();
}
