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

if (isActionAccessible($guid, $connection2, '/modules/Students/applicationForm_manage_edit.php') == false) {
    echo 0;
} else {
    $searchTerm = (isset($_REQUEST['q']))? $_REQUEST['q'] : '';

    // Allow for * as wildcard (as well as %)
    $searchTerm = str_replace('*', '%', $searchTerm);

    // Cancel out early for empty searches
    if (empty($searchTerm)) {
        die('[]');
    }

    $resultSet = array();
    $resultError = '[{"id":"","name":"Database Error"}]';

    // Search
    $data = array('search' => '%'.$searchTerm.'%', 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
    $sql = "SELECT gibbonFamily.gibbonFamilyID as id, gibbonFamily.name as familyName, GROUP_CONCAT(DISTINCT CONCAT(adult.title, ' ', adult.preferredName, ' ', adult.surname) SEPARATOR ', ') as parentNames, GROUP_CONCAT(DISTINCT CONCAT(child.preferredName, ' ', child.surname, ' ', gibbonRollGroup.nameShort) SEPARATOR ', ') as studentNames
            FROM gibbonFamily
            JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID)
            JOIN gibbonPerson AS adult ON (gibbonFamilyAdult.gibbonPersonID=adult.gibbonPersonID)
            JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID)
            JOIN gibbonPerson AS child ON (gibbonFamilyChild.gibbonPersonID=child.gibbonPersonID)
            JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=child.gibbonPersonID)
            JOIN gibbonRollGroup ON (gibbonRollGroup.gibbonRollGroupID=gibbonStudentEnrolment.gibbonRollGroupID)
            WHERE (gibbonFamily.name LIKE :search OR child.surname LIKE :search OR child.preferredName LIKE :search OR child.firstName LIKE :search 
                  OR adult.surname LIKE :search OR adult.preferredName LIKE :search OR adult.firstName LIKE :search)
            AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
            GROUP BY gibbonFamily.gibbonFamilyID
            ORDER BY gibbonFamily.name
            LIMIT 50
    ";
    $result = $pdo->executeQuery($data, $sql);

    if ($pdo->getQuerySuccess() == false) {
        die($resultError);
    }

    // Get and transform the results into jquery
    $resultSet = ($result && $result->rowCount() > 0)? $result->fetchAll() : array();
    $resultList = array_map(function ($item) {
        return '{"id": "'.$item['id'].'", "name": "'.$item['familyName'].'<br/><div style=\"padding-left:14px;display:inline-block;\"><small>Adults: '.$item['parentNames'].'<br>Students: '.$item['studentNames'].'</small></div>"}';
    }, $resultSet);

    echo '['.implode(',', $resultList).']';
}
