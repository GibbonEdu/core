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
use Gibbon\Domain\DataSet;
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;
use Gibbon\Domain\System\LogGateway;

require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/logs_view.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    $page->breadcrumbs->add(__('View Logs'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, $returns);
    }

    // echo '<h2>';
    // echo __('Filters');
    // echo '</h2>';

    echo '<h2>';
    echo __('Data');
    echo '</h2>';


    // QUERY
    $logGateway = $container->get(LogGateway::class);
    $criteria = $logGateway->newQueryCriteria(true)
        ->sortBy('timestamp', 'DESC')
        ->fromPOST();

    $logs = $logGateway->queryLogs($criteria);

    $table = DataTable::createPaginated('logView', $criteria);

    // $table->addMetaData('filterOptions', [
    //     'type:core'       => __('Type').': '.__('Core'),
    //     'type:additional' => __('Type').': '.__('Additional'),
    //     'active:Y' => __('Active').': '.__('Yes'),
    //     'active:N' => __('Active').': '.__('No'),
    // ]);

    $table->addExpandableColumn('comment')
        ->format(function($log) {
            $array = unserialize($log['serialisedArray']);

            $details = '';
            if (count($array) > 0) {
                $details = "<table class='smallIntBorder' style='width:100%;'>";
                foreach ($array as $fieldName => $fieldValue) {
                    $details .= sprintf('<tr><td><b>%1$s</b></td><td>%2$s</td></tr>', $fieldName, $fieldValue);
                }
                $details .= "</table>";
            }

            return $details;
        });
    $table->addColumn('gibbonLogID', __('Log ID'));
    $table->addColumn('timestamp', __('Timestamp'));
    $table->addColumn('title', __('Title'));
    $table->addColumn('type', __('Type'))
        ->format(function ($log) {
          return (empty($log['module'])) ? 'System' : $log['module'] ;
        });
    $table->addColumn('user', __('User'))
        ->format(function ($log) {
          return Format::name('', $log['preferredName'], $log['surname'], 'Student', false, true)."</br>". Format::small($log['username']) ;
        });
    $table->addColumn('ip', __('IP Address'));


    echo $table->render($logs);

}
