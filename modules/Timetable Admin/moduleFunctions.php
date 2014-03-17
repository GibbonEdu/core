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

function getNonTTYearGroups($connection2, $gibbonSchoolYearID, $gibbonTTID="") {
	
	$output=FALSE ;
	
	//Scan through year groups
	try {
		$data=array(); 
		$sql="SELECT * FROM gibbonYearGroup ORDER BY sequenceNumber" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { }
	while ($row=$result->fetch()) {
		//Check if year group is in an active TT this year
		try {
			$dataTT=array("gibbonYearGroupIDList"=>"%" . $row["gibbonYearGroupID"] . "%", "gibbonSchoolYearID"=>$gibbonSchoolYearID); 
			$sqlTTWhere="" ;
			if ($gibbonTTID!="") {
				$dataTT[$gibbonTTID]=$gibbonTTID ;
				$sqlTTWhere=" AND NOT gibbonTTID=:$gibbonTTID" ;
			}
			$sqlTT="SELECT * FROM gibbonTT WHERE gibbonYearGroupIDList LIKE :gibbonYearGroupIDList AND active='Y' AND gibbonSchoolYearID=:gibbonSchoolYearID $sqlTTWhere" ;
			$resultTT=$connection2->prepare($sqlTT);
			$resultTT->execute($dataTT);
		}
		catch(PDOException $e) { }
		
		if ($resultTT->rowCount()<1) {
			$output.=$row["gibbonYearGroupID"] . "," ;
			$output.=$row["name"] . "," ;
		}
	}
	
	if ($output!=FALSE) {
		$output=substr($output,0,(strlen($output)-1)) ;
		$output=explode(",", $output) ;
	}
	
	return $output ;
}
?>
