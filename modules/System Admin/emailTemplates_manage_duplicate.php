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
use Gibbon\Domain\System\EmailTemplateGateway;

if (isActionAccessible($guid, $connection2, '/modules/System Admin/emailTemplates_manage_duplicate.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Email Templates'), 'emailTemplates_manage.php')
        ->add(__('Duplicate Email Template'));

    if (isset($_GET['editID'])) {
        $page->return->setEditLink($session->get('absoluteURL').'/index.php?q=/modules/System Admin/emailTemplates_manage_edit.php&gibbonEmailTemplateID='.$_GET['editID']);
    }

    $gibbonEmailTemplateID = $_GET['gibbonEmailTemplateID'] ?? '';

    if (empty($gibbonEmailTemplateID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $values = $container->get(EmailTemplateGateway::class)->getByID($gibbonEmailTemplateID);

    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $form = Form::create('emailTemplates', $session->get('absoluteURL').'/modules/System Admin/emailTemplates_manage_duplicateProcess.php');

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonEmailTemplateID', $gibbonEmailTemplateID);

    $form->addRow()->addHeading('Basic Details', __('Basic Details'));

    $row = $form->addRow();
        $row->addLabel('moduleName', __('Module'));
        $row->addTextField('moduleName')->readonly()->setValue($values['moduleName']);

    $row = $form->addRow();
        $row->addLabel('templateType', __('Type'));
        $row->addTextField('templateType')->readonly()->setValue($values['templateType']);

    $row = $form->addRow();
        $row->addLabel('templateName', __('Name'));
        $row->addTextField('templateName')->maxLength(120)->setValue($values['templateName'].' '.__('Copy'));

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();
        
    echo $form->getOutput();
}
