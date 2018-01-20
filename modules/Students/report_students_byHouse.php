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

use Gibbon\Forms\Form;

if (isActionAccessible($guid, $connection2, '/modules/Students/report_students_byHouse.php') == false) {
	//Acess denied
	echo "<div class='error'>" ;
		echo __('You do not have access to this action.');
	echo "</div>" ;
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__('Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__(getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__('Students by House').'</div>';
    echo '</div>';

    $gibbonYearGroupIDList = (isset($_GET['gibbonYearGroupIDList']))? $_GET['gibbonYearGroupIDList'] : '';
    $gibbonYearGroupIDList = explode(',', $gibbonYearGroupIDList);

    try {
        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'today' => date('Y-m-d'));
        $sql = "SELECT gibbonYearGroup.gibbonYearGroupID, gibbonHouse.name AS house, gibbonHouse.gibbonHouseID, gibbonYearGroup.name as yearGroupName, count(gibbonStudentEnrolment.gibbonPersonID) AS total, count(CASE WHEN gibbonPerson.gender='M' THEN gibbonStudentEnrolment.gibbonPersonID END) as totalMale, count(CASE WHEN gibbonPerson.gender='F' THEN gibbonStudentEnrolment.gibbonPersonID END) as totalFemale
                FROM gibbonHouse
                    LEFT JOIN gibbonPerson ON (gibbonPerson.gibbonHouseID=gibbonHouse.gibbonHouseID
                        AND gibbonPerson.status='Full'
                        AND (gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:today)
                        AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:today) )
                    LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID
                        AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID)
                    LEFT JOIN gibbonYearGroup ON (gibbonYearGroup.gibbonYearGroupID=gibbonStudentEnrolment.gibbonYearGroupID)
                GROUP BY gibbonYearGroup.gibbonYearGroupID, gibbonHouse.gibbonHouseID
                HAVING total > 0
                ORDER BY gibbonYearGroup.sequenceNumber, gibbonHouse.name";

        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
    }

    if ($result->rowCount() == 0) {
        echo '<div class="error">';
        echo __('There are no records to display.');
        echo '</div>';
    } else {
        if (isset($_GET['count'])) {
            echo '<p>';
            echo sprintf(__('%1$s students have been assigned to houses. These results include all student counts by house, updated year groups are highlighted in green. Hover over a number to see the balance by gender.'), $_GET['count']);
            echo '</p>';
        }

        $yearGroups = $result->fetchAll(\PDO::FETCH_GROUP);

        // Group each year group result by house
        foreach ($yearGroups as $gibbonYearGroupID => &$yearGroup) {
            $yearGroup = array_reduce(array_keys($yearGroup), function ($carry, $key) use ($yearGroup) {
                $carry[$yearGroup[$key]['house']] = $yearGroup[$key];
                return $carry;
            }, array());
        }
        // Grab unique headings across the results
        $headings = array_reduce($yearGroups, function($carry, $value) {
            $carry = array_merge($carry, array_column($value, 'house'));
            return array_unique($carry);
        }, array());

        $totals = array_fill_keys($headings, array());

        echo '<table cellspacing="0" style="width: 100%">';
        echo '<tr class="head">';
        echo '<th style="width: 20%">';
        echo __('Year Group');
        echo '</th>';

        foreach ($headings as $house) {
            echo '<th style="width: '.(80 / count($headings)).'%">';
            echo __($house);
            echo '</th>';
        }
        echo '</tr>';

        foreach ($yearGroups as $gibbonYearGroupID => $rowData) {

            $row = current($rowData);
            $rowClass = (in_array($gibbonYearGroupID, $gibbonYearGroupIDList))? 'current' : '';

            echo '<tr class="'.$rowClass.'">';
            echo '<td>';
            echo $row['yearGroupName'];
            echo '</td>';

            foreach ($headings as $heading) {
                $data = (isset($rowData[$heading]))? $rowData[$heading] : null;

                echo '<td>';
                if (!empty($data)) {
                    echo '<span title="'.$data['totalFemale'].' '.__('Female').'<br/>'.$data['totalMale'].' '.__('Male').'">';
                    echo $data['total'];
                    echo '</span>';

                    // Append the current totals to the running totals for each house
                    $totals[$data['house']] = array_reduce(array_keys($data), function ($carry, $key) use ($data) {
                        $carry[$key] = (isset($carry[$key]))? $carry[$key] + $data[$key] : $data[$key];
                        return $carry;
                    }, $totals[$data['house']]);
                }
                echo '</td>';
            }
            echo '</tr>';
        }

        // Display the runnung totals for each house
        echo '<tr class="dull">';
        echo '<td>'.__('Total').'</td>';
        foreach ($totals as $houseName => $data) {
            echo '<td>';
            echo '<span title="'.$data['totalFemale'].' '.__('Female').'<br/>'.$data['totalMale'].' '.__('Male').'">';
            echo $data['total'];
            echo '</span>';
            echo '</td>';
        }
        echo '</tr>';

        echo '</table>';
    }
}
