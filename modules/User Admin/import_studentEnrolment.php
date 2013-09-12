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

session_start() ;

if (isActionAccessible($guid, $connection2, "/modules/User Admin/import_studentEnrolment.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>Sync Student Enrolment</div>" ;
	print "</div>" ;
	
	$step=$_GET["step"] ;
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
			Step 1 - Select CSV Files
		</h2>
		<p>
			This page allows you to import student enrolment data from a CSV file. The import includes all current students, giving their school year and roll group. The system will remove all enrolments in the current year, and add those provided in the import file. Select the CSV file you wish to use for the synchronise operation.<br/>
		</p>
		<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/import_studentEnrolment.php&step=2" ?>" enctype="multipart/form-data">
			<table style="width: 100%">	
				<tr><td style="width: 30%"></td><td></td></tr>
				<tr>
					<td> 
						<b>CSV File *</b><br/>
						<span style="font-size: 90%"><i>See Notes below for specification.</i></span>
					</td>
					<td class="right">
						<input type="file" name="file" id="file" size="chars">
						<script type="text/javascript">
							var file = new LiveValidation('file');
							file.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b>Field Delimiter *</b><br/>
						<span style="font-size: 90%"><i>Must be unique for this school year.</i></span>
					</td>
					<td class="right">
						<input type="text" style="width: 300px" name="fieldDelimiter" value="," maxlength=1>
						<script type="text/javascript">
							var fieldDelimiter = new LiveValidation('fieldDelimiter');
							fieldDelimiter.add(Validate.Presence);
						 </script>
					</td>
				</tr>
				<tr>
					<td> 
						<b>String Enclosure *</b><br/>
						<span style="font-size: 90%"><i></i></span>
					</td>
					<td class="right">
						<input type="text" style="width: 300px" name="stringEnclosure" value='"' maxlength=1>
						<script type="text/javascript">
							var stringEnclosure = new LiveValidation('stringEnclosure');
							stringEnclosure.add(Validate.Presence);
						 </script>
					</td>
				</tr>
				<tr>
					<td class="right" colspan=2>
						<input name="gibbonSchoolYearID" id="gibbonSchoolYearID" value="<? print $gibbonSchoolYearID ?>" type="hidden">
						<input type="hidden" name="address" value="<? print $_SESSION[$guid]["address"] ?>">
						<input type="reset" value="Reset"> <input type="submit" value="Submit">
					</td>
				</tr>
				<tr>
					<td class="right" colspan=2>
						<span style="font-size: 90%"><i>* denotes a required field</i></span>
					</td>
				</tr>
			</table>
		</form>
		
		
		
		<h4>
			Notes
		</h4>
		<ol>
			<li style='color: #c00; font-weight: bold'>THE SYSTEM WILL NOT PROMPT YOU TO PROCEED, IT WILL JUST DO THE IMPORT. BACKUP YOUR DATA.</li>
			<li>You may only submit CSV files.</li>
			<li>Imports cannot be run concurrently (e.g. make sure you are the only person importing at any one time).</li>
			<li>Your import should only include all current students.</li>
			<li>The submitted file must have the following fields in the following order (all fields are required): 
				<ol>
					<li><b>Username</b> - must be unique</li>
					<li><b>Roll Group</b> - roll group short name, as set in School Admin (must already exist)</li>
					<li><b>Year Group</b> - year group short name, as set in School Admin (must already exist).</li>
				</ol>
			</li>
			<li>Do not include a header row in the CSV files.</li>
		</ol>
	<?
	}
	else if ($step==2) {
		?>
		<h2>
			Step 2 - Data Check & Confirm
		</h2>
		<?
		
		//Check file type
		if (($_FILES['file']['type']!="text/csv") AND ($_FILES['file']['type']!="text/comma-separated-values") AND ($_FILES['file']['type']!="text/x-comma-separated-values") AND ($_FILES['file']['type']!="application/vnd.ms-excel")) {
			?>
			<div class='error'>
				Import cannot proceed, as the submitted file has a MIME-TYPE of "<? print $_FILES['file']['type'] ?>", and as such does not appear to be a CSV file.<br/>
			</div>
			<?
		}
		else if (($_POST["fieldDelimiter"]=="") OR ($_POST["stringEnclosure"]=="")) {
			?>
			<div class='error'>
				Import cannot proceed, as the "Field Delimiter" and/or "String Enclosure" fields have been left blank.<br/>
			</div>
			<?
		}
		else {
			$proceed=true ;
			
			//PREPARE TABLES
			print "<h4>" ;
				print "Prepare Database Tables" ;
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
					print "The database could not be locked for use." ;
				print "</div>" ;	
			}
			else if ($lockFail==false) {
				print "<div class='success'>" ;
					print "The database was successfully locked." ;
				print "</div>" ;	
			}	
			
			if ($lockFail==FALSE) {	
				//READ IN DATA
				if ($proceed==true) {
					print "<h4>" ;
						print "File Import" ;
					print "</h4>" ;
					$importFail=false ;
					$csvFile=$_FILES['file']['tmp_name'] ;
					$handle = fopen($csvFile, "r");
					$users=array() ;
					$userCount=0 ;
					$userSuccessCount=0 ;
					while (($data = fgetcsv($handle, 100000, stripslashes($_POST["fieldDelimiter"]), stripslashes($_POST["stringEnclosure"]))) !== FALSE) {
						if ($data[0]!="" AND $data[1]!="" AND $data[2]!="") {
							$users[$userSuccessCount]["username"]=$data[0] ;
							$users[$userSuccessCount]["rollGroup"]=$data[1] ;
							$users[$userSuccessCount]["yearGroup"]=$data[2] ;
							$userSuccessCount++ ;
						}
						else {
							print "<div class='error'>" ;
								print "Student with username " . $data[7] . " had some information malformations." ;
							print "</div>" ;
						}
						$userCount++ ;
					}
					fclose($handle);
					if ($userSuccessCount==0) {
						print "<div class='error'>" ;
							print "No useful students were detected in the import file (perhaps they did not meet minimum requirements), so the import will be aborted." ;
						print "</div>" ;
						$proceed=false ;
					}
					else if ($userSuccessCount<$userCount) {
						print "<div class='error'>" ;
							print "Some students could not be successfully read or used, so the import will be aborted." ;
						print "</div>" ;
						$proceed=false ;
					}
					else if ($userSuccessCount==$userCount) {
						print "<div class='success'>" ;
							print "All students could be read and used, so the import will proceed." ;
						print "</div>" ;
					}
					else {
						print "<div class='error'>" ;
							print "An unknown error occured, so the import will be aborted.." ;
						print "</div>" ;
						$proceed=false ;
					}
				}
				
				
				if ($proceed==TRUE) {
					//SET USERS NOT IN IMPORT TO LEFT
					print "<h4>" ;
						print "Delete All Enrolments" ;
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
							print "An error was encountered in deleting all enrolments." ;
						print "</div>" ;
					}
					else {
						print "<div class='success'>" ;
							print "All enrolments were deleted." ;
						print "</div>" ;
					}
					
					if ($deleteAllFail==FALSE) {
						print "<h4>" ;
							print "Enrol All Students" ;
						print "</h4>" ;
						foreach ($users AS $user) {
							$addUserFail=FALSE ;
							try {
								$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "username"=>$user["username"], "rollGroup"=>$user["rollGroup"], "yearGroup"=>$user["yearGroup"]); 
								$sql="INSERT INTO gibbonStudentEnrolment SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonPersonID=(SELECT gibbonPersonID FROM gibbonPerson WHERE username=:username), gibbonRollGroupID=(SELECT gibbonRollGroupID FROM gibbonRollGroup WHERE nameShort=:rollGroup), gibbonYearGroupID=(SELECT gibbonYearGroupID FROM gibbonYearGroup WHERE nameShort=:yearGroup)" ;
								$result=$connection2->prepare($sql);
								$result->execute($data);
							}
							catch(PDOException $e) { 
								$addUserFail=TRUE ;
							}
							
							//Spit out results
							if ($addUserFail==TRUE) {
								print "<div class='error'>" ;
									print "There was an error enroling student: " . $user["username"] . "." ;
								print "</div>" ;
							}
							else {
								print "<div class='success'>" ;
									print "User " . $user["username"] . " was successfully enroled." ;
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
?>