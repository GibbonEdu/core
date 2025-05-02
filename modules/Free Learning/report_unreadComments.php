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

use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Module\FreeLearning\Domain\System\NotificationGateway;

// Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/report_unreadComments.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
         ->add(__m('Unread Comment Notifications'));

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
             });
     
     
         echo $table->render($notifications);

}