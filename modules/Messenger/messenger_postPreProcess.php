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

use Gibbon\Module\Messenger\MessageProcess;

include '../../gibbon.php';

$URL = $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/messenger_post.php";

if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_post.php") == false) {
    $URL .= "&addReturn=fail0";
    header("Location: {$URL}");
    exit;
} else {

    $process = $container->get(MessageProcess::class);
    $process->startSendMessage($_SESSION[$guid]['gibbonSchoolYearID'], $_SESSION[$guid]['gibbonPersonID'], $_POST);

    $URL.="&addReturn=success0";
    header("Location: {$URL}") ;
}
