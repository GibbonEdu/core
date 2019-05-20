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
use Gibbon\Domain\Staff\SubstituteGateway;
use Gibbon\Domain\DataSet;

if (isActionAccessible($guid, $connection2, '/modules/Staff/report_subs_availability.php') == false) {
    // Access denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    // Proceed!
    $page->breadcrumbs->add(__('Substitute Availability'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $date = isset($_GET['date']) ? Format::dateConvert($_GET['date']) : date('Y-m-d');
    $allDay = $_GET['allDay'] ?? null;
    $timeStart = $_GET['timeStart'] ?? null;
    $timeEnd = $_GET['timeEnd'] ?? null;
    $allStaff = $_GET['allStaff'] ?? false;

    $subGateway = $container->get(SubstituteGateway::class);

    // CRITERIA
    $criteria = $subGateway->newQueryCriteria()
        ->sortBy('gibbonSubstitute.priority', 'DESC')
        ->sortBy(['surname', 'preferredName'])
        ->filterBy('showUnavailable', 'true')
        ->filterBy('allStaff', $allStaff)
        ->fromPOST();

    $form = Form::create('searchForm', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
    $form->setTitle(__('Filter'));

    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
    $form->addHiddenValue('q', '/modules/'.$_SESSION[$guid]['module'].'/report_subs_availability.php');

    $row = $form->addRow();
        $row->addLabel('date', __('Date'));
        $row->addDate('date')->setValue(Format::date($date));

    $allDayOptions = [
        'Y' => __('All Day'),
        'N' => __('Time Span'),
    ];
    $row = $form->addRow();
        $row->addLabel('allDay', __('When'));
        $row->addSelect('allDay')->fromArray($allDayOptions)->selected($allDay);
    
    $form->toggleVisibilityByClass('timeOptions')->onSelect('allDay')->when('N');

    $row = $form->addRow()->addClass('timeOptions');
        $row->addLabel('timeStart', __('Time'));
        $col = $row->addColumn('timeStart')->addClass('right inline');
        $col->addTime('timeStart')
            ->setClass('shortWidth')
            ->isRequired()
            ->setValue($timeStart);
        $col->addTime('timeEnd')
            ->chainedTo('timeStart')
            ->setClass('shortWidth')
            ->isRequired()
            ->setValue($timeEnd);

    if (isActionAccessible($guid, $connection2, '/modules/Staff/substitutes_manage.php')) {
        $row = $form->addRow();
            $row->addLabel('allStaff', __('All Staff'))->description(__('Include all teaching staff.'));
            $row->addCheckbox('allStaff')->checked($allStaff);
    }

    $row = $form->addRow();
        $row->addFooter();
        $row->addSearchSubmit($gibbon->session);

    echo $form->getOutput();

    if (!isSchoolOpen($guid, $date, $connection2)) {
        echo Format::alert(__('School is closed on the specified day.'), 'error');
        return;
    }

    $subs = $subGateway->queryAvailableSubsByDate($criteria, $date, $timeStart, $timeEnd);

    
    // DATA TABLE
    $table = DataTable::createPaginated('subsManage', $criteria);
    $table->setTitle(__('Substitute Availability'));

    $table->modifyRows(function ($values, $row) {
        if ($values['available'] == false) $row->addClass('error');
        return $row;
    });

    // COLUMNS
    $table->addColumn('image_240', __('Photo'))
        ->width('10%')
        ->notSortable()
        ->format(Format::using('userPhoto', 'image_240'));

    $table->addColumn('fullName', __('Name'))
        ->description(__('Priority'))
        ->sortable(['surname', 'preferredName'])
        ->format(function ($person) use ($guid) {
            $name = Format::name($person['title'], $person['preferredName'], $person['surname'], 'Staff', true, true);
            $url = !empty($person['gibbonStaffID'])
                ? $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Staff/staff_view_details.php&gibbonPersonID='.$person['gibbonPersonID']
                : '';

            return Format::link($url, $name).'<br/>'.Format::small($person['type']);
        });

    $table->addColumn('details', __('Details'));

    $table->addColumn('contact', __('Contact'))
        ->notSortable()
        ->format(function ($person) {
            $output = '';

            if ($person['available']) {
                if (!empty($person['email'])) {
                    $output .= $person['email'].'<br/>';
                }
                if (!empty($person['phone1'])) {
                    $output .= Format::phone($person['phone1'], $person['phone1CountryCode'], $person['phone1Type']).'<br/>';
                }
            } else {
                $reason = '';
                if (!empty($person['absence'])) $reason .= __('Absent').' - '.$person['absence'].'<br/>';
                if (!empty($person['coverage'])) $reason .= __('Covering').' - '.$person['coverage'].'<br/>';
                if (!empty($person['timetable'])) $reason .= __('Teaching').' - '.$person['timetable'].'<br/>';
                if (!empty($person['unavailable'])) $reason .= __($person['unavailable']).'<br/>';

                $output .= !empty($reason)? $reason : __('Not Available');
            }
            return $output;
        });

    echo $table->render($subs);
}
