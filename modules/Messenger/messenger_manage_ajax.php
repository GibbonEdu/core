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

// Gibbon system-wide includes

use Gibbon\Domain\System\LogGateway;

include '../../gibbon.php';

$gibbonLogID = $_POST['gibbonLogID'] ?? '';

if (empty($gibbonLogID)) return;
if (!$session->has('username')) return;

if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_manage.php')) {
    $log = $container->get(LogGateway::class)->getByID($gibbonLogID);
    $data = unserialize($log['serialisedArray'] ?? '') ?? [];

    if (empty($log) || empty($data)) return;
    $status = $data['status'] ?? '';

    if ($status == 'Running' || $status == 'Ready') {
        echo '<div class="mb-2"><img class="align-middle w-56 -mt-px -ml-1" src="./themes/Default/img/loading.gif">'
            .'<span class="tag ml-2 message">'.__('Sending').'</span></div>';
    } else {
        echo '<div class="mb-2"><span class="tag success">'.__('Sent').'</span></div>';
    }
}
