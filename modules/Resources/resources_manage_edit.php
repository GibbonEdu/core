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

@session_start() ;

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;


if (isActionAccessible($guid, $connection2, "/modules/Resources/resources_manage_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Get action with highest precendence
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print "The highest grouped action cannot be determined." ;
		print "</div>" ;
	}
	else {
		//Proceed!
		print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/resources_manage.php'>Manage Resources</a> > </div><div class='trailEnd'>Edit Resource</div>" ;
		print "</div>" ;
		
		if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
		$updateReturnMessage="" ;
		$class="error" ;
		if (!($updateReturn=="")) {
			if ($updateReturn=="fail0") {
				$updateReturnMessage=_("Your request failed because you do not have access to this action.") ;	
			}
			else if ($updateReturn=="fail1") {
				$updateReturnMessage=_("Your request failed because your inputs were invalid.") ;	
			}
			else if ($updateReturn=="fail2") {
				$updateReturnMessage=_("Your request failed due to a database error.") ;	
			}
			else if ($updateReturn=="fail3") {
				$updateReturnMessage=_("Your request failed because your inputs were invalid.") ;	
			}
			else if ($updateReturn=="fail4") {
				$updateReturnMessage=_("Your request failed because your inputs were invalid.") ;	
			}
			else if ($updateReturn=="fail5") {
				$updateReturnMessage=_("Your request failed due to an attachment error.") ;	
			}
			else if ($updateReturn=="success0") {
				$updateReturnMessage=_("Your request was completed successfully.") ;	
				$class="success" ;
			}
			print "<div class='$class'>" ;
				print $updateReturnMessage;
			print "</div>" ;
		} 
		
		//Check if school year specified
		$gibbonResourceID=$_GET["gibbonResourceID"];
		if ($gibbonResourceID=="Y") {
			print "<div class='error'>" ;
				print _("You have not specified one or more required parameters.") ;
			print "</div>" ;
		}
		else {
			try {
				if ($highestAction=="Manage Resources_all") {
					$data=array("gibbonResourceID"=>$gibbonResourceID); 
					$sql="SELECT gibbonResource.*, surname, preferredName, title FROM gibbonResource JOIN gibbonPerson ON (gibbonResource.gibbonPersonID=gibbonPerson.gibbonPersonID) AND gibbonResourceID=:gibbonResourceID ORDER BY timestamp DESC" ; 
				}
				else if ($highestAction=="Manage Resources_my") {
					$data=array("gibbonResourceID"=>$gibbonResourceID, "gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
					$sql="SELECT gibbonResource.*, surname, preferredName, title FROM gibbonResource JOIN gibbonPerson ON (gibbonResource.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonResource.gibbonPersonID=:gibbonPersonID AND gibbonResourceID=:gibbonResourceID ORDER BY timestamp DESC" ; 
				}
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			
			if ($result->rowCount()!=1) {
				print "<div class='error'>" ;
					print _("The selected record does not exist, or you do not have access to it.") ;
				print "</div>" ;
			}
			else {
				//Let's go!
				$row=$result->fetch() ;
				
				if ($_GET["search"]!="") {
					print "<div class='linkTop'>" ;
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Resources/resources_manage.php&search=" . $_GET["search"] . "'>Back to Search Results</a>" ;
					print "</div>" ;
				}
		
				?>
				<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/resources_manage_editProcess.php?gibbonResourceID=$gibbonResourceID&search=" . $_GET["search"] ?>" enctype="multipart/form-data">
					<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
						<input type="hidden" name="type" value="<? print $row["type"] ?>">
						<tr class='break'>
							<td colspan=2> 
								<h3>Resource Contents</h3>
							</td>
						</tr>
						<?
						if ($row["type"]=="File") {
							?>
							<tr id="resourceFile">
								<td> 
									<b>File</b><br/>
									<? if ($row["content"]!="") { ?>
									<span style="font-size: 90%"><i>Will overwrite existing attachment</i></span>
									<? } ?>
								</td>
								<td class="right">
									<?
									if ($row["content"]!="") {
										print "Current attachment: <a target='_blank' href='" . $_SESSION[$guid]["absoluteURL"] . "/" . $row["content"] . "'>" . $row["content"] . "</a><br/><br/>" ;
									}
									?>
									<input type="file" name="file" id="file"><br/><br/>
									<script type="text/javascript">
										<?
										//Get list of acceptable file extensions
										try {
											$dataExt=array(); 
											$sqlExt="SELECT * FROM gibbonFileExtension" ;
											$resultExt=$connection2->prepare($sqlExt);
											$resultExt->execute($dataExt);
										}
										catch(PDOException $e) { }
										$ext="" ;
										while ($rowExt=$resultExt->fetch()) {
											$ext=$ext . "'." . $rowExt["extension"] . "'," ;
										}
										?>
										var file=new LiveValidation('file');
										file.add( Validate.Inclusion, { within: [<? print $ext ;?>], failureMessage: "Illegal file type!", partialMatch: true, caseSensitive: false } );
									</script>	
									<?
									print getMaxUpload() ;
									?>
								</td>
							</tr>
							<?
						}
						else if ($row["type"]=="HTML") {
							?>
							<tr id="resourceHTML">
								<td colspan=2> 
									<b>HTML *</b>
									<? print getEditor($guid,  TRUE, "html", $row["content"], 20, true, true, false, false ) ?>
								</td>
							</tr>
							<?
						}
						else if ($row["type"]=="Link") {
							?>
							<tr id="resourceLink">
								<td> 
									<b>Link *</b><br/>
								</td>
								<td class="right">
									<input name="link" id="link" maxlength=255 value="<? print $row["content"] ?>" type="text" style="width: 300px">
									<script type="text/javascript">
										var link=new LiveValidation('link');
										link.add(Validate.Presence);
										link.add( Validate.Format, { pattern: /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/, failureMessage: "Must start with http://" } );
									</script>	
								</td>
							</tr>
							<?
						}
						?>
						
						<tr class='break'>
							<td colspan=2> 
								<h3>Resource Details</h3>
							</td>
						</tr>
						<tr>
							<td> 
								<? print "<b>" . _('Name') . " *</b><br/>" ; ?>
								<span style="font-size: 90%"><i></i></span>
							</td>
							<td class="right">
								<input name="name" id="name" maxlength=30 value="<? print $row["name"] ?>" type="text" style="width: 300px">
								<script type="text/javascript">
									var name=new LiveValidation('name');
									name.add(Validate.Presence);
								 </script>
							</td>
						</tr>
						<?
						try {
							$dataCategory=array(); 
							$sqlCategory="SELECT * FROM gibbonSetting WHERE scope='Resources' AND name='categories'" ;
							$resultCategory=$connection2->prepare($sqlCategory);
							$resultCategory->execute($dataCategory);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						
						if ($resultCategory->rowCount()==1) {
							$rowCategory=$resultCategory->fetch() ;
							$options=$rowCategory["value"] ;
							
							if ($options!="") {
								$options=explode(",", $options) ;
								?>
								<tr>
									<td> 
										<b>Category *</b><br/>
										<span style="font-size: 90%"><i></i></span>
									</td>
									<td class="right">
										<select name="category" id="category" style="width: 302px">
											<option value="Please select..."><? print _('Please select...') ?></option>
											<?
											for ($i=0; $i<count($options); $i++) {
												$selected="" ;
												if ($row["category"]==$options[$i]) {
													$selected="selected" ;
												}
												?>
												<option <? print $selected ?> value="<? print trim($options[$i]) ?>"><? print trim($options[$i]) ?></option>
											<?
											}
											?>
										</select>
										<script type="text/javascript">
											var category=new LiveValidation('category');
											category.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "Select something!"});
										 </script>
									</td>
								</tr>
								<?
							}
						}
						
						try {
							$dataPurpose=array(); 
							$sqlPurpose="(SELECT * FROM gibbonSetting WHERE scope='Resources' AND name='purposesGeneral')" ;
							if ($highestAction=="Manage Resources_all") {
								$sqlPurpose.=" UNION (SELECT * FROM gibbonSetting WHERE scope='Resources' AND name='purposesRestricted')" ;
							}
							$resultPurpose=$connection2->prepare($sqlPurpose);
							$resultPurpose->execute($dataPurpose);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						
						if ($resultPurpose->rowCount()>0) {
							$options="" ;
							while($rowPurpose=$resultPurpose->fetch()) {
								$options.=$rowPurpose["value"] . "," ;
							}
							$options=substr($options,0,-1) ;
				
							if ($options!="") {
								$options=explode(",", $options) ;
								?>
								<tr>
									<td> 
										<b>Purpose</b><br/>
										<span style="font-size: 90%"><i></i></span>
									</td>
									<td class="right">
										<select name="purpose" id="purpose" style="width: 302px">
											<option value=""></option>
											<?
											for ($i=0; $i<count($options); $i++) {
												$selected="" ;
												if ($row["purpose"]==$options[$i]) {
													$selected="selected" ;
												}
											?>
												<option <? print $selected ?> value="<? print trim($options[$i]) ?>"><? print trim($options[$i]) ?></option>
											<?
											}
											?>
										</select>
									</td>
								</tr>
								<?
							}
						}
						?>
						<tr>
							<td> 
								<b>Tags *</b><br/>
								<span style="font-size: 90%"><i>Use lots of tags!</i></span>
							</td>
							<td class="right">
								<?
								//Get tag list
								try {
									$dataList=array(); 
									$sqlList="SELECT * FROM gibbonResourceTag WHERE count>0 ORDER BY tag" ; 
									$resultList=$connection2->prepare($sqlList);
									$resultList->execute($dataList);
								}
								catch(PDOException $e) { 
									print "<div class='error'>" . $e->getMessage() . "</div>" ; 
								}
								
								$list="" ;
								while ($rowList=$resultList->fetch()) {
									$list=$list . "{id: \"" . $rowList["tag"] . "\", name: \"" . $rowList["tag"] . " <i>(" . $rowList["count"] . ")</i>\"}," ;
								}
								?>
								<input type="text" id="tags" name="tags" />
								<?
									$prepopulate="" ;
									$tags=explode(",", $row["tags"]) ;
									foreach ($tags as $tag) {
										$prepopulate.="{id: " . $tag . ", name: " . $tag . "}, " ;
									}
									$prepopulate=substr($prepopulate,0,-2) ;
								?>
								<script type="text/javascript">
									$(document).ready(function() {
										 $("#tags").tokenInput([
												<? print substr($list,0,-1) ?>
											], 
											{theme: "facebook",
											hintText: "Start typing a tag...",
											allowCreation: true,
											<?
											if ($prepopulate!="{id: , name: }") {
												print "prePopulate: [ $prepopulate ]," ;
											}
											?>
											preventDuplicates: true});
									});
								</script>
								<script type="text/javascript">
									var tags=new LiveValidation('tags');
									tags.add(Validate.Presence);
								 </script>
							</td>
						</tr>
						<tr>
							<td> 
								<b>Year Groups</b><br/>
								<span style="font-size: 90%"><i>Students year groups which may participate<br/></i></span>
							</td>
							<td class="right">
								<?
								print "<fieldset style='border: none'>" ;
								?>
								<script type="text/javascript">
									$(function () { // this line makes sure this code runs on page load
										$('.checkall').click(function () {
											$(this).parents('fieldset:eq(0)').find(':checkbox').attr('checked', this.checked);
										});
									});
								</script>
								<?
								print _("All") .  " / " . _("None") . " <input type='checkbox' class='checkall'><br/>" ;
								$yearGroups=getYearGroups($connection2) ;
								if ($yearGroups=="") {
									print "<i>" . _('No year groups available.') . "</i>" ;
								}
								else {
									$selectedYears=explode(",", $row["gibbonYearGroupIDList"]) ;
									for ($i=0; $i<count($yearGroups); $i=$i+2) {
										$checked="" ;
										foreach ($selectedYears as $selectedYear) {
											if ($selectedYear==$yearGroups[$i]) {
												$checked="checked" ;
											}
										}
										
										print $yearGroups[($i+1)] . " <input $checked type='checkbox' name='gibbonYearGroupIDCheck" . ($i)/2 . "'><br/>" ; 
										print "<input type='hidden' name='gibbonYearGroupID" . ($i)/2 . "' value='" . $yearGroups[$i] . "'>" ;
									}
								}
								print "</fieldset>" ;
								?>
								<input type="hidden" name="count" value="<? print (count($yearGroups))/2 ?>">
							</td>
						</tr>
						<tr>
							<td> 
								<b><? print _('Description') ?></b><br/>
								<span style="font-size: 90%"><i></i></span>
							</td>
							<td class="right">
								<textarea name="description" id="description" rows=8 style="width: 300px"><? print $row["description"] ?></textarea>
							</td>
						</tr>
						
						<tr>
							<td>
								<span style="font-size: 90%"><i>* <? print _("denotes a required field") ; ?></i></span>
							</td>
							<td class="right">
								<input type="hidden" name="address" value="<? print $_SESSION[$guid]["address"] ?>">
								<input type="submit" value="<? print _("Submit") ; ?>">
							</td>
						</tr>
					</table>
				</form>
				<?
			}
		}
	}
}
?>