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

session_start() ;

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Resources/resources_view_full.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this page." ;
	print "</div>" ;
}
else {	
	//Proceed!
	//Get class variable
	$gibbonResourceID=$_GET["gibbonResourceID"] ;
	if ($gibbonResourceID=="") {
		print "<div class='warning'>" ;
			print "Resource has not been specified ." ;
		print "</div>" ;
	}
	//Check existence of and access to this class.
	else {
		try {
			$data=array("gibbonResourceID"=>$gibbonResourceID); 
			$sql="SELECT * FROM gibbonResource WHERE gibbonResourceID=:gibbonResourceID" ; 
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		if ($result->rowCount()!=1) {
			print "<div class='warning'>" ;
				print "Resource does not exist." ;
			print "</div>" ;
		}
		else {
			$row=$result->fetch() ;
			
			print "<h1>" ;
			print $row["name"] ;
			print "</h1>" ;
			
			print $row["content"] ; 
		}
	}
}		
?>