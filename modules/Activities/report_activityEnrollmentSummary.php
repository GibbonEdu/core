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

use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\Activities\ActivityReportGateway;

//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Activities/report_activityEnrollmentSummary.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!

    $viewMode = isset($_REQUEST['view']) ? $_REQUEST['view'] : 'table';

    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Activity Enrolment Summary').'</div>';
    echo '</div>';

    $activityGateway = $container->get(ActivityReportGateway::class);

    // CRITERIA
    $criteria = $activityGateway->newQueryCriteria()
        ->sortBy('gibbonActivity.name')
        ->pageSize(0)
        ->fromArray($_POST);

    $activities = $activityGateway->queryActivityEnrollmentSummary($criteria, $_SESSION[$guid]['gibbonSchoolYearID']);

    // DATA TABLE
    $table = DataTable::createPaginated('activities', $criteria);

    $table->modifyRows(function($activity, $row) {
        if ($activity['enrolment'] == $activity['maxParticipants'] && $activity['maxParticipants'] > 0) {
            $row->addClass('current');
        } else if ($activity['enrolment'] > $activity['maxParticipants']) {
            $row->addClass('error');
        } else if ($activity['maxParticipants'] == 0) {
            $row->addClass('warning');
        }
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

    $table->addColumn('name', __('Activity'))
        ->format(function($activity) {
            return $activity['name'].'<br/><span class="small emphasis">'.$activity['type'].'</span>';
        });

    $table->addColumn('enrolment', __('Accepted'));
    $table->addColumn('registered', __('Registered'))->description(__('Excludes "Not Accepted"'));
    $table->addColumn('maxParticipants', __('Max Participants'));

    echo $table->render($activities);
}
