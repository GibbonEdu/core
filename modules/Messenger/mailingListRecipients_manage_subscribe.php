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

use Gibbon\Forms\Form;
use Gibbon\Domain\Messenger\MailingListRecipientGateway;

$return = $_GET['return'] ?? '';

$mode = $_GET['mode'] ?? 'subscribe';
$mode = ($mode == 'subscribe' || $mode == 'unsubscribe' || $mode == 'manage') ? $mode : 'subscribe';

$page->breadcrumbs->add(__('Mailing List Subscription'));

// Create form for later use
$form = Form::create('mailingList', $session->get('absoluteURL').'/modules/'.$session->get('module')."/mailingListRecipients_manage_subscribeProcess.php");
                    
$form->addHiddenValue('address', $session->get('address'));
$form->addHiddenValue('mode', $mode);

$row = $form->addRow();
    $row->addLabel('surname', __('Surname'));
    $row->addTextField('surname')->required()->maxLength(60);

$row = $form->addRow();
    $row->addLabel('preferredName', __('Preferred Name'));
    $row->addTextField('preferredName')->required()->maxLength(60);

if ($mode == 'subscribe') {
    $row = $form->addRow();
        $row->addLabel('email', __('Email'))->description(__('Must be unique.'));
        $row->addEmail('email')->required()->maxLength(75);
} else {
    $row = $form->addRow();
        $row->addLabel('email', __('Email'));
        $row->addEmail('email')->required()->maxLength(75)->readOnly();
}

$row = $form->addRow();
    $row->addLabel('organisation', __('Organisation'));
    $row->addTextField('organisation')->maxLength(60);

$sql = "SELECT gibbonMessengerMailingListID as value, name FROM gibbonMessengerMailingList WHERE active='Y' ORDER BY name";
$lists = $pdo->select($sql)->fetchKeyPair();
if (count($lists) > 0) {
    $row = $form->addRow();
        $row->addLabel('gibbonMessengerMailingListIDList', __('Mailing Lists'));
        $row->addCheckbox('gibbonMessengerMailingListIDList')->fromArray($lists);
}

$row = $form->addRow();
    $row->addFooter();
    $row->addSubmit();


// Deal with each possible mode
if ($mode == 'subscribe') {
    if ($return != 'success0') {
        $page->addAlert(__('Please use the form below to subscribe to our mailing lists as per your interests.'), 'message');

        echo $form->getOutput();
    }
} else if ($mode == 'manage' || $mode == 'unsubscribe') {
    // Get and check email and key
    $email = $_GET['email'] ?? '';
    $key = $_GET['key'] ?? '';

    $mailingListRecipientGateway = $container->get(MailingListRecipientGateway::class);
    $keyCheck = $mailingListRecipientGateway->keyCheck($email, $key);

    // Populate and display form according to email and key check
    if ($keyCheck->rowCount() != 1) {
        $page->addError(__('The specified record cannot be found.'));
    } else {
        if ($mode == 'manage') {
            $page->addAlert(__('Please use the form below to manage your subscription preferences.'), 'message');

            $form->addHiddenValue('email', $email);
            $form->addHiddenValue('key', $key);

            $values = $keyCheck->fetchAll()[0];
            $values['gibbonMessengerMailingListIDList'] = explode(',', $values['gibbonMessengerMailingListIDList'] ?? ''); 

            $form->loadAllValuesFrom($values);

            echo $form->getOutput();
        } else {
            $page->addAlert(__('Please {linkOpen}click here{linkClose} if you wish to re-subscribe to our mailing lists.', ['linkOpen' => '<a href="'.$session->get('absoluteURL').'/index.php?q=/modules/Messenger/mailingListRecipients_manage_subscribe.php&mode=manage&email='.$email.'&key='.$key.'">', 'linkClose' => '</a>']), 'message');
        }
    }
}


