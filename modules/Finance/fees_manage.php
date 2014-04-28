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

if (isActionAccessible($guid, $connection2, "/modules/Finance/fees_manage.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>Manage Fees</div>" ;
	print "</div>" ;
	
	print "<p>" ;
	print "In this area you can create the various fee options which apply to students. Fees are specific to a school year, cannot be deleted and must be linked to a category. When you come to create invoices later on, you will be able to use these fees, as well as ad hoc charges." ;
	print "</p>" ;
	
	$gibbonSchoolYearID="" ;
	if (isset($_GET["gibbonSchoolYearID"])) {
		$gibbonSchoolYearID=$_GET["gibbonSchoolYearID"] ;
	}
	if ($gibbonSchoolYearID=="" OR $gibbonSchoolYearID==$_SESSION[$guid]["gibbonSchoolYearID"]) {
		$gibbonSchoolYearID=$_SESSION[$guid]["gibbonSchoolYearID"] ;
		$gibbonSchoolYearName=$_SESSION[$guid]["gibbonSchoolYearName"] ;
	}
	
	if ($gibbonSchoolYearID!=$_SESSION[$guid]["gibbonSchoolYearID"]) {
		try {
			$data=array("gibbonSchoolYearID"=>$_GET["gibbonSchoolYearID"]); 
			$sql="SELECT * FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		if ($result->rowcount()!=1) {
			print "<div class='error'>" ;
				print _("The specified record does not exist.") ;
			print "</div>" ;
		}
		else {
			$row=$result->fetch() ;
			$gibbonSchoolYearID=$row["gibbonSchoolYearID"] ;
			$gibbonSchoolYearName=$row["name"] ;
		}
	}
	
	if ($gibbonSchoolYearID!="") {
		print "<h2>" ;
			print $gibbonSchoolYearName ;
		print "</h2>" ;
		
		print "<div class='linkTop'>" ;
			//Print year picker
			if (getPreviousSchoolYearID($gibbonSchoolYearID, $connection2)!=FALSE) {
				print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/fees_manage.php&gibbonSchoolYearID=" . getPreviousSchoolYearID($gibbonSchoolYearID, $connection2) . "'>" . _('Previous Year') . "</a> " ;
			}
			else {
				print _("Previous Year") . " " ;
			}
			print " | " ;
			if (getNextSchoolYearID($gibbonSchoolYearID, $connection2)!=FALSE) {
				print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/fees_manage.php&gibbonSchoolYearID=" . getNextSchoolYearID($gibbonSchoolYearID, $connection2) . "'>" . _('Next Year') . "</a> " ;
			}
			else {
				print _("Next Year") . " " ;
			}
		print "</div>" ;
	
		print "<h3>" ;
		print "Search" ;
		print "</h3>" ;
		?>
		<form method="get" action="<?php print $_SESSION[$guid]["absoluteURL"]?>/index.php">
			<table class='noIntBorder' cellspacing='0' style="width: 100%">	
				<tr><td style="width: 30%"></td><td></td></tr>
				<tr>
					<td> 
						<b><?php print _('Search For') ?></b><br/>
						<span style="font-size: 90%"><i>Fee name, category name.</i></span>
					</td>
					<td class="right">
						<input name="search" id="search" maxlength=20 value="<?php if (isset($_GET["search"])) { print $_GET["search"] ; } ?>" type="text" style="width: 300px">
					</td>
				</tr>
				<tr>
					<td colspan=2 class="right">
						<input type="hidden" name="q" value="/modules/<?php print $_SESSION[$guid]["module"] ?>/fees_manage.php">
						<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
						<?php
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/fees_manage.php'>" . _('Clear Search') . "</a>" ;
						?>
						<input type="submit" value="<?php print _("Submit") ; ?>">
					</td>
				</tr>
			</table>
		</form>
		<?php
		
		print "<h3>" ;
		print "View" ;
		print "</h3>" ;
		//Set pagination variable
		$page=1 ; if (isset($_GET["page"])) { $page=$_GET["page"] ; }
		if ((!is_numeric($page)) OR $page<1) {
			$page=1 ;
		}
		
		$search=NULL ;
		if (isset($_GET["search"])) {
			$search=$_GET["search"] ;
		}
		try {
			$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID); 
			$sql="SELECT gibbonFinanceFee.*, gibbonFinanceFeeCategory.name AS category FROM gibbonFinanceFee LEFT JOIN gibbonFinanceFeeCategory ON (gibbonFinanceFee.gibbonFinanceFeeCategoryID=gibbonFinanceFeeCategory.gibbonFinanceFeeCategoryID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY name" ; 
			if ($search!="") {
				$data=array("gibbonSchoolYearID"=>$gibbonSchoolYearID, "search1"=>"%$search%", "search2"=>"%$search%"); 
				$sql="SELECT gibbonFinanceFee.*, gibbonFinanceFeeCategory.name AS category FROM gibbonFinanceFee LEFT JOIN gibbonFinanceFeeCategory ON (gibbonFinanceFee.gibbonFinanceFeeCategoryID=gibbonFinanceFeeCategory.gibbonFinanceFeeCategoryID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND (gibbonFinanceFee.name LIKE :search1 OR gibbonFinanceFeeCategory.name LIKE :search2) ORDER BY name" ; 
			}
			$sqlPage=$sql . " LIMIT " . $_SESSION[$guid]["pagination"] . " OFFSET " . (($page-1)*$_SESSION[$guid]["pagination"]) ; 
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		print "<div class='linkTop'>" ;
			print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/fees_manage_add.php&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search'><img title='" . _('Add New Record') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.gif'/></a>" ;
		print "</div>" ;
	
		if ($result->rowCount()<1) {
			print "<div class='error'>" ;
			print _("There are no records to display.") ;
			print "</div>" ;
		}
		else {
			if ($result->rowCount()>$_SESSION[$guid]["pagination"]) {
				printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]["pagination"], "top", "gibbonSchoolYearID=$gibbonSchoolYearID&search=$search") ;
			}
		
			print "<table cellspacing='0' style='width: 100%'>" ;
				print "<tr class='head'>" ;
					print "<th>" ;
						print "Name<br/><span style='font-style: italic; font-size: 85%'>Short Name</span>" ;
					print "</th>" ;
					print "<th>" ;
						print "Category" ;
					print "</th>" ;
					print "<th>" ;
						print "Fee<br/><span style='font-style: italic; font-size: 85%'>" . $_SESSION[$guid]["currency"] . "</span>" ;
					print "</th>" ;
					print "<th>" ;
						print _("Actions") ;
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
					
					//Color rows based on start and end date
					if ($row["active"]!="Y") {
						$rowNum="error" ;
					}
					
					print "<tr class=$rowNum>" ;
						print "<td>" ;
							print "<b>" . $row["name"] . "</b><br/>" ;
							print "<span style='font-style: italic; font-size: 85%'>" . $row["nameShort"]  . "</span>" ;
						print "</td>" ;
						print "<td>" ;
							print $row["category"] ;
						print "</td>" ;
						print "<td>" ;
							if (substr($_SESSION[$guid]["currency"],4)!="") {
								print substr($_SESSION[$guid]["currency"],4) . " " ;
							}
							print number_format($row["fee"], 2, ".", ",") ;
						print "</td>" ;
						print "<td>" ;
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/fees_manage_edit.php&gibbonFinanceFeeID=" . $row["gibbonFinanceFeeID"] . "&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search'><img title='" . _('Edit Record') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
							print "<script type='text/javascript'>" ;	
								print "$(document).ready(function(){" ;
									print "\$(\".comment-$count-$count\").hide();" ;
									print "\$(\".show_hide-$count-$count\").fadeIn(1000);" ;
									print "\$(\".show_hide-$count-$count\").click(function(){" ;
									print "\$(\".comment-$count-$count\").fadeToggle(1000);" ;
									print "});" ;
								print "});" ;
							print "</script>" ;
							if ($row["description"]!="") {
								print "<a title='" . _('View Description') . "' class='show_hide-$count-$count' onclick='false' href='#'><img style='padding-right: 5px' src='" . $_SESSION[$guid]["absoluteURL"] . "/themes/Default/img/page_down.png' alt='" . _('Show Comment') . "' onclick='return false;' /></a>" ;
							}
						print "</td>" ;
					print "</tr>" ;
					if ($row["description"]!="") {
						print "<tr class='comment-$count-$count' id='comment-$count-$count'>" ;
							print "<td colspan=6>" ;
								print $row["description"] ;
							print "</td>" ;
						print "</tr>" ;
					}
				}
			print "</table>" ;
			
			if ($result->rowCount()>$_SESSION[$guid]["pagination"]) {
				printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]["pagination"], "bottom", "gibbonSchoolYearID=$gibbonSchoolYearID&search=$search") ;
			}
		}
	}
}
?>