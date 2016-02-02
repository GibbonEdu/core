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

if (isActionAccessible($guid, $connection2, "/modules/Individual Needs/iep_view_myChildren.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	$entryCount=0; 
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . _('View Individual Education Plans') . "</div>" ;
	print "</div>" ;

	print "<p>" ;
		print _("This section allows you to view individual education plans, where they exist, for children within your family.") . "<br/>" ;
	print "</p>" ;
	
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
		while ($row=$result->fetch()) {
			try {
				$dataChild=array("gibbonFamilyID"=>$row["gibbonFamilyID"], "gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
				$sqlChild="SELECT * FROM gibbonFamilyChild JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonFamilyID=:gibbonFamilyID AND gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID ORDER BY surname, preferredName " ;
				$resultChild=$connection2->prepare($sqlChild);
				$resultChild->execute($dataChild);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			while ($rowChild=$resultChild->fetch()) {
				$select="" ;
				if (isset($_GET["search"])) {
					if ($rowChild["gibbonPersonID"]==$_GET["search"]) {
						$select="selected" ;
					}
				}
				
				$options=$options . "<option $select value='" . $rowChild["gibbonPersonID"] . "'>" . formatName("", $rowChild["preferredName"], $rowChild["surname"], "Student", true). "</option>" ;
				$gibbonPersonID[$count]=$rowChild["gibbonPersonID"] ;
				$count++ ;
			}
		}
		
		if ($count==0) {
			print "<div class='error'>" ;
			print _("Access denied.") ;
			print "</div>" ;
		}
		else if ($count==1) {
			$_GET["search"]=$gibbonPersonID[0] ;
		}
		else {
			print "<h2>" ;
			print "Choose Student" ;
			print "</h2>" ;
			
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
							<select name="search" id="search" style="width: 302px">
								<option value=""></value>
								<?php print $options ; ?> 
							</select>
						</td>
					</tr>
					<tr>
						<td colspan=2 class="right">
							<input type="hidden" name="q" value="/modules/<?php print $_SESSION[$guid]["module"] ?>/iep_view_myChildren.php">
							<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
							<?php
							print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/iep_view_myChildren.php'>" . _('Clear Search') . "</a>" ;
							?>
							<input type="submit" value="<?php print _("Submit") ; ?>">
						</td>
					</tr>
				</table>
			</form>
			<?php
		}
		
		$gibbonPersonID=NULL ;
		if (isset($_GET["search"])) {
			$gibbonPersonID=$_GET["search"] ;
		}
		
		if ($gibbonPersonID!="" AND $count>0) {
			//Confirm access to this student
			try {
				$dataChild=array("gibbonPersonID"=>$gibbonPersonID, "gibbonPersonID2"=>$_SESSION[$guid]["gibbonPersonID"]); 
				$sqlChild="SELECT * FROM gibbonFamilyChild JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID) JOIN gibbonPerson ON (gibbonFamilyChild.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='" . date("Y-m-d") . "') AND (dateEnd IS NULL  OR dateEnd>='" . date("Y-m-d") . "') AND gibbonFamilyChild.gibbonPersonID=:gibbonPersonID AND gibbonFamilyAdult.gibbonPersonID=:gibbonPersonID2 AND childDataAccess='Y'" ;
				$resultChild=$connection2->prepare($sqlChild);
				$resultChild->execute($dataChild);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			if ($resultChild->rowCount()<1) {
				print "<div class='error'>" ;
				print _("The selected record does not exist, or you do not have access to it.") ;
				print "</div>" ;
			}
			else {
				$rowChild=$resultChild->fetch() ;

				try {
					$data=array("gibbonPersonID"=>$gibbonPersonID); 
					$sql="SELECT * FROM gibbonIN WHERE gibbonPersonID=:gibbonPersonID" ; 
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}

				if ($result->rowCount()!=1) {
					print "<h3>" ;
					print _("View") ;
					print "</h3>" ;
	
					print "<div class='error'>" ;
					print _("There are no records to display.") ;
					print "</div>" ;
				}
				else {
					print "<h3>" ;
					print _("View") ;
					print "</h3>" ;

					$row=$result->fetch() ;
					?>	
					<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
						<tr>
							<td colspan=2 style='padding-top: 25px'> 
								<span style='font-weight: bold; font-size: 135%'><?php print _('Targets') ?></span><br/>
								<?php
								print "<p>" . $row["targets"] . "</p>" ;
								?>
							</td>
						</tr>
						<tr>
							<td colspan=2> 
								<span style='font-weight: bold; font-size: 135%'><?php print _('Teaching Strategies') ?></span><br/>
								<?php
								print "<p>" . $row["strategies"] . "</p>" ;
								?>
							</td>
						</tr>
						<tr>
							<td colspan=2 style='padding-top: 25px'> 
								<span style='font-weight: bold; font-size: 135%'><?php print _('Notes & Review') ?></span><br/>
								<?php
								print "<p>" . $row["notes"] . "</p>" ;
								?>
							</td>
						</tr>
					</table>
					<?php
				}
			}
		}		
	}
}
?>