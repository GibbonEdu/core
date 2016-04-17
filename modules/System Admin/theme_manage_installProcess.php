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

include "../../functions.php" ;
include "../../config.php" ;

//New PDO DB connection
$pdo = new sqlConnection();
$connection2 = $pdo->getConnection();

@session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

//Get URL from calling page, and set returning URL
$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/System Admin/theme_manage.php" ;

if (isActionAccessible($guid, $connection2, "/modules/System Admin/theme_manage_install.php")==FALSE) {
	//Fail 0
	$URL.="&addReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	$themeName=NULL ;
	if (isset($_GET["name"])) {
		$themeName=$_GET["name"] ;
	}

	if ($themeName==NULL OR $themeName=="") {
		//Fail 3
		$URL.="&addReturn=fail3" ;
		header("Location: {$URL}");
	}
	else {
		if (!(include $_SESSION[$guid]["absolutePath"] . "/themes/$themeName/manifest.php")) {
			//Fail 3
			$URL.="&addReturn=fail3" ;
			header("Location: {$URL}");
		}
		else {
			//Validate Inputs
			if ($name=="" OR $description=="" OR $version=="") {
				//Fail 3
				$URL.="&addReturn=fail3" ;
				header("Location: {$URL}");
			}
			else {
				//Check for existence of theme
				try {
					$dataModule=array("name"=>$name); 
					$sqlModule="SELECT * FROM gibbonTheme WHERE name=:name" ;
					$resultModule=$connection2->prepare($sqlModule);
					$resultModule->execute($dataModule);
				}
				catch(PDOException $e) { 
					//Fail 2
					$URL.="&addReturn=fail2" ;
					header("Location: {$URL}");
					exit() ;
				}

				if ($resultModule->rowCount()>0) {
					//Fail 4
					$URL.="&addReturn=fail4" ;
					header("Location: {$URL}");
				}
				else {
					//Insert new theme row
					try {
						$dataModule=array("name"=>$name, "description"=>$description, "version"=>$version, "author"=>$author, "url"=>$url) ; 
						$sqlModule="INSERT INTO gibbonTheme SET name=:name, description=:description, active='N', version=:version, author=:author, url=:url" ;
						$resultModule=$connection2->prepare($sqlModule);
						$resultModule->execute($dataModule);
					}
					catch(PDOException $e) { 
						//Fail 2
						$URL.="&addReturn=fail2" ;
						header("Location: {$URL}");
						exit() ;
					}
				
					//Success 0
					$URL.="&addReturn=success0" ;
					header("Location: {$URL}");
				}
			}
		}
	}
}
?>