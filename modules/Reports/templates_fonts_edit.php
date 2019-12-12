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
use Gibbon\Module\Reports\Domain\ReportTemplateFontGateway;


if (isActionAccessible($guid, $connection2, '/modules/Reports/templates_fonts.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Template Builder'), 'templates_manage.php')
        ->add(__('Manage Fonts'), 'templates_fonts.php')
        ->add(__('Edit Font'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

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

    $form = Form::create('templatesFonts', $gibbon->session->get('absoluteURL').'/modules/Reports/templates_fonts_editProcess.php');

    $form->addHiddenValue('address', $gibbon->session->get('address'));
    $form->addHiddenValue('gibbonReportTemplateFontID', $gibbonReportTemplateFontID);

    $row = $form->addRow();
        $row->addLabel('fontName', __('Name'))->description(__('Must be unique'));
        $row->addTextField('fontName')->maxLength(90)->required();

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
