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

//Module includes
use Gibbon\Domain\System\SettingGateway;

require_once __DIR__ . '/moduleFunctions.php';

// common variables
$makeUnitsPublic = $container->get(SettingGateway::class)->getSettingByScope('Planner', 'makeUnitsPublic');
$gibbonUnitID = $_GET['gibbonUnitID'] ?? '';

$page->breadcrumbs->add(__('Learn With Us'));

if ($makeUnitsPublic != 'Y') {
    //Acess denied
    $page->addError(__('Your request failed because you do not have access to this action.'));
} else {
    //Get action with highest precendence
    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');

    $page->navigator->addSchoolYearNavigation($gibbonSchoolYearID, ['sidebar' => 'false']);

    //Fetch units
    
    $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
    $sql = "SELECT gibbonUnitID, gibbonUnit.gibbonCourseID, nameShort, gibbonUnit.name, gibbonUnit.description, gibbonCourse.name AS course FROM gibbonUnit JOIN gibbonCourse ON gibbonUnit.gibbonCourseID=gibbonCourse.gibbonCourseID WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND sharedPublic='Y' ORDER BY course, name";
    $result = $connection2->prepare($sql);
    $result->execute($data);

    if ($result->rowCount() < 1) {
        echo $page->getBlankSlate();
    } else {
        echo "<table cellspacing='0' style='width: 100%'>";
        echo "<tr class='head'>";
        echo "<th style='width: 150px'>";
        echo __('Course');
        echo '</th>';
        echo "<th style='width: 150px'>";
        echo __('Name');
        echo '</th>';
        echo "<th style='width: 450px'>";
        echo __('Description');
        echo '</th>';
        echo "<th style='width: 50px'>";
        echo __('Actions');
        echo '</th>';
        echo '</tr>';

        $count = 0;
        $rowNum = 'odd';
        while ($row = $result->fetch()) {
            if ($count % 2 == 0) {
                $rowNum = 'even';
            } else {
                $rowNum = 'odd';
            }

            //COLOR ROW BY STATUS!
            echo "<tr class=$rowNum>";
            echo '<td>';
            echo $row['course'];
            echo '</td>';
            echo '<td>';
            echo $row['name'];
            echo '</td>';
            echo "<td style='max-width: 270px'>";
            echo $row['description'];
            echo '</td>';
            echo '<td>';
            echo "<a href='".$session->get('absoluteURL').'/index.php?q=/modules/'.$session->get('module').'/units_public_view.php&gibbonUnitID='.$row['gibbonUnitID']."&gibbonSchoolYearID=$gibbonSchoolYearID&sidebar=false'><img title='".__('View Details')."' src='./themes/".$session->get('gibbonThemeName')."/img/plus.png'/></a>";
            echo '</td>';
            echo '</tr>';

            ++$count;
        }
        echo '</table>';
    }
}
