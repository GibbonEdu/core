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

function getRole( $gibbonPersonID, $gibbonDepartmentID, $connection2 ) {
	$role=FALSE ;
	try {
		$data=array("gibbonDepartmentID"=>$gibbonDepartmentID, "gibbonPersonID"=>$gibbonPersonID); 
		$sql="SELECT * FROM gibbonDepartmentStaff WHERE gibbonDepartmentID=:gibbonDepartmentID AND gibbonPersonID=:gibbonPersonID" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { }
	
	if ($result->rowCount()==1) {
		$row=$result->fetch() ;
		if ($row["role"]!="") {
			$role=$row["role"] ;
		}
	}
	return $role ;
}
?>
