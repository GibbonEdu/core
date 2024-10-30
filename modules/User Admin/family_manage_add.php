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
use Gibbon\Forms\DatabaseFormFactory;

if (isActionAccessible($guid, $connection2, '/modules/User Admin/family_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('Manage Families'), 'family_manage.php')
        ->add(__('Add Family'));    

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $session->get('absoluteURL').'/index.php?q=/modules/User Admin/family_manage_edit.php&gibbonFamilyID='.$_GET['editID'].'&search='.$_GET['search'];
    }
    $page->return->setEditLink($editLink);

    $search = $_GET['search'] ?? '';
    if ($search != '') {
        $page->navigator->addSearchResultsAction(Url::fromModuleRoute('User Admin', 'family_manage.php')->withQueryParam('search', $search));
    }

    $form = Form::create('action', $session->get('absoluteURL').'/modules/'.$session->get('module')."/family_manage_addProcess.php?search=$search");
    $form->setFactory(DatabaseFormFactory::create($pdo));
    
    $form->addHiddenValue('address', $session->get('address'));

    $form->addRow()->addHeading('General Information', __('General Information'));

    $row = $form->addRow();
        $row->addLabel('name', __('Family Name'));
        $row->addTextField('name')->maxLength(100)->required();

    $row = $form->addRow();
		$row->addLabel('status', __('Marital Status'));
		$row->addSelectMaritalStatus('status')->required();

    $row = $form->addRow();
        $row->addLabel('languageHomePrimary', __('Home Language - Primary'));
        $row->addSelectLanguage('languageHomePrimary');

    $row = $form->addRow();
        $row->addLabel('languageHomeSecondary', __('Home Language - Secondary'));
        $row->addSelectLanguage('languageHomeSecondary');

    $row = $form->addRow();
        $row->addLabel('nameAddress', __('Address Name'))->description(__('Formal name to address parents with.'));
        $row->addTextField('nameAddress')->maxLength(100)->required();

    $row = $form->addRow();
        $row->addLabel('homeAddress', __('Home Address'))->description(__('Unit, Building, Street'));
        $row->addTextField('homeAddress')->maxLength(255);

    $row = $form->addRow();
        $row->addLabel('homeAddressDistrict', __('Home Address (District)'))->description(__('County, State, District'));
        $row->addTextFieldDistrict('homeAddressDistrict');

    $row = $form->addRow();
        $row->addLabel('homeAddressCountry', __('Home Address (Country)'));
        $row->addSelectCountry('homeAddressCountry');

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
?>
