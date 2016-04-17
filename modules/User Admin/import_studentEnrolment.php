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

if (isActionAccessible($guid, $connection2, "/modules/User Admin/import_studentEnrolment.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Import Student Enrolment') . "</div>" ;
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
			<?php print __($guid, 'This page allows you to import student enrolment data from a CSV file, in one of two modes: 1) Sync - the import file includes all students. The system will take the import and delete enrolment for any existing students not present in the file, whilst importing new enrolments into the system, or 2) Import - the import file includes only student enrolments you wish to add to the system. Select the CSV file you wish to use for the synchronise operation.') ?><br/>
		</p>
		
		<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/import_studentEnrolment.php&step=2" ?>" enctype="multipart/form-data">
			<table class='smallIntBorder fullWidth' cellspacing='0'>	
				<tr>
					<td> 
						<b>Mode *</b><br/>
						<span class="emphasis small"></span>
					</td>
					<td class="right">
						<select name="mode" id="mode" class="standardWidth">
							<option value="sync"><?php print __($guid, 'Sync') ?></option>
							<option value="import"><?php print __($guid, 'Import') ?></option>
						</select>
					</td>
				</tr>
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
			<li><?php print __($guid, 'Your import should only include all current students.') ?></li>
			<li><?php print __($guid, 'The submitted file must have the following fields in the following order (* denotes required field):') ?></li> 
				<ol>
					<li><b><?php print __($guid, 'Username') ?></b> - <?php print __($guid, 'Must be unique.') ?></li>
					<li><b><?php print __($guid, 'Roll Group') ?></b> - <?php print __($guid, 'Roll group short name, as set in School Admim. Must already exist.') ?></li>
					<li><b><?php print __($guid, 'Year Group') ?></b> - <?php print __($guid, 'Year group short name, as set in School Admin. Must already exist') ?></li>
					<li><b><?php print __($guid, 'Roll Order') ?></b> - <?php print __($guid, 'Must be unique to roll group if set.') ?></li>
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
		else if ($_POST["mode"]!="sync" AND $_POST["mode"]!="import") {
			?>
			<div class='error'>
				<?php print __($guid, 'Import cannot proceed, as the "Mode" field have been left blank.') ?><br/>
			</div>
			<?php
		}
		else {
			$proceed=true ;
			$mode=$_POST["mode"] ;
			
			if ($mode=="sync") { //SYNC			
				//PREPARE TABLES
				print "<h4>" ;
					print __($guid, "Prepare Database Tables") ;
				print "</h4>" ;
				//Lock tables
				$lockFail=false ;
				try {
					$sql="LOCK TABLES gibbonStudentEnrolment WRITE, gibbonRollGroup WRITE, gibbonYearGroup WRITE, gibbonPerson WRITE" ;
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
						$users=array() ;
						$userCount=0 ;
						$userSuccessCount=0 ;
						while (($data=fgetcsv($handle, 100000, stripslashes($_POST["fieldDelimiter"]), stripslashes($_POST["stringEnclosure"]))) !==FALSE) {
							if ($data[0]!="" AND $data[1]!="" AND $data[2]!="") {
								$users[$userSuccessCount]["username"]=$data[0] ;
								$users[$userSuccessCount]["rollGroup"]=$data[1] ;
								$users[$userSuccessCount]["yearGroup"]=$data[2] ;
								$users[$userSuccessCount]["rollOrder"]=$data[3] ;
								if ($data[3]=="" OR is_null($data[3])) {
									$users[$userSuccessCount]["rollOrder"]=NULL ;
								}
								$userSuccessCount++ ;
							}
							else {
								print "<div class='error'>" ;
									print sprintf(__($guid, 'Student with username %1$s had some information malformations.'), $data[7]) ;
								print "</div>" ;
							}
							$userCount++ ;
						}
						fclose($handle);
						if ($userSuccessCount==0) {
							print "<div class='error'>" ;
								print __($guid, "No useful students were detected in the import file (perhaps they did not meet minimum requirements), so the import will be aborted.") ;
							print "</div>" ;
							$proceed=false ;
						}
						else if ($userSuccessCount<$userCount) {
							print "<div class='error'>" ;
								print __($guid, "Some students could not be successfully read or used, so the import will be aborted.") ;
							print "</div>" ;
							$proceed=false ;
						}
						else if ($userSuccessCount==$userCount) {
							print "<div class='success'>" ;
								print __($guid, "All students could be read and used, so the import will proceed.") ;
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
						//SET USERS NOT IN IMPORT TO LEFT
						print "<h4>" ;
							print __($guid, "Delete All Enrolments") ;
						print "</h4>" ;
						$deleteAllFail=FALSE ;
						try {
							$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
							$sql="DELETE FROM gibbonStudentEnrolment WHERE gibbonSchoolYearID=:gibbonSchoolYearID" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							$deleteAllFail=TRUE ;
						}
					
						if ($deleteAllFail==TRUE) {
							print "<div class='error'>" ;
								print __($guid, "An error was encountered in deleting all enrolments.") ;
							print "</div>" ;
						}
						else {
							print "<div class='success'>" ;
								print __($guid, "All enrolments were deleted.") ;
							print "</div>" ;
						}
					
						if ($deleteAllFail==FALSE) {
							print "<h4>" ;
								print __($guid, "Enrol All Students") ;
							print "</h4>" ;
							foreach ($users AS $user) {
								$addUserFail=FALSE ;
								try {
									$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonSchoolYearID2"=>$_SESSION[$guid]["gibbonSchoolYearID"], "username"=>$user["username"], "rollGroup"=>$user["rollGroup"], "yearGroup"=>$user["yearGroup"], "rollOrder"=>$user["rollOrder"]); 
									$sql="INSERT INTO gibbonStudentEnrolment SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonPersonID=(SELECT gibbonPersonID FROM gibbonPerson WHERE username=:username), gibbonRollGroupID=(SELECT gibbonRollGroupID FROM gibbonRollGroup WHERE nameShort=:rollGroup AND gibbonSchoolYearID=:gibbonSchoolYearID2), gibbonYearGroupID=(SELECT gibbonYearGroupID FROM gibbonYearGroup WHERE nameShort=:yearGroup), rollOrder=:rollOrder" ;
									$result=$connection2->prepare($sql);
									$result->execute($data);
								}
								catch(PDOException $e) { 
									$addUserFail=TRUE ;
								}
							
								//Spit out results
								if ($addUserFail==TRUE) {
									print "<div class='error'>" ;
									
										print __($guid, "There was an error enroling student:") . " " . $user["username"] . "." ;
									print "</div>" ;
								}
								else {
									print "<div class='success'>" ;
										print sprintf(__($guid, 'User %1$s was successfully enroled.'), $user["username"]) ;
									print "</div>" ;
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
			else if ($mode=="import") { //IMPORT
				//PREPARE TABLES
				print "<h4>" ;
					print __($guid, "Prepare Database Tables") ;
				print "</h4>" ;
				//Lock tables
				$lockFail=false ;
				try {
					$sql="LOCK TABLES gibbonStudentEnrolment WRITE, gibbonRollGroup WRITE, gibbonYearGroup WRITE, gibbonPerson WRITE" ;
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
						$users=array() ;
						$userCount=0 ;
						$userSuccessCount=0 ;
						while (($data=fgetcsv($handle, 100000, stripslashes($_POST["fieldDelimiter"]), stripslashes($_POST["stringEnclosure"]))) !==FALSE) {
							if ($data[0]!="" AND $data[1]!="" AND $data[2]!="") {
								$users[$userSuccessCount]["username"]=$data[0] ;
								$users[$userSuccessCount]["rollGroup"]=$data[1] ;
								$users[$userSuccessCount]["yearGroup"]=$data[2] ;
								$users[$userSuccessCount]["rollOrder"]=$data[3] ;
								if ($data[3]=="" OR is_null($data[3])) {
									$users[$userSuccessCount]["rollOrder"]=NULL ;
								}
								$userSuccessCount++ ;
							}
							else {
								print "<div class='error'>" ;
									print sprintf(__($guid, 'Student with username %1$s had some information malformations.'), $data[7]) ;
								print "</div>" ;
							}
							$userCount++ ;
						}
						fclose($handle);
						if ($userSuccessCount==0) {
							print "<div class='error'>" ;
								print __($guid, "No useful students were detected in the import file (perhaps they did not meet minimum requirements), so the import will be aborted.") ;
							print "</div>" ;
							$proceed=false ;
						}
						else if ($userSuccessCount<$userCount) {
							print "<div class='error'>" ;
								print __($guid, "Some students could not be successfully read or used, so the import will be aborted.") ;
							print "</div>" ;
							$proceed=false ;
						}
						else if ($userSuccessCount==$userCount) {
							print "<div class='success'>" ;
								print __($guid, "All students could be read and used, so the import will proceed.") ;
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
							print __($guid, "Enrol All Students") ;
						print "</h4>" ;
						foreach ($users AS $user) {
							$addUserFail=FALSE ;
							//Check for existing enrolment
							try {
								$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "username"=>$user["username"]); 
								$sql="SELECT * FROM gibbonStudentEnrolment WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonID=(SELECT gibbonPersonID FROM gibbonPerson WHERE username=:username)" ;
								$result=$connection2->prepare($sql);
								$result->execute($data);
							}
							catch(PDOException $e) { 
								$addUserFail=TRUE ;
							}
							
							if ($result->rowCount()>0) {
								$addUserFail=TRUE ;
								print "<div class='error'>" ;
									print __($guid, "There was an error enroling student:") . " " . $user["username"] . "." ;
								print "</div>" ;
							}
							else {
								try {
									$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonSchoolYearID2"=>$_SESSION[$guid]["gibbonSchoolYearID"], "username"=>$user["username"], "rollGroup"=>$user["rollGroup"], "yearGroup"=>$user["yearGroup"], "rollOrder"=>$user["rollOrder"]); 
									$sql="INSERT INTO gibbonStudentEnrolment SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonPersonID=(SELECT gibbonPersonID FROM gibbonPerson WHERE username=:username), gibbonRollGroupID=(SELECT gibbonRollGroupID FROM gibbonRollGroup WHERE nameShort=:rollGroup AND gibbonSchoolYearID=:gibbonSchoolYearID2), gibbonYearGroupID=(SELECT gibbonYearGroupID FROM gibbonYearGroup WHERE nameShort=:yearGroup), rollOrder=:rollOrder" ;
									$result=$connection2->prepare($sql);
									$result->execute($data);
								}
								catch(PDOException $e) { 
									print $e->getMessage() ;
									$addUserFail=TRUE ;
								}
						
								//Spit out results
								if ($addUserFail==TRUE) {
									print "<div class='error'>" ;
										print __($guid, "There was an error enroling student:") . " " . $user["username"] . "." ;
									print "</div>" ;
								}
								else {
									print "<div class='success'>" ;
										print sprintf(__($guid, 'User %1$s was successfully enroled.'), $user["username"]) ;
									print "</div>" ;
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
}
?>