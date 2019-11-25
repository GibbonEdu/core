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

if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_directory.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs->add(__('Staff Directory'));

    $search = (isset($_GET['search']) ? $_GET['search'] : '');
    $allStaff = (isset($_GET['allStaff']) ? $_GET['allStaff'] : '');

    $staffGateway = $container->get(StaffGateway::class);

    // QUERY
    $criteria = $staffGateway->newQueryCriteria()
        ->searchBy($staffGateway->getSearchableColumns(), $search)
        ->filterBy('all', $allStaff)
        ->sortBy(['surname', 'preferredName'])
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


    $staff = $staffGateway->queryStaffDirectory($criteria, $gibbon->session->get('gibbonSchoolYearID'));

    // DATA TABLE
    $table = DataTable::createPaginated('staffDirectory', $criteria);
    $table->setTitle(__('Staff Directory'));

    $table->addMetaData('filterOptions', [
        'all:on'        => __('All Staff'),
        'type:teaching' => __('Staff Type').': '.__('Teaching'),
        'type:support'  => __('Staff Type').': '.__('Support'),
        'type:other'    => __('Staff Type').': '.__('Other'),
    ]);

    // COLUMNS
    $table->addColumn('fullName', __('Name'))
        ->description(__('Job Title'))
        ->sortable(['surname', 'preferredName'])
        ->width('20%')
        ->format(function ($person) {
            $text = Format::name($person['title'], $person['preferredName'], $person['surname'], 'Staff', true, true);
            $url = './index.php?q=/modules/Staff/staff_view.php&gibbonPersonID='.$person['gibbonPersonID'];
            return Format::link($url, $text).'<br/>'.Format::small(!empty($person['jobTitle']) ? $person['jobTitle'] : $person['type']);
        });

    // $table->addColumn('jobTitle', __('Job Title'))
    //     ->description(__('Type'))
    //     ->format(function($person) {
    //         return $person['jobTitle'].'<br/>'.Format::small(__($person['type']));
    //     });
    $table->addColumn('department', __('Department'));
    $table->addColumn('facility', __('Facility'));
    $table->addColumn('extension', __('Extension'));
    $table->addColumn('email', __('Email'));
    $table->addColumn('phone', __('Phone'))->format(function($person) {
        $output = '';
        if (!empty($person['phone1'])) {
            $output .= Format::tooltip($person['phone1'].' ('.$person['phone1Type'].')', Format::phone($person['phone1'], $person['phone1CountryCode'], $person['phone1Type'])).'<br/>';
        }
        if (!empty($person['phone2'])) {
            $output .= Format::tooltip($person['phone2'].' ('.$person['phone2Type'].')', Format::phone($person['phone2'], $person['phone2CountryCode'], $person['phone2Type'])).'<br/>';
        }
        return $output;
    });

    // // ACTIONS
    // $table->addActionColumn()
    //     ->addParam('gibbonPersonID')
    //     ->addParam('allStaff', $allStaff)
    //     ->addParam('search', $criteria->getSearchText(true))
    //     ->format(function ($person, $actions) use ($guid) {
    //         $actions->addAction('view', __('View Details'))
    //                 ->setURL('/modules/Staff/staff_view_details.php');
    //     });

    echo $table->render($staff);
    
}
