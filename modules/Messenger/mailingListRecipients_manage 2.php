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
use Gibbon\Domain\Messenger\MailingListRecipientGateway;

$page->breadcrumbs->add(__('Manage Mailing List Recipients'));

if (isActionAccessible($guid, $connection2, '/modules/Messenger/mailingListRecipients_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->addAlert(__('Please copy and share {linkOpen}this link{linkClose} to allow members of the public to subscribe to your mailing lists.', ['linkOpen' => '<a href="'.$session->get('absoluteURL').'/index.php?q=/modules/Messenger/mailingListRecipients_manage_subscribe.php&mode=subscribe">', 'linkClose' => '</a>']), 'message');
       
    // QUERY
    $mailingListRecipientGateway = $container->get(MailingListRecipientGateway::class);
    $criteria = $mailingListRecipientGateway->newQueryCriteria(true)
        ->sortBy('surname', 'preferredName')
        ->fromPOST();

    $mailingLists = $mailingListRecipientGateway->queryMailingList($criteria);

    // TABLE
    $table = DataTable::createPaginated('mailingLists', $criteria);

    $table->addHeaderAction('add', __('Add'))
        ->displayLabel()
        ->setURL('/modules/Messenger/mailingListRecipients_manage_add.php');

    $table->addColumn('surname', __('Surname'));

    $table->addColumn('preferredName', __('Preferred Name'));

    $table->addColumn('email', __('Email'));

    $table->addColumn('organisation', __('Organisation'));

    $table->addActionColumn()
        ->addParam('gibbonMessengerMailingListRecipientID')
        ->format(function ($mailingList, $actions) {
            $actions->addAction('edit', __('Edit'))
                ->setURL('/modules/Messenger/mailingListRecipients_manage_edit.php');

            $actions->addAction('delete', __('Delete'))
                ->setURL('/modules/Messenger/mailingListRecipients_manage_delete.php');
        });

    echo $table->render($mailingLists);
}
