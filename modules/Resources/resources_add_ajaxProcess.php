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

if (isActionAccessible($guid, $connection2, "/modules/Resources/resources_manage_add.php")==FALSE) {
	//Fail 0
	print "<span style='font-weight: bold; color: #ff0000'>" ;
		print "Add failed because you do not have access to this action." ;
	print "</span>" ;
}
else {
	if (empty($_POST)) {
		//Fail 5
		print "<span style='font-weight: bold; color: #ff0000'>" ;
			print "Add failed due to an attachment error." ;
		print "</span>" ;
	}
	else {
		//Proceed!
		$id=$_POST["id"] ;
		$type=$_POST[$id . "type"] ; 
		if ($type=="File") {
			$content=$_FILES[$id . 'file'] ;
		}
		else if ($type=="Link") {
			$content=$_POST[$id . 'link'] ;
		}
		$name=$_POST[$id . "name"] ; 
		$category=$_POST[$id . "category"] ; 
		$purpose=$_POST[$id . "purpose"] ; 
		$tags=strtolower($_POST[$id . "tags"]) ;
		$gibbonYearGroupIDList="" ;
		for ($i=0; $i<$_POST[$id . "count"]; $i++) {
			if (isset($_POST[$id . "gibbonYearGroupIDCheck$i"])) {
				if ($_POST[$id . "gibbonYearGroupIDCheck$i"]=="on") {
					$gibbonYearGroupIDList=$gibbonYearGroupIDList . $_POST[$id . "gibbonYearGroupID$i"] . "," ;
				}
			}
		}
		$gibbonYearGroupIDList=substr($gibbonYearGroupIDList,0,(strlen($gibbonYearGroupIDList)-1)) ;
		$description=$_POST[$id . "description"] ; 
			
		if (($type!="File" AND $type!="Link") OR is_null($content) OR $name=="" OR $category=="" OR $tags=="" OR $id=="") {
			//Fail 3
			print "<span style='font-weight: bold; color: #ff0000'>" ;
				print "Add failed because your inputs were invalid." ;
			print "</span>" ;
		}
		else {
			if ($type=="File") {
				if ($_FILES[$id . 'file']["tmp_name"]!="") {
					//Check for folder in uploads based on today's date
					$path=$_SESSION[$guid]["absolutePath"] ; ;
					if (is_dir($path ."/uploads/" . date("Y", $time) . "/" . date("m", $time))==FALSE) {
						mkdir($path ."/uploads/" . date("Y", $time) . "/" . date("m", $time), 0777, TRUE) ;
					}
					$unique=FALSE;
					while ($unique==FALSE) {
						$suffix=randomPassword(16) ;
						$attachment="uploads/" . date("Y", $time) . "/" . date("m", $time) . "/" . preg_replace("/[^a-zA-Z0-9]/", "", $name) . "_$suffix" . strrchr($_FILES[$id . "file"]["name"], ".") ;
						if (!(file_exists($path . "/" . $attachment))) {
							$unique=TRUE ;
						}
					}
					if (!(move_uploaded_file($_FILES[$id . "file"]["tmp_name"],$path . "/" . $attachment))) {
						//Fail 5
						print "<span style='font-weight: bold; color: #ff0000'>" ;
							print "Add failed due to an attachment error." ;
						print "</span>" ;
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
				print "<span style='font-weight: bold; color: #ff0000'>" ;
					print "Add failed due to a database error." ;
				print "</span>" ;
				break ;
			}		

			//Update tag counts
			$partialFail=FALSE ;
			$tags=explode(",", $_POST[$id . "tags"]) ;
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
				print "<span style='font-weight: bold; color: #ff0000'>" ;
					print "Add failed due to a database error." ;
				print "</span>" ;
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
				print "<span style='font-weight: bold; color: #ff0000'>" ;
					print $e->getMessage() ;
				print "</span>" ;
				break ;
			}
			
			if ($partialFail==TRUE) {
				print "<span style='font-weight: bold; color: #ff0000'>" ;
					print "Some aspects of the update failed." ;
				print "</span>" ;
			}
			else {
				//Success 0
				$html="" ;
				$extension="" ;
				if ($type=="Link") {
					$extension=strrchr($content, ".") ;
					if (strcasecmp($extension, ".gif")==0 OR strcasecmp($extension, ".jpg")==0 OR strcasecmp($extension, ".jpeg")==0 OR strcasecmp($extension, ".png")==0) {
						$html="<a target='_blank' style='font-weight: bold' href='" . $content . "'><img class='resource' style='max-width: 500px' src='" . $content . "'></a>" ;
					}
					else {
						$html="<a target='_blank' style='font-weight: bold' href='" . $content . "'>" . $name . "</a>" ;
					}
				}
				else if ($type=="File") {
					$extension=strrchr($content, ".") ;
					if (strcasecmp($extension, ".gif")==0 OR strcasecmp($extension, ".jpg")==0 OR strcasecmp($extension, ".jpeg")==0 OR strcasecmp($extension, ".png")==0) {
						$html="<a target='_blank' style='font-weight: bold' href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $content . "'><img class='resource' style='max-width: 500px' src='" . $_SESSION[$guid]["absoluteURL"] . "/" . $content . "'></a>" ;
					}
					else {
						$html="<a target='_blank' style='font-weight: bold' href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $content . "'>" . $name . "</a>" ;
					}
				}
				print $html ;
			}
		}
	}
}
?>