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

if (isActionAccessible($guid, $connection2, "/modules/Behaviour/behaviour_manage_delete.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print __($guid, "You do not have access to this action.") ;
	print "</div>" ;
}
else {
	//Get action with highest precendence
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print __($guid, "The highest grouped action cannot be determined.") ;
		print "</div>" ;
	}
	else {
		//Proceed!
		print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Behaviour/behaviour_manage.php'>" . __($guid, 'Manage Behaviour Records') . "</a> > </div><div class='trailEnd'>" . __($guid, 'Delete') . "</div>" ;
		print "</div>" ;

		if (isset($_GET["return"])) { returnProcess($guid, $_GET["return"], null, null); }
		
		//Check if school year specified
		$gibbonBehaviourID=$_GET["gibbonBehaviourID"];
		if ($gibbonBehaviourID=="") {
			print "<div class='error'>" ;
				print __($guid, "You have not specified one or more required parameters.") ;
			print "</div>" ;
		}
		else {
			try {
				$data=array("gibbonBehaviourID"=>$gibbonBehaviourID); 
				$sql="SELECT * FROM gibbonBehaviour WHERE gibbonBehaviourID=:gibbonBehaviourID" ;
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
				//Let's go!
				$row=$result->fetch() ;
				
				if ($_GET["gibbonPersonID"]!="" OR $_GET["gibbonRollGroupID"]!="" OR $_GET["gibbonYearGroupID"]!="" OR $_GET["type"]!="") {
					print "<div class='linkTop'>" ;
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Behaviour/behaviour_manage.php&gibbonPersonID=" . $_GET["gibbonPersonID"] . "&gibbonRollGroupID=" . $_GET["gibbonRollGroupID"] . "&gibbonYearGroupID=" . $_GET["gibbonYearGroupID"] . "&type=" .$_GET["type"] . "'>" . __($guid, 'Back to Search Results') . "</a>" ;
					print "</div>" ;
				}
				?>
				<form method="post" action="<?php print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/behaviour_manage_deleteProcess.php?gibbonBehaviourID=$gibbonBehaviourID&gibbonPersonID=" . $_GET["gibbonPersonID"] . "&gibbonRollGroupID=" . $_GET["gibbonRollGroupID"] . "&gibbonYearGroupID=" . $_GET["gibbonYearGroupID"] . "&type=" .$_GET["type"] ?>">
					<table class='smallIntBorder fullWidth' cellspacing='0'>	
						<tr>
							<td> 
								<b><?php print __($guid, 'Are you sure you want to delete this record?') ; ?></b><br/>
								<span style="font-size: 90%; color: #cc0000"><i><?php print __($guid, 'This operation cannot be undone, and may lead to loss of vital data in your system. PROCEED WITH CAUTION!') ; ?></span>
							</td>
							<td class="right">
								
							</td>
						</tr>
						<tr>
							<td> 
								<input name="viewBy" id="viewBy" value="<?php print $viewBy ?>" type="hidden">
								<input name="date" id="date" value="<?php print $date ?>" type="hidden">
								<input name="gibbonBehaviourID" id="gibbonBehaviourID" value="<?php print $gibbonBehaviourID ?>" type="hidden">
								<input type="hidden" name="address" value="<?php print $_SESSION[$guid]["address"] ?>">
								<input type="submit" value="<?php print __($guid, 'Yes') ; ?>">
							</td>
							<td class="right">
								
							</td>
						</tr>
					</table>
				</form>
				<?php
			}
		}
	}
}
?>