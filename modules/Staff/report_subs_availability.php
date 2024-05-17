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
use Gibbon\Services\Format;
use Gibbon\Domain\DataSet;
use Gibbon\Domain\Staff\SubstituteGateway;
use Gibbon\Module\Staff\Tables\CoverageMiniCalendar;
use Gibbon\Domain\School\DaysOfWeekGateway;
use Gibbon\Domain\System\SettingGateway;

if (isActionAccessible($guid, $connection2, '/modules/Staff/report_subs_availability.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Substitute Availability'), 'report_subs_availability.php')
        ->add(__('Daily'));

    $subGateway = $container->get(SubstituteGateway::class);
    $settingGateway = $container->get(SettingGateway::class);

    $date = isset($_GET['date']) ? Format::dateConvert($_GET['date']) : date('Y-m-d');
    $dateObject = new DateTimeImmutable($date);
    $dateFormat = $session->get('i18n')['dateFormatPHP'];

    $allDay = $_GET['allDay'] ?? null;
    $timeStart = $_GET['timeStart'] ?? null;
    $timeEnd = $_GET['timeEnd'] ?? null;
    $allStaff = $_GET['allStaff'] ?? $settingGateway->getSettingByScope('Staff', 'coverageInternal');

    // CRITERIA
    $criteria = $subGateway->newQueryCriteria(true)
        ->sortBy('gibbonSubstitute.priority', 'DESC')
        ->sortBy(['surname', 'preferredName'])
        ->filterBy('showUnavailable', 'true')
        ->filterBy('allStaff', $allStaff)
        ->fromPOST();

    $form = Form::create('searchForm', $session->get('absoluteURL').'/index.php', 'get');
    $form->setTitle(__('Filter'));

    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('address', $session->get('address'));
    $form->addHiddenValue('sidebar', $_GET['sidebar'] ?? '');
    $form->addHiddenValue('q', '/modules/'.$session->get('module').'/report_subs_availability.php');

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

    $row = $form->addRow();
        $row->addLabel('allStaff', __('All Staff'))->description(__('Include all teaching staff.'));
        $row->addCheckbox('allStaff')->checked($allStaff)->setValue('Y');

    $row = $form->addRow();
        $row->addFooter();
        $row->addSearchSubmit($session);

    echo $form->getOutput();

    if (!isSchoolOpen($guid, $date, $connection2)) {
        echo Format::alert(__('School is closed on the specified day.'), 'error');
        return;
    }

    $subs = $subGateway->queryAvailableSubsByDate($criteria, $date, $timeStart, $timeEnd);

    $availability = $subGateway->selectUnavailableDatesByDateRange($date, $date)->fetchGrouped();

    $subs->transform(function (&$sub) use (&$availability) {
        $sub['dates'] = $availability[intval($sub['gibbonPersonID'])] ?? [];
    });

    $dayOfWeek = $container->get(DaysOfWeekGateway::class)->getDayOfWeekByDate($date);

    // DATA TABLE
    $table = DataTable::createPaginated('subsManage', $criteria);
    $table->setTitle(__('Substitute Availability'));
    $table->setDescription(Format::dateReadable($dateObject->format('Y-m-d'), Format::FULL));

    $table->addHeaderAction('calendar', __('Weekly').' '.__('View'))
        ->setIcon('planner')
        ->setURL('/modules/Staff/report_subs_availabilityWeekly.php')
        ->addParam('sidebar', 'false')
        ->addParam('allStaff', $allStaff)
        ->addParam('date', Format::date($date))
        ->displayLabel();

    $table->modifyRows(function ($values, $row) {
        if ($values['available'] == false) $row->addClass('error');
        return $row;
    });

    // COLUMNS
    $table->addColumn('image_240', __('Photo'))
        ->context('primary')
        ->width('10%')
        ->notSortable()
        ->format(Format::using('userPhoto', 'image_240'));

    $canManageCoverage = isActionAccessible($guid, $connection2, '/modules/Staff/coverage_manage.php');
    $table->addColumn('fullName', __('Name'))
        ->context('primary')
        ->description(__('Priority'))
        ->sortable(['surname', 'preferredName'])
        ->format(function ($person) use ($canManageCoverage) {
            $name = Format::name($person['title'], $person['preferredName'], $person['surname'], 'Staff', true, true);
            if ($canManageCoverage) {
                $url = './index.php?q=/modules/Staff/coverage_manage.php&search='.$person['username'].'+date:upcoming';
            } elseif (!empty($person['gibbonStaffID'])) {
                $url = './index.php?q=/modules/Staff/staff_view_details.php&gibbonPersonID='.$person['gibbonPersonID'];
            } else {
                $url = '';
            }

            return Format::link($url, $name).'<br/>'.Format::small($person['type']);
        });

    $table->addColumn('details', __('Details'));

    $table->addColumn('contact', __('Contact'))
        ->description(__('Availability'))
        ->context('primary')
        ->notSortable()
        ->format(function ($person) use ($dateObject, $dayOfWeek) {
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

                $output .= '<br/>';
                $output .= CoverageMiniCalendar::renderTimeRange($dayOfWeek, $person['dates'] ?? [], $dateObject);
            }



            return $output;
        });

    echo $table->render($subs);
}
