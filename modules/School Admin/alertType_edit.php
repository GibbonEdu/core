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
use Gibbon\Domain\Alerts\AlertTypeGateway;

if (!isActionAccessible($guid, $connection2, '/modules/School Admin/alertLevelSettings.php')) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Manage Alert Types and Level'), 'alertLevelSettings.php')
        ->add(__('Edit Type'));

    $alertTypeGateway = $container->get(AlertTypeGateway::class);

    $gibbonAlertTypeID = $_GET['gibbonAlertTypeID'] ?? '';
    $values = $alertTypeGateway->getByID($gibbonAlertTypeID);

    if (empty($values)) {
        $page->addError(__('The selected record does not exist, or you do not have access to it.'));
        return;
    }
        
    $form = Form::create('alertType', $session->get('absoluteURL').'/modules/School Admin/alertType_editProcess.php');
    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonAlertTypeID', $gibbonAlertTypeID);

    $row = $form->addRow();
        $row->addLabel('name', __('Name'))->description(__('Must be unique.'));
        $row->addTextField('name')->required()->readOnly();

    $row = $form->addRow();
        $row->addLabel('tag', __('Tag'));
        $row->addTextField('tag')->maxLength(5);

    $row = $form->addRow();
        $row->addLabel('active', __('Active'));
        $row->addYesNo('active')->required();

    $row = $form->addRow();
        $row->addLabel('color', __('Font/Border Colour'))->description(__('Click to select a colour.'));
    	$row->addColor('color');

    $row = $form->addRow();
        $row->addLabel('colorBG', __('Background Colour'))->description(__('Click to select a colour.'));
        $row->addColor('colorBG');

    $row = $form->addRow();
        $row->addLabel('description', __('Description'));
        $row->addTextArea('description');

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    $form->loadAllValuesFrom($values);

    echo $form->getOutput();
}
