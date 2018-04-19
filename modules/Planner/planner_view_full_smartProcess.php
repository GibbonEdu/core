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

//Gibbon system-wide includes
include '../../functions.php';
include '../../config.php';

//Module includes
include './moduleFunctions.php';

$gibbonPlannerEntryID = $_POST['gibbonPlannerEntryID'];
$mode = $_POST['mode'];
$URL = $_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Planner/planner_view_full.php&gibbonPlannerEntryID=$gibbonPlannerEntryID".$_POST['params'];

if (isActionAccessible($guid, $connection2, '/modules/Planner/planner_view_full.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $highestAction = getHighestGroupedAction($guid, $_POST['address'], $connection2);
    if ($highestAction == false) {
        $URL .= "&return=error0$params";
        header("Location: {$URL}");
    } else {
        //Proceed!
        //Check if planner specified
        if ($gibbonPlannerEntryID == '' or $mode == '' or ($mode != 'view' and $mode != 'edit')) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            try {
                $data = array('gibbonPlannerEntryID' => $gibbonPlannerEntryID);
                $sql = 'SELECT * FROM gibbonPlannerEntry WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID';
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

                //CHECK IF UNIT IS GIBBON OR HOOKED
                if ($row['gibbonHookID'] == null) {
                    $hooked = false;
                    $gibbonUnitID = $row['gibbonUnitID'];
                } else {
                    $hooked = true;
                    $gibbonUnitIDToken = $row['gibbonUnitID'];
                    $gibbonHookIDToken = $row['gibbonHookID'];

                    try {
                        $dataHooks = array('gibbonHookID' => $gibbonHookIDToken);
                        $sqlHooks = "SELECT * FROM gibbonHook WHERE type='Unit' AND gibbonHookID=:gibbonHookID ORDER BY name";
                        $resultHooks = $connection2->prepare($sqlHooks);
                        $resultHooks->execute($dataHooks);
                    } catch (PDOException $e) {
                    }
                    if ($resultHooks->rowCount() == 1) {
                        $rowHooks = $resultHooks->fetch();
                        $hookOptions = unserialize($rowHooks['options']);
                        if ($hookOptions['unitTable'] != '' and $hookOptions['unitIDField'] != '' and $hookOptions['unitCourseIDField'] != '' and $hookOptions['unitNameField'] != '' and $hookOptions['unitDescriptionField'] != '' and $hookOptions['classLinkTable'] != '' and $hookOptions['classLinkJoinFieldUnit'] != '' and $hookOptions['classLinkJoinFieldClass'] != '' and $hookOptions['classLinkIDField'] != '') {
                            try {
                                $data = array('unitIDField' => $gibbonUnitIDToken);
                                $sql = 'SELECT '.$hookOptions['unitTable'].'.*, gibbonCourse.nameShort FROM '.$hookOptions['unitTable'].' JOIN gibbonCourse ON ('.$hookOptions['unitTable'].'.'.$hookOptions['unitCourseIDField'].'=gibbonCourse.gibbonCourseID) WHERE '.$hookOptions['unitIDField'].'=:unitIDField';
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                            }
                        }
                    }
                }

                $partialFail = false;
                if ($mode == 'view') {
                    $ids = $_POST['gibbonUnitClassBlockID'];
                    for ($i = 0; $i < count($ids); ++$i) {
                        if ($ids[$i] == '') {
                            $partialFail = true;
                        } else {
                            $complete = 'N';
                            if (isset($_POST["complete$i"])) {
                                if ($_POST["complete$i"] == 'on') {
                                    $complete = 'Y';
                                }
                            }
                            //Write to database
                            try {
                                if ($hooked == false) {
                                    $data = array('complete' => $complete, 'gibbonUnitClassBlockID' => $ids[$i]);
                                    $sql = 'UPDATE gibbonUnitClassBlock SET complete=:complete WHERE gibbonUnitClassBlockID=:gibbonUnitClassBlockID';
                                } else {
                                    $data = array('complete' => $complete, 'gibbonUnitClassBlockID' => $ids[$i]);
                                    $sql = 'UPDATE '.$hookOptions['classSmartBlockTable'].' SET complete=:complete WHERE '.$hookOptions['classSmartBlockIDField'].'=:gibbonUnitClassBlockID';
                                }
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                echo 'Here';
                                echo $e->getMessage();
                                $partialFail = true;
                            }
                        }
                    }
                } else {
                    $order = $_POST['order'];
                    $seq = $_POST['minSeq'];

                    $summaryBlocks = '';
                    foreach ($order as $i) {
                        $id = $_POST["gibbonUnitClassBlockID$i"];
                        $title = $_POST["title$i"];
                        $summaryBlocks .= $title.', ';
                        $type = $_POST["type$i"];
                        $length = $_POST["length$i"];
                        $contents = $_POST["contents$i"];
                        $teachersNotes = $_POST["teachersNotes$i"];
                        $complete = 'N';
                        if (isset($_POST["complete$i"])) {
                            if ($_POST["complete$i"] == 'on') {
                                $complete = 'Y';
                            }
                        }

                        //Deal with outcomes
                        $gibbonOutcomeIDList = '';
                        if (isset($_POST['outcomes'.$i])) {
                            if (is_array($_POST['outcomes'.$i])) {
                                foreach ($_POST['outcomes'.$i] as $outcome) {
                                    $gibbonOutcomeIDList .= $outcome.',';
                                }
                            }
                            $gibbonOutcomeIDList = substr($gibbonOutcomeIDList, 0, -1);
                        }

                        //Write to database
                        try {
                            if ($hooked == false) {
                                $data = array('title' => $title, 'type' => $type, 'length' => $length, 'contents' => $contents, 'teachersNotes' => $teachersNotes, 'complete' => $complete, 'sequenceNumber' => $seq, 'gibbonOutcomeIDList' => $gibbonOutcomeIDList, 'gibbonUnitClassBlockID' => $id);
                                $sql = 'UPDATE gibbonUnitClassBlock SET title=:title, type=:type, length=:length, contents=:contents, teachersNotes=:teachersNotes, complete=:complete, sequenceNumber=:sequenceNumber, gibbonOutcomeIDList=:gibbonOutcomeIDList WHERE gibbonUnitClassBlockID=:gibbonUnitClassBlockID';
                            } else {
                                $data = array('title' => $title, 'type' => $type, 'length' => $length, 'contents' => $contents, 'teachersNotes' => $teachersNotes, 'complete' => $complete, 'sequenceNumber' => $seq, 'gibbonUnitClassBlockID' => $id);
                                $sql = 'UPDATE '.$hookOptions['classSmartBlockTable'].' SET '.$hookOptions['classSmartBlockTitleField'].'=:title, '.$hookOptions['classSmartBlockTypeField'].'=:type, '.$hookOptions['classSmartBlockLengthField'].'=:length, '.$hookOptions['classSmartBlockContentsField'].'=:contents, '.$hookOptions['classSmartBlockTeachersNotesField'].'=:teachersNotes, '.$hookOptions['classSmartBlockCompleteField'].'=:complete, '.$hookOptions['classSmartBlockSequenceNumberField'].'=:sequenceNumber WHERE '.$hookOptions['classSmartBlockIDField'].'=:gibbonUnitClassBlockID';
                            }
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $partialFail = true;
                        }
                        ++$seq;
                    }
                }

                $summaryBlocks = substr($summaryBlocks, 0, -2);
                if (strlen($summaryBlocks) > 75) {
                    $summaryBlocks = substr($summaryBlocks, 0, 72).'...';
                }
                if ($summaryBlocks) {
                    $summary = $summaryBlocks;
                }

                //Write to database
                try {
                    $data = array('summary' => $summary, 'gibbonPlannerEntryID' => $gibbonPlannerEntryID);
                    $sql = 'UPDATE gibbonPlannerEntry SET summary=:summary WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $partialFail = true;
                }

                //Return final verdict
                if ($partialFail == true) {
                    $URL .= '&return=error3';
                    header("Location: {$URL}");
                } else {
                    $URL .= '&return=success0';
                    header("Location: {$URL}");
                }
            }
        }
    }
}
