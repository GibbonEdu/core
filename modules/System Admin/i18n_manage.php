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

if (isActionAccessible($guid, $connection2, "/modules/System Admin/i18n_manage.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Language Settings') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
	$updateReturnMessage="" ;
	$class="error" ;
	if (!($updateReturn=="")) {
		if ($updateReturn=="fail0") {
			$updateReturnMessage=__($guid, "Your request failed because you do not have access to this action.") ;	
		}
		else if ($updateReturn=="fail1") {
			$updateReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
		}
		else if ($updateReturn=="fail2") {
			$updateReturnMessage=__($guid, "Your request failed due to a database error.") ;	
		}
		else if ($updateReturn=="success0") {
			$updateReturnMessage=__($guid, "Your request was completed successfully.") ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $updateReturnMessage;
		print "</div>" ;
	} 
	
	try {
		$data=array(); 
		$sql="SELECT * FROM gibboni18n ORDER BY name" ; 
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}
	
	print "<p>" ;
		print __($guid, "Inactive languages are not yet ready for use within the system as they are still under development. They cannot be set to default, nor selected by users.") ;
	print "</p>" ;
	
	if ($result->rowCount()<1) {
		print "<div class='error'>" ;
		print __($guid, "There are no records to display.") ;
		print "</div>" ;
	}
	else {
		print "<form method='post' action='" . $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/i18n_manageProcess.php'>" ;
			print "<table cellspacing='0' style='width: 100%'>" ;
				print "<tr class='head'>" ;
					print "<th>" ;
						print __($guid, "Name") ;
					print "</th>" ;
					print "<th>" ;
						print __($guid, "Code") ;
					print "</th>" ;
					print "<th>" ;
						print __($guid, "Active") ;
					print "</th>" ;
					print "<th>" ;
						print __($guid, "Maintainer") ;
					print "</th>" ;
					print "<th>" ;
						print __($guid, "Default") ;
					print "</th>" ;
				print "</tr>" ;
				
				$count=0;
				$rowNum="odd" ;
				while ($row=$result->fetch()) {
					if ($count%2==0) {
						$rowNum="even" ;
					}
					else {
						$rowNum="odd" ;
					}
					$count++ ;
					
					if ($row["active"]=="N") {
						$rowNum="error" ;
					}
					
					//COLOR ROW BY STATUS!
					print "<tr class=$rowNum>" ;
						print "<td>" ;
							print "<b>" . $row["name"] . "<b/>" ;
						print "</td>" ;
						print "<td>" ;
							print $row["code"] ;
						print "</td>" ;
						print "<td>" ;
							print ynExpander($guid, $row["active"]) ;
						print "</td>" ;
						print "<td>" ;
							if ($row["maintainerWebsite"]!="") {
								print "<a href='" . $row["maintainerWebsite"] . "'>" . $row["maintainerName"] . "</a>" ;
							}
							else {
								print $row["maintainerName"] ;
							}
						print "</td>" ;
						print "<td>" ;
							if ($row["active"]=="Y") {
								if ($row["systemDefault"]=="Y") {
									print "<input checked type='radio' name='gibboni18nID' value='" . $row["gibboni18nID"] . "'>" ;
								}
								else {
									print "<input type='radio' name='gibboni18nID' value='" . $row["gibboni18nID"] . "'>" ;
								}
							}
						print "</td>" ;
					print "</tr>" ;
				}
				print "<tr>" ;
					print "<td colspan=6 class='right'>" ;
						?>
							<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
							<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
						<?php
					print "</td>" ;
				print "</tr>" ;
			print "</table>" ;
			
		print "</form>" ;
	}
}
?>