<?
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

//Rubric includes
include "./modules/Rubrics/moduleFunctions.php" ;

@session_start() ;

if (isActionAccessible($guid, $connection2, "/modules/Rubrics/rubrics_view_full.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this page." ;
	print "</div>" ;
}
else {
	//Proceed!
	//Check if school year specified
	$gibbonRubricID=$_GET["gibbonRubricID"] ;
	if ($gibbonRubricID=="") {
		print "<div class='error'>" ;
			print "You have not specified one or more required parameters." ;
		print "</div>" ;
	}
	else {
		try {
		$data3=array("gibbonRubricID"=>$gibbonRubricID); 
		$sql3="SELECT * FROM gibbonRubric WHERE gibbonRubricID=:gibbonRubricID" ;
		$result3=$connection2->prepare($sql3);
		$result3->execute($data3);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}

	if ($result3->rowCount()!=1) {
		print "<div class='error'>" ;
			print "The selected rubric does not exist." ;
		print "</div>" ;
	}
	else {
			//Let's go!
			$row3=$result3->fetch() ;
			
			print "<h2 style='margin-bottom: 10px;'>" ;
				print $row3["name"] . "<br/>" ;
			print "</h2>" ;
			
			print rubricView($guid, $connection2, $gibbonRubricID, FALSE ) ;
		}
	}	
}	
?>