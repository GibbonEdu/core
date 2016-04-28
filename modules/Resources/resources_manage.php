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

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/Resources/resources_manage.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Get action with highest precendence
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print __($guid, "The highest grouped action cannot be determined.") ;
		print "</div>" ;
	}
	else {
		print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Manage Resources') . "</div>" ;
		print "</div>" ;
		
		if (isset($_GET["return"])) { returnProcess($guid, $_GET["return"], null, null); }
		
		//Set pagination variable
		$page=1 ; if (isset($_GET["page"])) { $page=$_GET["page"] ; }
		if ((!is_numeric($page)) OR $page<1) {
			$page=1 ;
		}
		
		$search=NULL ;
		if (isset($_GET["search"])) {
			$search=$_GET["search"] ;
		}
		
		print "<h2>" ;
		print __($guid, "Search") ;
		print "</h2>" ;
		?>
		<form method="get" action="<?php print $_SESSION[$guid]["absoluteURL"]?>/index.php">
			<table class='noIntBorder' cellspacing='0' style="width: 100%">	
				<tr><td style="width: 30%"></td><td></td></tr>
				<tr>
					<td> 
						<b><?php print __($guid, 'Search For') ?></b><br/>
						<span class="emphasis small"><?php print __($guid, 'Resource name.') ?></span>
					</td>
					<td class="right">
						<input name="search" id="search" maxlength=20 value="<?php print $search ?>" type="text" class="standardWidth">
					</td>
				</tr>
				<tr>
					<td colspan=2 class="right">
						<input type="hidden" name="q" value="/modules/<?php print $_SESSION[$guid]["module"] ?>/resources_manage.php">
						<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
						<?php
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/resources_manage.php'>" . __($guid, 'Clear Search') . "</a>" ;
						?>
						<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
					</td>
				</tr>
			</table>
		</form>
		<?php
		
		print "<h2>" ;
		print __($guid, "View") ;
		print "</h2>" ;
		
		
		try {
			if ($highestAction=="Manage Resources_all") {
				$data=array(); 
				$sql="SELECT gibbonResource.*, surname, preferredName, title FROM gibbonResource JOIN gibbonPerson ON (gibbonResource.gibbonPersonID=gibbonPerson.gibbonPersonID) ORDER BY timestamp DESC" ; 
				if ($search!="") {
					$data=array("name"=>"%$search%"); 
					$sql="SELECT gibbonResource.*, surname, preferredName, title FROM gibbonResource JOIN gibbonPerson ON (gibbonResource.gibbonPersonID=gibbonPerson.gibbonPersonID) AND (name LIKE :name) ORDER BY timestamp DESC" ; 
				}
			}
			else if ($highestAction=="Manage Resources_my") {
				$data=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
				$sql="SELECT gibbonResource.*, surname, preferredName, title FROM gibbonResource JOIN gibbonPerson ON (gibbonResource.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonResource.gibbonPersonID=:gibbonPersonID ORDER BY timestamp DESC" ; 
				if ($search!="") {
					$data=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"], "name"=>"%$search%"); 
					$sql="SELECT gibbonResource.*, surname, preferredName, title FROM gibbonResource JOIN gibbonPerson ON (gibbonResource.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonResource.gibbonPersonID=:gibbonPersonID AND (name LIKE :name) ORDER BY timestamp DESC" ; 
				}
			}
			$sqlPage=$sql . " LIMIT " . $_SESSION[$guid]["pagination"] . " OFFSET " . (($page-1)*$_SESSION[$guid]["pagination"]) ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		print "<div class='linkTop'>" ;
		print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/resources_manage_add.php&search=" . $search . "'>" .  __($guid, 'Add') . "<img style='margin-left: 5px' title='" . __($guid, 'Add') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.png'/></a>" ;
		print "</div>" ;
		
		if ($result->rowCount()<1) {
			print "<div class='error'>" ;
			print __($guid, "There are no records to display.") ;
			print "</div>" ;
		}
		else {
			if ($result->rowCount()>$_SESSION[$guid]["pagination"]) {
				printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]["pagination"], "top") ;
			}
		
			print "<table cellspacing='0' style='width: 100%'>" ;
				print "<tr class='head'>" ;
					print "<th>" ;
						print __($guid, "Name") . "<br/>";
						print "<span style='font-size: 85%; font-style: italic'>" . __($guid, 'Contributor') . "</span>" ;
					print "</th>" ;
					print "<th>" ;
						print __($guid, "Type") ;
					print "</th>" ;
					print "<th>" ;
						print __($guid, "Category") . "<br/>";
						print "<span style='font-size: 85%; font-style: italic'>" . __($guid, 'Purpose') . "</span>" ;
					print "</th>" ;
					print "<th>" ;
						print __($guid, "Tags") ;
					print "</th>" ;
					print "<th>" ;
						print __($guid, "Year Groups") ;
					print "</th>" ;
					print "<th>" ;
						print __($guid, "Actions") ;
					print "</th>" ;
				print "</tr>" ;
				
				$count=0;
				$rowNum="odd" ;
				try {
					$resultPage=$connection2->prepare($sqlPage);
					$resultPage->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				while ($row=$resultPage->fetch()) {
					if ($count%2==0) {
						$rowNum="even" ;
					}
					else {
						$rowNum="odd" ;
					}
					$count++ ;
					
					//COLOR ROW BY STATUS!
					print "<tr class=$rowNum>" ;
						print "<td>" ;
							print getResourceLink($guid, $row["gibbonResourceID"], $row["type"], $row["name"], $row["content"]) ;
							print "<span style='font-size: 85%; font-style: italic'>" . formatName($row["title"], $row["preferredName"], $row["surname"], "Staff") . "</span>" ;
						print "</td>" ;
						print "<td>" ;
							print $row["type"] ;
						print "</td>" ;
						print "<td>" ;
							print "<b>" . $row["category"] . "</b><br/>" ;
							print "<span style='font-size: 85%; font-style: italic'>" . $row["purpose"] . "</span>" ;
						print "</td>" ;
						print "</td>" ;
						print "<td>" ;
							$output="" ;
							$tags=explode(",", $row["tags"]) ;
							natcasesort($tags) ;
							foreach ($tags AS $tag) {
								$output.=substr(trim($tag),1,-1) . ", " ;
							}
							print substr($output,0,-2) ;
						print "</td>" ;
						print "<td>" ;
							try {
								$dataYears=array(); 
								$sqlYears="SELECT gibbonYearGroupID, nameShort, sequenceNumber FROM gibbonYearGroup ORDER BY sequenceNumber" ;
								$resultYears=$connection2->prepare($sqlYears);
								$resultYears->execute($dataYears);
							}
							catch(PDOException $e) { 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							}
							
							$years=explode(",", $row["gibbonYearGroupIDList"]) ;
							if (count($years)>0 AND $years[0]!="") {
								if (count($years)==$resultYears->rowCount()) {
									print "<i>" . __($guid, 'All Years') . "</i>" ;
								}
								else {
									$count3=0 ;
									$count4=0 ;
									while ($rowYears=$resultYears->fetch()) {
										for ($i=0; $i<count($years); $i++) {
											if ($rowYears["gibbonYearGroupID"]==$years[$i]) {
												if ($count3>0 AND $count4>0) {
													print ", " ;
												}
												print __($guid, $rowYears["nameShort"]) ;
												$count4++ ;
											}
										}
										$count3++ ;
									}
								}
							}
							else {
								print "<i>" . __($guid, 'None') . "</i>" ;
							}
						print "</td>" ;
						print "<td>" ;
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/resources_manage_edit.php&gibbonResourceID=" . $row["gibbonResourceID"] . "&search=$search'><img title='" . __($guid, 'Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
						print "</td>" ;
					print "</tr>" ;
				}
			print "</table>" ;
			
			if ($result->rowCount()>$_SESSION[$guid]["pagination"]) {
				printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]["pagination"], "bottom") ;
			}
		}
	}
}	
?>