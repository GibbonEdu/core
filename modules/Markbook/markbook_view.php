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

@session_start();

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();


//Module includes
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';


if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_view.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'Your request failed because you do not have access to this action.');
    echo '</div>';
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_GET['q'], $connection2);
    $highestAction2 = getHighestGroupedAction($guid, '/modules/Markbook/markbook_edit.php', $connection2);
    if ($highestAction == false) {
        echo "<div class='error'>";
        echo __($guid, 'The highest grouped action cannot be determined.');
        echo '</div>';
    } else {
        $alert = getAlert($guid, $connection2, 002);

        // Define a randomized lock for this script
        define("MARKBOOK_VIEW_LOCK", sha1( $highestAction . $_SESSION[$guid]['gibbonPersonID'] ) . date('zWy') );

        //VIEW ACCESS TO ALL MARKBOOK DATA
        if ($highestAction == 'View Markbook_allClassesAllData') {
            require_once './modules/'.$_SESSION[$guid]['module'].'/markbook_view_allClassesAllData.php';
        }
        //VIEW ACCESS TO MY OWN MARKBOOK DATA
        elseif ($highestAction == 'View Markbook_myMarks') {
            require_once './modules/'.$_SESSION[$guid]['module'].'/markbook_view_myMarks.php';
        }
        //VIEW ACCESS TO MY CHILDREN'S MARKBOOK DATA
        elseif ($highestAction == 'View Markbook_viewMyChildrensClasses') {
            require_once './modules/'.$_SESSION[$guid]['module'].'/markbook_view_viewMyChildrensClasses.php';
        }
    }
}
?>