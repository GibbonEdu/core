<?php

//Module includes
include '../../gibbon.php';

include "./moduleFunctions.php";

use Gibbon\Domain\Messenger\GroupGateway;

$URL = $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Trip Planner/";

$edit = false;
if (isset($_GET['mode']) && isset($_GET['tripPlannerRequestID'])) {
    $edit = true;
    $tripPlannerRequestID = $_GET['tripPlannerRequestID'];
}

if (!isActionAccessible($guid, $connection2, '/modules/Trip Planner/trips_submitRequest.php') || ($edit && !isOwner($connection2, $tripPlannerRequestID, $_SESSION[$guid]['gibbonPersonID']))) {
    //Acess denied
    $URL .= "trips_manage.php&return=error0";
    header("Location: {$URL}");
    exit();
} else {
    $multipleDays = false;
    if (isset($_POST['multipleDays'])) {
        $multipleDays = true; //WHY?!
    }
    $URL .= "trips_submitRequest.php";
    $date = new DateTime();
    $riskAssessmentApproval = getSettingByScope($connection2, "Trip Planner", "riskAssessmentApproval");
    $items = array("title" => true, "description" => true, "location" => true, "days" => $multipleDays, "riskAssessment" => !$riskAssessmentApproval, "letterToParents" => false, "teachers" => true, "students" => false, "order" => false);
    $data = array();
    $sql = ($edit ? "UPDATE" : "INSERT INTO") . " tripPlannerRequests SET" . ($edit ? " " : " creatorPersonID=:creatorPersonID, timestampCreation=now(), gibbonSchoolYearID=:gibbonSchoolYearID, ");

    $people = array();
    $days = array();
    $costs = array();

    foreach ($items as $item => $required) {
        if (isset($_POST[$item])) {
            if ($_POST[$item] != null && $_POST[$item] != "") {
                $key = $item;
                if ($item == "days") {
                    $key = null;
                    foreach ($_POST[$item] as $day) {
                        $day["startDate"] = DateTime::createFromFormat("d/m/Y", $day["startDate"])->format("Y-m-d");
                        $day["endDate"] = DateTime::createFromFormat("d/m/Y", $day["endDate"])->format("Y-m-d");
                        $day["allDay"] = $day["allDay"] == "true" ? 1 : 0; //Why?!
                        $days[] = $day;
                    }
                } elseif ($item == "teachers" || $item == "students") {
                    $key = null;
                    $role = "Teacher";
                    if ($item == "students") {
                        $role = "Student";
                    }

                    foreach ($_POST[$item] as $person) {
                        $people[] = array("role" => $role, "gibbonPersonID" => $person);
                    }
                } elseif ($item == "order") {
                    $key = null;
                    $order = $_POST[$item];
                    foreach ($order as $cost) {
                        $costs[$cost]['name'] = $_POST['cost'][$cost]['costName'];
                        $costs[$cost]['cost'] = $_POST['cost'][$cost]['costValue'];
                        $costs[$cost]['description'] = $_POST['cost'][$cost]['costDescription'];

                        if ($costs[$cost]['name'] == '' || $costs[$cost]['cost'] == '' || is_numeric($costs[$cost]['cost']) == false) {
                            $URL .= "&return=error1";
                            header("Location: {$URL}");
                            exit();
                        }
                    }
                } else {
                    $data[$item] = $_POST[$item];
                }

                if ($key != null) {
                    $sql .= $key . "=:" . $key . ", ";
                }
            }
        } elseif ($required) {
            $URL .= "&return=error1";
            header("Location: {$URL}");
            exit();
        }
    }

    $sql = substr($sql, 0, -2) . ($edit ? " WHERE tripPlannerRequestID=:tripPlannerRequestID" : "");

    if ($edit) {
        $data["tripPlannerRequestID"] = $tripPlannerRequestID;
    } else {
        $data["creatorPersonID"] = $_SESSION[$guid]["gibbonPersonID"];
        $data["gibbonSchoolYearID"] = $_SESSION[$guid]["gibbonSchoolYearID"];
    }

    // if (!$multipleDays) {
    //     if (!isset($_POST["startDate"]) || $_POST["startDate"] == "" || ((!isset($_POST["startTime"])) || !isset($_POST["endTime"]) && !isset($_POST["allDay"]))) {
    //         $URL .= "&return=error1";
    //         header("Location: {$URL}");
    //         exit();
    //     }
    //     $days[] = array("startDate" => DateTime::createFromFormat("d/m/Y", $_POST["startDate"])->format("Y-m-d"), "endDate" => DateTime::createFromFormat("d/m/Y", $_POST["startDate"])->format("Y-m-d"), "allDay" => (isset($_POST["allDay"]) ? 1 : 0), "startTime" => (!isset($_POST["allDay"]) ? $_POST["startTime"] : 0), "endTime" => (!isset($_POST["allDay"]) ? $_POST["endTime"] : 0));
    // }

    $groupGateway = $container->get(GroupGateway::class);
    $groupNotFound = false;

    try {
        if ($edit) {
            $dataGroup = array("tripPlannerRequestID" => $tripPlannerRequestID);
            $sqlGroup = "SELECT messengerGroupID FROM tripPlannerRequests WHERE tripPlannerRequestID=:tripPlannerRequestID";
            $resultGroup = $connection2->prepare($sqlGroup);
            $resultGroup->execute($dataGroup);

            if ($resultGroup->rowCount() == 1 && ($groupID = $resultGroup->fetch()["messengerGroupID"]) != null) {
                $sqlStudents = "SELECT gibbonPersonID FROM tripPlannerRequestPerson WHERE tripPlannerRequestID=:tripPlannerRequestID";
                $resultStudents = $connection2->prepare($sqlStudents);
                $resultStudents->execute($dataGroup);

                $peopleInTrip = array();

                while($row = $resultStudents->fetch()) {
                    $peopleInTrip[] = $row["gibbonPersonID"];
                    if(!in_array($row["gibbonPersonID"], array_column($people, "gibbonPersonID"))) {
                        $deleted = $groupGateway->deleteGroupPerson($groupID, $row["gibbonPersonID"]);
                    }
                }

                foreach ($people as $person) {
                    if(!in_array($person["gibbonPersonID"], $peopleInTrip)) {
                        $dataGroup = array('gibbonGroupID' => $groupID, 'gibbonPersonID' => $person["gibbonPersonID"]);
                        $inserted = $groupGateway->insertGroupPerson($dataGroup);
                    }
                }
            } else {
                $groupNotFound = true;
            }
        }

        $result = $connection2->prepare($sql);
        $result->execute($data);
        if (!$edit) $tripPlannerRequestID = $connection2->lastInsertId();
        logEvent($connection2, $tripPlannerRequestID, $_SESSION[$guid]["gibbonPersonID"], $edit ? "Edit" : "Request");

        if ($edit) {
            $tables = array("tripPlannerCostBreakdown", "tripPlannerRequestPerson", "tripPlannerRequestDays");
            foreach ($tables as $table) {
                $dataEdit = array("tripPlannerRequestID" => $tripPlannerRequestID);
                $sqlEdit = "DELETE FROM " . $table . " WHERE tripPlannerRequestID=:tripPlannerRequestID";
                $resultEdit = $connection2->prepare($sqlEdit);
                $resultEdit->execute($dataEdit);
            }
        }
        $sql1 = "INSERT INTO tripPlannerCostBreakdown SET tripPlannerRequestID=:tripPlannerRequestID, title=:name, cost=:cost, description=:description";
        foreach ($costs as $cost) {
            $cost['tripPlannerRequestID'] = $tripPlannerRequestID;
            $result1 = $connection2->prepare($sql1);
            $result1->execute($cost);
        }
        $sql2 = "INSERT INTO tripPlannerRequestPerson SET tripPlannerRequestID=:tripPlannerRequestID, gibbonPersonID=:gibbonPersonID, role=:role";
        foreach ($people as $person) {
            $person['tripPlannerRequestID'] = $tripPlannerRequestID;
            $result2 = $connection2->prepare($sql2);
            $result2->execute($person);
        }
        $sql3 = "INSERT INTO tripPlannerRequestDays SET tripPlannerRequestID=:tripPlannerRequestID, startDate=:startDate, endDate=:endDate, allDay=:allDay, startTime=:startTime, endTime=:endTime";
        foreach ($days as $day) {
            $day['tripPlannerRequestID'] = $tripPlannerRequestID;
            $result3 = $connection2->prepare($sql3);
            $result3->execute($day);
        }
        if (!$edit) notifyApprovers($guid, $connection2, $tripPlannerRequestID, $_SESSION[$guid]["gibbonPersonID"], $data["title"]);
    } catch (PDOException $e) {
        print $e;
        $URL .= "&return=error2";
        //header("Location: {$URL}");
        exit();
    }

    $createGroup = (isset($_POST['createGroup'])) ? $_POST['createGroup'] : 'N' ;
    if ($createGroup == 'Y' && (!$edit || $groupNotFound)) {
        $data = array('gibbonPersonIDOwner' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'name' => $data["title"] . " (Trip Planner)");
        $groupID = $groupGateway->insertGroup($data);

        if($groupID) {
            foreach ($people as $person) {
                $data = array('gibbonGroupID' => $groupID, 'gibbonPersonID' => $person["gibbonPersonID"]);
                $inserted = $groupGateway->insertGroupPerson($data);
                //$partialFail &= !$inserted;
            }

            $dataGroup = array("tripPlannerRequestID" => $tripPlannerRequestID, "groupID" => $groupID);
            $sqlGroup = "UPDATE tripPlannerRequests SET messengerGroupID=:groupID WHERE tripPlannerRequestID=:tripPlannerRequestID";
            $resultGroup = $connection2->prepare($sqlGroup);
            $resultGroup->execute($dataGroup);
        }
    }

    $URL .= "&return=success0&tripPlannerRequestID=" . $tripPlannerRequestID . ($edit ? "&mode=edit" : "");
    header("Location: {$URL}");
    exit();
}
?>
