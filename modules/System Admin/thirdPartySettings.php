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
use Gibbon\Contracts\Services\Payment;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/thirdPartySettings.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Third Party Settings'));

    // Add return messages for test payments
    $payment = $container->get(Payment::class);
    $page->return->addReturns($payment->getReturnMessages());

    // Add return messages for test emails
    $emailError = $session->get('testEmailError');
    $emailRecipient = $session->get('testEmailRecipient');
    $page->return->addReturns(['error10' => sprintf(__('An error (%1$s) occurred sending an email to %2$s.'), $emailError, $emailRecipient)]);

    // FORM
    $form = Form::create('thirdPartySettings', $session->get('absoluteURL').'/modules/'.$session->get('module').'/thirdPartySettingsProcess.php');
    $form->addHiddenValue('address', $session->get('address'));

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

    // PAYMENTS
    $form->addRow()->addHeading(__('Payment Gateway'))->append(__('Gibbon can handle payments using a payment gateway API. These are external services, not affiliated with Gibbon, and you must create your own account with them before being able to accept payments. Gibbon does not store or process any credit card details.'));

    $setting = getSettingByScope($connection2, 'System', 'enablePayments', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $form->toggleVisibilityByClass('paymentGateway')->onSelect($setting['name'])->when('Y');

    $paymentGateways = [
        'PayPal' => __('PayPal'),
        'Stripe' => __('Stripe'),
    ];
    $setting = getSettingByScope($connection2, 'System', 'paymentGateway', true);
    $row = $form->addRow()->addClass('paymentGateway');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelect($setting['name'])
            ->fromArray($paymentGateways)
            ->selected($setting['value'])
            ->placeholder()
            ->required();

    $form->toggleVisibilityByClass('paypalSettings')->onSelect($setting['name'])->when('PayPal');
    $form->toggleVisibilityByClass('stripeSettings')->onSelect($setting['name'])->when('Stripe');
    $form->toggleVisibilityByClass('paymentTest')->onSelect($setting['name'])->whenNot('Please select...');

    $setting = getSettingByScope($connection2, 'System', 'paymentAPIUsername', true);
    $row = $form->addRow()->addClass('paypalSettings');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value'])->required();

    $setting = getSettingByScope($connection2, 'System', 'paymentAPIPassword', true);
    $row = $form->addRow()->addClass('paypalSettings');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value'])->required();

    $setting = getSettingByScope($connection2, 'System', 'paymentAPISignature', true);
    $row = $form->addRow()->addClass('paypalSettings');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value'])->required();

    $setting = getSettingByScope($connection2, 'System', 'paymentAPIKey', true);
    $row = $form->addRow()->addClass('stripeSettings');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value'])->required();

    // Test Payment
    if ($session->get('enablePayments') == 'Y') {
        $row = $form->addRow()->addClass('paymentTest');
            $row->addLabel('paymentTest', __('Test Payment'))->description(__('You can use this tool to make a small payment in {currency} to test your gateway configuration.', ['currency' => $session->get('currency')]));
            $col = $row->addColumn();
            $col->addCurrency('paymentTest')->setValue(10)->addClass('w-full');
            $col->addButton(__('Go'), 'testPayment()')->addClass('-ml-px w-24');
    }

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

    // Test Email
    if ($session->get('enableMailerSMTP') == 'Y') {
        $row = $form->addRow()->addClass('emailTest');
            $row->addLabel('emailTest', __('Test Email'))->description(__('You can use this tool to send an email to test your SMTP configuration.'));
            $col = $row->addColumn();
            $col->addEmail('emailTest')->setValue($session->get('email'))->addClass('w-full');
            $col->addButton(__('Go'), 'testEmail()')->addClass('-ml-px w-24');
    }

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}

?>

<script>
    function testPayment() {
        var amount = $('#paymentTest').val();
        location.href = "<?php echo $session->get('absoluteURL'); ?>/modules/System Admin/thirdPartySettings_paymentProcess.php?amount="+amount;
    }

    function testEmail() {
        var email = $('#emailTest').val();
        location.href = "<?php echo $session->get('absoluteURL'); ?>/modules/System Admin/thirdPartySettings_emailProcess.php?email="+email;
    }
</script>
