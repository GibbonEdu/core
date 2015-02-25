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

function getThemeVersion($themeName, $guid) {
	$return=FALSE ;
	
	
	$file=file($_SESSION[$guid]["absolutePath"] . "/themes/$themeName/manifest.php") ;
	foreach($file AS $fileEntry) {
		if (substr($fileEntry,1,7)=="version") {
			$temp="" ;
			$temp=substr($fileEntry,10,-1) ;
			$temp=substr($temp, 0, strpos($temp, "\"")) ;
			$return=$temp ;
		}
	}
	
	return $return ;
}


function getCurrentVersion($guid, $connection2, $version) {
	$output="" ;
	
	$output.="<script type=\"text/javascript\">" ;
		$output.="$(document).ready(function(){" ;
			$output.="$.ajax({" ;
				$output.="crossDomain: true, type:\"GET\", contentType: \"application/json; charset=utf-8\",async:false," ;
				$output.="url: \"https://gibbonedu.org/services/version/version.php?callback=?\"," ;
				$output.="data: \"\",dataType: \"jsonp\", jsonpCallback: 'fnsuccesscallback',jsonpResult: 'jsonpResult'," ;
				$output.="success: function(data) {" ;
					$output.="if (data['version']==='false') {" ;
						$output.="$(\"#status\").attr(\"class\",\"error\");" ;
						$output.="$(\"#status\").html('" . _('Version check failed') . ".') ;" ;
					$output.="}" ;
					$output.="else {" ;
						$output.="if (data['version']<='" . $version . "') {" ;
							$output.="$(\"#status\").attr(\"class\",\"success\");" ;
							$output.="$(\"#status\").html('" . sprintf(_('Version check successful. Your Gibbon installation is up to date at %1$s.'), $version) . " " . sprintf(_('If you have recently updated your system files, please check that your database is up to date in %1$sUpdates%2$s.'), "<a href=\'" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/System Admin/update.php\'>", "</a>") . "') ;" ;
						$output.="}" ;
						$output.="else {" ;
							$output.="$(\"#status\").attr(\"class\",\"warning\");" ;
							$output.="$(\"#status\").html('" . sprintf(_('Version check successful. Your Gibbon installation is out of date. Please visit %1$s to download the latest version.'), "<a target=\"blank\" href=\'http://gibbonedu.org/download\'>the Gibbon download page</a>") . "') ;" ;
						$output.="}" ;
					$output.="}" ;
				$output.="}," ;
				$output.="error: function (data, textStatus, errorThrown) {" ;
					$output.="$(\"#status\").attr(\"class\",\"error\");" ;
					$output.="$(\"#status\").html('" . _('Version check failed') . ".') ;" ;
				$output.="}" ;
			$output.="});" ;
		$output.="});" ;
	$output.="</script>" ;
	
	$cuttingEdgeCode=getSettingByScope( $connection2, "System", "cuttingEdgeCode" ) ;
	if ($cuttingEdgeCode!="Y") {
		$output.="<div id='status' class='warning'>" ;
			$output.="<div style='width: 100%; text-align: center'>" ;
				$output.="<img style='margin: 10px 0 5px 0' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/loading.gif' alt='Loading'/><br/>" ;
				$output.=_("Checking for Gibbon updates.") ;
			$output.="</div>" ;
		$output.="</div>" ;
	}
	
	return $output ;
}


?>