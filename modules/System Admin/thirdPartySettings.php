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
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Contracts\Services\Payment;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\System\SettingGateway;

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
    $smsError = $session->get('testSMSErrors', []);
    $smsRecipient = $session->get('testSMSRecipient');

    $page->return->addReturns([
        'error10' => sprintf(__('An error (%1$s) occurred sending an email to %2$s.'), $emailError, $emailRecipient),
        'error11' => sprintf(__('An error occurred sending an SMS to %2$s: %1$s'), implode('<br/>', $smsError), $smsRecipient),
    ]);

    // FORM
    $form = Form::create('thirdPartySettings', $session->get('absoluteURL').'/modules/'.$session->get('module').'/thirdPartySettingsProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->addHiddenValue('address', $session->get('address'));

    // SINGLE SIGN-ON
    $form->addRow()->addHeading('Single Sign-On Integration', __('Single Sign-On Integration'))->append(__('If your school uses a service that offers OAuth2 authorization, you can enable single sign on integration with Gibbon. This process makes use of industry-standard OAuth2 protocols, and allows a user to access Gibbon without a username and password, provided that their listed email address is part of the chosen service and is unique in Gibbon.'));

    $settingGateway = $container->get(SettingGateway::class);
    $ssoGoogle = json_decode($settingGateway->getSettingByScope('System Admin', 'ssoGoogle'), true);
    $ssoMicrosoft = json_decode($settingGateway->getSettingByScope('System Admin', 'ssoMicrosoft'), true);
    $ssoOther = json_decode($settingGateway->getSettingByScope('System Admin', 'ssoOther'), true);

    $ssoList = [
        [
            'sso' => 'Google',
            'name' => __('Google'),
            'service' => __('Google Cloud Platform'),
            'url' => 'https://console.cloud.google.com',
            'enabled' => $ssoGoogle['enabled'] ?? 'N',
        ],
        [
            'sso' => 'Microsoft',
            'name' => __('Microsoft'),
            'service' => __('Microsoft Azure Portal'),
            'url' => 'https://portal.azure.com',
            'enabled' => $ssoMicrosoft['enabled'] ?? 'N',
        ],
        [
            'sso' => 'Other',
            'name' => !empty($ssoOther['clientName']) ? $ssoOther['clientName'] : __('Other'),
            'service' => __('Generic OAuth2 Provider'),
            'url' => '',
            'enabled' => $ssoOther['enabled'] ?? 'N',
        ],
    ];

    $table = DataTable::create('ssoList');

    $table->modifyRows(function ($values, $row) {
        if ($values['enabled'] == 'Y') $row->addClass('success');
        return $row;
    });

    $table->addColumn('name', __('Name'))
        ->format(function ($values) use ($session) {
            $output = $values['name'];
            $imagePath = '/themes/'.$session->get('gibbonThemeName').'/img/'.strtolower($values['sso']).'-login.svg';
            if (file_exists($session->get('absolutePath').$imagePath)) {
                $output = '<img src="'.$session->get('absoluteURL').$imagePath.'" class="w-6 h-6 -mt-1 mr-2 inline-block align-middle">' . $values['name'];
            }
            return $output;
        });
    $table->addColumn('service', __('Service'))
        ->format(function ($values) {
            return Format::link($values['url'], $values['service']);
        });
    $table->addColumn('enabled', __('Enabled'))
        ->format(Format::using('yesNo', 'enabled'));

    $table->addActionColumn()
        ->addParam('sso')
        ->format(function ($values, $actions) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/System Admin/thirdPartySettings_ssoEdit.php');
        });

    $form->addRow()->addContent($table->render($ssoList));

    // PAYMENTS
    $form->addRow()->addHeading('Payment Gateway', __('Payment Gateway'))->append(__('Gibbon can handle payments using a payment gateway API. These are external services, not affiliated with Gibbon, and you must create your own account with them before being able to accept payments. Gibbon does not store or process any credit card details.'));

    $setting = $settingGateway->getSettingByScope('System', 'enablePayments', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $form->toggleVisibilityByClass('paymentGateway')->onSelect($setting['name'])->when('Y');

    $paymentGateways = [
        'PayPal' => __('PayPal'),
        'Stripe' => __('Stripe'),
    ];
    $setting = $settingGateway->getSettingByScope('System', 'paymentGateway', true);
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

    $setting = $settingGateway->getSettingByScope('System', 'paymentAPIUsername', true);
    $row = $form->addRow()->addClass('paypalSettings');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value'])->required();

    $setting = $settingGateway->getSettingByScope('System', 'paymentAPIPassword', true);
    $row = $form->addRow()->addClass('paypalSettings');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value'])->required();

    $setting = $settingGateway->getSettingByScope('System', 'paymentAPISignature', true);
    $row = $form->addRow()->addClass('paypalSettings');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value'])->required();

    $setting = $settingGateway->getSettingByScope('System', 'paymentAPIKey', true);
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
    $form->addRow()->addHeading('SMS Settings', __('SMS Settings'))->append(__('Gibbon can use a number of different gateways to send out SMS messages. These are paid services, not affiliated with Gibbon, and you must create your own account with them before being able to send out SMSs using the Messenger module.'));

    // SMS Gateway Options - these are not translated, as they represent company names
    $smsGateways = ['OneWaySMS', 'Twilio', 'Nexmo', 'Clockwork', 'TextLocal', 'Mail to SMS'];
    $setting = $settingGateway->getSettingByScope('Messenger', 'smsGateway', true);
    $smsGatewaySetting = $setting['value'];
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
    
    $setting = $settingGateway->getSettingByScope('Messenger', 'smsSenderID', true);
    $row = $form->addRow()->addClass('smsSettings');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value'])->maxLength(50);

    // SMS Username variations - these aim to simplify the setup by using the matching terminology for each gateway.
    $setting = $settingGateway->getSettingByScope('Messenger', 'smsUsername', true);
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
    $setting = $settingGateway->getSettingByScope('Messenger', 'smsPassword', true);
    $row = $form->addRow()->addClass('smsSettingsOneWay');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value'])->maxLength(50);

    $row = $form->addRow()->addClass('smsAPIToken');
        $row->addLabel($setting['name'], __('API Secret/Auth Token'));
        $row->addTextField($setting['name'])->setValue($setting['value'])->maxLength(50);

    // SMS Endpoint URLs - currently used by OneWaySMS
    $setting = $settingGateway->getSettingByScope('Messenger', 'smsURL', true);
    $row = $form->addRow()->addClass('smsSettingsOneWay');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value']);

    $setting = $settingGateway->getSettingByScope('Messenger', 'smsURLCredit', true);
    $row = $form->addRow()->addClass('smsSettingsOneWay');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value']);

    // Test SMS
    if (!empty($smsGatewaySetting)) {
        $row = $form->addRow()->addClass('smsTest');
            $row->addLabel('smsTest', __('Test SMS'))->description(__('You can use this tool to send an sms to test your SMS Gateway configuration.'));
            $col = $row->addColumn();
            $col->addPhoneNumber('smsTest')->setValue($session->get('sms'))->addClass('w-full');
            $col->addButton(__('Go'), 'testSMS()')->addClass('-ml-px w-24');
    }

    // SMTP MAIL
    $form->addRow()->addHeading('SMTP Mail', __('SMTP Mail'));

    $setting = $settingGateway->getSettingByScope('System', 'enableMailerSMTP', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $form->toggleVisibilityByClass('smtpSettings')->onSelect($setting['name'])->when('Y');

    $setting = $settingGateway->getSettingByScope('System', 'mailerSMTPHost', true);
    $row = $form->addRow()->addClass('smtpSettings');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value']);

    $setting = $settingGateway->getSettingByScope('System', 'mailerSMTPPort', true);
    $row = $form->addRow()->addClass('smtpSettings');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value']);

    $encryptionOptions = [
        'auto' => __('Automatic'),
        'tls'  => 'TLS',
        'ssl'  => 'SSL',
        'none' => __('None'),
    ];
    $setting = $settingGateway->getSettingByScope('System', 'mailerSMTPSecure', true);
    $row = $form->addRow()->addClass('smtpSettings');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelect($setting['name'])->fromArray($encryptionOptions)->selected($setting['value']);

    $setting = $settingGateway->getSettingByScope('System', 'mailerSMTPUsername', true);
    $row = $form->addRow()->addClass('smtpSettings');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextField($setting['name'])->setValue($setting['value']);

    $setting = $settingGateway->getSettingByScope('System', 'mailerSMTPPassword', true);
    $row = $form->addRow()->addClass('smtpSettings');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addPassword($setting['name'])->setValue($setting['value']);

    // Test Email
    
    $row = $form->addRow()->addClass('emailTest');
        $row->addLabel('emailTest', __('Test Email'))->description(__('You can use this tool to send an email to test your SMTP configuration.'));
        $col = $row->addColumn();
        $col->addEmail('emailTest')->setValue($session->get('email'))->addClass('w-full');
        $col->addButton(__('Go'), 'testEmail()')->addClass('-ml-px w-24');
    

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
        var email = encodeURIComponent($('#emailTest').val());
        location.href = "<?php echo $session->get('absoluteURL'); ?>/modules/System Admin/thirdPartySettings_emailProcess.php?email="+email;
    }

    function testSMS() {
        var phoneNumber = $('#smsTestCountryCode').val() + $('#smsTest').val();
        location.href = "<?php echo $session->get('absoluteURL'); ?>/modules/System Admin/thirdPartySettings_smsProcess.php?phoneNumber="+phoneNumber;
    }
</script>
