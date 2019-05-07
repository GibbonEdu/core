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

use Gibbon\Contracts\Comms\SMS;

require_once '../../gibbon.php';

$from = $_POST['from'] ?? '';
$phoneNumber = $_POST['phoneNumber'] ?? '';
$smsGateway = getSettingByScope($connection2, 'Messenger', 'smsGateway');

if (isActionAccessible($guid, $connection2, '/modules/Staff/subs_manage_edit.php') == false) {
    die(Format::alert(__('Your request failed because you do not have access to this action.')));
} elseif (empty($from) || empty($phoneNumber)) {
    die(__('You have not specified one or more required parameters.'));
} elseif (empty($smsGateway)) {
    die(sprintf(__('SMS NOT CONFIGURED. Please contact %1$s for help.'), $_SESSION[$guid]['organisationAdministratorName']));
} else {
    // Proceed!
    $body = __('{name} sent you a test SMS via {system}', ['name' => $from, 'system' => $_SESSION[$guid]['systemName']]);

    $sms = $container->get(SMS::class);
    
    $result = $sms
        ->content($body)
        ->send([$phoneNumber]);

    echo !empty($result)
        ? __('Success')
        : __('Failed');
}
