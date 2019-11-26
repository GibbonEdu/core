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
use Gibbon\Services\Format;
use Gibbon\Domain\Staff\StaffGateway;
use Gibbon\Tables\View\GridView;

if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_directory.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('Staff Directory'));

    $search =  $_GET['search'] ?? '';
    $view =  $_GET['view'] ?? '';

    $staffGateway = $container->get(StaffGateway::class);

    // QUERY
    $criteria = $staffGateway->newQueryCriteria()
        ->searchBy($staffGateway->getSearchableColumns(), $search)
        ->sortBy(['biographicalGrouping', 'biographicalGroupingPriority', 'surname', 'preferredName'])
        ->fromPOST();

    $form = Form::create('action', $_SESSION[$guid]['absoluteURL']."/index.php", 'get');
    $form->setTitle(__('Search'));

    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
    $form->addHiddenValue('q', "/modules/".$_SESSION[$guid]['module']."/staff_directory.php");

    $row = $form->addRow();
        $row->addLabel('search', __('Search For'))->description(__('Preferred, surname, username.'));
        $row->addTextField('search')->setValue($criteria->getSearchText())->maxLength(20);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSearchSubmit($gibbon->session);

    echo $form->getOutput();

    $staff = $staffGateway->queryAllStaff($criteria, $gibbon->session->get('gibbonSchoolYearID'));

    // DATA TABLE
    $table = DataTable::createPaginated('staffDirectory', $criteria);
    $table->setTitle(__('Staff Directory'));

    
    $lastGroup = '';
    $table->modifyRows(function ($data, $row, $columnCount) use (&$lastGroup, &$view) {
        if ($lastGroup != $data['biographicalGrouping']) {
            $grouping = $view == 'grid'
                ? '<div class="w-full bg-gray-300 font-bold p-4">'.$data['biographicalGrouping'].'</div>'
                : '<tr class="bg-gray-300 font-bold"><td colspan="'.$columnCount.'">'.$data['biographicalGrouping'].'</td></tr>';
            $row->prepend($grouping);
            $lastGroup = $data['biographicalGrouping'];
        }
        return $row;
    });

    $table->addMetaData('filterOptions', [
        'type:teaching' => __('Staff Type').': '.__('Teaching'),
        'type:support'  => __('Staff Type').': '.__('Support'),
        'type:other'    => __('Staff Type').': '.__('Other'),
    ]);

    $table->addMetaData('listOptions', [
        'list' => __('List'),
        'grid' => __('Grid'),
    ]);

    // COLUMNS

    if ($view == 'grid') {
        $table->setRenderer(new GridView($container->get('twig')));
        $table->getRenderer()->setCriteria($criteria);

        $table->addMetaData('gridClass', 'rounded-sm bg-gray-100 border py-2');
        $table->addMetaData('gridItemClass', 'w-1/2 sm:w-1/4 md:w-1/5 my-4 text-center text-xs');

        $table->addColumn('image_240', __('Photo'))
            ->context('primary')
            ->notSortable()
            ->format(Format::using('userPhoto', ['image_240', 'sm']));
    }

    $table->addColumn('fullName', __('Name'))
        ->description(__('Job Title'))
        ->sortable(['surname', 'preferredName'])
        ->width('20%')
        ->format(function ($person) {
            $text = Format::name($person['title'], $person['preferredName'], $person['surname'], 'Staff', true, true);
            $url = './index.php?q=/modules/Staff/staff_view_details.php&gibbonPersonID='.$person['gibbonPersonID'];
            return Format::link($url, $text, ['class' => 'font-bold underline leading-normal']).'<br/>'.
                   Format::small(!empty($person['jobTitle']) ? $person['jobTitle'] : $person['type']);
        });

    if ($view == 'list') {
        $table->addColumn('type', __('Type'));
        $table->addColumn('department', __('Department'));
        $table->addColumn('facility', __('Facility'))->width('5%');
        $table->addColumn('extension', __('Extension'))->width('5%');
        $table->addColumn('email', __('Email'));
        $table->addColumn('phone', __('Phone'))->format(function ($person) {
            $output = '';
            if (!empty($person['phone1'])) {
                $output .= Format::tooltip($person['phone1'].' ('.$person['phone1Type'].')', Format::phone($person['phone1'], $person['phone1CountryCode'], $person['phone1Type'])).'<br/>';
            }
            if (!empty($person['phone2'])) {
                $output .= Format::tooltip($person['phone2'].' ('.$person['phone2Type'].')', Format::phone($person['phone2'], $person['phone2CountryCode'], $person['phone2Type'])).'<br/>';
            }
            return $output;
        });
    }

    echo $table->render($staff);
}
