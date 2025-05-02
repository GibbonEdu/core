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

use Gibbon\Services\Format;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\FreeLearning\Domain\UnitStudentGateway;

function getUnitList($connection2, $guid, $gibbonPersonID, $roleCategory, $highestAction, $gibbonDepartmentID = null, $difficulty = null, $name = null, $showInactive = null, $publicUnits = null, $freeLearningUnitID = null, $difficulties = null)
{
    global $session;

    $return = array();

    $sql = '';
    $data = array();
    $sqlWhere = 'AND ';
    //Apply filters
    if ($gibbonDepartmentID != '') {
        if (is_numeric($gibbonDepartmentID)) {
            $data['gibbonDepartmentID'] = $gibbonDepartmentID;
            $sqlWhere .= "gibbonDepartmentIDList LIKE concat('%', :gibbonDepartmentID, '%') AND ";
        }
        else {
            $data['course'] = $gibbonDepartmentID;
            $sqlWhere .= "course=:course AND ";
        }
    }
    if ($difficulty != '') {
        $data['difficulty'] = $difficulty;
        $sqlWhere .= 'difficulty=:difficulty AND ';
    }
    if ($name != '') {
        $data['name'] = $name;
        $sqlWhere .= "freeLearningUnit.name LIKE concat('%', :name, '%') AND ";
    }

    //Apply $freeLearningUnitID search
    if ($freeLearningUnitID != null) {
        $data['freeLearningUnitID'] = $freeLearningUnitID;
        $sqlWhere .= 'freeLearningUnit.freeLearningUnitID=:freeLearningUnitID AND ';
    }

    //Tidy up $sqlWhere
    if ($sqlWhere == 'AND ') {
        $sqlWhere = '';
    } else {
        $sqlWhere = substr($sqlWhere, 0, -5);
    }

    //Sort out difficulty order
    $difficultyOrder = '';
    if ($difficulties != null) {
        if ($difficulties != false) {
            $difficultyOrder = 'FIELD(difficulty';
            $difficulties = explode(',', $difficulties);
            foreach ($difficulties as $difficultyOption) {
                $difficultyOrder .= ",'".__m($difficultyOption)."'";
            }
            $difficultyOrder .= '), ';
        }
    }

    //Do it!
    if ($publicUnits == 'Y' and !$session->has('username')) {
        $sql = "SELECT DISTINCT freeLearningUnit.*, GROUP_CONCAT(DISTINCT freeLearningUnitPrerequisite.freeLearningUnitIDPrerequisite SEPARATOR ',') as freeLearningUnitIDPrerequisiteList, NULL AS status FROM freeLearningUnit LEFT JOIN freeLearningUnitPrerequisite ON freeLearningUnitPrerequisite.freeLearningUnitID=freeLearningUnit.freeLearningUnitID WHERE sharedPublic='Y' AND gibbonYearGroupIDMinimum IS NULL AND active='Y' $sqlWhere GROUP BY freeLearningUnit.freeLearningUnitID ORDER BY $difficultyOrder name";
    } else {
        if ($highestAction == 'Browse Units_all') {
            $data['gibbonPersonID'] = $gibbonPersonID;
            if ($showInactive == 'Y') {
                $sql = "SELECT DISTINCT freeLearningUnit.*, GROUP_CONCAT(DISTINCT freeLearningUnitPrerequisite.freeLearningUnitIDPrerequisite SEPARATOR ',') as freeLearningUnitIDPrerequisiteList, freeLearningUnitStudent.status FROM freeLearningUnit LEFT JOIN freeLearningUnitPrerequisite ON freeLearningUnitPrerequisite.freeLearningUnitID=freeLearningUnit.freeLearningUnitID LEFT JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID AND gibbonPersonIDStudent=:gibbonPersonID) WHERE (active='Y' OR active='N') $sqlWhere GROUP BY freeLearningUnit.freeLearningUnitID ORDER BY $difficultyOrder name";
            } else {
                $sql = "SELECT DISTINCT freeLearningUnit.*, GROUP_CONCAT(DISTINCT freeLearningUnitPrerequisite.freeLearningUnitIDPrerequisite SEPARATOR ',') as freeLearningUnitIDPrerequisiteList, freeLearningUnitStudent.status FROM freeLearningUnit LEFT JOIN freeLearningUnitPrerequisite ON freeLearningUnitPrerequisite.freeLearningUnitID=freeLearningUnit.freeLearningUnitID LEFT JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID AND gibbonPersonIDStudent=:gibbonPersonID) WHERE active='Y' $sqlWhere GROUP BY freeLearningUnit.freeLearningUnitID ORDER BY $difficultyOrder name";
            }
        } elseif ($highestAction == 'Browse Units_prerequisites') {
            if ($roleCategory == 'Student') {
                $data['gibbonPersonID'] =$session->get('gibbonPersonID');
                $data['gibbonPersonID2'] = $session->get('gibbonPersonID');
                $data['gibbonSchoolYearID'] = $session->get('gibbonSchoolYearID');
                $sql = "SELECT DISTINCT freeLearningUnit.*, GROUP_CONCAT(DISTINCT freeLearningUnitPrerequisite.freeLearningUnitIDPrerequisite SEPARATOR ',') as freeLearningUnitIDPrerequisiteList, freeLearningUnitStudent.status, gibbonYearGroup.sequenceNumber AS sn1, gibbonYearGroup2.sequenceNumber AS sn2
                FROM freeLearningUnit
                LEFT JOIN freeLearningUnitPrerequisite ON freeLearningUnitPrerequisite.freeLearningUnitID=freeLearningUnit.freeLearningUnitID
                LEFT JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID AND gibbonPersonIDStudent=:gibbonPersonID2)
                LEFT JOIN gibbonYearGroup ON (freeLearningUnit.gibbonYearGroupIDMinimum=gibbonYearGroup.gibbonYearGroupID)
                LEFT JOIN gibbonStudentEnrolment ON (gibbonPersonID=:gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID)
                LEFT JOIN gibbonYearGroup AS gibbonYearGroup2 ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup2.gibbonYearGroupID)
                WHERE active='Y' AND availableStudents='Y' $sqlWhere AND (gibbonYearGroup.sequenceNumber IS NULL OR gibbonYearGroup.sequenceNumber<=gibbonYearGroup2.sequenceNumber)
                GROUP BY freeLearningUnit.freeLearningUnitID
                ORDER BY $difficultyOrder name";
            }
            else if ($roleCategory == 'Parent') {
                $data['gibbonPersonID'] = $session->get('gibbonPersonID');
                $sql = "SELECT DISTINCT freeLearningUnit.*, GROUP_CONCAT(DISTINCT freeLearningUnitPrerequisite.freeLearningUnitIDPrerequisite SEPARATOR ',') as freeLearningUnitIDPrerequisiteList, freeLearningUnitStudent.status FROM freeLearningUnit LEFT JOIN freeLearningUnitPrerequisite ON freeLearningUnitPrerequisite.freeLearningUnitID=freeLearningUnit.freeLearningUnitID LEFT JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID AND gibbonPersonIDStudent=:gibbonPersonID) WHERE active='Y' AND availableParents='Y' $sqlWhere GROUP BY freeLearningUnit.freeLearningUnitID ORDER BY $difficultyOrder name";
            }
            else if ($roleCategory == 'Staff') {
                $data['gibbonPersonID'] = $session->get('gibbonPersonID');
                $sql = "SELECT DISTINCT freeLearningUnit.*, GROUP_CONCAT(DISTINCT freeLearningUnitPrerequisite.freeLearningUnitIDPrerequisite SEPARATOR ',') as freeLearningUnitIDPrerequisiteList, freeLearningUnitStudent.status FROM freeLearningUnit LEFT JOIN freeLearningUnitPrerequisite ON freeLearningUnitPrerequisite.freeLearningUnitID=freeLearningUnit.freeLearningUnitID LEFT JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID AND gibbonPersonIDStudent=:gibbonPersonID) WHERE active='Y' AND availableStaff='Y' $sqlWhere GROUP BY freeLearningUnit.freeLearningUnitID ORDER BY $difficultyOrder name";
            }
            else if ($roleCategory == 'Other') {
                $data['gibbonPersonID'] = $session->get('gibbonPersonID');
                $sql = "SELECT DISTINCT freeLearningUnit.*, GROUP_CONCAT(DISTINCT freeLearningUnitPrerequisite.freeLearningUnitIDPrerequisite SEPARATOR ',') as freeLearningUnitIDPrerequisiteList, freeLearningUnitStudent.status FROM freeLearningUnit LEFT JOIN freeLearningUnitPrerequisite ON freeLearningUnitPrerequisite.freeLearningUnitID=freeLearningUnit.freeLearningUnitID LEFT JOIN freeLearningUnitStudent ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID AND gibbonPersonIDStudent=:gibbonPersonID) WHERE active='Y' AND availableOther='Y' $sqlWhere GROUP BY freeLearningUnit.freeLearningUnitID ORDER BY $difficultyOrder name";
            }
        }
    }

    $return[0] = $data;
    $return[1] = $sql;
    return $return;

}

function prerequisitesRemoveInactive($connection2, $prerequisites)
{
    $return = false;

    if ($prerequisites == '') {
        $return = '';
    } else {
        $prerequisites = explode(',', $prerequisites);
        foreach ($prerequisites as $prerequisite) {
            try {
                $data = array('freeLearningUnitID' => $prerequisite);
                $sql = "SELECT * FROM freeLearningUnit WHERE freeLearningUnitID=:freeLearningUnitID AND active='Y'";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
            }
            if ($result->rowCount() == 1) {
                $return .= $prerequisite.',';
            }
        }
        if (substr($return, -1) == ',') {
            $return = substr($return, 0, -1);
        }
    }

    return $return;
}

//When $strict is false, a Complete - Pending unit counts as complete
function prerequisitesMet($connection2, $gibbonPersonID, $prerequisites, $strict = false)
{
    $return = false;

    //Get all courses completed
    $complete = array();
    try {
        $data = array('gibbonPersonID' => $gibbonPersonID);
        if ($strict) {
            $sql = "SELECT * FROM freeLearningUnitStudent WHERE gibbonPersonIDStudent=:gibbonPersonID AND (status='Complete - Approved' OR status='Exempt') ORDER BY freeLearningUnitID";
        }
        else {
            $sql = "SELECT * FROM freeLearningUnitStudent WHERE gibbonPersonIDStudent=:gibbonPersonID AND (status='Complete - Approved' OR status='Complete - Pending' OR status='Exempt') ORDER BY freeLearningUnitID";
        }
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
    }

    while ($row = $result->fetch()) {
        $complete[$row['freeLearningUnitID']] = true;
    }

    //Check prerequisites against courses completed
    if ($prerequisites == '') {
        $return = true;
    } else {
        $prerequisites = explode(',', $prerequisites);
        $prerequisiteCount = count($prerequisites);
        $prerequisiteMet = 0;
        foreach ($prerequisites as $prerequisite) {
            if (isset($complete[$prerequisite])) {
                ++$prerequisiteMet;
            }
        }
        if ($prerequisiteMet == $prerequisiteCount) {
            $return = true;
        }
    }

    return $return;
}

//Option second argument get's blocks only for selected units.
function getBlocksArray($connection2, $freeLearningUnitID = null)
{
    $return = false;
    try {
        if (is_null($freeLearningUnitID)) {
            $data = array();
            $sql = 'SELECT * FROM freeLearningUnitBlock ORDER BY freeLearningUnitID';
        } else {
            $data = array('freeLearningUnitID' => $freeLearningUnitID);
            $sql = 'SELECT * FROM freeLearningUnitBlock WHERE freeLearningUnitID=:freeLearningUnitID ORDER BY freeLearningUnitID';
        }
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
    }

    if ($result->rowCount() > 0) {
        $return = array();
        while ($row = $result->fetch()) {
            $return[$row['freeLearningUnitBlockID']][0] = $row['freeLearningUnitID'];
            $return[$row['freeLearningUnitBlockID']][1] = $row['title'];
            $return[$row['freeLearningUnitBlockID']][2] = $row['length'];
        }
    }

    return $return;
}

function getLearningAreaArray($connection2)
{
    $return = false;

    try {
        $data = array();
        $sql = "SELECT * FROM gibbonDepartment WHERE type='Learning Area' ORDER BY name";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
    }

    if ($result->rowCount() > 0) {
        $return = array();
        while ($row = $result->fetch()) {
            $return[$row['gibbonDepartmentID']] = $row['name'];
        }
    }

    return $return;
}

//If $freeLearningUnitID is NULL, all units are returned: otherwise, only the specified
function getAuthorsArray($connection2, $freeLearningUnitID = null)
{
    $return = false;

    try {
        if (is_null($freeLearningUnitID)) {
            $data = array();
            $sql = 'SELECT freeLearningUnitAuthorID, freeLearningUnitID, gibbonPerson.gibbonPersonID, gibbonPerson.surname AS gibbonPersonsurname, gibbonPerson.preferredName AS gibbonPersonpreferredName, gibbonPerson.website AS gibbonPersonwebsite, gibbonPerson.gibbonPersonID, freeLearningUnitAuthor.surname AS freeLearningUnitAuthorsurname, freeLearningUnitAuthor.preferredName AS freeLearningUnitAuthorpreferredName, freeLearningUnitAuthor.website AS freeLearningUnitAuthorwebsite FROM freeLearningUnitAuthor LEFT JOIN gibbonPerson ON (freeLearningUnitAuthor.gibbonPersonID=gibbonPerson.gibbonPersonID) ORDER BY gibbonPersonsurname, freeLearningUnitAuthorsurname, gibbonPersonpreferredName, freeLearningUnitAuthorpreferredName';
        } else {
            $data = array('freeLearningUnitID' => $freeLearningUnitID);
            $sql = 'SELECT freeLearningUnitAuthorID, freeLearningUnitID, gibbonPerson.gibbonPersonID, gibbonPerson.surname AS gibbonPersonsurname, gibbonPerson.preferredName AS gibbonPersonpreferredName, gibbonPerson.website AS gibbonPersonwebsite, gibbonPerson.gibbonPersonID, freeLearningUnitAuthor.surname AS freeLearningUnitAuthorsurname, freeLearningUnitAuthor.preferredName AS freeLearningUnitAuthorpreferredName, freeLearningUnitAuthor.website AS freeLearningUnitAuthorwebsite FROM freeLearningUnitAuthor LEFT JOIN gibbonPerson ON (freeLearningUnitAuthor.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE freeLearningUnitID=:freeLearningUnitID ORDER BY gibbonPersonsurname, freeLearningUnitAuthorsurname, gibbonPersonpreferredName, freeLearningUnitAuthorpreferredName';
        }
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo $e->getMessage();
    }

    if ($result->rowCount() > 0) {
        $return = array();
        while ($row = $result->fetch()) {
            if ($row['gibbonPersonID'] != null) {
                $return[$row['freeLearningUnitAuthorID']][0] = $row['freeLearningUnitID'];
                $return[$row['freeLearningUnitAuthorID']][1] = Format::name('', $row['gibbonPersonpreferredName'], $row['gibbonPersonsurname'], 'Student', false);
                $return[$row['freeLearningUnitAuthorID']][2] = $row['gibbonPersonID'];
                $return[$row['freeLearningUnitAuthorID']][3] = $row['gibbonPersonwebsite'];
            } else {
                $return[$row['freeLearningUnitAuthorID']][0] = $row['freeLearningUnitID'];
                $return[$row['freeLearningUnitAuthorID']][1] = Format::name('', $row['freeLearningUnitAuthorpreferredName'], $row['freeLearningUnitAuthorsurname'], 'Student', false);
                $return[$row['freeLearningUnitAuthorID']][2] = $row['gibbonPersonID'];
                $return[$row['freeLearningUnitAuthorID']][3] = $row['freeLearningUnitAuthorwebsite'];
            }
        }
    }

    return $return;
}

function getUnitsArray($connection2)
{
    $return = false;

    try {
        $data = array();
        $sql = "SELECT * FROM freeLearningUnit WHERE active='Y'";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
    }

    if ($result->rowCount() > 0) {
        $return = array();
        while ($row = $result->fetch()) {
            $return[$row['freeLearningUnitID']][0] = $row['name'];
        }
    }

    return $return;
}

//Set $limit=TRUE to only return departments that the user has curriculum editing rights in
function getLearningAreas($connection2, $guid, $limit = false)
{
    global $session;

    $output = false;
    try {
        if ($limit == true) {
            $data = array('gibbonPersonID' => $session->get('gibbonPersonID'));
            $sql = "SELECT * FROM gibbonDepartment JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE type='Learning Area' AND gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)')  ORDER BY name";
        } else {
            $data = array();
            $sql = "SELECT * FROM gibbonDepartment WHERE type='Learning Area' ORDER BY name";
        }
        $result = $connection2->prepare($sql);
        $result->execute($data);
        while ($row = $result->fetch()) {
            $output .= $row['gibbonDepartmentID'].',';
            $output .= $row['name'].',';
        }
    } catch (PDOException $e) {
    }

    if ($output != false) {
        $output = substr($output, 0, (strlen($output) - 1));
        $output = explode(',', $output);
    }

    return $output;
}

//Does not return errors, just does its best to get the job done
function grantBadges($connection2, $guid, $gibbonPersonID) {

    global $session, $container, $pdo;

    //Sort out difficulty order
    $difficulties = $container->get(SettingGateway::class)->getSettingByScope('Free Learning', 'difficultyOptions');
    if ($difficulties != false) {
        $difficulties = explode(',', $difficulties);
    }

    //Get list of active awards, including details on those already issued
    try {
        $data = array('gibbonPersonID' => $gibbonPersonID);
        $sql = "SELECT freeLearningBadge.*, gibbonPersonID
            FROM freeLearningBadge
                JOIN badgesBadge ON (freeLearningBadge.badgesBadgeID=badgesBadge.badgesBadgeID)
                LEFT JOIN badgesBadgeStudent ON (badgesBadgeStudent.badgesBadgeID=badgesBadge.badgesBadgeID AND gibbonPersonID=:gibbonPersonID)
            WHERE
                freeLearningBadge.active='Y'
                AND badgesBadge.active='Y'
        ";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) { }

    while ($row = $result->fetch()) {
        if (is_null($row['gibbonPersonID'])) { //Only work on awards not yet given to this person
            $hitsNeeded = 0 ;
            $hitsActually = 0 ;
            //CHECK AWARD CONDITIONS
            if ($row['unitsCompleteTotal'] > 0) { //UNITS COMPLETE TOTAL
                $hitsNeeded ++;
                try {
                    //Count conditions
                    $dataCount = array('gibbonPersonID' => $gibbonPersonID);
                    $sqlCount = "SELECT freeLearningUnitStudentID FROM freeLearningUnitStudent WHERE gibbonPersonIDStudent=:gibbonPersonID AND status='Complete - Approved'";
                    $resultCount = $connection2->prepare($sqlCount);
                    $resultCount->execute($dataCount);
                } catch (PDOException $e) { }

                if ($resultCount->rowCount() >= $row['unitsCompleteTotal']) {
                    $hitsActually ++;
                }
            }

            if ($row['unitsCompleteThisYear'] > 0) { //UNITS COMPLETE THIS YEAR
                $hitsNeeded ++;
                try {
                    //Count conditions
                    $dataCount = array('gibbonPersonID' => $gibbonPersonID, 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
                    $sqlCount = "SELECT freeLearningUnitStudentID FROM freeLearningUnitStudent WHERE gibbonPersonIDStudent=:gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND status='Complete - Approved'";
                    $resultCount = $connection2->prepare($sqlCount);
                    $resultCount->execute($dataCount);
                } catch (PDOException $e) { }

                if ($resultCount->rowCount() >= $row['unitsCompleteThisYear']) {
                    $hitsActually ++;
                }
            }

            if ($row['unitsCompleteDepartmentCount'] > 0) { //UNITS COMPLETE DEPARTMENT COUNT
                $hitsNeeded ++;
                try {
                    //Count conditions
                    $dataCount = array('gibbonPersonID' => $gibbonPersonID);
                    $sqlCount = "SELECT DISTINCT gibbonDepartment.name
                        FROM freeLearningUnitStudent
                            JOIN freeLearningUnit ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID)
                            JOIN gibbonDepartment ON (freeLearningUnit.gibbonDepartmentIDList LIKE concat( '%', gibbonDepartment.gibbonDepartmentID, '%' ))
                        WHERE gibbonPersonIDStudent=:gibbonPersonID
                            AND status='Complete - Approved'";
                    $resultCount = $connection2->prepare($sqlCount);
                    $resultCount->execute($dataCount);
                } catch (PDOException $e) { }

                if ($resultCount->rowCount() >= $row['unitsCompleteDepartmentCount']) {
                    $hitsActually ++;
                }
            }

            if ($row['unitsCompleteIndividual'] > 0) { //UNITS COMPLETE INDIVIDUAL
                $hitsNeeded ++;
                try {
                    //Count conditions
                    $dataCount = array('gibbonPersonID' => $gibbonPersonID);
                    $sqlCount = "SELECT freeLearningUnitStudentID
                        FROM freeLearningUnitStudent
                        WHERE gibbonPersonIDStudent=:gibbonPersonID
                            AND status='Complete - Approved'
                            AND `grouping`='Individual'
                    ";
                    $resultCount = $connection2->prepare($sqlCount);
                    $resultCount->execute($dataCount);
                } catch (PDOException $e) { }

                if ($resultCount->rowCount() >= $row['unitsCompleteIndividual']) {
                    $hitsActually ++;
                }
            }

            if ($row['unitsCompleteGroup'] > 0) { //UNITS COMPLETE GROUP
                $hitsNeeded ++;
                try {
                    //Count conditions
                    $dataCount = array('gibbonPersonID' => $gibbonPersonID);
                    $sqlCount = "SELECT freeLearningUnitStudentID
                        FROM freeLearningUnitStudent
                        WHERE gibbonPersonIDStudent=:gibbonPersonID
                            AND status='Complete - Approved'
                            AND NOT `grouping`='Individual'
                    ";
                    $resultCount = $connection2->prepare($sqlCount);
                    $resultCount->execute($dataCount);
                } catch (PDOException $e) { }

                if ($resultCount->rowCount() >= $row['unitsCompleteGroup']) {
                    $hitsActually ++;
                }
            }

            if (!is_null($row['difficultyLevelMaxAchieved']) AND count($difficulties) > 0) { //UNITS COMPLETE GROUP
                $hitsNeeded ++;
                try {
                    //Count conditions
                    $dataCount = array('gibbonPersonID' => $gibbonPersonID);
                    $sqlCount = "SELECT DISTINCT difficulty
                        FROM freeLearningUnitStudent
                            JOIN freeLearningUnit ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID)
                        WHERE gibbonPersonIDStudent=:gibbonPersonID
                            AND status='Complete - Approved'
                    ";
                    $resultCount = $connection2->prepare($sqlCount);
                    $resultCount->execute($dataCount);
                } catch (PDOException $e) { }

                $rowCountAll = $resultCount->fetchAll();
                $minReached = false ;
                $minAchieved = false ;

                foreach ($difficulties AS $difficulty) {
                    if ($difficulty == $row['difficultyLevelMaxAchieved']) {
                        $minReached = true ;
                    }

                    if ($minReached) {
                        foreach ($rowCountAll AS $rowCount) {
                            if ($rowCount['difficulty'] == $row['difficultyLevelMaxAchieved']) {
                                $minAchieved = true;
                            }

                        }
                    }
                }

                if ($minAchieved) {
                    $hitsActually ++;
                }
            }


            if ($row['specificUnitsComplete'] != '') { //SPECIFIC UNIT COMPLETION
                $hitsNeeded ++;

                $units = explode(',', $row['specificUnitsComplete']);
                $sqlCountWhere = ' AND (';
                $dataCount = array();
                foreach ($units AS $unit) {
                    $dataCount['unit'.$unit] = $unit;
                    $sqlCountWhere .= 'freeLearningUnitID=:unit'.$unit.' OR ';
                }
                $sqlCountWhere = substr($sqlCountWhere, 0, -4);
                $sqlCountWhere .= ')';

                try {
                    //Count conditions
                    $dataCount['gibbonPersonID'] = $gibbonPersonID;
                    $sqlCount = "SELECT freeLearningUnitStudentID
                        FROM freeLearningUnitStudent
                        WHERE gibbonPersonIDStudent=:gibbonPersonID
                            AND status='Complete - Approved'
                            $sqlCountWhere
                    ";
                    $resultCount = $connection2->prepare($sqlCount);
                    $resultCount->execute($dataCount);
                } catch (PDOException $e) {}

                if ($resultCount->rowCount() == count($units)) {
                    $hitsActually ++;
                }
            }

            //GRANT AWARD
            if ($hitsNeeded > 0 AND $hitsActually == $hitsNeeded) {
                try {
                    $dataGrant = array('badgesBadgeID' => $row['badgesBadgeID'], 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'date' => date('Y-m-d'), 'gibbonPersonID' => $gibbonPersonID, 'comment' => '', 'gibbonPersonIDCreator' => null);
                    $sqlGrant = 'INSERT INTO badgesBadgeStudent SET badgesBadgeID=:badgesBadgeID, gibbonSchoolYearID=:gibbonSchoolYearID, date=:date, gibbonPersonID=:gibbonPersonID, comment=:comment, gibbonPersonIDCreator=:gibbonPersonIDCreator';
                    $resultGrant = $connection2->prepare($sqlGrant);
                    $resultGrant->execute($dataGrant);
                } catch (PDOException $e) {}

                //Notify User
                $notificationGateway = new \Gibbon\Domain\System\NotificationGateway($pdo);
				$notificationSender = new \Gibbon\Comms\NotificationSender($notificationGateway, $session);
				$notificationText = __m('Someone has granted you a badge.');
				$notificationSender->addNotification($gibbonPersonID, $notificationText, 'Badges', "/index.php?q=/modules/Badges/badges_view.php&gibbonPersonID=$gibbonPersonID");
				$notificationSender->sendNotifications();
            }
        }
    }
}

function getCourses($connection2)
{
    $return = false;

    try {
        $data = array();
        $sql = 'SELECT DISTINCT course FROM freeLearningUnit WHERE active=\'Y\' AND NOT course IS NULL AND NOT course=\'\' ORDER BY course';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        echo $e->getMessage();
    }

    if ($result->rowCount() > 0) {
        $return = $result->fetchAll();
    }

    return $return;
}
?>
