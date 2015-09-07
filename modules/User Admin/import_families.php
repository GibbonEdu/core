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
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>". _('Import Families') . "</div>" ;
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
			<?php print _('Step 1 - Select CSV Files') ?>
		</h2>
		<p>
			<?php print _('This page allows you to import family data from a CSV file, and functions as follows: data contained in the CSV files that is new will be added to the system, whereas data that already exists in the system, but has been changed, will be updated.') ?><br/>
		</p>
		
		<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/import_families.php&step=2" ?>" enctype="multipart/form-data">
			<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
				<tr>
					<td style='width: 275px'> 
						<b><?php print _('Family CSV File') ?> *</b><br/>
						<span style="font-size: 90%"><i><?php print _('See Notes below for specification.') ?></i></span>
					</td>
					<td class="right">
						<input type="file" name="fileFamily" id="fileFamily" size="chars">
						<script type="text/javascript">
							var fileFamily=new LiveValidation('fileFamily');
							fileFamily.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print _('Parent CSV File') ?> *</b><br/>
						<span style="font-size: 90%"><i><?php print _('See Notes below for specification.') ?></i></span>
					</td>
					<td class="right">
						<input type="file" name="fileParent" id="fileParent" size="chars">
						<script type="text/javascript">
							var fileParent=new LiveValidation('fileParent');
							fileParent.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print _('Child CSV File') ?> *</b><br/>
						<span style="font-size: 90%"><i><?php print _('See Notes below for specification.') ?></i></span>
					</td>
					<td class="right">
						<input type="file" name="fileChild" id="fileChild" size="chars">
						<script type="text/javascript">
							var fileChild=new LiveValidation('fileChild');
							fileChild.add(Validate.Presence);
						</script>
					</td>
				</tr>
				
				<tr>
					<td> 
						<b><?php print _('Field Delimiter') ?> *</b><br/>
					</td>
					<td class="right">
						<input type="text" style="width: 300px" name="fieldDelimiter" value="," maxlength=1>
						<script type="text/javascript">
							var fieldDelimiter=new LiveValidation('fieldDelimiter');
							fieldDelimiter.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td> 
						<b><?php print _('String Enclosure') ?> *</b><br/>
						<span style="font-size: 90%"><i></i></span>
					</td>
					<td class="right">
						<input type="text" style="width: 300px" name="stringEnclosure" value='"' maxlength=1>
						<script type="text/javascript">
							var stringEnclosure=new LiveValidation('stringEnclosure');
							stringEnclosure.add(Validate.Presence);
						</script>
					</td>
				</tr>
				<tr>
					<td>
						<span style="font-size: 90%"><i>* <?php print _("denotes a required field") ; ?></i></span>
					</td>
					<td class="right">
						<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
						<input type="submit" value="<?php print _("Submit") ; ?>">
					</td>
				</tr>
			</table>
		</form>
		
		<h4>
			<?php print _('Notes') ?>
		</h4>
		<ol>
			<li style='color: #c00; font-weight: bold'><?php print _('THE SYSTEM WILL NOT PROMPT YOU TO PROCEED, IT WILL JUST DO THE IMPORT. BACKUP YOUR DATA.') ?></li>
			<li><?php print _('You may only submit CSV files.') ?></li>
			<li><?php print _('Imports cannot be run concurrently (e.g. make sure you are the only person importing at any one time).') ?></li>
			<li><?php print _('Your import should only include all current students.') ?></li>
			<li><?php print _('The submitted <b><u>family file</u></b> must have the following fields in the following order (* denotes required field)') ?>: 
				<ol>
					<li><b><?php print _('Family Sync Key') ?> *</b> - <?php print _('Unique ID for family, according to source system.') ?></li>
					<li><b><?php print _('Name') ?> *</b> - <?php print _('Name by which family is known.') ?></li>
					<li><b><?php print _('Address Name') ?></b> - <?php print _('Name to appear on written communication to family.') ?></li>
					<li><b><?php print _('Home Address') ?></b> - <?php print _('Unit, Building, Street') ?></li>
					<li><b><?php print _('Home Address (District)') ?></b> - <?php print _('County, State, District') ?></li>
					<li><b><?php print _('Home Address (Country)') ?></b></li>
					<li><b><?php print _('Marital Status') ?></b> - <?php print _('Married, Separated, Divorced, De Facto or Other') ?></li>
					<li><b><?php print _('Home Language') ?></b></li>
				</ol>
			</li>
			<li><?php print _('The submitted <b><u>parent file</u></b> must have the following fields in the following order (* denotes required field):') ?> 
				<ol>
					<li><b><?php print _('Family Sync Key') ?> *</b> - <?php print _('Unique ID for family, according to source system.') ?></li>
					<li><b><?php print _('Username') ?> *</b> - <?php print _('Parent username') ?>.</li>
					<li><b><?php print _('Contact Priority') ?> *</b> - <?php print _('1, 2 or 3 (each family needs one and only one 1).') ?></li>
				</ol>
			</li>
			<li><?php print _('The submitted <b><u>child file</u></b> must have the following fields in the following order (* denotes required field):') ?> 
				<ol>
					<li><b><?php print _('Family Sync Key') ?> *</b> - <?php print _('Unique ID for family, according to source system.') ?></li>
					<li><b><?php print _('Username') ?> *</b> - <?php print _('Child username.') ?></li>
				</ol>
			</li>
			<li><?php print _('Do not include a header row in the CSV files.') ?></li>
		</ol>
	<?php
	}
	else if ($step==2) {
		?>
		<h2>
			<?php print _('Step 2 - Data Check & Confirm') ?>
		</h2>
		<?php
		
		//DEAL WITH FAMILIES
		//Check file type
		if (($_FILES['fileFamily']['type']!="text/csv") AND ($_FILES['fileFamily']['type']!="text/comma-separated-values") AND ($_FILES['fileFamily']['type']!="text/x-comma-separated-values") AND ($_FILES['fileFamily']['type']!="application/vnd.ms-excel")) {
			?>
			<div class='error'>
				<?php print sprintf(_('Import cannot proceed, as the submitted family file has a MIME-TYPE of %1$s, and as such does not appear to be a CSV file.'), $_FILES['fileFamily']['type']) ?><br/>
			</div>
			<?php
		}
		else if (($_FILES['fileParent']['type']!="text/csv") AND ($_FILES['fileParent']['type']!="text/comma-separated-values") AND ($_FILES['fileParent']['type']!="text/x-comma-separated-values") AND ($_FILES['fileParent']['type']!="application/vnd.ms-excel")) {
			?>
			<div class='error'>
				<?php print sprintf(_('Import cannot proceed, as the submitted parent file has a MIME-TYPE of %1$s, and as such does not appear to be a CSV file.'), $_FILES['fileParent']['type']) ?><br/>
			</div>
			<?php
		}
		else if (($_FILES['fileChild']['type']!="text/csv") AND ($_FILES['fileChild']['type']!="text/comma-separated-values") AND ($_FILES['fileChild']['type']!="text/x-comma-separated-values") AND ($_FILES['fileChild']['type']!="application/vnd.ms-excel")) {
			?>
			<div class='error'>
				<?php print sprintf(_('Import cannot proceed, as the submitted parent file has a MIME-TYPE of %1$s, and as such does not appear to be a CSV file.'), $_FILES['fileChild']['type']) ?><br/>
			</div>
			<?php
		}
		else if (($_POST["fieldDelimiter"]=="") OR ($_POST["stringEnclosure"]=="")) {
			?>
			<div class='error'>
				<?php print _('Import cannot proceed, as the "Field Delimiter" and/or "String Enclosure" fields have been left blank.') ?><br/>
			</div>
			<?php
		}
		else {
			$proceed=true ;
			
			//PREPARE TABLES
			print "<h4>" ;
				print _("Prepare Database Tables") ;
			print "</h4>" ;
			//Lock tables
			$lockFail=false ;
			try {
				$sql="LOCK TABLES gibbonFamily WRITE, gibbonFamilyAdult WRITE, gibbonFamilyChild WRITE, gibbonPerson WRITE" ;
				$result=$connection2->query($sql);   
			}
			catch(PDOException $e) {
				$lockFail=true ; 
				$proceed=false ;
			}
			if ($lockFail==true) {
				print "<div class='error'>" ;
					print _("The database could not be locked for use.") ;
				print "</div>" ;	
			}
			else if ($lockFail==false) {
				print "<div class='success'>" ;
					print _("The database was successfully locked.") ;
				print "</div>" ;	
			}	
			
			if ($lockFail==FALSE) {	
				//READ IN DATA
				if ($proceed==true) {
					print "<h4>" ;
						print _("File Import") ;
					print "</h4>" ;
					$importFail=false ;
					
					//Families
					$csvFileFamily=$_FILES['fileFamily']['tmp_name'] ;
					$handle=fopen($csvFileFamily, "r");
					$families=array() ;
					$familyCount=0 ;
					$familySuccessCount=0 ;
					while (($data=fgetcsv($handle, 100000, stripslashes($_POST["fieldDelimiter"]), stripslashes($_POST["stringEnclosure"]))) !==FALSE) {
						if ($data[0]!="" AND $data[1]!="") {
							$families[$familySuccessCount]["familySync"]=$data[0] ;
							$families[$familySuccessCount]["name"]=$data[1] ;
							$families[$familySuccessCount]["nameAddress"]=$data[2] ;
							$families[$familySuccessCount]["homeAddress"]=$data[3] ;
							$families[$familySuccessCount]["homeAddressDistrict"]=$data[4] ;
							$families[$familySuccessCount]["homeAddressCountry"]=$data[5] ;
							$families[$familySuccessCount]["status"]=$data[6] ;
							$families[$familySuccessCount]["languageHome"]=$data[7] ;
							$familySuccessCount++ ;
						}
						else {
							print "<div class='error'>" ;
								print sprintf(_('Family with sync key %1$s had some information malformations.'), $data[0]) ;
							print "</div>" ;
						}
						$familyCount++ ;
					}
					fclose($handle);
					if ($familySuccessCount==0) {
						print "<div class='error'>" ;
							print _("No useful families were detected in the import file (perhaps they did not meet minimum requirements), so the import will be aborted.") ;
						print "</div>" ;
						$proceed=false ;
					}
					else if ($familySuccessCount<$familyCount) {
						print "<div class='error'>" ;
							print _("Some families could not be successfully read or used, so the import will be aborted.") ;
						print "</div>" ;
						$proceed=false ;
					}
					else if ($familySuccessCount==$familyCount) {
						print "<div class='success'>" ;
							print _("All families could be read and used, so the import will proceed.") ;
						print "</div>" ;
					}
					else {
						print "<div class='error'>" ;
							print _("An unknown family error occured, so the import will be aborted.") ;
						print "</div>" ;
						$proceed=false ;
					}
					
					//Parents
					$csvFileParent=$_FILES['fileParent']['tmp_name'] ;
					$handle=fopen($csvFileParent, "r");
					$parents=array() ;
					$parentCount=0 ;
					$parentSuccessCount=0 ;
					while (($data=fgetcsv($handle, 100000, stripslashes($_POST["fieldDelimiter"]), stripslashes($_POST["stringEnclosure"]))) !==FALSE) {
						if ($data[0]!="" AND $data[1]!="" AND $data[2]!="") {
							$parents[$parentSuccessCount]["familySync"]=$data[0] ;
							$parents[$parentSuccessCount]["username"]=$data[1] ;
							$parents[$parentSuccessCount]["contactPriority"]=$data[2] ;
							$parentSuccessCount++ ;
						}
						else {
							print "<div class='error'>" ;
								print sprintf(_('Parent with username %1$s had some information malformations.'), $data[1]) ;
							print "</div>" ;
						}
						$parentCount++ ;
					}
					fclose($handle);
					if ($parentSuccessCount==0) {
						print "<div class='error'>" ;
							print _("No useful parents were detected in the import file (perhaps they did not meet minimum requirements), so the import will be aborted.") ;
						print "</div>" ;
						$proceed=false ;
					}
					else if ($parentSuccessCount<$parentCount) {
						print "<div class='error'>" ;
							print _("Some parents could not be successfully read or used, so the import will be aborted.") ;
						print "</div>" ;
						$proceed=false ;
					}
					else if ($parentSuccessCount==$parentCount) {
						print "<div class='success'>" ;
							print _("All parents could be read and used, so the import will proceed.") ;
						print "</div>" ;
					}
					else {
						print "<div class='error'>" ;
							print _("An unknown parent error occured, so the import will be aborted.") ;
						print "</div>" ;
						$proceed=false ;
					}
					
					
					//Children
					$csvFileChild=$_FILES['fileChild']['tmp_name'] ;
					$handle=fopen($csvFileChild, "r");
					$children=array() ;
					$childCount=0 ;
					$childSuccessCount=0 ;
					while (($data=fgetcsv($handle, 100000, stripslashes($_POST["fieldDelimiter"]), stripslashes($_POST["stringEnclosure"]))) !==FALSE) {
						if ($data[0]!="" AND $data[1]!="") {
							$children[$childSuccessCount]["familySync"]=$data[0] ;
							$children[$childSuccessCount]["username"]=$data[1] ;
							$childSuccessCount++ ;
						}
						else {
							print "<div class='error'>" ;
								print sprintf(_('Child with username %1$s had some information malformations.'), $data[1]) ;
							print "</div>" ;
						}
						$childCount++ ;
					}
					fclose($handle);
					if ($childSuccessCount==0) {
						print "<div class='error'>" ;
							print _("No useful children were detected in the import file (perhaps they did not meet minimum requirements), so the import will be aborted.") ;
						print "</div>" ;
						$proceed=false ;
					}
					else if ($childSuccessCount<$childCount) {
						print "<div class='error'>" ;
							print _("Some children could not be successfully read or used, so the import will be aborted.") ;
						print "</div>" ;
						$proceed=false ;
					}
					else if ($childSuccessCount==$childCount) {
						print "<div class='success'>" ;
							print _("All children could be read and used, so the import will proceed.") ;
						print "</div>" ;
					}
					else {
						print "<div class='error'>" ;
							print _("An unknown error occured, so the import will be aborted.") ;
						print "</div>" ;
						$proceed=false ;
					}
				}
				
				if ($proceed==TRUE) {
					//CHECK FAMILIES IN IMPORT FOR EXISTENCE, IF NOT EXIST, ADD THEM, IF THEY ARE UPDATE THEM
					print "<h4>" ;
						print _("Update & Insert Families") ;
					print "</h4>" ;
					foreach ($families AS $family) {
						$familyProceed=TRUE ;
						try {
							$data=array("familySync"=>$family["familySync"]); 
							$sql="SELECT * FROM gibbonFamily WHERE familySync=:familySync" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							$familyProceed=FALSE ;
						}
						
						if ($familyProceed==FALSE) {
							print "<div class='error'>" ;
								print _("There was an error locating family:") . " " . $family["familySync"] . "." ;
							print "</div>" ;
						}
						else {
							if ($result->rowCount()==1) {
								$row=$result->fetch() ;
								//UPDATE FAMILY
								$updateFamilyFail=FALSE ;
								try {
									$data=array("name"=>$family["name"],  "nameAddress"=>$family["nameAddress"],  "homeAddress"=>$family["homeAddress"],  "homeAddressDistrict"=>$family["homeAddressDistrict"],  "homeAddressCountry"=>$family["homeAddressCountry"],  "status"=>$family["status"],  "languageHome"=>$family["languageHome"], "familySync"=>$family["familySync"]); 
									$sql="UPDATE gibbonFamily SET name=:name, nameAddress=:nameAddress, homeAddress=:homeAddress, homeAddressDistrict=:homeAddressDistrict, homeAddressCountry=:homeAddressCountry, status=:status, languageHome=:languageHome WHERE familySync=:familySync" ;
									$result=$connection2->prepare($sql);
									$result->execute($data);
								}
								catch(PDOException $e) { 
									$updateFamilyFail=TRUE ;
								}
								
								//Spit out results
								if ($updateFamilyFail==TRUE) {
									print "<div class='error'>" ;
										print _("There was an error updating family:") . " " . $family["familySync"] . "." ;
									print "</div>" ;
								}
								else {
									print "<div class='success'>" ;
										print sprintf(_('Family %1$s was successfully updated.'), $family["familySync"]) ;
									print "</div>" ;
								}
							}
							else if ($result->rowCount()==0) {
								//ADD FAMILY
								$addFamilyFail=FALSE ;
								try {
									$data=array("name"=>$family["name"],  "nameAddress"=>$family["nameAddress"],  "homeAddress"=>$family["homeAddress"],  "homeAddressDistrict"=>$family["homeAddressDistrict"],  "homeAddressCountry"=>$family["homeAddressCountry"],  "status"=>$family["status"],  "languageHome"=>$family["languageHome"], "familySync"=>$family["familySync"]); 
									$sql="INSERT INTO gibbonFamily SET name=:name, nameAddress=:nameAddress, homeAddress=:homeAddress, homeAddressDistrict=:homeAddressDistrict, homeAddressCountry=:homeAddressCountry, status=:status, languageHome=:languageHome, familySync=:familySync" ;
									$result=$connection2->prepare($sql);
									$result->execute($data);
								}
								catch(PDOException $e) { 
									$addFamilyFail=TRUE ;
								}
									
								//Spit out results
								if ($addFamilyFail==TRUE) {
									print "<div class='error'>" ;
										print _("There was an error creating family:") ." " . $family["familySync"] . "." ;
									print "</div>" ;
								}
								else {
									print "<div class='success'>" ;
										print sprintf(_('Family %1$s was successfully created.'), $family["familySync"]) ;
									print "</div>" ;
								}
							}
							else {
								print "<div class='error'>" ;
									print _("There was an error locating family:") . " " . $family["familySync"] . "." ;
								print "</div>" ;
							}	
						}
					}
					
					//CHECK PARENTS IN IMPORT FOR EXISTENCE, IF NOT EXIST, ADD THEM, IF THEY ARE UPDATE THEM
					print "<h4>" ;
						print _("Update & Insert Parents") ;
					print "</h4>" ;
					foreach ($parents AS $parent) {
						$familyProceed=TRUE ;
						try {
							$data=array("username"=>$parent["username"], "familySync"=>$parent["familySync"]); 
							$sql="SELECT * FROM gibbonFamilyAdult WHERE gibbonPersonID=(SELECT gibbonPersonID FROM gibbonPerson WHERE username=:username) AND gibbonFamilyID=(SELECT gibbonFamilyID FROM gibbonFamily WHERE familySync=:familySync)" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							$familyProceed=FALSE ;
							print $e->getMessage() ;
						}
						
						if ($familyProceed==FALSE) {
							print "<div class='error'>" ;
								print _("There was an error locating parent:") . " " . $parent["username"] . "." ;
							print "</div>" ;
						}
						else {
							if ($result->rowCount()==1) {
								$row=$result->fetch() ;
								//UPDATE PARENT
								$updateFamilyFail=FALSE ;
								try {
									$data=array("familySync"=>$parent["familySync"], "username"=>$parent["username"], "contactPriority"=>$parent["contactPriority"]); 
									$sql="UPDATE gibbonFamilyAdult SET contactPriority=:contactPriority WHERE gibbonPersonID=(SELECT gibbonPersonID FROM gibbonPerson WHERE username=:username) AND gibbonFamilyID=(SELECT gibbonFamilyID FROM gibbonFamily WHERE familySync=:familySync)" ;
									$result=$connection2->prepare($sql);
									$result->execute($data);
								}
								catch(PDOException $e) { 
									$updateFamilyFail=TRUE ;
								}
								
								//Spit out results
								if ($updateFamilyFail==TRUE) {
									print "<div class='error'>" ;
										print _("There was an error updating parent:") . " " . $parent["username"] . "." ;
									print "</div>" ;
								}
								else {
									print "<div class='success'>" ;
										print sprintf(_('Parent %1$s was successfully updated.'), $parent["username"]) ;
									print "</div>" ;
								}
							}
							else if ($result->rowCount()==0) {
								//ADD PARENT
								$addFamilyFail=FALSE ;
								try {
									$data=array("familySync"=>$parent["familySync"], "username"=>$parent["username"], "contactPriority"=>$parent["contactPriority"]); 
									$sql="INSERT INTO gibbonFamilyAdult SET gibbonPersonID=(SELECT gibbonPersonID FROM gibbonPerson WHERE username=:username), gibbonFamilyID=(SELECT gibbonFamilyID FROM gibbonFamily WHERE familySync=:familySync), contactPriority=:contactPriority, childDataAccess='Y', contactCall='Y', contactSMS='Y', contactEmail='Y', contactMail='Y'" ;
									$result=$connection2->prepare($sql);
									$result->execute($data);
								}
								catch(PDOException $e) { 
									$addFamilyFail=TRUE ;
								}
									
								//Spit out results
								if ($addFamilyFail==TRUE) {
									print "<div class='error'>" ;
										print _("There was an error creating parent:") . " " . $parent["username"] . "." ;
									print "</div>" ;
								}
								else {
									print "<div class='success'>" ;
										print sprintf(_('Family %1$s was successfully created.'), $parent["username"]) ;
									print "</div>" ;
								}
							}
							else {
								print "<div class='error'>" ;
									print _("There was an error locating family:") . " " . $parent["username"] . "." ;
								print "</div>" ;
							}	
						}
					}
					
					//CHECK STUDENTS IN IMPORT FOR EXISTENCE, IF NOT EXIST, ADD THEM, IF THEY ARE UPDATE THEM
					print "<h4>" ;
						print _("Update & Insert Students") ;
					print "</h4>" ;
					foreach ($children AS $child) {
						$familyProceed=TRUE ;
						try {
							$data=array("username"=>$child["username"], "familySync"=>$child["familySync"]); 
							$sql="SELECT * FROM gibbonFamilyChild WHERE gibbonPersonID=(SELECT gibbonPersonID FROM gibbonPerson WHERE username=:username) AND gibbonFamilyID=(SELECT gibbonFamilyID FROM gibbonFamily WHERE familySync=:familySync)" ;
							$result=$connection2->prepare($sql);
							$result->execute($data);
						}
						catch(PDOException $e) { 
							$familyProceed=FALSE ;
							print $e->getMessage() ;
						}
						
						if ($familyProceed==FALSE) {
							print "<div class='error'>" ;
								print _("There was an error locating student:") . " " . $child["username"] . "." ;
							print "</div>" ;
						}
						else {
							if ($result->rowCount()==1) {
								$row=$result->fetch() ;
								//UPDATE STUDENT
								$updateFamilyFail=FALSE ;
								
								//NOTHING TO UPDATE YET, MAY NEED THIS ONE DAY
								/*try {
									$data=array("familySync"=>$child["familySync"], "username"=>$child["username"]); 
									$sql="UPDATE gibbonFamilyAdult SET WHERE gibbonPersonID=(SELECT gibbonPersonID FROM gibbonPerson WHERE username=:username) AND gibbonFamilyID=(SELECT gibbonFamilyID FROM gibbonFamily WHERE familySync=:familySync)" ;
									$result=$connection2->prepare($sql);
									$result->execute($data);
								}
								catch(PDOException $e) { 
									$updateFamilyFail=TRUE ;
								}
								
								//Spit out results
								if ($updateFamilyFail==TRUE) {
									print "<div class='error'>" ;
										print _("There was an error student:") . " " . $child["username"] . "." ;
									print "</div>" ;
								}
								else {
									print "<div class='success'>" ;
										print sprintf(_('Student %1$s was successfully updated.'), $child["username"]) ;
									print "</div>" ;
								}*/
								
								print "<div class='success'>" ;
									print sprintf(_('Student %1$s was successfully updated.'), $child["username"]) ;
								print "</div>" ;
							}
							else if ($result->rowCount()==0) {
								//ADD STUDENT
								$addFamilyFail=FALSE ;
								try {
									$data=array("familySync"=>$child["familySync"], "username"=>$child["username"]); 
									$sql="INSERT INTO gibbonFamilyChild SET gibbonPersonID=(SELECT gibbonPersonID FROM gibbonPerson WHERE username=:username), gibbonFamilyID=(SELECT gibbonFamilyID FROM gibbonFamily WHERE familySync=:familySync)" ;
									$result=$connection2->prepare($sql);
									$result->execute($data);
								}
								catch(PDOException $e) { 
									$addFamilyFail=TRUE ;
								}
									
								//Spit out results
								if ($addFamilyFail==TRUE) {
									print "<div class='error'>" ;
										print _("There was an error creating student:") . " " . $child["username"] . "." ;
									print "</div>" ;
								}
								else {
									print "<div class='success'>" ;
										print sprintf(_('Student %1$s was successfully created.'), $child["username"]) ;
									print "</div>" ;
								}
							}
							else {
								print "<div class='error'>" ;
									print _("There was an error locating student:") . " " . $child["username"] . "." ;
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