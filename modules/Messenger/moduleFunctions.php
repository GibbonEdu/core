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

function hasEmojis($string) {

    $regexEmoticons = '/[\x{1F300}-\x{1F5FF}\x{1F600}-\x{1F64F}\x{1F680}-\x{1F6FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{1F900}-\x{1F9FF}\x{1F1E0}-\x{1F1FF}]/u';

    return preg_match($regexEmoticons, $string) ? true : false;
}

function removeEmoji($text) {

    $regexEmoticons = '/[\x{1F300}-\x{1F5FF}\x{1F600}-\x{1F64F}\x{1F680}-\x{1F6FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{1F900}-\x{1F9FF}\x{1F1E0}-\x{1F1FF}]/u';

    return preg_replace($regexEmoticons, '', $text);
}