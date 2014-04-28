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

if (isActionAccessible($guid, $connection2, "/modules/Students/student_view.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Get action with highest precendence
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print _("The highest grouped action cannot be determined.") ;
		print "</div>" ;
	}
	else {
		if ($highestAction=="View Student Profile_myChildren") {
			print "<div class='trail'>" ;
			print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>View Student Profiles</div>" ;
			print "</div>" ;
				
			//Test data access field for permission
			try {
				$data=array("gibbonPersonID"=>$_SESSION[$guid]["gibbonPersonID"]); 
				$sql="SELECT * FROM gibbonFamilyAdult WHERE gibbonPersonID=:gibbonPersonID AND childDataAccess='Y'" ;
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			if ($result->rowCount()<1) {
				print "<div class='error'>" ;
				print _("Access denied.") ;
				print "</div>" ;
			}
			else {
				//Get child list
				$count=0 ;
				$options="" ;
				$students=array() ;
				while ($row=$result->fetch()) {
					try {
						$dataChild=array("gibbonFamilyID"=>$row["gibbonFamilyID"]); 
						$sqlChild="SELECT gibbonPerson.gibbonPersonID, surname, preferredName, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonFamilyID=:gibbonFamilyID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=" . $_SESSION[$guid]["gibbonSchoolYearID"] . " ORDER BY surname, preferredName " ;
						$resultChild=$connection2->prepare($sqlChild);
						$resultChild->execute($dataChild);
					}
					catch(PDOException $e) { 
						print "<div class='error'>" . $e->getMessage() . "</div>" ; 
					}
					while ($rowChild=$resultChild->fetch()) {
						$students[$count][0]=$rowChild["surname"] ;
						$students[$count][1]=$rowChild["preferredName"] ;
						$students[$count][2]=$rowChild["yearGroup"] ;
						$students[$count][3]=$rowChild["rollGroup"] ;
						$students[$count][4]=$rowChild["gibbonPersonID"] ;
						
						$count++ ;
					}
				}
				
				if ($count==0) {
					print "<div class='error'>" ;
					print _("Access denied.") ;
					print "</div>" ;
				}
				else {
					print "<table cellspacing='0' style='width: 100%'>" ;
						print "<tr class='head'>" ;
							print "<th>" ;
								print _("Name") ;
							print "</th>" ;
							print "<th>" ;
								print _("Year Group") ;
							print "</th>" ;
							print "<th>" ;
								print _("Roll Group") ;
							print "</th>" ;
							print "<th>" ;
								print _("Actions") ;
							print "</th>" ;
						print "</tr>" ;
						
						for ($i=0;$i<$count;$i++) {
							if ($i%2==0) {
								$rowNum="even" ;
							}
							else {
								$rowNum="odd" ;
							}
							
							//COLOR ROW BY STATUS!
							print "<tr class=$rowNum>" ;
								print "<td>" ;
									print formatName("", $students[$i][1], $students[$i][0], "Student", true) ;
								print "</td>" ;
								print "<td>" ;
									print $students[$i][2] ;
								print "</td>" ;
								print "<td>" ;
									print $students[$i][3] ;
								print "</td>" ;
								print "<td>" ;
									print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/student_view_details.php&gibbonPersonID=" . $students[$i][4] . "'><img title='View User Details' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a> " ;
								print "</td>" ;
							print "</tr>" ;
						}
						
						
					print "</table>" ;
				}
			}
		}
		else {
			//Proceed!
			print "<div class='trail'>" ;
			print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>View Student Profiles</div>" ;
			print "</div>" ;
			
			print "<h2>" ;
			print "Search" ;
			print "</h2>" ;
			
			$gibbonPersonID=NULL;
			if (isset($_GET["gibbonPersonID"])) {
				$gibbonPersonID=$_GET["gibbonPersonID"] ;
			}
			$search=NULL;
			if (isset($_GET["search"])) {
				$search=$_GET["search"] ;
			}
			
		
			?>
			<form method="get" action="<?php print $_SESSION[$guid]["absoluteURL"]?>/index.php">
				<table class='noIntBorder' cellspacing='0' style="width: 100%">	
					<tr><td style="width: 30%"></td><td></td></tr>
					<tr>
						<td> 
							<b><?php print _('Search For') ?></b><br/>
							<span style="font-size: 90%"><i>Preferred, surname, username.</i></span>
						</td>
						<td class="right">
							<input name="search" id="search" maxlength=20 value="<?php print $search ?>" type="text" style="width: 300px">
						</td>
					</tr>
					<tr>
						<td colspan=2 class="right">
							<input type="hidden" name="q" value="/modules/<?php print $_SESSION[$guid]["module"] ?>/student_view.php">
							<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
							<?php
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/student_view.php'>" . _('Clear Search') . "</a>" ;
							?>
							<input type="submit" value="<?php print _("Submit") ; ?>">
						</td>
					</tr>
				</table>
			</form>
			<?php
			
			print "<h2>" ;
			print "Choose A Student" ;
			print "</h2>" ;
			
			//Set pagination variable
			$page=1 ; if (isset($_GET["page"])) { $page=$_GET["page"] ; }
			if ((!is_numeric($page)) OR $page<1) {
				$page=1 ;
			}
			
			
			try {
				$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
				$sql="SELECT gibbonPerson.gibbonPersonID, gibbonStudentEnrolmentID, surname, preferredName, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup FROM gibbonPerson, gibbonStudentEnrolment, gibbonYearGroup, gibbonRollGroup WHERE (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) AND (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) AND (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') ORDER BY surname, preferredName" ; 
				if ($search!="") {
					$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "search1"=>"%$search%", "search2"=>"%$search%", "search3"=>"%$search%"); 
					$sql="SELECT gibbonPerson.gibbonPersonID, gibbonStudentEnrolmentID, surname, preferredName, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup FROM gibbonPerson, gibbonStudentEnrolment, gibbonYearGroup, gibbonRollGroup WHERE (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) AND (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) AND (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND (preferredName LIKE :search1 OR surname LIKE :search2 OR username LIKE :search3) AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') ORDER BY surname, preferredName" ; 
				}
				$sqlPage=$sql . " LIMIT " . $_SESSION[$guid]["pagination"] . " OFFSET " . (($page-1)*$_SESSION[$guid]["pagination"]) ; 
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}

			if ($result->rowcount()<1) {
				print "<div class='error'>" ;
				print _("There are no records to display.") ;
				print "</div>" ;
			}
			else {
				if ($result->rowcount()>$_SESSION[$guid]["pagination"]) {
					printPagination($guid, $result->rowcount(), $page, $_SESSION[$guid]["pagination"], "top", "&search=$search") ;
				}
			
				print "<table cellspacing='0' style='width: 100%'>" ;
					print "<tr class='head'>" ;
						print "<th>" ;
							print _("Name") ;
						print "</th>" ;
						print "<th>" ;
							print _("Year Group") ;
						print "</th>" ;
						print "<th>" ;
							print _("Roll Group") ;
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
						
						//COLOR ROW BY STATUS!
						print "<tr class=$rowNum>" ;
							print "<td>" ;
								print formatName("", $row["preferredName"],$row["surname"], "Student", true) ;
							print "</td>" ;
							print "<td>" ;
								print $row["yearGroup"] ;
							print "</td>" ;
							print "<td>" ;
								print $row["rollGroup"] ;
							print "</td>" ;
							print "<td>" ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/student_view_details.php&gibbonPersonID=" . $row["gibbonPersonID"] . "&search=$search'><img title='View User Details' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/plus.png'/></a> " ;
							print "</td>" ;
						print "</tr>" ;
					}
				print "</table>" ;
				
				if ($result->rowcount()>$_SESSION[$guid]["pagination"]) {
					printPagination($guid, $result->rowcount(), $page, $_SESSION[$guid]["pagination"], "bottom", "search=$search") ;
				}
			}
		}
	}
}
?>