<?php

//Module includes
include '../../gibbon.php';

include "./moduleFunctions.php";

$URL = $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Trip Planner/trips_manageApprovers.php";

if (!isActionAccessible($guid, $connection2, '/modules/Trip Planner/trips_deleteApproverProcess.php')) {
    //Acess denied
    $URL .= "&return=error0";
    header("Location: {$URL}");
    exit();
} else {
    if (isset($_GET["tripPlannerApproverID"])) {
        if ($_GET["tripPlannerApproverID"] != null && $_GET["tripPlannerApproverID"] != "") {
            $tripPlannerApproverID = $_GET["tripPlannerApproverID"];
        }
    } else {
        $URL .= "&return=error1";
        header("Location: {$URL}");
        exit();
    }
        
    if (!approverExists($connection2, $tripPlannerApproverID)) {
        $URL .= "&return=error1";
        header("Location: {$URL}");
        exit();
    } else {  
        try {
            $data = array("tripPlannerApproverID"=> $tripPlannerApproverID);
            $sql = "DELETE FROM tripPlannerApprovers WHERE tripPlannerApproverID=:tripPlannerApproverID";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $URL .= "&return=error2";
            header("Location: {$URL}");
            exit();
        }

        $URL .= "&return=success0";
        header("Location: {$URL}");
        exit();
    }
}   
?>
