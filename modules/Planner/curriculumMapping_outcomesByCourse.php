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

if (isActionAccessible($guid, $connection2, "/modules/Planner/curriculumMapping_outcomesByCourse.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print _("You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . _(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . _('Outcomes By Course') . "</div>" ;
	print "</div>" ;
	print "<p>" ;
	print _("This view gives an overview of which whole school and learning area outcomes are covered by classes in a given course, allowing for curriculum mapping by outcome and course.") ;
	print "</p>" ;
	
	print "<h2>" ;
	print _("Choose Course") ;
	print "</h2>" ;
	
	$gibbonCourseID=NULL ;
	if (isset($_GET["gibbonCourseID"])) {
		$gibbonCourseID=$_GET["gibbonCourseID"] ;
	}
	?>
	
	<form method="get" action="<?php print $_SESSION[$guid]["absoluteURL"]?>/index.php">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<tr>
				<td style='width: 275px'> 
					<b><?php print _('Course') ?> *</b><br/>
				</td>
				<td class="right">
					<select style="width: 302px" name="gibbonCourseID">
						<?php
						print "<option value=''></option>" ;
						$currentDepartment="" ;
						$lastDepartment="" ;
						
						try {
							$dataSelect=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"]); 
							$sqlSelect="SELECT gibbonCourse.*, gibbonDepartment.name AS department FROM gibbonCourse LEFT JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND NOT gibbonYearGroupIDList='' ORDER BY department, nameShort" ;
							$resultSelect=$connection2->prepare($sqlSelect);
							$resultSelect->execute($dataSelect);
						}
						catch(PDOException $e) { }
						while ($rowSelect=$resultSelect->fetch()) {
							$currentDepartment=$rowSelect["department"] ;
							if (($currentDepartment!=$lastDepartment) AND $currentDepartment!="") {
								print "<optgroup label='--" . $currentDepartment . "--'>" ;
							}
							
							if ($gibbonCourseID==$rowSelect["gibbonCourseID"]) {
								print "<option selected value='" . $rowSelect["gibbonCourseID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
							}
							else {
								print "<option value='" . $rowSelect["gibbonCourseID"] . "'>" . htmlPrep($rowSelect["name"]) . "</option>" ;
							}
							$lastDepartment=$rowSelect["department"] ;
						}
						?>				
					</select>
				</td>
			</tr>
			<tr>
				<td colspan=2 class="right">
					<input type="hidden" name="q" value="/modules/<?php print $_SESSION[$guid]["module"] ?>/curriculumMapping_outcomesByCourse.php">
					<input type="submit" value="<?php print _("Submit") ; ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php
	
	if ($gibbonCourseID!="") {
		print "<h2>" ;
		print _("Outcomes") ;
		print "</h2>" ;
		
		//Check course exists
		try {
			$data=array("gibbonSchoolYearID"=>$_SESSION[$guid]["gibbonSchoolYearID"], "gibbonCourseID"=>$gibbonCourseID); 
			$sql="SELECT gibbonCourse.*, gibbonDepartment.name AS department FROM gibbonCourse LEFT JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID AND NOT gibbonYearGroupIDList='' AND gibbonCourseID=:gibbonCourseID ORDER BY department, nameShort" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print _("The selected record does not exist, or you do not have access to it.") ;
			print "</div>" ;
		}
		else {
			$row=$result->fetch() ;
			//Get classes in this course
			try {
				$dataClasses=array("gibbonCourseID"=>$gibbonCourseID); 
				$sqlClasses="SELECT * FROM gibbonCourseClass WHERE gibbonCourseID=:gibbonCourseID ORDER BY name" ;
				$resultClasses=$connection2->prepare($sqlClasses);
				$resultClasses->execute($dataClasses);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}
			
			if ($resultClasses->rowCount()<1) {
				print "<div class='error'>" ;
					print _("The selected record does not exist, or you do not have access to it.") ;
				print "</div>" ;
			}
			else {
				$classCount=$resultClasses->rowCount() ;
				$classes=$resultClasses->fetchAll() ;
				
				//GET ALL OUTCOMES MET IN THIS COURSE, AND STORE IN AN ARRAY FOR DB-EFFICIENT USE IN TABLE
				try {
					$dataOutcomes=array("gibbonCourseID1"=>$gibbonCourseID, "gibbonCourseID2"=>$gibbonCourseID); 
					$sqlOutcomes="(SELECT 'Unit' AS type, gibbonCourseClass.gibbonCourseClassID, gibbonOutcome.* FROM gibbonOutcome JOIN gibbonUnitOutcome ON (gibbonUnitOutcome.gibbonOutcomeID=gibbonOutcome.gibbonOutcomeID) JOIN gibbonUnit ON (gibbonUnitOutcome.gibbonUnitID=gibbonUnit.gibbonUnitID) JOIN gibbonUnitClass ON (gibbonUnitClass.gibbonUnitID=gibbonUnit.gibbonUnitID) JOIN gibbonCourseClass ON (gibbonUnitClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonCourseClass.gibbonCourseID=:gibbonCourseID1 AND gibbonOutcome.active='Y' AND running='Y')
					UNION ALL
					(SELECT 'Working Block' AS type, gibbonCourseClass.gibbonCourseClassID, gibbonOutcome.* FROM gibbonOutcome JOIN gibbonUnitClassBlock ON (gibbonUnitClassBlock.gibbonOutcomeIDList LIKE concat('%', gibbonOutcome.gibbonOutcomeID, '%')) JOIN gibbonUnitClass ON (gibbonUnitClassBlock.gibbonUnitClassID=gibbonUnitClass.gibbonUnitClassID) JOIN gibbonCourseClass ON (gibbonUnitClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonCourseClass.gibbonCourseID=:gibbonCourseID2 AND gibbonOutcome.active='Y' AND running='Y')
					UNION ALL
					(SELECT 'Planner Entry' AS type, gibbonCourseClass.gibbonCourseClassID, gibbonOutcome.* FROM gibbonOutcome JOIN gibbonPlannerEntryOutcome ON (gibbonPlannerEntryOutcome.gibbonOutcomeID=gibbonOutcome.gibbonOutcomeID) JOIN gibbonPlannerEntry ON (gibbonPlannerEntryOutcome.gibbonPlannerEntryID=gibbonPlannerEntry.gibbonPlannerEntryID) JOIN gibbonCourseClass ON (gibbonPlannerEntry.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID) WHERE gibbonCourseClass.gibbonCourseID=:gibbonCourseID2 AND gibbonOutcome.active='Y')" ;
					$resultOutcomes=$connection2->prepare($sqlOutcomes);
					$resultOutcomes->execute($dataOutcomes);
				}
				catch(PDOException $e) { 
					print "<div class='error'>" . $e->getMessage() . "</div>" ; 
				}
				$allOutcomes=$resultOutcomes->fetchAll() ;
				
				print "<table class='smallIntBorder' cellspacing='0' style='width: 100%'>" ;
					print "<tr class='head'>" ;
						print "<th>" ;
							print _("Category") ;
						print "</th>" ;
						print "<th>" ;
							print _("Outcome") ;
						print "</th>" ;
						foreach ($classes AS $class) {
							print "<th colspan=2>" ;
								print $row["nameShort"] . "." . _($class["nameShort"]) ;
							print "</th>" ;
						}
					print "</tr>" ;
					print "<tr class='head'>" ;
						print "<th>" ;
							
						print "</th>" ;
						print "<th>" ;
							
						print "</th>" ;
						foreach ($classes AS $class) {
							print "<th>" ;
								print "<span style='font-style: italic; font-size: 85%'>" . _('Unit') . "</span>" ;
							print "</th>" ;
							print "<th>" ;
								print "<span style='font-style: italic; font-size: 85%'>" . _('Lesson') . "</span>" ;
							print "</th>" ;
						}
					print "</tr>" ;
					
					//Prep where for year group matching of outcomes to course
					$where="" ;
					$yearGroups=explode(",", $row["gibbonYearGroupIDList"]) ;
					foreach ($yearGroups AS $yearGroup) {
						$where.=" AND gibbonYearGroupIDList LIKE concat('%', $yearGroup, '%')" ; 
					}
					
					//SCHOOL OUTCOMES
					print "<tr class='break'>" ;
						print "<td colspan=" . (($classCount*2)+2) . ">" ; 
							print "<h4>" . _('School Outcomes') . "</h4>" ;
						print "</td>" ;
					print "</tr>" ;
					try {
						$dataOutcomes=array(); 
						$sqlOutcomes="SELECT * FROM gibbonOutcome WHERE scope='School' AND active='Y' $where ORDER BY category, name" ;
						$resultOutcomes=$connection2->prepare($sqlOutcomes);
						$resultOutcomes->execute($dataOutcomes);
					}
					catch(PDOException $e) { 
						print "<tr>" ;
							print "<td colspan=" . (($classCount*2)+2) . ">" ; 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							print "</td>" ;
						print "</tr>" ;
					}
					
					if ($resultOutcomes->rowCount()<1) {
						print "<tr>" ;
							print "<td colspan=" . (($classCount*2)+2) . ">" ; 
								print "<div class='error'>" . _("There are no records to display.") . "</div>" ; 
							print "</td>" ;
						print "</tr>" ;
					}
					else {
						$count=0;
						$rowNum="odd" ;
						while ($rowOutcomes=$resultOutcomes->fetch()) {
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
									print $rowOutcomes["category"] ;
								print "</td>" ;
								print "<td>" ;
									print $rowOutcomes["name"] ;
								print "</td>" ;
								
								//Deal with outcomes
								foreach ($classes AS $class) {
									print "<td>" ;
										$outcomeCount=0 ;
										foreach ($allOutcomes AS $anOutcome) {
											if ($anOutcome["type"]=="Unit" AND $anOutcome["scope"]=="School" AND $anOutcome["gibbonOutcomeID"]==$rowOutcomes["gibbonOutcomeID"] AND $class["gibbonCourseClassID"]==$anOutcome["gibbonCourseClassID"]) {
												$outcomeCount++ ;
											}
										}
										if ($outcomeCount<1) {
											print "<img src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconCross.png'/> " ;
										}
										else {
											print "<img src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconTick.png'/> x " . $outcomeCount ;
										}
									print "</td>" ;
									print "<td>" ;
										$outcomeCount=0 ;
										foreach ($allOutcomes AS $anOutcome) {
											if ($anOutcome["type"]!="Unit" AND $anOutcome["scope"]=="School" AND $anOutcome["gibbonOutcomeID"]==$rowOutcomes["gibbonOutcomeID"] AND $class["gibbonCourseClassID"]==$anOutcome["gibbonCourseClassID"]) {
												$outcomeCount++ ;
											}
										}
										if ($outcomeCount<1) {
											print "<img src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconCross.png'/> " ;
										}
										else {
											print "<img src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconTick.png'/> x " . $outcomeCount ;
										}
									print "</td>" ;
								}
					
							print "</tr>" ;
						}
					}
					
					//LEARNING AREA OUTCOMES
					print "<tr class='break'>" ;
						print "<td colspan=" . (($classCount*2)+2) . ">" ; 
							print "<h4>" . sprintf(_('%1$s Outcomes'), $row["department"]) . "</h4>" ;
						print "</td>" ;
					print "</tr>" ;
					try {
						$dataOutcomes=array("gibbonDepartmentID"=>$row["gibbonDepartmentID"]); 
						$sqlOutcomes="SELECT * FROM gibbonOutcome WHERE scope='Learning Area' AND gibbonDepartmentID=:gibbonDepartmentID AND active='Y' $where ORDER BY category, name" ;
						$resultOutcomes=$connection2->prepare($sqlOutcomes);
						$resultOutcomes->execute($dataOutcomes);
					}
					catch(PDOException $e) { 
						print "<tr>" ;
							print "<td colspan=" . (($classCount*2)+2) . ">" ; 
								print "<div class='error'>" . $e->getMessage() . "</div>" ; 
							print "</td>" ;
						print "</tr>" ;
					}
					
					if ($resultOutcomes->rowCount()<1) {
						print "<tr>" ;
							print "<td colspan=" . (($classCount*2)+2) . ">" ; 
								print "<div class='error'>" . _("There are no records to display.") . "</div>" ; 
							print "</td>" ;
						print "</tr>" ;
					}
					else {
						$count=0;
						$rowNum="odd" ;
						while ($rowOutcomes=$resultOutcomes->fetch()) {
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
									print $rowOutcomes["category"] ;
								print "</td>" ;
								print "<td>" ;
									print $rowOutcomes["name"] ;
								print "</td>" ;
								
								//Deal with outcomes
								foreach ($classes AS $class) {
									print "<td>" ;
										$outcomeCount=0 ;
										foreach ($allOutcomes AS $anOutcome) {
											if ($anOutcome["type"]=="Unit" AND $anOutcome["scope"]=="Learning Area" AND $anOutcome["gibbonOutcomeID"]==$rowOutcomes["gibbonOutcomeID"] AND $class["gibbonCourseClassID"]==$anOutcome["gibbonCourseClassID"]) {
												$outcomeCount++ ;
											}
										}
										if ($outcomeCount<1) {
											print "<img src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconCross.png'/> " ;
										}
										else {
											print "<img src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconTick.png'/> x " . $outcomeCount ;
										}
									print "</td>" ;
									print "<td>" ;
										$outcomeCount=0 ;
										foreach ($allOutcomes AS $anOutcome) {
											if ($anOutcome["type"]!="Unit" AND $anOutcome["scope"]=="Learning Area" AND $anOutcome["gibbonOutcomeID"]==$rowOutcomes["gibbonOutcomeID"] AND $class["gibbonCourseClassID"]==$anOutcome["gibbonCourseClassID"]) {
												$outcomeCount++ ;
											}
										}
										if ($outcomeCount<1) {
											print "<img src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconCross.png'/> " ;
										}
										else {
											print "<img src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/iconTick.png'/> x " . $outcomeCount ;
										}
									print "</td>" ;
								}
					
							print "</tr>" ;
						}
					}
				print "</table>" ;
			}
		}
	}
}
?>