<?php

//Module includes
include '../../gibbon.php';

include "./moduleFunctions.php";

$URL = $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Trip Planner/";

if (!isActionAccessible($guid, $connection2, '/modules/Trip Planner/trips_manage.php')) {
    //Acess denied
    $URL .= "trips_manage.php&return=error0";
    header("Location: {$URL}");
    exit();
} else {
    if (isset($_POST["tripPlannerRequestID"])) {
        $tripPlannerRequestID = $_POST["tripPlannerRequestID"];
    } else {
        $URL .= "trips_manage.php&return=error1";
        header("Location: {$URL}");
        exit();
    }

    $gibbonPersonID = $_SESSION[$guid]["gibbonPersonID"];
    $departments = getHOD($connection2, $gibbonPersonID);
    $departments2 = getDepartments($connection2, getOwner($connection2, $tripPlannerRequestID));
    $isHOD = false;

    foreach ($departments as $department) {
        if (in_array($department["gibbonDepartmentID"], $departments2)) {
            $isHOD = true;
            break;
        }
    }

    if (isApprover($connection2, $gibbonPersonID) || isOwner($connection2, $tripPlannerRequestID, $gibbonPersonID) || isInvolved($connection2, $tripPlannerRequestID, $gibbonPersonID) || $isHOD) {
        $URL .= "trips_requestView.php&tripPlannerRequestID=" . $tripPlannerRequestID;
        if (isset($_POST["comment"])) {
            $comment = $_POST["comment"];
            if ($comment == "" || $comment == null) {
                $URL .= "&return=error1";
                header("Location: {$URL}");
                exit();
            }
        } else {
            $URL .= "&return=error1";
            header("Location: {$URL}");
            exit();
        }

        if (!logEvent($connection2, $tripPlannerRequestID, $gibbonPersonID, "Comment", $comment)) {
            $URL .= "&return=error2";
            header("Location: {$URL}");
            exit();
        }
        requestNotification($guid, $connection2, $tripPlannerRequestID, $_SESSION[$guid]["gibbonPersonID"], "Comment");

        $URL .= "&return=success0";
        header("Location: {$URL}");
        exit();
    } else {
        $URL .= "trips_manage.php&return=error0";
        header("Location: {$URL}");
        exit();
    }
}   
?>
