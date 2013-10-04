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

session_start() ;

//Module includes
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, "/modules/School Admin/externalAssessments_manage_edit.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/externalAssessments_manage.php'>Manage External Assessments</a> > </div><div class='trailEnd'>Edit External Assessment</div>" ;
	print "</div>" ;
	
	$updateReturn = $_GET["updateReturn"] ;
	$updateReturnMessage ="" ;
	$class="error" ;
	if (!($updateReturn=="")) {
		if ($updateReturn=="fail0") {
			$updateReturnMessage ="Update failed because you do not have access to this action." ;	
		}
		else if ($updateReturn=="fail1") {
			$updateReturnMessage ="Update failed because a required parameter was not set." ;	
		}
		else if ($updateReturn=="fail2") {
			$updateReturnMessage ="Update failed due to a database error." ;	
		}
		else if ($updateReturn=="fail3") {
			$updateReturnMessage ="Update failed because your inputs were invalid." ;	
		}
		else if ($updateReturn=="fail4") {
			$updateReturnMessage ="Update failed some values need to be unique but were not." ;	
		}
		else if ($updateReturn=="success0") {
			$updateReturnMessage ="Update was successful." ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $updateReturnMessage;
		print "</div>" ;
	} 
	
	$deleteReturn = $_GET["deleteReturn"] ;
	$deleteReturnMessage ="" ;
	$class="error" ;
	if (!($deleteReturn=="")) {
		if ($deleteReturn=="success0") {
			$deleteReturnMessage ="Delete was successful." ;	
			$class="success" ;
		}
		print "<div class='$class'>" ;
			print $deleteReturnMessage;
		print "</div>" ;
	} 
	
	//Check if school year specified
	$gibbonExternalAssessmentID=$_GET["gibbonExternalAssessmentID"] ;
	if ($gibbonExternalAssessmentID=="") {
		print "<div class='error'>" ;
			print "You have not specified a grade scale." ;
		print "</div>" ;
	}
	else {
		try {
			$data=array("gibbonExternalAssessmentID"=>$gibbonExternalAssessmentID); 
			$sql="SELECT * FROM gibbonExternalAssessment WHERE gibbonExternalAssessmentID=:gibbonExternalAssessmentID" ;
			$result=$connection2->prepare($sql);
			$result->execute($data);
		}
		catch(PDOException $e) { 
			print "<div class='error'>" . $e->getMessage() . "</div>" ; 
		}
		
		if ($result->rowCount()!=1) {
			print "<div class='error'>" ;
				print "The specified grade scale cannot be found." ;
			print "</div>" ;
		}
		else {
			//Let's go!
			$row=$result->fetch() ;
			?>
			<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/externalAssessments_manage_editProcess.php?gibbonExternalAssessmentID=$gibbonExternalAssessmentID" ?>">
			<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
				<tr>
					<td> 
						<b>Name *</b><br/>
						<span style="font-size: 90%"><i>Must be unique for this school year.</i></span>
					</td>
					<td class="right">
						<input name="name" id="name" maxlength=50 value="<? print htmlPrep($row["name"]) ?>" type="text" style="width: 300px">
						<script type="text/javascript">
							var name = new LiveValidation('name');
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
						<input name="nameShort" id="nameShort" maxlength=10 value="<? print htmlPrep($row["nameShort"]) ?>" type="text" style="width: 300px">
						<script type="text/javascript">
							var nameShort = new LiveValidation('nameShort');
							nameShort.add(Validate.Presence);
						 </script>
					</td>
				</tr>
				<tr>
					<td> 
						<b>Description *</b><br/>
						<span style="font-size: 90%"><i>Brief description of how scale is used.</i></span>
					</td>
					<td class="right">
						<input name="description" id="description" maxlength=50 value="<? print $row["description"] ?>" type="text" style="width: 300px">
						<script type="text/javascript">
							var description = new LiveValidation('description');
							description.add(Validate.Presence);
						 </script>
					</td>
				</tr>
				<tr>
					<td> 
						<b>Active *</b><br/>
						<span style="font-size: 90%"><i>Is this scale in active use?</i></span>
					</td>
					<td class="right">
						<select name="active" id="active" style="width: 302px">
							<option <? if ($row["active"]=="Y") { print "selected" ; } ?> value="Y">Y</option>
							<option <? if ($row["active"]=="N") { print "selected" ; } ?> value="N">N</option>
						</select>
					</td>
				</tr>
				<tr>
					<td>
						<span style="font-size: 90%"><i>* denotes a required field</i></span>
					</td>
					<td class="right">
						<input name="gibbonExternalAssessmentID" id="gibbonExternalAssessmentID" value="<? print $_GET["gibbonExternalAssessmentID"] ?>" type="hidden">
						<input type="hidden" name="address" value="<? print $_SESSION[$guid]["address"] ?>">
						<input type="reset" value="Reset"> <input type="submit" value="Submit">
					</td>
				</tr>
			</table>
			</form>
			<?
			
			print "<h2>" ;
			print "Edit Fields" ;
			print "</h2>" ;
			
			try {
				$data=array("gibbonExternalAssessmentID"=>$gibbonExternalAssessmentID); 
				$sql="SELECT * FROM gibbonExternalAssessmentField WHERE gibbonExternalAssessmentID=:gibbonExternalAssessmentID ORDER BY category, `order`" ; 
				$result=$connection2->prepare($sql);
				$result->execute($data);
			}
			catch(PDOException $e) { 
				print "<div class='error'>" . $e->getMessage() . "</div>" ; 
			}

			print "<div class='linkTop'>" ;
			print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/externalAssessments_manage_edit_field_add.php&gibbonExternalAssessmentID=$gibbonExternalAssessmentID'><img title='New' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/page_new.gif'/></a>" ;
			print "</div>" ;
			
			if ($result->rowCount()<1) {
				print "<div class='error'>" ;
				print "There are no grades to display." ;
				print "</div>" ;
			}
			else {
				print "<table cellspacing='0' style='width: 100%'>" ;
					print "<tr class='head'>" ;
						print "<th>" ;
							print "Name" ;
						print "</th>" ;
						print "<th>" ;
							print "Category" ;
						print "</th>" ;
						print "<th>" ;
							print "Order" ;
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
								print $row["category"] ;
							print "</td>" ;
							print "<td>" ;
								print $row["order"] ;
							print "</td>" ;
							print "<td>" ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/externalAssessments_manage_edit_field_edit.php&gibbonExternalAssessmentFieldID=" . $row["gibbonExternalAssessmentFieldID"] . "&gibbonExternalAssessmentID=$gibbonExternalAssessmentID'><img title='Edit' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/config.png'/></a> " ;
								print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . $_SESSION[$guid]["module"] . "/externalAssessments_manage_edit_field_delete.php&gibbonExternalAssessmentFieldID=" . $row["gibbonExternalAssessmentFieldID"] . "&gibbonExternalAssessmentID=$gibbonExternalAssessmentID'><img title='Delete' src='./themes/" . $_SESSION[$guid]["gibbonThemeName"] . "/img/garbage.png'/></a> " ;
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