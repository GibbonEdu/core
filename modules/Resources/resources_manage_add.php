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

if (isActionAccessible($guid, $connection2, "/modules/Resources/resources_manage_add.php")==FALSE) {
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
		print _("The highest grouped action cannot be determined.") ;
		print "</div>" ;
	}
	else {
		print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/resources_manage.php'>Manage Resources</a> > </div><div class='trailEnd'>Add Resource</div>" ;
		print "</div>" ;
		
		if (isset($_GET["addReturn"])) { $addReturn=$_GET["addReturn"] ; } else { $addReturn="" ; }
		$addReturnMessage="" ;
		$class="error" ;
		if (!($addReturn=="")) {
			if ($addReturn=="fail0") {
				$addReturnMessage=_("Your request failed because you do not have access to this action.") ;	
			}
			else if ($addReturn=="fail2") {
				$addReturnMessage=_("Your request failed due to a database error.") ;	
			}
			else if ($addReturn=="fail3") {
				$addReturnMessage=_("Your request failed because your inputs were invalid.") ;	
			}
			else if ($addReturn=="fail4") {
				$addReturnMessage=_("Your request failed because your inputs were invalid.") ;	
			}
			else if ($addReturn=="fail5") {
				$addReturnMessage=_("Your request failed due to an attachment error.") ;	
			}
			else if ($addReturn=="fail6") {
				$updateReturnMessage=_("Your request was successful, but some data was not properly saved.") ;
			}
			else if ($addReturn=="success0") {
				$addReturnMessage=_("Your request was completed successfully.You can now add another record if you wish.") ;	
				$class="success" ;
			}
			print "<div class='$class'>" ;
				print $addReturnMessage;
			print "</div>" ;
		} 
		
		if ($_GET["search"]!="") {
			print "<div class='linkTop'>" ;
				print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Resources/resources_manage.php&search=" . $_GET["search"] . "'>" . _('Back to Search Results') . "</a>" ;
			print "</div>" ;
		}
		
		?>
		<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/resources_manage_addProcess.php?search=" . $_GET["search"] ?>" enctype="multipart/form-data">
			<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
				<tr class='break'>
					<td colspan=2> 
						<h3>Resource Contents</h3>
					</td>
				</tr>
				<script type="text/javascript">
					$(document).ready(function(){
						$("#resourceFile").css("display","none");
						$("#resourceHTML").css("display","none");
						$("#resourceLink").css("display","none");
								
						$("#type").change(function(){
							if ($('select.type option:selected').val()=="Link" ) {
								$("#resourceFile").css("display","none");
								$("#resourceHTML").css("display","none");
								$("#resourceLink").slideDown("fast", $("#resourceLink").css("display","table-row")); 
								link.enable();
								file.disable();
								html.disable();
							} else if ($('select.type option:selected').val()=="File" ) {
								$("#resourceLink").css("display","none");
								$("#resourceHTML").css("display","none");
								$("#resourceFile").slideDown("fast", $("#resourceFile").css("display","table-row")); 
								file.enable();
								link.disable();
								html.disable();
							} else if ($('select.type option:selected').val()=="HTML" ) {
								$("#resourceLink").css("display","none");
								$("#resourceFile").css("display","none");
								$("#resourceHTML").slideDown("fast", $("#resourceHTML").css("display","table-row")); 
								html.enable();
								file.disable();
								link.disable();
							}
							else {
								$("#resourceFile").css("display","none");
								$("#resourceHTML").css("display","none");
								$("#resourceLink").css("display","none");
								file.disable();
								link.disable();
								html.disable();
							}
						 });
					});
				</script>
				<tr>
					<td> 
						<b><? print _('Type') ?> *</b><br/>
					</td>
					<td class="right">
						<select name="type" id="type" class='type' style="width: 302px">
							<option value="Please select..."><? print _('Please select...') ?></option>
							<option id='type' name="type" value="File" /> File
							<option id='type' name="type" value="HTML" /> HMTL
							<option id='type' name="type" value="Link" /> Link
						</select>
						<script type="text/javascript">
							var type=new LiveValidation('type');
							type.add(Validate.Inclusion, { within: ['File','HTML','Link'], failureMessage: "<? print _('Select something!') ?>"});
						</script>
					</td>
				</tr>
				<tr id="resourceFile">
					<td> 
						<b>File *</b><br/>
					</td>
					<td class="right">
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
							file.add(Validate.Presence);
							file.disable();
						</script>	
						<?
						print getMaxUpload() ;
						?>
					</td>
				</tr>
				<tr id="resourceHTML">
					<td colspan=2> 
						<b>HTML *</b>
						<? print getEditor($guid,  TRUE, "html", "", 20, false, false, false, false ) ?>
					</td>
				</tr>
				<tr id="resourceLink">
					<td> 
						<b>Link *</b><br/>
					</td>
					<td class="right">
						<input name="link" id="link" maxlength=255 value="" type="text" style="width: 300px">
						<script type="text/javascript">
							var link=new LiveValidation('link');
							link.add(Validate.Presence);
							link.add( Validate.Format, { pattern: /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/, failureMessage: "Must start with http://" } );
							link.disable();
						</script>	
					</td>
				</tr>
				
				
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
						<input name="name" id="name" maxlength=60 value="" type="text" style="width: 300px">
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
									?>
										<option value="<? print trim($options[$i]) ?>"><? print trim($options[$i]) ?></option>
									<?
									}
									?>
								</select>
								<script type="text/javascript">
									var category=new LiveValidation('category');
									category.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: "<? print _('Select something!') ?>"});
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
									?>
										<option value="<? print trim($options[$i]) ?>"><? print trim($options[$i]) ?></option>
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
						<style>
							td.right ul.token-input-list-facebook { width: 302px; float: right } 
							td.right div.token-input-dropdown-facebook { width: 120px } 
						</style>
						<input type="text" id="tags" name="tags" />
						<script type="text/javascript">
							$(document).ready(function() {
								 $("#tags").tokenInput([
									<? print substr($list,0,-1) ?>
								], 
									{theme: "facebook",
									hintText: "Start typing a tag...",
									allowCreation: true,
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
						<b><? print _('Year Groups') ?></b><br/>
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
						print "All / None <input type='checkbox' class='checkall' checked><br/>" ;
						$yearGroups=getYearGroups($connection2) ;
						if ($yearGroups=="") {
							print "<i>" . _('No year groups available.') . "</i>" ;
						}
						else {
							for ($i=0; $i<count($yearGroups); $i=$i+2) {
								$checked="checked " ;
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
						<textarea name="description" id="description" rows=8 style="width: 300px"></textarea>
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
?>