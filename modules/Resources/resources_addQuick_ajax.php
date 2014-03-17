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
$alpha=$_GET["alpha"] ;

$output.="<script type='text/javascript'>" ;
	$output.="$(document).ready(function() {" ; 
		$output.="var options={" ;
			$output.="success: function(response) {" ;
				$output.="tinymce.execCommand(\"mceFocus\",false,\"$id\"); tinyMCE.execCommand(\"mceInsertContent\", 0, response); formReset(); \$(\"." .$id . "resourceQuickSlider\").slideUp();" ;
			$output.="}, " ;
			$output.="url: '" . $_SESSION[$guid]["absoluteURL"] . "/modules/Resources/resources_addQuick_ajaxProcess.php'," ;
			$output.="type: 'POST'" ;
		$output.="};" ; 
	 
		$output.="$('#" . $id . "ajaxForm').submit(function() {" ; 
			$output.="$(this).ajaxSubmit(options);" ; 
			$output.="return false;" ; 
		$output.="});" ; 
	$output.="});" ; 
	
	$output.="var formReset=function() {" ;
		$output.="$('#" . $id . "resourceQuick').css('display','none');" ;
	$output.="};" ;
$output.="</script>" ;

$output.="<table cellspacing='0' style='width: 100%'>" ;	
	$output.="<tr><td style='width: 30%; height: 1px; padding-top: 0px; padding-bottom: 0px'></td><td style='padding-top: 0px; padding-bottom: 0px'></td></tr>" ;
	$output.="<tr id='" . $id . "resourceQuick'>" ;
		$output.="<td colspan=2 style='padding-top: 0px'>" ; 
			$output.="<div style='margin: 0px' class='linkTop'><a href='javascript:void(0)' onclick='formReset(); \$(\"." .$id . "resourceQuickSlider\").slideUp();'><img title='Close' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconCross.png'/></a></div>" ;
			$output.="<h3 style='margin-top: 0px; font-size: 140%'>Quick Insert</h3>" ;
			$output.="<p>Use the form below to quickly add a resource to this field, without having to set up a shared resource in Gibbon. If the addition is successful, then it will be automatically inserted into your work above. <b>You are encourage to create shared resources whenever you think a resource might be useful to others.</b></p>" ;
			$output.="<form id='" . $id . "ajaxForm'>" ;
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
								$output.="if ($('select." . $id . "type option:selected').val()=='Link' ) {" ;
									$output.="$('#" . $id . "resourceFile').css('display','none');" ;
									$output.="$('#" . $id . "resourceLink').slideDown('fast', $('#" . $id . "resourceLink').css('display','table-row'));" ;
									$output.=$id . "file.disable();" ;
									$output.=$id . "link.enable();" ;
								$output.="} else if ($('select." . $id . "type option:selected').val()=='File' ) {" ;
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
					$output.="<tr>" ;
						$output.="<td class='right' colspan=2>" ;
							$output.="<input type='hidden' name='id' value='" . $id . "'>" ;
							$output.="<input type='hidden' name='" . $id . "address' value='" . $_SESSION[$guid]["address"] . "'>" ;
							$output.="<input type='submit' value='Submit'>" ;
						$output.="</td>" ;
					$output.="</tr>" ;
					$output.="<tr>" ;
						$output.="<td class='right' colspan=2>" ;
							$output.="<span style='font-size: 90%'><i>* " . _("denotes a required field") . "</i></span>" ;
						$output.="</td>" ;
					$output.="</tr>" ;
				
				
				$output.="</table>" ;
			$output.="</form>" ;
		$output.="</td>" ; 
	$output.="</tr>" ;
$output.="</table>" ;

print $output ;
?>