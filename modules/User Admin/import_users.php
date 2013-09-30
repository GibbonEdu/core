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

if (isActionAccessible($guid, $connection2, "/modules/User Admin/import_users.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>Sync Users</div>" ;
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
			This page allows you to import user data from a CSV file. The import includes all users, whether they be students, staff, parents or other. The system will take the import and set any existing users not present in the file to "Left", whilst importing new users into the system. New users will be assigned a random password, unless a default is set. Select the CSV file you wish to use for the synchronise operation.<br/>
		</p>
		<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/import_users.php&step=2" ?>" enctype="multipart/form-data">
			<table cellspacing='0' style="width: 100%">	
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
					<td> 
						<b>Default Password</b><br/>
						<span style="font-size: 90%"><i>If not set, random passwords will be used.</i></span>
					</td>
					<td class="right">
						<input type="text" style="width: 300px" name="defaultPassword" value='' maxlength=20>
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
			<li>Your import should only include those users whose status is set "Full" (e.g. current users).</li>
			<li>The submitted file must have the following fields in the following order (* denotes required field): 
				<ol>
					<li><b>Title</b> - e.g. Mr, Mrs, Dr,</li>
					<li><b>Surname *</b> - family name</li>
					<li><b>First Name *</b> - given name</li>
					<li><b>Other Names</b> - middle names</li>
					<li><b>Preferred Name *</b> - most common name, alias, nickname, handle, etc</li>
					<li><b>Official Name *</b> - full name as shown in ID documents.</li>
					<li><b>Gender *</b> - F or M</li>
					<li><b>Username *</b> - must be unique</li>
					<li><b>House</b> - house short name, as set in School Admin (must already exist)</li>
					<li><b>DOB</b> - date of birth (YYYY-MM-DD)</li>
					<li><b>Role</b> - Teacher, Support Staff, Student or Parent</li>
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
				$sql="LOCK TABLES gibbonPerson WRITE, gibbonHouse WRITE" ;
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
						if ($data[1]!="" AND $data[2]!="" AND $data[4]!="" AND $data[5]!="" AND $data[6]!="" AND $data[7]!="") {
							$users[$userSuccessCount]["title"]=$data[0] ;
							$users[$userSuccessCount]["surname"]=$data[1] ;
							$users[$userSuccessCount]["firstName"]=$data[2] ;
							$users[$userSuccessCount]["otherNames"]=$data[3] ;
							$users[$userSuccessCount]["preferredName"]=$data[4] ;
							$users[$userSuccessCount]["officialName"]=$data[5] ;
							$users[$userSuccessCount]["gender"]=$data[6] ;
							$users[$userSuccessCount]["username"]=$data[7] ;
							$users[$userSuccessCount]["house"]=$data[8] ;
							$users[$userSuccessCount]["dob"]=$data[9] ;
							$users[$userSuccessCount]["role"]=$data[10] ;
							$userSuccessCount++ ;
						}
						else {
							print "<div class='error'>" ;
								print "User with username " . $data[7] . " had some information malformations." ;
							print "</div>" ;
						}
						$userCount++ ;
					}
					fclose($handle);
					if ($userSuccessCount==0) {
						print "<div class='error'>" ;
							print "No useful users were detected in the import file (perhaps they did not meet minimum requirements), so the import will be aborted." ;
						print "</div>" ;
						$proceed=false ;
					}
					else if ($userSuccessCount<$userCount) {
						print "<div class='error'>" ;
							print "Some users could not be successfully read or used, so the import will be aborted." ;
						print "</div>" ;
						$proceed=false ;
					}
					else if ($userSuccessCount==$userCount) {
						print "<div class='success'>" ;
							print "All users could be read and used, so the import will proceed." ;
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
						print "Set To Left" ;
					print "</h4>" ;
					$setLeftFail=FALSE ;
					$usernameWhere="(" ; 
					foreach ($users AS $user) {
						$usernameWhere.="'" . $user["username"] . "'," ;
					}
					$usernameWhere=substr($usernameWhere,0,-1) ; 
					$usernameWhere.=")" ; 
					
					try {
						$data=array(); 
						$sql="UPDATE gibbonPerson SET status='Left' WHERE username NOT IN $usernameWhere AND username <> '" . $_SESSION[$guid]["username"] . "'" ;
						$result=$connection2->prepare($sql);
						$result->execute($data);
					}
					catch(PDOException $e) { 
						$setLeftFail=TRUE ;
					}
					
					if ($setLeftFail==TRUE) {
						print "<div class='error'>" ;
							print "An error was encountered in setting users not in the import to Left" ;
						print "</div>" ;
					}
					else {
						print "<div class='success'>" ;
							print "All users not in the import (except you) have been set to left." ;
						print "</div>" ;
					}
			
					//CHECK USERS IN IMPORT FOR EXISTENCE, IF NOT EXIST, ADD THEM, IF THEY ARE UPDATE THEM
					print "<h4>" ;
						print "Update & Insert" ;
					print "</h4>" ;
					foreach ($users AS $user) {
						$userProceed=TRUE ;
						try {
							$data=array("username"=>$user["username"]); 
							$sql="SELECT * FROM gibbonPerson WHERE username=:username" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							$userProceed=FALSE ;
						}
						
						if ($userProceed==FALSE) {
							print "<div class='error'>" ;
								print "There was an error locating user " . $user["username"] . "." ;
							print "</div>" ;
						}
						else {
							if ($result->rowCount()==1) {
								$row=$result->fetch() ;
								//UPDATE USER
								$updateUserFail=FALSE ;
								$role="" ;
								$roleAll=$row["gibbonRoleIDAll"] ;
								if ($user["role"]=="Student") {
									$role="003" ;
								}
								if ($user["role"]=="Teacher") {
									$role="002" ;
								}
								if ($user["role"]=="Support Staff") {
									$role="006" ;
								}
								if ($user["role"]=="Parent") {
									$role="004" ;
								}
								if (strpos($role, $row["gibbonRoleIDAll"])===0) {
									$roleAll=$row["gibbonRoleIDAll"]. "," . $role ;
								}
								
								try {
									$data=array("title"=>$user["title"], "surname"=>$user["surname"], "firstName"=>$user["firstName"], "otherNames"=>$user["otherNames"], "preferredName"=>$user["preferredName"], "officialName"=>$user["officialName"], "gender"=>$user["gender"], "house"=>$user["house"], "dob"=>$user["dob"], "gibbonRoleIDPrimary"=>$role, "gibbonRoleIDAll"=>$roleAll, "username"=>$user["username"]); 
									$sql="UPDATE gibbonPerson SET title=:title, surname=:surname, firstName=:firstName, otherNames=:otherNames, preferredName=:preferredName, officialName=:officialName, gender=:gender, gibbonHouseID=(SELECT gibbonHouseID FROM gibbonHouse WHERE nameShort=:house), dob=:dob, gibbonRoleIDPrimary=:gibbonRoleIDPrimary, gibbonRoleIDAll=:gibbonRoleIDAll, status='Full' WHERE username=:username" ;
									$result=$connection2->prepare($sql);
									$result->execute($data);
								}
								catch(PDOException $e) { 
									print $e->getMessage() ;
									$updateUserFail=TRUE ;
								}
								
								//Spit out results
								if ($updateUserFail==TRUE) {
									print "<div class='error'>" ;
										print "There was an error updating user " . $user["username"] . "." ;
									print "</div>" ;
								}
								else {
									print "<div class='success'>" ;
										print "User " . $user["username"] . " was successfully updated." ;
									print "</div>" ;
								}
							}
							else if ($result->rowCount()==0) {
								//ADD USER
								$addUserFail=FALSE ;
								$salt=getSalt() ;
								if ($_POST["defaultPassword"]!="") {
									$password=$_POST["defaultPassword"];
									$passwordStrong=hash("sha256", $salt.$password) ;
								}
								else {
									$password=randomPassword(8);
									$passwordStrong=hash("sha256", $salt.$password) ;
								}
								$role="" ;
								if ($user["role"]=="Student") {
									$role="003" ;
								}
								if ($user["role"]=="Teacher") {
									$role="002" ;
								}
								if ($user["role"]=="Support Staff") {
									$role="006" ;
								}
								if ($user["role"]=="Parent") {
									$role="004" ;
								}
								
								if ($role=="") {
									print "<div class='error'>" ;
										print "There was an error with the role of user " . $user["username"] . "." ;
									print "</div>" ;
								}
								else {
									try {
										$data=array("title"=>$user["title"], "surname"=>$user["surname"], "firstName"=>$user["firstName"], "otherNames"=>$user["otherNames"], "preferredName"=>$user["preferredName"], "officialName"=>$user["officialName"], "gender"=>$user["gender"], "house"=>$user["house"], "dob"=>$user["dob"], "username"=>$user["username"], "passwordStrongSalt"=>$salt, "passwordStrong"=>$passwordStrong, "gibbonRoleIDPrimary"=>$role, "gibbonRoleIDAll"=>$role); 
										$sql="INSERT INTO gibbonPerson SET title=:title, surname=:surname, firstName=:firstName, otherNames=:otherNames, preferredName=:preferredName, officialName=:officialName, gender=:gender, gibbonHouseID=(SELECT gibbonHouseID FROM gibbonHouse WHERE nameShort=:house), dob=:dob, status='Full', username=:username, passwordStrongSalt=:passwordStrongSalt, passwordStrong=:passwordStrong, gibbonRoleIDPrimary=:gibbonRoleIDPrimary, gibbonRoleIDAll=:gibbonRoleIDAll, passwordForceReset='Y'" ;
										$result=$connection2->prepare($sql);
										$result->execute($data);
									}
									catch(PDOException $e) { 
										$addUserFail=TRUE ;
										print $e->getMessage() ;
									}
									
									//Spit out results
									if ($addUserFail==TRUE) {
										print "<div class='error'>" ;
											print "There was an error creating user " . $user["username"] . "." ;
										print "</div>" ;
									}
									else {
										print "<div class='success'>" ;
											print "User " . $user["username"] . " was successfully created with password $password." ;
										print "</div>" ;
									}
								}
							}
							else {
								print "<div class='error'>" ;
									print "There was an error locating user " . $user["username"] . "." ;
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