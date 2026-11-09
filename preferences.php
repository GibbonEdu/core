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

use Gibbon\Data\PasswordPolicy;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\User\RoleGateway;
use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Module\Messenger\Signature;
use Gibbon\Services\Format;
use Gibbon\Auth\Passkey\PasskeyService;
use Gibbon\Session\TokenHandler;

if (!$session->exists("username")) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $page->breadcrumbs->add(__('Preferences'));

    $return = $_GET['return'] ?? null;

    //Deal with force reset notification
    $forceReset = $_GET['forceReset'] ?? null;
    if ($forceReset == 'Y' AND $return != 'successa') {
        $page->addMessage(__('Your account has been flagged for a password reset. You cannot continue into the system until you change your password.'));
    }

    $returns = array();
    $returns['successa'] = __('Your account has been successfully updated. You can now continue to use the system as per normal.');
    $returns['errora'] = sprintf(__('Your account status could not be updated, and so you cannot continue to use the system. Please contact %1$s if you have any questions.'), "<a href='mailto:".$session->get('organisationAdministratorEmail')."'>".$session->get('organisationAdministratorName').'</a>');
    $returns['error4'] = __('Your request failed due to non-matching passwords.');
    $returns['error3'] = __('Your request failed due to incorrect current password.');
    $returns['error6'] = __('Your request failed because your password does not meet the minimum requirements for strength.');
    $returns['error7'] = __('Your request failed because your new password is the same as your current password.');
    $returns['error8'] = __('Your request failed because your MFA code was not valid, this may have occured if your code timed-out before the form was finished processing.');
    $page->return->addReturns($returns);

    $data = array('gibbonPersonID' => $session->get('gibbonPersonID'));
    $sql = 'SELECT * FROM gibbonPerson WHERE gibbonPersonID=:gibbonPersonID';
    $result = $connection2->prepare($sql);
    $result->execute($data);
    if ($result->rowCount() == 1) {
        $values = $result->fetch();
    }
    // $tfa = new RobThree\Auth\TwoFactorAuth('Gibbon'); //TODO: change the name to be based on the actual value of the school's gibbon name or similar...

    // //Check if there is an existing MFA Secret, so that we don't create a new one accidentally, and to have the correct values load below...
    // if (!empty($values['mfaSecret'])) {
    //     $secret = $values['mfaSecret'];
    //     $secretcheck = !empty($secret) ? 'Y' : 'N';
    // } else {
    //     $secret = $tfa->createSecret();
    //     $secretcheck = 'N';
    // }

    $form = Form::create('resetPassword', $session->get('absoluteURL').'/preferencesPasswordProcess.php');

    $form->addRow()->addHeading('Reset Password', __('Reset Password'));

    $row = $form->addRow();
        $row->addLabel('password', __('Current Password'));
        $row->addPassword('password')
            ->required()
            ->maxLength(30);

    $row = $form->addRow();
        $row->addLabel('passwordNew', __('New Password'));
        $row->addPassword('passwordNew')
            ->addPasswordPolicy($container->get(PasswordPolicy::class))
            ->addGeneratePasswordButton($form)
            ->required()
            ->maxLength(30);

    $row = $form->addRow();
        $row->addLabel('passwordConfirm', __('Confirm New Password'));
        $row->addPassword('passwordConfirm')
            ->addConfirmation('passwordNew')
            ->required()
            ->maxLength(30);

    // if ($secretcheck == 'Y') {
    //     $row = $form->addRow();
    //         $row->addLabel('mfaCode', __('Multi Factor Authentication Code'))->description(__('In order to change your password, please input the current 6 digit token'));
    //         $row->addNumber('mfaCode')->isRequired(); //TODO: Add visual validation that it's a 6 digit number, bit finnicky because there's the possibility of leading 0s this can't be done with max/min values... also not required for it to work.
    // }

    // $form->addHiddenValue('mfaSecret', $secret);
    // $form->addHiddenValue('mfaEnable', $secretcheck);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();

    if ($forceReset != 'Y') {
        $staff = false;

        /** @var RoleGateway */
        $roleGateway = $container->get(RoleGateway::class);

        foreach ($session->get('gibbonRoleIDAll') as $role) {
            $roleCategory = $roleGateway->getRoleCategory($role[0]);
            $staff = $staff || ($roleCategory == 'Staff');
        }

        $form = Form::create('preferences', $session->get('absoluteURL').'/preferencesProcess.php');
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->addRow()->addHeading('Settings', __('Settings'));

        $row = $form->addRow();
            $row->addLabel('calendarFeedPersonal', __('Personal Google Calendar ID'))->description(__('Google Calendar ID for your personal calendar.').'<br/>'.__('Only enables timetable integration when logging in via Google.'));
            $password = $row->addTextField('calendarFeedPersonal');

        $personalBackground = $container->get(SettingGateway::class)->getSettingByScope('User Admin', 'personalBackground');
        if ($personalBackground == 'Y') {
            $row = $form->addRow();
                $row->addLabel('personalBackground', __('Personal Background'))->description(__('Set your own custom background image.').'<br/>'.__('Please provide URL to image.'));
                $password = $row->addURL('personalBackground');
        }

        $row = $form->addRow();
            $row->addLabel('gibbonThemeIDPersonal', __('Personal Theme'))->description(__('Override the system theme.'));
            $row->addSelectTheme('gibbonThemeIDPersonal');


        $row = $form->addRow();
            $row->addLabel('gibboni18nIDPersonal', __('Personal Language'))->description(__('Override the system default language.'));
            $row->addSelectI18n('gibboni18nIDPersonal');

        $row = $form->addRow();
            $row->addLabel('receiveNotificationEmails', __('Receive Email Notifications?'))->description(__('Notifications can always be viewed on screen.'));
            $row->addYesNo('receiveNotificationEmails');


        // $form->addHiddenValue('mfaSecret', $secret);

    //     $row = $form->addRow();
    //         $row->addLabel('mfaEnable', __('Enable Multi Factor Authentication?'))->description(__('Enhance the security of your account login.'));
    //         $row->addYesNo('mfaEnable')->selected($secretcheck);


    //    //If MFA wasn't previously set, show the MFA QR code.
    //     if ($secretcheck == 'N') {
    //         $form->toggleVisibilityByClass('toggle')->onSelect('mfaEnable')->when('Y');
    //         $row = $form->addRow()->addClass('toggle');
    //             $row->addLabel('mfaCode', __('Multi Factor Authentication Code'))->description(__('Scan the below QR code in your relevant authenticator app and input the code it provides, ensuring it doesn\'t expire before you submit the form.').'<br><img src='. $tfa->getQRCodeImageAsDataUri('Login', $secret) .'>');
    //             $row->addNumber('mfaCode'); //TODO: Add visual validation that it's a 6 digit number, bit finnicky because there's the possibility of leading 0s this can't be done with max/min values... also not required for it to work.
    //     }
    //     //If MFA was previously set, and is being disabled
    //     if ($secretcheck == 'Y' && !empty($values['mfaSecret'])) {
    //         $form->toggleVisibilityByClass('toggle')->onSelect('mfaEnable')->when('N');
    //         $row = $form->addRow()->addClass('toggle');
    //             $row->addLabel('mfaCode', __('Multi Factor Authentication Code'))->description(__('In order to disable your Multi Factor Authentication, please input the current 6 digit token'));
    //             $row->addNumber('mfaCode'); //TODO: Add visual validation that it's a 6 digit number, bit finnicky because there's the possibility of leading 0s this can't be done with max/min values... also not required for it to work.
    //     }

        //TODO: Allow for easy reset of MFA secret, currently would need to disable and then re-enable MFA to do so

        if ($session->get('gibbonRoleIDCurrentCategory') == 'Staff') {
            $row = $form->addRow()->addHeading('Signature', __('Signature'))->append(__('Your messenger signature can be found below. You can use it as a regular email signature by selecting and copying the following contents into your email settings.'));

            include_once($session->get('absolutePath').'/modules/Messenger/src/Signature.php');
            $signature = $container->get(Signature::class)->getSignature($session->get('gibbonPersonID'));

            $row = $form->addRow()->addClass('bg-white');
                $row->addContent($signature.'<br/>');
        }

        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

        $form->loadAllValuesFrom($values);

        echo $form->getOutput();

        $passkeyService = $container->get(PasskeyService::class);
        $gibbonPersonID = (int) $session->get('gibbonPersonID');
        $passkeys = $passkeyService->getPasskeysForPerson($gibbonPersonID);
        $csrfToken = $container->get(TokenHandler::class)->getCSRF();
        $passkeyOptionsUrl = $session->get('absoluteURL').'/preferencesPasskeyOptions.php';
        $passkeyRegisterUrl = $session->get('absoluteURL').'/preferencesPasskeyRegisterProcess.php';
        $passkeyDeleteUrl = $session->get('absoluteURL').'/preferencesPasskeyDeleteProcess.php';

        $passkeyForm = Form::create('passkeys', '');
        $passkeyForm->setFactory(DatabaseFormFactory::create($pdo));

        $passkeyForm->addRow()->addHeading('Passkeys', __('Passkeys'))->append(__('Use a passkey (Touch ID, Windows Hello, a security key or your phone) to sign in without a password. Passkeys stay on your devices and are never stored on the server.'));

        $row = $passkeyForm->addRow();
            $row->addLabel('registeredPasskeys', __('Registered Passkeys'))->description(__('Passkeys registered for your account.'));
            if (empty($passkeys)) {
                $row->addContent(__('No passkeys registered yet.'))->addClass('text-sm text-gray-600');
            } else {
                $table = $row->addTable();
                $header = $table->addHeaderRow();
                $header->addContent(__('Name'));
                $header->addContent(__('Added'));
                $header->addContent(__('Last Used'));
                $header->addContent(__('Action'));

                foreach ($passkeys as $passkey) {
                    $label = !empty($passkey['label']) ? $passkey['label'] : __('Passkey');
                    $tableRow = $table->addRow();
                    $tableRow->addContent(htmlspecialchars($label, ENT_QUOTES, 'UTF-8'));
                    $tableRow->addContent(!empty($passkey['createdAt']) ? Format::dateTime($passkey['createdAt']) : '');
                    $tableRow->addContent(!empty($passkey['lastUsedAt']) ? Format::dateTime($passkey['lastUsedAt']) : __('Never'));

                    $deleteNonce = $container->get(TokenHandler::class)->getNonce();
                    $passkeyID = (int) $passkey['gibbonPersonPasskeyID'];
                    $removeAction = '<a href="#" class="passkeyDelete inline-flex items-center align-middle rounded-md text-sm sm:leading-5 font-semibold px-3 py-2 bg-white shadow-sm border border-gray-400 hover:bg-gray-100 hover:text-red-700 hover:border-red-700 text-gray-600 lg:text-gray-500"'
                        .' data-passkey-id="'.$passkeyID.'"'
                        .' data-passkey-nonce="'.htmlspecialchars($deleteNonce, ENT_QUOTES, 'UTF-8').'"'
                        .' title="'.__('Remove').'" aria-label="'.__('Remove').'">'
                        .icon('solid', 'cross', 'size-6 sm:size-5 lg:-ml-0.5 lg:mr-1.5')
                        .'<span class="hidden lg:block text-gray-800 whitespace-nowrap">'.__('Remove').'</span></a>';
                    $tableRow->addContent($removeAction);
                }
            }

        $row = $passkeyForm->addRow();
            $row->addLabel('passkeyLabel', __('Passkey Name'))->description(__('Give this passkey a name, such as the device it is stored on.'));
            $row->addTextField('passkeyLabel')->maxLength(60)->placeholder(__('e.g. MacBook Touch ID'));
            $row->addButton(__('Add Passkey'), null, 'passkeyRegisterBtn')->setIcon('solid', 'add', 'text-green-600');

        $passkeyForm->addRow()->addContent('<span id="passkeyRegisterMsg" class="text-xs text-gray-600"></span>');

        echo $passkeyForm->getOutput();

        $passkeyScript = <<<'JS'
(function () {
    var btn = document.getElementById('passkeyRegisterBtn');
    var msg = document.getElementById('passkeyRegisterMsg');
    if (!btn) return;

    function b64urlToBuf(value) {
        var v = String(value || '').replace(/-/g, '+').replace(/_/g, '/');
        while (v.length % 4) v += '=';
        var bin = atob(v);
        var bytes = new Uint8Array(bin.length);
        for (var i = 0; i < bin.length; i++) bytes[i] = bin.charCodeAt(i);
        return bytes.buffer;
    }
    function b64ToBuf(value) {
        var bin = atob(String(value || ''));
        var bytes = new Uint8Array(bin.length);
        for (var i = 0; i < bin.length; i++) bytes[i] = bin.charCodeAt(i);
        return bytes.buffer;
    }
    function bufToB64url(buf) {
        var bytes = new Uint8Array(buf), bin = '';
        for (var i = 0; i < bytes.length; i++) bin += String.fromCharCode(bytes[i]);
        return btoa(bin).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
    }
    function bufToB64(buf) {
        var bytes = new Uint8Array(buf), bin = '';
        for (var i = 0; i < bytes.length; i++) bin += String.fromCharCode(bytes[i]);
        return btoa(bin);
    }

    btn.addEventListener('click', async function () {
        msg.textContent = '';
        if (!window.PublicKeyCredential) {
            msg.textContent = __NOT_SUPPORTED__;
            msg.className = 'text-xs text-red-600';
            return;
        }
        try {
            var res = await fetch(__OPTIONS_URL__, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
            var data = await res.json();
            if (!data || !data.options) throw new Error((data && data.error) ? data.error : __START_FAILED__);

            var pk = data.options;
            pk.challenge = b64urlToBuf(pk.challenge);
            pk.user.id = b64urlToBuf(pk.user.id);
            if (pk.excludeCredentials) {
                pk.excludeCredentials = pk.excludeCredentials.map(function (c) {
                    return { type: c.type, id: b64urlToBuf(c.id), transports: c.transports };
                });
            }

            var credential = await navigator.credentials.create({ publicKey: pk });
            var response = credential.response;
            var payload = {
                attestation: {
                    id: bufToB64url(credential.rawId),
                    rawId: bufToB64(credential.rawId),
                    type: credential.type,
                    response: {
                        clientDataJSON: bufToB64url(response.clientDataJSON),
                        attestationObject: bufToB64url(response.attestationObject),
                        transports: response.getTransports ? response.getTransports() : []
                    }
                },
                nonce: data.nonce || '',
                label: document.getElementById('passkeyLabel').value || '',
                csrftoken: __CSRF_TOKEN__
            };

            var save = await fetch(__REGISTER_URL__, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            var saved = await save.json();
            if (!save.ok || !saved.success) throw new Error(saved.error || __SAVE_FAILED__);

            window.location.reload();
        } catch (e) {
            msg.textContent = (e && e.message) ? e.message : __REGISTER_FAILED__;
            msg.className = 'text-xs text-red-600';
        }
    });

    document.querySelectorAll('a.passkeyDelete').forEach(function (deleteBtn) {
        deleteBtn.addEventListener('click', async function (event) {
            event.preventDefault();
            if (!window.confirm(__DELETE_CONFIRM__)) return;

            deleteBtn.disabled = true;
            try {
                var body = new URLSearchParams();
                body.set('csrftoken', __CSRF_TOKEN__);
                body.set('nonce', deleteBtn.getAttribute('data-passkey-nonce') || '');
                body.set('gibbonPersonPasskeyID', deleteBtn.getAttribute('data-passkey-id') || '');

                var res = await fetch(__DELETE_URL__, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: body.toString()
                });

                if ((res.url || '').indexOf('return=success0') !== -1) {
                    window.location.reload();
                    return;
                }

                throw new Error(__DELETE_FAILED__);
            } catch (e) {
                deleteBtn.disabled = false;
                msg.textContent = (e && e.message) ? e.message : __DELETE_FAILED__;
                msg.className = 'text-xs text-red-600';
            }
        });
    });
})();
JS;

        $passkeyScript = str_replace(
            ['__OPTIONS_URL__', '__REGISTER_URL__', '__DELETE_URL__', '__CSRF_TOKEN__', '__NOT_SUPPORTED__', '__START_FAILED__', '__SAVE_FAILED__', '__REGISTER_FAILED__', '__DELETE_CONFIRM__', '__DELETE_FAILED__'],
            [
                json_encode($passkeyOptionsUrl),
                json_encode($passkeyRegisterUrl),
                json_encode($passkeyDeleteUrl),
                json_encode($csrfToken),
                json_encode(__('Passkeys are not supported in this browser.')),
                json_encode(__('Unable to start passkey registration.')),
                json_encode(__('Unable to save the passkey.')),
                json_encode(__('Passkey registration failed.')),
                json_encode(__('Remove this passkey?')),
                json_encode(__('Unable to remove the passkey.')),
            ],
            $passkeyScript
        );
        echo '<script type="text/javascript">'.$passkeyScript.'</script>';
    }
}
?>
