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
use Gibbon\Tables\View\GridView;
use Gibbon\Tables\Prefab\ReportTable;
use Gibbon\Domain\Staff\StaffGateway;
use Gibbon\View\View;

if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_view.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Get action with highest precedence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    if ($highestAction == false) {
        $page->addError(__('The highest grouped action cannot be determined.'));
        return;
    }

    // Proceed!
    $viewMode = $_REQUEST['format'] ?? '';
    $directoryView = $highestAction == 'Staff Directory_full' && !empty($_GET['view']);
    $_GET['sidebar'] = 'false';

    $urlParams = [
        'view' => $_GET['view'] ?? '',
        'search' => $_GET['search'] ?? '',
        'allStaff' => $_GET['allStaff'] ?? '',
        'grouping' => $_GET['grouping'] ?? '',
        'sortBy' => $_GET['sortBy'] ?? ($directoryView ? 'biographicalGrouping' : 'surname'),
        'sidebar' => 'false',
    ];

    if ($viewMode == 'export') {
        $urlParams['view'] = 'list';
    }

    // QUERY
    $staffGateway = $container->get(StaffGateway::class);
    $criteria = $staffGateway->newQueryCriteria(!$directoryView)
        ->searchBy($staffGateway->getSearchableColumns(), $urlParams['search'])
        ->filterBy('biographicalGrouping', $urlParams['grouping'])
        ->filterBy('all', $urlParams['allStaff'])
        ->pageSize(!empty($viewMode) ? 0 : 50)
        ->fromPOST();

    // FILTERS
    if (empty($viewMode)) {
        $page->breadcrumbs->add(__('Staff Directory'), 'staff_view.php');

        if ($directoryView) {
            $page->breadcrumbs->add(__('Staff Directory'));
        }

        $form = Form::create('filters', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
        $form->setTitle(__('Search'));

        $form->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('address', $_SESSION[$guid]['address']);
        $form->addHiddenValue('q', '/modules/Staff/staff_view.php');
        $form->addHiddenValue('sidebar', $directoryView ? 'false' : '');
        $form->addHiddenValue('view', $urlParams['view']);

        $row = $form->addRow();
            $row->addLabel('search', __('Search For'))->description(__('Preferred, surname, username.'));
            $row->addTextField('search')->setValue($criteria->getSearchText())->maxLength(20);

        if ($highestAction == 'Staff Directory_full') {
            $sortOptions = ['biographicalGrouping' => __('Biographical Grouping'), 'surname' => __('Surname')];
            $row = $form->addRow();
                $row->addLabel('sortBy', __('Sort By'));
                $row->addSelect('sortBy')->fromArray($sortOptions)->selected($urlParams['sortBy']);

            $row = $form->addRow();
                $row->addLabel('allStaff', __('All Staff'))->description(__('Include all staff, regardless of status, start date, end date, etc.'));
                $row->addCheckbox('allStaff')->checked($urlParams['allStaff']);
        }

        $row = $form->addRow();
            $row->addFooter();
            $row->addSearchSubmit($gibbon->session, 'Clear Filters', ['view', 'sidebar']);

        echo $form->getOutput();
    }


    if ($highestAction == 'Staff Directory_brief') {
        // BASIC STAFF LIST
        $criteria->sortBy(['surname', 'preferredName']);
        $staff = $staffGateway->queryAllStaff($criteria);

        $table = DataTable::createPaginated('staffView', $criteria);
        $table->setTitle(__('Choose A Staff Member'));

        // COLUMNS
        $table->addColumn('fullName', __('Name'))
            ->description(__('Initials'))
            ->width('35%')
            ->sortable(['surname', 'preferredName'])
            ->format(function ($person) {
                return Format::name($person['title'], $person['preferredName'], $person['surname'], 'Staff', true, true)
                    .'<br/><span style="font-size: 85%; font-style: italic">'.$person['initials']."</span>";
            });

        $table->addColumn('type', __('Type'))->width('25%')->translatable();
        $table->addColumn('jobTitle', __('Job Title'))->width('25%');

        // ACTIONS
        $table->addActionColumn()
            ->addParam('gibbonPersonID')
            ->addParam('search', $criteria->getSearchText(true))
            ->format(function ($person, $actions) {
                $actions->addAction('view', __('View Details'))
                        ->setURL('/modules/Staff/staff_view_details.php');
            });

        echo $table->render($staff);
        return;

    } elseif ($highestAction == 'Staff Directory_full') {
        // FULL STAFF DIRECTORY
        if ($urlParams['sortBy'] == 'biographicalGrouping') {
            $criteria->sortBy(['biographicalGroupingOrder', 'biographicalGrouping'])
                ->sortBy(['biographicalGroupingPriority'], 'DESC')
                ->sortBy(['surname', 'preferredName']);
        } else {
            $criteria->sortBy(['surname', 'preferredName']);
        }

        $staff = $staffGateway->queryAllStaff($criteria, $gibbon->session->get('gibbonSchoolYearID'));

        $table = ReportTable::createPaginated('staffView', $criteria)->setViewMode($viewMode, $gibbon->session);
        $table->setTitle($directoryView ? __('Staff Directory') : __('Choose A Staff Member'));

        if ($urlParams['sortBy'] == 'biographicalGrouping') {
            $lastGroup = '';
            $table->modifyRows(function ($data, $row, $columnCount) use (&$lastGroup, &$urlParams) {
                if ($lastGroup != $data['biographicalGrouping']) {
                    $urlParams['grouping'] = $lastGroup = $data['biographicalGrouping'];
                    $url = './index.php?q=/modules/Staff/staff_view.php&'.http_build_query($urlParams);
                    $link = Format::link($url, !empty($data['biographicalGrouping'])? $data['biographicalGrouping'] : ' ', ['class' => 'block text-gray-700  uppercase leading-relaxed hover:underline']);

                    if ($urlParams['view'] == 'list') {
                        $row->prepend('<tr class="bg-gray-300 font-bold"><td colspan="'.$columnCount.'">'.$link.'</td></tr>');
                    } elseif ($urlParams['view'] == 'grid') {
                        $row->prepend('<div class="w-full bg-gray-300 font-bold text-sm p-3">'.$link.'</div>');
                    } elseif ($urlParams['view'] == 'card') {
                        $row->prepend('<div class="w-full border rounded-sm bg-gray-300 font-bold text-sm p-3 mb-4 mx-2">'.$link.'</div>');
                    }
                }
                return $row;
            });
        }

        if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_manage_add.php')) {
            $table->addHeaderAction('add', __('Add'))
                ->setURL('/modules/Staff/staff_manage_add.php')
                ->addParam('search', $urlParams['search'])
                ->displayLabel()
                ->prepend('&nbsp; | &nbsp;');
        }

        $table->addMetaData('filterOptions', [
            'type:teaching' => __('Staff Type').': '.__('Teaching'),
            'type:support'  => __('Staff Type').': '.__('Support'),
        ]);

        $table->addMetaData('listOptions', [
            'list' => __('List'),
            'grid' => __('Grid'),
            'card' => __('Card'),
        ]);

        // COLUMNS
        if ($urlParams['view'] == 'grid' || $urlParams['view'] == 'card') {
            $table->setRenderer(new GridView($container->get('twig')));
            $table->getRenderer()->setCriteria($criteria);

            if ($urlParams['view'] == 'card') {
                $table->addMetaData('gridClass', 'items-stretch -mx-2');
                $table->addMetaData('gridItemClass', 'w-full lg:w-1/2 px-2 mb-4 text-center text-xs items-stretch');

                $templateView = $container->get(View::class);
                $table->addColumn('card', '')
                    ->addClass('border rounded-sm bg-gray-100 h-full')
                    ->format(function ($person) use (&$templateView) {
                        return $templateView->fetchFromTemplate('staffDirectoryCard.twig.html', ['staff' => $person]);
                    });
            } else {
                $table->addMetaData('gridClass', 'rounded-sm bg-gray-100 border');
                $table->addMetaData('gridItemClass', 'w-1/2 sm:w-1/4 md:w-1/5 my-4 text-center text-xs');

                $table->addColumn('image_240', __('Photo'))
                    ->context('primary')
                    ->format(function ($person) {
                        $url = './index.php?q=/modules/Staff/staff_view_details.php&gibbonPersonID='.$person['gibbonPersonID'];
                        return Format::link($url, Format::userPhoto($person['image_240'], 'sm'));
                    });
            }
        }

        if ($urlParams['view'] != 'card') {
            $table->addColumn('fullName', __('Name'))
                ->context('primary')
                ->description(__('Initials'))
                ->sortable(['surname', 'preferredName'])
                ->width('20%')
                ->format(function ($person) use ($urlParams) {
                    $text = Format::name($person['title'], $person['preferredName'], $person['surname'], 'Staff', $urlParams['view'] != 'grid', true);
                    $url = './index.php?q=/modules/Staff/staff_view_details.php&gibbonPersonID='.$person['gibbonPersonID'];
                    return Format::link($url, $text, ['class' => 'font-bold underline leading-normal']).'<br/>'.
                        Format::small($person['initials']);
                });

            if ($urlParams['view'] == 'list' || $urlParams['view'] == '') {
                $table->addColumn('type', __('Type'))->translatable();
            }
            $table->addColumn('jobTitle', __('Job Title'))
                ->context('primary')
                ->description(__('Roll Group'))
                ->sortable(['jobTitle', 'gibbonPerson.surname', 'gibbonPerson.preferredName'])
                ->format(function ($person) {
                    return (!empty($person['jobTitle']) ? $person['jobTitle'] : '').'<br/>'.
                        (!empty($person['rollGroupName']) ? Format::small($person['rollGroupName']) : '');
                });
        }

        if ($urlParams['view'] == 'list') {
            $table->addColumn('department', __('Department'));
            $table->addColumn('facility', __('Facility'))->width('5%');
            $table->addColumn('extension', __('Extension'))->width('5%');
            $table->addColumn('email', __('Email'));
            $table->addColumn('phone', __('Phone'))
                ->sortable(['phone1', 'phone2', 'phone3', 'phone4'])
                ->format(function ($person) {
                    $output = '';
                    for ($i = 1; $i <= 4; $i++) {
                        if (empty($person["phone{$i}"])) continue;

                        $shortPhoneNumber = $person["phone{$i}"].($person["phone{$i}Type"] != 'Mobile' ? '&nbsp;('.$person["phone{$i}Type"].')' : '');
                        $fullPhoneNumber = Format::phone($person["phone{$i}"], $person["phone{$i}CountryCode"], $person["phone{$i}Type"]);
                        $output .= Format::tooltip($shortPhoneNumber, $fullPhoneNumber).'<br/>';
                    }
                    return $output;
                });
        }

        if (!$directoryView) {
            // ACTIONS
            $table->addActionColumn()
                ->addParam('gibbonPersonID')
                ->addParam('allStaff', $urlParams['allStaff'])
                ->addParam('search', $criteria->getSearchText(true))
                ->format(function ($person, $actions) {
                    $actions->addAction('view', __('View Details'))
                            ->setURL('/modules/Staff/staff_view_details.php');
                });
        }

        echo $table->render($staff);
    }
}
