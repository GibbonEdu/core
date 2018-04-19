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

include '../../gibbon.php';

include './moduleFunctions.php';

$gibbonPersonID = $_POST['gibbonPersonID'];
$gibbonMessengerID = $_POST['gibbonMessengerID'];
$mode = $_POST['mode']; //can be "add" or "remove"
$comment = '';
if (isset($_POST['comment'])) {
    $comment = $_POST['comment'];
}

if ($gibbonPersonID == '' or $gibbonMessengerID == '' or ($mode != 'add' and $mode != 'remove')) {
    echo __($guid, 'Error');
} else {
    //Prepare scripts abd buttons to return via AJAX
    $script = '<script type="text/javascript">
		$(document).ready(function(){
			$("#starAdd'.$gibbonMessengerID.'").click(function(){
				$("#star'.$gibbonMessengerID.'").load("'.$_SESSION[$guid]['absoluteURL'].'/modules/Messenger/messageWall_view_starAjax.php",{"gibbonPersonID": "'.$gibbonPersonID.'", "gibbonMessengerID": "'.$gibbonMessengerID.'", "mode": "add", "comment": "'.$comment.'"});
			});
			$("#starRemove'.$gibbonMessengerID.'").click(function(){
				$("#star'.$gibbonMessengerID.'").load("'.$_SESSION[$guid]['absoluteURL'].'/modules/Messenger/messageWall_view_starAjax.php",{"gibbonPersonID": "'.$gibbonPersonID.'", "gibbonMessengerID": "'.$gibbonMessengerID.'", "mode": "remove", "comment": "'.$comment.'"});
			});
		});
	</script>';
    $on = $script."<a id='starRemove".$gibbonMessengerID."' onclick='return false;' href='#'><img style='margin-top: -8px; margin-bottom: 5px' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/like_on.png'></a>";
    $off = $script."<a id='starAdd".$gibbonMessengerID."' onclick='return false;' href='#'><img style='margin-top: -8px; margin-bottom: 5px' src='".$_SESSION[$guid]['absoluteURL'].'/themes/'.$_SESSION[$guid]['gibbonThemeName']."/img/like_off.png'></a>";

    //Act based on the mode
    if ($mode == 'add') { //ADD
        $return = setLike($connection2, 'Messenger', $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonMessengerID', $gibbonMessengerID, $_SESSION[$guid]['gibbonPersonID'], $gibbonPersonID, 'Messenger - Message Like', $comment);
        if ($return == false) {
            echo $off;
        } else {
            echo $on;
        }
    } elseif ($mode == 'remove') { //REMOVE
        $return = deleteLike($connection2, 'Messenger', 'gibbonMessengerID', $gibbonMessengerID, $_SESSION[$guid]['gibbonPersonID'], $gibbonPersonID, 'Messenger - Message Like');
        if ($return == false) {
            echo $on;
        } else {
            echo $off;
        }
    }
}
