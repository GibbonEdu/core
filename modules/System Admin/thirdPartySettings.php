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

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/thirdPartySettings.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Third Party Settings'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $form = Form::create('thirdPartySettings', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/thirdPartySettingsProcess.php');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    // GOOGLE
    $form->addRow()->addHeading(__('Google Integration'))->append(sprintf(__('If your school uses Google Apps, you can enable single sign on and calendar integration with Gibbon. This process makes use of Google\'s APIs, and allows a user to access Gibbon without a username and password, provided that their listed email address is a Google account to which they have access. For configuration instructions, %1$sclick here%2$s.'), "<a href='https://gibbonedu.org/support/administrators/installing-gibbon/authenticating-with-google-oauth/' target='_blank'>", '</a>'));

    $setting = getSettingByScope($connection2, 'System', 'googleOAuth', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $form->toggleVisibilityByClass('googleSettings')->onSelect($setting['name'])->when('Y');

    $setting = getSettingByScope($connection2, 'System', 'googleClientName', true);
    $row = $form->addRow()->addClass('googleSettings');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'System', 'googleClientID', true);
    $row = $form->addRow()->addClass('googleSettings');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'System', 'googleClientSecret', true);
    $row = $form->addRow()->addClass('googleSettings');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'System', 'googleRedirectUri', true);
    $row = $form->addRow()->addClass('googleSettings');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'System', 'googleDeveloperKey', true);
    $row = $form->addRow()->addClass('googleSettings');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'System', 'calendarFeed', true);
    $row = $form->addRow()->addClass('googleSettings');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value']);

    // PAYPAL
    $form->addRow()->addHeading(__('PayPal Payment Gateway'));

    $setting = getSettingByScope($connection2, 'System', 'enablePayments', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $form->toggleVisibilityByClass('paypalSettings')->onSelect($setting['name'])->when('Y');

    $setting = getSettingByScope($connection2, 'System', 'paypalAPIUsername', true);
    $row = $form->addRow()->addClass('paypalSettings');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'System', 'paypalAPIPassword', true);
    $row = $form->addRow()->addClass('paypalSettings');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'System', 'paypalAPISignature', true);
    $row = $form->addRow()->addClass('paypalSettings');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value']);

    // SMS
    $form->addRow()->addHeading(__('SMS Settings'))->append(__('Gibbon can use a number of different gateways to send out SMS messages. These are paid services, not affiliated with Gibbon, and you must create your own account with them before being able to send out SMSs using the Messenger module.'));

    // SMS Gateway Options - these are not translated, as they represent company names
    $smsGateways = ['OneWaySMS', 'Twilio', 'Nexmo', 'Clockwork', 'TextLocal', 'Mail to SMS'];
    $setting = getSettingByScope($connection2, 'Messenger', 'smsGateway', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelect($setting['name'])
            ->fromArray(['' => __('No')])
            ->fromArray($smsGateways)
            ->selected($setting['value']);

    $form->toggleVisibilityByClass('smsSettings')->onSelect($setting['name'])->whenNot('');
    $form->toggleVisibilityByClass('smsSettingsOneWay')->onSelect($setting['name'])->when('OneWaySMS');
    $form->toggleVisibilityByClass('smsAPIKey')->onSelect($setting['name'])->when(['Twilio', 'Nexmo', 'Clockwork', 'TextLocal']);
    $form->toggleVisibilityByClass('smsAPIToken')->onSelect($setting['name'])->when(['Twilio', 'Nexmo']);
    $form->toggleVisibilityByClass('smsDomain')->onSelect($setting['name'])->when('Mail to SMS');
    
    $setting = getSettingByScope($connection2, 'Messenger', 'smsSenderID', true);
    $row = $form->addRow()->addClass('smsSettings');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value'])->maxLength(50);

    // SMS Username variations - these aim to simplify the setup by using the matching terminology for each gateway.
    $setting = getSettingByScope($connection2, 'Messenger', 'smsUsername', true);
    $row = $form->addRow()->addClass('smsSettingsOneWay');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value'])->maxLength(50);

    $row = $form->addRow()->addClass('smsAPIKey');
        $row->addLabel($setting['name'], __('API Key'));
        $row->addTextField($setting['name'])->setValue($setting['value'])->maxLength(50);

    $row = $form->addRow()->addClass('smsDomain');
        $row->addLabel($setting['name'], __('SMS Domain'));
        $row->addTextField($setting['name'])->setValue($setting['value'])->maxLength(50);

    // SMS Password variations
    $setting = getSettingByScope($connection2, 'Messenger', 'smsPassword', true);
    $row = $form->addRow()->addClass('smsSettingsOneWay');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value'])->maxLength(50);

    $row = $form->addRow()->addClass('smsAPIToken');
        $row->addLabel($setting['name'], __('API Secret/Auth Token'));
        $row->addTextField($setting['name'])->setValue($setting['value'])->maxLength(50);

    // SMS Endpoint URLs - currently used by OneWaySMS
    $setting = getSettingByScope($connection2, 'Messenger', 'smsURL', true);
    $row = $form->addRow()->addClass('smsSettingsOneWay');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'Messenger', 'smsURLCredit', true);
    $row = $form->addRow()->addClass('smsSettingsOneWay');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value']);

    // SMTP MAIL
    $form->addRow()->addHeading(__('SMTP Mail'));

    $setting = getSettingByScope($connection2, 'System', 'enableMailerSMTP', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $form->toggleVisibilityByClass('smtpSettings')->onSelect($setting['name'])->when('Y');

    $setting = getSettingByScope($connection2, 'System', 'mailerSMTPHost', true);
    $row = $form->addRow()->addClass('smtpSettings');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'System', 'mailerSMTPPort', true);
    $row = $form->addRow()->addClass('smtpSettings');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value']);

    $encryptionOptions = [
        'auto' => __('Automatic'),
        'tls'  => 'TLS',
        'ssl'  => 'SSL',
        'none' => __('None'),
    ];
    $setting = getSettingByScope($connection2, 'System', 'mailerSMTPSecure', true);
    $row = $form->addRow()->addClass('smtpSettings');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelect($setting['name'])->fromArray($encryptionOptions)->selected($setting['value']);

    $setting = getSettingByScope($connection2, 'System', 'mailerSMTPUsername', true);
    $row = $form->addRow()->addClass('smtpSettings');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'System', 'mailerSMTPPassword', true);
    $row = $form->addRow()->addClass('smtpSettings');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addPassword($setting['name'])->setValue($setting['value']);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
