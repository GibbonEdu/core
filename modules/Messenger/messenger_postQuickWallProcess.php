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

use Gibbon\Data\Validator;
use Gibbon\Services\Format;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['body' => 'HTML']);

//Module includes
include './moduleFunctions.php';

$address = $_GET['address'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($address).'/messenger_postQuickWall.php';
$time = time();

if (isActionAccessible($guid, $connection2, '/modules/Messenger/messenger_postQuickWall.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    if (empty($_POST)) {
        $URL .= '&return=warning1';
        header("Location: {$URL}");
    } else {
        //Proceed!
        //Setup return variables

        $messageWall = $_POST['messageWall'] ?? '';
        if ($messageWall != 'Y') {
            $messageWall = 'N';
        }
        $messageWallPin = ($messageWall == "Y" && isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_manage.php", "Manage Messages_all") & !empty($_POST['messageWallPin'])) ? $_POST['messageWallPin'] : 'N' ;
        $dateStart = null;
        if (isset($_POST['dateStart'])) {
            if ($_POST['dateStart'] != '') {
                $dateStart = Format::dateConvert($_POST['dateStart']);
            }
        }
        $dateEnd = null;
        if (isset($_POST['dateEnd'])) {
            if ($_POST['dateEnd'] != '') {
                $dateEnd = Format::dateConvert($_POST['dateEnd']);
            }
        }

        $subject = $_POST['subject'] ?? '';
        $body = stripslashes($_POST['body'] ?? '');

        // Check for any emojis in the message and remove them
        $containsEmoji = hasEmojis($body);
        if ($containsEmoji) { 
            $body = removeEmoji($body); 
        }

        // Turn copy-pasted div breaks into paragraph breaks
        $body = str_ireplace(['<div ', '<div>', '</div>'], ['<p ', '<p>', '</p>'], $body);

        if ($subject == '' or $body == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            //Write to database
            try {
                $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'email' => '', 'messageWall' => $messageWall, "messageWallPin" => $messageWallPin, 'messageWall_dateStart' => $dateStart, 'messageWall_dateEnd' => $dateEnd, 'sms' => '', 'subject' => $subject, 'body' => $body, 'gibbonPersonID' => $session->get('gibbonPersonID'), 'confidential' => 'N', 'timestamp' => date('Y-m-d H:i:s'));
                $sql = 'INSERT INTO gibbonMessenger SET gibbonSchoolYearID=:gibbonSchoolYearID, email=:email, messageWall=:messageWall, messageWallPin=:messageWallPin, messageWall_dateStart=:messageWall_dateStart, messageWall_dateEnd=:messageWall_dateEnd, sms=:sms, subject=:subject, body=:body, gibbonPersonID=:gibbonPersonID, confidential=:confidential, timestamp=:timestamp';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                exit();
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            //Last insert ID
            $AI = str_pad($connection2->lastInsertID(), 12, '0', STR_PAD_LEFT);

            $partialFail = false;
            $choices = $_POST['roleCategories'] ?? '';
            if ($choices != '') {
                foreach ($choices as $t) {
                    try {
                        $data = array('AI' => $AI, 't' => $t);
                        $sql = "INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:AI, type='Role Category', id=:t";
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $partialFail = true;
                    }
                }
            }

            if ($partialFail == true) {
                $URL .= '&return=warning1';
                header("Location: {$URL}");
            } else {
                $session->set('pageLoads', null);
                $URL .= $containsEmoji
                    ? "&return=warning3"
                    : "&return=success0&editID=$AI";

                header("Location: {$URL}");
            }
        }
    }
}
