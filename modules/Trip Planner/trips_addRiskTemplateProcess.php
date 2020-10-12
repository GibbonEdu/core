<?php

//Module includes
include '../../gibbon.php';

include "./moduleFunctions.php";

$URL = $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Trip Planner/";

if (!isActionAccessible($guid, $connection2, '/modules/Trip Planner/trips_manageRiskTemplates.php')) {
    //Acess denied
    $URL .= "trips_manage.php&return=error0";
    header("Location: {$URL}");
    exit();
} else {    

    $URL .= "trips_addRiskTemplate.php";

    if (isset($_GET["tripPlannerRiskTemplateID"])) {
        if ($_GET["tripPlannerRiskTemplateID"] != null && $_GET["tripPlannerRiskTemplateID"] != "") {
            $tripPlannerRiskTemplateID = $_GET["tripPlannerRiskTemplateID"];
        }
    }

    if (isset($_POST["name"])) {
        if ($_POST["name"] != null && $_POST["name"] != "") {
            $name = $_POST["name"];
        }
    } else {
        $URL .= "&return=error1";
        header("Location: {$URL}");
        exit();
    }

    if (isset($_POST["body"])) {
        if ($_POST["body"] != null && $_POST["body"] != "") {
            $body = $_POST["body"];
        }
    } else {
        $URL .= "&return=error1";
        header("Location: {$URL}");
        exit();
    }
        
    try {
        $data = array("name" => $name, "body" => $body);
        if(isset($tripPlannerRiskTemplateID)) {
            $data["tripPlannerRiskTemplateID"] = $tripPlannerRiskTemplateID;
        }
        $sql = (isset($tripPlannerRiskTemplateID) ? "UPDATE" : "INSERT INTO") . " tripPlannerRiskTemplates SET name=:name, body=:body" . (isset($tripPlannerRiskTemplateID) ? " WHERE tripPlannerRiskTemplateID=:tripPlannerRiskTemplateID" : "") ;
        $result = $connection2->prepare($sql);
        $result->execute($data);
        if(!isset($tripPlannerRiskTemplateID)) {
            $tripPlannerRiskTemplateID = $connection2->lastInsertId();
        }
    } catch (PDOException $e) {
        $URL .= "&return=error2";
        header("Location: {$URL}");
        exit();
    }

    $URL .= "&return=success0&tripPlannerRiskTemplateID=" . $tripPlannerRiskTemplateID;
    header("Location: {$URL}");
    exit();
}   
?>
