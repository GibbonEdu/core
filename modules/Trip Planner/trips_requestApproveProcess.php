<?php

use Gibbon\Comms\NotificationEvent;

//Module includes
include '../../gibbon.php';

include "./moduleFunctions.php";

$URL = $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Trip Planner/";

if (!isActionAccessible($guid, $connection2, '/modules/Trip Planner/trips_manage.php') || !isApprover($connection2, $_SESSION[$guid]["gibbonPersonID"])) {
    //Acess denied
    $URL .= "trips_manage.php&return=error0";
    header("Location: {$URL}");
    exit();
} else {
    if (isset($_POST["tripPlannerRequestID"])) {
        $tripPlannerRequestID = $_POST["tripPlannerRequestID"];

        try {
            $data = array("tripPlannerRequestID" => $tripPlannerRequestID);
            $sql = "SELECT * FROM `tripPlannerRequests` WHERE tripPlannerRequestID=:tripPlannerRequestID";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $URL .= "trips_manage.php&return=error2";
            header("Location: {$URL}");
            exit();
        }

        if ($result->rowCount() != 1) {
            $URL .= "trips_manage.php&return=error2";
            header("Location: {$URL}");
            exit();
        }
        else {
            $row = $result->fetch();
            $title = $row['title'];
            $riskAssessmentApproval = getSettingByScope($connection2, "Trip Planner", "riskAssessmentApproval");
            $status = getTripStatus($connection2, $tripPlannerRequestID);
            if (($approvalReturn = needsApproval($connection2, $tripPlannerRequestID, $_SESSION[$guid]["gibbonPersonID"])) == 0 || ($status == "Awaiting Final Approval" && isApprover($connection2, $_SESSION[$guid]["gibbonPersonID"], true))) {
                $URL .= "trips_requestApprove.php&tripPlannerRequestID=" . $tripPlannerRequestID;

                if (isset($_POST["approval"])) {
                    $approval = $_POST["approval"];
                } else {
                    $URL .= "&return=error1";
                    header("Location: {$URL}");
                    exit();
                }

                if (isset($_POST["comment"])) {
                    $comment = $_POST["comment"];
                } elseif ($approval == "Comment") {
                    $URL .= "&return=error1";
                    header("Location: {$URL}");
                    exit();
                }

                if ($approval == "Approval - Partial") {
                    if($status == "Awaiting Final Approval") {
                        try {
                            $data = array("tripPlannerRequestID" => $tripPlannerRequestID, "status" => $status);
                            $sql = "UPDATE tripPlannerRequests SET status='Approved' WHERE tripPlannerRequestID=:tripPlannerRequestID";
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $URL .= "trips_manage.php&return=error2";
                            header("Location: {$URL}");
                            exit();
                        }

                        requestNotification($guid, $connection2, $tripPlannerRequestID, $_SESSION[$guid]["gibbonPersonID"], "Approved");
                    } else {
                        $done = false;
                        $requestApprovalType = getSettingByScope($connection2, "Trip Planner", "requestApprovalType");
                        if ($requestApprovalType == "One Of") {
                            $done = true;
                        } elseif ($requestApprovalType == "Two Of") {
                            $done = (getEvents($connection2, $tripPlannerRequestID, array("Approval - Partial"))->rowCount() == 1);
                        } elseif ($requestApprovalType == "Chain Of All") {
                            try {
                                $data = array("gibbonPersonID" => $_SESSION[$guid]["gibbonPersonID"]);
                                $sql = "SELECT * FROM `tripPlannerApprovers` WHERE sequenceNumber > (SELECT sequenceNumber FROM `tripPlannerApprovers` WHERE gibbonPersonID=:gibbonPersonID)";
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                $URL .= "trips_manage.php&return=error2";
                                header("Location: {$URL}");
                                exit();
                            }

                            $done = $result->rowCount() == 0;
                        }

                        if ($done) {
                            $approval = "Approval - Final";
                            try {
                                $status = "Approved";
                                if($riskAssessmentApproval) {
                                    $status = "Awaiting Final Approval";
                                    $approval = "Approval - Awaiting Final Approval";
                                }

                                $data = array("tripPlannerRequestID" => $tripPlannerRequestID, "status" => $status);
                                $sql = "UPDATE tripPlannerRequests SET status=:status WHERE tripPlannerRequestID=:tripPlannerRequestID";
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                $URL .= "trips_manage.php&return=error2";
                                header("Location: {$URL}");
                                exit();
                            }

                            requestNotification($guid, $connection2, $tripPlannerRequestID, $_SESSION[$guid]["gibbonPersonID"], $status);

                            if ($status == "Approved") {
                                //Custom notifications for final approval
                                $event = new NotificationEvent('Trip Planner', 'Trip Request Approval');

                                $notificationText = sprintf(__m('Trip %1$s has been approved.'), $title);

                                $event->setNotificationText($notificationText);
                                $event->setActionLink('/index.php?q=/modules/Trip Planner/trips_requestView.php&tripPlannerRequestID='.$tripPlannerRequestID);

                                $event->sendNotifications($pdo, $gibbon->session);
                            }

                        } elseif ($requestApprovalType == "Chain Of All") {
                            try {
                                $data = array("gibbonPersonID" => $_SESSION[$guid]["gibbonPersonID"]);
                                $sql = "SELECT gibbonPersonID FROM `tripPlannerApprovers` WHERE sequenceNumber = (SELECT sequenceNumber FROM `tripPlannerApprovers` WHERE gibbonPersonID=:gibbonPersonID)+1";
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                $URL .= "trips_manage.php&return=error2";
                                header("Location: {$URL}");
                                exit();
                            }

                            $message = __m('A trip request is awaiting your approval.');
                            setNotification($connection2, $guid, $result->fetch()["gibbonPersonID"], $message, 'Trip Planner', "/index.php?q=/modules/Trip Planner/trips_requestApprove.php&tripPlannerRequestID=$tripPlannerRequestID");
                        }

                        if (!logEvent($connection2, $tripPlannerRequestID, $_SESSION[$guid]["gibbonPersonID"], $approval, $comment)) {
                            $URL .= "trips_manage.php&return=error2";
                            header("Location: {$URL}");
                            exit();
                        }
                    }
                } elseif ($approval == "Rejection") {
                    try {
                        $data = array("tripPlannerRequestID" => $tripPlannerRequestID);
                        $sql = "UPDATE tripPlannerRequests SET status='Rejected' WHERE tripPlannerRequestID=:tripPlannerRequestID";
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= "trips_manage.php&return=error2";
                        header("Location: {$URL}");
                        exit();
                    }
                    requestNotification($guid, $connection2, $tripPlannerRequestID, $_SESSION[$guid]["gibbonPersonID"], "Rejected");
                } elseif ($approval == "Comment") {
                    if (!logEvent($connection2, $tripPlannerRequestID, $_SESSION[$guid]["gibbonPersonID"], "Comment", $comment)) {
                        $URL .= "trips_manage.php&return=error2";
                        header("Location: {$URL}");
                        exit();
                    }
                    requestNotification($guid, $connection2, $tripPlannerRequestID, $_SESSION[$guid]["gibbonPersonID"], "Comment");
                }

                $URL = $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Trip Planner/trips_manage.php&return=success0";
                header("Location: {$URL}");
                exit();
            } else {
                $URL .= "trips_requestView.php&tripPlannerRequestID=" . $tripPlannerRequestID . "&return=error0";
                header("Location: {$URL}");
                exit();
            }
        }
    } else {
        $URL .= "trips_manage.php&return=error1";
        header("Location: {$URL}");
        exit();
    }
}
?>
