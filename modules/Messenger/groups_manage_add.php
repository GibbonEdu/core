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
use Gibbon\Forms\DatabaseFormFactory;

$page->breadcrumbs
    ->add(__('Manage Groups'), 'groups_manage.php')
    ->add(__('Add Group'));

if (isActionAccessible($guid, $connection2, '/modules/Messenger/groups_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $session->get('absoluteURL').'/index.php?q=/modules/Messenger/groups_manage_edit.php&gibbonGroupID='.$_GET['editID'];
    }
    $page->return->setEditLink($editLink);
    
    $form = Form::create('groups', $session->get('absoluteURL').'/modules/'.$session->get('module')."/groups_manage_addProcess.php");
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));

    $row = $form->addRow();
        $row->addLabel('name', __('Name'));
        $row->addTextField('name')->required()->maxLength(60);

    $row = $form->addRow();
        $row->addLabel('members', __('Members'));
        $row->addSelectUsers('members', $session->get('gibbonSchoolYearID'), ['includeStudents' => true])
            ->selectMultiple()
            ->required();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();
        
    echo $form->getOutput();
}
