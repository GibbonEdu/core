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
    if (isset($_GET["tripPlannerRequestID"])) {
        $tripPlannerRequestID = $_GET["tripPlannerRequestID"];
        if(isset($_GET["gibbonCourseClassID"]) && isset($_GET["date"])) {
            $gibbonCourseClassID = $_GET["gibbonCourseClassID"];
            $date = $_GET["date"];
            if (isOwner($connection2, $tripPlannerRequestID, $_SESSION[$guid]["gibbonPersonID"])) {

                $requiresCover = false;
                if (isset($_POST["requiresCover"])) {
                    $requiresCover = true;
                }

                try {
                    $data = array("tripPlannerRequestID" => $tripPlannerRequestID, "gibbonCourseClassID" => $gibbonCourseClassID, "date" => $date);
                    $sql = "SELECT tripPlannerRequestCoverID FROM tripPlannerRequestCover WHERE tripPlannerRequestID=:tripPlannerRequestID AND gibbonCourseClassID=:gibbonCourseClassID AND date=:date";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);

                    $update = ($result->rowCount() > 0);
                    $data["requiresCover"] = $requiresCover;
                    $sql = $update ? "UPDATE " : "INSERT INTO ";
                    $sql .= "tripPlannerRequestCover SET requiresCover=:requiresCover";
                    if ($update) {
                        $sql .= " WHERE tripPlannerRequestID=:tripPlannerRequestID AND gibbonCourseClassID=:gibbonCourseClassID AND date=:date";
                    } else {
                        $sql .= ", tripPlannerRequestID=:tripPlannerRequestID, gibbonCourseClassID=:gibbonCourseClassID, date=:date";
                    }
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch(PDOException $e) {
                    print $e;
                    $URL .= "trips_requestView.php&tripPlannerRequestID=$tripPlannerRequestID&return=error2";
                    header("Location: {$URL}");
                    exit();
                }

                $URL .= "trips_requestView.php&tripPlannerRequestID=$tripPlannerRequestID&return=success0";
                header("Location: {$URL}");
                exit();
            } else {
                $URL .= "trips_manage.php&return=error0";
                header("Location: {$URL}");
                exit();
            }
        } else {    
            $URL .= "trips_requestView.php&tripPlannerRequestID=$tripPlannerRequestID&return=error1";
            header("Location: {$URL}");
            exit();
        }
    } else {    
        $URL .= "trips_manage.php&return=error1";
        header("Location: {$URL}");
        exit();
    }
}   
?>
