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

if (isActionAccessible($guid, $connection2, "/modules/School Admin/daysOfWeek_manage.php")==FALSE) {
	//Acess denied
	print "<div class='error'>" ;
		print "You do not have access to this action." ;
	print "</div>" ;
}
else {
	//Proceed!
	print "<div class='trail'>" ;
	print "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . _("Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . getModuleName($_GET["q"]) . "</a> > </div><div class='trailEnd'>Manage Days of the Week</div>" ;
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
	
	try {
		$data=array(); 
		$sql="SELECT * FROM gibbonDaysOfWeek WHERE name='Monday' OR name='Tuesday' OR name='Wednesday' OR name='Thursday' OR name='Friday' OR name='Saturday' OR name='Sunday' ORDER BY sequenceNumber" ;
		$result=$connection2->prepare($sql);
		$result->execute($data);
	}
	catch(PDOException $e) { 
		print "<div class='error'>" . $e->getMessage() . "</div>" ; 
	}

	if ($result->rowCount()!=7) {
		print "<div class='error'>" ;
			print "There is a problem with your database information for school days." ;
		print "</div>" ;
	}
	else {
		//Let's go!
		?>
		<form method="post" action="<? print $_SESSION[$guid]["absoluteURL"] . "/modules/" . $_SESSION[$guid]["module"] . "/daysOfWeek_manageProcess.php"?>">
			<table class='smallIntBorder' cellspacing='0' style="width: 100%">	
			<?
			while($row=$result->fetch()) {
				?>
				<tr class='break'>
					<td colspan=2> 
						<h3><? print $row["name"] . " (" . $row["nameShort"] . ")" ?></h3>
					</td>
				</tr>
				<input name="<?print $row["name"]?>sequenceNumber" id="<?print $row["name"]?>sequenceNumber" maxlength=2 value="<? print $row["sequenceNumber"] ?>" type="hidden" style="width: 300px">
				<tr>
					<td> 
						<b>School Day *</b>
					</td>
					<td class="right">
						<select style="width: 302px" name="<?print $row["name"]?>schoolDay" id="<?print $row["name"]?>schoolDay">
							<?
							if ($row["schoolDay"]=="Y") {
								print "<option selected value='Y'>Yes</option>" ;
								print "<option value='N'>No</option>" ;
							}
							else {
								print "<option value='Y'>Yes</option>" ;
								print "<option selected value='N'>No</option>" ;
							}
							?>				
						</select>
					</td>
				</tr>
				<tr>
					<td> 
						<b>School Opens</b>
					</td>
					<td class="right">
						<select style="width:100px" name="<?print $row["name"]?>schoolOpenM" id="<?print $row["name"]?>schoolOpenM">
							<?
							print "<option value='Minutes'>Minutes</option>" ;
							for ($i=0;$i<60;$i++) {
								$iPrint=$i;
								if (strlen($i)==1) {
									$iPrint="0" . $i ;
								}
							
								if (substr($row["schoolOpen"],3,2)==$i AND $row["schoolOpen"]!=NULL) {
									print "<option selected value='" . $iPrint . "'>" . $iPrint . "</option>" ;
								}
								else {
									print "<option value='" . $iPrint . "'>" . $iPrint . "</option>" ;
								}
							}
							?>				
						</select>
						<select style="width:100px" name="<?print $row["name"]?>schoolOpenH" id="<?print $row["name"]?>schoolOpenH">
							<?
							print "<option value='Hours'>Hours</option>" ;
							for ($i=0;$i<24;$i++) {
								$iPrint=$i;
								if (strlen($i)==1) {
									$iPrint="0" . $i ;
								}
							
								if (substr($row["schoolOpen"],0,2)==$i AND $row["schoolOpen"]!=NULL) {
									print "<option selected value='" . $iPrint . "'>" . $iPrint . "</option>" ;
								}
								else {
									print "<option value='" . $iPrint . "'>" . $iPrint . "</option>" ;
								}
							}
							?>				
						</select>
					</td>
				</tr>
				<tr>
					<td> 
						<b>School Starts</b>
					</td>
					<td class="right">
						<select style="width:100px" name="<?print $row["name"]?>schoolStartM" id="<?print $row["name"]?>schoolStartM">
							<?
							print "<option value='Minutes'>Minutes</option>" ;
							for ($i=0;$i<60;$i++) {
								$iPrint=$i;
								if (strlen($i)==1) {
									$iPrint="0" . $i ;
								}
							
								if (substr($row["schoolStart"],3,2)==$i AND $row["schoolStart"]!=NULL) {
									print "<option selected value='" . $iPrint . "'>" . $iPrint . "</option>" ;
								}
								else {
									print "<option value='" . $iPrint . "'>" . $iPrint . "</option>" ;
								}
							}
							?>				
						</select>
						<select style="width:100px" name="<?print $row["name"]?>schoolStartH" id="<?print $row["name"]?>schoolStartH">
							<?
							print "<option value='Hours'>Hours</option>" ;
							for ($i=0;$i<24;$i++) {
								$iPrint=$i;
								if (strlen($i)==1) {
									$iPrint="0" . $i ;
								}
							
								if (substr($row["schoolStart"],0,2)==$i AND $row["schoolStart"]!=NULL) {
									print "<option selected value='" . $iPrint . "'>" . $iPrint . "</option>" ;
								}
								else {
									print "<option value='" . $iPrint . "'>" . $iPrint . "</option>" ;
								}
							}
							?>				
						</select>
					</td>
				</tr>
				<tr>
					<td> 
						<b>School Ends</b>
					</td>
					<td class="right">
						<select style="width:100px" name="<?print $row["name"]?>schoolEndM" id="<?print $row["name"]?>schoolEndM">
							<?
							print "<option value='Minutes'>Minutes</option>" ;
							for ($i=0;$i<60;$i++) {
								$iPrint=$i;
								if (strlen($i)==1) {
									$iPrint="0" . $i ;
								}
							
								if (substr($row["schoolEnd"],3,2)==$i AND $row["schoolEnd"]!=NULL) {
									print "<option selected value='" . $iPrint . "'>" . $iPrint . "</option>" ;
								}
								else {
									print "<option value='" . $iPrint . "'>" . $iPrint . "</option>" ;
								}
							}
							?>				
						</select>
						<select style="width:100px" name="<?print $row["name"]?>schoolEndH" id="<?print $row["name"]?>schoolEndH">
							<?
							print "<option value='Hours'>Hours</option>" ;
							for ($i=0;$i<24;$i++) {
								$iPrint=$i;
								if (strlen($i)==1) {
									$iPrint="0" . $i ;
								}
							
								if (substr($row["schoolEnd"],0,2)==$i AND $row["schoolEnd"]!=NULL) {
									print "<option selected value='" . $iPrint . "'>" . $iPrint . "</option>" ;
								}
								else {
									print "<option value='" . $iPrint . "'>" . $iPrint . "</option>" ;
								}
							}
							?>				
						</select>
					</td>
				</tr>
				<tr>
					<td> 
						<b>School Closes</b>
					</td>
					<td class="right">
						<select style="width:100px" name="<?print $row["name"]?>schoolCloseM" id="<?print $row["name"]?>schoolCloseM">
							<?
							print "<option value='Minutes'>Minutes</option>" ;
							for ($i=0;$i<60;$i++) {
								$iPrint=$i;
								if (strlen($i)==1) {
									$iPrint="0" . $i ;
								}
							
								if (substr($row["schoolClose"],3,2)==$i AND $row["schoolClose"]!=NULL) {
									print "<option selected value='" . $iPrint . "'>" . $iPrint . "</option>" ;
								}
								else {
									print "<option value='" . $iPrint . "'>" . $iPrint . "</option>" ;
								}
							}
							?>				
						</select>
						<select style="width:100px" name="<?print $row["name"]?>schoolCloseH" id="<?print $row["name"]?>schoolCloseH">
							<?
							print "<option value='Hours'>Hours</option>" ;
							for ($i=0;$i<24;$i++) {
								$iPrint=$i;
								if (strlen($i)==1) {
									$iPrint="0" . $i ;
								}
							
								if (substr($row["schoolClose"],0,2)==$i AND $row["schoolClose"]!=NULL) {
									print "<option selected value='" . $iPrint . "'>" . $iPrint . "</option>" ;
								}
								else {
									print "<option value='" . $iPrint . "'>" . $iPrint . "</option>" ;
								}
							}
							?>				
						</select>
					</td>
				</tr>
			
				<?
			}
			?>
				<tr>
					<td>
						<span style="font-size: 90%"><i>* <? print _("denotes a required field") ; ?></i></span>
					</td>
					<td class="right">
						<input type="hidden" name="address" value="<? print $_SESSION[$guid]["address"] ?>">
						<input type="submit" value="<? print _("Submit") ; ?>">
					</td>
				</tr>
			</table>
		</form>
		<?
	}
}
?>