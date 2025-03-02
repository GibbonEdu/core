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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

require_once '../../gibbon.php';

require_once  './moduleFunctions.php';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address']).'/badges_manage_add.php&search='.$_GET['search'];

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/badges_manage_add.php') == false) {
    //Fail 0
    $URL = $URL.'&return=error0';
    header("Location: {$URL}");
} else {
    if (isModuleAccessible($guid, $connection2, '/modules/Badges/badges_manage.php') == false) {
        //Fail 0
        $URL = $URL.'&return=error0';
        header("Location: {$URL}");
    } else {
        //Proceed!
        $badgesBadgeID = $_POST['badgesBadgeID'] ?? '';
        $active = $_POST['active'] ?? '';
        $unitsCompleteTotal = !empty($_POST['unitsCompleteTotal']) ? $_POST['unitsCompleteTotal'] : null;
        $unitsCompleteThisYear = !empty($_POST['unitsCompleteThisYear']) ? $_POST['unitsCompleteThisYear'] :  null;
        $unitsCompleteDepartmentCount = !empty($_POST['unitsCompleteDepartmentCount']) ? $_POST['unitsCompleteDepartmentCount'] : null;
        $unitsCompleteIndividual = !empty($_POST['unitsCompleteIndividual']) ? $_POST['unitsCompleteIndividual'] : null;
        $unitsCompleteGroup = !empty($_POST['unitsCompleteGroup']) ? $_POST['unitsCompleteGroup'] : null;
        $difficultyLevelMaxAchieved = !empty($_POST['difficultyLevelMaxAchieved']) ? $_POST['difficultyLevelMaxAchieved'] : null;
        $specificUnitsCompleteList = '';
        if (isset($_POST['specificUnitsComplete'])) {
            $specificUnitsComplete = $_POST['specificUnitsComplete'];
            foreach ($specificUnitsComplete as $specificUnitComplete) {
                $specificUnitsCompleteList .= $specificUnitComplete.',';
            }
            $specificUnitsCompleteList = substr($specificUnitsCompleteList, 0, -1);
        }

        if ($badgesBadgeID == '' or $active == '' or ($unitsCompleteTotal == '' and $unitsCompleteThisYear == '' and $unitsCompleteDepartmentCount == '' and $unitsCompleteIndividual == '' and $unitsCompleteGroup == '' and $difficultyLevelMaxAchieved == '' and $specificUnitsCompleteList == '')) {
            //Fail 3
            $URL = $URL.'&return=error3';
            header("Location: {$URL}");
        } else {
            //Check if badge exists and is active
            try {
                $data = array('badgesBadgeID' => $badgesBadgeID);
                $sql = 'SELECT badgesBadgeID FROM badgesBadge WHERE active=\'Y\' AND badgesBadgeID=:badgesBadgeID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                //Fail 2
                $URL = $URL.'&return=error2';
                header("Location: {$URL}");
                exit();
            }
            if ($result->rowCount()!=1) {
                //Fail 2
                $URL = $URL.'&return=error2';
                header("Location: {$URL}");
            }
            else {
                //Write to database
                try {
                    $data = array('badgesBadgeID' => $badgesBadgeID, 'active' => $active, 'unitsCompleteTotal' => $unitsCompleteTotal, 'unitsCompleteThisYear' => $unitsCompleteThisYear, 'unitsCompleteDepartmentCount' => $unitsCompleteDepartmentCount, 'unitsCompleteIndividual' => $unitsCompleteIndividual, 'unitsCompleteGroup' => $unitsCompleteGroup, 'difficultyLevelMaxAchieved' => $difficultyLevelMaxAchieved, 'specificUnitsComplete' => $specificUnitsCompleteList);
                    $sql = 'INSERT INTO freeLearningBadge SET badgesBadgeID=:badgesBadgeID, active=:active, unitsCompleteTotal=:unitsCompleteTotal, unitsCompleteThisYear=:unitsCompleteThisYear, unitsCompleteDepartmentCount=:unitsCompleteDepartmentCount, unitsCompleteIndividual=:unitsCompleteIndividual, unitsCompleteGroup=:unitsCompleteGroup, difficultyLevelMaxAchieved=:difficultyLevelMaxAchieved, specificUnitsComplete=:specificUnitsComplete';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    //Fail 2

                    $URL = $URL.'&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                $AI = str_pad($connection2->lastInsertID(), 8, '0', STR_PAD_LEFT);

                //Success 0
                $URL = $URL.'&return=success0&editID='.$AI;
                header("Location: {$URL}");
            }
        }
    }
}
