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
use Gibbon\Services\Format;
use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\User\FamilyGateway;
use Gibbon\Domain\Admissions\AdmissionsAccountGateway;
use Gibbon\Tables\DataTable;

if (isActionAccessible($guid, $connection2, '/modules/Admissions/admissions_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $search = $_GET['search'] ?? '';

    $page->breadcrumbs
        ->add(__('Admissions Accounts'), 'admissions_manage.php', ['search' => $search])
        ->add(__('Edit Account'));

    // Get the admissions account
    $gibbonAdmissionsAccountID = $_GET['gibbonAdmissionsAccountID'] ?? '';
    $values = $container->get(AdmissionsAccountGateway::class)->getByID($gibbonAdmissionsAccountID);
    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $values['person'] = $container->get(UserGateway::class)->getByID($values['gibbonPersonID']);
    $values['family'] = $container->get(FamilyGateway::class)->getByID($values['gibbonFamilyID']);

    // DETAILS
    $table = DataTable::createDetails('admissionsAccount');

    $table->addColumn('person', __('Person'))
        ->format(function ($values)  {
            $person = $values['person'];
            return !empty($person)
                ? Format::nameLinked($person['gibbonPersonID'], $person['title'], $person['preferredName'], $person['surname'], 'Other', false, true)
                : __('This account is not linked to a user.');
        });

    $table->addColumn('family', __('Family'))
        ->addClass('col-span-2')
        ->format(function ($values) {
            $family = $values['family'];
            $url = Url::fromModuleRoute('User Admin', 'family_manage_edit')->withAbsoluteUrl();

            return !empty($family)
                ? Format::link($url->withQueryParams(['gibbonFamilyID' => $family['gibbonFamilyID']]), $family['name'])
                : __('This account is not linked to a family.');
        });

    $table->addColumn('created', __('Created'))->format(Format::using('dateReadable', 'timestampCreated'));

    $table->addColumn('active', __('Last Active'))->format(Format::using('relativeTime', 'timestampActive'));

    $table->addColumn('ipAddress', __('IP Address'));

    echo $table->render([$values]);


    // FORM
    $form = Form::create('admissionsManage', $session->get('absoluteURL').'/modules/Admissions/admissions_manage_editProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonAdmissionsAccountID', $gibbonAdmissionsAccountID);

    $row = $form->addRow();
        $row->addLabel('email', __('Email'));
        $row->addEmail('email')
            ->uniqueField('./modules/Admissions/admissions_manage_emailAjax.php', ['gibbonAdmissionsAccountID' => $gibbonAdmissionsAccountID])
            ->maxLength(75)
            ->required();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    $form->loadAllValuesFrom($values);

    echo $form->getOutput();

}
