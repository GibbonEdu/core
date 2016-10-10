<?php
/**
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

namespace Module\Resources\Functions ;

use Gibbon\core\moduleFunctions as mFBase;
use Gibbon\core\helper ;
use Gibbon\core\trans ;

/**
 * module Functions
 *
 * @version	21st April 2016
 * @since	21st April 2016
 */
class functions extends mFBase
{

	use \Gibbon\core\functions\arrayFunctions ;
	
	function getTagCloud($tagCount=50) {
		$output="" ;
		
		//Get array of top $tagCount tags
		$tags=array() ;
		$count=0 ;
		$max_count=0 ;
		$min_count=0 ;
		

		$sql="SELECT * FROM `gibbonResourceTag` ORDER BY count DESC LIMIT $tagCount" ;
		$data=array() ;
		$result = $this->pdo->executeQuery($data, $sql);
		if ($result->rowCount()>0) {
			while ($row=$result->fetch()) {
				if ($count==0) {
					$max_count=$row["count"] ;
					$min_count=$row["count"] ;
				}
				else {
					if ($row["count"]<$min_count) {
						$min_count=$row["count"] ;
					}
				}
				$tags[$count][0]=$row["tag"] ;
				$tags[$count][1]=$row["count"] ;
				
				$count++ ;
			}
			
			$tags = $this->msort($tags, 0, true) ;
		
			$min_font_size=16;
			$max_font_size=30;
			
			$spread=$max_count - $min_count;
			if($spread==0) {
				$spread=1;
			}
			
			
			$cloud_html='';
			$cloud_tags=array();
			for ($i=0; $i<count($tags); $i++) {
				$tag=$tags[$i][0] ;
				$count=$tags[$i][1] ;
				$size=$min_font_size + ($count - $min_count) * ($max_font_size - $min_font_size) / $spread;
				$cloud_tags[]="<a style='font-size: " . floor($size) . "px' class='tag_cloud' href='" . $this->session->get("absoluteURL") . "/index.php?q=/modules/Resources/resources_view.php&tag=" . str_replace("&", "%26", $tag) . "' title='$count resources'>" . htmlspecialchars(stripslashes($tag)) . "</a>" ;
			}
			$output.="<p style='margin-top: 10px; line-height: 220%'>" ;
			$output.=join("\n", $cloud_tags) . "\n";
			$output.="</p>" ;
		}
		else {
			$output.="<div class='warning'>" ;
				$output.="There are no resources in the system." ;
			$output.="</div>" ;
		}
		
		
		return $output ;
	}
	
	function sidebarExtra() {
		$output="" ;
		$output.="<h2>" ;
			$output.= trans::__( "Resource Tags") ;
		$output.="</h2>" ;
		$output.= $this->getTagCloud() ;
		return $output ;
	}
	
	function getResourceLink($gibbonResourceID, $type, $name, $content) {
		$output=FALSE ;
		
		if ($type=="Link") {
			$output="<a target='_blank' style='font-weight: bold' href='" . $content . "'>" . $name . "</a><br/>" ;
		}
		else if ($type=="File") {
			$output="<a target='_blank' style='font-weight: bold' href='" . $this->session->get("absoluteURL") . "/" . $content . "'>" . $name . "</a><br/>" ;
		}
		else if ($type=="HTML") {
			$output="<a style='font-weight: bold' class='thickbox' href='" . $this->session->get("absoluteURL") . "/fullscreen.php?q=/modules/Resources/resources_view_full.php&gibbonResourceID=" . $gibbonResourceID . "&width=1000&height=550'>" . $name . "</a><br/>" ;
		}
		
		return $output ;
	}
}
?>