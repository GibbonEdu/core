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
use Gibbon\Module\Reports\Domain\ReportTemplateSectionGateway;
use Gibbon\Module\Reports\Domain\ReportPrototypeSectionGateway;

if (isActionAccessible($guid, $connection2, '/modules/Reports/templates_manage_section_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $gibbonReportTemplateID = $_GET['gibbonReportTemplateID'] ?? '';
    $gibbonReportTemplateSectionID = $_GET['gibbonReportTemplateSectionID'] ?? '';
    $search = $_GET['search'] ?? '';

    $page->breadcrumbs
        ->add(__('Template Builder'), 'templates_manage.php', ['search' => $search])
        ->add(__('Edit Template'), 'templates_manage_edit.php', ['gibbonReportTemplateID' => $gibbonReportTemplateID, 'search' => $search, 'sidebar' => 'false'])
        ->add(__('Edit Section'));

    if (empty($gibbonReportTemplateID) || empty($gibbonReportTemplateSectionID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $templateSectionGateway = $container->get(ReportTemplateSectionGateway::class);
    $templatePrototypeGateway = $container->get(ReportPrototypeSectionGateway::class);

    $values = $templateSectionGateway->selectBy([
        'gibbonReportTemplateID'        => $gibbonReportTemplateID,
        'gibbonReportTemplateSectionID' => $gibbonReportTemplateSectionID,
    ])->fetch();

    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $prototype = $templatePrototypeGateway->getByID($values['gibbonReportPrototypeSectionID']);

    $form = Form::create('templatesManage', $session->get('absoluteURL').'/modules/Reports/templates_manage_section_editProcess.php');

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonReportTemplateID', $gibbonReportTemplateID);
    $form->addHiddenValue('gibbonReportTemplateSectionID', $gibbonReportTemplateSectionID);

    $form->addRow()->addHeading('Basic Details', __('Basic Details'));
    $row = $form->addRow();
        $row->addLabel('name', __('Name'));
        $row->addTextField('name')->maxLength(90)->required();

    if ($values['type'] == 'Header' || $values['type'] == 'Footer') {
        $pageNumber = $values['page'];
        $values['page'] = $values['page'] > 1? 'custom' : $values['page'];
        $pages = [
            '0'      => __('All Pages'),
            '1'      => __('First Page'),
            '-1'     => __('Last Page'),
            'custom' => __('Specific Page'),
        ];
        $row = $form->addRow();
            $row->addLabel('page', __('Display On'));
            $row->addSelect('page')->fromArray($pages)->required();

        $form->toggleVisibilityByClass('customPage')->onSelect('page')->when('custom');
        $row = $form->addRow()->addClass('customPage');
            $row->addLabel('pageCustom', __('Page'));
            $row->addNumber('pageCustom')->maxLength(3)->setValue(max(1, $pageNumber));
    }

    $flags = [
        0b00000001 => __('No Page Wrap'),
        0b00000010 => __('Page Break Before'),
        0b00000100 => __('Page Break After'),
        0b00001000 => __('Skip if Empty'),
        0b00010000 => __('Is Last Page'),
    ];
    // Select flags based on their bitwise value :)
    $values['flags'] = array_reduce(array_keys($flags), function ($group, $item) use ($values) {
        if (($values['flags'] & $item) == $item) {
            $group[] = $item;
        }
        return $group;
    }, []);

    $row = $form->addRow();
        $row->addLabel('flags', __('Flags'));
        $row->addSelect('flags')->fromArray($flags)->selectMultiple();

    // CONFIGURE
    if ($config = json_decode($prototype['config'] ?? '', true)) {
        $form->addRow()->addHeading('Configure', __('Configure'));
        $configValues = json_decode($values['config'] ?? '', true);

        foreach ($config as $configName => $configOptions) {
            if ($configOptions['type'] == 'editor') {
                $col = $form->addRow()->addColumn();
                $col->addLabel("config[{$configName}]", __($configOptions['label'] ?? ucwords($configName)));
                $col->addEditor("config[{$configName}]", $guid)->setID("config{$configName}")->setValue($configValues[$configName] ?? '');
            } else {
                $row = $form->addRow();
                $row->addLabel("config[{$configName}]", __($configOptions['label'] ?? ucwords($configName)));
                $row->addCustomField("config[{$configName}]", $configOptions)->setValue($configValues[$configName] ?? '');
            }
        }
    }
    
    // TEMPLATE
    $form->addRow()->addHeading('Template', __('Template'));
    $params = json_decode($values['templateParams'] ?? '', true);

    $row = $form->addRow();
        $row->addLabel('templateParams[width]', __('Width'));
        $row->addNumber('templateParams[width]')->maxLength(3)->setValue($params['width'] ?? '');

    $row = $form->addRow();
        $row->addLabel('templateParams[height]', __('Height'));
        $row->addNumber('templateParams[height]')->maxLength(3)->setValue($params['height'] ?? '');

    $row = $form->addRow();
        $row->addLabel('position', __('Position'));
        $col = $row->addColumn()->addClass('items-center');
        $col->addContent('<div class="flex-1 pr-1">X</div>');
        $col->addNumber('templateParams[x]')->decimalPlaces(2)->setValue($params['x'] ?? '');
        $col->addContent('<div class="flex-1 pr-1 pl-2">Y</div>');
        $col->addNumber('templateParams[y]')->decimalPlaces(2)->setValue($params['y'] ?? '');

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    $form->loadAllValuesFrom($values);

    echo $form->getOutput();
}
