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

include '../../functions.php';
include '../../config.php';

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/trackingSettings.php';

if (isActionAccessible($guid, $connection2, '/modules/School Admin/trackingSettings.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $fail = false;

   //DEAL WITH EXTERNAL ASSESSMENT DATA POINTS
   $externalAssessmentDataPoints = (isset($_POST['externalDP']))? $_POST['externalDP'] : null;

    if (!empty($externalAssessmentDataPoints) && is_array($externalAssessmentDataPoints)) {
      foreach ($externalAssessmentDataPoints as &$dp) {
        if (!empty($dp['gibbonYearGroupIDList'])) {
          $dp['category'] = filter_var($dp['category'], FILTER_SANITIZE_SPECIAL_CHARS);
          $dp['gibbonExternalAssessmentID'] = filter_var($dp['gibbonExternalAssessmentID'], FILTER_SANITIZE_NUMBER_INT);
          $dp['gibbonYearGroupIDList'] = implode(',', $dp['gibbonYearGroupIDList']);
        }
      }
    }

   //Write setting to database
   try {
       $data = array('value' => serialize($externalAssessmentDataPoints));
       $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Tracking' AND name='externalAssessmentDataPoints'";
       $result = $connection2->prepare($sql);
       $result->execute($data);
   } catch (PDOException $e) {
       $fail = true;
   }

   //DEAL WITH INTERNAL ASSESSMENT DATA POINTS
   $internalAssessmentDataPoints = (isset($_POST['internalDP']))? $_POST['internalDP'] : null;

    if (!empty($internalAssessmentDataPoints) && is_array($internalAssessmentDataPoints)) {
      foreach ($internalAssessmentDataPoints as &$dp) {
        if (!empty($dp['gibbonYearGroupIDList'])) {
          $dp['type'] = filter_var($dp['type'], FILTER_SANITIZE_SPECIAL_CHARS);
          $dp['gibbonYearGroupIDList'] = implode(',', $dp['gibbonYearGroupIDList']);
        }
      }
    }

   //Write setting to database
   try {
       $data = array('value' => serialize($internalAssessmentDataPoints));
       $sql = "UPDATE gibbonSetting SET value=:value WHERE scope='Tracking' AND name='internalAssessmentDataPoints'";
       $result = $connection2->prepare($sql);
       $result->execute($data);
   } catch (PDOException $e) {
       $fail = true;
   }

   //RETURN RESULTS
   if ($fail == true) {
       $URL .= '&return=error2';
       header("Location: {$URL}");
   } else {
       //Success 0
        $URL .= '&return=success0';
       header("Location: {$URL}");
   }
}
