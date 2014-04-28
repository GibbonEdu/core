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

@session_start() ;

//Gibbon system-wide includes
include "../../functions.php" ;
include "../../config.php" ;

//New PDO DB connection
try {
  	$connection2=new PDO("mysql:host=$databaseServer;dbname=$databaseName;charset=utf8", $databaseUsername, $databasePassword);
	$connection2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$connection2->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
}
catch(PDOException $e) {
  echo $e->getMessage();
}

print "<link rel='stylesheet' type='text/css' href='" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/css/main.css' />" ;
?>

<div id="wrap">
	<div id="header">
		<div id="header-left">
			<a href='<?php print $_SESSION[$guid]["absoluteURL"] ?>'><img height='107px' width='250px' class="logo" alt="Logo" title="Logo" src="<?php print $_SESSION[$guid]["absoluteURL"] . "/" . $_SESSION[$guid]["organisationLogo"] ; ?>"/></a>
		</div>
		<div id="header-right">
		
		</div>
	</div>
	<div id="content-wrap">
		<div id="content">
			<?php
			if (isActionAccessible($guid, $connection2, "/modules/Resources/resources_view_full.php")==FALSE) {
				//Acess denied
				print "<div class='error'>" ;
					print "Your request failed because you do not have access to this action." ;
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
							print _("The specified record does not exist.") ;
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
		</div>
		<div id="sidebar">
		</div>
	</div>
	<div id="footer">
		<a href="http://gibbonedu.org">Gibbon</a> v<?php print $version ?> | &#169; 2011, <a href="http://rossparker.org">Ross Parker</a> at <a href="http://www.ichk.edu.hk">International College Hong Kong</a> | Created under the <a href="http://www.gnu.org/licenses/gpl.html">GNU General Public License</a>
	</div>
</div>