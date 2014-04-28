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

include "../../functions.php" ;
include "../../config.php" ;

include "./moduleFunctions.php" ;

@session_start() ;

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]["timezone"]);

$location=$_POST["location"] ;
$count=$_POST["count"] ;

if ($location!="") {
	$site=file_get_contents($location);
	if (strstr($site, "<meta name=\"generator\" content=\"WordPress") OR strstr($site, "wp-content")) {
		$action="" ;
		$doc=new DOMDocument();
		@$doc->loadHtml($site);
		
		//Get form action
		$selector=new DOMXpath($doc);
		$results=$selector->query('//form');
		foreach ($results as $result) {
			if (strstr($result->getAttribute('action'), "wp-comments-post.php")) {
				$action=$result->getAttribute('action') ;
				break ;
			}
		}
	
		//Get post ID
		$id="" ;
		$doc->preserveWhiteSpace=FALSE;
		@$doc->loadXml($xhtml);
		foreach( $doc->getElementsByTagName('link') as $node ) {
			if ($node->getAttribute('rel')=="shortlink") {
				$id=$node->getAttribute('href') ;
				$id=substr($id,(strpos($id, "?p=")+3)) ;
				break ;
			}
		}
	
		if ($action!="" AND $id!="") {
			print " <input name='$count-wordpressCommentPush' id='$count-wordpressCommentPush' type='checkbox' value='$id-$action'>" ;
		}	
	}
}

?>