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
use Gibbon\Services\Format;
use Gibbon\Tables\View\GridView;
use Gibbon\Tables\Prefab\ReportTable;
use Gibbon\Domain\Staff\StaffGateway;
use Gibbon\View\View;

if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_directory.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $staffGateway = $container->get(StaffGateway::class);
    
    $viewMode = $_REQUEST['format'] ?? '';
    $urlParams = [
        'view' => $_GET['view'] ?? 'list',
        'search' => $_GET['search'] ?? '',
        'grouping' => $_GET['grouping'] ?? '',
        'sortBy' => $_GET['sortBy'] ?? 'biographicalGrouping',
    ];

    // QUERY
    $criteria = $staffGateway->newQueryCriteria()
        ->searchBy($staffGateway->getSearchableColumns(), $urlParams['search'])
        ->sortBy($urlParams['sortBy'] == 'biographicalGrouping'
            ? ['biographicalGroupingOrder', 'biographicalGrouping', 'biographicalGroupingPriority', 'surname', 'preferredName']
            : ['surname', 'preferredName'])
        ->filterBy('biographicalGrouping', $urlParams['grouping'])
        ->fromPOST();

    // FILTERS
    if (empty($viewMode)) {
        $page->breadcrumbs->add(__('Staff Directory'));
        $form = Form::create('action', $_SESSION[$guid]['absoluteURL']."/index.php", 'get');
        $form->setTitle(__('Search'));

        $form->setClass('noIntBorder fullWidth');

        $form->addHiddenValue('address', $_SESSION[$guid]['address']);
        $form->addHiddenValue('q', '/modules/Staff/staff_directory.php');

        $row = $form->addRow();
            $row->addLabel('search', __('Search For'))->description(__('Preferred, surname, username.'));
            $row->addTextField('search')->setValue($criteria->getSearchText())->maxLength(20);

        $sortOptions = ['biographicalGrouping' => __('Biographical Grouping'), 'surname' => __('Surname')];
        $row = $form->addRow();
            $row->addLabel('sortBy', __('Sort By'));
            $row->addSelect('sortBy')->fromArray($sortOptions)->selected($urlParams['sortBy']);

        $row = $form->addRow();
            $row->addFooter();
            $row->addSearchSubmit($gibbon->session);

        echo $form->getOutput();
    }

    $staff = $staffGateway->queryAllStaff($criteria, $gibbon->session->get('gibbonSchoolYearID'));

    // DATA TABLE
    $table = ReportTable::createPaginated('staffDirectory', $criteria)->setViewMode($viewMode, $gibbon->session);
    $table->setTitle(__('Staff Directory'));
    
    if (empty($_POST['sortBy']) && $urlParams['sortBy'] == 'biographicalGrouping') {
        $lastGroup = '';
        $table->modifyRows(function ($data, $row, $columnCount) use (&$lastGroup, &$urlParams) {
            if ($lastGroup != $data['biographicalGrouping']) {
                $urlParams['grouping'] = $lastGroup = $data['biographicalGrouping'];
                $url = './index.php?q=/modules/Staff/staff_directory.php&'.http_build_query($urlParams);
                $link = Format::link($url, !empty($data['biographicalGrouping'])? $data['biographicalGrouping'] : ' ', ['class' => 'block text-gray-700  uppercase leading-relaxed hover:underline']);
                
                $row->prepend($urlParams['view'] == 'grid'
                    ? '<div class="w-full bg-gray-300 font-bold text-sm p-3">'.$link.'</div>'
                    : '<tr class="bg-gray-300 font-bold"><td colspan="'.$columnCount.'">'.$link.'</td></tr>');
            }
            return $row;
        });
    }

    $table->addMetaData('filterOptions', [
        'type:teaching' => __('Staff Type').': '.__('Teaching'),
        'type:support'  => __('Staff Type').': '.__('Support'),
        'type:other'    => __('Staff Type').': '.__('Other'),
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
            $table->addMetaData('gridItemClass', 'w-1/2 px-2 mb-4 text-center text-xs items-stretch');

            $templateView = $container->get(View::class);
            $table->addColumn('card', '')
                ->addClass('border rounded bg-gray-100 h-full')
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

    if ($urlParams['view'] == 'list' || $urlParams['view'] == 'grid') {
        $table->addColumn('fullName', __('Name'))
            ->description(__('Initials'))
            ->sortable(['surname', 'preferredName'])
            ->width('20%')
            ->format(function ($person) use ($urlParams) {
                $text = Format::name($person['title'], $person['preferredName'], $person['surname'], 'Staff', $urlParams['view'] != 'grid', true);
                $url = './index.php?q=/modules/Staff/staff_view_details.php&gibbonPersonID='.$person['gibbonPersonID'];
                return Format::link($url, $text, ['class' => 'font-bold underline leading-normal']).'<br/>'.
                    Format::small($person['initials']);
            });

        $table->addColumn('jobTitle', __('Job Title'))
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

    echo $table->render($staff);
}
