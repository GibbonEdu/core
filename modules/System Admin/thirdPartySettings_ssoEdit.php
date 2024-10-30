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
    }

    if ($sso == 'Microsoft' || $sso == 'Other') {
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

    if ($sso == 'Other') {
        $row = $form->addRow()->addClass('settingActive');
            $row->addLabel('authorizeEndpoint', __('API Authorization Endpoint'));
            $row->addURL('authorizeEndpoint')->required();

        $row = $form->addRow()->addClass('settingActive');
            $row->addLabel('tokenEndpoint', __('API Token Endpoint'));
            $row->addURL('tokenEndpoint')->required();

        $row = $form->addRow()->addClass('settingActive');
            $row->addLabel('userEndpoint', __('API User Endpoint'));
            $row->addURL('userEndpoint')->required();
    
        $row = $form->addRow()->addHeading('Additional Parameters', __('Additional Parameters'))
                    ->addClass('settingActive')
                    ->append(__('Some systems require additional parameters for a login request in order to read the user\'s basic profile.'));
            
        $row = $form->addRow()->addClass('settingActive');
            $row->addLabel('scopes', __('Scopes'))
                ->description(__('Scope is a mechanism in OAuth 2.0 to limit an application\'s access to a user\'s account. An application can request one or more scopes. The standard scopes for an OpenID Connect compliant system are: openid profile email.'));
            $row->addTextField('scopes');

        $row = $form->addRow()->addClass('settingActive');
            $row->addLabel('usernameAttribute', __('Username attribute'))
                ->description(__('Name of the attribute containing usernames in the OAuth service.'));
            $row->addTextField('usernameAttribute')->required();                       
    }

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    $form->loadAllValuesFrom($values);

    echo $form->getOutput();
}
