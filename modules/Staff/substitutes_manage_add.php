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
use Gibbon\Domain\System\SettingGateway;

if (isActionAccessible($guid, $connection2, '/modules/Staff/substitutes_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $search = $_GET['search'] ?? '';

    $page->breadcrumbs
        ->add(__('Manage Substitutes'), 'substitutes_manage.php', ['search' => $search])
        ->add(__('Add Substitute'));

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $session->get('absoluteURL').'/index.php?q=/modules/Staff/substitutes_manage_edit.php&gibbonSubstituteID='.$_GET['editID'].'&search='.$_GET['search'];
    }
    $page->return->setEditLink($editLink);

    if ($search != '') {
        $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Staff', 'substitutes_manage.php')->withQueryParam('search', $search));
    }

    $form = Form::create('subsManage', $session->get('absoluteURL').'/modules/'.$session->get('module').'/substitutes_manage_addProcess.php?search='.$search);

    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));

    $form->addRow()->addHeading('Basic Information', __('Basic Information'));

    $row = $form->addRow();
        $row->addLabel('gibbonPersonID', __('Person'))->description(__('Must be unique.'));
        $row->addSelectUsers('gibbonPersonID')->placeholder()->required();

    $row = $form->addRow();
        $row->addLabel('active', __('Active'));
        $row->addYesNo('active')->required();

    $types = $container->get(SettingGateway::class)->getSettingByScope('Staff', 'substituteTypes');
    $types = array_filter(array_map('trim', explode(',', $types)));

    $row = $form->addRow();
        $row->addLabel('type', __('Type'));
        $row->addSelect('type')->fromArray($types);

    $row = $form->addRow();
        $row->addLabel('priority', __('Priority'))->description(__('Higher priority substitutes appear first when booking coverage.'));
        $row->addSelect('priority')->fromArray(range(-9, 9))->required()->selected(0);

    $row = $form->addRow();
        $row->addLabel('details', __('Details'))->description(__('Additional information such as year group preference, language preference, etc.'));
        $row->addTextArea('details')->setRows(2)->maxlength(255);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
