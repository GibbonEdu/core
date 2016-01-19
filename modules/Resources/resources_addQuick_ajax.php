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
			$output.="$(\"." .$id . "resourceQuickSlider\").html(\"<div class='resourceAddSlider'><img style='margin: 10px 0 5px 0' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/loading.gif' alt='" . _('Uploading') . "' onclick='return false;' /><br/>" . _('Loading') . "</div>\");" ;
			$output.="return false;" ; 
		$output.="});" ; 
	$output.="});" ; 
	
	$output.="var formReset=function() {" ;
		$output.="$('#" . $id . "resourceQuick').css('display','none');" ;
	$output.="};" ;
$output.="</script>" ;

$output.="<table cellspacing='0' style='width: 100%'>" ;	
	$output.="<tr id='" . $id . "resourceQuick'>" ;
		$output.="<td colspan=2 style='border: none; padding-top: 0px'>" ; 
			$output.="<div style='margin: 0px' class='linkTop'><a href='javascript:void(0)' onclick='formReset(); \$(\"." .$id . "resourceQuickSlider\").slideUp();'><img title='" . _('Close') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconCross.png'/></a></div>" ;
			$output.="<form id='" . $id . "ajaxForm'>" ;
				$output.="<table cellspacing='0' style='border: none; width: 100%'>" ;
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
							
					//Produce 4 file input boxes
					for ($i=1; $i<5; $i++) {
						$output.="<tr id='" . $id . "resourceFile'>" ;
							$output.="<td>" ;
								$output.="<b>" . sprintf(_('File %1$s'), $i) . "</b><br/>" ;
							$output.="</td>" ;
							$output.="<td class='right'>" ;
								$output.="<input type='file' name='" . $id . "file" . $i . "' id='" . $id . "file" . $i . "' style='max-width: 235px'><br/><br/>" ;
								$output.="<script type='text/javascript'>" ;
								$output.="var " . $id . "file" . $i . "=new LiveValidation('" . $id . "file" . $i . "');" ;
									$output.=$id . "file" . $i . ".add( Validate.Inclusion, { within: [" . $ext . "], failureMessage: 'Illegal file type!', partialMatch: true, caseSensitive: false } );" ;
								$output.="</script>" ;
							$output.="</td>" ;
						$output.="</tr>" ;
					}
					
					$output.="<tr>" ;
						$output.="<td>" ;
							$output.="<b>" . _("Insert Images As") . "*</b><br/>" ;
						$output.="</td>" ;
						$output.="<td class=\"right\">" ;
							$output.="<select name=\"imagesAsLinks\" id=\"imagesAsLinks\" style=\"width: 302px\">" ;
								$output.="<option value='N'>" . _('Image') . "</option>" ;		
								$output.="<option value='Y'>" . _('Link') . "</option>" ;	
							$output.="</select>" ;
						$output.="</td>" ;
					$output.="</tr>" ;
					
					$output.="<tr>" ;
						$output.="<td>" ;
							$output.=getMaxUpload(TRUE) ;
						$output.="</td>" ;
						$output.="<td class='right'>" ;
							$output.="<input type='hidden' name='id' value='" . $id . "'>" ;
							$output.="<input type='hidden' name='" . $id . "address' value='" . $_SESSION[$guid]["address"] . "'>" ;
							$output.="<input type='submit' value='Submit'>" ;
						$output.="</td>" ;
					$output.="</tr>" ;
				
				
				$output.="</table>" ;
			$output.="</form>" ;
		$output.="</td>" ; 
	$output.="</tr>" ;
$output.="</table>" ;

print $output ;
?>