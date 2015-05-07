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
include "./config.php" ;

$total=count($_SESSION[$guid]["messageWallOutput"]) ;

if (isset($_GET["count"])) {
	$count=$_GET["count"] ;
}
else {
	$count=0 ;
}

$rowCount=0 ;
$output=3 ; 
if ($total<$output) {
	$output=$total ;
}
for ($i=0 ; $i<$output ; $i++) {
	$offset=($count+$i)%$total ;
	$message=$_SESSION[$guid]["messageWallOutput"][$offset] ;
	if ($rowCount%2==0) {
		$rowNum="even" ;
	}
	else {
		$rowNum="odd" ;
	}
	$rowCount++ ;

	//COLOR ROW BY STATUS!
	print "<tr class=$rowNum>" ;
		print "<td style='font-size: 95%; letter-spacing: 85%'>" ;
			//Image
			$style="style='width: 45px; height: 60px; float: right; margin-left: 6px; border: 1px solid black'" ;
			if ($message["photo"]=="" OR file_exists($_SESSION[$guid]["absolutePath"] . "/" . $message["photo"])==FALSE) {    
				print "<img $style  src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/anonymous_75.jpg'/>" ;
			}
			else {
				print "<img $style src='" . $_SESSION[$guid]["absoluteURL"] . "/" . $message["photo"] . "'/>" ;
			}
			
			//Message number
			print "<div style='margin-bottom: 4px; text-transform: uppercase; font-size: 70%; color: #888'>Message " . ($offset+1) . "</div>" ;
			
			//Title
			$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Messenger/messageWall_view.php#" . $message["gibbonMessengerID"] ;
			if (strlen($message["subject"])<=16) {
				print "<a style='font-weight: bold; font-size: 105%; letter-spacing: 85%; text-transform: uppercase' href='$URL'>"  . $message["subject"] . "</a><br/>" ;
			}
			else {
				print "<a style='font-weight: bold; font-size: 105%; letter-spacing: 85%; text-transform: uppercase' href='$URL'>"  . substr($message["subject"], 0, 16) . "...</a><br/>" ;
			}
			
			//Text
			print "<div style='margin-top: 5px'>" ;
				if (strlen(strip_tags($message["details"]))<=40) {
					print strip_tags($message["details"]) . "<br/>" ;
				}
				else {
					print substr(strip_tags($message["details"]), 0, 40) . "...<br/>" ;
				}
			print "</div>" ;
		print "</td>" ;
	print "</tr>" ;
}	
?>