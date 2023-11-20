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
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\User\UserStatusLogGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/User Admin/user_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {

    //Check if gibbonPersonID specified
    $gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
    if (empty($gibbonPersonID)) {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        $userGateway = $container->get(UserGateway::class);
        $person = $userGateway->getByID($gibbonPersonID);

        if (empty($person)) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $userStatusLogGateway = $container->get(UserStatusLogGateway::class);
            $criteria = $userStatusLogGateway->newQueryCriteria(true)
                ->sortBy('timestamp')
                ->fromPOST();

            $table = DataTable::createPaginated('statusLog', $criteria);

            $table->setTitle(Format::name($person['title'], $person['preferredName'], $person['surname'], 'Student'));
        
            $table->addColumn('timestamp', __('Date Changed'))
                ->format(Format::using('dateTime', ['timestamp']));

            $table->addColumn('statusOld', __('Old Status'));

            $table->addColumn('statusNew', __('New Status'));

            $table->addColumn('reason', __('Reason'));

            echo $table->render($userStatusLogGateway->queryStatusLogByPerson($criteria, $gibbonPersonID));
        }
    }
}
