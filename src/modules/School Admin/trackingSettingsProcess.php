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

namespace Module\School_Admin ;

use Gibbon\core\post ;

if (! $this instanceof post) die();

$URL = array('q' => '/modules/School Admin/trackingSettings.php') ;

if (! $this->getSecurity()->isActionAccessible("/modules/School Admin/trackingSettings.php", NULL, '')) {
	$this->insertMessage("return.error.0") ;
	$this->redirect($URL);
}
else {
	$fail = false ;
	
	//DEAL WITH EXTERNAL ASSESSMENT DATA POINTS
	$externalAssessmentDataPoints = array() ;
	$assessmentCount = filter_var($_POST["gibbonExternalAssessmentID_count"], FILTER_VALIDATE_INT) ;
	$yearCount = filter_var($_POST["external_year_count"], FILTER_VALIDATE_INT) ;
	$count = 0 ;
	for ($i=0; $i<$assessmentCount; $i++) {
		$externalAssessmentDataPoints[$count]["gibbonExternalAssessmentID"] = filter_var($_POST["gibbonExternalAssessmentID"][$i]['ID']) ;
		$externalAssessmentDataPoints[$count]["category"] = filter_var($_POST["external_category"][$i]['category']) ;
		$externalAssessmentDataPoints[$count]["gibbonYearGroupIDList"] = "" ;
		for ($j=0; $j<$yearCount; $j++) {
			if (isset($_POST["gibbonExternalAssessmentID"][$i]["gibbonYearGroupID"][$j])) {
				$externalAssessmentDataPoints[$count]["gibbonYearGroupIDList"] .= filter_var($_POST["gibbonExternalAssessmentID"][$i]["gibbonYearGroupID"][$j]) . "," ;
			}
		}
		if (! empty($externalAssessmentDataPoints[$count]["gibbonYearGroupIDList"])) {
			$externalAssessmentDataPoints[$count]["gibbonYearGroupIDList"] = rtrim($externalAssessmentDataPoints[$count]["gibbonYearGroupIDList"], ',') ;
		}
		$count++ ;
	}

	//Write External setting to database
	if (! $this->config->setSettingByScope('externalAssessmentDataPoints', serialize($externalAssessmentDataPoints), 'Tracking')) $fail = true;
	
	//DEAL WITH INTERNAL ASSESSMENT DATA POINTS
	$internalAssessmentDataPoints = array() ;
	$assessmentCount = filter_var($_POST["internal_type_count"], FILTER_VALIDATE_INT) ;
	$yearCount = filter_var($_POST["internal_year_count"], FILTER_VALIDATE_INT) ;
	$count = 0 ;
	for ($i=0; $i<$assessmentCount; $i++) {
		if (isset($_POST["internal_type"][$i])){
			$internalAssessmentDataPoints[$count]["type"] = filter_var($_POST["internal_type"][$i]['type']) ;
			$internalAssessmentDataPoints[$count]["gibbonYearGroupIDList"] = "" ;
			for ($j=0; $j<$yearCount; $j++) {
				if (isset($_POST["internal_type"][$i]["gibbonYearGroupID"][$j])) {
					$internalAssessmentDataPoints[$count]["gibbonYearGroupIDList"] .= filter_var($_POST["internal_type"][$i]["gibbonYearGroupID"][$j]) . "," ;
				}
			}
			if (! empty($internalAssessmentDataPoints[$count]["gibbonYearGroupIDList"])) {
				$internalAssessmentDataPoints[$count]["gibbonYearGroupIDList"] = rtrim($internalAssessmentDataPoints[$count]["gibbonYearGroupIDList"], ',') ;
			}
			$count++ ;
		}
	}

	//Write internal setting to database
	if (! $this->config->setSettingByScope('internalAssessmentDataPoints', serialize($internalAssessmentDataPoints), 'Tracking')) $fail = true;
	
	//RETURN RESULTS
	if ($fail) {
		$this->insertMessage("return.error.2");
		$this->redirect($URL);
	}
	else {
		//Success 0
		$this->insertMessage("return.success.0", 'success');
		$this->redirect($URL);
	}
}
