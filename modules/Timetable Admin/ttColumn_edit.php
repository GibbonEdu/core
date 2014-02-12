<?
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

if (isActionAccessible($guid, $connection2, "/modules/Timetable Admin/ttColumn_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/ttColumn.php'>Manage Columns</a> > </div><div class='trailEnd'>Edit Column</div>" ;
	print "</div>" ;
	
	if (isset($_GET["updateReturn"])) { $updateReturn=$_GET["updateReturn"] ; } else { $updateReturn="" ; }
	$updateReturnMessage ="" ;
	$class="error" ;
	if (!($updateReturn=="")) {
		if ($updateReturn=="fail0") {
			$updateReturnMessage ="Your request failed because you do not have access to this action." ;	
		}
		else if ($updateReturn=="fail1") {
			$updateReturnMessage ="Your request failed because your inputs were invalid." ;	
		}
		else if ($updateReturn=="fail2") {
			$updateReturnMessage ="Your request failed due to a database error." ;	
		}
		else if ($updateReturn=="fail3") {
			$updateReturnMessage ="Your request failed because your inputs were invalid." ;	
		}
		else if ($updateReturn=="fail4") {
			$updateReturnMessage ="Your request failed because your inputs were invalid." ;	
		}
		else if ($updateReturn=="success0") {
			$updateReturnMessage ="Your request was completed successfully." ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $updateReturnMessage;
		print "</div>" ;
	} 
	
	if (isset($_GET["deleteReturn"])) { $deleteReturn=$_GET["deleteReturn"] ; } else { $deleteReturn="" ; }
	$deleteReturnMessage ="" ;
	$class="error" ;
	if (!($deleteReturn=="")) {
		if ($deleteReturn=="success0") {
			$deleteReturnMessage ="Your request was successful." ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $deleteReturnMessage;
		print "</div>" ;
	} 
	
	//Check if school year specified
	$gibbonTTColumnID=$_GET["gibbonTTColumnID"] ;
	if ($gibbonTTColumnID=="") {
		print "<div class='error'>" ;
			print "You have not specified a timetable." ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonTTColumnID"=>$gibbonTTColumnID); 
			$sql="SELECT * FROM gibbonTTColumn WHERE gibbonTTColumnID=:gibbonTTColumnID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print "The specified column cannot be found." ;
			print "</div>" ;
		}
		else {
			//Let's go!
			$row=$result->fetch() ;
			?>
			<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/ttColumn_editProcess.php?gibbonTTColumnID=$gibbonTTColumnID" ?>">
				<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
					<tr>
						<td> 
							<b>Name *</b><br/>
							<span style="font-size: 90%"><i>Must be unique for this school year.</i></span>
						</td>
						<td class="right">
							<input name="name" id="name" maxlength=30 value="<? print htmlPrep($row["name"]) ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var name=new LiveValidation('name');
								name.add(Validate.Presence);
							 </script>
						</td>
					</tr>
					<tr>
						<td> 
							<b>Short Name *</b><br/>
							<span style="font-size: 90%"><i></i></span>
						</td>
						<td class="right">
							<input name="nameShort" id="nameShort" maxlength=12 value="<? print htmlPrep($row["nameShort"]) ?>" type="text" style="width: 300px">
							<script type="text/javascript">
								var nameShort=new LiveValidation('nameShort');
								nameShort.add(Validate.Presence);
							 </script>
						</td>
					</tr>
					<tr>
						<td>
							<span style="font-size: 90%"><i>* denotes a required field</i></span>
						</td>
						<td class="right">
							<input name="gibbonTTColumnID" id="gibbonTTColumnID" value="<? print $_GET["gibbonTTColumnID"] ?>" type="hidden">
							<input type="hidden" name="address" value="<? print $_SESSION[$guid]["address"] ?>">
							<input type="submit" value="Submit">
						</td>
					</tr>
				</table>
			</form>
			<?
			
			print "<h2>" ;
			print "Edit Column Rows" ;
			print "</h2>" ;
			
			try {
				$data=array("gibbonTTColumnID"=>$gibbonTTColumnID); 
				$sql="SELECT * FROM gibbonTTColumnRow WHERE gibbonTTColumnID=:gibbonTTColumnID ORDER BY timeStart, name" ; 
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}

			print "<div class='linkTop'>" ;
			print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/ttColumn_edit_row_add.php&gibbonTTColumnID=$gibbonTTColumnID'><img title='New' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.gif'/></a>" ;
			print "</div>" ;
			
			if ($result->rowCount()<1) {
				print "<div class='error'>" ;
				print "There are no records to display." ;
				print "</div>" ;
			}
			else {
				print "<table cellspacing='0' style='width: 100%'>" ;
					print "<tr class='head'>" ;
						print "<th>" ;
							print "Name" ;
						print "</th>" ;
						print "<th>" ;
							print "Short Name" ;
						print "</th>" ;
						print "<th>" ;
							print "Time" ;
						print "</th>" ;
						print "<th>" ;
							print "Type" ;
						print "</th>" ;
						print "<th>" ;
							print "Actions" ;
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
						
						//COLOR ROW BY STATUS!
						print "<tr class=$rowNum>" ;
							print "<td>" ;
								print $row["name"] ;
							print "</td>" ;
							print "<td>" ;
								print $row["nameShort"] ;
							print "</td>" ;
							print "<td>" ;
								print substr($row["timeStart"],0,-3) . " - " . substr($row["timeEnd"],0, -3) ;
							print "</td>" ;
							print "<td>" ;
								print $row["type"] ;
							print "</td>" ;
							print "<td>" ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/ttColumn_edit_row_edit.php&gibbonTTColumnRowID=" . $row["gibbonTTColumnRowID"] . "&gibbonTTColumnID=$gibbonTTColumnID'><img title='Edit' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/ttColumn_edit_row_delete.php&gibbonTTColumnRowID=" . $row["gibbonTTColumnRowID"] . "&gibbonTTColumnID=$gibbonTTColumnID'><img title='Delete' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a> " ;
							print "</td>" ;
						print "</tr>" ;
						
						$count++ ;
					}
				print "</table>" ;
				
			}
		}
	}
}
?>