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

$gibbonCourseID = $_POST['gibbonCourseID'];
$gibbonCourseIDCopyTo = $_POST['gibbonCourseIDCopyTo'];
$gibbonSchoolYearID = $_POST['gibbonSchoolYearID'];
$action = $_POST['action'];

if ($gibbonCourseID == '' or $gibbonCourseIDCopyTo == '' or $gibbonSchoolYearID == '' or $action == '') { echo 'Fatal error loading this page!';
} else {
    $URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/units.php&gibbonCourseID=$gibbonCourseID&gibbonSchoolYearID=$gibbonSchoolYearID";

    if (isActionAccessible($guid, $connection2, '/modules/Planner/units.php') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        $units = array();
        for ($i = 0; $i < $_POST['count']; ++$i) {
            if (isset($_POST["check-$i"])) {
                if ($_POST["check-$i"] == 'on') {
                    $units[$i] = $_POST["gibbonUnitID-$i"];
                }
            }
        }

        //Proceed!
        //Check if person specified
        if (count($units) < 1) {
            $URL .= '&return=error3';
            header("Location: {$URL}");
        } else {
            $partialFail = false;
            if ($action == 'Duplicate') {
                foreach ($units AS $gibbonUnitID) { //For every unit to be copied
                    //Check existence of unit and fetch details
                    try {
                        $data = array('gibbonUnitID' => $gibbonUnitID);
                        $sql = 'SELECT * FROM gibbonUnit WHERE gibbonUnitID=:gibbonUnitID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    if ($result->rowCount() != 1) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                    } else {
                        $row = $result->fetch();
                        $name = $row['name'];
                        if ($gibbonCourseIDCopyTo == $gibbonCourseID) {
                            $name .= ' (Copy)';
                        }

                        //Write the duplicate to the database
                        try {
                            $data = array('gibbonCourseID' => $gibbonCourseIDCopyTo, 'name' => $name, 'description' => $row['description'], 'map' => $row['map'], 'tags' => $row['tags'], 'ordering' => $row['ordering'], 'attachment' => $row['attachment'], 'details' => $row['details'], 'gibbonPersonIDCreator' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonPersonIDLastEdit' => $_SESSION[$guid]['gibbonPersonID']);
                            $sql = 'INSERT INTO gibbonUnit SET gibbonCourseID=:gibbonCourseID, name=:name, description=:description, map=:map, tags=:tags, ordering=:ordering, attachment=:attachment, details=:details ,gibbonPersonIDCreator=:gibbonPersonIDCreator, gibbonPersonIDLastEdit=:gibbonPersonIDLastEdit';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $URL .= '&return=error2';
                            header("Location: {$URL}");
                            exit();
                        }

                        //Last insert ID
                        $AI = str_pad($connection2->lastInsertID(), 10, '0', STR_PAD_LEFT);

                        //Copy Outcomes
                        try {
                            $dataOutcomes = array('gibbonUnitID' => $gibbonUnitID);
                            $sqlOutcomes = 'SELECT * FROM gibbonUnitOutcome WHERE gibbonUnitID=:gibbonUnitID';
                            $resultOutcomes = $connection2->prepare($sqlOutcomes);
                            $resultOutcomes->execute($dataOutcomes);
                        } catch (PDOException $e) {
                            $partialFail = true;
                        }
                        if ($resultOutcomes->rowCount() > 0) {
                            while ($rowOutcomes = $resultOutcomes->fetch()) {
                                //Write to database
                                try {
                                    $dataCopy = array('gibbonUnitID' => $AI, 'gibbonOutcomeID' => $rowOutcomes['gibbonOutcomeID'], 'sequenceNumber' => $rowOutcomes['sequenceNumber'], 'content' => $rowOutcomes['content']);
                                    $sqlCopy = 'INSERT INTO gibbonUnitOutcome SET gibbonUnitID=:gibbonUnitID, gibbonOutcomeID=:gibbonOutcomeID, sequenceNumber=:sequenceNumber, content=:content';
                                    $resultCopy = $connection2->prepare($sqlCopy);
                                    $resultCopy->execute($dataCopy);
                                } catch (PDOException $e) {
                                    $partialFail = true;
                                }
                            }
                        }

                        //Copy smart blocks
                        try {
                            $dataBlocks = array('gibbonUnitID' => $gibbonUnitID);
                            $sqlBlocks = 'SELECT * FROM gibbonUnitBlock WHERE gibbonUnitID=:gibbonUnitID ORDER BY sequenceNumber';
                            $resultBlocks = $connection2->prepare($sqlBlocks);
                            $resultBlocks->execute($dataBlocks);
                        } catch (PDOException $e) {
                            $partialFail = true;
                        }
                        while ($rowBlocks = $resultBlocks->fetch()) {
                            try {
                                $dataBlock = array('gibbonUnitID' => $AI, 'title' => $rowBlocks['title'], 'type' => $rowBlocks['type'], 'length' => $rowBlocks['length'], 'contents' => $rowBlocks['contents'], 'teachersNotes' => $rowBlocks['teachersNotes'], 'sequenceNumber' => $rowBlocks['sequenceNumber'], 'gibbonOutcomeIDList' => $rowBlocks['gibbonOutcomeIDList']);
                                $sqlBlock = 'INSERT INTO gibbonUnitBlock SET gibbonUnitID=:gibbonUnitID, title=:title, type=:type, length=:length, contents=:contents, teachersNotes=:teachersNotes, sequenceNumber=:sequenceNumber, gibbonOutcomeIDList=:gibbonOutcomeIDList';
                                $resultBlock = $connection2->prepare($sqlBlock);
                                $resultBlock->execute($dataBlock);
                            } catch (PDOException $e) {
                                $partialFail = true;
                            }
                        }
                    }
                }
            }
            else {
                $URL .= '&return=error3';
                header("Location: {$URL}");
            }

            if ($partialFail == true) {
                $URL .= '&return=warning1';
                header("Location: {$URL}");
            } else {
                $URL .= '&return=success0';
                header("Location: {$URL}");
            }
        }
    }
}
