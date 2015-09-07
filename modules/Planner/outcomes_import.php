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

if (isActionAccessible($guid, $connection2, "/modules/Planner/outcomes_import.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Check access based on privileges in Manage Outcomes
	$permission=FALSE ;
	$highestAction=getHighestGroupedAction($guid, "/modules/Planner/outcomes.php", $connection2) ;
	if ($highestAction=="Manage Outcomes_viewAllEditLearningArea") {
		$permission="Learning Area" ;
	}
	else if ($highestAction=="Manage Outcomes_viewEditAll") {
		$permission="School" ;
	}
	
	if ($permission!="Learning Area" AND $permission!="School") {
		//Acess denied due to privileges in Manage Outcomes
		print "<div class='error'>" ;
			print _("You do not have access to this action.") ;
		print "</div>" ;
	}
	else {
		//Proceed!
		print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . _('Import Outcomes') . "</div>" ;
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
		
		$yearGroups=getYearGroups($connection2) ;
	
		//STEP 1, SELECT TERM
		if ($step==1) {
			?>
			<h2>
				<?php print _('Step 1 - Select CSV Files') ?>
			</h2>
			<p>
				<?php print _('This page allows you to import outcomes from a CSV file, based on your access level in Manage Outcomes.') ?><br/>
			</p>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/outcomes_import.php&step=2" ?>" enctype="multipart/form-data">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr>
						<td style='width: 275px'> 
							<b><?php print _('CSV File') ?> *</b><br/>
							<span style="font-size: 90%"><i><?php print _('See Notes below for specification.') ?></i></span>
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
				<li><?php print _('The submitted file must have the following fields in the following order (* denotes required field):') ?></li> 
					<ol>
						<?php
						if ($permission=="Learning Area") {
							print "<li><b>" . _('Scope') . " *</b> - " . _('Learning Area') . "</li>" ;
						}
						else if ($permission=="School") {
							print "<li><b>" . _('Scope') . " *</b> - " . _('School or Learning Area') . "</li>" ;
						}
						?>
						<li><b><?php print _('Learning Area') ?></b> - <?php print _('Learning Area name, or blank if scope is School') ?></li>
						<li><b><?php print _('Name') ?> *</b></li>
						<li><b><?php print _('Short Name') ?> *</b></li>
						<li><b><?php print _('Category') ?></b></li>
						<li><b><?php print _('Description') ?></b></li>
						<?php
						$yearGroupList="" ;
						for ($i=0; $i<count($yearGroups); $i=$i+2) {
							$yearGroupList.= _($yearGroups[($i+1)]) . ", " ;
						}
						$yearGroupList=substr($yearGroupList,0,-2) ;
						?>
						<li><b><?php print _('Year Groups') ?></b> - <?php print sprintf(_('Comma separated list, e.g: %1$s'), "<i>" . $yearGroupList . "</i>") ?></li>
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
		
			//Check file type
			if (($_FILES['file']['type']!="text/csv") AND ($_FILES['file']['type']!="text/comma-separated-values") AND ($_FILES['file']['type']!="text/x-comma-separated-values") AND ($_FILES['file']['type']!="application/vnd.ms-excel")) {
				?>
				<div class='error'>
					<?php print sprintf(_('Import cannot proceed, as the submitted file has a MIME-TYPE of %1$s, and as such does not appear to be a CSV file.'), $_FILES['file']['type']) ?><br/>
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
				$proceed=TRUE ;
				
				print "<h4>" ;
					print _("File Import") ;
				print "</h4>" ;
				$importFail=false ;
				$csvFile=$_FILES['file']['tmp_name'] ;
				$handle=fopen($csvFile, "r");
				$users=array() ;
				$userCount=0 ;
				$userSuccessCount=0 ;
				while (($data=fgetcsv($handle, 100000, stripslashes($_POST["fieldDelimiter"]), stripslashes($_POST["stringEnclosure"]))) !==FALSE) {
					if ($data[0]!="" AND $data[2]!="" AND $data[3]!="") {
						$users[$userSuccessCount]["scope"]="" ; if (isset($data[0])) { $users[$userSuccessCount]["scope"]=$data[0] ; }
						$users[$userSuccessCount]["learningArea"]="" ; if (isset($data[1])) { $users[$userSuccessCount]["learningArea"]=$data[1] ;  }
						$users[$userSuccessCount]["name"]="" ; if (isset($data[2])) { $users[$userSuccessCount]["name"]=$data[2] ; }
						$users[$userSuccessCount]["nameShort"]="" ; if (isset($data[3])) { $users[$userSuccessCount]["nameShort"]=$data[3] ; }
						$users[$userSuccessCount]["category"]="" ; if (isset($data[4])) { $users[$userSuccessCount]["category"]=$data[4] ; }
						$users[$userSuccessCount]["description"]="" ; if (isset($data[5])) { $users[$userSuccessCount]["description"]=$data[5] ; }
						$users[$userSuccessCount]["yearGroups"]="" ; if (isset($data[6])) { $users[$userSuccessCount]["yearGroups"]=$data[6] ; }
						
						$userSuccessCount++ ;
					}
					else {
						print "<div class='error'>" ;
							print sprintf(_('Outcome with name %1$s had some information malformations.'), $data[2]) ;
						print "</div>" ;
					}
					$userCount++ ;
				}
				fclose($handle);
				if ($userSuccessCount==0) {
					print "<div class='error'>" ;
						print _("No useful outcomes were detected in the import file (perhaps they did not meet minimum requirements), so the import will be aborted.") ;
					print "</div>" ;
					$proceed=false ;
				}
				else if ($userSuccessCount<$userCount) {
					print "<div class='error'>" ;
						print _("Some outcomes could not be successfully read or used, so the import will be aborted.") ;
					print "</div>" ;
					$proceed=false ;
				}
				else if ($userSuccessCount==$userCount) {
					print "<div class='success'>" ;
						print _("All outcomes could be read and used, so the import will proceed.") ;
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
				foreach ($users AS $user) {
					//ADD USER
					$addUserFail=FALSE ;
					
					//Check permisison
					if ($user["scope"]=="School" AND $permission!="School") {
						print "<div class='error'>" ;
							print _("There was an error creating outcome:") . " " . $user["name"] . "." ;
						print "</div>" ;
					}
					else {
						$gibbonDepartmentID=NULL ;
						if ($user["learningArea"]!="") {
							try {
								$data=array("learningArea"=>$user["learningArea"]); 
								$sql="SELECT gibbonDepartmentID FROM gibbonDepartment WHERE name=:learningArea" ;
								$result=$connection2->prepare($sql);
								$result->execute($data);
							}
							catch(PDOException $e) { }
							if ($result->rowCount()==1) {
								$row=$result->fetch() ;
								$gibbonDepartmentID=$row["gibbonDepartmentID"] ;
							}	
						}
						$gibbonYearGroupIDList="" ;
						$yearGroupsSelected=explode(",", $user["yearGroups"]) ;
						foreach ($yearGroupsSelected AS $yearGroupSelected) {
							for ($i=0; $i<count($yearGroups); $i=$i+2) {
								if (trim($yearGroupSelected)==$yearGroups[($i+1)]) {
									$gibbonYearGroupIDList.=$yearGroups[$i] . "," ;
								}
							}
						}
						if ($gibbonYearGroupIDList!="") {
							$gibbonYearGroupIDList=substr($gibbonYearGroupIDList, 0, -1) ;
						}
					
						//Add smart year group ID fill here...
						try {
							$data=array("scope"=>$user["scope"], "gibbonDepartmentID"=>$gibbonDepartmentID, "name"=>$user["name"], "nameShort"=>$user["nameShort"], "category"=>$user["category"], "description"=>$user["description"], "gibbonYearGroupIDList"=>$gibbonYearGroupIDList); 
							$sql="INSERT INTO gibbonOutcome SET scope=:scope, gibbonDepartmentID=:gibbonDepartmentID, name=:name, nameShort=:nameShort, category=:category, description=:description, gibbonYearGroupIDList=:gibbonYearGroupIDList" ;
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
								print _("There was an error creating outcome:") . " " . $user["name"] . "." ;
							print "</div>" ;
						}
						else {
							print "<div class='success'>" ;
								print sprintf(_('Outcome %1$s was successfully created.'), $user["name"]) ;
							print "</div>" ;
						}
					}
				}
			}
		}
	}
}
?>