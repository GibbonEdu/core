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
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Module\Reports\Domain\ReportTemplateGateway;
use Gibbon\Module\Reports\Domain\ReportTemplateFontGateway;
use Gibbon\Module\Reports\Domain\ReportTemplateSectionGateway;
use Gibbon\Module\Reports\Domain\ReportPrototypeSectionGateway;
use Gibbon\Tables\View\GridView;
use Gibbon\Domain\DataSet;

if (isActionAccessible($guid, $connection2, '/modules/Reports/templates_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $search = $_GET['search'] ?? '';

    $page->breadcrumbs
        ->add(__('Template Builder'), 'templates_manage.php', ['search' => $search])
        ->add(__('Edit Template'));

    $gibbonReportTemplateID = $_GET['gibbonReportTemplateID'] ?? '';
    $templateGateway = $container->get(ReportTemplateGateway::class);
    $templateFontGateway = $container->get(ReportTemplateFontGateway::class);
    $templateSectionGateway = $container->get(ReportTemplateSectionGateway::class);
    $prototypeSectionGateway = $container->get(ReportPrototypeSectionGateway::class);

    if (empty($gibbonReportTemplateID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $values = $templateGateway->getByID($gibbonReportTemplateID);
    $config = json_decode($values['config'] ?? '', true);

    if (empty($values)) {
        $page->addError(__('The specified record cannot be found.'));
        return;
    }

    $form = Form::create('templatesManage', $session->get('absoluteURL') . '/modules/Reports/templates_manage_editProcess.php');

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('gibbonReportTemplateID', $gibbonReportTemplateID);

    $form->addRow()->addHeading('Basic Information', __('Basic Information'));

    $row = $form->addRow();
    $row->addLabel('name', __('Name'))->description(__('Must be unique'));
    $row->addTextField('name')->maxLength(90)->required();

    $row = $form->addRow();
    $row->addLabel('context', __('Context'));
    $row->addTextField('context')->readonly();

    $stylesheets = $prototypeSectionGateway->selectPrototypeStylesheets();
    $row = $form->addRow();
    $row->addLabel('stylesheet', __('Stylesheet'));
    $row->addSelect('stylesheet')->fromResults($stylesheets)->placeholder();

    $fontFamilies = $templateFontGateway->selectFontFamilies();
    $row = $form->addRow();
    $row->addLabel('fonts', __('Fonts'));
    $row->addSelect('fonts')
        ->fromResults($fontFamilies)
        ->selectMultiple()
        ->setSize(4)
        ->selected($config['fonts'] ?? []);

    $flags = ['000' => __('TCPDF Renderer - Faster, Limited HTML'), '001' => __('mPDF Renderer - Slower, Better HTML Support')];
    $row = $form->addRow();
    $row->addLabel('flags', __('Renderer'));
    $row->addSelect('flags')->fromArray($flags)->required();

    $form->addRow()->addHeading('Document Setup', __('Document Setup'));

    $orientations = ['P' => __('Portrait'), 'L' => __('Landscape')];
    $row = $form->addRow();
    $row->addLabel('orientation', __('Orientation'));
    $row->addSelect('orientation')->fromArray($orientations)->required();

    $pageSizes = ['A4' => __('A4'), 'LETTER' => __('US Letter')];
    $row = $form->addRow();
    $row->addLabel('pageSize', __('Page Size'));
    $row->addSelect('pageSize')->fromArray($pageSizes)->required();

    $row = $form->addRow();
    $row->addLabel('margins', __('Margins'));
    $col = $row->addColumn()->addClass('items-center');
    $col->addContent('<div class="flex-1 pr-1">X</div>');
    $col->addNumber('marginX')->decimalPlaces(2)->required();
    $col->addContent('<div class="flex-1 pr-1 pl-2">Y</div>');
    $col->addNumber('marginY')->decimalPlaces(2)->required();

    $row = $form->addRow();
    $row->addFooter();
    $row->addSubmit();

    $form->loadAllValuesFrom($values);

    echo $form->getOutput();

    // QUERY
    $criteria = $templateSectionGateway->newQueryCriteria()
        ->sortBy('sequenceNumber', 'ASC')
        ->fromPOST();

    // DATA TABLE
    $table = $container->get(DataTable::class);
    $table->addMetaData('blankSlate', __('There are no sections here yet.'));

    $draggableAJAX = $session->get('absoluteURL') . '/modules/Reports/templates_manage_editOrderAjax.php';
    $table->addDraggableColumn('gibbonReportTemplateSectionID', $draggableAJAX, [
        'gibbonReportTemplateID' => $gibbonReportTemplateID,
    ]);

    $table->addColumn('name', __('Name'));

    // Add column to header/footer tables
    $table->addColumn('page', __('Page'))
        ->width('20%')
        ->format(function ($section) {
            $pages = [
                '0'      => __('All Pages'),
                '1'      => __('First Page'),
                '-1'     => __('Last Page'),
            ];
            return $pages[$section['page']] ?? $section['page'];
        });

    $table->addActionColumn()
        ->addParam('gibbonReportTemplateID', $gibbonReportTemplateID)
        ->addParam('gibbonReportTemplateSectionID')
        ->format(function ($template, $actions) {
            $actions->addAction('edit', __('Edit'))
                ->setURL('/modules/Reports/templates_manage_section_edit.php');

            $actions->addAction('delete', __('Delete'))
                ->setURL('/modules/Reports/templates_manage_section_delete.php');
        });

    // BODY
    $bodySections = $templateSectionGateway->querySectionsByType($criteria, $gibbonReportTemplateID, 'Body');
    $bodyTable = clone $table;
    $bodyTable->setTitle(__('Body'));
    $bodyTable->setID('bodyTable');
    $bodyTable->removeColumn('page');

    // HEADERS
    $headerSections = $templateSectionGateway->querySectionsByType($criteria, $gibbonReportTemplateID, 'Header');
    $headerTable = clone $table;
    $headerTable->setTitle(__('Header'));
    $headerTable->setID('headerTable');

    // FOOTER
    $footerSections = $templateSectionGateway->querySectionsByType($criteria, $gibbonReportTemplateID, 'Footer');
    $footerTable = clone $table;
    $footerTable->setTitle(__('Footer'));
    $footerTable->setID('footerTable');

    // PROTOTYPE
    $prototypeCoreSections = $prototypeSectionGateway->selectPrototypeSections('Core')->fetchGrouped();
    $prototypeAdditionalSections = $prototypeSectionGateway->selectPrototypeSections('Additional')->fetchGrouped();

    // SETTINGS FORM
    // $form = Form::create('settings', $session->get('absoluteURL').'/modules/Reports/templates_manage_editProcess.php?search='.$search);

    // $form->addHiddenValue('address', $session->get('address'));
    // $form->addHiddenValue('gibbonReportTemplateID', $gibbonReportTemplateID);

    // $fonts = ['Helvetica', 'Arial', 'Times New Roman'];
    // $row = $form->addRow();
    //     $row->addLabel('font', __('Font'));
    //     $row->addSelect('font')->fromArray($fonts);

    // $row = $form->addRow();
    //     $row->addLabel('size', __('Size'));
    //     $row->addNumber('size');

    // $row = $form->addRow();
    //     $row->addLabel('color', __('Color'));
    //     $row->addTextField('color');

    // $row = $form->addRow();
    //     $row->addSubmit();


    echo $page->fetchFromTemplate('ui/templateBuilder.twig.html', [
        'gibbonReportTemplateID' => $gibbonReportTemplateID,
        'template' => $values,
        // 'form'     => $form->getOutput(),
        'headers'  => $headerTable->render($headerSections),
        'body'     => $bodyTable->render($bodySections),
        'footers'  => $footerTable->render($footerSections),
        'coreSections' => $prototypeCoreSections,
        'additionalSections' => $prototypeAdditionalSections,
    ]);
}
