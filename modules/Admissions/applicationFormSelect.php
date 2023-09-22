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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\Forms\FormGateway;
use Gibbon\Services\Format;
use Gibbon\Http\Url;
use Gibbon\Forms\Form;

$proceed = false;
$public = false;

$settingGateway = $container->get(SettingGateway::class);

if (!$session->has('username')) {
    $public = true;
    $session->forget('admissionsAccessToken');
    
    //Get public access
    $publicApplications = $settingGateway->getSettingByScope('Application Form', 'publicApplications');
    if ($publicApplications == 'Y') {
        $proceed = true;
    }
} else if (isActionAccessible($guid, $connection2, '/modules/Admissions/applicationForm.php') != false) {
    $proceed = true;
}

$gibbonPersonID = $session->get('gibbonPersonID', null);

if ($proceed == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('Admissions Welcome'));

    $page->return->addReturns([
        'success1' => __('Success. Please check your email for a link to access your admissions account.'),
        'error4' => __('An existing application was not found for this email address.'),
        'error5' => __('Email failed to send to {email}', ['email' => $_GET['email'] ?? '']),
    ]);

    if (!$session->has('username')) {
        echo Format::alert(__('If you already have an account for {organisation} {systemName}, please log in now to prevent creation of duplicate data about you! Once logged in, you can find the form under {linkName} in the main menu.', ['organisation' => $session->get('organisationNameShort'), 'systemName' => $session->get('systemName'), 'linkName' => __('People').' > '.__('Admissions') 
        ]).' '.sprintf(__('If you do not have an account for %1$s %2$s, please use the form below.'), $session->get('organisationNameShort'), $session->get('systemName')), 'message');
    }

    $welcomeHeading = $settingGateway->getSettingByScope('Admissions', 'welcomeHeading');
    $welcomeText = $settingGateway->getSettingByScope('Admissions', 'welcomeText');

    // QUERY
    $formGateway = $container->get(FormGateway::class);
    $criteria = $formGateway->newQueryCriteria(true)
        ->sortBy('name', 'ASC')
        ->filterBy('type', 'Application')
        ->filterBy('active', 'Y')
        ->filterBy('public', 'Y');

    $forms = $formGateway->queryForms($criteria);

    if (count($forms) == 0) {
        echo Format::alert(__('There are no application forms available at this time.'), 'warning');
        return;
    } 
    
    // FORM
    $form = Form::create('admissionsAccount', $session->get('absoluteURL').'/modules/Admissions/applicationFormSelectProcess.php');
    $form->setClass('w-full blank');
    $form->setTitle(__($welcomeHeading, ['organisationNameShort' => $session->get('organisationNameShort')]));
    $form->setDescription(__($welcomeText));
    
    $form->addHiddenValue('address', $session->get('address'));
    
    // Display all available public forms
    foreach ($forms as $index => $applicationForm) {
        $table = $form->addRow()->addTable()->setClass('w-full noIntBorder border rounded my-2 bg-blue-100 mb-2');

        $row = $table->addRow();
            $row->addLabel('gibbonFormID'.$index, __($applicationForm['name']))->description($applicationForm['description'])->setClass('block w-full p-6 font-medium text-sm text-gray-700');
            $row->addRadio('gibbonFormID')->setID('gibbonFormID'.$index)->fromArray([$applicationForm['gibbonFormID'] => ''])->addClass('mr-6')->checked($index == 0 ? $applicationForm['gibbonFormID'] : false);
    }

    $table = $form->addRow()->addTable()->setClass('w-full noIntBorder border rounded my-2 bg-blue-100 mb-2');

    $row = $table->addRow();
        $row->addLabel('gibbonFormID'.count($forms), __('Continue an Existing Application Form'))->description(__('If you already have an application form in progress or would like to check the status of an application form, select this option. You will receive an email with a link to access your existing forms.'))->setClass('block w-full p-6 font-medium text-sm text-gray-700');
        $row->addRadio('gibbonFormID')->setID('gibbonFormID'.count($forms))->fromArray(['existing' => ''])->addClass('mr-6')->checked(false);

    $table = $form->addRow()->addTable()->setClass('smallIntBorder w-full my-4 p-6');

    $row = $table->addRow();
        $row->addLabel('admissionsLoginEmail', __('Email Address'));
        $row->addEmail('admissionsLoginEmail')->required()->addClass('flex w-full max-w-sm float-right');

    $form->addRow()->addSubmit(__('Next'));

    echo $form->getOutput();
}
