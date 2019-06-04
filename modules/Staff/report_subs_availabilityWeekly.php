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
use Gibbon\Domain\DataSet;
use Gibbon\Domain\Staff\SubstituteGateway;

if (isActionAccessible($guid, $connection2, '/modules/Staff/report_subs_availability.php') == false) {
    // Access denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Substitute Availability'), 'report_subs_availability.php')
        ->add(__('Weekly'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $date = isset($_GET['date']) ? Format::dateConvert($_GET['date']) : date('Y-m-d');
    $dateObject = new DateTimeImmutable($date);
    $dateFormat = $_SESSION[$guid]['i18n']['dateFormatPHP'];

    $subGateway = $container->get(SubstituteGateway::class);
    
    // DATE SELECTOR
    $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Staff/report_subs_availabilityWeekly.php&sidebar=false');
    $form->setClass('blank fullWidth');
    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $row = $form->addRow()->addClass('flex flex-wrap');

    $link = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Staff/report_subs_availabilityWeekly.php&sidebar=false';

    $lastWeek = $dateObject->modify('-1 week')->format($dateFormat);
    $thisWeek = (new DateTimeImmutable('Today'))->format($dateFormat);
    $nextWeek = $dateObject->modify('+1 week')->format($dateFormat);

    $col = $row->addColumn()->addClass('flex items-center ');
        $col->addButton(__('Last Week'))->addClass('')->onClick("window.location.href='{$link}&date={$lastWeek}'");
        $col->addButton(__('This Week'))->addClass('ml-px')->onClick("window.location.href='{$link}&date={$thisWeek}'");
        $col->addButton(__('Next Week'))->addClass('ml-px')->onClick("window.location.href='{$link}&date={$nextWeek}'");

    $col = $row->addColumn()->addClass('flex items-center justify-end');
        $col->addDate('date')->setValue($dateObject->format($dateFormat))->setClass('shortWidth');
        $col->addSubmit(__('Go'));

    // DATA
    $firstDayOfTheWeek = getSettingByScope($connection2, 'System', 'firstDayOfTheWeek');
    $dateStart = $dateObject->modify($firstDayOfTheWeek == 'Monday' ? "Monday this week" : "Sunday last week");
    $dateEnd = $dateObject->modify($firstDayOfTheWeek == 'Monday' ? "Monday next week" : "Sunday this week");

    $criteria = $subGateway->newQueryCriteria()
        ->sortBy('priority', 'DESC')
        ->sortBy(['type', 'surname', 'preferredName'])
        ->filterBy('active', 'Y')
        ->fromPOST();

    // Get all subs
    $subs = $subGateway->queryAllSubstitutes($criteria)->toArray();
    $subs = array_reduce($subs, function ($group, $item) {
        $group[$item['gibbonPersonID']] = $item;
        return $group;
    }, []);

    // Attach availability info to each sub
    $availability = $subGateway->selectUnavailableDatesByDateRange($dateStart->format('Y-m-d'), $dateEnd->format('Y-m-d'))->fetchAll();
    $subsAvailability = array_reduce($availability, function ($group, $item) {
        $gibbonPersonID = str_pad($item['gibbonPersonID'], 10, '0', STR_PAD_LEFT);
        $group[$gibbonPersonID]['dates'][$item['date']][] = $item;
        return $group;
    }, $subs);

    // CALENDAR VIEW
    $table = DataTable::createPaginated('subsManage', $criteria);
    $table->setTitle(__('Substitute Availability'));
    $table->setDescription($form->getOutput());

    $table->addHeaderAction('daily', __('Daily').' '.__('View'))
        ->setIcon('rubric')
        ->setURL('/modules/Staff/report_subs_availability.php')
        ->addParam('date', Format::date($date))
        ->displayLabel();

    $table->addColumn('image_240', __('Photo'))
        ->context('primary')
        ->width('6%')
        ->notSortable()
        ->format(Format::using('userPhoto', ['image_240', '125', 'w-12 p-px']));

    $table->addColumn('fullName', __('Name'))
        ->context('primary')
        ->description(__('Type'))
        ->width('10%')
        ->sortable(['surname', 'preferredName'])
        ->format(function ($person) {
            $name = Format::name($person['title'], $person['preferredName'], $person['surname'], 'Staff', true, true);
            $url = './index.php?q=/modules/Staff/coverage_manage.php&search='.$person['username'].'+date:upcoming';

            return Format::link($url, $name).'<br/>'.Format::small($person['type']);
        });

    $dateRange = new DatePeriod($dateStart, new DateInterval('P1D'), $dateEnd);

    foreach ($dateRange as $weekday) {
        if (!isSchoolOpen($guid, $weekday->format('Y-m-d'), $connection2)) continue;

        $url = './index.php?q=/modules/Staff/report_subs_availability.php&date='.Format::date($weekday);
        $columnTitle = Format::link($url, Format::dateReadable($weekday->format('Y-m-d'), '%a, %b %e'));

        $table->addColumn($weekday->format('D'), $columnTitle)
            ->context('primary')
            ->notSortable()
            ->description(Format::date($weekday))
            ->format(function ($values) use ($weekday) {
                $availabilityByDate = $values['dates'][$weekday->format('Y-m-d')] ?? [];

                $title = '';
                foreach ($availabilityByDate as $availability) {
                    $title .= $availability['status'].': ';
                    $title .= $availability['allDay'] == 'N'
                        ? Format::timeRange($availability['timeStart'], $availability['timeEnd'])
                        : __('All Day');
                    $title .= '<br/>';
                }

                $output = '<div class="flex h-12 border" style="min-width: 8rem;" title="'.$title.'">';

                $timeRange = new DatePeriod($weekday->modify('8:30am'), new DateInterval('PT10M'), $weekday->modify('4pm'));

                foreach ($timeRange as $time) {
                    $class = 'bg-white';

                    $timeStart = $time->format('H:i:s');
                    $timeEnd = $time->modify('+9 minutes')->format('H:i:s');

                    foreach ($availabilityByDate as $availability) {
                        switch ($availability['status']) {
                            case 'Not Available':   $highlight = 'bg-gray-500'; break;
                            case 'Absent':          $highlight = 'bg-gray-500'; break;
                            case 'Teaching':        $highlight = 'bg-blue-500'; break;
                            default:                $highlight = 'bg-purple-500';
                        }

                        if ($availability['allDay'] == 'Y') $class = $highlight;
                        if ($timeStart <= $availability['timeEnd'] && $timeEnd >= $availability['timeStart']) $class = $highlight;
                    }
                    $output .= '<div class="flex-1 '.$class.'"></div>';
                }
                $output .= '</div>';

                return $output;
            })
            ->modifyCells(function ($values, $cell) use ($weekday) {
                if ($weekday->format('Y-m-d') == date('Y-m-d')) $cell->addClass('bg-yellow-100');
                return $cell;
            });
    }

    echo $table->render(new DataSet($subsAvailability));
}
