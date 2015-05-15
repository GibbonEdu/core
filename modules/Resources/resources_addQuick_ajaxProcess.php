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
try {
  	$connection2=new PDO("mysql:host=$databaseServer;dbname=$databaseName;charset=utf8", $databaseUsername, $databasePassword);
	$connection2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$connection2->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
}
catch(PDOException $e) {
  echo $e->getMessage();
}

@session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$time=time() ;
	
if (empty($_POST) OR empty($_FILES)) {
	//Fail 5
	print "<span style='font-weight: bold; color: #ff0000'>" ;
		print "Your request failed due to an attachment error." ;
	print "</span>" ;
}
else {
	//Proceed!
	$id=$_POST["id"] ;
	$imagesAsLinks=FALSE ;
	if ($_POST["imagesAsLinks"]=="Y") {
		$imagesAsLinks=TRUE ;
	}
	
	if ($id=="") {
		//Fail 3
		print "<span style='font-weight: bold; color: #ff0000'>" ;
			print _("Your request failed because your inputs were invalid.") ;
		print "</span>" ;
	}
	else {
		//Check if multiple files
		$multiple=FALSE ;
		$multipleCount=0 ;
		for ($i=1; $i<5; $i++) {
			if (isset($_FILES[$id . "file" . $i])) { 
				$multipleCount++ ;
			}
		}
		if ($multipleCount>1) {
			$multiple=TRUE ;
		}
		
		//Insert files
		for ($i=1; $i<5; $i++) {
			$html="" ;
			if (isset($_FILES[$id . "file" . $i])) { 
				$name=substr($_FILES[$id . "file" . $i]["name"], 0, strrpos($_FILES[$id . "file" . $i]["name"], ".")) ;
				if ($_FILES[$id . "file" . $i]["tmp_name"]!="") {
					//Check for folder in uploads based on today's date
					$path=$_SESSION[$guid]["absolutePath"] ; ;
					if (is_dir($path ."/uploads/" . date("Y", $time) . "/" . date("m", $time))==FALSE) {
						mkdir($path ."/uploads/" . date("Y", $time) . "/" . date("m", $time), 0777, TRUE) ;
					}
					$unique=FALSE;
					$count=0 ;
					while ($unique==FALSE AND $count<100) {
						$suffix=randomPassword(16) ;
						$attachment="uploads/" . date("Y", $time) . "/" . date("m", $time) . "/" . preg_replace("/[^a-zA-Z0-9]/", "", $name) . "_$suffix" . strrchr($_FILES[$id . "file" . $i]["name"], ".") ;
						if (!(file_exists($path . "/" . $attachment))) {
							$unique=TRUE ;
						}
						$count++ ;
					}
					if (!(move_uploaded_file($_FILES[$id . "file" . $i]["tmp_name"],$path . "/" . $attachment))) {
						//Fail 5
						print "<span style='font-weight: bold; color: #ff0000'>" ;
							print "Your request failed due to an attachment error." ;
						print "</span>" ;
					}
				} 
				
				$extension=strrchr($attachment, ".") ;
				if ((strcasecmp($extension, ".gif")==0 OR strcasecmp($extension, ".jpg")==0 OR strcasecmp($extension, ".jpeg")==0 OR strcasecmp($extension, ".png")==0) AND $imagesAsLinks==FALSE) {
					$html="<a target='_blank' style='font-weight: bold' href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $attachment . "'><img class='resource' style='max-width: 500px' src='" . $_SESSION[$guid]["absoluteURL"] . "/" . $attachment . "'></a>" ;
				}
				else {
					$html="<a target='_blank' style='font-weight: bold' href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $attachment . "'>" . $name . "</a>" ;
				}
			}
			if ($multiple) {
				print "<br/>" ;
			}
			print $html ;
		}
	}
}
?>