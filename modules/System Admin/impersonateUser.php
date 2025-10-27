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

use Gibbon\Http\Url;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Domain\User\RoleGateway;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Forms\DatabaseFormFactory;

if (isActionAccessible($guid, $connection2, '/modules/System Admin/impersonateUser.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Impersonate User'));

    // Validate the current user and that the session data is correct
    $currentUser = $container->get(UserGateway::class)->getByID($session->get('gibbonPersonID'), ['gibbonRoleIDPrimary']);
    if (empty($currentUser) || $currentUser['gibbonRoleIDPrimary'] != $session->get('gibbonRoleIDCurrent')) {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    // Check that the current user had Administrator access
    $primaryRole = $container->get(RoleGateway::class)->selectBy(['gibbonRoleID' => $session->get('gibbonRoleIDPrimary')], ['name', 'gibbonRoleID'])->fetch();
    if (empty($primaryRole) || $primaryRole['name'] != 'Administrator' || $primaryRole['gibbonRoleID'] != '001') {
        $page->addError(__('You do not have access to this action.'));
        return;
    }

    $form = Form::create('sessionImpersonate', Url::fromModuleRoute('System Admin', 'impersonateUserProcess')->directLink());
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addRow()->addHeading('Impersonate User', __('Impersonate User'))->append(Format::alert(__('This option enables Administrators to impersonate another account in the system for troubleshooting and testing purposes. The selected user will still be able to log in and out of their own account. You will need to log out and back in to your own account when finished.'), 'warning'));

    $row = $form->addRow();
        $row->addLabel('gibbonPersonIDAccountSwitch', __('User Account'))->description(__('Your active session will be switched to the selected user.'));
        $row->addSelectUsers('gibbonPersonIDAccountSwitch', $session->get('gibbonSchoolYearID'), [])->placeholder()->required();

    $row = $form->addRow()->addSubmit();

    echo $form->getOutput();
}
