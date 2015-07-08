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

$URL=$_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_POST["address"]) . "/resources_manage_add.php" ;
$time=time() ;

if (isActionAccessible($guid, $connection2, "/modules/Resources/resources_manage_add.php")==FALSE) {
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
		$highestAction=getHighestGroupedAction($guid, $_POST["address"], $connection2) ;
		if ($highestAction==FALSE) {
			//Fail 0
			$URL.="&updateReturn=fail0" ;
			header("Location: {$URL}");
		}
		else {
			//Proceed!
			$type=$_POST["type"] ; 
			if ($type=="File") {
				$content=$_FILES['file'] ;
			}
			else if ($type=="HTML") {
				$content=$_POST['html'] ;
			}
			else if ($type=="Link") {
				$content=$_POST['link'] ;
			}
			$name=$_POST["name"] ; 
			$category=$_POST["category"] ; 
			$purpose=$_POST["purpose"] ; 
			$tags=strtolower($_POST["tags"]) ;
			$gibbonYearGroupIDList="" ;
			for ($i=0; $i<$_POST["count"]; $i++) {
				if (isset($_POST["gibbonYearGroupIDCheck$i"])) {
					if ($_POST["gibbonYearGroupIDCheck$i"]=="on") {
						$gibbonYearGroupIDList=$gibbonYearGroupIDList . $_POST["gibbonYearGroupID$i"] . "," ;
					}
				}
			}
			$gibbonYearGroupIDList=substr($gibbonYearGroupIDList,0,(strlen($gibbonYearGroupIDList)-1)) ;
			$description=$_POST["description"] ; 
				
			if (($type!="File" AND $type!="HTML" AND $type!="Link") OR is_null($content) OR $name=="" OR $category=="" OR $tags=="") {
				//Fail 3
				$URL.="&addReturn=fail3" ;
				header("Location: {$URL}");
			}
			else {
				if ($type=="File") {
					if ($_FILES['file']["tmp_name"]!="") {
						//Check for folder in uploads based on today's date
						$path=$_SESSION[$guid]["absolutePath"] ; ;
						if (is_dir($path ."/uploads/" . date("Y", $time) . "/" . date("m", $time))==FALSE) {
							mkdir($path ."/uploads/" . date("Y", $time) . "/" . date("m", $time), 0777, TRUE) ;
						}
						$unique=FALSE;
						$count=0 ;
						while ($unique==FALSE AND $count<100) {
							$suffix=randomPassword(16) ;
							$attachment="uploads/" . date("Y", $time) . "/" . date("m", $time) . "/" . preg_replace("/[^a-zA-Z0-9]/", "", $name) . "_$suffix" . strrchr($_FILES["file"]["name"], ".") ;
							if (!(file_exists($path . "/" . $attachment))) {
								$unique=TRUE ;
							}
							$count++ ;
						}
						if (!(move_uploaded_file($_FILES["file"]["tmp_name"],$path . "/" . $attachment))) {
							//Fail 5
							$URL.="&addReturn=fail5" ;
							header("Location: {$URL}");
						}
					}
					$content=$attachment ;
				}
				
				//Deal with tags
				try {
					$sql="LOCK TABLES gibbonResourceTag WRITE" ;
					$result=$connection2->query($sql);   
				}
				catch(PDOException $e) { 
					//Fail 2
					$URL.="&addReturn=fail2" ;
					header("Location: {$URL}");
					break ;
				}		

				//Update tag counts
				$partialFail=FALSE ;
				$tags=explode(",", $_POST["tags"]) ;
				$tagList="" ;
				foreach ($tags as $tag) {
					if (trim($tag)!="") {
						$tagList.="'" . trim($tag) . "'," ;
						try {
							$dataTags=array("tag"=>trim($tag)); 
							$sqlTags="SELECT * FROM gibbonResourceTag WHERE tag=:tag" ;
							$resultTags=$connection2->prepare($sqlTags);
							$resultTags->execute($dataTags);
						}
						catch(PDOException $e) { 
							$partialFail=TRUE ;
						}
						if ($resultTags->rowCount()==1) {
							$rowTags=$resultTags->fetch() ;
							try {
								$dataTag=array("count"=>($rowTags["count"]+1), "tag"=>trim($tag)); 
								$sqlTag="UPDATE gibbonResourceTag SET count=:count WHERE tag=:tag" ;
								$resultTag=$connection2->prepare($sqlTag);
								$resultTag->execute($dataTag);
							}
							catch(PDOException $e) { 
								$partialFail=TRUE ;
							}
						}
						else if ($resultTags->rowCount()==0) {
							try {
								$dataTag=array("tag"=>trim($tag)); 
								$sqlTag="INSERT INTO gibbonResourceTag SET tag=:tag, count=1" ;
								$resultTag=$connection2->prepare($sqlTag);
								$resultTag->execute($dataTag);
							}
							catch(PDOException $e) { 
								$partialFail=TRUE ;
							}
						}
						else {
							$partialFail=TRUE ;
						}
					}
				}
				//Unlock table
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
				
				//Write to database
				try {
					$data=array("type"=>$type, "content"=>$content, "name"=>$name, "category"=>$category, "purpose"=>$purpose, "tags"=>substr($tagList,0,-1), "gibbonYearGroupIDList"=>$gibbonYearGroupIDList, "description"=>$description, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "timestamp"=>date("Y-m-d H:i:s", $time) ); 
					$sql="INSERT INTO gibbonResource SET type=:type, content=:content, name=:name, category=:category, purpose=:purpose, tags=:tags, gibbonYearGroupIDList=:gibbonYearGroupIDList, description=:description, gibbonPersonID=:gibbonPersonID, timestamp=:timestamp" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					//Fail 2
					$URL.="&addReturn=fail2" ;
					header("Location: {$URL}");
					break ;
				}
				
				if ($partialFail==TRUE) {
					$URL.="&addReturn=fail6" ;
					header("Location: {$URL}");
				}
				else {
					//Success 0
					$URL.="&addReturn=success0" ;
					header("Location: {$URL}");
				}
			}
		}
	}
}
?>