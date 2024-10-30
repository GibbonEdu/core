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
use Gibbon\Tables\DataTable;
use Gibbon\Module\Reports\Domain\ReportPrototypeSectionGateway;
use Gibbon\Domain\System\SettingGateway;


if (isActionAccessible($guid, $connection2, '/modules/Reports/templates_assets_components_duplicate.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Template Builder'), 'templates_manage.php')
        ->add(__('Manage Assets'), 'templates_assets.php')
        ->add(__('Duplicate Component'));

    $gibbonReportPrototypeSectionID = $_GET['gibbonReportPrototypeSectionID'] ?? '';
    $prototypeGateway = $container->get(ReportPrototypeSectionGateway::class);

    if (empty($gibbonReportPrototypeSectionID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $values = $prototypeGateway->getByID($gibbonReportPrototypeSectionID);
    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $customAssetPath = $container->get(SettingGateway::class)->getSettingByScope('Reports', 'customAssetPath');
    $values['templateFile'] = str_replace('.twig.html', '_copy.twig.html', $values['templateFile']);

    $form = Form::create('manageComponents', $session->get('absoluteURL').'/modules/Reports/templates_assets_components_duplicateProcess.php');

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonReportPrototypeSectionID', $gibbonReportPrototypeSectionID);

    $row = $form->addRow();
        $row->addLabel('templateFileDestination', __('File'));
        $row->addTextField('templateFileDestination')
            ->maxLength(255)
            ->required()
            ->setValue(basename($values['templateFile']))
            ->prepend(dirname($values['templateFile']).'/')
            ->addValidation(
                'Validate.Format',
                'pattern: /.*\.twig\.html/, failureMessage: "'.__('Invalid File Type').'"'
            );

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
