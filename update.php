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

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>
			Gibbon Database Updater
		</title>
		<meta charset="utf-8"/>
		<meta name="author" content="Ross Parker, International College Hong Kong"/>
		<meta name="robots" content="none"/>
		
		<link rel="shortcut icon" type="image/x-icon" href="./favicon.ico"/>
		<link rel='stylesheet' type='text/css' href='./themes/Default/css/main.css' />
	</head>
	<body>
		<?php
		include "./functions.php" ;
		include "./config.php" ;
		include "./version.php" ;

		$partialFail=FALSE ;
		
		//New PDO DB connection
		$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

		@session_start() ;

		$cuttingEdgeCode=getSettingByScope( $connection2, "System", "cuttingEdgeCode" ) ;
		if ($cuttingEdgeCode!="Y") {
			$type="regularRelease" ;
		}
		else {
			$type="cuttingEdge" ;
		}

		if ($type!="regularRelease" AND $type!="cuttingEdge") {
			print "<div class='error'>" ;
				print __($guid, "Your request failed because your inputs were invalid.") ;
			print "</div>" ;
		}
		else if ($type=="regularRelease") { //Do regular release update
			$versionDB=getSettingByScope( $connection2, "System", "version" ) ;
			$versionCode=$version ;

			//Validate Inputs
			if ($versionDB=="" OR $versionCode=="" OR version_compare($versionDB, $versionCode)!=-1) {
				print "<div class='error'>" ;
					print __($guid, "Your request failed because your inputs were invalid, or no update was required.") ;
				print "</div>" ;
			}
			else {	
				include "./CHANGEDB.php" ;

				foreach ($sql AS $version) {
					if (version_compare($version[0], $versionDB, ">") AND version_compare($version[0], $versionCode, "<=")) {
						$sqlTokens=explode(";end", $version[1]) ;
						foreach ($sqlTokens AS $sqlToken) {
							if (trim($sqlToken)!="") {
								try {
									$result=$connection2->query($sqlToken);   
								}
								catch(PDOException $e) { 
									$partialFail=TRUE;
								}
							}
						}
					}
				}

				if ($partialFail==TRUE) {
					print "<div class='error'>" ;
						print __($guid, "Some aspects of your update failed.") ;
					print "</div>" ;
				}
				else {
					//Update DB version
					try {
						$data=array("value"=>$versionCode); 
						$sql="UPDATE gibbonSetting SET value=:value WHERE scope='System' AND name='version'" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" ;
							print __($guid, "Some aspects of your update failed.") ;
						print "</div>" ;
						exit ;
					}
	
					print "<div class='success'>" ;
						print __($guid, "Your request was completed successfully.") ;
					print "</div>" ;
				}
			}
		}
		else if ($type=="cuttingEdge") { //Do cutting edge update
			$versionDB=getSettingByScope( $connection2, "System", "version" ) ;
			$versionCode=$version ;
			$cuttingEdgeCodeLine=getSettingByScope( $connection2, "System", "cuttingEdgeCodeLine" ) ;

			include "./CHANGEDB.php" ;
			$versionMax=$sql[(count($sql))][0] ;
			$sqlTokens=explode(";end", $sql[(count($sql))][1]) ;
			$versionMaxLinesMax=(count($sqlTokens)-1) ;	
			$update=FALSE ;
			if (version_compare($versionMax, $versionDB, ">")) {
				$update=TRUE ;
			}
			else {
				if ($versionMaxLinesMax>$cuttingEdgeCodeLine) {
					$update=TRUE ;
				}
			}

			if ($update==FALSE) { //Something went wrong...abandon!
				print "<div class='error'>" ;
					print __($guid, "Some aspects of your update failed.") ;
				print "</div>" ;
				exit ;
			}
			else { //Let's do it
				if (version_compare($versionMax, $versionDB, ">")) { //At least one whole verison needs to be done
					foreach ($sql AS $version) {
						$tokenCount=0 ;		
						if (version_compare($version[0], $versionDB, ">=") AND version_compare($version[0], $versionCode, "<=")) {
							$sqlTokens=explode(";end", $version[1]) ;
							if ($version[0]==$versionDB) { //Finish current version
								foreach ($sqlTokens AS $sqlToken) {
									if ($tokenCount>=$cuttingEdgeCodeLine) {
										if (trim($sqlToken)!="") { //Decide whether this has been run or not
											try {
												$result=$connection2->query($sqlToken);   
											}
											catch(PDOException $e) { 
												$partialFail=TRUE;
											}
										}
									}
									$tokenCount++ ;
								}
							}
							else { //Update intermediate versions and max version
								foreach ($sqlTokens AS $sqlToken) {
									if (trim($sqlToken)!="") { //Decide whether this has been run or not
										try {
											$result=$connection2->query($sqlToken);   
										}
										catch(PDOException $e) { 
											$partialFail=TRUE;
										}
									}
								}
							}
						}
					}
				}
				else { //Less than one whole version
					//Get up to speed in max version
					foreach ($sql AS $version) {
						$tokenCount=0 ;
						if (version_compare($version[0], $versionDB, ">=") AND version_compare($version[0], $versionCode, "<=")) {
							$sqlTokens=explode(";end", $version[1]) ;
							foreach ($sqlTokens AS $sqlToken) {
								if ($tokenCount>=$cuttingEdgeCodeLine) {
									if (trim($sqlToken)!="") { //Decide whether this has been run or not
										try {
											$result=$connection2->query($sqlToken);   
										}
										catch(PDOException $e) { 
											$partialFail=TRUE;
										}
									}
								}
								$tokenCount++ ;
							}
						}
					}
				}
	
				if ($partialFail==TRUE) {
					print "<div class='error'>" ;
						print __($guid, "Some aspects of your update failed.") ;
					print "</div>" ;
				}
				else {
					//Update DB version
					try {
						$data=array("value"=>$versionMax); 
						$sql="UPDATE gibbonSetting SET value=:value WHERE scope='System' AND name='version'" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" ;
							print __($guid, "Some aspects of your update failed.") ;
						print "</div>" ;
						exit ;
					}
		
					//Update DB line count
					try {
						$data=array("value"=>$versionMaxLinesMax); 
						$sql="UPDATE gibbonSetting SET value=:value WHERE scope='System' AND name='cuttingEdgeCodeLine'" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" ;
							print __($guid, "Some aspects of your update failed.") ;
						print "</div>" ;
						exit ;
					}
		
					print "<div class='success'>" ;
						print __($guid, "Your request was completed successfully.") ;
					print "</div>" ;
				}
			}

		}
		?>
	</body>
</html>