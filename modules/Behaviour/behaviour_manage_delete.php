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

if (isActionAccessible($guid, $connection2, "/modules/Behaviour/behaviour_manage_delete.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Get action with highest precendence
	$highestAction=getHighestGroupedAction($guid, $_GET["q"], $connection2) ;
	if ($highestAction==FALSE) {
		print "<div class='error'>" ;
		print "The highest grouped action cannot be determined." ;
		print "</div>" ;
	}
	else {
		//Proceed!
		print "<div class='trail'>" ;
		print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>Home</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Behaviour/behaviour_manage.php'>Manage Behaviour Records</a> > </div><div class='trailEnd'>Delete Record</div>" ;
		print "</div>" ;
		
		if (isset($_GET["deleteReturn"])) { $deleteReturn=$_GET["deleteReturn"] ; } else { $deleteReturn="" ; }
		$deleteReturnMessage ="" ;
		$class="error" ;
		if (!($deleteReturn=="")) {
			if ($deleteReturn=="fail0") {
				$deleteReturnMessage ="Your request failed because you do not have access to this action." ;	
			}
			else if ($deleteReturn=="fail1") {
				$deleteReturnMessage ="Your request failed because your inputs were invalid." ;	
			}
			else if ($deleteReturn=="fail2") {
				$deleteReturnMessage ="Your request failed due to a database error." ;	
			}
			else if ($deleteReturn=="fail3") {
				$deleteReturnMessage ="Your request failed because your inputs were invalid." ;	
			}
			print "<div class='$class'>" ;
				print $deleteReturnMessage;
			print "</div>" ;
		} 
		
		//Check if school year specified
		$gibbonBehaviourID=$_GET["gibbonBehaviourID"];
		if ($gibbonBehaviourID=="") {
			print "<div class='error'>" ;
				print "You have not specified an activity." ;
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
					print "The selected behaviour record does not exist, or you do not have access to it." ;
				print "</div>" ;
			}
			else {
				//Let's go!
				$row=$result->fetch() ;
				
				if ($_GET["gibbonPersonID"]!="" OR $_GET["gibbonRollGroupID"]!="" OR $_GET["gibbonYearGroupID"]!="" OR $_GET["type"]!="") {
					print "<div class='linkTop'>" ;
						print "<a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/Behaviour/behaviour_manage.php&gibbonPersonID=" . $_GET["gibbonPersonID"] . "&gibbonRollGroupID=" . $_GET["gibbonRollGroupID"] . "&gibbonYearGroupID=" . $_GET["gibbonYearGroupID"] . "&type=" .$_GET["type"] . "'>Back to Search Results</a>" ;
					print "</div>" ;
				}
				?>
				<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/behaviour_manage_deleteProcess.php?gibbonBehaviourID=$gibbonBehaviourID&gibbonPersonID=" . $_GET["gibbonPersonID"] . "&gibbonRollGroupID=" . $_GET["gibbonRollGroupID"] . "&gibbonYearGroupID=" . $_GET["gibbonYearGroupID"] . "&type=" .$_GET["type"] ?>">
					<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
						<tr>
							<td> 
								<b>Are you sure you want to delete this record?</b><br/>
								<span style="font-size: 90%; color: #cc0000"><i>This operation cannot be undone, and may lead to loss of vital data in your system.<br/>PROCEED WITH CAUTION!</i></span>
							</td>
							<td class="right">
								
							</td>
						</tr>
						<tr>
							<td> 
								<input name="viewBy" id="viewBy" value="<? print $viewBy ?>" type="hidden">
								<input name="date" id="date" value="<? print $date ?>" type="hidden">
								<input name="gibbonBehaviourID" id="gibbonBehaviourID" value="<? print $gibbonBehaviourID ?>" type="hidden">
								<input type="hidden" name="address" value="<? print $_SESSION[$guid]["address"] ?>">
								<input type="submit" value="Yes">
							</td>
							<td class="right">
								
							</td>
						</tr>
					</table>
				</form>
				<?
			}
		}
	}
}
?>