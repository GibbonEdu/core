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


if (isActionAccessible($guid, $connection2, "/modules/User Admin/data_family_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/User Admin/data_family.php'>" . __($guid, 'Family Data Updates') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Edit Request') . "</div>" ;
	print "</div>" ;
	
	//Check if school year specified
	$gibbonFamilyUpdateID=$_GET["gibbonFamilyUpdateID"];
	if ($gibbonFamilyUpdateID=="Y") {
		print "<div class='error'>" ;
			print __($guid, "You have not specified one or more required parameters.") ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonFamilyUpdateID"=>$gibbonFamilyUpdateID); 
			$sql="SELECT gibbonFamilyUpdate.gibbonFamilyID, gibbonFamily.name AS name, gibbonFamily.nameAddress AS nameAddress, gibbonFamily.homeAddress AS homeAddress, gibbonFamily.homeAddressDistrict AS homeAddressDistrict, gibbonFamily.homeAddressCountry AS homeAddressCountry, gibbonFamily.languageHomePrimary AS languageHomePrimary, gibbonFamily.languageHomeSecondary AS languageHomeSecondary, gibbonFamilyUpdate.nameAddress AS newnameAddress, gibbonFamilyUpdate.homeAddress AS newhomeAddress, gibbonFamilyUpdate.homeAddressDistrict AS newhomeAddressDistrict, gibbonFamilyUpdate.homeAddressCountry AS newhomeAddressCountry, gibbonFamilyUpdate.languageHomePrimary AS newlanguageHomePrimary, gibbonFamilyUpdate.languageHomeSecondary AS newlanguageHomeSecondary FROM gibbonFamilyUpdate JOIN gibbonFamily ON (gibbonFamilyUpdate.gibbonFamilyID=gibbonFamily.gibbonFamilyID) WHERE gibbonFamilyUpdateID=:gibbonFamilyUpdateID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}

		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print __($guid, "The selected record does not exist, or you do not have access to it.") ;
			print "</div>" ;
		}
		else {
			if (isset($_GET["return"])) { returnProcess($guid, $_GET["return"], null, null); }

			//Let's go!
			$row=$result->fetch() ;
			?>
			<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/data_family_editProcess.php?gibbonFamilyUpdateID=$gibbonFamilyUpdateID" ?>">
				<?php
				print "<table cellspacing='0' style='width: 100%'>" ;
					print "<tr class='head'>" ;
						print "<th>" ;
							print __($guid, "Field") ;
						print "</th>" ;
						print "<th>" ;
							print __($guid, "Current Value") ;
						print "</th>" ;
						print "<th>" ;
							print __($guid, "New Value") ;
						print "</th>" ;
						print "<th>" ;
							print __($guid, "Accept") ;
						print "</th>" ;
					print "</tr>" ;
					
					$rowNum="even" ;
						
					//COLOR ROW BY STATUS!
					print "<tr class='odd'>" ;
						print "<td>" ;
							print __($guid, "Address Name") ;
						print "</td>" ;
						print "<td>" ;
							print $row["nameAddress"] ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($row["nameAddress"]!=$row["newnameAddress"]) {
								$style="style='color: #ff0000'" ;
							}
							print "<span $style>" ;
							print $row["newnameAddress"] ;
						print "</td>" ;
						print "<td>" ;
							if ($row["nameAddress"]!=$row["newnameAddress"]) { print "<input checked type='checkbox' name='newnameAddressOn'><input name='newnameAddress' type='hidden' value='" . htmlprep($row["newnameAddress"]) . "'>" ; }
						print "</td>" ;
					print "</tr>" ;
					print "<tr class='even'>" ;
						print "<td>" ;
							print __($guid, "Home Address") ;
						print "</td>" ;
						print "<td>" ;
							print $row["homeAddress"] ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($row["homeAddress"]!=$row["newhomeAddress"]) {
								$style="style='color: #ff0000'" ;
							}
							print "<span $style>" ;
							print $row["newhomeAddress"] ;
						print "</td>" ;
						print "<td>" ;
							if ($row["homeAddress"]!=$row["newhomeAddress"]) { print "<input checked type='checkbox' name='newhomeAddressOn'><input name='newhomeAddress' type='hidden' value='" . htmlprep($row["newhomeAddress"]) . "'>" ; }
						print "</td>" ;
					print "</tr>" ;
					print "<tr class='odd'>" ;
						print "<td>" ;
							print __($guid, "Home Address (District)") ;
						print "</td>" ;
						print "<td>" ;
							print $row["homeAddressDistrict"] ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($row["homeAddressDistrict"]!=$row["newhomeAddressDistrict"]) {
								$style="style='color: #ff0000'" ;
							}
							print "<span $style>" ;
							print $row["newhomeAddressDistrict"] ;
						print "</td>" ;
						print "<td>" ;
							if ($row["homeAddressDistrict"]!=$row["newhomeAddressDistrict"]) { print "<input checked type='checkbox' name='newhomeAddressDistrictOn'><input name='newhomeAddressDistrict' type='hidden' value='" . htmlprep($row["newhomeAddressDistrict"]) . "'>" ; }
						print "</td>" ;
					print "</tr>" ;
					print "<tr class='even'>" ;
						print "<td>" ;
							print __($guid, "Home Address (Country)") ;
						print "</td>" ;
						print "<td>" ;
							print $row["homeAddressCountry"] ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($row["homeAddressCountry"]!=$row["newhomeAddressCountry"]) {
								$style="style='color: #ff0000'" ;
							}
							print "<span $style>" ;
							print $row["newhomeAddressCountry"] ;
						print "</td>" ;
						print "<td>" ;
							if ($row["homeAddressCountry"]!=$row["newhomeAddressCountry"]) { print "<input checked type='checkbox' name='newhomeAddressCountryOn'><input name='newhomeAddressCountry' type='hidden' value='" . htmlprep($row["newhomeAddressCountry"]) . "'>" ; }
						print "</td>" ;
					print "</tr>" ;
					print "<tr>" ;
						print "<td>" ;
							print __($guid, "Home Language - Primary") ;
						print "</td>" ;
						print "<td>" ;
							print $row["languageHomePrimary"] ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($row["languageHomePrimary"]!=$row["newlanguageHomePrimary"]) {
								$style="style='color: #ff0000'" ;
							}
							print "<span $style>" ;
							print $row["newlanguageHomePrimary"] ;
						print "</td>" ;
						print "<td>" ;
							if ($row["languageHomePrimary"]!=$row["newlanguageHomePrimary"]) { print "<input checked type='checkbox' name='newlanguageHomePrimaryOn'><input name='newlanguageHomePrimary' type='hidden' value='" . htmlprep($row["newlanguageHomePrimary"]) . "'>" ; }
						print "</td>" ;
					print "</tr>" ;
					print "<tr>" ;
						print "<td>" ;
							print __($guid, "Home Language - Secondary") ;
						print "</td>" ;
						print "<td>" ;
							print $row["languageHomeSecondary"] ;
						print "</td>" ;
						print "<td>" ;
							$style="" ;
							if ($row["languageHomeSecondary"]!=$row["newlanguageHomeSecondary"]) {
								$style="style='color: #ff0000'" ;
							}
							print "<span $style>" ;
							print $row["newlanguageHomeSecondary"] ;
						print "</td>" ;
						print "<td>" ;
							if ($row["languageHomeSecondary"]!=$row["newlanguageHomeSecondary"]) { print "<input checked type='checkbox' name='newlanguageHomeSecondaryOn'><input name='newlanguageHomeSecondary' type='hidden' value='" . htmlprep($row["newlanguageHomeSecondary"]) . "'>" ; }
						print "</td>" ;
					print "</tr>" ;
					print "<tr>" ;
							print "<td class='right' colspan=4>" ;
								print "<input name='gibbonFamilyID' type='hidden' value='" . $row["gibbonFamilyID"] . "'>" ;
								print "<input name='address' type='hidden' value='" . $_GET["q"] . "'>" ;
								print "<input type='submit' value='Submit'>" ;
							print "</td>" ;
						print "</tr>" ;
				print "</table>" ;
				?>
			</form>
			<?php
		}
	}
}
?>