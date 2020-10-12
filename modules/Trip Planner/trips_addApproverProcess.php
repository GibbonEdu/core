<?php

//Module includes
include '../../gibbon.php';

include "./moduleFunctions.php";

$URL = $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Trip Planner/";

if (!isActionAccessible($guid, $connection2, '/modules/Trip Planner/trips_addApprover.php')) {
    //Acess denied
    $URL .= "trips_manageApprover.php&return=error0";
    header("Location: {$URL}");
    exit();
} else {    

    $URL .= "trips_addApprover.php";

    if (isset($_POST["gibbonPersonID"])) {
        if ($_POST["gibbonPersonID"] != null && $_POST["gibbonPersonID"] != "") {
            $gibbonPersonID = $_POST["gibbonPersonID"];
        }
    } else {
        $URL .= "&return=error1";
        header("Location: {$URL}");
        exit();
    }

    $expenseApprovalType = getSettingByScope($connection2, "Trip Planner", "requestApprovalType");
    if ($expenseApprovalType == "Chain Of All") {
        if (isset($_POST["sequenceNumber"])) {
            if ($_POST["sequenceNumber"] != null && $_POST["sequenceNumber"] != "") {
                $sequenceNumber = abs($_POST["sequenceNumber"]);
            }
        } else {
            $URL .= "&return=error1";
            header("Location: {$URL}");
            exit();
        }
    } else {
        $sequenceNumber = 0;
    }
    
    $finalApprover = 0;
    $riskAssessmentApproval = getSettingByScope($connection2, "Trip Planner", "riskAssessmentApproval");
    if ($riskAssessmentApproval) {
        if (isset($_POST["finalApprover"])) {
            if($_POST["finalApprover"] != null && $_POST["finalApprover"] != "") {
                $finalApprover = 1;
            }
        }
    }

    try {
        $data = array("gibbonPersonID"=>$gibbonPersonID); 
        $sql = "SELECT * FROM tripPlannerApprovers WHERE gibbonPersonID=:gibbonPersonID";
        if ($expenseApprovalType == "Chain Of All") {
            $data["sequenceNumber"] = $sequenceNumber;
            $sql .= " OR sequenceNumber=:sequenceNumber";
        }
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) { 
        $URL .= "&return=error2";
        header("Location: {$URL}");
        exit();
    }
        
    if ($result->rowCount() > 0) {
        $URL .= "&return=error5";
        header("Location: {$URL}");
        exit();
    } else {  
        try {
            $data = array("gibbonPersonID"=> $gibbonPersonID, "sequenceNumber"=> $sequenceNumber, "gibbonPersonIDCreator"=> $_SESSION[$guid]["gibbonPersonID"], "timestampCreator"=>date('Y-m-d H:i:s', time()), "finalApprover" => $finalApprover);
            $sql = "INSERT INTO tripPlannerApprovers SET gibbonPersonID=:gibbonPersonID, sequenceNumber=:sequenceNumber, gibbonPersonIDCreator=:gibbonPersonIDCreator, timestampCreator=:timestampCreator, finalApprover=:finalApprover";
            $result = $connection2->prepare($sql);
            $result->execute($data);
            $tripPlannerApproverID = $connection2->lastInsertId();
        } catch (PDOException $e) {
            $URL .= "&return=error2";
            header("Location: {$URL}");
            exit();
        }

        $URL .= "&return=success0&tripPlannerApproverID=" . $tripPlannerApproverID;
        header("Location: {$URL}");
        exit();
    }
}   
?>
