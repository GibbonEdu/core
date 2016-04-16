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

if (isActionAccessible($guid, $connection2, "/modules/School Admin/department_manage.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Manage Departments') . "</div>" ;
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
			$updateReturnMessage=__($guid, "One or more of the fields in your request failed due to a database error.") ;	
		}
		else if ($updateReturn=="fail3") {
			$updateReturnMessage=__($guid, "Your request failed because your inputs were invalid.") ;	
		}
		else if ($updateReturn=="success0") {
			$updateReturnMessage=__($guid, "Your request was completed successfully.") ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $updateReturnMessage;
		print "</div>" ;
	} 
	
	print "<h3>" ;
		print __($guid, "Department Access") ;
	print "</h3>" ;
	
	?>
	<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/department_manageProcess.php" ?>">
		<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<tr>
				<?php
				try {
					$data=array(); 
					$sql="SELECT * FROM gibbonSetting WHERE scope='Departments' AND name='makeDepartmentsPublic'" ;
					$result=$connection2->prepare($sql);
					$result->execute($data);
				}
				catch(PDOException $e) { }
				$row=$result->fetch() ;
				?>
				<td style='width: 275px'> 
					<b><?php print __($guid, $row["nameDisplay"]) ?> *</b><br/>
					<span style="font-size: 90%"><i><?php if ($row["description"]!="") { print __($guid, $row["description"]) ; } ?></i></span>
				</td>
				<td class="right">
					<select name="<?php print $row["name"] ?>" id="<?php print $row["name"] ?>" style="width: 302px">
						<option <?php if ($row["value"]=="N") {print "selected ";} ?>value="N"><?php print __($guid, 'No') ?></option>
						<option <?php if ($row["value"]=="Y") {print "selected ";} ?>value="Y"><?php print __($guid, 'Yes') ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<td>
					<span style="font-size: 90%"><i>* <?php print __($guid, "denotes a required field") ; ?></i></span>
				</td>
				<td class="right">
					<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
					<input type="submit" value="<?php print __($guid, "Submit") ; ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php
	
	print "<h3>" ;
		print __($guid, "Departments") ;
	print "</h3>" ;
	
	try {
		$data=array(); 
		$sql="SELECT * FROM gibbonDepartment ORDER BY name" ; 
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}
	
	print "<div class='linkTop'>" ;
	print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/department_manage_add.php'>" .  __($guid, 'Add') . "<img style='margin-left: 5px' title='" . __($guid, 'Add') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.png'/></a>" ;
	print "</div>" ;
	
	if ($result->rowCount()<1) {
		print "<div class='error'>" ;
		print __($guid, "There are no records to display.") ;
		print "</div>" ;
	}
	else {
		print "<table cellspacing='0' style='width: 100%'>" ;
			print "<tr class='head'>" ;
				print "<th>" ;
					print __($guid, "Name") ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Type") ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Short Name") ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Staff") ;
				print "</th>" ;
				print "<th>" ;
					print __($guid, "Actions") ;
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

				//COLOR ROW BY STATUS!
				print "<tr class=$rowNum>" ;
					print "<td>" ;
						print $row["name"] ;
					print "</td>" ;
					print "<td>" ;
						print $row["type"] ;
					print "</td>" ;
					print "<td>" ;
						print $row["nameShort"] ;
					print "</td>" ;
					print "<td>" ;
						try {
							$dataCoord=array("gibbonDepartmentID"=>$row["gibbonDepartmentID"]); 
							$sqlCoord="SELECT preferredName, surname FROM gibbonDepartmentStaff JOIN gibbonPerson ON (gibbonDepartmentStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND gibbonDepartmentID=:gibbonDepartmentID ORDER BY surname, preferredName" ;
							$resultCoord=$connection2->prepare($sqlCoord);
							$resultCoord->execute($dataCoord);
						}
						catch(PDOException $e) { 
							print "<div class='error'>" . $e->getMessage() . "</div>" ; 
						}
						if ($resultCoord->rowCount()>0) {
							while ($rowCoord=$resultCoord->fetch()) {
								print formatName("", $rowCoord["preferredName"], $rowCoord["surname"], "Staff", true, true) . "<br/>" ; 
							}
						}
						else {
							print "<i>" . __($guid, 'None') . "</i>" ;
						}
					print "</td>" ;
					print "<td>" ;
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/department_manage_edit.php&gibbonDepartmentID=" . $row["gibbonDepartmentID"] . "'><img title='" . __($guid, 'Edit') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/department_manage_delete.php&gibbonDepartmentID=" . $row["gibbonDepartmentID"] . "'><img title='" . __($guid, 'Delete') . "' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a> " ;
					print "</td>" ;
				print "</tr>" ;
			}
		print "</table>" ;
	}
}	
?>