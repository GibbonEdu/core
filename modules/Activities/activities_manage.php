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

use Gibbon\Domain\Activities\ActivityGateway;
use Gibbon\Forms\Prefab\BulkActionForm;
use Gibbon\Forms\Form;
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Forms\DatabaseFormFactory;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Set returnTo point for upcoming pages
    $page->breadcrumbs->add(__('Manage Activities'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $search = $_GET['search'] ?? '';
    $gibbonSchoolYearTermID = $_GET['gibbonSchoolYearTermID'] ?? '';
    $gibbonYearGroupID = $_GET['gibbonYearGroupID'] ?? '';
    $dateType = getSettingByScope($connection2, 'Activities', 'dateType');
    $enrolmentType = getSettingByScope($connection2, 'Activities', 'enrolmentType');
    $schoolTerms = getTerms($connection2, $_SESSION[$guid]['gibbonSchoolYearID']);
    $yearGroups = getYearGroups($connection2);

    $activityGateway = $container->get(ActivityGateway::class);
    
    // CRITERIA
    $criteria = $activityGateway->newQueryCriteria(true)
        ->searchBy($activityGateway->getSearchableColumns(), $search)
        ->filterBy('term', $gibbonSchoolYearTermID)
        ->filterBy('yearGroup', $gibbonYearGroupID)
        ->sortBy($dateType != 'Date' ? 'gibbonSchoolYearTermIDList' : 'programStart', $dateType != 'Date' ? 'ASC' : 'DESC')
        ->sortBy('name');

    $criteria->fromPOST();

    echo '<h2>';
    echo __('Search & Filter');
    echo '</h2>';

    $paymentOn = getSettingByScope($connection2, 'Activities', 'payment') != 'None' and getSettingByScope($connection2, 'Activities', 'payment') != 'Single';

    $form = Form::create('searchForm', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('noIntBorder fullWidth');

    $form->addHiddenValue('q', "/modules/".$_SESSION[$guid]['module']."/activities_manage.php");

    $row = $form->addRow();
        $row->addLabel('search', __('Search'))->description(__('Activity name.'));
        $row->addTextField('search')->setValue($criteria->getSearchText());

    if ($dateType != 'Date') {
        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
        $sql = "SELECT gibbonSchoolYearTermID as value, name FROM gibbonSchoolYearTerm WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY sequenceNumber";
        $row = $form->addRow();
            $row->addLabel('gibbonSchoolYearTermID', __('Term'));
            $row->addSelect('gibbonSchoolYearTermID')->fromQuery($pdo, $sql, $data)->selected($gibbonSchoolYearTermID)->placeholder();
    }

    $row = $form->addRow();
        $row->addLabel('gibbonYearGroupID', __('Year Group'));
        $row->addSelectYearGroup('gibbonYearGroupID')->placeholder()->selected($gibbonYearGroupID);

    $row = $form->addRow();
        $row->addSearchSubmit($gibbon->session, __('Clear Search'));

    echo $form->getOutput();

    echo '<h2>';
    echo __('Activities');
    echo '</h2>';

    $activities = $activityGateway->queryActivitiesBySchoolYear($criteria, $_SESSION[$guid]['gibbonSchoolYearID']);

    // FORM
    $form = BulkActionForm::create('bulkAction', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/activities_manageProcessBulk.php');
    $form->addHiddenValue('search', $search);

    $bulkActions = array(
        'Duplicate' => __('Duplicate'),
        'DuplicateParticipants' => __('Duplicate With Participants'),
        'Delete' => __('Delete'),
    );
    $sql = "SELECT gibbonSchoolYearID as value, gibbonSchoolYear.name FROM gibbonSchoolYear WHERE (status='Upcoming' OR status='Current') ORDER BY sequenceNumber LIMIT 0, 2";

    $col = $form->createBulkActionColumn($bulkActions);
        $col->addSelect('gibbonSchoolYearIDCopyTo')
            ->fromQuery($pdo, $sql)
            ->setClass('shortWidth schoolYear');
        $col->addSubmit(__('Go'));

    $form->toggleVisibilityByClass('schoolYear')->onSelect('action')->when(array('Duplicate', 'DuplicateParticipants'));

    // DATA TABLE
    $table = $form->addRow()->addDataTable('activities', $criteria)->withData($activities);

    $table->addHeaderAction('add', __('Add'))
        ->setURL('/modules/Activities/activities_manage_add.php')
        ->addParam('search', $search)
        ->addParam('gibbonSchoolYearTermID', $gibbonSchoolYearTermID)
        ->displayLabel();

    $table->modifyRows(function ($activity, $row) {
        if ($activity['active'] == 'N') $row->addClass('error');
        return $row;
    });

    $table->addMetaData('filterOptions', [
        'active:Y'          => __('Active').': '.__('Yes'),
        'active:N'          => __('Active').': '.__('No'),
        'registration:Y'    => __('Registration').': '.__('Yes'),
        'registration:N'    => __('Registration').': '.__('No'),
        'enrolment:less'    => __('Enrolment').': &lt; '.__('Full'),
        'enrolment:full'    => __('Enrolment').': '.__('Full'),
        'enrolment:greater' => __('Enrolment').': &gt; '.__('Full'),
    ]);

    if ($enrolmentType == 'Competitive') {
        $table->addMetaData('filterOptions', ['status:waiting' => __('Waiting List')]);
    } else {
        $table->addMetaData('filterOptions', ['status:pending' => __('Pending')]);
    }

    $table->addMetaData('bulkActions', $col);

    // COLUMNS
    $table->addColumn('name', __('Activity'))
        ->format(function($activity) {
            return $activity['name'].'<br/><span class="small emphasis">'.$activity['type'].'</span>';
        });

    $table->addColumn('days', __('Days'))
        ->notSortable()
        ->format(function($activity) use ($activityGateway) {
            return implode(', ', array_map('__', $activityGateway->selectWeekdayNamesByActivity($activity['gibbonActivityID'])->fetchAll(\PDO::FETCH_COLUMN)));
        });

    $table->addColumn('yearGroups', __('Years'))
        ->format(function($activity) use ($yearGroups) {
            return ($activity['yearGroupCount'] >= count($yearGroups)/2)? '<i>'.__('All').'</i>' : $activity['yearGroups'];
        });

    $table->addColumn('date', $dateType != 'Date'? __('Term') : __('Dates'))
        ->sortable($dateType != 'Date' ? ['gibbonSchoolYearTermIDList'] : ['programStart', 'programEnd'])
        ->format(function($activity) use ($dateType, $schoolTerms) {
            if (empty($schoolTerms)) return '';
            if ($dateType != 'Date') {
                $dateRange = '';
                if (!empty(array_intersect($schoolTerms, explode(',', $activity['gibbonSchoolYearTermIDList'])))) {
                    $termList = array_map(function ($item) use ($schoolTerms) {
                        $index = array_search($item, $schoolTerms);
                        return ($index !== false && isset($schoolTerms[$index+1]))? $schoolTerms[$index+1] : '';
                    }, explode(',', $activity['gibbonSchoolYearTermIDList']));
                    return implode('<br/>', $termList);
                }
            } else {
                return Format::dateRangeReadable($activity['programStart'], $activity['programEnd']);
            }
        });

    if ($paymentOn) {
        $table->addColumn('payment', __('Cost'))
            ->description($_SESSION[$guid]['currency'])
            ->format(function($activity) {
                $payment = ($activity['payment'] > 0) 
                    ? Format::currency($activity['payment']) . '<br/>' . __($activity['paymentType'])
                    : '<i>'.__('None').'</i>';
                if ($activity['paymentFirmness'] != 'Finalised') $payment .= '<br/><i>'.__($activity['paymentFirmness']).'</i>';

                return $payment;
            });
    }

    $table->addColumn('provider', __('Provider'))
        ->format(function($activity) use ($guid){
            return ($activity['provider'] == 'School')? $_SESSION[$guid]['organisationNameShort'] : __('External');
        });

    $table->addColumn('enrolment', __('Enrolment'))
        ->format(function($activity) {
            return $activity['enrolment'] .' / '. $activity['maxParticipants']
                . (!empty($activity['waiting'])? '<br><small><i>' .$activity['waiting'].' '.__('Waiting') .'</i></small>' : '')
                . (!empty($activity['pending'])? '<br><small><i>' .$activity['pending'].' '.__('Pending') .'</i></small>' : '');
        });

    // ACTIONS
    $table->addActionColumn()
        ->addParam('gibbonActivityID')
        ->addParam('search', $criteria->getSearchText(true))
        ->addParam('gibbonSchoolYearTermID', $gibbonSchoolYearTermID)
        ->format(function ($activity, $actions) use ($guid) {
            $actions->addAction('edit', __('Edit'))
                    ->setURL('/modules/Activities/activities_manage_edit.php');

            $actions->addAction('delete', __('Delete'))
                    ->setURL('/modules/Activities/activities_manage_delete.php');

            $actions->addAction('enrolment', __('Enrolment'))
                    ->setURL('/modules/Activities/activities_manage_enrolment.php')
                    ->setIcon('attendance');
        });

    $table->addCheckboxColumn('gibbonActivityID');

    echo $form->getOutput();
}
