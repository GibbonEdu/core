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
use Gibbon\Domain\DataSet;
use Gibbon\Tables\DataTable;
use Gibbon\Tables\Prefab\ReportTable;
use Gibbon\Domain\School\SchoolYearGateway;
use Gibbon\Domain\Staff\StaffAbsenceGateway;
use Gibbon\Domain\Staff\StaffAbsenceTypeGateway;
use Gibbon\Domain\Staff\StaffGateway;
use Gibbon\Services\Format;

if (isActionAccessible($guid, $connection2, '/modules/Staff/report_absences_summary.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $viewMode = $_REQUEST['format'] ?? '';
    $dateFormat = $session->get('i18n')['dateFormatPHP'];
    $gibbonSchoolYearID = $session->get('gibbonSchoolYearID');
    $gibbonStaffAbsenceTypeID = $_GET['gibbonStaffAbsenceTypeID'] ?? '';
    $month = $_GET['month'] ?? '';

    $schoolYearGateway = $container->get(SchoolYearGateway::class);
    $staffAbsenceGateway = $container->get(StaffAbsenceGateway::class);
    $staffAbsenceTypeGateway = $container->get(StaffAbsenceTypeGateway::class);

    // ABSENCE DATA
    $status = $_GET['status'] ?? 'Full';
    $criteria = $staffAbsenceGateway->newQueryCriteria()
        ->filterBy('type', $gibbonStaffAbsenceTypeID)
        ->filterBy('all', $status == 'on')
        ->fromPOST();

    $schoolYear = $schoolYearGateway->getSchoolYearByID($gibbonSchoolYearID);
    $nextSchoolYear = $schoolYearGateway->getNextSchoolYearByID($gibbonSchoolYearID);


    // Setup the date range for this school year
    $dateStart = new DateTime(substr($schoolYear['firstDay'], 0, 7).'-01');
    $dateEnd = !empty($nextSchoolYear) ? new DateTime($nextSchoolYear['firstDay']) : new DateTime($schoolYear['lastDay']);
    $dateEnd->modify('last day of this month');

    $months = [];
    $dateRange = new DatePeriod($dateStart, new DateInterval('P1M'), $dateEnd);

    // Translated array of months in the current school year
    foreach ($dateRange as $monthDate) {
        $months[$monthDate->format('Y-m-d')] = Format::monthName($monthDate->format('Y-m-d')).' '.$monthDate->format('Y');
    }

    // Setup the date range used for this report
    if (!empty($month)) {
        $monthDate = new DateTimeImmutable($month);
        $dateStart = $monthDate->modify('first day of this month');
        $dateEnd = $monthDate->modify('last day of this month');

        $dateRange = new DatePeriod($dateStart, new DateInterval('P1M'), $dateEnd);
    } else {
        $dateStart = new DateTime($schoolYear['firstDay']);
    }

    $absences = $staffAbsenceGateway->queryApprovedAbsencesByDateRange($criteria, $dateStart->format('Y-m-d'), $dateEnd->format('Y-m-d'), false);


    if (empty($viewMode)) {
        $page->breadcrumbs->add(__('Staff Absence Summary'));

        $form = Form::create('filter', $session->get('absoluteURL').'/index.php', 'get');
        $form->setTitle(__('Filter'));
        $form->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('address', $session->get('address'));
        $form->addHiddenValue('q', '/modules/Staff/report_absences_summary.php');

        $types = $staffAbsenceTypeGateway->selectAllTypes()->fetchAll();
        $types = array_combine(array_column($types, 'gibbonStaffAbsenceTypeID'), array_column($types, 'name'));

        $row = $form->addRow();
        $row->addLabel('gibbonStaffAbsenceTypeID', __('Type'));
        $row->addSelect('gibbonStaffAbsenceTypeID')
                ->fromArray(['' => __('All')])
                ->fromArray($types)
                ->selected($gibbonStaffAbsenceTypeID);

        $row = $form->addRow();
            $row->addLabel('month', __('Month'));
            $row->addSelect('month')->fromArray(['' => __('All')])->fromArray($months)->selected($month);
        
        $row = $form->addRow();
            $row->addLabel('Status', __('All Staff'))->description(__('Include all staff, regardless of status and current employment.'));
            $row->addCheckbox('status')->setValue('on')->checked($status);

        $row = $form->addRow();
        $row->addFooter();
        $row->addSearchSubmit($session);

        echo $form->getOutput();


        // CALENDAR DATA
        $absencesByDate = array_reduce($absences->toArray(), function ($group, $item) {
            $group[$item['date']][] = $item;
            return $group;
        }, []);

        $calendar = [];
        $totalAbsence = 0;
        $maxAbsence = 0;

        foreach ($dateRange as $monthDate) {
            $days = [];
            for ($dayCount = 1; $dayCount <= $monthDate->format('t'); $dayCount++) {
                $date = new DateTime($monthDate->format('Y-m').'-'.$dayCount);
                $absenceCount = count($absencesByDate[$date->format('Y-m-d')] ?? []);

                $days[$dayCount] = [
                    'date'    => $date,
                    'number'  => $dayCount,
                    'count'   => $absenceCount,
                    'weekend' => $date->format('N') >= 6,
                ];
                $totalAbsence += $absenceCount;
                $maxAbsence = max($absenceCount, $maxAbsence);
            }

            $calendar[] = [
                'name'  => Format::monthName($monthDate->format('Y-m-d'), true),
                'days'  => $days,
            ];
        }

        // CALENDAR TABLE
        $table = DataTable::createPaginated('staffAbsenceCalendar', $criteria);
        $table->setTitle(__('Staff Absence Summary'));
        $table->setDescription(__n('{count} Absence', '{count} Absences', $totalAbsence));
        $table->getRenderer()->addData('class', 'calendarTable border-collapse bg-transparent border-r-0');
        $table->addMetaData('hidePagination', true);
        $table->modifyRows(function ($values, $row) {
            return $row->setClass('bg-transparent');
        });

        $table->addColumn('name', '')->notSortable();

        $baseURL = isActionAccessible($guid, $connection2, '/modules/Staff/absences_manage.php')
            ? $session->get('absoluteURL').'/index.php?q=/modules/Staff/absences_manage.php'
            : $session->get('absoluteURL').'/index.php?q=/modules/Staff/report_absences.php';

        for ($dayCount = 1; $dayCount <= 31; $dayCount++) {
            $table->addColumn($dayCount, '')
                ->context('primary')
                ->notSortable()
                ->format(function ($month) use ($baseURL, $dayCount, $gibbonStaffAbsenceTypeID, $dateFormat) {
                    $day = $month['days'][$dayCount] ?? null;
                    if (empty($day)) return '';
                    $dateText = $day['date']->format($dateFormat);
                    $url = $baseURL.'&dateStart='.$dateText.'&dateEnd='.$dateText.'&gibbonStaffAbsenceTypeID='.$gibbonStaffAbsenceTypeID;
                    $title =  Format::dayOfWeekName($day['date']);
                    $title .= '<br/>'.Format::dateReadable($day['date'], Format::MEDIUM);
                    if ($day['count'] > 0) {
                        $title .= '<br/>'.__n('{count} Absence', '{count} Absences', $day['count']);
                    }

                    return Format::link($url, $day['number'], $title);
                })
                ->modifyCells(function ($month, $cell) use ($dayCount, $maxAbsence) {
                    $day = $month['days'][$dayCount] ?? null;
                    if (empty($day)) return '';

                    $count = $day['count'] ?? 0;

                    $cell->addClass($day['date']->format('Y-m-d') == date('Y-m-d') ? 'border-2 border-gray-700' : 'border');

                    if ($count > ceil($maxAbsence * 0.8)) $cell->addClass('bg-purple-800');
                    elseif ($count > ceil($maxAbsence * 0.5)) $cell->addClass('bg-purple-600');
                    elseif ($count > ceil($maxAbsence * 0.2)) $cell->addClass('bg-purple-400');
                    elseif ($count > 0) $cell->addClass('bg-purple-200');
                    elseif ($day['weekend']) $cell->addClass('bg-gray-200');
                    else $cell->addClass('bg-white');

                    $cell->addClass('h-3 sm:h-6');

                    return $cell;
                });
        }

        echo $table->render(new DataSet($calendar));
    }

    // DATA TABLE
    $staffGateway = $container->get(StaffGateway::class);
    $criteria = $staffGateway->newQueryCriteria()
        ->filterBy('all', $status == 'on')
        ->sortBy(['surname', 'preferredName'])
        ->fromPOST();

    $absenceTypes = $staffAbsenceTypeGateway->selectAllTypes()->fetchAll();
    $types = array_fill_keys(array_column($absenceTypes, 'name'), null);

    $absencesByPerson = [];

    foreach ($absences as $absence) {
        $id = $absence['gibbonPersonID'];
        if (empty($absencesByPerson[$id])) $absencesByPerson[$id] = $types;

        $absencesByPerson[$id][$absence['type']] += $absence['value'];
    }

    $allStaff = $staffGateway->queryAllStaff($criteria);

    $allStaff->transform(function (&$person) use ($absencesByPerson) {
        $id = $person['gibbonPersonID'];
        if (isset($absencesByPerson[$id])) {
            $person = array_merge($person, $absencesByPerson[$id]);
            $person['total'] = array_sum($absencesByPerson[$id]);
        } else {
            $person['total'] = 0;
        }
    });



    // DATA TABLE
    $table = ReportTable::createPaginated('staffAbsences', $criteria)->setViewMode($viewMode, $session);
    $table->setTitle(__('Report'));
    $table->setDescription(Format::dateRangeReadable($dateStart->format('Y-m-d'), $dateEnd->format('Y-m-d')));

    if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_view.php', 'Staff Directory_full')) {
        $table->addMetaData('filterOptions', [
            'all:on'        => __('All Staff'),
            'type:teaching' => __('Staff Type').': '.__('Teaching'),
            'type:support'  => __('Staff Type').': '.__('Support'),
        ]);
    }

    // COLUMNS
    $table->addColumn('fullName', __('Name'))
        ->sortable(['surname', 'preferredName'])
        ->format(function ($person) use ($session) {
            $text = Format::name($person['title'], $person['preferredName'], $person['surname'], 'Staff', true, true);
            $url = $session->get('absoluteURL').'/index.php?q=/modules/Staff/absences_view_byPerson.php&gibbonPersonID='.$person['gibbonPersonID'];
            $output = Format::link($url, $text);
            $output .= '<br/>'.Format::small($person['jobTitle']);
            return $output;
        });

    foreach ($absenceTypes as $type) {
        $table->addColumn($type['name'], $type['nameShort'])
            ->setTitle($type['name'])
            ->notSortable()
            ->width('10%');
    }

    $table->modifyRows(function ($values, $row) {
        if ($values['status'] == 'Left') $row->addClass('error');
        return $row;
    });

    $table->addColumn('total', __('Total'))->notSortable();



    echo $table->render($allStaff);
}
