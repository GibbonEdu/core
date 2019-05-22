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
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

use Gibbon\Forms\Prefab\DeleteForm;

//Module includes
include './modules/Badges/moduleFunctions.php';


if (isActionAccessible($guid, $connection2, '/modules/Badges/badges_manage_delete.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo 'You do not have access to this action.';
    echo '</div>';
} else {
    //Proceed!
    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if school year specified
    $badgesBadgeID = $_GET['badgesBadgeID'];
    if ($badgesBadgeID == '') {
        echo "<div class='error'>";
        echo 'You have not specified a policy.';
        echo '</div>';
    } else {
        try {
            $data = array('badgesBadgeID' => $badgesBadgeID);
            $sql = 'SELECT * FROM badgesBadge WHERE badgesBadgeID=:badgesBadgeID';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            echo "<div class='error'>" . $e->getMessage() . '</div>';
        }

        if ($result->rowCount() != 1) {
            echo "<div class='error'>";
            echo 'The selected policy does not exist.';
            echo '</div>';
        } else {
            //Let's go!
            $row = $result->fetch();

            if ($_GET['category'] != '') {
                echo "<div class='linkTop'>";
                echo "<a href='" . $gibbon->session->get('absoluteURL','') . '/index.php?q=/modules/Badges/badges_manage.php&search=' . $_GET['search'] ?? '' . '&category=' . $_GET['category'] ?? ''. "'>Back to Search Results</a>";
                echo '</div>';
            }

            $form = DeleteForm::createForm($gibbon->session->get('absoluteURL','').'/modules/'.$gibbon->session->get('module')."/badges_manage_deleteProcess.php?badgesBadgeID=$badgesBadgeID&search=".$_GET['category']."&category=" . $_GET['category']);
            echo $form->getOutput();
        }
    }
}
?>
