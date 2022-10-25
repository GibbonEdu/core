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

use Gibbon\Domain\Messenger\MessengerGateway;
use Gibbon\Database\Result;

/**
 * Retrieve messages into the format specified by $mode parameter.
 *
 * @deprecated v25
 * @version    v12
 *
 * @param string $guid         Obsoleted parameter.
 * @param string $connection2  Obsoleted parameter.
 * @param string $mode  Mode may be:
 *                      "print" (return table of messages); or
 *                      "array" (return array of messages); or
 *                      "count" (return message count); or
 *                      "result" (return database query result)
 *                      Default: "print".
 * @param string $date         The YYYY-MM-DD representation of date. Default: today's date.
 *
 * @return string|int|Result  Format specified by $mode parameter.
 */
function getMessages($guid, $connection2, $mode = '', $date = '')
{
    global $container;
    return $container->get(MessengerGateway::class)->getMessages($mode, $date);
}
