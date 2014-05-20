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

if (isActionAccessible($guid, $connection2, "/modules/Library/library_manage_catalog_add.php")==FALSE) {
	//Acess denied
	$output.="<div class='error'>" ;
		$output.=_("Your request failed because you do not have access to this action.") ;
	$output.="</div>" ;
}
else {
	try {
		$data=array("gibbonLibraryTypeID"=>$id); 
		$sql="SELECT * FROM gibbonLibraryType WHERE gibbonLibraryTypeID=:gibbonLibraryTypeID AND active='Y' ORDER BY name" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { }
	
	if ($result->rowCount()!=1) {
		$output.="<div class='error'>" ;
			$output.=_("The specified recod cannot be found.") ;
		$output.="</div>" ;
	}
	else {
		$row=$result->fetch() ;
		
		//Add Google Books data grabber
		if ($row["name"]=="Print Publication") {
			print "<script type='text/javascript'>" ;
				print "function stopRKey(evt) {" ;
					print "var evt=(evt) ? evt : ((event) ? event : null); var node=(evt.target) ? evt.target : ((evt.srcElement) ? evt.srcElement : null); if ((evt.keyCode==13) && (node.type==\"text\"))  {return false;}" ;
				print "}" ;
				print "document.onkeypress=stopRKey;" ; 	
				
				print "$(document).ready(function(){" ;
					print "\$(\".gbooks\").click(function(){" ;
						print "var isbn=$(\"#fieldISBN13\").val() ;" ;
						print "if ($(\"#fieldISBN10\").val()) {" ;
							print "isbn=$(\"#fieldISBN10\").val()" ;
						print "}" ;
						print "if (isbn) {" ;
							print "$.get((\"https://www.googleapis.com/books/v1/volumes?q=isbn:\" + isbn), function(data){" ;
								print "if(data.constructor===String){" ;
									print "var obj=jQuery.parseJSON(data);" ;
								print "} else {" ;
									print "var obj=data;" ;
								print "}" ;
								print "if (obj['totalItems']==0) {" ;
									print "alert('" . _('The specified record cannot be found.') . "');" ;
								print "} else {" ;
									//SET FIELDS
									print "$(\"#name\").val(obj['items'][0]['volumeInfo']['title']);" ;
									print "var authors='';" ;
									print "for (var i=0; i < obj['items'][0]['volumeInfo']['authors'].length; i++) {" ;
    									print "authors=authors + obj['items'][0]['volumeInfo']['authors'][i] + ', ';" ;
									print "}" ;
									print "$(\"#producer\").val(authors.substring(0,(authors.length-2)));" ;
									print "$(\"#fieldPublisher\").val(obj['items'][0]['volumeInfo']['publisher']);" ;
									print "if (obj['items'][0]['volumeInfo']['publishedDate'].length==10) {" ;
										print "$(\"#fieldPublicationDate\").val(obj['items'][0]['volumeInfo']['publishedDate'].substring(8,10)+'/'+obj['items'][0]['volumeInfo']['publishedDate'].substring(5,7)+'/'+obj['items'][0]['volumeInfo']['publishedDate'].substring(0,4));" ;
									print "} else if (obj['items'][0]['volumeInfo']['publishedDate'].length==7) {" ;
										print "$(\"#fieldPublicationDate\").val(obj['items'][0]['volumeInfo']['publishedDate'].substring(5,7)+'/'+obj['items'][0]['volumeInfo']['publishedDate'].substring(0,4));" ;
									print "} else if (obj['items'][0]['volumeInfo']['publishedDate'].length==4) {" ;
										print "$(\"#fieldPublicationDate\").val(obj['items'][0]['volumeInfo']['publishedDate'].substring(0,4));" ;
									print "}" ;
									print "$(\"#fieldDescription\").val(obj['items'][0]['volumeInfo']['description']);" ;
									print "if (obj['items'][0]['volumeInfo']['industryIdentifiers'][0]['type']=='ISBN_10') {" ;
										print "$(\"#fieldISBN10\").val(obj['items'][0]['volumeInfo']['industryIdentifiers'][0]['identifier']);" ;
									print "}" ;
									print "if (obj['items'][0]['volumeInfo']['industryIdentifiers'][1]['type']=='ISBN_13') {" ;
										print "$(\"#fieldISBN13\").val(obj['items'][0]['volumeInfo']['industryIdentifiers'][1]['identifier']);" ;
									print "}" ;
									print "$(\"#fieldPageCount\").val(obj['items'][0]['volumeInfo']['pageCount']);" ;
									print "var format=obj['items'][0]['volumeInfo']['printType'].toLowerCase() ;" ;
									print "format=format.charAt(0).toUpperCase() + format.slice(1);" ;
									print "$(\"#fieldFormat\").val(format);" ;
									print "$(\"#fieldLink\").val(obj['items'][0]['volumeInfo']['infoLink']);" ;
									print "var image=obj['items'][0]['volumeInfo']['imageLinks']['thumbnail'];" ;
									print "if (image) {" ;
										print "$(\"#imageType\").val('Link');" ;
										print "$(\"#imageLinkRow\").slideDown(\"fast\", $(\"#imageLinkRow\").css(\"display\",\"table-row\"));" ;
										print "$(\"#imageLink\").enable();" ;
										print "$(\"#imageLink\").val(image);" ;
									print "}" ;
									print "$(\"#fieldLanguage\").val(obj['items'][0]['volumeInfo']['language']);" ;
									print "var subjects='';" ;
									print "for (var i=0; i < obj['items'][0]['volumeInfo']['categories'].length; i++) {" ;
    									print "subjects=subjects + obj['items'][0]['volumeInfo']['categories'][i] + ', ';" ;
									print "}" ;
									print "$(\"#fieldSubjects\").val(subjects.substring(0,(subjects.length-2)));" ;
								print "}" ;
							print "});" ;
						print "} else {" ;
							print "alert('" . _('Please enter an ISBN13 or ISBN10 value before trying to get data from Google Books.') . "') ;" ;
						print "}" ;
					print "});" ;
				print "});" ;
			print "</script>" ;
			print "<div style='text-align: right'>" ;
				print "<a class='gbooks' onclick='return false' href='#'>" . _('Get Book Data From Google') . "</a>" ;
			print "</div>" ;
		}
		
		//Create fields
		$fields=unserialize($row["fields"]) ;
		$output.="<table cellspacing='0' style='text-align: left; width: 100%'>" ;
			foreach ($fields as $field) {
				$fieldName=preg_replace("/ /", "", $field["name"]) ;
				$output.="<tr>" ;
					$output.="<td> " ;
						$output.="<b>" . _($field["name"]) . "</b>" ;
						if ($field["required"]=="Y") {
							$output.=" *" ;
						}
						$output.="<br/><span style='font-size: 90%'><i>" . str_replace("dd/mm/yyyy", $_SESSION[$guid]["i18n"]["dateFormat"], $field["description"]) . "</i></span>" ;
					$output.="</td>" ;
					$output.="<td class='right'>" ;
						if ($field["type"]=="Text") {
							$output.="<input maxlength='" . $field["options"] . "' name='field" . $fieldName . "' id='field" . $fieldName . "' value='" . htmlPrep($field["default"]) . "' type='text' style='width: 300px'>" ;
						}
						else if ($field["type"]=="Select") {
							$output.="<select name='field" . $fieldName . "' id='field" . $fieldName . "' type='text' style='width: 300px'>" ;
								if ($field["required"]=="Y") {
									$output.="<option value='Please select...'>Please select...</option>" ;
								}
								$options=explode(",", $field["options"]) ;
								foreach ($options as $option) {
									$option=trim($option) ;
									$selected="" ;
									if ($option==$field["default"]) {
										$selected="selected" ;
									}
									$output.="<option $selected value='$option'>$option</option>" ;
								}
							$output.="</select>" ;
						}
						else if ($field["type"]=="Textarea") {
							$output.="<textarea rows='" . $field["options"] . "' name='field" . $fieldName . "' id='field" . $fieldName . "' style='width: 300px'>" . htmlPrep($field["default"]) . "</textarea>" ;
						}
						else if ($field["type"]=="Date") {
							$output.="<input name='field" . $fieldName . "' id='field" . $fieldName . "' maxlength=10 value='' type='text' style='width: 300px'>" ;
							$output.="<script type='text/javascript'>" ;
								$output.="var field" . $fieldName . "=new LiveValidation('field" . $fieldName . "');" ;
								$output.="field" . $fieldName . ".add( Validate.Format, {pattern:" ; if ($_SESSION[$guid]["i18n"]["dateFormatRegEx"]=="") {  $output.="/^(0[1-9]|[12][0-9]|3[01])[- /.](0[1-9]|1[012])[- /.](19|20)\d\d$/i" ; } else { $output.=$_SESSION[$guid]["i18n"]["dateFormatRegEx"] ; } $output.=", failureMessage: 'Use " . $_SESSION[$guid]["i18n"]["dateFormat"] . ".' } );" ; 
							$output.="</script>" ;
							$output.="<script type='text/javascript'>" ;
								$output.="$(function() {" ;
									$output.="$( '#field" . $fieldName . "' ).datepicker();" ;
								$output.="});" ;
							$output.="</script>" ;
						}
						else if ($field["type"]=="URL") {
							$output.="<input maxlength='" . $field["options"] . "' name='field" . $fieldName . "' id='field" . $fieldName . "' value='" . htmlPrep($field["default"]) . "' type='text' style='width: 300px'>" ;
							$output.="<script type='text/javascript'>" ;
								$output.="var field" . $fieldName . "=new LiveValidation('field" . $fieldName . "');" ;
								$output.="field" . $fieldName . ".add( Validate.Format, { pattern: /(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/, failureMessage: \"Must start with http://\" } );" ;
							$output.="</script>" ;
						}
					$output.="</td>" ;
				$output.="</tr>" ;
				//NEED LIVE VALIDATION
				if ($field["required"]=="Y") {
					if ($field["type"]=="Text" OR $field["type"]=="Textarea" OR $field["type"]=="Date" OR $field["type"]=="URL") {
						$output.="<script type='text/javascript'>" ;
							$output.="var field" . $fieldName . "=new LiveValidation('field" . $fieldName . "');" ;
							$output.="field" . $fieldName . ".add(Validate.Presence);" ;
						$output.="</script>" ;
					}
					else if ($field["type"]=="Select") {
						$output.="<script type='text/javascript'>" ;
							$output.="var field" . $fieldName . "=new LiveValidation('field" . $fieldName . "');" ;
							$output.="field" . $fieldName . ".add(Validate.Exclusion, { within: ['Please select...'], failureMessage: 'Select something!'});" ;
						$output.="</script>" ;
					}
				}
			}
		$output.="</table>" ;
		
		$output.="<script type='text/javascript'>" ;
			$output.="$(document).ready(function(){" ;
				$output.="$('#type').change(function(){" ;
					foreach ($fields as $field) {
						if ($field["required"]=="Y") {
							$fieldName=preg_replace("/ /", "", $field["name"]) ;
							$output.="field" . $fieldName . ".disable() ;" ;
						}
					}
				$output.="})" ;
			$output.="});" ;
		$output.="</script>" ;
	}
}

print $output ;
?>