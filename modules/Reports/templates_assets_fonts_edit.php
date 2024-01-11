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
use Gibbon\Module\Reports\Domain\ReportTemplateFontGateway;


if (isActionAccessible($guid, $connection2, '/modules/Reports/templates_assets_fonts_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Template Builder'), 'templates_manage.php')
        ->add(__('Manage Assets'), 'templates_assets.php')
        ->add(__('Edit Font'));

    $gibbonReportTemplateFontID = $_GET['gibbonReportTemplateFontID'] ?? '';
    $fontGateway = $container->get(ReportTemplateFontGateway::class);

    if (empty($gibbonReportTemplateFontID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $values = $fontGateway->getByID($gibbonReportTemplateFontID);
    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $form = Form::create('templatesFonts', $session->get('absoluteURL').'/modules/Reports/templates_assets_fonts_editProcess.php');

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonReportTemplateFontID', $gibbonReportTemplateFontID);

    $row = $form->addRow();
        $row->addLabel('fontName', __('Name'))->description(__('Must be unique'));
        $row->addTextField('fontName')->maxLength(90)->required();

    $row = $form->addRow();
        $row->addLabel('fontFamily', __('Font Family'));
        $row->addTextField('fontFamily')->maxLength(60)->required();

    $types = [
        'R' => __('Regular'),
        'B' => __('Bold'),
        'I' => __('Italic'),
        'BI' => __('Bold & Italic'),
    ];
    $row = $form->addRow();
        $row->addLabel('fontType', __('Type'));
        $row->addSelect('fontType')->fromArray($types);

    $row = $form->addRow();
        $row->addLabel('fontTCPDF', __('File Name'));
        $row->addTextField('fontTCPDF')->readonly();

    $row = $form->addRow();
        $row->addLabel('fontPath', __('Path'));
        $row->addTextField('fontPath')->readonly();

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    $form->loadAllValuesFrom($values);

    echo $form->getOutput();
}
