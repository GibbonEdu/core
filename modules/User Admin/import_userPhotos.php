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

if (isActionAccessible($guid, $connection2, "/modules/User Admin/import_userPhotos.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Import Users') . "</div>" ;
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
			<?php print __($guid, 'Step 1 - Select ZIP File') ?>
		</h2>
		<p>
			<?php print __($guid, 'This page allows you to bulk import user photos, in the form of a ZIP file contain images named with individual usernames. See notes below for sizing information.') ?><br/>
		</p>
		<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/import_userPhotos.php&step=2" ?>" enctype="multipart/form-data">
			<table class='smallIntBorder fullWidth' cellspacing='0'>	
				<tr>
					<td style='width: 275px'> 
						<b><?php print __($guid, 'ZIP File') ?> *</b><br/>
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
			<li><?php print __($guid, 'You may only submit ZIP files.') ?></li>
			<li><?php print __($guid, 'Imports cannot be run concurrently (e.g. make sure you are the only person importing at any one time).') ?></li>
			<li><?php print __($guid, 'Please note the following requirements for images in preparing your ZIP file:') ?></li> 
				<ol>
					<li><b><?php print __($guid, 'File Name') ?></b> - <?php print __($guid, 'File name of each image must be username plus extension, e.g. astudent.jpg') ?></li>
					<li><b><?php print __($guid, 'Folder') ?> *</b> - <?php print __($guid, 'The ZIP file must not contain any folders, only files.') ?></li>
					<li><b><?php print __($guid, 'File Type') ?> *</b> - <?php print __($guid, 'Images must be formatted as JPG or PNG.') ?></li>
					<li><b><?php print __($guid, 'Image Size') ?> *</b> - <?php print __($guid, 'Displayed at 240px by 320px.') ?></li>
					<li><b><?php print __($guid, 'Size Range') ?> *</b> - <?php print __($guid, 'Accepts images up to 360px by 480px.') ?></li>
					<li><b><?php print __($guid, 'Aspect Ratio Range') ?> *</b> - <?php print __($guid, 'Accepts aspect ratio between 1:1.2 and 1:1.4.') ?></li>
				</ol>
			</li>
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
		if ($_FILES['file']['type']!="application/zip") {
			?>
			<div class='error'>
				<?php print sprintf(__($guid, 'Import cannot proceed, as the submitted file has a MIME-TYPE of %1$s, and as such does not appear to be a ZIP file.'), $_FILES['file']['type']) ?><br/>
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
				$sql="LOCK TABLES gibbonPerson WRITE" ;
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
				$path=$_FILES['file']['tmp_name'] ;
				$path=str_replace("\\","/",$path); 
				$zip=new ZipArchive;
				$time=time() ;
						
				if ($zip->open($path) === true) { //Success
					for($i=0; $i < $zip->numFiles; $i++) {
						if (substr($zip->getNameIndex($i),0, 8)!="__MACOSX") {
							$filename=$zip->getNameIndex($i);
							
							//Check file type
							$fileTypeFail=FALSE ;
							if (strtolower(substr($filename, -4, 4))!=".jpg" AND strtolower(substr($filename, -4, 4))!=".png") {
								$fileTypeFail=TRUE ;
								print "<div class='error'>" ;
									print sprintf(__($guid, 'Image %1$s does not appear to be, formatted as JPG or PNG.'), $_FILES['file']['type']) ;
								print "</div>" ;
							}
							
							if ($fileTypeFail==FALSE) {
								//Extract username from file name, and check existence of user
								$userCheckFail=FALSE ;
								$username=substr($filename, 0, -4) ;
								
								try {
									$data=array("username"=>$username); 
									$sql="SELECT username FROM gibbonPerson WHERE username=:username" ;
									$result=$connection2->prepare($sql);
									$result->execute($data);
								}
								catch(PDOException $e) { 
									$userCheckFail=TRUE ;
								}
								if ($result->rowCount()!=1) {
									$userCheckFail=TRUE ;
								}
								
								if ($userCheckFail) {
									print "<div class='error'>" ;
										print __($guid, "There was an error locating user:") . " " . $username . "." ;
									print "</div>" ;
								}
								else {
									if ($userCheckFail==FALSE) {
										//Upload file with unique name
										$fileUploadFail=FALSE ;
										$filePath="" ;
										$unique=FALSE;
										$count=0 ;
										while ($unique==FALSE AND $count<100) {
											if ($count==0) {
												$filePath="uploads/" . date("Y", $time) . "/" . date("m", $time) . "/" . $username . strrchr($filename, ".") ;
											}
											else {
												$filePath="uploads/" . date("Y", $time) . "/" . date("m", $time) . "/" . $username . "_$count" . strrchr($filename, ".") ;
											}
											if (!(file_exists($_SESSION[$guid]["absolutePath"] . "/" . $filePath))) {
												$unique=TRUE ;
											}
											$count++ ;
										}
										if (!(copy("zip://" . $path . "#" . $filename, $filePath))) {
											$fileUploadFail=TRUE ;
											print "<div class='error'>" ;
												print __($guid, "There was an error uploading photo for user:") . " " . $username . "." ;
											print "</div>" ;
										}
										
										if ($fileUploadFail==FALSE) {
											//Check image properties
											$imageFail=FALSE ;
											
											$size=getimagesize($_SESSION[$guid]["absolutePath"] . "/" . $filePath) ;
											$width=$size[0] ;
											$height=$size[1] ;
											$aspect=$height/$width ;
											if ($width>360 OR $height>480 OR $aspect<1.2 OR $aspect>1.4) {
												$imageFail=TRUE ;
												//Report error
												print "<div class='error'>" ;
													print __($guid, "There was an error in the sizing of the photo for user:") . " " . $username . "." ;
												print "</div>" ;
											}

											if ($imageFail==FALSE) {
												//Update gibbonPerson
												$updateFail=FALSE ;
												try {
													$data=array("image_240"=>$filePath, "username"=>$username) ;
													$sql="UPDATE gibbonPerson SET image_240=:image_240 WHERE username=:username" ;
													$result=$connection2->prepare($sql);
													$result->execute($data);
												}
												catch(PDOException $e) { 
													print "<div class='error'>" ;
														print __($guid, "There was an error updating user:") . " " . $username . "." ;
													print "</div>" ;
													$updateFail=TRUE ;
												}
												
												//Spit out results
												if ($updateFail==FALSE) {
													print "<div class='success'>" ;
														print sprintf(__($guid, 'User %1$s was successfully updated.'), $username) ;
													print "</div>" ;
												}
												
											}
										}
									}
								}
							}
						}
					}                  
					$zip->close();               
				}
				else {	//Error
					print "<div class='error'>" ;
						print __($guid, "The import file could not be decompressed.") ;
					print "</div>" ;	
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