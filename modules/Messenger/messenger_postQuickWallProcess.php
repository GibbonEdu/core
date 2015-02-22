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

//Module includes
include "./moduleFunctions.php" ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["address"]) . "/messenger_postQuickWall.php" ;
$time=time() ;

if (isActionAccessible($guid, $connection2, "/modules/Messenger/messenger_postQuickWall.php")==FALSE) {
	//Fail 0
	$URL.="&addReturn=fail0" ;
	header("Location: {$URL}");
}
else {
	if (empty($_POST)) {
		//Fail 5
		$URL.="&addReturn=fail5" ;
		header("Location: {$URL}");
	}
	else {
		//Proceed!
		//Setup return variables
		$messageWall=$_POST["messageWall"] ;
		if ($messageWall!="Y") {
			$messageWall="N" ;
		}
		$date1=NULL ;
		if (isset($_POST["date1"])) {
			if ($_POST["date1"]!="") {
				$date1=dateConvert($guid, $_POST["date1"]) ;
			}
		}
		$date2=NULL ;
		if (isset($_POST["date2"])) {
			if ($_POST["date2"]!="") {
				$date2=dateConvert($guid, $_POST["date2"]) ;
			}
		}
		$date3=NULL ;
		if (isset($_POST["date3"])) {
			if ($_POST["date3"]!="") {
				$date3=dateConvert($guid, $_POST["date3"]) ;
			}
		}
		$subject=$_POST["subject"] ;
		$body=stripslashes($_POST["body"]) ;
		
		if ($subject=="" OR $body=="") {
			//Fail 3
			$URL.="&addReturn=fail3" ;
			header("Location: {$URL}");
		}
		else {
			//Lock table
			try {
				$sql="LOCK TABLES gibbonMessenger WRITE" ;
				$result=$connection2->query($sql);  
			}
			catch(PDOException $e) { 
				//Fail 2
				$URL.="&addReturn=fail2" ;
				header("Location: {$URL}");
				break ;
			}		
			
			//Get next autoincrement
			try {
				$sqlAI="SHOW TABLE STATUS LIKE 'gibbonMessenger'";
				$resultAI=$connection2->query($sqlAI);  
			}
			catch(PDOException $e) { 
				//Fail 2
				$URL.="&addReturn=fail2" ;
				header("Location: {$URL}");
				break ;
			}		
			
			$rowAI=$resultAI->fetch();
			$AI=str_pad($rowAI['Auto_increment'], 12, "0", STR_PAD_LEFT) ;
			
			//Write to database
			try {
				$data=array("email"=>"", "messageWall"=>$messageWall, "messageWall_date1"=>$date1, "messageWall_date2"=>$date2, "messageWall_date3"=>$date3, "sms"=>"", "subject"=>$subject, "body"=>$body, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "timestamp"=>date("Y-m-d H:i:s")); 
				$sql="INSERT INTO gibbonMessenger SET email=:email, messageWall=:messageWall, messageWall_date1=:messageWall_date1, messageWall_date2=:messageWall_date2, messageWall_date3=:messageWall_date3, sms=:sms, subject=:subject, body=:body, gibbonPersonID=:gibbonPersonID, timestamp=:timestamp" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				//Fail 2
				print $e->getMessage() ; exit() ;
				$URL.="&addReturn=fail2" ;
				header("Location: {$URL}");
				break ;
			}
			
			try {
				$sql="UNLOCK TABLES" ;
				$result=$connection2->query($sql);  
			}
			catch(PDOException $e) { 
				//Fail 2
				$URL.="&addReturn=fail2" ;
				header("Location: {$URL}");
				break ;
			}		
			
			$partialFail=FALSE ;
			$choices=$_POST["roleCategories"] ;
			if ($choices!="") {
				foreach ($choices as $t) {
					try {
						$data=array("AI"=>$AI, "t"=>$t); 
						$sql="INSERT INTO gibbonMessengerTarget SET gibbonMessengerID=:AI, type='Role Category', id=:t" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						$partialFail=TRUE;
					}
				}
			}
						
			if ($partialFail==TRUE) {
				//Fail 4
				$URL.="&addReturn=fail4" ;
				header("Location: {$URL}");
			}
			else {
				//Success 0
				$URL.="&addReturn=success0" ;
				header("Location: {$URL}") ;
			}
		}
	}
}
?>