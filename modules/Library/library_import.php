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

if (isActionAccessible($guid, $connection2, "/modules/Library/library_import.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Import Records') . "</div>" ;
	print "</div>" ;
	
	$step=NULL ;
	if (isset($_GET["step"])) {
		$step=$_GET["step"] ;
	}
	if ($step=="") {
		$step=1 ;
	}
	else if (($step!=1) AND ($step!=2)) {
		$step=1 ;
	}
	
	//STEP 1, SELECT TERM
	if ($step==1) {
		?>
		<h2>
			<?php print __($guid, 'Step 1 - Select CSV Files') ?>
		</h2>
		<p>
			<?php print __($guid, 'This page allows you to import library records from a CSV file. The import includes one row for each record. The system will match records by ID, updating any matching results, whilst creating new records not already existing in the system.') ?><br/>
		</p>
		<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/library_import.php&step=2" ?>" enctype="multipart/form-data">
			<table class='smallIntBorder fullWidth' cellspacing='0'>	
				<tr>
					<td style='width: 275px'> 
						<b><?php print __($guid, 'CSV File') ?> *</b><br/>
						<span class="emphasis small"><?php print __($guid, 'See Notes below for specification.') ?></span>
					</td>
					<td class="right">
						<input type="file" name="file" id="file" size="chars">
						<script type="text/javascript">
							var file=new LiveValidation('file');
							file.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print __($guid, 'Field Delimiter') ?> *</b><br/>
					</td>
					<td class="right">
						<input type="text" class="standardWidth" name="fieldDelimiter" value="," maxlength=1>
						<script type="text/javascript">
							var fieldDelimiter=new LiveValidation('fieldDelimiter');
							fieldDelimiter.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print __($guid, 'String Enclosure') ?> *</b><br/>
						<span class="emphasis small"></span>
					</td>
					<td class="right">
						<input type="text" class="standardWidth" name="stringEnclosure" value='"' maxlength=1>
						<script type="text/javascript">
							var stringEnclosure=new LiveValidation('stringEnclosure');
							stringEnclosure.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td>
						<span class="emphasis small">* <?php print __($guid, "denotes a required field") ; ?></span>
					</td>
					<td class="right">
						<input name="gibbonSchoolYearID" id="gibbonSchoolYearID" value="<?php print $gibbonSchoolYearID ?>" type="hidden">
						<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
						<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
					</td>
				</tr>
			</table>
		</form>
		
		
		
		<h4>
			<?php print __($guid, 'Notes') ?>
		</h4>
		<ol>
			<li style='color: #c00; font-weight: bold'><?php print __($guid, 'THE SYSTEM WILL NOT PROMPT YOU TO PROCEED, IT WILL JUST DO THE IMPORT. BACKUP YOUR DATA.') ?></li>
			<li><?php print __($guid, 'You may only submit CSV files.') ?></li>
			<li><?php print __($guid, 'Imports cannot be run concurrently (e.g. make sure you are the only person importing at any one time).') ?></li>
			<li><?php print __($guid, 'Imports can only be for one Type (e.g. Print Publication, Computer, etc). The type of the first item in the import will be applied to all other entries.') ?></li>
			<li><?php print __($guid, 'The submitted file must have the following fields in the following order (* denotes required field):') ?></li> 
				<ol>
					<li><b><?php print __($guid, 'General Details') ; ?></b></li>
					<ol>
						<li><b><?php print __($guid, 'Type') ?>* </b> - <?php print __($guid, 'One of:') . " " ;
							try {
								$dataType=array(); 
								$sqlType="SELECT name FROM gibbonLibraryType WHERE active='Y' ORDER BY name" ;
								$resultType=$connection2->prepare($sqlType);
								$resultType->execute($dataType);
							}
							catch(PDOException $e) { }
							$typeCount=1 ;
							while ($rowType=$resultType->fetch()) {
								print "'" . __($guid, $rowType["name"]) . "'" ;
								if ($typeCount<$resultType->rowCount()) {
									print ", " ;
								}
								else {
									print "." ;
								}
								$typeCount++ ;
							}
						?></li>
						<li><b><?php print __($guid, 'Name') ?> *</b> - <?php print __($guid, 'Volume or product name.') ?></li>
						<li><b><?php print __($guid, 'ID') ?> *</b> - <?php print __($guid, 'Must be unique, or will lead to update not insert.') ?></li>
						<li><b><?php print __($guid, 'Author/Brand') ?> *</b> - <?php print __($guid, 'Who created the item?') ?></li>
						<li><b><?php print __($guid, 'Vendor') ?></b> - <?php print __($guid, 'Who supplied the item?') ?></li>
						<li><b><?php print __($guid, 'Purchase Date') ?></b> - <?php print __($guid, 'dd/mm/yyyy') ?></li>
						<li><b><?php print __($guid, 'Invoice Number') ?></b></li>
						<li><b><?php print __($guid, 'Location') ?> *</b> - <?php print __($guid, 'Space \'Name\' field.') ?></li>
						<li><b><?php print __($guid, 'Location Detail') ?></b> - <?php print __($guid, 'Shelf, cabinet, sector, etc') ?></li>
						<li><b><?php print __($guid, 'Ownership Type') ?> *</b> - <?php print __($guid, 'One of: \'School\' or \'Individual\'.') ?></li>
						<li><b><?php print __($guid, 'Main User') . "/" . __($guid, 'Owner') ?></b> - <?php print __($guid, 'Username of person the device is assigned to.') ?></li>
						<li><b><?php print __($guid, 'Department') ?></b> - <?php print __($guid, '\'Name\' filed for department responsible for the item.') ?></li>
						<li><b><?php print __($guid, 'Borrowable?') ?> *</b> - <?php print __($guid, 'Is item available for loan?' . " " . __($guid, 'One of: \'Y\' or \'N\'.')) ?></li>
						<li><b><?php print __($guid, 'Status?') ?> *</b> - <?php print __($guid, 'Initial availability.' . " " . 'One of: \'Available\',\'In Use\',\'Decommissioned\',\'Lost\',\'On Loan\',\'Repair\' or \'Reserved\'.') ?></li>
						<li><b><?php print __($guid, 'Comments/Notes') ?></b></li>
					</ol>
					<li><b><?php print __($guid, 'Type-Specific Details') ; ?></b></li>
						<ol>
							<?php
							try {
								$dataType=array(); 
								$sqlType="SELECT * FROM gibbonLibraryType WHERE active='Y' ORDER BY name" ;
								$resultType=$connection2->prepare($sqlType);
								$resultType->execute($dataType);
							}
							catch(PDOException $e) { }
							while ($rowType=$resultType->fetch()) {
								print "<li><b>" . $rowType["name"] . "</b></li>" ;
								print "<ol>" ;
									$fields=unserialize($rowType["fields"]) ;
									foreach ($fields AS $field) {
										print "<li>" ;
											print "<b>" . $field["name"] ;
											if ($field["required"]=="Y") {
												print " *" ;
											}
											print "</b>" ;
											if ($field["description"]!="") {
												 print " - " . $field["description"] . "</li>" ;
											}
									}
								print "</ol>" ;
							}
							?>
						</ol>
				</ol>
			</li>
			<li><?php print __($guid, 'Do not include a header row in the CSV files.') ?></li>
		</ol>
	<?php
	}
	else if ($step==2) {
		?>
		<h2>
			<?php print __($guid, 'Step 2 - Data Check & Confirm') ?>
		</h2>
		<?php
		
		//Check file type
		if (($_FILES['file']['type']!="text/csv") AND ($_FILES['file']['type']!="text/comma-separated-values") AND ($_FILES['file']['type']!="text/x-comma-separated-values") AND ($_FILES['file']['type']!="application/vnd.ms-excel") AND ($_FILES['file']['type']!="application/csv")) {
			?>
			<div class='error'>
				<?php print sprintf(__($guid, 'Import cannot proceed, as the submitted file has a MIME-TYPE of %1$s, and as such does not appear to be a CSV file.'), $_FILES['file']['type']) ?><br/>
			</div>
			<?php
		}
		else if (($_POST["fieldDelimiter"]=="") OR ($_POST["stringEnclosure"]=="")) {
			?>
			<div class='error'>
				<?php print __($guid, 'Import cannot proceed, as the "Field Delimiter" and/or "String Enclosure" fields have been left blank.') ?><br/>
			</div>
			<?php
		}
		else {
			$proceed=true ;
			
			//PREPARE TABLES
			print "<h4>" ;
				print __($guid, "Prepare Database Tables") ;
			print "</h4>" ;
			//Lock tables
			$lockFail=false ;
			try {
				$sql="LOCK TABLES gibbonLibraryItem WRITE, gibbonLibraryType WRITE, gibbonPerson WRITE, gibbonDepartment WRITE, gibbonSpace WRITE" ;
				$result=$connection2->query($sql);   
			}
			catch(PDOException $e) {
				$lockFail=true ; 
				$proceed=false ;
			}
			if ($lockFail==true) {
				print "<div class='error'>" ;
					print __($guid, "The database could not be locked for use.") ;
				print "</div>" ;	
			}
			else if ($lockFail==false) {
				print "<div class='success'>" ;
					print __($guid, "The database was successfully locked.") ;
				print "</div>" ;	
			}	
			
			if ($lockFail==FALSE) {	
				//READ IN DATA
				if ($proceed==true) {
					print "<h4>" ;
						print __($guid, "File Import") ;
					print "</h4>" ;
					$importFail=false ;
					$csvFile=$_FILES['file']['tmp_name'] ;
					$handle=fopen($csvFile, "r");
					$results=array() ;
					$resultCount=0 ;
					$resultSuccessCount=0 ;
					while (($data=fgetcsv($handle, 100000, stripslashes($_POST["fieldDelimiter"]), stripslashes($_POST["stringEnclosure"]))) !==FALSE) {
						//Turn type into gibbonTypeID (only needs to be done once)
						if ($resultCount==0 AND $data[0]!="") {
							try {
								$dataType=array("name"=>$data[0]); 
								$sqlType="SELECT gibbonLibraryTypeID, fields FROM gibbonLibraryType WHERE name=:name" ;
								$resultType=$connection2->prepare($sqlType);
								$resultType->execute($dataType);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" ;
									print __($guid, 'Your request failed due to a database error.') ;
								print "</div>" ;
								$types=NULL ;
								$typeFields=NULL ;
							}
							if ($resultType->rowCount()!=1) {
								print "<div class='error'>" ;
									print sprintf(__($guid, 'Type with name %1$s in import cannot be found.'), $data[0]) ;
								print "</div>" ;
								$types=NULL ;
								$typeFields=NULL ;
							}
							else {
								$rowType=$resultType->fetch() ;
								$type=$rowType["gibbonLibraryTypeID"] ;
								$typeFields=unserialize($rowType["fields"]) ;
							}
						}
						
						//Get fields
						if ($data[0]!="" AND $data[1]!="" AND $data[2]!="" AND $data[3]!="" AND $data[7]!="" AND $data[9]!="" AND $data[12]!="" AND $data[13]!="" ) {
							//General fields
							$results[$resultSuccessCount]["type"]="" ; if (isset($data[0])) { $results[$resultSuccessCount]["type"]=$data[0] ; }
							$results[$resultSuccessCount]["name"]="" ; if (isset($data[1])) { $results[$resultSuccessCount]["name"]=$data[1] ; }
							$results[$resultSuccessCount]["id"]="" ; if (isset($data[2])) { $results[$resultSuccessCount]["id"]=$data[2] ; }
							$results[$resultSuccessCount]["producer"]="" ; if (isset($data[3])) { $results[$resultSuccessCount]["producer"]=$data[3] ; }
							$results[$resultSuccessCount]["vendor"]="" ; if (isset($data[4])) { $results[$resultSuccessCount]["vendor"]=$data[4] ; }
							$results[$resultSuccessCount]["purchaseDate"]="" ; if (isset($data[5])) { $results[$resultSuccessCount]["purchaseDate"]=$data[5] ; }
							$results[$resultSuccessCount]["invoiceNumber"]="" ; if (isset($data[6])) { $results[$resultSuccessCount]["invoiceNumber"]=$data[6] ; }
							$results[$resultSuccessCount]["location"]="" ; if (isset($data[7])) { $results[$resultSuccessCount]["location"]=$data[7] ; }
							$results[$resultSuccessCount]["locationDetail"]="" ; if (isset($data[8])) { $results[$resultSuccessCount]["locationDetail"]=$data[8] ; }
							$results[$resultSuccessCount]["ownershipType"]="" ; if (isset($data[9])) { $results[$resultSuccessCount]["ownershipType"]=$data[9] ; }
							$results[$resultSuccessCount]["username"]="" ; if (isset($data[10])) { $results[$resultSuccessCount]["username"]=$data[10] ; }
							$results[$resultSuccessCount]["department"]="" ; if (isset($data[11])) { $results[$resultSuccessCount]["department"]=$data[11] ; }
							$results[$resultSuccessCount]["borrowable"]="" ; if (isset($data[12])) { $results[$resultSuccessCount]["borrowable"]=$data[12] ; }
							$results[$resultSuccessCount]["status"]="" ; if (isset($data[13])) { $results[$resultSuccessCount]["status"]=$data[13] ; }
							$results[$resultSuccessCount]["comment"]="" ; if (isset($data[14])) { $results[$resultSuccessCount]["comment"]=$data[14] ; }
							
							//Type specific fields
							$results[$resultSuccessCount]["fields"]="" ;
							$typeFieldValues=array() ;
							$totalFieldCount=15 ;
							if (is_array($typeFields)) {
								foreach ($typeFields AS $typeField) {
									if (isset($data[$totalFieldCount])) { 
										$typeFieldValues[$typeField["name"]]=$data[$totalFieldCount] ; 
									}
									$totalFieldCount++ ;
								}
							}
							if (count($typeFieldValues)>0) {
								$results[$resultSuccessCount]["fields"]=serialize($typeFieldValues) ;
							}
							
							$resultSuccessCount++ ;
						}
						else {
							print "<div class='error'>" ;
								print sprintf(__($guid, 'Record with ID %1$s had some information malformations.'), $data[2]) ;
							print "</div>" ;
						}
						$resultCount++ ;
					}
					fclose($handle);
					if ($resultSuccessCount==0) {
						print "<div class='error'>" ;
							print __($guid, "No useful results were detected in the import file (perhaps they did not meet minimum requirements), so the import will be aborted.") ;
						print "</div>" ;
						$proceed=false ;
					}
					else if ($resultSuccessCount<$resultCount) {
						print "<div class='error'>" ;
							print __($guid, "Some results could not be successfully read or used, so the import will be aborted.") ;
						print "</div>" ;
						$proceed=false ;
					}
					else if ($resultSuccessCount==$resultCount) {
						print "<div class='success'>" ;
							print __($guid, "All results could be read and used, so the import will proceed.") ;
						print "</div>" ;
					}
					else {
						print "<div class='error'>" ;
							print __($guid, "An unknown error occured, so the import will be aborted.") ;
						print "</div>" ;
						$proceed=false ;
					}
				}
				
				if ($proceed==TRUE) {
					print "<h4>" ;
						print __($guid, "Results") ;
					print "</h4>" ;
					
					$locations=array() ;
					$users=array() ;
					$departments=array() ;
					
					//Scroll through all records
					foreach ($results AS $result) {
						//If we have gibbonLibraryTypeID, move on
						if ($type!="" AND is_array($typeFields)) {
							//Turn location into gibbonSpaceID in db-efficient manner
							if (isset($locations[$result["location"]])==FALSE) {
								try {
									$dataLocation=array("name"=>$result["location"]); 
									$sqlLocation="SELECT gibbonSpaceID FROM gibbonSpace WHERE name=:name" ;
									$resultLocation=$connection2->prepare($sqlLocation);
									$resultLocation->execute($dataLocation);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" ;
										print __($guid, 'Your request failed due to a database error.') ;
									print "</div>" ;
									$locations[$result["location"]]=NULL ;
								}
								if ($resultLocation->rowCount()!=1) {
									print "<div class='error'>" ;
										print sprintf(__($guid, 'Location with name %1$s in import cannot be found.'), $result["location"]) ;
									print "</div>" ;
									$locations[$result["location"]]=NULL ;
								}
								else {
									$rowLocation=$resultLocation->fetch() ;
									$locations[$result["location"]]=$rowLocation["gibbonSpaceID"] ;
								}
							}
							
							//If we have gibbonSpaceID, move on
							if ($locations[$result["location"]]!="") {
								//Get users, but they are not compulsorary
								if ($result["username"]!="") {
									if (isset($users[$result["username"]])==FALSE) {
										try {
											$dataUser=array("username"=>$result["username"]); 
											$sqlUser="SELECT gibbonPersonID FROM gibbonPerson WHERE username=:username" ;
											$resultUser=$connection2->prepare($sqlUser);
											$resultUser->execute($dataUser);
										}
										catch(PDOException $e) { 
											print "<div class='error'>" ;
												print __($guid, 'Your request failed due to a database error.') ;
											print "</div>" ;
											$users[$result["username"]]=NULL ;
										}
										if ($resultUser->rowCount()!=1) {
											print "<div class='error'>" ;
												print sprintf(__($guid, 'User with username %1$s in import cannot be found.'), $result["username"]) ;
											print "</div>" ;
											$users[$result["username"]]=NULL ;
										}
										else {
											$rowUser=$resultUser->fetch() ;
											$users[$result["username"]]=$rowUser["gibbonPersonID"] ;
										}
									}
								}
								
								//Get departments, but they are not compulsorary
								if ($result["department"]!="") {
									if (isset($users[$result["department"]])==FALSE) {
										try {
											$dataUser=array("name"=>$result["department"]); 
											$sqlUser="SELECT gibbonDepartmentID FROM gibbonDepartment WHERE name=:name" ;
											$resultUser=$connection2->prepare($sqlUser);
											$resultUser->execute($dataUser);
										}
										catch(PDOException $e) { 
											print "<div class='error'>" ;
												print __($guid, 'Your request failed due to a database error.') ;
											print "</div>" ;
											$departments[$result["department"]]=NULL ;
										}
										if ($resultUser->rowCount()!=1) {
											print "<div class='error'>" ;
												print sprintf(__($guid, 'Department with name %1$s in import cannot be found.'), $result["department"]) ;
											print "</div>" ;
											$departments[$result["department"]]=NULL ;
										}
										else {
											$rowUser=$resultUser->fetch() ;
											$departments[$result["department"]]=$rowUser["gibbonDepartmentID"] ;
										}
									}
								}
									
								//Check if we are OK to go
								if ($type=="" OR $locations[$result["location"]]=="" OR ($result["username"]!="" AND $users[$result["username"]]=="") OR ($result["department"]!="" AND $departments[$result["department"]]=="")) { //NOT OK!
									print "<div class='error'>" ;
										print sprintf(__($guid, 'Record with ID %1$s had some information malformations.'), $data[2]) ;
									print "</div>" ;
								}
								else { //OK!
									//GET FIELDS READY
									$name=$result["name"] ;
									$id=$result["id"] ;
									$producer=$result["producer"] ;
									$vendor=$result["vendor"] ;
									$purchaseDate=NULL ;
									if ($result["purchaseDate"]!="") {
										$purchaseDate=dateConvert($guid, $result["purchaseDate"]) ;
									}
									$invoiceNumber=$result["invoiceNumber"] ;
									$gibbonSpaceID=$locations[$result["location"]];
									$locationDetail=$result["locationDetail"] ;
									$ownershipType=$result["ownershipType"] ;
									$gibbonPersonIDOwnership=NULL ;
									if ($result["username"]!="") {
										$gibbonPersonIDOwnership=$users[$result["username"]] ;
									}
									$gibbonDepartmentID=NULL ;
									if ($result["department"]!="") {
										$gibbonDepartmentID=$departments[$result["department"]] ;
									}
									$borrowable=$result["borrowable"] ;
									$status=$result["status"] ;
									$comment=$result["comment"] ;
									$fields=$result["fields"] ;
									
									//CHECK IF ID EXISTS
									try {
										$dataCheck=array("id"=>$id, ); 
										$sqlCheck="SELECT * FROM gibbonLibraryItem WHERE id=:id" ;
										$resultCheck=$connection2->prepare($sqlCheck);
										$resultCheck->execute($dataCheck);
									}
									catch(PDOException $e) { 
										print "<div class='error'>" ;
											print __($guid, 'Your request failed due to a database error.') ;
										print "</div>" ;
									}
									if ($resultCheck->rowCount()==1) { //IF IT DOES, UPDATE
										$updateFail=FALSE ;
										try {
											$dataUpdate=array("gibbonLibraryTypeID"=>$type, "id"=>$id, "name"=>$name, "producer"=>$producer, "vendor"=>$vendor, "purchaseDate"=>$purchaseDate, "invoiceNumber"=>$invoiceNumber, "comment"=>$comment, "gibbonSpaceID"=>$gibbonSpaceID, "locationDetail"=>$locationDetail, "ownershipType"=>$ownershipType, "gibbonPersonIDOwnership"=>$gibbonPersonIDOwnership, "gibbonDepartmentID"=>$gibbonDepartmentID, "borrowable"=>$borrowable, "status"=>$status, "gibbonPersonIDCreator"=>$_SESSION[$guid]["gibbonPersonID"], "timestampCreator"=>date('Y-m-d H:i:s', time()), "fields"=>$fields); 
											$sqlUpdate="UPDATE gibbonLibraryItem SET gibbonLibraryTypeID=:gibbonLibraryTypeID, name=:name, producer=:producer, vendor=:vendor, purchaseDate=:purchaseDate, invoiceNumber=:invoiceNumber, comment=:comment, gibbonSpaceID=:gibbonSpaceID, locationDetail=:locationDetail, ownershipType=:ownershipType, gibbonPersonIDOwnership=:gibbonPersonIDOwnership, gibbonDepartmentID=:gibbonDepartmentID, borrowable=:borrowable, status=:status, gibbonPersonIDCreator=:gibbonPersonIDCreator, timestampCreator=:timestampCreator, fields=:fields WHERE id=:id" ;
											$resultUpdate=$connection2->prepare($sqlUpdate);
											$resultUpdate->execute($dataUpdate);
										}
										catch(PDOException $e) { 
											print "<div class='error'>" ;
												print $e->getMEssage() ;
												print __($guid, 'Your request failed due to a database error.') ;
											print "</div>" ;
											$updateFail=TRUE ;
										}
										if ($updateFail==FALSE) {
											print "<div class='success'>" ;
												print sprintf(__($guid, '%1$s was successfully updated.'), $result["id"]) ;
											print "</div>" ;
										}
										
									}
									else { //IF IT DOES NOT, INSERT
										$insertFail=FALSE ;
										try {
											$dataInsert=array("gibbonLibraryTypeID"=>$type, "id"=>$id, "name"=>$name, "producer"=>$producer, "vendor"=>$vendor, "purchaseDate"=>$purchaseDate, "invoiceNumber"=>$invoiceNumber, "comment"=>$comment, "gibbonSpaceID"=>$gibbonSpaceID, "locationDetail"=>$locationDetail, "ownershipType"=>$ownershipType, "gibbonPersonIDOwnership"=>$gibbonPersonIDOwnership, "gibbonDepartmentID"=>$gibbonDepartmentID, "borrowable"=>$borrowable, "status"=>$status, "gibbonPersonIDCreator"=>$_SESSION[$guid]["gibbonPersonID"], "timestampCreator"=>date('Y-m-d H:i:s', time()), "fields"=>$fields); 
											$sqlInsert="INSERT INTO gibbonLibraryItem SET gibbonLibraryTypeID=:gibbonLibraryTypeID, id=:id, name=:name, producer=:producer, vendor=:vendor, purchaseDate=:purchaseDate, invoiceNumber=:invoiceNumber, comment=:comment, gibbonSpaceID=:gibbonSpaceID, locationDetail=:locationDetail, ownershipType=:ownershipType, gibbonPersonIDOwnership=:gibbonPersonIDOwnership, gibbonDepartmentID=:gibbonDepartmentID, borrowable=:borrowable, status=:status, gibbonPersonIDCreator=:gibbonPersonIDCreator, timestampCreator=:timestampCreator, fields=:fields" ;
											$resultInsert=$connection2->prepare($sqlInsert);
											$resultInsert->execute($dataInsert);
										}
										catch(PDOException $e) { 
											print "<div class='error'>" ;
												print $e->getMEssage() ;
												print __($guid, 'Your request failed due to a database error.') ;
											print "</div>" ;
											$insertFail=TRUE ;
										}
										if ($insertFail==FALSE) {
											print "<div class='success'>" ;
												print sprintf(__($guid, '%1$s was successfully inserted into the system.'), $result["id"]) ;
											print "</div>" ;
										}
									}	
								}
							}	
						}			
					}			
				}
				
				//UNLOCK TABLES
				try {
					$sql="UNLOCK TABLES" ;
					$result=$connection2->query($sql);   
				}
				catch(PDOException $e) { }	
			}			
		}
	}
}
?>