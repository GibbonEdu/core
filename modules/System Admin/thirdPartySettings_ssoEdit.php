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
use Gibbon\Domain\System\SettingGateway;

if (isActionAccessible($guid, $connection2, '/modules/System Admin/thirdPartySettings.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $page->return->addReturns([
        'success0' => __('SSO settings saved.'),
        'error0' => __('You do not have access to this action.'),
        'error1' => __('Your request failed because your inputs were invalid.'),
        'error12' => __('The OIDC discovery document could not be retrieved. Please check the issuer or discovery URL, or disable auto-configuration.'),
    ]);
    //Proceed!
    $page->breadcrumbs
        ->add(__('Third Party Settings'), 'thirdPartySettings.php')
        ->add(__('Edit SSO Settings'));

    //Check if StringID specified
    $sso = $_GET['sso'] ?? '';
    
    if (empty($sso)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    } 
        
    $settingGateway = $container->get(SettingGateway::class);
    $ssoSetting = $settingGateway->getSettingByScope('System Admin', 'sso'.$sso);

    if (empty($ssoSetting)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    } 

    //Let's go!
    $values = json_decode($ssoSetting, true);

    $form = Form::create('editSSO', $session->get('absoluteURL').'/modules/'.$session->get('module').'/thirdPartySettings_ssoEditProcess.php');
    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('sso', $sso);

    if ($sso == 'Google') {
        // GOOGLE
        $form->addRow()->addHeading('Google Integration', __('Google Integration'))->append(sprintf(__('If your school uses Google Apps, you can enable single sign on and calendar integration with Gibbon. This process makes use of Google\'s APIs, and allows a user to access Gibbon without a username and password, provided that their listed email address is a Google account to which they have access. For configuration instructions, %1$sclick here%2$s.'), "<a href='https://gibbonedu.org/support/administrators/installing-gibbon/authenticating-with-google-oauth/' target='_blank'>", '</a>'));

        $row = $form->addRow();
            $row->addLabel('enabled', __('API Enabled'))->description(__('Enable Gibbon-wide integration with the Google APIs?'));
            $row->addYesNo('enabled')->required();

        $form->toggleVisibilityByClass('googleActive')->onSelect('enabled')->when('Y');

        $row = $form->addRow()->addClass('googleActive');
            $row->addLabel('clientName', __('Google Developers Client Name'))->description(__('Name of Google Project in Developers Console.'));
            $row->addTextArea('clientName')->setRows(2)->required();
            
        $row = $form->addRow()->addClass('googleActive');
            $row->addLabel('clientID', __('Google Developers Client ID'))->description(__('Client ID for Google Project In Developers Console.'));
            $row->addTextArea('clientID')->setRows(2)->required();
            
        $row = $form->addRow()->addClass('googleActive');
            $row->addLabel('clientSecret', __('Google Developers Client Secret'))->description(__('Client Secret for Google Project In Developers Console.'));
            $row->addTextArea('clientSecret')->setRows(2)->required();
            
        $row = $form->addRow()->addClass('googleActive');
            $row->addLabel('developerKey', __('Google Developers Developer Key'))->description(__('Google project Developer Key.'));
            $row->addTextArea('developerKey')->setRows(2)->required();

        $redirectUri = $session->get('absoluteURL').'/login.php';
        $row = $form->addRow()->addClass('googleActive');
            $row->addLabel('redirectUri', __('Google Developers Redirect Url'))->description(__('Copy-and-paste this redirect uri and register it with your OAuth2 provider.'));
            $row->addTextArea('redirectUri')->setRows(2)->readonly()->setValue($redirectUri);

        $setting = $settingGateway->getSettingByScope('System', 'calendarFeed', true);
        $row = $form->addRow()->addClass('googleActive');
            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
            $row->addTextField($setting['name'])->setValue($setting['value']);

    } else if ($sso == 'Microsoft') {
        // MICROSOFT
        $form->addRow()->addHeading('Microsoft Integration', __('Microsoft Integration'))->append(sprintf(__('If your school uses Microsoft Azure or Office 365, you can enable single sign on and calendar integration with Gibbon. This process makes use of Microsoft\'s APIs, and allows a user to access Gibbon without a username and password, provided that their listed email address is a Microsoft account to which they have access. For configuration instructions, %1$sclick here%2$s.'), "<a href='https://gibbonedu.org/support/administrators/installing-gibbon/authenticating-with-microsoft-oauth/' target='_blank'>", '</a>'));

        $row = $form->addRow();
            $row->addLabel('enabled', __('API Enabled'))->description(__('Enable Gibbon-wide integration with the Microsoft APIs?'));
            $row->addYesNo('enabled')->required();

    } else if ($sso == 'Other') {
        $form->addRow()->addHeading('Generic OAuth2 Provider', __('Generic OAuth2 Provider'))->append(__('This setting offers a generic implementation of industry-standard OAuth2 protocols. It uses standard Client ID and Client Secret parameters to connect to an OAuth2 API server. You will need to specify the API endpoints of your chosen service, which can often be found in that service\'s documentation. If your OAuth2 service requires specific API parameters, this feature is unlikely to work.'));

        $row = $form->addRow();
            $row->addLabel('enabled', __('API Enabled'));
            $row->addYesNo('enabled')->required();

        $row = $form->addRow()->addClass('settingActive');
            $row->addLabel('clientName', __('Service Name'))->description(__('The name of the OAuth2 service, which will be displayed on the login page button.'));
            $row->addTextField('clientName')->required();
    } else if ($sso == 'OIDC') {
        $form->addRow()->addHeading('OpenID Connect Provider', __('OpenID Connect Provider'))->append(__('Configure an OpenID Connect provider. Manual binding is the default: an unknown OIDC identity must sign in with its existing Gibbon account before it is linked. Automatic unique matching by email or username can be enabled below.'));

        $row = $form->addRow();
            $row->addLabel('enabled', __('API Enabled'));
            $row->addYesNo('enabled')->required();

        $row = $form->addRow()->addClass('settingActive');
            $row->addLabel('clientName', __('Service Name'))->description(__('The name of the OpenID Connect service, which will be displayed on the login page button.'));
            $row->addTextField('clientName')->required();
    }

    if ($sso == 'Microsoft' || $sso == 'Other' || $sso == 'OIDC') {
        $form->toggleVisibilityByClass('settingActive')->onSelect('enabled')->when('Y');

        $row = $form->addRow()->addClass('settingActive');
            $row->addLabel('clientID', __('API Client ID'))->description(__('The application (client) ID provided by the OAuth2 service.'));
            $row->addTextArea('clientID')->setRows(2)->required();

        $row = $form->addRow()->addClass('settingActive');
            $row->addLabel('clientSecret', __('API Client Secret'))->description(__('A unique secret key generated by the OAuth2 service.'));
            $row->addTextArea('clientSecret')->setRows(2)->required();

        $redirectUri = $session->get('absoluteURL').'/login.php';
        $row = $form->addRow()->addClass('settingActive');
            $row->addLabel('redirectUri', __('API Redirect Uri'))->description(__('Copy-and-paste this redirect uri and register it with your OAuth2 provider.'));
            $row->addTextArea('redirectUri')->setRows(2)->readonly()->setValue($redirectUri);
    }

    if ($sso == 'Other' || $sso == 'OIDC') {
        $row = $form->addRow()->addClass('settingActive');
            $row->addLabel('authorizeEndpoint', __('API Authorization Endpoint'));
            $row->addURL('authorizeEndpoint')->required();

        $row = $form->addRow()->addClass('settingActive');
            $row->addLabel('tokenEndpoint', __('API Token Endpoint'));
            $row->addURL('tokenEndpoint')->required();

        $row = $form->addRow()->addClass('settingActive');
            $row->addLabel('userEndpoint', __('API User Endpoint'));
            $row->addURL('userEndpoint')->required();

        $row = $form->addRow()->addClass('settingActive');
            $row->addLabel('jwksUri', __('JWKS URI'))->description(__('Public key set published by the provider. It is filled automatically when auto-discovery is enabled.'));
            $row->addURL('jwksUri');

        if ($sso == 'OIDC') {
            $row = $form->addRow()->addClass('settingActive');
                $row->addLabel('issuer', __('OIDC Issuer / Discovery Base URL'))->description(__('The issuer identifier, used to keep bindings unique. It is also the base URL for the standard discovery document.'));
                $row->addURL('issuer');

            $row = $form->addRow()->addClass('settingActive');
                $row->addLabel('discoveryUrl', __('Discovery URL (optional)'))->description(__('Leave blank to use <issuer>/.well-known/openid-configuration. Set this only when the provider uses a custom discovery location.'));
                $row->addURL('discoveryUrl');

            $row = $form->addRow()->addClass('settingActive');
                $row->addLabel('autoDiscovery', __('Auto-configure from Discovery'))->description(__('When saving, fetch the discovery document and populate the authorization, token, user info and JWKS endpoints. Disable this if you need to set the endpoints manually.'));
                $row->addYesNo('autoDiscovery')->selected('Y');

            $row = $form->addRow()->addClass('settingActive');
                $row->addLabel('subjectAttribute', __('Subject Attribute'))->description(__('The claim containing the stable subject identifier. The standard OpenID Connect claim is: sub.'));
                $row->addTextField('subjectAttribute')->setValue('sub');

            $row = $form->addRow()->addClass('settingActive');
                $row->addLabel('accountMatching', __('Account Matching'))->description(__('Choose whether OIDC identities are matched to existing accounts. Manual binding never matches by email or username. Automatic matching uses the mappings below and only proceeds when exactly one existing account matches.'));
                $row->addSelect('accountMatching')->fromArray([
                    'manual' => __('Manual binding (recommended)'),
                    'auto'   => __('Automatic matching'),
                ])->required()->selected('manual');

            $row = $form->addRow()->addClass('settingActive');
                $row->addLabel('matchClaims', __('Match Claims'))->description(__('Comma-separated mappings of Gibbon account fields to OIDC claims. Supported fields: email, username. Examples: email=email or email=email, username=preferred_username.'));
                $row->addTextArea('matchClaims')->setRows(2)->setValue('email=email');

            $row = $form->addRow()->addClass('settingActive');
                $row->addLabel('requireVerifiedEmail', __('Require Verified Email'))->description(__('For automatic email matching, require the OIDC email_verified claim before linking. If the claim is missing or false, automatic matching is skipped; manual binding is only offered when fallback is enabled.'));
                $row->addYesNo('requireVerifiedEmail')->selected('Y');

            $row = $form->addRow()->addClass('settingActive');
                $row->addLabel('fallbackToManual', __('Fall Back to Manual Binding'))->description(__('Only applies when automatic matching is enabled. If enabled, an unmatched OIDC identity is shown the manual binding form; if disabled, the login is rejected. Default: No.'));
                $row->addYesNo('fallbackToManual')->selected('N');
        }

        $row = $form->addRow()->addHeading('Additional Parameters', __('Additional Parameters'))
                    ->addClass('settingActive')
                    ->append(__('Some systems require additional parameters for a login request in order to read the user\'s basic profile.'));

        $row = $form->addRow()->addClass('settingActive');
            $row->addLabel('scopes', __('Scopes'))
                ->description(__('Scope is a mechanism in OAuth 2.0 to limit an application\'s access to a user\'s account. An application can request one or more scopes. The standard scopes for an OpenID Connect compliant system are: openid profile email.'));
            $row->addTextField('scopes');

        if ($sso == 'Other') {
            $row = $form->addRow()->addClass('settingActive');
                $row->addLabel('usernameAttribute', __('Username attribute'))
                    ->description(__('Name of the attribute containing usernames in the OAuth service.'));
                $row->addTextField('usernameAttribute')->required();
        }
    } else {
        $row = $form->addRow()->addClass('settingActive');
        $row->addLabel('scopes', __('Custom API Scopes'))->description(__('Leave this blank unless you have specific API scopes you need to set.'));
        $row->addTextField('scopes');
    }

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    $form->loadAllValuesFrom($values);

    echo $form->getOutput();
}
