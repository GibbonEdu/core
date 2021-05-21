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

use Gibbon\Domain\System\NotificationGateway;
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;

if (!$gibbon->session->exists('username')) {
    echo "<div class='error'>";
    echo __('You do not have access to this action.');
    echo '</div>';
} else {
    $page->breadcrumbs->add(__('Notifications'));

    echo "<div class='linkTop'>";
    echo "<a onclick='return confirm(\"Are you sure you want to delete these records.\")' href='".$gibbon->session->get('absoluteURL')."/notificationsDeleteAllProcess.php'>".__('Delete All Notifications')." <img style='vertical-align: -25%' src='".$gibbon->session->get('absoluteURL').'/themes/'.$gibbon->session->get('gibbonThemeName')."/img/garbage.png'></a>";
    echo '</div>';

    // Notifications
    $notificationGateway = $container->get(NotificationGateway::class);
    $criteria = $notificationGateway->newQueryCriteria(true)
        ->sortBy('timestamp', 'DESC')
        ->fromPOST('newNotifications');

    $notifications = $notificationGateway->queryNotificationsByPerson($criteria, $gibbon->session->get('gibbonPersonID'));

    $table = DataTable::createPaginated('newNotifications', $criteria);

    $table->setTitle(__('New Notifications'));

    $table->addColumn('source', __('Source'))->translatable();
    $table->addColumn('timestamp', __('Date'))->format(Format::using('date', 'timestamp'));
    $table->addColumn('text', __('Message'));
    $table->addColumn('count', __('Count'));

    $table->addActionColumn()
        ->addParam('gibbonNotificationID')
        ->format(function ($row, $actions) {
            $actions->addAction('view', __('Action & Archive'))
                    ->addParam('action', urlencode($row['actionLink']))
                    ->setURL('/notificationsActionProcess.php');

            $actions->addAction('deleteImmediate', __('Delete'))
                    ->setIcon('garbage')
                    ->setURL('/notificationsDeleteProcess.php');
        });


    echo $table->render($notifications);

    // Archived Notifications
    $criteria = $notificationGateway->newQueryCriteria(true)
        ->sortBy('timestamp', 'DESC')
        ->fromPOST('archivedNotifications');

    $archivedNotifications = $notificationGateway->queryNotificationsByPerson($criteria, $gibbon->session->get('gibbonPersonID'), 'Archived');

    $table = DataTable::createPaginated('archivedNotifications', $criteria);

    $table->setTitle(__('Archived Notifications'));

    $table->addColumn('source', __('Source'))->translatable();
    $table->addColumn('timestamp', __('Date'))->format(Format::using('date', 'timestamp'));
    $table->addColumn('text', __('Message'));
    $table->addColumn('count', __('Count'));

    $table->addActionColumn()
        ->addParam('gibbonNotificationID')
        ->format(function ($row, $actions) {
            $actions->addAction('view', __('Action'))
                    ->addParam('action', urlencode($row['actionLink']))
                    ->setURL('/notificationsActionProcess.php');

            $actions->addAction('deleteImmediate', __('Delete'))
                    ->setIcon('garbage')
                    ->setURL('/notificationsDeleteProcess.php');
        });


    echo $table->render($archivedNotifications);
}
