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

use Gibbon\Services\Format;
use Gibbon\Domain\System\LogGateway;

include '../../gibbon.php';

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/System Admin/logs_view_purge.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/logs_view.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $title = $_POST['title'] ?? [];
    $cutoffDate = isset($_POST['cutoffDate']) ? Format::dateConvert($_POST['cutoffDate']) : '';

    if (empty($title) || empty($cutoffDate)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");  
    }

    $logGateway = $container->get(LogGateway::class);
    $logGateway->purgeLogs($title, $cutoffDate);

    $URL .= '&return=success0';
    header("Location: {$URL}");
}
