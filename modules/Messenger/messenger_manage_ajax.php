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

// Gibbon system-wide includes

use Gibbon\Domain\System\LogGateway;

include '../../gibbon.php';

$gibbonLogID = $_POST['gibbonLogID'] ?? '';

if (empty($gibbonLogID)) return;
if (!$session->has('username')) return;

if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_manage.php')) {
    $log = $container->get(LogGateway::class)->getByID($gibbonLogID);
    if (empty($log)) return;

    $data = unserialize($log['serialisedArray']) ?? [];
    $status = $data['status'];

    if ($status == 'Running' || $status == 'Ready') {
        echo '<div class="mb-2"><img class="align-middle w-56 -mt-px -ml-1" src="./themes/Default/img/loading.gif">'
            .'<span class="tag ml-2 message">'.__('Sending').'</span></div>';
    } else {
        echo '<div class="mb-2"><span class="tag success">'.__('Sent').'</span></div>';
    }
}
