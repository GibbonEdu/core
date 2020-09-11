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
use Gibbon\Tables\DataTable;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\Reports\Domain\ReportPrototypeSectionGateway;

if (isActionAccessible($guid, $connection2, '/modules/Reports/templates_assets_components_duplicate.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Template Builder'), 'templates_manage.php')
        ->add(__('Manage Assets'), 'templates_assets.php')
        ->add(__('Edit Component'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

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

    $absolutePath = $gibbon->session->get('absolutePath');
    $customAssetPath = $container->get(SettingGateway::class)->getSettingByScope('Reports', 'customAssetPath');

    $form = Form::create('manageComponents', $gibbon->session->get('absoluteURL').'/modules/Reports/templates_assets_components_editProcess.php');

    $form->addHiddenValue('address', $gibbon->session->get('address'));
    $form->addHiddenValue('gibbonReportPrototypeSectionID', $gibbonReportPrototypeSectionID);

    $row = $form->addRow();
        $row->addLabel('name', __('Name'));
        $row->addTextField('name')->readonly();

    $row = $form->addRow();
        $row->addLabel('templateFile', __('File'));
        $row->addTextField('templateFile')->readonly();

    $templateContent = file_get_contents($absolutePath.$customAssetPath.'/templates/'.$values['templateFile']);

    $col = $form->addRow()->addColumn();
        $col->addLabel('templateContent', __('Template Code'));
        $col->addWebLink('<img title="'.__('Preview').'" src="./themes/'.$gibbon->session->get('gibbonThemeName').'/img/plus.png" style="margin-bottom:5px"/>')
                ->setURL($gibbon->session->get('absoluteURL').'/fullscreen.php?q=/modules/Reports/templates_assets_components_preview.php&width=1100&height=550')
                ->addParam('gibbonReportPrototypeSectionID', $gibbonReportPrototypeSectionID)
                ->addParam('TB_iframe', 'true')
                ->addClass('thickbox float-right -mt-3');
        $col->addCodeEditor('templateContent')->setMode('twig')->setValue($templateContent);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    $form->loadAllValuesFrom($values);

    echo $form->getOutput();
}
