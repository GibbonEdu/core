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

//Gibbon system-wide includes
include "../../functions.php" ;
include "../../config.php" ;

//New PDO DB connection
try {
  	$connection2=new PDO("mysql:host=$databaseServer;dbname=$databaseName;charset=utf8", $databaseUsername, $databasePassword);
	$connection2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$connection2->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
}
catch(PDOException $e) {
  echo $e->getMessage();
}

//Module includes
include $_SESSION[$guid]["absolutePath"] . "/modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

//Setup variables
$output="" ;
$id=$_GET["id"] ;
$action=NULL ;
if (isset($_GET["action"])) {
	$action=$_GET["action"] ;
}
$category=NULL ;
if (isset($_GET["category"])) {
	$category=$_GET["category"] ;
}
$purpose=NULL ;
if (isset($_GET["purpose"])) {
	$purpose=$_GET["purpose"] ;
}
$tag=NULL ;
if (isset($_GET["tag" . $id])) {
	$tag=$_GET["tag" . $id] ;
}
$gibbonYearGroupID=NULL ;
if (isset($_GET["gibbonYearGroupID"])) {
	$gibbonYearGroupID=$_GET["gibbonYearGroupID"] ;
}
$allowUpload=$_GET["allowUpload"] ;
$alpha=NULL ;
if (isset($_GET["alpha"])) {
	$alpha=$_GET["alpha"] ;
}

if (isActionAccessible($guid, $connection2, "/modules/Resources/resources_manage_add.php")==FALSE) {
	//Acess denied
	$output.="<div class='error'>" ;
		$output.=_("You do not have access to this page.") ;
	$output.="</div>" ;
}
else {
	$highestAction=getHighestGroupedAction($guid, "/modules/Resources/resources_manage.php", $connection2) ;
	if ($highestAction==FALSE) {
		$output.="<div class='error'>" ;
		$output.="The highest grouped action cannot be determined." ;
		$output.="</div>" ;
	}
	else {
		$output.="<script type='text/javascript'>" ;
			$output.="$(document).ready(function() {" ; 
				$output.="var options={" ;
					$output.="success: function(response) {" ;
						$output.="tinymce.execCommand(\"mceFocus\",false,\"$id\"); tinyMCE.execCommand(\"mceInsertContent\", 0, response); formReset(); \$(\"." .$id . "resourceAddSlider\").slideUp();" ;
					$output.="}, " ;
					$output.="url: '" . $_SESSION[$guid]["absoluteURL"] . "/modules/Resources/resources_add_ajaxProcess.php'," ;
					$output.="type: 'POST'" ;
				$output.="};" ; 
		 
				$output.="$('#" . $id . "ajaxForm').submit(function() {" ; 
					$output.="$(this).ajaxSubmit(options);" ; 
					$output.="return false;" ; 
				$output.="});" ; 
			$output.="});" ; 
		
			$output.="var formReset=function() {" ;
				$output.="$('#" . $id . "resourceAdd').css('display','none');" ;
			$output.="};" ;
		$output.="</script>" ;
	
		$output.="<table cellspacing='0' style='width: 100%'>" ;	
			$output.="<tr><td style='width: 30%; height: 1px; padding-top: 0px; padding-bottom: 0px'></td><td style='padding-top: 0px; padding-bottom: 0px'></td></tr>" ;
			$output.="<tr id='" . $id . "resourceInsert'>" ;
				$output.="<td colspan=2 style='padding-top: 0px'>" ; 
					$output.="<div style='margin: 0px' class='linkTop'><a href='javascript:void(0)' onclick='formReset(); \$(\"." .$id . "resourceAddSlider\").slideUp();'><img title='Close' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconCross.png'/></a></div>" ;
					$output.="<h3 style='margin-top: 0px; font-size: 140%'>Add & Insert A New Resource</h3>" ;
					$output.="<p>Use the form below to add a new resource to Gibbon. If the addition is successful, then it will be automatically inserted into your work above. Note that you cannot create HTML resources here (you have to go to the Resources module for that).</p>" ;
					$output.="<form id='" . $id . "ajaxForm' action='#'>" ;
						$output.="<table cellspacing='0' style='width: 100%'>" ;
							$output.="<tr><td style='width: 30%'></td><td></td></tr>" ;
							$output.="<tr>" ;
								$output.="<td colspan=2> " ;
									$output.="<h4>Resource Contents</h4>" ;
								$output.="</td>" ;
							$output.="</tr>" ;
						
							$output.="<script type='text/javascript'>" ;
								$output.="$(document).ready(function(){" ;
									$output.="$('#" . $id . "resourceFile').css('display','none');" ;
									$output.="$('#" . $id . "resourceLink').css('display','none');" ;
										
									$output.="$('#" . $id . "type').change(function(){" ;
										$output.="if ($('select." . $id . "type option:selected').val() == 'Link' ) {" ;
											$output.="$('#" . $id . "resourceFile').css('display','none');" ;
											$output.="$('#" . $id . "resourceLink').slideDown('fast', $('#" . $id . "resourceLink').css('display','table-row'));" ;
											$output.=$id . "file.disable();" ;
											$output.=$id . "link.enable();" ;
										$output.="} else if ($('select." . $id . "type option:selected').val() == 'File' ) {" ;
											$output.="$('#" . $id . "resourceLink').css('display','none');" ;
											$output.="$('#" . $id . "resourceFile').slideDown('fast', $('#" . $id . "resourceFile').css('display','table-row'));" ;
											$output.=$id . "file.enable();" ;
											$output.=$id . "link.disable();" ;
										$output.="}" ;
										$output.="else {" ;
											$output.="$('#" . $id . "resourceFile').css('display','none');" ;
											$output.="$('#" . $id . "resourceLink').css('display','none');" ;
											$output.=$id . "file.disable();" ;
											$output.=$id . "link.disable();" ;
										$output.="}" ;
									$output.="});" ;
								$output.="});" ;
							$output.="</script>" ;
						
							$output.="<tr>" ;
								$output.="<td>" ;
									$output.="<b>Type *</b><br/>" ;
								$output.="</td>" ;
								$output.="<td class='right'>" ;
									$output.="<select name='" . $id . "type' id='" . $id . "type' class='" . $id . "type' style='width: 302px'>" ;
										$output.="<option value='Please select...'>Please select...</option>" ;
										$output.="<option id='type' name='type' value='File'>File</option>" ;
										$output.="<option id='type' name='type' value='Link'>Link</option>" ;
									$output.="</select>" ;
									$output.="<script type='text/javascript'>" ;
										$output.="var " . $id . "type=new LiveValidation('" . $id . "type');" ;
										$output.="" . $id . "type.add(Validate.Inclusion, { within: ['File','Link'], failureMessage: 'Select something!'});" ;
									$output.="</script>" ;
								$output.="</td>" ;
							$output.="</tr>" ;
							$output.="<tr id='" . $id . "resourceFile'>" ;
								$output.="<td>" ;
									$output.="<b>File *</b><br/>" ;
								$output.="</td>" ;
								$output.="<td class='right'>" ;
									$output.="<input type='file' name='" . $id . "file' id='" . $id . "file'><br/><br/>" ;
									$output.="<script type='text/javascript'>" ;
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
										$output.="var " . $id . "file=new LiveValidation('" . $id . "file');" ;
										$output.=$id . "file.add( Validate.Inclusion, { within: [" . $ext . "], failureMessage: 'Illegal file type!', partialMatch: true, caseSensitive: false } );" ;
										$output.=$id . "file.add(Validate.Presence);" ;
										$output.=$id . "file.disable();" ;
									$output.="</script>" ;
									$output.=getMaxUpload() ;
								$output.="</td>" ;
							$output.="</tr>" ;
							$output.="<tr id='" . $id . "resourceLink'>" ;
								$output.="<td>" ;
									$output.="<b>Link *</b><br/>" ;
								$output.="</td>" ;
								$output.="<td class='right'>" ;
									$output.="<input name='" . $id . "link' id='" . $id . "link' maxlength=255 value='' type='text' style='width: 300px'>" ;
									$output.="<script type='text/javascript'>" ;
										$output.="var " . $id . "link=new LiveValidation('" . $id . "link');" ;
										$output.=$id . "link.add(Validate.Presence);" ;
										$output.=$id . "link.add( Validate.Format, { pattern: /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/, failureMessage: 'Must start with http://' } );" ;
										$output.=$id . "link.disable();" ;
									$output.="</script>" ;	
								$output.="</td>" ;
							$output.="</tr>" ;
						
						
							$output.="<tr>" ;
								$output.="<td colspan=2>" ;
									$output.="<h4>Resource Details</h4>" ;
								$output.="</td>" ;
							$output.="</tr>" ;
							$output.="<tr>" ;
								$output.="<td> " ;
									$output.="<b>Name *</b><br/>" ;
									$output.="<span style='font-size: 90%'><i></i></span>" ;
								$output.="</td>" ;
								$output.="<td class='right'>" ;
									$output.="<input name='" . $id . "name' id='" . $id . "name' maxlength=60 value='' type='text' style='width: 300px'>" ;
									$output.="<script type='text/javascript'>" ;
										$output.="var " . $id . "name=new LiveValidation('" . $id . "name');" ;
										$output.=$id . "name.add(Validate.Presence);" ;
									 $output.="</script>" ;
								$output.="</td>" ;
							$output.="</tr>" ;
							
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
									$output.="<tr>" ;
										$output.="<td> " ;
											$output.="<b>Category *</b><br/>" ;
											$output.="<span style='font-size: 90%'><i></i></span>" ;
										$output.="</td>" ;
										$output.="<td class='right'>" ;
											$output.="<select name='" . $id . "category' id='" . $id . "category' style='width: 302px'>" ;
												$output.="<option value='Please select...'>Please select...</option>" ;
												for ($i=0; $i<count($options); $i++) {
													$output.="<option value='" . trim($options[$i]) . "'>" . trim($options[$i]) . "</option>" ;
												}
											$output.="</select>" ;
											$output.="<script type='text/javascript'>" ;
												$output.="var " . $id . "category=new LiveValidation('" . $id . "category');" ;
												$output.="" . $id . "category.add(Validate.Exclusion, { within: ['Please select...'], failureMessage: 'Select something!'});" ;
											 $output.="</script>" ;
										$output.="</td>" ;
									$output.="</tr>" ;
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
									$output.="<tr>" ;
										$output.="<td>" ;
											$output.="<b>Purpose</b><br/>" ;
											$output.="<span style='font-size: 90%'><i></i></span>" ;
										$output.="</td>" ;
										$output.="<td class='right'>" ;
											$output.="<select name='" . $id . "purpose' id='" . $id . "purpose' style='width: 302px'>" ;
												$output.="<option value=''></option>" ;
												for ($i=0; $i<count($options); $i++) {
													$output.="<option value='" . trim($options[$i]). "'>" . trim($options[$i]) . "</option>" ;
												}
											$output.="</select>" ;
										$output.="</td>" ;
									$output.="</tr>" ;
								}
							}
						
							$output.="<tr>" ;
								$output.="<td> " ;
									$output.="<b>Tags *</b><br/>" ;
									$output.="<span style='font-size: 90%'><i>Use lots of tags!</i></span>" ;
								$output.="</td>" ;
								$output.="<td class='right'>" ;
									//Get tag list
									try {
										$dataList=array(); 
										$sqlList="SELECT * FROM gibbonResourceTag WHERE count>0 ORDER BY tag" ; 
										$resultList=$connection2->prepare($sqlList);
										$resultList->execute($dataList);
									}
									catch(PDOException $e) { 
										$output.="<div class='error'>" . $e->getMessage() . "</div>" ; 
									}
									$list="" ;
									while ($rowList=$resultList->fetch()) {
										$list=$list . "{id: \"" . $rowList["tag"] . "\", name: \"" . $rowList["tag"] . " <i>(" . $rowList["count"] . ")</i>\"}," ;
									}
									$output.="<style>" ;
										$output.="td.right ul.token-input-list-facebook { width: 302px; float: right }" ;
										$output.="td.right div.token-input-dropdown-facebook { width: 120px }" ;
									$output.="</style>" ;
									$output.="<input type='text' id='" . $id . "tags' name='" . $id . "tags' />" ;
									$output.="<script type='text/javascript'>" ;
										$output.="$(document).ready(function() {" ;
											$output.="$('#" . $id . "tags').tokenInput([" ;
											$output.=substr($list,0,-1) . "]," ; 
											$output.="{theme: 'facebook'," ;
											$output.="hintText: 'Start typing a tag...'," ;
											$output.="allowCreation: true," ;
											$output.="preventDuplicates: true});" ;
										$output.="});" ;
									$output.="</script>" ;
									$output.="<script type='text/javascript'>" ;
										$output.="var " . $id . "tags=new LiveValidation('" . $id . "tags');" ;
										$output.=$id . "tags.add(Validate.Presence);" ;
									 $output.="</script>" ;
								$output.="</td>" ;
							$output.="</tr>" ;
					
							$output.="<tr>" ;
								$output.="<td>" ;
									$output.="<b>Year Groups</b><br/>" ;
									$output.="<span style='font-size: 90%'><i>Students year groups which may participate<br/></i></span>" ;
								$output.="</td>" ;
								$output.="<td class='right'>" ;
									$output.="<fieldset style='border: none'>" ;
									$output.="<script type='text/javascript'>" ;
										$output.="$(function () {" ;
											$output.="$('.checkall').click(function () {" ;
												$output.="$(this).parents('fieldset:eq(0)').find(':checkbox').attr('checked', this.checked);" ;
											$output.="});" ;
										$output.="});" ;
									$output.="</script>" ;
									$output.="All / None <input type='checkbox' class='checkall' checked><br/>" ;
									$yearGroups=getYearGroups($connection2) ;
									if ($yearGroups=="") {
										$output.="<i>No year groups available.</i>" ;
									}
									else {
										for ($i=0; $i<count($yearGroups); $i=$i+2) {
											$checked="checked " ;
											$output.=$yearGroups[($i+1)] . " <input $checked type='checkbox' name='" . $id . "gibbonYearGroupIDCheck" . ($i)/2 . "'><br/>" ; 
											$output.="<input type='hidden' name='" . $id . "gibbonYearGroupID" . ($i)/2 . "' value='" . $yearGroups[$i] . "'>" ;
										}
									}
									$output.="</fieldset>" ;
									$output.="<input type='hidden' name='" . $id . "count' value='" . (count($yearGroups))/2 . "'>" ;
								$output.="</td>" ;
							$output.="</tr>" ;
						
							$output.="<tr>" ;
								$output.="<td>" ;
									$output.="<b>Description</b><br/>" ;
									$output.="<span style='font-size: 90%'><i></i></span>" ;
								$output.="</td>" ;
								$output.="<td class='right'>" ;
									$output.="<textarea name='" . $id . "description' id='" . $id . "description' rows=8 style='width: 300px'></textarea>" ;
								$output.="</td>" ;
							$output.="</tr>" ;
						
							$output.="<tr>" ;
								$output.="<td class='right' colspan=2>" ;
									$output.="<input type='hidden' name='id' value='" . $id . "'>" ;
									$output.="<input type='hidden' name='" . $id . "address' value='" . $_SESSION[$guid]["address"] . "'>" ;
									$output.="<input type='submit' value='Submit'>" ;
								$output.="</td>" ;
							$output.="</tr>" ;
							$output.="<tr>" ;
								$output.="<td class='right' colspan=2>" ;
									$output.="<span style='font-size: 90%'><i>* <? print _("denotes a required field") ; ?></i></span>" ;
								$output.="</td>" ;
							$output.="</tr>" ;
					
					
						$output.="</table>" ;
					$output.="</form>" ;
				$output.="</td>" ; 
			$output.="</tr>" ;
		$output.="</table>" ;
	}
}

print $output ;
?>