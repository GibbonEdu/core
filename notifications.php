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

use Gibbon\Domain\System\NotificationGateway;
use Gibbon\Tables\DataTable;
use Gibbon\Services\Format;

if (!$session->exists('username')) {
    $page->addError(__('You do not have access to this action.'));
} else {
    $page->breadcrumbs->add(__('Notifications'));

    echo "<p class='text-right mb-2 text-xs'>";
    echo "<a onclick='return confirm(\"Are you sure you want to delete these records.\")' href='".$session->get('absoluteURL')."/notificationsDeleteAllProcess.php'>".__('Delete All Notifications')." <img style='vertical-align: -25%' src='".$session->get('absoluteURL').'/themes/'.$session->get('gibbonThemeName')."/img/garbage.png'></a>";
    echo '</p>';

    // Notifications
    $notificationGateway = $container->get(NotificationGateway::class);
    $criteria = $notificationGateway->newQueryCriteria(true)
        ->sortBy('timestamp', 'DESC')
        ->fromPOST('newNotifications');

    $notifications = $notificationGateway->queryNotificationsByPerson($criteria, $session->get('gibbonPersonID'));

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

    $archivedNotifications = $notificationGateway->queryNotificationsByPerson($criteria, $session->get('gibbonPersonID'), 'Archived');

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
