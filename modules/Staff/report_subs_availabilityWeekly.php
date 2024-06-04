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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\Form;
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\DataSet;
use Gibbon\Domain\Staff\SubstituteGateway;
use Gibbon\Module\Staff\Tables\CoverageMiniCalendar;
use Gibbon\Domain\School\DaysOfWeekGateway;

if (isActionAccessible($guid, $connection2, '/modules/Staff/report_subs_availability.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__('Substitute Availability'), 'report_subs_availability.php')
        ->add(__('Weekly'));

    $subGateway = $container->get(SubstituteGateway::class);
    $settingGateway = $container->get(SettingGateway::class);

    $date = isset($_REQUEST['date']) ? Format::dateConvert($_REQUEST['date']) : date('Y-m-d');
    $dateObject = new DateTimeImmutable($date);
    $dateFormat = $session->get('i18n')['dateFormatPHP'];
    $allStaff = $_GET['allStaff'] ?? $settingGateway->getSettingByScope('Staff', 'coverageInternal');

    // DATE SELECTOR
    $form = Form::create('action', $session->get('absoluteURL').'/index.php?q=/modules/Staff/report_subs_availabilityWeekly.php&sidebar=false');
    $form->setClass('blank fullWidth');
    $form->addHiddenValue('address', $session->get('address'));

    $row = $form->addRow()->addClass('flex flex-wrap');

    $link = $session->get('absoluteURL').'/index.php?q=/modules/Staff/report_subs_availabilityWeekly.php&sidebar=false';

    $lastWeek = $dateObject->modify('-1 week')->format($dateFormat);
    $thisWeek = (new DateTimeImmutable('Today'))->format($dateFormat);
    $nextWeek = $dateObject->modify('+1 week')->format($dateFormat);

    $col = $row->addColumn()->setClass('flex-1 flex items-center ');
        $col->addButton(__('Last Week'))->addClass('rounded-l-sm')->onClick("window.location.href='{$link}&date={$lastWeek}&allStaff={$allStaff}'");
        $col->addButton(__('This Week'))->addClass('ml-px')->onClick("window.location.href='{$link}&date={$thisWeek}&allStaff={$allStaff}'");
        $col->addButton(__('Next Week'))->addClass('ml-px rounded-r-sm')->onClick("window.location.href='{$link}&date={$nextWeek}&allStaff={$allStaff}'");

    $col = $row->addColumn()->addClass('flex items-center justify-end');
        $col->addCheckbox('allStaff')->description(__('All Staff'))->setValue('Y')->checked($allStaff)->setClass('mr-4');
        $col->addDate('date')->setValue($dateObject->format($dateFormat))->setClass('shortWidth');
        $col->addSubmit(__('Go'));

    // DATA
    $firstDayOfTheWeek = $container->get(SettingGateway::class)->getSettingByScope('System', 'firstDayOfTheWeek');
    $dateStart = $dateObject->modify($firstDayOfTheWeek == 'Monday' ? "Monday this week" : "Sunday last week");
    $dateEnd = $dateObject->modify($firstDayOfTheWeek == 'Monday' ? "Monday next week" : "Sunday this week");

    $criteria = $subGateway->newQueryCriteria()
        ->sortBy('priority', 'DESC')
        ->sortBy(['type', 'surname', 'preferredName'])
        ->filterBy('active', 'Y')
        ->filterBy('status', 'Full')
        ->filterBy('allStaff', $allStaff)
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
        if (!isset($group[$gibbonPersonID])) return $group;

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

    $canManageCoverage = isActionAccessible($guid, $connection2, '/modules/Staff/coverage_manage.php');
    $table->addColumn('fullName', __('Name'))
        ->context('primary')
        ->description(__('Type'))
        ->width('10%')
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
            return Format::link($url, $name).'<br/>'.Format::small(!empty($person['type']) ? $person['type'] : $person['jobTitle']);
        });

    $dateRange = new DatePeriod($dateStart, new DateInterval('P1D'), $dateEnd);
    $daysOfWeekGateway = $container->get(DaysOfWeekGateway::class);

    foreach ($dateRange as $weekday) {
        if (!isSchoolOpen($guid, $weekday->format('Y-m-d'), $connection2)) continue;

        $dayOfWeek = $daysOfWeekGateway->getDayOfWeekByDate($weekday->format('Y-m-d'));

        $url = './index.php?q=/modules/Staff/report_subs_availability.php&date='.Format::date($weekday->format('Y-m-d'));
        $columnTitle = Format::link($url, Format::dateReadable($weekday->format('Y-m-d'), Format::FULL_NO_YEAR));

        $table->addColumn($weekday->format('D'), $columnTitle)
            ->context('primary')
            ->notSortable()
            ->description(Format::date($weekday->format('Y-m-d')))
            ->format(function ($values) use ($weekday, $dayOfWeek) {
                return CoverageMiniCalendar::renderTimeRange($dayOfWeek, $values['dates'][$weekday->format('Y-m-d')] ?? [], $weekday);
            })
            ->modifyCells(function ($values, $cell) use ($weekday) {
                if ($weekday->format('Y-m-d') == date('Y-m-d')) $cell->addClass('bg-yellow-100');
                return $cell;
            });
    }

    echo $table->render(new DataSet($subsAvailability));
}
