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

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$URL = $session->get('absoluteURL').'/index.php?q=/modules/School Admin/house_manage_assign.php';
$URLSuccess = $session->get('absoluteURL').'/index.php?q=/modules/Students/report_students_byHouse.php';

if (isActionAccessible($guid, $connection2, '/modules/School Admin/house_manage_assign.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Validate Inputs

    $gibbonYearGroupIDList = $_POST['gibbonYearGroupIDList'] ?? '';
    $gibbonHouseIDList = $_POST['gibbonHouseIDList'] ?? '';
    $balanceYearGroup = $_POST['balanceYearGroup'] ?? '';
    $balanceGender = $_POST['balanceGender'] ?? '';
    $overwrite = $_POST['overwrite'] ?? '';

    if (empty($gibbonYearGroupIDList) || empty($gibbonHouseIDList) || empty($balanceYearGroup) || empty($balanceGender) || empty($overwrite)) {
        $URL .= "&return=error1";
        header("Location: {$URL}");
        exit;
    } else {
        $partialFail = false;
        $count = 0;

        $gibbonHouseIDList = (is_array($gibbonHouseIDList))? implode(',', $gibbonHouseIDList) : $gibbonHouseIDList;
        $gibbonYearGroupIDList = (is_array($gibbonYearGroupIDList))? implode(',', $gibbonYearGroupIDList) : $gibbonYearGroupIDList;

        $yearGroupArray = ($balanceYearGroup == 'Y')? explode(',', $gibbonYearGroupIDList) : array($gibbonYearGroupIDList);

        foreach ($yearGroupArray as $gibbonYearGroupIDs) {

            if ($overwrite == 'Y') {
                // Grab the applicable houses, start all the counters at 0
                try {
                    $data = array('gibbonHouseIDList' => $gibbonHouseIDList);
                    $sql = "SELECT gibbonHouse.gibbonHouseID as groupBy, gibbonHouse.gibbonHouseID, 0 AS total, 0 as totalM, 0 as totalF
                        FROM gibbonHouse
                        WHERE FIND_IN_SET(gibbonHouse.gibbonHouseID, :gibbonHouseIDList)
                        GROUP BY gibbonHouse.gibbonHouseID
                        ORDER BY RAND()";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $partialFail = true;
                }
            } else {
                // Grab the applicable houses and current totals for this year group (or set of year groups)
                try {
                    $data = array('gibbonHouseIDList' => $gibbonHouseIDList, 'gibbonYearGroupIDs' => $gibbonYearGroupIDs, 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'today' => date('Y-m-d'));
                    $sql = "SELECT gibbonHouse.gibbonHouseID as groupBy, gibbonHouse.gibbonHouseID, count(gibbonStudentEnrolment.gibbonPersonID) AS total, count(CASE WHEN gibbonPerson.gender='M' THEN gibbonStudentEnrolment.gibbonPersonID END) as totalM, count(CASE WHEN gibbonPerson.gender='F' THEN gibbonStudentEnrolment.gibbonPersonID END) as totalF
                        FROM gibbonHouse
                            LEFT JOIN gibbonPerson ON (gibbonPerson.gibbonHouseID=gibbonHouse.gibbonHouseID
                                AND gibbonPerson.status='Full'
                                AND (gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:today)
                                AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:today) )
                            LEFT JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonSchoolYearID=:gibbonSchoolYearID AND FIND_IN_SET(gibbonYearGroupID, :gibbonYearGroupIDs) )
                        WHERE FIND_IN_SET(gibbonHouse.gibbonHouseID, :gibbonHouseIDList)
                        GROUP BY gibbonHouse.gibbonHouseID
                        ORDER BY RAND()";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $partialFail = true;
                }
            }

            $houses = ($result->rowCount() > 0)? $result->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_UNIQUE) : array();

            // Build a closure for getting the gibbonHouseID with the minimum students for a particular group
            $getNextHouse = function($group) use (&$houses) {
                return array_reduce(array_keys($houses), function ($resultID, $currentID) use (&$houses, $group) {
                    $currentValue = $houses[$currentID][$group];
                    $resultValue = $houses[$resultID][$group];

                    return (is_null($resultValue) || $currentValue < $resultValue)? $currentID : $resultID;
                }, key($houses));
            };

            // Grab the list of students
            try {
                $data = array('gibbonYearGroupIDs' => $gibbonYearGroupIDs, 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'today' => date('Y-m-d'));
                $sql = "SELECT gibbonStudentEnrolment.gibbonYearGroupID, gibbonPerson.gender, gibbonPerson.gibbonPersonID, gibbonPerson.gibbonHouseID FROM
                        gibbonPerson
                        JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                        WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                        AND FIND_IN_SET(gibbonStudentEnrolment.gibbonYearGroupID, :gibbonYearGroupIDs)
                        AND gibbonPerson.status='Full'
                        AND (gibbonPerson.dateStart IS NULL OR gibbonPerson.dateStart<=:today)
                        AND (gibbonPerson.dateEnd IS NULL OR gibbonPerson.dateEnd>=:today)";

                if ($overwrite == 'N') {
                    $sql .= " AND gibbonPerson.gibbonHouseID IS NULL";
                }

                $sql .= " ORDER BY RAND()";

                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $partialFail = true;
            }

            if (!empty($houses) && $result->rowCount() > 0) {

                while ($student = $result->fetch()) {
                    if ($student['gender'] == 'Other' || $student['gender'] == 'Unspecified') {
                        $student['gender'] = random_int(0, 1) == 1? 'M' : 'F';
                    }

                    // Use the closure to grab the next house to fill
                    $group = ($balanceGender == 'Y')? 'total'.$student['gender'] : 'total';
                    $gibbonHouseID = $getNextHouse($group);

                    if ($gibbonHouseID !== $student['gibbonHouseID']) {
                        //Write to database
                        try {
                            $data = array('gibbonPersonID' => $student['gibbonPersonID'], 'gibbonHouseID' => $gibbonHouseID);
                            $sql = 'UPDATE gibbonPerson SET gibbonHouseID=:gibbonHouseID WHERE gibbonPersonID=:gibbonPersonID';
                            $resultUpdate = $connection2->prepare($sql);
                            $resultUpdate->execute($data);
                        } catch (PDOException $e) {
                            $partialFail = true;
                        }
                    }

                    // Increment the counters so we're filling up each house
                    $houses[$gibbonHouseID]['total']++;
                    $houses[$gibbonHouseID]['total'.$student['gender']]++;
                    $count++;
                }
            }
        }

        if ($partialFail) {
            $URL .= "&return=warning1";
            header("Location: {$URL}");
        } else {
            $URLSuccess .= "&gibbonYearGroupIDList={$gibbonYearGroupIDList}&count={$count}";
            $URLSuccess .= "&return=success0";
            header("Location: {$URLSuccess}");
        }
    }
}
