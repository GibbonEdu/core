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

if (isActionAccessible($guid, $connection2, '/modules/Reports/templates_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $search = $_GET['search'] ?? '';

    $page->breadcrumbs
        ->add(__('Template Builder'), 'templates_manage.php', ['search' => $search])
        ->add(__('Add Template'));

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $gibbon->session->get('absoluteURL').'/index.php?q=/modules/Reports/templates_manage_edit.php&sidebar=false&gibbonReportTemplateID='.$_GET['editID'].'&search='.$_GET['search'];
    }
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    if ($search != '') {
        echo "<div class='linkTop'>";
        echo "<a href='".$gibbon->session->get('absoluteURL')."/index.php?q=/modules/Reports/templates_manage.php&search=$search'>".__('Back to Search Results').'</a>';
        echo '</div>';
    }

    $form = Form::create('templatesManage', $gibbon->session->get('absoluteURL').'/modules/Reports/templates_manage_addProcess.php?search='.$search);

    $form->addHiddenValue('address', $gibbon->session->get('address'));

    $form->addRow()->addHeading(__('Basic Information'));

    $row = $form->addRow();
        $row->addLabel('name', __('Name'))->description(__('Must be unique'));
        $row->addTextField('name')->maxLength(90)->required();

    $contexts = [
        'Student Enrolment' => __('Student Enrolment'),
        'Reporting Cycle'   => __('Reporting Cycle'),
        // 'Custom Query' => __('Custom Query'),
    ];
    $row = $form->addRow();
        $row->addLabel('context', __('Context'));
        $row->addSelect('context')->fromArray($contexts)->required()->placeholder();
    
    $form->addRow()->addHeading(__('Document Setup'));

    $orientations = ['P' => __('Portrait'), 'L' => __('Landscape')];
    $row = $form->addRow();
        $row->addLabel('orientation', __('Orientation'));
        $row->addSelect('orientation')->fromArray($orientations)->required();

    $pageSizes = ['A4' => __('A4'), 'letter' => __('US Letter')];
    $row = $form->addRow();
        $row->addLabel('pageSize', __('Page Size'));
        $row->addSelect('pageSize')->fromArray($pageSizes)->required();

    $row = $form->addRow();
        $row->addLabel('margins', __('Margins'));
        $col = $row->addColumn()->addClass('items-center');
        $col->addContent('<div class="flex-1 pr-1">X</div>');
        $col->addNumber('marginX')->decimalPlaces(2)->required()->setValue('10');
        $col->addContent('<div class="flex-1 pr-1 pl-2">Y</div>');
        $col->addNumber('marginY')->decimalPlaces(2)->required()->setValue('10');
        
    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
