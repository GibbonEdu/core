<?php

//Module includes
include '../../gibbon.php';

include "./moduleFunctions.php";

$URL = $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Trip Planner/trips_manageSettings.php";

if (!isActionAccessible($guid, $connection2, '/modules/Trip Planner/trips_manageSettings.php')) {
    //Acess denied
    $URL .= "&return=error0";
    header("Location: {$URL}");
    exit();
} else {

    $settings = array("requestApprovalType", "riskAssessmentTemplate", "missedClassWarningThreshold", "riskAssessmentApproval", "defaultRiskTemplate", "expiredUnapprovedFilter", "letterToParentsTemplate");

    foreach ($settings as $setting) {
        $value = null;
        if (isset($_POST[$setting])) {
            if ($_POST[$setting] != null && $_POST[$setting] != "") {
                if($setting == "missedClassWarningThreshold") {
                    $value = abs($_POST[$setting]);
                } else if($setting == "riskAssessmentApproval" || $setting == "expiredUnapprovedFilter") {
                    $value = 1;
                } else {
                    $value = $_POST[$setting];
                }
            }
        } else if($setting == "riskAssessmentApproval" || $setting == "expiredUnapprovedFilter") {
            $value = 0;
        }

        if ($value === null && ($setting != "riskAssessmentTemplate" && $setting != "letterToParentsTemplate")) {
            $URL .= "&return=error1";
            header("Location: {$URL}");
            exit();
        }

        try {
            $data = array("value" => $value, "setting" => $setting);
            $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Trip Planner' AND name=:setting;";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $URL .= "&return=error2";
            header("Location: {$URL}");
            exit();
        }

        if($setting == "riskAssessmentApproval") {
            try {
                $sql = "UPDATE tripPlannerRequests SET status='Requested' WHERE status='Awaiting Final Approval'";
                $result = $connection2->prepare($sql);
                $result->execute();
            } catch (PDOException $e) {
            }
        }
    }
 
    $URL .= "&return=success0";
    header("Location: {$URL}");
    exit();
}   
?>
