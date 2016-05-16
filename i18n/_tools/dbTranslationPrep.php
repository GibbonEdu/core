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

print "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />" ;
//New PDO DB connection
try {
  	$connection2=new PDO("mysql:host=localhost;dbname=gibbon_dev_core;charset=utf8", 'root', '42Liblabb');
	$connection2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$connection2->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
}
catch(PDOException $e) {
  echo $e->getMessage();
}

$queries=array() ;

$count=0 ; $queries[$count][0]="gibbonAction" ; $queries[$count][1]="category" ;
$count++ ; $queries[$count][0]="gibbonAction" ; $queries[$count][1]="name" ;
$count++ ; $queries[$count][0]="gibbonAction" ; $queries[$count][1]="description" ;
$count++ ; $queries[$count][0]="gibbonAlertLevel" ; $queries[$count][1]="name" ;
$count++ ; $queries[$count][0]="gibbonAlertLevel" ; $queries[$count][1]="description" ;
$count++ ; $queries[$count][0]="gibbonCountry" ; $queries[$count][1]="printable_name" ;
$count++ ; $queries[$count][0]="gibbonDaysOfWeek" ; $queries[$count][1]="name" ;
$count++ ; $queries[$count][0]="gibbonExternalAssessment" ; $queries[$count][1]="name" ;
$count++ ; $queries[$count][0]="gibbonExternalAssessment" ; $queries[$count][1]="nameShort" ;
$count++ ; $queries[$count][0]="gibbonExternalAssessment" ; $queries[$count][1]="description" ;
$count++ ; $queries[$count][0]="gibbonExternalAssessmentField" ; $queries[$count][1]="name" ;
$count++ ; $queries[$count][0]="gibbonExternalAssessmentField" ; $queries[$count][1]="category" ;
$count++ ; $queries[$count][0]="gibbonFileExtension" ; $queries[$count][1]="type" ;
$count++ ; $queries[$count][0]="gibbonFileExtension" ; $queries[$count][1]="name" ;
$count++ ; $queries[$count][0]="gibbonINDescriptor" ; $queries[$count][1]="name" ;
$count++ ; $queries[$count][0]="gibbonINDescriptor" ; $queries[$count][1]="nameShort" ;
$count++ ; $queries[$count][0]="gibbonINDescriptor" ; $queries[$count][1]="description" ;
$count++ ; $queries[$count][0]="gibbonLibraryType" ; $queries[$count][1]="name" ;
$count++ ; $queries[$count][0]="gibbonLibraryType" ; $queries[$count][1]="fields" ;
$count++ ; $queries[$count][0]="gibbonMedicalCondition" ; $queries[$count][1]="name" ;
$count++ ; $queries[$count][0]="gibbonModule" ; $queries[$count][1]="name" ;
$count++ ; $queries[$count][0]="gibbonModule" ; $queries[$count][1]="description" ;
$count++ ; $queries[$count][0]="gibbonModule" ; $queries[$count][1]="category" ;
$count++ ; $queries[$count][0]="gibbonRole" ; $queries[$count][1]="category" ;
$count++ ; $queries[$count][0]="gibbonRole" ; $queries[$count][1]="name" ;
$count++ ; $queries[$count][0]="gibbonRole" ; $queries[$count][1]="nameShort" ;
$count++ ; $queries[$count][0]="gibbonRole" ; $queries[$count][1]="description" ;
$count++ ; $queries[$count][0]="gibbonScale" ; $queries[$count][1]="name" ;
$count++ ; $queries[$count][0]="gibbonScale" ; $queries[$count][1]="nameShort" ;
$count++ ; $queries[$count][0]="gibbonScale" ; $queries[$count][1]="usage" ;
$count++ ; $queries[$count][0]="gibbonScale" ; $queries[$count][1]="name" ;
$count++ ; $queries[$count][0]="gibbonScaleGrade" ; $queries[$count][1]="value" ;
$count++ ; $queries[$count][0]="gibbonScaleGrade" ; $queries[$count][1]="descriptor" ;
$count++ ; $queries[$count][0]="gibbonSetting" ; $queries[$count][1]="nameDisplay" ;
$count++ ; $queries[$count][0]="gibbonSetting" ; $queries[$count][1]="description" ;
$count++ ; $queries[$count][0]="gibbonYearGroup" ; $queries[$count][1]="name" ;
$count++ ; $queries[$count][0]="gibbonYearGroup" ; $queries[$count][1]="nameShort" ;


foreach ($queries AS $query) {
	print "//" . $query[0] . " - " . $query[1] . "<br/>" ;
	try {
		$data=array();
		$result=$connection2->prepare("SELECT DISTINCT `" . $query[1] . "` FROM `" . $query[0] . "` WHERE NOT `" . $query[1] . "`='' ORDER BY `" . $query[1] . "`");
		$result->execute($data);
	}
	catch(PDOException $e) { print "<span='color: red'>" . $e->getMessage() . "</span>" ; }
	while ($row=$result->fetch()) {
		//Deal with special case of gibbonAction names
		if ($query[0]=="gibbonAction" AND $query[1]=="name") {
			if (strpos($row[$query[1]],'_')===false) {
				print "__($" . "guid" . ", '" . addslashes($row[$query[1]]) . "') ;<br/>" ;
			}
			else {
				print "__($" . "guid" . ", '" . addslashes(substr($row[$query[1]],0, strpos($row[$query[1]],'_'))) . "') ;<br/>" ;
			}
		}
		//Deal with special case of gibbonExternalAssessmentField categories
		else if ($query[0]=="gibbonExternalAssessmentField" AND $query[1]=="category") {
			if (strpos($row[$query[1]],'_')===false) {
				print "__($" . "guid" . ", '" . addslashes($row[$query[1]]) . "') ;<br/>" ;
			}
			else {
				print "__($" . "guid" . ", '" . addslashes(substr($row[$query[1]],(strpos($row[$query[1]],'_')+1))) . "') ;<br/>" ;
			}
		}
		//Deal with special case of gibbonExternalAssessmentField categories
		else if ($query[0]=="gibbonLibraryType" AND $query[1]=="fields") {
			$fields=array() ;
			$fields=unserialize($row[$query[1]]) ;
			foreach ($fields AS $field) {
				print "__($" . "guid" . ", '" . addslashes($field["name"] ) . "') ;<br/>" ;
			}
		}
		//Deal with all other cases
		else {
			print "__($" . "guid" . ", '" . addslashes($row[$query[1]]) . "') ;<br/>" ;
		}
	}
	print "<br/>" ;
}
?>
