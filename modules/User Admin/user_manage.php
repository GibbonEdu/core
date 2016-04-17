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

if (isActionAccessible($guid, $connection2, "/modules/User Admin/user_manage.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Manage Users') . "</div>" ;
	print "</div>" ;
	
	if (isset($_GET["deleteReturn"])) { $deleteReturn=$_GET["deleteReturn"] ; } else { $deleteReturn="" ; }
	$deleteReturnMessage="" ;
	$class="error" ;
	if (!($deleteReturn=="")) {
		if ($deleteReturn=="success0") {
			$deleteReturnMessage=__($guid, "Your request was completed successfully.") ;		
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $deleteReturnMessage;
		print "</div>" ;
	} 
	
	//Set pagination variable
	$page=1 ; if (isset($_GET["page"])) { $page=$_GET["page"] ; }
	if ((!is_numeric($page)) OR $page<1) {
		$page=1 ;
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
					<span class="emphasis small"><?php print __($guid, 'Preferred, surname, username, email, phone number, vehicle registration') ?></span>
				</td>
				<td class="right">
					<input name="search" id="search" maxlength=20 value="<?php if (isset($_GET["search"])) { print $_GET["search"] ; } ?>" type="text" class="standardWidth">
				</td>
			</tr>
			<tr>
				<td colspan=2 class="right">
					<input type="hidden" name="q" value="/modules/<?php print $_SESSION[$guid]["module"] ?>/user_manage.php">
					<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
					<?php
					print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/user_manage.php'>" . __($guid, 'Clear Search') . "</a>" ;
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
	
	$search="" ;
	if (isset($_GET["search"])) {
		$search=$_GET["search"] ;
	}
	try {
		$data=array(); 
		$sql="SELECT * FROM gibbonPerson LEFT JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) ORDER BY surname, preferredName" ; 
		if ($search!="") {
			$data=array("search1"=>"%$search%", "search2"=>"%$search%", "search3"=>"%$search%", "search4"=>"%$search%", "search5"=>"%$search%", "search6"=>"%$search%", "search7"=>"%$search%", "search8"=>"%$search%", "search9"=>"%$search%", "search10"=>"%$search%"); 
			$sql="SELECT * FROM gibbonPerson LEFT JOIN gibbonRole ON (gibbonPerson.gibbonRoleIDPrimary=gibbonRole.gibbonRoleID) WHERE (preferredName LIKE :search1 OR surname LIKE :search2 OR username LIKE :search3 OR email LIKE :search4 OR emailAlternate LIKE :search5 OR phone1 LIKE :search6 OR phone2 LIKE :search7 OR phone3 LIKE :search8 OR phone4 LIKE :search9 OR vehicleRegistration LIKE :search10) ORDER BY surname, preferredName" ; 	
		}
		$sqlPage=$sql . " LIMIT " . $_SESSION[$guid]["pagination"] . " OFFSET " . (($page-1)*$_SESSION[$guid]["pagination"]) ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}
	
	//Build cache of families for use below
	$families=array() ;
	try {
		$dataFamily=array(); 
		$sqlFamily="(SELECT gibbonFamilyAdult.gibbonFamilyID, gibbonFamilyAdult.gibbonPersonID, 'adult' AS role, gibbonFamily.name, dob FROM gibbonFamily JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID)) UNION (SELECT gibbonFamilyChild.gibbonFamilyID, gibbonFamilyChild.gibbonPersonID, 'child' AS role, gibbonFamily.name, dob FROM gibbonFamily JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID)) ORDER BY gibbonFamilyID, role, dob DESC, gibbonPersonID" ; 
		$resultFamily=$connection2->prepare($sqlFamily);
		$resultFamily->execute($dataFamily);
	}
	catch(PDOException $e) { }
	$countFamily=0 ;
	while ($rowFamily=$resultFamily->fetch()) {
		$families[$countFamily][0]=$rowFamily["gibbonFamilyID"] ;
		$families[$countFamily][1]=$rowFamily["gibbonPersonID"] ;
		$families[$countFamily][2]=$rowFamily["role"] ;
		$families[$countFamily][3]=$rowFamily["name"] ;
		$countFamily++ ;
	}
	
	print "<div class='linkTop'>" ;
	print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/user_manage_add.php&search=$search'>" .  __($guid, 'Add') . "<img style='margin-left: 5px' title='" . __($guid, 'Add') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.png'/></a>" ;
	print "</div>" ;
	
	if ($result->rowCount()<1) {
		print "<div class='error'>" ;
		print __($guid, "There are no records to display.") ;
		print "</div>" ;
	}
	else {
		if ($result->rowCount()>$_SESSION[$guid]["pagination"]) {
			printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]["pagination"], "top", "search=$search") ;
		}
	
		print "<table cellspacing='0' style='width: 100%'>" ;
			print "<tr class='head'>" ;
				print "<th>" ;
					print __($guid, "Photo") ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Name") ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Status") ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Primary Role") ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Family") ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Username") ;
				print "</th>" ;
				print "<th style='width: 100px'>" ;
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
						print getUserPhoto($guid, $row["image_240"], 75) ;
					print "</td>" ;
					print "<td>" ;
						print formatName("", $row["preferredName"], $row["surname"], "Student", true) ;
					print "</td>" ;
					print "<td>" ;
						print $row["status"] ;
					print "</td>" ;
					print "<td>" ;
						if ($row["name"]!="") {
							print __($guid, $row["name"]) ;
						}
					print "</td>" ;
					print "<td>" ;
						$childCount=0 ;
						foreach ($families AS $family) {
							if ($family[1]==$row["gibbonPersonID"]) {
								if ($family[2]=="child") { //Link child to self
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $family[1] . "&search=&allStudents=on&sort=surname, preferredName&subpage=Family'>" . $family[3] . "</a><br/>" ;
								}
								else { //Link adult to eldest child in family
									foreach ($families AS $family2) {
										if ($family[0]==$family2[0] AND $family2[2]=="child" AND $childCount==0) {
											print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID=" . $family2[1] . "&search=&allStudents=on&sort=surname, preferredName&subpage=Family'>" . $family[3] . "</a><br/>" ;
											$childCount++ ;
										}
									}
								}
							}
						}
					print "</td>" ;
					print "<td>" ;
						print $row["username"] ;
					print "</td>" ;
					print "<td>" ;
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/user_manage_edit.php&gibbonPersonID=" . $row["gibbonPersonID"] . "&search=$search'><img title='" . __($guid, 'Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/user_manage_delete.php&gibbonPersonID=" . $row["gibbonPersonID"] . "&search=$search'><img title='" . __($guid, 'Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a>" ;
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/user_manage_password.php&gibbonPersonID=" . $row["gibbonPersonID"] . "&search=$search'><img title='" . __($guid, 'Change Password') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/key.png'/></a>" ;
					print "</td>" ;
				print "</tr>" ;
			}
		print "</table>" ;
		
		if ($result->rowCount()>$_SESSION[$guid]["pagination"]) {
			printPagination($guid, $result->rowCount(), $page, $_SESSION[$guid]["pagination"], "bottom", "search=$search") ;
		}
	}
}
?>