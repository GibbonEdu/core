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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\Form;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/space_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('Manage Facilities'), 'space_manage.php')
        ->add(__('Add Facility'));

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $session->get('absoluteURL').'/index.php?q=/modules/School Admin/space_manage_edit.php&gibbonSpaceID='.$_GET['editID'];
    }
    $page->return->setEditLink($editLink);

    $form = Form::create('spaceAdd', $session->get('absoluteURL').'/modules/'.$session->get('module').'/space_manage_addProcess.php');

    $form->addHiddenValue('address', $session->get('address'));

    $row = $form->addRow();
        $row->addLabel('name', __('Name'))->description(__('Must be unique.'));
        $row->addTextField('name')->required()->maxLength(30);

    $types = $container->get(SettingGateway::class)->getSettingByScope('School Admin', 'facilityTypes');

    $row = $form->addRow();
        $row->addLabel('type', __('Type'));
        $row->addSelect('type')->fromString($types)->required()->placeholder();
    
    $row = $form->addRow();
        $row->addLabel('active', __('Active'));
        $row->addYesNo('active')->selected('Y');

    $row = $form->addRow();
        $row->addLabel('capacity', __('Capacity'));
        $row->addNumber('capacity')->maxLength(5)->setValue('0');

    $row = $form->addRow();
        $row->addLabel('computer', __('Teacher\'s Computer'));
        $row->addYesNo('computer')->selected('N');

    $row = $form->addRow();
        $row->addLabel('computerStudent', __('Student Computers'))->description(__('How many are there'));
        $row->addNumber('computerStudent')->maxLength(5)->setValue('0');

    $row = $form->addRow();
        $row->addLabel('projector', __('Projector'));
        $row->addYesNo('projector')->selected('N');

    $row = $form->addRow();
        $row->addLabel('tv', __('TV'));
        $row->addYesNo('tv')->selected('N');

    $row = $form->addRow();
        $row->addLabel('dvd', __('DVD Player'));
        $row->addYesNo('dvd')->selected('N');

    $row = $form->addRow();
        $row->addLabel('hifi', __('Hifi'));
        $row->addYesNo('hifi')->selected('N');

    $row = $form->addRow();
        $row->addLabel('speakers', __('Speakers'));
        $row->addYesNo('speakers')->selected('N');

    $row = $form->addRow();
        $row->addLabel('iwb', __('Interactive White Board'));
        $row->addYesNo('iwb')->selected('N');

    $row = $form->addRow();
        $row->addLabel('phoneInternal', __('Extension'))->description(__('Room\'s internal phone number.'));
        $row->addTextField('phoneInternal')->maxLength(5);

    $row = $form->addRow();
        $row->addLabel('phoneExternal', __('Phone Number'))->description(__('Room\'s external phone number.'));
        $row->addTextField('phoneExternal')->maxLength(20);

    $row = $form->addRow();
        $row->addLabel('comment', __('Comment'));
        $row->addTextArea('comment')->setRows(8);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
